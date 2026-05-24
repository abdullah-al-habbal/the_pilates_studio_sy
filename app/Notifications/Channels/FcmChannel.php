<?php

declare(strict_types=1);

namespace App\Notifications\Channels;

use App\Models\PushNotificationLog;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Kreait\Firebase\Exception\MessagingException;
use Kreait\Firebase\Messaging\AndroidConfig;          // ← ADD
use Kreait\Firebase\Messaging\ApnsConfig;             // ← ADD
use Kreait\Firebase\Messaging\CloudMessage;
use Kreait\Firebase\Messaging\Notification as FirebaseNotification;
use Kreait\Laravel\Firebase\Facades\Firebase;
use Throwable;

final class FcmChannel
{
    public function send(mixed $notifiable, Notification $notification): void
    {
        $notificationClass = get_class($notification);
        $notificationId    = (string) Str::uuid();

        try {
            $tokens = $this->getTokens($notifiable);

            // FIX: log key aligned with actual class name for ELK traceability
            Log::info('FCM dispatch started', [
                'notification'    => $notificationClass,
                'notification_id' => $notificationId,
                'notifiable_type' => get_class($notifiable),
                'notifiable_id'   => $notifiable->getKey(),
                'tokens_count'    => count($tokens),
            ]);

            if (empty($tokens)) {
                Log::warning('FCM skipped: no tokens', [
                    'notification'    => $notificationClass,
                    'notification_id' => $notificationId,
                ]);
                return;
            }

            if (! method_exists($notification, 'toFcm')) {
                Log::error('FCM missing toFcm method', [
                    'notification'    => $notificationClass,
                    'notification_id' => $notificationId,
                ]);
                return;
            }

            $payload = $notification->toFcm($notifiable);

            if (! is_array($payload)) {
                Log::error('FCM invalid payload type', [
                    'notification'    => $notificationClass,
                    'notification_id' => $notificationId,
                ]);
                return;
            }

            if (! $this->isFirebaseConfigured()) {
                Log::error('FCM Firebase not configured', [
                    'notification'    => $notificationClass,
                    'notification_id' => $notificationId,
                ]);
                return;
            }

            $payload['data'] = array_merge(
                $payload['data'] ?? [],
                ['notification_id' => $notificationId]
            );

            $messaging = Firebase::messaging();

            foreach ($tokens as $token) {
                try {
                    $message   = $this->buildMessage($payload, $token);
                    $messageId = $messaging->send($message);

                    Log::info('FCM sent', [
                        'notification'    => $notificationClass,
                        'notification_id' => $notificationId,
                        'token'           => $token,
                        'message_id'      => $messageId,
                    ]);

                    $this->logSent($notifiable, $notification, $payload, $token, $notificationId);
                } catch (MessagingException $e) {
                    Log::error('FCM MessagingException', [
                        'notification' => $notificationClass,
                        'token'        => $token,
                        'error'        => $e->getMessage(),
                    ]);

                    if ($this->isInvalidTokenException($e)) {
                        $this->deleteToken($notifiable, $token);
                    }
                } catch (Throwable $e) {
                    Log::error('FCM unexpected token send failure', [
                        'notification'    => $notificationClass,
                        'notification_id' => $notificationId,
                        'notifiable_id'   => $notifiable->getKey(),
                        'token'           => $token,
                        'error'           => $e->getMessage(),
                        'trace'           => $e->getTraceAsString(),
                    ]);
                }
            }
        } catch (Throwable $e) {
            Log::critical('FCM channel fatal error', [
                'notification' => $notificationClass,
                'error'        => $e->getMessage(),
            ]);
        }
    }

    private function buildMessage(array $payload, string $token): CloudMessage
    {
        $title = $payload['title'] ?? 'Notification';
        $body  = $payload['body']  ?? '';
        $data  = array_map(static fn(mixed $v): string => (string) $v, $payload['data'] ?? []);

        $androidConfig = AndroidConfig::fromArray([
            'priority'     => 'high',
            'notification' => [
                'sound' => 'default',
            ],
        ]);

        $apnsConfig = ApnsConfig::fromArray([
            'payload' => [
                'aps' => [
                    'sound'             => 'default',
                    'badge'             => 1,
                    'content-available' => 1,
                ],
            ],
        ]);

        return CloudMessage::new()
            ->withToken($token)
            ->withNotification(FirebaseNotification::create($title, $body))
            ->withData($data)
            ->withAndroidConfig($androidConfig)
            ->withApnsConfig($apnsConfig)
            ->withHighestPossiblePriority();
    }
    
    private function logSent(
        mixed        $notifiable,
        Notification $notification,
        array        $payload,
        string       $token,
        string       $notificationId
    ): void {
        try {
            PushNotificationLog::create([
                'notifiable_type'    => $notifiable->getMorphClass(),
                'notifiable_id'      => $notifiable->getKey(),
                'notification_class' => get_class($notification),
                'data'               => [
                    'payload'         => $payload,
                    'token'           => $token,
                    'notification_id' => $notificationId,
                ],
                'channel' => 'fcm',
                'sent_at' => now(),
            ]);
        } catch (Throwable $e) {
            Log::error('FCM push log failed', [
                'error' => $e->getMessage(),
            ]);
        }
    }

    private function getTokens(mixed $notifiable): array
    {
        try {
            if (! empty($notifiable->fcm_token)) {
                return [(string) $notifiable->fcm_token];
            }

            if (method_exists($notifiable, 'fcmTokens')) {
                return $notifiable->fcmTokens()
                    ->pluck('token')
                    ->filter()
                    ->unique()
                    ->values()
                    ->all();
            }

            return [];
        } catch (Throwable $e) {
            Log::error('FCM token retrieval failed', [
                'error' => $e->getMessage(),
            ]);
            return [];
        }
    }

    private function isFirebaseConfigured(): bool
    {
        $default     = config('firebase.default');
        $credentials = config("firebase.projects.{$default}.credentials");

        return ! blank($credentials);
    }

    private function isInvalidTokenException(MessagingException $e): bool
    {
        $msg = strtolower($e->getMessage());

        return str_contains($msg, 'not-registered')
            || str_contains($msg, 'invalid-registration')
            || str_contains($msg, 'unregistered');
    }

    private function deleteToken(mixed $notifiable, string $token): void
    {
        try {
            if (method_exists($notifiable, 'fcmTokens')) {
                $notifiable->fcmTokens()
                    ->where('token', $token)
                    ->delete();
                return;
            }

            if ($notifiable->fcm_token === $token) {
                $notifiable->forceFill(['fcm_token' => null])->save();
            }
        } catch (Throwable $e) {
            Log::warning('FCM token deletion failed', [
                'error' => $e->getMessage(),
            ]);
        }
    }
}

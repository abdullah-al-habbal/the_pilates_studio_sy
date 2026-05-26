<?php

declare(strict_types=1);

namespace App\Notifications\Channels;

use App\Notifications\Fcm\FcmConfigValidator;
use App\Notifications\Fcm\FcmInvalidTokenDetector;
use App\Notifications\Fcm\FcmLogSaver;
use App\Notifications\Fcm\FcmMessageBuilder;
use App\Notifications\Fcm\FcmSender;
use App\Notifications\Fcm\FcmTokenDeleter;
use App\Notifications\Fcm\FcmTokenGetter;
use App\Services\Log\LoggingService;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Str;
use Kreait\Firebase\Exception\MessagingException;
use Throwable;

final class FcmChannel
{
    public function __construct(
        private readonly LoggingService $loggingService,
        private readonly FcmMessageBuilder $builder,
        private readonly FcmSender $sender,
        private readonly FcmLogSaver $logSaver,
        private readonly FcmTokenGetter $tokenGetter,
        private readonly FcmConfigValidator $validator,
        private readonly FcmInvalidTokenDetector $invalidDetector,
        private readonly FcmTokenDeleter $tokenDeleter,
    ) {}

    public function send(mixed $notifiable, Notification $notification): void
    {
        $notificationClass = get_class($notification);
        $notificationId    = (string) Str::uuid();

        try {
            $tokens = $this->tokenGetter->getTokens($notifiable);

            $this->loggingService->info('FCM dispatch started', [
                'notification'    => $notificationClass,
                'notification_id' => $notificationId,
                'notifiable_type' => get_class($notifiable),
                'notifiable_id'   => $notifiable->getKey(),
                'tokens_count'    => count($tokens),
            ]);

            if (empty($tokens)) {
                $this->loggingService->warning('FCM skipped: no tokens', [
                    'notification'    => $notificationClass,
                    'notification_id' => $notificationId,
                ]);
                return;
            }

            if (! method_exists($notification, 'toFcm')) {
                $this->loggingService->error('FCM missing toFcm method', [
                    'notification'    => $notificationClass,
                    'notification_id' => $notificationId,
                ]);
                return;
            }

            $payload = $notification->toFcm($notifiable);
            if (! is_array($payload)) {
                $this->loggingService->error('FCM invalid payload type', [
                    'notification'    => $notificationClass,
                    'notification_id' => $notificationId,
                ]);
                return;
            }

            if (! $this->validator->isConfigured()) {
                $this->loggingService->error('FCM Firebase not configured', [
                    'notification'    => $notificationClass,
                    'notification_id' => $notificationId,
                ]);
                return;
            }

            $payload['data'] = array_merge($payload['data'] ?? [], ['notification_id' => $notificationId]);

            foreach ($tokens as $token) {
                try {
                    $message   = $this->builder->build($payload, $token);
                    $messageId = $this->sender->send($message);

                    $this->loggingService->info('FCM sent', [
                        'notification'    => $notificationClass,
                        'notification_id' => $notificationId,
                        'token'           => $token,
                        'message_id'      => $messageId,
                    ]);

                    try {
                        $this->logSaver->saveSent(
                            $notifiable,
                            $notificationClass,
                            $payload,
                            $token,
                            $notificationId,
                            $messageId,
                        );
                    } catch (Throwable $e) {
                        $this->loggingService->error('FCM push log failed', ['error' => $e->getMessage()]);
                    }
                } catch (MessagingException $e) {
                    $this->loggingService->error('FCM MessagingException', [
                        'notification' => $notificationClass,
                        'token'        => $token,
                        'error'        => $e->getMessage(),
                    ]);

                    if ($this->invalidDetector->isInvalidTokenException($e)) {
                        try {
                            $this->tokenDeleter->delete($notifiable, $token);
                        } catch (Throwable $e) {
                            $this->loggingService->warning('FCM token deletion failed', ['error' => $e->getMessage()]);
                        }
                    }
                } catch (Throwable $e) {
                    $this->loggingService->error('FCM unexpected token send failure', [
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
            $this->loggingService->critical('FCM channel fatal error', [
                'notification' => $notificationClass,
                'error'        => $e->getMessage(),
            ]);
        }
    }
}

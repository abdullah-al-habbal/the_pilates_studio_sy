<?php

namespace App\Notifications;

use App\Models\ClassSession;
use App\Models\NotificationTemplate;
use App\Notifications\Channels\FcmChannel;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class SessionReminderNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public function __construct(public ClassSession $session) {}

    public function backoff(): array
    {
        return [60, 300, 900];
    }

    public function via($notifiable): array
    {
        return [
            FcmChannel::class,
            'database',
        ];
    }

    public function toFcm($notifiable): array
    {
        return $this->buildPayload($notifiable);
    }

    public function toDatabase($notifiable): array
    {
        $payload = $this->buildPayload($notifiable);

        return [
            'title' => $payload['title'],
            'message' => $payload['body'],
            'session_id' => $this->session->id,
        ];
    }

    protected function buildPayload(object $notifiable): array
    {
        $locale = $notifiable->preferred_locale ?? app()->getLocale();

        $class = $this->session->class;
        $classTitle = $class->getTranslation('title', $locale);
        $instructor = $class->instructor?->fullname ?? 'Instructor';

        $date = $this->session->date->format('M d, Y');
        $time = $this->session->start_time;

        $template = NotificationTemplate::query()
            ->where('key', 'session_reminder')
            ->where('is_active', true)
            ->first();

        $title = $template
            ? $template->getResolvedTitle($locale)
            : 'Class Reminder';

        $defaultBody = sprintf(
            'Your class %s with %s starts at %s on %s',
            $classTitle,
            $instructor,
            $time,
            $date
        );

        $body = $template
            ? str_replace(
                [':class', ':instructor', ':time', ':date'],
                [$classTitle, $instructor, $time, $date],
                $template->getResolvedBody($locale)
            )
            : $defaultBody;

        return [
            'title' => $title,
            'body' => $body,
            'data' => [
                'session_id' => (string) $this->session->id,
                'type' => 'session_reminder',
            ],
        ];
    }
}
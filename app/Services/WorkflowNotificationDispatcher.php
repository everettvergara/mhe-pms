<?php

namespace App\Services;

use Illuminate\Notifications\Notification;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

class WorkflowNotificationDispatcher
{
    /**
     * @param  Collection<int, \App\Models\User>  $recipients
     * @param  array<string, mixed>  $logMeta
     */
    public function notifyUsers(Collection $recipients, Notification $notification, string $context, array $logMeta = []): void
    {
        if ($recipients->isEmpty()) {
            Log::warning('No recipients found for workflow notification.', [
                'context' => $context,
                ...$logMeta,
            ]);

            return;
        }

        foreach ($recipients as $recipient) {
            $email = trim((string) $recipient->email);

            if ($email === '') {
                Log::info('Email skipped: recipient has no email address.', [
                    'context' => $context,
                    'user_id' => $recipient->id,
                    ...$logMeta,
                ]);

                continue;
            }

            $recipient->notify($notification);
        }
    }
}

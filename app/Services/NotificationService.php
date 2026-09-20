<?php

declare(strict_types=1);

namespace App\Services;

use App\Events\NotificationSent;
use App\Mail\GenericMail;
use App\Mail\JobVacancyNotificationMail;
use App\Models\Notification;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Throwable;

class NotificationService
{
    public function send(
        string|int $userId,
        string $type,
        string $title,
        string $message,
        array $data = [],
        bool $sendEmail = true
    ): bool {
        try {
            $notification = Notification::create([
                'id' => (string) Str::uuid(),
                'user_id' => $userId,
                'type' => $type,
                'title' => $title,
                'message' => $message,
                'data' => $data,
            ]);

            event(new NotificationSent((string) $userId, $notification->toArray()));

            if ($sendEmail) {
                try {
                    $user = User::find($userId);
                    if ($user && ! empty($user->email)) {
                        $mailable = $type === 'job_vacancy'
                            ? new JobVacancyNotificationMail($user, $title, $message, $data)
                            : new GenericMail($title, $message);

                        Mail::to($user->email)->queue($mailable);
                    }
                } catch (Throwable $mailEx) {
                    Log::error("Failed to queue notification email for user ID: $userId | ".$mailEx->getMessage());
                }
            }

            return true;
        } catch (Throwable $th) {
            Log::error("Failed to send notification for user ID: $userId | ".$th->getMessage());

            return false;
        }
    }

    public function sendMultiple(
        array $userIds,
        string $type,
        string $title,
        string $message,
        array $data = [],
        bool $sendEmail = true
    ): void {
        if (empty($userIds)) {
            return;
        }

        try {
            $now = now();
            $records = [];
            $broadcastPayloads = [];

            foreach ($userIds as $userId) {
                $id = (string) Str::uuid();
                $records[] = [
                    'id' => $id,
                    'user_id' => $userId,
                    'type' => $type,
                    'title' => $title,
                    'message' => $message,
                    'data' => json_encode($data),
                    'created_at' => $now,
                    'updated_at' => $now,
                ];

                $broadcastPayloads[] = [
                    'userId' => (string) $userId,
                    'data' => [
                        'id' => $id,
                        'user_id' => $userId,
                        'type' => $type,
                        'title' => $title,
                        'message' => $message,
                        'data' => $data,
                        'created_at' => $now->toISOString(),
                    ],
                ];
            }

            Notification::insert($records);

            $hasBroadcaster = config('broadcasting.default') !== 'null'
                && (! empty(config('broadcasting.connections.reverb.key')) || ! empty(config('broadcasting.connections.pusher.key')));

            if ($hasBroadcaster) {
                foreach ($broadcastPayloads as $payload) {
                    event(new NotificationSent($payload['userId'], $payload['data']));
                }
            }

            if ($sendEmail) {
                try {
                    $users = User::whereIn('id', $userIds)
                        ->where('is_active', true)
                        ->whereNotNull('email')
                        ->get(['id', 'full_name', 'email']);

                    foreach ($users as $user) {
                        if (! empty($user->email)) {
                            $mailable = $type === 'job_vacancy'
                                ? new JobVacancyNotificationMail($user, $title, $message, $data)
                                : new GenericMail($title, $message);

                            Mail::to($user->email)->queue($mailable);
                        }
                    }
                } catch (Throwable $mailEx) {
                    Log::error('Failed to queue notification emails: '.$mailEx->getMessage());
                }
            }
        } catch (Throwable $th) {
            Log::error('Failed to sendMultiple notifications: '.$th->getMessage());
        }
    }

    public function getUserNotifications(User $user, int $limit = 15): LengthAwarePaginator
    {
        return Notification::where('user_id', $user->id)
            ->latest()
            ->paginate($limit);
    }

    public function getUnreadNotifications(User $user): Collection
    {
        return Notification::where('user_id', $user->id)
            ->whereNull('read_at')
            ->latest()
            ->get();
    }

    public function markAsRead(User $user, string $id): Notification
    {
        $notification = Notification::where('user_id', $user->id)
            ->where('id', $id)
            ->firstOrFail();

        if (is_null($notification->read_at)) {
            $notification->update(['read_at' => now()]);
        }

        return $notification;
    }

    public function markAllAsRead(User $user): int
    {
        return Notification::where('user_id', $user->id)
            ->whereNull('read_at')
            ->update(['read_at' => now()]);
    }
}

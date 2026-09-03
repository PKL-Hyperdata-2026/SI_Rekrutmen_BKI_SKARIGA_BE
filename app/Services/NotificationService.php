<?php

declare(strict_types=1);

namespace App\Services;

use App\Events\NotificationSent;
use App\Models\Notification;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

class NotificationService
{
    public function send(string $userId, string $type, string $title, string $message, array $data = []): bool
    {
        try {
            $notification = Notification::create([
                'id' => Str::uuid(),
                'user_id' => $userId,
                'type' => $type,
                'title' => $title,
                'message' => $message,
                'data' => $data,
            ]);

            event(new NotificationSent($userId, $notification->toArray()));

            return true;
        } catch (Throwable $th) {
            Log::error("Failed to send notification for user ID: $userId | " . $th->getMessage());

            return false;
        }
    }

    public function sendMultiple(array $userIds, string $type, string $title, string $message, array $data = []): void
    {
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
                && (!empty(config('broadcasting.connections.reverb.key')) || !empty(config('broadcasting.connections.pusher.key')));

            if ($hasBroadcaster) {
                foreach ($broadcastPayloads as $payload) {
                    event(new NotificationSent($payload['userId'], $payload['data']));
                }
            }
        } catch (Throwable $th) {
            Log::error("Failed to sendMultiple notifications: " . $th->getMessage());
        }
    }
}

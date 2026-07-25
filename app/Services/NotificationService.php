<?php

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
        foreach ($userIds as $userId) {
            $this->send($userId, $type, $title, $message, $data);
        }
    }
}

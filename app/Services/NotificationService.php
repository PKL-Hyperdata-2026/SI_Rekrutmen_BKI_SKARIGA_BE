<?php

namespace App\Services;

use App\Events\NotificationSent;
use Illuminate\Support\Facades\Log;
use Throwable;

class NotificationService
{
    public function send(string $userId, string $message): bool
    {
        try {
            event(new NotificationSent($userId, $message));
            return true;
        } catch (Throwable $th) {
            Log::error("Failed to send notification for user ID: $userId | " . $th->getMessage());

            return false;
        }
    }

    public function sendMultiple(array $userIds, string $message): void
    {
        foreach ($userIds as $userId) {
            $this->send($userId, $message);
        }
    }
}

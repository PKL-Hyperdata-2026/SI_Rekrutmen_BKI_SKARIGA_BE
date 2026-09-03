<?php

declare(strict_types=1);

namespace App\Services;

use App\Mail\GenericMail;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Throwable;

class MailService
{
    public function send(string $toEmail, string $title, string $body): bool
    {
        try {
            Mail::to($toEmail)->send(new GenericMail($title, $body));

            return true;
        } catch (Throwable $th) {
            Log::error("Failed to send email to $toEmail: " . $th->getMessage());

            return false;
        }
    }
}

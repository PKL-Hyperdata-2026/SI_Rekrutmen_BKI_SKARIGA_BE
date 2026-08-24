<?php

declare(strict_types=1);

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ResetPasswordMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public string $email;

    public string $resetUrl;

    public int $expiresInMinutes;

    public function __construct(string $email, string $token, int $expiresInMinutes)
    {
        $this->email = $email;
        $this->expiresInMinutes = $expiresInMinutes;

        $frontendUrl = rtrim((string) config('app.frontend_url'), '/');

        $this->resetUrl = sprintf(
            '%s/reset-password?%s',
            $frontendUrl,
            http_build_query(['token' => $token, 'email' => $email])
        );
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Reset Password - ' . config('app.name'),
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.reset-password',
            with: [
                'email' => $this->email,
                'resetUrl' => $this->resetUrl,
                'expiresInMinutes' => $this->expiresInMinutes,
                'appName' => (string) config('app.name'),
            ],
        );
    }

    public function attachments(): array
    {
        return [];
    }
}

<?php

declare(strict_types=1);

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class JobVacancyNotificationMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public string $userName;

    public string $title;

    public string $bodyMessage;

    public string $companyName;

    public string $position;

    public ?int $quota;

    public string $actionUrl;

    public function __construct(
        User|string $user,
        string $title,
        string $message,
        array $data = []
    ) {
        $this->userName = $user instanceof User ? ($user->full_name ?? 'Siswa/Alumni') : (string) $user;
        $this->title = $title;
        $this->bodyMessage = $message;
        $this->companyName = (string) ($data['company_name'] ?? 'Perusahaan Mitra');
        $this->position = (string) ($data['position'] ?? $title);
        $this->quota = isset($data['quota']) ? (int) $data['quota'] : null;

        $frontendUrl = rtrim((string) config('app.frontend_url'), '/');
        $this->actionUrl = $frontendUrl . '/student/lowongan';
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: $this->title . ' - ' . (string) config('app.name'),
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.job-vacancy-notification',
            with: [
                'userName' => $this->userName,
                'title' => $this->title,
                'bodyMessage' => $this->bodyMessage,
                'companyName' => $this->companyName,
                'position' => $this->position,
                'quota' => $this->quota,
                'actionUrl' => $this->actionUrl,
            ],
        );
    }

    public function attachments(): array
    {
        return [];
    }
}

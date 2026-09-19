<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Mail\JobVacancyNotificationMail;
use App\Models\User;
use App\Services\NotificationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class JobVacancyNotificationMailTest extends TestCase
{
    use RefreshDatabase;

    protected User $user1;

    protected User $user2;

    protected NotificationService $notificationService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user1 = User::factory()->create([
            'role' => 'siswa',
            'email' => 'siswa1@test.com',
            'full_name' => 'Budi Santoso',
            'is_active' => true,
        ]);

        $this->user2 = User::factory()->create([
            'role' => 'alumni',
            'email' => 'alumni1@test.com',
            'full_name' => 'Siti Nurhaliza',
            'is_active' => true,
        ]);

        $this->notificationService = app(NotificationService::class);
    }

    public function test_mailable_renders_content_properly(): void
    {
        $mailable = new JobVacancyNotificationMail(
            $this->user1,
            'Lowongan Baru: Mobile Developer',
            'PT Maju Bersama membuka lowongan baru.',
            [
                'company_name' => 'PT Maju Bersama',
                'position' => 'Mobile Developer',
                'quota' => 3,
            ]
        );

        $mailable->assertHasSubject('Lowongan Baru: Mobile Developer - '.config('app.name'));
        $mailable->assertSeeInHtml('Budi Santoso');
        $mailable->assertSeeInHtml('PT Maju Bersama');
        $mailable->assertSeeInHtml('Mobile Developer');
        $mailable->assertSeeInHtml('3 Orang');
    }

    public function test_send_single_notification_queues_email(): void
    {
        Mail::fake();

        $this->notificationService->send(
            $this->user1->id,
            'job_vacancy',
            'Lowongan Baru: Backend Engineer',
            'PT Solusi membuka lowongan.',
            [
                'company_name' => 'PT Solusi',
                'position' => 'Backend Engineer',
                'quota' => 2,
            ],
            true
        );

        Mail::assertQueued(JobVacancyNotificationMail::class, function ($mail) {
            return $mail->hasTo('siswa1@test.com')
                && $mail->position === 'Backend Engineer'
                && $mail->companyName === 'PT Solusi';
        });
    }

    public function test_send_single_notification_without_email_flag_does_not_queue_mail(): void
    {
        Mail::fake();

        $this->notificationService->send(
            $this->user1->id,
            'job_vacancy',
            'Lowongan Baru: Backend Engineer',
            'PT Solusi membuka lowongan.',
            [],
            false
        );

        Mail::assertNothingQueued();
    }

    public function test_send_multiple_notifications_queues_emails_for_all_users(): void
    {
        Mail::fake();

        $this->notificationService->sendMultiple(
            [$this->user1->id, $this->user2->id],
            'job_vacancy',
            'Lowongan Baru: UI/UX Designer',
            'PT Kreatif membuka lowongan.',
            [
                'company_name' => 'PT Kreatif',
                'position' => 'UI/UX Designer',
                'quota' => 4,
            ],
            true
        );

        Mail::assertQueued(JobVacancyNotificationMail::class, 2);
        Mail::assertQueued(JobVacancyNotificationMail::class, function ($mail) {
            return $mail->hasTo('siswa1@test.com');
        });
        Mail::assertQueued(JobVacancyNotificationMail::class, function ($mail) {
            return $mail->hasTo('alumni1@test.com');
        });
    }

    public function test_preview_route_returns_ok_and_renders_html(): void
    {
        $response = $this->get('/preview/job-vacancy-notification');

        $response->assertOk();
        $response->assertSee('LOWONGAN KERJA BARU');
        $response->assertSee('PT Hyperdata Solusi Teknologi');
        $response->assertSee('Frontend Engineer');
    }
}

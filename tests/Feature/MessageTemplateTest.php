<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\MessageTemplate;
use App\Models\User;
use App\Services\MessageTemplateService;
use Database\Seeders\ClassSeeder;
use Database\Seeders\CompanyIndustrySeeder;
use Database\Seeders\MajorSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MessageTemplateTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected User $student;

    protected MessageTemplateService $templateService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed([
            CompanyIndustrySeeder::class,
            ClassSeeder::class,
            MajorSeeder::class,
        ]);

        $this->admin = User::factory()->create([
            'role' => 'admin',
            'is_active' => true,
        ]);

        $this->student = User::factory()->create([
            'role' => 'siswa',
            'is_active' => true,
        ]);

        $this->templateService = app(MessageTemplateService::class);
    }

    public function test_admin_can_list_message_templates(): void
    {
        MessageTemplate::create([
            'code' => 'selection_accepted',
            'name' => 'Pengumuman Kelulusan Seleksi',
            'channel' => 'both',
            'subject' => 'Selamat {nama}, Anda Diterima di {perusahaan}!',
            'body' => 'Selamat {nama}! Anda telah diterima pada posisi {posisi} di {perusahaan}.',
            'variables' => ['nama', 'posisi', 'perusahaan'],
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->admin, 'sanctum')
            ->getJson('/api/admin/message-templates');

        $response->assertOk()
            ->assertJsonPath('success', true);

        $data = $response->json('data');
        $this->assertNotEmpty($data);
    }

    public function test_admin_can_update_message_template(): void
    {
        $template = MessageTemplate::create([
            'code' => 'admin_review_failed',
            'name' => 'Seleksi Administrasi Tidak Lolos',
            'channel' => 'notification',
            'subject' => 'Status Berkas: {posisi}',
            'body' => 'Mohon maaf {nama}, berkas Anda belum sesuai.',
            'variables' => ['nama', 'posisi'],
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->admin, 'sanctum')
            ->putJson("/api/admin/message-templates/{$template->id}", [
                'subject' => 'Pembaruan Berkas: {posisi}',
                'body' => 'Halo {nama}, berkas Anda belum lolos tahap seleksi berkas.',
                'is_active' => true,
            ]);

        $response->assertOk()
            ->assertJsonPath('success', true);

        $template->refresh();
        $this->assertSame('Pembaruan Berkas: {posisi}', $template->subject);
    }

    public function test_template_service_renders_variables_correctly(): void
    {
        MessageTemplate::create([
            'code' => 'test_greeting',
            'name' => 'Test Sapaan',
            'channel' => 'notification',
            'subject' => 'Halo {nama} dari {sekolah}!',
            'body' => 'Selamat datang {nama} di {sekolah}.',
            'variables' => ['nama', 'sekolah'],
            'is_active' => true,
        ]);

        $rendered = $this->templateService->render('test_greeting', [
            'nama' => 'Ahmad',
            'sekolah' => 'SMK PGRI 3 Malang',
        ]);

        $this->assertNotNull($rendered);
        $this->assertSame('Halo Ahmad dari SMK PGRI 3 Malang!', $rendered['subject']);
        $this->assertSame('Selamat datang Ahmad di SMK PGRI 3 Malang.', $rendered['body']);
    }

    public function test_non_admin_cannot_manage_message_templates(): void
    {
        $response = $this->actingAs($this->student, 'sanctum')
            ->getJson('/api/admin/message-templates');

        $response->assertStatus(403);
    }
}

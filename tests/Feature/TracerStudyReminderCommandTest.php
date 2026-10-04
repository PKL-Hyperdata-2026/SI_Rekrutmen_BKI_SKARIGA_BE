<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Company;
use App\Models\JobPlacement;
use App\Models\Major;
use App\Models\Notification;
use App\Models\StandardType;
use App\Models\StudentAlumni;
use App\Models\TracerStudy;
use App\Models\User;
use Database\Seeders\ClassSeeder;
use Database\Seeders\CompanyIndustrySeeder;
use Database\Seeders\MajorSeeder;
use Database\Seeders\PlacementStatusStandardTypeSeeder;
use Database\Seeders\TracerStudyStandardTypeSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TracerStudyReminderCommandTest extends TestCase
{
    use RefreshDatabase;

    protected User $alumniUser;

    protected StudentAlumni $alumniProfile;

    protected Company $company;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed([
            CompanyIndustrySeeder::class,
            ClassSeeder::class,
            MajorSeeder::class,
            TracerStudyStandardTypeSeeder::class,
            PlacementStatusStandardTypeSeeder::class,
        ]);

        $major = Major::firstOrFail();

        $this->alumniUser = User::factory()->create([
            'role' => 'alumni',
            'full_name' => 'Alumni Baru Lulus',
            'is_active' => true,
        ]);

        $this->alumniProfile = StudentAlumni::create([
            'user_id' => $this->alumniUser->id,
            'major_id' => $major->id,
            'nis' => '889900',
            'graduation_year' => (int) date('Y'),
            'is_active' => true,
        ]);

        $hrdUser = User::factory()->create(['role' => 'hrd']);
        $this->company = Company::create([
            'user_id' => $hrdUser->id,
            'name' => 'PT Mitra Sejahtera',
            'is_active' => true,
        ]);
    }

    public function test_command_sends_reminder_to_alumni_without_tracer_study(): void
    {
        $this->artisan('tracer:send-reminders')
            ->assertSuccessful();

        $notification = Notification::where('user_id', $this->alumniUser->id)
            ->where('type', 'tracer_study_reminder')
            ->first();

        $this->assertNotNull($notification);
    }

    public function test_command_sends_periodic_reminder_for_placed_candidate_at_3_months(): void
    {
        $placedUser = User::factory()->create([
            'role' => 'alumni',
            'full_name' => 'Alumni Bekerja 3 Bulan',
            'is_active' => true,
        ]);

        $placedProfile = StudentAlumni::create([
            'user_id' => $placedUser->id,
            'major_id' => $this->alumniProfile->major_id,
            'nis' => '778899',
            'graduation_year' => (int) date('Y'),
            'is_active' => true,
        ]);

        $status = StandardType::byCategory('placement_status')->first();

        JobPlacement::create([
            'student_alumni_id' => $placedProfile->id,
            'company_id' => $this->company->id,
            'placement_status_id' => $status?->id,
            'accepted_date' => now()->subMonths(3)->toDateString(),
            'start_date' => now()->subMonths(3)->toDateString(),
        ]);

        $this->artisan('tracer:send-reminders')
            ->assertSuccessful();

        $notification = Notification::where('user_id', $placedUser->id)
            ->where('type', 'tracer_study_reminder')
            ->first();

        $this->assertNotNull($notification);
    }

    public function test_command_skips_alumni_who_already_completed_tracer_study(): void
    {
        TracerStudy::create([
            'student_alumni_id' => $this->alumniProfile->id,
            'career_status' => 'bekerja',
            'company_name' => 'PT Sukses Mandiri',
        ]);

        $this->artisan('tracer:send-reminders')
            ->assertSuccessful();

        $notification = Notification::where('user_id', $this->alumniUser->id)
            ->where('type', 'tracer_study_reminder')
            ->first();

        $this->assertNull($notification);
    }
}

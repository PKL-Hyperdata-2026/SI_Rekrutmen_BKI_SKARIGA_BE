<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\ApplicationStageHistory;
use App\Models\JobApplication;
use App\Models\JobVacancy;
use App\Models\RecruitmentAttendance;
use App\Models\SelectionStage;
use App\Models\StandardType;
use App\Models\StudentAlumni;
use Illuminate\Database\Seeder;

class RecruitmentAttendanceSeeder extends Seeder
{
    private const TOTAL_ROWS = 20;

    private const STAGE_NAMES = ['Sosialisasi', 'Tes Psikotes', 'Interview HR', 'Pemberkasan'];

    public function run(): void
    {
        RecruitmentAttendance::query()->delete();

        $vacancies = JobVacancy::orderBy('id')->take(self::TOTAL_ROWS)->get();
        $students = StudentAlumni::orderBy('id')->take(self::TOTAL_ROWS)->get();
        if ($vacancies->isEmpty() || $students->isEmpty()) {
            return;
        }

        $stageType = StandardType::byCategory('stage_type')->first();
        $appStatus = StandardType::byCategory('job_application_status')->where('code', 'pending')->first()
            ?? StandardType::byCategory('job_application_status')->first();
        $scheduledStatus = StandardType::byCategory('application_stage_status')->where('code', 'scheduled')->first()
            ?? StandardType::byCategory('application_stage_status')->first();
        $presentStatus = StandardType::byCategory('attendance_status')->where('code', 'present')->first();

        for ($i = 0; $i < self::TOTAL_ROWS; $i++) {
            $vacancy = $vacancies[$i % $vacancies->count()];
            $student = $students[$i % $students->count()];
            $stageName = self::STAGE_NAMES[$i % count(self::STAGE_NAMES)];

            $stage = SelectionStage::firstOrCreate(
                [
                    'job_vacancy_id' => $vacancy->id,
                    'name' => $stageName,
                ],
                [
                    'stage_type_id' => $stageType?->id,
                    'sequence_order' => ($i % count(self::STAGE_NAMES)) + 1,
                    'scheduled_at' => now()->addDays(($i % 5) + 1)->setTime(8 + ($i % 4), ($i % 2 === 0 ? 0 : 30)),
                ]
            );

            if ($stage->scheduled_at === null) {
                $stage->update([
                    'scheduled_at' => now()->addDays(($i % 5) + 1)->setTime(8 + ($i % 4), ($i % 2 === 0 ? 0 : 30)),
                ]);
            }

            $application = JobApplication::firstOrCreate(
                [
                    'job_vacancy_id' => $vacancy->id,
                    'student_alumni_id' => $student->id,
                ],
                [
                    'status_id' => $appStatus?->id,
                    'current_stage_id' => $stage->id,
                    'applied_at' => now()->subDays(($i % 7) + 2),
                ]
            );

            $history = ApplicationStageHistory::firstOrCreate(
                [
                    'job_application_id' => $application->id,
                    'selection_stage_id' => $stage->id,
                ],
                [
                    'status_id' => $scheduledStatus?->id,
                ]
            );

            $attendedAt = now()->subDays($i % 7)->setTime(8 + ($i % 9), ($i * 13) % 60);

            $attendance = RecruitmentAttendance::firstOrNew([
                'stage_history_id' => $history->id,
            ]);

            $attendance->fill([
                'attendance_status_id' => $presentStatus?->id,
                'validation_status' => 'pending',
                'attended_at' => $attendedAt,
                'validated_by' => null,
                'validated_at' => null,
                'notes' => null,
                'system_action' => null,
            ]);
            $attendance->save();
        }
    }
}


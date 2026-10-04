<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\JobPlacement;
use App\Models\Notification;
use App\Models\StudentAlumni;
use App\Services\NotificationService;
use Illuminate\Console\Command;

class SendTracerStudyRemindersCommand extends Command
{
    protected $signature = 'tracer:send-reminders';

    protected $description = 'Kirim notifikasi pengingat tracer study berkala ke alumni dan kandidat yang ditempatkan';

    public function handle(NotificationService $notificationService): int
    {
        $today = now()->startOfDay();
        $threeMonths = $today->copy()->subMonths(3)->toDateString();
        $sixMonths = $today->copy()->subMonths(6)->toDateString();
        $twelveMonths = $today->copy()->subMonths(12)->toDateString();

        $sentCount = 0;

        $placements = JobPlacement::where(function ($q) use ($threeMonths, $sixMonths, $twelveMonths) {
            $q->whereIn('start_date', [$threeMonths, $sixMonths, $twelveMonths])
                ->orWhere(function ($sub) use ($threeMonths, $sixMonths, $twelveMonths) {
                    $sub->whereNull('start_date')
                        ->whereIn('accepted_date', [$threeMonths, $sixMonths, $twelveMonths]);
                });
        })->with(['studentAlumni.user', 'company'])->get();

        foreach ($placements as $placement) {
            $userId = $placement->studentAlumni?->user_id;
            if (! $userId) {
                continue;
            }

            $alreadySentToday = Notification::where('user_id', $userId)
                ->where('type', 'tracer_study_reminder')
                ->whereDate('created_at', now()->toDateString())
                ->exists();

            if (! $alreadySentToday) {
                $notificationService->send(
                    $userId,
                    'tracer_study_reminder',
                    'Pengingat Evaluasi Berkala & Tracer Study',
                    "Halo! Sudah saatnya memperbarui evaluasi masa kerja dan Tracer Study Anda di {$placement->company?->name}.",
                    [
                        'placement_id' => $placement->id,
                        'action_url' => '/student/tracer',
                    ],
                    true
                );
                $sentCount++;
            }
        }

        $unfilledAlumni = StudentAlumni::alumni()
            ->whereNotNull('graduation_year')
            ->whereDoesntHave('tracerStudy')
            ->with('user')
            ->get();

        foreach ($unfilledAlumni as $alumni) {
            $userId = $alumni->user_id;
            if (! $userId) {
                continue;
            }

            $alreadySentRecently = Notification::where('user_id', $userId)
                ->where('type', 'tracer_study_reminder')
                ->where('created_at', '>=', now()->subDays(30))
                ->exists();

            if (! $alreadySentRecently) {
                $notificationService->send(
                    $userId,
                    'tracer_study_reminder',
                    'Pengingat Pengisian Tracer Study SKARIGA',
                    'Halo! Mohon luangkan waktu 2 menit untuk memperbarui status karier Anda pada kuesioner Tracer Study.',
                    [
                        'action_url' => '/student/tracer',
                    ],
                    true
                );
                $sentCount++;
            }
        }

        $this->info("Pengingat Tracer Study berhasil dikirim ke {$sentCount} pengguna.");

        return self::SUCCESS;
    }
}

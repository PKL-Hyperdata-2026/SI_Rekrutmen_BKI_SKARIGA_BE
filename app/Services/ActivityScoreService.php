<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\ActivityLog;
use Illuminate\Support\Collection;

class ActivityScoreService
{
    public const POINTS = [
        'login' => 5,
        'complete_profile' => 15,
        'apply_vacancy' => 20,
        'attend_test' => 30,
        'submit_tracer' => 30,
    ];

    public function logActivity(
        int $userId,
        string $action,
        string $module,
        ?string $subjectType = null,
        ?int $subjectId = null,
        array $properties = [],
        ?string $ipAddress = null,
        ?string $userAgent = null
    ): ActivityLog {
        return ActivityLog::create([
            'user_id' => $userId,
            'action' => $action,
            'module' => $module,
            'method' => request()->method() ?? 'POST',
            'url' => request()->fullUrl() ?? null,
            'subject_type' => $subjectType,
            'subject_id' => $subjectId,
            'properties' => $properties,
            'ip_address' => $ipAddress ?? request()->ip(),
            'user_agent' => $userAgent ?? request()->userAgent(),
            'created_at' => now(),
        ]);
    }

    public function calculateScore(int $userId): int
    {
        $activities = ActivityLog::where('user_id', $userId)
            ->pluck('action');

        $total = 0;
        foreach ($activities as $action) {
            $total += self::POINTS[$action] ?? 0;
        }

        return $total;
    }

    public function getActivityHistory(int $userId, int $limit = 20): Collection
    {
        return ActivityLog::where('user_id', $userId)
            ->latest('id')
            ->limit($limit)
            ->get();
    }
}

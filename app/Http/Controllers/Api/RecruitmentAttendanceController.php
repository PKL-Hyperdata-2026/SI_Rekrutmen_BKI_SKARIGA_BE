<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\BulkValidateAttendanceRequest;
use App\Http\Requests\GetAttendanceQueueRequest;
use App\Http\Requests\GetAttendanceStageSummariesRequest;
use App\Http\Requests\ValidateAttendanceRequest;
use App\Http\Resources\RecruitmentAttendanceResource;
use App\Http\Resources\SelectionStageSummaryResource;
use App\Models\RecruitmentAttendance;
use App\Services\RecruitmentAttendanceService;
use App\Services\ResponseService;
use Illuminate\Contracts\Support\Responsable;
use Illuminate\Support\Facades\Auth;

class RecruitmentAttendanceController extends Controller
{
    public function __construct(
        protected RecruitmentAttendanceService $attendanceService,
        protected ResponseService $response
    ) {}

    public function vacancyOptions(): Responsable
    {
        $options = $this->attendanceService->getVacancyFilterOptions();

        return $this->response
            ->message('Opsi lowongan berhasil diambil.')
            ->data($options);
    }

    public function stageSummaries(GetAttendanceStageSummariesRequest $request): Responsable
    {
        $validated = $request->validated();
        $jobVacancyId = isset($validated['job_vacancy_id']) ? (int) $validated['job_vacancy_id'] : null;
        $summaries = $this->attendanceService->getStageSummaries($jobVacancyId);

        return $this->response
            ->message('Ringkasan tahapan seleksi berhasil diambil.')
            ->data(SelectionStageSummaryResource::collection($summaries));
    }

    public function queue(GetAttendanceQueueRequest $request): Responsable
    {
        $filters = $request->validated();
        $queue = $this->attendanceService->getPendingQueue($filters);

        return $this->response
            ->message('Antrean presensi menunggu validasi berhasil diambil.')
            ->data(RecruitmentAttendanceResource::collection($queue)->response()->getData(true));
    }

    public function history(GetAttendanceQueueRequest $request): Responsable
    {
        $filters = $request->validated();
        $history = $this->attendanceService->getHistory($filters);

        return $this->response
            ->message('Riwayat validasi presensi berhasil diambil.')
            ->data(RecruitmentAttendanceResource::collection($history)->response()->getData(true));
    }

    public function bulkValidate(BulkValidateAttendanceRequest $request): Responsable
    {
        $validatedData = $request->validated();
        $count = $this->attendanceService->bulkValidateAttendance(
            $validatedData['attendance_ids'],
            (int) Auth::id(),
            $validatedData
        );
        $statusText = $validatedData['validation_status'] === 'verified' ? 'divalidasi' : 'ditolak';

        return $this->response
            ->message("Sebanyak {$count} presensi peserta berhasil {$statusText}.")
            ->data(['affected' => $count]);
    }

    public function validateAttendance(ValidateAttendanceRequest $request, RecruitmentAttendance $attendance): Responsable
    {
        $validatedData = $request->validated();
        $result = $this->attendanceService->validateAttendance($attendance, (int) Auth::id(), $validatedData);
        $statusText = $validatedData['validation_status'] === 'verified' ? 'divalidasi' : 'ditolak';

        return $this->response
            ->message("Presensi peserta berhasil {$statusText}.")
            ->data(new RecruitmentAttendanceResource($result));
    }
}

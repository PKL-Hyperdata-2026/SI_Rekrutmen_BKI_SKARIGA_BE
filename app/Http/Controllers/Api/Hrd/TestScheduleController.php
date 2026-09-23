<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Hrd;

use App\Http\Controllers\Controller;
use App\Http\Requests\GetHrdTestParticipantRequest;
use App\Http\Requests\GetHrdTestScheduleRequest;
use App\Http\Requests\StoreHrdTestScheduleRequest;
use App\Http\Requests\UpdateHrdTestScheduleRequest;
use App\Http\Resources\HrdTestParticipantResource;
use App\Http\Resources\HrdTestScheduleResource;
use App\Services\ResponseService;
use App\Services\TestScheduleService;
use Illuminate\Contracts\Support\Responsable;
use Illuminate\Http\Request;

class TestScheduleController extends Controller
{
    public function __construct(
        protected TestScheduleService $testScheduleService,
        protected ResponseService $response
    ) {}

    public function index(GetHrdTestScheduleRequest $request): Responsable
    {
        $company = $request->user()?->company;
        if (! $company) {
            return $this->response
                ->success(false)
                ->message('Akun HRD belum terhubung dengan data perusahaan.')
                ->code(403);
        }

        $filters = $request->validated();

        $perPage = $request->integer('per_page', 15);
        $schedules = $this->testScheduleService->getHrdTestSchedules($company->id, $filters, $perPage);

        return $this->response
            ->message('Daftar jadwal tes berhasil diambil.')
            ->data(HrdTestScheduleResource::collection($schedules)->response()->getData(true));
    }

    public function store(StoreHrdTestScheduleRequest $request): Responsable
    {
        $company = $request->user()?->company;
        if (! $company) {
            return $this->response
                ->success(false)
                ->message('Akun HRD belum terhubung dengan data perusahaan.')
                ->code(403);
        }

        $schedule = $this->testScheduleService->createTestSchedule(
            $company->id,
            $request->validated(),
            $request->user()?->id
        );

        return $this->response
            ->message('Jadwal tes berhasil dibuat dan pelamar lolos berkas berhasil dialokasikan.')
            ->data(new HrdTestScheduleResource($schedule))
            ->code(201);
    }

    public function show(Request $request, int $id): Responsable
    {
        $company = $request->user()?->company;
        if (! $company) {
            return $this->response
                ->success(false)
                ->message('Akun HRD belum terhubung dengan data perusahaan.')
                ->code(403);
        }

        $schedule = $this->testScheduleService->getHrdTestScheduleDetail($company->id, $id);

        return $this->response
            ->message('Detail jadwal tes berhasil diambil.')
            ->data(new HrdTestScheduleResource($schedule));
    }

    public function update(UpdateHrdTestScheduleRequest $request, int $id): Responsable
    {
        $company = $request->user()?->company;
        if (! $company) {
            return $this->response
                ->success(false)
                ->message('Akun HRD belum terhubung dengan data perusahaan.')
                ->code(403);
        }

        $schedule = $this->testScheduleService->getHrdTestScheduleDetail($company->id, $id);
        $updated = $this->testScheduleService->updateTestSchedule(
            $schedule,
            $request->validated(),
            $request->user()?->id
        );

        return $this->response
            ->message('Jadwal tes berhasil diperbarui.')
            ->data(new HrdTestScheduleResource($updated));
    }

    public function destroy(Request $request, int $id): Responsable
    {
        $company = $request->user()?->company;
        if (! $company) {
            return $this->response
                ->success(false)
                ->message('Akun HRD belum terhubung dengan data perusahaan.')
                ->code(403);
        }

        $schedule = $this->testScheduleService->getHrdTestScheduleDetail($company->id, $id);
        $this->testScheduleService->deleteTestSchedule($schedule, $request->user()?->id);

        return $this->response->message('Jadwal tes berhasil dihapus.');
    }

    public function participants(GetHrdTestParticipantRequest $request, int $id): Responsable
    {
        $company = $request->user()?->company;
        if (! $company) {
            return $this->response
                ->success(false)
                ->message('Akun HRD belum terhubung dengan data perusahaan.')
                ->code(403);
        }

        $schedule = $this->testScheduleService->getHrdTestScheduleDetail($company->id, $id);

        $filters = $request->validated();
        $perPage = $request->integer('per_page', 15);
        $participants = $this->testScheduleService->getScheduleParticipants($company->id, $id, $filters, $perPage);

        return $this->response
            ->message('Daftar peserta jadwal tes berhasil diambil.')
            ->data([
                'schedule' => new HrdTestScheduleResource($schedule),
                'participants' => HrdTestParticipantResource::collection($participants)->response()->getData(true),
            ]);
    }

    public function remindParticipant(Request $request, int $id, int $participantId): Responsable
    {
        $company = $request->user()?->company;
        if (! $company) {
            return $this->response
                ->success(false)
                ->message('Akun HRD belum terhubung dengan data perusahaan.')
                ->code(403);
        }

        $this->testScheduleService->remindParticipant(
            $company->id,
            $id,
            $participantId,
            $request->user()?->id
        );

        return $this->response->message('Pengingat jadwal tes berhasil dikirim ke peserta.');
    }

    public function remindAllParticipants(Request $request, int $id): Responsable
    {
        $company = $request->user()?->company;
        if (! $company) {
            return $this->response
                ->success(false)
                ->message('Akun HRD belum terhubung dengan data perusahaan.')
                ->code(403);
        }

        $count = $this->testScheduleService->remindAllParticipants(
            $company->id,
            $id,
            $request->user()?->id
        );

        return $this->response
            ->message("Pengingat jadwal tes berhasil dikirim ke {$count} peserta.")
            ->data(['reminded_count' => $count]);
    }

    public function options(Request $request): Responsable
    {
        $company = $request->user()?->company;
        if (! $company) {
            return $this->response
                ->success(false)
                ->message('Akun HRD belum terhubung dengan data perusahaan.')
                ->code(403);
        }

        $options = $this->testScheduleService->getHrdFormOptions($company->id);

        return $this->response
            ->message('Opsi formulir jadwal tes berhasil diambil.')
            ->data($options);
    }
}

<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\GetUserListRequest;
use App\Http\Requests\ResetUserPasswordRequest;
use App\Http\Requests\StoreUserRequest;
use App\Http\Requests\UpdateUserRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use App\Services\ResponseService;
use App\Services\UserService;
use Illuminate\Contracts\Support\Responsable;
use Illuminate\Http\Request;

class UserController extends Controller
{
    public function __construct(
        protected UserService $userService,
        protected ResponseService $response
    ) {}

    public function index(GetUserListRequest $request): Responsable
    {
        $filters = $request->validated();

        $perPage = $request->integer('per_page', 15);
        $users = $this->userService->getUsers($filters, $perPage, $request->user()?->id);

        return $this->response
            ->message('Daftar data pengguna berhasil diambil.')
            ->data(UserResource::collection($users)->response()->getData(true));
    }

    public function options(): Responsable
    {
        $options = $this->userService->getFormOptions();

        return $this->response
            ->message('Opsi formulir pengguna berhasil diambil.')
            ->data(encrypt_recursive($options));
    }

    public function show(User $user): Responsable
    {
        $data = $this->userService->show($user);

        return $this->response
            ->message('Detail data pengguna berhasil diambil.')
            ->data(new UserResource($data));
    }

    public function store(StoreUserRequest $request): Responsable
    {
        $user = $this->userService->createUser(
            $request->validated(),
            $request->user()?->id
        );

        return $this->response
            ->message('Data pengguna berhasil ditambahkan.')
            ->data(new UserResource($user))
            ->code(201);
    }

    public function update(UpdateUserRequest $request, User $user): Responsable
    {
        $updated = $this->userService->updateUser(
            $user,
            $request->validated(),
            $request->user()?->id
        );

        return $this->response
            ->message('Data pengguna berhasil diperbarui.')
            ->data(new UserResource($updated));
    }

    public function toggleActive(Request $request, User $user): Responsable
    {
        $updated = $this->userService->toggleActive(
            $user,
            $request->user()?->id
        );

        return $this->response
            ->message('Status aktif pengguna berhasil diubah.')
            ->data(new UserResource($updated));
    }

    public function resetPassword(ResetUserPasswordRequest $request, User $user): Responsable
    {
        $this->userService->resetPassword(
            $user,
            $request->validated('password'),
            $request->user()?->id
        );

        return $this->response
            ->message('Password pengguna berhasil direset.');
    }

    public function destroy(Request $request, User $user): Responsable
    {
        $this->userService->deleteUser($user, $request->user()?->id);

        return $this->response
            ->message('Data pengguna berhasil dihapus.');
    }
}

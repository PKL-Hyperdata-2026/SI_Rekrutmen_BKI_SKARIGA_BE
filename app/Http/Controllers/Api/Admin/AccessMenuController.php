<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateAccessMenuRequest;
use App\Services\AccessMenuService;
use App\Services\ResponseService;
use Illuminate\Contracts\Support\Responsable;
use Illuminate\Http\Request;

class AccessMenuController extends Controller
{
    public function __construct(
        protected AccessMenuService $service,
        protected ResponseService $response
    ) {}

    public function index(): Responsable
    {
        $matrix = $this->service->getMatrix();

        return $this->response
            ->message('Matriks hak akses menu berhasil diambil.')
            ->data($matrix);
    }

    public function update(UpdateAccessMenuRequest $request): Responsable
    {
        $validated = $request->validated();
        $this->service->updateRolePermissions(
            $validated['role'],
            $validated['menu_ids'],
            (int) $request->user()?->id
        );

        return $this->response
            ->message("Hak akses menu untuk role {$validated['role']} berhasil diperbarui.");
    }

    public function myMenus(Request $request): Responsable
    {
        $menus = $this->service->getUserMenus($request->user());

        return $this->response
            ->message('Daftar menu pengguna berhasil diambil.')
            ->data($menus);
    }
}

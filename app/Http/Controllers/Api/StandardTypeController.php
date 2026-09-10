<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StandardTypeOptionsRequest;
use App\Http\Resources\SelectOptionResource;
use App\Services\ResponseService;
use App\Services\StandardTypeService;
use Illuminate\Contracts\Support\Responsable;

class StandardTypeController extends Controller
{
    public function __construct(
        protected StandardTypeService $standardTypeService,
        protected ResponseService $response
    ) {}

    public function index(StandardTypeOptionsRequest $request): Responsable
    {
        $options = $this->standardTypeService->getSelectOptions(
            (string) $request->validated('category'),
            $request->validated('search'),
            $request->integer('per_page', 20)
        );

        return $this->response
            ->message('Opsi tipe standar berhasil diambil.')
            ->data(SelectOptionResource::collection($options)->response()->getData(true));
    }
}

<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateMessageTemplateRequest;
use App\Services\MessageTemplateService;
use App\Services\ResponseService;
use Illuminate\Contracts\Support\Responsable;

class MessageTemplateController extends Controller
{
    public function __construct(
        protected MessageTemplateService $service,
        protected ResponseService $response
    ) {}

    public function index(): Responsable
    {
        $templates = $this->service->listTemplates();

        return $this->response
            ->message('Daftar template pesan berhasil diambil.')
            ->data($templates);
    }

    public function show(int $id): Responsable
    {
        $template = $this->service->find($id);

        return $this->response
            ->message('Detail template pesan berhasil diambil.')
            ->data($template);
    }

    public function update(UpdateMessageTemplateRequest $request, int $id): Responsable
    {
        $template = $this->service->update(
            $id,
            $request->validated(),
            (int) $request->user()?->id
        );

        return $this->response
            ->message('Template pesan berhasil diperbarui.')
            ->data($template);
    }
}

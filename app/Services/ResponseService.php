<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Contracts\Support\Responsable;
use Illuminate\Http\JsonResponse;

class ResponseService implements Responsable
{
    protected bool $success = true;

    protected string $message = '';

    protected mixed $data = null;

    protected int $statusCode = 200;

    protected array $additionalPayload = [];

    public static function make(): self
    {
        return new static;
    }

    public function success(bool $success): self
    {
        $this->success = $success;

        return $this;
    }

    public function message(string $message): self
    {
        $this->message = $message;

        return $this;
    }

    public function data(mixed $data): self
    {
        $this->data = $data;

        return $this;
    }

    public function code(int $httpCode): self
    {
        $this->statusCode = $httpCode;

        return $this;
    }

    // public function error(string $message, int $httpCode = 400): JsonResponse
    // {
    //     $this->success = false;
    //     $this->message = $message;
    //     $this->statusCode = $httpCode;

    //     return response()->json($this->toArray(), $this->statusCode);
    // }

    public function with(string $key, mixed $value): self
    {
        $this->additionalPayload[$key] = $value;

        return $this;
    }

    public function toArray(): array
    {
        $payload = [
            'success' => $this->success,
        ];

        if (! empty($this->message)) {
            $payload['message'] = $this->message;
        }

        if (! is_null($this->data)) {
            $payload['data'] = $this->data;
        }

        return array_merge($payload, $this->additionalPayload);
    }

    public function toResponse($request): JsonResponse
    {
        return response()->json($this->toArray(), $this->statusCode);
    }
}

<?php

namespace App\Services;

use Illuminate\Http\JsonResponse;

class ResponseService
{
    public function responseJson(string $status, string $message, int $httpCode, array $data = []): JsonResponse
    {
        if (!empty($data)) {
            return response()->json([
                'status' => $status,
                'message' => $message,
            ], $httpCode);
        }

        return response()->json([
            'status' => $status,
            'message' => $message,
            'data' => $data,
        ], $httpCode);
    }

    public function success(string $message, int $httpCode = 200, array $data = []): JsonResponse
    {
        return $this->responseJson(
            status: 'success',
            message: $message,
            data: $data,
            httpCode: $httpCode
        );
    }

    public function error(string $message, int $httpCode = 500, array $data = []): JsonResponse
    {
        return $this->responseJson(
            status: 'error',
            message: $message,
            data: $data,
            httpCode: $httpCode
        );
    }
}

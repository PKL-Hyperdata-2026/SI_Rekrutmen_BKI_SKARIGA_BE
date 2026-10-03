<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Services\ResponseService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureAccountIsActive
{
    public function __construct(
        protected ResponseService $response
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user('sanctum');

        if ($user && ! $user->is_active) {
            $user->tokens()->delete();

            return $this->response
                ->message('Akun Anda telah dinonaktifkan!')
                ->code(403)
                ->success(false)
                ->toResponse($request);
        }

        return $next($request);
    }
}

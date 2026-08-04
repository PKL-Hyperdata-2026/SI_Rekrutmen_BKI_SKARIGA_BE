<?php

namespace App\Http\Middleware;

use App\Services\ResponseService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RBAC
{
    public function __construct(
        protected ResponseService $response
    ) {}

    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        if (!$user || !in_array($user->role, $roles)) {
            return $this->response->error(
                message: 'Anda tidak memiliki hak untuk mengakses ini',
                httpCode: 403,
            );
        }

        return $next($request);
    }
}

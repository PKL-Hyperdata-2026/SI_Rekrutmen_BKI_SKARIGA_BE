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

        if (!$user) {
            return $this->response
                ->message('Anda tidak memiliki hak untuk mengakses ini!')
                ->code(403)
                ->success(false)
                ->toResponse($request);
        }

        $allowed = in_array($user->role, $roles);

        if (!$allowed && $user->role === 'superadmin' && in_array('admin', $roles)) {
            $allowed = true;
        }

        if (!$allowed) {
            return $this->response
                ->message('Anda tidak memiliki hak untuk mengakses ini!')
                ->code(403)
                ->success(false)
                ->toResponse($request);
        }

        return $next($request);
    }
}

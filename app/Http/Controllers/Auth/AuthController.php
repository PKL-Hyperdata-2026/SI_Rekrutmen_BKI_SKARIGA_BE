<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\ForgotPasswordRequest;
use App\Http\Requests\LoginRequest;
use App\Http\Requests\ResetPasswordRequest;
use App\Http\Resources\UserResource;
use App\Services\AuthService;
use App\Services\PasswordResetService;
use App\Services\ResponseService;
use Illuminate\Contracts\Support\Responsable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    public function __construct(
        protected ResponseService $response,
        protected PasswordResetService $passwordResetService,
        protected AuthService $authService
    ) {}

    public function login(LoginRequest $request): Responsable
    {
        $result = $this->authService->attemptLogin(
            (string) $request->validated('email'),
            (string) $request->validated('password')
        );

        if (! $result['success']) {
            return $this->response
                ->message($result['message'])
                ->code($result['code']);
        }

        return $this->response
            ->message('Login success')
            ->with('access_token', $result['token'])
            ->with('user', new UserResource($result['user']->loadMissing('company')))
            ->code(200);
    }

    public function forgot(ForgotPasswordRequest $request): Responsable
    {
        return $this->passwordResetService->sendResetLink(
            (string) $request->validated('email')
        );
    }

    public function reset(ResetPasswordRequest $request): Responsable
    {
        return $this->passwordResetService->reset(
            $request->validated()
        );
    }

    public function me(): Responsable
    {
        return $this->response
            ->with('user', new UserResource(Auth::user()?->loadMissing('company')))
            ->code(200);
    }

    public function logout(Request $request): Responsable
    {
        $request->user()->currentAccessToken()->delete();

        return $this->response->message('Logout success');
    }
}

<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\ForgotPasswordRequest;
use App\Http\Requests\LoginRequest;
use App\Http\Requests\ResetPasswordRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use App\Services\PasswordResetService;
use App\Services\ResponseService;
use Illuminate\Contracts\Support\Responsable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function __construct(
        protected ResponseService $response,
        protected PasswordResetService $passwordResetService
    ) {}

    public function login(LoginRequest $request): Responsable
    {
        $user = User::where('email', $request->validated('email'))->first();

        if (!$user || !Hash::check($request->validated('password'), $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['Invalid Credentials!'],
            ]);
        }

        if (!$user->is_active) {
            return $this->response
                ->message('Your account is deactivated. Contact admin for further help.')
                ->code(403);
        }

        $token = $user->createToken('auth_token')->plainTextToken;

        return $this->response
            ->message('Login success')
            ->with('access_token', $token)
            ->with('user', new UserResource($user))
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
            ->with('user', new UserResource(Auth::user()))
            ->code(200);
    }

    public function logout(Request $request): Responsable
    {
        $request->user()->currentAccessToken()->delete();

        return $this->response->message('Logout success');
    }
}

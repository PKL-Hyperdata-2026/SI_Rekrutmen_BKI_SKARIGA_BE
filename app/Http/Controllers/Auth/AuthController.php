<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use App\Services\ResponseService;

class AuthController extends Controller
{
    public function __construct(
        protected ResponseService $response
    ) {}

    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required'
        ]);

        $user = User::where('email', $request->email)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
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
            ->with('user', $user)
            ->code(200);
    }

    public function me()
    {
        return $this->response
            ->with('user', Auth::user())
            ->code(200);
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return $this->response->message('Logout success');
    }
}

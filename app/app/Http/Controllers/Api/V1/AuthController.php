<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\CompleteProfileRequest;
use App\Http\Requests\Auth\GoogleLoginRequest;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RefreshTokenRequest;
use App\Http\Requests\Auth\RegisterRequest;
use App\Http\Requests\Auth\ResendOtpRequest;
use App\Http\Requests\Auth\VerifyEmailRequest;
use App\Http\Resources\AuthUserResource;
use App\Models\AuthSession;
use App\Models\User;
use App\Services\AuthService;
use App\Services\EmailVerificationService;
use App\Services\RegistrationService;
use App\Services\TokenService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AuthController extends Controller
{
    public function register(RegisterRequest $request, RegistrationService $registration): JsonResponse
    {
        $user = $registration->register($request->validated());

        return response()->json([
            'message' => 'Account created. Check your email for a verification code.',
            'data' => ['user' => (new AuthUserResource($user->load('roles')))->resolve($request)],
        ], 201);
    }

    public function verifyEmail(
        VerifyEmailRequest $request,
        EmailVerificationService $verification,
    ): JsonResponse {
        $data = $request->validated();
        $user = $verification->verify($data['email'], $data['otp']);

        return response()->json([
            'message' => 'Email address verified.',
            'data' => ['user' => (new AuthUserResource($user->load('roles')))->resolve($request)],
        ]);
    }

    public function resendOtp(
        ResendOtpRequest $request,
        EmailVerificationService $verification,
    ): JsonResponse {
        $user = User::query()->where('email', $request->validated('email'))->first();

        if ($user !== null && $user->status === 'active' && ! $user->hasVerifiedEmail()) {
            $verification->sendVerificationCode($user);
        }

        return response()->json(['message' => 'If the account can be verified, a code has been sent.'], 202);
    }

    public function google(GoogleLoginRequest $request, AuthService $auth): JsonResponse
    {
        return $this->tokenResponse($request, $auth->loginWithGoogle($request->validated('id_token')));
    }

    public function login(LoginRequest $request, AuthService $auth): JsonResponse
    {
        $data = $request->validated();

        return $this->tokenResponse($request, $auth->login($data['email'], $data['password']));
    }

    public function refresh(RefreshTokenRequest $request, TokenService $tokens): JsonResponse
    {
        $result = $tokens->refresh($request->validated('refresh_token'));

        return response()->json([
            'message' => 'Session renewed.',
            'data' => [
                'user' => (new AuthUserResource($result['user']->load('roles')))->resolve($request),
                'tokens' => $result['tokens'],
            ],
        ]);
    }

    public function logout(Request $request, TokenService $tokens): JsonResponse
    {
        $session = $request->attributes->get('auth_session');

        if (! $session instanceof AuthSession) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        $tokens->revoke($session);

        return response()->json(['message' => 'Session revoked.']);
    }

    public function me(Request $request): JsonResponse
    {
        $user = $request->user();

        abort_unless($user instanceof User, 401, 'Unauthenticated.');

        return response()->json([
            'data' => ['user' => (new AuthUserResource($user->load('roles')))->resolve($request)],
        ]);
    }

    public function completeProfile(CompleteProfileRequest $request): JsonResponse
    {
        $user = $request->user();

        abort_unless($user instanceof User, 401, 'Unauthenticated.');

        $data = $request->validated();
        $user->update([
            'name' => $data['full_name'],
            'phone' => $data['phone'] ?? null,
            'avatar_url' => $data['avatar_url'] ?? null,
        ]);

        return response()->json([
            'message' => 'Profile updated.',
            'data' => ['user' => (new AuthUserResource($user->load('roles')))->resolve($request)],
        ]);
    }

    /**
     * @param  array{user: User, tokens: array<string, string|int>}  $result
     */
    private function tokenResponse(Request $request, array $result): JsonResponse
    {
        return response()->json([
            'message' => 'Authenticated.',
            'data' => [
                'user' => (new AuthUserResource($result['user']->load('roles')))->resolve($request),
                'tokens' => $result['tokens'],
            ],
        ]);
    }
}

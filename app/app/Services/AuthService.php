<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Support\Facades\Hash;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

class AuthService
{
    public function __construct(
        private TokenService $tokens,
        private GoogleAuthService $google,
    ) {}

    /**
     * @return array{user: User, tokens: array<string, string|int>}
     */
    public function login(string $email, string $password): array
    {
        $user = User::query()->where('email', $email)->first();

        if ($user === null || $user->password === null || ! Hash::check($password, $user->password)) {
            throw new AuthenticationException('The provided credentials are incorrect.');
        }

        if ($user->status !== 'active') {
            throw new AccessDeniedHttpException('This account is not active.');
        }

        if (! $user->hasVerifiedEmail()) {
            throw new AccessDeniedHttpException('Email verification is required before login.');
        }

        return ['user' => $user, 'tokens' => $this->tokens->issue($user)];
    }

    /**
     * @return array{user: User, tokens: array<string, string|int>}
     */
    public function loginWithGoogle(string $idToken): array
    {
        $user = $this->google->authenticate($idToken);

        if ($user->status !== 'active') {
            throw new AccessDeniedHttpException('This account is not active.');
        }

        if (! $user->hasVerifiedEmail()) {
            throw new AccessDeniedHttpException('Email verification is required before login.');
        }

        return ['user' => $user, 'tokens' => $this->tokens->issue($user)];
    }
}

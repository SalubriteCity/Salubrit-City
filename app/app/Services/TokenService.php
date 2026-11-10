<?php

namespace App\Services;

use App\Models\AuthSession;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;

class TokenService
{
    /**
     * @return array{access_token: string, refresh_token: string, token_type: string, expires_in: int, refresh_expires_in: int}
     */
    public function issue(User $user): array
    {
        $accessToken = $this->newToken();
        $refreshToken = $this->newToken();
        $accessTtl = (int) config('authentication.access_token_ttl');
        $refreshTtl = (int) config('authentication.refresh_token_ttl');

        $user->authSessions()->create([
            'access_token_hash' => hash('sha256', $accessToken),
            'refresh_token_hash' => hash('sha256', $refreshToken),
            'token_expires_at' => now()->addMinutes($accessTtl),
            'refresh_expires_at' => now()->addMinutes($refreshTtl),
        ]);

        return $this->tokenPayload($accessToken, $refreshToken, $accessTtl, $refreshTtl);
    }

    public function authenticate(string $token): ?AuthSession
    {
        $session = AuthSession::query()
            ->where('access_token_hash', hash('sha256', $token))
            ->whereNull('revoked_at')
            ->where('token_expires_at', '>', now())
            ->with('user')
            ->first();

        if ($session === null) {
            return null;
        }

        $session->forceFill(['last_used_at' => now()])->save();

        return $session;
    }

    /**
     * @return array{user: User, tokens: array{access_token: string, refresh_token: string, token_type: string, expires_in: int, refresh_expires_in: int}}
     */
    public function refresh(string $refreshToken): array
    {
        return DB::transaction(function () use ($refreshToken): array {
            $session = AuthSession::query()
                ->where('refresh_token_hash', hash('sha256', $refreshToken))
                ->whereNull('revoked_at')
                ->where('refresh_expires_at', '>', now())
                ->lockForUpdate()
                ->with('user')
                ->first();

            if ($session === null || $session->user === null || $session->user->status !== 'active') {
                throw new UnauthorizedHttpException('Bearer', 'The refresh token is invalid or expired.');
            }

            $accessToken = $this->newToken();
            $newRefreshToken = $this->newToken();
            $accessTtl = (int) config('authentication.access_token_ttl');
            $refreshTtl = (int) config('authentication.refresh_token_ttl');

            $session->update([
                'access_token_hash' => hash('sha256', $accessToken),
                'refresh_token_hash' => hash('sha256', $newRefreshToken),
                'token_expires_at' => now()->addMinutes($accessTtl),
                'refresh_expires_at' => now()->addMinutes($refreshTtl),
                'last_used_at' => now(),
            ]);

            return [
                'user' => $session->user,
                'tokens' => $this->tokenPayload($accessToken, $newRefreshToken, $accessTtl, $refreshTtl),
            ];
        });
    }

    public function revoke(AuthSession $session): void
    {
        $session->update(['revoked_at' => now()]);
    }

    private function newToken(): string
    {
        return bin2hex(random_bytes(32));
    }

    /**
     * @return array{access_token: string, refresh_token: string, token_type: string, expires_in: int, refresh_expires_in: int}
     */
    private function tokenPayload(string $accessToken, string $refreshToken, int $accessTtl, int $refreshTtl): array
    {
        return [
            'access_token' => $accessToken,
            'refresh_token' => $refreshToken,
            'token_type' => 'Bearer',
            'expires_in' => $accessTtl * 60,
            'refresh_expires_in' => $refreshTtl * 60,
        ];
    }
}

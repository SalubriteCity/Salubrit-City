<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\ServiceUnavailableHttpException;

class GoogleAuthService
{
    public function __construct(private RegistrationService $registration) {}

    public function authenticate(string $idToken): User
    {
        $clientId = config('services.google.client_id');

        if (! is_string($clientId) || $clientId === '') {
            throw new ServiceUnavailableHttpException(null, 'Google authentication is not configured.');
        }

        try {
            $response = Http::asForm()
                ->connectTimeout(3)
                ->timeout(5)
                ->post('https://oauth2.googleapis.com/tokeninfo', ['id_token' => $idToken]);
        } catch (ConnectionException $exception) {
            throw new ServiceUnavailableHttpException(null, 'Google authentication is temporarily unavailable.');
        }

        $claims = $response->json();
        $issuer = is_array($claims) ? ($claims['iss'] ?? null) : null;
        $verified = is_array($claims) && filter_var(
            $claims['email_verified'] ?? false,
            FILTER_VALIDATE_BOOLEAN,
        );

        if (
            ! $response->successful()
            || ! is_array($claims)
            || ($claims['aud'] ?? null) !== $clientId
            || ! in_array($issuer, ['accounts.google.com', 'https://accounts.google.com'], true)
            || ! $verified
            || ! is_string($claims['sub'] ?? null)
            || ! is_string($claims['email'] ?? null)
            || filter_var($claims['email'] ?? null, FILTER_VALIDATE_EMAIL) === false
            || ! is_numeric($claims['exp'] ?? null)
            || (int) $claims['exp'] <= now()->timestamp
        ) {
            throw ValidationException::withMessages([
                'id_token' => ['The Google identity token is invalid.'],
            ]);
        }

        $user = User::withTrashed()->where('google_id', $claims['sub'])->first();

        if ($user !== null) {
            if ($user->trashed()) {
                throw ValidationException::withMessages([
                    'id_token' => ['This account is not available.'],
                ]);
            }

            return $user;
        }

        return $this->registration->registerGoogleAccount([
            'sub' => $claims['sub'],
            'email' => $claims['email'],
            'name' => $claims['name'] ?? null,
            'picture' => $claims['picture'] ?? null,
        ]);
    }
}

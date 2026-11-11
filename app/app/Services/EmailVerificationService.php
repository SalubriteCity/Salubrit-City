<?php

namespace App\Services;

use App\Models\EmailOtp;
use App\Models\User;
use App\Notifications\VerifyEmailOtp;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\ServiceUnavailableHttpException;

class EmailVerificationService
{
    private const PURPOSE = 'email_verification';

    public function sendVerificationCode(User $user): void
    {
        $this->assertDeliveryAvailable();
        $code = (string) random_int(100000, 999999);

        DB::transaction(function () use ($user, $code): void {
            $user->emailOtps()
                ->where('purpose', self::PURPOSE)
                ->whereNull('used_at')
                ->update(['used_at' => now()]);

            $user->emailOtps()->create([
                'purpose' => self::PURPOSE,
                'code_hash' => Hash::make($code),
                'expires_at' => now()->addMinutes((int) config('authentication.email_otp_ttl')),
            ]);
        });

        Notification::route('mail', $user->email)->notify(new VerifyEmailOtp($code));
    }

    private function assertDeliveryAvailable(): void
    {
        $mailer = config('mail.default');

        if (app()->environment(['local', 'testing'])) {
            return;
        }

        if (in_array($mailer, ['log', 'array'], true)) {
            throw new ServiceUnavailableHttpException(
                null,
                'Email delivery is not configured.'
            );
        }
    }

    public function verify(string $email, string $code): User
    {
        $user = User::query()->where('email', $email)->first();
        $otp = $user?->emailOtps()
            ->where('purpose', self::PURPOSE)
            ->whereNull('used_at')
            ->where('expires_at', '>', now())
            ->latest('id')
            ->first();

        if ($user === null || $otp === null || ! Hash::check($code, $otp->code_hash)) {
            throw ValidationException::withMessages([
                'otp' => ['The verification code is invalid or expired.'],
            ]);
        }

        return DB::transaction(function () use ($otp, $user): User {
            $used = EmailOtp::query()
                ->whereKey($otp->getKey())
                ->whereNull('used_at')
                ->where('expires_at', '>', now())
                ->update(['used_at' => now()]);

            if ($used !== 1) {
                throw ValidationException::withMessages([
                    'otp' => ['The verification code is invalid or expired.'],
                ]);
            }

            $user->markEmailAsVerified();

            return $user->refresh();
        });
    }
}

<?php

namespace App\Services;

use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class RegistrationService
{
    public function __construct(private EmailVerificationService $emailVerification) {}

    /**
     * @param  array{full_name: string, email: string, password: string, phone?: ?string}  $data
     */
    public function register(array $data): User
    {
        

        $user = DB::transaction(function () use ($data): User {
            $user = User::create([
                'name' => $data['full_name'],
                'email' => $data['email'],
                'phone' => $data['phone'] ?? null,
                'password' => $data['password'],
                'status' => 'active',
            ]);

            $this->assignCitizenRole($user);

            return $user;
        });

        $this->emailVerification->sendVerificationCode($user);

        return $user;
    }

    /**
     * @param  array{sub: string, email: string, name?: ?string, picture?: ?string}  $claims
     */
    public function registerGoogleAccount(array $claims): User
    {
        return DB::transaction(function () use ($claims): User {
            if (User::withTrashed()->where('email', $claims['email'])->exists()) {
                throw ValidationException::withMessages([
                    'email' => ['This email is already associated with an account.'],
                ]);
            }

            $user = User::create([
                'name' => $claims['name'] ?? $claims['email'],
                'email' => $claims['email'],
                'google_id' => $claims['sub'],
                'avatar_url' => $claims['picture'] ?? null,
            ]);
            $user->forceFill(['email_verified_at' => now()])->save();

            $this->assignCitizenRole($user);

            return $user;
        });
    }

    private function assignCitizenRole(User $user): void
    {
        $role = Role::query()->where('label', 'citizen')->firstOrFail();
        $user->roles()->attach($role);
    }
}

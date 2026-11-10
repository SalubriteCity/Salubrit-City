<?php

namespace App\Services;

use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Facades\DB;


class RegistrationService
{
    public function __construct(private EmailVerificationService $emailVerification) {}

    /**
     * @param  array{full_name: string, email: string, password: string, phone?: ?string}  $data
     */
    public function register(array $data): User
    {
        $this->emailVerification->assertDeliveryAvailable();

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


    private function assignCitizenRole(User $user): void
    {
        $role = Role::query()->where('label', 'citizen')->firstOrFail();
        $user->roles()->attach($role);
    }
}

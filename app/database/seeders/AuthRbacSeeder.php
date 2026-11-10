<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Seeder;

class AuthRbacSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $permissions = [
            'auth.register' => ['Register', 'Create an account', 'auth'],
            'auth.login' => ['Login', 'Authenticate with email and password', 'auth'],
            'auth.logout' => ['Logout', 'Revoke an active session', 'auth'],
            'auth.verify_email' => ['Verify email', 'Verify an email address', 'auth'],
            'auth.resend_otp' => ['Resend OTP', 'Request another verification code', 'auth'],
            'profile.view' => ['View profile', 'View the authenticated profile', 'profile'],
            'profile.update' => ['Update profile', 'Update the authenticated profile', 'profile'],
            'reports.create' => ['Create reports', 'Submit a report', 'reports'],
            'reports.view' => ['View reports', 'View reports within the assigned scope', 'reports'],
            'reports.view_all' => ['View all reports', 'View all platform reports', 'reports'],
            'reports.update' => ['Update reports', 'Update reports within the assigned scope', 'reports'],
            'reports.delete' => ['Delete reports', 'Delete reports within the assigned scope', 'reports'],
            'reports.confirm' => ['Confirm reports', 'Confirm a reported incident', 'reports'],
            'reports.manage' => ['Manage reports', 'Manage reports within the assigned scope', 'reports'],
            'users.view' => ['View users', 'View users within the assigned scope', 'users'],
            'users.create' => ['Create users', 'Create user accounts', 'users'],
            'users.update' => ['Update users', 'Update users within the assigned scope', 'users'],
            'users.delete' => ['Delete users', 'Delete users within the assigned scope', 'users'],
            'users.manage_roles' => ['Manage user roles', 'Assign roles to users', 'users'],
            'admin.access' => ['Access administration', 'Access administrative features', 'admin'],
            'admin.manage_settings' => ['Manage settings', 'Manage platform settings', 'admin'],
            'admin.manage_permissions' => ['Manage permissions', 'Manage roles and permissions', 'admin'],
        ];

        $permissionIds = [];

        foreach ($permissions as $label => [$name, $description, $module]) {
            $permissionIds[$label] = Permission::updateOrCreate(
                ['label' => $label],
                compact('name', 'description', 'module'),
            )->id;
        }

        $roles = [
            'citizen' => [
                'name' => 'CITIZEN',
                'description' => 'Utilisateur citoyen',
                'permissions' => [
                    'auth.register', 'auth.login', 'auth.logout', 'auth.verify_email',
                    'auth.resend_otp', 'profile.view', 'profile.update', 'reports.create',
                    'reports.view', 'reports.confirm',
                ],
            ],
            'municipal-agent' => [
                'name' => 'MUNICIPAL_AGENT',
                'description' => 'Agent municipal',
                'permissions' => [
                    'auth.logout', 'profile.view', 'profile.update', 'reports.view',
                    'reports.update', 'reports.confirm',
                ],
            ],
            'admin' => [
                'name' => 'ADMIN',
                'description' => 'Administrateur',
                'permissions' => [
                    'auth.logout', 'profile.view', 'profile.update', 'reports.view',
                    'reports.view_all', 'reports.update', 'reports.delete', 'reports.confirm',
                    'reports.manage', 'users.view', 'users.create', 'users.update',
                    'users.delete', 'users.manage_roles', 'admin.access',
                ],
            ],
            'super-admin' => [
                'name' => 'SUPER_ADMIN',
                'description' => 'Administrateur principal',
                'permissions' => array_keys($permissions),
            ],
        ];

        foreach ($roles as $label => $roleData) {
            $role = Role::updateOrCreate(
                ['label' => $label],
                ['name' => $roleData['name'], 'description' => $roleData['description']],
            );

            $role->permissions()->sync(array_map(
                fn (string $permissionlabel): int => $permissionIds[$permissionlabel],
                $roleData['permissions'],
            ));
        }
    }
}

<?php

namespace Database\Seeders;

use App\Models\User;
use App\Support\AdminPermissions;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use RuntimeException;

class AdminUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $name = $this->credential('name') ?: 'Local Amarvero Admin';
        $email = $this->credential('email');
        $password = $this->credential('password');

        if ($email === '' || $password === '') {
            if (app()->isProduction()) {
                throw new RuntimeException('ADMIN_EMAIL and ADMIN_PASSWORD must be set before seeding the production admin user.');
            }

            $email = 'admin@example.test';
            $password = 'password';
        }

        $admin = User::query()->updateOrCreate(
            ['email' => $email],
            [
                'name' => $name,
                'email_verified_at' => now(),
                'password' => Hash::make($password),
            ],
        );

        $admin->assignRole(AdminPermissions::SuperAdmin);
    }

    protected function credential(string $key): string
    {
        $value = config('admin.seed.'.$key);

        return is_scalar($value) ? trim((string) $value) : '';
    }
}

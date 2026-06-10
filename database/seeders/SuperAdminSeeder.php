<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Database\Seeder;

class SuperAdminSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        User::updateOrCreate(
            ['email' => 'superadmin@boutitrad.com'],
            [
                'name' => 'Super Admin',
                'password' => 'password',
                'role' => UserRole::SuperAdmin->value,
                'email_verified_at' => now(),
            ]
        );
    }
}

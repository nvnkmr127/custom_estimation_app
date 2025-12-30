<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DemoLoginsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $users = [
            [
                'name' => 'Super Admin',
                'email' => 'super@example.com',
                'role' => 'super_admin',
            ],
            [
                'name' => 'Estimator Admin',
                'email' => 'estimator@example.com',
                'role' => 'estimator_admin',
            ],
            [
                'name' => 'Estimator Manager',
                'email' => 'manager@example.com',
                'role' => 'estimator_manager',
            ],
            [
                'name' => 'Estimator User',
                'email' => 'sales@example.com',
                'role' => 'estimator',
            ],
        ];

        foreach ($users as $userData) {
            User::firstOrCreate(
                ['email' => $userData['email']],
                [
                    'name' => $userData['name'],
                    'role' => $userData['role'],
                    'password' => Hash::make('password'),
                    'email_verified_at' => now(),
                ]
            );
        }

        $this->command->info('Demo users created safely.');
    }
}

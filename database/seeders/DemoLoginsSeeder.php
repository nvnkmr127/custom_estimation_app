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
                'name' => 'Sales Manager',
                'email' => 'manager@example.com',
                'role' => 'sales_manager',
            ],
            [
                'name' => 'Sales User',
                'email' => 'sales@example.com',
                'role' => 'sales',
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

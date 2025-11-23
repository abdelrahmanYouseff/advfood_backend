<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{   
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Admin user
        User::updateOrCreate(
            ['email' => 'admin@advfood.com'],
            [
                'name' => 'Admin',
                'email_verified_at' => now(),
                'password' => Hash::make('password123'),
                'role' => 'admin',
            ]
        );

        // Invoice viewer user (acc@adv-line.sa)
        User::updateOrCreate(
            ['email' => 'acc@adv-line.sa'],
            [
                'name' => 'Invoice Viewer',
                'email_verified_at' => now(),
                'password' => Hash::make('password123'),
                'role' => 'user',
            ]
        );
    }
}

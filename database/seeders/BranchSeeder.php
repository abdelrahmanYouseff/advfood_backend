<?php

namespace Database\Seeders;

use App\Models\Branch;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class BranchSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Branch 1: Mrouj (existing branch)
        Branch::updateOrCreate(
            ['email' => 'mrouj@advfood.com'],
            [
                'name' => 'Mrouj',
                'password' => Hash::make('password'), // Change this to a secure password
                'latitude' => 24.7560922,
                'longitude' => 46.6749848,
                'status' => 'active',
            ]
        );

        // Branch 2: Laban
        Branch::updateOrCreate(
            ['email' => 'laban@advfood.com'],
            [
                'name' => 'Laban',
                'password' => Hash::make('password'), // Change this to a secure password
                'latitude' => 24.62632179260254,
                'longitude' => 46.531005859375,
                'status' => 'active',
            ]
        );
    }
}

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Check if admin2 user already exists
        $existingUser = DB::table('users')->where('email', 'admin2@advfood.com')->first();

        if (!$existingUser) {
            // Insert admin2 user only if it doesn't exist
            DB::table('users')->insert([
                'name' => 'Admin2',
                'email' => 'admin2@advfood.com',
                'password' => Hash::make('password123'),
                'role' => 'admin',
                'phone' => '+966501234567',
                'email_verified_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Remove admin2 user
        DB::table('users')->where('email', 'admin2@advfood.com')->delete();
    }
};

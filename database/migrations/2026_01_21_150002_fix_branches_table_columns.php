<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     * This migration safely adds email, password, and remember_token columns
     * to branches table if they don't exist.
     */
    public function up(): void
    {
        // Check if branches table exists first
        if (!Schema::hasTable('branches')) {
            // Table doesn't exist, skip this migration
            // The recreate migration will handle it
            return;
        }
        
        $connection = DB::connection();
        $dbName = $connection->getDatabaseName();
        
        // Check if columns exist using information_schema
        $columns = DB::select(
            "SELECT COLUMN_NAME FROM information_schema.COLUMNS 
             WHERE TABLE_SCHEMA = ? AND TABLE_NAME = 'branches'",
            [$dbName]
        );
        
        $existingColumns = array_column($columns, 'COLUMN_NAME');
        
        // First ensure 'name' column exists (required for 'after' clause)
        if (!in_array('name', $existingColumns)) {
            Schema::table('branches', function (Blueprint $table) {
                $table->string('name')->after('id');
            });
            // Refresh existing columns list
            $columns = DB::select(
                "SELECT COLUMN_NAME FROM information_schema.COLUMNS 
                 WHERE TABLE_SCHEMA = ? AND TABLE_NAME = 'branches'",
                [$dbName]
            );
            $existingColumns = array_column($columns, 'COLUMN_NAME');
        }
        
        // Add email column if it doesn't exist
        if (!in_array('email', $existingColumns)) {
            Schema::table('branches', function (Blueprint $table) {
                $table->string('email')->unique()->after('name');
            });
        } else {
            // Email exists, ensure it has unique constraint
            try {
                $constraints = DB::select(
                    "SELECT CONSTRAINT_NAME FROM information_schema.TABLE_CONSTRAINTS 
                     WHERE TABLE_SCHEMA = ? AND TABLE_NAME = 'branches' 
                     AND CONSTRAINT_TYPE = 'UNIQUE' AND CONSTRAINT_NAME LIKE '%email%'",
                    [$dbName]
                );
                if (empty($constraints)) {
                    DB::statement('ALTER TABLE branches ADD UNIQUE KEY branches_email_unique (email)');
                }
            } catch (\Exception $e) {
                // Unique constraint might already exist, ignore
            }
        }
        
        // Add password column if it doesn't exist
        if (!in_array('password', $existingColumns)) {
            Schema::table('branches', function (Blueprint $table) {
                $table->string('password')->after('email');
            });
        }
        
        // Add remember_token column if it doesn't exist
        if (!in_array('remember_token', $existingColumns)) {
            Schema::table('branches', function (Blueprint $table) {
                $table->rememberToken()->after('password');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('branches', function (Blueprint $table) {
            $columns = ['email', 'password', 'remember_token'];
            foreach ($columns as $column) {
                if (Schema::hasColumn('branches', $column)) {
                    if ($column === 'email') {
                        try {
                            $table->dropUnique(['email']);
                        } catch (\Exception $e) {
                            // Ignore if unique doesn't exist
                        }
                    }
                    $table->dropColumn($column);
                }
            }
        });
    }
};

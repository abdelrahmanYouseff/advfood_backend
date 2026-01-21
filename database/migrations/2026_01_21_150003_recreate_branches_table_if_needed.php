<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     * This migration checks the current structure of branches table
     * and adds missing columns or recreates the table if needed.
     */
    public function up(): void
    {
        // Check if branches table exists
        if (!Schema::hasTable('branches')) {
            // Table doesn't exist, create it with all columns
            Schema::create('branches', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('email')->unique();
                $table->string('password');
                $table->rememberToken();
                $table->decimal('latitude', 10, 8)->nullable();
                $table->decimal('longitude', 11, 8)->nullable();
                $table->string('status')->default('active');
                $table->unsignedBigInteger('dashboard_user_id')->nullable();
                $table->timestamps();
                
                $table->foreign('dashboard_user_id')->references('id')->on('users')->onDelete('set null');
            });
            return;
        }
        
        // Table exists, check and add missing columns
        $connection = DB::connection();
        $dbName = $connection->getDatabaseName();
        
        $columns = DB::select(
            "SELECT COLUMN_NAME FROM information_schema.COLUMNS 
             WHERE TABLE_SCHEMA = ? AND TABLE_NAME = 'branches'
             ORDER BY ORDINAL_POSITION",
            [$dbName]
        );
        
        $existingColumns = array_column($columns, 'COLUMN_NAME');
        
        // Add columns in the correct order
        Schema::table('branches', function (Blueprint $table) use ($existingColumns) {
            // Add id if missing (shouldn't happen, but just in case)
            if (!in_array('id', $existingColumns)) {
                $table->id()->first();
            }
            
            // Add name if missing (add after id)
            if (!in_array('name', $existingColumns)) {
                $table->string('name')->after('id');
            }
            
            // Add email if missing (add after name)
            if (!in_array('email', $existingColumns)) {
                $table->string('email')->unique()->after('name');
            } else {
                // Email exists, ensure unique constraint
                try {
                    // Check if unique constraint exists
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
            
            // Add password if missing (add after email)
            if (!in_array('password', $existingColumns)) {
                $table->string('password')->after('email');
            }
            
            // Add remember_token if missing (add after password)
            if (!in_array('remember_token', $existingColumns)) {
                $table->rememberToken()->after('password');
            }
            
            // Add latitude if missing
            if (!in_array('latitude', $existingColumns)) {
                $table->decimal('latitude', 10, 8)->nullable()->after('remember_token');
            }
            
            // Add longitude if missing
            if (!in_array('longitude', $existingColumns)) {
                $table->decimal('longitude', 11, 8)->nullable()->after('latitude');
            }
            
            // Add status if missing
            if (!in_array('status', $existingColumns)) {
                $table->string('status')->default('active')->after('longitude');
            }
            
            // Add dashboard_user_id if missing
            if (!in_array('dashboard_user_id', $existingColumns)) {
                $table->unsignedBigInteger('dashboard_user_id')->nullable()->after('status');
            }
            
            // Add timestamps if missing
            if (!in_array('created_at', $existingColumns)) {
                $table->timestamps();
            }
        });
        
        // Add foreign key constraint if it doesn't exist
        try {
            $foreignKeys = DB::select(
                "SELECT CONSTRAINT_NAME FROM information_schema.KEY_COLUMN_USAGE 
                 WHERE TABLE_SCHEMA = ? AND TABLE_NAME = 'branches' 
                 AND COLUMN_NAME = 'dashboard_user_id' AND REFERENCED_TABLE_NAME IS NOT NULL",
                [$dbName]
            );
            if (empty($foreignKeys)) {
                DB::statement('ALTER TABLE branches ADD CONSTRAINT branches_dashboard_user_id_foreign 
                    FOREIGN KEY (dashboard_user_id) REFERENCES users(id) ON DELETE SET NULL');
            }
        } catch (\Exception $e) {
            // Foreign key might already exist, ignore
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Don't drop the table in down() to preserve data
        // If you need to rollback, do it manually
    }
};

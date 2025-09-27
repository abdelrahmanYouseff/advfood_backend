<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            // Change the default value for status column
            $table->enum('status', ['pending', 'confirmed', 'preparing', 'ready', 'delivering', 'delivered', 'cancelled'])
                  ->default('confirmed')
                  ->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            // Revert back to pending as default
            $table->enum('status', ['pending', 'confirmed', 'preparing', 'ready', 'delivering', 'delivered', 'cancelled'])
                  ->default('pending')
                  ->change();
        });
    }
};

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
        Schema::table('zyda_orders', function (Blueprint $table) {
            // Change location column from string (255 chars) to text to support long Google Maps URLs
            $table->text('location')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('zyda_orders', function (Blueprint $table) {
            // Revert back to string (255 chars) - Note: This may truncate existing long URLs
            $table->string('location', 255)->nullable()->change();
        });
    }
};

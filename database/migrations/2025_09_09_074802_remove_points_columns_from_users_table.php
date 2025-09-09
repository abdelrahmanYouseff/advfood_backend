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
        Schema::table('users', function (Blueprint $table) {
            // Check if columns exist before dropping
            if (Schema::hasColumn('users', 'points')) {
                $table->dropColumn('points');
            }
            if (Schema::hasColumn('users', 'points_tier')) {
                $table->dropColumn('points_tier');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->integer('points')->default(0)->after('point_customer_id');
            $table->string('points_tier')->default('bronze')->after('points');
            $table->index('points');
            $table->index('points_tier');
        });
    }
};
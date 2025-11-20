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
            $table->string('zyda_order_key')->nullable()->unique()->after('id');
            // Add index for faster lookups
            $table->index('zyda_order_key');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('zyda_orders', function (Blueprint $table) {
            $table->dropIndex(['zyda_order_key']);
            $table->dropColumn('zyda_order_key');
        });
    }
};

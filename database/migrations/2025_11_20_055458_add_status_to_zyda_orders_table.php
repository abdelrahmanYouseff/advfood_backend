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
            $table->string('status')->default('pending')->after('order_id'); // pending, received
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('zyda_orders', function (Blueprint $table) {
            $table->dropColumn('status');
        });
    }
};

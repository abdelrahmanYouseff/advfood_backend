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
            $table->string('shop_id', 50)->nullable()->after('status');
            $table->string('dsp_order_id', 100)->nullable()->after('shop_id');
            $table->string('shipping_status', 50)->default('New Order')->nullable()->after('dsp_order_id');
            $table->string('driver_name', 191)->nullable()->after('shipping_status');
            $table->string('driver_phone', 20)->nullable()->after('driver_name');
            $table->decimal('driver_latitude', 10, 6)->nullable()->after('driver_phone');
            $table->decimal('driver_longitude', 10, 6)->nullable()->after('driver_latitude');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn([
                'shop_id',
                'dsp_order_id',
                'shipping_status',
                'driver_name',
                'driver_phone',
                'driver_latitude',
                'driver_longitude',
            ]);
        });
    }
};



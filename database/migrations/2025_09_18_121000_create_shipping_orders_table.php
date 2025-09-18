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
        Schema::create('shipping_orders', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('order_id');
            $table->string('shop_id', 50);
            $table->string('dsp_order_id', 100)->nullable();
            $table->string('shipping_status', 50)->default('New Order');
            $table->string('recipient_name', 191);
            $table->string('recipient_phone', 20);
            $table->text('recipient_address');
            $table->decimal('latitude', 10, 6)->nullable();
            $table->decimal('longitude', 10, 6)->nullable();
            $table->string('driver_name', 191)->nullable();
            $table->string('driver_phone', 20)->nullable();
            $table->decimal('driver_latitude', 10, 6)->nullable();
            $table->decimal('driver_longitude', 10, 6)->nullable();
            $table->decimal('total', 10, 2);
            $table->tinyInteger('payment_type');
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->foreign('order_id')->references('id')->on('orders')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('shipping_orders');
    }
};



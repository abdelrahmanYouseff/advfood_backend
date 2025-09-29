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
        Schema::create('delivery_trip_orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('delivery_trip_id')->constrained()->onDelete('cascade');
            $table->foreignId('order_id')->constrained()->onDelete('cascade');
            $table->integer('sequence_order')->default(1); // ترتيب التسليم
            $table->enum('delivery_status', ['pending', 'picked_up', 'delivered', 'failed'])->default('pending');
            $table->timestamp('picked_up_at')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->text('delivery_notes')->nullable();
            $table->decimal('delivery_fee', 8, 2)->nullable();
            $table->timestamps();

            $table->unique(['delivery_trip_id', 'order_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('delivery_trip_orders');
    }
};

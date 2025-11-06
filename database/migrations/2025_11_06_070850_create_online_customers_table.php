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
        Schema::create('online_customers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('restaurant_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('link_order_id')->nullable()->constrained('link_orders')->nullOnDelete();
            $table->foreignId('order_id')->nullable()->constrained()->nullOnDelete();
            $table->string('full_name');
            $table->string('phone_number');
            $table->string('building_no')->nullable();
            $table->string('floor')->nullable();
            $table->string('apartment_number')->nullable();
            $table->string('street')->nullable();
            $table->text('note')->nullable();
            $table->decimal('customer_latitude', 10, 7)->nullable();
            $table->decimal('customer_longitude', 10, 7)->nullable();
            $table->string('source')->nullable();
            $table->string('latest_status')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('online_customers');
    }
};

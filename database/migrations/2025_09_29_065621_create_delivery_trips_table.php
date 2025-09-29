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
        Schema::create('delivery_trips', function (Blueprint $table) {
            $table->id();
            $table->string('trip_number')->unique(); // رقم الرحلة
            $table->string('driver_name'); // اسم السائق
            $table->string('driver_phone'); // هاتف السائق
            $table->string('vehicle_type'); // نوع المركبة (دراجة، سيارة، شاحنة)
            $table->string('vehicle_number'); // رقم المركبة
            $table->enum('status', ['pending', 'in_progress', 'completed', 'cancelled'])->default('pending');
            $table->timestamp('started_at')->nullable(); // وقت بداية الرحلة
            $table->timestamp('completed_at')->nullable(); // وقت انتهاء الرحلة
            $table->text('notes')->nullable(); // ملاحظات
            $table->decimal('total_distance', 8, 2)->nullable(); // المسافة الإجمالية
            $table->decimal('fuel_cost', 8, 2)->nullable(); // تكلفة الوقود
            $table->decimal('driver_fee', 8, 2)->nullable(); // أجر السائق
            $table->decimal('total_cost', 8, 2)->nullable(); // التكلفة الإجمالية
            $table->json('route_data')->nullable(); // بيانات المسار
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('delivery_trips');
    }
};

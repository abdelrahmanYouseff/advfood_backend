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
        Schema::create('addresses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('title')->nullable(); // عنوان للعنوان مثل "المنزل", "العمل"
            $table->text('address'); // العنوان النصي
            $table->string('building_number')->nullable(); // رقم المبنى
            $table->string('floor')->nullable(); // الطابق
            $table->string('apartment')->nullable(); // رقم الشقة
            $table->string('landmark')->nullable(); // معلم قريب
            $table->decimal('latitude', 10, 8); // خط العرض
            $table->decimal('longitude', 11, 8); // خط الطول
            $table->boolean('is_default')->default(false); // العنوان الافتراضي
            $table->boolean('is_active')->default(true); // نشط أم لا
            $table->timestamps();
            
            // فهرس للبحث السريع
            $table->index(['user_id', 'is_default']);
            $table->index(['latitude', 'longitude']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('addresses');
    }
};

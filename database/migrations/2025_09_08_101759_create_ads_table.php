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
        Schema::create('ads', function (Blueprint $table) {
            $table->id();
            $table->string('title'); // عنوان الإعلان
            $table->text('description')->nullable(); // وصف الإعلان
            $table->string('image'); // صورة الإعلان
            $table->string('link')->nullable(); // رابط الإعلان (اختياري)
            $table->enum('type', ['banner', 'popup', 'sidebar'])->default('banner'); // نوع الإعلان
            $table->enum('position', ['top', 'bottom', 'left', 'right', 'center'])->default('top'); // موقع الإعلان
            $table->boolean('is_active')->default(true); // نشط أم لا
            $table->dateTime('start_date')->nullable(); // تاريخ بداية الإعلان
            $table->dateTime('end_date')->nullable(); // تاريخ انتهاء الإعلان
            $table->integer('clicks_count')->default(0); // عدد النقرات
            $table->integer('views_count')->default(0); // عدد المشاهدات
            $table->integer('sort_order')->default(0); // ترتيب الإعلان
            $table->timestamps();

            // فهارس للبحث السريع
            $table->index(['is_active', 'type']);
            $table->index(['start_date', 'end_date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ads');
    }
};

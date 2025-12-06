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
        Schema::create('whatsapp_msg', function (Blueprint $table) {
            $table->id();

            // محتوى رسالة "تسليم الطلب" أو أي نص مستلم من الواتساب
            $table->text('deliver_order')->nullable();

            // نص الموقع / رابط اللوكيشن / أي بيانات خاصة بالمكان
            $table->text('location')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('whatsapp_msg');
    }
};



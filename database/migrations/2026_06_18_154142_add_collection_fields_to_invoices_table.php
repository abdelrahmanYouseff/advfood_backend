<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->boolean('is_collected')->default(false)->after('notes');
            $table->timestamp('collected_at')->nullable()->after('is_collected');
            $table->string('collected_by')->nullable()->after('collected_at'); // اسم الموظف
        });
    }

    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->dropColumn(['is_collected', 'collected_at', 'collected_by']);
        });
    }
};

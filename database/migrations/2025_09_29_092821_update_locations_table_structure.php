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
        Schema::table('locations', function (Blueprint $table) {
            // Add new columns if they don't exist
            if (!Schema::hasColumn('locations', 'address_text')) {
                $table->text('address_text')->after('longitude');
            }
            if (!Schema::hasColumn('locations', 'city')) {
                $table->string('city', 100)->nullable()->after('address_text');
            }
            if (!Schema::hasColumn('locations', 'area')) {
                $table->string('area', 100)->nullable()->after('city');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('locations', function (Blueprint $table) {
            $table->dropColumn(['address_text', 'city', 'area']);
        });
    }
};

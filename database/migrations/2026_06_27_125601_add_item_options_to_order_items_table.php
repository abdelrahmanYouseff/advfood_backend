<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('order_items', function (Blueprint $table) {
            // Stores selected options/variants for a product.
            // Each entry is an object with { name: string, quantity: number }.
            // Example: [{"name":"كبير","quantity":1},{"name":"بدون بصل","quantity":1}]
            $table->json('item_options')->nullable()->after('special_instructions');
        });
    }

    public function down(): void
    {
        Schema::table('order_items', function (Blueprint $table) {
            $table->dropColumn('item_options');
        });
    }
};

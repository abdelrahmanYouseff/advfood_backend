<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Add index on deliver_order column for faster lookups when searching for Zyda order keys.
     * Using prefix index (first 100 characters) since deliver_order is TEXT type.
     */
    public function up(): void
    {
        // Use raw SQL to create prefix index on TEXT column
        // This is faster for LIKE queries that start with the search term
        DB::statement('CREATE INDEX whatsapp_msg_deliver_order_index ON whatsapp_msg (deliver_order(100))');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement('DROP INDEX whatsapp_msg_deliver_order_index ON whatsapp_msg');
    }
};

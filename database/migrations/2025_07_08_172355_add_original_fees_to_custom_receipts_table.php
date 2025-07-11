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
        Schema::table('custom_receipts', function (Blueprint $table) {
            $table->json('original_fees')->nullable()->after('fees'); // Store the original system-generated fees
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('custom_receipts', function (Blueprint $table) {
            $table->dropColumn('original_fees');
        });
    }
};

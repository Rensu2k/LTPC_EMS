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
            $table->enum('fund_type', ['General Fund', 'Trust Fund'])->default('General Fund')->after('type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('custom_receipts', function (Blueprint $table) {
            $table->dropColumn('fund_type');
        });
    }
};

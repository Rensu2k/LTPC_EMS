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
            $table->string('program_id', 50)->nullable()->after('trainee_model_id');
            $table->foreign('program_id')->references('program_id')->on('programs')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('custom_receipts', function (Blueprint $table) {
            $table->dropForeign(['program_id']);
            $table->dropColumn('program_id');
        });
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // First, update existing data to use new values
        DB::table('assessments')
            ->where('result', 'pass')
            ->update(['result' => 'competent']);
            
        DB::table('assessments')
            ->where('result', 'fail')
            ->update(['result' => 'not_yet_competent']);

        // Then modify the column to use new enum values
        Schema::table('assessments', function (Blueprint $table) {
            $table->enum('result', ['competent', 'not_yet_competent', 'absent'])->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // First, update existing data back to old values
        DB::table('assessments')
            ->where('result', 'competent')
            ->update(['result' => 'pass']);
            
        DB::table('assessments')
            ->where('result', 'not_yet_competent')
            ->update(['result' => 'fail']);

        // Then modify the column back to old enum values
        Schema::table('assessments', function (Blueprint $table) {
            $table->enum('result', ['pass', 'fail', 'absent'])->nullable()->change();
        });
    }
};

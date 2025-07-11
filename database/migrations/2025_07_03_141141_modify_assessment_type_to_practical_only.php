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
        // First, update all existing records to 'practical' if they're not already
        DB::table('assessments')->whereIn('type', ['theoretical', 'both'])->update(['type' => 'practical']);
        
        // Then modify the enum column
        Schema::table('assessments', function (Blueprint $table) {
            $table->enum('type', ['practical'])->default('practical')->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('assessments', function (Blueprint $table) {
            $table->enum('type', ['practical', 'theoretical', 'both'])->default('theoretical')->change();
        });
    }
};

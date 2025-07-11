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
        // First, update existing status values to map to new system
        DB::table('assessments')->where('status', 'pass')->update(['status' => 'completed']);
        DB::table('assessments')->where('status', 'fail')->update(['status' => 'completed']);
        DB::table('assessments')->where('status', 'graded')->update(['status' => 'completed']);
        
        Schema::table('assessments', function (Blueprint $table) {
            // Drop the old scoring columns
            $table->dropColumn(['max_score', 'passing_score']);
            
            // Change score from integer to enum for competency evaluation
            $table->dropColumn('score');
        });
        
        Schema::table('assessments', function (Blueprint $table) {
            // Add the new competency-based score column
            $table->enum('score', ['competent', 'not_yet_competent', 'absent'])->nullable()->after('status');
            
            // Update status enum to remove pass/fail/graded options
            $table->enum('status', ['pending', 'completed'])->default('pending')->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('assessments', function (Blueprint $table) {
            // Restore the old scoring system
            $table->dropColumn('score');
        });
        
        Schema::table('assessments', function (Blueprint $table) {
            // Restore old columns
            $table->integer('score')->nullable()->after('status');
            $table->integer('max_score')->after('score');
            $table->integer('passing_score')->nullable()->after('max_score');
            
            // Restore old status enum
            $table->enum('status', ['pending', 'completed', 'graded', 'pass', 'fail'])->default('pending')->change();
        });
    }
};

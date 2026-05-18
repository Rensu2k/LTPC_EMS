<?php
/**
 * LTPC Enrollment Management System (LTPC_EMS)
 *
 * @copyright  2025-2026 Clarence Buenaflor & Jester Pastor
 * @author     Clarence Buenaflor <cbuenaflor2@ssct.edu.ph>
 * @author     Jester Pastor <pastorjester98@mail.com>
 * @license    Proprietary - All Rights Reserved
 *
 * Unauthorized copying, modification, or distribution of this
 * software is strictly prohibited without express written permission.
 */
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
        DB::table('assessments')->where('status', 'pass')->update(['status' => 'completed']);
        DB::table('assessments')->where('status', 'fail')->update(['status' => 'completed']);
        DB::table('assessments')->where('status', 'graded')->update(['status' => 'completed']);
        
        Schema::table('assessments', function (Blueprint $table) {
            $table->dropColumn(['max_score', 'passing_score']);
            
            $table->dropColumn('score');
        });
        
        Schema::table('assessments', function (Blueprint $table) {
            $table->enum('score', ['competent', 'not_yet_competent', 'absent'])->nullable()->after('status');
            
            $table->enum('status', ['pending', 'completed'])->default('pending')->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('assessments', function (Blueprint $table) {
            $table->dropColumn('score');
        });
        
        Schema::table('assessments', function (Blueprint $table) {
            $table->integer('score')->nullable()->after('status');
            $table->integer('max_score')->after('score');
            $table->integer('passing_score')->nullable()->after('max_score');
            
            $table->enum('status', ['pending', 'completed', 'graded', 'pass', 'fail'])->default('pending')->change();
        });
    }
};

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
        DB::statement("ALTER TABLE trainee_enrollments MODIFY COLUMN status ENUM('active', 'completed', 'dropped', 'suspended', 'pending') NOT NULL DEFAULT 'active'");

        DB::table('trainee_enrollments')
            ->where('status', 'suspended')
            ->update(['status' => 'pending']);

        DB::statement("ALTER TABLE trainee_enrollments MODIFY COLUMN status ENUM('active', 'completed', 'dropped', 'pending') NOT NULL DEFAULT 'active'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('trainee_enrollments')
            ->where('status', 'pending')
            ->update(['status' => 'suspended']);

        DB::statement("ALTER TABLE trainee_enrollments MODIFY COLUMN status ENUM('active', 'completed', 'dropped', 'suspended') NOT NULL DEFAULT 'active'");
    }
};

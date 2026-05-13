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
        // First, add 'pending' to the enum temporarily alongside 'suspended'
        DB::statement("ALTER TABLE trainees MODIFY COLUMN status ENUM('active', 'completed', 'dropped', 'suspended', 'pending') NOT NULL DEFAULT 'suspended'");

        // Then update any existing 'suspended' status to 'pending'
        DB::table('trainees')
            ->where('status', 'suspended')
            ->update(['status' => 'pending']);

        // Finally, remove 'suspended' from the enum and change default to 'pending'
        DB::statement("ALTER TABLE trainees MODIFY COLUMN status ENUM('active', 'completed', 'dropped', 'pending') NOT NULL DEFAULT 'pending'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // First update any 'pending' status back to 'suspended'
        DB::table('trainees')
            ->where('status', 'pending')
            ->update(['status' => 'suspended']);

        // Then revert the enum back to the original values
        DB::statement("ALTER TABLE trainees MODIFY COLUMN status ENUM('active', 'completed', 'dropped', 'suspended') NOT NULL DEFAULT 'suspended'");
    }
};

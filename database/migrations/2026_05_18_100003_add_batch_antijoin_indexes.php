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

/**
 * Add a covering index for the NOT EXISTS anti-join used in batch counting.
 *
 * The legacy trainee check in getNextAvailableBatch() and getEnrollmentCountForBatch()
 * runs: NOT EXISTS (SELECT 1 FROM trainee_enrollments WHERE trainee_id = ? AND program_id = ? AND status IN (?))
 * This composite index allows the correlated subquery to resolve with a single
 * index probe per trainee, reducing batch lookup from ~4s to <500ms.
 *
 * Also adds a covering index on trainees for the batch/status/program filter.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('trainee_enrollments', function (Blueprint $table) {
            $table->index(
                ['trainee_id', 'program_id', 'status'],
                'idx_enrollments_trainee_program_status'
            );
        });

        Schema::table('trainees', function (Blueprint $table) {
            $table->index(
                ['program_qualification', 'status', 'batch'],
                'idx_trainees_program_status_batch'
            );
        });
    }

    public function down(): void
    {
        Schema::table('trainee_enrollments', function (Blueprint $table) {
            $table->dropIndex('idx_enrollments_trainee_program_status');
        });

        Schema::table('trainees', function (Blueprint $table) {
            $table->dropIndex('idx_trainees_program_status_batch');
        });
    }
};

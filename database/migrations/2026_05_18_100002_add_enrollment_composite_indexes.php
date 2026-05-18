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
 * Add composite indexes to trainee_enrollments for marginal COUNT queries.
 *
 * Benchmark queries #24 (Enrollment COUNT: 511ms) and #25 (Enrollment active
 * count: 695ms) are borderline. These composite indexes allow the database to
 * satisfy the most common filter+count combinations from a single index scan
 * without touching the main table data.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('trainee_enrollments', function (Blueprint $table) {
            // Covers: SUM(enrollment_fee) WHERE payment_status = 'paid'
            $table->index(['payment_status', 'enrollment_fee'], 'idx_enrollments_payment_fee');
            // Covers: COUNT(*) WHERE status = 'active' (and other status queries)
            $table->index(['status', 'payment_status'], 'idx_enrollments_status_payment');
        });
    }

    public function down(): void
    {
        Schema::table('trainee_enrollments', function (Blueprint $table) {
            $table->dropIndex('idx_enrollments_payment_fee');
            $table->dropIndex('idx_enrollments_status_payment');
        });
    }
};

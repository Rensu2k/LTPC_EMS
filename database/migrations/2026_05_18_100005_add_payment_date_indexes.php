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
 * Add covering indexes on payment_date for Cashier Dashboard stats.
 *
 * calculateDashboardStats() and getPaymentSummaries() heavily filter by
 * (payment_status, payment_date) on both trainee_enrollments and assessments.
 * Without these indexes, each date-range SUM/COUNT is a full table scan.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('trainee_enrollments', function (Blueprint $table) {
            $table->index(['payment_status', 'payment_date'], 'idx_enrollments_pay_status_date');
        });

        Schema::table('assessments', function (Blueprint $table) {
            $table->index(['payment_status', 'payment_date'], 'idx_assessments_pay_status_date');
        });

        Schema::table('custom_receipts', function (Blueprint $table) {
            $table->index(['status', 'date_generated'], 'idx_receipts_status_date');
        });
    }

    public function down(): void
    {
        Schema::table('trainee_enrollments', function (Blueprint $table) {
            $table->dropIndex('idx_enrollments_pay_status_date');
        });

        Schema::table('assessments', function (Blueprint $table) {
            $table->dropIndex('idx_assessments_pay_status_date');
        });

        Schema::table('custom_receipts', function (Blueprint $table) {
            $table->dropIndex('idx_receipts_status_date');
        });
    }
};

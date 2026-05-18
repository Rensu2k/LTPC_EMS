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
 * Add database indexes required for scalability at government-scale volumes.
 *
 * The trainees table had ZERO indexes beyond the primary key. At millions of
 * rows, every filter, search, and join would be a full table scan. This
 * migration adds targeted indexes for all commonly queried columns across
 * the five core tables.
 */
return new class extends Migration
{
    public function up(): void
    {
        // ── trainees ─────────────────────────────────────────────────
        // Previously had NO indexes besides PK. Every controller query
        // (search, filter by status/program/scholarship) was a full scan.
        Schema::table('trainees', function (Blueprint $table) {
            $table->index('status', 'idx_trainees_status');
            $table->index('payment_status', 'idx_trainees_payment_status');
            $table->index('program_qualification', 'idx_trainees_program_qualification');
            $table->index(['last_name', 'first_name'], 'idx_trainees_name');
            $table->index('scholarship_package', 'idx_trainees_scholarship');
            $table->index('created_at', 'idx_trainees_created_at');
        });

        // ── assessments ──────────────────────────────────────────────
        // Had only FK indexes. Dashboard aggregation queries and cashier
        // payment queries filter by status and payment_status constantly.
        Schema::table('assessments', function (Blueprint $table) {
            $table->index('status', 'idx_assessments_status');
            $table->index('payment_status', 'idx_assessments_payment_status');
            $table->index('applicant_type', 'idx_assessments_applicant_type');
            $table->index(['program_id', 'trainee_id'], 'idx_assessments_program_trainee');
        });

        // ── custom_receipts ──────────────────────────────────────────
        // Receipt queries always filter by type + status combination.
        Schema::table('custom_receipts', function (Blueprint $table) {
            $table->index(['type', 'status'], 'idx_receipts_type_status');
            $table->index(['trainee_model_id', 'type', 'status'], 'idx_receipts_trainee_type_status');
        });

        // ── trainee_enrollments ──────────────────────────────────────
        // Already had (trainee_id, status) and (program_id, status) indexes.
        // Adding payment_status for cashier dashboard and batch+status for
        // batch enrollment counting.
        Schema::table('trainee_enrollments', function (Blueprint $table) {
            $table->index('payment_status', 'idx_enrollments_payment_status');
            $table->index(['program_id', 'batch', 'status'], 'idx_enrollments_program_batch_status');
            $table->index('created_at', 'idx_enrollments_created_at');
        });
    }

    public function down(): void
    {
        Schema::table('trainees', function (Blueprint $table) {
            $table->dropIndex('idx_trainees_status');
            $table->dropIndex('idx_trainees_payment_status');
            $table->dropIndex('idx_trainees_program_qualification');
            $table->dropIndex('idx_trainees_name');
            $table->dropIndex('idx_trainees_scholarship');
            $table->dropIndex('idx_trainees_created_at');
        });

        Schema::table('assessments', function (Blueprint $table) {
            $table->dropIndex('idx_assessments_status');
            $table->dropIndex('idx_assessments_payment_status');
            $table->dropIndex('idx_assessments_applicant_type');
            $table->dropIndex('idx_assessments_program_trainee');
        });

        Schema::table('custom_receipts', function (Blueprint $table) {
            $table->dropIndex('idx_receipts_type_status');
            $table->dropIndex('idx_receipts_trainee_type_status');
        });

        Schema::table('trainee_enrollments', function (Blueprint $table) {
            $table->dropIndex('idx_enrollments_payment_status');
            $table->dropIndex('idx_enrollments_program_batch_status');
            $table->dropIndex('idx_enrollments_created_at');
        });
    }
};

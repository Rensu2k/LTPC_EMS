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

return new class extends Migration
{
    /**
     * Run the migrations.
     * Adds indexes to support the scalability fixes for the Assessment pages.
     */
    public function up(): void
    {
        Schema::table('assessments', function (Blueprint $table) {
            if (!$this->indexExists('assessments', 'idx_assessments_original_id')) {
                $table->index('original_assessment_id', 'idx_assessments_original_id');
            }

            if (!$this->indexExists('assessments', 'idx_assessments_competent_trainee')) {
                $table->index(['result', 'trainee_id', 'program_id'], 'idx_assessments_competent_trainee');
            }

            if (!$this->indexExists('assessments', 'idx_assessments_grouping')) {
                $table->index(['trainee_id', 'type', 'program_id', 'applicant_type'], 'idx_assessments_grouping');
            }

            if (!$this->indexExists('assessments', 'idx_assessments_status_result')) {
                $table->index(['status', 'result'], 'idx_assessments_status_result');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('assessments', function (Blueprint $table) {
            $table->dropIndex('idx_assessments_original_id');
            $table->dropIndex('idx_assessments_competent_trainee');
            $table->dropIndex('idx_assessments_grouping');
            $table->dropIndex('idx_assessments_status_result');
        });
    }

    /**
     * Check if an index already exists.
     */
    private function indexExists(string $table, string $indexName): bool
    {
        $indexes = Schema::getIndexes($table);
        foreach ($indexes as $index) {
            if ($index['name'] === $indexName) {
                return true;
            }
        }
        return false;
    }
};

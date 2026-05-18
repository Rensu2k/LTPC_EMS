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
     */
    public function up(): void
    {
        if (!Schema::hasTable('courses')) {
            return;
        }

        if (Schema::hasTable('assessments') && Schema::hasColumn('assessments', 'course_id')) {
            Schema::table('assessments', function (Blueprint $table) {
                $table->dropForeign(['course_id']);
            });
        }

        if (Schema::hasTable('trainings') && Schema::hasColumn('trainings', 'course_id')) {
            Schema::table('trainings', function (Blueprint $table) {
                $table->dropForeign(['course_id']);
            });
        }

        Schema::rename('courses', 'programs');

        if (Schema::hasColumn('programs', 'course_id')) {
            Schema::table('programs', function (Blueprint $table) {
                $table->renameColumn('course_id', 'program_id');
            });
        }

        if (Schema::hasTable('assessments') && Schema::hasColumn('assessments', 'course_id')) {
            Schema::table('assessments', function (Blueprint $table) {
                $table->renameColumn('course_id', 'program_id');
            });
        }

        if (Schema::hasTable('trainings') && Schema::hasColumn('trainings', 'course_id')) {
            Schema::table('trainings', function (Blueprint $table) {
                $table->renameColumn('course_id', 'program_id');
            });
        }

        if (Schema::hasTable('trainees') && Schema::hasColumn('trainees', 'course_qualification')) {
            Schema::table('trainees', function (Blueprint $table) {
                $table->renameColumn('course_qualification', 'program_qualification');
            });
        }

        if (Schema::hasTable('assessments') && Schema::hasColumn('assessments', 'program_id')) {
            Schema::table('assessments', function (Blueprint $table) {
                $table->foreign('program_id')->references('program_id')->on('programs')->onDelete('cascade');
            });
        }

        if (Schema::hasTable('trainings') && Schema::hasColumn('trainings', 'program_id')) {
            Schema::table('trainings', function (Blueprint $table) {
                $table->foreign('program_id')->references('program_id')->on('programs')->onDelete('cascade');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (!Schema::hasTable('programs')) {
            return;
        }

        if (Schema::hasTable('assessments') && Schema::hasColumn('assessments', 'program_id')) {
            Schema::table('assessments', function (Blueprint $table) {
                $table->dropForeign(['program_id']);
            });
        }

        if (Schema::hasTable('trainings') && Schema::hasColumn('trainings', 'program_id')) {
            Schema::table('trainings', function (Blueprint $table) {
                $table->dropForeign(['program_id']);
            });
        }

        if (Schema::hasTable('assessments') && Schema::hasColumn('assessments', 'program_id')) {
            Schema::table('assessments', function (Blueprint $table) {
                $table->renameColumn('program_id', 'course_id');
            });
        }

        if (Schema::hasTable('trainings') && Schema::hasColumn('trainings', 'program_id')) {
            Schema::table('trainings', function (Blueprint $table) {
                $table->renameColumn('program_id', 'course_id');
            });
        }

        if (Schema::hasTable('trainees') && Schema::hasColumn('trainees', 'program_qualification')) {
            Schema::table('trainees', function (Blueprint $table) {
                $table->renameColumn('program_qualification', 'course_qualification');
            });
        }

        if (Schema::hasColumn('programs', 'program_id')) {
            Schema::table('programs', function (Blueprint $table) {
                $table->renameColumn('program_id', 'course_id');
            });
        }

        Schema::rename('programs', 'courses');

        if (Schema::hasTable('assessments') && Schema::hasColumn('assessments', 'course_id')) {
            Schema::table('assessments', function (Blueprint $table) {
                $table->foreign('course_id')->references('course_id')->on('courses')->onDelete('cascade');
            });
        }

        if (Schema::hasTable('trainings') && Schema::hasColumn('trainings', 'course_id')) {
            Schema::table('trainings', function (Blueprint $table) {
                $table->foreign('course_id')->references('course_id')->on('courses')->onDelete('cascade');
            });
        }
    }
}; 
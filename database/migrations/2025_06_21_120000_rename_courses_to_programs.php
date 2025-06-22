<?php

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
        // Only proceed if the courses table exists (meaning this is an existing installation)
        if (!Schema::hasTable('courses')) {
            // This is a fresh installation, skip this migration
            return;
        }

        // First, drop foreign key constraints that reference the courses table
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

        // Rename the courses table to programs
        Schema::rename('courses', 'programs');

        // Rename course_id column to program_id in the programs table
        if (Schema::hasColumn('programs', 'course_id')) {
            Schema::table('programs', function (Blueprint $table) {
                $table->renameColumn('course_id', 'program_id');
            });
        }

        // Update assessments table
        if (Schema::hasTable('assessments') && Schema::hasColumn('assessments', 'course_id')) {
            Schema::table('assessments', function (Blueprint $table) {
                $table->renameColumn('course_id', 'program_id');
            });
        }

        // Update trainings table
        if (Schema::hasTable('trainings') && Schema::hasColumn('trainings', 'course_id')) {
            Schema::table('trainings', function (Blueprint $table) {
                $table->renameColumn('course_id', 'program_id');
            });
        }

        // Update trainees table - rename course_qualification to program_qualification
        if (Schema::hasTable('trainees') && Schema::hasColumn('trainees', 'course_qualification')) {
            Schema::table('trainees', function (Blueprint $table) {
                $table->renameColumn('course_qualification', 'program_qualification');
            });
        }

        // Re-add foreign key constraints
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
        // Only proceed if the programs table exists
        if (!Schema::hasTable('programs')) {
            return;
        }

        // Drop foreign key constraints
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

        // Rename program_id back to course_id in related tables
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

        // Rename program_id back to course_id in the programs table
        if (Schema::hasColumn('programs', 'program_id')) {
            Schema::table('programs', function (Blueprint $table) {
                $table->renameColumn('program_id', 'course_id');
            });
        }

        // Rename programs table back to courses
        Schema::rename('programs', 'courses');

        // Re-add foreign key constraints
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
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
        Schema::table('assessments', function (Blueprint $table) {
            // Drop the existing foreign key constraints
            $table->dropForeign(['course_id']);
            $table->dropForeign(['trainee_id']);
            $table->dropForeign(['trainer_id']);
            
            // Drop the columns
            $table->dropColumn(['course_id', 'trainee_id', 'trainer_id']);
        });
        
        Schema::table('assessments', function (Blueprint $table) {
            // Add the columns back with correct foreign key constraints
            $table->string('course_id', 50);
            $table->unsignedBigInteger('trainee_id')->nullable();
            $table->unsignedBigInteger('trainer_id');
            
            // Add foreign key constraints
            $table->foreign('course_id')->references('course_id')->on('courses')->onDelete('cascade');
            $table->foreign('trainee_id')->references('id')->on('trainees')->onDelete('cascade');
            $table->foreign('trainer_id')->references('id')->on('trainers')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('assessments', function (Blueprint $table) {
            // Drop the new foreign key constraints
            $table->dropForeign(['course_id']);
            $table->dropForeign(['trainee_id']);
            $table->dropForeign(['trainer_id']);
            
            // Drop the columns
            $table->dropColumn(['course_id', 'trainee_id', 'trainer_id']);
        });
        
        Schema::table('assessments', function (Blueprint $table) {
            // Add the columns back with original foreign key constraints
            $table->foreignId('course_id')->constrained()->onDelete('cascade');
            $table->foreignId('trainee_id')->constrained()->onDelete('cascade');
            $table->foreignId('trainer_id')->constrained()->onDelete('cascade');
        });
    }
};

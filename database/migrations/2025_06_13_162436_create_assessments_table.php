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
        Schema::defaultStringLength(191);
        Schema::create('assessments', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('description')->nullable();
            $table->enum('type', ['practical', 'theoretical', 'both'])->default('theoretical');
            $table->enum('status', ['pending', 'completed', 'graded'])->default('pending');
            $table->integer('score')->nullable();
            $table->integer('max_score');
            
            // Fix foreign key constraints to match actual table structures
            $table->string('course_id', 50); // Match courses.course_id
            $table->unsignedBigInteger('trainee_id'); // Match trainees.id
            $table->unsignedBigInteger('trainer_id'); // Match trainers.id
            
            // Add foreign key constraints with correct references
            $table->foreign('course_id')->references('course_id')->on('courses')->onDelete('cascade');
            $table->foreign('trainee_id')->references('id')->on('trainees')->onDelete('cascade');
            $table->foreign('trainer_id')->references('id')->on('trainers')->onDelete('cascade');
            
            $table->date('assessment_date');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('assessments');
    }
};

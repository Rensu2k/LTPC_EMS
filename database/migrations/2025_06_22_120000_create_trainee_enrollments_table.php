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
        Schema::create('trainee_enrollments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('trainee_id');
            $table->string('program_id', 50);
            $table->integer('batch')->default(1);
            $table->date('enrollment_date');
            $table->date('completion_date')->nullable();
            $table->enum('status', ['active', 'completed', 'dropped', 'suspended'])->default('active');
            $table->enum('payment_status', ['paid', 'unpaid', 'partial'])->default('unpaid');
            $table->decimal('enrollment_fee', 10, 2)->default(0);
            $table->string('payment_method')->nullable();
            $table->string('payment_reference')->nullable();
            $table->timestamp('payment_date')->nullable();
            $table->text('payment_notes')->nullable();
            $table->text('notes')->nullable(); // For any special notes about this enrollment
            $table->timestamps();

            // Foreign key constraints
            $table->foreign('trainee_id')->references('id')->on('trainees')->onDelete('cascade');
            $table->foreign('program_id')->references('program_id')->on('programs')->onDelete('cascade');
            
            // Ensure a trainee can only have one active enrollment per program
            $table->unique(['trainee_id', 'program_id', 'status'], 'unique_active_enrollment');
            
            // Index for performance
            $table->index(['trainee_id', 'status']);
            $table->index(['program_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('trainee_enrollments');
    }
}; 
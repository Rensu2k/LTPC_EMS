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
        Schema::create('custom_receipts', function (Blueprint $table) {
            $table->id();
            $table->string('receipt_number')->unique();
            $table->string('payment_id'); // Reference to original payment ID (ENR-001, ASS-001, etc.)
            $table->enum('type', ['enrollment', 'assessment']);
            
            // Trainee information
            $table->string('trainee_name');
            $table->string('trainee_id_number');
            $table->string('trainee_uli_number')->nullable();
            
            // Receipt details that can be customized
            $table->json('fees'); // Array of fees with course, amount, account_code
            $table->decimal('total_amount', 10, 2);
            $table->date('date_generated');
            $table->time('time_generated');
            $table->string('status')->default('generated');
            
            // Reference to original records
            $table->unsignedBigInteger('enrollment_id')->nullable();
            $table->unsignedBigInteger('assessment_id')->nullable();
            $table->unsignedBigInteger('trainee_model_id')->nullable();
            
            $table->timestamps();
            
            $table->foreign('enrollment_id')->references('id')->on('trainee_enrollments')->onDelete('cascade');
            $table->foreign('assessment_id')->references('id')->on('assessments')->onDelete('cascade');
            $table->foreign('trainee_model_id')->references('id')->on('trainees')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('custom_receipts');
    }
};

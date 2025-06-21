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
        Schema::create('courses', function (Blueprint $table) {
            $table->string('course_id', 50)->primary();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('duration'); // e.g., "8 weeks", "3 months"
            $table->json('assigned_trainers')->nullable(); // Store trainer IDs as JSON array
            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->integer('max_enrollments')->default(30);
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('courses');
    }
};

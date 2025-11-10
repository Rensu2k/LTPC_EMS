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
        Schema::dropIfExists('training_results');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::create('training_results', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('training_id');
            $table->unsignedBigInteger('trainee_id');
            $table->enum('completion_status', ['complete', 'incomplete'])->default('incomplete');
            $table->date('date_completed')->nullable();
            $table->timestamps();

            $table->foreign('training_id')->references('id')->on('trainings')->onDelete('cascade');
            $table->foreign('trainee_id')->references('id')->on('trainees')->onDelete('cascade');
        });
    }
};

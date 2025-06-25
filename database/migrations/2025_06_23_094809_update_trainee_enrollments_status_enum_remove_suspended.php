<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // First, add 'pending' to the enum temporarily alongside 'suspended'
        DB::statement("ALTER TABLE trainee_enrollments MODIFY COLUMN status ENUM('active', 'completed', 'dropped', 'suspended', 'pending') NOT NULL DEFAULT 'active'");

        // Then update any existing 'suspended' status to 'pending'
        DB::table('trainee_enrollments')
            ->where('status', 'suspended')
            ->update(['status' => 'pending']);

        // Finally, remove 'suspended' from the enum
        DB::statement("ALTER TABLE trainee_enrollments MODIFY COLUMN status ENUM('active', 'completed', 'dropped', 'pending') NOT NULL DEFAULT 'active'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // First update any 'pending' status back to 'suspended'
        DB::table('trainee_enrollments')
            ->where('status', 'pending')
            ->update(['status' => 'suspended']);

        // Then revert the enum back to the original values
        DB::statement("ALTER TABLE trainee_enrollments MODIFY COLUMN status ENUM('active', 'completed', 'dropped', 'suspended') NOT NULL DEFAULT 'active'");
    }
};

<?php
/**
 * LTPC Enrollment Management System (LTPC_EMS)
 *
 * @copyright  2025-2026 Clarence Buenaflor & Jester Pastor
 * @license    Proprietary - All Rights Reserved
 */

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Create system_metadata table and seed developer provenance records.
     *
     * This migration embeds authorship information directly in the database.
     * Even if the source code is copied, running migrations will automatically
     * create these records — serving as a persistent digital watermark.
     */
    public function up(): void
    {
        Schema::create('system_metadata', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->text('value');
            $table->timestamps();
        });

        DB::table('system_metadata')->insert([
            [
                'key'        => 'system.name',
                'value'      => 'LTPC Enrollment Management System',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'key'        => 'system.version',
                'value'      => '1.0.0',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'key'        => 'system.developers',
                'value'      => json_encode([
                    'team'     => 'Clarence Buenaflor & Jester Pastor',
                    'members'  => [
                        [
                            'name'  => 'Clarence Buenaflor',
                            'email' => 'cbuenaflor2@ssct.edu.ph',
                            'role'  => 'Lead Developer',
                        ],
                        [
                            'name'  => 'Jester Pastor',
                            'email' => 'pastorjester98@mail.com',
                            'role'  => 'Contributor',
                        ],
                    ],
                    'institution'   => 'SSCT - Surigao del Sur State College of Technology',
                    'developed_for' => 'Surigao City Livelihood Training and Productivity Center',
                    'inception'     => '2025-06',
                ]),
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'key'        => 'system.signature',
                'value'      => hash('sha256', 'LTPC_EMS:ClarenceBuenaflor:JesterPastor:2025:Surigao_City_LTPC'),
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('system_metadata');
    }
};

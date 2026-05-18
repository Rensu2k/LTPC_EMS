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
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $trainers = DB::table('trainers')->get();
        
        foreach ($trainers as $trainer) {
            if ($trainer->expertise && $trainer->expertise !== '') {
                $expertiseArray = [$trainer->expertise];
                DB::table('trainers')
                    ->where('id', $trainer->id)
                    ->update(['expertise' => json_encode($expertiseArray)]);
            } else {
                DB::table('trainers')
                    ->where('id', $trainer->id)
                    ->update(['expertise' => json_encode([])]);
            }
        }

        Schema::table('trainers', function (Blueprint $table) {
            $table->json('expertise')->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $trainers = DB::table('trainers')->get();
        
        foreach ($trainers as $trainer) {
            if ($trainer->expertise) {
                $expertiseArray = json_decode($trainer->expertise, true);
                $singleExpertise = is_array($expertiseArray) && !empty($expertiseArray) 
                    ? $expertiseArray[0] 
                    : '';
                    
                DB::table('trainers')
                    ->where('id', $trainer->id)
                    ->update(['expertise' => $singleExpertise]);
            }
        }

        Schema::table('trainers', function (Blueprint $table) {
            $table->string('expertise')->nullable()->change();
        });
    }
};

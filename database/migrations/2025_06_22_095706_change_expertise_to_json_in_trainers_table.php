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
        // First, convert existing single expertise values to JSON arrays
        $trainers = DB::table('trainers')->get();
        
        foreach ($trainers as $trainer) {
            if ($trainer->expertise && $trainer->expertise !== '') {
                // Convert single expertise to array format
                $expertiseArray = [$trainer->expertise];
                DB::table('trainers')
                    ->where('id', $trainer->id)
                    ->update(['expertise' => json_encode($expertiseArray)]);
            } else {
                // Set empty array for trainers with no expertise
                DB::table('trainers')
                    ->where('id', $trainer->id)
                    ->update(['expertise' => json_encode([])]);
            }
        }

        // Now change the column type to JSON
        Schema::table('trainers', function (Blueprint $table) {
            $table->json('expertise')->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Convert JSON arrays back to single string (take first expertise)
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

        // Change back to string column
        Schema::table('trainers', function (Blueprint $table) {
            $table->string('expertise')->nullable()->change();
        });
    }
};

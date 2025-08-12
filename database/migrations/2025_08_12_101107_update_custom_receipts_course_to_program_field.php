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
        // Update existing custom receipts to use 'program' field instead of 'course'
        $customReceipts = DB::table('custom_receipts')->get();
        
        foreach ($customReceipts as $receipt) {
            $fees = json_decode($receipt->fees, true);
            
            if (is_array($fees)) {
                $updatedFees = [];
                
                foreach ($fees as $fee) {
                    // If fee has 'course' field but no 'program' field, migrate it
                    if (isset($fee['course']) && !isset($fee['program'])) {
                        $fee['program'] = $fee['course'];
                        unset($fee['course']); // Remove old field
                    }
                    $updatedFees[] = $fee;
                }
                
                // Update the record
                DB::table('custom_receipts')
                    ->where('id', $receipt->id)
                    ->update(['fees' => json_encode($updatedFees)]);
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Revert back to 'course' field
        $customReceipts = DB::table('custom_receipts')->get();
        
        foreach ($customReceipts as $receipt) {
            $fees = json_decode($receipt->fees, true);
            
            if (is_array($fees)) {
                $updatedFees = [];
                
                foreach ($fees as $fee) {
                    // If fee has 'program' field, revert it back to 'course'
                    if (isset($fee['program'])) {
                        $fee['course'] = $fee['program'];
                        unset($fee['program']);
                    }
                    $updatedFees[] = $fee;
                }
                
                // Update the record
                DB::table('custom_receipts')
                    ->where('id', $receipt->id)
                    ->update(['fees' => json_encode($updatedFees)]);
            }
        }
    }
};
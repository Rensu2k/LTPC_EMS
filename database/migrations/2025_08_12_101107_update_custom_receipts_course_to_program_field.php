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
        $customReceipts = DB::table('custom_receipts')->get();
        
        foreach ($customReceipts as $receipt) {
            $fees = json_decode($receipt->fees, true);
            
            if (is_array($fees)) {
                $updatedFees = [];
                
                foreach ($fees as $fee) {
                    if (isset($fee['course']) && !isset($fee['program'])) {
                        $fee['program'] = $fee['course'];
                        unset($fee['course']); // Remove old field
                    }
                    $updatedFees[] = $fee;
                }
                
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
        $customReceipts = DB::table('custom_receipts')->get();
        
        foreach ($customReceipts as $receipt) {
            $fees = json_decode($receipt->fees, true);
            
            if (is_array($fees)) {
                $updatedFees = [];
                
                foreach ($fees as $fee) {
                    if (isset($fee['program'])) {
                        $fee['course'] = $fee['program'];
                        unset($fee['program']);
                    }
                    $updatedFees[] = $fee;
                }
                
                DB::table('custom_receipts')
                    ->where('id', $receipt->id)
                    ->update(['fees' => json_encode($updatedFees)]);
            }
        }
    }
};
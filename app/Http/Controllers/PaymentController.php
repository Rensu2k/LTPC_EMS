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
namespace App\Http\Controllers;

use App\Models\TraineeEnrollment;
use App\Models\Trainee;
use App\Models\Program;
use App\Models\CustomReceipt;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Carbon\Carbon;

class PaymentController extends Controller
{
    /**
     * Display payments for admin (read-only) - Centralized by trainee
     */
    public function adminIndex()
    {
        $customReceipts = CustomReceipt::with(['trainee', 'program', 'trainee.enrollments.program'])
            ->where('status', 'generated')
            ->orderBy('date_generated', 'desc')
            ->get()
            ->map(function ($receipt) {
                $traineeName = $receipt->trainee?->full_name ?? $receipt->trainee_name;
                $uliNumber = $receipt->trainee?->uli_number ?? $receipt->trainee_uli_number;
                
                $programName = 'Unknown Program';
                $programId = 'N/A';
                
                if ($receipt->program) {
                    $programName = $receipt->program->name;
                    $programId = $receipt->program->program_id;
                }
                elseif ($receipt->enrollment && $receipt->enrollment->program) {
                    $programName = $receipt->enrollment->program->name;
                    $programId = $receipt->enrollment->program->program_id;
                }
                elseif ($receipt->trainee && $receipt->trainee->currentEnrollment && $receipt->trainee->currentEnrollment->program) {
                    $programName = $receipt->trainee->currentEnrollment->program->name;
                    $programId = $receipt->trainee->currentEnrollment->program->program_id;
                }
                elseif ($receipt->trainee && $receipt->trainee->enrollments->isNotEmpty()) {
                    $latestEnrollment = $receipt->trainee->enrollments->sortByDesc('enrollment_date')->first();
                    if ($latestEnrollment && $latestEnrollment->program) {
                        $programName = $latestEnrollment->program->name;
                        $programId = $latestEnrollment->program->program_id;
                    }
                }
                
                $natureOfCollection = 'Custom Payment';
                if ($receipt->original_fees && is_array($receipt->original_fees) && !empty($receipt->original_fees)) {
                    $firstFee = $receipt->original_fees[0] ?? null;
                    if ($firstFee && isset($firstFee['natureOfCollection'])) {
                        $natureOfCollection = $firstFee['natureOfCollection'];
                    } elseif ($firstFee && isset($firstFee['accountCode'])) {
                        $natureOfCollection = $firstFee['accountCode'];
                    } elseif ($firstFee && isset($firstFee['program'])) {
                        $natureOfCollection = $firstFee['program'];
                    }
                } elseif ($receipt->fees && is_array($receipt->fees) && !empty($receipt->fees)) {
                    $firstFee = $receipt->fees[0] ?? null;
                    if ($firstFee && isset($firstFee['natureOfCollection'])) {
                        $natureOfCollection = $firstFee['natureOfCollection'];
                    } elseif ($firstFee && isset($firstFee['accountCode'])) {
                        $natureOfCollection = $firstFee['accountCode'];
                    } elseif ($firstFee && isset($firstFee['program'])) {
                        $natureOfCollection = $firstFee['program'];
                    }
                }
                return [
                    'id' => $receipt->receipt_number,
                    'reference_number' => $receipt->receipt_number,
                    'trainee_id' => $receipt->trainee_model_id ?? $receipt->trainee?->id,
                    'trainee_name' => $traineeName,
                    'uli_number' => $uliNumber,
                    'program' => $programName,
                    'program_id' => $programId,
                    'amount' => $receipt->total_amount,
                    'payment_method' => 'cash',
                    'payment_date' => $receipt->date_generated,
                    'status' => $receipt->status === 'generated' ? 'paid' : 'pending',
                    'description' => $natureOfCollection,
                    'type' => $receipt->type ?? 'custom_receipt',
                ];
            });

        $groupedReceipts = $customReceipts->groupBy('trainee_name')->map(function ($receipts, $traineeName) {
            $first = $receipts->first();
            return [
                'trainee_id' => $first['trainee_id'],
                'trainee_name' => $traineeName,
                'uli_number' => $first['uli_number'],
                'program' => $first['program'],
                'program_id' => $first['program_id'],
                'total_receipts' => $receipts->count(),
                'total_amount' => $receipts->sum('amount'),
                'receipts' => $receipts->sortByDesc('payment_date')->values(),
                'latest_receipt_date' => $receipts->max('payment_date'),
            ];
        })->sortByDesc('latest_receipt_date')->values();

        return Inertia::render('Admin/Payments', [
            'groupedReceipts' => $groupedReceipts,
        ]);
    }

    /**
     * Store a new payment (admin)
     */
    public function adminStore(Request $request)
    {
        $validated = $request->validate([
            'trainee_id' => 'required|exists:trainees,id',
            'program_id' => 'required|exists:programs,program_id',
            'amount' => 'required|numeric|min:0',
            'payment_method' => 'required|in:cash,card,bank_transfer,online,check',
            'payment_date' => 'required|date',
            'due_date' => 'nullable|date',
            'status' => 'required|in:pending,paid',
            'reference_number' => 'required|string|max:255',
            'description' => 'nullable|string',
        ]);

        $receipt = CustomReceipt::create([
            'trainee_model_id' => $validated['trainee_id'],
            'program_id' => $validated['program_id'],
            'receipt_number' => $validated['reference_number'],
            'total_amount' => $validated['amount'],
            'date_generated' => $validated['payment_date'],
            'time_generated' => now(),
            'status' => $validated['status'] === 'paid' ? 'generated' : 'pending',
            'type' => 'registration',
            'trainee_name' => Trainee::find($validated['trainee_id'])->full_name ?? '',
            'trainee_id_number' => Trainee::find($validated['trainee_id'])->uli_number ?? '',
            'trainee_uli_number' => Trainee::find($validated['trainee_id'])->uli_number ?? '',
            'fees' => json_encode([['program' => Program::find($validated['program_id'])->name ?? '', 'amount' => $validated['amount'], 'account_code' => 'ADMIN']]),
        ]);

        return redirect()->back()->with('success', 'Payment created successfully!');
    }

    /**
     * Update a payment (admin)
     */
    public function adminUpdate(Request $request, $id)
    {
        $validated = $request->validate([
            'trainee_id' => 'required|exists:trainees,id',
            'program_id' => 'required|exists:programs,program_id',
            'amount' => 'required|numeric|min:0',
            'payment_method' => 'required|in:cash,card,bank_transfer,online,check',
            'payment_date' => 'required|date',
            'due_date' => 'nullable|date',
            'status' => 'required|in:pending,paid',
            'reference_number' => 'required|string|max:255',
            'description' => 'nullable|string',
        ]);

        if (str_starts_with($id, 'ENR-')) {
            $enrollmentId = (int) str_replace('ENR-', '', $id);
            $enrollment = TraineeEnrollment::find($enrollmentId);
            
            if ($enrollment) {
                $enrollment->update([
                    'payment_reference' => $validated['reference_number'],
                    'payment_method' => $validated['payment_method'],
                    'payment_date' => $validated['payment_date'],
                    'payment_status' => $validated['status'],
                    'payment_notes' => $validated['description'],
                ]);
            }
        } else {
            $receiptId = (int) str_replace('REC-', '', $id);
            $receipt = CustomReceipt::find($receiptId);
            
            if ($receipt) {
                $receipt->update([
                    'trainee_model_id' => $validated['trainee_id'],
                    'program_id' => $validated['program_id'],
                    'receipt_number' => $validated['reference_number'],
                    'total_amount' => $validated['amount'],
                    'date_generated' => $validated['payment_date'],
                    'status' => $validated['status'] === 'paid' ? 'generated' : 'pending',
                    'trainee_name' => Trainee::find($validated['trainee_id'])->full_name ?? '',
                    'trainee_id_number' => Trainee::find($validated['trainee_id'])->uli_number ?? '',
                    'trainee_uli_number' => Trainee::find($validated['trainee_id'])->uli_number ?? '',
                    'fees' => json_encode([['program' => Program::find($validated['program_id'])->name ?? '', 'amount' => $validated['amount'], 'account_code' => 'ADMIN']]),
                ]);
            }
        }

        return redirect()->back()->with('success', 'Payment updated successfully!');
    }

    /**
     * Delete a payment (admin)
     */
    public function adminDestroy($id)
    {
        if (str_starts_with($id, 'ENR-')) {
            $enrollmentId = (int) str_replace('ENR-', '', $id);
            $enrollment = TraineeEnrollment::find($enrollmentId);
            
            if ($enrollment) {
                if ($enrollment->status === 'completed') {
                    return redirect()->back()->with('error', 'Cannot delete payment for a completed enrollment. This would break the audit trail.');
                }

                $enrollment->update([
                    'payment_reference' => null,
                    'payment_method' => null,
                    'payment_date' => null,
                    'payment_status' => 'unpaid',
                    'payment_notes' => null,
                ]);
            }
        } else {
            $receiptId = (int) str_replace('REC-', '', $id);
            $receipt = CustomReceipt::find($receiptId);
            
            if ($receipt) {
                if ($receipt->status === 'generated') {
                    return redirect()->back()->with('error', 'Cannot delete a generated receipt. Use the cancellation feature instead to preserve the audit trail.');
                }

                $receipt->delete();
            }
        }

        return redirect()->back()->with('success', 'Payment deleted successfully!');
    }
} 
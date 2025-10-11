<?php

namespace App\Http\Controllers;

use App\Models\TraineeEnrollment;
use App\Models\Assessment;
use App\Models\Program;
use App\Models\Trainee;
use App\Models\CustomReceipt;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CashierController extends Controller
{
    /**
     * Display the cashier dashboard
     */
    public function dashboard(Request $request)
    {
        $perPage = $request->get('per_page', 3); // Default to 3 items per page for testing pagination
        $page = $request->get('page', 1);
        
        // Calculate statistics
        $stats = $this->calculateDashboardStats();
        
        // Get recent payment status (active enrollments) with pagination
        $paymentStatus = $this->getPaymentStatus($perPage, $page);

        // Get payment summaries by month
        $paymentSummaries = $this->getPaymentSummaries();
        
        // Get recent activities
        $recentActivities = $this->getRecentActivities();

        return Inertia::render('Cashier/Dashboard', [
            'stats' => $stats,
            'paymentStatus' => $paymentStatus,
            'paymentSummaries' => $paymentSummaries,
            'recentActivities' => $recentActivities,
            'filters' => [
                'per_page' => $perPage,
                'page' => $page,
            ]
        ]);
    }

    /**
     * Display payments management page
     */
    public function payments(Request $request)
    {
        $perPage = $request->get('per_page', 20); // Default to 20 items per page
        $search = $request->get('search', '');
        $status = $request->get('status', '');
        $type = $request->get('type', '');

        // Get enrollment payment records (including scholars for additional fees)
        // Scholars should appear in Additional Fees tab with additional fees (Trainee ID, Certificate, etc.)
        $enrollmentPaymentsQuery = TraineeEnrollment::with(['trainee', 'program'])
            ->where(function ($query) {
                $query->whereNotNull('enrollment_fee') // Include enrollments with enrollment fees
                      ->orWhereHas('trainee', function ($subQuery) { // OR include enrollments where trainee is a scholar
                          $subQuery->whereNotNull('scholarship_package')
                                   ->where('scholarship_package', '!=', '');
                      });
            });
        
        // Apply search filter if provided
        if ($search) {
            $enrollmentPaymentsQuery->whereHas('trainee', function ($query) use ($search) {
                $query->where('first_name', 'like', "%{$search}%")
                      ->orWhere('last_name', 'like', "%{$search}%")
                      ->orWhere('uli_number', 'like', "%{$search}%");
            });
        }
        
        $enrollmentPayments = $enrollmentPaymentsQuery
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($enrollment) {
                $isScholar = $enrollment->trainee &&
                            $enrollment->trainee->scholarship_package &&
                            trim($enrollment->trainee->scholarship_package) !== '';

                // For scholars, show additional fees (Trainee ID, Certification, etc.)
                $additionalFees = 0;
                $programName = $enrollment->program->name;

                if ($isScholar) {
                    // Add additional fees for scholars
                    $additionalFees = 500; // Trainee ID + Certification fees
                    $programName .= ' (Scholar - Additional Fees)';
                }

                // Hide from Additional Fees if trainee is newly paid but has no generated registration receipt yet
                // EXCEPT for scholars - they should appear in Additional Fees tab even without registration receipt
                $hasRegistrationReceipt = \App\Models\CustomReceipt::where('type', 'registration')
                    ->where('status', 'generated')
                    ->where('trainee_model_id', $enrollment->trainee->id)
                    ->exists();
                if ($enrollment->trainee && $enrollment->trainee->payment_status === 'paid' && !$hasRegistrationReceipt && !$isScholar) {
                    return null; // keep them in the Registration tab until receipt is generated
                }

                $totalAmount = $enrollment->enrollment_fee + $additionalFees;

                // Skip if no fees at all
                if ($totalAmount <= 0) {
                    return null;
                }

                // For scholars with additional fees, check if they've actually paid the additional fees
                $actualStatus = 'unpaid';
                if ($isScholar && $additionalFees > 0) {
                    // Check if scholar has generated a custom receipt for additional fees
                    $hasCustomReceipt = \App\Models\CustomReceipt::where('enrollment_id', $enrollment->id)
                        ->where('status', 'generated')
                        ->exists();
                    $actualStatus = $hasCustomReceipt ? 'paid' : 'unpaid';
                } else {
                    // Non-scholars use the enrollment payment status
                    $actualStatus = $enrollment->payment_status === 'paid' ? 'paid' : 'unpaid';
                }

                return [
                    'id' => 'ENR-' . str_pad($enrollment->id, 4, '0', STR_PAD_LEFT),
                    'type' => 'enrollment',
                    'trainee' => [
                        'name' => $enrollment->trainee->full_name,
                        'id' => 'T-' . str_pad($enrollment->trainee->id, 4, '0', STR_PAD_LEFT),
                        'uli_number' => $enrollment->trainee->uli_number,
                    ],
                    'program' => $programName,
                    'amount' => $totalAmount,
                    'receiptNo' => $enrollment->payment_reference ?: 'RN-ENR-' . str_pad($enrollment->id, 4, '0', STR_PAD_LEFT),
                    'date' => $enrollment->enrollment_date->format('Y-m-d'),
                    'status' => $actualStatus,
                    'enrollment_id' => $enrollment->id,
                    'assessment_id' => null,
                    'trainee_id' => null,
                    'is_scholarship' => $isScholar,
                    'additional_fees' => $additionalFees,
                ];
            })
            ->filter() // Remove null entries
            ->values();

        // Get trainee registration payments (newly registered trainees excluding scholars who are exempted)
        // Show unpaid trainees OR paid trainees who do not yet have a generated registration receipt (regardless of enrollment status)
        // Scholars should skip this tab entirely since they're exempted from enrollment fees
        $registrationPaymentsQuery = Trainee::query()
            ->where(function ($query) {
                $query->where('payment_status', 'unpaid')
                      ->orWhere(function ($subQuery) {
                          $subQuery->where('payment_status', 'paid')
                                   ->whereNotExists(function ($q) {
                                       $q->select(DB::raw(1))
                                         ->from('custom_receipts')
                                         ->whereColumn('custom_receipts.trainee_model_id', 'trainees.id')
                                         ->where('custom_receipts.type', 'registration')
                                         ->where('custom_receipts.status', 'generated');
                                   });
                      });
            })
            ->where(function ($query) {
                // Exclude scholars (trainees with scholarship packages) from registration fees
                $query->whereNull('scholarship_package')
                      ->orWhere('scholarship_package', '');
            });
        
        // Apply search filter if provided
        if ($search) {
            $registrationPaymentsQuery->where(function ($query) use ($search) {
                $query->where('first_name', 'like', "%{$search}%")
                      ->orWhere('last_name', 'like', "%{$search}%")
                      ->orWhere('uli_number', 'like', "%{$search}%");
            });
        }
        
        $registrationPayments = $registrationPaymentsQuery
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($trainee) {
                // Get the program they want to enroll in
                $program = Program::where('name', $trainee->program_qualification)->first();
                $enrollmentFee = $program ? $program->enrollment_fee : 0;

                // Since we've excluded scholars from this query, all trainees here are non-scholars
                $programName = $trainee->program_qualification; // Remove ' (Enrollment Fee)' suffix
                $totalAmount = $enrollmentFee;

                // Skip if no fees at all
                if ($totalAmount <= 0) {
                    return null;
                }
                
                return [
                    'id' => 'REG-' . str_pad($trainee->id, 4, '0', STR_PAD_LEFT),
                    'type' => 'registration',
                    'trainee' => [
                        'name' => $trainee->full_name,
                        'id' => 'T-' . str_pad($trainee->id, 4, '0', STR_PAD_LEFT),
                        'uli_number' => $trainee->uli_number,
                    ],
                    'program' => $programName,
                    'amount' => $totalAmount,
                    'receiptNo' => $trainee->payment_reference ?: 'RN-REG-' . str_pad($trainee->id, 4, '0', STR_PAD_LEFT),
                    'date' => $trainee->created_at->format('Y-m-d'),
                    'status' => $trainee->payment_status === 'paid' ? 'paid_pending_enrollment' : 'unpaid',
                    'enrollment_id' => null,
                    'assessment_id' => null,
                    'trainee_id' => $trainee->id,
                    'is_scholarship' => false, // All non-scholars since we excluded scholars
                    'additional_fees' => 0, // No additional fees for non-scholars
                ];
            })
            ->filter() // Remove null entries (trainees with no fees)
            ->values();

        // Combine enrollment and registration payments
        $allEnrollmentPayments = $enrollmentPayments->concat($registrationPayments)->sortByDesc('date')->values();

        // Get assessment payment records (including scholar assessments with $0 fee)
        $assessmentPaymentsQuery = Assessment::with(['trainee', 'program'])
            ->whereNotNull('assessment_fee');
        
        // Apply search filter if provided
        if ($search) {
            $assessmentPaymentsQuery->where(function ($query) use ($search) {
                $query->where('external_applicant_name', 'like', "%{$search}%")
                      ->orWhereHas('trainee', function ($traineeQuery) use ($search) {
                          $traineeQuery->where('first_name', 'like', "%{$search}%")
                                      ->orWhere('last_name', 'like', "%{$search}%")
                                      ->orWhere('uli_number', 'like', "%{$search}%");
                      });
            });
        }
        
        $assessmentPayments = $assessmentPaymentsQuery
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($assessment) {
                $applicantName = $assessment->applicant_type === 'external_applicant' 
                    ? $assessment->external_applicant_name 
                    : ($assessment->trainee ? $assessment->trainee->full_name : 'N/A');
                    
                $applicantId = $assessment->applicant_type === 'external_applicant' 
                    ? 'EXT-' . str_pad($assessment->id, 4, '0', STR_PAD_LEFT)
                    : ($assessment->trainee ? 'T-' . str_pad($assessment->trainee->id, 4, '0', STR_PAD_LEFT) : 'N/A');

                $programName = $assessment->program ? $assessment->program->name : $assessment->title;
                if ($assessment->assessment_fee == 0 && $assessment->payment_method === 'scholarship_exemption') {
                    $programName .= ' (Scholar)';
                }

                return [
                    'id' => 'ASS-' . str_pad($assessment->id, 4, '0', STR_PAD_LEFT),
                    'type' => 'assessment',
                    'trainee' => [
                        'name' => $applicantName,
                        'id' => $applicantId,
                        'uli_number' => $assessment->trainee ? $assessment->trainee->uli_number : null,
                    ],
                    'program' => $programName,
                    'amount' => $assessment->assessment_fee,
                    'receiptNo' => $assessment->payment_reference ?: 'RN-ASS-' . str_pad($assessment->id, 4, '0', STR_PAD_LEFT),
                    'date' => $assessment->assessment_date ? $assessment->assessment_date->format('Y-m-d') : $assessment->created_at->format('Y-m-d'),
                    'status' => $assessment->payment_status === 'paid' ? 'paid' : 'unpaid',
                    'enrollment_id' => null,
                    'assessment_id' => $assessment->id,
                    'trainee_id' => null,
                    'is_scholarship' => $assessment->assessment_fee == 0 && $assessment->payment_method === 'scholarship_exemption',
                ];
            });

        // Calculate summary statistics
        $summaryStats = $this->calculatePaymentSummaryStats();
        
        // Get collections by program
        $collectionsByProgram = $this->getCollectionsByProgram();
        
        // Paginate the combined results
        $allPayments = $allEnrollmentPayments->concat($assessmentPayments)->sortByDesc('date')->values();
        $currentPage = $request->get('page', 1);
        
        // Ensure HTTPS URLs for pagination when FORCE_HTTPS is enabled
        $path = $request->url();
        if (filter_var(env('FORCE_HTTPS', false), FILTER_VALIDATE_BOOLEAN) && 
            env('APP_URL') && str_starts_with(env('APP_URL'), 'https://')) {
            $path = str_replace('http://', 'https://', $path);
        }
        
        $paginatedPayments = new \Illuminate\Pagination\LengthAwarePaginator(
            $allPayments->forPage($currentPage, $perPage),
            $allPayments->count(),
            $perPage,
            $currentPage,
            ['path' => $path]
        );

        return Inertia::render('Cashier/Payments', [
            'enrollmentPayments' => $paginatedPayments,
            'assessmentPayments' => $assessmentPayments,
            'summaryStats' => $summaryStats,
            'collectionsByProgram' => $collectionsByProgram,
            'filters' => [
                'search' => $search,
                'status' => $status,
                'type' => $type,
                'per_page' => $perPage,
            ]
        ]);
    }

    /**
     * Display receipts page
     */
    public function receipts()
    {
        // Get enrollment receipts - only show enrollments that have manually generated custom receipts
        // Auto-enrollments should not appear as receipts until cashier manually generates a receipt
        $enrollmentReceipts = collect(); // Start with empty collection, only custom receipts will be shown

        // Get assessment receipts (paid assessments) - exclude scholars' automatic assessments
        $assessmentReceipts = Assessment::with(['trainee', 'program'])
            ->where('payment_status', 'paid')
            ->whereNotNull('payment_reference')
            ->where(function ($query) {
                // Exclude automatic scholar assessments - they have payment_method = 'scholarship_exemption'
                $query->where('payment_method', '!=', 'scholarship_exemption')
                      ->orWhereNull('payment_method');
            })
            ->orderBy('payment_date', 'desc')
            ->get()
            ->map(function ($assessment) {
                $applicantName = $assessment->applicant_type === 'external_applicant' 
                    ? $assessment->external_applicant_name 
                    : ($assessment->trainee ? $assessment->trainee->full_name : 'N/A');
                    
                $applicantId = $assessment->applicant_type === 'external_applicant' 
                    ? 'EXT-' . str_pad($assessment->id, 4, '0', STR_PAD_LEFT)
                    : ($assessment->trainee ? 'T-' . str_pad($assessment->trainee->id, 4, '0', STR_PAD_LEFT) : 'N/A');

                return [
                    'id' => $assessment->payment_reference ?: 'RN-ASS-' . str_pad($assessment->id, 4, '0', STR_PAD_LEFT),
                    'paymentId' => 'ASS-' . str_pad($assessment->id, 4, '0', STR_PAD_LEFT),
                    'type' => 'assessment',
                    'trainee' => [
                        'name' => $applicantName,
                        'id' => $applicantId,
                        'uli_number' => $assessment->trainee ? $assessment->trainee->uli_number : null,
                        'trainee_id' => $assessment->trainee ? $assessment->trainee->id : 'external_' . $assessment->id, // Add trainee ID for grouping
                    ],
                    'program' => $assessment->program ? $assessment->program->name : $assessment->title,
                    'amount' => $assessment->assessment_fee,
                    'dateGenerated' => $assessment->payment_date->format('Y-m-d'),
                    'timeGenerated' => $assessment->payment_date->format('g:i A'),
                    'status' => 'generated',
                ];
            });

        // Get custom receipts (user-edited receipts) - only generated ones
        $customEnrollmentReceipts = CustomReceipt::where('type', 'enrollment')
            ->where('status', 'generated')
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($receipt) {
                $allFees = array_merge($receipt->original_fees ?: [], $receipt->fees ?: []);
                
                return [
                    'id' => $receipt->receipt_number,
                    'paymentId' => $receipt->payment_id,
                    'type' => $receipt->type,
                    'fund_type' => $receipt->fund_type,
                    'trainee' => [
                        'name' => $receipt->trainee_name,
                        'id' => $receipt->trainee_id_number,
                        'uli_number' => $receipt->trainee_uli_number,
                        'trainee_id' => $receipt->trainee_id_number, // Use ID number for grouping custom receipts
                    ],
                    'program' => collect($allFees)->pluck('program')->unique()->filter()->join(', '), // Program names
                    'natureOfCollection' => collect($allFees)->pluck('natureOfCollection')->unique()->join(', '), // Nature of collection items
                    'amount' => $receipt->total_amount,
                    'dateGenerated' => $receipt->date_generated->format('Y-m-d'),
                    'timeGenerated' => $receipt->time_generated->format('g:i A'),
                    'status' => $receipt->status,
                    'fees' => $receipt->fees ?: [],
                    'original_fees' => $receipt->original_fees ?: [],
                    'isCustom' => true,
                    'customReceiptId' => $receipt->id,
                ];
            });

        $customAssessmentReceipts = CustomReceipt::where('type', 'assessment')
            ->where('status', 'generated')
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($receipt) {
                $allFees = array_merge($receipt->original_fees ?: [], $receipt->fees ?: []);
                
                return [
                    'id' => $receipt->receipt_number,
                    'paymentId' => $receipt->payment_id,
                    'type' => $receipt->type,
                    'fund_type' => $receipt->fund_type,
                    'trainee' => [
                        'name' => $receipt->trainee_name,
                        'id' => $receipt->trainee_id_number,
                        'uli_number' => $receipt->trainee_uli_number,
                        'trainee_id' => $receipt->trainee_id_number, // Use ID number for grouping custom receipts
                    ],
                    'program' => collect($allFees)->pluck('program')->unique()->filter()->join(', '), // Program names
                    'natureOfCollection' => collect($allFees)->pluck('natureOfCollection')->unique()->join(', '), // Nature of collection items
                    'amount' => $receipt->total_amount,
                    'dateGenerated' => $receipt->date_generated->format('Y-m-d'),
                    'timeGenerated' => $receipt->time_generated->format('g:i A'),
                    'status' => $receipt->status,
                    'fees' => $receipt->fees ?: [],
                    'original_fees' => $receipt->original_fees ?: [],
                    'isCustom' => true,
                    'customReceiptId' => $receipt->id,
                ];
            });

        // Get custom registration receipts (newly registered trainees who got receipts)
        $customRegistrationReceipts = CustomReceipt::where('type', 'registration')
            ->where('status', 'generated')
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($receipt) {
                $allFees = array_merge($receipt->original_fees ?: [], $receipt->fees ?: []);
                
                return [
                    'id' => $receipt->receipt_number,
                    'paymentId' => $receipt->payment_id,
                    'type' => $receipt->type,
                    'fund_type' => $receipt->fund_type,
                    'trainee' => [
                        'name' => $receipt->trainee_name,
                        'id' => $receipt->trainee_id_number,
                        'uli_number' => $receipt->trainee_uli_number,
                        'trainee_id' => $receipt->trainee_id_number, // Use ID number for grouping custom receipts
                    ],
                    'program' => collect($allFees)->pluck('program')->unique()->filter()->join(', '), // Program names
                    'natureOfCollection' => collect($allFees)->pluck('natureOfCollection')->unique()->join(', '), // Nature of collection items
                    'amount' => $receipt->total_amount,
                    'dateGenerated' => $receipt->date_generated->format('Y-m-d'),
                    'timeGenerated' => $receipt->time_generated->format('g:i A'),
                    'status' => $receipt->status,
                    'fees' => $receipt->fees ?: [],
                    'original_fees' => $receipt->original_fees ?: [],
                    'isCustom' => true,
                    'customReceiptId' => $receipt->id,
                ];
            });

        // Get cancelled receipts (for audit purposes)
        $cancelledReceipts = CustomReceipt::where('status', 'cancelled')
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($receipt) {
                $allFees = array_merge($receipt->original_fees ?: [], $receipt->fees ?: []);
                
                return [
                    'id' => $receipt->receipt_number,
                    'paymentId' => $receipt->payment_id,
                    'type' => $receipt->type,
                    'fund_type' => $receipt->fund_type,
                    'trainee' => [
                        'name' => $receipt->trainee_name,
                        'id' => $receipt->trainee_id_number,
                        'uli_number' => $receipt->trainee_uli_number,
                    ],
                    'program' => collect($allFees)->pluck('program')->unique()->filter()->join(', '), // Program names
                    'natureOfCollection' => collect($allFees)->pluck('natureOfCollection')->unique()->join(', '), // Nature of collection items
                    'amount' => $receipt->total_amount,
                    'dateGenerated' => $receipt->date_generated->format('Y-m-d'),
                    'timeGenerated' => $receipt->time_generated->format('g:i A'),
                    'status' => $receipt->status,
                    'cancellation_reason' => $receipt->cancellation_reason,
                    'fees' => $receipt->fees ?: [],
                    'original_fees' => $receipt->original_fees ?: [],
                    'isCustom' => true,
                    'customReceiptId' => $receipt->id,
                ];
            });

        // Merge custom receipts with default receipts, giving priority to custom receipts
        $customEnrollmentPaymentIds = $customEnrollmentReceipts->pluck('paymentId')->toArray();
        $customAssessmentPaymentIds = $customAssessmentReceipts->pluck('paymentId')->toArray();
        $customRegistrationPaymentIds = $customRegistrationReceipts->pluck('paymentId')->toArray();

        $finalEnrollmentReceipts = $enrollmentReceipts->filter(function ($receipt) use ($customEnrollmentPaymentIds) {
            return !in_array($receipt['paymentId'], $customEnrollmentPaymentIds);
        })->concat($customEnrollmentReceipts);

        $finalAssessmentReceipts = $assessmentReceipts->filter(function ($receipt) use ($customAssessmentPaymentIds) {
            return !in_array($receipt['paymentId'], $customAssessmentPaymentIds);
        })->concat($customAssessmentReceipts);

        // Combine all receipts and group by trainee (including registration receipts)
        $allReceipts = $finalEnrollmentReceipts->concat($finalAssessmentReceipts)->concat($customRegistrationReceipts);

        // Group receipts by trainee
        $groupedReceipts = $allReceipts->groupBy('trainee.trainee_id')->map(function ($receipts, $traineeId) {
            $firstReceipt = $receipts->first();
            $sortedReceipts = $receipts->sortByDesc(function ($receipt) {
                return $receipt['dateGenerated'] . ' ' . $receipt['timeGenerated'];
            })->values();

            return [
                'trainee_id' => $traineeId,
                'trainee_name' => $firstReceipt['trainee']['name'],
                'trainee_id_number' => $firstReceipt['trainee']['id'],
                'trainee_uli_number' => $firstReceipt['trainee']['uli_number'],
                'total_receipts' => $receipts->count(),
                'total_amount' => $receipts->sum('amount'),
                'receipts' => $sortedReceipts,
                'latest_receipt_date' => $sortedReceipts->first()['dateGenerated'] ?? null,
                'program_names' => $receipts->pluck('program')->unique()->filter()->join(', '), // Aggregate program names
            ];
        })->sortByDesc('latest_receipt_date')->values();

        return Inertia::render('Cashier/Receipts', [
            'groupedReceipts' => $groupedReceipts,
            'cancelledReceipts' => $cancelledReceipts->sortByDesc('dateGenerated')->values(),
            // Keep the old format for backward compatibility during transition
            'enrollmentReceipts' => $finalEnrollmentReceipts->sortByDesc('dateGenerated')->values(),
            'assessmentReceipts' => $finalAssessmentReceipts->sortByDesc('dateGenerated')->values(),
        ]);
    }

    /**
     * Display dedicated reports page (summary & collections)
     */
    public function reports()
    {
        $summaryStats = $this->calculatePaymentSummaryStats();

        // Reuse existing aggregation by program
        $collectionsByProgram = $this->getCollectionsByProgram();

        return Inertia::render('Cashier/Reports', [
            'summaryStats' => $summaryStats,
            'collectionsByProgram' => $collectionsByProgram,
        ]);
    }

    /**
     * Process a payment
     */
    public function processPayment(Request $request)
    {
        $request->validate([
            'enrollment_id' => 'nullable|exists:trainee_enrollments,id',
            'assessment_id' => 'nullable|exists:assessments,id',
            'trainee_id' => 'nullable|exists:trainees,id',
            'payment_method' => 'required|string',
            'payment_reference' => 'nullable|string',
            'payment_notes' => 'nullable|string',
            'skip_enrollment' => 'nullable|boolean',
        ]);

        // Ensure one of enrollment_id, assessment_id, or trainee_id is provided
        if (!$request->enrollment_id && !$request->assessment_id && !$request->trainee_id) {
            return redirect()->back()->withErrors(['error' => 'Either enrollment, assessment, or trainee ID must be provided.']);
        }

        if ($request->enrollment_id) {
            // Process enrollment payment
            $enrollment = TraineeEnrollment::findOrFail($request->enrollment_id);
            
            $enrollment->markAsPaid(
                $request->payment_method,
                $request->payment_reference ?: 'RN-ENR-' . str_pad($enrollment->id, 4, '0', STR_PAD_LEFT),
                $request->payment_notes
            );

            // Keep trainee model in sync for Officer/Trainees and dashboards
            if ($enrollment->trainee) {
                $enrollment->trainee->update([
                    'payment_status' => 'paid',
                    'status' => 'active',
                    'payment_method' => $request->payment_method,
                    'payment_reference' => $request->payment_reference ?: 'RN-ENR-' . str_pad($enrollment->id, 4, '0', STR_PAD_LEFT),
                    'payment_date' => now(),
                    'payment_notes' => $request->payment_notes,
                ]);
            }
        } else if ($request->assessment_id) {
            // Process assessment payment
            $assessment = Assessment::findOrFail($request->assessment_id);
            
            $assessment->update([
                'payment_status' => 'paid',
                'payment_method' => $request->payment_method,
                'payment_reference' => $request->payment_reference ?: 'RN-ASS-' . str_pad($assessment->id, 4, '0', STR_PAD_LEFT),
                'payment_date' => now(),
                'payment_notes' => $request->payment_notes
            ]);
        } else if ($request->trainee_id) {
            // Process registration payment for newly registered trainee
            $trainee = Trainee::findOrFail($request->trainee_id);

            if ($request->skip_enrollment) {
                // Mark as paid and activate immediately to trigger auto-enrollment. Receipt can follow.
                $updated = $trainee->update([
                    'payment_status' => 'paid',
                    'status' => 'active',
                    'payment_method' => $request->payment_method,
                    'payment_reference' => $request->payment_reference ?: 'RN-REG-' . str_pad($trainee->id, 4, '0', STR_PAD_LEFT),
                    'payment_date' => now(),
                    'payment_notes' => $request->payment_notes
                ]);
                
                // Refresh the model to get the latest data
                $trainee->refresh();
            } else {
                // Standard flow: Update trainee payment status and activate them
                $trainee->update([
                    'payment_status' => 'paid',
                    'status' => 'active', // Activate the trainee after payment
                    'payment_method' => $request->payment_method,
                    'payment_reference' => $request->payment_reference ?: 'RN-REG-' . str_pad($trainee->id, 4, '0', STR_PAD_LEFT),
                    'payment_date' => now(),
                    'payment_notes' => $request->payment_notes
                ]);

                // The handleAutoEnrollment method will be triggered automatically via the model's boot method
                // This will create the TraineeEnrollment record for the trainee
            }
        }

        return redirect()->back()->with('success', 'Payment processed successfully!');
    }

    /**
     * Save a custom receipt with edited details
     */
    public function saveCustomReceipt(Request $request)
    {
        $request->validate([
            'receiptNo' => 'required|string',
            'paymentId' => 'required|string',
            'type' => 'required|in:enrollment,assessment,registration',
            'fund_type' => 'required|in:General Fund,Trust Fund',
            'trainee' => 'required|array',
            'trainee.name' => 'required|string',
            'trainee.id' => 'required|string',
            'trainee.uli_number' => 'nullable|string',
            'fees' => 'required|array|min:1',
            'fees.*.natureOfCollection' => 'required|string',
            'fees.*.program' => 'required|string',
            'fees.*.amount' => 'required|numeric|min:0',
            'fees.*.accountCode' => 'required|string',
            'dateGenerated' => 'required|date',
            'enrollment_id' => 'nullable|exists:trainee_enrollments,id',
            'assessment_id' => 'nullable|exists:assessments,id',
            'trainee_id' => 'nullable|exists:trainees,id',
            'complete_enrollment' => 'nullable|boolean',
            'status' => 'nullable|in:generated,cancelled',
            'cancellation_reason' => 'nullable|string',
        ]);

        // Determine if this is a cancelled receipt
        $isCancelled = $request->status === 'cancelled';

        // Separate original fees (first fee, which is the system-generated one) from custom fees
        $originalFees = collect($request->fees)->take(1)->toArray(); // First fee is original
        $customFees = collect($request->fees)->skip(1)->toArray(); // Rest are custom

        $totalAmount = collect($request->fees)->sum('amount');

        // Generate a unique receipt number if one is not provided or if it already exists
        $baseReceiptNo = $request->receiptNo;
        $receiptNo = $baseReceiptNo;
        $counter = 1;
        
        // Check for existing receipt numbers and make sure we generate a unique one
        while (CustomReceipt::where('receipt_number', $receiptNo)->exists()) {
            $receiptNo = $baseReceiptNo . '-' . $counter;
            $counter++;
        }

        // Always create a new custom receipt (allow multiple receipts per payment)
        $receipt = CustomReceipt::create([
            'receipt_number' => $receiptNo,
            'payment_id' => $request->paymentId,
            'type' => $request->type,
            'fund_type' => $request->fund_type,
            'trainee_name' => $request->trainee['name'],
            'trainee_id_number' => $request->trainee['id'],
            'trainee_uli_number' => $request->trainee['uli_number'],
            'fees' => $customFees,
            'original_fees' => $originalFees,
            'total_amount' => $totalAmount,
            'date_generated' => $request->dateGenerated,
            'time_generated' => now()->format('H:i:s'),
            'status' => $isCancelled ? 'cancelled' : 'generated',
            'cancellation_reason' => $isCancelled ? $request->cancellation_reason : null,
            'enrollment_id' => $request->enrollment_id,
            'assessment_id' => $request->assessment_id,
            'trainee_model_id' => $request->trainee_id,
        ]);

        // Handle enrollment completion for registration payments (only for generated receipts, not cancelled)
        if (!$isCancelled && $request->complete_enrollment && $request->type === 'registration' && $request->trainee_id) {
            $trainee = Trainee::findOrFail($request->trainee_id);
            
            // Activate the trainee and trigger enrollment
            $trainee->update([
                'status' => 'active'
            ]);
            
            // The handleAutoEnrollment method will be triggered automatically via the model's boot method
            // This will create the TraineeEnrollment record for the trainee and move them to "Additional Fees" tab
        }

        if ($isCancelled) {
            return redirect()->back()->with('success', 'Receipt ' . $receiptNo . ' has been cancelled and saved for audit purposes.');
        } else {
        return redirect()->back()->with('success', 'New receipt generated successfully! Receipt Number: ' . $receiptNo);
        }
    }

    /**
     * Update an existing custom receipt
     */
    public function updateCustomReceipt(Request $request, CustomReceipt $customReceipt)
    {
        $request->validate([
            'receiptNo' => 'required|string',
            'fees' => 'required|array|min:0', // Only custom fees can be edited
            'fees.*.natureOfCollection' => 'required|string',
            'fees.*.program' => 'required|string',
            'fees.*.amount' => 'required|numeric|min:0',
            'fees.*.accountCode' => 'required|string',
        ]);

        // Calculate total amount including original fees
        $customFeesTotal = collect($request->fees)->sum('amount');
        $originalFeesTotal = collect($customReceipt->original_fees ?: [])->sum('amount');
        $totalAmount = $customFeesTotal + $originalFeesTotal;

        $customReceipt->update([
            'receipt_number' => $request->receiptNo,
            'fees' => $request->fees, // Only update custom fees
            'total_amount' => $totalAmount,
            'time_generated' => now()->format('H:i:s'),
        ]);

        return redirect()->back()->with('success', 'Receipt updated successfully!');
    }

    /**
     * Calculate dashboard statistics
     */
    private function calculateDashboardStats()
    {
        $currentMonth = Carbon::now()->startOfMonth();
        $lastMonth = Carbon::now()->subMonth()->startOfMonth();
        
        // Current month stats - Enrollments
        $currentEnrollmentTotal = TraineeEnrollment::where('payment_status', 'paid')
            ->where('payment_date', '>=', $currentMonth)
            ->sum('enrollment_fee');
            
        $currentEnrollmentPending = TraineeEnrollment::where('payment_status', '!=', 'paid')
            ->where('created_at', '>=', $currentMonth)
            ->whereNotNull('enrollment_fee')
            ->where('enrollment_fee', '>', 0)
            ->count();
            
        $currentEnrollmentReceipts = TraineeEnrollment::where('payment_status', 'paid')
            ->where('payment_date', '>=', $currentMonth)
            ->count();

        // Current month stats - Assessments
        $currentAssessmentTotal = Assessment::where('payment_status', 'paid')
            ->where('payment_date', '>=', $currentMonth)
            ->sum('assessment_fee');
            
        $currentAssessmentPending = Assessment::where('payment_status', '!=', 'paid')
            ->where('created_at', '>=', $currentMonth)
            ->whereNotNull('assessment_fee')
            ->where('assessment_fee', '>', 0)
            ->count();
            
        $currentAssessmentReceipts = Assessment::where('payment_status', 'paid')
            ->where('payment_date', '>=', $currentMonth)
            ->count();

        // Current month stats - Registration Payments (newly registered trainees)
        $currentRegistrationPending = Trainee::where('payment_status', 'unpaid')
            ->where('status', 'pending')
            ->where('created_at', '>=', $currentMonth)
            ->whereDoesntHave('enrollments')
            ->count();

        // Current month stats - Registration Payments (paid trainees who became enrolled)
        $currentRegistrationPaid = Trainee::where('payment_status', 'paid')
            ->where('payment_date', '>=', $currentMonth)
            ->whereHas('enrollments')
            ->count();

        // Include Custom Receipts (additional fees and finalized collections)
        // Avoid double-counting by excluding enrollments/assessments that already have custom receipts
        $currentMonthCustomReceiptsTotal = CustomReceipt::where('status', 'generated')
            ->where('date_generated', '>=', $currentMonth)
            ->sum('total_amount');

        $currentMonthEnrollmentIdsWithReceipts = CustomReceipt::where('status', 'generated')
            ->where('date_generated', '>=', $currentMonth)
            ->whereNotNull('enrollment_id')
            ->pluck('enrollment_id')
            ->toArray();

        $currentMonthAssessmentIdsWithReceipts = CustomReceipt::where('status', 'generated')
            ->where('date_generated', '>=', $currentMonth)
            ->whereNotNull('assessment_id')
            ->pluck('assessment_id')
            ->toArray();

        // Also exclude enrollments where the trainee has ANY custom receipt (registration, etc.)
        $currentMonthTraineeIdsWithAnyReceipts = CustomReceipt::where('status', 'generated')
            ->where('date_generated', '>=', $currentMonth)
            ->whereNotNull('trainee_model_id')
            ->pluck('trainee_model_id')
            ->toArray();

        $currentEnrollmentTotalNoReceipt = TraineeEnrollment::where('payment_status', 'paid')
            ->where('payment_date', '>=', $currentMonth)
            ->whereNotIn('id', $currentMonthEnrollmentIdsWithReceipts)
            ->whereNotIn('trainee_id', $currentMonthTraineeIdsWithAnyReceipts) // Exclude if trainee has ANY receipt
            ->sum('enrollment_fee');

        $currentAssessmentTotalNoReceipt = Assessment::where('payment_status', 'paid')
            ->where('payment_date', '>=', $currentMonth)
            ->whereNotIn('id', $currentMonthAssessmentIdsWithReceipts)
            ->sum('assessment_fee');

        // Current month registration totals (exclude trainees with ANY custom receipt to avoid double counting)
        $currentMonthTraineesWithReceipts = CustomReceipt::where('date_generated', '>=', $currentMonth)
            ->whereNotNull('trainee_model_id')
            ->pluck('trainee_model_id')
            ->toArray();
            
        $currentRegistrationTotalNoReceipt = Trainee::where('payment_status', 'paid')
            ->where('payment_date', '>=', $currentMonth)
            ->whereDoesntHave('enrollments')
            ->whereNotIn('id', $currentMonthTraineesWithReceipts)
            ->get()
            ->sum(function ($trainee) {
                $program = Program::where('name', $trainee->program_qualification)->first();
                $enrollmentFee = $program ? $program->enrollment_fee : 0;
                
                $isScholar = $trainee->scholarship_package && trim($trainee->scholarship_package) !== '';
                $additionalFees = 0;
                
                if ($isScholar && $enrollmentFee == 0) {
                    $additionalFees = 500; // Trainee ID + Certification fees
                }
                
                return $enrollmentFee + $additionalFees;
            });

        // Combined current month totals now include additional fees via Custom Receipts
        $currentTotal = $currentMonthCustomReceiptsTotal + $currentEnrollmentTotalNoReceipt + $currentAssessmentTotalNoReceipt + $currentRegistrationTotalNoReceipt;
        $currentPending = $currentEnrollmentPending + $currentAssessmentPending + $currentRegistrationPending;
        $currentReceipts = $currentEnrollmentReceipts + $currentAssessmentReceipts + $currentRegistrationPaid;

        // Last month stats - Enrollments
        $lastEnrollmentTotal = TraineeEnrollment::where('payment_status', 'paid')
            ->whereBetween('payment_date', [$lastMonth, $currentMonth])
            ->sum('enrollment_fee');
            
        $lastEnrollmentPending = TraineeEnrollment::where('payment_status', '!=', 'paid')
            ->whereBetween('created_at', [$lastMonth, $currentMonth])
            ->whereNotNull('enrollment_fee')
            ->where('enrollment_fee', '>', 0)
            ->count();
            
        $lastEnrollmentReceipts = TraineeEnrollment::where('payment_status', 'paid')
            ->whereBetween('payment_date', [$lastMonth, $currentMonth])
            ->count();

        // Last month stats - Assessments
        $lastAssessmentTotal = Assessment::where('payment_status', 'paid')
            ->whereBetween('payment_date', [$lastMonth, $currentMonth])
            ->sum('assessment_fee');
            
        $lastAssessmentPending = Assessment::where('payment_status', '!=', 'paid')
            ->whereBetween('created_at', [$lastMonth, $currentMonth])
            ->whereNotNull('assessment_fee')
            ->where('assessment_fee', '>', 0)
            ->count();
            
        $lastAssessmentReceipts = Assessment::where('payment_status', 'paid')
            ->whereBetween('payment_date', [$lastMonth, $currentMonth])
            ->count();

        // Last month stats - Registration Payments
        $lastRegistrationPending = Trainee::where('payment_status', 'unpaid')
            ->where('status', 'pending')
            ->whereBetween('created_at', [$lastMonth, $currentMonth])
            ->whereDoesntHave('enrollments')
            ->count();

        $lastRegistrationPaid = Trainee::where('payment_status', 'paid')
            ->whereBetween('payment_date', [$lastMonth, $currentMonth])
            ->whereHas('enrollments')
            ->count();

        // Include Custom Receipts for last month range as well (avoid double count similarly)
        $lastMonthCustomReceiptsTotal = CustomReceipt::where('status', 'generated')
            ->whereBetween('date_generated', [$lastMonth, $currentMonth])
            ->sum('total_amount');

        $lastMonthEnrollmentIdsWithReceipts = CustomReceipt::where('status', 'generated')
            ->whereBetween('date_generated', [$lastMonth, $currentMonth])
            ->whereNotNull('enrollment_id')
            ->pluck('enrollment_id')
            ->toArray();

        $lastMonthAssessmentIdsWithReceipts = CustomReceipt::where('status', 'generated')
            ->whereBetween('date_generated', [$lastMonth, $currentMonth])
            ->whereNotNull('assessment_id')
            ->pluck('assessment_id')
            ->toArray();

        // Also exclude enrollments where the trainee has ANY custom receipt (registration, etc.) for last month
        $lastMonthTraineeIdsWithAnyReceipts = CustomReceipt::where('status', 'generated')
            ->whereBetween('date_generated', [$lastMonth, $currentMonth])
            ->whereNotNull('trainee_model_id')
            ->pluck('trainee_model_id')
            ->toArray();

        $lastEnrollmentTotalNoReceipt = TraineeEnrollment::where('payment_status', 'paid')
            ->whereBetween('payment_date', [$lastMonth, $currentMonth])
            ->whereNotIn('id', $lastMonthEnrollmentIdsWithReceipts)
            ->whereNotIn('trainee_id', $lastMonthTraineeIdsWithAnyReceipts) // Exclude if trainee has ANY receipt
            ->sum('enrollment_fee');

        $lastAssessmentTotalNoReceipt = Assessment::where('payment_status', 'paid')
            ->whereBetween('payment_date', [$lastMonth, $currentMonth])
            ->whereNotIn('id', $lastMonthAssessmentIdsWithReceipts)
            ->sum('assessment_fee');

        // Last month registration totals (exclude trainees with ANY custom receipt to avoid double counting)
        $lastMonthTraineesWithReceipts = CustomReceipt::whereBetween('date_generated', [$lastMonth, $currentMonth])
            ->whereNotNull('trainee_model_id')
            ->pluck('trainee_model_id')
            ->toArray();
            
        $lastRegistrationTotalNoReceipt = Trainee::where('payment_status', 'paid')
            ->whereBetween('payment_date', [$lastMonth, $currentMonth])
            ->whereDoesntHave('enrollments')
            ->whereNotIn('id', $lastMonthTraineesWithReceipts)
            ->get()
            ->sum(function ($trainee) {
                $program = Program::where('name', $trainee->program_qualification)->first();
                $enrollmentFee = $program ? $program->enrollment_fee : 0;
                
                $isScholar = $trainee->scholarship_package && trim($trainee->scholarship_package) !== '';
                $additionalFees = 0;
                
                if ($isScholar && $enrollmentFee == 0) {
                    $additionalFees = 500; // Trainee ID + Certification fees
                }
                
                return $enrollmentFee + $additionalFees;
            });

        // Combined last month totals include custom receipts
        $lastTotal = $lastMonthCustomReceiptsTotal + $lastEnrollmentTotalNoReceipt + $lastAssessmentTotalNoReceipt + $lastRegistrationTotalNoReceipt;
        $lastPending = $lastEnrollmentPending + $lastAssessmentPending + $lastRegistrationPending;
        $lastReceipts = $lastEnrollmentReceipts + $lastAssessmentReceipts + $lastRegistrationPaid;

        return [
            'totalCollected' => $currentTotal,
            'totalCollectedChange' => $lastTotal > 0 ? round((($currentTotal - $lastTotal) / $lastTotal) * 100, 1) : 0,
            'pendingPayments' => $currentPending,
            'pendingPaymentsChange' => $lastPending > 0 ? round((($currentPending - $lastPending) / $lastPending) * 100, 1) : 0,
            'receiptsGenerated' => $currentReceipts,
            'receiptsGeneratedChange' => $lastReceipts > 0 ? round((($currentReceipts - $lastReceipts) / $lastReceipts) * 100, 1) : 0,
        ];
    }

    /**
     * Get payment status for dashboard
     */
    private function getPaymentStatus($perPage = 9, $page = 1)
    {
        // Get enrollment payments (including scholars with additional fees)
        $enrollmentPayments = TraineeEnrollment::with(['trainee', 'program'])
            ->whereNotNull('enrollment_fee')
            ->orderBy('created_at', 'desc')
            ->get() // Get all to enable proper pagination
            ->map(function ($enrollment) {
                $isScholar = $enrollment->trainee && 
                            $enrollment->trainee->scholarship_package && 
                            trim($enrollment->trainee->scholarship_package) !== '';
                
                // For dashboard, only show enrollment fees (no additional fees)
                $programName = $enrollment->program->name . ' (Enrollment)';
                $displayAmount = $enrollment->enrollment_fee; // Only show enrollment fee amount
                
                if ($isScholar) {
                    $programName = $enrollment->program->name . ' (Scholar - Enrollment Fee)';
                    $displayAmount = 0; // Scholars have 0 enrollment fee
                }

                $initials = collect(explode(' ', $enrollment->trainee->full_name))
                    ->map(fn($name) => substr($name, 0, 1))
                    ->take(2)
                    ->join('');
                    
                // Status based only on enrollment fee payment
                $actualStatus = $enrollment->payment_status === 'paid' ? 'paid' : 'pending';

                return [
                    'id' => 'ENR-' . str_pad($enrollment->id, 4, '0', STR_PAD_LEFT),
                    'initials' => $initials,
                    'name' => $enrollment->trainee->full_name,
                    'program' => $programName,
                    'amountDue' => $displayAmount,
                    'status' => $actualStatus,
                    'created_at' => $enrollment->created_at,
                    'is_scholarship' => $isScholar,
                ];
            })
            ->filter() // Remove null entries
            ->values();

        // Get registration payments (newly registered trainees)
        $registrationPayments = Trainee::where('payment_status', 'unpaid')
            ->where('status', 'pending')
            ->whereDoesntHave('enrollments')
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($trainee) {
                // Get the program they want to enroll in
                $program = Program::where('name', $trainee->program_qualification)->first();
                $enrollmentFee = $program ? $program->enrollment_fee : 0;
                
                $isScholar = $trainee->scholarship_package && 
                            trim($trainee->scholarship_package) !== '';
                
                // For dashboard, only show enrollment fees (no additional fees)
                $programName = $trainee->program_qualification . ' (Enrollment Fee)';
                $displayAmount = $enrollmentFee; // Only show enrollment fee amount
                
                if ($isScholar) {
                    $programName = $trainee->program_qualification . ' (Scholar - Enrollment Fee)';
                    $displayAmount = 0; // Scholars have 0 enrollment fee
                }

                $initials = collect(explode(' ', $trainee->full_name))
                    ->map(fn($name) => substr($name, 0, 1))
                    ->take(2)
                    ->join('');
                    
                // Status based only on enrollment fee payment
                $actualStatus = $trainee->payment_status === 'paid' ? 'paid' : 'pending';

                return [
                    'id' => 'REG-' . str_pad($trainee->id, 4, '0', STR_PAD_LEFT),
                    'initials' => $initials,
                    'name' => $trainee->full_name,
                    'program' => $programName,
                    'amountDue' => $displayAmount,
                    'status' => $actualStatus,
                    'created_at' => $trainee->created_at,
                    'is_scholarship' => $isScholar,
                ];
            })
            ->filter() // Remove null entries
            ->values();

        // Get assessment payments (including scholar assessments with 0 amount due)
        $assessmentPayments = Assessment::with(['trainee', 'program'])
            ->whereNotNull('assessment_fee')
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($assessment) {
                $applicantName = $assessment->applicant_type === 'external_applicant' 
                    ? $assessment->external_applicant_name 
                    : ($assessment->trainee ? $assessment->trainee->full_name : 'N/A');

                $initials = collect(explode(' ', $applicantName))
                    ->map(fn($name) => substr($name, 0, 1))
                    ->take(2)
                    ->join('');

                // Apply scholar exemption logic - show 0 amount due for scholars
                $amountDue = $assessment->assessment_fee;
                if ($assessment->shouldApplyScholarExemption()) {
                    $amountDue = 0;
                }
                    
                return [
                    'id' => 'ASS-' . str_pad($assessment->id, 4, '0', STR_PAD_LEFT),
                    'initials' => $initials,
                    'name' => $applicantName,
                    'program' => ($assessment->program ? $assessment->program->name : $assessment->title) . ' (Assessment)',
                    'amountDue' => $amountDue,
                    'status' => $assessment->payment_status === 'paid' ? 'paid' : 'pending',
                    'created_at' => $assessment->created_at,
                ];
            });

        // Combine and sort all payments
        $allPayments = $enrollmentPayments->concat($registrationPayments)->concat($assessmentPayments)
            ->sortByDesc('created_at')
            ->values();
        
        // Apply pagination
        $total = $allPayments->count();
        $offset = ($page - 1) * $perPage;
        $paginatedPayments = $allPayments->slice($offset, $perPage);
        
        // Ensure HTTPS URLs for pagination when FORCE_HTTPS is enabled
        $path = request()->url();
        if (filter_var(env('FORCE_HTTPS', false), FILTER_VALIDATE_BOOLEAN) && 
            env('APP_URL') && str_starts_with(env('APP_URL'), 'https://')) {
            $path = str_replace('http://', 'https://', $path);
        }
        
        return new \Illuminate\Pagination\LengthAwarePaginator(
            $paginatedPayments,
            $total,
            $perPage,
            $page,
            ['path' => $path]
        );
    }

    /**
     * Get payment summaries by month
     */
    private function getPaymentSummaries()
    {
        $summaries = [];
        
        for ($i = 0; $i < 3; $i++) {
            $month = Carbon::now()->subMonths($i);
            $startOfMonth = $month->copy()->startOfMonth();
            $endOfMonth = $month->copy()->endOfMonth();
            
            // Enrollment payments for this month
            $enrollmentAmount = TraineeEnrollment::where('payment_status', 'paid')
                ->whereBetween('payment_date', [$startOfMonth, $endOfMonth])
                ->sum('enrollment_fee');
                
            $enrollmentPaid = TraineeEnrollment::where('payment_status', 'paid')
                ->whereBetween('payment_date', [$startOfMonth, $endOfMonth])
                ->count();
                
            $enrollmentPending = TraineeEnrollment::where('payment_status', '!=', 'paid')
                ->whereBetween('created_at', [$startOfMonth, $endOfMonth])
                ->whereNotNull('enrollment_fee')
                ->where('enrollment_fee', '>', 0)
                ->count();

            // Assessment payments for this month
            $assessmentAmount = Assessment::where('payment_status', 'paid')
                ->whereBetween('payment_date', [$startOfMonth, $endOfMonth])
                ->sum('assessment_fee');
                
            $assessmentPaid = Assessment::where('payment_status', 'paid')
                ->whereBetween('payment_date', [$startOfMonth, $endOfMonth])
                ->count();
                
            $assessmentPending = Assessment::where('payment_status', '!=', 'paid')
                ->whereBetween('created_at', [$startOfMonth, $endOfMonth])
                ->whereNotNull('assessment_fee')
                ->where('assessment_fee', '>', 0)
                ->count();

            // Combined totals
            $totalAmount = $enrollmentAmount + $assessmentAmount;
            $paid = $enrollmentPaid + $assessmentPaid;
            $pending = $enrollmentPending + $assessmentPending;
            
            $summaries[] = [
                'period' => $month->format('F Y'),
                'totalAmount' => $totalAmount,
                'paid' => $paid,
                'pending' => $pending,
            ];
        }
        
        return $summaries;
    }

    /**
     * Get recent activities
     */
    private function getRecentActivities()
    {
        // Get enrollment activities
        $enrollmentActivities = TraineeEnrollment::with(['trainee', 'program'])
            ->where('payment_status', 'paid')
            ->whereNotNull('payment_date')
            ->orderBy('payment_date', 'desc')
            ->limit(5)
            ->get()
            ->map(function ($enrollment) {
                return [
                    'id' => $enrollment->id,
                    'description' => "Enrollment payment received for {$enrollment->trainee->full_name} - {$enrollment->program->name} (₱" . number_format($enrollment->enrollment_fee, 0) . ")",
                    'timestamp' => $enrollment->payment_date->toISOString(),
                    'payment_date' => $enrollment->payment_date,
                ];
            });

        // Get registration activities (trainees who completed registration payment and enrolled)
        $registrationActivities = Trainee::where('payment_status', 'paid')
            ->whereNotNull('payment_date')
            ->whereHas('enrollments')
            ->orderBy('payment_date', 'desc')
            ->limit(5)
            ->get()
            ->map(function ($trainee) {
                $program = Program::where('name', $trainee->program_qualification)->first();
                $enrollmentFee = $program ? $program->enrollment_fee : 0;
                
                return [
                    'id' => $trainee->id,
                    'description' => "Enrollment fee payment received for {$trainee->full_name} - {$trainee->program_qualification} (₱" . number_format($enrollmentFee, 0) . ")",
                    'timestamp' => $trainee->payment_date->toISOString(),
                    'payment_date' => $trainee->payment_date,
                ];
            });

        // Get assessment activities
        $assessmentActivities = Assessment::with(['trainee', 'program'])
            ->where('payment_status', 'paid')
            ->whereNotNull('payment_date')
            ->orderBy('payment_date', 'desc')
            ->limit(5)
            ->get()
            ->map(function ($assessment) {
                $applicantName = $assessment->applicant_type === 'external_applicant' 
                    ? $assessment->external_applicant_name 
                    : ($assessment->trainee ? $assessment->trainee->full_name : 'N/A');
                    
                $programName = $assessment->program ? $assessment->program->name : $assessment->title;
                
                $paymentInfo = $assessment->assessment_fee == 0 && $assessment->payment_method === 'scholarship_exemption'
                    ? "(Scholar exemption - ₱0)"
                    : "(₱" . number_format($assessment->assessment_fee, 0) . ")";
                
                return [
                    'id' => $assessment->id,
                    'description' => "Assessment payment received for {$applicantName} - {$programName} {$paymentInfo}",
                    'timestamp' => $assessment->payment_date->toISOString(),
                    'payment_date' => $assessment->payment_date,
                ];
            });

        // Combine and sort all activities
        return $enrollmentActivities->concat($registrationActivities)->concat($assessmentActivities)
            ->sortByDesc('payment_date')
            ->take(10)
            ->map(function ($activity) {
                // Remove payment_date from final output
                unset($activity['payment_date']);
                return $activity;
            })
            ->values();
    }

    /**
     * Calculate payment summary statistics
     */
    private function calculatePaymentSummaryStats()
    {
        // Custom receipt totals (primary source of truth for collections)
        $totalCustomReceiptCollections = CustomReceipt::where('status', 'generated')->sum('total_amount');
        
        // Get enrollment totals for payments that haven't been processed into custom receipts yet
        // Only count enrollments that don't have corresponding custom receipts
        $enrollmentsWithReceipts = CustomReceipt::where('status', 'generated')
            ->whereNotNull('enrollment_id')
            ->pluck('enrollment_id')
            ->toArray();

        // Also exclude enrollments where the trainee has ANY custom receipt (registration, etc.)
        $traineeIdsWithAnyReceipts = CustomReceipt::where('status', 'generated')
            ->whereNotNull('trainee_model_id')
            ->pluck('trainee_model_id')
            ->toArray();
            
        $totalEnrollmentCollections = TraineeEnrollment::where('payment_status', 'paid')
            ->whereNotIn('id', $enrollmentsWithReceipts)
            ->whereNotIn('trainee_id', $traineeIdsWithAnyReceipts) // Exclude if trainee has ANY receipt
            ->sum('enrollment_fee');
        $totalEnrollmentCollectionsCount = TraineeEnrollment::where('payment_status', 'paid')
            ->whereNotIn('id', $enrollmentsWithReceipts)
            ->whereNotIn('trainee_id', $traineeIdsWithAnyReceipts) // Exclude if trainee has ANY receipt
            ->count();
        
        // Get assessment totals for payments that haven't been processed into custom receipts yet
        $assessmentsWithReceipts = CustomReceipt::where('status', 'generated')
            ->whereNotNull('assessment_id')
            ->pluck('assessment_id')
            ->toArray();
            
        $totalAssessmentCollections = Assessment::where('payment_status', 'paid')
            ->whereNotIn('id', $assessmentsWithReceipts)
            ->sum('assessment_fee');
        $totalAssessmentCollectionsCount = Assessment::where('payment_status', 'paid')
            ->whereNotIn('id', $assessmentsWithReceipts)
            ->count();
        
        // Registration totals (new trainees who paid but haven't been processed into custom receipts)
        // Exclude trainees who have ANY custom receipt (generated or cancelled) to avoid double counting
        $traineesWithReceipts = CustomReceipt::whereNotNull('trainee_model_id')
            ->pluck('trainee_model_id')
            ->toArray();
            
        $totalRegistrationCollections = Trainee::where('payment_status', 'paid')
            ->where('status', 'pending')
            ->whereDoesntHave('enrollments')
            ->whereNotIn('id', $traineesWithReceipts)
            ->get()
            ->sum(function ($trainee) {
                $program = Program::where('name', $trainee->program_qualification)->first();
                $enrollmentFee = $program ? $program->enrollment_fee : 0;
                
                $isScholar = $trainee->scholarship_package && trim($trainee->scholarship_package) !== '';
                $additionalFees = 0;
                
                if ($isScholar && $enrollmentFee == 0) {
                    $additionalFees = 500; // Trainee ID + Certification fees
                }
                
                return $enrollmentFee + $additionalFees;
            });
        $totalRegistrationCollectionsCount = Trainee::where('payment_status', 'paid')
            ->where('status', 'pending')
            ->whereDoesntHave('enrollments')
            ->whereNotIn('id', $traineesWithReceipts)
            ->count();
        
        // Combined totals (custom receipts are primary, add only unprocessed payments)
        $totalCollections = $totalCustomReceiptCollections + $totalEnrollmentCollections + $totalAssessmentCollections + $totalRegistrationCollections;
        $totalCollectionsCount = $totalEnrollmentCollectionsCount + $totalAssessmentCollectionsCount + $totalRegistrationCollectionsCount;
        
        $thisMonth = Carbon::now()->startOfMonth();
        
        // This month custom receipts (primary source)
        $thisMonthCustomReceiptAmount = CustomReceipt::where('status', 'generated')
            ->where('date_generated', '>=', $thisMonth)
            ->sum('total_amount');
            
        // This month enrollment (only those not processed into custom receipts)
        $thisMonthEnrollmentsWithReceipts = CustomReceipt::where('status', 'generated')
            ->whereNotNull('enrollment_id')
            ->where('date_generated', '>=', $thisMonth)
            ->pluck('enrollment_id')
            ->toArray();

        // Also exclude enrollments where the trainee has ANY custom receipt this month
        $thisMonthTraineeIdsWithAnyReceipts = CustomReceipt::where('status', 'generated')
            ->whereNotNull('trainee_model_id')
            ->where('date_generated', '>=', $thisMonth)
            ->pluck('trainee_model_id')
            ->toArray();
            
        $thisMonthEnrollmentAmount = TraineeEnrollment::where('payment_status', 'paid')
            ->where('payment_date', '>=', $thisMonth)
            ->whereNotIn('id', $thisMonthEnrollmentsWithReceipts)
            ->whereNotIn('trainee_id', $thisMonthTraineeIdsWithAnyReceipts) // Exclude if trainee has ANY receipt
            ->sum('enrollment_fee');
        $thisMonthEnrollmentCount = TraineeEnrollment::where('payment_status', 'paid')
            ->where('payment_date', '>=', $thisMonth)
            ->whereNotIn('id', $thisMonthEnrollmentsWithReceipts)
            ->whereNotIn('trainee_id', $thisMonthTraineeIdsWithAnyReceipts) // Exclude if trainee has ANY receipt
            ->count();
            
        // This month assessment (only those not processed into custom receipts)
        $thisMonthAssessmentsWithReceipts = CustomReceipt::where('status', 'generated')
            ->whereNotNull('assessment_id')
            ->where('date_generated', '>=', $thisMonth)
            ->pluck('assessment_id')
            ->toArray();
            
        $thisMonthAssessmentAmount = Assessment::where('payment_status', 'paid')
            ->where('payment_date', '>=', $thisMonth)
            ->whereNotIn('id', $thisMonthAssessmentsWithReceipts)
            ->sum('assessment_fee');
        $thisMonthAssessmentCount = Assessment::where('payment_status', 'paid')
            ->where('payment_date', '>=', $thisMonth)
            ->whereNotIn('id', $thisMonthAssessmentsWithReceipts)
            ->count();
            
        // This month registration (only those not processed into custom receipts)
        $thisMonthTraineesWithReceipts = CustomReceipt::where('status', 'generated')
            ->whereNotNull('trainee_model_id')
            ->where('date_generated', '>=', $thisMonth)
            ->pluck('trainee_model_id')
            ->toArray();
            
        $thisMonthRegistrationAmount = Trainee::where('payment_status', 'paid')
            ->where('status', 'pending')
            ->whereDoesntHave('enrollments')
            ->where('payment_date', '>=', $thisMonth)
            ->whereNotIn('id', $thisMonthTraineesWithReceipts)
            ->get()
            ->sum(function ($trainee) {
                $program = Program::where('name', $trainee->program_qualification)->first();
                $enrollmentFee = $program ? $program->enrollment_fee : 0;
                
                $isScholar = $trainee->scholarship_package && trim($trainee->scholarship_package) !== '';
                $additionalFees = 0;
                
                if ($isScholar && $enrollmentFee == 0) {
                    $additionalFees = 500; // Trainee ID + Certification fees
                }
                
                return $enrollmentFee + $additionalFees;
            });
        $thisMonthRegistrationCount = Trainee::where('payment_status', 'paid')
            ->where('status', 'pending')
            ->whereDoesntHave('enrollments')
            ->where('payment_date', '>=', $thisMonth)
            ->whereNotIn('id', $thisMonthTraineesWithReceipts)
            ->count();
            
        // Combined this month (custom receipts are primary, add only unprocessed payments)
        $thisMonthAmount = $thisMonthCustomReceiptAmount + $thisMonthEnrollmentAmount + $thisMonthAssessmentAmount + $thisMonthRegistrationAmount;
        $thisMonthCount = $thisMonthEnrollmentCount + $thisMonthAssessmentCount + $thisMonthRegistrationCount;
            
        // Outstanding enrollment (only those not processed into custom receipts)
        $outstandingEnrollmentsWithReceipts = CustomReceipt::where('status', 'generated')
            ->whereNotNull('enrollment_id')
            ->pluck('enrollment_id')
            ->toArray();
            
        $outstandingEnrollmentAmount = TraineeEnrollment::where('payment_status', '!=', 'paid')
            ->whereNotNull('enrollment_fee')
            ->where('enrollment_fee', '>', 0)
            ->whereNotIn('id', $outstandingEnrollmentsWithReceipts)
            ->sum('enrollment_fee');
        $outstandingEnrollmentCount = TraineeEnrollment::where('payment_status', '!=', 'paid')
            ->whereNotNull('enrollment_fee')
            ->where('enrollment_fee', '>', 0)
            ->whereNotIn('id', $outstandingEnrollmentsWithReceipts)
            ->count();
            
        // Outstanding assessment (only those not processed into custom receipts)
        $outstandingAssessmentsWithReceipts = CustomReceipt::where('status', 'generated')
            ->whereNotNull('assessment_id')
            ->pluck('assessment_id')
            ->toArray();
            
        $outstandingAssessmentAmount = Assessment::where('payment_status', '!=', 'paid')
            ->whereNotNull('assessment_fee')
            ->where('assessment_fee', '>', 0)
            ->whereNotIn('id', $outstandingAssessmentsWithReceipts)
            ->sum('assessment_fee');
        $outstandingAssessmentCount = Assessment::where('payment_status', '!=', 'paid')
            ->whereNotNull('assessment_fee')
            ->where('assessment_fee', '>', 0)
            ->whereNotIn('id', $outstandingAssessmentsWithReceipts)
            ->count();
            
        // Outstanding registration (only those not processed into custom receipts)
        $outstandingTraineesWithReceipts = CustomReceipt::where('status', 'generated')
            ->whereNotNull('trainee_model_id')
            ->pluck('trainee_model_id')
            ->toArray();
            
        $outstandingRegistrationAmount = Trainee::where('payment_status', '!=', 'paid')
            ->where('status', 'pending')
            ->whereDoesntHave('enrollments')
            ->whereNotIn('id', $outstandingTraineesWithReceipts)
            ->get()
            ->sum(function ($trainee) {
                $program = Program::where('name', $trainee->program_qualification)->first();
                $enrollmentFee = $program ? $program->enrollment_fee : 0;
                
                $isScholar = $trainee->scholarship_package && trim($trainee->scholarship_package) !== '';
                $additionalFees = 0;
                
                if ($isScholar && $enrollmentFee == 0) {
                    $additionalFees = ""; // Trainee ID + Certification fees
                }
                
                return $enrollmentFee + $additionalFees;
            });
        $outstandingRegistrationCount = Trainee::where('payment_status', '!=', 'paid')
            ->where('status', 'pending')
            ->whereDoesntHave('enrollments')
            ->whereNotIn('id', $outstandingTraineesWithReceipts)
            ->count();
            
        // Combined outstanding
        $outstandingAmount = $outstandingEnrollmentAmount + $outstandingAssessmentAmount + $outstandingRegistrationAmount;
        $outstandingCount = $outstandingEnrollmentCount + $outstandingAssessmentCount + $outstandingRegistrationCount;

        return [
            'totalCollections' => [
                'amount' => $totalCollections,
                'trainees' => $totalCollectionsCount,
            ],
            'thisMonth' => [
                'amount' => $thisMonthAmount,
                'trainees' => $thisMonthCount,
            ],
            'outstandingBalance' => [
                'amount' => $outstandingAmount,
                'trainees' => $outstandingCount,
            ],
        ];
    }

    /**
     * Get collections by program
     */
    private function getCollectionsByProgram()
    {
        return Program::with(['enrollments.trainee'])
            ->get()
            ->map(function ($program) {
                // Get enrollment data
                $enrollments = $program->enrollments;
                $enrollmentCount = $enrollments->count();
                $enrollmentPaid = $enrollments->where('payment_status', 'paid')->count();
                $enrollmentUnpaid = $enrollments->where('payment_status', 'unpaid')->count();
                $enrollmentAmount = $enrollments->where('payment_status', 'paid')->sum('enrollment_fee');

                // Get assessment data for this program (including scholar assessments)
                $assessments = Assessment::where('program_id', $program->program_id)
                    ->whereNotNull('assessment_fee')
                    ->get();
                    
                $assessmentCount = $assessments->count();
                $assessmentPaid = $assessments->where('payment_status', 'paid')->count();
                $assessmentUnpaid = $assessments->where('payment_status', 'unpaid')->count();
                $assessmentAmount = $assessments->where('payment_status', 'paid')->sum('assessment_fee');

                // Get registration data for this program (new trainees who haven't been enrolled yet)
                $registrations = Trainee::where('program_qualification', $program->name)
                    ->where('status', 'pending')
                    ->whereDoesntHave('enrollments')
                    ->get();
                    
                $registrationCount = $registrations->count();
                $registrationPaid = $registrations->where('payment_status', 'paid')->count();
                $registrationUnpaid = $registrations->where('payment_status', 'unpaid')->count();
                $registrationAmount = $registrations->where('payment_status', 'paid')
                    ->sum(function ($trainee) use ($program) {
                        $enrollmentFee = $program->enrollment_fee;
                        $isScholar = $trainee->scholarship_package && trim($trainee->scholarship_package) !== '';
                        $additionalFees = 0;
                        
                        if ($isScholar && $enrollmentFee == 0) {
                            $additionalFees = 500; // Trainee ID + Certification fees
                        }
                        
                        return $enrollmentFee + $additionalFees;
                    });

                // Get custom receipt data for this program (match by program name in fees array)
                $customReceiptAmount = CustomReceipt::where('status', 'generated')
                    ->get()
                    ->filter(function ($receipt) use ($program) {
                        $fees = $receipt->fees;
                        if (is_array($fees)) {
                            foreach ($fees as $fee) {
                                if (isset($fee['program']) && strpos($fee['program'], $program->name) !== false) {
                                    return true;
                                }
                            }
                        }
                        return false;
                    })
                    ->sum('total_amount');

                // Combined totals (including custom receipts)
                $totalPayments = $enrollmentCount + $assessmentCount + $registrationCount;
                $totalPaid = $enrollmentPaid + $assessmentPaid + $registrationPaid;
                $totalUnpaid = $enrollmentUnpaid + $assessmentUnpaid + $registrationUnpaid;
                $totalCollectionAmount = $enrollmentAmount + $assessmentAmount + $registrationAmount + $customReceiptAmount;

                return [
                    'program' => $program->name,
                    'totalTrainees' => $totalPayments, // Total payments (enrollments + assessments)
                    'fullyPaid' => $totalPaid,
                    'partiallyPaid' => 0, // We don't have partial payments yet
                    'unpaid' => $totalUnpaid,
                    'collectionAmount' => $totalCollectionAmount,
                ];
            })
            ->sortByDesc('collectionAmount')
            ->values();
    }
} 
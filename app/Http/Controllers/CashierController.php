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
use App\Models\Assessment;
use App\Models\Program;
use App\Models\Trainee;
use App\Models\CustomReceipt;
use App\Models\PaymentSummary;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CashierController extends Controller
{
    /**
     * Map a CustomReceipt model to the standard receipt array format.
     * Shared by enrollment, assessment, registration, and cancelled receipt queries.
     */
    private function mapCustomReceipt(CustomReceipt $receipt): array
    {
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
                'trainee_id' => $receipt->trainee_id_number,
            ],
            'program' => collect($allFees)->pluck('program')->unique()->filter()->join(', '),
            'natureOfCollection' => collect($allFees)->pluck('natureOfCollection')->unique()->join(', '),
            'amount' => $receipt->total_amount,
            'dateGenerated' => $receipt->date_generated->format('Y-m-d'),
            'timeGenerated' => $receipt->time_generated->format('g:i A'),
            'status' => $receipt->status,
            'cancellation_reason' => $receipt->cancellation_reason ?? null,
            'fees' => $receipt->fees ?: [],
            'original_fees' => $receipt->original_fees ?: [],
            'isCustom' => true,
            'customReceiptId' => $receipt->id,
        ];
    }

    /**
     * Display the cashier dashboard
     */
    public function dashboard(Request $request)
    {
        $perPage = min((int) $request->get('per_page', 3), 100);
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
     * Display enrollment payments page (registration fees for new trainees)
     */
    public function enrollmentPayments(Request $request)
    {
        return $this->renderPaymentPage($request, 'registration');
    }

    /**
     * Display additional payments page (additional fees for enrolled trainees)
     */
    public function additionalPayments(Request $request)
    {
        return $this->renderPaymentPage($request, 'enrollment');
    }

    /**
     * Display assessment payments page
     */
    public function assessmentPayments(Request $request)
    {
        return $this->renderPaymentPage($request, 'assessment');
    }

    /**
     * Helper method to render payment page by type
     */
    private function renderPaymentPage(Request $request, $type)
    {
        $perPage = min((int) $request->get('per_page', 20), 100);
        $search = $this->sanitizeSearch($request->get('search', ''));
        $status = $request->get('status', '');
        $currentPage = (int) $request->get('page', 1);

        // Scalability: Only query the ACTIVE tab's data with DB-level pagination.
        // Previously loaded ALL records from ALL 3 types into memory (~500MB at 1M rows).
        // Now we query only the requested type and use COUNT() for the other tabs.

        // --- Tab counts (lightweight COUNT queries for badges) ---
        $registrationCount = $this->getRegistrationPaymentsQuery($search)->count();
        $enrollmentCount = TraineeEnrollment::where(function ($query) {
                $query->whereNotNull('enrollment_fee')
                      ->orWhereHas('trainee', function ($subQuery) {
                          $subQuery->whereNotNull('scholarship_package')
                                   ->where('scholarship_package', '!=', '');
                      });
            })
            ->when($search, function ($query) use ($search) {
                $query->whereHas('trainee', function ($q) use ($search) {
                    $q->where('first_name', 'like', "%{$search}%")
                      ->orWhere('last_name', 'like', "%{$search}%")
                      ->orWhere('uli_number', 'like', "%{$search}%");
                });
            })
            ->count();
        $assessmentCount = Assessment::whereNotNull('assessment_fee')
            ->when($search, function ($query) use ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('external_applicant_name', 'like', "%{$search}%")
                      ->orWhereHas('trainee', function ($tq) use ($search) {
                          $tq->where('first_name', 'like', "%{$search}%")
                             ->orWhere('last_name', 'like', "%{$search}%")
                             ->orWhere('uli_number', 'like', "%{$search}%");
                      });
                });
            })
            ->count();

        // --- Active tab data (paginated) ---
        $paginatedPayments = collect();
        $totalItems = 0;

        if ($type === 'enrollment') {
            $query = TraineeEnrollment::with(['trainee', 'program'])
                ->where(function ($query) {
                    $query->whereNotNull('enrollment_fee')
                          ->orWhereHas('trainee', function ($subQuery) {
                              $subQuery->whereNotNull('scholarship_package')
                                       ->where('scholarship_package', '!=', '');
                          });
                });
            if ($search) {
                $query->whereHas('trainee', function ($q) use ($search) {
                    $q->where('first_name', 'like', "%{$search}%")
                      ->orWhere('last_name', 'like', "%{$search}%")
                      ->orWhere('uli_number', 'like', "%{$search}%");
                });
            }
            $paginated = $query->orderBy('created_at', 'desc')->paginate($perPage);
            $paginatedPayments = $paginated->through(function ($enrollment) {
                $isScholar = $enrollment->trainee &&
                            $enrollment->trainee->scholarship_package &&
                            trim($enrollment->trainee->scholarship_package) !== '';
                $additionalFees = 0;
                $programName = $enrollment->program->name;
                if ($isScholar) {
                    $additionalFees = 500;
                    $programName .= ' (Scholar - Additional Fees)';
                }
                $hasRegistrationReceipt = CustomReceipt::where('type', 'registration')
                    ->where('status', 'generated')
                    ->where('trainee_model_id', $enrollment->trainee->id)
                    ->exists();
                if ($enrollment->trainee && $enrollment->trainee->payment_status === 'paid' && !$hasRegistrationReceipt && !$isScholar) {
                    return null;
                }
                $totalAmount = $enrollment->enrollment_fee + $additionalFees;
                if ($totalAmount <= 0) {
                    return null;
                }
                $actualStatus = 'unpaid';
                if ($isScholar && $additionalFees > 0) {
                    $hasCustomReceipt = CustomReceipt::where('enrollment_id', $enrollment->id)
                        ->where('status', 'generated')->exists();
                    $actualStatus = $hasCustomReceipt ? 'paid' : 'unpaid';
                } else {
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
            });
        } elseif ($type === 'assessment') {
            $query = Assessment::with(['trainee', 'program'])->whereNotNull('assessment_fee');
            if ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('external_applicant_name', 'like', "%{$search}%")
                      ->orWhereHas('trainee', function ($tq) use ($search) {
                          $tq->where('first_name', 'like', "%{$search}%")
                             ->orWhere('last_name', 'like', "%{$search}%")
                             ->orWhere('uli_number', 'like', "%{$search}%");
                      });
                });
            }
            $paginatedPayments = $query->orderBy('created_at', 'desc')
                ->paginate($perPage)
                ->through(function ($assessment) {
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
                        'trainee' => ['name' => $applicantName, 'id' => $applicantId, 'uli_number' => $assessment->trainee ? $assessment->trainee->uli_number : null],
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
        } else {
            // Registration tab (default)
            $query = $this->getRegistrationPaymentsQuery($search);
            $paginated = $query->orderBy('created_at', 'desc')->paginate($perPage);
            $paginatedPayments = $paginated->through(function ($trainee) {
                $program = Program::where('name', $trainee->program_qualification)->first();
                $enrollmentFee = $program ? $program->enrollment_fee : 0;
                $totalAmount = $enrollmentFee;
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
                    'program' => $trainee->program_qualification,
                    'amount' => $totalAmount,
                    'receiptNo' => $trainee->payment_reference ?: 'RN-REG-' . str_pad($trainee->id, 4, '0', STR_PAD_LEFT),
                    'date' => $trainee->created_at->format('Y-m-d'),
                    'status' => $trainee->payment_status === 'paid' ? 'paid_pending_enrollment' : 'unpaid',
                    'enrollment_id' => null,
                    'assessment_id' => null,
                    'trainee_id' => $trainee->id,
                    'is_scholarship' => false,
                    'additional_fees' => 0,
                ];
            });
        }

        // Calculate summary statistics
        $summaryStats = $this->calculatePaymentSummaryStats();
        
        // Get collections by program
        $collectionsByProgram = $this->getCollectionsByProgram();

        return Inertia::render('Cashier/Payments', [
            'enrollmentPayments' => $paginatedPayments,
            'assessmentPayments' => collect(), // No longer sent as a separate full collection
            'summaryStats' => $summaryStats,
            'collectionsByProgram' => $collectionsByProgram,
            'paymentCounts' => [
                'registration' => $registrationCount,
                'enrollment' => $enrollmentCount,
                'assessment' => $assessmentCount,
            ],
            'filters' => [
                'search' => $search,
                'status' => $status,
                'type' => $type,
                'per_page' => $perPage,
            ],
            'paymentType' => $type,
        ]);
    }

    /**
     * Build the registration payments query (reusable for both data and count).
     */
    private function getRegistrationPaymentsQuery(string $search = '')
    {
        $query = Trainee::query()
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
                $query->whereNull('scholarship_package')
                      ->orWhere('scholarship_package', '');
            });
        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('first_name', 'like', "%{$search}%")
                  ->orWhere('last_name', 'like', "%{$search}%")
                  ->orWhere('uli_number', 'like', "%{$search}%");
            });
        }
        return $query;
    }

    /**
     * Display payments management page (legacy - redirects to enrollment)
     */
    public function payments(Request $request)
    {
        return redirect()->route('cashier.payments.enrollment');
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
            ->map(fn($receipt) => $this->mapCustomReceipt($receipt));

        $customAssessmentReceipts = CustomReceipt::where('type', 'assessment')
            ->where('status', 'generated')
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(fn($receipt) => $this->mapCustomReceipt($receipt));

        // Get custom registration receipts (newly registered trainees who got receipts)
        $customRegistrationReceipts = CustomReceipt::where('type', 'registration')
            ->where('status', 'generated')
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(fn($receipt) => $this->mapCustomReceipt($receipt));

        // Get cancelled receipts (for audit purposes)
        $cancelledReceipts = CustomReceipt::where('status', 'cancelled')
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(fn($receipt) => $this->mapCustomReceipt($receipt));

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

            // Idempotency guard: skip if already paid
            if ($enrollment->payment_status === 'paid') {
                return redirect()->back()->with('success', 'Payment was already processed for this enrollment.');
            }
            
            $enrollment->markAsPaid(
                $request->payment_method,
                $request->payment_reference ?: 'RN-ENR-' . str_pad($enrollment->id, 4, '0', STR_PAD_LEFT),
                $request->payment_notes
            );

            // Keep trainee model in sync — use forceFill since payment fields are guarded
            if ($enrollment->trainee) {
                $enrollment->trainee->forceFill([
                    'payment_status' => 'paid',
                    'status' => 'active',
                    'payment_method' => $request->payment_method,
                    'payment_reference' => $request->payment_reference ?: 'RN-ENR-' . str_pad($enrollment->id, 4, '0', STR_PAD_LEFT),
                    'payment_date' => now(),
                    'payment_notes' => $request->payment_notes,
                ])->save();
            }
        } else if ($request->assessment_id) {
            // Process assessment payment
            $assessment = Assessment::findOrFail($request->assessment_id);

            // Idempotency guard: skip if already paid
            if ($assessment->payment_status === 'paid') {
                return redirect()->back()->with('success', 'Payment was already processed for this assessment.');
            }
            
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

            // Idempotency guard: skip if already paid
            if ($trainee->payment_status === 'paid') {
                return redirect()->back()->with('success', 'Payment was already processed for this trainee.');
            }

            if ($request->skip_enrollment) {
                // Mark as paid and activate immediately to trigger auto-enrollment. Receipt can follow.
                // Use forceFill since payment/status fields are guarded.
                $trainee->forceFill([
                    'payment_status' => 'paid',
                    'status' => 'active',
                    'payment_method' => $request->payment_method,
                    'payment_reference' => $request->payment_reference ?: 'RN-REG-' . str_pad($trainee->id, 4, '0', STR_PAD_LEFT),
                    'payment_date' => now(),
                    'payment_notes' => $request->payment_notes
                ])->save();
                
                // Refresh the model to get the latest data
                $trainee->refresh();
            } else {
                // Standard flow: Update trainee payment status and activate them
                // Use forceFill since payment/status fields are guarded.
                $trainee->forceFill([
                    'payment_status' => 'paid',
                    'status' => 'active',
                    'payment_method' => $request->payment_method,
                    'payment_reference' => $request->payment_reference ?: 'RN-REG-' . str_pad($trainee->id, 4, '0', STR_PAD_LEFT),
                    'payment_date' => now(),
                    'payment_notes' => $request->payment_notes
                ])->save();

                // The handleAutoEnrollment method will be triggered automatically via the model's boot method
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
            
            // Activate the trainee and trigger enrollment — use forceFill since status is guarded
            $trainee->forceFill([
                'status' => 'active'
            ])->save();
            
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
            
        // Scalability: Use DB-level JOIN SUM instead of loading all trainees into PHP memory
        $currentRegistrationTotalNoReceipt = (float) DB::table('trainees as t')
            ->leftJoin('programs as p', 'p.name', '=', 't.program_qualification')
            ->where('t.payment_status', 'paid')
            ->where('t.payment_date', '>=', $currentMonth)
            ->whereNotExists(function ($q) {
                $q->select(DB::raw(1))->from('trainee_enrollments')->whereColumn('trainee_id', 't.id');
            })
            ->when(!empty($currentMonthTraineesWithReceipts), function ($q) use ($currentMonthTraineesWithReceipts) {
                $q->whereNotIn('t.id', $currentMonthTraineesWithReceipts);
            })
            ->sum(DB::raw('COALESCE(p.enrollment_fee, 0) + IF(t.scholarship_package IS NOT NULL AND t.scholarship_package != "" AND COALESCE(p.enrollment_fee, 0) = 0, 500, 0)'));

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
            
        // Scalability: Use DB-level JOIN SUM instead of loading all trainees into PHP memory
        $lastRegistrationTotalNoReceipt = (float) DB::table('trainees as t')
            ->leftJoin('programs as p', 'p.name', '=', 't.program_qualification')
            ->where('t.payment_status', 'paid')
            ->whereBetween('t.payment_date', [$lastMonth, $currentMonth])
            ->whereNotExists(function ($q) {
                $q->select(DB::raw(1))->from('trainee_enrollments')->whereColumn('trainee_id', 't.id');
            })
            ->when(!empty($lastMonthTraineesWithReceipts), function ($q) use ($lastMonthTraineesWithReceipts) {
                $q->whereNotIn('t.id', $lastMonthTraineesWithReceipts);
            })
            ->sum(DB::raw('COALESCE(p.enrollment_fee, 0) + IF(t.scholarship_package IS NOT NULL AND t.scholarship_package != "" AND COALESCE(p.enrollment_fee, 0) = 0, 500, 0)'));

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

    private function getPaymentStatus($perPage = 9, $page = 1)
    {
        // Scalability: Use DB-level LIMIT instead of loading ALL records into PHP memory.
        // Previously loaded ALL enrollments + registrations + assessments (~500MB at 1M rows).
        // Now uses a UNION ALL query with proper LIMIT/OFFSET for true DB pagination.

        // Use raw UNION query to combine all payment types with DB-level pagination
        $enrollmentQuery = DB::table('trainee_enrollments as te')
            ->join('trainees as t', 't.id', '=', 'te.trainee_id')
            ->join('programs as p', 'p.program_id', '=', 'te.program_id')
            ->whereNotNull('te.enrollment_fee')
            ->select(
                DB::raw("CONCAT('ENR-', LPAD(te.id, 4, '0')) as id"),
                DB::raw("UPPER(CONCAT(LEFT(t.first_name, 1), LEFT(t.last_name, 1))) as initials"),
                DB::raw("CONCAT(t.first_name, ' ', t.last_name) as name"),
                DB::raw("CONCAT(p.name, IF(t.scholarship_package IS NOT NULL AND t.scholarship_package != '', ' (Scholar - Enrollment Fee)', ' (Enrollment)')) as program"),
                DB::raw("IF(t.scholarship_package IS NOT NULL AND t.scholarship_package != '', 0, te.enrollment_fee) as amountDue"),
                DB::raw("IF(te.payment_status = 'paid', 'paid', 'pending') as status"),
                'te.created_at',
                DB::raw("IF(t.scholarship_package IS NOT NULL AND t.scholarship_package != '', 1, 0) as is_scholarship")
            );

        $registrationQuery = DB::table('trainees as t')
            ->leftJoin('trainee_enrollments as te', 'te.trainee_id', '=', 't.id')
            ->where('t.payment_status', 'unpaid')
            ->where('t.status', 'pending')
            ->whereNull('te.id')
            ->leftJoin('programs as p', 'p.name', '=', 't.program_qualification')
            ->select(
                DB::raw("CONCAT('REG-', LPAD(t.id, 4, '0')) as id"),
                DB::raw("UPPER(CONCAT(LEFT(t.first_name, 1), LEFT(t.last_name, 1))) as initials"),
                DB::raw("CONCAT(t.first_name, ' ', t.last_name) as name"),
                DB::raw("CONCAT(t.program_qualification, IF(t.scholarship_package IS NOT NULL AND t.scholarship_package != '', ' (Scholar - Enrollment Fee)', ' (Enrollment Fee)')) as program"),
                DB::raw("IF(t.scholarship_package IS NOT NULL AND t.scholarship_package != '', 0, COALESCE(p.enrollment_fee, 0)) as amountDue"),
                DB::raw("IF(t.payment_status = 'paid', 'paid', 'pending') as status"),
                't.created_at',
                DB::raw("IF(t.scholarship_package IS NOT NULL AND t.scholarship_package != '', 1, 0) as is_scholarship")
            );

        $assessmentQuery = DB::table('assessments as a')
            ->leftJoin('trainees as t', 't.id', '=', 'a.trainee_id')
            ->leftJoin('programs as p', 'p.program_id', '=', 'a.program_id')
            ->whereNotNull('a.assessment_fee')
            ->select(
                DB::raw("CONCAT('ASS-', LPAD(a.id, 4, '0')) as id"),
                DB::raw("UPPER(CONCAT(LEFT(COALESCE(IF(a.applicant_type='external_applicant', a.external_applicant_name, t.first_name), 'N'), 1), LEFT(COALESCE(t.last_name, 'A'), 1))) as initials"),
                DB::raw("COALESCE(IF(a.applicant_type='external_applicant', a.external_applicant_name, CONCAT(t.first_name, ' ', t.last_name)), 'N/A') as name"),
                DB::raw("CONCAT(COALESCE(p.name, a.title), ' (Assessment)') as program"),
                DB::raw("a.assessment_fee as amountDue"),
                DB::raw("IF(a.payment_status = 'paid', 'paid', 'pending') as status"),
                'a.created_at',
                DB::raw("IF(a.assessment_fee = 0 AND a.payment_method = 'scholarship_exemption', 1, 0) as is_scholarship")
            );

        // UNION ALL + ORDER + LIMIT at the DB level
        $total = DB::query()->fromSub(
            $enrollmentQuery->unionAll($registrationQuery)->unionAll($assessmentQuery), 'combined'
        )->count();

        $results = DB::query()->fromSub(
            $enrollmentQuery->unionAll($registrationQuery)->unionAll($assessmentQuery), 'combined'
        )
            ->orderByDesc('created_at')
            ->offset(($page - 1) * $perPage)
            ->limit($perPage)
            ->get();

        // Ensure HTTPS URLs for pagination when FORCE_HTTPS is enabled
        $path = request()->url();
        if (config('app.force_https') && str_starts_with(config('app.url', ''), 'https://')) {
            $path = str_replace('http://', 'https://', $path);
        }

        return new \Illuminate\Pagination\LengthAwarePaginator(
            $results,
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
            
        // Scalability: Use cached totals for the global paid enrollment SUM/COUNT.
        // These queries scan 2M+ rows (6.8s each). The cache is updated atomically
        // by PaymentSummaryObserver on every payment event.
        $totalEnrollmentCollections = PaymentSummary::getValue('enrollment_paid_sum');
        $totalEnrollmentCollectionsCount = (int) PaymentSummary::getValue('enrollment_paid_count');
        
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
            
        // Scalability: Use DB-level JOIN SUM instead of loading all trainees into PHP memory
        $totalRegistrationCollections = (float) DB::table('trainees as t')
            ->leftJoin('programs as p', 'p.name', '=', 't.program_qualification')
            ->where('t.payment_status', 'paid')
            ->where('t.status', 'pending')
            ->whereNotExists(function ($q) {
                $q->select(DB::raw(1))->from('trainee_enrollments')->whereColumn('trainee_id', 't.id');
            })
            ->when(!empty($traineesWithReceipts), function ($q) use ($traineesWithReceipts) {
                $q->whereNotIn('t.id', $traineesWithReceipts);
            })
            ->sum(DB::raw('COALESCE(p.enrollment_fee, 0) + IF(t.scholarship_package IS NOT NULL AND t.scholarship_package != "" AND COALESCE(p.enrollment_fee, 0) = 0, 500, 0)'));
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
            
        // Scalability: Use DB-level JOIN SUM instead of loading all trainees into PHP memory
        $thisMonthRegistrationAmount = (float) DB::table('trainees as t')
            ->leftJoin('programs as p', 'p.name', '=', 't.program_qualification')
            ->where('t.payment_status', 'paid')
            ->where('t.status', 'pending')
            ->whereNotExists(function ($q) {
                $q->select(DB::raw(1))->from('trainee_enrollments')->whereColumn('trainee_id', 't.id');
            })
            ->where('t.payment_date', '>=', $thisMonth)
            ->when(!empty($thisMonthTraineesWithReceipts), function ($q) use ($thisMonthTraineesWithReceipts) {
                $q->whereNotIn('t.id', $thisMonthTraineesWithReceipts);
            })
            ->sum(DB::raw('COALESCE(p.enrollment_fee, 0) + IF(t.scholarship_package IS NOT NULL AND t.scholarship_package != "" AND COALESCE(p.enrollment_fee, 0) = 0, 500, 0)'));
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
            
        // Scalability: Use cached totals for outstanding enrollment aggregations.
        // These queries scan 2M+ rows (6.2s each). Cache is maintained by observer.
        $outstandingEnrollmentAmount = PaymentSummary::getValue('enrollment_unpaid_sum');
        $outstandingEnrollmentCount = (int) PaymentSummary::getValue('enrollment_unpaid_count');
            
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
            
        // Scalability: Use DB-level JOIN SUM instead of loading all trainees into PHP memory
        // Also fixes bug: $additionalFees was being set to empty string instead of 500
        $outstandingRegistrationAmount = (float) DB::table('trainees as t')
            ->leftJoin('programs as p', 'p.name', '=', 't.program_qualification')
            ->where('t.payment_status', '!=', 'paid')
            ->where('t.status', 'pending')
            ->whereNotExists(function ($q) {
                $q->select(DB::raw(1))->from('trainee_enrollments')->whereColumn('trainee_id', 't.id');
            })
            ->when(!empty($outstandingTraineesWithReceipts), function ($q) use ($outstandingTraineesWithReceipts) {
                $q->whereNotIn('t.id', $outstandingTraineesWithReceipts);
            })
            ->sum(DB::raw('COALESCE(p.enrollment_fee, 0) + IF(t.scholarship_package IS NOT NULL AND t.scholarship_package != "" AND COALESCE(p.enrollment_fee, 0) = 0, 500, 0)'));
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
        // Scalability: Use DB-level aggregations instead of loading all records per program.
        // Previously loaded ALL enrollments, assessments, registrations, and receipts for each
        // program into PHP memory — N×4 full-table scans with N programs.

        return Program::withCount([
                'enrollments as enrollment_count',
                'enrollments as enrollment_paid' => function ($q) { $q->where('payment_status', 'paid'); },
                'enrollments as enrollment_unpaid' => function ($q) { $q->where('payment_status', 'unpaid'); },
            ])
            ->get()
            ->map(function ($program) {
                // Enrollment amount — single SUM at DB level
                $enrollmentAmount = (float) TraineeEnrollment::where('program_id', $program->program_id)
                    ->where('payment_status', 'paid')
                    ->sum('enrollment_fee');

                // Assessment stats — DB-level COUNT + SUM
                $assessmentCount = Assessment::where('program_id', $program->program_id)
                    ->whereNotNull('assessment_fee')->count();
                $assessmentPaid = Assessment::where('program_id', $program->program_id)
                    ->whereNotNull('assessment_fee')->where('payment_status', 'paid')->count();
                $assessmentUnpaid = Assessment::where('program_id', $program->program_id)
                    ->whereNotNull('assessment_fee')->where('payment_status', 'unpaid')->count();
                $assessmentAmount = (float) Assessment::where('program_id', $program->program_id)
                    ->where('payment_status', 'paid')->sum('assessment_fee');

                // Registration stats — DB-level COUNT
                $registrationBase = Trainee::where('program_qualification', $program->name)
                    ->where('status', 'pending')
                    ->whereDoesntHave('enrollments');
                $registrationCount = (clone $registrationBase)->count();
                $registrationPaid = (clone $registrationBase)->where('payment_status', 'paid')->count();
                $registrationUnpaid = (clone $registrationBase)->where('payment_status', 'unpaid')->count();

                // Registration amount — for paid registrations, use program enrollment fee
                $registrationAmount = $registrationPaid * ($program->enrollment_fee ?: 0);

                // Custom receipt amount — sum only receipts linked to this program via enrollment_id
                $customReceiptAmount = (float) CustomReceipt::where('status', 'generated')
                    ->whereHas('enrollment', function ($q) use ($program) {
                        $q->where('program_id', $program->program_id);
                    })
                    ->sum('total_amount');

                // Combined totals
                $totalPayments = $program->enrollment_count + $assessmentCount + $registrationCount;
                $totalPaid = $program->enrollment_paid + $assessmentPaid + $registrationPaid;
                $totalUnpaid = $program->enrollment_unpaid + $assessmentUnpaid + $registrationUnpaid;
                $totalCollectionAmount = $enrollmentAmount + $assessmentAmount + $registrationAmount + $customReceiptAmount;

                return [
                    'program' => $program->name,
                    'totalTrainees' => $totalPayments,
                    'fullyPaid' => $totalPaid,
                    'partiallyPaid' => 0,
                    'unpaid' => $totalUnpaid,
                    'collectionAmount' => $totalCollectionAmount,
                ];
            })
            ->sortByDesc('collectionAmount')
            ->values();
    }
} 
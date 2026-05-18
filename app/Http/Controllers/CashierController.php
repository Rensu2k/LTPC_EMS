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
        
        $stats = $this->calculateDashboardStats();
        
        $paymentStatus = $this->getPaymentStatus($perPage, $page);

        $paymentSummaries = $this->getPaymentSummaries();
        
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

        $summaryStats = $this->calculatePaymentSummaryStats();
        
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
        $enrollmentReceipts = collect(); // Start with empty collection, only custom receipts will be shown

        $assessmentReceipts = Assessment::with(['trainee', 'program'])
            ->where('payment_status', 'paid')
            ->whereNotNull('payment_reference')
            ->where(function ($query) {
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

        $customRegistrationReceipts = CustomReceipt::where('type', 'registration')
            ->where('status', 'generated')
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(fn($receipt) => $this->mapCustomReceipt($receipt));

        $cancelledReceipts = CustomReceipt::where('status', 'cancelled')
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(fn($receipt) => $this->mapCustomReceipt($receipt));

        $customEnrollmentPaymentIds = $customEnrollmentReceipts->pluck('paymentId')->toArray();
        $customAssessmentPaymentIds = $customAssessmentReceipts->pluck('paymentId')->toArray();
        $customRegistrationPaymentIds = $customRegistrationReceipts->pluck('paymentId')->toArray();

        $finalEnrollmentReceipts = $enrollmentReceipts->filter(function ($receipt) use ($customEnrollmentPaymentIds) {
            return !in_array($receipt['paymentId'], $customEnrollmentPaymentIds);
        })->concat($customEnrollmentReceipts);

        $finalAssessmentReceipts = $assessmentReceipts->filter(function ($receipt) use ($customAssessmentPaymentIds) {
            return !in_array($receipt['paymentId'], $customAssessmentPaymentIds);
        })->concat($customAssessmentReceipts);

        $allReceipts = $finalEnrollmentReceipts->concat($finalAssessmentReceipts)->concat($customRegistrationReceipts);

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

        if (!$request->enrollment_id && !$request->assessment_id && !$request->trainee_id) {
            return redirect()->back()->withErrors(['error' => 'Either enrollment, assessment, or trainee ID must be provided.']);
        }

        if ($request->enrollment_id) {
            $enrollment = TraineeEnrollment::findOrFail($request->enrollment_id);

            if ($enrollment->payment_status === 'paid') {
                return redirect()->back()->with('success', 'Payment was already processed for this enrollment.');
            }
            
            $enrollment->markAsPaid(
                $request->payment_method,
                $request->payment_reference ?: 'RN-ENR-' . str_pad($enrollment->id, 4, '0', STR_PAD_LEFT),
                $request->payment_notes
            );

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
            $assessment = Assessment::findOrFail($request->assessment_id);

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
            $trainee = Trainee::findOrFail($request->trainee_id);

            if ($trainee->payment_status === 'paid') {
                return redirect()->back()->with('success', 'Payment was already processed for this trainee.');
            }

            if ($request->skip_enrollment) {
                $trainee->forceFill([
                    'payment_status' => 'paid',
                    'status' => 'active',
                    'payment_method' => $request->payment_method,
                    'payment_reference' => $request->payment_reference ?: 'RN-REG-' . str_pad($trainee->id, 4, '0', STR_PAD_LEFT),
                    'payment_date' => now(),
                    'payment_notes' => $request->payment_notes
                ])->save();
                
                $trainee->refresh();
            } else {
                $trainee->forceFill([
                    'payment_status' => 'paid',
                    'status' => 'active',
                    'payment_method' => $request->payment_method,
                    'payment_reference' => $request->payment_reference ?: 'RN-REG-' . str_pad($trainee->id, 4, '0', STR_PAD_LEFT),
                    'payment_date' => now(),
                    'payment_notes' => $request->payment_notes
                ])->save();

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

        $isCancelled = $request->status === 'cancelled';

        $originalFees = collect($request->fees)->take(1)->toArray(); // First fee is original
        $customFees = collect($request->fees)->skip(1)->toArray(); // Rest are custom

        $totalAmount = collect($request->fees)->sum('amount');

        $baseReceiptNo = $request->receiptNo;
        $receiptNo = $baseReceiptNo;
        $counter = 1;
        
        while (CustomReceipt::where('receipt_number', $receiptNo)->exists()) {
            $receiptNo = $baseReceiptNo . '-' . $counter;
            $counter++;
        }

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

        if (!$isCancelled && $request->complete_enrollment && $request->type === 'registration' && $request->trainee_id) {
            $trainee = Trainee::findOrFail($request->trainee_id);
            
            $trainee->forceFill([
                'status' => 'active'
            ])->save();
            
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

        $currentRegistrationPending = Trainee::where('payment_status', 'unpaid')
            ->where('status', 'pending')
            ->where('created_at', '>=', $currentMonth)
            ->whereDoesntHave('enrollments')
            ->count();

        $currentRegistrationPaid = Trainee::where('payment_status', 'paid')
            ->where('payment_date', '>=', $currentMonth)
            ->whereHas('enrollments')
            ->count();

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

        $currentMonthTraineesWithReceipts = CustomReceipt::where('date_generated', '>=', $currentMonth)
            ->whereNotNull('trainee_model_id')
            ->pluck('trainee_model_id')
            ->toArray();
            
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

        $currentTotal = $currentMonthCustomReceiptsTotal + $currentEnrollmentTotalNoReceipt + $currentAssessmentTotalNoReceipt + $currentRegistrationTotalNoReceipt;
        $currentPending = $currentEnrollmentPending + $currentAssessmentPending + $currentRegistrationPending;
        $currentReceipts = $currentEnrollmentReceipts + $currentAssessmentReceipts + $currentRegistrationPaid;

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

        $lastRegistrationPending = Trainee::where('payment_status', 'unpaid')
            ->where('status', 'pending')
            ->whereBetween('created_at', [$lastMonth, $currentMonth])
            ->whereDoesntHave('enrollments')
            ->count();

        $lastRegistrationPaid = Trainee::where('payment_status', 'paid')
            ->whereBetween('payment_date', [$lastMonth, $currentMonth])
            ->whereHas('enrollments')
            ->count();

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

        $lastMonthTraineesWithReceipts = CustomReceipt::whereBetween('date_generated', [$lastMonth, $currentMonth])
            ->whereNotNull('trainee_model_id')
            ->pluck('trainee_model_id')
            ->toArray();
            
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

        return $enrollmentActivities->concat($registrationActivities)->concat($assessmentActivities)
            ->sortByDesc('payment_date')
            ->take(10)
            ->map(function ($activity) {
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
        $totalCustomReceiptCollections = CustomReceipt::where('status', 'generated')->sum('total_amount');
        
        $enrollmentsWithReceipts = CustomReceipt::where('status', 'generated')
            ->whereNotNull('enrollment_id')
            ->pluck('enrollment_id')
            ->toArray();

        $traineeIdsWithAnyReceipts = CustomReceipt::where('status', 'generated')
            ->whereNotNull('trainee_model_id')
            ->pluck('trainee_model_id')
            ->toArray();
            
        $totalEnrollmentCollections = PaymentSummary::getValue('enrollment_paid_sum');
        $totalEnrollmentCollectionsCount = (int) PaymentSummary::getValue('enrollment_paid_count');
        
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
        
        $traineesWithReceipts = CustomReceipt::whereNotNull('trainee_model_id')
            ->pluck('trainee_model_id')
            ->toArray();
            
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
        
        $totalCollections = $totalCustomReceiptCollections + $totalEnrollmentCollections + $totalAssessmentCollections + $totalRegistrationCollections;
        $totalCollectionsCount = $totalEnrollmentCollectionsCount + $totalAssessmentCollectionsCount + $totalRegistrationCollectionsCount;
        
        $thisMonth = Carbon::now()->startOfMonth();
        
        $thisMonthCustomReceiptAmount = CustomReceipt::where('status', 'generated')
            ->where('date_generated', '>=', $thisMonth)
            ->sum('total_amount');
            
        $thisMonthEnrollmentsWithReceipts = CustomReceipt::where('status', 'generated')
            ->whereNotNull('enrollment_id')
            ->where('date_generated', '>=', $thisMonth)
            ->pluck('enrollment_id')
            ->toArray();

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
            
        $thisMonthTraineesWithReceipts = CustomReceipt::where('status', 'generated')
            ->whereNotNull('trainee_model_id')
            ->where('date_generated', '>=', $thisMonth)
            ->pluck('trainee_model_id')
            ->toArray();
            
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
            
        $thisMonthAmount = $thisMonthCustomReceiptAmount + $thisMonthEnrollmentAmount + $thisMonthAssessmentAmount + $thisMonthRegistrationAmount;
        $thisMonthCount = $thisMonthEnrollmentCount + $thisMonthAssessmentCount + $thisMonthRegistrationCount;
            
        $outstandingEnrollmentsWithReceipts = CustomReceipt::where('status', 'generated')
            ->whereNotNull('enrollment_id')
            ->pluck('enrollment_id')
            ->toArray();
            
        $outstandingEnrollmentAmount = PaymentSummary::getValue('enrollment_unpaid_sum');
        $outstandingEnrollmentCount = (int) PaymentSummary::getValue('enrollment_unpaid_count');
            
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
            
        $outstandingTraineesWithReceipts = CustomReceipt::where('status', 'generated')
            ->whereNotNull('trainee_model_id')
            ->pluck('trainee_model_id')
            ->toArray();
            
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

        return Program::withCount([
                'enrollments as enrollment_count',
                'enrollments as enrollment_paid' => function ($q) { $q->where('payment_status', 'paid'); },
                'enrollments as enrollment_unpaid' => function ($q) { $q->where('payment_status', 'unpaid'); },
            ])
            ->get()
            ->map(function ($program) {
                $enrollmentAmount = (float) TraineeEnrollment::where('program_id', $program->program_id)
                    ->where('payment_status', 'paid')
                    ->sum('enrollment_fee');

                $assessmentCount = Assessment::where('program_id', $program->program_id)
                    ->whereNotNull('assessment_fee')->count();
                $assessmentPaid = Assessment::where('program_id', $program->program_id)
                    ->whereNotNull('assessment_fee')->where('payment_status', 'paid')->count();
                $assessmentUnpaid = Assessment::where('program_id', $program->program_id)
                    ->whereNotNull('assessment_fee')->where('payment_status', 'unpaid')->count();
                $assessmentAmount = (float) Assessment::where('program_id', $program->program_id)
                    ->where('payment_status', 'paid')->sum('assessment_fee');

                $registrationBase = Trainee::where('program_qualification', $program->name)
                    ->where('status', 'pending')
                    ->whereDoesntHave('enrollments');
                $registrationCount = (clone $registrationBase)->count();
                $registrationPaid = (clone $registrationBase)->where('payment_status', 'paid')->count();
                $registrationUnpaid = (clone $registrationBase)->where('payment_status', 'unpaid')->count();

                $registrationAmount = $registrationPaid * ($program->enrollment_fee ?: 0);

                $customReceiptAmount = (float) CustomReceipt::where('status', 'generated')
                    ->whereHas('enrollment', function ($q) use ($program) {
                        $q->where('program_id', $program->program_id);
                    })
                    ->sum('total_amount');

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
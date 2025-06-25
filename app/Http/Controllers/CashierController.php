<?php

namespace App\Http\Controllers;

use App\Models\TraineeEnrollment;
use App\Models\Assessment;
use App\Models\Program;
use App\Models\Trainee;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\DB;

class CashierController extends Controller
{
    /**
     * Display the cashier dashboard
     */
    public function dashboard()
    {
        // Calculate statistics
        $stats = $this->calculateDashboardStats();
        
        // Get recent payment status (active enrollments)
        $paymentStatus = $this->getPaymentStatus();
        
        // Get payment summaries by month
        $paymentSummaries = $this->getPaymentSummaries();
        
        // Get recent activities
        $recentActivities = $this->getRecentActivities();

        return Inertia::render('Cashier/Dashboard', [
            'stats' => $stats,
            'paymentStatus' => $paymentStatus,
            'paymentSummaries' => $paymentSummaries,
            'recentActivities' => $recentActivities,
        ]);
    }

    /**
     * Display payments management page
     */
    public function payments(Request $request)
    {
        // Get enrollment payment records (existing enrollments)
        $enrollmentPayments = TraineeEnrollment::with(['trainee', 'program'])
            ->whereNotNull('enrollment_fee')
            ->where('enrollment_fee', '>', 0)
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($enrollment) {
                return [
                    'id' => 'ENR-' . str_pad($enrollment->id, 4, '0', STR_PAD_LEFT),
                    'type' => 'enrollment',
                    'trainee' => [
                        'name' => $enrollment->trainee->full_name,
                        'id' => 'T-' . str_pad($enrollment->trainee->id, 4, '0', STR_PAD_LEFT),
                    ],
                    'course' => $enrollment->program->name,
                    'amount' => $enrollment->enrollment_fee,
                    'receiptNo' => $enrollment->payment_reference ?: 'RN-ENR-' . str_pad($enrollment->id, 4, '0', STR_PAD_LEFT),
                    'date' => $enrollment->enrollment_date->format('Y-m-d'),
                    'status' => $enrollment->payment_status === 'paid' ? 'paid' : 'unpaid',
                    'enrollment_id' => $enrollment->id,
                    'assessment_id' => null,
                    'trainee_id' => null,
                ];
            });

        // Get trainee registration payments (newly registered trainees with unpaid status who aren't enrolled yet)
        $registrationPayments = Trainee::query()
            ->where('payment_status', 'unpaid')
            ->where('status', 'pending')
            ->whereDoesntHave('enrollments') // Only trainees who haven't been enrolled yet
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($trainee) {
                // Get the program they want to enroll in
                $program = Program::where('name', $trainee->program_qualification)->first();
                $enrollmentFee = $program ? $program->enrollment_fee : 0;
                
                // Skip if no enrollment fee required
                if ($enrollmentFee <= 0) {
                    return null;
                }
                
                return [
                    'id' => 'REG-' . str_pad($trainee->id, 4, '0', STR_PAD_LEFT),
                    'type' => 'registration',
                    'trainee' => [
                        'name' => $trainee->full_name,
                        'id' => 'T-' . str_pad($trainee->id, 4, '0', STR_PAD_LEFT),
                    ],
                    'course' => $trainee->program_qualification . ' (Enrollment Fee)',
                    'amount' => $enrollmentFee,
                    'receiptNo' => 'RN-REG-' . str_pad($trainee->id, 4, '0', STR_PAD_LEFT),
                    'date' => $trainee->created_at->format('Y-m-d'),
                    'status' => 'unpaid',
                    'enrollment_id' => null,
                    'assessment_id' => null,
                    'trainee_id' => $trainee->id,
                ];
            })
            ->filter() // Remove null entries (trainees with no enrollment fee)
            ->values();

        // Combine enrollment and registration payments
        $allEnrollmentPayments = $enrollmentPayments->concat($registrationPayments)->sortByDesc('date')->values();

        // Get assessment payment records
        $assessmentPayments = Assessment::with(['trainee', 'program'])
            ->whereNotNull('assessment_fee')
            ->where('assessment_fee', '>', 0)
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($assessment) {
                $applicantName = $assessment->applicant_type === 'external_applicant' 
                    ? $assessment->external_applicant_name 
                    : ($assessment->trainee ? $assessment->trainee->full_name : 'N/A');
                    
                $applicantId = $assessment->applicant_type === 'external_applicant' 
                    ? 'EXT-' . str_pad($assessment->id, 4, '0', STR_PAD_LEFT)
                    : ($assessment->trainee ? 'T-' . str_pad($assessment->trainee->id, 4, '0', STR_PAD_LEFT) : 'N/A');

                return [
                    'id' => 'ASS-' . str_pad($assessment->id, 4, '0', STR_PAD_LEFT),
                    'type' => 'assessment',
                    'trainee' => [
                        'name' => $applicantName,
                        'id' => $applicantId,
                    ],
                    'course' => $assessment->program ? $assessment->program->name : $assessment->title,
                    'amount' => $assessment->assessment_fee,
                    'receiptNo' => $assessment->payment_reference ?: 'RN-ASS-' . str_pad($assessment->id, 4, '0', STR_PAD_LEFT),
                    'date' => $assessment->assessment_date ? $assessment->assessment_date->format('Y-m-d') : $assessment->created_at->format('Y-m-d'),
                    'status' => $assessment->payment_status === 'paid' ? 'paid' : 'unpaid',
                    'enrollment_id' => null,
                    'assessment_id' => $assessment->id,
                    'trainee_id' => null,
                ];
            });

        // Calculate summary statistics
        $summaryStats = $this->calculatePaymentSummaryStats();
        
        // Get collections by course
        $collectionsByCourse = $this->getCollectionsByCourse();

        return Inertia::render('Cashier/Payments', [
            'enrollmentPayments' => $allEnrollmentPayments,
            'assessmentPayments' => $assessmentPayments,
            'summaryStats' => $summaryStats,
            'collectionsByCourse' => $collectionsByCourse,
        ]);
    }

    /**
     * Display receipts page
     */
    public function receipts()
    {
        // Get enrollment receipts (paid enrollments)
        $enrollmentReceipts = TraineeEnrollment::with(['trainee', 'program'])
            ->where('payment_status', 'paid')
            ->whereNotNull('payment_reference')
            ->orderBy('payment_date', 'desc')
            ->get()
            ->map(function ($enrollment) {
                return [
                    'id' => $enrollment->payment_reference ?: 'RN-ENR-' . str_pad($enrollment->id, 4, '0', STR_PAD_LEFT),
                    'paymentId' => 'ENR-' . str_pad($enrollment->id, 4, '0', STR_PAD_LEFT),
                    'type' => 'enrollment',
                    'trainee' => [
                        'name' => $enrollment->trainee->full_name,
                        'id' => 'T-' . str_pad($enrollment->trainee->id, 4, '0', STR_PAD_LEFT),
                    ],
                    'course' => $enrollment->program->name,
                    'amount' => $enrollment->enrollment_fee,
                    'dateGenerated' => $enrollment->payment_date->format('Y-m-d'),
                    'timeGenerated' => $enrollment->payment_date->format('g:i A'),
                    'status' => 'generated',
                ];
            });

        // Get assessment receipts (paid assessments)
        $assessmentReceipts = Assessment::with(['trainee', 'program'])
            ->where('payment_status', 'paid')
            ->whereNotNull('payment_reference')
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
                    ],
                    'course' => $assessment->program ? $assessment->program->name : $assessment->title,
                    'amount' => $assessment->assessment_fee,
                    'dateGenerated' => $assessment->payment_date->format('Y-m-d'),
                    'timeGenerated' => $assessment->payment_date->format('g:i A'),
                    'status' => 'generated',
                ];
            });

        return Inertia::render('Cashier/Receipts', [
            'enrollmentReceipts' => $enrollmentReceipts,
            'assessmentReceipts' => $assessmentReceipts,
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
            
            // Update trainee payment status and activate them
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

        return redirect()->back()->with('success', 'Payment processed successfully!');
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

        // Combined current month totals
        $currentTotal = $currentEnrollmentTotal + $currentAssessmentTotal;
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

        // Combined last month totals
        $lastTotal = $lastEnrollmentTotal + $lastAssessmentTotal;
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
    private function getPaymentStatus()
    {
        // Get enrollment payments
        $enrollmentPayments = TraineeEnrollment::with(['trainee', 'program'])
            ->whereNotNull('enrollment_fee')
            ->where('enrollment_fee', '>', 0)
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get()
            ->map(function ($enrollment) {
                $initials = collect(explode(' ', $enrollment->trainee->full_name))
                    ->map(fn($name) => substr($name, 0, 1))
                    ->take(2)
                    ->join('');
                    
                return [
                    'id' => 'ENR-' . str_pad($enrollment->id, 4, '0', STR_PAD_LEFT),
                    'initials' => $initials,
                    'name' => $enrollment->trainee->full_name,
                    'course' => $enrollment->program->name . ' (Enrollment)',
                    'amountDue' => $enrollment->enrollment_fee,
                    'status' => $enrollment->payment_status === 'paid' ? 'paid' : 'pending',
                    'created_at' => $enrollment->created_at,
                ];
            });

        // Get registration payments (newly registered trainees)
        $registrationPayments = Trainee::where('payment_status', 'unpaid')
            ->where('status', 'pending')
            ->whereDoesntHave('enrollments')
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get()
            ->map(function ($trainee) {
                // Get the program they want to enroll in
                $program = Program::where('name', $trainee->program_qualification)->first();
                $enrollmentFee = $program ? $program->enrollment_fee : 0;
                
                // Skip if no enrollment fee required
                if ($enrollmentFee <= 0) {
                    return null;
                }

                $initials = collect(explode(' ', $trainee->full_name))
                    ->map(fn($name) => substr($name, 0, 1))
                    ->take(2)
                    ->join('');
                    
                return [
                    'id' => 'REG-' . str_pad($trainee->id, 4, '0', STR_PAD_LEFT),
                    'initials' => $initials,
                    'name' => $trainee->full_name,
                    'course' => $trainee->program_qualification . ' (Enrollment Fee)',
                    'amountDue' => $enrollmentFee,
                    'status' => 'pending',
                    'created_at' => $trainee->created_at,
                ];
            })
            ->filter()
            ->values();

        // Get assessment payments
        $assessmentPayments = Assessment::with(['trainee', 'program'])
            ->whereNotNull('assessment_fee')
            ->where('assessment_fee', '>', 0)
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get()
            ->map(function ($assessment) {
                $applicantName = $assessment->applicant_type === 'external_applicant' 
                    ? $assessment->external_applicant_name 
                    : ($assessment->trainee ? $assessment->trainee->full_name : 'N/A');

                $initials = collect(explode(' ', $applicantName))
                    ->map(fn($name) => substr($name, 0, 1))
                    ->take(2)
                    ->join('');
                    
                return [
                    'id' => 'ASS-' . str_pad($assessment->id, 4, '0', STR_PAD_LEFT),
                    'initials' => $initials,
                    'name' => $applicantName,
                    'course' => ($assessment->program ? $assessment->program->name : $assessment->title) . ' (Assessment)',
                    'amountDue' => $assessment->assessment_fee,
                    'status' => $assessment->payment_status === 'paid' ? 'paid' : 'pending',
                    'created_at' => $assessment->created_at,
                ];
            });

        // Combine and sort all payments
        return $enrollmentPayments->concat($registrationPayments)->concat($assessmentPayments)
            ->sortByDesc('created_at')
            ->take(10)
            ->values();
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
                
                return [
                    'id' => $assessment->id,
                    'description' => "Assessment payment received for {$applicantName} - {$programName} (₱" . number_format($assessment->assessment_fee, 0) . ")",
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
        // Enrollment totals
        $totalEnrollmentCollections = TraineeEnrollment::where('payment_status', 'paid')->sum('enrollment_fee');
        $totalEnrollmentCollectionsCount = TraineeEnrollment::where('payment_status', 'paid')->count();
        
        // Assessment totals
        $totalAssessmentCollections = Assessment::where('payment_status', 'paid')->sum('assessment_fee');
        $totalAssessmentCollectionsCount = Assessment::where('payment_status', 'paid')->count();
        
        // Combined totals
        $totalCollections = $totalEnrollmentCollections + $totalAssessmentCollections;
        $totalCollectionsCount = $totalEnrollmentCollectionsCount + $totalAssessmentCollectionsCount;
        
        $thisMonth = Carbon::now()->startOfMonth();
        
        // This month enrollment
        $thisMonthEnrollmentAmount = TraineeEnrollment::where('payment_status', 'paid')
            ->where('payment_date', '>=', $thisMonth)
            ->sum('enrollment_fee');
        $thisMonthEnrollmentCount = TraineeEnrollment::where('payment_status', 'paid')
            ->where('payment_date', '>=', $thisMonth)
            ->count();
            
        // This month assessment
        $thisMonthAssessmentAmount = Assessment::where('payment_status', 'paid')
            ->where('payment_date', '>=', $thisMonth)
            ->sum('assessment_fee');
        $thisMonthAssessmentCount = Assessment::where('payment_status', 'paid')
            ->where('payment_date', '>=', $thisMonth)
            ->count();
            
        // Combined this month
        $thisMonthAmount = $thisMonthEnrollmentAmount + $thisMonthAssessmentAmount;
        $thisMonthCount = $thisMonthEnrollmentCount + $thisMonthAssessmentCount;
            
        // Outstanding enrollment
        $outstandingEnrollmentAmount = TraineeEnrollment::where('payment_status', '!=', 'paid')
            ->whereNotNull('enrollment_fee')
            ->where('enrollment_fee', '>', 0)
            ->sum('enrollment_fee');
        $outstandingEnrollmentCount = TraineeEnrollment::where('payment_status', '!=', 'paid')
            ->whereNotNull('enrollment_fee')
            ->where('enrollment_fee', '>', 0)
            ->count();
            
        // Outstanding assessment
        $outstandingAssessmentAmount = Assessment::where('payment_status', '!=', 'paid')
            ->whereNotNull('assessment_fee')
            ->where('assessment_fee', '>', 0)
            ->sum('assessment_fee');
        $outstandingAssessmentCount = Assessment::where('payment_status', '!=', 'paid')
            ->whereNotNull('assessment_fee')
            ->where('assessment_fee', '>', 0)
            ->count();
            
        // Combined outstanding
        $outstandingAmount = $outstandingEnrollmentAmount + $outstandingAssessmentAmount;
        $outstandingCount = $outstandingEnrollmentCount + $outstandingAssessmentCount;

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
     * Get collections by course
     */
    private function getCollectionsByCourse()
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

                // Get assessment data for this program
                $assessments = Assessment::where('program_id', $program->program_id)
                    ->whereNotNull('assessment_fee')
                    ->where('assessment_fee', '>', 0)
                    ->get();
                    
                $assessmentCount = $assessments->count();
                $assessmentPaid = $assessments->where('payment_status', 'paid')->count();
                $assessmentUnpaid = $assessments->where('payment_status', 'unpaid')->count();
                $assessmentAmount = $assessments->where('payment_status', 'paid')->sum('assessment_fee');

                // Combined totals
                $totalPayments = $enrollmentCount + $assessmentCount;
                $totalPaid = $enrollmentPaid + $assessmentPaid;
                $totalUnpaid = $enrollmentUnpaid + $assessmentUnpaid;
                $totalCollectionAmount = $enrollmentAmount + $assessmentAmount;
                
                // Skip programs with no payments
                if ($totalPayments === 0) {
                    return null;
                }

                return [
                    'course' => $program->name,
                    'totalTrainees' => $totalPayments, // Changed to total payments (enrollments + assessments)
                    'fullyPaid' => $totalPaid,
                    'partiallyPaid' => 0, // We don't have partial payments yet
                    'unpaid' => $totalUnpaid,
                    'collectionAmount' => $totalCollectionAmount,
                ];
            })
            ->filter() // Remove null entries
            ->sortByDesc('collectionAmount')
            ->values();
    }
} 
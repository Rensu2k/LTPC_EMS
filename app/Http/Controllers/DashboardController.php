<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Trainee;
use App\Models\Trainer;
use App\Models\Program;
use App\Models\TraineeEnrollment;
use App\Models\Assessment;
use App\Models\CustomReceipt;
use Inertia\Inertia;
use Carbon\Carbon;

class DashboardController extends Controller
{
    public function admin()
    {
        // Get dashboard statistics using the enrollment system
        $totalEnrollments = TraineeEnrollment::count();
        $activePrograms = Program::where('status', 'active')->count();
        $completedTrainings = TraineeEnrollment::where('status', 'completed')->count();
        $activeEnrollments = TraineeEnrollment::where('status', 'active')->count();
        $totalTrainers = Trainer::where('status', 'active')->count();
        
        // Assessment statistics
        $totalAssessments = Assessment::count();
        $pendingAssessments = Assessment::where('status', 'pending')->count();
        $completedAssessments = Assessment::whereIn('status', ['completed', 'graded', 'pass', 'fail'])->count();
        $passedAssessments = Assessment::where('status', 'pass')->count();

        // Payment statistics
        $regularTrainees = Trainee::whereNull('scholarship_package')->orWhere('scholarship_package', '')->count();
        $scholarTrainees = Trainee::whereNotNull('scholarship_package')->where('scholarship_package', '!=', '')->count();
        
        // Get payment data from both enrollment system and custom receipts
        $paidTrainingPayments = TraineeEnrollment::where('payment_status', 'paid')->count();
        $pendingTrainingPayments = TraineeEnrollment::where('payment_status', 'unpaid')->count();
        
        $paidAssessmentPayments = Assessment::where('payment_status', 'paid')->count();
        $pendingAssessmentPayments = Assessment::where('payment_status', 'unpaid')->count();
        
        $totalPendingPayments = $pendingTrainingPayments + $pendingAssessmentPayments;
        
        // Employment statistics (placeholder - you may need to create an employment model)
        $employmentEndorsements = 0; // Placeholder
        $employmentRate = 0; // Placeholder

        // Get previous month data for comparison
        $lastMonth = Carbon::now()->subMonth();
        $lastMonthEnrollments = TraineeEnrollment::where('created_at', '<=', $lastMonth)->count();
        $lastMonthCompleted = TraineeEnrollment::where('status', 'completed')
            ->where('updated_at', '<=', $lastMonth)->count();
        $lastMonthPrograms = Program::where('status', 'active')
            ->where('created_at', '<=', $lastMonth)->count();
        $lastMonthAssessments = Assessment::where('created_at', '<=', $lastMonth)->count();

        // Calculate percentage changes
        $enrollmentsChange = $lastMonthEnrollments > 0 
            ? round((($totalEnrollments - $lastMonthEnrollments) / $lastMonthEnrollments) * 100, 1) 
            : 0;
        $programsChange = $lastMonthPrograms > 0 
            ? round((($activePrograms - $lastMonthPrograms) / $lastMonthPrograms) * 100, 1) 
            : 0;
        $completedChange = $lastMonthCompleted > 0 
            ? round((($completedTrainings - $lastMonthCompleted) / $lastMonthCompleted) * 100, 1) 
            : 0;
        $assessmentsChange = $lastMonthAssessments > 0 
            ? round((($totalAssessments - $lastMonthAssessments) / $lastMonthAssessments) * 100, 1) 
            : 0;

        // Get recent activities (last 10 enrollments with trainee and program data)
        $recentEnrollments = TraineeEnrollment::with(['trainee', 'program'])
            ->latest('created_at')
            ->limit(10)
            ->get()
            ->map(function ($enrollment) {
                $trainee = $enrollment->trainee;
                $program = $enrollment->program;
                
                // Find assigned trainer for this program
                $assignedTrainer = null;
                if ($program && $program->assigned_trainers) {
                    $trainerIds = $program->assigned_trainers;
                    if (!empty($trainerIds)) {
                        $trainer = Trainer::find($trainerIds[0]); // Get first assigned trainer
                        $assignedTrainer = $trainer ? $trainer->full_name : null;
                    }
                }

                return [
                    'id' => $enrollment->id,
                    'type' => 'enrollment',
                    'message' => 'New enrollment registered',
                    'details' => $trainee ? trim($trainee->first_name . ' ' . $trainee->last_name) . ' enrolled in ' . ($program ? $program->name : 'Unknown Program') : 'Unknown trainee',
                    'officer' => 'Enrollment Officer',
                    'time' => $enrollment->created_at->diffForHumans(),
                    'timestamp' => $enrollment->created_at,
                ];
            });

        // Get recent assessments
        $recentAssessments = Assessment::with(['program', 'trainee', 'trainer'])
            ->latest('created_at')
            ->limit(5)
            ->get()
            ->map(function ($assessment) {
                return [
                    'id' => $assessment->id,
                    'type' => 'assessment',
                    'message' => 'Assessment scheduled',
                    'details' => $assessment->applicant_name . ' - ' . ($assessment->program ? $assessment->program->name : 'Unknown Program'),
                    'officer' => 'Assessment Officer',
                    'time' => $assessment->created_at->diffForHumans(),
                    'timestamp' => $assessment->created_at,
                ];
            });

        // Get recent payments
        $recentPayments = CustomReceipt::with(['trainee', 'program'])
            ->latest('date_generated')
            ->limit(5)
            ->get()
            ->map(function ($receipt) {
                // Combine date_generated and time_generated into a single timestamp
                $generatedDate = $receipt->date_generated
                    ? \Carbon\Carbon::parse($receipt->date_generated)->format('Y-m-d')
                    : ($receipt->created_at ? $receipt->created_at->format('Y-m-d') : now()->format('Y-m-d'));

                $generatedTime = '00:00:00';
                if (!empty($receipt->time_generated)) {
                    if ($receipt->time_generated instanceof \Carbon\Carbon) {
                        $generatedTime = $receipt->time_generated->format('H:i:s');
                    } elseif (is_string($receipt->time_generated)) {
                        // Accept HH:MM or HH:MM:SS
                        if (preg_match('/^\d{2}:\d{2}(?::\d{2})?$/', $receipt->time_generated)) {
                            $generatedTime = strlen($receipt->time_generated) === 5
                                ? $receipt->time_generated . ':00'
                                : $receipt->time_generated;
                        }
                    }
                }

                $generatedAt = \Carbon\Carbon::createFromFormat(
                    'Y-m-d H:i:s',
                    $generatedDate . ' ' . $generatedTime,
                    config('app.timezone')
                );

                return [
                    'id' => $receipt->id,
                    'type' => 'payment',
                    'message' => 'Payment received',
                    'details' => ($receipt->trainee ? trim($receipt->trainee->first_name . ' ' . $receipt->trainee->last_name) : 'Unknown') . ' - ₱' . number_format($receipt->total_amount, 2),
                    'officer' => 'Cashier',
                    'time' => $generatedAt->diffForHumans(),
                    'timestamp' => $generatedAt,
                ];
            });

        // Combine and sort all recent activities by actual timestamp
        $recentActivities = $recentEnrollments->concat($recentAssessments)->concat($recentPayments)
            ->sortByDesc('timestamp')
            ->take(10)
            ->values();

        // Get program progress data
        $programProgress = Program::where('status', 'active')
            ->withCount(['enrollments as enrolled_count' => function($query) {
                $query->where('status', 'active');
            }])
            ->get()
            ->map(function ($program) {
                $enrolledCount = $program->enrolled_count ?? 0;
                $maxCapacity = 25; // Maximum trainees per batch
                $progress = min(($enrolledCount / $maxCapacity) * 100, 100);
                
                return [
                    'name' => $program->name,
                    'enrolled' => $enrolledCount,
                    'progress' => round($progress, 1),
                ];
            })
            ->take(5); // Show top 5 programs

        // Prepare statistics data
        $stats = [
            'total_enrollments' => $totalEnrollments,
            'active_programs' => $activePrograms,
            'regular_trainees' => $regularTrainees,
            'scholar_trainees' => $scholarTrainees,
            'pending_payments' => $totalPendingPayments,
            'employment_endorsements' => $employmentEndorsements,
            'employment_rate' => $employmentRate,
        ];

        // Prepare payment summary
        $paymentSummary = [
            'regular_trainees' => $regularTrainees,
            'scholar_trainees' => $scholarTrainees,
            'paid_training' => $paidTrainingPayments,
            'paid_assessment' => $paidAssessmentPayments,
            'pending_training' => $pendingTrainingPayments,
            'pending_assessment' => $pendingAssessmentPayments,
        ];

        return Inertia::render('Admin/Dashboard', [
            'stats' => $stats,
            'recent_activities' => $recentActivities,
            'program_progress' => $programProgress,
            'payment_summary' => $paymentSummary,
        ]);
    }

    public function officer()
    {
        // Get dashboard statistics using the enrollment system
        $totalEnrollments = TraineeEnrollment::count();
        $activePrograms = Program::where('status', 'active')->count();
        $completedTrainings = TraineeEnrollment::where('status', 'completed')->count();
        $activeEnrollments = TraineeEnrollment::where('status', 'active')->count();
        $totalTrainers = Trainer::where('status', 'active')->count();
        
        // Assessment statistics
        $totalAssessments = Assessment::count();
        $pendingAssessments = Assessment::where('status', 'pending')->count();
        $completedAssessments = Assessment::whereIn('status', ['completed', 'graded', 'pass', 'fail'])->count();
        $passedAssessments = Assessment::where('status', 'pass')->count();

        // Get previous month data for comparison
        $lastMonth = Carbon::now()->subMonth();
        $lastMonthEnrollments = TraineeEnrollment::where('created_at', '<=', $lastMonth)->count();
        $lastMonthCompleted = TraineeEnrollment::where('status', 'completed')
            ->where('updated_at', '<=', $lastMonth)->count();
        $lastMonthPrograms = Program::where('status', 'active')
            ->where('created_at', '<=', $lastMonth)->count();
        $lastMonthAssessments = Assessment::where('created_at', '<=', $lastMonth)->count();

        // Calculate percentage changes
        $enrollmentsChange = $lastMonthEnrollments > 0 
            ? round((($totalEnrollments - $lastMonthEnrollments) / $lastMonthEnrollments) * 100, 1) 
            : 0;
        $programsChange = $lastMonthPrograms > 0 
            ? round((($activePrograms - $lastMonthPrograms) / $lastMonthPrograms) * 100, 1) 
            : 0;
        $completedChange = $lastMonthCompleted > 0 
            ? round((($completedTrainings - $lastMonthCompleted) / $lastMonthCompleted) * 100, 1) 
            : 0;
        $assessmentsChange = $lastMonthAssessments > 0 
            ? round((($totalAssessments - $lastMonthAssessments) / $lastMonthAssessments) * 100, 1) 
            : 0;

        // Get recent enrollments (last 10 enrollments with trainee and program data)
        $recentEnrollments = TraineeEnrollment::with(['trainee', 'program'])
            ->latest('created_at')
            ->limit(10)
            ->get()
            ->map(function ($enrollment) {
                $trainee = $enrollment->trainee;
                $program = $enrollment->program;
                
                // Find assigned trainer for this program
                $assignedTrainer = null;
                if ($program && $program->assigned_trainers) {
                    $trainerIds = $program->assigned_trainers;
                    if (!empty($trainerIds)) {
                        $trainer = Trainer::find($trainerIds[0]); // Get first assigned trainer
                        $assignedTrainer = $trainer ? $trainer->full_name : null;
                    }
                }

                return [
                    'id' => $trainee->uli_number ?: 'T-' . str_pad($trainee->id, 4, '0', STR_PAD_LEFT),
                    'name' => trim($trainee->first_name . ' ' . $trainee->last_name),
                    'program' => $program ? $program->name : 'Unknown Program',
                    'trainer' => $assignedTrainer ?: 'Not assigned',
                    'status' => ucfirst($enrollment->status ?: 'active'),
                    'payment' => ucfirst($enrollment->payment_status ?: 'unpaid'),
                    'enrollment_date' => $enrollment->enrollment_date 
                        ? Carbon::parse($enrollment->enrollment_date)->format('M d, Y') 
                        : $enrollment->created_at->format('M d, Y'),
                    'avatar' => strtoupper(substr($trainee->first_name, 0, 1) . substr($trainee->last_name, 0, 1)),
                ];
            });

        // Get recent assessments (last 5 assessments with related data)
        $recentAssessments = Assessment::with(['program', 'trainee', 'trainer'])
            ->latest('created_at')
            ->limit(5)
            ->get()
            ->map(function ($assessment) {
                return [
                    'id' => $assessment->id,
                    'title' => $assessment->title,
                    'applicant_name' => $assessment->applicant_name,
                    'applicant_type' => $assessment->applicant_type === 'enrolled_trainee' ? 'Enrolled Applicant' : 'External Applicant',
                    'program_name' => $assessment->program->name ?? 'N/A',
                    'trainer_name' => $assessment->trainer->full_name ?? 'N/A',
                    'status' => $assessment->status,
    
                    'max_score' => $assessment->max_score,
                    'percentage' => $assessment->percentage,
                    'assessment_date' => $assessment->assessment_date 
                        ? Carbon::parse($assessment->assessment_date)->format('M d, Y') 
                        : $assessment->created_at->format('M d, Y'),
                    'payment_status' => $assessment->payment_status,
                    'assessment_fee' => $assessment->assessment_fee,
                ];
            });

        // Prepare statistics data
        $statistics = [
            'total_enrollments' => [
                'value' => $totalEnrollments,
                'change' => $enrollmentsChange,
                'label' => 'Total Enrollments'
            ],
            'active_programs' => [
                'value' => $activePrograms,
                'change' => $programsChange,
                'label' => 'Active Programs'
            ],
            'completed_trainings' => [
                'value' => $completedTrainings,
                'change' => $completedChange,
                'label' => 'Completed Trainings'
            ],
            'total_assessments' => [
                'value' => $totalAssessments,
                'change' => $assessmentsChange,
                'label' => 'Total Assessments'
            ],
        ];

        return Inertia::render('Officer/Dashboard', [
            'statistics' => $statistics,
            'recent_enrollments' => $recentEnrollments,
            'recent_assessments' => $recentAssessments,
            'assessment_summary' => [
                'total' => $totalAssessments,
                'pending' => $pendingAssessments,
                'completed' => $completedAssessments,
                'passed' => $passedAssessments,
            ]
        ]);
    }
}

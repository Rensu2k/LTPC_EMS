<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Trainee;
use App\Models\Trainer;
use App\Models\Program;
use App\Models\TraineeEnrollment;
use App\Models\Assessment;
use Inertia\Inertia;
use Carbon\Carbon;

class DashboardController extends Controller
{
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
                    'score' => $assessment->score,
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

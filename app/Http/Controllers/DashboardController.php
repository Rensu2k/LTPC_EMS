<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Trainee;
use App\Models\Trainer;
use App\Models\Course;
use Inertia\Inertia;
use Carbon\Carbon;

class DashboardController extends Controller
{
    public function officer()
    {
        // Get dashboard statistics
        $totalTrainees = Trainee::count();
        $activeCourses = Course::where('status', 'active')->count();
        $completedTrainees = Trainee::where('status', 'completed')->count();
        $activeTrainees = Trainee::where('status', 'active')->count();
        $totalTrainers = Trainer::where('status', 'active')->count();

        // Get previous month data for comparison
        $lastMonth = Carbon::now()->subMonth();
        $lastMonthTrainees = Trainee::where('created_at', '<=', $lastMonth)->count();
        $lastMonthCompleted = Trainee::where('status', 'completed')
            ->where('updated_at', '<=', $lastMonth)->count();
        $lastMonthCourses = Course::where('status', 'active')
            ->where('created_at', '<=', $lastMonth)->count();

        // Calculate percentage changes
        $traineesChange = $lastMonthTrainees > 0 
            ? round((($totalTrainees - $lastMonthTrainees) / $lastMonthTrainees) * 100, 1) 
            : 0;
        $coursesChange = $lastMonthCourses > 0 
            ? round((($activeCourses - $lastMonthCourses) / $lastMonthCourses) * 100, 1) 
            : 0;
        $completedChange = $lastMonthCompleted > 0 
            ? round((($completedTrainees - $lastMonthCompleted) / $lastMonthCompleted) * 100, 1) 
            : 0;

        // Get recent enrollments (last 10 trainees)
        $recentEnrollments = Trainee::latest('created_at')
            ->limit(10)
            ->get()
            ->map(function ($trainee) {
                // Find assigned trainer for this trainee's course
                $course = Course::where('name', $trainee->course_qualification)->first();
                $assignedTrainer = null;
                
                if ($course && $course->assigned_trainers) {
                    $trainerIds = $course->assigned_trainers;
                    if (!empty($trainerIds)) {
                        $trainer = Trainer::find($trainerIds[0]); // Get first assigned trainer
                        $assignedTrainer = $trainer ? $trainer->full_name : null;
                    }
                }

                return [
                    'id' => $trainee->uli_number ?: 'T-' . str_pad($trainee->id, 4, '0', STR_PAD_LEFT),
                    'name' => trim($trainee->first_name . ' ' . $trainee->last_name),
                    'course' => $trainee->course_qualification ?: 'Not assigned',
                    'trainer' => $assignedTrainer ?: 'Not assigned',
                    'status' => ucfirst($trainee->status ?: 'active'),
                    'payment' => ucfirst($trainee->payment_status ?: 'unpaid'),
                    'enrollment_date' => $trainee->entry_date 
                        ? $trainee->entry_date->format('M d, Y') 
                        : $trainee->created_at->format('M d, Y'),
                    'avatar' => strtoupper(substr($trainee->first_name, 0, 1) . substr($trainee->last_name, 0, 1)),
                ];
            });

        // Prepare statistics data
        $statistics = [
            'total_enrollments' => [
                'value' => $totalTrainees,
                'change' => $traineesChange,
                'label' => 'Total Enrollments'
            ],
            'active_courses' => [
                'value' => $activeCourses,
                'change' => $coursesChange,
                'label' => 'Active Courses'
            ],
            'completed_trainings' => [
                'value' => $completedTrainees,
                'change' => $completedChange,
                'label' => 'Completed Trainings'
            ],
        ];

        return Inertia::render('Officer/Dashboard', [
            'statistics' => $statistics,
            'recent_enrollments' => $recentEnrollments,
        ]);
    }
}

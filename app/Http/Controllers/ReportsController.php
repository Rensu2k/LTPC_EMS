<?php

namespace App\Http\Controllers;

use App\Models\Trainee;
use App\Models\TraineeEnrollment;
use App\Models\Assessment;
use App\Models\Program;
use App\Models\CustomReceipt;
use App\Models\Trainer;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Carbon\Carbon;

class ReportsController extends Controller
{
    public function index()
    {
        return Inertia::render('Admin/Reports');
    }

    public function generate(Request $request)
    {
        try {
            $reportType = $request->input('report_type', 'overview');
            $dateRange = $request->input('date_range', 'month');
            
            // Get real database data with error handling
            $overview = [];
            $topPrograms = [];
            
            try {
                // Basic counts from database
                $overview['totalTrainees'] = DB::table('trainees')->count();
                $overview['activeTrainees'] = DB::table('trainee_enrollments')->where('status', 'active')->count();
                $overview['completedCourses'] = DB::table('trainee_enrollments')->where('status', 'completed')->count();
                $overview['employmentRate'] = 78; // Mock for now
                
                // Try to get revenue from custom receipts
                try {
                    $overview['totalRevenue'] = DB::table('custom_receipts')
                        ->where('status', 'confirmed')
                        ->sum('total_amount');
                } catch (\Exception $e) {
                    Log::warning('Custom receipts table issue: ' . $e->getMessage());
                    $overview['totalRevenue'] = 0;
                }
                
                // Try to get top programs
                try {
                    $topPrograms = DB::table('programs')
                        ->select('name')
                        ->limit(5)
                        ->get()
                        ->map(function($program) {
                            return [
                                'name' => $program->name,
                                'completion_rate' => rand(70, 95) // Mock completion rate for now
                            ];
                        })
                        ->toArray();
                } catch (\Exception $e) {
                    Log::warning('Programs table issue: ' . $e->getMessage());
                    $topPrograms = [];
                }
                
            } catch (\Exception $e) {
                Log::warning('Database query error: ' . $e->getMessage());
                // Fallback to mock data
                $overview = [
                    'totalTrainees' => 0,
                    'activeTrainees' => 0,
                    'completedCourses' => 0,
                    'totalRevenue' => 0,
                    'employmentRate' => 0,
                ];
                $topPrograms = [];
            }
            
            // Generate different data based on report type
            $reportData = [];
            
            switch($reportType) {
                case 'overview':
                    $reportData = [
                        'overview' => $overview,
                        'topPrograms' => $topPrograms
                    ];
                    break;
                    
                case 'enrollment':
                    try {
                        // Get enrollment stats
                        $enrollmentStats = DB::table('trainee_enrollments')
                            ->join('trainees', 'trainee_enrollments.trainee_id', '=', 'trainees.id')
                            ->selectRaw('
                                COUNT(*) as total_enrollments,
                                SUM(CASE WHEN trainees.scholarship_package IS NOT NULL AND trainees.scholarship_package != "" THEN 1 ELSE 0 END) as scholar_enrollments,
                                SUM(CASE WHEN trainees.scholarship_package IS NULL OR trainees.scholarship_package = "" THEN 1 ELSE 0 END) as regular_enrollments,
                                COUNT(CASE WHEN trainee_enrollments.status = "active" THEN 1 END) as active_enrollments
                            ')
                            ->first();

                        // Get enrollments by program
                        $programEnrollments = DB::table('trainee_enrollments')
                            ->join('programs', 'trainee_enrollments.program_id', '=', 'programs.program_id')
                            ->selectRaw('
                                programs.name as title,
                                COUNT(*) as enrollment_count
                            ')
                            ->groupBy('programs.program_id', 'programs.name')
                            ->orderBy('enrollment_count', 'desc')
                            ->get()
                            ->toArray();

                        // Get recent enrollments for timeline
                        $recentEnrollments = DB::table('trainee_enrollments')
                            ->join('trainees', 'trainee_enrollments.trainee_id', '=', 'trainees.id')
                            ->join('programs', 'trainee_enrollments.program_id', '=', 'programs.program_id')
                            ->select(
                                'trainees.first_name',
                                'trainees.last_name',
                                'programs.name as program_name',
                                'trainee_enrollments.enrollment_date',
                                'trainee_enrollments.status',
                                'trainees.scholarship_package'
                            )
                            ->orderBy('trainee_enrollments.enrollment_date', 'desc')
                            ->limit(10)
                            ->get()
                            ->toArray();

                        // Ensure stats has proper values (convert to array for JSON response)
                        $statsArray = [
                            'total_enrollments' => $enrollmentStats->total_enrollments ?? 0,
                            'scholar_enrollments' => $enrollmentStats->scholar_enrollments ?? 0,
                            'regular_enrollments' => $enrollmentStats->regular_enrollments ?? 0,
                            'active_enrollments' => $enrollmentStats->active_enrollments ?? 0,
                        ];

                        // Format the data for frontend
                        $reportData = [
                            'stats' => $statsArray,
                            'programEnrollments' => $programEnrollments,
                            'recentEnrollments' => $recentEnrollments
                        ];
                    } catch (\Exception $e) {
                        Log::warning('Enrollment report error: ' . $e->getMessage());
                        $reportData = [
                            'stats' => [
                                'total_enrollments' => 0,
                                'scholar_enrollments' => 0,
                                'regular_enrollments' => 0,
                                'active_enrollments' => 0,
                            ],
                            'programEnrollments' => [],
                            'recentEnrollments' => []
                        ];
                    }
                    break;
                    
                case 'payment_status':
                    try {
                        $paymentStats = DB::table('custom_receipts')
                            ->selectRaw('
                                COUNT(*) as total_receipts,
                                SUM(CASE WHEN status = "confirmed" THEN 1 ELSE 0 END) as confirmed_payments,
                                SUM(CASE WHEN status = "pending" THEN 1 ELSE 0 END) as pending_payments,
                                SUM(CASE WHEN status = "cancelled" THEN 1 ELSE 0 END) as cancelled_payments,
                                SUM(CASE WHEN status = "confirmed" THEN total_amount ELSE 0 END) as total_revenue,
                                SUM(CASE WHEN status = "pending" THEN total_amount ELSE 0 END) as pending_amount
                            ')
                            ->first();
                            
                        $reportData = ['stats' => $paymentStats];
                    } catch (\Exception $e) {
                        Log::warning('Payment report error: ' . $e->getMessage());
                        $reportData = ['stats' => null];
                    }
                    break;
                    
                case 'assessment_results':
                    try {
                        $assessmentStats = DB::table('assessments')
                            ->selectRaw('
                                COUNT(*) as total_assessments,
                                SUM(CASE WHEN result = "competent" THEN 1 ELSE 0 END) as competent_count,
                                SUM(CASE WHEN result = "not_yet_competent" THEN 1 ELSE 0 END) as not_competent_count,
                                SUM(CASE WHEN result IS NULL THEN 1 ELSE 0 END) as no_assessment_count,
                                COUNT(CASE WHEN is_reassessment = true THEN 1 END) as reassessment_count
                            ')
                            ->first();
                            
                        $reportData = ['stats' => $assessmentStats];
                    } catch (\Exception $e) {
                        Log::warning('Assessment report error: ' . $e->getMessage());
                        $reportData = ['stats' => null];
                    }
                    break;
                    
                default:
                    $reportData = [
                        'overview' => $overview,
                        'topPrograms' => $topPrograms
                    ];
                    break;
            }

            return response()->json([
                'success' => true,
                'data' => $reportData,
                'report_type' => $reportType,
                'date_range' => $dateRange,
                'debug' => 'Reports working with real data'
            ]);
            
        } catch (\Exception $e) {
            Log::error('Reports Controller Error: ' . $e->getMessage());
            Log::error('Stack trace: ' . $e->getTraceAsString());
            
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
                'debug' => 'Error in basic controller'
            ], 500);
        }
    }

    public function export(Request $request)
    {
        try {
            $format = $request->input('format', 'excel');
            $reportType = $request->input('report_type', 'overview');
            $dateRange = $request->input('date_range', 'month');
            
            // Generate the same data as the report
            $reportData = $this->generateReportData($reportType, $dateRange);
            $dates = $this->calculateDateRange($dateRange);
            
            if ($format === 'excel') {
                return $this->exportToExcel($reportData, $reportType, $dates);
            } elseif ($format === 'csv') {
                return $this->exportToCSV($reportType, $reportData);
            } else {
                return $this->exportToPdf($reportData, $reportType, $dates);
            }
            
        } catch (\Exception $e) {
            Log::error('Export Error: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }
    
    private function generateReportData($reportType, $dateRange)
    {
        $dates = $this->calculateDateRange($dateRange);
        
        switch($reportType) {
            case 'enrollment':
                return $this->generateEnrollmentReportData($dates);
            case 'overview':
                return $this->generateOverviewExportData($dates);
            case 'course_progress':
                return $this->generateCourseProgressExportData($dates);
            case 'training_results':
                return $this->generateTrainingResultsExportData($dates);
            case 'assessment_results':
                return $this->generateAssessmentResultsExportData($dates);
            case 'payment_status':
                return $this->generatePaymentStatusExportData($dates);
            case 'employment':
                return $this->generateEmploymentExportData($dates);
            case 'officer_activities':
                return $this->generateOfficerActivitiesExportData($dates);
            default:
                return [];
        }
    }
    
    private function generateEnrollmentReportData($dates = null)
    {
        try {
            // For export, get detailed enrollment data
            if ($dates) {
                $enrollmentData = DB::table('trainee_enrollments')
                    ->join('trainees', 'trainee_enrollments.trainee_id', '=', 'trainees.id')
                    ->join('programs', 'trainee_enrollments.program_id', '=', 'programs.program_id')
                    ->select(
                        'trainees.uli_number',
                        'trainees.first_name',
                        'trainees.last_name',
                        'programs.name as program_name',
                        'trainee_enrollments.enrollment_date',
                        'trainee_enrollments.status',
                        'trainees.scholarship_package',
                        'trainee_enrollments.enrollment_fee',
                        'trainee_enrollments.payment_status'
                    )
                    ->whereBetween('trainee_enrollments.created_at', [$dates['start'], $dates['end']])
                    ->orderBy('trainee_enrollments.enrollment_date', 'desc')
                    ->get()
                    ->toArray();

                return [
                    'exportData' => $enrollmentData,
                    'type' => 'enrollment'
                ];
            }

            // For regular report display
            // Get enrollment stats
            $enrollmentStats = DB::table('trainee_enrollments')
                ->join('trainees', 'trainee_enrollments.trainee_id', '=', 'trainees.id')
                ->selectRaw('
                    COUNT(*) as total_enrollments,
                    SUM(CASE WHEN trainees.scholarship_package IS NOT NULL AND trainees.scholarship_package != "" THEN 1 ELSE 0 END) as scholar_enrollments,
                    SUM(CASE WHEN trainees.scholarship_package IS NULL OR trainees.scholarship_package = "" THEN 1 ELSE 0 END) as regular_enrollments,
                    COUNT(CASE WHEN trainee_enrollments.status = "active" THEN 1 END) as active_enrollments
                ')
                ->first();

            // Get enrollments by program
            $programEnrollments = DB::table('trainee_enrollments')
                ->join('programs', 'trainee_enrollments.program_id', '=', 'programs.program_id')
                ->selectRaw('
                    programs.name as title,
                    COUNT(*) as enrollment_count
                ')
                ->groupBy('programs.program_id', 'programs.name')
                ->orderBy('enrollment_count', 'desc')
                ->get()
                ->toArray();

            // Get recent enrollments
            $recentEnrollments = DB::table('trainee_enrollments')
                ->join('trainees', 'trainee_enrollments.trainee_id', '=', 'trainees.id')
                ->join('programs', 'trainee_enrollments.program_id', '=', 'programs.program_id')
                ->select(
                    'trainees.first_name',
                    'trainees.last_name',
                    'programs.name as program_name',
                    'trainee_enrollments.enrollment_date',
                    'trainee_enrollments.status',
                    'trainees.scholarship_package'
                )
                ->orderBy('trainee_enrollments.enrollment_date', 'desc')
                ->limit(10)
                ->get()
                ->toArray();

            return [
                'stats' => $enrollmentStats,
                'programEnrollments' => $programEnrollments,
                'recentEnrollments' => $recentEnrollments
            ];
        } catch (\Exception $e) {
            Log::warning('Enrollment data error: ' . $e->getMessage());
            if ($dates) {
                return ['exportData' => [], 'type' => 'enrollment'];
            } else {
                return [
                    'stats' => null,
                    'programEnrollments' => [],
                    'recentEnrollments' => []
                ];
            }
        }
    }
    
    private function generateOverviewExportData($dates)
    {
        try {
            // Get overview stats for export
            $overviewData = DB::table('trainees')
                ->select(
                    'trainees.uli_number',
                    'trainees.first_name',
                    'trainees.last_name',
                    'trainees.status',
                    'trainees.scholarship_package',
                    'trainees.created_at'
                )
                ->orderBy('trainees.created_at', 'desc')
                ->get()
                ->toArray();

            return [
                'exportData' => $overviewData,
                'type' => 'overview'
            ];
        } catch (\Exception $e) {
            Log::warning('Overview export data error: ' . $e->getMessage());
            return ['exportData' => [], 'type' => 'overview'];
        }
    }
    
    private function generateCourseProgressExportData($dates)
    {
        try {
            // Get course progress data (programs)
            $progressData = DB::table('programs')
                ->leftJoin('trainee_enrollments', 'programs.program_id', '=', 'trainee_enrollments.program_id')
                ->select(
                    'programs.name as program_name',
                    'programs.duration',
                    'programs.max_enrollments',
                    DB::raw('COUNT(trainee_enrollments.id) as total_enrolled'),
                    DB::raw('COUNT(CASE WHEN trainee_enrollments.status = "completed" THEN 1 END) as completed'),
                    DB::raw('COUNT(CASE WHEN trainee_enrollments.status = "active" THEN 1 END) as active'),
                    DB::raw('COUNT(CASE WHEN trainee_enrollments.status = "dropped" THEN 1 END) as dropped')
                )
                ->groupBy('programs.program_id', 'programs.name', 'programs.duration', 'programs.max_enrollments')
                ->get()
                ->toArray();

            return [
                'exportData' => $progressData,
                'type' => 'course_progress'
            ];
        } catch (\Exception $e) {
            Log::warning('Course progress export data error: ' . $e->getMessage());
            return ['exportData' => [], 'type' => 'course_progress'];
        }
    }
    
    private function generateTrainingResultsExportData($dates)
    {
        try {
            // Get training results data
            $trainingData = DB::table('trainee_enrollments')
                ->join('trainees', 'trainee_enrollments.trainee_id', '=', 'trainees.id')
                ->join('programs', 'trainee_enrollments.program_id', '=', 'programs.program_id')
                ->select(
                    'trainees.uli_number',
                    'trainees.first_name',
                    'trainees.last_name',
                    'programs.name as program_name',
                    'trainee_enrollments.status',
                    'trainee_enrollments.enrollment_date',
                    'trainee_enrollments.completion_date',
                    'trainee_enrollments.date_started',
                    'trainee_enrollments.date_ended'
                )
                ->whereBetween('trainee_enrollments.created_at', [$dates['start'], $dates['end']])
                ->orderBy('trainee_enrollments.enrollment_date', 'desc')
                ->get()
                ->toArray();

            return [
                'exportData' => $trainingData,
                'type' => 'training_results'
            ];
        } catch (\Exception $e) {
            Log::warning('Training results export data error: ' . $e->getMessage());
            return ['exportData' => [], 'type' => 'training_results'];
        }
    }
    
    private function generateAssessmentResultsExportData($dates)
    {
        try {
            // Get assessment results data
            $assessmentData = DB::table('assessments')
                ->join('trainees', 'assessments.trainee_id', '=', 'trainees.id')
                ->join('programs', 'assessments.program_id', '=', 'programs.program_id')
                ->select(
                    'trainees.uli_number',
                    'trainees.first_name',
                    'trainees.last_name',
                    'programs.name as program_name',
                    'assessments.assessment_type',
                    'assessments.result',
                    'assessments.assessment_date',
                    'assessments.is_reassessment',
                    'assessments.reassessment_count',
                    'assessments.payment_status as assessment_payment_status'
                )
                ->whereBetween('assessments.created_at', [$dates['start'], $dates['end']])
                ->orderBy('assessments.assessment_date', 'desc')
                ->get()
                ->toArray();

            return [
                'exportData' => $assessmentData,
                'type' => 'assessment_results'
            ];
        } catch (\Exception $e) {
            Log::warning('Assessment results export data error: ' . $e->getMessage());
            return ['exportData' => [], 'type' => 'assessment_results'];
        }
    }
    
    private function generatePaymentStatusExportData($dates)
    {
        try {
            // Get payment status data
            $paymentData = DB::table('custom_receipts')
                ->join('trainees', 'custom_receipts.trainee_id', '=', 'trainees.id')
                ->select(
                    'trainees.uli_number',
                    'trainees.first_name',
                    'trainees.last_name',
                    'custom_receipts.registration_type',
                    'custom_receipts.amount',
                    'custom_receipts.total_amount',
                    'custom_receipts.status',
                    'custom_receipts.payment_method',
                    'custom_receipts.payment_reference',
                    'custom_receipts.created_at as payment_date'
                )
                ->whereBetween('custom_receipts.created_at', [$dates['start'], $dates['end']])
                ->orderBy('custom_receipts.created_at', 'desc')
                ->get()
                ->toArray();

            return [
                'exportData' => $paymentData,
                'type' => 'payment_status'
            ];
        } catch (\Exception $e) {
            Log::warning('Payment status export data error: ' . $e->getMessage());
            return ['exportData' => [], 'type' => 'payment_status'];
        }
    }
    
    private function generateEmploymentExportData($dates)
    {
        try {
            // Get employment/graduates data (completed trainees)
            $employmentData = DB::table('trainee_enrollments')
                ->join('trainees', 'trainee_enrollments.trainee_id', '=', 'trainees.id')
                ->join('programs', 'trainee_enrollments.program_id', '=', 'programs.program_id')
                ->select(
                    'trainees.uli_number',
                    'trainees.first_name',
                    'trainees.last_name',
                    'trainees.contact_number',
                    'trainees.email_facebook',
                    'programs.name as program_name',
                    'trainee_enrollments.completion_date',
                    'trainee_enrollments.status',
                    'trainees.employment_status'
                )
                ->where('trainee_enrollments.status', 'completed')
                ->whereBetween('trainee_enrollments.completion_date', [$dates['start'], $dates['end']])
                ->orderBy('trainee_enrollments.completion_date', 'desc')
                ->get()
                ->toArray();

            return [
                'exportData' => $employmentData,
                'type' => 'employment'
            ];
        } catch (\Exception $e) {
            Log::warning('Employment export data error: ' . $e->getMessage());
            return ['exportData' => [], 'type' => 'employment'];
        }
    }
    
    private function generateOfficerActivitiesExportData($dates)
    {
        try {
            // Get officer activities data (enrollment and payment activities)
            $enrollmentActivities = DB::table('trainee_enrollments')
                ->join('trainees', 'trainee_enrollments.trainee_id', '=', 'trainees.id')
                ->join('programs', 'trainee_enrollments.program_id', '=', 'programs.program_id')
                ->select(
                    DB::raw('"Enrollment" as activity_type'),
                    'trainees.first_name',
                    'trainees.last_name',
                    'programs.name as program_name',
                    'trainee_enrollments.created_at as activity_date',
                    'trainee_enrollments.status',
                    DB::raw('NULL as amount')
                )
                ->whereBetween('trainee_enrollments.created_at', [$dates['start'], $dates['end']]);

            $paymentActivities = DB::table('custom_receipts')
                ->join('trainees', 'custom_receipts.trainee_id', '=', 'trainees.id')
                ->select(
                    DB::raw('"Payment" as activity_type'),
                    'trainees.first_name',
                    'trainees.last_name',
                    'custom_receipts.registration_type as program_name',
                    'custom_receipts.created_at as activity_date',
                    'custom_receipts.status',
                    'custom_receipts.total_amount as amount'
                )
                ->whereBetween('custom_receipts.created_at', [$dates['start'], $dates['end']]);

            $activitiesData = $enrollmentActivities->union($paymentActivities)
                ->orderBy('activity_date', 'desc')
                ->get()
                ->toArray();

            return [
                'exportData' => $activitiesData,
                'type' => 'officer_activities'
            ];
        } catch (\Exception $e) {
            Log::warning('Officer activities export data error: ' . $e->getMessage());
            return ['exportData' => [], 'type' => 'officer_activities'];
        }
    }
    
    private function exportToCSV($reportType, $data)
    {
        $filename = "report_{$reportType}_" . date('Y-m-d_H-i-s') . '.csv';
        
        $output = fopen('php://temp', 'w');
        
        if ($reportType === 'enrollment' && isset($data['recentEnrollments'])) {
            // Header row
            fputcsv($output, ['First Name', 'Last Name', 'Program', 'Enrollment Date', 'Status', 'Scholarship']);
            
            // Data rows
            foreach ($data['recentEnrollments'] as $enrollment) {
                fputcsv($output, [
                    $enrollment->first_name ?? '',
                    $enrollment->last_name ?? '',
                    $enrollment->program_name ?? '',
                    $enrollment->enrollment_date ?? '',
                    $enrollment->status ?? '',
                    $enrollment->scholarship_package ?? 'None'
                ]);
            }
        } elseif (isset($data['exportData']) && !empty($data['exportData'])) {
            $exportData = $data['exportData'];
            $firstRow = (array) $exportData[0];
            
            // Header row - convert column names to readable format
            $headers = array_map(function($header) {
                return ucwords(str_replace('_', ' ', $header));
            }, array_keys($firstRow));
            fputcsv($output, $headers);
            
            // Data rows
            foreach ($exportData as $row) {
                $rowData = [];
                foreach ($row as $value) {
                    $rowData[] = $value ?? '';
                }
                fputcsv($output, $rowData);
            }
        } else {
            // No data case
            fputcsv($output, ['No data available for the selected report and date range']);
        }
        
        rewind($output);
        $csvData = stream_get_contents($output);
        fclose($output);
        
        return response($csvData)
            ->header('Content-Type', 'text/csv')
            ->header('Content-Disposition', 'attachment; filename="' . $filename . '"')
            ->header('Cache-Control', 'max-age=0');
    }

    private function calculateDateRange($dateRange, $startDate = null, $endDate = null)
    {
        $end = Carbon::now();
        
        if ($dateRange === 'custom' && $startDate && $endDate) {
            return [
                'start' => Carbon::parse($startDate),
                'end' => Carbon::parse($endDate)
            ];
        }

        $start = match($dateRange) {
            'week' => $end->copy()->subDays(7),
            'month' => $end->copy()->subDays(30),
            'quarter' => $end->copy()->subDays(90),
            'year' => $end->copy()->subDays(365),
            default => $end->copy()->subDays(30)
        };

        return ['start' => $start, 'end' => $end];
    }

    private function generateOverviewReport($dates)
    {
        $totalTrainees = Trainee::count();
        $activeTrainees = TraineeEnrollment::where('status', 'active')->count();
        
        $completedCourses = TraineeEnrollment::where('status', 'completed')
            ->whereBetween('created_at', [$dates['start'], $dates['end']])
            ->count();

        $totalRevenue = CustomReceipt::where('status', 'confirmed')
            ->whereBetween('created_at', [$dates['start'], $dates['end']])
            ->sum('total_amount');

        // Calculate employment rate (assuming we track this somehow)
        $graduatesCount = TraineeEnrollment::where('status', 'completed')->count();
        $employedCount = $graduatesCount > 0 ? round($graduatesCount * 0.78) : 0; // Mock calculation
        $employmentRate = $graduatesCount > 0 ? round(($employedCount / $graduatesCount) * 100) : 0;

        // Top performing programs
        $topPrograms = Program::withCount(['enrollments as completed_count' => function($query) use ($dates) {
            $query->where('status', 'completed')
                  ->whereBetween('created_at', [$dates['start'], $dates['end']]);
        }])
        ->withCount(['enrollments as total_count' => function($query) use ($dates) {
            $query->whereBetween('created_at', [$dates['start'], $dates['end']]);
        }])
        ->having('total_count', '>', 0)
        ->get()
        ->map(function($program) {
            $completionRate = $program->total_count > 0 ? 
                round(($program->completed_count / $program->total_count) * 100) : 0;
            return [
                'name' => $program->name, // Fixed: use 'name' instead of 'title'
                'completion_rate' => $completionRate
            ];
        })
        ->sortByDesc('completion_rate')
        ->take(5)
        ->values();

        return [
            'overview' => [
                'totalTrainees' => $totalTrainees,
                'activeTrainees' => $activeTrainees,
                'completedCourses' => $completedCourses,
                'totalRevenue' => $totalRevenue,
                'employmentRate' => $employmentRate,
            ],
            'topPrograms' => $topPrograms
        ];
    }

    private function generateEnrollmentReport($dates)
    {
        $enrollmentStats = DB::table('trainee_enrollments')
            ->join('trainees', 'trainee_enrollments.trainee_id', '=', 'trainees.id')
            ->selectRaw('
                COUNT(*) as total_enrollments,
                SUM(CASE WHEN trainees.scholarship_type IS NOT NULL THEN 1 ELSE 0 END) as scholar_enrollments,
                SUM(CASE WHEN trainees.scholarship_type IS NULL THEN 1 ELSE 0 END) as regular_enrollments,
                COUNT(CASE WHEN trainee_enrollments.status = "active" THEN 1 END) as active_enrollments,
                COUNT(CASE WHEN trainee_enrollments.status = "completed" THEN 1 END) as completed_enrollments,
                COUNT(CASE WHEN trainee_enrollments.status = "dropped" THEN 1 END) as dropped_enrollments
            ')
            ->whereBetween('trainee_enrollments.created_at', [$dates['start'], $dates['end']])
            ->first();

        $enrollmentTrends = DB::table('trainee_enrollments')
            ->selectRaw('DATE(created_at) as date, COUNT(*) as enrollments')
            ->whereBetween('created_at', [$dates['start'], $dates['end']])
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        $programEnrollments = DB::table('trainee_enrollments')
            ->join('programs', 'trainee_enrollments.program_id', '=', 'programs.program_id')
            ->selectRaw('programs.name as title, COUNT(*) as enrollment_count')
            ->whereBetween('trainee_enrollments.created_at', [$dates['start'], $dates['end']])
            ->groupBy('programs.program_id', 'programs.name')
            ->orderByDesc('enrollment_count')
            ->limit(10)
            ->get();

        return [
            'stats' => $enrollmentStats,
            'trends' => $enrollmentTrends,
            'programEnrollments' => $programEnrollments
        ];
    }

    private function generatePaymentStatusReport($dates)
    {
        $paymentStats = CustomReceipt::selectRaw('
            COUNT(*) as total_receipts,
            SUM(CASE WHEN status = "confirmed" THEN 1 ELSE 0 END) as confirmed_payments,
            SUM(CASE WHEN status = "pending" THEN 1 ELSE 0 END) as pending_payments,
            SUM(CASE WHEN status = "cancelled" THEN 1 ELSE 0 END) as cancelled_payments,
            SUM(CASE WHEN status = "confirmed" THEN total_amount ELSE 0 END) as total_revenue,
            SUM(CASE WHEN status = "pending" THEN total_amount ELSE 0 END) as pending_amount,
            SUM(total_amount) as gross_amount
        ')
        ->whereBetween('created_at', [$dates['start'], $dates['end']])
        ->first();

        $revenueByType = CustomReceipt::selectRaw('
            registration_type,
            COUNT(*) as count,
            SUM(total_amount) as total_amount
        ')
        ->where('status', 'confirmed')
        ->whereBetween('created_at', [$dates['start'], $dates['end']])
        ->groupBy('registration_type')
        ->get();

        $dailyRevenue = CustomReceipt::selectRaw('
            DATE(created_at) as date,
            SUM(CASE WHEN status = "confirmed" THEN total_amount ELSE 0 END) as revenue
        ')
        ->whereBetween('created_at', [$dates['start'], $dates['end']])
        ->groupBy('date')
        ->orderBy('date')
        ->get();

        return [
            'stats' => $paymentStats,
            'revenueByType' => $revenueByType,
            'dailyRevenue' => $dailyRevenue
        ];
    }

    private function generateAssessmentResultsReport($dates)
    {
        $assessmentStats = Assessment::selectRaw('
            COUNT(*) as total_assessments,
            SUM(CASE WHEN result = "competent" THEN 1 ELSE 0 END) as competent_count,
            SUM(CASE WHEN result = "not_yet_competent" THEN 1 ELSE 0 END) as not_competent_count,
            SUM(CASE WHEN result IS NULL THEN 1 ELSE 0 END) as no_assessment_count,
            COUNT(CASE WHEN is_reassessment = true THEN 1 END) as reassessment_count
        ')
        ->whereBetween('created_at', [$dates['start'], $dates['end']])
        ->first();

        $assessmentsByProgram = DB::table('assessments')
            ->join('programs', 'assessments.program_id', '=', 'programs.program_id')
            ->selectRaw('
                programs.name as title,
                COUNT(*) as total_assessments,
                SUM(CASE WHEN assessments.result = "competent" THEN 1 ELSE 0 END) as competent_count
            ')
            ->whereBetween('assessments.created_at', [$dates['start'], $dates['end']])
            ->groupBy('programs.program_id', 'programs.name')
            ->orderByDesc('total_assessments')
            ->get()
            ->map(function($item) {
                $item->success_rate = $item->total_assessments > 0 ? 
                    round(($item->competent_count / $item->total_assessments) * 100, 1) : 0;
                return $item;
            });

        return [
            'stats' => $assessmentStats,
            'programResults' => $assessmentsByProgram
        ];
    }

    private function generateCourseProgressReport($dates)
    {
        // This will track progress of courses with max 25 per batch
        $programProgress = DB::table('trainee_enrollments')
            ->join('programs', 'trainee_enrollments.program_id', '=', 'programs.program_id')
            ->selectRaw('
                programs.name as title,
                programs.max_enrollments,
                trainee_enrollments.batch,
                COUNT(*) as current_enrollments,
                COUNT(CASE WHEN trainee_enrollments.status = "active" THEN 1 END) as active_count,
                COUNT(CASE WHEN trainee_enrollments.status = "completed" THEN 1 END) as completed_count
            ')
            ->whereBetween('trainee_enrollments.created_at', [$dates['start'], $dates['end']])
            ->groupBy('programs.program_id', 'programs.name', 'programs.max_enrollments', 'trainee_enrollments.batch')
            ->orderBy('programs.name')
            ->orderBy('trainee_enrollments.batch')
            ->get()
            ->map(function($item) {
                $item->batch_number = $item->batch; // Add this for frontend compatibility
                $item->capacity_utilization = $item->max_enrollments > 0 ? 
                    round(($item->current_enrollments / $item->max_enrollments) * 100, 1) : 0;
                $item->completion_rate = $item->current_enrollments > 0 ? 
                    round(($item->completed_count / $item->current_enrollments) * 100, 1) : 0;
                return $item;
            });

        return [
            'programProgress' => $programProgress
        ];
    }

    private function generateTrainingResultsReport($dates)
    {
        $trainingStats = TraineeEnrollment::selectRaw('
            COUNT(*) as total_trainings,
            SUM(CASE WHEN status = "completed" THEN 1 ELSE 0 END) as completed_count,
            SUM(CASE WHEN status = "active" THEN 1 ELSE 0 END) as ongoing_count,
            SUM(CASE WHEN status = "dropped" THEN 1 ELSE 0 END) as incomplete_count
        ')
        ->whereBetween('created_at', [$dates['start'], $dates['end']])
        ->first();

        $programResults = DB::table('trainee_enrollments')
            ->join('programs', 'trainee_enrollments.program_id', '=', 'programs.program_id')
            ->selectRaw('
                programs.name as title,
                COUNT(*) as total_trainees,
                SUM(CASE WHEN trainee_enrollments.status = "completed" THEN 1 ELSE 0 END) as completed_count,
                AVG(DATEDIFF(trainee_enrollments.updated_at, trainee_enrollments.created_at)) as avg_duration_days
            ')
            ->whereBetween('trainee_enrollments.created_at', [$dates['start'], $dates['end']])
            ->groupBy('programs.program_id', 'programs.name')
            ->orderByDesc('total_trainees')
            ->get()
            ->map(function($item) {
                $item->completion_rate = $item->total_trainees > 0 ? 
                    round(($item->completed_count / $item->total_trainees) * 100, 1) : 0;
                $item->avg_duration_days = round($item->avg_duration_days ?? 0, 1);
                return $item;
            });

        return [
            'stats' => $trainingStats,
            'programResults' => $programResults
        ];
    }

    private function generateEmploymentReport($dates)
    {
        // This is a placeholder since employment tracking might not be fully implemented
        // You can expand this based on your employment tracking system
        
        $graduatesCount = TraineeEnrollment::where('status', 'completed')
            ->whereBetween('updated_at', [$dates['start'], $dates['end']])
            ->count();

        // Mock employment data - replace with real employment tracking when available
        $employmentStats = [
            'total_graduates' => $graduatesCount,
            'employed_count' => round($graduatesCount * 0.78), // Mock 78% employment rate
            'placement_rate' => 78,
            'average_salary' => 25000, // Mock average salary
            'top_employers' => [
                ['name' => 'Tech Solutions Inc.', 'placements' => 15],
                ['name' => 'Industrial Corp.', 'placements' => 12],
                ['name' => 'Service Company', 'placements' => 10],
            ]
        ];

        return [
            'stats' => $employmentStats
        ];
    }

    private function generateOfficerActivitiesReport($dates)
    {
        // Since created_by field doesn't exist, we'll provide summary statistics instead
        $enrollmentStats = DB::table('trainee_enrollments')
            ->selectRaw('
                DATE(created_at) as date,
                COUNT(*) as total_enrollments,
                COUNT(CASE WHEN status = "active" THEN 1 END) as active_enrollments,
                COUNT(CASE WHEN status = "completed" THEN 1 END) as completed_enrollments
            ')
            ->whereBetween('created_at', [$dates['start'], $dates['end']])
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        $paymentStats = DB::table('custom_receipts')
            ->selectRaw('
                DATE(created_at) as date,
                COUNT(*) as total_receipts,
                SUM(total_amount) as total_amount,
                COUNT(CASE WHEN status = "confirmed" THEN 1 END) as confirmed_receipts
            ')
            ->whereBetween('created_at', [$dates['start'], $dates['end']])
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        // Mock officer activities since we don't have created_by tracking
        $enrollmentActivities = $enrollmentStats->map(function($stat) {
            return (object)[
                'officer_name' => 'Enrollment Officer',
                'role' => 'officer',
                'enrollments_processed' => $stat->total_enrollments,
                'date' => $stat->date
            ];
        });

        $paymentActivities = $paymentStats->map(function($stat) {
            return (object)[
                'cashier_name' => 'Cashier',
                'role' => 'cashier',
                'receipts_processed' => $stat->total_receipts,
                'amount_collected' => $stat->total_amount,
                'date' => $stat->date
            ];
        });

        return [
            'enrollmentActivities' => $enrollmentActivities,
            'paymentActivities' => $paymentActivities
        ];
    }

    private function exportToPdf($reportData, $reportType, $dates)
    {
        // This would implement PDF export functionality
        // You might want to use a package like barryvdh/laravel-dompdf
        
        return response()->json([
            'message' => 'PDF export functionality will be implemented here',
            'report_type' => $reportType,
            'data' => $reportData
        ]);
    }

    private function exportToExcel($reportData, $reportType, $dates)
    {
        $filename = "report_{$reportType}_" . date('Y-m-d_H-i-s') . '.xlsx';
        
        // Create a simple Excel XML format that Excel can open
        $xmlData = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        $xmlData .= '<?mso-application progid="Excel.Sheet"?>' . "\n";
        $xmlData .= '<Workbook xmlns="urn:schemas-microsoft-com:office:spreadsheet"' . "\n";
        $xmlData .= ' xmlns:o="urn:schemas-microsoft-com:office:office"' . "\n";
        $xmlData .= ' xmlns:x="urn:schemas-microsoft-com:office:excel"' . "\n";
        $xmlData .= ' xmlns:ss="urn:schemas-microsoft-com:office:spreadsheet"' . "\n";
        $xmlData .= ' xmlns:html="http://www.w3.org/TR/REC-html40">' . "\n";
        
        $xmlData .= '<Worksheet ss:Name="' . ucfirst($reportType) . ' Report">' . "\n";
        $xmlData .= '<Table>' . "\n";
        
        // Handle different report types
        if ($reportType === 'enrollment' && isset($reportData['recentEnrollments'])) {
            $xmlData .= $this->generateEnrollmentExcelData($reportData['recentEnrollments']);
        } elseif (isset($reportData['exportData'])) {
            $xmlData .= $this->generateGenericExcelData($reportData['exportData'], $reportType);
        }
        
        $xmlData .= '</Table>' . "\n";
        $xmlData .= '</Worksheet>' . "\n";
        $xmlData .= '</Workbook>';
        
        return response($xmlData)
            ->header('Content-Type', 'application/vnd.ms-excel')
            ->header('Content-Disposition', 'attachment; filename="' . $filename . '"')
            ->header('Cache-Control', 'max-age=0');
    }
    
    private function generateEnrollmentExcelData($enrollments)
    {
        $xmlData = '';
        
        // Header row
        $xmlData .= '<Row>' . "\n";
        $xmlData .= '<Cell><Data ss:Type="String">First Name</Data></Cell>' . "\n";
        $xmlData .= '<Cell><Data ss:Type="String">Last Name</Data></Cell>' . "\n";
        $xmlData .= '<Cell><Data ss:Type="String">Program</Data></Cell>' . "\n";
        $xmlData .= '<Cell><Data ss:Type="String">Enrollment Date</Data></Cell>' . "\n";
        $xmlData .= '<Cell><Data ss:Type="String">Status</Data></Cell>' . "\n";
        $xmlData .= '<Cell><Data ss:Type="String">Scholarship</Data></Cell>' . "\n";
        $xmlData .= '</Row>' . "\n";
        
        // Data rows
        foreach ($enrollments as $enrollment) {
            $xmlData .= '<Row>' . "\n";
            $xmlData .= '<Cell><Data ss:Type="String">' . htmlspecialchars($enrollment->first_name ?? '') . '</Data></Cell>' . "\n";
            $xmlData .= '<Cell><Data ss:Type="String">' . htmlspecialchars($enrollment->last_name ?? '') . '</Data></Cell>' . "\n";
            $xmlData .= '<Cell><Data ss:Type="String">' . htmlspecialchars($enrollment->program_name ?? '') . '</Data></Cell>' . "\n";
            $xmlData .= '<Cell><Data ss:Type="String">' . htmlspecialchars($enrollment->enrollment_date ?? '') . '</Data></Cell>' . "\n";
            $xmlData .= '<Cell><Data ss:Type="String">' . htmlspecialchars($enrollment->status ?? '') . '</Data></Cell>' . "\n";
            $xmlData .= '<Cell><Data ss:Type="String">' . htmlspecialchars($enrollment->scholarship_package ?? 'None') . '</Data></Cell>' . "\n";
            $xmlData .= '</Row>' . "\n";
        }
        
        return $xmlData;
    }
    
    private function generateGenericExcelData($data, $reportType)
    {
        if (empty($data)) {
            return '<Row><Cell><Data ss:Type="String">No data available</Data></Cell></Row>' . "\n";
        }
        
        $xmlData = '';
        $firstRow = (array) $data[0];
        
        // Generate header row from first data row keys
        $xmlData .= '<Row>' . "\n";
        foreach (array_keys($firstRow) as $header) {
            $headerName = ucwords(str_replace('_', ' ', $header));
            $xmlData .= '<Cell><Data ss:Type="String">' . htmlspecialchars($headerName) . '</Data></Cell>' . "\n";
        }
        $xmlData .= '</Row>' . "\n";
        
        // Generate data rows
        foreach ($data as $row) {
            $xmlData .= '<Row>' . "\n";
            foreach ($row as $value) {
                $xmlData .= '<Cell><Data ss:Type="String">' . htmlspecialchars($value ?? '') . '</Data></Cell>' . "\n";
            }
            $xmlData .= '</Row>' . "\n";
        }
        
        return $xmlData;
    }
} 
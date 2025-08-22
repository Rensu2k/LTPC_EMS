<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Assessment;
use App\Models\Program;
use App\Models\Trainer;
use Inertia\Inertia;

class AssessmentResultsController extends Controller
{
    /**
     * Display a listing of assessment results.
     */
    public function index(Request $request)
    {
        $perPage = $request->get('per_page', 10); // Default to 10 items per page
        $search = $request->get('search', '');
        $program = $request->get('program', '');
        $status = $request->get('status', '');
        $dateFrom = $request->get('date_from', '');
        $dateTo = $request->get('date_to', '');

        $query = Assessment::with(['program', 'trainee', 'trainer']);

        // Apply search filter if provided
        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('applicant_name', 'like', "%{$search}%")
                  ->orWhereHas('program', function ($programQuery) use ($search) {
                      $programQuery->where('name', 'like', "%{$search}%");
                  })
                  ->orWhereHas('trainer', function ($trainerQuery) use ($search) {
                      $trainerQuery->where('full_name', 'like', "%{$search}%");
                  });
            });
        }

        // Apply program filter if provided
        if ($program && $program !== 'All Programs') {
            $query->where('program_id', $program);
        }

        // Apply status filter if provided
        if ($status && $status !== 'All Statuses') {
            $query->where('status', $status);
        }

        // Apply date range filter if provided
        if ($dateFrom) {
            $query->where('assessment_date', '>=', $dateFrom);
        }
        if ($dateTo) {
            $query->where('assessment_date', '<=', $dateTo);
        }

        // Get only the latest assessment per trainee/assessment type/program combination
        // This prevents showing multiple attempts in the main table
        $assessments = $query->select('assessments.*')
            ->whereIn('assessments.id', function ($subQuery) {
                $subQuery->selectRaw('MAX(a.id)')
                    ->from('assessments as a')
                    ->whereRaw('a.trainee_id = assessments.trainee_id')
                    ->whereRaw('a.type = assessments.type')
                    ->whereRaw('a.program_id = assessments.program_id')
                    ->whereRaw('a.applicant_type = assessments.applicant_type')
                    ->groupBy('a.trainee_id', 'a.type', 'a.program_id', 'a.applicant_type');
            })
            ->orderBy('assessments.assessment_date', 'desc')
            ->orderBy('assessments.id', 'desc')
            ->paginate($perPage)
            ->through(function ($assessment) {
                return [
                    'id' => $assessment->id,
                    'title' => $assessment->title,
                    'description' => $assessment->description,
                    'type' => $assessment->type,
                    'status' => $assessment->status,
                    'result' => $assessment->result,
                    'result_status' => $assessment->result_status,
                    'result_color' => $assessment->result_color,
                    'can_be_reassessed' => $assessment->canBeReassessed(),
                    'is_reassessment' => $assessment->is_reassessment,
                    'attempt_number' => $assessment->attempt_number,
                    'original_assessment_id' => $assessment->original_assessment_id,
                    'program_id' => $assessment->program_id,
                    'program_name' => $assessment->program->name ?? 'N/A',
                    'applicant_name' => $assessment->applicant_name,
                    'applicant_type' => $assessment->applicant_type,
                    'trainee_id' => $assessment->trainee_id,
                    'trainee' => $assessment->trainee ? [
                        'id' => $assessment->trainee->id,
                        'first_name' => $assessment->trainee->first_name,
                        'last_name' => $assessment->trainee->last_name,
                        'scholarship_package' => $assessment->trainee->scholarship_package,
                    ] : null,
                    'trainer_name' => $assessment->trainer->full_name ?? 'N/A',
                    'assessment_date' => $assessment->assessment_date,
                    'assessment_fee' => $assessment->assessment_fee,
                    'payment_status' => $assessment->payment_status,
                    'payment_required' => $assessment->payment_required,
                    'payment_completed' => $assessment->payment_completed,
                    'external_applicant_name' => $assessment->external_applicant_name,
                    'external_applicant_email' => $assessment->external_applicant_email,
                    'external_applicant_phone' => $assessment->external_applicant_phone,
                    'payment_method' => $assessment->payment_method,
                    'payment_reference' => $assessment->payment_reference,
                    'payment_date' => $assessment->payment_date,
                    'payment_notes' => $assessment->payment_notes,
                    'created_at' => $assessment->created_at,
                    'updated_at' => $assessment->updated_at,
                ];
            });

        // Get comprehensive statistics data (ALL assessments, not just latest)
        $comprehensiveQuery = Assessment::query();

        // Apply the same filters to comprehensive statistics
        if ($search) {
            $comprehensiveQuery->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('applicant_name', 'like', "%{$search}%")
                  ->orWhereHas('program', function ($programQuery) use ($search) {
                      $programQuery->where('name', 'like', "%{$search}%");
                  })
                  ->orWhereHas('trainer', function ($trainerQuery) use ($search) {
                      $trainerQuery->where('full_name', 'like', "%{$search}%");
                  });
            });
        }

        if ($program && $program !== 'All Programs') {
            $comprehensiveQuery->where('program_id', $program);
        }

        if ($status && $status !== 'All Statuses') {
            $comprehensiveQuery->where('status', $status);
        }

        if ($dateFrom) {
            $comprehensiveQuery->where('assessment_date', '>=', $dateFrom);
        }
        if ($dateTo) {
            $comprehensiveQuery->where('assessment_date', '<=', $dateTo);
        }

        $comprehensiveAssessments = $comprehensiveQuery->with(['trainee', 'program', 'trainer'])->get()->map(function ($assessment) {
            return [
                'id' => $assessment->id,
                'title' => $assessment->title,
                'description' => $assessment->description,
                'type' => $assessment->type,
                'status' => $assessment->status,
                'result' => $assessment->result,
                'result_status' => $assessment->result_status,
                'result_color' => $assessment->result_color,
                'program_id' => $assessment->program_id,
                'program_name' => $assessment->program->name ?? 'N/A',
                'applicant_name' => $assessment->applicant_name ?: ($assessment->trainee ? $assessment->trainee->first_name . ' ' . $assessment->trainee->last_name : ($assessment->external_applicant_name ?: 'Unknown')),
                'applicant_type' => $assessment->applicant_type,
                'trainee_id' => $assessment->trainee_id,
                'external_applicant_email' => $assessment->external_applicant_email,
                'external_applicant_name' => $assessment->external_applicant_name,
                'assessment_date' => $assessment->assessment_date,
                'trainer_name' => $assessment->trainer->full_name ?? 'N/A',
                'created_at' => $assessment->created_at,
                'updated_at' => $assessment->updated_at,
            ];
        });

        $programs = Program::where('status', 'active')->get(['program_id', 'name']);
        $trainers = Trainer::where('status', 'active')->get(['id', 'full_name']);

        return Inertia::render('Admin/AssestmentResults', [
            'assessments' => $assessments,
            'comprehensive_assessments' => $comprehensiveAssessments,
            'programs' => $programs,
            'trainers' => $trainers,
            'filters' => [
                'search' => $search,
                'program' => $program,
                'status' => $status,
                'date_from' => $dateFrom,
                'date_to' => $dateTo,
                'per_page' => $perPage,
            ]
        ]);
    }

    /**
     * Store a newly created assessment result.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'type' => 'required|in:practical',
            'status' => 'required|in:pending,completed',
            'result' => 'nullable|in:competent,not_yet_competent,absent',
            'program_id' => 'required|exists:programs,program_id',
            'trainer_id' => 'required|exists:trainers,id',
            'assessment_date' => 'required|date',
            'applicant_type' => 'required|in:enrolled_trainee,external_applicant',
            'trainee_id' => 'nullable|exists:trainees,id',
            'external_applicant_name' => 'nullable|string|max:255',
            'external_applicant_email' => 'nullable|email',
            'external_applicant_phone' => 'nullable|string|max:20',
            'assessment_fee' => 'required|numeric|min:0',
            'payment_status' => 'required|in:pending,paid',
            'payment_method' => 'nullable|string|max:255',
            'payment_reference' => 'nullable|string|max:255',
            'payment_notes' => 'nullable|string',
        ]);

        Assessment::create($validated);

        return redirect()->back()->with('success', 'Assessment result created successfully.');
    }

    /**
     * Update the specified assessment result.
     */
    public function update(Request $request, Assessment $assessment)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'type' => 'required|in:practical',
            'status' => 'required|in:pending,completed',
            'result' => 'nullable|in:competent,not_yet_competent,absent',
            'program_id' => 'required|exists:programs,program_id',
            'trainer_id' => 'required|exists:trainers,id',
            'assessment_date' => 'required|date',
            'applicant_type' => 'required|in:enrolled_trainee,external_applicant',
            'trainee_id' => 'nullable|exists:trainees,id',
            'external_applicant_name' => 'nullable|string|max:255',
            'external_applicant_email' => 'nullable|email',
            'external_applicant_phone' => 'nullable|string|max:20',
            'assessment_fee' => 'required|numeric|min:0',
            'payment_status' => 'required|in:pending,paid',
            'payment_method' => 'nullable|string|max:255',
            'payment_reference' => 'nullable|string|max:255',
            'payment_notes' => 'nullable|string',
        ]);

        $assessment->update($validated);

        return redirect()->back()->with('success', 'Assessment result updated successfully.');
    }

    /**
     * Remove the specified assessment result.
     */
    public function destroy(Assessment $assessment)
    {
        $assessment->delete();

        return redirect()->back()->with('success', 'Assessment result deleted successfully.');
    }

    /**
     * Display assessment history for a specific assessment.
     */
    public function assessmentHistory(Assessment $assessment)
    {
        // Get ALL assessment attempts for the same applicant and assessment type
        // This includes the current assessment in the history
        $query = Assessment::where('type', $assessment->type)
            ->where('program_id', $assessment->program_id)
            ->where(function ($q) use ($assessment) {
                if ($assessment->applicant_type === 'enrolled_trainee') {
                    $q->where('trainee_id', $assessment->trainee_id)
                      ->where('applicant_type', 'enrolled_trainee');
                } else {
                    $q->where('external_applicant_email', $assessment->external_applicant_email)
                      ->where('applicant_type', 'external_applicant');
                }
            })
            ->with(['program', 'trainer', 'trainee'])
            ->orderBy('assessment_date', 'desc')
            ->orderBy('id', 'desc'); // Secondary sort by ID for consistent ordering

        $history = $query->get()->map(function ($attempt) {
            return [
                'id' => $attempt->id,
                'title' => $attempt->title,
                'type' => $attempt->type,
                'status' => $attempt->status,
                'result' => $attempt->result,
                'result_status' => $attempt->result_status,
                'result_color' => $attempt->result_color,
                'score' => $attempt->score,
                'grade' => $attempt->grade,
                'duration' => $attempt->duration,
                'notes' => $attempt->notes,
                'feedback' => $attempt->feedback,
                'attempt_number' => $attempt->attempt_number,
                'is_reassessment' => $attempt->is_reassessment,
                'assessment_date' => $attempt->assessment_date,
                'trainer_name' => $attempt->trainer->full_name ?? 'N/A',
                'assessor_name' => $attempt->trainer->full_name ?? 'N/A',
                'program_name' => $attempt->program->name ?? 'N/A',
                'created_at' => $attempt->created_at,
                'updated_at' => $attempt->updated_at,
            ];
        });

        // Get applicant information
        $applicant = null;
        if ($assessment->applicant_type === 'enrolled_trainee' && $assessment->trainee) {
            $applicant = [
                'id' => $assessment->trainee->id,
                'name' => $assessment->trainee->first_name . ' ' . $assessment->trainee->last_name,
                'email' => $assessment->trainee->email,
                'type' => 'Enrolled Trainee',
            ];
        } else {
            $applicant = [
                'name' => $assessment->external_applicant_name,
                'email' => $assessment->external_applicant_email,
                'type' => 'External Applicant',
            ];
        }

        return Inertia::render('Admin/AssessmentHistory', [
            'assessment' => [
                'id' => $assessment->id,
                'title' => $assessment->title,
                'type' => $assessment->type,
                'program_name' => $assessment->program->name ?? 'N/A',
                'program_id' => $assessment->program_id,
            ],
            'history' => $history,
            'applicant' => $applicant,
        ]);
    }
}

<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Assessment;
use App\Models\Program;
use App\Models\Trainee;
use App\Models\Trainer;
use Inertia\Inertia;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\EmploymentController;


class AssessmentController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $assessments = Assessment::with(['program', 'trainee', 'trainer'])
            ->latest()
            ->get()
            ->map(function ($assessment) {
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
                ];
            });

        $programs = Program::where('status', 'active')->get(['program_id', 'name', 'assigned_trainers']);
        
        // Get trainees who have completed at least one enrollment (modern system)
        // OR trainees with completed status (legacy system)
        $trainees = Trainee::with(['enrollments.program' => function($query) {
            $query->select('program_id', 'name');
        }])->where(function($query) {
            $query->where('status', 'completed')
                  ->orWhereHas('enrollments', function($enrollmentQuery) {
                      $enrollmentQuery->where('status', 'completed');
                  });
        })->get(['id', 'first_name', 'last_name', 'scholarship_package', 'status', 'program_qualification']);
        
        $trainers = Trainer::where('status', 'active')->get(['id', 'full_name']);

        return Inertia::render('Officer/Assessments', [
            'assessments' => $assessments,
            'programs' => $programs,
            'trainees' => $trainees,
            'trainers' => $trainers
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        try {
            Log::info('Assessment creation attempt', ['request_data' => $request->all()]);
            
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
                'trainee_id' => 'nullable|exists:trainees,id|required_if:applicant_type,enrolled_trainee',
                'external_applicant_name' => 'nullable|string|max:255|required_if:applicant_type,external_applicant',
                'external_applicant_email' => 'nullable|email|required_if:applicant_type,external_applicant',
                'external_applicant_phone' => 'nullable|string|max:20|required_if:applicant_type,external_applicant',
                'assessment_fee' => 'required|numeric|min:0',
                'payment_status' => 'required|in:pending,paid,refunded',
                'payment_method' => 'nullable|string|required_if:payment_status,paid',
                'payment_reference' => 'nullable|string|required_if:payment_status,paid',
                'payment_notes' => 'nullable|string',
                // Re-assessment fields
                'original_assessment_id' => 'nullable|exists:assessments,id',
                'is_reassessment' => 'nullable|boolean',
            ]);

            Log::info('Assessment validation passed', ['validated_data' => $validated]);

            // Determine attempt number and re-assessment status
            if ($validated['original_assessment_id']) {
                $originalAssessment = Assessment::find($validated['original_assessment_id']);
                $validated['is_reassessment'] = true;
                $validated['attempt_number'] = $originalAssessment->attempt_number + 1;
            } else {
                $validated['is_reassessment'] = false;
                $validated['attempt_number'] = 1;
            }

            // Additional validation: If enrolled trainee, check if they are completed
            if ($validated['applicant_type'] === 'enrolled_trainee' && $validated['trainee_id']) {
                $trainee = Trainee::find($validated['trainee_id']);
                if ($trainee && $trainee->status !== 'completed') {
                    Log::warning('Trainee not completed', ['trainee_id' => $validated['trainee_id'], 'status' => $trainee->status]);
                    return redirect()->back()->withErrors([
                        'trainee_id' => 'Only completed trainees can take assessments.'
                    ]);
                }
                
                // Check if trainee is a scholar - only exempt first attempt
                if ($trainee && !empty($trainee->scholarship_package) && !$validated['is_reassessment']) {
                    $validated['assessment_fee'] = 0;
                    $validated['payment_status'] = 'paid';
                    $validated['payment_method'] = 'scholarship_exemption';
                    $validated['payment_notes'] = 'Payment exempted due to ' . $trainee->scholarship_package . ' scholarship package (first attempt only)';
                }
            }

            // Set payment date if payment is made
            if ($validated['payment_status'] === 'paid') {
                $validated['payment_date'] = now();
            }

            // Clean up null values that shouldn't be null for external applicant fields
            if ($validated['applicant_type'] === 'external_applicant') {
                // Keep external applicant fields
            } else {
                // Remove external applicant fields when not needed
                unset($validated['external_applicant_name']);
                unset($validated['external_applicant_email']);
                unset($validated['external_applicant_phone']);
            }

            Log::info('Creating assessment with data', ['final_data' => $validated]);
            $assessment = Assessment::create($validated);
            Log::info('Assessment created successfully', ['assessment_id' => $assessment->id]);

            return redirect()->back()->with('success', 'Assessment created successfully!');
            
        } catch (\Illuminate\Validation\ValidationException $e) {
            Log::error('Assessment validation failed', ['errors' => $e->errors()]);
            throw $e;
        } catch (\Exception $e) {
            Log::error('Assessment creation failed', ['error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
            return redirect()->back()->withErrors([
                'general' => 'Failed to create assessment. Please check your input and try again.'
            ])->withInput();
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(Assessment $assessment)
    {
        return redirect()->route('officer.assessments');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Assessment $assessment)
    {
        // Prevent editing graded assessments
        if ($assessment->isGraded()) {
            return redirect()->route('officer.assessments')->with('error', 'Cannot edit graded assessments. This assessment has already been finalized.');
        }

        $programs = Program::where('status', 'active')->get(['program_id', 'name', 'assigned_trainers']);
        
        // Get trainees who have completed at least one enrollment (modern system)
        // OR trainees with completed status (legacy system)
        $trainees = Trainee::with(['enrollments.program' => function($query) {
            $query->select('program_id', 'name');
        }])->where(function($query) {
            $query->where('status', 'completed')
                  ->orWhereHas('enrollments', function($enrollmentQuery) {
                      $enrollmentQuery->where('status', 'completed');
                  });
        })->get(['id', 'first_name', 'last_name', 'scholarship_package', 'status', 'program_qualification']);
        
        $trainers = Trainer::where('status', 'active')->get(['id', 'full_name']);

        return Inertia::render('Officer/EditAssessment', [
            'assessment' => $assessment,
            'programs' => $programs,
            'trainees' => $trainees,
            'trainers' => $trainers
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Assessment $assessment)
    {
        // Prevent updating graded assessments
        if ($assessment->isGraded()) {
            return redirect()->back()->withErrors([
                'assessment' => 'Cannot update graded assessments. This assessment has already been finalized.'
            ]);
        }

        // Prevent setting result if payment is not completed
        if ($request->has('result') && $request->result && $assessment->payment_status !== 'paid') {
            return redirect()->back()->withErrors([
                'result' => 'Assessment cannot be evaluated until payment is completed.'
            ]);
        }

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
            'trainee_id' => 'nullable|exists:trainees,id|required_if:applicant_type,enrolled_trainee',
            'external_applicant_name' => 'nullable|string|max:255|required_if:applicant_type,external_applicant',
            'external_applicant_email' => 'nullable|email|required_if:applicant_type,external_applicant',
            'external_applicant_phone' => 'nullable|string|max:20|required_if:applicant_type,external_applicant',
            'assessment_fee' => 'required|numeric|min:0',
            'payment_status' => 'required|in:pending,paid,refunded',
            'payment_method' => 'nullable|string|required_if:payment_status,paid',
            'payment_reference' => 'nullable|string|required_if:payment_status,paid',
            'payment_notes' => 'nullable|string',
        ]);

        // Additional validation: If enrolled trainee, check if they are completed
        if ($validated['applicant_type'] === 'enrolled_trainee' && $validated['trainee_id']) {
            $trainee = Trainee::find($validated['trainee_id']);
            if ($trainee && $trainee->status !== 'completed') {
                return redirect()->back()->withErrors([
                    'trainee_id' => 'Only completed trainees can take assessments.'
                ]);
            }
            
            // Check if trainee is a scholar and automatically exempt from fee
            if ($trainee && !empty($trainee->scholarship_package)) {
                $validated['assessment_fee'] = 0;
                $validated['payment_status'] = 'paid';
                $validated['payment_method'] = 'scholarship_exemption';
                $validated['payment_notes'] = 'Payment exempted due to ' . $trainee->scholarship_package . ' scholarship package';
            }
        }

        // Set payment date if payment status changed to paid
        if ($validated['payment_status'] === 'paid' && $assessment->payment_status !== 'paid') {
            $validated['payment_date'] = now();
        } elseif ($validated['payment_status'] !== 'paid') {
            $validated['payment_date'] = null;
        }

        // Auto-set status based on result
        if (isset($validated['result']) && $validated['result'] !== null) {
            $validated['status'] = 'completed';
        } else {
            $validated['status'] = 'pending';
        }

        $assessment->update($validated);

        // Create employment record if assessment is marked as competent
        if (isset($validated['result']) && $validated['result'] === 'competent') {
            try {
                EmploymentController::createFromCompetentAssessment($assessment);
            } catch (\Exception $e) {
                Log::error('Failed to create employment record from assessment', [
                    'assessment_id' => $assessment->id,
                    'error' => $e->getMessage()
                ]);
            }
        }

        return redirect()->back()->with('success', 'Assessment updated successfully!');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Assessment $assessment)
    {
        // Prevent deleting graded assessments
        if ($assessment->isGraded()) {
            return redirect()->back()->with('error', 'Cannot delete graded assessments. This assessment has already been finalized.');
        }

        $assessment->delete();

        return redirect()->back()->with('success', 'Assessment deleted successfully!');
    }

    /**
     * Create a re-assessment
     */
    public function reassessment(Request $request, Assessment $assessment)
    {
        // Verify that the original assessment can be re-assessed
        if (!$assessment->canBeReassessed()) {
            return redirect()->back()->withErrors([
                'assessment' => 'This assessment cannot be re-assessed. Only assessments with "Not Yet Competent" or "Absent" results can be re-assessed.'
            ]);
        }

        $validated = $request->validate([
            'assessment_date' => 'required|date',
            'trainer_id' => 'required|exists:trainers,id',
            'assessment_fee' => 'required|numeric|min:0',
            'payment_status' => 'required|in:pending,paid,refunded',
            'payment_method' => 'nullable|string|required_if:payment_status,paid',
            'payment_reference' => 'nullable|string|required_if:payment_status,paid',
            'payment_notes' => 'nullable|string',
        ]);

        // Create re-assessment with same basic data as original
        $reassessmentData = [
            'title' => $assessment->title,
            'description' => $assessment->description,
            'type' => $assessment->type,
            'status' => 'pending',
            'program_id' => $assessment->program_id,
            'applicant_type' => $assessment->applicant_type,
            'trainee_id' => $assessment->trainee_id,
            'external_applicant_name' => $assessment->external_applicant_name,
            'external_applicant_email' => $assessment->external_applicant_email,
            'external_applicant_phone' => $assessment->external_applicant_phone,
            'original_assessment_id' => $assessment->id,
            'is_reassessment' => true,
            'attempt_number' => $assessment->attempt_number + 1,
            // New data from request
            'assessment_date' => $validated['assessment_date'],
            'trainer_id' => $validated['trainer_id'],
            'assessment_fee' => $validated['assessment_fee'],
            'payment_status' => $validated['payment_status'],
            'payment_method' => $validated['payment_method'] ?? null,
            'payment_reference' => $validated['payment_reference'] ?? null,
            'payment_notes' => $validated['payment_notes'] ?? 'Re-assessment - payment required',
        ];

        // Set payment date if payment is made
        if ($validated['payment_status'] === 'paid') {
            $reassessmentData['payment_date'] = now();
        }

        $reassessmentAssessment = Assessment::create($reassessmentData);

        return redirect()->back()->with('success', 'Re-assessment scheduled successfully!');
    }
}

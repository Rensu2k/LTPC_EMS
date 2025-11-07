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
    public function index(Request $request)
    {
        $perPage = $request->get('per_page', 20); // Default to 20 items per page
        $search = $request->get('search', '');
        $status = $request->get('status', '');
        $program = $request->get('program', '');

        // Build the query
        $query = Assessment::with(['program', 'trainee', 'trainer']);

        // Apply search filter if provided
        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('applicant_name', 'like', "%{$search}%")
                  ->orWhere('external_applicant_name', 'like', "%{$search}%")
                  ->orWhereHas('trainee', function ($traineeQuery) use ($search) {
                      $traineeQuery->where('first_name', 'like', "%{$search}%")
                                  ->orWhere('last_name', 'like', "%{$search}%");
                  })
                  ->orWhereHas('program', function ($programQuery) use ($search) {
                      $programQuery->where('name', 'like', "%{$search}%");
                  });
            });
        }

        // Apply status filter if provided
        if ($status && $status !== 'All Statuses' && $status !== '') {
            $query->where('status', $status);
        }

        // Apply program filter if provided
        if ($program && $program !== 'All Programs') {
            $query->whereHas('program', function ($programQuery) use ($program) {
                $programQuery->where('name', $program);
            });
        }

        // Get all assessments and group them by their original assessment
        $allAssessments = $query->latest()->get();

        // Group assessments by their original assessment ID
        $groupedAssessments = $allAssessments->groupBy(function ($assessment) {
            // Use original_assessment_id if it's a re-assessment, otherwise use its own ID
            return $assessment->is_reassessment ? $assessment->original_assessment_id : $assessment->id;
        });

        // Get the latest assessment from each group
        $assessments = $groupedAssessments->map(function ($group) {
            // Return the assessment with the highest attempt_number (most recent)
            return $group->sortByDesc('attempt_number')->first();
        })->values()
            ->map(function ($assessment) {
                // Get the original assessment ID for history navigation
                $originalAssessmentId = $assessment->is_reassessment ? $assessment->original_assessment_id : $assessment->id;
                
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
                    'requires_reenrollment' => $assessment->requiresReenrollment(),
                    'is_deletable' => $assessment->isDeletable(),
                    'is_reassessment' => $assessment->is_reassessment,
                    'attempt_number' => $assessment->attempt_number,
                    'original_assessment_id' => $assessment->original_assessment_id,
                    'original_assessment_for_history' => $originalAssessmentId, // For history navigation
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

        // Paginate the grouped assessments
        $assessmentsCollection = collect($assessments);
        $currentPage = $request->get('page', 1);
        $perPage = $request->get('per_page', 20);
        
        // Ensure HTTPS URLs for pagination when FORCE_HTTPS is enabled
        $path = $request->url();
        if (filter_var(env('FORCE_HTTPS', false), FILTER_VALIDATE_BOOLEAN) && 
            env('APP_URL') && str_starts_with(env('APP_URL'), 'https://')) {
            $path = str_replace('http://', 'https://', $path);
        }
        
        $paginatedAssessments = new \Illuminate\Pagination\LengthAwarePaginator(
            $assessmentsCollection->forPage($currentPage, $perPage),
            $assessmentsCollection->count(),
            $perPage,
            $currentPage,
            ['path' => $path]
        );

        return Inertia::render('Officer/Assessments', [
            'assessments' => $paginatedAssessments,
            'programs' => $programs,
            'trainees' => $trainees,
            'trainers' => $trainers,
            'filters' => [
                'search' => $search,
                'status' => $status,
                'program' => $program,
                'per_page' => $perPage,
            ]
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
                
                if (!$originalAssessment) {
                    Log::warning('Invalid original assessment ID', ['original_assessment_id' => $validated['original_assessment_id']]);
                    return redirect()->back()->withErrors([
                        'original_assessment_id' => 'The selected original assessment does not exist.'
                    ]);
                }
                
                $validated['is_reassessment'] = true;
                
                // Validate that re-assessment matches original assessment details
                if ($validated['trainee_id'] && $validated['trainee_id'] !== $originalAssessment->trainee_id) {
                    Log::warning('Re-assessment trainee mismatch', [
                        'reassessment_trainee_id' => $validated['trainee_id'],
                        'original_trainee_id' => $originalAssessment->trainee_id
                    ]);
                    return redirect()->back()->withErrors([
                        'trainee_id' => 'Re-assessment must be for the same trainee as the original assessment.'
                    ]);
                }
                
                if ($validated['program_id'] !== $originalAssessment->program_id) {
                    Log::warning('Re-assessment program mismatch', [
                        'reassessment_program_id' => $validated['program_id'],
                        'original_program_id' => $originalAssessment->program_id
                    ]);
                    return redirect()->back()->withErrors([
                        'program_id' => 'Re-assessment must be for the same program as the original assessment.'
                    ]);
                }
                
                // Find the original assessment ID to determine the correct attempt number
                $originalAssessmentId = $originalAssessment->is_reassessment ? $originalAssessment->original_assessment_id : $originalAssessment->id;
                
                // Get the highest attempt number for this assessment chain
                $maxAttemptNumber = Assessment::where(function($query) use ($originalAssessmentId) {
                    $query->where('id', $originalAssessmentId)
                          ->orWhere('original_assessment_id', $originalAssessmentId);
                })->max('attempt_number');
                
                // Check if maximum attempts reached (typically 3 attempts)
                if ($maxAttemptNumber >= 3) {
                    Log::warning('Maximum assessment attempts reached', [
                        'trainee_id' => $validated['trainee_id'] ?? 'external',
                        'program_id' => $validated['program_id'],
                        'max_attempts' => $maxAttemptNumber
                    ]);
                    return redirect()->back()->withErrors([
                        'general' => 'Maximum assessment attempts (3) reached for this trainee. They need to re-enroll in the program.'
                    ]);
                }
                
                $validated['attempt_number'] = $maxAttemptNumber + 1;
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
                
                // Check for existing assessments for this trainee in the same program
                if (!$validated['is_reassessment']) {
                    $existingAssessment = Assessment::where('trainee_id', $validated['trainee_id'])
                        ->where('program_id', $validated['program_id'])
                        ->where('is_reassessment', false)
                        ->first();
                    
                    if ($existingAssessment) {
                        Log::warning('Duplicate assessment attempt', [
                            'trainee_id' => $validated['trainee_id'], 
                            'program_id' => $validated['program_id'],
                            'existing_assessment_id' => $existingAssessment->id
                        ]);
                        return redirect()->back()->withErrors([
                            'trainee_id' => 'This trainee already has an assessment for this program. Use the re-assessment option instead.'
                        ]);
                    }
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
            
            // Check if trainee is a scholar - only exempt first attempt
            if ($trainee && !empty($trainee->scholarship_package) && $assessment->isFirstAttempt()) {
                $validated['assessment_fee'] = 0;
                $validated['payment_status'] = 'paid';
                $validated['payment_method'] = 'scholarship_exemption';
                $validated['payment_notes'] = 'Payment exempted due to ' . $trainee->scholarship_package . ' scholarship package (first attempt only)';
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
        // Prevent deleting assessments that cannot be deleted
        if (!$assessment->isDeletable()) {
        if ($assessment->isGraded()) {
            return redirect()->back()->with('error', 'Cannot delete graded assessments. This assessment has already been finalized.');
            }
            
            if ($assessment->payment_status === 'paid') {
                return redirect()->back()->with('error', 'Cannot delete paid assessments. This assessment payment has already been processed.');
            }
            
            return redirect()->back()->with('error', 'This assessment cannot be deleted.');
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
            if ($assessment->requiresReenrollment()) {
                return redirect()->back()->withErrors([
                    'assessment' => 'Maximum of 3 assessment attempts reached. The applicant must re-enroll in the program to take the assessment again.'
                ]);
            }
            
            return redirect()->back()->withErrors([
                'assessment' => 'This assessment cannot be re-assessed. Either the assessment result is already competent, or there is already a pending re-assessment scheduled.'
            ]);
        }

        $validated = $request->validate([
            'assessment_date' => 'required|date',
            'trainer_id' => 'required|exists:trainers,id',
            'assessment_fee' => 'required|numeric|min:0',
        ]);

        // Find the original assessment ID to determine the correct attempt number
        $originalAssessmentId = $assessment->is_reassessment ? $assessment->original_assessment_id : $assessment->id;
        
        // Get the highest attempt number for this assessment chain
        $maxAttemptNumber = Assessment::where(function($query) use ($originalAssessmentId) {
            $query->where('id', $originalAssessmentId)
                  ->orWhere('original_assessment_id', $originalAssessmentId);
        })->max('attempt_number');

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
            'original_assessment_id' => $originalAssessmentId,
            'is_reassessment' => true,
            'attempt_number' => $maxAttemptNumber + 1,
            // New data from request
            'assessment_date' => $validated['assessment_date'],
            'trainer_id' => $validated['trainer_id'],
            'assessment_fee' => $validated['assessment_fee'],
            // Payment will be handled by cashier - set default values
            'payment_status' => 'pending',
            'payment_method' => null,
            'payment_reference' => null,
            'payment_notes' => 'Re-assessment - payment to be processed by cashier',
            'payment_date' => null,
        ];

        $reassessmentAssessment = Assessment::create($reassessmentData);

        return redirect()->back()->with('success', 'Re-assessment scheduled successfully! Payment will be processed by the cashier.');
    }

    /**
     * Display assessment history for a specific assessment
     */
    public function assessmentHistory(Assessment $assessment)
    {
        // Get the original assessment (in case this is called from a re-assessment)
        $originalAssessment = $assessment->is_reassessment ? $assessment->originalAssessment : $assessment;
        
        // Get all re-assessments for this original assessment
        $reassessments = Assessment::with(['program', 'trainee', 'trainer'])
            ->where('original_assessment_id', $originalAssessment->id)
            ->orderBy('attempt_number', 'asc')
            ->get()
            ->map(function ($reassessment) {
                return [
                    'id' => $reassessment->id,
                    'title' => $reassessment->title,
                    'description' => $reassessment->description,
                    'type' => $reassessment->type,
                    'status' => $reassessment->status,
                    'result' => $reassessment->result,
                    'result_status' => $reassessment->result_status,
                    'result_color' => $reassessment->result_color,
                    'can_be_reassessed' => $reassessment->canBeReassessed(),
                    'is_reassessment' => $reassessment->is_reassessment,
                    'attempt_number' => $reassessment->attempt_number,
                    'original_assessment_id' => $reassessment->original_assessment_id,
                    'program_id' => $reassessment->program_id,
                    'program_name' => $reassessment->program->name ?? 'N/A',
                    'applicant_name' => $reassessment->applicant_name,
                    'applicant_type' => $reassessment->applicant_type,
                    'trainee_id' => $reassessment->trainee_id,
                    'trainee' => $reassessment->trainee ? [
                        'id' => $reassessment->trainee->id,
                        'first_name' => $reassessment->trainee->first_name,
                        'last_name' => $reassessment->trainee->last_name,
                        'scholarship_package' => $reassessment->trainee->scholarship_package,
                    ] : null,
                    'trainer_id' => $reassessment->trainer_id,
                    'trainer_name' => $reassessment->trainer ? $reassessment->trainer->full_name : 'N/A',
                    'assessment_date' => $reassessment->assessment_date,
                    'assessment_fee' => (float) $reassessment->assessment_fee,
                    'payment_status' => $reassessment->payment_status,
                    'payment_required' => $reassessment->payment_required,
                    'payment_completed' => $reassessment->payment_completed,
                ];
            });

        // Format the original assessment data
        $originalAssessmentData = [
            'id' => $originalAssessment->id,
            'title' => $originalAssessment->title,
            'description' => $originalAssessment->description,
            'type' => $originalAssessment->type,
            'status' => $originalAssessment->status,
            'result' => $originalAssessment->result,
            'result_status' => $originalAssessment->result_status,
            'result_color' => $originalAssessment->result_color,
            'can_be_reassessed' => $originalAssessment->canBeReassessed(),
            'is_reassessment' => $originalAssessment->is_reassessment,
            'attempt_number' => $originalAssessment->attempt_number,
            'original_assessment_id' => $originalAssessment->original_assessment_id,
            'program_id' => $originalAssessment->program_id,
            'program_name' => $originalAssessment->program->name ?? 'N/A',
            'applicant_name' => $originalAssessment->applicant_name,
            'applicant_type' => $originalAssessment->applicant_type,
            'trainee_id' => $originalAssessment->trainee_id,
            'trainee' => $originalAssessment->trainee ? [
                'id' => $originalAssessment->trainee->id,
                'first_name' => $originalAssessment->trainee->first_name,
                'last_name' => $originalAssessment->trainee->last_name,
                'scholarship_package' => $originalAssessment->trainee->scholarship_package,
            ] : null,
            'trainer_id' => $originalAssessment->trainer_id,
            'trainer_name' => $originalAssessment->trainer ? $originalAssessment->trainer->full_name : 'N/A',
            'assessment_date' => $originalAssessment->assessment_date,
            'assessment_fee' => (float) $originalAssessment->assessment_fee,
            'payment_status' => $originalAssessment->payment_status,
            'payment_required' => $originalAssessment->payment_required,
            'payment_completed' => $originalAssessment->payment_completed,
        ];

        // Get available trainers and programs for potential new re-assessment
        $trainers = Trainer::all(['id', 'full_name']);
        $programs = Program::where('status', 'active')->get(['program_id', 'name', 'assigned_trainers']);

        // Get the latest assessment for the modal (highest attempt number)
        $latestAssessment = Assessment::where(function($query) use ($originalAssessment) {
            $query->where('id', $originalAssessment->id)
                  ->orWhere('original_assessment_id', $originalAssessment->id);
        })->orderByDesc('attempt_number')->first();
        
        // Format the latest assessment data for the modal
        $latestAssessmentData = [
            'id' => $latestAssessment->id,
            'title' => $latestAssessment->title,
            'description' => $latestAssessment->description,
            'type' => $latestAssessment->type,
            'status' => $latestAssessment->status,
            'result' => $latestAssessment->result,
            'result_status' => $latestAssessment->result_status,
            'result_color' => $latestAssessment->result_color,
            'can_be_reassessed' => $latestAssessment->canBeReassessed(),
            'requires_reenrollment' => $latestAssessment->requiresReenrollment(),
            'is_reassessment' => $latestAssessment->is_reassessment,
            'attempt_number' => $latestAssessment->attempt_number,
            'original_assessment_id' => $latestAssessment->original_assessment_id,
            'program_id' => $latestAssessment->program_id,
            'program_name' => $latestAssessment->program->name ?? 'N/A',
            'applicant_name' => $latestAssessment->applicant_name,
            'applicant_type' => $latestAssessment->applicant_type,
            'trainee_id' => $latestAssessment->trainee_id,
            'trainee' => $latestAssessment->trainee ? [
                'id' => $latestAssessment->trainee->id,
                'first_name' => $latestAssessment->trainee->first_name,
                'last_name' => $latestAssessment->trainee->last_name,
                'scholarship_package' => $latestAssessment->trainee->scholarship_package,
            ] : null,
            'trainer_id' => $latestAssessment->trainer_id,
            'trainer_name' => $latestAssessment->trainer ? $latestAssessment->trainer->full_name : 'N/A',
            'assessment_date' => $latestAssessment->assessment_date,
            'assessment_fee' => (float) $latestAssessment->assessment_fee,
            'payment_status' => $latestAssessment->payment_status,
            'payment_required' => $latestAssessment->payment_required,
            'payment_completed' => $latestAssessment->payment_completed,
        ];

        return Inertia::render('Officer/AssessmentHistory', [
            'originalAssessment' => $originalAssessmentData,
            'latestAssessment' => $latestAssessmentData,
            'reassessments' => $reassessments,
            'trainers' => $trainers,
            'programs' => $programs,
        ]);
    }
}

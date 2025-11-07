<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Trainee;
use App\Models\Program;
use Inertia\Inertia;

class TraineeController extends Controller
{
    public function index(Request $request)
    {
        $perPage = $request->get('per_page', 20); // Default to 20 items per page
        $search = $request->get('search', '');
        $program = $request->get('program', '');
        $status = $request->get('status', '');
        $enrollmentType = $request->get('enrollment_type', '');
        $dateFrom = $request->get('date_from', '');
        $dateTo = $request->get('date_to', '');

        // Build the query
        $query = Trainee::with([
            'enrollments' => function($query) {
                $query->select('id', 'trainee_id', 'program_id', 'batch', 'enrollment_date', 'date_started', 'date_ended', 'completion_date', 'status', 'payment_status', 'created_at', 'updated_at');
            }, 
            'enrollments.program',
            'assessments' => function($query) {
                $query->select('id', 'trainee_id', 'program_id', 'assessment_date', 'result', 'status', 'attempt_number', 'is_reassessment', 'original_assessment_id')
                      ->orderBy('attempt_number', 'desc');
            }
        ]);

        // Apply search filter if provided
        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('first_name', 'like', "%{$search}%")
                  ->orWhere('last_name', 'like', "%{$search}%")
                  ->orWhere('uli_number', 'like', "%{$search}%")
                  ->orWhere('email_facebook', 'like', "%{$search}%")
                  ->orWhere('program_qualification', 'like', "%{$search}%")
                  ->orWhereRaw("CONCAT(first_name, ' ', last_name) LIKE ?", ["%{$search}%"])
                  ->orWhereRaw("CONCAT(first_name, ' ', middle_name, ' ', last_name) LIKE ?", ["%{$search}%"]);
            });
        }

        // Apply program filter if provided
        if ($program && $program !== 'All Programs') {
            $query->where('program_qualification', $program);
        }

        // Apply status filter if provided
        if ($status && $status !== 'All Statuses') {
            $query->where('status', $status);
        }

        // Apply enrollment type filter if provided (based on scholarship_package)
        if ($enrollmentType && $enrollmentType !== 'All Types') {
            if ($enrollmentType === 'scholar') {
                $query->whereNotNull('scholarship_package')
                      ->where('scholarship_package', '!=', '');
            } else if ($enrollmentType === 'regular') {
                $query->where(function($q) {
                    $q->whereNull('scholarship_package')
                      ->orWhere('scholarship_package', '');
                });
            }
        }

        // Apply date range filter if provided - filter by enrollment_date from enrollments
        if ($dateFrom) {
            $query->where(function($q) use ($dateFrom) {
                $q->whereHas('enrollments', function($subQ) use ($dateFrom) {
                    $subQ->whereDate('enrollment_date', '>=', $dateFrom);
                })->orWhere(function($subQ) use ($dateFrom) {
                    // Fallback for legacy trainees without enrollments
                    $subQ->whereDoesntHave('enrollments')
                          ->whereDate('entry_date', '>=', $dateFrom);
                });
            });
        }
        if ($dateTo) {
            $query->where(function($q) use ($dateTo) {
                $q->whereHas('enrollments', function($subQ) use ($dateTo) {
                    $subQ->whereDate('enrollment_date', '<=', $dateTo);
                })->orWhere(function($subQ) use ($dateTo) {
                    // Fallback for legacy trainees without enrollments
                    $subQ->whereDoesntHave('enrollments')
                          ->whereDate('entry_date', '<=', $dateTo);
                });
            });
        }

        // Get paginated results
        $trainees = $query->latest()
            ->paginate($perPage)
            ->through(function ($trainee) {
                // Determine current active enrollment; if none, use the most recent enrollment
                $activeEnrollment = $trainee->enrollments
                    ->where('status', 'active')
                    ->sortByDesc('created_at')
                    ->first();

                $latestEnrollment = $trainee->enrollments
                    ->sortByDesc('created_at')
                    ->first();

                $selectedEnrollment = $activeEnrollment ?: $latestEnrollment;

                // Compute display program name based on enrollment, fallback to legacy field
                $displayProgramName = $selectedEnrollment && $selectedEnrollment->program
                    ? $selectedEnrollment->program->name
                    : $trainee->program_qualification;

                // Assigned trainers should come from the selected enrollment's program when available
                $assignedTrainerNames = [];
                if ($selectedEnrollment && $selectedEnrollment->program && $selectedEnrollment->program->assigned_trainers) {
                    $trainers = \App\Models\Trainer::whereIn('id', $selectedEnrollment->program->assigned_trainers)->get();
                    $assignedTrainerNames = $trainers->pluck('full_name')->toArray();
                } else {
                    // Fallback to legacy program_qualification mapping
                    $legacyProgram = Program::where('name', $trainee->program_qualification)->first();
                    if ($legacyProgram && $legacyProgram->assigned_trainers) {
                        $trainers = \App\Models\Trainer::whereIn('id', $legacyProgram->assigned_trainers)->get();
                        $assignedTrainerNames = $trainers->pluck('full_name')->toArray();
                    }
                }

                // Mutate properties used by the frontend list for display
                // Frontend reads trainee.program_qualification to show Program column
                $trainee->program_qualification = $displayProgramName;

                // Frontend shows Batch from trainee.batch; use enrollment batch when present
                if ($selectedEnrollment) {
                    $trainee->batch = $selectedEnrollment->batch;
                }

                // Frontend shows Enrollment Date from trainee.entry_date; prefer enrollment_date
                if ($selectedEnrollment && $selectedEnrollment->enrollment_date) {
                    $trainee->entry_date = $selectedEnrollment->enrollment_date;
                }

                // Frontend shows Payment pill from trainee.payment_status; prefer enrollment payment_status
                if ($selectedEnrollment && $selectedEnrollment->payment_status) {
                    $trainee->payment_status = $selectedEnrollment->payment_status;
                }

                // Frontend shows Status from trainee.status; prefer enrollment status for current program
                if ($selectedEnrollment && $selectedEnrollment->status) {
                    $trainee->status = $selectedEnrollment->status;
                }

                // Attach enrollment dates for export
                if ($selectedEnrollment) {
                    $trainee->date_started = $selectedEnrollment->date_started;
                    $trainee->date_ended = $selectedEnrollment->date_ended;
                    $trainee->completion_date = $selectedEnrollment->completion_date;
                }

                // Attach latest assessment data for export
                // Get assessments for the current program
                $latestAssessment = $trainee->assessments
                    ->where('program_id', $selectedEnrollment ? $selectedEnrollment->program_id : null)
                    ->sortByDesc('attempt_number')
                    ->first();
                
                if ($latestAssessment) {
                    $trainee->latest_assessment_date = $latestAssessment->assessment_date;
                    $trainee->latest_assessment_result = $latestAssessment->result;
                } else {
                    $trainee->latest_assessment_date = null;
                    $trainee->latest_assessment_result = null;
                }

                // Attach trainer names for display
                $trainee->assigned_trainers = array_values(array_unique($assignedTrainerNames));

                return $trainee;
            });
        
        $programs = Program::where('status', 'active')->get(['program_id', 'name', 'description', 'duration']);
        
        return Inertia::render('Officer/Trainees', [
            'trainees' => $trainees,
            'programs' => $programs,
            'filters' => [
                'search' => $search,
                'program' => $program,
                'status' => $status,
                'enrollment_type' => $enrollmentType,
                'date_from' => $dateFrom,
                'date_to' => $dateTo,
                'per_page' => $perPage,
            ]
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'last_name' => 'required|string|max:255',
            'first_name' => 'required|string|max:255',
            'middle_name' => 'nullable|string|max:255',
            'extension' => 'nullable|string|max:10',
            'program_qualification' => 'required|string|max:255',
            'uli_number' => 'nullable|string|max:255',
            'entry_date' => 'nullable|date',
            'street_number' => 'nullable|string|max:255',
            'barangay' => 'nullable|string|max:255',
            'district' => 'nullable|string|max:255',
            'city_municipality' => 'nullable|string|max:255',
            'province' => 'nullable|string|max:255',
            'region' => 'nullable|string|max:255',
            'email_facebook' => 'nullable|string|max:255',
            'contact_number' => 'nullable|string|max:255',
            'nationality' => 'nullable|string|max:255',
            'sex' => 'nullable|in:male,female',
            'civil_status' => 'nullable|in:single,married,separated,widowed,common_law',
            'employment_status' => 'nullable|in:wage_employed,underemployed,self_employed,unemployed',
            'employment_type' => 'nullable|in:none,casual,probationary,contractual,regular,job_order,permanent,temporary',
            'birth_month' => 'nullable|string|max:2',
            'birth_day' => 'nullable|integer|min:1|max:31',
            'birth_year' => 'nullable|integer|min:1900|max:2010',
            'age' => 'nullable|integer|min:0|max:120',
            'birthplace_city' => 'nullable|string|max:255',
            'birthplace_province' => 'nullable|string|max:255',
            'birthplace_region' => 'nullable|string|max:255',
            'education' => 'nullable|array',
            'parent_guardian_name' => 'nullable|string|max:255',
            'parent_guardian_address' => 'nullable|string|max:255',
            'classification' => 'nullable|array',
            'classification_others' => 'nullable|string|max:255',
            'disability_type' => 'nullable|array',
            'disability_causes' => 'nullable|array',
            'scholarship_package' => 'nullable|string|max:255',
            'requirements' => 'nullable|array',
            'status' => 'nullable|in:active,completed,dropped,pending',
        ]);

        // Auto-manage payment status based on scholarship (not user input)
        if (!empty($validated['scholarship_package'])) {
            // Has scholarship - automatically paid and can be active
            $validated['payment_status'] = 'paid';
            // If no status is explicitly set, automatically enroll scholar
            if (!isset($validated['status'])) {
                $validated['status'] = 'active';
            }
        } else {
            // No scholarship - automatically unpaid and set to pending
            $validated['payment_status'] = 'unpaid';
            // If no status is explicitly set, set to pending for non-scholars
            if (!isset($validated['status'])) {
                $validated['status'] = 'pending';
            }
        }

        // Enrollment validation: Can only be active if payment is paid
        if (isset($validated['status']) && $validated['status'] === 'active' && $validated['payment_status'] !== 'paid') {
            return redirect()->back()->with('error', 'Trainee cannot be enrolled (active status) until payment is completed. Current payment status: ' . ucfirst($validated['payment_status']));
        }

        // Auto-assign batch based on program enrollment
        $program = Program::where('name', $validated['program_qualification'])->first();
        if ($program) {
            $validated['batch'] = $program->getNextAvailableBatch();
        } else {
            $validated['batch'] = 1; // Default to batch 1 if program not found
        }

        $trainee = Trainee::create($validated);

        $batchMessage = $validated['batch'] == 1 ? '' : ' (Assigned to Batch 2 due to enrollment capacity)';
        return redirect()->back()->with('success', 'Trainee registered successfully!' . $batchMessage);
    }

    public function show(Trainee $trainee)
    {
        // Redirect to trainees list since we use modals for viewing
        return redirect()->route('officer.trainees');
    }

    public function edit(Trainee $trainee)
    {
        $programs = Program::where('status', 'active')->get(['program_id', 'name', 'description', 'duration']);
        
        return Inertia::render('Officer/EditTrainee', [
            'trainee' => $trainee,
            'programs' => $programs
        ]);
    }

    public function update(Request $request, Trainee $trainee)
    {
        $validated = $request->validate([
            'last_name' => 'required|string|max:255',
            'first_name' => 'required|string|max:255',
            'middle_name' => 'nullable|string|max:255',
            'extension' => 'nullable|string|max:10',
            'program_qualification' => 'required|string|max:255',
            'uli_number' => 'nullable|string|max:255',
            'entry_date' => 'nullable|date',
            'street_number' => 'nullable|string|max:255',
            'barangay' => 'nullable|string|max:255',
            'district' => 'nullable|string|max:255',
            'city_municipality' => 'nullable|string|max:255',
            'province' => 'nullable|string|max:255',
            'region' => 'nullable|string|max:255',
            'email_facebook' => 'nullable|string|max:255',
            'contact_number' => 'nullable|string|max:255',
            'nationality' => 'nullable|string|max:255',
            'sex' => 'nullable|in:male,female',
            'civil_status' => 'nullable|in:single,married,separated,widowed,common_law',
            'employment_status' => 'nullable|in:wage_employed,underemployed,self_employed,unemployed',
            'employment_type' => 'nullable|in:none,casual,probationary,contractual,regular,job_order,permanent,temporary',
            'birth_month' => 'nullable|string|max:2',
            'birth_day' => 'nullable|integer|min:1|max:31',
            'birth_year' => 'nullable|integer|min:1900|max:2010',
            'age' => 'nullable|integer|min:0|max:120',
            'birthplace_city' => 'nullable|string|max:255',
            'birthplace_province' => 'nullable|string|max:255',
            'birthplace_region' => 'nullable|string|max:255',
            'education' => 'nullable|array',
            'parent_guardian_name' => 'nullable|string|max:255',
            'parent_guardian_address' => 'nullable|string|max:255',
            'classification' => 'nullable|array',
            'classification_others' => 'nullable|string|max:255',
            'disability_type' => 'nullable|array',
            'disability_causes' => 'nullable|array',
            'scholarship_package' => 'nullable|string|max:255',
            'requirements' => 'nullable|array',
            'status' => 'nullable|in:active,completed,dropped,pending',
        ]);

        // Auto-manage payment status based on scholarship (not user input)
        if (!empty($validated['scholarship_package'])) {
            // Has scholarship - automatically paid and can be active
            $validated['payment_status'] = 'paid';
            // If no status is explicitly set, automatically enroll scholar
            if (!isset($validated['status'])) {
                $validated['status'] = 'active';
            }
        } else {
            // No scholarship - automatically unpaid and set to pending
            $validated['payment_status'] = 'unpaid';
            // If no status is explicitly set, set to pending for non-scholars
            if (!isset($validated['status'])) {
                $validated['status'] = 'pending';
            }
        }

        // Enrollment validation: Can only be active if payment is paid
        if (isset($validated['status']) && $validated['status'] === 'active' && $validated['payment_status'] !== 'paid') {
            return redirect()->back()->with('error', 'Trainee cannot be enrolled (active status) until payment is completed. Current payment status: ' . ucfirst($validated['payment_status']));
        }

        // If payment status becomes unpaid and trainee is active, set to pending
        if ($validated['payment_status'] !== 'paid' && $trainee->status === 'active') {
            $validated['status'] = 'pending';
            return redirect()->back()->with('warning', 'Trainee has been set to pending due to payment status change. Payment status: ' . ucfirst($validated['payment_status']));
        }

        // If program qualification changed, reassign batch
        if ($validated['program_qualification'] !== $trainee->program_qualification) {
            $program = Program::where('name', $validated['program_qualification'])->first();
            if ($program) {
                $validated['batch'] = $program->getNextAvailableBatch();
            } else {
                $validated['batch'] = 1; // Default to batch 1 if program not found
            }
        }

        $trainee->update($validated);

        $batchMessage = isset($validated['batch']) && $validated['batch'] == 2 ? ' (Reassigned to Batch 2 due to enrollment capacity)' : '';
        return redirect()->back()->with('success', 'Trainee updated successfully!' . $batchMessage);
    }

    public function destroy(Trainee $trainee)
    {
        // Check if trainee can be deleted
        // Cannot delete if: status is active OR payment status is paid
        if ($trainee->status === 'active' || $trainee->payment_status === 'paid') {
            return redirect()->back()->with('error', 'Cannot delete trainee. Trainees with active status or paid payment status cannot be deleted. Current status: ' . ucfirst($trainee->status) . ', Payment: ' . ucfirst($trainee->payment_status));
        }

        $trainee->delete();

        return redirect()->back()->with('success', 'Trainee deleted successfully!');
    }

    /**
     * Display a listing of the resource for admin.
     */
    public function adminIndex(Request $request)
    {
        $perPage = $request->get('per_page', 10); // Default to 10 items per page
        $search = $request->get('search', '');
        $program = $request->get('program', '');
        $status = $request->get('status', '');
        $enrollmentType = $request->get('enrollment_type', '');
        $dateFrom = $request->get('date_from', '');
        $dateTo = $request->get('date_to', '');

        $query = Trainee::with('enrollments');

        // Apply search filter if provided
        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('first_name', 'like', "%{$search}%")
                  ->orWhere('last_name', 'like', "%{$search}%")
                  ->orWhere('uli_number', 'like', "%{$search}%")
                  ->orWhere('email_facebook', 'like', "%{$search}%")
                  ->orWhereRaw("CONCAT(first_name, ' ', last_name) LIKE ?", ["%{$search}%"])
                  ->orWhereRaw("CONCAT(first_name, ' ', middle_name, ' ', last_name) LIKE ?", ["%{$search}%"]);
            });
        }

        // Apply program filter if provided
        if ($program && $program !== 'All Programs') {
            // Filter by program_id from enrollments relationship
            $query->whereHas('enrollments', function($q) use ($program) {
                $q->where('program_id', $program);
            });
        }

        // Apply status filter if provided
        if ($status && $status !== 'All Statuses') {
            $query->where('status', $status);
        }

        // Apply enrollment type filter if provided (based on scholarship_package)
        if ($enrollmentType && $enrollmentType !== 'All Types') {
            if ($enrollmentType === 'scholar') {
                $query->whereNotNull('scholarship_package')
                      ->where('scholarship_package', '!=', '');
            } else if ($enrollmentType === 'regular') {
                $query->where(function($q) {
                    $q->whereNull('scholarship_package')
                      ->orWhere('scholarship_package', '');
                });
            }
        }

        // Apply date range filter if provided - filter by enrollment_date from enrollments
        if ($dateFrom) {
            $query->where(function($q) use ($dateFrom) {
                $q->whereHas('enrollments', function($subQ) use ($dateFrom) {
                    $subQ->whereDate('enrollment_date', '>=', $dateFrom);
                })->orWhere(function($subQ) use ($dateFrom) {
                    // Fallback for legacy trainees without enrollments
                    $subQ->whereDoesntHave('enrollments')
                          ->whereDate('entry_date', '>=', $dateFrom);
                });
            });
        }
        if ($dateTo) {
            $query->where(function($q) use ($dateTo) {
                $q->whereHas('enrollments', function($subQ) use ($dateTo) {
                    $subQ->whereDate('enrollment_date', '<=', $dateTo);
                })->orWhere(function($subQ) use ($dateTo) {
                    // Fallback for legacy trainees without enrollments
                    $subQ->whereDoesntHave('enrollments')
                          ->whereDate('entry_date', '<=', $dateTo);
                });
            });
        }

        $trainees = $query->latest()
            ->paginate($perPage)
            ->through(function ($trainee) {
                // Get trainer information from the trainee's enrollments
                $trainerNames = [];
                
                // Check if trainee has enrollments with programs that have assigned trainers
                foreach ($trainee->enrollments as $enrollment) {
                    $program = Program::where('program_id', $enrollment->program_id)->first();
                    if ($program && $program->assigned_trainers) {
                        $trainers = \App\Models\Trainer::whereIn('id', $program->assigned_trainers)->get();
                        $trainerNames = array_merge($trainerNames, $trainers->pluck('full_name')->toArray());
                    }
                }
                
                // If no trainers found from enrollments, try to get from program_qualification (legacy)
                if (empty($trainerNames)) {
                    $program = Program::where('name', $trainee->program_qualification)->first();
                    if ($program && $program->assigned_trainers) {
                        $trainers = \App\Models\Trainer::whereIn('id', $program->assigned_trainers)->get();
                        $trainerNames = $trainers->pluck('full_name')->toArray();
                    }
                }
                
                // Get the most recent enrollment date from the new enrollment system
                $latestEnrollment = $trainee->enrollments->sortByDesc('enrollment_date')->first();
                
                // Use the enrollment date from the new system if available, otherwise fall back to the old system
                $actualEnrollmentDate = $latestEnrollment ? $latestEnrollment->enrollment_date : $trainee->date_enrolled;
                
                // Add trainer information and actual enrollment date to the trainee data
                $trainee->assigned_trainers = array_unique($trainerNames);
                $trainee->actual_enrollment_date = $actualEnrollmentDate;
                
                return $trainee;
            });
        
        $programs = Program::where('status', 'active')->get(['program_id', 'name', 'description', 'duration']);
        
        return Inertia::render('Admin/Trainees', [
            'trainees' => $trainees,
            'programs' => $programs,
            'filters' => [
                'search' => $search,
                'program' => $program,
                'status' => $status,
                'enrollment_type' => $enrollmentType,
                'date_from' => $dateFrom,
                'date_to' => $dateTo,
                'per_page' => $perPage,
            ]
        ]);
    }

    public function adminStore(Request $request)
    {
        $validated = $request->validate([
            'last_name' => 'required|string|max:255',
            'first_name' => 'required|string|max:255',
            'middle_name' => 'nullable|string|max:255',
            'extension' => 'nullable|string|max:10',
            'program_qualification' => 'required|string|max:255',
            'uli_number' => 'nullable|string|max:255',
            'entry_date' => 'nullable|date',
            'street_number' => 'nullable|string|max:255',
            'barangay' => 'nullable|string|max:255',
            'district' => 'nullable|string|max:255',
            'city_municipality' => 'nullable|string|max:255',
            'province' => 'nullable|string|max:255',
            'region' => 'nullable|string|max:255',
            'email_facebook' => 'nullable|string|max:255',
            'contact_number' => 'nullable|string|max:255',
            'nationality' => 'nullable|string|max:255',
            'sex' => 'nullable|in:male,female',
            'civil_status' => 'nullable|in:single,married,separated,widowed,common_law',
            'employment_status' => 'nullable|in:wage_employed,underemployed,self_employed,unemployed',
            'employment_type' => 'nullable|in:none,casual,probationary,contractual,regular,job_order,permanent,temporary',
            'birth_month' => 'nullable|string|max:2',
            'birth_day' => 'nullable|integer|min:1|max:31',
            'birth_year' => 'nullable|integer|min:1900|max:2010',
            'age' => 'nullable|integer|min:0|max:120',
            'birthplace_city' => 'nullable|string|max:255',
            'birthplace_province' => 'nullable|string|max:255',
            'birthplace_region' => 'nullable|string|max:255',
            'education' => 'nullable|array',
            'parent_guardian_name' => 'nullable|string|max:255',
            'parent_guardian_address' => 'nullable|string|max:255',
            'classification' => 'nullable|array',
            'classification_others' => 'nullable|string|max:255',
            'disability_type' => 'nullable|array',
            'disability_causes' => 'nullable|array',
            'scholarship_package' => 'nullable|string|max:255',
            'requirements' => 'nullable|array',
            'status' => 'nullable|in:active,completed,dropped,pending',
        ]);

        // Auto-manage payment status based on scholarship (not user input)
        if (!empty($validated['scholarship_package'])) {
            // Has scholarship - automatically paid and can be active
            $validated['payment_status'] = 'paid';
            // If no status is explicitly set, automatically enroll scholar
            if (!isset($validated['status'])) {
                $validated['status'] = 'active';
            }
        } else {
            // No scholarship - automatically unpaid and set to pending
            $validated['payment_status'] = 'unpaid';
            // If no status is explicitly set, set to pending for non-scholars
            if (!isset($validated['status'])) {
                $validated['status'] = 'pending';
            }
        }

        // Enrollment validation: Can only be active if payment is paid
        if (isset($validated['status']) && $validated['status'] === 'active' && $validated['payment_status'] !== 'paid') {
            return redirect()->back()->with('error', 'Trainee cannot be enrolled (active status) until payment is completed. Current payment status: ' . ucfirst($validated['payment_status']));
        }

        // Auto-assign batch based on program enrollment
        $program = Program::where('name', $validated['program_qualification'])->first();
        if ($program) {
            $validated['batch'] = $program->getNextBatch();
        } else {
            $validated['batch'] = 1; // Default to batch 1 if program not found
        }

        $trainee = Trainee::create($validated);

        $batchMessage = $validated['batch'] == 1 ? '' : ' (Assigned to Batch 2 due to enrollment capacity)';
        return redirect()->back()->with('success', 'Trainee registered successfully!' . $batchMessage);
    }

    public function adminUpdate(Request $request, Trainee $trainee)
    {
        $validated = $request->validate([
            'last_name' => 'required|string|max:255',
            'first_name' => 'required|string|max:255',
            'middle_name' => 'nullable|string|max:255',
            'extension' => 'nullable|string|max:10',
            'program_qualification' => 'required|string|max:255',
            'uli_number' => 'nullable|string|max:255',
            'entry_date' => 'nullable|date',
            'street_number' => 'nullable|string|max:255',
            'barangay' => 'nullable|string|max:255',
            'district' => 'nullable|string|max:255',
            'city_municipality' => 'nullable|string|max:255',
            'province' => 'nullable|string|max:255',
            'region' => 'nullable|string|max:255',
            'email_facebook' => 'nullable|string|max:255',
            'contact_number' => 'nullable|string|max:255',
            'nationality' => 'nullable|string|max:255',
            'sex' => 'nullable|in:male,female',
            'civil_status' => 'nullable|in:single,married,separated,widowed,common_law',
            'employment_status' => 'nullable|in:wage_employed,underemployed,self_employed,unemployed',
            'employment_type' => 'nullable|in:none,casual,probationary,contractual,regular,job_order,permanent,temporary',
            'birth_month' => 'nullable|string|max:2',
            'birth_day' => 'nullable|integer|min:1|max:31',
            'birth_year' => 'nullable|integer|min:1900|max:2010',
            'age' => 'nullable|integer|min:0|max:120',
            'birthplace_city' => 'nullable|string|max:255',
            'birthplace_province' => 'nullable|string|max:255',
            'birthplace_region' => 'nullable|string|max:255',
            'education' => 'nullable|array',
            'parent_guardian_name' => 'nullable|string|max:255',
            'parent_guardian_address' => 'nullable|string|max:255',
            'classification' => 'nullable|array',
            'classification_others' => 'nullable|string|max:255',
            'disability_type' => 'nullable|array',
            'disability_causes' => 'nullable|array',
            'scholarship_package' => 'nullable|string|max:255',
            'requirements' => 'nullable|array',
            'status' => 'nullable|in:active,completed,dropped,pending',
        ]);

        // Auto-manage payment status based on scholarship (not user input)
        if (!empty($validated['scholarship_package'])) {
            // Has scholarship - automatically paid and can be active
            $validated['payment_status'] = 'paid';
            // If no status is explicitly set, automatically enroll scholar
            if (!isset($validated['status'])) {
                $validated['status'] = 'active';
            }
        } else {
            // No scholarship - automatically unpaid and set to pending
            $validated['payment_status'] = 'unpaid';
            // If no status is explicitly set, set to pending for non-scholars
            if (!isset($validated['status'])) {
                $validated['status'] = 'pending';
            }
        }

        // Enrollment validation: Can only be active if payment is paid
        if (isset($validated['status']) && $validated['status'] === 'active' && $validated['payment_status'] !== 'paid') {
            return redirect()->back()->with('error', 'Trainee cannot be enrolled (active status) until payment is completed. Current payment status: ' . ucfirst($validated['payment_status']));
        }

        // If payment status becomes unpaid and trainee is active, set to pending
        if ($validated['payment_status'] !== 'paid' && $trainee->status === 'active') {
            $validated['status'] = 'pending';
            return redirect()->back()->with('warning', 'Trainee has been set to pending due to payment status change. Payment status: ' . ucfirst($validated['payment_status']));
        }

        // If program qualification changed, reassign batch
        if ($validated['program_qualification'] !== $trainee->program_qualification) {
            $program = Program::where('name', $validated['program_qualification'])->first();
            if ($program) {
                $validated['batch'] = $program->getNextBatch();
            } else {
                $validated['batch'] = 1; // Default to batch 1 if program not found
            }
        }

        $trainee->update($validated);

        $batchMessage = isset($validated['batch']) && $validated['batch'] == 2 ? ' (Reassigned to Batch 2 due to enrollment capacity)' : '';
        return redirect()->back()->with('success', 'Trainee updated successfully!' . $batchMessage);
    }

    public function adminDestroy(Trainee $trainee)
    {
        $trainee->delete();

        return redirect()->back()->with('success', 'Trainee deleted successfully!');
    }

    public function updateStatus(Request $request, Trainee $trainee)
    {
        $validated = $request->validate([
            'status' => 'required|in:active,completed,dropped,pending'
        ]);

        // Get the current/latest enrollment to operate on
        $currentEnrollment = $trainee->enrollments()
            ->with('program')
            ->orderBy('created_at', 'desc')
            ->first();

        if (!$currentEnrollment) {
            // Fallback to legacy behavior if no enrollments exist
            if (in_array($trainee->status, ['completed', 'dropped'])) {
                return redirect()->back()->with('error', 'This trainee\'s status can no longer be changed after being marked as completed or dropped.');
            }
        } else {
            // New enrollment system: check the current enrollment status instead of overall trainee status
            if (in_array($currentEnrollment->status, ['completed', 'dropped'])) {
                return redirect()->back()->with('error', 'This enrollment\'s status can no longer be changed after being marked as completed or dropped.');
            }
        }

        // Guard: cannot set to active unless payment is paid (check current enrollment if available)
        $paymentStatus = $currentEnrollment ? $currentEnrollment->payment_status : $trainee->payment_status;
        if ($validated['status'] === 'active' && $paymentStatus !== 'paid') {
            return redirect()->back()->with('error', 'Trainee cannot be set to Active until payment is completed. Current payment status: ' . ucfirst($paymentStatus));
        }

        // Update the appropriate enrollment and trainee records
        if ($currentEnrollment) {
            // New enrollment system: update the current enrollment
            $enrollmentData = ['status' => $validated['status']];
            
            // Set completion date if status is completed
            if ($validated['status'] === 'completed') {
                $enrollmentData['completion_date'] = now()->toDateString();
            }
            
            $currentEnrollment->update($enrollmentData);

            // Also update the trainee's overall status to match current enrollment
            $trainee->update([
                'status' => $validated['status'],
                'payment_status' => $currentEnrollment->payment_status
            ]);

            return redirect()->back()->with('success', 'Trainee enrollment status updated successfully for ' . ($currentEnrollment->program ? $currentEnrollment->program->name : 'current program') . '!');
        } else {
            // Legacy system: update trainee directly
            $trainee->update($validated);
            return redirect()->back()->with('success', 'Trainee status updated successfully!');
        }
    }
}

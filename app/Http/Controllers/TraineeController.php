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

use Illuminate\Http\Request;
use App\Models\Trainee;
use App\Models\Program;
use Inertia\Inertia;

class TraineeController extends Controller
{
    /**
     * Shared validation rules for trainee store/update operations.
     */
    private function traineeValidationRules(): array
    {
        return [
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
        ];
    }

    /**
     * Apply shared business logic for scholarship, payment, and batch assignment.
     *
     * @return \Illuminate\Http\RedirectResponse|null Returns null on success (modifies $validated by reference), or a redirect response on validation failure.
     */
    private function processTraineeData(array &$validated, ?Trainee $existingTrainee = null)
    {
        if (!empty($validated['scholarship_package'])) {
            $validated['_payment_status'] = 'paid';
            $validated['_status'] = 'active';
        } else {
            $validated['_payment_status'] = 'unpaid';
            $validated['_status'] = 'pending';
        }

        if ($validated['_status'] === 'active' && $validated['_payment_status'] !== 'paid') {
            return redirect()->back()->with('error', 'Trainee cannot be enrolled (active status) until payment is completed. Current payment status: ' . ucfirst($validated['_payment_status']));
        }

        if ($existingTrainee && $validated['_payment_status'] !== 'paid' && $existingTrainee->status === 'active') {
            $existingTrainee->update($validated);
            $existingTrainee->forceFill(['status' => 'pending', 'payment_status' => $validated['_payment_status']])->save();
            return redirect()->back()->with('warning', 'Trainee has been set to pending due to payment status change. Payment status: ' . ucfirst($validated['_payment_status']));
        }

        $needsBatch = !$existingTrainee || ($existingTrainee && $validated['program_qualification'] !== $existingTrainee->program_qualification);
        if ($needsBatch) {
            $program = Program::where('name', $validated['program_qualification'])->first();
            if ($program) {
                $validated['batch'] = $program->getNextAvailableBatch();
            } else {
                $validated['batch'] = 1;
            }
        }

        return null; // Success — no redirect needed
    }

    public function index(Request $request)
    {
        $perPage = min((int) $request->get('per_page', 20), 100);
        $search = $this->sanitizeSearch($request->get('search', ''));
        $program = $request->get('program', '');
        $status = $request->get('status', '');
        $enrollmentType = $request->get('enrollment_type', '');
        $dateFrom = $request->get('date_from', '');
        $dateTo = $request->get('date_to', '');

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

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('first_name', 'like', "%{$search}%")
                  ->orWhere('last_name', 'like', "%{$search}%")
                  ->orWhere('email_facebook', 'like', "%{$search}%")
                  ->orWhere('program_qualification', 'like', "%{$search}%")
                  ->orWhereRaw("CONCAT(first_name, ' ', last_name) LIKE ?", ["%{$search}%"])
                  ->orWhereRaw("CONCAT(first_name, ' ', middle_name, ' ', last_name) LIKE ?", ["%{$search}%"]);

                if (preg_match('/^(ULI|STRESSTEST|T-)/i', $search)) {
                    $q->orWhere('uli_number', 'like', "{$search}%");
                } else {
                    $q->orWhere('uli_number', 'like', "%{$search}%");
                }
            });
        }

        if ($program && $program !== 'All Programs') {
            $query->where('program_qualification', $program);
        }

        if ($status && $status !== 'All Statuses') {
            $query->where('status', $status);
        }

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

        if ($dateFrom) {
            $query->where(function($q) use ($dateFrom) {
                $q->whereHas('enrollments', function($subQ) use ($dateFrom) {
                    $subQ->whereDate('enrollment_date', '>=', $dateFrom);
                })->orWhere(function($subQ) use ($dateFrom) {
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
                    $subQ->whereDoesntHave('enrollments')
                          ->whereDate('entry_date', '<=', $dateTo);
                });
            });
        }

        $trainees = $query->latest()
            ->paginate($perPage)
            ->through(function ($trainee) {
                $activeEnrollment = $trainee->enrollments
                    ->where('status', 'active')
                    ->sortByDesc('created_at')
                    ->first();

                $latestEnrollment = $trainee->enrollments
                    ->sortByDesc('created_at')
                    ->first();

                $selectedEnrollment = $activeEnrollment ?: $latestEnrollment;

                $displayProgramName = $selectedEnrollment && $selectedEnrollment->program
                    ? $selectedEnrollment->program->name
                    : $trainee->program_qualification;

                $assignedTrainerNames = [];
                if ($selectedEnrollment && $selectedEnrollment->program && $selectedEnrollment->program->assigned_trainers) {
                    $trainers = \App\Models\Trainer::whereIn('id', $selectedEnrollment->program->assigned_trainers)->get();
                    $assignedTrainerNames = $trainers->pluck('full_name')->toArray();
                } else {
                    $legacyProgram = Program::where('name', $trainee->program_qualification)->first();
                    if ($legacyProgram && $legacyProgram->assigned_trainers) {
                        $trainers = \App\Models\Trainer::whereIn('id', $legacyProgram->assigned_trainers)->get();
                        $assignedTrainerNames = $trainers->pluck('full_name')->toArray();
                    }
                }

                $trainee->program_qualification = $displayProgramName;

                if ($selectedEnrollment) {
                    $trainee->batch = $selectedEnrollment->batch;
                }

                if ($selectedEnrollment && $selectedEnrollment->enrollment_date) {
                    $trainee->entry_date = $selectedEnrollment->enrollment_date;
                }

                if ($selectedEnrollment && $selectedEnrollment->payment_status) {
                    $trainee->payment_status = $selectedEnrollment->payment_status;
                }

                if ($selectedEnrollment && $selectedEnrollment->status) {
                    $trainee->status = $selectedEnrollment->status;
                }

                if ($selectedEnrollment) {
                    $trainee->date_started = $selectedEnrollment->date_started;
                    $trainee->date_ended = $selectedEnrollment->date_ended;
                    $trainee->completion_date = $selectedEnrollment->completion_date;
                }

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
        $validated = $request->validate($this->traineeValidationRules());

        $redirect = $this->processTraineeData($validated);
        if ($redirect) return $redirect;

        $trainee = Trainee::create($validated);
        $trainee->forceFill([
            'status' => $validated['_status'],
            'payment_status' => $validated['_payment_status'],
        ])->save();

        $batchMessage = $validated['batch'] == 1 ? '' : ' (Assigned to Batch 2 due to enrollment capacity)';
        return redirect()->back()->with('success', 'Trainee registered successfully!' . $batchMessage);
    }

    public function show(Trainee $trainee)
    {
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
        $validated = $request->validate($this->traineeValidationRules());

        $redirect = $this->processTraineeData($validated, $trainee);
        if ($redirect) return $redirect;

        $trainee->update($validated);
        $trainee->forceFill([
            'status' => $validated['_status'],
            'payment_status' => $validated['_payment_status'],
        ])->save();

        $batchMessage = isset($validated['batch']) && $validated['batch'] == 2 ? ' (Reassigned to Batch 2 due to enrollment capacity)' : '';
        return redirect()->back()->with('success', 'Trainee updated successfully!' . $batchMessage);
    }

    public function destroy(Trainee $trainee)
    {
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
        $perPage = min((int) $request->get('per_page', 10), 100);
        $search = $this->sanitizeSearch($request->get('search', ''));
        $program = $request->get('program', '');
        $status = $request->get('status', '');
        $enrollmentType = $request->get('enrollment_type', '');
        $dateFrom = $request->get('date_from', '');
        $dateTo = $request->get('date_to', '');

        $query = Trainee::with('enrollments');

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('first_name', 'like', "%{$search}%")
                  ->orWhere('last_name', 'like', "%{$search}%")
                  ->orWhere('email_facebook', 'like', "%{$search}%")
                  ->orWhereRaw("CONCAT(first_name, ' ', last_name) LIKE ?", ["%{$search}%"])
                  ->orWhereRaw("CONCAT(first_name, ' ', middle_name, ' ', last_name) LIKE ?", ["%{$search}%"]);

                if (preg_match('/^(ULI|STRESSTEST|T-)/i', $search)) {
                    $q->orWhere('uli_number', 'like', "{$search}%");
                } else {
                    $q->orWhere('uli_number', 'like', "%{$search}%");
                }
            });
        }

        if ($program && $program !== 'All Programs') {
            $query->whereHas('enrollments', function($q) use ($program) {
                $q->where('program_id', $program);
            });
        }

        if ($status && $status !== 'All Statuses') {
            $query->where('status', $status);
        }

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

        if ($dateFrom) {
            $query->where(function($q) use ($dateFrom) {
                $q->whereHas('enrollments', function($subQ) use ($dateFrom) {
                    $subQ->whereDate('enrollment_date', '>=', $dateFrom);
                })->orWhere(function($subQ) use ($dateFrom) {
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
                    $subQ->whereDoesntHave('enrollments')
                          ->whereDate('entry_date', '<=', $dateTo);
                });
            });
        }

        $trainees = $query->latest()
            ->paginate($perPage)
            ->through(function ($trainee) {
                $trainerNames = [];
                
                foreach ($trainee->enrollments as $enrollment) {
                    $program = Program::where('program_id', $enrollment->program_id)->first();
                    if ($program && $program->assigned_trainers) {
                        $trainers = \App\Models\Trainer::whereIn('id', $program->assigned_trainers)->get();
                        $trainerNames = array_merge($trainerNames, $trainers->pluck('full_name')->toArray());
                    }
                }
                
                if (empty($trainerNames)) {
                    $program = Program::where('name', $trainee->program_qualification)->first();
                    if ($program && $program->assigned_trainers) {
                        $trainers = \App\Models\Trainer::whereIn('id', $program->assigned_trainers)->get();
                        $trainerNames = $trainers->pluck('full_name')->toArray();
                    }
                }
                
                $latestEnrollment = $trainee->enrollments->sortByDesc('enrollment_date')->first();
                
                $actualEnrollmentDate = $latestEnrollment ? $latestEnrollment->enrollment_date : $trainee->date_enrolled;
                
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
        $validated = $request->validate($this->traineeValidationRules());

        $redirect = $this->processTraineeData($validated);
        if ($redirect) return $redirect;

        $trainee = Trainee::create($validated);
        $trainee->forceFill([
            'status' => $validated['_status'],
            'payment_status' => $validated['_payment_status'],
        ])->save();

        $batchMessage = $validated['batch'] == 1 ? '' : ' (Assigned to Batch 2 due to enrollment capacity)';
        return redirect()->back()->with('success', 'Trainee registered successfully!' . $batchMessage);
    }

    public function adminUpdate(Request $request, Trainee $trainee)
    {
        $validated = $request->validate($this->traineeValidationRules());

        $redirect = $this->processTraineeData($validated, $trainee);
        if ($redirect) return $redirect;

        $trainee->update($validated);
        $trainee->forceFill([
            'status' => $validated['_status'],
            'payment_status' => $validated['_payment_status'],
        ])->save();

        $batchMessage = isset($validated['batch']) && $validated['batch'] == 2 ? ' (Reassigned to Batch 2 due to enrollment capacity)' : '';
        return redirect()->back()->with('success', 'Trainee updated successfully!' . $batchMessage);
    }

    public function adminDestroy(Trainee $trainee)
    {
        if ($trainee->status === 'active' || $trainee->payment_status === 'paid') {
            return redirect()->back()->with('error', 'Cannot delete trainee. Trainees with active status or paid payment status cannot be deleted. Current status: ' . ucfirst($trainee->status) . ', Payment: ' . ucfirst($trainee->payment_status));
        }

        $trainee->delete();

        return redirect()->back()->with('success', 'Trainee deleted successfully!');
    }

    public function updateStatus(Request $request, Trainee $trainee)
    {
        $validated = $request->validate([
            'status' => 'required|in:active,completed,dropped,pending'
        ]);

        $currentEnrollment = $trainee->enrollments()
            ->with('program')
            ->orderBy('created_at', 'desc')
            ->first();

        if (!$currentEnrollment) {
            if (in_array($trainee->status, ['completed', 'dropped'])) {
                return redirect()->back()->with('error', 'This trainee\'s status can no longer be changed after being marked as completed or dropped.');
            }
        } else {
            if (in_array($currentEnrollment->status, ['completed', 'dropped'])) {
                return redirect()->back()->with('error', 'This enrollment\'s status can no longer be changed after being marked as completed or dropped.');
            }
        }

        $paymentStatus = $currentEnrollment ? $currentEnrollment->payment_status : $trainee->payment_status;
        if ($validated['status'] === 'active' && $paymentStatus !== 'paid') {
            return redirect()->back()->with('error', 'Trainee cannot be set to Active until payment is completed. Current payment status: ' . ucfirst($paymentStatus));
        }

        if ($currentEnrollment) {
            $enrollmentData = ['status' => $validated['status']];
            
            if ($validated['status'] === 'completed') {
                $enrollmentData['completion_date'] = now()->toDateString();
            }
            
            $currentEnrollment->update($enrollmentData);

            $trainee->forceFill([
                'status' => $validated['status'],
                'payment_status' => $currentEnrollment->payment_status
            ])->save();

            return redirect()->back()->with('success', 'Trainee enrollment status updated successfully for ' . ($currentEnrollment->program ? $currentEnrollment->program->name : 'current program') . '!');
        } else {
            $trainee->forceFill($validated)->save();
            return redirect()->back()->with('success', 'Trainee status updated successfully!');
        }
    }
}

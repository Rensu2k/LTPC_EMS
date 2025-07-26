<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Program;
use App\Models\Trainer;
use Inertia\Inertia;

class ProgramController extends Controller
{
    /**
     * Display a listing of the resource for officers.
     */
    public function index()
    {
        $programs = Program::latest()->get()->map(function ($program) {
            return [
                'program_id' => $program->program_id,
                'name' => $program->name,
                'description' => $program->description,
                'duration' => $program->duration,
                'status' => $program->status,
                'assigned_trainers' => $program->assigned_trainers,
                'enrollments' => $program->enrollment_count, // Active trainees only
                'current_batch' => $program->current_batch,
                'current_batch_count' => $program->getCurrentBatchEnrollmentCount(),
                'enrollment_fee' => $program->enrollment_fee,
                'start_date' => $program->start_date,
                'end_date' => $program->end_date,
                'created_at' => $program->created_at,
                // Additional status counts
                'total_trainees' => $program->total_trainees_count,
                'completed_trainees' => $program->completed_trainees_count,
                'dropped_trainees' => $program->dropped_trainees_count,
                'pending_trainees' => $program->pending_trainees_count,
            ];
        });

        $trainers = Trainer::where('status', 'active')->get()->map(function ($trainer) {
            return [
                'id' => $trainer->id,
                'name' => $trainer->full_name,
                'expertise' => $trainer->expertise,
                'expertise_string' => $trainer->expertise_string,
            ];
        });

        return Inertia::render('Officer/Programs', [
            'programs' => $programs,
            'trainers' => $trainers
        ]);
    }

    /**
     * Display a listing of the resource for admin.
     */
    public function adminIndex()
    {
        $programs = Program::latest()->get()->map(function ($program) {
            return [
                'program_id' => $program->program_id,
                'name' => $program->name,
                'description' => $program->description,
                'duration' => $program->duration,
                'prerequisites' => $program->prerequisites,
                'enrollment_fee' => $program->enrollment_fee,
                'status' => $program->status,
                'created_at' => $program->created_at,
                'assigned_trainers' => $program->assigned_trainers,
                // Additional status counts
                'enrollments' => $program->total_enrollment_count, // All enrollments (active, completed, dropped, etc.)
                'total_trainees' => $program->total_trainees_count,
                'completed_trainees' => $program->completed_trainees_count,
                'dropped_trainees' => $program->dropped_trainees_count,
                'pending_trainees' => $program->pending_trainees_count,
            ];
        });

        $trainers = Trainer::where('status', 'active')->get()->map(function ($trainer) {
            return [
                'id' => $trainer->id,
                'name' => $trainer->full_name,
                'expertise' => $trainer->expertise,
                'expertise_string' => $trainer->expertise_string,
            ];
        });

        return Inertia::render('Admin/Programs', [
            'programs' => $programs,
            'trainers' => $trainers
        ]);
    }

    /**
     * Store a newly created resource in storage (Officer).
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:programs,name',
            'description' => 'nullable|string',
            'duration' => 'required|string|max:255',
            'enrollment_fee' => 'nullable|numeric|min:0',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
        ]);

        // Programs now allow unlimited enrollments

        $program = Program::create($validated);

        return redirect()->back()->with('success', 'Program created successfully! Click the edit button to assign trainers to this program.');
    }

    /**
     * Store a newly created resource in storage (Admin).
     */
    public function adminStore(Request $request)
    {
        $validated = $request->validate([
            'program_id' => 'nullable|string|max:50|unique:programs,program_id',
            'name' => 'required|string|max:255|unique:programs,name',
            'description' => 'nullable|string',
            'duration' => 'required|string|max:255',
            'prerequisites' => 'nullable|string',
            'enrollment_fee' => 'nullable|numeric|min:0',
            'status' => 'nullable|in:active,inactive',
        ]);

        // Map admin fields to database fields
        $programData = [
            'program_id' => $validated['program_id'] ?? null, // Will be auto-generated by model if empty
            'name' => $validated['name'],
            'description' => $validated['description'],
            'duration' => $validated['duration'],
            'prerequisites' => $validated['prerequisites'] ?? null,
            // Programs now allow unlimited enrollments
            'enrollment_fee' => $validated['enrollment_fee'] ?? null,
            'status' => $validated['status'] ?? 'active',
        ];

        $program = Program::create($programData);

        return redirect()->back()->with('success', 'Program created successfully! Click the edit button to assign trainers to this program.');
    }

    /**
     * Display the specified resource.
     */
    public function show(Program $program)
    {
        return redirect()->route('officer.programs');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Program $program)
    {
        // Filter trainers to show only those whose expertise matches the program name
        $trainers = Trainer::where('status', 'active')
            ->where(function ($query) use ($program) {
                $query->whereJsonContains('expertise', $program->name)
                      ->orWhere(function ($subQuery) use ($program) {
                          // Also check if any expertise contains the program name
                          $subQuery->whereRaw('JSON_SEARCH(expertise, "one", ?) IS NOT NULL', ["%{$program->name}%"]);
                      });
            })
            ->get()
            ->map(function ($trainer) {
                return [
                    'id' => $trainer->id,
                    'name' => $trainer->full_name,
                    'expertise' => $trainer->expertise,
                    'expertise_string' => $trainer->expertise_string,
                ];
            });

        return Inertia::render('Officer/EditProgram', [
            'program' => $program,
            'trainers' => $trainers
        ]);
    }

    /**
     * Update the specified resource in storage (Officer).
     */
    public function update(Request $request, Program $program)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:programs,name,' . $program->program_id . ',program_id',
            'description' => 'nullable|string',
            'duration' => 'required|string|max:255',
            'assigned_trainers' => 'nullable|array',
            'assigned_trainers.*' => 'exists:trainers,id',
            'status' => 'nullable|in:active,inactive',
            'enrollment_fee' => 'nullable|numeric|min:0',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
        ]);

        // Programs now allow unlimited enrollments

        $program->update($validated);

        return redirect()->back()->with('success', 'Program updated successfully!');
    }

    /**
     * Update the specified resource in storage (Admin).
     */
    public function adminUpdate(Request $request, Program $program)
    {
        $validated = $request->validate([
            'program_id' => 'nullable|string|max:50|unique:programs,program_id,' . $program->program_id . ',program_id',
            'name' => 'required|string|max:255|unique:programs,name,' . $program->program_id . ',program_id',
            'description' => 'nullable|string',
            'duration' => 'required|string|max:255',
            'prerequisites' => 'nullable|string',
            'enrollment_fee' => 'nullable|numeric|min:0',
            'status' => 'nullable|in:active,inactive',
            'assigned_trainers' => 'nullable|array',
            'assigned_trainers.*' => 'exists:trainers,id',
        ]);

        // Map admin fields to database fields
        $programData = [
            'program_id' => $validated['program_id'] ?? $program->program_id,
            'name' => $validated['name'],
            'description' => $validated['description'],
            'duration' => $validated['duration'],
            'prerequisites' => $validated['prerequisites'] ?? null,
            'max_enrollments' => 25, // Fixed value for all programs
            'enrollment_fee' => $validated['enrollment_fee'] ?? null,
            'status' => $validated['status'] ?? 'active',
            'assigned_trainers' => $validated['assigned_trainers'] ?? $program->assigned_trainers,
        ];

        $program->update($programData);

        return redirect()->back()->with('success', 'Program updated successfully!');
    }

    /**
     * Display enrollments for a specific program (Admin).
     */
    public function adminEnrollments(Program $program)
    {
        // Get new system enrollments
        $newSystemEnrollments = $program->enrollments()
            ->with('trainee')
            ->orderBy('enrollment_date', 'desc')
            ->get()
            ->map(function ($enrollment) {
                return [
                    'id' => $enrollment->id,
                    'trainee_id' => $enrollment->trainee_id,
                    'program_id' => $enrollment->program_id,
                    'batch' => $enrollment->batch,
                    'enrollment_date' => $enrollment->enrollment_date,
                    'date_started' => $enrollment->date_started,
                    'completion_date' => $enrollment->completion_date,
                    'date_ended' => $enrollment->date_ended,
                    'status' => $enrollment->status,
                    'payment_status' => $enrollment->payment_status,
                    'enrollment_fee' => $enrollment->enrollment_fee,
                    'payment_method' => $enrollment->payment_method,
                    'payment_reference' => $enrollment->payment_reference,
                    'payment_date' => $enrollment->payment_date,
                    'payment_notes' => $enrollment->payment_notes,
                    'notes' => $enrollment->notes,
                    'trainee' => [
                        'id' => $enrollment->trainee->id,
                        'full_name' => $enrollment->trainee->full_name,
                        'email' => $enrollment->trainee->email,
                        'contact_number' => $enrollment->trainee->contact_number,
                        'address' => $enrollment->trainee->address,
                        'status' => $enrollment->trainee->status,
                    ],
                    'enrollment_type' => 'new_system',
                ];
            });

        // Get legacy trainees (from old system)
        $enrolledTraineeIds = $newSystemEnrollments->pluck('trainee_id')->toArray();
        $legacyTrainees = \App\Models\Trainee::where('program_qualification', $program->name)
            ->whereNotIn('id', $enrolledTraineeIds)
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($trainee) {
                return [
                    'id' => null, // No enrollment ID for legacy trainees
                    'trainee_id' => $trainee->id,
                    'program_id' => null, // No program_id for legacy trainees
                    'batch' => $trainee->batch,
                    'enrollment_date' => $trainee->created_at,
                    'date_started' => $trainee->date_started,
                    'completion_date' => $trainee->completion_date,
                    'date_ended' => $trainee->date_ended,
                    'status' => $trainee->status,
                    'payment_status' => $trainee->payment_status ?? 'unknown',
                    'enrollment_fee' => $trainee->enrollment_fee,
                    'payment_method' => null,
                    'payment_reference' => null,
                    'payment_date' => null,
                    'payment_notes' => null,
                    'notes' => $trainee->notes,
                    'trainee' => [
                        'id' => $trainee->id,
                        'full_name' => $trainee->full_name,
                        'email' => $trainee->email,
                        'contact_number' => $trainee->contact_number,
                        'address' => $trainee->address,
                        'status' => $trainee->status,
                    ],
                    'enrollment_type' => 'legacy',
                ];
            });

        // Combine and sort all enrollments by enrollment date
        $allEnrollments = $newSystemEnrollments->concat($legacyTrainees)
            ->sortByDesc('enrollment_date')
            ->values();

        return Inertia::render('Admin/ProgramEnrollments', [
            'program' => [
                'program_id' => $program->program_id,
                'name' => $program->name,
                'description' => $program->description,
                'duration' => $program->duration,
                'prerequisites' => $program->prerequisites,
                'enrollment_fee' => $program->enrollment_fee,
                'status' => $program->status,
                'created_at' => $program->created_at,
            ],
            'enrollments' => $allEnrollments
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Program $program)
    {
        // Check if program has enrolled trainees
        $enrollmentCount = $program->enrollment_count;
        if ($enrollmentCount > 0) {
            return redirect()->back()->with('error', 'Cannot delete program with active enrollments. Please transfer or complete all trainees first.');
        }

        $program->delete();

        return redirect()->back()->with('success', 'Program deleted successfully!');
    }

    /**
     * Remove the specified resource from storage (Admin).
     */
    public function adminDestroy(Program $program)
    {
        // Check if program has enrolled trainees
        $enrollmentCount = $program->enrollment_count;
        if ($enrollmentCount > 0) {
            return redirect()->back()->with('error', 'Cannot delete program with active enrollments. Please transfer or complete all trainees first.');
        }

        $program->delete();

        return redirect()->back()->with('success', 'Program deleted successfully!');
    }
}

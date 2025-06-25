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
                'enrollments' => $program->enrollment_count, // Active trainees only
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

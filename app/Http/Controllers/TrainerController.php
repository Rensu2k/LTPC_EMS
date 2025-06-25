<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Trainer;
use App\Models\Program;
use Inertia\Inertia;

class TrainerController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $trainers = Trainer::latest()->get()->map(function ($trainer) {
            return [
                'id' => $trainer->id,
                'full_name' => $trainer->full_name,
                'expertise' => $trainer->expertise,
                'expertise_string' => $trainer->expertise_string,
                'email' => $trainer->email,
                'phone' => $trainer->phone,
                'biography' => $trainer->biography,
                'availability_schedule' => $trainer->availability_schedule,
                'status' => $trainer->status,
                'active_programs_count' => $trainer->active_programs_count,
                'total_trainees_count' => $trainer->total_trainees_count,
                'active_trainees_count' => $trainer->active_trainees_count,
                'completed_trainees_count' => $trainer->completed_trainees_count,
                'assigned_programs' => $trainer->assigned_programs,
                'created_at' => $trainer->created_at,
                'updated_at' => $trainer->updated_at,
            ];
        });
        
        $programs = Program::where('status', 'active')->get(['program_id', 'name', 'description', 'duration']);
        
        return Inertia::render('Officer/Trainers', [
            'trainers' => $trainers,
            'programs' => $programs
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
        $validated = $request->validate([
            'full_name' => 'required|string|max:255',
            'expertise' => 'required|array|min:1',
            'expertise.*' => 'required|string|max:255',
            'email' => 'required|email|unique:trainers,email',
            'phone' => 'required|string|max:255',
            'biography' => 'nullable|string',
            'availability_schedule' => 'nullable|array',
            'availability_schedule.*.day' => 'required|string',
            'availability_schedule.*.available' => 'required|boolean',
            'availability_schedule.*.start_time' => 'nullable|string',
            'availability_schedule.*.end_time' => 'nullable|string',
        ]);

        $trainer = Trainer::create($validated);

        return redirect()->back()->with('success', 'Trainer added successfully!');
    }

    /**
     * Display the specified resource.
     */
    public function show(Trainer $trainer)
    {
        return redirect()->route('officer.trainers');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Trainer $trainer)
    {
        $trainerData = [
            'id' => $trainer->id,
            'full_name' => $trainer->full_name,
            'expertise' => $trainer->expertise,
            'email' => $trainer->email,
            'phone' => $trainer->phone,
            'biography' => $trainer->biography,
            'availability_schedule' => $trainer->availability_schedule,
            'status' => $trainer->status,
            'active_programs_count' => $trainer->active_programs_count,
            'total_trainees_count' => $trainer->total_trainees_count,
            'active_trainees_count' => $trainer->active_trainees_count,
            'completed_trainees_count' => $trainer->completed_trainees_count,
            'assigned_programs' => $trainer->assigned_programs,
            'created_at' => $trainer->created_at,
            'updated_at' => $trainer->updated_at,
        ];
        
        $programs = Program::where('status', 'active')->get(['program_id', 'name', 'description', 'duration']);
        
        return Inertia::render('Officer/EditTrainer', [
            'trainer' => $trainerData,
            'programs' => $programs
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Trainer $trainer)
    {
        $validated = $request->validate([
            'full_name' => 'required|string|max:255',
            'expertise' => 'required|array|min:1',
            'expertise.*' => 'required|string|max:255',
            'email' => 'required|email|unique:trainers,email,' . $trainer->id,
            'phone' => 'required|string|max:255',
            'biography' => 'nullable|string',
            'availability_schedule' => 'nullable|array',
            'availability_schedule.*.day' => 'required|string',
            'availability_schedule.*.available' => 'required|boolean',
            'availability_schedule.*.start_time' => 'nullable|string',
            'availability_schedule.*.end_time' => 'nullable|string',
            'status' => 'nullable|in:active,inactive',
        ]);

        $trainer->update($validated);

        return redirect()->back()->with('success', 'Trainer updated successfully!');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Trainer $trainer)
    {
        $trainer->delete();

        return redirect()->back()->with('success', 'Trainer deleted successfully!');
    }

    /**
     * Admin methods for trainer management
     */
    public function adminIndex()
    {
        $trainers = Trainer::latest()->get()->map(function ($trainer) {
            return [
                'id' => $trainer->id,
                'full_name' => $trainer->full_name,
                'expertise' => $trainer->expertise,
                'expertise_string' => $trainer->expertise_string,
                'email' => $trainer->email,
                'phone' => $trainer->phone,
                'biography' => $trainer->biography,
                'availability_schedule' => $trainer->availability_schedule,
                'status' => $trainer->status,
                'active_programs_count' => $trainer->active_programs_count,
                'total_trainees_count' => $trainer->total_trainees_count,
                'active_trainees_count' => $trainer->active_trainees_count,
                'completed_trainees_count' => $trainer->completed_trainees_count,
                'assigned_programs' => $trainer->assigned_programs,
                'created_at' => $trainer->created_at,
                'updated_at' => $trainer->updated_at,
            ];
        });
        
        $programs = Program::where('status', 'active')->get(['program_id', 'name', 'description', 'duration']);
        
        return Inertia::render('Admin/Trainers', [
            'trainers' => $trainers,
            'programs' => $programs
        ]);
    }

    public function adminStore(Request $request)
    {
        $validated = $request->validate([
            'full_name' => 'required|string|max:255',
            'expertise' => 'required|array|min:1',
            'expertise.*' => 'required|string|max:255',
            'email' => 'required|email|unique:trainers,email',
            'phone' => 'required|string|max:255',
            'biography' => 'nullable|string',
            'availability_schedule' => 'nullable|array',
            'availability_schedule.*.day' => 'required|string',
            'availability_schedule.*.available' => 'required|boolean',
            'availability_schedule.*.start_time' => 'nullable|string',
            'availability_schedule.*.end_time' => 'nullable|string',
        ]);

        $trainer = Trainer::create($validated);

        return redirect()->back()->with('success', 'Trainer added successfully!');
    }

    public function adminUpdate(Request $request, Trainer $trainer)
    {
        $validated = $request->validate([
            'full_name' => 'required|string|max:255',
            'expertise' => 'required|array|min:1',
            'expertise.*' => 'required|string|max:255',
            'email' => 'required|email|unique:trainers,email,' . $trainer->id,
            'phone' => 'required|string|max:255',
            'biography' => 'nullable|string',
            'availability_schedule' => 'nullable|array',
            'availability_schedule.*.day' => 'required|string',
            'availability_schedule.*.available' => 'required|boolean',
            'availability_schedule.*.start_time' => 'nullable|string',
            'availability_schedule.*.end_time' => 'nullable|string',
            'status' => 'nullable|in:active,inactive',
        ]);

        $trainer->update($validated);

        return redirect()->back()->with('success', 'Trainer updated successfully!');
    }

    public function adminDestroy(Trainer $trainer)
    {
        $trainer->delete();

        return redirect()->back()->with('success', 'Trainer deleted successfully!');
    }
}

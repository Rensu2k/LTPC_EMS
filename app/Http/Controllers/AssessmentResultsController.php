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
                    'updated_at' => $assessment->updated_at,
                ];
            });

        $programs = Program::where('status', 'active')->get(['program_id', 'name']);
        $trainers = Trainer::where('status', 'active')->get(['id', 'full_name']);

        return Inertia::render('Admin/AssestmentResults', [
            'assessments' => $assessments,
            'programs' => $programs,
            'trainers' => $trainers,
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
}

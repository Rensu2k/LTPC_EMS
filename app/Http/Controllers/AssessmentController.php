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
                    'score' => $assessment->score,
                    'max_score' => $assessment->max_score,
                    'program_name' => $assessment->program->name ?? 'N/A',
                    'applicant_name' => $assessment->applicant_name,
                    'applicant_type' => $assessment->applicant_type,
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

        $programs = Program::where('status', 'active')->get(['program_id', 'name']);
        $trainees = Trainee::where('status', 'completed')->get(['id', 'first_name', 'last_name', 'scholarship_package', 'status']);
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
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'type' => 'required|in:practical,theoretical,both',
            'program_id' => 'required|exists:programs,program_id',
            'trainer_id' => 'required|exists:trainers,id',
            'max_score' => 'required|integer|min:1',
            'passing_score' => 'required|integer|min:1|lte:max_score',
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

        $assessment = Assessment::create($validated);

        return redirect()->back()->with('success', 'Assessment created successfully!');
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

        $programs = Program::where('status', 'active')->get(['program_id', 'name']);
        $trainees = Trainee::where('status', 'completed')->get(['id', 'first_name', 'last_name', 'scholarship_package', 'status']);
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

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'type' => 'required|in:practical,theoretical,both',
            'status' => 'required|in:pending,completed,graded,pass,fail',
            'score' => 'nullable|integer|min:0',
            'max_score' => 'required|integer|min:1',
            'passing_score' => 'required|integer|min:1|lte:max_score',
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

        // Auto-determine pass/fail status if score is provided
        if (isset($validated['score']) && $validated['score'] !== null && isset($validated['passing_score'])) {
            $validated['status'] = $validated['score'] >= $validated['passing_score'] ? 'pass' : 'fail';
        }

        $assessment->update($validated);

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
}

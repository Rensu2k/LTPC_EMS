<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Trainee;
use App\Models\Program;
use App\Models\TraineeEnrollment;
use Inertia\Inertia;

class TraineeEnrollmentController extends Controller
{
    /**
     * Show enrollment form for existing trainee
     */
    public function create(Trainee $trainee)
    {
        // Block enrollment form if trainee already has any active enrollment
        $hasActiveEnrollment = $trainee->enrollments()->where('status', 'active')->exists();
        if ($hasActiveEnrollment) {
            return redirect()->back()->with('error', 'Trainee already has an active enrollment and cannot enroll in a new program. Complete or drop the active enrollment first.');
        }

        // Get available programs (exclude programs trainee is already enrolled in OR has completed)
        $enrolledProgramIds = $trainee->enrollments()
            ->where('status', 'active')
            ->pluck('program_id')
            ->toArray();

        // Get programs trainee has already completed
        $completedProgramIds = $trainee->enrollments()
            ->where('status', 'completed')
            ->pluck('program_id')
            ->toArray();

        // Combine both lists to exclude from available programs
        $excludedProgramIds = array_unique(array_merge($enrolledProgramIds, $completedProgramIds));

        $availablePrograms = Program::where('status', 'active')
            ->whereNotIn('program_id', $excludedProgramIds)
            ->get(['program_id', 'name', 'description', 'duration', 'enrollment_fee']);

        // Get trainee's enrollment history
        $enrollmentHistory = $trainee->enrollments()
            ->with('program')
            ->orderBy('created_at', 'desc')
            ->get();

        return Inertia::render('Officer/EnrollTrainee', [
            'trainee' => $trainee,
            'availablePrograms' => $availablePrograms,
            'enrollmentHistory' => $enrollmentHistory
        ]);
    }

    /**
     * Enroll trainee in a new program
     */
    public function store(Request $request, Trainee $trainee)
    {
        // Server-side guard: prevent enrolling while any active enrollment exists
        if ($trainee->enrollments()->where('status', 'active')->exists()) {
            return redirect()->back()->with('error', 'Trainee already has an active enrollment and cannot enroll in a new program.');
        }

        $validated = $request->validate([
            'program_id' => 'required|string|exists:programs,program_id',
            'batch' => 'nullable|integer|min:1',
            'enrollment_fee' => 'nullable|numeric|min:0',
            'notes' => 'nullable|string|max:500',
            'maintain_scholarship' => 'nullable|boolean'
        ]);

        try {
            // Get the program
            $program = Program::find($validated['program_id']);

            // Check if trainee has already completed this program
            if ($trainee->hasCompletedProgram($validated['program_id'])) {
                return redirect()->back()->with('error', 'This trainee has already completed this program and cannot re-enroll.');
            }
            
            // TODO: Enable prerequisite checking when implemented
            // if ($program->prerequisites && !$this->checkPrerequisites($trainee, $program)) {
            //     return redirect()->back()->with('error', 'Trainee has not completed required prerequisites.');
            // }

            // Determine the batch for enrollment
            $assignedBatch = $validated['batch'] ?? $program->getNextAvailableBatch();
            
            // Check if the determined batch is full (25 trainees per batch)
            if ($program->getCurrentBatchEnrollmentCount() >= 25 && $assignedBatch == $program->current_batch) {
                // Current batch is full, advance to next batch
                $program->advanceBatchIfFull();
                $assignedBatch = $program->current_batch;
            }

            // Enroll the trainee in the assigned batch
            $enrollment = $trainee->enrollInProgram(
                $validated['program_id'],
                $assignedBatch,
                $validated['enrollment_fee'] ?? null,
                $validated['maintain_scholarship'] ?? null
            );

            // Add any additional notes
            if ($validated['notes']) {
                $enrollment->update(['notes' => $validated['notes']]);
            }

            $message = "Trainee successfully enrolled in {$program->name} (Batch {$assignedBatch})";
            if ($assignedBatch > $program->current_batch - 1 && $program->getCurrentBatchEnrollmentCount() >= 25) {
                $message .= ". Previous batch was full (25 trainees), enrolled in next available batch.";
            }
            
            // Add scholarship message based on the choice made
            if ($trainee->scholarship_package && ($validated['maintain_scholarship'] ?? true)) {
                $message .= " (Fee exempted - scholarship maintained)";
            } elseif ($trainee->scholarship_package && !($validated['maintain_scholarship'] ?? true)) {
                $message .= " (Standard fees apply - scholarship not maintained for this program)";
            }

            return redirect()->back()->with('success', $message);

        } catch (\Exception $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    /**
     * Update enrollment status
     */
    public function updateStatus(Request $request, TraineeEnrollment $enrollment)
    {
        $validated = $request->validate([
            'status' => 'required|in:active,completed,dropped,pending',
            'completion_date' => 'nullable|date|required_if:status,completed',
            'notes' => 'nullable|string|max:500'
        ]);

        $updateData = [
            'status' => $validated['status'],
            'notes' => $validated['notes']
        ];

        if ($validated['status'] === 'completed') {
            $updateData['completion_date'] = $validated['completion_date'];
        }

        // Automatically set date_ended when status is changed to "dropped"
        if ($validated['status'] === 'dropped') {
            $updateData['date_ended'] = now();
        }

        $enrollment->update($updateData);

        return redirect()->back()->with('success', 'Enrollment status updated successfully!');
    }

    /**
     * Update payment status
     */
    public function updatePayment(Request $request, TraineeEnrollment $enrollment)
    {
        $validated = $request->validate([
            'payment_status' => 'required|in:paid,unpaid,partial',
            'payment_method' => 'nullable|string|max:255',
            'payment_reference' => 'nullable|string|max:255',
            'payment_notes' => 'nullable|string|max:500'
        ]);

        $wasActivated = false;
        $updateData = $validated;
        
        if ($validated['payment_status'] === 'paid') {
            $updateData['payment_date'] = now();
            
            // If payment is completed and trainee was pending due to payment, reactivate
            if ($enrollment->status === 'pending') {
                $updateData['status'] = 'active';
                $wasActivated = true;
            }
        } elseif ($validated['payment_status'] !== 'paid' && $enrollment->status === 'active') {
            // If payment becomes unpaid and enrollment is active, set to pending
            $updateData['status'] = 'pending';
        }

        $enrollment->update($updateData);

        $message = 'Payment status updated successfully!';
        if ($wasActivated) {
            $message = 'Payment completed and enrollment automatically activated!';
        } elseif ($validated['payment_status'] !== 'paid' && $enrollment->status === 'pending') {
            $message = 'Payment status updated and enrollment set to pending due to payment issue.';
        }

        // Also check if trainee has other eligibility for auto-enrollment
        $trainee = $enrollment->trainee;
        if ($trainee->status === 'active' && $trainee->payment_status === 'paid') {
            $trainee->handleAutoEnrollment();
        }

        return redirect()->back()->with('success', $message);
    }

    /**
     * Show trainee's enrollment history
     */
    public function history(Request $request, Trainee $trainee)
    {
        $enrollments = $trainee->enrollments()
            ->with('program')
            ->orderBy('created_at', 'desc')
            ->get();

        // Get available programs for new enrollment (exclude active enrollments and completed programs)
        $enrolledProgramIds = $trainee->enrollments()
            ->where('status', 'active')
            ->pluck('program_id')
            ->toArray();

        // Get programs trainee has already completed
        $completedProgramIds = $trainee->enrollments()
            ->where('status', 'completed')
            ->pluck('program_id')
            ->toArray();

        // Combine both lists to exclude from available programs
        $excludedProgramIds = array_unique(array_merge($enrolledProgramIds, $completedProgramIds));

        $availablePrograms = Program::where('status', 'active')
            ->whereNotIn('program_id', $excludedProgramIds)
            ->get(['program_id', 'name', 'description', 'duration', 'enrollment_fee']);

        return Inertia::render('Officer/TraineeEnrollmentHistory', [
            'trainee' => $trainee,
            'enrollments' => $enrollments,
            'availablePrograms' => $availablePrograms
        ]);
    }

    /**
     * Check if trainee can enroll in a program (considering prerequisites)
     */
    private function checkPrerequisites(Trainee $trainee, Program $program): bool
    {
        // TODO: Implement prerequisite checking logic:
        // - Check completed prerequisite programs
        // - Verify required certifications
        // - Validate age/education requirements
        
        return true; // Currently allows all enrollments
    }

    /**
     * Get available programs for a trainee (excluding already enrolled and completed programs)
     */
    public function getAvailablePrograms(Trainee $trainee)
    {
        $enrolledProgramIds = $trainee->enrollments()
            ->where('status', 'active')
            ->pluck('program_id')
            ->toArray();

        // Get programs trainee has already completed
        $completedProgramIds = $trainee->enrollments()
            ->where('status', 'completed')
            ->pluck('program_id')
            ->toArray();

        // Combine both lists to exclude from available programs
        $excludedProgramIds = array_unique(array_merge($enrolledProgramIds, $completedProgramIds));

        $availablePrograms = Program::where('status', 'active')
            ->whereNotIn('program_id', $excludedProgramIds)
            ->get(['program_id', 'name', 'description', 'duration', 'enrollment_fee']);

        return response()->json($availablePrograms);
    }
} 
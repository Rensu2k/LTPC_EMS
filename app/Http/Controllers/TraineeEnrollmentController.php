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
use App\Models\TraineeEnrollment;
use Inertia\Inertia;

class TraineeEnrollmentController extends Controller
{
    /**
     * Show enrollment form for existing trainee
     */
    public function create(Trainee $trainee)
    {
        $hasActiveEnrollment = $trainee
            ->enrollments()
            ->where("status", "active")
            ->exists();
        if ($hasActiveEnrollment) {
            return redirect()
                ->back()
                ->with(
                    "error",
                    "Trainee already has an active enrollment and cannot enroll in a new program. Complete or drop the active enrollment first.",
                );
        }

        $enrolledProgramIds = $trainee
            ->enrollments()
            ->where("status", "active")
            ->pluck("program_id")
            ->toArray();

        $completedProgramIds = $trainee
            ->enrollments()
            ->where("status", "completed")
            ->pluck("program_id")
            ->toArray();

        $excludedProgramIds = array_unique(
            array_merge($enrolledProgramIds, $completedProgramIds),
        );

        $availablePrograms = Program::where("status", "active")
            ->whereNotIn("program_id", $excludedProgramIds)
            ->get([
                "program_id",
                "name",
                "description",
                "duration",
                "enrollment_fee",
            ]);

        $enrollmentHistory = $trainee
            ->enrollments()
            ->with("program")
            ->orderBy("created_at", "desc")
            ->get();

        return Inertia::render("Officer/EnrollTrainee", [
            "trainee" => $trainee,
            "availablePrograms" => $availablePrograms,
            "enrollmentHistory" => $enrollmentHistory,
        ]);
    }

    /**
     * Enroll trainee in a new program
     */
    public function store(Request $request, Trainee $trainee)
    {
        if ($trainee->enrollments()->where("status", "active")->exists()) {
            return redirect()
                ->back()
                ->with(
                    "error",
                    "Trainee already has an active enrollment and cannot enroll in a new program.",
                );
        }

        $validated = $request->validate([
            "program_id" => "required|string|exists:programs,program_id",
            "batch" => "nullable|integer|min:1",
            "enrollment_fee" => "nullable|numeric|min:0",
            "notes" => "nullable|string|max:500",
            "maintain_scholarship" => "nullable|boolean",
        ]);

        try {
            $program = Program::find($validated["program_id"]);

            if ($trainee->hasCompletedProgram($validated["program_id"])) {
                return redirect()
                    ->back()
                    ->with(
                        "error",
                        "This trainee has already completed this program and cannot re-enroll.",
                    );
            }


            $assignedBatch =
                $validated["batch"] ?? $program->getNextAvailableBatch();

            $enrollment = $trainee->enrollInProgram(
                $validated["program_id"],
                $assignedBatch,
                $validated["enrollment_fee"] ?? null,
                $validated["maintain_scholarship"] ?? null,
            );

            if ($validated["notes"]) {
                $enrollment->update(["notes" => $validated["notes"]]);
            }

            $message = "Trainee successfully enrolled in {$program->name} (Batch {$assignedBatch})";
            if ($assignedBatch > $program->current_batch) {
                $message .=
                    ". Previous batch(es) were full (25 trainees each), enrolled in next available batch.";
            }

            if (
                $trainee->scholarship_package &&
                ($validated["maintain_scholarship"] ?? true)
            ) {
                $message .= " (Fee exempted - scholarship maintained)";
            } elseif (
                $trainee->scholarship_package &&
                !($validated["maintain_scholarship"] ?? true)
            ) {
                $message .=
                    " (Standard fees apply - scholarship not maintained for this program)";
            }

            return redirect()->back()->with("success", $message);
        } catch (\Exception $e) {
            return redirect()->back()->with("error", $e->getMessage());
        }
    }

    /**
     * Update enrollment status
     */
    public function updateStatus(
        Request $request,
        TraineeEnrollment $enrollment,
    ) {
        $validated = $request->validate([
            "status" => "required|in:active,completed,dropped,pending",
            "completion_date" => "nullable|date|required_if:status,completed",
            "notes" => "nullable|string|max:500",
        ]);

        $updateData = [
            "status" => $validated["status"],
            "notes" => $validated["notes"],
        ];

        if ($validated["status"] === "completed") {
            $updateData["completion_date"] = $validated["completion_date"];
        }

        if ($validated["status"] === "dropped") {
            $updateData["date_ended"] = now();
        }

        $enrollment->update($updateData);

        return redirect()
            ->back()
            ->with("success", "Enrollment status updated successfully!");
    }

    /**
     * Update payment status
     */
    public function updatePayment(
        Request $request,
        TraineeEnrollment $enrollment,
    ) {
        $validated = $request->validate([
            "payment_status" => "required|in:paid,unpaid,partial",
            "payment_method" => "nullable|string|max:255",
            "payment_reference" => "nullable|string|max:255",
            "payment_notes" => "nullable|string|max:500",
        ]);

        $wasActivated = false;
        $updateData = $validated;

        if ($validated["payment_status"] === "paid") {
            $updateData["payment_date"] = now();

            if ($enrollment->status === "pending") {
                $updateData["status"] = "active";
                $wasActivated = true;
            }
        } elseif (
            $validated["payment_status"] !== "paid" &&
            $enrollment->status === "active"
        ) {
            $updateData["status"] = "pending";
        }

        $enrollment->update($updateData);

        $message = "Payment status updated successfully!";
        if ($wasActivated) {
            $message =
                "Payment completed and enrollment automatically activated!";
        } elseif (
            $validated["payment_status"] !== "paid" &&
            $enrollment->status === "pending"
        ) {
            $message =
                "Payment status updated and enrollment set to pending due to payment issue.";
        }

        $trainee = $enrollment->trainee;
        if (
            $trainee->status === "active" &&
            $trainee->payment_status === "paid"
        ) {
            $trainee->handleAutoEnrollment();
        }

        return redirect()->back()->with("success", $message);
    }

    /**
     * Show trainee's enrollment history
     */
    public function history(Request $request, Trainee $trainee)
    {
        $enrollments = $trainee
            ->enrollments()
            ->with("program")
            ->orderBy("created_at", "desc")
            ->get();

        $enrolledProgramIds = $trainee
            ->enrollments()
            ->where("status", "active")
            ->pluck("program_id")
            ->toArray();

        $completedProgramIds = $trainee
            ->enrollments()
            ->where("status", "completed")
            ->pluck("program_id")
            ->toArray();

        $excludedProgramIds = array_unique(
            array_merge($enrolledProgramIds, $completedProgramIds),
        );

        $availablePrograms = Program::where("status", "active")
            ->whereNotIn("program_id", $excludedProgramIds)
            ->get([
                "program_id",
                "name",
                "description",
                "duration",
                "enrollment_fee",
            ]);

        return Inertia::render("Officer/TraineeEnrollmentHistory", [
            "trainee" => $trainee,
            "enrollments" => $enrollments,
            "availablePrograms" => $availablePrograms,
        ]);
    }

    /**
     * Check if trainee can enroll in a program (considering prerequisites)
     */
    private function checkPrerequisites(
        Trainee $trainee,
        Program $program,
    ): bool {

        return true; // Currently allows all enrollments
    }

    /**
     * Get available programs for a trainee (excluding already enrolled and completed programs)
     */
    public function getAvailablePrograms(Trainee $trainee)
    {
        $enrolledProgramIds = $trainee
            ->enrollments()
            ->where("status", "active")
            ->pluck("program_id")
            ->toArray();

        $completedProgramIds = $trainee
            ->enrollments()
            ->where("status", "completed")
            ->pluck("program_id")
            ->toArray();

        $excludedProgramIds = array_unique(
            array_merge($enrolledProgramIds, $completedProgramIds),
        );

        $availablePrograms = Program::where("status", "active")
            ->whereNotIn("program_id", $excludedProgramIds)
            ->get([
                "program_id",
                "name",
                "description",
                "duration",
                "enrollment_fee",
            ]);

        return response()->json($availablePrograms);
    }
}

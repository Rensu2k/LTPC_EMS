<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Trainee;
use App\Models\Course;
use Inertia\Inertia;

class TraineeController extends Controller
{
    public function index()
    {
        $trainees = Trainee::latest()->get();
        $courses = Course::where('status', 'active')->get(['course_id', 'name', 'description', 'duration']);
        
        return Inertia::render('Officer/Trainees', [
            'trainees' => $trainees,
            'courses' => $courses
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'last_name' => 'required|string|max:255',
            'first_name' => 'required|string|max:255',
            'middle_name' => 'nullable|string|max:255',
            'extension' => 'nullable|string|max:10',
            'course_qualification' => 'required|string|max:255',
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
            'status' => 'nullable|in:active,completed,dropped,suspended',
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
            // No scholarship - automatically unpaid
            $validated['payment_status'] = 'unpaid';
        }

        // Enrollment validation: Can only be active if payment is paid
        if (isset($validated['status']) && $validated['status'] === 'active' && $validated['payment_status'] !== 'paid') {
            return redirect()->back()->with('error', 'Trainee cannot be enrolled (active status) until payment is completed. Current payment status: ' . ucfirst($validated['payment_status']));
        }

        // Auto-assign batch based on course enrollment
        $course = Course::where('name', $validated['course_qualification'])->first();
        if ($course) {
            $validated['batch'] = $course->getNextBatch();
        } else {
            $validated['batch'] = 1; // Default to batch 1 if course not found
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
        $courses = Course::where('status', 'active')->get(['course_id', 'name', 'description', 'duration']);
        
        return Inertia::render('Officer/EditTrainee', [
            'trainee' => $trainee,
            'courses' => $courses
        ]);
    }

    public function update(Request $request, Trainee $trainee)
    {
        $validated = $request->validate([
            'last_name' => 'required|string|max:255',
            'first_name' => 'required|string|max:255',
            'middle_name' => 'nullable|string|max:255',
            'extension' => 'nullable|string|max:10',
            'course_qualification' => 'required|string|max:255',
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
            'status' => 'nullable|in:active,completed,dropped,suspended',
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
            // No scholarship - automatically unpaid
            $validated['payment_status'] = 'unpaid';
        }

        // Enrollment validation: Can only be active if payment is paid
        if (isset($validated['status']) && $validated['status'] === 'active' && $validated['payment_status'] !== 'paid') {
            return redirect()->back()->with('error', 'Trainee cannot be enrolled (active status) until payment is completed. Current payment status: ' . ucfirst($validated['payment_status']));
        }

        // If payment status becomes unpaid and trainee is active, suspend them
        if ($validated['payment_status'] !== 'paid' && $trainee->status === 'active') {
            $validated['status'] = 'suspended';
            return redirect()->back()->with('warning', 'Trainee has been suspended due to payment status change. Payment status: ' . ucfirst($validated['payment_status']));
        }

        // If course qualification changed, reassign batch
        if ($validated['course_qualification'] !== $trainee->course_qualification) {
            $course = Course::where('name', $validated['course_qualification'])->first();
            if ($course) {
                $validated['batch'] = $course->getNextBatch();
            } else {
                $validated['batch'] = 1; // Default to batch 1 if course not found
            }
        }

        $trainee->update($validated);

        $batchMessage = isset($validated['batch']) && $validated['batch'] == 2 ? ' (Reassigned to Batch 2 due to enrollment capacity)' : '';
        return redirect()->back()->with('success', 'Trainee updated successfully!' . $batchMessage);
    }

    public function destroy(Trainee $trainee)
    {
        // Check if trainee can be deleted (only active trainees)
        if ($trainee->status !== 'active') {
            return redirect()->back()->with('error', 'Cannot delete trainee with ' . ucfirst($trainee->status) . ' status. Only active trainees can be deleted.');
        }

        $trainee->delete();

        return redirect()->back()->with('success', 'Trainee archived successfully!');
    }

    /**
     * Admin methods for trainee management
     */
    public function adminIndex()
    {
        $trainees = Trainee::latest()->get();
        $courses = Course::where('status', 'active')->get(['course_id', 'name', 'description', 'duration']);
        
        return Inertia::render('Admin/Trainees', [
            'trainees' => $trainees,
            'courses' => $courses
        ]);
    }

    public function adminStore(Request $request)
    {
        $validated = $request->validate([
            'last_name' => 'required|string|max:255',
            'first_name' => 'required|string|max:255',
            'middle_name' => 'nullable|string|max:255',
            'extension' => 'nullable|string|max:10',
            'course_qualification' => 'required|string|max:255',
            'uli_number' => 'nullable|string|max:255',
            'entry_date' => 'nullable|date',
            'email_facebook' => 'nullable|string|max:255',
            'contact_number' => 'nullable|string|max:255',
            'nationality' => 'nullable|string|max:255',
            'scholarship_package' => 'nullable|string|max:255',
            'status' => 'nullable|in:active,completed,dropped,suspended',
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
            // No scholarship - automatically unpaid
            $validated['payment_status'] = 'unpaid';
        }

        // Enrollment validation: Can only be active if payment is paid
        if (isset($validated['status']) && $validated['status'] === 'active' && $validated['payment_status'] !== 'paid') {
            return redirect()->back()->with('error', 'Trainee cannot be enrolled (active status) until payment is completed. Current payment status: ' . ucfirst($validated['payment_status']));
        }

        // Auto-assign batch based on course enrollment
        $course = Course::where('name', $validated['course_qualification'])->first();
        if ($course) {
            $validated['batch'] = $course->getNextBatch();
        } else {
            $validated['batch'] = 1; // Default to batch 1 if course not found
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
            'course_qualification' => 'required|string|max:255',
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
            'status' => 'nullable|in:active,completed,dropped,suspended',
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
            // No scholarship - automatically unpaid
            $validated['payment_status'] = 'unpaid';
        }

        // Enrollment validation: Can only be active if payment is paid
        if (isset($validated['status']) && $validated['status'] === 'active' && $validated['payment_status'] !== 'paid') {
            return redirect()->back()->with('error', 'Trainee cannot be enrolled (active status) until payment is completed. Current payment status: ' . ucfirst($validated['payment_status']));
        }

        // If payment status becomes unpaid and trainee is active, suspend them
        if ($validated['payment_status'] !== 'paid' && $trainee->status === 'active') {
            $validated['status'] = 'suspended';
            return redirect()->back()->with('warning', 'Trainee has been suspended due to payment status change. Payment status: ' . ucfirst($validated['payment_status']));
        }

        // If course qualification changed, reassign batch
        if ($validated['course_qualification'] !== $trainee->course_qualification) {
            $course = Course::where('name', $validated['course_qualification'])->first();
            if ($course) {
                $validated['batch'] = $course->getNextBatch();
            } else {
                $validated['batch'] = 1; // Default to batch 1 if course not found
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
            'status' => 'required|in:active,completed,dropped,suspended'
        ]);

        $trainee->update($validated);

        return redirect()->back()->with('success', 'Trainee status updated successfully!');
    }
}

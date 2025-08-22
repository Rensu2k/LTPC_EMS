<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Employment;
use App\Models\Trainee;
use App\Models\Assessment;
use Inertia\Inertia;

class EmploymentController extends Controller
{
    public function index(Request $request)
    {
        $perPage = $request->get('per_page', 10); // Default to 10 items per page
        $search = $request->get('search', '');
        $status = $request->get('status', '');
        $company = $request->get('company', '');

        $query = Employment::with(['trainee', 'assessment']);

        // Apply search filter if provided
        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('company_name', 'like', "%{$search}%")
                  ->orWhere('position_title', 'like', "%{$search}%")
                  ->orWhereHas('trainee', function ($traineeQuery) use ($search) {
                      $traineeQuery->where('first_name', 'like', "%{$search}%")
                                  ->orWhere('last_name', 'like', "%{$search}%");
                  });
            });
        }

        // Apply status filter if provided
        if ($status && $status !== 'All Statuses') {
            $query->where('status', $status);
        }

        // Apply company filter if provided
        if ($company && $company !== 'All Companies') {
            $query->where('company_name', $company);
        }

        $employments = $query->latest()
            ->paginate($perPage)
            ->through(function ($employment) {
                return [
                    'id' => $employment->id,
                    'trainee_id' => $employment->trainee_id,
                    'trainee' => $employment->trainee ? [
                        'id' => $employment->trainee->id,
                        'first_name' => $employment->trainee->first_name,
                        'last_name' => $employment->trainee->last_name,
                        'full_name' => $employment->trainee->first_name . ' ' . $employment->trainee->last_name,
                    ] : null,
                    'company_name' => $employment->company_name,
                    'position_title' => $employment->position_title,
                    'job_description' => $employment->job_description,
                    'employment_date' => $employment->employment_date,
                    'status' => $employment->status,
                    'notes' => $employment->notes,
                    'contact_person' => $employment->contact_person,
                    'contact_email' => $employment->contact_email,
                    'contact_phone' => $employment->contact_phone,
                    'salary_range' => $employment->salary_range,
                    'employment_type' => $employment->employment_type,
                    'is_auto_generated' => $employment->is_auto_generated,
                    'assessment_id' => $employment->assessment_id,
                    'assessment' => $employment->assessment ? [
                        'id' => $employment->assessment->id,
                        'title' => $employment->assessment->title,
                        'program_name' => $employment->assessment->program->name ?? 'N/A',
                    ] : null,
                    'created_at' => $employment->created_at,
                    'updated_at' => $employment->updated_at,
                ];
            });

        $trainees = Trainee::where('status', 'active')
            ->get(['id', 'first_name', 'last_name'])
            ->map(function ($trainee) {
                return [
                    'id' => $trainee->id,
                    'name' => $trainee->first_name . ' ' . $trainee->last_name,
                ];
            });

        return Inertia::render('Admin/Employments', [
            'employment_referrals' => $employments,
            'trainees' => $trainees,
            'filters' => [
                'search' => $search,
                'status' => $status,
                'company' => $company,
                'per_page' => $perPage,
            ]
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'trainee_id' => 'required|exists:trainees,id',
            'company_name' => 'required|string|max:255',
            'position_title' => 'required|string|max:255',
            'job_description' => 'nullable|string',
            'employment_date' => 'required|date',
            'status' => 'required|in:employed,not_yet_employed',
            'notes' => 'nullable|string',
            'contact_person' => 'nullable|string|max:255',
            'contact_email' => 'nullable|email|max:255',
            'contact_phone' => 'nullable|string|max:255',
            'salary_range' => 'nullable|string|max:255',
            'employment_type' => 'nullable|string|max:255',
        ]);

        Employment::create([
            'trainee_id' => $request->trainee_id,
            'company_name' => $request->company_name,
            'position_title' => $request->position_title,
            'job_description' => $request->job_description,
            'employment_date' => $request->employment_date,
            'status' => $request->status,
            'notes' => $request->notes,
            'contact_person' => $request->contact_person,
            'contact_email' => $request->contact_email,
            'contact_phone' => $request->contact_phone,
            'salary_range' => $request->salary_range,
            'employment_type' => $request->employment_type,
            'is_auto_generated' => false,
        ]);

        return redirect()->back()->with('success', 'Employment record created successfully.');
    }

    public function update(Request $request, $id)
    {
        $employment = Employment::findOrFail($id);

        $request->validate([
            'trainee_id' => 'required|exists:trainees,id',
            'company_name' => 'required|string|max:255',
            'position_title' => 'required|string|max:255',
            'job_description' => 'nullable|string',
            'employment_date' => 'required|date',
            'status' => 'required|in:employed,not_yet_employed',
            'notes' => 'nullable|string',
            'contact_person' => 'nullable|string|max:255',
            'contact_email' => 'nullable|email|max:255',
            'contact_phone' => 'nullable|string|max:255',
            'salary_range' => 'nullable|string|max:255',
            'employment_type' => 'nullable|string|max:255',
        ]);

        $employment->update([
            'trainee_id' => $request->trainee_id,
            'company_name' => $request->company_name,
            'position_title' => $request->position_title,
            'job_description' => $request->job_description,
            'employment_date' => $request->employment_date,
            'status' => $request->status,
            'notes' => $request->notes,
            'contact_person' => $request->contact_person,
            'contact_email' => $request->contact_email,
            'contact_phone' => $request->contact_phone,
            'salary_range' => $request->salary_range,
            'employment_type' => $request->employment_type,
        ]);

        return redirect()->back()->with('success', 'Employment record updated successfully.');
    }

    public function destroy($id)
    {
        $employment = Employment::findOrFail($id);
        $employment->delete();

        return redirect()->back()->with('success', 'Employment record deleted successfully.');
    }

    /**
     * Create employment record automatically when assessment is marked as competent
     */
    public static function createFromCompetentAssessment(Assessment $assessment)
    {
        // Only create employment record for enrolled trainees (not external applicants)
        if ($assessment->applicant_type !== 'enrolled_trainee' || !$assessment->trainee_id) {
            return null;
        }

        // Check if employment record already exists for this trainee and assessment
        $existingEmployment = Employment::where('trainee_id', $assessment->trainee_id)
            ->where('assessment_id', $assessment->id)
            ->first();

        if ($existingEmployment) {
            return $existingEmployment;
        }

        // Get trainee information
        $trainee = $assessment->trainee;
        $program = $assessment->program;

        // Create employment record
        return Employment::create([
            'trainee_id' => $assessment->trainee_id,
            'company_name' => 'To be determined', // Default value, can be updated later
            'position_title' => $program ? $program->name . ' Graduate' : 'Program Graduate',
            'job_description' => 'Graduate from ' . ($program ? $program->name : 'training program') . ' with competent assessment result.',
            'employment_date' => $assessment->assessment_date,
            'status' => 'not_yet_employed', // Default status, can be updated when actually employed
            'notes' => 'Automatically generated from competent assessment result. Assessment: ' . $assessment->title,
            'is_auto_generated' => true,
            'assessment_id' => $assessment->id,
        ]);
    }
}

<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Course;
use App\Models\Trainer;
use Inertia\Inertia;

class CourseController extends Controller
{
    /**
     * Display a listing of the resource for officers.
     */
    public function index()
    {
        $courses = Course::latest()->get()->map(function ($course) {
            return [
                'course_id' => $course->course_id,
                'name' => $course->name,
                'description' => $course->description,
                'duration' => $course->duration,
                'status' => $course->status,
                'assigned_trainers' => $course->assigned_trainers,
                'enrollments' => $course->enrollment_count,
                'max_enrollments' => $course->max_enrollments,
                'enrollment_fee' => $course->enrollment_fee,
                'start_date' => $course->start_date,
                'end_date' => $course->end_date,
                'created_at' => $course->created_at,
            ];
        });

        $trainers = Trainer::where('status', 'active')->get()->map(function ($trainer) {
            return [
                'id' => $trainer->id,
                'name' => $trainer->full_name,
                'expertise' => $trainer->expertise,
            ];
        });

        return Inertia::render('Officer/Courses', [
            'courses' => $courses,
            'trainers' => $trainers
        ]);
    }

    /**
     * Display a listing of the resource for admin.
     */
    public function adminIndex()
    {
        $courses = Course::latest()->get()->map(function ($course) {
            return [
                'course_id' => $course->course_id,
                'name' => $course->name,
                'description' => $course->description,
                'duration' => $course->duration,
                'prerequisites' => $course->prerequisites,
                'max_students' => $course->max_enrollments,
                'enrollment_fee' => $course->enrollment_fee,
                'status' => $course->status,
                'created_at' => $course->created_at,
            ];
        });

        return Inertia::render('Admin/Courses', [
            'courses' => $courses
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
     * Store a newly created resource in storage (Officer).
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:courses,name',
            'description' => 'nullable|string',
            'duration' => 'required|string|max:255',
            'max_enrollments' => 'nullable|integer|min:1|max:100',
            'enrollment_fee' => 'nullable|numeric|min:0',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
        ]);

        $course = Course::create($validated);

        return redirect()->back()->with('success', 'Course created successfully! Click the edit button to assign trainers to this course.');
    }

    /**
     * Store a newly created resource in storage (Admin).
     */
    public function adminStore(Request $request)
    {
        $validated = $request->validate([
            'course_id' => 'nullable|string|max:50|unique:courses,course_id',
            'name' => 'required|string|max:255|unique:courses,name',
            'description' => 'nullable|string',
            'duration' => 'required|string|max:255',
            'prerequisites' => 'nullable|string',
            'max_students' => 'nullable|integer|min:1|max:100',
            'enrollment_fee' => 'nullable|numeric|min:0',
            'status' => 'nullable|in:active,inactive',
        ]);

        // Generate course_id if not provided
        if (empty($validated['course_id'])) {
            $validated['course_id'] = 'COURSE-' . strtoupper(substr(preg_replace('/[^A-Za-z0-9]/', '', $validated['name']), 0, 8)) . '-' . time();
        }

        // Map admin fields to database fields
        $courseData = [
            'course_id' => $validated['course_id'],
            'name' => $validated['name'],
            'description' => $validated['description'],
            'duration' => $validated['duration'],
            'prerequisites' => $validated['prerequisites'] ?? null,
            'max_enrollments' => $validated['max_students'] ?? null,
            'enrollment_fee' => $validated['enrollment_fee'] ?? null,
            'status' => $validated['status'] ?? 'active',
        ];

        $course = Course::create($courseData);

        return redirect()->back()->with('success', 'Course created successfully! Click the edit button to assign trainers to this course.');
    }

    /**
     * Display the specified resource.
     */
    public function show(Course $course)
    {
        return redirect()->route('officer.courses');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Course $course)
    {
        // Filter trainers to show only those whose expertise matches the course name
        $trainers = Trainer::where('status', 'active')
            ->where(function ($query) use ($course) {
                $query->whereRaw('LOWER(expertise) LIKE ?', ['%' . strtolower($course->name) . '%'])
                      ->orWhereRaw('LOWER(?) LIKE CONCAT("%", LOWER(expertise), "%")', [$course->name]);
            })
            ->get()
            ->map(function ($trainer) {
                return [
                    'id' => $trainer->id,
                    'name' => $trainer->full_name,
                    'expertise' => $trainer->expertise,
                ];
            });

        return Inertia::render('Officer/EditCourse', [
            'course' => $course,
            'trainers' => $trainers
        ]);
    }

    /**
     * Update the specified resource in storage (Officer).
     */
    public function update(Request $request, Course $course)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:courses,name,' . $course->course_id,
            'description' => 'nullable|string',
            'duration' => 'required|string|max:255',
            'assigned_trainers' => 'nullable|array',
            'assigned_trainers.*' => 'exists:trainers,id',
            'status' => 'nullable|in:active,inactive',
            'max_enrollments' => 'nullable|integer|min:1|max:100',
            'enrollment_fee' => 'nullable|numeric|min:0',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
        ]);

        $course->update($validated);

        return redirect()->back()->with('success', 'Course updated successfully!');
    }

    /**
     * Update the specified resource in storage (Admin).
     */
    public function adminUpdate(Request $request, Course $course)
    {
        $validated = $request->validate([
            'course_id' => 'nullable|string|max:50|unique:courses,course_id,' . $course->course_id,
            'name' => 'required|string|max:255|unique:courses,name,' . $course->course_id,
            'description' => 'nullable|string',
            'duration' => 'required|string|max:255',
            'prerequisites' => 'nullable|string',
            'max_students' => 'nullable|integer|min:1|max:100',
            'enrollment_fee' => 'nullable|numeric|min:0',
            'status' => 'nullable|in:active,inactive',
        ]);

        // Map admin fields to database fields
        $courseData = [
            'course_id' => $validated['course_id'] ?? $course->course_id,
            'name' => $validated['name'],
            'description' => $validated['description'],
            'duration' => $validated['duration'],
            'prerequisites' => $validated['prerequisites'] ?? null,
            'max_enrollments' => $validated['max_students'] ?? null,
            'enrollment_fee' => $validated['enrollment_fee'] ?? null,
            'status' => $validated['status'] ?? 'active',
        ];

        $course->update($courseData);

        return redirect()->back()->with('success', 'Course updated successfully!');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Course $course)
    {
        // Check if course has enrolled trainees
        $enrollmentCount = $course->enrollment_count;
        if ($enrollmentCount > 0) {
            return redirect()->back()->with('error', 'Cannot delete course with active enrollments. Please transfer or complete all trainees first.');
        }

        $course->delete();

        return redirect()->back()->with('success', 'Course deleted successfully!');
    }

    /**
     * Remove the specified resource from storage (Admin).
     */
    public function adminDestroy(Course $course)
    {
        // Check if course has enrolled trainees
        $enrollmentCount = $course->enrollment_count;
        if ($enrollmentCount > 0) {
            return redirect()->back()->with('error', 'Cannot delete course with active enrollments. Please transfer or complete all trainees first.');
        }

        $course->delete();

        return redirect()->back()->with('success', 'Course deleted successfully!');
    }
}

<?php

namespace App\Http\Controllers;

use App\Models\Registration;
use App\Models\Section;
use Illuminate\Http\Request;

class SectionController extends Controller
{
    public function index(Request $request)
    {
        $selectedSection = $request->input('section');
        $search = $request->input('search');
        $unassignedOnly = $request->boolean('unassigned_only');

        $sections = Section::orderBy('grade_level')->orderBy('name')->get();

        $studentsQuery = Registration::query()
            ->select('id', 'first_name', 'middle_name', 'last_name', 'section', 'adviser', 'photo_path');

        // Filter by specific section or fetch only unassigned students
        if ($selectedSection) {
            $studentsQuery->where('section', $selectedSection);
        } elseif ($unassignedOnly) {
            $studentsQuery->whereNull('section')->orWhere('section', '');
        }

        // Server-side search filter
        if ($search) {
            $studentsQuery->where(function ($q) use ($search) {
                $q->where('first_name', 'like', "%{$search}%")
                  ->orWhere('last_name', 'like', "%{$search}%");
            });
        }

        // Limit maximum records sent per request to prevent payload bloat
        $students = $studentsQuery->orderBy('last_name')->take(200)->get();

        return inertia('Dashboard/Sectioning', [
            'sections' => $sections,
            'students' => $students,
            'filters' => [
                'section' => $selectedSection ?? '',
                'search' => $search ?? '',
                'unassigned_only' => $unassignedOnly,
            ],
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'grade_level' => 'required|string|max:50',
            'adviser_name' => 'nullable|string|max:255',
            'school_year' => 'nullable|string|max:50',
            'room' => 'nullable|string|max:50',
        ]);

        Section::create([
            'name' => $validated['name'],
            'grade_level' => $validated['grade_level'],
            'adviser_name' => $validated['adviser_name'] ?? null,
            'school_year' => $validated['school_year'] ?? '2025-2026',
            'room' => $validated['room'] ?? null,
            'capacity' => 40,
            'enrolled_count' => 0,
        ]);

        return back()->with('success', 'Section created successfully.');
    }

    public function updateStudents(Request $request, Section $section)
    {
        $validated = $request->validate([
            'student_ids' => 'nullable|array',
            'student_ids.*' => 'exists:registrations,id',
        ]);

        $assignedIds = $validated['student_ids'] ?? [];

        // Unassign students previously in this section who were removed
        Registration::where('section', $section->name)
            ->whereNotIn('id', $assignedIds)
            ->update([
                'section' => null,
                'adviser' => null,
            ]);

        // Assign selected students to this section and set their adviser
        if (!empty($assignedIds)) {
            Registration::whereIn('id', $assignedIds)
                ->update([
                    'section' => $section->name,
                    'adviser' => $section->adviser_name,
                ]);
        }

        // Sync enrolled_count on the section
        $section->update([
            'enrolled_count' => Registration::where('section', $section->name)->count(),
        ]);

        return back()->with('success', 'Students updated successfully.');
    }
}

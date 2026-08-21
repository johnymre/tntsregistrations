<?php

namespace App\Http\Controllers;

use App\Models\Registration;
use App\Models\Section;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class SectionController extends Controller
{
    public function index(): Response
    {
        $sections = Section::query()
            ->orderBy('grade_level')
            ->orderBy('name')
            ->get();

        $students = Registration::query()
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->get();

        return Inertia::render('Dashboard/Sectioning', [
            'sections' => $sections,
            'students' => $students,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
            ],
            'school_year' => [
                'nullable',
                'string',
                'max:255',
            ],
            'grade_level' => [
                'required',
                'string',
                'max:255',
            ],
            'strand' => [
                'nullable',
                'string',
                'max:255',
            ],
            'adviser_name' => [
                'nullable',
                'string',
                'max:255',
            ],
            'room' => [
                'nullable',
                'string',
                'max:255',
            ],
            'capacity' => [
                'required',
                'integer',
                'min:1',
            ],
        ]);

        Section::create([
            'name' => $validated['name'],
            'school_year' => $validated['school_year'] ?? null,
            'grade_level' => $validated['grade_level'],
            'strand' => $validated['strand'] ?? null,
            'adviser_name' => $validated['adviser_name'] ?? null,
            'room' => $validated['room'] ?? null,
            'capacity' => $validated['capacity'],
            'enrolled_count' => 0,
        ]);

        return back()->with('flash', [
            'success' => 'Section created successfully!',
        ]);
    }

    public function updateStudents(
        Request $request,
        int $section
    ): RedirectResponse {
        $validated = $request->validate([
            'student_ids' => [
                'required',
                'array',
            ],
            'student_ids.*' => [
                'integer',
                'exists:registrations,id',
            ],
        ]);

        $sectionModel = Section::query()->findOrFail($section);

        $studentIds = $validated['student_ids'];

        if (count($studentIds) > $sectionModel->capacity) {
            return back()->withErrors([
                'student_ids' => 'Selected students exceed the section capacity.',
            ]);
        }

        DB::transaction(function () use ($sectionModel, $studentIds) {
            Registration::query()
                ->where('section', $sectionModel->name)
                ->update([
                    'section' => null,
                    'adviser' => null,
                ]);

            Registration::query()
                ->whereIn('id', $studentIds)
                ->update([
                    'section' => $sectionModel->name,
                    'adviser' => $sectionModel->adviser_name,
                    'school_year' => $sectionModel->school_year,
                ]);

            $sectionModel->update([
                'enrolled_count' => count($studentIds),
            ]);
        });

        return back()->with('flash', [
            'success' => 'Students assigned successfully!',
        ]);
    }
}

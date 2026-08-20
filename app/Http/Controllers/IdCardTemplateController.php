<?php

namespace App\Http\Controllers;

use App\Models\DoneId;
use App\Models\IdCardTemplate;
use App\Models\Registration;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class IdCardTemplateController extends Controller
{
    private function template(): IdCardTemplate
    {
        return IdCardTemplate::first() ?? IdCardTemplate::create([
            'front_layout' => [],
            'back_layout' => [],
        ]);
    }

    public function edit(Request $request)
    {
        $template = $this->template();
        $doneStudentIds = DoneId::pluck('student_id')->map(fn($id) => (int)$id)->toArray();

        // 1. Fetch ALL distinct Grade Levels from sections table
        $allGrades = DB::table('sections')
            ->whereNotNull('grade_level')
            ->where('grade_level', '!=', '')
            ->distinct()
            ->orderBy('grade_level')
            ->pluck('grade_level');

        // Default to first grade if not provided or invalid
        $selectedGrade = $request->input('grade');
        if (!$selectedGrade || !$allGrades->contains($selectedGrade)) {
            $selectedGrade = $allGrades->first() ?? '';
        }

        // 2. Fetch ALL Sections belonging to the selected Grade Level
        $availableSections = DB::table('sections')
            ->where('grade_level', $selectedGrade)
            ->orderBy('name')
            ->pluck('name');

        // Default to first section of that grade
        $selectedSection = $request->input('section');
        if (!$selectedSection || !$availableSections->contains($selectedSection)) {
            $selectedSection = $availableSections->first() ?? '';
        }

        $selectedStatus = $request->input('status', 'pending');

        // 3. Query students ONLY for the active section
        $baseQuery = Registration::query()
            ->leftJoin('sections', 'registrations.section', '=', 'sections.name')
            ->select([
                'registrations.id',
                'registrations.first_name',
                'registrations.middle_name',
                'registrations.last_name',
                'registrations.school_year',
                'registrations.section',
                'registrations.adviser',
                'registrations.address',
                'registrations.birthday',
                'registrations.parent_name',
                'registrations.parent_address',
                'registrations.parent_contact_number',
                'registrations.photo_path',
                'sections.grade_level',
            ])
            ->where('registrations.section', $selectedSection);

        // Calculate status counts for the selected section
        $sectionStudentIds = (clone $baseQuery)->pluck('registrations.id')->toArray();
        $completedCount = count(array_intersect($sectionStudentIds, $doneStudentIds));
        $totalCount = count($sectionStudentIds);
        $pendingCount = $totalCount - $completedCount;

        $query = clone $baseQuery;

        // Filter by active status tab
        if ($selectedStatus === 'pending') {
            $query->whereNotIn('registrations.id', $doneStudentIds);
        } elseif ($selectedStatus === 'completed') {
            $query->whereIn('registrations.id', $doneStudentIds);
        }

        // Search filter
        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('registrations.first_name', 'like', "%{$search}%")
                  ->orWhere('registrations.last_name', 'like', "%{$search}%");
            });
        }

        $students = $query->orderBy('registrations.last_name')->get();

        return inertia('Dashboard/IdMakerApp', [
            'template' => [
                'front_image_url' => $template->front_image_url,
                'back_image_url' => $template->back_image_url,
                'front_layout' => $template->front_layout ?? [],
                'back_layout' => $template->back_layout ?? [],
            ],
            'sample' => $students->first() ?? Registration::first(),
            'students' => $students,
            'done_student_ids' => $doneStudentIds,
            'grades' => $allGrades,
            'sections' => $availableSections,
            'filters' => [
                'grade' => $selectedGrade,
                'section' => $selectedSection,
                'status' => $selectedStatus,
                'search' => $request->input('search', ''),
            ],
            'counts' => [
                'pending' => $pendingCount,
                'completed' => $completedCount,
                'total' => $totalCount,
            ],
        ]);
    }

    public function update(Request $request)
    {
        $validated = $request->validate([
            'front_layout' => 'array',
            'back_layout' => 'array',
        ]);

        $this->template()->update($validated);

        return back();
    }

    public function markDone(Request $request)
    {
        $validated = $request->validate([
            'student_ids' => 'required|array',
            'student_ids.*' => 'exists:registrations,id',
        ]);

        foreach ($validated['student_ids'] as $studentId) {
            DoneId::firstOrCreate(['student_id' => $studentId]);
        }

        return back()->with('success', 'IDs marked as done.');
    }

    public function unmarkDone(Request $request)
    {
        $validated = $request->validate([
            'student_ids' => 'required|array',
            'student_ids.*' => 'exists:registrations,id',
        ]);

        DoneId::whereIn('student_id', $validated['student_ids'])->delete();

        return back()->with('success', 'Selected students re-enabled for reprint.');
    }

    public function uploadBackground(Request $request, string $side)
    {
        abort_unless(in_array($side, ['front', 'back']), 404);

        $request->validate([
            'image' => 'required|image|mimes:jpg,jpeg,png,webp|max:8192',
        ]);

        $template = $this->template();
        $oldPath = $side === 'front' ? $template->front_image_path : $template->back_image_path;

        if ($oldPath) {
            Storage::disk('public')->delete($oldPath);
        }

        $photo = $request->file('image');
        $manager = \Intervention\Image\ImageManager::usingDriver(\Intervention\Image\Drivers\Gd\Driver::class);
        $image = $manager->decode($photo->getRealPath());
        $image->scaleDown(width: 1200);
        $encoded = $image->encodeUsingFileExtension('jpg', quality: 85);

        $filename = $side . '-' . uniqid() . '.jpg';
        $path = 'id-templates/' . $filename;
        Storage::disk('public')->put($path, (string) $encoded);

        $template->update([$side . '_image_path' => $path]);

        return back();
    }

    public function print(Request $request)
    {
        $template = $this->template();
        $doneStudentIds = DoneId::pluck('student_id')->toArray();

        $query = Registration::query()
            ->leftJoin('sections', 'registrations.section', '=', 'sections.name')
            ->select(['registrations.*', 'sections.grade_level'])
            ->orderBy('registrations.last_name')
            ->whereNotIn('registrations.id', $doneStudentIds);

        if ($request->has('ids') && !empty($request->query('ids'))) {
            $ids = explode(',', $request->query('ids'));
            $query->whereIn('registrations.id', $ids);
        }

        $students = $query->get();

        return inertia('Dashboard/IdMakerPrint', [
            'template' => [
                'front_image_url' => $template->front_image_url,
                'back_image_url' => $template->back_image_url,
                'front_layout' => $template->front_layout ?? [],
                'back_layout' => $template->back_layout ?? [],
            ],
            'students' => $students,
        ]);
    }
}

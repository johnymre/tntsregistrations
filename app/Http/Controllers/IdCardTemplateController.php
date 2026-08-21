<?php

namespace App\Http\Controllers;

use App\Models\DoneId;
use App\Models\IdCardTemplate;
use App\Models\Registration;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Inertia\Response;

class IdCardTemplateController extends Controller
{
    private function template(): IdCardTemplate
    {
        return IdCardTemplate::first() ?? IdCardTemplate::create([
            'front_layout' => [],
            'back_layout' => [],
        ]);
    }

    private function temporaryUrl(?string $path): ?string
    {
        if (! $path) {
            return null;
        }

        return Storage::disk('s3')->temporaryUrl(
            $path,
            now()->addMinutes(60),
        );
    }

    private function studentPhotoUrl(?string $path): ?string
    {
        if (! $path) {
            return null;
        }

        return Storage::disk('s3')->temporaryUrl(
            $path,
            now()->addMinutes(60),
        );
    }

    public function edit(Request $request): Response
    {
        $template = $this->template();

        $doneStudentIds = DoneId::pluck('student_id')
            ->map(fn ($id) => (int) $id)
            ->toArray();

        $allGrades = DB::table('sections')
            ->whereNotNull('grade_level')
            ->where('grade_level', '!=', '')
            ->distinct()
            ->orderBy('grade_level')
            ->pluck('grade_level');

        $selectedGrade = $request->input('grade');

        if (! $selectedGrade || ! $allGrades->contains($selectedGrade)) {
            $selectedGrade = $allGrades->first() ?? '';
        }

        $availableSections = DB::table('sections')
            ->where('grade_level', $selectedGrade)
            ->orderBy('name')
            ->pluck('name');

        $selectedSection = $request->input('section');

        if (
            ! $selectedSection
            || ! $availableSections->contains($selectedSection)
        ) {
            $selectedSection = $availableSections->first() ?? '';
        }

        $selectedStatus = $request->input('status', 'pending');

        $baseQuery = Registration::query()
            ->leftJoin(
                'sections',
                'registrations.section',
                '=',
                'sections.name'
            )
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

        $sectionStudentIds = (clone $baseQuery)
            ->pluck('registrations.id')
            ->toArray();

        $completedCount = count(
            array_intersect($sectionStudentIds, $doneStudentIds)
        );

        $totalCount = count($sectionStudentIds);
        $pendingCount = $totalCount - $completedCount;

        $query = clone $baseQuery;

        if ($selectedStatus === 'pending') {
            $query->whereNotIn(
                'registrations.id',
                $doneStudentIds
            );
        } elseif ($selectedStatus === 'completed') {
            $query->whereIn(
                'registrations.id',
                $doneStudentIds
            );
        }

        if ($request->filled('search')) {
            $search = (string) $request->input('search');

            $query->where(function ($query) use ($search) {
                $query
                    ->where(
                        'registrations.first_name',
                        'ilike',
                        "%{$search}%"
                    )
                    ->orWhere(
                        'registrations.middle_name',
                        'ilike',
                        "%{$search}%"
                    )
                    ->orWhere(
                        'registrations.last_name',
                        'ilike',
                        "%{$search}%"
                    );
            });
        }

        $students = $query
            ->orderBy('registrations.last_name')
            ->get()
            ->map(function (Registration $student): array {
                return [
                    ...$student->toArray(),
                    'photo_url' => $this->studentPhotoUrl(
                        $student->photo_path
                    ),
                ];
            });

        $sample = $students->first();

        if (! $sample) {
            $fallbackStudent = Registration::first();

            $sample = $fallbackStudent
                ? [
                    ...$fallbackStudent->toArray(),
                    'photo_url' => $this->studentPhotoUrl(
                        $fallbackStudent->photo_path
                    ),
                ]
                : null;
        }

        return inertia('Dashboard/IdMakerApp', [
            'template' => [
                'front_image_url' => $this->temporaryUrl(
                    $template->front_image_path
                ),
                'back_image_url' => $this->temporaryUrl(
                    $template->back_image_path
                ),
                'front_layout' => $template->front_layout ?? [],
                'back_layout' => $template->back_layout ?? [],
            ],
            'sample' => $sample,
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

    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'front_layout' => ['array'],
            'back_layout' => ['array'],
        ]);

        $this->template()->update($validated);

        return back()->with(
            'success',
            'ID template saved.'
        );
    }

    public function markDone(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'student_ids' => ['required', 'array'],
            'student_ids.*' => ['exists:registrations,id'],
        ]);

        foreach ($validated['student_ids'] as $studentId) {
            DoneId::firstOrCreate([
                'student_id' => $studentId,
            ]);
        }

        return back()->with(
            'success',
            'IDs marked as done.'
        );
    }

    public function unmarkDone(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'student_ids' => ['required', 'array'],
            'student_ids.*' => ['exists:registrations,id'],
        ]);

        DoneId::whereIn(
            'student_id',
            $validated['student_ids']
        )->delete();

        return back()->with(
            'success',
            'Selected students re-enabled for reprint.'
        );
    }

    public function uploadBackground(
        Request $request,
        string $side
    ): RedirectResponse {
        abort_unless(
            in_array($side, ['front', 'back'], true),
            404
        );

        $validated = $request->validate([
            'image' => [
                'required',
                'image',
                'mimes:jpg,jpeg,png,webp',
                'max:8192',
            ],
        ]);

        $template = $this->template();

        $oldPath = $side === 'front'
            ? $template->front_image_path
            : $template->back_image_path;

        $photo = $validated['image'];

        $extension = strtolower(
            $photo->getClientOriginalExtension()
        );

        $filename = $side.'-'.uniqid().'.'.$extension;

        $path = Storage::disk('s3')->putFileAs(
            'id-templates',
            $photo,
            $filename
        );

        $template->update([
            $side.'_image_path' => $path,
        ]);

        if ($oldPath && $oldPath !== $path) {
            Storage::disk('s3')->delete($oldPath);
        }

        return back()->with(
            'success',
            ucfirst($side).' background updated.'
        );
    }

    public function print(Request $request): Response
    {
        $template = $this->template();

        $doneStudentIds = DoneId::pluck('student_id')
            ->toArray();

        $query = Registration::query()
            ->leftJoin(
                'sections',
                'registrations.section',
                '=',
                'sections.name'
            )
            ->select([
                'registrations.*',
                'sections.grade_level',
            ])
            ->whereNotIn(
                'registrations.id',
                $doneStudentIds
            )
            ->orderBy('registrations.last_name');

        if (
            $request->has('ids')
            && ! empty($request->query('ids'))
        ) {
            $ids = explode(
                ',',
                (string) $request->query('ids')
            );

            $query->whereIn(
                'registrations.id',
                $ids
            );
        }

        $students = $query
            ->get()
            ->map(function (Registration $student): array {
                return [
                    ...$student->toArray(),
                    'photo_url' => $this->studentPhotoUrl(
                        $student->photo_path
                    ),
                ];
            });

        return inertia('Dashboard/IdMakerPrint', [
            'template' => [
                'front_image_url' => $this->temporaryUrl(
                    $template->front_image_path
                ),
                'back_image_url' => $this->temporaryUrl(
                    $template->back_image_path
                ),
                'front_layout' => $template->front_layout ?? [],
                'back_layout' => $template->back_layout ?? [],
            ],
            'students' => $students,
        ]);
    }
}

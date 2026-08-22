<?php

namespace App\Http\Controllers;

use App\Models\Registration;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;
use Intervention\Image\Drivers\Gd\Driver;
use Intervention\Image\Format;
use Intervention\Image\ImageManager;

class RegistrationController extends Controller
{
    /**
     * Display the registered students.
     */
    public function index(Request $request): Response
    {
        $search = trim((string) $request->input('search', ''));

        $registrations = Registration::query()
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($query) use ($search) {
                    $query
                        ->where('first_name', 'ilike', "%{$search}%")
                        ->orWhere('middle_name', 'ilike', "%{$search}%")
                        ->orWhere('last_name', 'ilike', "%{$search}%");
                });
            })
            ->latest()
            ->paginate(10)
            ->withQueryString()
            ->through(function (Registration $registration) {
                return [
                    'id' => $registration->id,
                    'first_name' => $registration->first_name,
                    'middle_name' => $registration->middle_name,
                    'last_name' => $registration->last_name,
                    'school_year' => $registration->school_year,
                    'section' => $registration->section,
                    'address' => $registration->address,
                    'birthday' => $registration->birthday,
                    'parent_name' => $registration->parent_name,
                    'parent_contact_number' => $registration->parent_contact_number,
                    'photo_url' => $this->temporaryPhotoUrl(
                        $registration->photo_path,
                    ),
                ];
            });

        return Inertia::render('Dashboard/Students', [
            'registrations' => $registrations,
            'filters' => [
                'search' => $search,
            ],
        ]);
    }

    /**
     * Display the registration form.
     */
    public function create(): Response
    {
        return Inertia::render('Registrations/Create');
    }

    /**
     * Store a new registration.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'first_name' => [
                'required',
                'string',
                'max:255',
            ],
            'middle_name' => [
                'nullable',
                'string',
                'max:255',
            ],
            'last_name' => [
                'required',
                'string',
                'max:255',
            ],
            'address' => [
                'required',
                'string',
                'max:255',
            ],
            'birthday' => [
                'required',
                'date',
                'before_or_equal:today',
            ],
            'parent_name' => [
                'required',
                'string',
                'max:255',
            ],
            'parent_address' => [
                'required',
                'string',
                'max:255',
            ],
            'parent_contact_number' => [
                'required',
                'string',
                'max:20',
            ],
            'photo' => [
                'nullable',
                'image',
                'mimes:jpeg,jpg,png,webp',
                'max:20480',
            ],
        ]);

        $photoPath = null;

        if ($request->hasFile('photo')) {
            $photo = $request->file('photo');

            $manager = ImageManager::usingDriver(
                Driver::class,
            );

            $image = $manager->decodePath(
                $photo->getRealPath(),
            );

            $image->scaleDown(
                width: 1920,
            );

            $encoded = $image->encodeUsingFormat(
                Format::WEBP,
                quality: 80,
            );

            $photoPath = sprintf(
                'registrations/photos/%s.webp',
                Str::uuid(),
            );

            Storage::disk('s3')->put(
                $photoPath,
                $encoded->toString(),
            );
        }

        Registration::create([
            'first_name' => $validated['first_name'],
            'middle_name' => $validated['middle_name'] ?? null,
            'last_name' => $validated['last_name'],
            'address' => $validated['address'],
            'birthday' => $validated['birthday'],
            'parent_name' => $validated['parent_name'],
            'parent_address' => $validated['parent_address'],
            'parent_contact_number' => $validated['parent_contact_number'],
            'photo_path' => $photoPath,
        ]);

        return back()->with(
            'success',
            'Registration submitted successfully.',
        );
    }

    /**
     * Generate a temporary URL for a private R2 photo.
     */
    private function temporaryPhotoUrl(?string $photoPath): ?string
    {
        if (! $photoPath) {
            return null;
        }

        return Storage::disk('s3')->temporaryUrl(
            $photoPath,
            now()->addMinutes(30),
        );
    }
}

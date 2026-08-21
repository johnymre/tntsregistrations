<?php

namespace App\Http\Controllers;

use App\Models\Registration;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class RegistrationController extends Controller
{
    public function index(): Response
    {
        $registrations = Registration::query()
            ->latest()
            ->get()
            ->map(function (Registration $registration) {
                return [
                    'id' => $registration->id,
                    'first_name' => $registration->first_name,
                    'middle_name' => $registration->middle_name,
                    'last_name' => $registration->last_name,
                    'address' => $registration->address,
                    'birthday' => $registration->birthday,
                    'parent_name' => $registration->parent_name,
                    'parent_address' => $registration->parent_address,
                    'parent_contact_number' => $registration->parent_contact_number,
                    'photo_path' => $registration->photo_path,

                    // R2 is private, so generate a temporary URL.
                    'photo_url' => $registration->photo_path
                        ? Storage::disk('s3')->temporaryUrl(
                            $registration->photo_path,
                            now()->addMinutes(30),
                        )
                        : null,

                    'created_at' => $registration->created_at,
                    'updated_at' => $registration->updated_at,
                ];
            });

        return Inertia::render('Dashboard/Students', [
            'registrations' => $registrations,
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Registrations/Create');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'first_name' => ['required', 'string', 'max:255'],
            'middle_name' => ['nullable', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'address' => ['required', 'string', 'max:255'],
            'birthday' => ['required', 'date', 'before_or_equal:today'],
            'parent_name' => ['required', 'string', 'max:255'],
            'parent_address' => ['required', 'string', 'max:255'],
            'parent_contact_number' => ['required', 'string', 'max:20'],
            'photo' => [
                'nullable',
                'image',
                'mimes:jpeg,jpg,png,webp',
                'max:20480',
            ],
        ]);

        $photoPath = null;

        if ($request->hasFile('photo')) {
            $photoPath = $request
                ->file('photo')
                ->store('registrations/photos', 's3');
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
}

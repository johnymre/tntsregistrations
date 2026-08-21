<?php

namespace App\Http\Controllers;

use App\Models\Registration;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class RegistrationController extends Controller
{
    /**
     * Show the registration form.
     */
    public function create(): Response
    {
        return Inertia::render('Registrations/Create');
    }

    /**
     * Save a new registration.
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
                'max:50',
            ],
            'photo_path' => [
                'nullable',
                'string',
                'max:255',
            ],
            'school_year' => [
                'nullable',
                'string',
                'max:255',
            ],
            'section' => [
                'nullable',
                'string',
                'max:255',
            ],
            'adviser' => [
                'nullable',
                'string',
                'max:255',
            ],
        ]);

        Registration::create($validated);

        return back()->with('flash', [
            'success' => 'Registration submitted successfully!',
        ]);
    }

    /**
     * Display registrations.
     */
    public function index(Request $request): Response
    {
        $search = $request->query('search');

        $registrations = Registration::query()
            ->when(
                is_string($search) && $search !== '',
                function ($query) use ($search) {
                    $query
                        ->where('first_name', 'like', "%{$search}%")
                        ->orWhere('last_name', 'like', "%{$search}%");
                }
            )
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return Inertia::render('Dashboard/Students', [
            'registrations' => $registrations,
            'filters' => [
                'search' => is_string($search) ? $search : '',
            ],
        ]);
    }
}

<?php

namespace App\Http\Controllers;

use App\Models\Registration;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class RegistrationController extends Controller
{
    public function create(): Response
    {
        return Inertia::render('Registrations/Create');
    }

    public function store(Request $request): RedirectResponse
    {
        return back()->with('flash', ['success' => 'Registration submitted successfully!']);
    }

    public function index(Request $request): Response
    {
        $search = $request->query('search');

        $registrations = Registration::query()
            ->when(is_string($search) && $search !== '', function ($query) use ($search) {
                $query->where('first_name', 'like', "%{$search}%")
                    ->orWhere('last_name', 'like', "%{$search}%");
            })
            ->paginate(20)
            ->withQueryString();

        return Inertia::render('Dashboard/Students', [
            'registrations' => $registrations,
            'filters' => ['search' => is_string($search) ? $search : ''],
        ]);
    }
}

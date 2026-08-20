<?php

namespace App\Http\Controllers;

use App\Models\Registration;
use Illuminate\Http\Request;

class RegistrationController extends Controller
{
    public function create()
    {
        return inertia('Registrations/Create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'first_name' => 'required|string|max:255',
            'middle_name' => 'nullable|string|max:255',
            'last_name' => 'required|string|max:255',
            'address' => 'required|string|max:500',
            'birthday' => 'required|date|before_or_equal:today',
            'parent_name' => 'required|string|max:255',
            'parent_address' => 'required|string|max:500',
            'parent_contact_number' => 'required|string|max:20',
            'photo' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:20480',
            'school_year' => 'nullable|string|max:50',
            'section' => 'nullable|string|max:50',
        ]);

        if ($request->hasFile('photo')) {
    $photo = $request->file('photo');

    $manager = \Intervention\Image\ImageManager::usingDriver(\Intervention\Image\Drivers\Gd\Driver::class);
    $image = $manager->decode($photo->getRealPath());
    $image->scaleDown(width: 1000);

    $encoded = $image->encodeUsingFileExtension('jpg', quality: 75);

    $filename = strtoupper($validated['last_name']) . ', ' . strtoupper($validated['first_name']) . '.jpg';
    $path = 'photos/' . $filename;

    \Illuminate\Support\Facades\Storage::disk('public')->put($path, (string) $encoded);
    $validated['photo_path'] = $path;
}
        unset($validated['photo']);

        Registration::create($validated);

        return back()->with('success', 'Registration submitted.');
    }

    public function index(Request $request)
{
    $search = $request->string('search')->trim()->toString();

    $registrations = Registration::query()
        ->when($search, function ($query, $search) {
            $query->where(function ($q) use ($search) {
                $q->where('first_name', 'ilike', "%{$search}%")
                    ->orWhere('last_name', 'ilike', "%{$search}%")
                    ->orWhere('middle_name', 'ilike', "%{$search}%");
            });
        })
        ->latest()
        ->paginate(15)
        ->withQueryString();

    return inertia('Dashboard/Students', [
        'registrations' => $registrations,
        'filters' => ['search' => $search],
    ]);
}
}
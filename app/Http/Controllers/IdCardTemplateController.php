<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class IdCardTemplateController extends Controller
{
    public function edit(): Response
    {
        return Inertia::render('Dashboard/IdMakerApp');
    }

    public function update(Request $request): RedirectResponse
    {
        return back();
    }

    public function markDone(Request $request): RedirectResponse
    {
        return back();
    }

    public function unmarkDone(Request $request): RedirectResponse
    {
        return back();
    }

    public function uploadBackground(Request $request, string $side): RedirectResponse
    {
        return back();
    }

    public function print(Request $request): Response
    {
        return Inertia::render('Dashboard/IdMakerPrint');
    }
}

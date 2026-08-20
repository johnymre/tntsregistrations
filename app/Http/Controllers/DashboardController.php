<?php

namespace App\Http\Controllers;

use App\Models\Registration;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Dashboard/Dashboard', [
            'totalStudents' => Registration::count(),
        ]);
    }
}

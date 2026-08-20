<?php

namespace App\Http\Controllers;

use App\Models\Registration;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index()
    {
        return inertia('Dashboard/Dashboard', [
            'totalStudents' => Registration::count(),
        ]);
    }
}
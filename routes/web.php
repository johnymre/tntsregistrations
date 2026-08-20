<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\RegistrationController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\SectionController;
use App\Http\Controllers\IdCardTemplateController;
use App\Http\Controllers\DashboardController;


Route::inertia('/', 'Welcome')->name('home');
Route::get('/tntsregistration', [RegistrationController::class, 'create'])->name('registrations.create');
Route::post('/registrations', [RegistrationController::class, 'store'])->name('registrations.store');

Route::get('/login', [AuthController::class, 'create'])->name('login')->middleware('guest');
Route::post('/login', [AuthController::class, 'store'])->middleware('guest');
Route::post('/logout', [AuthController::class, 'destroy'])->middleware('auth');

Route::get('/register', [AuthController::class, 'createAccount'])->name('register')->middleware('guest');
Route::post('/register', [AuthController::class, 'storeAccount'])->middleware('guest');


Route::middleware(['auth'])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
});

Route::get('/students', [RegistrationController::class, 'index'])->name('students')->middleware('auth');
Route::get('/school-year', fn () => inertia('Dashboard/SchoolYear'))->name('school-year')->middleware('auth');

Route::middleware(['auth'])->group(function () {
    Route::get('/sectioning', [SectionController::class, 'index'])->name('sectioning.index');
    Route::post('/sectioning', [SectionController::class, 'store'])->name('sectioning.store');
    Route::post('/sectioning/{section}/students', [SectionController::class, 'updateStudents'])->name('sectioning.updateStudents');
});

Route::middleware(['auth'])->group(function () {
    Route::get('/id-maker', [IdCardTemplateController::class, 'edit'])->name('id-maker.edit');
    Route::post('/id-maker', [IdCardTemplateController::class, 'update'])->name('id-maker.update');
    Route::post('/id-maker/background/{side}', [IdCardTemplateController::class, 'uploadBackground'])->name('id-maker.background');
    Route::get('/id-maker/print', [IdCardTemplateController::class, 'print'])->name('id-maker.print');
    Route::post('/id-maker/mark-done', [IdCardTemplateController::class, 'markDone'])->name('id-maker.mark-done');
    Route::post('/id-maker/unmark-done', [IdCardTemplateController::class, 'unmarkDone'])->name('id-maker.unmark-done');
});
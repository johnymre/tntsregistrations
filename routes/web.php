<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\IdCardTemplateController;
use App\Http\Controllers\RegistrationController;
use App\Http\Controllers\SectionController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect()->route('login');
})->name('home');

Route::get('/tntsregistration', [RegistrationController::class, 'create'])
    ->name('registrations.create');

Route::post('/registrations', [RegistrationController::class, 'store'])
    ->name('registrations.store');

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'create'])
        ->name('login');

    Route::post('/login', [AuthController::class, 'store']);

    Route::get('/register', [AuthController::class, 'createAccount'])
        ->name('register');

    Route::post('/register', [AuthController::class, 'storeAccount']);
});

Route::middleware('auth')->group(function () {
    Route::post('/logout', [AuthController::class, 'destroy'])
        ->name('logout');

    Route::get('/dashboard', [DashboardController::class, 'index'])
        ->name('dashboard');

    Route::get('/students', [RegistrationController::class, 'index'])
        ->name('students');

    Route::get('/sectioning', [SectionController::class, 'index'])
        ->name('sectioning.index');

    Route::post('/sectioning', [SectionController::class, 'store'])
        ->name('sectioning.store');

    Route::post(
        '/sectioning/{section}/students',
        [SectionController::class, 'updateStudents']
    )->name('sectioning.students.update');

    Route::get('/id-maker', [IdCardTemplateController::class, 'edit'])
        ->name('id-maker.edit');

    Route::post('/id-maker', [IdCardTemplateController::class, 'update'])
        ->name('id-maker.update');

    Route::post(
        '/id-maker/mark-done',
        [IdCardTemplateController::class, 'markDone']
    )->name('id-maker.mark-done');

    Route::post(
        '/id-maker/unmark-done',
        [IdCardTemplateController::class, 'unmarkDone']
    )->name('id-maker.unmark-done');

    Route::post(
        '/id-maker/background/{side}',
        [IdCardTemplateController::class, 'uploadBackground']
    )->name('id-maker.background');

    Route::get('/id-maker/print', [IdCardTemplateController::class, 'print'])
        ->name('id-maker.print');
});

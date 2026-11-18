<?php

use App\Http\Controllers\Doctor\DoctorAuthController;
use App\Http\Controllers\Doctor\DoctorPanelController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

Route::get('/', fn () => redirect()->route('doctor.appointments'));

// Standalone Doctor Authentication
Route::get('/login', [DoctorAuthController::class, 'showLoginForm'])->name('login');
Route::post('/login', [DoctorAuthController::class, 'login'])->name('login.post');
Route::post('/demo-login', [DoctorAuthController::class, 'demoLogin'])->name('demoLogin');
Route::get('/register', [DoctorAuthController::class, 'showRegisterForm'])->name('register');
Route::post('/register', [DoctorAuthController::class, 'register'])->name('register.post');
Route::post('/logout', [DoctorAuthController::class, 'logout'])->name('logout');

// Standalone Doctor Workspace (Guarded by Doctor Auth)
Route::middleware(['doctor.auth'])->group(function () {
    Route::get('/appointments', [DoctorPanelController::class, 'index'])->name('appointments');
    Route::get('/appointments/{consultation}', [DoctorPanelController::class, 'show'])->name('appointments.show');
    Route::post('/appointments/{consultation}/schedule', [DoctorPanelController::class, 'schedule'])->name('appointments.schedule');
    Route::post('/appointments/{consultation}/meeting-link', [DoctorPanelController::class, 'saveMeetingLink'])->name('appointments.meetingLink');
    Route::post('/appointments/{consultation}/notes', [DoctorPanelController::class, 'saveNotes'])->name('appointments.notes');
    Route::post('/appointments/{consultation}/complete', [DoctorPanelController::class, 'complete'])->name('appointments.complete');
    Route::delete('/appointments/{consultation}', [DoctorPanelController::class, 'destroy'])->name('appointments.destroy');
});

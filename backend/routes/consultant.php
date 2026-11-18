<?php

use App\Http\Controllers\Consultant\ConsultantAppointmentController;
use App\Http\Controllers\Consultant\ConsultantAuthController;
use Illuminate\Support\Facades\Route;

Route::get('/', fn () => redirect()->route('consultant.appointments'));
Route::get('/login', [ConsultantAuthController::class, 'showLoginForm'])->name('login');
Route::post('/login', [ConsultantAuthController::class, 'login'])->name('login.post');
Route::post('/logout', [ConsultantAuthController::class, 'logout'])->name('logout');

Route::middleware(['auth'])->group(function () {
    Route::get('/appointments', [ConsultantAppointmentController::class, 'index'])->name('appointments');
    Route::get('/appointments/{consultation}', [ConsultantAppointmentController::class, 'show'])->name('appointments.show');
    Route::post('/appointments/{consultation}/generate-zoom', [ConsultantAppointmentController::class, 'generateZoom'])->name('appointments.generateZoom');
    Route::post('/appointments/{consultation}/schedule', [ConsultantAppointmentController::class, 'schedule'])->name('appointments.schedule');
    Route::post('/appointments/{consultation}/notify-customer', [ConsultantAppointmentController::class, 'notifyCustomer'])->name('appointments.notifyCustomer');
    Route::post('/appointments/{consultation}/complete', [ConsultantAppointmentController::class, 'complete'])->name('appointments.complete');
    Route::post('/appointments/order-followup', [ConsultantAppointmentController::class, 'createOrderFollowup'])->name('appointments.createOrderFollowup');
});

<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\ForgotPasswordController;
use App\Http\Controllers\Auth\ResetPasswordController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\EmployeeController;
use App\Http\Controllers\SettingsController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
*/

// =========================
// AUTH ROUTES
// =========================

// Root -> Login page
Route::get('/', [LoginController::class, 'showLoginForm'])->name('login');

// Login
Route::get('/login', [LoginController::class, 'showLoginForm'])->name('login');
Route::post('/login', [LoginController::class, 'login'])->name('login.submit');
Route::post('/logout', [LoginController::class, 'logout'])->name('logout');

// Forgot Password
Route::get('/forgot-password', [ForgotPasswordController::class, 'showForm'])->name('password.request');
Route::post('/forgot-password', [ForgotPasswordController::class, 'sendResetLink'])->name('password.email');


// Reset Password
Route::get('/reset-password/{token}', [ResetPasswordController::class, 'showForm'])->name('password.reset');
Route::post('/reset-password', [ResetPasswordController::class, 'reset'])->name('password.update');

// Email Verification
use Illuminate\Foundation\Auth\EmailVerificationRequest;

Route::get('/email/verify', function () {
    return view('auth.verify-email');
})->middleware('auth')->name('verification.notice');

Route::get('/email/verify/{id}/{hash}', function (Illuminate\Http\Request $request, $id, $hash) {
    $user = \App\Models\User::findOrFail($id);

    // Validate the hash
    if (!hash_equals(sha1($user->getEmailForVerification()), $hash)) {
        abort(403, 'Invalid verification link.');
    }

    // Check signature
    if (!$request->hasValidSignature()) {
        abort(403, 'Verification link has expired.');
    }

    // Mark as verified
    if (!$user->hasVerifiedEmail()) {
        $user->markEmailAsVerified();
    }

    return redirect()->route('login')->with('success', 'Email verified! You can now log in.');
})->middleware(['signed'])->name('verification.verify');

Route::post('/email/verification-notification', function (Illuminate\Http\Request $request) {
    $request->user()->sendEmailVerificationNotification();
    return back()->with('message', 'Verification link sent!');
})->middleware(['auth', 'throttle:6,1'])->name('verification.send');


// =========================
// DASHBOARD (ADMIN / USER)
// =========================

Route::middleware(['auth'])->group(function () {

    // Employee Routes
Route::prefix('employees')->name('employees.')->group(function () {
    Route::get('/directory', [EmployeeController::class, 'directory'])->name('directory');
    Route::get('/profile', [EmployeeController::class, 'profile'])->name('profile');
    Route::post('/store', [EmployeeController::class, 'store'])->name('store');
    Route::post('/departments', [EmployeeController::class, 'storeDepartment'])->name('departments.store');
Route::match(['POST', 'PUT'], '/departments/{id}', [EmployeeController::class, 'updateDepartment'])->name('departments.update');
 Route::put('/{id}', [EmployeeController::class, 'update'])->name('update');
Route::put('/job-title/{id}', [EmployeeController::class, 'updateJobTitle'])->name('job_title.update');
});

    // Admin Dashboard
    Route::get('/admin', [DashboardController::class, 'admin_dashboard'])->name('admin.dashboard');

    // Other Dashboards
    Route::get('/hr', function () {
        return view('hr.hr_dashboard');
    })->name('hr.dashboard');

    Route::get('/supervisor', function () {
        return view('supervisor.supervisor_dashboard');
    })->name('supervisor.dashboard');

    Route::get('/payroll', function () {
        return view('payroll.payroll_dashboard');
    })->name('payroll.dashboard');

    Route::get('/finance', function () {
        return view('finance.finance_dashboard');
    })->name('finance.dashboard');

    Route::get('/employees', function () {
        return view('employee.employee_dashboard');
    })->name('employee.dashboard');

    // =========================
    // SETTINGS ROUTES
    // =========================
    Route::prefix('settings')->name('settings.')->group(function () {
        Route::get('/', [SettingsController::class, 'index'])->name('index');
        Route::get('/general', [SettingsController::class, 'general'])->name('general');
        Route::get('/permissions', [SettingsController::class, 'permissions'])->name('permissions');
        Route::get('/notifications', [SettingsController::class, 'notifications'])->name('notifications');
        Route::get('/security', [SettingsController::class, 'security'])->name('security');
        Route::get('/email', [SettingsController::class, 'email'])->name('email');
    });

});

// Optional fallback
Route::fallback(function () {
    return redirect()->route('login');
});
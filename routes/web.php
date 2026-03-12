<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\ForgotPasswordController;
use App\Http\Controllers\Auth\ResetPasswordController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\AdminEmployeeController;
use App\Http\Controllers\SettingsController;
use App\Http\Controllers\HRDashboardController;
use App\Http\Controllers\SupervisorDashboardController;
use App\Http\Controllers\EmployeeDashboardController;
use App\Http\Controllers\EmployeeAttendanceController;
use App\Http\Controllers\HRAttendanceController;
use App\Http\Controllers\SupervisorAttendanceController;
use App\Http\Controllers\AdminAttendanceController; 

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
        Route::get('/directory', [AdminEmployeeController::class, 'directory'])->name('directory');
        Route::get('/profile', [AdminEmployeeController::class, 'profile'])->name('profile');
        Route::post('/store', [AdminEmployeeController::class, 'store'])->name('store');
        Route::post('/departments', [AdminEmployeeController::class, 'storeDepartment'])->name('departments.store');
        Route::match(['POST', 'PUT'], '/departments/{id}', [AdminEmployeeController::class, 'updateDepartment'])->name('departments.update');
        Route::put('/{id}', [AdminEmployeeController::class, 'update'])->name('update');
        Route::put('/job-title/{id}', [AdminEmployeeController::class, 'updateJobTitle'])->name('job_title.update');
    });

    // Admin Dashboard
    Route::get('/admin', [DashboardController::class, 'admin_dashboard'])->name('admin.dashboard');
    // HR Dashboard
    Route::get('/hr', [HRDashboardController::class, 'index'])->name('hr.dashboard');
    // Supervisor Dashboard
    Route::get('/supervisor', [SupervisorDashboardController::class, 'index'])->name('supervisor.dashboard');

    // Employee Dashboard
    Route::get('/employee/dashboard', [EmployeeDashboardController::class, 'index'])->name('employee.dashboard');

    // ── EMPLOYEE ATTENDANCE ROUTES ─────────────────────────────────────────
    Route::get('/employee/attendance/reports', [EmployeeAttendanceController::class, 'index'])->name('employee.attendance.reports');
    // ──────────────────────────────────────────────────────────────────────

    // ── HR ATTENDANCE ROUTES ───────────────────────────────────────────────
    Route::get('/hr/attendance/reports', [HRAttendanceController::class, 'index'])->name('hr.attendance.reports');
    // ──────────────────────────────────────────────────────────────────────

    // ── SUPERVISOR ATTENDANCE ROUTES ───────────────────────────────────────
    Route::get('/supervisor/attendance/reports', [SupervisorAttendanceController::class, 'index'])->name('supervisor.attendance.reports');
    // ──────────────────────────────────────────────────────────────────────

    // ── ADMIN ATTENDANCE ROUTES ────────────────────────────────────────────
    Route::get('/admin/attendance/reports', [AdminAttendanceController::class, 'index'])->name('admin.attendance.reports');
    // ──────────────────────────────────────────────────────────────────────

    // Other Dashboards
     Route::get('/payroll_officer', function () {
        return view('payroll_officer.payroll_dashboard');
    })->name('payroll_officer.dashboard');

    Route::get('/finance_officer', function () {
        return view('finance_officer.finance_dashboard');
    })->name('finance_officer.dashboard');


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


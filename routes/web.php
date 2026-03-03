<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\ForgotPasswordController;
use App\Http\Controllers\Auth\ResetPasswordController;

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\EmployeeController;

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


// =========================
// DASHBOARD (ADMIN / USER)
// =========================

Route::middleware(['auth'])->group(function () {

    // Admin main dashboard (from your partner)
    Route::get('/dashboard', [DashboardController::class, 'admin_dashboard'])->name('dashboard');

    // Employee Directory
    Route::prefix('employees')->name('employees.')->group(function () {
        Route::get('/directory', [EmployeeController::class, 'directory'])->name('directory');
    });

    // Role-based dashboards (from you)
    Route::get('/admin', function () {
        return view('admin.admin_dashboard');
    })->name('admin.dashboard');

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

    Route::get('/employee', function () {
        return view('employee.employee_dashboard');
    })->name('employee.dashboard');

});

// Optional fallback
Route::fallback(function () {
    return redirect()->route('login');
});
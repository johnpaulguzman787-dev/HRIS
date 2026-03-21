<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\ForgotPasswordController;
use App\Http\Controllers\Auth\ResetPasswordController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\AdminEmployeeController;
use App\Http\Controllers\SettingsController;
use App\Http\Controllers\HrDashboardController;
use App\Http\Controllers\SupervisorDashboardController;
use App\Http\Controllers\EmployeeDashboardController;
use App\Http\Controllers\EmployeeAttendanceController;
use App\Http\Controllers\HRAttendanceController;
use App\Http\Controllers\SupervisorAttendanceController;
use App\Http\Controllers\AdminAttendanceController;

use App\Http\Controllers\HrEmployeeController;
use App\Http\Controllers\SupervisorEmployeeController;

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

    // ── HR EMPLOYEE ROUTES ─────────────────────────────────────────────────
    Route::prefix('hr/employees')->name('hr.employees.')->group(function () {
        Route::get('/directory', [App\Http\Controllers\HrEmployeeController::class, 'directory'])->name('directory');
        Route::post('/store', [App\Http\Controllers\HrEmployeeController::class, 'store'])->name('store');
        Route::post('/departments', [App\Http\Controllers\HrEmployeeController::class, 'storeDepartment'])->name('departments.store');
        Route::match(['POST', 'PUT'], '/departments/{id}', [App\Http\Controllers\HrEmployeeController::class, 'updateDepartment'])->name('departments.update');
        Route::post('/{id}', [App\Http\Controllers\HrEmployeeController::class, 'update'])->name('update');
        Route::get('/profile', [App\Http\Controllers\HrEmployeeController::class, 'profile'])->name('profile');
    });
    // ──────────────────────────────────────────────────────────────────────

    // ── SUPERVISOR EMPLOYEE ROUTES ─────────────────────────────────────────
    Route::prefix('supervisor/employees')->name('supervisor.employees.')->group(function () {
        Route::get('/directory', [SupervisorEmployeeController::class, 'directory'])->name('directory');
        Route::get('/profile', [SupervisorEmployeeController::class, 'profile'])->name('profile');
        Route::post('/store', [SupervisorEmployeeController::class, 'store'])->name('store');
        Route::match(['POST', 'PUT'], '/departments/{id}', [SupervisorEmployeeController::class, 'updateDepartment'])->name('departments.update');
        Route::post('/{id}', [SupervisorEmployeeController::class, 'update'])->name('update');
    });
    // ──────────────────────────────────────────────────────────────────────

    // Admin Dashboard
    Route::get('/admin', [DashboardController::class, 'admin_dashboard'])->name('admin.dashboard');
    // HR Dashboard
    Route::get('/hr', [HrDashboardController::class, 'index'])->name('hr.dashboard');
    // Supervisor Dashboard
    Route::get('/supervisor', [SupervisorDashboardController::class, 'index'])->name('supervisor.dashboard');

    // Employee Dashboard
    Route::get('/employee/dashboard', [EmployeeDashboardController::class, 'index'])->name('employee.dashboard');
    Route::get('/employee/profile', [App\Http\Controllers\EmployeeProfileController::class, 'profile'])->name('employee.profile');

    // ── EMPLOYEE ATTENDANCE ROUTES ─────────────────────────────────────────
    Route::get('/employee/attendance/reports', [EmployeeAttendanceController::class, 'index'])->name('employee.attendance.reports');
    Route::get('/employee/attendance/today', [EmployeeAttendanceController::class, 'today'])->name('employee.attendance.today');
    Route::get('/employee/attendance/records', [EmployeeAttendanceController::class, 'records'])->name('employee.attendance.records');
    Route::post('/employee/attendance/clock-in', [EmployeeAttendanceController::class, 'clockIn'])->name('employee.attendance.clock-in');
    Route::post('/employee/attendance/clock-out', [EmployeeAttendanceController::class, 'clockOut'])->name('employee.attendance.clock-out');
    Route::post('/employee/attendance/break', [EmployeeAttendanceController::class, 'breakStart'])->name('employee.attendance.break');
    Route::get('/employee/leave/management',  [EmployeeAttendanceController::class, 'leaveManagement'])->name('employee.leave.management');
// ── EMPLOYEE LEAVE ROUTES ─────────────────────────────────────────────
    Route::post('/employee/leave/file',            [EmployeeAttendanceController::class, 'fileLeave'])->name('employee.leave.file');
    Route::post('/employee/leave/{id}/cancel',     [EmployeeAttendanceController::class, 'cancelLeave'])->name('employee.leave.cancel');
    Route::get('/employee/leave/{id}',             [EmployeeAttendanceController::class, 'getLeaveRequest'])->name('employee.leave.get');
// ─────────────────────────────────────────────────────────────────────
    // ──────────────────────────────────────────────────────────────────────

    // ── EMPLOYEE REQUESTS & APPROVAL ─────────────────────────────────────
    Route::get('/employee/requests/pending',       [EmployeeAttendanceController::class, 'pendingRequests'])->name('employee.requests.pending');
    Route::get('/employee/requests/approved',      [EmployeeAttendanceController::class, 'approvedRequests'])->name('employee.requests.approved');
    Route::post('/employee/requests/{id}/cancel',  [EmployeeAttendanceController::class, 'cancelRequest'])->name('employee.requests.cancel');
    // ──────────────────────────────────────────────────────────────────────
    // ── HR ATTENDANCE ROUTES ───────────────────────────────────────────────
    Route::get('/hr/attendance/reports', [HRAttendanceController::class, 'index'])->name('hr.attendance.reports');
    Route::get('/hr/attendance/today', [HRAttendanceController::class, 'today'])->name('hr.attendance.today');
    Route::get('/hr/attendance/records', [HRAttendanceController::class, 'records'])->name('hr.attendance.records');
    Route::post('/hr/attendance/clock-in', [HRAttendanceController::class, 'clockIn'])->name('hr.attendance.clock-in');
    Route::post('/hr/attendance/clock-out', [HRAttendanceController::class, 'clockOut'])->name('hr.attendance.clock-out');
    Route::post('/hr/attendance/break', [HRAttendanceController::class, 'breakStart'])->name('hr.attendance.break');
    Route::get('/hr/attendance/employee', [HRAttendanceController::class, 'employeeAttendance'])->name('hr.attendance.employee');
    Route::get('/hr/shift/scheduling', [HRAttendanceController::class, 'shiftScheduling'])->name('hr.shift.scheduling');
    Route::post('/hr/shift/assign', [HRAttendanceController::class, 'assignShift'])->name('hr.shift.assign');
    Route::post('/hr/shift/update', [HRAttendanceController::class, 'updateShift'])->name('hr.shift.update');
    Route::get('/hr/shift/employees-by-dept', [HRAttendanceController::class, 'employeesByDept'])->name('hr.shift.employees-by-dept');
    Route::get('/hr/shift/type/{id}', [HRAttendanceController::class, 'getShiftType'])->name('hr.shift.type.get');
    Route::post('/hr/shift/type/{id}/update', [HRAttendanceController::class, 'updateShiftType'])->name('hr.shift.type.update');
    Route::post('/hr/shift/type/store', [HRAttendanceController::class, 'storeShiftType'])->name('hr.shift.type.store');
    Route::post('/hr/holidays', [HRAttendanceController::class, 'storeHoliday'])->name('hr.holidays.store');
    Route::post('/hr/holidays/{id}/update', [HRAttendanceController::class, 'updateHoliday'])->name('hr.holidays.update');
    Route::delete('/hr/holidays/{id}', [HRAttendanceController::class, 'destroyHoliday'])->name('hr.holidays.destroy');
    Route::get('/hr/holidays/{id}', [HRAttendanceController::class, 'getHoliday'])->name('hr.holidays.get');
    Route::get('/hr/leave/management',           [HRAttendanceController::class, 'leaveManagement'])->name('hr.leave.management');
// ── HR LEAVE MANAGEMENT ROUTES ────────────────────────────────────────
    Route::get('/hr/leave/credits',              [HRAttendanceController::class, 'getLeaveCredits'])->name('hr.leave.credits.get'); 
    Route::post('/hr/leave/types/store',         [HRAttendanceController::class, 'storeLeaveType'])->name('hr.leave.type.store');
    Route::get('/hr/leave/types/{id}',           [HRAttendanceController::class, 'getLeaveType'])->name('hr.leave.type.get');
    Route::post('/hr/leave/types/{id}/update',   [HRAttendanceController::class, 'updateLeaveType'])->name('hr.leave.type.update');
    Route::post('/hr/leave/file',                [HRAttendanceController::class, 'fileLeave'])->name('hr.leave.file');
    Route::post('/hr/leave/{id}/approve',        [HRAttendanceController::class, 'approveLeave'])->name('hr.leave.approve');
    Route::post('/hr/leave/{id}/reject',         [HRAttendanceController::class, 'rejectLeave'])->name('hr.leave.reject');
    Route::post('/hr/leave/{id}/cancel',         [HRAttendanceController::class, 'cancelLeave'])->name('hr.leave.cancel');
    Route::get('/hr/leave/{id}',                 [HRAttendanceController::class, 'getLeaveRequest'])->name('hr.leave.get');
// ── HR REQUESTS & APPROVAL ────────────────────────────────────────────
    Route::get('/hr/requests/pending',           [HRAttendanceController::class, 'pendingRequests'])->name('hr.requests.pending');
    Route::get('/hr/requests/approved',          [HRAttendanceController::class, 'approvedRequests'])->name('hr.requests.approved');
    Route::post('/hr/requests/{id}/approve',     [HRAttendanceController::class, 'approveRequest'])->name('hr.requests.approve');
    Route::post('/hr/requests/{id}/reject',      [HRAttendanceController::class, 'rejectRequest'])->name('hr.requests.reject');
// ──────────────────────────────────────────────────────────────────────


    // ── SUPERVISOR ATTENDANCE ROUTES ───────────────────────────────────────
    Route::get('/supervisor/attendance/reports', [SupervisorAttendanceController::class, 'index'])->name('supervisor.attendance.reports');
    Route::get('/supervisor/attendance/today', [SupervisorAttendanceController::class, 'today'])->name('supervisor.attendance.today');
    Route::get('/supervisor/attendance/records', [SupervisorAttendanceController::class, 'records'])->name('supervisor.attendance.records');
    Route::post('/supervisor/attendance/clock-in', [SupervisorAttendanceController::class, 'clockIn'])->name('supervisor.attendance.clock-in');
    Route::post('/supervisor/attendance/clock-out', [SupervisorAttendanceController::class, 'clockOut'])->name('supervisor.attendance.clock-out');
    Route::post('/supervisor/attendance/break', [SupervisorAttendanceController::class, 'breakStart'])->name('supervisor.attendance.break');
    Route::get('/supervisor/attendance/employee', [SupervisorAttendanceController::class, 'employeeAttendance'])->name('supervisor.attendance.employee');
    Route::get('/supervisor/shift/scheduling', [SupervisorAttendanceController::class, 'shiftScheduling'])->name('supervisor.shift.scheduling');
    Route::get('/supervisor/leave/management', [SupervisorAttendanceController::class, 'leaveManagement'])->name('supervisor.leave.management');
// ── SUPERVISOR LEAVE ROUTES ───────────────────────────────────────────
    Route::post('/supervisor/leave/file',              [SupervisorAttendanceController::class, 'fileLeave'])->name('supervisor.leave.file');
    Route::post('/supervisor/leave/{id}/cancel',       [SupervisorAttendanceController::class, 'cancelLeave'])->name('supervisor.leave.cancel');
    Route::get('/supervisor/leave/{id}',               [SupervisorAttendanceController::class, 'getLeaveRequest'])->name('supervisor.leave.get');
// ─────────────────────────────────────────────────────────────────────
    Route::post('/supervisor/shift/assign', [SupervisorAttendanceController::class, 'assignShift'])->name('supervisor.shift.assign');
    Route::post('/supervisor/shift/update', [SupervisorAttendanceController::class, 'updateShift'])->name('supervisor.shift.update');
    Route::get('/supervisor/shift/employees-by-dept', [SupervisorAttendanceController::class, 'employeesByDept'])->name('supervisor.shift.employees-by-dept');
    // ──────────────────────────────────────────────────────────────────────

    // ── SUPERVISOR REQUESTS & APPROVAL ──────────────────────────────────
    Route::get('/supervisor/requests/pending',           [SupervisorAttendanceController::class, 'pendingRequests'])->name('supervisor.requests.pending');
    Route::get('/supervisor/requests/approved',          [SupervisorAttendanceController::class, 'approvedRequests'])->name('supervisor.requests.approved');
    Route::post('/supervisor/requests/{id}/approve',     [SupervisorAttendanceController::class, 'approveRequest'])->name('supervisor.requests.approve');
    Route::post('/supervisor/requests/{id}/reject',      [SupervisorAttendanceController::class, 'rejectRequest'])->name('supervisor.requests.reject');
    // ──────────────────────────────────────────────────────────────────────
    // ── ADMIN ATTENDANCE ROUTES ────────────────────────────────────────────
    Route::get('/admin/attendance/reports', [AdminAttendanceController::class, 'index'])->name('admin.attendance.reports');
    Route::get('/admin/attendance/today', [AdminAttendanceController::class, 'today'])->name('admin.attendance.today');
    Route::get('/admin/attendance/records', [AdminAttendanceController::class, 'records'])->name('admin.attendance.records');
    Route::post('/admin/attendance/clock-in', [AdminAttendanceController::class, 'clockIn'])->name('admin.attendance.clock-in');
    Route::post('/admin/attendance/clock-out', [AdminAttendanceController::class, 'clockOut'])->name('admin.attendance.clock-out');
    Route::post('/admin/attendance/break', [AdminAttendanceController::class, 'breakStart'])->name('admin.attendance.break');
    Route::get('/admin/attendance/employee', [AdminAttendanceController::class, 'employeeAttendance'])->name('admin.attendance.employee');
    Route::get('/admin/leave/management', [AdminAttendanceController::class, 'leaveManagement'])->name('admin.leave.management');
    // ── ADMIN LEAVE ROUTES ───────────────────────────────────────────
    Route::post('/admin/leave/file',        [AdminAttendanceController::class, 'fileLeave'])->name('admin.leave.file');
    Route::post('/admin/leave/{id}/cancel', [AdminAttendanceController::class, 'cancelLeave'])->name('admin.leave.cancel');
    Route::get('/admin/leave/{id}',         [AdminAttendanceController::class, 'getLeaveRequest'])->name('admin.leave.get');
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
        Route::post('/permissions', [SettingsController::class, 'updatePermissions'])->name('permissions.update');
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
<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\ForgotPasswordController;
use App\Http\Controllers\Auth\ResetPasswordController;
use App\Http\Controllers\Auth\SetPasswordController;
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
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\AnnouncementController;

use App\Http\Controllers\HREmployeeController;
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

// Set Password (new account activation)
Route::get('/set-password/{token}', [SetPasswordController::class, 'showForm'])->name('set-password.show');
Route::post('/set-password', [SetPasswordController::class, 'setPassword'])->name('set-password.submit');

// Email Verification
use Illuminate\Foundation\Auth\EmailVerificationRequest;

Route::get('/email/verify', function () {
    return view('auth.verify-email');
})->middleware('auth')->name('verification.notice');

Route::get('/email/verify/{id}/{hash}', function (Illuminate\Http\Request $request, $id, $hash) {
    $user = \App\Models\User::findOrFail($id);

    if (!hash_equals(sha1($user->getEmailForVerification()), $hash)) {
        abort(403, 'Invalid verification link.');
    }

    if (!$request->hasValidSignature()) {
        abort(403, 'Verification link has expired.');
    }

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
// AUTHENTICATED ROUTES
// =========================

Route::middleware(['auth'])->group(function () {

    // ── NOTIFICATION ROUTES ────────────────────────────────────────────────
    Route::get('/notifications',            [NotificationController::class, 'index'])->name('notifications.index');
    Route::post('/notifications/read-all',  [NotificationController::class, 'markAllRead'])->name('notifications.read-all');
    Route::post('/notifications/{id}/read', [NotificationController::class, 'markRead'])->name('notifications.read');
    Route::delete('/notifications/clear',   [NotificationController::class, 'clearAll'])->name('notifications.clear');
    Route::delete('/notifications/{id}',    [NotificationController::class, 'destroy'])->name('notifications.destroy');
    Route::post('/notifications/check-shift', [NotificationController::class, 'checkShift'])->name('notifications.check-shift');

    // ── ANNOUNCEMENT ROUTES ────────────────────────────────────────────────
    Route::post('/admin/announcements',      [AnnouncementController::class, 'store'])->name('admin.announcements.store');
    Route::post('/hr/announcements',         [AnnouncementController::class, 'store'])->name('hr.announcements.store');
    Route::post('/supervisor/announcements', [AnnouncementController::class, 'store'])->name('supervisor.announcements.store');

    // ── ADMIN EMPLOYEE ROUTES ──────────────────────────────────────────────
    Route::prefix('employees')->name('employees.')->group(function () {
        Route::get('/directory', [AdminEmployeeController::class, 'directory'])->name('directory');
        Route::get('/profile', [AdminEmployeeController::class, 'profile'])->name('profile');
        Route::post('/store', [AdminEmployeeController::class, 'store'])->name('store');
        Route::post('/departments', [AdminEmployeeController::class, 'storeDepartment'])->name('departments.store');
        Route::match(['POST', 'PUT'], '/departments/{id}', [AdminEmployeeController::class, 'updateDepartment'])->name('departments.update');
        Route::delete('/departments/{id}', [AdminEmployeeController::class, 'destroyDepartment'])->name('departments.destroy');
        Route::get('/{id}/documents', [AdminEmployeeController::class, 'getDocuments'])->name('documents.index')->whereNumber('id');
        Route::post('/{id}/documents', [AdminEmployeeController::class, 'uploadDocument'])->name('documents.store')->whereNumber('id');
        Route::get('/documents/{docId}/download', [AdminEmployeeController::class, 'downloadDocument'])->name('documents.download');
        Route::put('/{id}', [AdminEmployeeController::class, 'update'])->name('update');
        Route::put('/job-title/{id}', [AdminEmployeeController::class, 'updateJobTitle'])->name('job_title.update');
    });

    // ── HR EMPLOYEE ROUTES ─────────────────────────────────────────────────
    Route::prefix('hr/employees')->name('hr.employees.')->group(function () {
        Route::get('/directory', [App\Http\Controllers\HREmployeeController::class, 'directory'])->name('directory');
        Route::post('/store', [App\Http\Controllers\HREmployeeController::class, 'store'])->name('store');
        Route::post('/departments', [App\Http\Controllers\HREmployeeController::class, 'storeDepartment'])->name('departments.store');
        Route::match(['POST', 'PUT'], '/departments/{id}', [App\Http\Controllers\HREmployeeController::class, 'updateDepartment'])->name('departments.update');
        Route::delete('/departments/{id}', [App\Http\Controllers\HREmployeeController::class, 'destroyDepartment'])->name('departments.destroy');
        Route::get('/{id}/documents', [App\Http\Controllers\HREmployeeController::class, 'getDocuments'])->name('documents.index')->whereNumber('id');
        Route::post('/{id}/documents', [App\Http\Controllers\HREmployeeController::class, 'uploadDocument'])->name('documents.store')->whereNumber('id');
        Route::get('/documents/{docId}/download', [App\Http\Controllers\HREmployeeController::class, 'downloadDocument'])->name('documents.download');
        Route::put('/{id}', [App\Http\Controllers\HREmployeeController::class, 'update'])->name('update');
        Route::get('/profile', [App\Http\Controllers\HREmployeeController::class, 'profile'])->name('profile');
    });

    // ── SUPERVISOR EMPLOYEE ROUTES ─────────────────────────────────────────
    Route::prefix('supervisor/employees')->name('supervisor.employees.')->group(function () {
        Route::get('/directory', [SupervisorEmployeeController::class, 'directory'])->name('directory');
        Route::get('/profile', [SupervisorEmployeeController::class, 'profile'])->name('profile');
        Route::post('/store', [SupervisorEmployeeController::class, 'store'])->name('store');
        Route::match(['POST', 'PUT'], '/departments/{id}', [SupervisorEmployeeController::class, 'updateDepartment'])->name('departments.update');
        Route::get('/{id}/documents', [SupervisorEmployeeController::class, 'getDocuments'])->name('documents.index')->whereNumber('id');
        Route::post('/{id}/documents', [SupervisorEmployeeController::class, 'uploadDocument'])->name('documents.store')->whereNumber('id');
        Route::get('/documents/{docId}/download', [SupervisorEmployeeController::class, 'downloadDocument'])->name('documents.download');
        Route::put('/{id}', [SupervisorEmployeeController::class, 'update'])->name('update');
    });

    // ── DASHBOARDS ─────────────────────────────────────────────────────────
    Route::get('/admin',              [DashboardController::class, 'admin_dashboard'])->name('admin.dashboard');
    Route::get('/hr',                 [HRDashboardController::class, 'index'])->name('hr.dashboard');
    Route::get('/supervisor',         [SupervisorDashboardController::class, 'index'])->name('supervisor.dashboard');
    Route::get('/employee/dashboard', [EmployeeDashboardController::class, 'index'])->name('employee.dashboard');
    Route::get('/employee/profile',   [App\Http\Controllers\EmployeeProfileController::class, 'profile'])->name('employee.profile');

    // ── EMPLOYEE ATTENDANCE ────────────────────────────────────────────────
    Route::get('/employee/attendance/reports',  [EmployeeAttendanceController::class, 'index'])->name('employee.attendance.reports');
    Route::get('/employee/attendance/today',    [EmployeeAttendanceController::class, 'today'])->name('employee.attendance.today');
    Route::get('/employee/attendance/records',  [EmployeeAttendanceController::class, 'records'])->name('employee.attendance.records');
    Route::post('/employee/attendance/clock-in',[EmployeeAttendanceController::class, 'clockIn'])->name('employee.attendance.clock-in');
    Route::post('/employee/attendance/clock-out',[EmployeeAttendanceController::class, 'clockOut'])->name('employee.attendance.clock-out');
    Route::post('/employee/attendance/break',   [EmployeeAttendanceController::class, 'breakStart'])->name('employee.attendance.break');
    Route::get('/employee/leave/management',    [EmployeeAttendanceController::class, 'leaveManagement'])->name('employee.leave.management');

    // ── EMPLOYEE LEAVE ─────────────────────────────────────────────────────
    Route::post('/employee/leave/file',         [EmployeeAttendanceController::class, 'fileLeave'])->name('employee.leave.file');
    Route::post('/employee/leave/{id}/cancel',  [EmployeeAttendanceController::class, 'cancelLeave'])->name('employee.leave.cancel');
    Route::get('/employee/leave/{id}',          [EmployeeAttendanceController::class, 'getLeaveRequest'])->name('employee.leave.get');

    // ── EMPLOYEE REQUESTS ──────────────────────────────────────────────────
    Route::get('/employee/requests/pending',            [EmployeeAttendanceController::class, 'pendingRequests'])->name('employee.requests.pending');
    Route::get('/employee/requests/approved',           [EmployeeAttendanceController::class, 'approvedRequests'])->name('employee.requests.approved');
    Route::post('/employee/requests/{id}/cancel',       [EmployeeAttendanceController::class, 'cancelRequest'])->name('employee.requests.cancel');
    Route::post('/employee/requests/overtime/file',     [EmployeeAttendanceController::class, 'fileOvertimeRequest'])->name('employee.requests.overtime.file');
    Route::post('/employee/requests/shift/file',        [EmployeeAttendanceController::class, 'fileShiftChangeRequest'])->name('employee.requests.shift.file');

    // ── HR ATTENDANCE ──────────────────────────────────────────────────────
    Route::get('/hr/attendance/reports',        [HRAttendanceController::class, 'index'])->name('hr.attendance.reports');
    Route::get('/hr/attendance/today',          [HRAttendanceController::class, 'today'])->name('hr.attendance.today');
    Route::get('/hr/attendance/records',        [HRAttendanceController::class, 'records'])->name('hr.attendance.records');
    Route::post('/hr/attendance/clock-in',      [HRAttendanceController::class, 'clockIn'])->name('hr.attendance.clock-in');
    Route::post('/hr/attendance/clock-out',     [HRAttendanceController::class, 'clockOut'])->name('hr.attendance.clock-out');
    Route::post('/hr/attendance/break',         [HRAttendanceController::class, 'breakStart'])->name('hr.attendance.break');
    Route::get('/hr/attendance/employee',       [HRAttendanceController::class, 'employeeAttendance'])->name('hr.attendance.employee');
    Route::get('/hr/shift/scheduling',          [HRAttendanceController::class, 'shiftScheduling'])->name('hr.shift.scheduling');
    Route::post('/hr/shift/assign',             [HRAttendanceController::class, 'assignShift'])->name('hr.shift.assign');
    Route::post('/hr/shift/update',             [HRAttendanceController::class, 'updateShift'])->name('hr.shift.update');
    Route::get('/hr/shift/employees-by-dept',   [HRAttendanceController::class, 'employeesByDept'])->name('hr.shift.employees-by-dept');
    Route::get('/hr/shift/type/{id}',           [HRAttendanceController::class, 'getShiftType'])->name('hr.shift.type.get');
    Route::post('/hr/shift/type/{id}/update',   [HRAttendanceController::class, 'updateShiftType'])->name('hr.shift.type.update');
    Route::post('/hr/shift/type/store',         [HRAttendanceController::class, 'storeShiftType'])->name('hr.shift.type.store');
    Route::post('/hr/holidays',                 [HRAttendanceController::class, 'storeHoliday'])->name('hr.holidays.store');
    Route::post('/hr/holidays/{id}/update',     [HRAttendanceController::class, 'updateHoliday'])->name('hr.holidays.update');
    Route::delete('/hr/holidays/{id}',          [HRAttendanceController::class, 'destroyHoliday'])->name('hr.holidays.destroy');
    Route::get('/hr/holidays/{id}',             [HRAttendanceController::class, 'getHoliday'])->name('hr.holidays.get');
    Route::get('/hr/leave/management',          [HRAttendanceController::class, 'leaveManagement'])->name('hr.leave.management');

    // ── HR LEAVE ───────────────────────────────────────────────────────────
    Route::get('/hr/leave/credits',             [HRAttendanceController::class, 'getLeaveCredits'])->name('hr.leave.credits.get');
    Route::post('/hr/leave/types/store',        [HRAttendanceController::class, 'storeLeaveType'])->name('hr.leave.type.store');
    Route::get('/hr/leave/types/{id}',          [HRAttendanceController::class, 'getLeaveType'])->name('hr.leave.type.get');
    Route::post('/hr/leave/types/{id}/update',  [HRAttendanceController::class, 'updateLeaveType'])->name('hr.leave.type.update');
    Route::post('/hr/leave/file',               [HRAttendanceController::class, 'fileLeave'])->name('hr.leave.file');
    Route::post('/hr/leave/{id}/approve',       [HRAttendanceController::class, 'approveLeave'])->name('hr.leave.approve');
    Route::post('/hr/leave/{id}/reject',        [HRAttendanceController::class, 'rejectLeave'])->name('hr.leave.reject');
    Route::post('/hr/leave/{id}/cancel',        [HRAttendanceController::class, 'cancelLeave'])->name('hr.leave.cancel');
    Route::get('/hr/leave/{id}',                [HRAttendanceController::class, 'getLeaveRequest'])->name('hr.leave.get');

    // ── HR REQUESTS ────────────────────────────────────────────────────────
    Route::get('/hr/requests/pending',                  [HRAttendanceController::class, 'pendingRequests'])->name('hr.requests.pending');
    Route::get('/hr/requests/approved',                 [HRAttendanceController::class, 'approvedRequests'])->name('hr.requests.approved');
    Route::post('/hr/requests/{id}/approve',            [HRAttendanceController::class, 'approveRequest'])->name('hr.requests.approve');
    Route::post('/hr/requests/{id}/reject',             [HRAttendanceController::class, 'rejectRequest'])->name('hr.requests.reject');
    Route::post('/hr/requests/overtime/file',           [HRAttendanceController::class, 'fileOvertimeRequest'])->name('hr.requests.overtime.file');
    Route::post('/hr/requests/shift/file',              [HRAttendanceController::class, 'fileShiftChangeRequest'])->name('hr.requests.shift.file');
    Route::post('/hr/requests/overtime/{id}/approve',   [HRAttendanceController::class, 'approveOvertimeRequest'])->name('hr.requests.overtime.approve');
    Route::post('/hr/requests/overtime/{id}/reject',    [HRAttendanceController::class, 'rejectOvertimeRequest'])->name('hr.requests.overtime.reject');
    Route::post('/hr/requests/shift/{id}/approve',      [HRAttendanceController::class, 'approveShiftChangeRequest'])->name('hr.requests.shift.approve');
    Route::post('/hr/requests/shift/{id}/reject',       [HRAttendanceController::class, 'rejectShiftChangeRequest'])->name('hr.requests.shift.reject');

    // ── SUPERVISOR ATTENDANCE ──────────────────────────────────────────────
    Route::get('/supervisor/attendance/reports',        [SupervisorAttendanceController::class, 'index'])->name('supervisor.attendance.reports');
    Route::get('/supervisor/attendance/today',          [SupervisorAttendanceController::class, 'today'])->name('supervisor.attendance.today');
    Route::get('/supervisor/attendance/records',        [SupervisorAttendanceController::class, 'records'])->name('supervisor.attendance.records');
    Route::post('/supervisor/attendance/clock-in',      [SupervisorAttendanceController::class, 'clockIn'])->name('supervisor.attendance.clock-in');
    Route::post('/supervisor/attendance/clock-out',     [SupervisorAttendanceController::class, 'clockOut'])->name('supervisor.attendance.clock-out');
    Route::post('/supervisor/attendance/break',         [SupervisorAttendanceController::class, 'breakStart'])->name('supervisor.attendance.break');
    Route::get('/supervisor/attendance/employee',       [SupervisorAttendanceController::class, 'employeeAttendance'])->name('supervisor.attendance.employee');
    Route::get('/supervisor/shift/scheduling',          [SupervisorAttendanceController::class, 'shiftScheduling'])->name('supervisor.shift.scheduling');
    Route::get('/supervisor/leave/management',          [SupervisorAttendanceController::class, 'leaveManagement'])->name('supervisor.leave.management');

    // ── SUPERVISOR LEAVE ───────────────────────────────────────────────────
    Route::post('/supervisor/leave/file',               [SupervisorAttendanceController::class, 'fileLeave'])->name('supervisor.leave.file');
    Route::post('/supervisor/leave/{id}/cancel',        [SupervisorAttendanceController::class, 'cancelLeave'])->name('supervisor.leave.cancel');
    Route::get('/supervisor/leave/{id}',                [SupervisorAttendanceController::class, 'getLeaveRequest'])->name('supervisor.leave.get');

    Route::post('/supervisor/shift/assign',             [SupervisorAttendanceController::class, 'assignShift'])->name('supervisor.shift.assign');
    Route::post('/supervisor/shift/update',             [SupervisorAttendanceController::class, 'updateShift'])->name('supervisor.shift.update');
    Route::get('/supervisor/shift/employees-by-dept',   [SupervisorAttendanceController::class, 'employeesByDept'])->name('supervisor.shift.employees-by-dept');

    // ── SUPERVISOR REQUESTS ────────────────────────────────────────────────
    Route::get('/supervisor/requests/pending',          [SupervisorAttendanceController::class, 'pendingRequests'])->name('supervisor.requests.pending');
    Route::get('/supervisor/requests/approved',         [SupervisorAttendanceController::class, 'approvedRequests'])->name('supervisor.requests.approved');
    Route::post('/supervisor/requests/{id}/approve',    [SupervisorAttendanceController::class, 'approveRequest'])->name('supervisor.requests.approve');
    Route::post('/supervisor/requests/{id}/reject',     [SupervisorAttendanceController::class, 'rejectRequest'])->name('supervisor.requests.reject');
    Route::post('/supervisor/requests/overtime/file',   [SupervisorAttendanceController::class, 'fileOvertimeRequest'])->name('supervisor.requests.overtime.file');
    Route::post('/supervisor/requests/shift/file',      [SupervisorAttendanceController::class, 'fileShiftChangeRequest'])->name('supervisor.requests.shift.file');

    // ── ADMIN ATTENDANCE ───────────────────────────────────────────────────
    Route::get('/admin/attendance/reports',             [AdminAttendanceController::class, 'index'])->name('admin.attendance.reports');
    Route::get('/admin/attendance/today',               [AdminAttendanceController::class, 'today'])->name('admin.attendance.today');
    Route::get('/admin/attendance/records',             [AdminAttendanceController::class, 'records'])->name('admin.attendance.records');
    Route::post('/admin/attendance/clock-in',           [AdminAttendanceController::class, 'clockIn'])->name('admin.attendance.clock-in');
    Route::post('/admin/attendance/clock-out',          [AdminAttendanceController::class, 'clockOut'])->name('admin.attendance.clock-out');
    Route::post('/admin/attendance/break',              [AdminAttendanceController::class, 'breakStart'])->name('admin.attendance.break');
    Route::get('/admin/attendance/employee',            [AdminAttendanceController::class, 'employeeAttendance'])->name('admin.attendance.employee');
    Route::get('/admin/leave/management',               [AdminAttendanceController::class, 'leaveManagement'])->name('admin.leave.management');

    // ── ADMIN LEAVE ────────────────────────────────────────────────────────
    Route::post('/admin/leave/file',                    [AdminAttendanceController::class, 'fileLeave'])->name('admin.leave.file');
    Route::post('/admin/leave/{id}/cancel',             [AdminAttendanceController::class, 'cancelLeave'])->name('admin.leave.cancel');
    Route::get('/admin/leave/{id}',                     [AdminAttendanceController::class, 'getLeaveRequest'])->name('admin.leave.get');
    Route::post('/admin/requests/overtime/file',        [AdminAttendanceController::class, 'fileOvertimeRequest'])->name('admin.requests.overtime.file');
    Route::post('/admin/requests/shift/file',           [AdminAttendanceController::class, 'fileShiftChangeRequest'])->name('admin.requests.shift.file');

    // ── ADMIN SHIFT ────────────────────────────────────────────────────────
    Route::get('/admin/shift/scheduling',               [AdminAttendanceController::class, 'shiftScheduling'])->name('admin.shift.scheduling');
    Route::post('/admin/shift/assign',                  [AdminAttendanceController::class, 'assignShiftAdmin'])->name('admin.shift.assign');
    Route::post('/admin/shift/update',                  [AdminAttendanceController::class, 'updateShiftAdmin'])->name('admin.shift.update');
    Route::get('/admin/shift/employees-by-dept',        [AdminAttendanceController::class, 'employeesByDeptShift'])->name('admin.shift.employees-by-dept');
    Route::get('/admin/shift/type/{id}',                [AdminAttendanceController::class, 'getShiftTypeAdmin'])->name('admin.shift.type.get');
    Route::post('/admin/shift/type/{id}/update',        [AdminAttendanceController::class, 'updateShiftTypeAdmin'])->name('admin.shift.type.update');
    Route::post('/admin/shift/type/store',              [AdminAttendanceController::class, 'storeShiftTypeAdmin'])->name('admin.shift.type.store');
    Route::post('/admin/holidays',                      [AdminAttendanceController::class, 'storeHolidayAdmin'])->name('admin.holidays.store');
    Route::post('/admin/holidays/{id}/update',          [AdminAttendanceController::class, 'updateHolidayAdmin'])->name('admin.holidays.update');
    Route::delete('/admin/holidays/{id}',               [AdminAttendanceController::class, 'destroyHolidayAdmin'])->name('admin.holidays.destroy');
    Route::get('/admin/holidays/{id}',                  [AdminAttendanceController::class, 'getHolidayAdmin'])->name('admin.holidays.get');

    // ── ADMIN REQUESTS ─────────────────────────────────────────────────────
    Route::get('/admin/requests/pending',               [AdminAttendanceController::class, 'pendingRequests'])->name('admin.requests.pending');
    Route::get('/admin/requests/approved',              [AdminAttendanceController::class, 'approvedRequests'])->name('admin.requests.approved');
    Route::post('/admin/requests/{id}/cancel',          [AdminAttendanceController::class, 'cancelRequest'])->name('admin.requests.cancel');

    // ── ADMIN PAYROLL ──────────────────────────────────────────────────────
    Route::get('/admin/payroll',       [\App\Http\Controllers\AdminPayrollController::class, 'index'])->name('admin.payroll');
    Route::get('/admin/payslips',      [\App\Http\Controllers\AdminPayrollController::class, 'payslips'])->name('admin.payslips');
    Route::get('/admin/govpay',        [\App\Http\Controllers\AdminPayrollController::class, 'govpay'])->name('admin.govpay');
    Route::get('/admin/govpay/{id}',   [\App\Http\Controllers\AdminPayrollController::class, 'govpayView'])->name('admin.govpay.view');

    // ── Admin Payroll Actions ────────────────────────────────────────────────
    Route::post('/admin/payroll/period/{id}/release',          [\App\Http\Controllers\AdminPayrollController::class, 'releasePayroll'])->name('admin.payroll.period.release');
    Route::get('/admin/payroll/period/{id}/payslips',          [\App\Http\Controllers\AdminPayrollController::class, 'periodPayslips'])->name('admin.payroll.period.payslips');
    Route::get('/admin/payroll/period/{id}/released-payslips', [\App\Http\Controllers\AdminPayrollController::class, 'releasedPeriodPayslips'])->name('admin.payroll.period.released-payslips');

    // ── Admin Salary Grades ──────────────────────────────────────────────────
    Route::post('/admin/payroll/grade/store',            [\App\Http\Controllers\AdminPayrollController::class, 'storeGrade'])->name('admin.payroll.grade.store');
    Route::put('/admin/payroll/grade/{id}/update',       [\App\Http\Controllers\AdminPayrollController::class, 'updateGrade'])->name('admin.payroll.grade.update');
    Route::delete('/admin/payroll/grade/{id}/delete',    [\App\Http\Controllers\AdminPayrollController::class, 'deleteGrade'])->name('admin.payroll.grade.delete');

    // ── Admin Payroll Items ──────────────────────────────────────────────────
    Route::post('/admin/payroll/item/store',             [\App\Http\Controllers\AdminPayrollController::class, 'storePayrollItem'])->name('admin.payroll.item.store');
    Route::put('/admin/payroll/item/{id}/update',        [\App\Http\Controllers\AdminPayrollController::class, 'updatePayrollItem'])->name('admin.payroll.item.update');
    Route::post('/admin/payroll/item/{id}/deactivate',   [\App\Http\Controllers\AdminPayrollController::class, 'deactivatePayrollItem'])->name('admin.payroll.item.deactivate');
    Route::delete('/admin/payroll/item/{id}/delete',     [\App\Http\Controllers\AdminPayrollController::class, 'deletePayrollItem'])->name('admin.payroll.item.delete');
    Route::get('/admin/payroll/item/{id}',               [\App\Http\Controllers\AdminPayrollController::class, 'getPayrollItem'])->name('admin.payroll.item.get');

    // ── Admin Benefits ───────────────────────────────────────────────────────
    Route::post('/admin/payroll/benefit/store',          [\App\Http\Controllers\AdminPayrollController::class, 'storeBenefit'])->name('admin.payroll.benefit.store');
    Route::put('/admin/payroll/benefit/{id}/update',     [\App\Http\Controllers\AdminPayrollController::class, 'updateBenefit'])->name('admin.payroll.benefit.update');
    Route::post('/admin/payroll/benefit/{id}/deactivate',[\App\Http\Controllers\AdminPayrollController::class, 'deactivateBenefit'])->name('admin.payroll.benefit.deactivate');
    Route::delete('/admin/payroll/benefit/{id}/delete',  [\App\Http\Controllers\AdminPayrollController::class, 'deleteBenefit'])->name('admin.payroll.benefit.delete');
    Route::get('/admin/payroll/benefit/{id}',            [\App\Http\Controllers\AdminPayrollController::class, 'getBenefit'])->name('admin.payroll.benefit.get');

    // ── Admin Contribution Settings ──────────────────────────────────────────
    Route::post('/admin/payroll/contrib/update',         [\App\Http\Controllers\AdminPayrollController::class, 'updateContrib'])->name('admin.payroll.contrib.update');

    // ── Admin Payslips AJAX ──────────────────────────────────────────────────
    Route::get('/admin/payslips/period/{id}',            [\App\Http\Controllers\AdminPayrollController::class, 'releasedPeriodPayslips'])->name('admin.payslips.period');

    // ── HR PAYROLL ─────────────────────────────────────────────────────────
    Route::get('/hr/payslips', [\App\Http\Controllers\EmployeePayrollController::class, 'payslips'])->name('hr.payslips');
    Route::get('/hr/govpay',   [\App\Http\Controllers\EmployeePayrollController::class, 'govpay'])->name('hr.govpay');

    // ── SUPERVISOR PAYROLL ─────────────────────────────────────────────────
    Route::get('/supervisor/payslips', [\App\Http\Controllers\EmployeePayrollController::class, 'payslips'])->name('supervisor.payslips');
    Route::get('/supervisor/govpay',   [\App\Http\Controllers\EmployeePayrollController::class, 'govpay'])->name('supervisor.govpay');

    // ── EMPLOYEE PAYROLL ───────────────────────────────────────────────────
    Route::get('/employee/payslips', [\App\Http\Controllers\EmployeePayrollController::class, 'payslips'])->name('employee.payslips');
    Route::get('/employee/govpay',   [\App\Http\Controllers\EmployeePayrollController::class, 'govpay'])->name('employee.govpay');

    // ══════════════════════════════════════════════════════════════════════
    // ── PAYROLL OFFICER ROUTES ─────────────────────────────────────────────
    // ══════════════════════════════════════════════════════════════════════

    Route::get('/payroll_officer/dashboard', [\App\Http\Controllers\PayrollOfficerDashboardController::class, 'index'])->name('payroll_officer.dashboard');
    Route::get('/payroll_officer/profile',   [\App\Http\Controllers\PayrollOfficerProfileController::class, 'profile'])->name('payroll_officer.profile');
    Route::post('/payroll_officer/announcements', [\App\Http\Controllers\AnnouncementController::class, 'store'])->name('payroll_officer.announcements.store');

    // ── Payroll Officer Attendance ─────────────────────────────────────────
    Route::get('/payroll_officer/attendance/reports',   [\App\Http\Controllers\PayrollOfficerAttendanceController::class, 'index'])->name('payroll_officer.attendance.reports');
    Route::get('/payroll_officer/attendance/today',     [\App\Http\Controllers\PayrollOfficerAttendanceController::class, 'today'])->name('payroll_officer.attendance.today');
    Route::get('/payroll_officer/attendance/records',   [\App\Http\Controllers\PayrollOfficerAttendanceController::class, 'records'])->name('payroll_officer.attendance.records');
    Route::post('/payroll_officer/attendance/clock-in', [\App\Http\Controllers\PayrollOfficerAttendanceController::class, 'clockIn'])->name('payroll_officer.attendance.clock-in');
    Route::post('/payroll_officer/attendance/clock-out',[\App\Http\Controllers\PayrollOfficerAttendanceController::class, 'clockOut'])->name('payroll_officer.attendance.clock-out');
    Route::post('/payroll_officer/attendance/break',    [\App\Http\Controllers\PayrollOfficerAttendanceController::class, 'breakStart'])->name('payroll_officer.attendance.break');

    // ── Payroll Officer Leave ──────────────────────────────────────────────
    Route::get('/payroll_officer/leave/management',         [\App\Http\Controllers\PayrollOfficerAttendanceController::class, 'leaveManagement'])->name('payroll_officer.leave.management');
    Route::post('/payroll_officer/leave/file',              [\App\Http\Controllers\PayrollOfficerAttendanceController::class, 'fileLeave'])->name('payroll_officer.leave.file');
    Route::post('/payroll_officer/leave/{id}/cancel',       [\App\Http\Controllers\PayrollOfficerAttendanceController::class, 'cancelLeave'])->name('payroll_officer.leave.cancel');
    Route::get('/payroll_officer/leave/{id}',               [\App\Http\Controllers\PayrollOfficerAttendanceController::class, 'getLeaveRequest'])->name('payroll_officer.leave.get');

    // ── Payroll Officer Requests ───────────────────────────────────────────
    Route::get('/payroll_officer/requests/pending',         [\App\Http\Controllers\PayrollOfficerAttendanceController::class, 'pendingRequests'])->name('payroll_officer.requests.pending');
    Route::get('/payroll_officer/requests/approved',        [\App\Http\Controllers\PayrollOfficerAttendanceController::class, 'approvedRequests'])->name('payroll_officer.requests.approved');
    Route::post('/payroll_officer/requests/{id}/cancel',    [\App\Http\Controllers\PayrollOfficerAttendanceController::class, 'cancelRequest'])->name('payroll_officer.requests.cancel');
    Route::post('/payroll_officer/requests/overtime/file',  [\App\Http\Controllers\PayrollOfficerAttendanceController::class, 'fileOvertimeRequest'])->name('payroll_officer.requests.overtime.file');
    Route::post('/payroll_officer/requests/shift/file',     [\App\Http\Controllers\PayrollOfficerAttendanceController::class, 'fileShiftChangeRequest'])->name('payroll_officer.requests.shift.file');

    // ── Payroll Officer Payroll (FIXED — now uses controller) ─────────────
    Route::get('/payroll_officer/payroll',  [\App\Http\Controllers\PayrollOfficerPayrollController::class, 'index'])->name('payroll_officer.payroll');
    Route::get('/payroll_officer/payslips', [\App\Http\Controllers\PayrollOfficerPayrollController::class, 'payslips'])->name('payroll_officer.payslips');
    Route::get('/payroll_officer/govpay',        [\App\Http\Controllers\PayrollOfficerPayrollController::class, 'govpay'])->name('payroll_officer.govpay');
    Route::get('/payroll_officer/govpay/{id}',   [\App\Http\Controllers\PayrollOfficerPayrollController::class, 'govpayView'])->name('payroll_officer.govpay.view');

    // ── Payroll Period ─────────────────────────────────────────────────────
    Route::post('/payroll_officer/payroll/period/store',                 [\App\Http\Controllers\PayrollOfficerPayrollController::class, 'storePeriod'])->name('payroll_officer.payroll.period.store');
    Route::get('/payroll_officer/payroll/period/{id}/payslips',          [\App\Http\Controllers\PayrollOfficerPayrollController::class, 'periodPayslips'])->name('payroll_officer.payroll.period.payslips');
    Route::get('/payroll_officer/payroll/period/{id}/all-payslips',      [\App\Http\Controllers\PayrollOfficerPayrollController::class, 'allPeriodPayslips'])->name('payroll_officer.payroll.period.all-payslips');
    Route::post('/payroll_officer/payroll/period/{id}/submit',           [\App\Http\Controllers\PayrollOfficerPayrollController::class, 'submitForApproval'])->name('payroll_officer.payroll.period.submit');
    Route::put('/payroll_officer/payroll/payslip/{id}/save',        [\App\Http\Controllers\PayrollOfficerPayrollController::class, 'savePayslip'])->name('payroll_officer.payroll.payslip.save');
    Route::post('/payroll_officer/payroll/payslip/{id}/submit',     [\App\Http\Controllers\PayrollOfficerPayrollController::class, 'submitPayslip'])->name('payroll_officer.payroll.payslip.submit');

    // ── Payroll Items (Salary Structure) ───────────────────────────────────
    Route::post('/payroll_officer/payroll/item/store',              [\App\Http\Controllers\PayrollOfficerPayrollController::class, 'storePayrollItem'])->name('payroll_officer.payroll.item.store');
    Route::put('/payroll_officer/payroll/item/{id}/update',         [\App\Http\Controllers\PayrollOfficerPayrollController::class, 'updatePayrollItem'])->name('payroll_officer.payroll.item.update');
    Route::post('/payroll_officer/payroll/item/{id}/deactivate',    [\App\Http\Controllers\PayrollOfficerPayrollController::class, 'deactivatePayrollItem'])->name('payroll_officer.payroll.item.deactivate');
    Route::delete('/payroll_officer/payroll/item/{id}/delete',      [\App\Http\Controllers\PayrollOfficerPayrollController::class, 'deletePayrollItem'])->name('payroll_officer.payroll.item.delete');
    Route::get('/payroll_officer/payroll/item/{id}',                [\App\Http\Controllers\PayrollOfficerPayrollController::class, 'getPayrollItem'])->name('payroll_officer.payroll.item.get');

    // ── Salary Grades ──────────────────────────────────────────────────────
    Route::post('/payroll_officer/payroll/grade/store',             [\App\Http\Controllers\PayrollOfficerPayrollController::class, 'storeGrade'])->name('payroll_officer.payroll.grade.store');
    Route::put('/payroll_officer/payroll/grade/{id}/update',        [\App\Http\Controllers\PayrollOfficerPayrollController::class, 'updateGrade'])->name('payroll_officer.payroll.grade.update');
    Route::delete('/payroll_officer/payroll/grade/{id}/delete',     [\App\Http\Controllers\PayrollOfficerPayrollController::class, 'deleteGrade'])->name('payroll_officer.payroll.grade.delete');
    Route::post('/payroll_officer/payroll/contrib/update',          [\App\Http\Controllers\PayrollOfficerPayrollController::class, 'updateContrib'])->name('payroll_officer.payroll.contrib.update');

    // ── Benefits ───────────────────────────────────────────────────────────
    Route::post('/payroll_officer/payroll/benefit/store',           [\App\Http\Controllers\PayrollOfficerPayrollController::class, 'storeBenefit'])->name('payroll_officer.payroll.benefit.store');
    Route::put('/payroll_officer/payroll/benefit/{id}/update',      [\App\Http\Controllers\PayrollOfficerPayrollController::class, 'updateBenefit'])->name('payroll_officer.payroll.benefit.update');
    Route::post('/payroll_officer/payroll/benefit/{id}/deactivate', [\App\Http\Controllers\PayrollOfficerPayrollController::class, 'deactivateBenefit'])->name('payroll_officer.payroll.benefit.deactivate');
    Route::delete('/payroll_officer/payroll/benefit/{id}/delete',   [\App\Http\Controllers\PayrollOfficerPayrollController::class, 'deleteBenefit'])->name('payroll_officer.payroll.benefit.delete');
    Route::get('/payroll_officer/payroll/benefit/{id}',             [\App\Http\Controllers\PayrollOfficerPayrollController::class, 'getBenefit'])->name('payroll_officer.payroll.benefit.get');

    // ══════════════════════════════════════════════════════════════════════
    // ── FINANCE OFFICER ROUTES ─────────────────────────────────────────────
    // ══════════════════════════════════════════════════════════════════════

    Route::get('/finance_officer/dashboard', [\App\Http\Controllers\FinanceOfficerDashboardController::class, 'index'])->name('finance_officer.dashboard');
    Route::get('/finance_officer/profile',   [\App\Http\Controllers\FinanceOfficerProfileController::class, 'profile'])->name('finance_officer.profile');
    Route::post('/finance_officer/announcements', [\App\Http\Controllers\AnnouncementController::class, 'store'])->name('finance_officer.announcements.store');

    Route::get('/finance_officer/payroll',  [\App\Http\Controllers\FinanceOfficerPayrollController::class, 'index'])->name('finance_officer.payroll');
    Route::get('/finance_officer/payslips', [\App\Http\Controllers\FinanceOfficerPayrollController::class, 'payslips'])->name('finance_officer.payslips');
    Route::get('/finance_officer/govpay',        [\App\Http\Controllers\FinanceOfficerPayrollController::class, 'govpay'])->name('finance_officer.govpay');
    Route::get('/finance_officer/govpay/{id}',   [\App\Http\Controllers\FinanceOfficerPayrollController::class, 'govpayView'])->name('finance_officer.govpay.view');

    // ── Finance Officer Payroll Actions ───────────────────────────────────────
    Route::post('/finance_officer/payroll/period/{id}/release',       [\App\Http\Controllers\FinanceOfficerPayrollController::class, 'releasePayroll'])->name('finance_officer.payroll.period.release');
    Route::get('/finance_officer/payroll/period/{id}/payslips',       [\App\Http\Controllers\FinanceOfficerPayrollController::class, 'periodPayslips'])->name('finance_officer.payroll.period.payslips');
    Route::get('/finance_officer/payroll/period/{id}/released-payslips', [\App\Http\Controllers\FinanceOfficerPayrollController::class, 'releasedPeriodPayslips'])->name('finance_officer.payroll.period.released-payslips');

    // ── Finance Officer Salary Grades ─────────────────────────────────────────
    Route::post('/finance_officer/payroll/grade/store',            [\App\Http\Controllers\FinanceOfficerPayrollController::class, 'storeGrade'])->name('finance_officer.payroll.grade.store');
    Route::put('/finance_officer/payroll/grade/{id}/update',       [\App\Http\Controllers\FinanceOfficerPayrollController::class, 'updateGrade'])->name('finance_officer.payroll.grade.update');
    Route::delete('/finance_officer/payroll/grade/{id}/delete',    [\App\Http\Controllers\FinanceOfficerPayrollController::class, 'deleteGrade'])->name('finance_officer.payroll.grade.delete');

    // ── Finance Officer Payroll Items ─────────────────────────────────────────
    Route::post('/finance_officer/payroll/item/store',             [\App\Http\Controllers\FinanceOfficerPayrollController::class, 'storePayrollItem'])->name('finance_officer.payroll.item.store');
    Route::put('/finance_officer/payroll/item/{id}/update',        [\App\Http\Controllers\FinanceOfficerPayrollController::class, 'updatePayrollItem'])->name('finance_officer.payroll.item.update');
    Route::post('/finance_officer/payroll/item/{id}/deactivate',   [\App\Http\Controllers\FinanceOfficerPayrollController::class, 'deactivatePayrollItem'])->name('finance_officer.payroll.item.deactivate');
    Route::delete('/finance_officer/payroll/item/{id}/delete',     [\App\Http\Controllers\FinanceOfficerPayrollController::class, 'deletePayrollItem'])->name('finance_officer.payroll.item.delete');
    Route::get('/finance_officer/payroll/item/{id}',               [\App\Http\Controllers\FinanceOfficerPayrollController::class, 'getPayrollItem'])->name('finance_officer.payroll.item.get');

    // ── Finance Officer Benefits ───────────────────────────────────────────────
    Route::post('/finance_officer/payroll/benefit/store',          [\App\Http\Controllers\FinanceOfficerPayrollController::class, 'storeBenefit'])->name('finance_officer.payroll.benefit.store');
    Route::put('/finance_officer/payroll/benefit/{id}/update',     [\App\Http\Controllers\FinanceOfficerPayrollController::class, 'updateBenefit'])->name('finance_officer.payroll.benefit.update');
    Route::post('/finance_officer/payroll/benefit/{id}/deactivate',[\App\Http\Controllers\FinanceOfficerPayrollController::class, 'deactivateBenefit'])->name('finance_officer.payroll.benefit.deactivate');
    Route::delete('/finance_officer/payroll/benefit/{id}/delete',  [\App\Http\Controllers\FinanceOfficerPayrollController::class, 'deleteBenefit'])->name('finance_officer.payroll.benefit.delete');
    Route::get('/finance_officer/payroll/benefit/{id}',            [\App\Http\Controllers\FinanceOfficerPayrollController::class, 'getBenefit'])->name('finance_officer.payroll.benefit.get');

    // ── Finance Officer Contributions ─────────────────────────────────────────
    Route::post('/finance_officer/payroll/contrib/update',         [\App\Http\Controllers\FinanceOfficerPayrollController::class, 'updateContrib'])->name('finance_officer.payroll.contrib.update');

    // ── Finance Officer Attendance ─────────────────────────────────────────
    Route::get('/finance_officer/attendance/reports',   [\App\Http\Controllers\FinanceOfficerAttendanceController::class, 'index'])->name('finance_officer.attendance.reports');
    Route::get('/finance_officer/attendance/today',     [\App\Http\Controllers\FinanceOfficerAttendanceController::class, 'today'])->name('finance_officer.attendance.today');
    Route::get('/finance_officer/attendance/records',   [\App\Http\Controllers\FinanceOfficerAttendanceController::class, 'records'])->name('finance_officer.attendance.records');
    Route::post('/finance_officer/attendance/clock-in', [\App\Http\Controllers\FinanceOfficerAttendanceController::class, 'clockIn'])->name('finance_officer.attendance.clock-in');
    Route::post('/finance_officer/attendance/clock-out',[\App\Http\Controllers\FinanceOfficerAttendanceController::class, 'clockOut'])->name('finance_officer.attendance.clock-out');
    Route::post('/finance_officer/attendance/break',    [\App\Http\Controllers\FinanceOfficerAttendanceController::class, 'breakStart'])->name('finance_officer.attendance.break');

    // ── Finance Officer Leave ──────────────────────────────────────────────
    Route::get('/finance_officer/leave/management',         [\App\Http\Controllers\FinanceOfficerAttendanceController::class, 'leaveManagement'])->name('finance_officer.leave.management');
    Route::post('/finance_officer/leave/file',              [\App\Http\Controllers\FinanceOfficerAttendanceController::class, 'fileLeave'])->name('finance_officer.leave.file');
    Route::post('/finance_officer/leave/{id}/cancel',       [\App\Http\Controllers\FinanceOfficerAttendanceController::class, 'cancelLeave'])->name('finance_officer.leave.cancel');
    Route::get('/finance_officer/leave/{id}',               [\App\Http\Controllers\FinanceOfficerAttendanceController::class, 'getLeaveRequest'])->name('finance_officer.leave.get');

    // ── Finance Officer Requests ───────────────────────────────────────────
    Route::get('/finance_officer/requests/pending',         [\App\Http\Controllers\FinanceOfficerAttendanceController::class, 'pendingRequests'])->name('finance_officer.requests.pending');
    Route::get('/finance_officer/requests/approved',        [\App\Http\Controllers\FinanceOfficerAttendanceController::class, 'approvedRequests'])->name('finance_officer.requests.approved');
    Route::post('/finance_officer/requests/{id}/cancel',    [\App\Http\Controllers\FinanceOfficerAttendanceController::class, 'cancelRequest'])->name('finance_officer.requests.cancel');
    Route::post('/finance_officer/requests/overtime/file',  [\App\Http\Controllers\FinanceOfficerAttendanceController::class, 'fileOvertimeRequest'])->name('finance_officer.requests.overtime.file');
    Route::post('/finance_officer/requests/shift/file',     [\App\Http\Controllers\FinanceOfficerAttendanceController::class, 'fileShiftChangeRequest'])->name('finance_officer.requests.shift.file');

    // ══════════════════════════════════════════════════════════════════════
    // ── SETTINGS ROUTES ────────────────────────────────────────────────────
    // ══════════════════════════════════════════════════════════════════════
    Route::prefix('settings')->name('settings.')->group(function () {
        Route::get('/',              [SettingsController::class, 'index'])->name('index');
        Route::post('/permissions',  [SettingsController::class, 'updatePermissions'])->name('permissions.update');
        Route::get('/general',       [SettingsController::class, 'general'])->name('general');
        Route::get('/permissions',   [SettingsController::class, 'permissions'])->name('permissions');
        Route::get('/notifications', [SettingsController::class, 'notifications'])->name('notifications');
        Route::get('/security',      [SettingsController::class, 'security'])->name('security');
        Route::get('/email',         [SettingsController::class, 'email'])->name('email');
    });

});

// Fallback
Route::fallback(function () {
    return redirect()->route('login');
});

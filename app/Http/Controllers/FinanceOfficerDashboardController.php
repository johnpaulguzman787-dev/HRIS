<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Carbon\Carbon;
use App\Models\Employee;
use App\Models\AttendanceLog;
use Illuminate\Support\Facades\Auth;

class FinanceOfficerDashboardController extends Controller
{
    public function index()
    {
        $today = Carbon::today();

        $authUser     = auth()->user();
        $authEmployee = Employee::with('jobTitle')->where('user_id', $authUser->id)->first();

        $dashInitials = $authEmployee
            ? strtoupper(substr($authEmployee->fname, 0, 1) . substr($authEmployee->lname, 0, 1))
            : strtoupper(substr($authUser->email, 0, 2));

        $dashName = $authEmployee
            ? trim($authEmployee->fname . ' ' . $authEmployee->lname)
            : $authUser->email;

        $dashRole = $authEmployee?->jobTitle?->title ?? $authUser->role;

        $month = $today->month;
        $year  = $today->year;

        $todayLog = $authEmployee ? AttendanceLog::where('employee_id', $authEmployee->id)
            ->whereDate('attendance_date', $today)
            ->with('shift')
            ->first() : null;

        $availableShifts = \App\Models\Shift::where('is_active', true)->get();

        $stats = $authEmployee ? [
            'present'  => AttendanceLog::where('employee_id', $authEmployee->id)->whereMonth('attendance_date', $month)->whereYear('attendance_date', $year)->whereIn('status', ['present', 'undertime', 'overtime'])->count(),
            'late'     => AttendanceLog::where('employee_id', $authEmployee->id)->whereMonth('attendance_date', $month)->whereYear('attendance_date', $year)->where('status', 'late')->count(),
            'absent'   => AttendanceLog::where('employee_id', $authEmployee->id)->whereMonth('attendance_date', $month)->whereYear('attendance_date', $year)->where('status', 'absent')->count(),
            'on_leave' => AttendanceLog::where('employee_id', $authEmployee->id)->whereMonth('attendance_date', $month)->whereYear('attendance_date', $year)->whereIn('status', ['on_leave', 'holiday'])->count(),
        ] : array_fill_keys(['present', 'late', 'absent', 'on_leave'], 0);

        $data = [
            'dashInitials'    => $dashInitials,
            'dashName'        => $dashName,
            'dashRole'        => $dashRole,
            'currentDate'     => $today->format('l, F j, Y'),
            'todayLog'        => $todayLog,
            'availableShifts' => $availableShifts,
            'employeeShift'   => $authEmployee ? \App\Models\EmployeeShift::with('shift')
                ->where('employee_id', $authEmployee->id)
                ->where('is_active', true)
                ->whereDate('effective_date', '<=', $today)
                ->where(function ($q) use ($today) {
                    $q->whereNull('end_date')->orWhereDate('end_date', '>=', $today);
                })
                ->latest('effective_date')
                ->first() : null,
            'stats'           => $stats,
            'month'           => $month,
            'year'            => $year,
            'upcomingHolidays' => \App\Models\Holiday::whereDate('date', '>=', $today)
                ->whereDate('date', '<=', Carbon::today()->endOfMonth())
                ->orderBy('date')
                ->get(),
            'calendarHolidays' => \App\Models\Holiday::whereYear('date', Carbon::today()->year)
                ->whereMonth('date', Carbon::today()->month)
                ->get(),
        ];

        return view('finance_officer.finance_dashboard', $data);
    }
}
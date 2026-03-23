<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use App\Models\Employee;
use App\Models\AttendanceLog;
use App\Models\Department;
use App\Models\EmployeeShift;

class SupervisorDashboardController extends Controller
{
    public function index()
    {
        $today = Carbon::today();

        $authUser     = Auth::user();
        $authEmployee = Employee::with('jobTitle')->where('user_id', $authUser->id)->first();
        $authDeptId   = $authEmployee?->department_id;
        $authEmpId    = $authEmployee?->id ?? 0;

        $dashInitials = $authEmployee
            ? strtoupper(substr($authEmployee->fname, 0, 1) . substr($authEmployee->lname, 0, 1))
            : strtoupper(substr($authUser->email, 0, 2));

        $dashName = $authEmployee
            ? trim($authEmployee->fname . ' ' . $authEmployee->lname)
            : $authUser->email;

        $dashRole = $authEmployee?->jobTitle?->title ?? $authUser->role;

        $todayLog = $authEmployee ? AttendanceLog::where('employee_id', $authEmployee->id)
            ->whereDate('attendance_date', $today)
            ->with('shift')
            ->first() : null;

        $employeeShift = $authEmployee ? \App\Models\EmployeeShift::with('shift')
            ->where('employee_id', $authEmployee->id)
            ->where('is_active', true)
            ->whereDate('effective_date', '<=', Carbon::today())
            ->where(function ($q) {
                $q->whereNull('end_date')->orWhereDate('end_date', '>=', Carbon::today());
            })
            ->latest('effective_date')
            ->first() : null;

        $availableShifts = \App\Models\Shift::where('is_active', true)->get();

        $departments = \App\Models\Department::with(['employees.attendanceLogs' => function ($q) use ($today) {
            $q->whereDate('attendance_date', $today);
        }])->where('id', $authDeptId)->get();

        $departmentProgress = $departments->map(function ($dept) {
            $total   = $dept->employees->count();
            $present = $dept->employees->filter(function ($emp) {
                return $emp->attendanceLogs->whereIn('status', ['present', 'undertime', 'overtime', 'late'])->count() > 0;
            })->count();
            $percentage = $total > 0 ? round($present / $total * 100) : 0;
            return [
                'name'       => $dept->name,
                'total'      => $total,
                'present'    => $present,
                'percentage' => $percentage,
            ];
        })->filter(fn($d) => $d['total'] > 0)->values();

        $data = [
            'dashInitials'    => $dashInitials,
            'dashName'        => $dashName,
            'dashRole'        => $dashRole,
            'todayLog'        => $todayLog,
            'employeeShift'   => $employeeShift,
            'availableShifts' => $availableShifts,
            'pendingRequests' => \App\Models\LeaveRequest::where('status', 'pending')
                ->whereHas('employee', fn($q) => $q->where('department_id', $authDeptId)->where('id', '!=', $authEmpId))
                ->count()
                + \App\Models\OvertimeRequest::where('status', 'pending')
                ->whereHas('employee', fn($q) => $q->where('department_id', $authDeptId)->where('id', '!=', $authEmpId))
                ->count()
                + \App\Models\ShiftChangeRequest::where('status', 'pending')
                ->whereHas('employee', fn($q) => $q->where('department_id', $authDeptId)->where('id', '!=', $authEmpId))
                ->count(),
            'currentDate'     => Carbon::today()->format('l, F j, Y'),

            'totalEmployees' => Employee::where('department_id', $authDeptId)
                ->where('id', '!=', $authEmpId)
                ->count(),

            'presentToday' => AttendanceLog::whereDate('attendance_date', $today)
                ->whereIn('status', ['present', 'undertime', 'overtime', 'late'])
                ->whereHas('employee', fn($q) => $q->where('department_id', $authDeptId)->where('id', '!=', $authEmpId))
                ->count(),

            'attendanceSummary' => [
                'present'  => AttendanceLog::whereDate('attendance_date', $today)
                    ->whereIn('status', ['present', 'undertime', 'overtime'])
                    ->whereHas('employee', fn($q) => $q->where('department_id', $authDeptId)->where('id', '!=', $authEmpId))
                    ->count(),
                'late'     => AttendanceLog::whereDate('attendance_date', $today)
                    ->where('late_minutes', '>', 0)
                    ->whereHas('employee', fn($q) => $q->where('department_id', $authDeptId)->where('id', '!=', $authEmpId))
                    ->count(),
                'absent'   => AttendanceLog::whereDate('attendance_date', $today)
                    ->where('status', 'absent')
                    ->whereHas('employee', fn($q) => $q->where('department_id', $authDeptId)->where('id', '!=', $authEmpId))
                    ->count(),
                'on_leave' => AttendanceLog::whereDate('attendance_date', $today)
                    ->whereIn('status', ['on_leave', 'holiday'])
                    ->whereHas('employee', fn($q) => $q->where('department_id', $authDeptId)->where('id', '!=', $authEmpId))
                    ->count(),
            ],

            'departmentProgress' => $departmentProgress,
            'upcomingHolidays'   => \App\Models\Holiday::whereDate('date', '>=', $today)
                ->whereDate('date', '<=', Carbon::today()->endOfMonth())
                ->orderBy('date')
                ->get(),
            'calendarHolidays'   => \App\Models\Holiday::whereYear('date', Carbon::today()->year)
                ->whereMonth('date', Carbon::today()->month)
                ->get(),
        ];

        return view('supervisor.supervisor_dashboard', $data);
    }
}
<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use App\Models\Employee;
use App\Models\AttendanceLog;
use App\Models\Department;
use App\Models\EmployeeShift;

class HRDashboardController extends Controller
{
    public function index()
    {
        $today = Carbon::today();

        $authUser     = Auth::user();
        $authEmployee = Employee::with('jobTitle')->where('user_id', $authUser->id)->first();

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

        $availableShifts = \App\Models\Shift::where('is_active', true)->get();

        $employeeShift = $authEmployee ? EmployeeShift::with('shift')
            ->where('employee_id', $authEmployee->id)
            ->where('is_active', true)
            ->whereDate('effective_date', '<=', Carbon::today())
            ->where(function ($q) {
                $q->whereNull('end_date')->orWhereDate('end_date', '>=', Carbon::today());
            })
            ->latest('effective_date')
            ->first() : null;

        $departments = Department::with(['employees.attendanceLogs' => function ($q) use ($today) {
            $q->whereDate('attendance_date', $today);
        }])->get();

        $departmentProgress = $departments->map(function ($dept) {
            $total   = $dept->employees->count();
            $present = $dept->employees->filter(function ($emp) {
                return $emp->attendanceLogs->whereIn('status', ['present', 'undertime', 'overtime', 'late'])->count() > 0;
            })->count();
            $percentage = $total > 0 ? round($present / $total * 100) : 0;
            return [
                'id'         => $dept->id,
                'name'       => $dept->name,
                'total'      => $total,
                'present'    => $present,
                'percentage' => $percentage,
            ];
        })->filter(fn($d) => $d['total'] > 0)->values();

        $allHolidays = \App\Models\Holiday::select('name', 'date', 'type')->get()->map(fn($h) => [
            'name'  => $h->name,
            'date'  => $h->date->format('Y-m-d'),
            'day'   => (int) $h->date->format('j'),
            'month' => (int) $h->date->format('n'),
            'year'  => (int) $h->date->format('Y'),
            'type'  => $h->type,
        ])->values();

        $data = [
            'dashInitials'    => $dashInitials,
            'dashName'        => $dashName,
            'dashRole'        => $dashRole,
            'totalEmployees'  => Employee::count(),
            'presentToday'    => AttendanceLog::whereDate('attendance_date', $today)->whereIn('status', ['present', 'undertime', 'overtime'])->count(),
            'lateToday'       => AttendanceLog::whereDate('attendance_date', $today)->whereIn('status', ['late', 'absent'])->count(),
            'pendingRequests' => \App\Models\LeaveRequest::whereIn('status', ['pending', 'supervisor_approved'])->count()
                + \App\Models\OvertimeRequest::whereIn('status', ['pending', 'supervisor_approved'])->count()
                + \App\Models\ShiftChangeRequest::whereIn('status', ['pending', 'supervisor_approved'])->count(),
            'todayLog'        => $todayLog,
            'availableShifts' => $availableShifts,
            'employeeShift'   => $employeeShift,
            'attendanceSummary' => [
                'present'  => AttendanceLog::whereDate('attendance_date', $today)->whereIn('status', ['present', 'undertime', 'overtime'])->count(),
                'late'     => AttendanceLog::whereDate('attendance_date', $today)->where('status', 'late')->count(),
                'absent'   => AttendanceLog::whereDate('attendance_date', $today)->where('status', 'absent')->count(),
                'on_leave' => AttendanceLog::whereDate('attendance_date', $today)->whereIn('status', ['on_leave', 'holiday'])->count(),
            ],
            'departmentAttendance' => (function () use ($today) {
                $logs = AttendanceLog::whereDate('attendance_date', $today)
                    ->join('employees', 'attendance_logs.employee_id', '=', 'employees.id')
                    ->whereNotNull('employees.department_id')
                    ->select('employees.department_id', 'attendance_logs.status')
                    ->get();

                $map = ['all' => ['present' => 0, 'late' => 0, 'absent' => 0, 'on_leave' => 0]];

                foreach ($logs as $log) {
                    $deptId = (string) $log->department_id;
                    if (!isset($map[$deptId])) {
                        $map[$deptId] = ['present' => 0, 'late' => 0, 'absent' => 0, 'on_leave' => 0];
                    }
                    if (in_array($log->status, ['present', 'undertime', 'overtime'])) {
                        $map[$deptId]['present']++; $map['all']['present']++;
                    } elseif ($log->status === 'late') {
                        $map[$deptId]['late']++; $map['all']['late']++;
                    } elseif ($log->status === 'absent') {
                        $map[$deptId]['absent']++; $map['all']['absent']++;
                    } elseif (in_array($log->status, ['on_leave', 'holiday'])) {
                        $map[$deptId]['on_leave']++; $map['all']['on_leave']++;
                    }
                }

                return $map;
            })(),
            'departments'        => $departments,
            'departmentProgress' => $departmentProgress,
            'currentDate'      => Carbon::today()->format('l, F j, Y'),
            'allHolidays' => $allHolidays,
        ];

        return view('hr.hr_dashboard', $data);
    }
}
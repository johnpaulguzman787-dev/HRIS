<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Carbon\Carbon;
use App\Models\Employee;
use App\Models\AttendanceLog;
use App\Models\Department;
use App\Models\LeaveRequest;
use App\Models\OvertimeRequest;
use App\Models\ShiftChangeRequest;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    public function index()
    {
        $today = Carbon::today();
        
        // Dummy data for dashboard
        $data = [
            'totalEmployees' => 156,
            'presentToday' => 130,
            'lateToday' => 18,
            'absentToday' => 10,
            'pendingRequests' => 12,
            'attendanceSummary' => [
                'present' => 128,
                'late' => 18,
                'absent' => 10,
                'on_leave' => 8
            ],
            'departmentProgress' => [
                ['name' => 'IT', 'total' => 45, 'present' => 42, 'percentage' => 93],
                ['name' => 'Finance', 'total' => 32, 'present' => 28, 'percentage' => 88],
                ['name' => 'Nursing', 'total' => 78, 'present' => 58, 'percentage' => 74],
            ],
            'pendingRequestsList' => [
                (object)[
                    'id' => 1,
                    'employee' => (object)[
                        'full_name' => 'John Doe',
                        'initials' => 'JD',
                        'position' => 'System Administrator'
                    ],
                    'type' => 'leave',
                    'title' => 'Annual Leave Request'
                ],
                (object)[
                    'id' => 2,
                    'employee' => (object)[
                        'full_name' => 'Jane Smith',
                        'initials' => 'JS',
                        'position' => 'IT Manager'
                    ],
                    'type' => 'overtime',
                    'title' => 'Overtime Approval'
                ],
                (object)[
                    'id' => 3,
                    'employee' => (object)[
                        'full_name' => 'Robert Johnson',
                        'initials' => 'RJ',
                        'position' => 'Financial Analyst'
                    ],
                    'type' => 'reimbursement',
                    'title' => 'Medical Reimbursement'
                ],
                (object)[
                    'id' => 4,
                    'employee' => (object)[
                        'full_name' => 'Maria Garcia',
                        'initials' => 'MG',
                        'position' => 'Head Nurse'
                    ],
                    'type' => 'adjustment',
                    'title' => 'Schedule Adjustment'
                ],
            ],
            'upcomingEvents' => [
                (object)[
                    'title' => 'Team Meeting',
                    'date' => Carbon::today()->addDays(2)->format('M d, Y'),
                    'time' => '10:00 AM',
                    'department' => 'All Departments',
                    'color' => 'blue'
                ],
                (object)[
                    'title' => 'Training Session',
                    'date' => Carbon::today()->addDays(4)->format('M d, Y'),
                    'time' => '2:00 PM',
                    'department' => 'IT Department',
                    'color' => 'green'
                ],
                (object)[
                    'title' => 'Health & Safety Workshop',
                    'date' => Carbon::today()->addDays(6)->format('M d, Y'),
                    'time' => '9:30 AM',
                    'department' => 'Nursing',
                    'color' => 'purple'
                ],
            ],
            'currentDate' => Carbon::today()->format('l, F j, Y'),
        ];
        
        return view('dashboard.index', $data);
    }

    /**
     * Show the admin dashboard
     * 
     * @return \Illuminate\View\View
     */


public function admin_dashboard()
{
    $today = Carbon::today();

    $authUser = auth()->user();
    $authEmployee = \App\Models\Employee::with('jobTitle')->where('user_id', $authUser->id)->first();
    $dashInitials = $authEmployee ? strtoupper(substr($authEmployee->fname, 0, 1) . substr($authEmployee->lname, 0, 1)) : strtoupper(substr($authUser->email, 0, 2));
    $dashName = $authEmployee ? trim($authEmployee->fname . ' ' . $authEmployee->lname) : $authUser->email;
    $dashRole = $authEmployee?->jobTitle?->title ?? $authUser->role;
    $todayLog = $authEmployee ? AttendanceLog::where('employee_id', $authEmployee->id)
    ->whereDate('attendance_date', $today)
    ->with('shift')
    ->first() : null;

$availableShifts = \App\Models\Shift::where('is_active', true)->get();

    $data = [
        'dashInitials' => $dashInitials,
        'dashName'     => $dashName,
        'dashRole'     => $dashRole,
        'totalEmployees' => Employee::count(), // ← REAL DATA
        'presentToday'   => AttendanceLog::whereDate('attendance_date', $today)->whereIn('status', ['present', 'undertime', 'overtime'])->count(),
'lateToday'      => AttendanceLog::whereDate('attendance_date', $today)->whereIn('status', ['late', 'absent'])->count(),
'absentToday'    => AttendanceLog::whereDate('attendance_date', $today)->where('status', 'absent')->count(),
'pendingRequests' => \App\Models\LeaveRequest::whereIn('status', ['pending', 'supervisor_approved'])->count()
    + \App\Models\OvertimeRequest::whereIn('status', ['pending', 'supervisor_approved'])->count()
    + \App\Models\ShiftChangeRequest::whereIn('status', ['pending', 'supervisor_approved'])->count(),
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
                    $map[$deptId]['present']++;
                    $map['all']['present']++;
                } elseif ($log->status === 'late') {
                    $map[$deptId]['late']++;
                    $map['all']['late']++;
                } elseif ($log->status === 'absent') {
                    $map[$deptId]['absent']++;
                    $map['all']['absent']++;
                } elseif (in_array($log->status, ['on_leave', 'holiday'])) {
                    $map[$deptId]['on_leave']++;
                    $map['all']['on_leave']++;
                }
            }

            return $map;
        })(),
        'departmentProgress' => Department::with(['employees.attendanceLogs' => function ($q) use ($today) {
            $q->whereDate('attendance_date', $today);
        }])->get()->map(function ($dept) {
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
        })->filter(fn($d) => $d['total'] > 0)->values()->toArray(),
        'pendingRequestsList' => [
            (object)['id' => 1, 'employee' => (object)['full_name' => 'John Doe', 'initials' => 'JD', 'position' => 'System Administrator'], 'type' => 'leave', 'title' => 'Annual Leave Request', 'date_submitted' => Carbon::today()->subDays(1)->format('M d, Y')],
            (object)['id' => 2, 'employee' => (object)['full_name' => 'Jane Smith', 'initials' => 'JS', 'position' => 'IT Manager'], 'type' => 'overtime', 'title' => 'Overtime Approval', 'date_submitted' => Carbon::today()->subDays(2)->format('M d, Y')],
            (object)['id' => 3, 'employee' => (object)['full_name' => 'Robert Johnson', 'initials' => 'RJ', 'position' => 'Financial Analyst'], 'type' => 'reimbursement', 'title' => 'Medical Reimbursement', 'date_submitted' => Carbon::today()->subDays(1)->format('M d, Y')],
            (object)['id' => 4, 'employee' => (object)['full_name' => 'Maria Garcia', 'initials' => 'MG', 'position' => 'Head Nurse'], 'type' => 'adjustment', 'title' => 'Schedule Adjustment', 'date_submitted' => Carbon::today()->format('M d, Y')],
            (object)['id' => 5, 'employee' => (object)['full_name' => 'James Wilson', 'initials' => 'JW', 'position' => 'HR Coordinator'], 'type' => 'leave', 'title' => 'Sick Leave Request', 'date_submitted' => Carbon::today()->subDays(3)->format('M d, Y')],
        ],
        'upcomingEvents' => [
            (object)['title' => 'Team Meeting', 'date' => Carbon::today()->addDays(2)->format('M d, Y'), 'time' => '10:00 AM', 'department' => 'All Departments', 'color' => 'blue'],
            (object)['title' => 'Training Session', 'date' => Carbon::today()->addDays(4)->format('M d, Y'), 'time' => '2:00 PM', 'department' => 'IT Department', 'color' => 'green'],
            (object)['title' => 'Health & Safety Workshop', 'date' => Carbon::today()->addDays(6)->format('M d, Y'), 'time' => '9:30 AM', 'department' => 'Nursing', 'color' => 'purple'],
            (object)['title' => 'Monthly Review', 'date' => Carbon::today()->addDays(9)->format('M d, Y'), 'time' => '3:00 PM', 'department' => 'Management', 'color' => 'red'],
        ],
        'departments'      => Department::orderBy('name')->get(),
        'currentDate'      => Carbon::today()->format('l, F j, Y'),
        'allHolidays' => \App\Models\Holiday::select('name', 'date', 'type')->get()->map(fn($h) => [
            'name'  => $h->name,
            'date'  => $h->date->format('Y-m-d'),
            'day'   => (int) $h->date->format('j'),
            'month' => (int) $h->date->format('n'),
            'year'  => (int) $h->date->format('Y'),
            'type'  => $h->type,
        ])->values(),
        'totalDepartments' => 7,
        'newHires' => 5,
        'birthdaysThisMonth' => 3,
    ];

    return view('admin.admin_dashboard', $data);
}

    /**
     * Show the user dashboard (optional)
     * 
     * @return \Illuminate\View\View
     */
    public function user_dashboard()
    {
        $today = Carbon::today();
        
        // User-specific data
        $data = [
            'currentDate' => Carbon::today()->format('l, F j, Y'),
            'myAttendance' => [
                'status' => 'Present',
                'time_in' => '08:15 AM',
                'time_out' => '--:-- --',
                'total_hours' => '3.5 hrs'
            ],
            'myRequests' => [
                (object)[
                    'type' => 'Leave',
                    'status' => 'Approved',
                    'date' => Carbon::today()->addDays(10)->format('M d, Y')
                ]
            ],
            'upcomingEvents' => [
                (object)[
                    'title' => 'Team Meeting',
                    'date' => Carbon::today()->addDays(2)->format('M d, Y'),
                    'time' => '10:00 AM'
                ]
            ]
        ];
        
        return view('dashboard.user_dashboard', $data);
    }
}
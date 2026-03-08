<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Carbon\Carbon;
  use App\Models\Employee;

class DashboardController extends Controller
{
    public function index()
    {
        $today = Carbon::today();
        
        // Dummy data for dashboard
        $data = [
            'totalEmployees' => 156,
            'presentToday' => 128,
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

    $data = [
        'dashInitials' => $dashInitials,
        'dashName'     => $dashName,
        'dashRole'     => $dashRole,
        'totalEmployees' => Employee::count(), // ← REAL DATA
        'presentToday' => 128,
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
            ['name' => 'HR', 'total' => 25, 'present' => 23, 'percentage' => 92],
            ['name' => 'Nursing', 'total' => 78, 'present' => 58, 'percentage' => 74],
            ['name' => 'Administration', 'total' => 15, 'present' => 14, 'percentage' => 93],
        ],
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
        'currentDate' => Carbon::today()->format('l, F j, Y'),
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
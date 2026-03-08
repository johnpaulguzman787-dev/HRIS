<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Carbon\Carbon;
use App\Models\Employee;

class SupervisorDashboardController extends Controller
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

        $data = [
            'dashInitials'   => $dashInitials,
            'dashName'       => $dashName,
            'dashRole'       => $dashRole,
            'totalEmployees' => Employee::count(),
            'presentToday'   => 45,
            'lateToday'      => 10,
            'absentToday'    => 5,
            'pendingRequests' => 5,
            'attendanceSummary' => [
                'present'  => 45,
                'late'     => 10,
                'absent'   => 5,
                'on_leave' => 0,
            ],
            'upcomingEvents' => [
                (object)['title' => 'Team Meeting',    'date' => $today->copy()->addDays(2)->format('M d, Y'), 'time' => '10:00 AM', 'department' => 'All Departments', 'color' => 'blue'],
                (object)['title' => 'Training Session', 'date' => $today->copy()->addDays(4)->format('M d, Y'), 'time' => '2:00 PM',  'department' => 'IT Department',   'color' => 'green'],
            ],
            'currentDate' => $today->format('l, F j, Y'),
        ];

        return view('supervisor.supervisor_dashboard', $data);
    }
}
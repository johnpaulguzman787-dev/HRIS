<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Carbon\Carbon;
use App\Models\Employee;

class EmployeeDashboardController extends Controller
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
            'currentDate'    => $today->format('l, F j, Y'),
        ];

        return view('employee.employee_dashboard', $data);
    }
}
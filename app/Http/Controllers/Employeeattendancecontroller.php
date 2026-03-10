<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Employee;

class EmployeeAttendanceController extends Controller
{
    public function index()
    {
        return view('employee.attendance-reports');
    }
}
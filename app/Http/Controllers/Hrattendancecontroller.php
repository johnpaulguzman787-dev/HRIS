<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class HRAttendanceController extends Controller
{
    public function index()
    {
        return view('hr.hr_attendance-reports');
    }
}
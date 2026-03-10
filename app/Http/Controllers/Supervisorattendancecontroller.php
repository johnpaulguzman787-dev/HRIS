<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class SupervisorAttendanceController extends Controller
{
    public function index()
    {
        return view('supervisor.supervisor_attendance-reports');
    }
}
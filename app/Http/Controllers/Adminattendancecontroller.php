<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class AdminAttendanceController extends Controller
{
    /**
     * Display the admin attendance reports page.
     */
    public function index()
    {
        return view('admin.admin_attendance-reports');
    }
}
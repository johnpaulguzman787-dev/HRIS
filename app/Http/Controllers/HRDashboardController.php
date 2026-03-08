<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Employee;

class HRDashboardController extends Controller
{
    public function index()
    {
        $totalEmployees = Employee::count();

        return view('hr.hr_dashboard', compact('totalEmployees'));
    }
}
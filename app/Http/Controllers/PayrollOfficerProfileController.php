<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class PayrollOfficerProfileController extends Controller
{
    public function profile()
    {
        $user = auth()->user();
        $employee = \App\Models\Employee::with(['department', 'jobTitle'])
            ->where('user_id', $user->id)
            ->first();

        return view('payroll_officer.payroll-profile', compact('user', 'employee'));
    }
}
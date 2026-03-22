<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class FinanceOfficerProfileController extends Controller
{
    public function profile()
    {
        $user = auth()->user();
        $employee = \App\Models\Employee::with(['department', 'jobTitle'])
            ->where('user_id', $user->id)
            ->first();

        return view('finance_officer.finance-profile', compact('user', 'employee'));
    }
}
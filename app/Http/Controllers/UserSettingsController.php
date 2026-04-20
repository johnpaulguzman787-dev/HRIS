<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class UserSettingsController extends Controller
{
    private array $roleConfig = [
        'hr'              => ['view' => 'hr.hr-settings',                       'route' => 'hr.settings.password'],
        'supervisor'      => ['view' => 'supervisor.supervisor-settings',        'route' => 'supervisor.settings.password'],
        'employee'        => ['view' => 'employee.employee-settings',            'route' => 'employee.settings.password'],
        'finance_officer' => ['view' => 'finance_officer.finance-settings',      'route' => 'finance_officer.settings.password'],
        'payroll_officer' => ['view' => 'payroll_officer.payroll-settings',      'route' => 'payroll_officer.settings.password'],
    ];

    private function roleFromRoute(): string
    {
        $name = request()->route()->getName(); // e.g. 'hr.settings'
        $prefix = explode('.', $name)[0];     // 'hr'
        return $prefix;
    }

    public function show()
    {
        $role = $this->roleFromRoute();
        return view($this->roleConfig[$role]['view']);
    }

    public function changePassword(Request $request)
    {
        $request->validate([
            'current_password'          => 'required',
            'new_password'              => 'required|min:8|confirmed',
            'new_password_confirmation' => 'required',
        ]);

        $user = auth()->user();

        if (!Hash::check($request->current_password, $user->password)) {
            return back()->withErrors(['current_password' => 'Current password is incorrect.'])->withInput();
        }

        $user->update(['password' => Hash::make($request->new_password)]);

        return back()->with('success', 'Password changed successfully.');
    }
}

<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Permission;

class SettingsController extends Controller
{
    private array $roles   = ['admin', 'hr_manager', 'supervisor', 'employee', 'payroll_officer', 'finance_officer'];
    private array $modules = ['Employee Management', 'Time & Attendance', 'Leave Management', 'Requests & Approval'];

    private array $defaults = [
        'Employee Management' => [
            'admin'           => ['can_view'=>true,  'can_create'=>true,  'can_edit'=>true,  'can_archive'=>true,  'can_import'=>true,  'can_export'=>true],
            'hr_manager'      => ['can_view'=>true,  'can_create'=>true,  'can_edit'=>true,  'can_archive'=>false, 'can_import'=>true,  'can_export'=>true],
            'supervisor'      => ['can_view'=>true,  'can_create'=>false, 'can_edit'=>false, 'can_archive'=>false, 'can_import'=>false, 'can_export'=>false],
            'employee'        => ['can_view'=>false, 'can_create'=>false, 'can_edit'=>false, 'can_archive'=>false, 'can_import'=>false, 'can_export'=>false],
            'payroll_officer' => ['can_view'=>true,  'can_create'=>false, 'can_edit'=>false, 'can_archive'=>false, 'can_import'=>false, 'can_export'=>false],
            'finance_officer' => ['can_view'=>true,  'can_create'=>false, 'can_edit'=>false, 'can_archive'=>false, 'can_import'=>false, 'can_export'=>false],
        ],
        'Time & Attendance' => [
            'admin'           => ['can_view'=>true,  'can_create'=>true,  'can_edit'=>true,  'can_archive'=>false, 'can_import'=>true,  'can_export'=>true],
            'hr_manager'      => ['can_view'=>true,  'can_create'=>true,  'can_edit'=>true,  'can_archive'=>false, 'can_import'=>true,  'can_export'=>true],
            'supervisor'      => ['can_view'=>true,  'can_create'=>true,  'can_edit'=>true,  'can_archive'=>false, 'can_import'=>false, 'can_export'=>false],
            'employee'        => ['can_view'=>true,  'can_create'=>true,  'can_edit'=>false, 'can_archive'=>false, 'can_import'=>false, 'can_export'=>false],
            'payroll_officer' => ['can_view'=>true,  'can_create'=>true,  'can_edit'=>false, 'can_archive'=>false, 'can_import'=>false, 'can_export'=>true],
            'finance_officer' => ['can_view'=>true,  'can_create'=>true,  'can_edit'=>false, 'can_archive'=>false, 'can_import'=>false, 'can_export'=>true],
        ],
        'Leave Management' => [
            'admin'           => ['can_view'=>true,  'can_create'=>true,  'can_edit'=>true,  'can_archive'=>false, 'can_import'=>false, 'can_export'=>false],
            'hr_manager'      => ['can_view'=>true,  'can_create'=>true,  'can_edit'=>true,  'can_archive'=>false, 'can_import'=>false, 'can_export'=>false],
            'supervisor'      => ['can_view'=>true,  'can_create'=>true,  'can_edit'=>true,  'can_archive'=>false, 'can_import'=>false, 'can_export'=>false],
            'employee'        => ['can_view'=>true,  'can_create'=>true,  'can_edit'=>false, 'can_archive'=>false, 'can_import'=>false, 'can_export'=>false],
            'payroll_officer' => ['can_view'=>true,  'can_create'=>true,  'can_edit'=>false, 'can_archive'=>false, 'can_import'=>false, 'can_export'=>false],
            'finance_officer' => ['can_view'=>true,  'can_create'=>true,  'can_edit'=>false, 'can_archive'=>false, 'can_import'=>false, 'can_export'=>false],
        ],
        'Requests & Approval' => [
            'admin'           => ['can_view'=>true,  'can_create'=>true,  'can_edit'=>true,  'can_archive'=>false, 'can_import'=>false, 'can_export'=>false],
            'hr_manager'      => ['can_view'=>true,  'can_create'=>true,  'can_edit'=>true,  'can_archive'=>false, 'can_import'=>false, 'can_export'=>false],
            'supervisor'      => ['can_view'=>true,  'can_create'=>true,  'can_edit'=>true,  'can_archive'=>false, 'can_import'=>false, 'can_export'=>false],
            'employee'        => ['can_view'=>true,  'can_create'=>true,  'can_edit'=>false, 'can_archive'=>false, 'can_import'=>false, 'can_export'=>false],
            'payroll_officer' => ['can_view'=>true,  'can_create'=>true,  'can_edit'=>false, 'can_archive'=>false, 'can_import'=>false, 'can_export'=>false],
            'finance_officer' => ['can_view'=>true,  'can_create'=>true,  'can_edit'=>false, 'can_archive'=>false, 'can_import'=>false, 'can_export'=>false],
        ],
    ];

    public function index()
    {
        $authUser     = auth()->user();
        $authEmployee = \App\Models\Employee::with('jobTitle')->where('user_id', $authUser->id)->first();

        // Seed default permissions if not yet in DB
        foreach ($this->roles as $role) {
            foreach ($this->modules as $module) {
                $def = $this->defaults[$module][$role] ?? [
                    'can_view'=>false, 'can_create'=>false, 'can_edit'=>false,
                    'can_archive'=>false, 'can_import'=>false, 'can_export'=>false,
                ];
                Permission::firstOrCreate(
                    ['role' => $role, 'module' => $module],
                    $def
                );
            }
        }

        $permissions = Permission::whereIn('role', $this->roles)
            ->whereIn('module', $this->modules)
            ->get()
            ->groupBy('role');

        return view('admin.settings', compact('permissions', 'authUser', 'authEmployee'));
    }

    public function updatePermissions(Request $request)
    {
        $request->validate([
            'role'   => 'required|in:admin,hr_manager,supervisor,employee,payroll_officer,finance_officer',
            'module' => 'required|string',
        ]);

        Permission::updateOrCreate(
            ['role' => $request->role, 'module' => $request->module],
            [
                'can_view'    => $request->boolean('can_view'),
                'can_create'  => $request->boolean('can_create'),
                'can_edit'    => $request->boolean('can_edit'),
                'can_archive' => $request->boolean('can_archive'),
                'can_import'  => $request->boolean('can_import'),
                'can_export'  => $request->boolean('can_export'),
            ]
        );

        return response()->json(['success' => true, 'message' => 'Permissions updated successfully!']);
    }

    public function general()      { return view('settings.general'); }
    public function notifications() { return view('settings.notifications'); }
    public function security()     { return view('settings.security'); }
    public function email()        { return view('settings.email'); }
}
<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Permission;
use App\Models\Employee;

class SettingsController extends Controller
{
    private array $roles = [
        'admin', 'hr_manager', 'supervisor', 'payroll_officer', 'finance_officer', 'employee',
    ];

    private array $modules = [
        'Employee Management', 'Time & Attendance', 'Leave Management', 'Requests & Approval', 'Payroll',
    ];

    /**
     * Feature definitions per module.
     * Only the actions listed here are shown in the permissions matrix UI.
     */
    private array $features = [
        'Employee Management' => [
            'icon'  => 'M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z',
            'color' => 'blue',
            'actions' => [
                'can_view'   => 'View Employee Directory',
                'can_create' => 'Add Employee',
                'can_edit'   => 'Edit Employee Info',
            ],
        ],
        'Time & Attendance' => [
            'icon'  => 'M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z',
            'color' => 'green',
            'actions' => [
                'can_view'   => 'View Attendance Reports',
                'can_export' => 'Export Attendance Report',
            ],
        ],
        'Leave Management' => [
            'icon'  => 'M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z',
            'color' => 'yellow',
            'actions' => [
                'can_view'   => 'View Leave Records',
                'can_create' => 'File Leave Request',
            ],
        ],
        'Requests & Approval' => [
            'icon'  => 'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2',
            'color' => 'purple',
            'actions' => [
                'can_view'   => 'View Requests',
                'can_create' => 'File OT / Shift Request',
                'can_edit'   => 'Approve / Reject Requests',
            ],
        ],
        'Payroll' => [
            'icon'  => 'M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z',
            'color' => 'indigo',
            'actions' => [
                'can_view'   => 'View Payroll & Payslips',
                'can_create' => 'Submit Payroll Period',
                'can_edit'   => 'Release / Approve Payroll',
                'can_export' => 'Export Payroll Reports',
            ],
        ],
    ];

    /**
     * N/A matrix: which actions are not applicable per role per module.
     * These roles have no UI (no blade) for those actions, so toggling them has no effect.
     */
    private array $notApplicable = [
        'admin'      => [],
        'hr_manager' => [
            'Payroll' => ['can_create', 'can_edit'],
        ],
        'supervisor' => [
            'Employee Management' => ['can_archive', 'can_import', 'can_export'],
            'Requests & Approval' => ['can_export'],
            'Payroll'             => ['can_create', 'can_edit', 'can_export'],
        ],
        'payroll_officer' => [
            'Employee Management' => ['can_create', 'can_edit', 'can_archive', 'can_import'],
            'Time & Attendance'   => ['can_create', 'can_edit'],
            'Leave Management'    => ['can_edit'],
            'Requests & Approval' => ['can_edit'],
            'Payroll'             => ['can_edit'],  // payroll officer submits, does NOT release
        ],
        'finance_officer' => [
            'Employee Management' => ['can_create', 'can_edit', 'can_archive', 'can_import'],
            'Time & Attendance'   => ['can_create', 'can_edit'],
            'Leave Management'    => ['can_edit'],
            'Requests & Approval' => ['can_edit'],
            'Payroll'             => ['can_create'], // finance officer releases, does NOT submit
        ],
        'employee' => [
            'Employee Management' => ['can_view', 'can_create', 'can_edit', 'can_archive', 'can_import', 'can_export'],
            'Time & Attendance'   => ['can_view', 'can_create', 'can_edit'],
            'Leave Management'    => ['can_view', 'can_edit', 'can_export'],
            'Requests & Approval' => ['can_view', 'can_edit', 'can_export'],
            'Payroll'             => ['can_create', 'can_edit', 'can_export'],
        ],
    ];

    /** Default values seeded on first visit */
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
            'payroll_officer' => ['can_view'=>true,  'can_create'=>false, 'can_edit'=>false, 'can_archive'=>false, 'can_import'=>false, 'can_export'=>true],
            'finance_officer' => ['can_view'=>true,  'can_create'=>false, 'can_edit'=>false, 'can_archive'=>false, 'can_import'=>false, 'can_export'=>true],
        ],
        'Leave Management' => [
            'admin'           => ['can_view'=>true,  'can_create'=>true,  'can_edit'=>true,  'can_archive'=>false, 'can_import'=>false, 'can_export'=>false],
            'hr_manager'      => ['can_view'=>true,  'can_create'=>true,  'can_edit'=>true,  'can_archive'=>false, 'can_import'=>false, 'can_export'=>false],
            'supervisor'      => ['can_view'=>true,  'can_create'=>true,  'can_edit'=>true,  'can_archive'=>false, 'can_import'=>false, 'can_export'=>false],
            'employee'        => ['can_view'=>true,  'can_create'=>true,  'can_edit'=>false, 'can_archive'=>false, 'can_import'=>false, 'can_export'=>false],
            'payroll_officer' => ['can_view'=>true,  'can_create'=>false, 'can_edit'=>false, 'can_archive'=>false, 'can_import'=>false, 'can_export'=>false],
            'finance_officer' => ['can_view'=>true,  'can_create'=>false, 'can_edit'=>false, 'can_archive'=>false, 'can_import'=>false, 'can_export'=>false],
        ],
        'Requests & Approval' => [
            'admin'           => ['can_view'=>true,  'can_create'=>true,  'can_edit'=>true,  'can_archive'=>false, 'can_import'=>false, 'can_export'=>false],
            'hr_manager'      => ['can_view'=>true,  'can_create'=>true,  'can_edit'=>true,  'can_archive'=>false, 'can_import'=>false, 'can_export'=>false],
            'supervisor'      => ['can_view'=>true,  'can_create'=>true,  'can_edit'=>true,  'can_archive'=>false, 'can_import'=>false, 'can_export'=>false],
            'employee'        => ['can_view'=>true,  'can_create'=>true,  'can_edit'=>false, 'can_archive'=>false, 'can_import'=>false, 'can_export'=>false],
            'payroll_officer' => ['can_view'=>true,  'can_create'=>true,  'can_edit'=>false, 'can_archive'=>false, 'can_import'=>false, 'can_export'=>false],
            'finance_officer' => ['can_view'=>true,  'can_create'=>true,  'can_edit'=>false, 'can_archive'=>false, 'can_import'=>false, 'can_export'=>false],
        ],
        'Payroll' => [
            'admin'           => ['can_view'=>true,  'can_create'=>true,  'can_edit'=>true,  'can_archive'=>false, 'can_import'=>false, 'can_export'=>true],
            'hr_manager'      => ['can_view'=>true,  'can_create'=>false, 'can_edit'=>false, 'can_archive'=>false, 'can_import'=>false, 'can_export'=>true],
            'supervisor'      => ['can_view'=>true,  'can_create'=>false, 'can_edit'=>false, 'can_archive'=>false, 'can_import'=>false, 'can_export'=>false],
            'employee'        => ['can_view'=>true,  'can_create'=>false, 'can_edit'=>false, 'can_archive'=>false, 'can_import'=>false, 'can_export'=>false],
            'payroll_officer' => ['can_view'=>true,  'can_create'=>true,  'can_edit'=>false, 'can_archive'=>false, 'can_import'=>false, 'can_export'=>true],
            'finance_officer' => ['can_view'=>true,  'can_create'=>false, 'can_edit'=>true,  'can_archive'=>false, 'can_import'=>false, 'can_export'=>true],
        ],
    ];

    public function index()
    {
        $authUser     = auth()->user();
        $authEmployee = Employee::with('jobTitle')->where('user_id', $authUser->id)->first();

        // Seed defaults if not yet in DB
        foreach ($this->roles as $role) {
            foreach ($this->modules as $module) {
                $def = $this->defaults[$module][$role] ?? [
                    'can_view' => false, 'can_create' => false, 'can_edit' => false,
                    'can_archive' => false, 'can_import' => false, 'can_export' => false,
                ];
                Permission::firstOrCreate(['role' => $role, 'module' => $module], $def);
            }
        }

        // Build matrix: [module][role] = { can_view, can_create, ... }
        $rawPerms = Permission::whereIn('role', $this->roles)
            ->whereIn('module', $this->modules)
            ->get();

        $matrix = [];
        foreach ($rawPerms as $perm) {
            $matrix[$perm->module][$perm->role] = [
                'can_view'    => (bool) $perm->can_view,
                'can_create'  => (bool) $perm->can_create,
                'can_edit'    => (bool) $perm->can_edit,
                'can_archive' => (bool) $perm->can_archive,
                'can_import'  => (bool) $perm->can_import,
                'can_export'  => (bool) $perm->can_export,
            ];
        }

        return view('admin.settings', [
            'authUser'      => $authUser,
            'authEmployee'  => $authEmployee,
            'matrix'        => $matrix,
            'features'      => $this->features,
            'notApplicable' => $this->notApplicable,
        ]);
    }

    public function updatePermissions(Request $request)
    {
        $request->validate([
            'role'   => 'required|in:admin,hr_manager,supervisor,employee,payroll_officer,finance_officer',
            'module' => 'required|string',
        ]);

        // Admin permissions are always full — never update via API
        if ($request->role === 'admin') {
            return response()->json(['success' => true, 'message' => 'Admin permissions are locked.']);
        }

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

        return response()->json(['success' => true, 'message' => 'Permission updated.']);
    }

    // Stub routes redirect to index
    public function general()      { return redirect()->route('settings.index'); }
    public function permissions()  { return redirect()->route('settings.index'); }
    public function notifications(){ return redirect()->route('settings.index'); }
    public function security()     { return redirect()->route('settings.index'); }
    public function email()        { return redirect()->route('settings.index'); }
}

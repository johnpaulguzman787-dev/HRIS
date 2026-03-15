<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Permission;

class SettingsController extends Controller
{
    private array $roles   = ['admin', 'hr_manager', 'supervisor', 'employee'];
    private array $modules = ['Employee Management'];

    public function index()
    {
        $authUser     = auth()->user();
        $authEmployee = \App\Models\Employee::with('jobTitle')->where('user_id', $authUser->id)->first();

        // Seed default permissions if not yet in DB
        foreach ($this->roles as $role) {
            foreach ($this->modules as $module) {
                Permission::firstOrCreate(
                    ['role' => $role, 'module' => $module],
                    [
                        'can_view'    => $role === 'admin',
                        'can_create'  => $role === 'admin',
                        'can_edit'    => $role === 'admin',
                        'can_archive' => false,
                        'can_import'  => $role === 'admin',
                        'can_export'  => $role === 'admin',
                    ]
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
            'role'   => 'required|in:admin,hr_manager,supervisor,employee',
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
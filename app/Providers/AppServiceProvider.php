<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Blade;
use App\Models\Permission;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void {}

    public function boot(): void
    {
        /**
         * @canDo('Module Name', 'action')
         *
         * Checks if the current user's role has a specific permission.
         * Usage in blade views:
         *
         *   @canDo('Employee Management', 'create')
         *       <button>Add Employee</button>
         *   @endcanDo
         *
         * Available modules:  'Employee Management', 'Time & Attendance', 'Leave Management', 'Requests & Approval'
         * Available actions:  'view', 'create', 'edit', 'archive', 'import', 'export'
         *
         * Admin always returns true. If no permission row exists, returns false.
         */
        Blade::if('canDo', function (string $module, string $action) {
            static $cache = [];

            $user = auth()->user();
            if (!$user) return false;

            // Admin always has full access
            if ($user->role === 'admin') return true;

            $cacheKey = $user->role . '|' . $module;
            if (!array_key_exists($cacheKey, $cache)) {
                $cache[$cacheKey] = Permission::where('role', $user->role)
                    ->where('module', $module)
                    ->first();
            }

            $perm = $cache[$cacheKey];
            return $perm ? (bool) $perm->{"can_{$action}"} : false;
        });
    }
}

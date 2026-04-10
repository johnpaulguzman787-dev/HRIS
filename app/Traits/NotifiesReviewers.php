<?php

namespace App\Traits;

use App\Models\Employee;
use App\Models\User;
use Illuminate\Support\Facades\DB;

trait NotifiesReviewers
{
    /**
     * Notify all HR managers about a request from admin or supervisor.
     */
    private function notifyHR(string $title, string $message): void
    {
        $hrIds = User::where('role', 'hr_manager')->pluck('id');
        $now   = now();
        foreach ($hrIds as $userId) {
            DB::table('notifications')->insert([
                'user_id'    => $userId,
                'title'      => $title,
                'message'    => $message,
                'icon'       => 'notice',
                'is_read'    => false,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }

    /**
     * Notify a single employee (via their linked user account).
     */
    private function notifyEmployee(int $employeeId, string $title, string $message, string $icon = 'process_done'): void
    {
        $emp = \App\Models\Employee::find($employeeId);
        if (!$emp || !$emp->user_id) return;

        DB::table('notifications')->insert([
            'user_id'    => $emp->user_id,
            'title'      => $title,
            'message'    => $message,
            'icon'       => $icon,
            'is_read'    => false,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /**
     * Notify admin, HR managers, and the supervisor of the employee's department
     * whenever an employee files a new request.
     */
    private function notifyReviewers(Employee $employee, string $title, string $message): void
    {
        $reviewerIds = User::whereIn('role', ['admin', 'hr_manager'])->pluck('id')->toArray();

        if ($employee->department_id) {
            $supervisorIds = User::where('role', 'supervisor')
                ->whereHas('employee', function ($q) use ($employee) {
                    $q->where('department_id', $employee->department_id);
                })
                ->pluck('id')
                ->toArray();
            $reviewerIds = array_unique(array_merge($reviewerIds, $supervisorIds));
        }

        $now = now();
        foreach ($reviewerIds as $userId) {
            DB::table('notifications')->insert([
                'user_id'    => $userId,
                'title'      => $title,
                'message'    => $message,
                'icon'       => 'notice',
                'is_read'    => false,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }
}

<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use App\Models\User;

class AnnouncementController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $user = Auth::user();

        $validated = $request->validate([
            'type'          => ['required', Rule::in(['notice', 'process_done', 'caution', 'warning'])],
            'title'         => ['required', 'string', 'max:255'],
            'message'       => ['required', 'string', 'max:2000'],
            'audience'      => ['nullable', Rule::in(['everyone', 'department'])],
            'department_id' => ['nullable', 'integer', 'exists:departments,id'],
        ]);

        if ($user->isSupervisor()) {
            // Auto-target the supervisor's own department
            $deptId = $user->employee?->department_id;
            if (!$deptId) {
                return response()->json(['message' => 'You have no department assigned.'], 422);
            }
            $userIds = User::join('employees', 'users.id', '=', 'employees.user_id')
                ->where('employees.department_id', $deptId)
                ->where('users.id', '!=', $user->id)
                ->whereNull('employees.deleted_at')
                ->pluck('users.id');
        } elseif (($validated['audience'] ?? '') === 'department') {
            if (empty($validated['department_id'])) {
                return response()->json(['message' => 'Please select a department.'], 422);
            }
            $userIds = User::join('employees', 'users.id', '=', 'employees.user_id')
                ->where('employees.department_id', $validated['department_id'])
                ->where('users.id', '!=', $user->id)
                ->whereNull('employees.deleted_at')
                ->pluck('users.id');
        } else {
            // everyone
            $userIds = User::where('id', '!=', $user->id)->pluck('id');
        }

        if ($userIds->isEmpty()) {
            return response()->json(['message' => 'No recipients found for this announcement.'], 422);
        }

        $now = now();
        $rows = $userIds->map(fn($id) => [
            'user_id'    => $id,
            'title'      => $validated['title'],
            'message'    => $validated['message'],
            'icon'       => $validated['type'],
            'link'       => null,
            'is_read'    => false,
            'created_at' => $now,
            'updated_at' => $now,
        ])->all();

        DB::table('notifications')->insert($rows);

        return response()->json(['success' => true]);
    }
}

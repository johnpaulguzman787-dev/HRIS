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

        $documents = $employee
            ? $employee->documents()->orderByDesc('created_at')->get()->map(fn($d) => [
                'id'           => $d->id,
                'name'         => $d->file_name,
                'file_type'    => strtolower($d->file_type),
                'file_size'    => $d->formatted_size,
                'created_at'   => $d->created_at->format('M j, Y'),
                'download_url' => route('profile.documents.download', $d->id),
              ])->values()
            : collect();

        return view('finance_officer.finance-profile', compact('user', 'employee', 'documents'));
    }
}
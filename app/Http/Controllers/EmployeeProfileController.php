<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class EmployeeProfileController extends Controller
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

        return view('employee.employee-profile', compact('user', 'employee', 'documents'));
    }

    public function downloadDocument($docId)
    {
        $user     = auth()->user();
        $employee = \App\Models\Employee::where('user_id', $user->id)->firstOrFail();
        $doc      = \App\Models\Document::where('id', $docId)
                        ->where('employee_id', $employee->id)
                        ->firstOrFail();

        $path = storage_path('app/public/' . $doc->file_path);
        abort_unless(file_exists($path), 404);

        return response()->download($path, $doc->file_name);
    }
}
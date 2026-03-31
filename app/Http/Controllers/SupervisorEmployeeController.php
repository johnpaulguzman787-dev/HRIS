<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Department;
use App\Models\Employee;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class SupervisorEmployeeController extends Controller
{
    private function getAuthDeptId()
    {
        $authUser     = Auth::user();
        $authEmployee = Employee::where('user_id', $authUser->id)->first();
        return $authEmployee?->department_id;
    }

    public function directory()
    {
        $authDeptId = $this->getAuthDeptId();

        $departments = Department::withCount('employees')
            ->with('jobTitles')
            ->where('id', $authDeptId)
            ->get()
            ->map(function($d) {
                return [
                    'id'              => $d->id,
                    'name'            => $d->name,
                    'employees_count' => $d->employees_count,
                    'job_titles'      => $d->jobTitles->map(fn($j) => ['id' => $j->id, 'title' => $j->title])->values(),
                ];
            });

        $jobTitles = \App\Models\JobTitle::where('department_id', $authDeptId)->get();

        $employees = Employee::with(['department', 'jobTitle', 'user'])
            ->where('department_id', $authDeptId)
            ->whereHas('user', fn($q) => $q->whereNotNull('email_verified_at'))
            ->get()
            ->map(function($e) {
                return [
                    'id'              => $e->id,
                    'avatar'          => strtoupper(substr($e->fname, 0, 1) . substr($e->lname, 0, 1)),
                    'name'            => trim($e->fname . ' ' . ($e->mi ? $e->mi . '. ' : '') . $e->lname),
                    'email'           => $e->user->email ?? '',
                    'department'      => $e->department->name ?? '',
                    'department_code' => $e->employee_code,
                    'job_title'       => $e->jobTitle->title ?? '',
                    'status'          => $e->employment_status,
                    'first_name'      => $e->fname,
                    'last_name'       => $e->lname,
                    'mi'              => $e->mi ?? '',
                    'contact_no'      => $e->contact_no,
                    'start_date'      => $e->start_date ? \Carbon\Carbon::parse($e->start_date)->format('Y-m-d') : '',
                    'department_id'   => $e->department_id,
                    'job_title_id'    => $e->job_title_id,
                    'role'              => $e->user->role ?? '',
                    'suffix'            => $e->suffix ?? '',
                    'gender'            => $e->gender ?? '',
                    'date_of_birth'     => $e->date_of_birth ? \Carbon\Carbon::parse($e->date_of_birth)->format('Y-m-d') : '',
                    'address'           => $e->address ?? '',
                    'employment_type'   => $e->employment_type ?? '',
                    'employment_status' => $e->employment_status ?? '',
                ];
            });

        $authUser     = Auth::user();
        $authEmployee = \App\Models\Employee::with('jobTitle')->where('user_id', $authUser->id)->first();

        return view('supervisor.supervisor_directory', compact('departments', 'employees', 'jobTitles', 'authUser', 'authEmployee'));
    }

    public function store(Request $request)
    {
        $authDeptId = $this->getAuthDeptId();

        $request->validate([
            'first_name'        => 'required|string|max:255',
            'last_name'         => 'required|string|max:255',
            'mi'                => 'nullable|string|max:5',
            'suffix'            => 'nullable|string|max:20',
            'email'             => 'required|email|unique:users,email',
            'contact_no'        => 'required|string|max:20',
            'gender'            => 'required|string',
            'date_of_birth'     => 'required|date',
            'address'           => 'required|string',
            'department_id'     => 'required|exists:departments,id',
            'job_title_id'      => 'required|exists:job_titles,id',
            'employment_type'   => 'required|string',
            'employment_status' => 'required|string',
            'contract_period'   => 'nullable|string',
            'end_date'          => 'nullable|date',
        ]);

        // Prevent supervisor from adding to other departments
        if ((int)$request->department_id !== (int)$authDeptId) {
            return response()->json(['success' => false, 'message' => 'You can only add employees to your own department.'], 403);
        }

        try {
            DB::transaction(function () use ($request) {

                $jobTitle = \DB::table('job_titles')
                    ->where('id', $request->job_title_id)
                    ->value('title');

                $role = match($jobTitle) {
                    'System Administrator' => 'admin',
                    'HR Manager'           => 'hr_manager',
                    'Finance Officer'      => 'finance_officer',
                    'Payroll Officer'      => 'payroll_officer',
                    'Supervisor'           => 'supervisor',
                    default                => 'employee',
                };

                $user = User::create([
                    'email'    => $request->email,
                    'password' => Hash::make('Welcome@123'),
                    'role'     => $role,
                ]);
                $user->sendEmailVerificationNotification();

                $prefixMap = [
                    'admin'           => 'ADM',
                    'hr_manager'      => 'HRM',
                    'supervisor'      => 'SUP',
                    'payroll_officer' => 'PAY',
                    'finance_officer' => 'FIN',
                    'employee'        => 'EMP',
                ];

                $prefix = $prefixMap[$user->role] ?? 'EMP';
                $count  = User::where('role', $user->role)->count();
                $code   = $prefix . str_pad($count, 3, '0', STR_PAD_LEFT);

                Employee::create([
                    'user_id'           => $user->id,
                    'employee_code'     => $code,
                    'fname'             => $request->first_name,
                    'mi'                => $request->mi,
                    'lname'             => $request->last_name,
                    'suffix'            => $request->suffix,
                    'gender'            => $request->gender,
                    'date_of_birth'     => $request->date_of_birth,
                    'contact_no'        => $request->contact_no,
                    'address'           => $request->address,
                    'department_id'     => $request->department_id,
                    'job_title_id'      => $request->job_title_id,
                    'employment_type'   => $request->employment_type,
                    'employment_status' => $request->employment_status,
                    'contract_period'   => $request->contract_period,
                    'start_date'        => now()->toDateString(),
                    'end_date'          => $request->end_date,
                ]);
            });

            return response()->json(['success' => true, 'message' => 'Employee added successfully!']);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function update(Request $request, $id)
    {
        $authDeptId = $this->getAuthDeptId();

        $employee = Employee::with('user')->findOrFail($id);

        // Prevent supervisor from editing employees outside their department
        if ((int)$employee->department_id !== (int)$authDeptId) {
            return response()->json(['success' => false, 'message' => 'You can only edit employees in your own department.'], 403);
        }

        $request->validate([
            'first_name'    => 'required|string|max:255',
            'last_name'     => 'required|string|max:255',
            'mi'            => 'nullable|string|max:3',
            'suffix'        => 'nullable|string|max:20',
            'email'         => 'required|email|unique:users,email,' . $employee->user->id,
            'contact_no'    => 'required|string|min:10|max:15',
            'department_id'   => 'required|exists:departments,id',
            'job_title_id'    => 'required|exists:job_titles,id',
            'start_date'      => 'required|date',
            'employment_type' => 'required|string',
        ]);

        // Prevent supervisor from moving employee to another department
        if ((int)$request->department_id !== (int)$authDeptId) {
            return response()->json(['success' => false, 'message' => 'You cannot move employees to another department.'], 403);
        }

        $jobTitle = \DB::table('job_titles')->where('id', $request->job_title_id)->value('title');
        $role = match($jobTitle) {
            'System Administrator' => 'admin',
            'HR Manager'           => 'hr_manager',
            'Finance Officer'      => 'finance_officer',
            'Payroll Officer'      => 'payroll_officer',
            'Supervisor'           => 'supervisor',
            default                => 'employee',
        };

        try {
            DB::transaction(function () use ($request, $employee, $role) {
                $emailChanged = $employee->user->email !== $request->email;

                $employee->user->update([
                    'email' => $request->email,
                    'role'  => $role,
                ]);

                if ($emailChanged) {
                    $employee->user->update(['email_verified_at' => null]);
                    $employee->user->sendEmailVerificationNotification();
                }

                $employee->update([
                    'fname'           => $request->first_name,
                    'lname'           => $request->last_name,
                    'mi'              => $request->mi,
                    'suffix'          => $request->suffix,
                    'contact_no'      => $request->contact_no,
                    'department_id'   => $request->department_id,
                    'job_title_id'    => $request->job_title_id,
                    'start_date'      => $request->start_date,
                    'employment_type' => $request->employment_type,
                ]);
            });

            return response()->json(['success' => true, 'message' => 'Employee updated successfully!']);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function updateDepartment(Request $request, $id)
    {
        $authDeptId = $this->getAuthDeptId();

        // Prevent supervisor from editing other departments
        if ((int)$id !== (int)$authDeptId) {
            return response()->json(['success' => false, 'message' => 'You can only manage your own department.'], 403);
        }

        $request->validate([
            'name'               => 'required|string|max:255',
            'job_titles'         => 'array',
            'job_titles.*.id'    => 'nullable|integer|exists:job_titles,id',
            'job_titles.*.title' => 'required|string|max:255',
        ]);

        try {
            DB::transaction(function () use ($request, $id) {
                $department = \App\Models\Department::findOrFail($id);

                $department->update(['name' => $request->name]);

                $incomingTitles = collect($request->job_titles ?? []);

                $keptIds = $incomingTitles
                    ->pluck('id')
                    ->filter()
                    ->values()
                    ->toArray();

                \App\Models\JobTitle::where('department_id', $id)
                    ->whereNotIn('id', $keptIds)
                    ->delete();

                foreach ($incomingTitles as $jt) {
                    if (!empty($jt['id'])) {
                        \App\Models\JobTitle::where('id', $jt['id'])
                            ->where('department_id', $id)
                            ->update(['title' => $jt['title']]);
                    } else {
                        \App\Models\JobTitle::create([
                            'department_id' => $id,
                            'title'         => $jt['title'],
                        ]);
                    }
                }
            });

            $updatedDepartment = \App\Models\Department::with('jobTitles')->findOrFail($id);

            return response()->json([
                'success'    => true,
                'message'    => 'Department updated successfully!',
                'job_titles' => $updatedDepartment->jobTitles->map(fn($j) => [
                    'id'    => $j->id,
                    'title' => $j->title,
                ])->values(),
            ]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

     public function profile()
    {
        $user = auth()->user();
        $employee = \App\Models\Employee::with(['department', 'jobTitle'])
            ->where('user_id', $user->id)
            ->first();

        return view('supervisor.supervisor-profile', compact('user', 'employee'));
    }

    public function getDocuments($id)
    {
        $authDeptId = $this->getAuthDeptId();
        $employee   = Employee::where('id', $id)->where('department_id', $authDeptId)->firstOrFail();
        $documents  = $employee->documents()->orderByDesc('created_at')->get()->map(fn($d) => [
            'id'         => $d->id,
            'file_name'  => $d->file_name,
            'file_type'  => strtolower($d->file_type),
            'file_size'  => $d->formatted_size,
            'created_at' => $d->created_at->format('M j, Y'),
        ]);
        return response()->json(['documents' => $documents]);
    }

    public function uploadDocument(Request $request, $id)
    {
        $authDeptId = $this->getAuthDeptId();
        $request->validate(['document' => 'required|file|mimes:pdf,doc,docx|max:10240']);
        $employee = Employee::where('id', $id)->where('department_id', $authDeptId)->firstOrFail();
        $file     = $request->file('document');
        $path     = $file->store('employee_documents', 'public');
        $doc = \App\Models\Document::create([
            'employee_id' => $employee->id,
            'file_name'   => $file->getClientOriginalName(),
            'file_path'   => $path,
            'file_type'   => strtolower($file->getClientOriginalExtension()),
            'file_size'   => $file->getSize(),
            'uploaded_by' => auth()->id(),
        ]);
        return response()->json([
            'success'  => true,
            'document' => [
                'id'         => $doc->id,
                'file_name'  => $doc->file_name,
                'file_type'  => $doc->file_type,
                'file_size'  => $doc->formatted_size,
                'created_at' => $doc->created_at->format('M j, Y'),
            ],
        ]);
    }

    public function downloadDocument($docId)
    {
        $authDeptId = $this->getAuthDeptId();
        $doc        = \App\Models\Document::findOrFail($docId);
        // Verify employee belongs to supervisor's dept
        $employee   = Employee::where('id', $doc->employee_id)->where('department_id', $authDeptId)->firstOrFail();
        $path       = storage_path('app/public/' . $doc->file_path);
        if (!file_exists($path)) {
            abort(404, 'File not found.');
        }
        if ($doc->file_type === 'pdf') {
            return response()->file($path, [
                'Content-Type'        => 'application/pdf',
                'Content-Disposition' => 'inline; filename="' . $doc->file_name . '"',
            ]);
        }
        return response()->download($path, $doc->file_name);
    }
}
<?php

namespace App\Http\Controllers;

use App\Models\Department;
use App\Models\Employee;

class ViewOnlyDirectoryController extends Controller
{
    private function directoryData(): array
    {
        $departments = Department::withCount(['employees' => fn($q) => $q->whereHas('user', fn($u) => $u->whereNotNull('email_verified_at'))])
            ->with('jobTitles')
            ->get()
            ->map(fn($d) => [
                'id'              => $d->id,
                'name'            => $d->name,
                'employees_count' => $d->employees_count,
                'job_titles'      => $d->jobTitles->map(fn($j) => ['id' => $j->id, 'title' => $j->title])->values(),
            ]);

        $jobTitles = \App\Models\JobTitle::all();

        $employees = Employee::with(['department', 'jobTitle', 'user'])
            ->whereHas('user', fn($q) => $q->whereNotNull('email_verified_at'))
            ->get()
            ->map(fn($e) => [
                'id'                => $e->id,
                'avatar'            => strtoupper(substr($e->fname, 0, 1) . substr($e->lname, 0, 1)),
                'name'              => trim($e->fname . ' ' . ($e->mi ? $e->mi . '. ' : '') . $e->lname),
                'email'             => $e->user->email ?? '',
                'department'        => $e->department->name ?? '',
                'department_code'   => $e->employee_code,
                'job_title'         => $e->jobTitle->title ?? '',
                'status'            => $e->employment_status,
                'first_name'        => $e->fname,
                'last_name'         => $e->lname,
                'mi'                => $e->mi ?? '',
                'contact_no'        => $e->contact_no,
                'start_date'        => $e->start_date ? \Carbon\Carbon::parse($e->start_date)->format('Y-m-d') : '',
                'department_id'     => $e->department_id,
                'job_title_id'      => $e->job_title_id,
                'role'              => $e->user->role ?? '',
                'suffix'            => $e->suffix ?? '',
                'gender'            => $e->gender ?? '',
                'date_of_birth'     => $e->date_of_birth ? \Carbon\Carbon::parse($e->date_of_birth)->format('Y-m-d') : '',
                'address'           => $e->address ?? '',
                'employment_type'   => $e->employment_type ?? '',
                'employment_status' => $e->employment_status ?? '',
            ]);

        return compact('departments', 'employees', 'jobTitles');
    }

    public function payroll()
    {
        return view('payroll_officer.payroll-officer_directory', $this->directoryData());
    }

    public function finance()
    {
        return view('finance_officer.finance-officer_directory', $this->directoryData());
    }
}

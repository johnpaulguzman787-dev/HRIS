<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Carbon\Carbon;
use App\Models\Employee;
use App\Models\EmployeeShift;
use App\Models\AttendanceLog;
use App\Models\LeaveRequest;

class MarkAbsentEmployees extends Command
{
    protected $signature   = 'attendance:mark-absent';
    protected $description = 'Mark employees as absent if they have an assigned shift but did not clock in and have no approved leave';

    public function handle()
    {
        $today = Carbon::today();
        $now   = Carbon::now();

        // Only run for employees who have an active shift assignment today
        $shiftAssignments = EmployeeShift::with(['employee', 'shift'])
            ->where('is_active', true)
            ->whereDate('effective_date', '<=', $today)
            ->where(function ($q) use ($today) {
                $q->whereNull('end_date')->orWhereDate('end_date', '>=', $today);
            })
            ->get()
            ->groupBy('employee_id')
            ->map(fn($rows) => $rows->sortByDesc('effective_date')->first());

        $marked = 0;

        foreach ($shiftAssignments as $employeeId => $assignment) {
            $employee = $assignment->employee;
            $shift    = $assignment->shift;

            if (!$employee || !$shift || $employee->employment_status !== 'Active') {
                continue;
            }

            // Only mark absent after the shift has started (with a 30-min grace period)
            $shiftStart = Carbon::createFromTimeString($today->toDateString() . ' ' . $shift->start_time);
            if ($now->lt($shiftStart->copy()->addMinutes(30))) {
                continue;
            }

            // Skip if already has an attendance log today
            $hasLog = AttendanceLog::where('employee_id', $employeeId)
                ->whereDate('attendance_date', $today)
                ->exists();

            if ($hasLog) {
                continue;
            }

            // Skip if on approved leave today
            $onLeave = LeaveRequest::where('employee_id', $employeeId)
                ->where('status', 'approved')
                ->whereDate('start_date', '<=', $today)
                ->whereDate('end_date', '>=', $today)
                ->exists();

            if ($onLeave) {
                continue;
            }

            // Mark as absent
            AttendanceLog::create([
                'employee_id'     => $employeeId,
                'attendance_date' => $today,
                'shift_id'        => $assignment->shift_id,
                'status'          => 'absent',
            ]);

            $marked++;
            $this->line("Marked absent: {$employee->full_name}");
        }

        $this->info("Done. Marked {$marked} employee(s) as absent.");
    }
}

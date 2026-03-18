<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Carbon\Carbon;
use App\Models\Holiday;
use App\Models\Employee;
use App\Models\AttendanceLog;

class MarkHolidayAttendance extends Command
{
    protected $signature   = 'attendance:mark-holidays';
    protected $description = 'Mark employees as holiday status if today is a holiday and they did not clock in';

    public function handle()
    {
        $today   = Carbon::today();
        $holiday = Holiday::whereDate('date', $today)->first();

        if (!$holiday) {
            $this->info('No holiday today. Nothing to do.');
            return;
        }

        $employees = Employee::where('employment_status', 'Active')->get();

        $marked = 0;
        foreach ($employees as $emp) {
            $existing = AttendanceLog::where('employee_id', $emp->id)
                ->whereDate('attendance_date', $today)
                ->first();

            if (!$existing) {
                AttendanceLog::create([
                    'employee_id'    => $emp->id,
                    'holiday_id'     => $holiday->id,
                    'attendance_date'=> $today->toDateString(),
                    'status'         => 'holiday',
                    'late_minutes'   => 0,
                    'undertime_minutes' => 0,
                    'overtime_minutes'  => 0,
                    'total_hours'    => 0,
                    'break_minutes'  => 0,
                ]);
                $marked++;
            }
        }

        $this->info("Marked {$marked} employees as holiday for {$today->toDateString()}.");
    }
}
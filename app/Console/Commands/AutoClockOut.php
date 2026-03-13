<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Carbon\Carbon;
use App\Models\AttendanceLog;
use App\Models\Shift;

class AutoClockOut extends Command
{
    protected $signature   = 'attendance:auto-clockout';
    protected $description = 'Auto clock-out employees who forgot to clock out based on their shift end time';

    public function handle()
    {
        $yesterday = Carbon::yesterday();

        // Find all logs from yesterday with clock_in but no clock_out
        $incompleteLogs = AttendanceLog::with('shift')
            ->whereDate('attendance_date', $yesterday)
            ->whereNotNull('clock_in')
            ->whereNull('clock_out')
            ->get();

        if ($incompleteLogs->isEmpty()) {
            $this->info('No incomplete logs found.');
            return;
        }

        foreach ($incompleteLogs as $log) {
            $shift = $log->shift;

            if (!$shift) {
                $this->warn("Log ID {$log->id} has no shift — skipping.");
                continue;
            }

            // Auto clock-out time = shift end time on that day
            $autoClockOut = Carbon::createFromTimeString(
                $yesterday->toDateString() . ' ' . $shift->end_time
            );

            // If they were on break and never resumed, close the break too
            $breakMinutes = $log->break_minutes;
            if ($log->break_start && !$log->break_end) {
                $breakEnd     = $autoClockOut;
                $breakMinutes = (int) Carbon::parse($log->break_start)->diffInMinutes($breakEnd);
                $log->break_end     = $breakEnd;
                $log->break_minutes = $breakMinutes;
            }

            // Compute total hours minus break
            $clockIn    = Carbon::parse($log->clock_in);
            $totalHours = round(($clockIn->diffInMinutes($autoClockOut) - $breakMinutes) / 60, 2);

            // Determine overtime or undertime
            $shiftEnd         = Carbon::createFromTimeString($yesterday->toDateString() . ' ' . $shift->end_time);
            $overtimeMinutes  = 0;
            $undertimeMinutes = 0;

            // Auto clock-out is exactly at shift end so neither overtime nor undertime
            // Status stays as-is (present/late) since we're clocking out exactly at shift end

            $log->update([
                'clock_out'         => $autoClockOut,
                'total_hours'       => $totalHours,
                'overtime_minutes'  => $overtimeMinutes,
                'undertime_minutes' => $undertimeMinutes,
                'break_end'         => $log->break_end,
                'break_minutes'     => $breakMinutes,
            ]);

            $this->info("Auto clocked out Log ID {$log->id} at {$autoClockOut->format('h:i A')}");
        }

        $this->info('Auto clock-out completed.');
    }
}
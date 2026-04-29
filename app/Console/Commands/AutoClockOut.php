<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Carbon\Carbon;
use App\Models\AttendanceLog;

class AutoClockOut extends Command
{
    protected $signature   = 'attendance:auto-clockout';
    protected $description = 'Auto clock-out employees who forgot to clock out based on their shift end time';

    public function handle()
    {
        // Find all logs before today with clock_in but no clock_out
        $incompleteLogs = AttendanceLog::with('shift')
            ->whereDate('attendance_date', '<', Carbon::today())
            ->whereNotNull('clock_in')
            ->whereNull('clock_out')
            ->get();

        if ($incompleteLogs->isEmpty()) {
            $this->info('No incomplete logs found.');
            return;
        }

        foreach ($incompleteLogs as $log) {
            $shift   = $log->shift;
            $dateStr = Carbon::parse($log->attendance_date)->toDateString();

            if (!$shift) {
                $this->warn("Log ID {$log->id} has no shift — skipping.");
                continue;
            }

            // Auto clock-out time = shift end time on the log's actual date
            $autoClockOut = Carbon::createFromTimeString($dateStr . ' ' . $shift->end_time);

            // Night shift: if end_time is earlier than start_time, it crosses midnight
            if ($autoClockOut->lt(Carbon::createFromTimeString($dateStr . ' ' . $shift->start_time))) {
                $autoClockOut->addDay();
            }

            // If they were on break and never resumed, close the break too
            $breakMinutes = $log->break_minutes;
            if ($log->break_start && !$log->break_end) {
                $breakEnd         = $autoClockOut;
                $maxBreakMinutes  = max(0, (int) Carbon::parse($log->clock_in)->diffInMinutes($autoClockOut));
                $breakMinutes     = min(
                    (int) Carbon::parse($log->break_start)->diffInMinutes($breakEnd),
                    $maxBreakMinutes
                );
                $log->break_end     = $breakEnd;
                $log->break_minutes = $breakMinutes;
            }

            // Compute total hours minus break
            $clockIn    = Carbon::parse($log->clock_in);
            $totalHours = round(($clockIn->diffInMinutes($autoClockOut) - $breakMinutes) / 60, 2);

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
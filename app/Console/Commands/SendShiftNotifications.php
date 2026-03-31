<?php

namespace App\Console\Commands;

use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use App\Models\EmployeeShift;

class SendShiftNotifications extends Command
{
    protected $signature   = 'shift:send-notifications';
    protected $description = 'Send break time and shift end notifications to employees';

    public function handle(): void
    {
        $now         = Carbon::now();
        $today       = $now->toDateString();
        $currentTime = $now->format('H:i');
        $todayName   = $now->dayName; // e.g. "Monday"

        $employeeShifts = EmployeeShift::with(['employee.user', 'shift'])
            ->where('is_active', true)
            ->where('effective_date', '<=', $today)
            ->where(function ($q) use ($today) {
                $q->whereNull('end_date')->orWhere('end_date', '>=', $today);
            })
            ->get();

        foreach ($employeeShifts as $empShift) {
            $employee = $empShift->employee;
            if (!$employee || !$employee->user) continue;

            $userId = $employee->user->id;

            // Skip if today is a day off
            $daysOff = $empShift->days_off ?? [];
            if (is_string($daysOff)) {
                $daysOff = json_decode($daysOff, true) ?? [];
            }
            if (in_array($todayName, $daysOff)) continue;

            $shift = $empShift->shift;
            if (!$shift) continue;

            // Break schedule: employee shift overrides shift-level schedule
            $breakSchedule = $empShift->break_schedule ?? $shift->break_schedule ?? null;
            if (is_string($breakSchedule)) {
                $breakSchedule = json_decode($breakSchedule, true);
            }

            // ── Shift end notification ──────────────────────────────────────
            $shiftEnd = Carbon::parse($shift->end_time)->format('H:i');
            if ($currentTime === $shiftEnd) {
                $this->sendIfNotSent(
                    $userId,
                    'Shift Ended',
                    "Your shift ({$shift->name}) has ended. Don't forget to clock out!",
                    'notice',
                    $today
                );
            }

            // ── Break start notification ────────────────────────────────────
            if ($breakSchedule && isset($breakSchedule['start'])) {
                $breakStart = Carbon::parse($breakSchedule['start'])->format('H:i');
                if ($currentTime === $breakStart) {
                    $end = $breakSchedule['end'] ?? '';
                    $this->sendIfNotSent(
                        $userId,
                        'Break Time',
                        "It's break time! Your break runs from {$breakSchedule['start']}" . ($end ? " to {$end}" : '') . '.',
                        'notice',
                        $today
                    );
                }
            }

            // ── Break end notification ──────────────────────────────────────
            if ($breakSchedule && isset($breakSchedule['end'])) {
                $breakEnd = Carbon::parse($breakSchedule['end'])->format('H:i');
                if ($currentTime === $breakEnd) {
                    $this->sendIfNotSent(
                        $userId,
                        'Break Over',
                        "Your break has ended. Time to get back to work!",
                        'notice',
                        $today
                    );
                }
            }
        }

        $this->info("Shift notifications processed at {$currentTime}.");
    }

    private function sendIfNotSent(int $userId, string $title, string $message, string $icon, string $today): void
    {
        $alreadySent = DB::table('notifications')
            ->where('user_id', $userId)
            ->where('title', $title)
            ->whereDate('created_at', $today)
            ->exists();

        if (!$alreadySent) {
            DB::table('notifications')->insert([
                'user_id'    => $userId,
                'title'      => $title,
                'message'    => $message,
                'icon'       => $icon,
                'is_read'    => false,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
}

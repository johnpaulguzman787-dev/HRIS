<?php

namespace App\Console\Commands;

use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use App\Models\PayrollPeriod;

class SendPayrollCutoffNotifications extends Command
{
    protected $signature   = 'payroll:cutoff-notifications';
    protected $description = 'Send notifications to payroll/finance officers 3 days before payroll cutoff';

    public function handle(): void
    {
        $targetDate = Carbon::today()->addDays(3)->toDateString();

        // Find active periods whose end_date is exactly 3 days from today
        $periods = PayrollPeriod::whereIn('status', ['Pending', 'Submitted'])
            ->where('end_date', $targetDate)
            ->get();

        if ($periods->isEmpty()) {
            $this->info('No periods with cutoff in 3 days.');
            return;
        }

        // Get all payroll officers and finance officers
        $userIds = DB::table('users')
            ->whereIn('role', ['payroll_officer', 'finance_officer'])
            ->pluck('id');

        if ($userIds->isEmpty()) {
            $this->info('No payroll/finance officers found.');
            return;
        }

        $now = now();

        foreach ($periods as $period) {
            $rows = $userIds->map(fn($uid) => [
                'user_id'    => $uid,
                'title'      => 'Payroll Cutoff in 3 Days',
                'message'    => "Payroll period \"{$period->name}\" ends on " . Carbon::parse($period->end_date)->format('F j, Y') . ". Make sure all payslips are submitted before the cutoff.",
                'icon'       => 'caution',
                'link'       => null,
                'is_read'    => false,
                'created_at' => $now,
                'updated_at' => $now,
            ])->all();

            // Avoid duplicate notifications for the same period on the same day
            $alreadySent = DB::table('notifications')
                ->where('title', 'Payroll Cutoff in 3 Days')
                ->where('message', 'like', "%{$period->name}%")
                ->whereDate('created_at', today())
                ->exists();

            if (!$alreadySent) {
                DB::table('notifications')->insert($rows);
                $this->info("Notified {$userIds->count()} users for period: {$period->name}");
            } else {
                $this->info("Already notified today for period: {$period->name}");
            }
        }
    }
}

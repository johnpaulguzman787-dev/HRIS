<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\PayrollPeriod;
use App\Models\Payslip;
use Illuminate\Support\Facades\DB;

class EmployeePayrollController extends Controller
{
    /**
     * Determine the Blade view path based on the authenticated user's role.
     * Handles hr_manager, supervisor, and employee.
     */
    private function viewFor(string $page): string
    {
        $role = auth()->user()->role ?? 'employee';

        [$folder, $prefix] = match ($role) {
            'hr_manager' => ['hr',         'hr'],
            'supervisor' => ['supervisor', 'supervisor'],
            default      => ['employee',   'employee'],
        };

        return "{$folder}.{$prefix}_{$page}";
    }

    // ── Payslips ──────────────────────────────────────────────────────────────

    public function payslips()
    {
        $user         = auth()->user();
        $authEmployee = $user->employee ?? null;

        $myPayslips = collect();

        if ($authEmployee) {
            $authEmployee->load('jobTitle', 'department');
            $slips = Payslip::with('period')
                ->where('employee_id', $authEmployee->id)
                ->where('status', 'Released')
                ->orderByDesc('created_at')
                ->get();

            $myPayslips = $slips->map(fn($p) => [
                'id'             => $p->id,
                'periodId'       => $p->payroll_period_id,
                'periodName'     => $p->period->name,
                'startDate'      => $p->period->start_date->format('m/d/Y'),
                'endDate'        => $p->period->end_date->format('m/d/Y'),
                'year'           => $p->period->start_date->year,
                'basicPay'       => (float) $p->basic_pay,
                'otPay'          => (float) $p->ot_pay,
                'benefits'       => (float) $p->benefits_total,
                'grossPay'       => (float) $p->gross_pay,
                'sss'            => (float) $p->sss,
                'philhealth'     => (float) $p->philhealth,
                'pagibig'        => (float) $p->pagibig,
                'withholdingTax' => (float) $p->withholding_tax,
                'totalDeductions'=> (float) $p->total_deductions,
                'netPay'         => (float) $p->net_pay,
                'status'         => $p->status,
            ])->values();
        }

        return view($this->viewFor('payslips'), compact('authEmployee', 'myPayslips'));
    }

    // ── Government Contributions ──────────────────────────────────────────────

    public function govpay(Request $request)
    {
        $year         = $request->get('year', now()->year);
        $authEmployee = auth()->user()->employee ?? null;

        $myContributions = collect();

        if ($authEmployee) {
            $myContributions = DB::table('payslips as ps')
                ->join('payroll_periods as pp', 'ps.payroll_period_id', '=', 'pp.id')
                ->where('ps.employee_id', $authEmployee->id)
                ->where('pp.status', 'Released')
                ->whereYear('pp.start_date', $year)
                ->select(
                    'pp.name as period_name',
                    'pp.status',
                    'ps.sss',
                    'ps.philhealth',
                    'ps.pagibig',
                    'ps.withholding_tax as tax'
                )
                ->orderByDesc('pp.start_date')
                ->get();
        }

        $years = range(now()->year, now()->year - 3);

        return view($this->viewFor('govpay'), compact('myContributions', 'years', 'year'));
    }
}

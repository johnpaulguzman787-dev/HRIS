<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\PayrollPeriod;
use App\Models\Employee;
use App\Models\EmployeePayroll;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class PayrollOfficerGovpayController extends Controller
{
    /**
     * Main page — All Contributions + My Contributions tabs
     */
    public function index(Request $request)
    {
        $year = $request->get('year', now()->year);

        // ── ALL CONTRIBUTIONS ────────────────────────────────────────────────
        // Aggregate per payroll period: sum SSS, PhilHealth, Pag-IBIG, W/Tax
        $contributions = DB::table('employee_payrolls as ep')
            ->join('payroll_periods as pp', 'ep.payroll_period_id', '=', 'pp.id')
            ->whereYear('pp.start_date', $year)
            ->select(
                'pp.id as payroll_period_id',
                'pp.name as period_name',
                'pp.status',
                DB::raw('SUM(ep.sss)       as sss_total'),
                DB::raw('SUM(ep.philhealth) as philhealth_total'),
                DB::raw('SUM(ep.pagibig)   as pagibig_total'),
                DB::raw('SUM(ep.tax)       as tax_total')
            )
            ->groupBy('pp.id', 'pp.name', 'pp.status')
            ->orderByDesc('pp.start_date')
            ->get();

        // ── MY CONTRIBUTIONS ─────────────────────────────────────────────────
        // Contributions of the currently logged-in employee
        $authEmployee = Employee::where('user_id', Auth::id())->first();

        $myContributions = collect();
        if ($authEmployee) {
            $myContributions = DB::table('employee_payrolls as ep')
                ->join('payroll_periods as pp', 'ep.payroll_period_id', '=', 'pp.id')
                ->where('ep.employee_id', $authEmployee->id)
                ->whereYear('pp.start_date', $year)
                ->select(
                    'pp.name as period_name',
                    'pp.status',
                    'ep.sss      as sss_ee',
                    'ep.sss_er   as sss_er',
                    'ep.philhealth',
                    'ep.pagibig',
                    'ep.tax'
                )
                ->orderByDesc('pp.start_date')
                ->get();
        }

        $years  = range(now()->year, now()->year - 3);
        $isView = false;

        return view('payroll_officer.payroll-officer_govpay', compact(
            'contributions', 'myContributions', 'years', 'year', 'isView'
        ));
    }

    /**
     * View — Employee-level contributions for a specific payroll period
     */
    public function view(Request $request, $periodId)
    {
        $year   = $request->get('year', now()->year);
        $period = PayrollPeriod::findOrFail($periodId);

        // Per-employee contributions for this period
        $records = DB::table('employee_payrolls as ep')
            ->join('employees as e', 'ep.employee_id', '=', 'e.id')
            ->where('ep.payroll_period_id', $periodId)
            ->select(
                'e.fname',
                'e.lname',
                'ep.sss      as sss_ee',
                'ep.sss_er',
                'ep.philhealth',
                'ep.pagibig',
                'ep.tax',
                'ep.status'
            )
            ->orderBy('e.lname')
            ->get();

        $years      = range(now()->year, now()->year - 3);
        $periodName = $period->name;
        $isView     = true;

        return view('payroll_officer.payroll-officer_govpay', compact(
            'records', 'years', 'year', 'periodName', 'periodId', 'isView'
        ));
    }
}
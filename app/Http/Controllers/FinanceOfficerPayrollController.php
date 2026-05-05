<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\SalaryGrade;
use App\Models\PayrollItem;
use App\Models\Benefit;
use App\Models\PayrollPeriod;
use App\Models\Payslip;
use App\Models\Employee;
use Illuminate\Support\Facades\DB;

class FinanceOfficerPayrollController extends Controller
{
    // ── Index ────────────────────────────────────────────────────────────────

    public function index(Request $request)
    {
        $year = $request->input('year', now()->year);

        $periods = PayrollPeriod::whereIn('status', ['Submitted', 'Released'])
            ->orderByDesc('start_date')->get();

        $latestPeriod    = $periods->first();
        $grossPayroll    = 0;
        $netPay          = 0;
        $totalDeductions = 0;

        if ($latestPeriod) {
            $latestSlips     = $latestPeriod->payslips;
            $grossPayroll    = $latestSlips->sum('gross_pay');
            $netPay          = $latestSlips->sum('net_pay');
            $totalDeductions = $latestSlips->sum('total_deductions');
        }

        $daysToCutoff  = 0;
        $activePeriod  = PayrollPeriod::whereIn('status', ['Pending', 'Submitted'])
            ->where('end_date', '>=', now()->toDateString())
            ->orderBy('end_date')
            ->first();
        if ($activePeriod) {
            $daysToCutoff = max(0, (int) ceil(now()->floatDiffInDays($activePeriod->end_date, false)));
        }

        $payrollItems = PayrollItem::orderBy('name')->get();
        $salaryGrades = SalaryGrade::withCount('employees')->with('employees')->orderBy('grade_code')->get();
        $benefits     = Benefit::with('employees:id')->orderBy('name')->get();
        $employees    = Employee::whereNull('deleted_at')->whereHas('user', fn($q) => $q->whereNotNull('email_verified_at'))->orderBy('fname')->get();
        $contrib      = DB::table('contribution_settings')->pluck('value', 'key');
        $sssRows      = DB::table('sss_contributions')->orderBy('salary_from')->get();
        $sssRowsForJs = $sssRows->map(fn($r) => [
            'salary_from'    => $r->salary_from,
            'salary_to'      => $r->salary_to,
            'employee_share' => $r->employee_share,
            'employer_share' => $r->employer_share,
        ])->values()->all();

        return view('finance_officer.finance-officer_payroll', compact(
            'periods', 'grossPayroll', 'netPay', 'totalDeductions',
            'latestPeriod', 'activePeriod', 'year', 'daysToCutoff',
            'payrollItems', 'salaryGrades', 'benefits', 'employees', 'contrib', 'sssRows', 'sssRowsForJs'
        ));
    }

    public function updatePeriod(Request $request, $id)
    {
        $period = PayrollPeriod::findOrFail($id);

        $rules = ['name' => 'required|string|max:255'];
        if ($period->status === 'Pending') {
            $rules['start_date']  = 'required|date';
            $rules['end_date']    = 'required|date|after_or_equal:start_date';
            $rules['payout_date'] = 'required|date';
        }
        $request->validate($rules);

        $period->name = $request->name;
        if ($period->status === 'Pending') {
            $period->start_date  = $request->start_date;
            $period->end_date    = $request->end_date;
            $period->payout_date = $request->payout_date;
        }
        $period->save();

        return response()->json(['success' => true, 'message' => 'Payroll period updated successfully.']);
    }

    // ── Release Payroll ──────────────────────────────────────────────────────

    public function releasePayroll($id)
    {
        $period = PayrollPeriod::findOrFail($id);

        if ($period->status !== 'Submitted') {
            return redirect()->back()->with('error', 'Only Submitted payroll periods can be released.');
        }

        $period->update(['status' => 'Released']);
        Payslip::where('payroll_period_id', $id)->update(['status' => 'Released']);

        // Notify payroll officers and admins
        $recipients = DB::table('users')
            ->whereIn('role', ['payroll_officer', 'admin'])
            ->pluck('id');

        $now = now();
        $notifications = $recipients->map(fn($uid) => [
            'user_id'    => $uid,
            'title'      => 'Payroll Released',
            'message'    => "Payroll period \"{$period->name}\" has been approved and released to employees.",
            'icon'       => 'process_done',
            'link'       => null,
            'is_read'    => false,
            'created_at' => $now,
            'updated_at' => $now,
        ])->all();

        if ($notifications) {
            DB::table('notifications')->insert($notifications);
        }

        return redirect()->back()->with('success', 'Payroll released successfully.');
    }

    // ── Period Payslips (AJAX — all statuses, for period view) ───────────────

    public function periodPayslips($id)
    {
        $period   = PayrollPeriod::findOrFail($id);
        $payslips = Payslip::with(['employee' => fn($q) => $q->withTrashed()->with(['department', 'jobTitle'])])
            ->where('payroll_period_id', $id)
            ->get();

        $data = $payslips->map(fn($p) => [
            'id'             => $p->id,
            'employeeId'     => $p->employee_id,
            'employeeName'   => $p->employee?->full_name ?? '—',
            'jobTitle'       => $p->employee?->jobTitle?->title ?? '—',
            'department'     => $p->employee?->department?->name ?? '—',
            'basicPay'       => (float) $p->basic_pay,
            'otPay'          => (float) $p->ot_pay,
            'benefits'        => (float) $p->benefits_total,
            'otherAdditions'  => (float) ($p->other_additions ?? 0),
            'grossPay'        => (float) $p->gross_pay,
            'sss'            => (float) $p->sss,
            'philhealth'     => (float) $p->philhealth,
            'pagibig'        => (float) $p->pagibig,
            'lateDeduction'   => (float) ($p->late_deduction ?? 0),
            'otherDeductions' => (float) ($p->other_deductions ?? 0),
            'withholdingTax'  => (float) $p->withholding_tax,
            'totalDeductions'=> (float) $p->total_deductions,
            'netPay'         => (float) $p->net_pay,
            'status'         => $p->status,
            'period'         => $period->name,
        ])->values();

        return response()->json(['payslips' => $data]);
    }

    // ── Payslips Page ────────────────────────────────────────────────────────

    public function payslips()
    {
        $user         = auth()->user();
        $authEmployee = $user->employee ?? null;

        $periods      = PayrollPeriod::where('status', 'Released')->orderByDesc('start_date')->get();
        $latestPeriod = $periods->first();

        $allPayslips        = collect();
        $allGrossPayroll    = 0;
        $allNetPay          = 0;
        $allTotalDeductions = 0;

        if ($latestPeriod) {
            $query = Payslip::with(['employee' => fn($q) => $q->withTrashed()->with(['department', 'jobTitle'])])
                ->where('payroll_period_id', $latestPeriod->id)
                ->where('status', 'Released');
            if ($authEmployee) {
                $query->where('employee_id', '!=', $authEmployee->id);
            }
            $slips = $query->get();
            $allGrossPayroll    = $slips->sum('gross_pay');
            $allNetPay          = $slips->sum('net_pay');
            $allTotalDeductions = $slips->sum('total_deductions');
            $allPayslips = $slips->map(fn($p) => [
                'id'             => $p->id,
                'employeeId'     => $p->employee_id,
                'employeeName'   => $p->employee?->full_name ?? '—',
                'jobTitle'       => $p->employee?->jobTitle?->title ?? '—',
                'department'     => $p->employee?->department?->name ?? '—',
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
                'period'         => $latestPeriod->name,
            ])->values();
        }

        $myPayslips        = collect();
        $myGrossPayroll    = 0;
        $myNetPay          = 0;
        $myTotalDeductions = 0;

        if ($authEmployee) {
            $authEmployee->load('jobTitle', 'department', 'salaryGrade');
            $mySlips = Payslip::with('period')
                ->where('employee_id', $authEmployee->id)
                ->where('status', 'Released')
                ->orderByDesc('created_at')
                ->get();
            $myGrossPayroll    = $mySlips->sum('gross_pay');
            $myNetPay          = $mySlips->sum('net_pay');
            $myTotalDeductions = $mySlips->sum('total_deductions');
            $myPayslips = $mySlips->map(fn($p) => [
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

        $periodsForJs = $periods->map(fn($p) => [
            'id'   => $p->id,
            'name' => $p->name,
            'year' => $p->start_date->year,
        ])->values();

        return view('finance_officer.finance-officer_payslips', compact(
            'latestPeriod', 'periodsForJs',
            'allPayslips', 'allGrossPayroll', 'allNetPay', 'allTotalDeductions',
            'myPayslips', 'myGrossPayroll', 'myNetPay', 'myTotalDeductions',
            'authEmployee'
        ));
    }

    // ── Period Payslips — payslips page (Released only) ──────────────────────

    public function releasedPeriodPayslips($id)
    {
        $period   = PayrollPeriod::findOrFail($id);
        $payslips = Payslip::with(['employee' => fn($q) => $q->withTrashed()->with(['department', 'jobTitle'])])
            ->where('payroll_period_id', $id)
            ->where('status', 'Released')
            ->get();

        $data = $payslips->map(fn($p) => [
            'id'             => $p->id,
            'employeeId'     => $p->employee_id,
            'employeeName'   => $p->employee?->full_name ?? '—',
            'jobTitle'       => $p->employee?->jobTitle?->title ?? '—',
            'department'     => $p->employee?->department?->name ?? '—',
            'basicPay'       => (float) $p->basic_pay,
            'otPay'          => (float) $p->ot_pay,
            'benefits'        => (float) $p->benefits_total,
            'otherAdditions'  => (float) ($p->other_additions ?? 0),
            'grossPay'        => (float) $p->gross_pay,
            'sss'            => (float) $p->sss,
            'philhealth'     => (float) $p->philhealth,
            'pagibig'        => (float) $p->pagibig,
            'lateDeduction'   => (float) ($p->late_deduction ?? 0),
            'otherDeductions' => (float) ($p->other_deductions ?? 0),
            'withholdingTax'  => (float) $p->withholding_tax,
            'totalDeductions'=> (float) $p->total_deductions,
            'netPay'         => (float) $p->net_pay,
            'status'         => $p->status,
            'period'         => $period->name,
        ])->values();

        return response()->json(['payslips' => $data]);
    }

    // ── Salary Grades ────────────────────────────────────────────────────────

    public function storeGrade(Request $request)
    {
        $request->validate([
            'grade_code'           => 'required|string|unique:salary_grades,grade_code',
            'level_name'           => 'required|string',
            'monthly_basic_salary' => 'required|numeric|min:0',
        ]);

        $grade = SalaryGrade::create($request->only('grade_code', 'level_name', 'monthly_basic_salary'));

        if ($request->filled('employee_ids')) {
            Employee::whereIn('id', $request->employee_ids)
                ->update(['salary_grade_id' => $grade->id]);
        }

        return redirect()->route('finance_officer.payroll', ['tab' => 'salary-structure'])
            ->with('success', 'Salary grade added.');
    }

    public function updateGrade(Request $request, $id)
    {
        $request->validate([
            'grade_code'           => "required|string|unique:salary_grades,grade_code,{$id}",
            'level_name'           => 'required|string',
            'monthly_basic_salary' => 'required|numeric|min:0',
        ]);

        $grade = SalaryGrade::findOrFail($id);
        $grade->update($request->only('grade_code', 'level_name', 'monthly_basic_salary'));

        Employee::where('salary_grade_id', $id)->update(['salary_grade_id' => null]);
        if ($request->filled('employee_ids')) {
            Employee::whereIn('id', $request->employee_ids)
                ->update(['salary_grade_id' => $id]);
        }

        return redirect()->route('finance_officer.payroll', ['tab' => 'salary-structure'])
            ->with('success', 'Salary grade updated.');
    }

    public function deleteGrade($id)
    {
        $grade = SalaryGrade::findOrFail($id);
        Employee::where('salary_grade_id', $id)->update(['salary_grade_id' => null]);
        $grade->delete();

        return response()->json(['success' => true]);
    }

    // ── Payroll Items ────────────────────────────────────────────────────────

    public function storePayrollItem(Request $request)
    {
        $request->validate([
            'name'       => 'required|string|max:255',
            'multiplier' => 'required|numeric|min:0',
            'type'       => 'required|in:Addition,Deduction',
            'basis'      => 'required|string|max:255',
        ]);

        PayrollItem::create([
            'name'       => $request->name,
            'multiplier' => $request->multiplier,
            'type'       => $request->type,
            'basis'      => $request->basis,
            'status'     => 'Active',
        ]);

        return redirect()->route('finance_officer.payroll', ['tab' => 'salary-structure'])
            ->with('success', 'Payroll item added.');
    }

    public function updatePayrollItem(Request $request, $id)
    {
        $request->validate([
            'name'       => 'required|string|max:255',
            'multiplier' => 'required|numeric|min:0',
            'type'       => 'required|in:Addition,Deduction',
            'basis'      => 'required|string|max:255',
        ]);

        PayrollItem::findOrFail($id)->update($request->only('name', 'multiplier', 'type', 'basis'));

        return redirect()->route('finance_officer.payroll', ['tab' => 'salary-structure'])
            ->with('success', 'Payroll item updated.');
    }

    public function deactivatePayrollItem($id)
    {
        $item = PayrollItem::findOrFail($id);
        $item->update(['status' => $item->status === 'Active' ? 'Inactive' : 'Active']);

        return response()->json(['success' => true]);
    }

    public function deletePayrollItem($id)
    {
        PayrollItem::findOrFail($id)->delete();

        return response()->json(['success' => true]);
    }

    public function getPayrollItem($id)
    {
        return response()->json(PayrollItem::findOrFail($id));
    }

    // ── Benefits ─────────────────────────────────────────────────────────────

    public function storeBenefit(Request $request)
    {
        $request->validate([
            'name'        => 'required|string|max:255',
            'type'        => 'required|in:Allowance,Bonus,Incentive',
            'amount'      => 'required|numeric|min:0',
            'tax'         => 'required|in:Taxable,Non-taxable',
            'frequency'   => 'required|string|max:255',
            'eligibility' => 'required|string|max:255',
        ]);

        Benefit::create([
            'name'        => $request->name,
            'type'        => $request->type,
            'amount'      => $request->amount,
            'tax'         => $request->tax,
            'frequency'   => $request->frequency,
            'eligibility' => $request->eligibility,
            'status'      => 'Active',
        ]);

        return redirect()->route('finance_officer.payroll', ['tab' => 'benefits'])
            ->with('success', 'Benefit added.');
    }

    public function updateBenefit(Request $request, $id)
    {
        $request->validate([
            'name'        => 'required|string|max:255',
            'type'        => 'required|in:Allowance,Bonus,Incentive',
            'amount'      => 'required|numeric|min:0',
            'tax'         => 'required|in:Taxable,Non-taxable',
            'frequency'   => 'required|string|max:255',
            'eligibility' => 'required|string|max:255',
        ]);

        Benefit::findOrFail($id)->update(
            $request->only('name', 'type', 'amount', 'tax', 'frequency', 'eligibility')
        );

        return redirect()->route('finance_officer.payroll', ['tab' => 'benefits'])
            ->with('success', 'Benefit updated.');
    }

    public function deactivateBenefit($id)
    {
        $benefit = Benefit::findOrFail($id);
        $benefit->update(['status' => $benefit->status === 'Active' ? 'Inactive' : 'Active']);

        return response()->json(['success' => true]);
    }

    public function getBenefit($id)
    {
        return response()->json(Benefit::findOrFail($id));
    }

    public function deleteBenefit($id)
    {
        Benefit::findOrFail($id)->delete();

        return response()->json(['success' => true]);
    }

    public function syncBenefitEmployees(Request $request, $id)
    {
        $benefit = Benefit::findOrFail($id);
        $benefit->employees()->sync($request->input('employee_ids', []));

        return redirect()->route('finance_officer.payroll', ['tab' => 'benefits'])
            ->with('success', 'Benefit employees updated.');
    }

    // ── Contribution Settings ─────────────────────────────────────────────────

    public function updateContrib(Request $request)
    {
        $data = $request->validate([
            'keys'   => 'required|array',
            'values' => 'required|array',
        ]);

        foreach ($data['keys'] as $i => $key) {
            DB::table('contribution_settings')
                ->where('key', $key)
                ->update(['value' => $data['values'][$i]]);
        }

        return response()->json(['success' => true]);
    }

    public function saveSssTable(Request $request)
    {
        $data = $request->validate([
            'rows'                   => 'required|array',
            'rows.*.salary_from'     => 'required|numeric|min:0',
            'rows.*.salary_to'       => 'nullable|numeric|min:0',
            'rows.*.employee_share'  => 'required|numeric|min:0',
            'rows.*.employer_share'  => 'required|numeric|min:0',
        ]);

        DB::table('sss_contributions')->truncate();
        foreach ($data['rows'] as $row) {
            DB::table('sss_contributions')->insert([
                'salary_from'    => $row['salary_from'],
                'salary_to'      => $row['salary_to'] !== '' ? $row['salary_to'] : null,
                'employee_share' => $row['employee_share'],
                'employer_share' => $row['employer_share'],
                'created_at'     => now(),
                'updated_at'     => now(),
            ]);
        }

        return response()->json(['success' => true]);
    }

    // ── Govt. Contributions ──────────────────────────────────────────────────

    public function govpay(Request $request)
    {
        $year = $request->get('year', now()->year);

        $contributions = DB::table('payslips as ps')
            ->join('payroll_periods as pp', 'ps.payroll_period_id', '=', 'pp.id')
            ->where('pp.status', 'Released')
            ->whereYear('pp.start_date', $year)
            ->select(
                'pp.id as payroll_period_id',
                'pp.name as period_name',
                'pp.status',
                DB::raw('SUM(ps.sss)            as sss_total'),
                DB::raw('SUM(ps.philhealth)      as philhealth_total'),
                DB::raw('SUM(ps.pagibig)         as pagibig_total'),
                DB::raw('SUM(ps.withholding_tax) as tax_total')
            )
            ->groupBy('pp.id', 'pp.name', 'pp.status')
            ->orderByDesc('pp.start_date')
            ->get();

        $authEmployee    = auth()->user()->employee ?? null;
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

        $years        = range(now()->year, now()->year - 3);
        $totalPending = DB::table('notifications')
            ->where('user_id', auth()->id())
            ->where('is_read', false)
            ->count();

        return view('finance_officer.finance-officer_govpay', compact(
            'contributions', 'myContributions', 'years', 'year', 'totalPending'
        ));
    }

    public function govpayView(Request $request, $id)
    {
        $year   = $request->get('year', now()->year);
        $period = PayrollPeriod::findOrFail($id);

        $records = DB::table('payslips as ps')
            ->join('employees as e', 'ps.employee_id', '=', 'e.id')
            ->join('users as u', 'e.user_id', '=', 'u.id')
            ->whereNotNull('u.email_verified_at')
            ->where('ps.payroll_period_id', $id)
            ->select(
                'e.fname',
                'e.lname',
                'ps.sss',
                'ps.philhealth',
                'ps.pagibig',
                'ps.withholding_tax as tax',
                'ps.status'
            )
            ->orderBy('e.lname')
            ->get();

        $years        = range(now()->year, now()->year - 3);
        $periodName   = $period->name;
        $periodId     = $period->id;
        $totalPending = DB::table('notifications')
            ->where('user_id', auth()->id())
            ->where('is_read', false)
            ->count();

        return view('finance_officer.finance-officer_govpay', compact(
            'records', 'years', 'year', 'periodName', 'periodId', 'totalPending'
        ));
    }
}

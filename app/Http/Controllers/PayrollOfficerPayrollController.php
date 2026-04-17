<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\SalaryGrade;
use App\Models\PayrollItem;
use App\Models\Benefit;
use App\Models\PayrollPeriod;
use App\Models\Payslip;
use App\Models\Employee;
use App\Models\OvertimeRequest;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class PayrollOfficerPayrollController extends Controller
{
    // ── Index ────────────────────────────────────────────────────────────────

    public function index(Request $request)
    {
        $year   = $request->input('year', now()->year);

        $periods      = PayrollPeriod::orderByDesc('start_date')->get();
        $payrollItems = PayrollItem::orderBy('name')->get();
        $salaryGrades = SalaryGrade::withCount('employees')->with('employees')->orderBy('grade_code')->get();
        $benefits     = Benefit::with('employees:id')->orderBy('name')->get();

        // Summary from the latest period's payslips
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

        // Days to cutoff — nearest active period (Pending or Submitted) with a future end date
        $daysToCutoff = 0;
        $activePeriod = $periods->whereIn('status', ['Pending', 'Submitted'])
            ->where('end_date', '>=', now()->toDateString())
            ->sortBy('end_date')
            ->first();
        if ($activePeriod) {
            $daysToCutoff = max(0, (int) ceil(now()->floatDiffInDays($activePeriod->end_date, false)));
        }

        $employees = Employee::whereNull('deleted_at')->whereHas('user', fn($q) => $q->whereNotNull('email_verified_at'))->orderBy('fname')->get();

        $contrib      = DB::table('contribution_settings')->pluck('value', 'key');
        $sssRows      = DB::table('sss_contributions')->orderBy('salary_from')->get();
        $sssRowsForJs = $sssRows->map(fn($r) => [
            'salary_from'    => $r->salary_from,
            'salary_to'      => $r->salary_to,
            'employee_share' => $r->employee_share,
            'employer_share' => $r->employer_share,
        ])->values()->all();

        return view('payroll_officer.payroll-officer_payroll', compact(
            'periods', 'grossPayroll', 'netPay', 'totalDeductions',
            'daysToCutoff', 'activePeriod', 'latestPeriod', 'year',
            'payrollItems', 'benefits', 'salaryGrades', 'employees', 'contrib', 'sssRows', 'sssRowsForJs'
        ));
    }

    // ── Payroll Period ───────────────────────────────────────────────────────

    public function storePeriod(Request $request)
    {
        $request->validate([
            'name'        => 'required|string|max:255',
            'start_date'  => 'required|date',
            'end_date'    => 'required|date|after_or_equal:start_date',
            'payout_date' => 'required|date',
        ]);

        $period = PayrollPeriod::create([
            'name'        => $request->name,
            'start_date'  => $request->start_date,
            'end_date'    => $request->end_date,
            'payout_date' => $request->payout_date,
            'status'      => 'Pending',
        ]);

        $this->generatePayslips($period);

        return redirect()->route('payroll_officer.payroll')
            ->with('success', 'Payroll period created and payslips generated.');
    }

    public function submitForApproval($id)
    {
        $period = PayrollPeriod::findOrFail($id);

        // Guard: period must still be Pending
        if ($period->status !== 'Pending') {
            return redirect()->back()->with('error', 'This payroll period has already been submitted or released.');
        }

        // Guard: ALL payslips must be Submitted before the period can be submitted
        $unsubmitted = $period->payslips()->where('status', '!=', 'Submitted')->count();
        if ($unsubmitted > 0) {
            return redirect()->back()->with('error', "Cannot submit: {$unsubmitted} payslip(s) are not yet submitted.");
        }

        $period->update(['status' => 'Submitted']);

        // Notify all admins and finance officers
        $recipients = DB::table('users')
            ->whereIn('role', ['admin', 'finance_officer'])
            ->pluck('id');

        $now = now();
        $notifications = $recipients->map(fn($uid) => [
            'user_id'    => $uid,
            'title'      => 'Payroll Submitted for Approval',
            'message'    => "Payroll period \"{$period->name}\" has been submitted and is awaiting your approval.",
            'icon'       => 'notice',
            'link'       => null,
            'is_read'    => false,
            'created_at' => $now,
            'updated_at' => $now,
        ])->all();

        if ($notifications) {
            DB::table('notifications')->insert($notifications);
        }

        return redirect()->back()->with('success', 'Payroll period submitted for approval.');
    }

    public function unsubmitPeriod($id)
    {
        $period = PayrollPeriod::findOrFail($id);

        // Guard: can only unsubmit if currently Submitted (not Released)
        if ($period->status !== 'Submitted') {
            return redirect()->back()->with('error', 'This payroll period cannot be unsubmitted.');
        }

        $period->update(['status' => 'Pending']);

        return redirect()->back()->with('success', 'Payroll period has been unsubmitted and returned to Pending.');
    }

    // ── Payslips Page ────────────────────────────────────────────────────────

    public function payslips()
    {
        $user         = auth()->user();
        $authEmployee = $user->employee ?? null;

        // Only periods whose payslips have been Released appear here
        $periods      = PayrollPeriod::where('status', 'Released')->orderByDesc('start_date')->get();
        $latestPeriod = $periods->first();

        // ── All Payslips (latest released period, excluding own) ──
        $allPayslips      = collect();
        $allGrossPayroll  = 0;
        $allNetPay        = 0;
        $allTotalDeductions = 0;

        if ($latestPeriod) {
            $query = Payslip::with('employee.department', 'employee.jobTitle')
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
                'employeeName'   => $p->employee->full_name,
                'jobTitle'       => $p->employee->jobTitle->title ?? '—',
                'department'     => $p->employee->department->name ?? '—',
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

        // ── My Payslips (own, all periods) ──
        $myPayslips       = collect();
        $myGrossPayroll   = 0;
        $myNetPay         = 0;
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

        return view('payroll_officer.payroll-officer_payslips', compact(
            'latestPeriod', 'periodsForJs',
            'allPayslips', 'allGrossPayroll', 'allNetPay', 'allTotalDeductions',
            'myPayslips', 'myGrossPayroll', 'myNetPay', 'myTotalDeductions',
            'authEmployee'
        ));
    }

    // ── Period Payslips — management view (ALL statuses, used in period view page) ──

    public function allPeriodPayslips($id)
    {
        $period   = PayrollPeriod::findOrFail($id);
        $payslips = Payslip::with('employee.department', 'employee.jobTitle')
            ->where('payroll_period_id', $id)
            ->get();

        $data = $payslips->map(fn($p) => [
            'id'             => $p->id,
            'employeeId'     => $p->employee_id,
            'employeeName'   => $p->employee->full_name,
            'jobTitle'       => $p->employee->jobTitle->title ?? '—',
            'department'     => $p->employee->department->name ?? '—',
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
            'period'         => $period->name,
        ])->values();

        return response()->json(['payslips' => $data]);
    }

    // ── Period Payslips — payslips page (Released only) ──────────────────────

    public function periodPayslips($id)
    {
        $period   = PayrollPeriod::findOrFail($id);
        $payslips = Payslip::with('employee.department', 'employee.jobTitle')
            ->where('payroll_period_id', $id)
            ->where('status', 'Released')
            ->get();

        $data = $payslips->map(fn($p) => [
            'id'             => $p->id,
            'employeeId'     => $p->employee_id,
            'employeeName'   => $p->employee->full_name,
            'jobTitle'       => $p->employee->jobTitle->title ?? '—',
            'department'     => $p->employee->department->name ?? '—',
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
            'period'         => $period->name,
        ])->values();

        return response()->json(['payslips' => $data]);
    }

    // ── Payslip ──────────────────────────────────────────────────────────────

    public function savePayslip(Request $request, $id)
    {
        $request->validate([
            'basicPay'       => 'required|numeric|min:0',
            'otPay'          => 'required|numeric|min:0',
            'benefits'       => 'required|numeric|min:0',
            'sss'            => 'required|numeric|min:0',
            'philhealth'     => 'required|numeric|min:0',
            'pagibig'        => 'required|numeric|min:0',
            'withholdingTax' => 'required|numeric|min:0',
        ]);

        $payslip = Payslip::with('period')->findOrFail($id);

        // Guard: cannot edit payslips once the period is Submitted or Released
        if (in_array($payslip->period->status, ['Submitted', 'Released'])) {
            return response()->json(['success' => false, 'message' => 'Payslip cannot be edited after the period has been submitted.']);
        }

        $grossPay        = $request->basicPay + $request->otPay + $request->benefits;
        $totalDeductions = $request->sss + $request->philhealth + $request->pagibig + $request->withholdingTax;

        $payslip->update([
            'basic_pay'        => $request->basicPay,
            'ot_pay'           => $request->otPay,
            'benefits_total'   => $request->benefits,
            'gross_pay'        => $grossPay,
            'sss'              => $request->sss,
            'philhealth'       => $request->philhealth,
            'pagibig'          => $request->pagibig,
            'withholding_tax'  => $request->withholdingTax,
            'total_deductions' => $totalDeductions,
            'net_pay'          => $grossPay - $totalDeductions,
        ]);

        return response()->json(['success' => true]);
    }

    public function submitPayslip($id)
    {
        $payslip = Payslip::with('period')->findOrFail($id);

        // Guard: can only submit payslips in a Pending period
        if ($payslip->period->status !== 'Pending') {
            return response()->json(['success' => false, 'message' => 'Payslip cannot be submitted after the period is no longer pending.']);
        }

        $payslip->update(['status' => 'Submitted']);

        return response()->json(['success' => true]);
    }

    public function unsubmitPayslip($id)
    {
        $payslip = Payslip::with('period')->findOrFail($id);

        // Guard: can only unsubmit if period is still Pending (not submitted/released)
        if ($payslip->period->status !== 'Pending') {
            return response()->json(['success' => false, 'message' => 'Payslip cannot be unsubmitted once the period has been submitted for approval.']);
        }

        if ($payslip->status !== 'Submitted') {
            return response()->json(['success' => false, 'message' => 'Payslip is not in a submitted state.']);
        }

        $payslip->update(['status' => 'Pending']);

        return response()->json(['success' => true]);
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

        return redirect()->route('payroll_officer.payroll', ['tab' => 'salary-structure'])
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

        // Clear all employees from this grade then re-assign submitted ones
        Employee::where('salary_grade_id', $id)->update(['salary_grade_id' => null]);
        if ($request->filled('employee_ids')) {
            Employee::whereIn('id', $request->employee_ids)
                ->update(['salary_grade_id' => $id]);
        }

        return redirect()->route('payroll_officer.payroll', ['tab' => 'salary-structure'])
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

        return redirect()->route('payroll_officer.payroll', ['tab' => 'salary-structure'])
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

        return redirect()->route('payroll_officer.payroll', ['tab' => 'salary-structure'])
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

        return redirect()->route('payroll_officer.payroll', ['tab' => 'benefits'])
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

        return redirect()->route('payroll_officer.payroll', ['tab' => 'benefits'])
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

        return redirect()->route('payroll_officer.payroll', ['tab' => 'benefits'])
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

    // ── Payslip Generation ───────────────────────────────────────────────────

    private function generatePayslips(PayrollPeriod $period): void
    {
        $employees = Employee::with(['salaryGrade', 'benefits' => fn($q) => $q->where('status', 'Active')])->whereNull('deleted_at')->get();

        $c       = DB::table('contribution_settings')->pluck('value', 'key');
        $sssRows = DB::table('sss_contributions')->orderBy('salary_from')->get();

        foreach ($employees as $emp) {
            $grade         = $emp->salaryGrade;
            $monthlySalary = $grade ? (float) $grade->monthly_basic_salary : 0;
            $basicPay      = $monthlySalary / 2;

            // OT pay: approved OT hours × hourly rate × 1.25 (Regular OT)
            $hourlyRate = $monthlySalary > 0 ? ($monthlySalary * 12 / 261 / 8) : 0;
            $otHours    = OvertimeRequest::where('employee_id', $emp->id)
                ->where('status', 'approved')
                ->whereBetween('ot_date', [$period->start_date, $period->end_date])
                ->sum('approved_hours');
            $otPay = round($otHours * $hourlyRate * 1.25, 2);

            $benefitsTotal = $emp->benefits->sum('amount');
            $grossPay = $basicPay + $otPay + $benefitsTotal;

            // ── SSS: table-based lookup (fixed employee share per salary bracket) ──
            $sssRow = $sssRows->first(fn($r) =>
                $monthlySalary >= $r->salary_from &&
                ($r->salary_to === null || $monthlySalary <= $r->salary_to)
            );
            $sss = $sssRow ? round((float) $sssRow->employee_share / 2, 2) : 0;

            // ── PhilHealth: percentage-based, employee half, semi-monthly ─────────
            $phBase     = max((float) $c['philhealth_floor'], min($monthlySalary, (float) $c['philhealth_ceiling']));
            $philhealth = round($phBase * ((float) $c['philhealth_rate'] / 100 / 2) / 2, 2);

            // ── Pag-IBIG: fixed ₱100 or ₱200/month based on salary threshold ─────
            $pagibigMonthly = $monthlySalary < (float) $c['pagibig_threshold']
                ? (float) $c['pagibig_low_amount']
                : (float) $c['pagibig_high_amount'];
            $pagibig = round($pagibigMonthly / 2, 2);

            // ── Withholding tax: TRAIN Law 6-bracket, annualized projection ───────
            $annualDeductions = ($sss + $philhealth + $pagibig) * 24;
            $annualTaxable    = max(0, ($grossPay * 24) - $annualDeductions);
            $withholdingTax   = round($this->computeWithholdingTax($annualTaxable, $c) / 24, 2);

            $totalDeductions = $sss + $philhealth + $pagibig + $withholdingTax;
            $netPay          = $grossPay - $totalDeductions;

            Payslip::create([
                'payroll_period_id' => $period->id,
                'employee_id'       => $emp->id,
                'basic_pay'         => $basicPay,
                'ot_pay'            => $otPay,
                'benefits_total'    => $benefitsTotal,
                'gross_pay'         => $grossPay,
                'sss'               => $sss,
                'philhealth'        => $philhealth,
                'pagibig'           => $pagibig,
                'withholding_tax'   => $withholdingTax,
                'total_deductions'  => $totalDeductions,
                'net_pay'           => $netPay,
                'status'            => 'Pending',
            ]);
        }
    }

    private function computeWithholdingTax(float $annual, $c = null): float
    {
        // TRAIN Law brackets (6-tier, effective 2023)
        $b1 = $c ? (float) $c['wtax_bracket_1'] : 250000;
        $b2 = $c ? (float) $c['wtax_bracket_2'] : 400000;
        $b3 = $c ? (float) $c['wtax_bracket_3'] : 800000;
        $b4 = $c ? (float) $c['wtax_bracket_4'] : 2000000;
        $b5 = $c ? (float) $c['wtax_bracket_5'] : 8000000;
        $r1 = $c ? (float) $c['wtax_rate_1'] / 100 : 0.15;
        $r2 = $c ? (float) $c['wtax_rate_2'] / 100 : 0.20;
        $r3 = $c ? (float) $c['wtax_rate_3'] / 100 : 0.25;
        $r4 = $c ? (float) $c['wtax_rate_4'] / 100 : 0.30;
        $r5 = $c ? (float) $c['wtax_rate_5'] / 100 : 0.35;

        if ($annual <= $b1) return 0;
        if ($annual <= $b2) return ($annual - $b1) * $r1;
        if ($annual <= $b3) return ($b2 - $b1) * $r1 + ($annual - $b2) * $r2;
        if ($annual <= $b4) return ($b2 - $b1) * $r1 + ($b3 - $b2) * $r2 + ($annual - $b3) * $r3;
        if ($annual <= $b5) return ($b2 - $b1) * $r1 + ($b3 - $b2) * $r2 + ($b4 - $b3) * $r3 + ($annual - $b4) * $r4;

        return ($b2 - $b1) * $r1 + ($b3 - $b2) * $r2 + ($b4 - $b3) * $r3 + ($b5 - $b4) * $r4 + ($annual - $b5) * $r5;
    }

    // ── SSS Contribution Table CRUD ──────────────────────────────────────────

    public function saveSssTable(Request $request)
    {
        $request->validate([
            'rows'                   => 'required|array|min:1',
            'rows.*.salary_from'     => 'required|numeric|min:0',
            'rows.*.salary_to'       => 'nullable|numeric|gt:rows.*.salary_from',
            'rows.*.employee_share'  => 'required|numeric|min:0',
            'rows.*.employer_share'  => 'required|numeric|min:0',
        ]);

        DB::table('sss_contributions')->truncate();

        $now  = now();
        $rows = collect($request->rows)->map(fn($r) => [
            'salary_from'    => $r['salary_from'],
            'salary_to'      => $r['salary_to'] ?: null,
            'employee_share' => $r['employee_share'],
            'employer_share' => $r['employer_share'],
            'created_at'     => $now,
            'updated_at'     => $now,
        ])->all();

        DB::table('sss_contributions')->insert($rows);

        return response()->json(['success' => true]);
    }

    // ── Govt. Contributions ──────────────────────────────────────────────────

    public function govpay(Request $request)
    {
        $year = $request->get('year', now()->year);

        // All periods: sum contributions per period from payslips (Released only)
        $contributions = DB::table('payslips as ps')
            ->join('payroll_periods as pp', 'ps.payroll_period_id', '=', 'pp.id')
            ->where('pp.status', 'Released')
            ->whereYear('pp.start_date', $year)
            ->select(
                'pp.id as payroll_period_id',
                'pp.name as period_name',
                'pp.status',
                DB::raw('SUM(ps.sss)              as sss_total'),
                DB::raw('SUM(ps.philhealth)        as philhealth_total'),
                DB::raw('SUM(ps.pagibig)           as pagibig_total'),
                DB::raw('SUM(ps.withholding_tax)   as tax_total')
            )
            ->groupBy('pp.id', 'pp.name', 'pp.status')
            ->orderByDesc('pp.start_date')
            ->get();

        // My contributions: logged-in employee's payslips
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

        return view('payroll_officer.payroll-officer_govpay', compact(
            'contributions', 'myContributions', 'years', 'year', 'totalPending'
        ));
    }

    public function govpayView(Request $request, $id)
    {
        $year   = $request->get('year', now()->year);
        $period = PayrollPeriod::findOrFail($id);

        $records = DB::table('payslips as ps')
            ->join('employees as e', 'ps.employee_id', '=', 'e.id')
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

        return view('payroll_officer.payroll-officer_govpay', compact(
            'records', 'years', 'year', 'periodName', 'periodId', 'totalPending'
        ));
    }
}

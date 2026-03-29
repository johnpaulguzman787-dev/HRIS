<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class PayrollOfficerPayrollController extends Controller
{
    public function index(Request $request)
    {
        $year            = $request->get('year', now()->year);
        $grossPayroll    = 263082;
        $netPay          = 200000;
        $totalDeductions = 63082;
        $daysToCutoff    = 12;
        $latestPeriod    = null;
        $periods         = collect();
        $payrollItems    = collect();
        $benefits        = collect();

        return view('payroll_officer.payroll-officer_payroll', compact(
            'periods', 'grossPayroll', 'netPay', 'totalDeductions',
            'daysToCutoff', 'latestPeriod', 'year', 'payrollItems', 'benefits'
        ));
    }

    public function storePeriod(Request $request)
    {
        return redirect()->route('payroll_officer.payroll')
            ->with('success', 'Payroll period added successfully.');
    }

    public function viewPeriod($id)
    {
        // Sample period object using stdClass for demo (replace with DB model later)
        $period                = new \stdClass();
        $period->id            = $id;
        $period->name          = 'February Payroll Period 2';
        $period->start_date    = '2026-02-16';
        $period->end_date      = '2026-02-28';
        $period->payout_date   = '2026-03-05';
        $period->status        = 'Pending';

        // Sample payslips collection (replace with DB query later)
        // e.g. $payslips = Payslip::with('employee')->where('payroll_period_id', $id)->get();
        $payslips = collect([
            (object)[
                'id'               => 1,
                'gross_pay'        => 28500.00,
                'net_pay'          => 23600.00,
                'status'           => 'Submitted',
                'basic_pay'        => 23655.00,
                'ot_pay'           => 1425.00,
                'benefits'         => 3000.00,
                'sss'              => 1125.00,
                'philhealth'       => 500.00,
                'pagibig'          => 200.00,
                'withholding_tax'  => 2995.00,
                'total_deductions' => 4820.00,
                'employee'         => (object)[
                    'full_name'  => 'Juan Dela Cruz',
                    'job_title'  => 'Senior Programmer',
                    'department' => (object)['name' => 'IT'],
                ],
            ],
            (object)[
                'id'               => 2,
                'gross_pay'        => 28500.00,
                'net_pay'          => 23600.00,
                'status'           => 'Pending',
                'basic_pay'        => 23655.00,
                'ot_pay'           => 1425.00,
                'benefits'         => 3000.00,
                'sss'              => 1125.00,
                'philhealth'       => 500.00,
                'pagibig'          => 200.00,
                'withholding_tax'  => 2995.00,
                'total_deductions' => 4820.00,
                'employee'         => (object)[
                    'full_name'  => 'Maria Santos',
                    'job_title'  => 'Nurse',
                    'department' => (object)['name' => 'Medical'],
                ],
            ],
            (object)[
                'id'               => 3,
                'gross_pay'        => 28500.00,
                'net_pay'          => 23600.00,
                'status'           => 'Pending',
                'basic_pay'        => 23655.00,
                'ot_pay'           => 1425.00,
                'benefits'         => 3000.00,
                'sss'              => 1125.00,
                'philhealth'       => 500.00,
                'pagibig'          => 200.00,
                'withholding_tax'  => 2995.00,
                'total_deductions' => 4820.00,
                'employee'         => (object)[
                    'full_name'  => 'Pedro Reyes',
                    'job_title'  => 'Accountant',
                    'department' => (object)['name' => 'Finance'],
                ],
            ],
        ]);

        // Counts for processing status progress bars
        $totalCount     = $payslips->count();
        $submittedCount = $payslips->where('status', 'Submitted')->count();

        // Payroll summary totals
        $grossPayroll    = $payslips->sum('gross_pay');
        $totalDeductions = $payslips->sum('total_deductions');
        $netPayroll      = $payslips->sum('net_pay');

        // JSON for Alpine.js payslip panel
        $payslipsJson = $payslips->map(fn($p) => [
            'id'               => $p->id,
            'employeeName'     => $p->employee->full_name,
            'jobTitle'         => $p->employee->job_title,
            'department'       => $p->employee->department->name,
            'basicPay'         => $p->basic_pay,
            'otPay'            => $p->ot_pay,
            'benefits'         => $p->benefits,
            'grossPay'         => $p->gross_pay,
            'sss'              => $p->sss,
            'philhealth'       => $p->philhealth,
            'pagibig'          => $p->pagibig,
            'withholdingTax'   => $p->withholding_tax,
            'totalDeductions'  => $p->total_deductions,
            'netPay'           => $p->net_pay,
            'status'           => $p->status,
        ])->values();

        return view('payroll_officer.payroll_period_view', compact(
            'period',
            'payslips',
            'payslipsJson',
            'totalCount',
            'submittedCount',
            'grossPayroll',
            'totalDeductions',
            'netPayroll'
        ));
    }

    public function submitForApproval($id)
    {
        return redirect()->back()->with('success', 'Payroll submitted for approval.');
    }

    public function storePayrollItem(Request $request)
    {
        return redirect()->route('payroll_officer.payroll')
            ->with('success', 'Payroll item added.');
    }

    public function updatePayrollItem(Request $request, $id)
    {
        return redirect()->route('payroll_officer.payroll')
            ->with('success', 'Payroll item updated.');
    }

    public function deactivatePayrollItem($id)
    {
        return response()->json(['success' => true]);
    }

    public function getPayrollItem($id)
    {
        return response()->json([]);
    }

    public function storeBenefit(Request $request)
    {
        return redirect()->route('payroll_officer.payroll')
            ->with('success', 'Benefit added.');
    }

    public function updateBenefit(Request $request, $id)
    {
        return redirect()->route('payroll_officer.payroll')
            ->with('success', 'Benefit updated.');
    }

    public function deactivateBenefit($id)
    {
        return response()->json(['success' => true]);
    }

    public function getBenefit($id)
    {
        return response()->json([]);
    }

    public function submitPayslip($id)
    {
        // Update payslip status to Submitted
        // Payslip::findOrFail($id)->update(['status' => 'Submitted']);
        return response()->json(['success' => true]);
    }
}
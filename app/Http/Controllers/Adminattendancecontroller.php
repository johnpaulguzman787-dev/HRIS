<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use App\Models\AttendanceLog;
use App\Models\Employee;
use App\Models\EmployeeShift;
use App\Models\Department;

class AdminAttendanceController extends Controller
{
    /**
     * Display the admin attendance reports page.
     */
    public function index()
    {
        $user     = Auth::user();
        $employee = Employee::where('user_id', $user->id)->first();

        $now   = Carbon::now();
        $month = $now->month;
        $year  = $now->year;

        // Get available shifts
        $availableShifts = \App\Models\Shift::where('is_active', true)->get();

        // Get active shift
        $employeeShift = $employee ? EmployeeShift::with('shift')
            ->where('employee_id', $employee->id)
            ->where('is_active', true)
            ->whereDate('effective_date', '<=', Carbon::today())
            ->where(function ($q) {
                $q->whereNull('end_date')->orWhereDate('end_date', '>=', Carbon::today());
            })
            ->latest('effective_date')
            ->first() : null;

        // Stat counts for current month
        $stats = $employee ? [
            'present'   => AttendanceLog::where('employee_id', $employee->id)->whereMonth('attendance_date', $month)->whereYear('attendance_date', $year)->whereIn('status', ['present', 'undertime', 'overtime'])->count(),
            'late'      => AttendanceLog::where('employee_id', $employee->id)->whereMonth('attendance_date', $month)->whereYear('attendance_date', $year)->where('status', 'late')->count(),
            'absent'    => AttendanceLog::where('employee_id', $employee->id)->whereMonth('attendance_date', $month)->whereYear('attendance_date', $year)->where('status', 'absent')->count(),
            'on_leave'  => AttendanceLog::where('employee_id', $employee->id)->whereMonth('attendance_date', $month)->whereYear('attendance_date', $year)->whereIn('status', ['on_leave', 'holiday'])->count(),
            'overtime'  => AttendanceLog::where('employee_id', $employee->id)->whereMonth('attendance_date', $month)->whereYear('attendance_date', $year)->where('overtime_minutes', '>', 0)->count(),
            'undertime' => AttendanceLog::where('employee_id', $employee->id)->whereMonth('attendance_date', $month)->whereYear('attendance_date', $year)->where('undertime_minutes', '>', 0)->count(),
        ] : array_fill_keys(['present', 'late', 'absent', 'on_leave', 'overtime', 'undertime'], 0);

        // Today's log
        $todayLog = $employee ? AttendanceLog::where('employee_id', $employee->id)
            ->whereDate('attendance_date', Carbon::today())
            ->with('shift')
            ->first() : null;

        return view('admin.admin_attendance-reports', compact('employeeShift', 'availableShifts', 'stats', 'todayLog', 'month', 'year'));
    }

    /**
     * Clock in the currently logged-in admin.
     */
    public function clockIn(Request $request)
{
    $user     = Auth::user();
    $employee = Employee::where('user_id', $user->id)->firstOrFail();
    $today    = Carbon::today();
    $now      = Carbon::now();

    // Check for resume from break BEFORE validation
    $existing = AttendanceLog::where('employee_id', $employee->id)
        ->whereDate('attendance_date', $today)
        ->first();

    if ($existing && $existing->clock_in && $existing->break_start && !$existing->break_end && !$existing->clock_out) {
        $breakMinutes = (int) Carbon::parse($existing->break_start)->diffInMinutes($now);
        $existing->update([
            'break_end'     => $now,
            'break_minutes' => $breakMinutes,
        ]);
        return response()->json([
            'message'       => 'Break ended, resumed work.',
            'break_end'     => $now->format('h:i A'),
            'break_minutes' => $breakMinutes,
        ]);
    }

    $request->validate([
        'work_setup' => 'required|in:office,wfh',
        'shift_id'   => 'nullable|exists:shifts,id',
    ]);

        $user     = Auth::user();
        $employee = Employee::where('user_id', $user->id)->firstOrFail();
        $today    = Carbon::today();
        $now      = Carbon::now();

        // Prevent duplicate clock-in
        $existing = AttendanceLog::where('employee_id', $employee->id)
            ->whereDate('attendance_date', $today)
            ->first();

        if ($existing && $existing->clock_in) {
            return response()->json(['message' => 'Already clocked in today.'], 409);
        }

        // Get active shift for today
        $employeeShift = EmployeeShift::with('shift')
            ->where('employee_id', $employee->id)
            ->where('is_active', true)
            ->whereDate('effective_date', '<=', $today)
            ->where(function ($q) use ($today) {
                $q->whereNull('end_date')->orWhereDate('end_date', '>=', $today);
            })
            ->latest('effective_date')
            ->first();

        $shiftId     = $request->shift_id ?? $employeeShift?->shift_id;
        $lateMinutes = 0;
        $status      = 'present';

        // Compute late minutes using the SELECTED shift (from request), not just employee's default shift
        $selectedShift = $shiftId ? \App\Models\Shift::find($shiftId) : $employeeShift?->shift;

        if ($selectedShift) {
            $shiftStart = Carbon::createFromTimeString(
                Carbon::today()->toDateString() . ' ' . $selectedShift->start_time
            );

           if ($now->gt($shiftStart)) {
    $lateMinutes = (int) $shiftStart->diffInMinutes($now);
    if ($lateMinutes > 0) {
        $status = 'late';
        if ($lateMinutes > 999) $lateMinutes = 999;
    }
} else {
    // Clock-in is BEFORE shift start = early/on time, always 0
    $lateMinutes = 0;
}
        }

        $log = AttendanceLog::updateOrCreate(
            ['employee_id' => $employee->id, 'attendance_date' => $today],
            [
                'shift_id'     => $shiftId,
                'work_setup'   => $request->work_setup,
                'clock_in'     => $now,
                'late_minutes' => $lateMinutes,
                'status'       => $status,
            ]
        );

        return response()->json([
            'message'      => 'Clocked in successfully.',
            'clock_in'     => $now->format('h:i A'),
            'status'       => $status,
            'late_minutes' => $lateMinutes,
            'log_id'       => $log->id,
        ]);
    }

    /**
     * Clock out the currently logged-in admin.
     */
    public function clockOut(Request $request)
    {
        $user     = Auth::user();
        $employee = Employee::where('user_id', $user->id)->firstOrFail();
        $today    = Carbon::today();
        $now      = Carbon::now();

        $log = AttendanceLog::where('employee_id', $employee->id)
            ->whereDate('attendance_date', $today)
            ->firstOrFail();

        if (!$log->clock_in) {
            return response()->json(['message' => 'No clock-in record found for today.'], 422);
        }

        if ($log->clock_out) {
            return response()->json(['message' => 'Already clocked out today.'], 409);
        }

        $clockIn    = Carbon::parse($log->clock_in);
        $totalHours = round(($clockIn->diffInMinutes($now) - $log->break_minutes) / 60, 2);

        $overtimeMinutes  = 0;
        $undertimeMinutes = 0;
        $clockOutStatus   = $log->status; // preserve 'late' if already late

        // Use the shift that was clocked in with
        $selectedShift = $log->shift_id ? \App\Models\Shift::find($log->shift_id) : null;

        if (!$selectedShift) {
            // Fallback to employee's active shift
            $employeeShift = EmployeeShift::with('shift')
                ->where('employee_id', $employee->id)
                ->where('is_active', true)
                ->whereDate('effective_date', '<=', $today)
                ->where(function ($q) use ($today) {
                    $q->whereNull('end_date')->orWhereDate('end_date', '>=', $today);
                })
                ->latest('effective_date')
                ->first();
            $selectedShift = $employeeShift?->shift;
        }

        if ($selectedShift) {
            $shiftEnd = Carbon::createFromTimeString(
                Carbon::today()->toDateString() . ' ' . $selectedShift->end_time
            );

            // Night shift: if end_time is earlier than start_time, it crosses midnight
            if ($shiftEnd->lt(Carbon::createFromTimeString(
                Carbon::today()->toDateString() . ' ' . $selectedShift->start_time
            ))) {
                $shiftEnd->addDay();
            }

            if ($now->gt($shiftEnd)) {
                // Clocked out AFTER shift end = overtime
                $overtimeMinutes = (int) $now->diffInMinutes($shiftEnd);
                if ($clockOutStatus !== 'late') {
                    $clockOutStatus = 'overtime';
                }
            } elseif ($now->lt($shiftEnd)) {
                // Clocked out BEFORE shift end = undertime
                $undertimeMinutes = (int) $now->diffInMinutes($shiftEnd);
                // Only override status if not already 'late'
                if ($clockOutStatus !== 'late') {
                    $clockOutStatus = 'undertime';
                }
            }
        }

        $log->update([
            'clock_out'         => $now,
            'total_hours'       => $totalHours,
            'overtime_minutes'  => $overtimeMinutes,
            'undertime_minutes' => $undertimeMinutes,
            'status'            => $clockOutStatus,
        ]);

        return response()->json([
            'message'           => 'Clocked out successfully.',
            'clock_out'         => $now->format('h:i A'),
            'total_hours'       => $totalHours,
            'overtime_minutes'  => $overtimeMinutes,
            'undertime_minutes' => $undertimeMinutes,
            'status'            => $clockOutStatus,
        ]);
    }

    /**
     * Fetch attendance records for the logged-in admin.
     */
    public function records(Request $request)
    {
        $user     = Auth::user();
        $employee = Employee::where('user_id', $user->id)->firstOrFail();

        $month = $request->get('month', Carbon::now()->month);
        $year  = $request->get('year', Carbon::now()->year);

        $logs = AttendanceLog::with('shift')
            ->where('employee_id', $employee->id)
            ->whereMonth('attendance_date', $month)
            ->whereYear('attendance_date', $year)
            ->orderByDesc('attendance_date')
            ->paginate(10);

        return response()->json($logs);
    }

    /**
     * Fetch today's attendance status for the logged-in admin.
     */
    public function today()
    {
        $user     = Auth::user();
        $employee = Employee::where('user_id', $user->id)->firstOrFail();

        $log = AttendanceLog::with('shift')
            ->where('employee_id', $employee->id)
            ->whereDate('attendance_date', Carbon::today())
            ->first();

        return response()->json($log);
    }


    public function breakStart(Request $request)
{
    $user     = Auth::user();
    $employee = Employee::where('user_id', $user->id)->firstOrFail();
    $today    = Carbon::today();
    $now      = Carbon::now();

    $log = AttendanceLog::where('employee_id', $employee->id)
        ->whereDate('attendance_date', $today)
        ->firstOrFail();

    if (!$log->clock_in) {
        return response()->json(['message' => 'Not clocked in yet.'], 422);
    }

    if ($log->break_start) {
        return response()->json(['message' => 'Already on break.'], 409);
    }

    if ($log->clock_out) {
        return response()->json(['message' => 'Already clocked out.'], 409);
    }

    $log->update(['break_start' => $now]);

    return response()->json([
        'message'      => 'Break started.',
        'break_start'  => $now->format('h:i A'),
    ]);
}





    /**
     * Display all employees' attendance records for admin.
     */
    public function employeeAttendance(Request $request)
    {
        $currentView  = $request->get('view', 'daily');
        $departments  = Department::orderBy('name')->get();
        $authEmployee = Employee::where('user_id', Auth::id())->first();
        $authEmpId    = $authEmployee?->id ?? 0;

        // ── DETAIL VIEW (drilldown for one employee) ──
        if ($currentView === 'monthly' && $request->filled('employee_id')) {
            $selectedMonth  = $request->get('month', now()->format('Y-m'));
            $selectedPeriod = $request->get('period', '1');
            [$year, $month] = explode('-', $selectedMonth);

            $period1Start = Carbon::parse("$selectedMonth-01");
            $period1End   = Carbon::parse("$selectedMonth-15");
            $period2Start = Carbon::parse("$selectedMonth-16");
            $period2End   = Carbon::parse("$selectedMonth-01")->endOfMonth();

            $start = $selectedPeriod == '1' ? $period1Start : $period2Start;
            $end   = $selectedPeriod == '1' ? $period1End   : $period2End;

            $logs = AttendanceLog::with('shift')
                ->where('employee_id', $request->employee_id)
                ->whereBetween('attendance_date', [$start->toDateString(), $end->toDateString()])
                ->orderBy('attendance_date')
                ->get();

            $fmt = fn($m) => floor($m / 60) . 'h ' . str_pad((int)($m % 60), 2, '0', STR_PAD_LEFT) . 'm';

            $dailyRecords = $logs->map(function ($log) use ($fmt) {
                return (object) [
                    'date'                => $log->attendance_date,
                    'work_setup'          => $log->work_setup ? strtoupper($log->work_setup) : '—',
                    'shift_type'          => $log->shift?->name ?? '—',
                    'schedule'            => $log->shift
                        ? Carbon::parse($log->shift->start_time)->format('g:i A') . ' – ' . Carbon::parse($log->shift->end_time)->format('g:i A')
                        : '—',
                    'time_in'             => $log->clock_in,
                    'time_out'            => $log->clock_out,
                    'overtime_formatted'  => $log->overtime_minutes  > 0 ? $fmt($log->overtime_minutes)  : '00h 00m',
                    'undertime_formatted' => $log->undertime_minutes > 0 ? $fmt($log->undertime_minutes) : '00h 00m',
                    'status'              => $log->status,
                ];
            });

            $totalWorkMinutes      = $logs->sum(fn($l) => round(($l->total_hours ?? 0) * 60));
            $totalOvertimeMinutes  = $logs->sum('overtime_minutes');
            $totalUndertimeMinutes = $logs->sum('undertime_minutes');

            $workingDaysInPeriod = 0;
            $cursor = $start->copy();
            while ($cursor->lte($end)) {
                if (!$cursor->isWeekend()) $workingDaysInPeriod++;
                $cursor->addDay();
            }

            $totals = [
                'work_hours'      => $fmt($totalWorkMinutes),
                'overtime_hours'  => $fmt($totalOvertimeMinutes),
                'undertime_hours' => $fmt($totalUndertimeMinutes),
                'working_days'    => $logs->count() . '/' . $workingDaysInPeriod,
            ];

            return view('admin.admin_employee_attendance-reports', compact(
                'currentView', 'departments', 'dailyRecords',
                'selectedMonth', 'selectedPeriod', 'totals'
            ));
        }

        // ── DAILY VIEW ──
        if ($currentView === 'daily') {
            $date = $request->get('date', now()->toDateString());

            $query = AttendanceLog::with(['employee.department', 'shift'])
                ->whereDate('attendance_date', $date)
                ->whereHas('employee', fn($q) => $q->where('id', '!=', $authEmpId));

            if ($request->filled('department')) {
                $query->whereHas('employee', fn($q) => $q->where('department_id', $request->department));
            }
            if ($request->filled('search')) {
                $s = $request->search;
                $query->whereHas('employee', fn($q) =>
                    $q->where('fname', 'like', "%$s%")->orWhere('lname', 'like', "%$s%")
                );
            }

            $records      = $query->paginate(15);
            $totalEmp     = Employee::where('id', '!=', $authEmpId)->count();
            $presentCount = AttendanceLog::whereDate('attendance_date', $date)
                ->whereIn('status', ['present', 'late', 'overtime', 'undertime'])
                ->whereHas('employee', fn($q) => $q->where('id', '!=', $authEmpId))
                ->count();
            $lateCount    = AttendanceLog::whereDate('attendance_date', $date)
                ->where('late_minutes', '>', 0)
                ->whereHas('employee', fn($q) => $q->where('id', '!=', $authEmpId))
                ->count();
            $absentCount  = $totalEmp - $presentCount;

            $otMins = AttendanceLog::whereDate('attendance_date', $date)
                ->whereHas('employee', fn($q) => $q->where('id', '!=', $authEmpId))
                ->sum('overtime_minutes');
            $utMins = AttendanceLog::whereDate('attendance_date', $date)
                ->whereHas('employee', fn($q) => $q->where('id', '!=', $authEmpId))
                ->sum('undertime_minutes');

            $overtimeEmployees  = AttendanceLog::whereDate('attendance_date', $date)
                ->where('overtime_minutes', '>', 0)
                ->whereHas('employee', fn($q) => $q->where('id', '!=', $authEmpId))
                ->count();
            $undertimeEmployees = AttendanceLog::whereDate('attendance_date', $date)
                ->where('undertime_minutes', '>', 0)
                ->whereHas('employee', fn($q) => $q->where('id', '!=', $authEmpId))
                ->count();

            $presentRate    = $totalEmp > 0 ? round(($presentCount / $totalEmp) * 100, 1) . '%' : '0%';
            $lateRate       = $totalEmp > 0 ? round(($lateCount    / $totalEmp) * 100, 1) . '%' : '0%';
            $absentRate     = $totalEmp > 0 ? round(($absentCount  / $totalEmp) * 100, 1) . '%' : '0%';
            $overtimeHours  = floor($otMins / 60) . ' hrs';
            $undertimeHours = floor($utMins / 60) . ' hrs';

            return view('admin.admin_employee_attendance-reports', compact(
                'currentView', 'departments', 'records',
                'presentCount', 'presentRate',
                'lateCount', 'lateRate',
                'absentCount', 'absentRate',
                'overtimeHours', 'overtimeEmployees',
                'undertimeHours', 'undertimeEmployees'
            ));
        }

        // ── MONTHLY LIST VIEW ──
        $selectedMonth = $request->get('month', now()->format('Y-m'));
        [$year, $month] = explode('-', $selectedMonth);

        $empQuery = Employee::with('department')
            ->where('id', '!=', $authEmpId);

        if ($request->filled('department')) {
            $empQuery->where('department_id', $request->department);
        }

        $fmt      = fn($m) => floor($m / 60) . 'h ' . str_pad((int)($m % 60), 2, '0', STR_PAD_LEFT) . 'm';
        $employees = $empQuery->paginate(15);

        $monthlyRecords = $employees->through(function ($emp) use ($month, $year, $fmt) {
            $logs = AttendanceLog::where('employee_id', $emp->id)
                ->whereMonth('attendance_date', $month)
                ->whereYear('attendance_date', $year)
                ->get();

            return (object) [
                'employee_id'  => $emp->id,
                'employee'     => $emp,
                'present_days' => $logs->whereIn('status', ['present', 'overtime', 'undertime'])->count(),
                'late_days'    => $logs->where('status', 'late')->count(),
                'absent_days'  => $logs->where('status', 'absent')->count(),
                'leave_days'   => $logs->whereIn('status', ['on_leave', 'holiday'])->count(),
                'total_hours'  => $fmt($logs->sum(fn($l) => round(($l->total_hours ?? 0) * 60))),
                'ot_hours'     => $fmt($logs->sum('overtime_minutes')),
                'ut_hours'     => $fmt($logs->sum('undertime_minutes')),
            ];
        });

        // stat cards for monthly view use today's date
        $date         = now()->toDateString();
        $totalEmp     = Employee::where('id', '!=', $authEmpId)->count();
        $presentCount = AttendanceLog::whereDate('attendance_date', $date)
            ->whereIn('status', ['present', 'late', 'overtime', 'undertime'])
            ->whereHas('employee', fn($q) => $q->where('id', '!=', $authEmpId))
            ->count();
        $lateCount    = AttendanceLog::whereDate('attendance_date', $date)
            ->where('late_minutes', '>', 0)
            ->whereHas('employee', fn($q) => $q->where('id', '!=', $authEmpId))
            ->count();
        $absentCount  = $totalEmp - $presentCount;

        $otMins = AttendanceLog::whereDate('attendance_date', $date)
            ->whereHas('employee', fn($q) => $q->where('id', '!=', $authEmpId))
            ->sum('overtime_minutes');
        $utMins = AttendanceLog::whereDate('attendance_date', $date)
            ->whereHas('employee', fn($q) => $q->where('id', '!=', $authEmpId))
            ->sum('undertime_minutes');

        $overtimeEmployees  = AttendanceLog::whereDate('attendance_date', $date)
            ->where('overtime_minutes', '>', 0)
            ->whereHas('employee', fn($q) => $q->where('id', '!=', $authEmpId))
            ->count();
        $undertimeEmployees = AttendanceLog::whereDate('attendance_date', $date)
            ->where('undertime_minutes', '>', 0)
            ->whereHas('employee', fn($q) => $q->where('id', '!=', $authEmpId))
            ->count();

        $presentRate    = $totalEmp > 0 ? round(($presentCount / $totalEmp) * 100, 1) . '%' : '0%';
        $lateRate       = $totalEmp > 0 ? round(($lateCount    / $totalEmp) * 100, 1) . '%' : '0%';
        $absentRate     = $totalEmp > 0 ? round(($absentCount  / $totalEmp) * 100, 1) . '%' : '0%';
        $overtimeHours  = floor($otMins / 60) . ' hrs';
        $undertimeHours = floor($utMins / 60) . ' hrs';

        return view('admin.admin_employee_attendance-reports', compact(
            'currentView', 'departments', 'monthlyRecords', 'selectedMonth',
            'presentCount', 'presentRate',
            'lateCount', 'lateRate',
            'absentCount', 'absentRate',
            'overtimeHours', 'overtimeEmployees',
            'undertimeHours', 'undertimeEmployees'
        ));
    }




}
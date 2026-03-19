<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;
use Carbon\Carbon;
use App\Models\AttendanceLog;
use App\Models\Employee;
use App\Models\EmployeeShift;
use App\Models\Department;
use App\Models\LeaveType;
use App\Models\LeaveCredit;
use App\Models\LeaveRequest;

class HRAttendanceController extends Controller
{
    public function index()
    {
        $user     = Auth::user();
        $employee = Employee::where('user_id', $user->id)->first();

        $now   = Carbon::now();
        $month = $now->month;
        $year  = $now->year;

        $availableShifts = \App\Models\Shift::where('is_active', true)->get();

        $employeeShift = $employee ? EmployeeShift::with('shift')
            ->where('employee_id', $employee->id)
            ->where('is_active', true)
            ->whereDate('effective_date', '<=', Carbon::today())
            ->where(function ($q) {
                $q->whereNull('end_date')->orWhereDate('end_date', '>=', Carbon::today());
            })
            ->latest('effective_date')
            ->first() : null;

        $stats = $employee ? [
            'present'   => AttendanceLog::where('employee_id', $employee->id)->whereMonth('attendance_date', $month)->whereYear('attendance_date', $year)->whereIn('status', ['present', 'undertime', 'overtime'])->count(),
            'late'      => AttendanceLog::where('employee_id', $employee->id)->whereMonth('attendance_date', $month)->whereYear('attendance_date', $year)->where('status', 'late')->count(),
            'absent'    => AttendanceLog::where('employee_id', $employee->id)->whereMonth('attendance_date', $month)->whereYear('attendance_date', $year)->where('status', 'absent')->count(),
            'on_leave'  => AttendanceLog::where('employee_id', $employee->id)->whereMonth('attendance_date', $month)->whereYear('attendance_date', $year)->whereIn('status', ['on_leave', 'holiday'])->count(),
            'overtime'  => AttendanceLog::where('employee_id', $employee->id)->whereMonth('attendance_date', $month)->whereYear('attendance_date', $year)->where('overtime_minutes', '>', 0)->count(),
            'undertime' => AttendanceLog::where('employee_id', $employee->id)->whereMonth('attendance_date', $month)->whereYear('attendance_date', $year)->where('undertime_minutes', '>', 0)->count(),
        ] : array_fill_keys(['present', 'late', 'absent', 'on_leave', 'overtime', 'undertime'], 0);

        $todayLog = $employee ? AttendanceLog::where('employee_id', $employee->id)
            ->whereDate('attendance_date', Carbon::today())
            ->with('shift')
            ->first() : null;

        return view('hr.hr_attendance-reports', compact('employeeShift', 'availableShifts', 'stats', 'todayLog', 'month', 'year'));
    }

    public function clockIn(Request $request)
    {
        $user     = Auth::user();
        $employee = Employee::where('user_id', $user->id)->firstOrFail();
        $today    = Carbon::today();
        $now      = Carbon::now();

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

        if ($existing && $existing->clock_in) {
            return response()->json(['message' => 'Already clocked in today.'], 409);
        }

        $employeeShift = EmployeeShift::with('shift')
            ->where('employee_id', $employee->id)
            ->where('is_active', true)
            ->whereDate('effective_date', '<=', $today)
            ->where(function ($q) use ($today) {
                $q->whereNull('end_date')->orWhereDate('end_date', '>=', $today);
            })
            ->latest('effective_date')
            ->first();

        $shiftId       = $request->shift_id ?? $employeeShift?->shift_id;
        $lateMinutes   = 0;
        $status        = 'present';
        $selectedShift = $shiftId ? \App\Models\Shift::find($shiftId) : $employeeShift?->shift;

        // Check if today is a holiday
        $todayHoliday = \App\Models\Holiday::whereDate('date', $today)->first();
        $holidayId    = $todayHoliday?->id;

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
                $lateMinutes = 0;
            }
        }

        $log = AttendanceLog::updateOrCreate(
            ['employee_id' => $employee->id, 'attendance_date' => $today],
            [
                'shift_id'     => $shiftId,
                'holiday_id'   => $holidayId,
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
        $clockOutStatus   = $log->status;

        $selectedShift = $log->shift_id ? \App\Models\Shift::find($log->shift_id) : null;

        if (!$selectedShift) {
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
            if ($shiftEnd->lt(Carbon::createFromTimeString(
                Carbon::today()->toDateString() . ' ' . $selectedShift->start_time
            ))) {
                $shiftEnd->addDay();
            }

            if ($now->gt($shiftEnd)) {
                $overtimeMinutes = (int) $now->diffInMinutes($shiftEnd);
                if ($clockOutStatus !== 'late') $clockOutStatus = 'overtime';
            } elseif ($now->lt($shiftEnd)) {
                $undertimeMinutes = (int) $now->diffInMinutes($shiftEnd);
                if ($clockOutStatus !== 'late') $clockOutStatus = 'undertime';
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

        if (!$log->clock_in)  return response()->json(['message' => 'Not clocked in yet.'], 422);
        if ($log->break_start) return response()->json(['message' => 'Already on break.'], 409);
        if ($log->clock_out)  return response()->json(['message' => 'Already clocked out.'], 409);

        $log->update(['break_start' => $now]);

        return response()->json([
            'message'     => 'Break started.',
            'break_start' => $now->format('h:i A'),
        ]);
    }

    public function employeeAttendance(Request $request)
    {
        $currentView  = $request->get('view', 'daily');
        $departments  = Department::orderBy('name')->get();
        $authEmployee = Employee::where('user_id', Auth::id())->first();
        $authEmpId    = $authEmployee?->id ?? 0;

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

            return view('hr.hr_employee_attendance-reports', compact(
                'currentView', 'departments', 'dailyRecords',
                'selectedMonth', 'selectedPeriod', 'totals'
            ));
        }

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
            $presentCount = AttendanceLog::whereDate('attendance_date', $date)->whereIn('status', ['present', 'late', 'overtime', 'undertime'])->whereHas('employee', fn($q) => $q->where('id', '!=', $authEmpId))->count();
            $lateCount    = AttendanceLog::whereDate('attendance_date', $date)->where('late_minutes', '>', 0)->whereHas('employee', fn($q) => $q->where('id', '!=', $authEmpId))->count();
            $absentCount  = $totalEmp - $presentCount;
            $otMins       = AttendanceLog::whereDate('attendance_date', $date)->whereHas('employee', fn($q) => $q->where('id', '!=', $authEmpId))->sum('overtime_minutes');
            $utMins       = AttendanceLog::whereDate('attendance_date', $date)->whereHas('employee', fn($q) => $q->where('id', '!=', $authEmpId))->sum('undertime_minutes');
            $overtimeEmployees  = AttendanceLog::whereDate('attendance_date', $date)->where('overtime_minutes', '>', 0)->whereHas('employee', fn($q) => $q->where('id', '!=', $authEmpId))->count();
            $undertimeEmployees = AttendanceLog::whereDate('attendance_date', $date)->where('undertime_minutes', '>', 0)->whereHas('employee', fn($q) => $q->where('id', '!=', $authEmpId))->count();

            $presentRate    = $totalEmp > 0 ? round(($presentCount / $totalEmp) * 100, 1) . '%' : '0%';
            $lateRate       = $totalEmp > 0 ? round(($lateCount    / $totalEmp) * 100, 1) . '%' : '0%';
            $absentRate     = $totalEmp > 0 ? round(($absentCount  / $totalEmp) * 100, 1) . '%' : '0%';
            $overtimeHours  = floor($otMins / 60) . ' hrs';
            $undertimeHours = floor($utMins / 60) . ' hrs';

            return view('hr.hr_employee_attendance-reports', compact(
                'currentView', 'departments', 'records',
                'presentCount', 'presentRate', 'lateCount', 'lateRate',
                'absentCount', 'absentRate', 'overtimeHours', 'overtimeEmployees',
                'undertimeHours', 'undertimeEmployees'
            ));
        }

        // ── Monthly List View ──────────────────────────────────────────────
        $selectedMonth = $request->get('month', now()->format('Y-m'));
        [$year, $month] = explode('-', $selectedMonth);

        $empQuery = Employee::with('department')->where('id', '!=', $authEmpId);
        if ($request->filled('department')) {
            $empQuery->where('department_id', $request->department);
        }

        $fmt       = fn($m) => floor($m / 60) . 'h ' . str_pad((int)($m % 60), 2, '0', STR_PAD_LEFT) . 'm';
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

        $date         = now()->toDateString();
        $totalEmp     = Employee::where('id', '!=', $authEmpId)->count();
        $presentCount = AttendanceLog::whereDate('attendance_date', $date)->whereIn('status', ['present', 'late', 'overtime', 'undertime'])->whereHas('employee', fn($q) => $q->where('id', '!=', $authEmpId))->count();
        $lateCount    = AttendanceLog::whereDate('attendance_date', $date)->where('late_minutes', '>', 0)->whereHas('employee', fn($q) => $q->where('id', '!=', $authEmpId))->count();
        $absentCount  = $totalEmp - $presentCount;
        $otMins       = AttendanceLog::whereDate('attendance_date', $date)->whereHas('employee', fn($q) => $q->where('id', '!=', $authEmpId))->sum('overtime_minutes');
        $utMins       = AttendanceLog::whereDate('attendance_date', $date)->whereHas('employee', fn($q) => $q->where('id', '!=', $authEmpId))->sum('undertime_minutes');
        $overtimeEmployees  = AttendanceLog::whereDate('attendance_date', $date)->where('overtime_minutes', '>', 0)->whereHas('employee', fn($q) => $q->where('id', '!=', $authEmpId))->count();
        $undertimeEmployees = AttendanceLog::whereDate('attendance_date', $date)->where('undertime_minutes', '>', 0)->whereHas('employee', fn($q) => $q->where('id', '!=', $authEmpId))->count();

        $presentRate    = $totalEmp > 0 ? round(($presentCount / $totalEmp) * 100, 1) . '%' : '0%';
        $lateRate       = $totalEmp > 0 ? round(($lateCount    / $totalEmp) * 100, 1) . '%' : '0%';
        $absentRate     = $totalEmp > 0 ? round(($absentCount  / $totalEmp) * 100, 1) . '%' : '0%';
        $overtimeHours  = floor($otMins / 60) . ' hrs';
        $undertimeHours = floor($utMins / 60) . ' hrs';

        return view('hr.hr_employee_attendance-reports', compact(
            'currentView', 'departments', 'monthlyRecords', 'selectedMonth',
            'presentCount', 'presentRate', 'lateCount', 'lateRate',
            'absentCount', 'absentRate', 'overtimeHours', 'overtimeEmployees',
            'undertimeHours', 'undertimeEmployees'
        ));
    }

    // ══════════════════════════════════════════════════════════════════════
    // SHIFT SCHEDULING
    // ══════════════════════════════════════════════════════════════════════

    public function shiftScheduling(Request $request)
    {
        $activeTab   = $request->get('tab', 'weekly');
        $departments = Department::orderBy('name')->get();

        // ── Weekly Schedule ────────────────────────────────────────────────
        $weekStart = $request->get('week_start')
            ? Carbon::parse($request->get('week_start'))->startOfWeek(Carbon::MONDAY)
            : Carbon::now()->startOfWeek(Carbon::MONDAY);
        $weekEnd = $weekStart->copy()->endOfWeek(Carbon::SUNDAY);

        $employees = Employee::with('department')
            ->where('employment_status', 'Active')
            ->get();

        // Fetch all active employee_shifts covering this week
        $allShifts = EmployeeShift::with('shift')
            ->whereIn('employee_id', $employees->pluck('id'))
            ->where('is_active', true)
            ->whereDate('effective_date', '<=', $weekEnd)
            ->where(function ($q) use ($weekStart) {
                $q->whereNull('end_date')->orWhereDate('end_date', '>=', $weekStart);
            })
            ->get()
            ->keyBy('employee_id');

        // Fetch on_leave attendance logs for this week
        $leaveLogs = AttendanceLog::whereIn('employee_id', $employees->pluck('id'))
            ->whereBetween('attendance_date', [$weekStart->toDateString(), $weekEnd->toDateString()])
            ->where('status', 'on_leave')
            ->get()
            ->groupBy('employee_id');

        // Build schedule records
        $scheduleRecords = collect();
        foreach ($employees as $emp) {
            $empShift = $allShifts->get($emp->id);
            $daysOff  = $empShift && $empShift->days_off
                ? json_decode($empShift->days_off, true)
                : ['Sat', 'Sun'];

            $days = [];
            for ($i = 0; $i < 7; $i++) {
                $day     = $weekStart->copy()->addDays($i);
                $dayAbbr = $day->format('D');
                $dayKey  = $day->toDateString();

                $isOnLeave = isset($leaveLogs[$emp->id]) &&
                    $leaveLogs[$emp->id]->contains('attendance_date', $dayKey);

                if (in_array($dayAbbr, $daysOff)) {
                    $days[$dayKey] = ['type' => 'day_off'];
                } elseif ($isOnLeave) {
                    $days[$dayKey] = ['type' => 'leave'];
                } elseif ($empShift) {
                    $days[$dayKey] = [
                        'type'       => 'shift',
                        'shift_name' => $empShift->shift->name ?? '—',
                        'work_setup' => $empShift->work_setup ?? 'wfh',
                    ];
                } else {
                    $days[$dayKey] = ['type' => 'none'];
                }
            }

            $scheduleRecords->push((object) [
                'employee'       => $emp,
                'employee_shift' => $empShift,
                'days'           => $days,
            ]);
        }

        // ── Shift Types ────────────────────────────────────────────────────
        $shiftTypeQuery = \App\Models\Shift::where('is_active', true)
            ->withCount('employeeShifts as assigned');

        if ($request->filled('department') && $activeTab === 'shift-types') {
            $shiftTypeQuery->whereHas('employeeShifts', function ($q) use ($request) {
                $q->whereHas('employee', function ($eq) use ($request) {
                    $eq->where('department_id', $request->department);
                });
            });
        }

        $shiftTypes = $shiftTypeQuery->orderBy('name')->get()->map(function ($shift) {
            $startMins = Carbon::parse($shift->start_time)->diffInMinutes(Carbon::parse('00:00:00'));
            $endMins   = Carbon::parse($shift->end_time)->diffInMinutes(Carbon::parse('00:00:00'));
            $totalMins = $endMins >= $startMins
                ? $endMins - $startMins
                : (1440 - $startMins) + $endMins;
            $shift->work_hours = round($totalMins / 60, 1);
            return $shift;
        });


        // ── Holiday Calendar ───────────────────────────────────────────────
        $currentYear     = (int) $request->get('year', now()->year);
        $holidays        = collect();
        $regularHolidays = 0;
        $specialHolidays = 0;
        $localHolidays   = 0;
        $localRegion     = '—';

        $holidays        = \App\Models\Holiday::whereYear('date', $currentYear)->orderBy('date')->get();
        $regularHolidays = $holidays->where('type', 'regular')->count();
        $specialHolidays = $holidays->where('type', 'special')->count();
        $localHolidays   = $holidays->where('type', 'local')->count();
        $localRegion     = $holidays->where('type', 'local')->first()?->region ?? '—';

        return view('hr.hr_shift_scheduling', compact(
            'activeTab', 'departments', 'weekStart', 'weekEnd',
            'scheduleRecords', 'employees', 'shiftTypes',
            'currentYear', 'holidays',
            'regularHolidays', 'specialHolidays', 'localHolidays', 'localRegion'
        ));
    }

    // ══════════════════════════════════════════════════════════════════════
    // SHIFT ASSIGNMENT
    // ══════════════════════════════════════════════════════════════════════

    public function employeesByDept(Request $request)
    {
        if ($request->filled('employee_id')) {
            $empShift = EmployeeShift::with('shift')
                ->where('employee_id', $request->employee_id)
                ->where('is_active', true)
                ->latest('effective_date')
                ->first();

            return response()->json([
                'shift' => $empShift ? [
                    'id'             => $empShift->id,
                    'shift_id'       => $empShift->shift_id,
                    'shift_name'     => $empShift->shift?->name,
                    'schedule'       => $empShift->shift
                        ? Carbon::parse($empShift->shift->start_time)->format('g:i A') . ' – ' . Carbon::parse($empShift->shift->end_time)->format('g:i A')
                        : null,
                    'work_setup'     => $empShift->work_setup,
                    'effective_date' => $empShift->effective_date,
                    'end_date'       => $empShift->end_date,
                    'days_off'       => $empShift->days_off,
                ] : null
            ]);
        }

        $employees = Employee::where('department_id', $request->department_id)
            ->where('employment_status', 'Active')
            ->get(['id', 'fname', 'lname']);

        return response()->json($employees);
    }

    public function assignShift(Request $request)
    {
        $request->validate([
            'employee_id'    => 'required|exists:employees,id',
            'shift_id'       => 'required|exists:shifts,id',
            'work_setup'     => 'required|in:office,wfh',
            'effective_date' => 'required|date',
            'end_date'       => 'nullable|date|after_or_equal:effective_date',
            'days_off'       => 'nullable|array',
        ]);

        EmployeeShift::where('employee_id', $request->employee_id)
            ->where('is_active', true)
            ->update([
                'is_active' => false,
                'end_date'  => Carbon::parse($request->effective_date)->subDay()->toDateString(),
            ]);

        EmployeeShift::create([
            'employee_id'    => $request->employee_id,
            'shift_id'       => $request->shift_id,
            'work_setup'     => $request->work_setup,
            'effective_date' => $request->effective_date,
            'end_date'       => $request->end_date ?? null,
            'days_off'       => json_encode($request->days_off ?? ['Sat', 'Sun']),
            'is_active'      => true,
        ]);

        return response()->json(['message' => 'Shift assigned successfully.']);
    }

    public function updateShift(Request $request)
    {
        $request->validate([
            'employee_shift_id' => 'required|exists:employee_shifts,id',
            'shift_id'          => 'required|exists:shifts,id',
            'work_setup'        => 'required|in:office,wfh',
            'effective_date'    => 'required|date',
            'end_date'          => 'nullable|date|after_or_equal:effective_date',
            'days_off'          => 'nullable|array',
        ]);

        $empShift = EmployeeShift::findOrFail($request->employee_shift_id);
        $empShift->update([
            'shift_id'       => $request->shift_id,
            'work_setup'     => $request->work_setup,
            'effective_date' => $request->effective_date,
            'end_date'       => $request->end_date ?? null,
            'days_off'       => json_encode($request->days_off ?? ['Sat', 'Sun']),
        ]);

        return response()->json(['message' => 'Shift updated successfully.']);
    }

    public function storeShiftType(Request $request)
    {
        $request->validate([
            'name'        => 'required|string|max:100',
            'code'        => 'required|string|max:20|unique:shifts,code',
            'start_time'  => 'required|date_format:H:i',
            'end_time'    => 'required|date_format:H:i',
            'break_start' => 'nullable|date_format:H:i',
            'break_end'   => 'nullable|date_format:H:i',
        ]);

        $breakSchedule = null;
        if ($request->filled('break_start') && $request->filled('break_end')) {
            $breakSchedule = json_encode([
                'start' => $request->break_start,
                'end'   => $request->break_end,
            ]);
        }

        \App\Models\Shift::create([
            'name'           => $request->name,
            'code'           => $request->code,
            'start_time'     => $request->start_time,
            'end_time'       => $request->end_time,
            'break_schedule' => $breakSchedule,
            'is_active'      => true,
        ]);

        return response()->json(['message' => 'Shift type created successfully.']);
    }

    public function getShiftType($id)
    {
        $shift = \App\Models\Shift::findOrFail($id);
        return response()->json([
            'id'             => $shift->id,
            'name'           => $shift->name,
            'code'           => $shift->code,
            'start_time'     => $shift->start_time,
            'end_time'       => $shift->end_time,
            'break_schedule' => $shift->break_schedule,
            'is_active'      => $shift->is_active,
        ]);
    }

    public function updateShiftType(Request $request, $id)
    {
        $request->validate([
            'name'        => 'required|string|max:100',
            'code'        => 'required|string|max:20|unique:shifts,code,' . $id,
            'start_time'  => 'required|date_format:H:i',
            'end_time'    => 'required|date_format:H:i',
            'break_start' => 'nullable|date_format:H:i',
            'break_end'   => 'nullable|date_format:H:i',
        ]);

        $shift = \App\Models\Shift::findOrFail($id);

        $breakSchedule = null;
        if ($request->filled('break_start') && $request->filled('break_end')) {
            $breakSchedule = json_encode([
                'start' => $request->break_start,
                'end'   => $request->break_end,
            ]);
        }

        $shift->update([
            'name'           => $request->name,
            'code'           => $request->code,
            'start_time'     => $request->start_time,
            'end_time'       => $request->end_time,
            'break_schedule' => $breakSchedule,
        ]);

        return response()->json(['message' => 'Shift type updated successfully.']);
    }

    
// ══════════════════════════════════════════════════════════════════════
    // HOLIDAYS
    // ══════════════════════════════════════════════════════════════════════

    public function getHoliday($id)
    {
        $holiday = \App\Models\Holiday::findOrFail($id);
        return response()->json($holiday);
    }

    public function storeHoliday(Request $request)
    {
        $request->validate([
            'name'     => 'required|string|max:255',
            'date'     => 'required|date',
            'type'     => 'required|in:regular,special,local',
            'pay_rate' => 'required|string|max:10',
            'region'   => 'nullable|string|max:100',
            'yearly'   => 'boolean',
        ]);

        \App\Models\Holiday::create([
            'name'     => $request->name,
            'date'     => $request->date,
            'type'     => $request->type,
            'pay_rate' => $request->pay_rate,
            'region'   => $request->region,
            'yearly'   => $request->boolean('yearly'),
        ]);

        return response()->json(['message' => 'Holiday added successfully.']);
    }

    public function updateHoliday(Request $request, $id)
    {
        $request->validate([
            'name'     => 'required|string|max:255',
            'date'     => 'required|date',
            'type'     => 'required|in:regular,special,local',
            'pay_rate' => 'required|string|max:10',
            'region'   => 'nullable|string|max:100',
            'yearly'   => 'boolean',
        ]);

        $holiday = \App\Models\Holiday::findOrFail($id);
        $holiday->update([
            'name'     => $request->name,
            'date'     => $request->date,
            'type'     => $request->type,
            'pay_rate' => $request->pay_rate,
            'region'   => $request->region,
            'yearly'   => $request->boolean('yearly'),
        ]);

        return response()->json(['message' => 'Holiday updated successfully.']);
    }

    public function destroyHoliday($id)
    {
        $holiday = \App\Models\Holiday::findOrFail($id);
        $holiday->delete();
        return response()->json(['message' => 'Holiday deleted successfully.']);
    }







    // ══════════════════════════════════════════════════════════════════════
    // LEAVE MANAGEMENT
    // ══════════════════════════════════════════════════════════════════════

    public function leaveManagement(Request $request)
    {
        $activeTab   = $request->get('tab', 'my-leave');
        $departments = Department::orderBy('name')->get();
        $employees   = Employee::with('department')->orderBy('fname')->get();
        $currentYear = (int) $request->get('year', now()->year);
        $leaveTypes  = LeaveType::where('is_active', true)->orderBy('name')->get();

        $user     = Auth::user();
        $employee = Employee::where('user_id', $user->id)->first();

        // ── MY LEAVE tab ──────────────────────────────────────────────
        $myLeaveStats    = [];
        $myLeaveRequests = collect();

        if ($activeTab === 'my-leave' && $employee) {
            $myLeaveRequests = LeaveRequest::with(['leaveType', 'approver'])
                ->where('employee_id', $employee->id)
                ->orderByDesc('created_at')
                ->get();

            $credits = LeaveCredit::with('leaveType')
                ->where('employee_id', $employee->id)
                ->where('year', $currentYear)
                ->get();

            $myLeaveStats['pending'] = $myLeaveRequests->where('status', 'pending')->count();

            foreach ($credits as $credit) {
                $key = strtolower($credit->leaveType->code ?? '');
                $myLeaveStats[$key . '_used']      = $credit->used_days;
                $myLeaveStats[$key . '_remaining']  = $credit->remaining_days;
                $myLeaveStats[$key . '_total']      = $credit->total_days;
            }
        }

        // ── LEAVE CREDITS tab ─────────────────────────────────────────
        $creditStats  = [];
        $leaveHistory = collect();

        if ($activeTab === 'leave-credits') {
            $selectedEmpId = $request->get('employee_id', $employee?->id);
            $selectedEmp   = Employee::find($selectedEmpId);

            if ($selectedEmp) {
                $leaveHistory = LeaveRequest::with(['leaveType', 'approver'])
                    ->where('employee_id', $selectedEmp->id)
                    ->orderByDesc('created_at')
                    ->get();

                $credits = LeaveCredit::with('leaveType')
                    ->where('employee_id', $selectedEmp->id)
                    ->where('year', $currentYear)
                    ->get();

                foreach ($credits as $credit) {
                    $key = strtolower($credit->leaveType->code ?? '');
                    $creditStats[$key . '_used']      = $credit->used_days;
                    $creditStats[$key . '_remaining']  = $credit->remaining_days;
                    $creditStats[$key . '_total']      = $credit->total_days;
                }
            }
        }

        // ── LEAVE CALENDAR tab ────────────────────────────────────────
        $calendarEvents = collect();

        if ($activeTab === 'leave-calendar') {
            $calMonth  = $request->get('month')
                ? Carbon::parse($request->get('month') . '-01')
                : Carbon::now()->startOfMonth();

            $calStart = $calMonth->copy()->startOfMonth();
            $calEnd   = $calMonth->copy()->endOfMonth();

            $query = LeaveRequest::with(['employee', 'leaveType'])
                ->where('status', 'approved')
                ->whereBetween('start_date', [$calStart->toDateString(), $calEnd->toDateString()]);

            if ($request->filled('department')) {
                $query->whereHas('employee', fn($q) => $q->where('department_id', $request->department));
            }
            if ($request->filled('leave_type_id')) {
                $query->where('leave_type_id', $request->leave_type_id);
            }

            $approvedLeaves = $query->get();

            foreach ($approvedLeaves as $leave) {
                $cursor = Carbon::parse($leave->start_date);
                $end    = Carbon::parse($leave->end_date);
                while ($cursor->lte($end)) {
                    if ($cursor->between($calStart, $calEnd)) {
                        $calendarEvents->push([
                            'day'   => (int) $cursor->format('j'),
                            'type'  => 'cal-' . strtolower($leave->leaveType->code ?? 'vl'),
                            'label' => ($leave->leaveType->code ?? 'LV') . ' - ' . ($leave->employee->fname ?? ''),
                        ]);
                    }
                    $cursor->addDay();
                }
            }
        }

        return view('hr.hr_leave-management', compact(
            'activeTab', 'departments', 'employees',
            'myLeaveStats', 'myLeaveRequests',
            'creditStats', 'leaveHistory',
            'calendarEvents', 'leaveTypes', 'currentYear'
        ));
    }

    public function fileLeave(Request $request)
    {
        $request->validate([
            'leave_type_id' => 'required|exists:leave_types,id',
            'start_date'    => 'required|date|after_or_equal:today',
            'end_date'      => 'required|date|after_or_equal:start_date',
            'reason'        => 'required|string|max:500',
            'document'      => 'nullable|file|mimes:pdf,docx|max:10240',
        ]);

        $user     = Auth::user();
        $employee = Employee::where('user_id', $user->id)->firstOrFail();

        $start     = Carbon::parse($request->start_date);
        $end       = Carbon::parse($request->end_date);
        $totalDays = 0;
        $cursor    = $start->copy();
        while ($cursor->lte($end)) {
            if (!$cursor->isWeekend()) $totalDays++;
            $cursor->addDay();
        }

        $lastRef = LeaveRequest::where('ref_no', 'like', 'REQ-%')
            ->orderByDesc('id')->value('ref_no');
        $nextNum = $lastRef ? (int) substr($lastRef, 4) + 1 : 1;
        $refNo   = 'REQ-' . str_pad($nextNum, 4, '0', STR_PAD_LEFT);

        $docPath = null;
        if ($request->hasFile('document')) {
            $docPath = $request->file('document')->store('leave_documents', 'public');
        }

        LeaveRequest::create([
            'ref_no'        => $refNo,
            'employee_id'   => $employee->id,
            'leave_type_id' => $request->leave_type_id,
            'start_date'    => $request->start_date,
            'end_date'      => $request->end_date,
            'total_days'    => $totalDays,
            'reason'        => $request->reason,
            'document_path' => $docPath,
            'status'        => 'pending',
        ]);

        return response()->json(['message' => 'Leave request filed successfully.', 'ref_no' => $refNo]);
    }

    public function approveLeave(Request $request, $id)
    {
        $leave    = LeaveRequest::with('leaveType')->findOrFail($id);
        $approver = Employee::where('user_id', Auth::id())->firstOrFail();

        if ($leave->status !== 'pending') {
            return response()->json(['message' => 'Leave is no longer pending.'], 409);
        }

        $leave->update([
            'status'      => 'approved',
            'approved_by' => $approver->id,
            'approved_at' => now(),
            'hr_notes'    => $request->hr_notes ?? null,
        ]);

        // Write on_leave into attendance_logs for each working day
        $cursor = Carbon::parse($leave->start_date);
        $end    = Carbon::parse($leave->end_date);
        while ($cursor->lte($end)) {
            if (!$cursor->isWeekend()) {
                AttendanceLog::updateOrCreate(
                    [
                        'employee_id'     => $leave->employee_id,
                        'attendance_date' => $cursor->toDateString(),
                    ],
                    [
                        'status'    => 'on_leave',
                        'work_setup' => null,
                    ]
                );
            }
            $cursor->addDay();
        }

        // Deduct from leave credits
        $credit = LeaveCredit::where('employee_id', $leave->employee_id)
            ->where('leave_type_id', $leave->leave_type_id)
            ->where('year', Carbon::parse($leave->start_date)->year)
            ->first();

        if ($credit) {
            $credit->used_days      += $leave->total_days;
            $credit->remaining_days  = max(0, $credit->total_days - $credit->used_days);
            $credit->save();
        }

        return response()->json(['message' => 'Leave approved successfully.']);
    }

    public function rejectLeave(Request $request, $id)
    {
        $request->validate([
            'rejection_reason' => 'required|string|max:500',
        ]);

        $leave = LeaveRequest::findOrFail($id);

        if ($leave->status !== 'pending') {
            return response()->json(['message' => 'Leave is no longer pending.'], 409);
        }

        $leave->update([
            'status'           => 'rejected',
            'rejection_reason' => $request->rejection_reason,
        ]);

        return response()->json(['message' => 'Leave rejected.']);
    }

    public function cancelLeave(Request $request, $id)
    {
        $user     = Auth::user();
        $employee = Employee::where('user_id', $user->id)->firstOrFail();
        $leave    = LeaveRequest::where('id', $id)
            ->where('employee_id', $employee->id)
            ->firstOrFail();

        if (!in_array($leave->status, ['pending', 'approved'])) {
            return response()->json(['message' => 'This leave cannot be cancelled.'], 409);
        }

        // If approved, reverse attendance logs and credits
        if ($leave->status === 'approved') {
            AttendanceLog::where('employee_id', $leave->employee_id)
                ->whereBetween('attendance_date', [$leave->start_date, $leave->end_date])
                ->where('status', 'on_leave')
                ->delete();

            $credit = LeaveCredit::where('employee_id', $leave->employee_id)
                ->where('leave_type_id', $leave->leave_type_id)
                ->where('year', Carbon::parse($leave->start_date)->year)
                ->first();

            if ($credit) {
                $credit->used_days      = max(0, $credit->used_days - $leave->total_days);
                $credit->remaining_days = $credit->total_days - $credit->used_days;
                $credit->save();
            }
        }

        $leave->update(['status' => 'cancelled']);

        return response()->json(['message' => 'Leave cancelled successfully.']);
    }

    public function getLeaveRequest($id)
    {
        $leave = LeaveRequest::with(['leaveType', 'approver'])->findOrFail($id);
        return response()->json([
            'id'               => $leave->id,
            'ref_no'           => $leave->ref_no,
            'leave_type'       => $leave->leaveType->name ?? '—',
            'leave_type_code'  => $leave->leaveType->code ?? '—',
            'start_date'       => $leave->start_date->format('m/d/Y'),
            'end_date'         => $leave->end_date->format('m/d/Y'),
            'total_days'       => $leave->total_days,
            'reason'           => $leave->reason,
            'status'           => $leave->status,
            'approver_name'    => $leave->approver
                ? trim($leave->approver->fname . ' ' . $leave->approver->lname)
                : '—',
            'rejection_reason' => $leave->rejection_reason,
            'hr_notes'         => $leave->hr_notes,
            'document_path'    => $leave->document_path,
            'filed_on'         => $leave->created_at->format('m/d/Y'),
        ]);
    }

    public function getLeaveCredits(Request $request)
    {
        $employeeId = $request->get('employee_id');
        $year       = $request->get('year', now()->year);

        $credits = LeaveCredit::with('leaveType')
            ->where('employee_id', $employeeId)
            ->where('year', $year)
            ->get()
            ->mapWithKeys(fn($c) => [
                strtolower($c->leaveType->code ?? 'unknown') => [
                    'total'     => $c->total_days,
                    'used'      => $c->used_days,
                    'remaining' => $c->remaining_days,
                ]
            ]);

        return response()->json($credits);
    }

    public function storeLeaveType(Request $request)
    {
        $request->validate([
            'name'               => 'required|string|max:100',
            'code'               => 'required|string|max:20|unique:leave_types,code',
            'days_entitled'      => 'nullable|integer|min:1|max:365',
            'is_paid'            => 'boolean',
            'requires_document'  => 'boolean',
            'applicable_to'      => 'nullable|string|max:255',
            'carry_over'         => 'boolean',
        ]);

        $leaveType = LeaveType::create([
            'name'              => $request->name,
            'code'              => strtoupper($request->code),
            'days_entitled'     => $request->days_entitled,
            'is_paid'           => $request->boolean('is_paid'),
            'requires_document' => $request->boolean('requires_document'),
            'applicable_to'     => $request->applicable_to,
            'carry_over'        => $request->boolean('carry_over'),
            'is_active'         => true,
        ]);

        // Seed credits for all active employees
        if ($request->days_entitled) {
            $activeEmployees = Employee::where('employment_status', 'Active')->get();
            foreach ($activeEmployees as $emp) {
                LeaveCredit::firstOrCreate(
                    [
                        'employee_id'   => $emp->id,
                        'leave_type_id' => $leaveType->id,
                        'year'          => now()->year,
                    ],
                    [
                        'total_days'     => $request->days_entitled,
                        'used_days'      => 0,
                        'remaining_days' => $request->days_entitled,
                    ]
                );
            }
        }

        return response()->json(['message' => 'Leave type created successfully.']);
    }

    public function getLeaveType($id)
    {
        $lt = LeaveType::findOrFail($id);
        return response()->json($lt);
    }

    public function updateLeaveType(Request $request, $id)
    {
        $request->validate([
            'name'              => 'required|string|max:100',
            'code'              => 'required|string|max:20|unique:leave_types,code,' . $id,
            'days_entitled'     => 'nullable|integer|min:1|max:365',
            'is_paid'           => 'boolean',
            'requires_document' => 'boolean',
            'applicable_to'     => 'nullable|string|max:255',
            'carry_over'        => 'boolean',
        ]);

        $lt = LeaveType::findOrFail($id);
        $lt->update([
            'name'              => $request->name,
            'code'              => strtoupper($request->code),
            'days_entitled'     => $request->days_entitled,
            'is_paid'           => $request->boolean('is_paid'),
            'requires_document' => $request->boolean('requires_document'),
            'applicable_to'     => $request->applicable_to,
            'carry_over'        => $request->boolean('carry_over'),
        ]);

        return response()->json(['message' => 'Leave type updated successfully.']);
    }
}
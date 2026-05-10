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
use App\Models\OvertimeRequest;
use App\Models\ShiftChangeRequest;
use App\Models\AttendanceAdjustmentRequest;
use App\Traits\NotifiesReviewers;

class HRAttendanceController extends Controller
{
    use NotifiesReviewers;
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

        $pendingRequests = \App\Models\LeaveRequest::whereIn('status', ['pending', 'supervisor_approved'])->count()
            + \App\Models\OvertimeRequest::whereIn('status', ['pending', 'supervisor_approved'])->count()
            + \App\Models\ShiftChangeRequest::whereIn('status', ['pending', 'supervisor_approved'])->count();

        return view('hr.hr_attendance-reports', compact('employeeShift', 'availableShifts', 'stats', 'todayLog', 'month', 'year', 'pendingRequests'));
    }

    public function clockIn(Request $request)
    {
        $user     = Auth::user();
        $employee = Employee::where('user_id', $user->id)->first();
        if (!$employee) {
            return response()->json(['message' => 'Employee record not found.'], 422);
        }
        $today    = Carbon::today();
        $now      = Carbon::now();

        $existing = AttendanceLog::where('employee_id', $employee->id)
            ->whereDate('attendance_date', $today)
            ->first();

        if ($existing && $existing->status === 'on_leave') {
            return response()->json(['message' => 'You are on approved leave today.'], 409);
        }

        $openSession = $existing ? $existing->sessions()->whereNull('clock_out')->first() : null;

        if ($openSession && $openSession->break_start && !$openSession->break_end) {
            $newBreakSeconds   = (int) Carbon::parse($openSession->break_start)->diffInSeconds($now);
            $newBreakMinutes   = (int) floor($newBreakSeconds / 60);
            $totalBreakMinutes = $openSession->break_minutes + $newBreakMinutes;
            $totalBreakSeconds = $openSession->break_minutes * 60 + $newBreakSeconds;
            $openSession->update(['break_end' => $now, 'break_minutes' => $totalBreakMinutes]);
            $existing->update(['break_minutes' => $existing->sessions()->sum('break_minutes')]);
            return response()->json([
                'message'        => 'Break ended, resumed work.',
                'break_end'      => $now->format('h:i A'),
                'break_minutes'  => $totalBreakMinutes,
                'break_seconds'  => $totalBreakSeconds,
            ]);
        }

        if ($openSession) {
            return response()->json(['message' => 'Already clocked in.'], 409);
        }

        $request->validate([
            'work_setup' => 'required|in:office,wfh',
            'shift_id'   => 'nullable|exists:shifts,id',
        ]);

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
        $selectedShift = $shiftId ? \App\Models\Shift::find($shiftId) : $employeeShift?->shift;

        $isFirstSession = !$existing;
        $lateMinutes    = 0;
        $status         = 'present';

        if ($isFirstSession && $selectedShift && !$selectedShift->is_flexi) {
            $shiftStart = Carbon::createFromTimeString(Carbon::today()->toDateString() . ' ' . $selectedShift->start_time);
            if ($now->gt($shiftStart)) {
                $lateMinutes = min(999, (int) $shiftStart->diffInMinutes($now));
                $status      = $lateMinutes > 0 ? 'late' : 'present';
            }
        }

        if ($isFirstSession) {
            $todayHoliday = \App\Models\Holiday::whereDate('date', $today)->first();
            $log = AttendanceLog::updateOrCreate(
                ['employee_id' => $employee->id, 'attendance_date' => $today],
                [
                    'shift_id'     => $shiftId,
                    'holiday_id'   => $todayHoliday?->id,
                    'work_setup'   => $request->work_setup,
                    'clock_in'     => $now,
                    'late_minutes' => $lateMinutes,
                    'status'       => $status,
                ]
            );
        } else {
            $log    = $existing;
            $status = $existing->status;
        }

        $log->sessions()->create(['clock_in' => $now]);

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
        $employee = Employee::where('user_id', $user->id)->first();
        if (!$employee) {
            return response()->json(['message' => 'Employee record not found.'], 422);
        }
        $today    = Carbon::today();
        $now      = Carbon::now();

        $log = AttendanceLog::where('employee_id', $employee->id)
            ->whereDate('attendance_date', $today)
            ->firstOrFail();

        if (!$log->clock_in) {
            return response()->json(['message' => 'No clock-in record found for today.'], 422);
        }

        $openSession = $log->sessions()->whereNull('clock_out')->first();
        if (!$openSession) {
            return response()->json(['message' => 'No active clock-in session found.'], 409);
        }

        if ($openSession->break_start && !$openSession->break_end) {
            $extraBreak = (int) Carbon::parse($openSession->break_start)->diffInMinutes($now);
            $openSession->update(['break_end' => $now, 'break_minutes' => $openSession->break_minutes + $extraBreak]);
            $openSession->refresh();
        }

        $openSession->update(['clock_out' => $now]);

        $allSessions       = $log->sessions()->get();
        $totalBreakMinutes = $allSessions->sum('break_minutes');
        $totalWorkMinutes  = $allSessions
            ->filter(fn($s) => $s->clock_out !== null)
            ->sum(fn($s) => max(0, (int) Carbon::parse($s->clock_in)->diffInMinutes(Carbon::parse($s->clock_out)) - $s->break_minutes));
        $totalHours = round($totalWorkMinutes / 60, 2);

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
            if ($selectedShift->is_flexi) {
                $requiredMinutes = (int) (($selectedShift->required_hours ?? 8) * 60);
                $workedMinutes   = (int) ($totalHours * 60);
                if ($workedMinutes < $requiredMinutes) {
                    $undertimeMinutes = $requiredMinutes - $workedMinutes;
                    if ($clockOutStatus !== 'late') $clockOutStatus = 'undertime';
                }
            } else {
                $shiftEnd = Carbon::createFromTimeString(
                    Carbon::today()->toDateString() . ' ' . $selectedShift->end_time
                );
                if ($shiftEnd->lt(Carbon::createFromTimeString(
                    Carbon::today()->toDateString() . ' ' . $selectedShift->start_time
                ))) {
                    $shiftEnd->addDay();
                }

                if ($now->gt($shiftEnd)) {
                    $approvedOt = OvertimeRequest::where('employee_id', $employee->id)
                        ->where('status', 'approved')
                        ->where(function ($q) use ($today) {
                            $yesterday = Carbon::yesterday()->toDateString();
                            $q->whereDate('ot_date', $today)
                              ->orWhere(function ($q2) use ($yesterday) {
                                  $q2->whereDate('ot_date', $yesterday)
                                     ->whereColumn('ot_end_time', '<', 'ot_start_time');
                              });
                        })
                        ->first();

                    if ($approvedOt) {
                        $otDateBase  = Carbon::parse($approvedOt->ot_date)->toDateString();
                        $approvedEnd = Carbon::createFromTimeString($otDateBase . ' ' . $approvedOt->ot_end_time);
                        if ($approvedEnd->lt($shiftEnd)) {
                            $approvedEnd->addDay();
                        }
                        $overtimeMinutes = (int) $shiftEnd->diffInMinutes($approvedEnd);
                        if ($clockOutStatus !== 'late') $clockOutStatus = 'overtime';
                    }
                } elseif ($now->lt($shiftEnd)) {
                    $undertimeMinutes = (int) $now->diffInMinutes($shiftEnd);
                    if ($clockOutStatus !== 'late') $clockOutStatus = 'undertime';
                }
            }
        }

        $log->update([
            'clock_out'         => $now,
            'break_minutes'     => $totalBreakMinutes,
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
        $employee = Employee::where('user_id', $user->id)->first();
        if (!$employee) {
            return response()->json(['message' => 'Employee record not found.'], 422);
        }

        $month = $request->get('month', Carbon::now()->month);
        $year  = $request->get('year', Carbon::now()->year);

        $logs = AttendanceLog::with('shift')
            ->where('employee_id', $employee->id)
            ->whereMonth('attendance_date', $month)
            ->whereYear('attendance_date', $year)
            ->orderByDesc('attendance_date')
            ->paginate(min((int)$request->get('per_page', 10), 500));

        return response()->json($logs);
    }

    public function today()
    {
        $user     = Auth::user();
        $employee = Employee::where('user_id', $user->id)->first();
        if (!$employee) {
            return response()->json(['message' => 'Employee record not found.'], 422);
        }

        $log = AttendanceLog::with('shift')
            ->where('employee_id', $employee->id)
            ->whereDate('attendance_date', Carbon::today())
            ->first();

        return response()->json($log);
    }

    public function breakStart(Request $request)
    {
        $user     = Auth::user();
        $employee = Employee::where('user_id', $user->id)->first();
        if (!$employee) {
            return response()->json(['message' => 'Employee record not found.'], 422);
        }
        $today    = Carbon::today();
        $now      = Carbon::now();

        $log = AttendanceLog::where('employee_id', $employee->id)
            ->whereDate('attendance_date', $today)
            ->firstOrFail();

        $openSession = $log->sessions()->whereNull('clock_out')->first();
        if (!$openSession) {
            return response()->json(['message' => 'Not clocked in.'], 422);
        }
        if ($openSession->break_start && !$openSession->break_end) {
            return response()->json(['message' => 'Already on break.'], 409);
        }

        $openSession->update(['break_start' => $now, 'break_end' => null]);

        $reminder = null;
        $shift = $log->shift_id ? \App\Models\Shift::find($log->shift_id) : null;
        if ($shift && $shift->break_schedule) {
            $bs = json_decode($shift->break_schedule, true);
            if (!empty($bs['start']) && !empty($bs['end'])) {
                $bStart = Carbon::createFromTimeString(Carbon::today()->toDateString() . ' ' . $bs['start']);
                $bEnd   = Carbon::createFromTimeString(Carbon::today()->toDateString() . ' ' . $bs['end']);
                if ($now->lt($bStart) || $now->gt($bEnd)) {
                    $reminder = 'Your scheduled break is ' . $bStart->format('g:i A') . ' – ' . $bEnd->format('g:i A') . '.';
                }
            }
        }

        return response()->json([
            'message'     => 'Break started.',
            'break_start' => $now->format('h:i A'),
            'reminder'    => $reminder,
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
                    'id'                  => $log->id,
                    'date'                => $log->attendance_date,
                    'work_setup'          => $log->work_setup ? strtoupper($log->work_setup) : '—',
                    'shift_type'          => $log->shift?->name ?? '—',
                    'schedule'            => $log->shift
                        ? ($log->shift->is_flexi
                            ? ($log->shift->required_hours . 'h required')
                            : Carbon::parse($log->shift->start_time)->format('g:i A') . ' – ' . Carbon::parse($log->shift->end_time)->format('g:i A'))
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
            $totalEmp     = Employee::where('id', '!=', $authEmpId)->whereHas('user', fn($q) => $q->whereNotNull('email_verified_at'))->count();
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

        $empQuery = Employee::with('department')->where('id', '!=', $authEmpId)->whereHas('user', fn($q) => $q->whereNotNull('email_verified_at'));
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
        $totalEmp     = Employee::where('id', '!=', $authEmpId)->whereHas('user', fn($q) => $q->whereNotNull('email_verified_at'))->count();
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

        $weekStart = $request->get('week_start')
            ? Carbon::parse($request->get('week_start'))->startOfWeek(Carbon::MONDAY)
            : Carbon::now()->startOfWeek(Carbon::MONDAY);
        $weekEnd = $weekStart->copy()->endOfWeek(Carbon::SUNDAY);

        $employees = Employee::with('department')
            ->where('employment_status', 'Active')
            ->whereHas('user', fn($q) => $q->whereNotNull('email_verified_at'))
            ->get();

        $allShifts = EmployeeShift::with('shift')
            ->whereIn('employee_id', $employees->pluck('id'))
            ->where('is_active', true)
            ->whereDate('effective_date', '<=', $weekEnd)
            ->where(function ($q) use ($weekStart) {
                $q->whereNull('end_date')->orWhereDate('end_date', '>=', $weekStart);
            })
            ->get()
            ->keyBy('employee_id');

        $leaveLogs = AttendanceLog::whereIn('employee_id', $employees->pluck('id'))
            ->whereBetween('attendance_date', [$weekStart->toDateString(), $weekEnd->toDateString()])
            ->where('status', 'on_leave')
            ->get()
            ->groupBy('employee_id');

        $scheduleRecords = collect();
        foreach ($employees as $emp) {
            $empShift = $allShifts->get($emp->id);
            $daysOff  = $empShift && $empShift->days_off
                ? $empShift->days_off
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

        $currentYear     = (int) $request->get('year', now()->year);
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
            ->whereHas('user', fn($q) => $q->whereNotNull('email_verified_at'))
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
            'days_off'       => $request->days_off ?? ['Sat', 'Sun'],
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

        $old = EmployeeShift::findOrFail($request->employee_shift_id);

        // Deactivate old record and cap its end_date
        $old->update([
            'is_active' => false,
            'end_date'  => Carbon::parse($request->effective_date)->subDay()->toDateString(),
        ]);

        // Create new active record so attendance computation picks it up correctly
        EmployeeShift::create([
            'employee_id'    => $old->employee_id,
            'shift_id'       => $request->shift_id,
            'work_setup'     => $request->work_setup,
            'effective_date' => $request->effective_date,
            'end_date'       => $request->end_date ?? null,
            'days_off'       => $request->days_off ?? ['Sat', 'Sun'],
            'is_active'      => true,
        ]);

        return response()->json(['message' => 'Shift updated successfully.']);
    }

    public function storeShiftType(Request $request)
    {
        $isFlexi = (bool) $request->input('is_flexi', false);

        $request->validate([
            'name'           => 'required|string|max:100',
            'code'           => 'required|string|max:20|unique:shifts,code',
            'start_time'     => $isFlexi ? 'nullable|date_format:H:i' : 'required|date_format:H:i',
            'end_time'       => $isFlexi ? 'nullable|date_format:H:i' : 'required|date_format:H:i',
            'break_start'    => 'nullable|date_format:H:i',
            'break_end'      => 'nullable|date_format:H:i',
            'required_hours' => $isFlexi ? 'required|numeric|min:1|max:24' : 'nullable|numeric|min:1|max:24',
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
            'start_time'     => $request->start_time ?? '00:00:00',
            'end_time'       => $request->end_time ?? '00:00:00',
            'break_schedule' => $breakSchedule,
            'is_active'      => true,
            'is_flexi'       => $isFlexi,
            'required_hours' => $isFlexi ? $request->required_hours : null,
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
            'is_flexi'       => (bool) $shift->is_flexi,
            'required_hours' => $shift->required_hours,
        ]);
    }

    public function updateShiftType(Request $request, $id)
    {
        $isFlexi = (bool) $request->input('is_flexi', false);

        $request->validate([
            'name'           => 'required|string|max:100',
            'code'           => 'required|string|max:20|unique:shifts,code,' . $id,
            'start_time'     => $isFlexi ? 'nullable|date_format:H:i' : 'required|date_format:H:i',
            'end_time'       => $isFlexi ? 'nullable|date_format:H:i' : 'required|date_format:H:i',
            'break_start'    => 'nullable|date_format:H:i',
            'break_end'      => 'nullable|date_format:H:i',
            'required_hours' => $isFlexi ? 'required|numeric|min:1|max:24' : 'nullable|numeric|min:1|max:24',
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
            'start_time'     => $request->start_time ?? '00:00:00',
            'end_time'       => $request->end_time ?? '00:00:00',
            'break_schedule' => $breakSchedule,
            'is_flexi'       => $isFlexi,
            'required_hours' => $isFlexi ? $request->required_hours : null,
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
        $employees   = Employee::with('department')->whereHas('user', fn($q) => $q->whereNotNull('email_verified_at'))->orderBy('fname')->get();
        $currentYear = (int) $request->get('year', now()->year);
        $leaveTypes  = LeaveType::where('is_active', true)->orderBy('name')->get();

        $user     = Auth::user();
        $employee = Employee::where('user_id', $user->id)->first();

        $myLeaveStats    = [];
        $myLeaveRequests = collect();

        if ($activeTab === 'my-leave' && $employee) {
            $myLeaveQuery = LeaveRequest::with(['leaveType', 'approver'])
                ->where('employee_id', $employee->id)
                ->orderByDesc('created_at');
            if (request()->filled('status')) {
                $myLeaveQuery->where('status', request('status'));
            }
            if (request()->filled('type')) {
                $myLeaveQuery->where('leave_type_id', request('type'));
            }
            $myLeaveRequests = $myLeaveQuery->get();

            foreach ($leaveTypes as $lt) {
                LeaveCredit::firstOrCreate(
                    ['employee_id' => $employee->id, 'leave_type_id' => $lt->id, 'year' => $currentYear],
                    ['total_days' => $lt->days_entitled ?? 0, 'used_days' => 0, 'remaining_days' => $lt->days_entitled ?? 0]
                );
            }

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

        $calendarEvents = collect();

        if ($activeTab === 'leave-calendar') {
            $calMonth = $request->get('month')
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
            'start_date'    => 'required|date',
            'end_date'      => 'required|date|after_or_equal:start_date',
            'reason'        => 'required|string|max:500',
            'document'      => 'nullable|file|mimes:pdf,docx|max:10240',
        ]);

        $user     = Auth::user();
        $employee = Employee::where('user_id', $user->id)->first();
        if (!$employee) {
            return response()->json(['message' => 'Employee record not found.'], 422);
        }

        $start     = Carbon::parse($request->start_date);
        $end       = Carbon::parse($request->end_date);
        $totalDays = 0;
        $cursor    = $start->copy();
        while ($cursor->lte($end)) {
            $totalDays++;
            $cursor->addDay();
        }

        do {
            $lastRef = LeaveRequest::where('ref_no', 'like', 'REQ-%')
                ->orderByDesc('id')->value('ref_no');
            $nextNum = $lastRef ? (int) substr($lastRef, 4) + 1 : 1;
            $refNo   = 'REQ-' . str_pad($nextNum, 4, '0', STR_PAD_LEFT);
        } while (LeaveRequest::where('ref_no', $refNo)->exists());

        $overlap = LeaveRequest::where('employee_id', $employee->id)
            ->whereIn('status', ['pending', 'supervisor_approved', 'approved'])
            ->where(function ($q) use ($request) {
                $q->whereBetween('start_date', [$request->start_date, $request->end_date])
                  ->orWhereBetween('end_date', [$request->start_date, $request->end_date])
                  ->orWhere(function ($q2) use ($request) {
                      $q2->where('start_date', '<=', $request->start_date)
                         ->where('end_date', '>=', $request->end_date);
                  });
            })->first();

        if ($overlap) {
            return response()->json([
                'message' => 'You already have a ' . $overlap->status . ' leave request covering those dates (' . $overlap->ref_no . ').',
            ], 409);
        }

        $docPath = null;
        if ($request->hasFile('document')) {
            $docPath = $request->file('document')->store('leave_documents', 'public');
        }

        $leaveType = LeaveType::find($request->leave_type_id);
        LeaveCredit::firstOrCreate(
            ['employee_id' => $employee->id, 'leave_type_id' => $request->leave_type_id, 'year' => $start->year],
            ['total_days' => $leaveType->days_entitled ?? 0, 'used_days' => 0, 'remaining_days' => $leaveType->days_entitled ?? 0]
        );

        $leave = LeaveRequest::create([
            'ref_no'        => $refNo,
            'employee_id'   => $employee->id,
            'leave_type_id' => $request->leave_type_id,
            'start_date'    => $request->start_date,
            'end_date'      => $request->end_date,
            'total_days'    => $totalDays,
            'reason'        => $request->reason,
            'document_path' => $docPath,
            'status'        => 'approved',
            'approved_by'   => $employee->id,
            'approved_at'   => now(),
            'hr_notes'      => 'Auto-approved — HR self-request.',
        ]);

        $cursor = Carbon::parse($request->start_date);
        $end    = Carbon::parse($request->end_date);
        while ($cursor->lte($end)) {
            AttendanceLog::updateOrCreate(
                ['employee_id' => $employee->id, 'attendance_date' => $cursor->toDateString()],
                ['status' => 'on_leave', 'work_setup' => null]
            );
            $cursor->addDay();
        }

        $credit = LeaveCredit::where('employee_id', $employee->id)
            ->where('leave_type_id', $request->leave_type_id)
            ->where('year', Carbon::parse($request->start_date)->year)
            ->first();

        if ($credit) {
            $credit->used_days      += $totalDays;
            $credit->remaining_days  = max(0, $credit->total_days - $credit->used_days);
            $credit->save();
        }

        return response()->json(['message' => 'Leave request auto-approved successfully.', 'ref_no' => $refNo]);
    }

    public function approveLeave(Request $request, $id)
    {
        $leave    = LeaveRequest::with('leaveType')->findOrFail($id);
        $approver = Employee::where('user_id', Auth::id())->first();
        if (!$approver) {
            return response()->json(['message' => 'Employee record not found.'], 422);
        }

        if (!in_array($leave->status, ['pending', 'supervisor_approved'])) {
            return response()->json(['message' => 'Leave is no longer pending.'], 409);
        }

        $leave->update([
            'status'      => 'approved',
            'approved_by' => $approver->id,
            'approved_at' => now(),
            'hr_notes'    => $request->hr_notes ?? null,
        ]);

        $cursor = Carbon::parse($leave->start_date);
        $end    = Carbon::parse($leave->end_date);
        $holidayDates = \App\Models\Holiday::whereBetween('date', [$cursor->toDateString(), $end->toDateString()])
            ->pluck('date')
            ->map(fn($d) => \Carbon\Carbon::parse($d)->toDateString())
            ->toArray();
        $leaveEmployeeShift = EmployeeShift::where('employee_id', $leave->employee_id)
            ->where('is_active', true)
            ->whereDate('effective_date', '<=', $cursor->toDateString())
            ->where(fn($q) => $q->whereNull('end_date')->orWhereDate('end_date', '>=', $cursor->toDateString()))
            ->latest('effective_date')
            ->first();
        $leaveDaysOff = $leaveEmployeeShift?->days_off;
        while ($cursor->lte($end)) {
            $isOff = $leaveDaysOff ? in_array($cursor->format('D'), $leaveDaysOff) : $cursor->isWeekend();
            if (!$isOff && !in_array($cursor->toDateString(), $holidayDates)) {
                AttendanceLog::updateOrCreate(
                    ['employee_id' => $leave->employee_id, 'attendance_date' => $cursor->toDateString()],
                    ['status' => 'on_leave', 'work_setup' => null]
                );
            }
            $cursor->addDay();
        }

        $credit = LeaveCredit::where('employee_id', $leave->employee_id)
            ->where('leave_type_id', $leave->leave_type_id)
            ->where('year', Carbon::parse($leave->start_date)->year)
            ->first();

        if ($credit) {
            $credit->used_days      += $leave->total_days;
            $credit->remaining_days  = max(0, $credit->total_days - $credit->used_days);
            $credit->save();
        }

        $this->notifyEmployee(
            $leave->employee_id,
            'Leave Request Approved',
            "Your leave request ({$leave->ref_no}) from {$leave->start_date} to {$leave->end_date} has been approved."
        );
        return response()->json(['message' => 'Leave approved successfully.']);
    }

    public function rejectLeave(Request $request, $id)
    {
        $request->validate(['rejection_reason' => 'required|string|max:500']);

        $leave = LeaveRequest::findOrFail($id);

        if (!in_array($leave->status, ['pending', 'supervisor_approved'])) {
            return response()->json(['message' => 'Leave is no longer pending.'], 409);
        }

        $leave->update([
            'status'           => 'rejected',
            'rejection_reason' => $request->rejection_reason,
        ]);

        $this->notifyEmployee(
            $leave->employee_id,
            'Leave Request Rejected',
            "Your leave request ({$leave->ref_no}) has been rejected. Reason: {$request->rejection_reason}",
            'warning'
        );
        return response()->json(['message' => 'Leave rejected.']);
    }

    public function cancelLeave(Request $request, $id)
    {
        $user     = Auth::user();
        $employee = Employee::where('user_id', $user->id)->first();
        if (!$employee) {
            return response()->json(['message' => 'Employee record not found.'], 422);
        }
        $leave    = LeaveRequest::where('id', $id)
            ->where('employee_id', $employee->id)
            ->firstOrFail();

        if (!in_array($leave->status, ['pending', 'supervisor_approved', 'approved'])) {
            return response()->json(['message' => 'This leave cannot be cancelled.'], 409);
        }

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

        if ($request->days_entitled) {
            $activeEmployees = Employee::where('employment_status', 'Active')->get();
            foreach ($activeEmployees as $emp) {
                LeaveCredit::firstOrCreate(
                    ['employee_id' => $emp->id, 'leave_type_id' => $leaveType->id, 'year' => now()->year],
                    ['total_days' => $request->days_entitled, 'used_days' => 0, 'remaining_days' => $request->days_entitled]
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

        if ($request->days_entitled) {
            LeaveCredit::where('leave_type_id', $id)
                ->where('year', now()->year)
                ->get()
                ->each(function ($credit) use ($request) {
                    $credit->total_days     = $request->days_entitled;
                    $credit->remaining_days = max(0, $request->days_entitled - $credit->used_days);
                    $credit->save();
                });
        }

        return response()->json(['message' => 'Leave type updated successfully.']);
    }

    // ══════════════════════════════════════════════════════════════════════
    // REQUESTS & APPROVAL
    // ══════════════════════════════════════════════════════════════════════

    public function pendingRequests(Request $request)
    {
        $departments  = Department::orderBy('name')->get();
        $shiftTypes   = \App\Models\Shift::where('is_active', true)->orderBy('name')->get();
        $leaveTypes   = LeaveType::where('is_active', true)->orderBy('name')->get();

        $authEmployee = Employee::where('user_id', Auth::id())->first();
        $authEmpId    = $authEmployee?->id ?? 0;

        $filterType = $request->get('type', 'all');
        $filterDept = $request->get('department');
        $search     = $request->get('search');

        $leaveQuery = LeaveRequest::with(['employee.department', 'employee.jobTitle', 'leaveType'])
            ->whereIn('status', ['pending', 'supervisor_approved'])
            ->where('employee_id', '!=', $authEmpId);
        if ($filterDept) {
            $leaveQuery->whereHas('employee', fn($q) => $q->where('department_id', $filterDept));
        }
        if ($search) {
            $leaveQuery->whereHas('employee', fn($q) =>
                $q->where('fname', 'like', "%$search%")->orWhere('lname', 'like', "%$search%")
            );
        }

        $otQuery = OvertimeRequest::with(['employee.department', 'employee.jobTitle'])
            ->whereIn('status', ['pending', 'supervisor_approved'])
            ->where('employee_id', '!=', $authEmpId);
        if ($filterDept) {
            $otQuery->whereHas('employee', fn($q) => $q->where('department_id', $filterDept));
        }
        if ($search) {
            $otQuery->whereHas('employee', fn($q) =>
                $q->where('fname', 'like', "%$search%")->orWhere('lname', 'like', "%$search%")
            );
        }

        $shiftQuery = ShiftChangeRequest::with(['employee.department', 'employee.jobTitle', 'currentShift', 'requestedShift'])
            ->whereIn('status', ['pending', 'supervisor_approved'])
            ->where('employee_id', '!=', $authEmpId);
        if ($filterDept) {
            $shiftQuery->whereHas('employee', fn($q) => $q->where('department_id', $filterDept));
        }
        if ($search) {
            $shiftQuery->whereHas('employee', fn($q) =>
                $q->where('fname', 'like', "%$search%")->orWhere('lname', 'like', "%$search%")
            );
        }

        $adjustQuery = AttendanceAdjustmentRequest::with(['employee.department', 'employee.jobTitle'])
            ->whereIn('status', ['pending', 'supervisor_approved'])
            ->where('employee_id', '!=', $authEmpId);
        if ($filterDept) {
            $adjustQuery->whereHas('employee', fn($q) => $q->where('department_id', $filterDept));
        }
        if ($search) {
            $adjustQuery->whereHas('employee', fn($q) =>
                $q->where('fname', 'like', "%$search%")->orWhere('lname', 'like', "%$search%")
            );
        }

        $leaveCount      = $leaveQuery->count();
        $overtimeCount   = $otQuery->count();
        $shiftCount      = $shiftQuery->count();
        $adjustmentCount = $adjustQuery->count();
        $awaitingCount   = $leaveCount + $overtimeCount + $shiftCount + $adjustmentCount;

        $allRequests = collect();

        if ($filterType === 'all' || $filterType === 'leave') {
            foreach ($leaveQuery->get() as $r) {
                $allRequests->push((object)[
                    'type'          => 'leave',
                    'id'            => $r->id,
                    'ref_no'        => $r->ref_no,
                    'status'        => $r->status,
                    'employee'      => $r->employee,
                    'leaveType'     => $r->leaveType,
                    'start_date'    => $r->start_date,
                    'end_date'      => $r->end_date,
                    'total_days'    => $r->total_days,
                    'reason'        => $r->reason,
                    'document_path' => $r->document_path,
                    'created_at'    => $r->created_at,
                    'credit'        => LeaveCredit::where('employee_id', $r->employee_id)
                                        ->where('leave_type_id', $r->leave_type_id)
                                        ->where('year', Carbon::parse($r->start_date)->year)
                                        ->first(),
                ]);
            }
        }

        if ($filterType === 'all' || $filterType === 'overtime') {
            foreach ($otQuery->get() as $r) {
                $allRequests->push((object)[
                    'type'            => 'overtime',
                    'id'              => $r->id,
                    'ref_no'          => $r->ref_no,
                    'status'          => $r->status,
                    'employee'        => $r->employee,
                    'ot_date'         => $r->ot_date,
                    'ot_start_time'   => $r->ot_start_time,
                    'ot_end_time'     => $r->ot_end_time,
                    'requested_hours' => $r->requested_hours,
                    'reason'          => $r->reason,
                    'document_path'   => $r->document_path,
                    'created_at'      => $r->created_at,
                ]);
            }
        }

        if ($filterType === 'all' || $filterType === 'shift') {
            foreach ($shiftQuery->get() as $r) {
                $allRequests->push((object)[
                    'type'            => 'shift',
                    'id'              => $r->id,
                    'ref_no'          => $r->ref_no,
                    'status'          => $r->status,
                    'employee'        => $r->employee,
                    'current_shift'   => $r->currentShift,
                    'requested_shift' => $r->requestedShift,
                    'effective_from'  => $r->effective_from,
                    'effective_until' => $r->effective_until,
                    'reason'          => $r->reason,
                    'document_path'   => $r->document_path,
                    'created_at'      => $r->created_at,
                ]);
            }
        }

        if ($filterType === 'all' || $filterType === 'adjustment') {
            foreach ($adjustQuery->get() as $r) {
                $allRequests->push((object)[
                    'type'                => 'adjustment',
                    'id'                  => $r->id,
                    'ref_no'              => $r->ref_no,
                    'status'              => $r->status,
                    'employee'            => $r->employee,
                    'attendance_date'     => $r->attendance_date,
                    'original_clock_in'   => $r->original_clock_in,
                    'original_clock_out'  => $r->original_clock_out,
                    'requested_clock_in'  => $r->requested_clock_in,
                    'requested_clock_out' => $r->requested_clock_out,
                    'reason'              => $r->reason,
                    'document_path'       => $r->document_path,
                    'created_at'          => $r->created_at,
                ]);
            }
        }

        $requests = $allRequests->sortByDesc('created_at')->values();

        return view('hr.hr_pending-requests', compact(
            'departments', 'shiftTypes', 'leaveTypes',
            'awaitingCount', 'leaveCount', 'shiftCount', 'overtimeCount', 'adjustmentCount',
            'requests', 'filterType'
        ));
    }

    public function approvedRequests(Request $request)
    {
        $departments = Department::orderBy('name')->get();

        $filterType   = $request->get('type', 'all');
        $filterDept   = $request->get('department');
        $filterStatus = $request->get('status', 'all');
        $search       = $request->get('search');

        $authEmployee = Employee::where('user_id', Auth::id())->first();
        $authEmpId    = $authEmployee?->id ?? 0;

        $allRequests = collect();

        if ($filterType === 'all' || $filterType === 'leave') {
            $q = LeaveRequest::with(['employee.department', 'employee.jobTitle', 'leaveType', 'approver'])
                ->whereIn('status', ['approved', 'rejected', 'cancelled']);
            if ($filterDept)             $q->whereHas('employee', fn($e) => $e->where('department_id', $filterDept));
            if ($filterStatus !== 'all') $q->where('status', $filterStatus);
            if ($search)                 $q->whereHas('employee', fn($e) => $e->where('fname', 'like', "%$search%")->orWhere('lname', 'like', "%$search%"));
            foreach ($q->get() as $r) {
                $allRequests->push((object)[
                    'type'             => 'leave',
                    'id'               => $r->id,
                    'ref_no'           => $r->ref_no,
                    'status'           => $r->status,
                    'employee'         => $r->employee,
                    'leaveType'        => $r->leaveType,
                    'start_date'       => $r->start_date,
                    'end_date'         => $r->end_date,
                    'total_days'       => $r->total_days,
                    'reason'           => $r->reason,
                    'document_path'    => $r->document_path,
                    'rejection_reason' => $r->rejection_reason,
                    'hr_notes'         => $r->hr_notes,
                    'approver'         => $r->approver,
                    'approved_at'      => $r->approved_at,
                    'created_at'       => $r->created_at,
                ]);
            }
        }

        if ($filterType === 'all' || $filterType === 'overtime') {
            $q = OvertimeRequest::with(['employee.department', 'employee.jobTitle'])
                ->whereIn('status', ['approved', 'rejected']);
            if ($filterDept)             $q->whereHas('employee', fn($e) => $e->where('department_id', $filterDept));
            if ($filterStatus !== 'all') $q->where('status', $filterStatus);
            if ($search)                 $q->whereHas('employee', fn($e) => $e->where('fname', 'like', "%$search%")->orWhere('lname', 'like', "%$search%"));
            foreach ($q->get() as $r) {
                $allRequests->push((object)[
                    'type'             => 'overtime',
                    'id'               => $r->id,
                    'ref_no'           => $r->ref_no,
                    'status'           => $r->status,
                    'employee'         => $r->employee,
                    'ot_date'          => $r->ot_date,
                    'ot_start_time'    => $r->ot_start_time,
                    'ot_end_time'      => $r->ot_end_time,
                    'requested_hours'  => $r->requested_hours,
                    'approved_hours'   => $r->approved_hours,
                    'reason'           => $r->reason,
                    'document_path'    => $r->document_path,
                    'rejection_reason' => $r->rejection_reason,
                    'approved_by'      => $r->approved_by,
                    'approved_at'      => $r->approved_at,
                    'created_at'       => $r->created_at,
                ]);
            }
        }

        if ($filterType === 'all' || $filterType === 'shift') {
            $q = ShiftChangeRequest::with(['employee.department', 'employee.jobTitle', 'currentShift', 'requestedShift'])
                ->whereIn('status', ['approved', 'rejected']);
            if ($filterDept)             $q->whereHas('employee', fn($e) => $e->where('department_id', $filterDept));
            if ($filterStatus !== 'all') $q->where('status', $filterStatus);
            if ($search)                 $q->whereHas('employee', fn($e) => $e->where('fname', 'like', "%$search%")->orWhere('lname', 'like', "%$search%"));
            foreach ($q->get() as $r) {
                $allRequests->push((object)[
                    'type'             => 'shift',
                    'id'               => $r->id,
                    'ref_no'           => $r->ref_no,
                    'status'           => $r->status,
                    'employee'         => $r->employee,
                    'current_shift'    => $r->currentShift,
                    'requested_shift'  => $r->requestedShift,
                    'effective_from'   => $r->effective_from,
                    'effective_until'  => $r->effective_until,
                    'reason'           => $r->reason,
                    'document_path'    => $r->document_path,
                    'rejection_reason' => $r->rejection_reason,
                    'approved_by'      => $r->approved_by,
                    'approved_at'      => $r->approved_at,
                    'created_at'       => $r->created_at,
                ]);
            }
        }

        if ($filterType === 'all' || $filterType === 'adjustment') {
            $q = AttendanceAdjustmentRequest::with(['employee.department', 'employee.jobTitle', 'approver'])
                ->whereIn('status', ['approved', 'rejected'])
                ->where('employee_id', '!=', $authEmpId);
            if ($filterDept)             $q->whereHas('employee', fn($e) => $e->where('department_id', $filterDept));
            if ($filterStatus !== 'all') $q->where('status', $filterStatus);
            if ($search)                 $q->whereHas('employee', fn($e) => $e->where('fname', 'like', "%$search%")->orWhere('lname', 'like', "%$search%"));
            foreach ($q->get() as $r) {
                $allRequests->push((object)[
                    'type'                => 'adjustment',
                    'id'                  => $r->id,
                    'ref_no'              => $r->ref_no,
                    'status'              => $r->status,
                    'employee'            => $r->employee,
                    'attendance_date'     => $r->attendance_date,
                    'original_clock_in'   => $r->original_clock_in,
                    'original_clock_out'  => $r->original_clock_out,
                    'requested_clock_in'  => $r->requested_clock_in,
                    'requested_clock_out' => $r->requested_clock_out,
                    'reason'              => $r->reason,
                    'rejection_reason'    => $r->rejection_reason,
                    'approved_by'         => $r->approved_by,
                    'approver'            => $r->approver,
                    'approved_at'         => $r->approved_at,
                    'created_at'          => $r->created_at,
                ]);
            }
        }

        $approvedCount = $allRequests->where('status', 'approved')->count();
        $rejectedCount = $allRequests->where('status', 'rejected')->count();
        $requests      = $filterStatus !== 'all'
            ? $allRequests->where('status', $filterStatus)->sortByDesc('created_at')->values()
            : $allRequests->sortByDesc('created_at')->values();

        return view('hr.hr_approved-requests', compact(
            'departments', 'requests',
            'approvedCount', 'rejectedCount',
            'filterType', 'filterStatus', 'search', 'filterDept'
        ));
    }

    public function approveRequest(Request $request, $id)
    {
        $leave = LeaveRequest::find($id);
        if (!$leave) {
            return response()->json(['message' => 'Leave request not found.'], 404);
        }
        if (!in_array($leave->status, ['pending', 'supervisor_approved'])) {
            return response()->json(['message' => 'Leave is no longer pending.'], 409);
        }
        return $this->approveLeave($request, $id);
    }

    public function rejectRequest(Request $request, $id)
    {
        if (!$request->filled('rejection_reason')) {
            $request->merge(['rejection_reason' => $request->reason]);
        }
        if (!$request->filled('rejection_reason')) {
            return response()->json(['message' => 'Please provide a rejection reason.'], 422);
        }

        $leave = LeaveRequest::find($id);
        if ($leave && !in_array($leave->status, ['pending', 'supervisor_approved'])) {
            return response()->json(['message' => 'Leave is no longer pending.'], 409);
        }

        return $this->rejectLeave($request, $id);
    }

    public function fileOvertimeRequest(Request $request)
    {
        $request->validate([
            'ot_date'       => 'required|date',
            'ot_start_time' => 'required',
            'ot_end_time'   => 'required',
            'reason'        => 'required|string|max:500',
            'document'      => 'nullable|file|mimes:pdf,docx|max:10240',
        ]);

        $otStart = Carbon::parse($request->ot_date . ' ' . $request->ot_start_time);
        $otEnd   = Carbon::parse($request->ot_date . ' ' . $request->ot_end_time);
        if ($otEnd->lte($otStart)) {
            $otEnd->addDay();
        }
        if ($otEnd->lte($otStart)) {
            return response()->json(['message' => 'OT end time must be after start time.'], 422);
        }

        $user     = Auth::user();
        $employee = Employee::where('user_id', $user->id)->first();
        if (!$employee) {
            return response()->json(['message' => 'Employee record not found.'], 422);
        }

        $requestedHours = round($otStart->diffInMinutes($otEnd) / 60, 2);

        do {
            $lastRef = OvertimeRequest::where('ref_no', 'like', 'OT-%')
                ->orderByDesc('id')->value('ref_no');
            $nextNum = $lastRef ? (int) substr($lastRef, 3) + 1 : 1;
            $refNo   = 'OT-' . str_pad($nextNum, 4, '0', STR_PAD_LEFT);
        } while (OvertimeRequest::where('ref_no', $refNo)->exists());

        $docPath = null;
        if ($request->hasFile('document')) {
            $docPath = $request->file('document')->store('ot_documents', 'public');
        }

        OvertimeRequest::create([
            'ref_no'          => $refNo,
            'employee_id'     => $employee->id,
            'requested_by'    => $user->id,
            'ot_date'         => $request->ot_date,
            'ot_start_time'   => $request->ot_start_time,
            'ot_end_time'     => $request->ot_end_time,
            'requested_hours' => $requestedHours,
            'reason'          => $request->reason,
            'document_path'   => $docPath,
            'status'          => 'pending',
        ]);

        $this->notifyHR(
            'New Overtime Request',
            "{$employee->full_name} (HR) filed an overtime request ({$refNo}) on {$request->ot_date} ({$requestedHours} hrs)."
        );

        return response()->json(['message' => 'Overtime request filed successfully.', 'ref_no' => $refNo]);
    }

    public function fileShiftChangeRequest(Request $request)
    {
        $request->validate([
            'requested_shift_id' => 'required|exists:shifts,id',
            'effective_from'     => 'required|date|after_or_equal:today',
            'effective_until'    => 'nullable|date|after_or_equal:effective_from',
            'reason'             => 'required|string|max:500',
            'document'           => 'nullable|file|mimes:pdf,docx|max:10240',
        ]);

        $user     = Auth::user();
        $employee = Employee::where('user_id', $user->id)->first();
        if (!$employee) {
            return response()->json(['message' => 'Employee record not found.'], 422);
        }

        $currentShift = EmployeeShift::where('employee_id', $employee->id)
            ->where('is_active', true)
            ->latest('effective_date')
            ->first();

        do {
            $lastRef = ShiftChangeRequest::where('ref_no', 'like', 'SCR-%')
                ->orderByDesc('id')->value('ref_no');
            $nextNum = $lastRef ? (int) substr($lastRef, 4) + 1 : 1;
            $refNo   = 'SCR-' . str_pad($nextNum, 4, '0', STR_PAD_LEFT);
        } while (ShiftChangeRequest::where('ref_no', $refNo)->exists());

        $docPath = null;
        if ($request->hasFile('document')) {
            $docPath = $request->file('document')->store('shift_documents', 'public');
        }

        ShiftChangeRequest::create([
            'ref_no'             => $refNo,
            'employee_id'        => $employee->id,
            'current_shift_id'   => $currentShift?->shift_id,
            'requested_shift_id' => $request->requested_shift_id,
            'effective_from'     => $request->effective_from,
            'effective_until'    => $request->effective_until ?? null,
            'reason'             => $request->reason,
            'document_path'      => $docPath,
            'status'             => 'pending',
        ]);

        return response()->json(['message' => 'Shift change request filed successfully.', 'ref_no' => $refNo]);
    }

    public function approveOvertimeRequest(Request $request, $id)
    {
        $ot       = OvertimeRequest::findOrFail($id);
        $approver = Employee::where('user_id', Auth::id())->first();
        if (!$approver) {
            return response()->json(['message' => 'Employee record not found.'], 422);
        }

        if (!in_array($ot->status, ['pending', 'supervisor_approved'])) {
            return response()->json(['message' => 'Request is no longer pending.'], 409);
        }

        $ot->update([
            'status'         => 'approved',
            'approved_by'    => Auth::id(),
            'approved_hours' => $ot->requested_hours,
            'approved_at'    => now(),
        ]);

        $this->notifyEmployee(
            $ot->employee_id,
            'Overtime Request Approved',
            "Your overtime request ({$ot->ref_no}) on {$ot->ot_date} ({$ot->requested_hours} hrs) has been approved."
        );

        return response()->json(['message' => 'Overtime request approved.']);
    }

    public function rejectOvertimeRequest(Request $request, $id)
    {
        $request->validate(['rejection_reason' => 'required|string|max:500']);
        $ot = OvertimeRequest::findOrFail($id);

        if (!in_array($ot->status, ['pending', 'supervisor_approved'])) {
            return response()->json(['message' => 'Request is no longer pending.'], 409);
        }

        $ot->update([
            'status'           => 'rejected',
            'rejection_reason' => $request->rejection_reason,
        ]);

        $this->notifyEmployee(
            $ot->employee_id,
            'Overtime Request Rejected',
            "Your overtime request ({$ot->ref_no}) has been rejected. Reason: {$request->rejection_reason}",
            'warning'
        );

        return response()->json(['message' => 'Overtime request rejected.']);
    }

    public function approveShiftChangeRequest(Request $request, $id)
    {
        $scr      = ShiftChangeRequest::findOrFail($id);
        $approver = Employee::where('user_id', Auth::id())->first();
        if (!$approver) {
            return response()->json(['message' => 'Employee record not found.'], 422);
        }

        if (!in_array($scr->status, ['pending', 'supervisor_approved'])) {
            return response()->json(['message' => 'Request is no longer pending.'], 409);
        }

        $scr->update([
            'status'      => 'approved',
            'approved_by' => $approver->id,
            'approved_at' => now(),
        ]);

        $oldShift = EmployeeShift::where('employee_id', $scr->employee_id)
            ->where('is_active', true)
            ->latest('effective_date')
            ->first();

        // Deactivate current shift, ending it the day before the new one starts
        if ($oldShift) {
            $oldShift->update([
                'is_active' => false,
                'end_date'  => Carbon::parse($scr->effective_from)->subDay()->toDateString(),
            ]);
        }

        // Create the new shift assignment, carrying over work_setup and days_off
        EmployeeShift::create([
            'employee_id'    => $scr->employee_id,
            'shift_id'       => $scr->requested_shift_id,
            'work_setup'     => $oldShift?->work_setup ?? 'office',
            'effective_date' => $scr->effective_from,
            'end_date'       => $scr->effective_until ?? null,
            'is_active'      => true,
            'days_off'       => $oldShift?->days_off ?? json_encode(['Sat', 'Sun']),
        ]);

        // If temporary, restore the old shift automatically after effective_until
        if ($scr->effective_until && $oldShift) {
            EmployeeShift::create([
                'employee_id'    => $scr->employee_id,
                'shift_id'       => $oldShift->shift_id,
                'work_setup'     => $oldShift->work_setup,
                'effective_date' => Carbon::parse($scr->effective_until)->addDay()->toDateString(),
                'end_date'       => null,
                'is_active'      => true,
                'days_off'       => $oldShift->days_off,
            ]);
        }

        return response()->json(['message' => 'Shift change request approved.']);
    }

    public function rejectShiftChangeRequest(Request $request, $id)
    {
        $request->validate(['rejection_reason' => 'required|string|max:500']);
        $scr = ShiftChangeRequest::findOrFail($id);

        if (!in_array($scr->status, ['pending', 'supervisor_approved'])) {
            return response()->json(['message' => 'Request is no longer pending.'], 409);
        }

        $scr->update([
            'status'           => 'rejected',
            'rejection_reason' => $request->rejection_reason,
        ]);

        return response()->json(['message' => 'Shift change request rejected.']);
    }

    public function updateAttendanceLog(Request $request, $id)
    {
        $log = AttendanceLog::with('shift')->findOrFail($id);

        if (Carbon::parse($log->attendance_date)->isToday()) {
            return response()->json(['message' => "Cannot edit today's attendance."], 422);
        }

        $request->validate([
            'clock_in'  => 'required|date_format:H:i',
            'clock_out' => 'nullable|date_format:H:i',
        ]);

        $date     = Carbon::parse($log->attendance_date)->toDateString();
        $clockIn  = Carbon::createFromFormat('Y-m-d H:i', "$date {$request->clock_in}");
        $clockOut = $request->clock_out
            ? Carbon::createFromFormat('Y-m-d H:i', "$date {$request->clock_out}")
            : null;

        if ($clockOut) {
            $isNightShift = $log->shift && $log->shift->end_time < $log->shift->start_time;
            if ($clockOut->lte($clockIn)) {
                if ($isNightShift) {
                    $clockOut->addDay();
                } else {
                    return response()->json(['message' => 'Clock-out must be after clock-in.'], 422);
                }
            }
        }

        $breakMin     = $log->break_minutes ?? 0;
        $totalWorkMin = $clockOut ? max(0, (int) $clockIn->diffInMinutes($clockOut) - $breakMin) : 0;
        $lateMinutes  = $log->late_minutes ?? 0;
        $status       = $log->status;

        if ($log->shift) {
            $isNightShift = $log->shift->end_time < $log->shift->start_time;
            $shiftStart   = Carbon::createFromTimeString("$date {$log->shift->start_time}");
            $shiftEnd     = Carbon::createFromTimeString("$date {$log->shift->end_time}");
            if ($isNightShift) $shiftEnd->addDay();
            $lateMinutes = $clockIn->gt($shiftStart) ? min(999, (int) $shiftStart->diffInMinutes($clockIn)) : 0;
            $isLate      = $lateMinutes > 0;
            if ($clockOut) {
                if ($clockOut->lt($shiftEnd))      $status = $isLate ? 'late' : 'undertime';
                elseif ($clockOut->gt($shiftEnd))  $status = $isLate ? 'late' : 'overtime';
                else                               $status = $isLate ? 'late' : 'present';
            } else {
                $status = $isLate ? 'late' : 'present';
            }
        }

        $log->update([
            'clock_in'     => $clockIn,
            'clock_out'    => $clockOut,
            'total_hours'  => round($totalWorkMin / 60, 2),
            'late_minutes' => $lateMinutes,
            'status'       => $status,
        ]);

        return response()->json(['success' => true]);
    }

    public function exportCsv(Request $request)
    {
        $user     = Auth::user();
        $employee = Employee::where('user_id', $user->id)->first();
        if (!$employee) abort(404);

        $month = (int) $request->get('month', Carbon::now()->month);
        $year  = (int) $request->get('year',  Carbon::now()->year);

        $logs = AttendanceLog::with('shift')
            ->where('employee_id', $employee->id)
            ->whereMonth('attendance_date', $month)
            ->whereYear('attendance_date', $year)
            ->orderBy('attendance_date')
            ->get();

        $name     = trim($employee->fname . '_' . $employee->lname);
        $filename = 'attendance_' . $name . '_' . Carbon::create($year, $month)->format('F_Y') . '.csv';

        $headers = [
            'Content-Type'        => 'text/csv',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
            'Pragma'              => 'no-cache',
            'Cache-Control'       => 'must-revalidate, post-check=0, pre-check=0',
            'Expires'             => '0',
        ];

        $callback = function () use ($logs) {
            $h = fopen('php://output', 'w');
            fputcsv($h, ['Date', 'Work Setup', 'Shift', 'Schedule', 'Clock In', 'Clock Out', 'Break Start', 'Break (min)', 'Total Hours', 'Overtime (min)', 'Undertime (min)', 'Status']);
            foreach ($logs as $log) {
                $shift    = $log->shift;
                $schedule = $shift ? Carbon::parse($shift->start_time)->format('g:i A') . ' - ' . Carbon::parse($shift->end_time)->format('g:i A') : '';
                fputcsv($h, [
                    Carbon::parse($log->attendance_date)->format('Y-m-d'),
                    $log->work_setup ? strtoupper($log->work_setup) : '',
                    $shift->name ?? '',
                    $schedule,
                    $log->clock_in  ? Carbon::parse($log->clock_in)->format('h:i A')  : '',
                    $log->clock_out ? Carbon::parse($log->clock_out)->format('h:i A') : '',
                    $log->break_start ? Carbon::parse($log->break_start)->format('h:i A') : '',
                    $log->break_minutes ?? 0,
                    $log->total_hours ?? '',
                    $log->overtime_minutes ?? 0,
                    $log->undertime_minutes ?? 0,
                    $log->status ? ucwords(str_replace('_', ' ', $log->status)) : '',
                ]);
            }
            fclose($h);
        };

        return response()->stream($callback, 200, $headers);
    }

    public function fileAttendanceAdjustment(Request $request)
    {
        $request->validate([
            'attendance_date'     => 'required|date|before:today',
            'requested_clock_in'  => 'required|date_format:H:i',
            'requested_clock_out' => 'nullable|date_format:H:i',
            'reason'              => 'required|string|max:500',
            'document'            => 'nullable|file|mimes:pdf,docx|max:10240',
        ]);

        $employee = Employee::where('user_id', Auth::id())->first();
        if (!$employee) {
            return response()->json(['message' => 'Employee record not found.'], 422);
        }

        $log = AttendanceLog::where('employee_id', $employee->id)
            ->whereDate('attendance_date', $request->attendance_date)->first();

        do {
            $lastRef = AttendanceAdjustmentRequest::where('ref_no', 'like', 'AAR-%')
                ->orderByDesc('id')->value('ref_no');
            $nextNum = $lastRef ? (int) substr($lastRef, 4) + 1 : 1;
            $refNo   = 'AAR-' . str_pad($nextNum, 4, '0', STR_PAD_LEFT);
        } while (AttendanceAdjustmentRequest::where('ref_no', $refNo)->exists());

        $docPath = $request->hasFile('document')
            ? $request->file('document')->store('adjustment_documents', 'public') : null;

        $aar = AttendanceAdjustmentRequest::create([
            'ref_no'              => $refNo,
            'employee_id'         => $employee->id,
            'attendance_log_id'   => $log?->id,
            'attendance_date'     => $request->attendance_date,
            'original_clock_in'   => $log?->clock_in  ? Carbon::parse($log->clock_in)->format('H:i')  : null,
            'original_clock_out'  => $log?->clock_out ? Carbon::parse($log->clock_out)->format('H:i') : null,
            'requested_clock_in'  => $request->requested_clock_in,
            'requested_clock_out' => $request->requested_clock_out,
            'reason'              => $request->reason,
            'document_path'       => $docPath,
            'status'              => 'approved',
            'approved_by'         => Auth::id(),
            'approved_at'         => now(),
        ]);

        $this->applyAdjustment($aar);

        return response()->json(['message' => 'Attendance adjustment applied successfully.', 'ref_no' => $refNo]);
    }

    public function approveAttendanceAdjustment(Request $request, $id)
    {
        $aar = AttendanceAdjustmentRequest::with('attendanceLog')->findOrFail($id);

        if (!in_array($aar->status, ['pending', 'supervisor_approved'])) {
            return response()->json(['message' => 'Request is no longer pending.'], 409);
        }

        $this->applyAdjustment($aar);

        $aar->update([
            'status'      => 'approved',
            'approved_by' => Auth::id(),
            'approved_at' => now(),
        ]);

        $this->notifyEmployee(
            $aar->employee_id,
            'Attendance Adjustment Approved',
            "Your attendance adjustment request ({$aar->ref_no}) for {$aar->attendance_date->format('F j, Y')} has been approved."
        );

        return response()->json(['message' => 'Attendance adjustment approved.']);
    }

    public function rejectAttendanceAdjustment(Request $request, $id)
    {
        $request->validate(['rejection_reason' => 'required|string|max:500']);
        $aar = AttendanceAdjustmentRequest::findOrFail($id);

        if (!in_array($aar->status, ['pending', 'supervisor_approved'])) {
            return response()->json(['message' => 'Request is no longer pending.'], 409);
        }

        $aar->update([
            'status'           => 'rejected',
            'rejection_reason' => $request->rejection_reason,
            'rejected_by'      => Auth::id(),
            'rejected_at'      => now(),
        ]);

        $this->notifyEmployee(
            $aar->employee_id,
            'Attendance Adjustment Rejected',
            "Your attendance adjustment request ({$aar->ref_no}) has been rejected. Reason: {$request->rejection_reason}",
            'warning'
        );

        return response()->json(['message' => 'Attendance adjustment rejected.']);
    }

    private function applyAdjustment(AttendanceAdjustmentRequest $aar): void
    {
        $date     = $aar->attendance_date->toDateString();
        $clockIn  = Carbon::parse("$date {$aar->requested_clock_in}");
        $clockOut = $aar->requested_clock_out
            ? Carbon::parse("$date {$aar->requested_clock_out}") : null;

        $log = $aar->attendanceLog ?? AttendanceLog::where('employee_id', $aar->employee_id)
            ->whereDate('attendance_date', $date)->first();

        if (!$log) {
            $log = AttendanceLog::create(['employee_id' => $aar->employee_id, 'attendance_date' => $date, 'status' => 'present']);
        }

        $shift = $log->shift ?? \App\Models\EmployeeShift::with('shift')
            ->where('employee_id', $aar->employee_id)->where('is_active', true)
            ->latest('effective_date')->first()?->shift;

        $isNightShift = $shift && $shift->end_time < $shift->start_time;
        if ($clockOut && $clockOut->lte($clockIn) && $isNightShift) $clockOut->addDay();

        $breakMin     = $log->break_minutes ?? 0;
        $totalWorkMin = $clockOut ? max(0, (int) $clockIn->diffInMinutes($clockOut) - $breakMin) : 0;
        $lateMinutes  = 0;
        $status       = $log->status;

        if ($shift) {
            $shiftStart  = Carbon::createFromTimeString("$date {$shift->start_time}");
            $shiftEnd    = Carbon::createFromTimeString("$date {$shift->end_time}");
            if ($isNightShift) $shiftEnd->addDay();
            $lateMinutes = $clockIn->gt($shiftStart) ? min(999, (int) $shiftStart->diffInMinutes($clockIn)) : 0;
            $isLate      = $lateMinutes > 0;
            if ($clockOut) {
                if ($clockOut->lt($shiftEnd))     $status = $isLate ? 'late' : 'undertime';
                elseif ($clockOut->gt($shiftEnd)) $status = $isLate ? 'late' : 'overtime';
                else                              $status = $isLate ? 'late' : 'present';
            } else {
                $status = $isLate ? 'late' : 'present';
            }
        } else {
            if ($status === 'absent') $status = 'present';
        }

        $log->update([
            'clock_in'     => $clockIn,
            'clock_out'    => $clockOut,
            'late_minutes' => $lateMinutes,
            'total_hours'  => round($totalWorkMin / 60, 2),
            'status'       => $status,
        ]);
    }
}
<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use App\Models\AttendanceLog;
use App\Models\Employee;
use App\Models\EmployeeShift;

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
    // Cap at 999 to prevent DB overflow, and only mark late within same workday (< 6 hours)
    if ($lateMinutes > 0 && $lateMinutes < 360) {
        $status = 'late';
    } elseif ($lateMinutes >= 360) {
        // More than 6 hours late = treat as present (different shift scenario)
        $lateMinutes = 0;
        $status = 'present';
    }
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
    public function employeeAttendance()
    {
        return view('admin.admin_employee_attendance-reports');
    }




}
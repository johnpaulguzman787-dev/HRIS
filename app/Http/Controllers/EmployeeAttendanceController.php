<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use App\Models\AttendanceLog;
use App\Models\Employee;
use App\Models\EmployeeShift;
use App\Models\LeaveType;
use App\Models\LeaveCredit;
use App\Models\LeaveRequest;
use App\Models\OvertimeRequest;
use App\Models\ShiftChangeRequest;

class EmployeeAttendanceController extends Controller
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

        return view('employee.employee_attendance-reports', compact('employeeShift', 'availableShifts', 'stats', 'todayLog', 'month', 'year'));
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

        if ($existing && $existing->status === 'on_leave') {
            return response()->json(['message' => 'You are on approved leave today.'], 409);
        }

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

            // Night shift: if end_time is earlier than start_time, it crosses midnight
            if ($shiftEnd->lt(Carbon::createFromTimeString(
                Carbon::today()->toDateString() . ' ' . $selectedShift->start_time
            ))) {
                $shiftEnd->addDay();
            }

            if ($now->gt($shiftEnd)) {
                $approvedOt = OvertimeRequest::where('employee_id', $employee->id)
                    ->where('status', 'approved')
                    ->whereDate('ot_date', $today)
                    ->first();

                if ($approvedOt) {
                    $approvedEnd = Carbon::createFromTimeString(
                        Carbon::today()->toDateString() . ' ' . $approvedOt->ot_end_time
                    );
                    if ($approvedEnd->lt($shiftEnd)) {
                        $approvedEnd->addDay();
                    }
                    $overtimeMinutes = (int) $shiftEnd->diffInMinutes($approvedEnd);
                    if ($clockOutStatus !== 'late') $clockOutStatus = 'overtime';
                }
            } elseif ($now->lt($shiftEnd)) {
                $undertimeMinutes = (int) $now->diffInMinutes($shiftEnd);
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

        $shift = $log->shift_id ? \App\Models\Shift::find($log->shift_id) : null;
        if (!$shift || !$shift->break_schedule) {
            return response()->json(['message' => 'No break schedule defined for your shift.'], 422);
        }

        $breakSchedule = json_decode($shift->break_schedule, true);
        if (empty($breakSchedule['start']) || empty($breakSchedule['end'])) {
            return response()->json(['message' => 'No break schedule defined for your shift.'], 422);
        }

        $breakStart = Carbon::createFromTimeString(Carbon::today()->toDateString() . ' ' . $breakSchedule['start']);
        $breakEnd   = Carbon::createFromTimeString(Carbon::today()->toDateString() . ' ' . $breakSchedule['end']);

        if ($now->lt($breakStart) || $now->gt($breakEnd)) {
            return response()->json([
                'message' => 'Break is only allowed between ' . $breakStart->format('g:i A') . ' and ' . $breakEnd->format('g:i A') . '.',
            ], 422);
        }

        $log->update(['break_start' => $now]);

        return response()->json([
            'message'     => 'Break started.',
            'break_start' => $now->format('h:i A'),
        ]);
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

    // ══════════════════════════════════════════════════════════════════════
    // LEAVE MANAGEMENT — added below, nothing above was changed
    // ══════════════════════════════════════════════════════════════════════

    public function leaveManagement(Request $request)
    {
        $activeTab   = $request->get('tab', 'my-leave');
        $currentYear = (int) $request->get('year', now()->year);
        $leaveTypes  = LeaveType::where('is_active', true)->orderBy('name')->get();

        $user     = Auth::user();
        $employee = Employee::where('user_id', $user->id)->first();

        // ── MY LEAVE tab ──────────────────────────────────────────────
        $myLeaveStats    = [];
        $myLeaveRequests = collect();

        if ($activeTab === 'my-leave' && $employee) {
            $myLeaveQuery = LeaveRequest::with(['leaveType', 'approver'])
                ->where('employee_id', $employee->id)
                ->orderByDesc('created_at');
            if (request()->filled('status')) {
                $myLeaveQuery->where('status', request('status'));
            }
            $myLeaveRequests = $myLeaveQuery->get();

            $credits = LeaveCredit::with('leaveType')
                ->where('employee_id', $employee->id)
                ->where('year', $currentYear)
                ->get();

            $myLeaveStats['pending'] = LeaveRequest::where('employee_id', $employee->id)
                ->where('status', 'pending')->count();

            foreach ($credits as $credit) {
                $key = strtolower($credit->leaveType->code ?? '');
                $myLeaveStats[$key . '_used']      = $credit->used_days;
                $myLeaveStats[$key . '_remaining']  = $credit->remaining_days;
                $myLeaveStats[$key . '_total']      = $credit->total_days;
            }
        }

        // ── LEAVE CALENDAR tab ────────────────────────────────────────
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

        return view('employee.employee_leave-management', compact(
            'activeTab', 'myLeaveStats', 'myLeaveRequests',
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
        $user     = Auth::user();
        $employee = Employee::where('user_id', $user->id)->firstOrFail();
        $leave    = LeaveRequest::with(['leaveType', 'approver'])
            ->where('id', $id)
            ->where('employee_id', $employee->id)
            ->firstOrFail();

        return response()->json([
            'id'               => $leave->id,
            'ref_no'           => $leave->ref_no,
            'leave_type'       => $leave->leaveType->name ?? '—',
            'start_date'       => $leave->start_date->format('m/d/Y'),
            'end_date'         => $leave->end_date->format('m/d/Y'),
            'total_days'       => $leave->total_days,
            'reason'           => $leave->reason,
            'status'           => $leave->status,
            'approver_name'    => $leave->approver
                ? trim($leave->approver->fname . ' ' . $leave->approver->lname)
                : '—',
            'rejection_reason' => $leave->rejection_reason,
            'filed_on'         => $leave->created_at->format('m/d/Y'),
        ]);
    }

    // ══════════════════════════════════════════════════════════════════════
    // REQUESTS & APPROVAL — added below, nothing above was changed
    // ══════════════════════════════════════════════════════════════════════

    public function pendingRequests(Request $request)
    {
        $user     = Auth::user();
        $employee = Employee::where('user_id', $user->id)->firstOrFail();

        $filterType = $request->get('type', 'all');

        $leaveQuery = LeaveRequest::with(['leaveType'])
            ->where('employee_id', $employee->id)
            ->whereIn('status', ['pending', 'supervisor_approved']);

        $otQuery = OvertimeRequest::where('employee_id', $employee->id)
            ->whereIn('status', ['pending', 'supervisor_approved']);

        $shiftQuery = ShiftChangeRequest::with(['currentShift', 'requestedShift'])
            ->where('employee_id', $employee->id)
            ->whereIn('status', ['pending', 'supervisor_approved']);

        $leaveCount    = $leaveQuery->count();
        $overtimeCount = $otQuery->count();
        $shiftCount    = $shiftQuery->count();
        $awaitingCount = $leaveCount + $overtimeCount + $shiftCount;

        $allRequests = collect();

        if ($filterType === 'all' || $filterType === 'leave') {
            foreach ($leaveQuery->get() as $r) {
                $allRequests->push((object)[
                    'type'          => 'leave',
                    'id'            => $r->id,
                    'ref_no'        => $r->ref_no,
                    'status'        => $r->status,
                    'leaveType'     => $r->leaveType,
                    'start_date'    => $r->start_date,
                    'end_date'      => $r->end_date,
                    'total_days'    => $r->total_days,
                    'reason'        => $r->reason,
                    'document_path' => $r->document_path,
                    'created_at'    => $r->created_at,
                    'credit'        => LeaveCredit::where('employee_id', $employee->id)
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

        $requests   = $allRequests->sortByDesc('created_at')->values();
        $leaveTypes = LeaveType::where('is_active', true)->orderBy('name')->get();
        $shiftTypes = \App\Models\Shift::where('is_active', true)->orderBy('name')->get();
        $departments = collect();

        return view('employee.employee_pending-requests', compact(
            'departments', 'leaveTypes', 'shiftTypes',
            'awaitingCount', 'leaveCount', 'shiftCount', 'overtimeCount',
            'requests', 'filterType'
        ));
    }

    public function approvedRequests(Request $request)
    {
        $user     = Auth::user();
        $employee = Employee::where('user_id', $user->id)->firstOrFail();

        $filterType   = $request->get('type', 'all');
        $filterStatus = $request->get('status', 'all');
        $search       = $request->get('search');

        $allRequests = collect();

        if ($filterType === 'all' || $filterType === 'leave') {
            $q = LeaveRequest::with(['leaveType', 'approver'])
                ->where('employee_id', $employee->id)
                ->whereIn('status', ['approved', 'rejected', 'cancelled', 'supervisor_approved']);
            if ($filterStatus !== 'all') $q->where('status', $filterStatus);
            foreach ($q->get() as $r) {
                $allRequests->push((object)[
                    'type'             => 'leave',
                    'id'               => $r->id,
                    'ref_no'           => $r->ref_no,
                    'status'           => $r->status,
                    'leaveType'        => $r->leaveType,
                    'start_date'       => $r->start_date,
                    'end_date'         => $r->end_date,
                    'total_days'       => $r->total_days,
                    'reason'           => $r->reason,
                    'rejection_reason' => $r->rejection_reason,
                    'hr_notes'         => $r->hr_notes,
                    'approver'         => $r->approver,
                    'approved_at'      => $r->approved_at,
                    'created_at'       => $r->created_at,
                ]);
            }
        }

        if ($filterType === 'all' || $filterType === 'overtime') {
            $q = OvertimeRequest::where('employee_id', $employee->id)
                ->whereIn('status', ['approved', 'rejected', 'supervisor_approved']);
            if ($filterStatus !== 'all') $q->where('status', $filterStatus);
            foreach ($q->get() as $r) {
                $allRequests->push((object)[
                    'type'             => 'overtime',
                    'id'               => $r->id,
                    'ref_no'           => $r->ref_no,
                    'status'           => $r->status,
                    'ot_date'          => $r->ot_date,
                    'ot_start_time'    => $r->ot_start_time,
                    'ot_end_time'      => $r->ot_end_time,
                    'requested_hours'  => $r->requested_hours,
                    'approved_hours'   => $r->approved_hours,
                    'reason'           => $r->reason,
                    'rejection_reason' => $r->rejection_reason,
                    'approved_by'      => $r->approved_by,
                    'approved_at'      => $r->approved_at,
                    'created_at'       => $r->created_at,
                ]);
            }
        }

        if ($filterType === 'all' || $filterType === 'shift') {
            $q = ShiftChangeRequest::with(['currentShift', 'requestedShift'])
                ->where('employee_id', $employee->id)
                ->whereIn('status', ['approved', 'rejected', 'supervisor_approved']);
            if ($filterStatus !== 'all') $q->where('status', $filterStatus);
            foreach ($q->get() as $r) {
                $allRequests->push((object)[
                    'type'             => 'shift',
                    'id'               => $r->id,
                    'ref_no'           => $r->ref_no,
                    'status'           => $r->status,
                    'current_shift'    => $r->currentShift,
                    'requested_shift'  => $r->requestedShift,
                    'effective_from'   => $r->effective_from,
                    'effective_until'  => $r->effective_until,
                    'reason'           => $r->reason,
                    'rejection_reason' => $r->rejection_reason,
                    'approved_by'      => $r->approved_by,
                    'approved_at'      => $r->approved_at,
                    'created_at'       => $r->created_at,
                ]);
            }
        }

        $requests      = $allRequests->sortByDesc('created_at')->values();
        $approvedCount = $allRequests->where('status', 'approved')->count();
        $rejectedCount = $allRequests->where('status', 'rejected')->count();
        $departments   = collect();

        return view('employee.employee_approved-requests', compact(
            'departments', 'requests',
            'approvedCount', 'rejectedCount',
            'filterType', 'filterStatus', 'search'
        ));
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

        $user     = Auth::user();
        $employee = Employee::where('user_id', $user->id)->firstOrFail();

        $otStart = Carbon::parse($request->ot_date . ' ' . $request->ot_start_time);
        $otEnd   = Carbon::parse($request->ot_date . ' ' . $request->ot_end_time);
        if ($otEnd->lte($otStart)) {
            $otEnd->addDay();
        }
        if ($otEnd->lte($otStart)) {
            return response()->json(['message' => 'OT end time must be after start time.'], 422);
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
        $employee = Employee::where('user_id', $user->id)->firstOrFail();

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

    public function cancelRequest(Request $request, $id)
    {
        $user     = Auth::user();
        $employee = Employee::where('user_id', $user->id)->firstOrFail();

        $leave = LeaveRequest::where('id', $id)->where('employee_id', $employee->id)->first();
        if ($leave) {
            return $this->cancelLeave($request, $id);
        }

        $ot = OvertimeRequest::where('id', $id)->where('employee_id', $employee->id)->first();
        if ($ot) {
            if (!in_array($ot->status, ['pending', 'supervisor_approved'])) {
                return response()->json(['message' => 'This request cannot be cancelled.'], 409);
            }
            $ot->update(['status' => 'cancelled']);
            return response()->json(['message' => 'Overtime request cancelled.']);
        }

        $scr = ShiftChangeRequest::where('id', $id)->where('employee_id', $employee->id)->first();
        if ($scr) {
            if (!in_array($scr->status, ['pending', 'supervisor_approved'])) {
                return response()->json(['message' => 'This request cannot be cancelled.'], 409);
            }
            $scr->update(['status' => 'cancelled']);
            return response()->json(['message' => 'Shift change request cancelled.']);
        }

        return response()->json(['message' => 'Request not found.'], 404);
    }
}
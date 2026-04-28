<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
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
use App\Traits\NotifiesReviewers;

class FinanceOfficerAttendanceController extends Controller
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

        return view('finance_officer.finance_attendance-reports', compact('employeeShift', 'availableShifts', 'stats', 'todayLog', 'month', 'year'));
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
            $newBreakMinutes   = (int) Carbon::parse($openSession->break_start)->diffInMinutes($now);
            $totalBreakMinutes = $openSession->break_minutes + $newBreakMinutes;
            $openSession->update(['break_end' => $now, 'break_minutes' => $totalBreakMinutes]);
            $existing->update(['break_minutes' => $existing->sessions()->sum('break_minutes')]);
            return response()->json([
                'message'       => 'Break ended, resumed work.',
                'break_end'     => $now->format('h:i A'),
                'break_minutes' => $totalBreakMinutes,
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

                // Night shift: if end_time is earlier than start_time, it crosses midnight
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
                    if ($clockOutStatus !== 'late') {
                        $clockOutStatus = 'undertime';
                    }
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

    // â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•
    // LEAVE MANAGEMENT â€” added below, nothing above was changed
    // â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•

    public function leaveManagement(Request $request)
    {
        $activeTab   = $request->get('tab', 'my-leave');
        $currentYear = (int) $request->get('year', now()->year);
        $leaveTypes  = LeaveType::where('is_active', true)->orderBy('name')->get();

        $user     = Auth::user();
        $employee = Employee::where('user_id', $user->id)->first();

        // â”€â”€ MY LEAVE tab â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
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

        // â”€â”€ LEAVE CALENDAR tab â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
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

        return view('finance_officer.finance_leave-management', compact(
            'activeTab', 'myLeaveStats', 'myLeaveRequests',
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

        $leaveType = LeaveType::find($request->leave_type_id);
        LeaveCredit::firstOrCreate(
            ['employee_id' => $employee->id, 'leave_type_id' => $request->leave_type_id, 'year' => $start->year],
            ['total_days' => $leaveType->days_entitled ?? 0, 'used_days' => 0, 'remaining_days' => $leaveType->days_entitled ?? 0]
        );

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

        $this->notifyReviewers(
            $employee,
            'New Leave Request',
            "{$employee->full_name} filed a leave request ({$refNo}) from {$request->start_date} to {$request->end_date}."
        );

        return response()->json(['message' => 'Leave request filed successfully.', 'ref_no' => $refNo]);
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
        $employee = Employee::where('user_id', $user->id)->first();
        if (!$employee) {
            return response()->json(['message' => 'Employee record not found.'], 422);
        }
        $leave    = LeaveRequest::with(['leaveType', 'approver'])
            ->where('id', $id)
            ->where('employee_id', $employee->id)
            ->firstOrFail();

        return response()->json([
            'id'               => $leave->id,
            'ref_no'           => $leave->ref_no,
            'leave_type'       => $leave->leaveType->name ?? 'â€”',
            'start_date'       => $leave->start_date->format('m/d/Y'),
            'end_date'         => $leave->end_date->format('m/d/Y'),
            'total_days'       => $leave->total_days,
            'reason'           => $leave->reason,
            'status'           => $leave->status,
            'approver_name'    => $leave->approver
                ? trim($leave->approver->fname . ' ' . $leave->approver->lname)
                : 'â€”',
            'rejection_reason' => $leave->rejection_reason,
            'filed_on'         => $leave->created_at->format('m/d/Y'),
        ]);
    }

    // â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•
    // REQUESTS & APPROVAL â€” added below, nothing above was changed
    // â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•

    public function pendingRequests(Request $request)
    {
        $user     = Auth::user();
        $employee = Employee::where('user_id', $user->id)->first();
        if (!$employee) {
            return response()->json(['message' => 'Employee record not found.'], 422);
        }

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

        return view('finance_officer.finance_pending-requests', compact(
            'departments', 'leaveTypes', 'shiftTypes',
            'awaitingCount', 'leaveCount', 'shiftCount', 'overtimeCount',
            'requests', 'filterType'
        ));
    }

    public function approvedRequests(Request $request)
    {
        $user     = Auth::user();
        $employee = Employee::where('user_id', $user->id)->first();

        if (!$employee) {
            return view('finance_officer.finance_approved-requests', [
                'departments'   => collect(),
                'requests'      => collect(),
                'approvedCount' => 0,
                'rejectedCount' => 0,
                'filterType'    => $request->get('type', 'all'),
                'filterStatus'  => $request->get('status', 'all'),
                'search'        => $request->get('search'),
            ]);
        }

        $filterType   = $request->get('type', 'all');
        $filterStatus = $request->get('status', 'all');
        $search       = $request->get('search');

        $allRequests = collect();

        if ($filterType === 'all' || $filterType === 'leave') {
            $q = LeaveRequest::with(['leaveType', 'approver'])
                ->where('employee_id', $employee->id)
                ->whereIn('status', ['approved', 'rejected', 'cancelled', 'supervisor_approved']);
            if ($search) $q->where('ref_no', 'like', "%$search%");
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
            if ($search) $q->where('ref_no', 'like', "%$search%");
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
            if ($search) $q->where('ref_no', 'like', "%$search%");
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

        $approvedCount = $allRequests->where('status', 'approved')->count();
        $rejectedCount = $allRequests->where('status', 'rejected')->count();
        $requests      = $filterStatus !== 'all'
            ? $allRequests->where('status', $filterStatus)->sortByDesc('created_at')->values()
            : $allRequests->sortByDesc('created_at')->values();
        $departments   = collect();

        return view('finance_officer.finance_approved-requests', compact(
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
        $employee = Employee::where('user_id', $user->id)->first();
        if (!$employee) {
            return response()->json(['message' => 'Employee record not found.'], 422);
        }

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

        $this->notifyReviewers(
            $employee,
            'New Overtime Request',
            "{$employee->full_name} filed an overtime request ({$refNo}) on {$request->ot_date} ({$requestedHours} hrs)."
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

        $this->notifyReviewers(
            $employee,
            'New Shift Change Request',
            "{$employee->full_name} filed a shift change request ({$refNo}) effective {$request->effective_from}."
        );

        return response()->json(['message' => 'Shift change request filed successfully.', 'ref_no' => $refNo]);
    }

    public function cancelRequest(Request $request, $id)
    {
        $user     = Auth::user();
        $employee = Employee::where('user_id', $user->id)->first();
        if (!$employee) {
            return response()->json(['message' => 'Employee record not found.'], 422);
        }

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

        return response()->json(['message' => 'Request not found.']);
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

            return view('finance_officer.finance-officer_employee_attendance', compact(
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

            return view('finance_officer.finance-officer_employee_attendance', compact(
                'currentView', 'departments', 'records',
                'presentCount', 'presentRate', 'lateCount', 'lateRate',
                'absentCount', 'absentRate', 'overtimeHours', 'overtimeEmployees',
                'undertimeHours', 'undertimeEmployees'
            ));
        }

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

        return view('finance_officer.finance-officer_employee_attendance', compact(
            'currentView', 'departments', 'monthlyRecords', 'selectedMonth',
            'presentCount', 'presentRate', 'lateCount', 'lateRate',
            'absentCount', 'absentRate', 'overtimeHours', 'overtimeEmployees',
            'undertimeHours', 'undertimeEmployees'
        ));
    }
}
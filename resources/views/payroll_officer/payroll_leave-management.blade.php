@php
    $currentRoute = request()->route()->getName();
    $activeTab    = request('tab', 'my-leave');

    // ── Sidebar vars ──────────────────────────────────────────────────────
    $sidebarUser     = auth()->user();
    $sidebarEmployee = $sidebarUser
        ? \App\Models\Employee::with('jobTitle')->where('user_id', $sidebarUser->id)->first()
        : null;
    $sidebarInitials = $sidebarEmployee
        ? strtoupper(substr($sidebarEmployee->fname, 0, 1) . substr($sidebarEmployee->lname, 0, 1))
        : ($sidebarUser ? strtoupper(substr($sidebarUser->email, 0, 2)) : 'U');
    $sidebarName = $sidebarEmployee
        ? trim($sidebarEmployee->fname . ' ' . $sidebarEmployee->lname)
        : ($sidebarUser?->email ?? 'User');
    $sidebarRole = $sidebarEmployee?->jobTitle?->title ?? ($sidebarUser?->role ?? '—');

    // ── Sidebar route groups ──────────────────────────────────────────────
    $attendanceRoutes = ['payroll_officer.attendance.reports', 'payroll_officer.attendance.shift', 'payroll_officer.attendance.leave', 'payroll_officer.leave.management'];
    $payrollRoutes    = ['payroll_officer.payroll', 'payroll_officer.payslips', 'payroll_officer.contributions'];
    $requestRoutes    = ['payroll_officer.requests.pending', 'payroll_officer.requests.approved'];

    // ── Calendar data ──────────────────────────────────────────────────────
    $calMonth = request('month') ? \Carbon\Carbon::parse(request('month').'-01') : \Carbon\Carbon::now()->startOfMonth();
    $calPrev  = $calMonth->copy()->subMonth()->format('Y-m');
    $calNext  = $calMonth->copy()->addMonth()->format('Y-m');
    $calStart = $calMonth->copy()->startOfMonth();
    $calEnd   = $calMonth->copy()->endOfMonth();
    $firstDow = $calStart->dayOfWeek; // 0=Sun
@endphp

<!DOCTYPE html>
<html lang="en">
<head>
    <link rel="icon" type="image/png" href="{{ asset('images/HRISLogo-Icon.png') }}">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Leave Management — MEDISOURCE</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        * { font-family: 'DM Sans', sans-serif; box-sizing: border-box; }
        [x-cloak] { display: none !important; }
        @@keyframes pulseDot {
            0%, 100% { opacity: 1; transform: scale(1); }
            50%       { opacity: .5; transform: scale(1.3); }
        }
        :root { --blue:#3b82f6; --blue-dark:#1d4ed8; --blue-light:#eff6ff; --muted:#6b7280; --border:#e5e7eb; }
        .nav-item    { transition: all 0.2s cubic-bezier(0.4,0,0.2,1); }
        .nav-item:hover { transform: translateX(2px); }
        .submenu-item { transition: all 0.18s ease; }
        .submenu-item:hover { transform: translateX(3px); }
        .chevron-icon { transition: transform 0.3s cubic-bezier(0.4,0,0.2,1); }
        .settings-icon { transition: transform 0.5s ease; }
        .nav-item:hover .settings-icon { transform: rotate(60deg); }
        .avatar-ring { box-shadow: 0 0 0 3px rgba(59,130,246,0.2); }

        .tab-nav { display:flex; border-bottom:2px solid var(--border); margin-bottom:24px; }
        .tab-btn { padding:10px 24px; font-size:14px; font-weight:500; color:var(--muted); border:none; background:none; cursor:pointer; font-family:inherit; border-bottom:3px solid transparent; margin-bottom:-2px; transition:color .15s,border-color .15s; text-decoration:none; display:inline-block; }
        .tab-btn:hover { color:#374151; }
        .tab-btn.active { color:var(--blue); border-bottom-color:var(--blue); font-weight:600; }

        .leave-cards { display:grid; grid-template-columns:repeat(4,1fr); gap:16px; margin-bottom:28px; }
        .leave-card { background:#fff; border:1px solid var(--border); border-radius:14px; padding:20px 22px; position:relative; box-shadow:0 1px 6px rgba(0,0,0,.04); }
        .leave-card-label { font-size:13px; color:var(--muted); font-weight:500; margin-bottom:6px; }
        .leave-card-value { font-size:34px; font-weight:800; color:#111827; line-height:1; }
        .leave-card-sub   { font-size:11.5px; color:#9ca3af; margin-top:6px; }
        .leave-badge { position:absolute; top:18px; right:18px; padding:3px 10px; border-radius:20px; font-size:12px; font-weight:700; }
        .lb-vl   { background:#dbeafe; color:#1d4ed8; }
        .lb-sl   { background:#fce7f3; color:#be185d; }
        .lb-lwop { background:#ffedd5; color:#c2410c; }

        .toolbar { display:flex; align-items:center; justify-content:space-between; margin-bottom:16px; gap:12px; flex-wrap:wrap; }
        .toolbar-title { font-size:16px; font-weight:700; color:#111827; }
        .toolbar-right { display:flex; align-items:center; gap:10px; flex-wrap:wrap; }
        .filter-select { appearance:none; background:#f9fafb; border:1px solid var(--border); border-radius:8px; padding:8px 28px 8px 12px; font-size:13px; color:#374151; cursor:pointer; outline:none; font-family:inherit; background-image:url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='10' height='6' fill='none'%3E%3Cpath d='M1 1l4 4 4-4' stroke='%236b7280' stroke-width='1.5' stroke-linecap='round'/%3E%3C/svg%3E"); background-repeat:no-repeat; background-position:right 10px center; }
        .btn-primary { display:inline-flex; align-items:center; gap:6px; padding:8px 18px; border-radius:8px; font-size:13px; font-weight:600; font-family:inherit; cursor:pointer; border:none; background:var(--blue); color:#fff; transition:background .15s; }
        .btn-primary:hover { background:var(--blue-dark); }
        .btn-outline-sm { display:inline-flex; align-items:center; padding:5px 14px; border-radius:7px; font-size:12px; font-weight:600; font-family:inherit; cursor:pointer; border:1.5px solid #bfdbfe; background:var(--blue-light); color:var(--blue-dark); }
        .btn-outline-sm:hover { background:#dbeafe; }

        .table-card { background:#fff; border-radius:14px; border:1px solid var(--border); overflow:hidden; box-shadow:0 1px 8px rgba(0,0,0,.04); }
        .data-table { width:100%; border-collapse:collapse; }
        .data-table thead tr { background:#f9fafb; }
        .data-table th { padding:11px 16px; font-size:11.5px; font-weight:600; color:var(--muted); text-align:left; border-bottom:1px solid var(--border); white-space:nowrap; letter-spacing:.4px; text-transform:uppercase; }
        .data-table td { padding:14px 16px; font-size:13px; color:#111827; border-bottom:1px solid #f3f4f6; vertical-align:middle; }
        .data-table tr:last-child td { border-bottom:none; }
        .data-table tbody tr:hover { background:#fafafa; }

        .lt-vl   { background:#dbeafe; color:#1d4ed8; padding:3px 11px; border-radius:20px; font-size:12px; font-weight:600; display:inline-block; }
        .lt-sl   { background:#fce7f3; color:#be185d; padding:3px 11px; border-radius:20px; font-size:12px; font-weight:600; display:inline-block; }
        .lt-lwop { background:#ffedd5; color:#c2410c; padding:3px 11px; border-radius:20px; font-size:12px; font-weight:600; display:inline-block; }
        .status-pending  { background:#ffedd5; color:#c2410c; padding:3px 12px; border-radius:20px; font-size:12px; font-weight:600; display:inline-block; }
        .status-approved { background:#dcfce7; color:#15803d; padding:3px 12px; border-radius:20px; font-size:12px; font-weight:600; display:inline-block; }
        .status-rejected { background:#fee2e2; color:#dc2626; padding:3px 12px; border-radius:20px; font-size:12px; font-weight:600; display:inline-block; }

        .cal-nav { display:flex; align-items:center; gap:10px; }
        .cal-month-label { font-size:20px; font-weight:800; color:#111827; }
        .cal-nav-btn { display:inline-flex; align-items:center; gap:5px; padding:6px 12px; border-radius:7px; font-size:12.5px; font-weight:500; font-family:inherit; cursor:pointer; border:1px solid var(--border); background:#fff; color:#374151; text-decoration:none; }
        .cal-nav-btn:hover { background:#f9fafb; }
        .cal-grid { width:100%; border-collapse:collapse; background:#fff; border-radius:14px; overflow:hidden; border:1px solid var(--border); }
        .cal-grid th { padding:10px; font-size:12px; font-weight:700; color:var(--muted); text-align:center; background:#f9fafb; border-bottom:1px solid var(--border); letter-spacing:.5px; text-transform:uppercase; }
        .cal-grid td { width:14.28%; height:100px; padding:8px; border:1px solid #f3f4f6; vertical-align:top; font-size:13px; font-weight:600; color:#374151; }
        .cal-grid td.other-month { background:#fafafa; color:#d1d5db; }
        .cal-event { display:inline-block; padding:2px 8px; border-radius:5px; font-size:11px; font-weight:600; margin-top:4px; width:100%; overflow:hidden; text-overflow:ellipsis; white-space:nowrap; }
        .cal-vl      { background:#dbeafe; color:#1d4ed8; }
        .cal-sl      { background:#fce7f3; color:#be185d; }
        .cal-lwop    { background:#ffedd5; color:#c2410c; }
        .cal-holiday { background:#fee2e2; color:#dc2626; }
        .cal-legend { display:flex; align-items:center; gap:20px; margin-top:16px; flex-wrap:wrap; }
        .cal-legend-item { display:flex; align-items:center; gap:6px; font-size:12px; color:#374151; }
        .cal-legend-dot  { width:12px; height:12px; border-radius:3px; flex-shrink:0; }

        .modal-overlay { position:fixed; inset:0; background:rgba(0,0,0,.4); display:flex; align-items:center; justify-content:center; z-index:999; }
        .modal-box { background:#fff; border-radius:16px; padding:28px 32px; width:520px; max-width:95vw; box-shadow:0 20px 60px rgba(0,0,0,.15); }
        .modal-title { font-size:17px; font-weight:800; color:#111827; }
        .form-label  { font-size:12px; font-weight:600; color:#374151; text-transform:uppercase; letter-spacing:.5px; margin-bottom:5px; display:block; }
        .form-input  { width:100%; border:1.5px solid var(--border); border-radius:8px; padding:9px 13px; font-size:13px; font-family:inherit; outline:none; color:#111827; transition:border-color .15s; }
        .form-input:focus { border-color:var(--blue); }
        .form-row    { display:grid; grid-template-columns:1fr 1fr; gap:14px; }
        .modal-actions { display:flex; justify-content:flex-end; gap:10px; margin-top:22px; }
        .btn-cancel { padding:8px 18px; border-radius:8px; font-size:13px; font-weight:600; font-family:inherit; cursor:pointer; border:1.5px solid var(--border); background:#fff; color:#374151; }
        .btn-save   { padding:8px 20px; border-radius:8px; font-size:13px; font-weight:600; font-family:inherit; cursor:pointer; border:none; background:var(--blue); color:#fff; }
        .close-btn  { width:32px; height:32px; border:2px solid #374151; border-radius:50%; display:flex; align-items:center; justify-content:center; background:none; cursor:pointer; flex-shrink:0; }
            @keyframes spin { from { transform: rotate(0deg); } to { transform: rotate(360deg); } }
    </style>
</head>
<body class="bg-gray-50">

{{-- ══════════ EMPLOYEE SIDEBAR ══════════ --}}
@include('payroll_officer.payroll_sidebar')

{{-- ══════════ MAIN CONTENT ══════════ --}}
<div x-data="{
         collapsed: localStorage.getItem('sidebarCollapsed') === 'true',
         showFileLeave: false,
         showLeaveDetails: false,
         selectedLeave: {},
         leaveError: '',
         cancelling: false,
         async openLeaveDetails(id) {
             this.leaveError = '';
             this.selectedLeave = {};
             const res = await fetch(`/employee/leave/${id}`, {
                 headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content }
             });
             const data = await res.json();
             if (res.ok) { this.selectedLeave = data; this.showLeaveDetails = true; }
             else { this.leaveError = data.message ?? 'Failed to load leave details.'; }
         },
         async cancelLeave(id) {
             if (!confirm('Are you sure you want to cancel this leave request?')) return;
             this.cancelling = true;
             const res = await fetch(`/employee/leave/${id}/cancel`, {
                 method: 'POST',
                 headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content }
             });
             this.cancelling = false;
             if (res.ok) { window.location.reload(); }
             else { this.leaveError = 'Failed to cancel leave request.'; }
         }
     }"
     x-init="window.addEventListener('sidebar-toggle', e => { collapsed = e.detail.collapsed })"
     :style="collapsed ? 'margin-left:5rem' : 'margin-left:16rem'"
     style="transition:margin-left 0.35s cubic-bezier(0.4,0,0.2,1); min-height:100vh;">

    <header class="bg-gradient-to-br from-blue-500 to-blue-700 sticky top-0 z-10 shadow-lg mt-4 mx-4 rounded-2xl overflow-hidden">
        <div class="flex items-center justify-between px-8 py-4">
            <h1 class="text-white font-bold text-xl">Leave Management</h1>
            <button class="w-9 h-9 rounded-full flex items-center justify-center hover:bg-white/20">
                <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/></svg>
            </button>
        </div>
    </header>

    <div style="padding:24px 32px;">

        {{-- Tab Nav --}}
        <div class="tab-nav">
            <a href="{{ route('payroll_officer.leave.management', ['tab' => 'my-leave']) }}"       class="tab-btn {{ $activeTab === 'my-leave'      ? 'active' : '' }}">My Leave</a>
            <a href="{{ route('payroll_officer.leave.management', ['tab' => 'leave-calendar']) }}" class="tab-btn {{ $activeTab === 'leave-calendar' ? 'active' : '' }}">Leave Calendar</a>
        </div>

        {{-- ══════════ MY LEAVE ══════════ --}}
        @if($activeTab === 'my-leave')

        <div class="leave-cards">
            @foreach($leaveTypes as $lt)
            @php $key = strtolower($lt->code); @endphp
            <div class="leave-card">
                <span class="leave-badge" style="background:#dbeafe;color:#1d4ed8;">{{ $lt->code }}</span>
                <div class="leave-card-label">{{ $lt->name }}</div>
                <div class="leave-card-value">{{ $myLeaveStats[$key.'_used'] ?? 0 }}</div>
                <div class="leave-card-sub">{{ $myLeaveStats[$key.'_remaining'] ?? 0 }} remaining</div>
            </div>
            @endforeach
            <div class="leave-card">
                <div class="leave-card-label">Pending Request</div>
                <div class="leave-card-value">{{ $myLeaveStats['pending'] ?? 0 }}</div>
                <div class="leave-card-sub">Waiting for Approval</div>
            </div>
        </div>

        <div class="toolbar">
            <div class="toolbar-title">My Leave Requests</div>
            <div class="toolbar-right">
                <select class="filter-select" onchange="window.location.href='?tab=my-leave&status='+this.value">
    <option value="">All Status</option>
    <option value="pending" {{ request('status')==='pending' ? 'selected' : '' }}>Pending</option>
    <option value="approved" {{ request('status')==='approved' ? 'selected' : '' }}>Approved</option>
    <option value="rejected" {{ request('status')==='rejected' ? 'selected' : '' }}>Rejected</option>
</select>
                <select class="filter-select" onchange="window.location.href='{{ route('payroll_officer.leave.management') }}?tab=my-leave&type='+this.value+'&status={{ request('status') }}'">
                    <option value="">All Types</option>
                    @foreach($leaveTypes as $lt)
                        <option value="{{ $lt->id }}" {{ request('type') == $lt->id ? 'selected' : '' }}>{{ $lt->name }}</option>
                    @endforeach
                </select>
                @canDo('Leave Management', 'create')
                <button class="btn-primary" @click="showFileLeave = true">
                    <svg style="width:14px;height:14px;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                    File Leave
                </button>
                @endcanDo
            </div>
        </div>

        <div class="table-card">
            <table class="data-table">
                <thead><tr>
                    <th>Ref #</th><th>Leave Type</th><th>Filed On</th><th>Date From</th><th>Date To</th><th>Days</th><th>Reason</th><th>Approver</th><th>Status</th><th></th>
                </tr></thead>
                <tbody>
                @forelse($myLeaveRequests as $req)
                <tr>
                    <td style="font-weight:600;color:#6b7280;">{{ $req->ref_no }}</td>
                    <td><span style="background:#dbeafe;color:#1d4ed8;padding:3px 11px;border-radius:20px;font-size:12px;font-weight:600;display:inline-block;">{{ $req->leaveType->name ?? '—' }}</span></td>
                    <td>{{ $req->created_at->format('m/d/Y') }}</td>
                    <td>{{ $req->start_date->format('m/d/Y') }}</td>
                    <td>{{ $req->end_date->format('m/d/Y') }}</td>
                    <td>{{ $req->total_days }}</td>
                    <td style="color:#6b7280;">{{ $req->reason }}</td>
                    <td>
                        <div style="font-weight:700;font-size:13px;">{{ $req->approver ? trim($req->approver->fname.' '.$req->approver->lname) : '—' }}</div>
                        <div style="font-size:11px;color:#9ca3af;">{{ $req->approver?->jobTitle?->title ?? '—' }}</div>
                    </td>
                    <td><span class="status-{{ $req->status }}">{{ ucfirst($req->status) }}</span></td>
                    <td><button class="btn-outline-sm" @click="openLeaveDetails({{ $req->id }})">View</button></td>
                </tr>
                @empty
                <tr>
                    <td colspan="10" style="text-align:center;padding:40px 16px;color:#9ca3af;font-size:13px;">
                        No leave requests found.
                    </td>
                </tr>
                @endforelse
                </tbody>
            </table>
        </div>

        {{-- ══════════ LEAVE CALENDAR ══════════ --}}
        @elseif($activeTab === 'leave-calendar')

        <div class="toolbar">
            <div class="cal-nav">
                <a href="{{ route('payroll_officer.leave.management', ['tab' => 'leave-calendar', 'month' => $calPrev]) }}" class="cal-nav-btn">
                    <svg style="width:12px;height:12px;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                    Previous
                </a>
                <span class="cal-month-label">{{ $calMonth->format('F') }}</span>
                <a href="{{ route('payroll_officer.leave.management', ['tab' => 'leave-calendar', 'month' => $calNext]) }}" class="cal-nav-btn">
                    Next
                    <svg style="width:12px;height:12px;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                </a>
            </div>
            <div class="toolbar-right">
                <form method="GET" action="{{ route('payroll_officer.leave.management') }}" id="calFilterForm" style="display:contents;">
                    <input type="hidden" name="tab" value="leave-calendar">
                    <input type="hidden" name="month" value="{{ $calMonth->format('Y-m') }}">
                    <select class="filter-select" name="leave_type_id" onchange="document.getElementById('calFilterForm').submit()">
                        <option value="">All Types</option>
                        @foreach($leaveTypes as $lt)
                            <option value="{{ $lt->id }}" {{ request('leave_type_id') == $lt->id ? 'selected' : '' }}>{{ $lt->name }}</option>
                        @endforeach
                    </select>
                </form>
            </div>
        </div>

        @php
            $eventsByDay = $calendarEvents->groupBy('day');
        @endphp

        <table class="cal-grid">
            <thead>
                <tr>
                    @foreach(['SUN','MON','TUE','WED','THURS','FRI','SAT'] as $dh)
                    <th>{{ $dh }}</th>
                    @endforeach
                </tr>
            </thead>
            <tbody>
            @php $day = 1; $startPad = $firstDow; $totalDays = $calEnd->day; @endphp
            @for($row = 0; $row < 6; $row++)
            @php if($day > $totalDays) break; @endphp
            <tr>
                @for($col = 0; $col < 7; $col++)
                @php $cellDay = ($row === 0 && $col < $startPad) ? null : ($day <= $totalDays ? $day++ : null); @endphp
                <td class="{{ $cellDay === null ? 'other-month' : '' }}">
                    @if($cellDay)
                    <div>{{ $cellDay }}</div>
                    @foreach($eventsByDay->get($cellDay, []) as $ev)
                    <div class="cal-event {{ $ev['type'] }}">{{ $ev['label'] }}</div>
                    @endforeach
                    @endif
                </td>
                @endfor
            </tr>
            @endfor
            </tbody>
        </table>

        <div class="cal-legend">
            @foreach($leaveTypes as $lt)
            <div class="cal-legend-item">
                <div class="cal-legend-dot" style="background:#dbeafe;"></div>
                {{ $lt->code }} – {{ $lt->name }}
            </div>
            @endforeach
        </div>

        @endif

        {{-- ══════════ MODALS ══════════ --}}

        {{-- File Leave Modal --}}
        <div x-show="showFileLeave" class="modal-overlay" x-cloak @click.self="showFileLeave = false"
             x-data="{
                 leaveTypeId: '', startDate: '', endDate: '', reason: '',
                 fileName: '', saving: false, errorMsg: '',
                 handleFile(e) { const f = e.target.files[0]; this.fileName = f ? f.name : ''; },
                 async submit() {
                     this.errorMsg = '';
                     if (!this.leaveTypeId || !this.startDate || !this.endDate || !this.reason) {
                         this.errorMsg = 'Please fill in all required fields.'; return;
                     }
                     this.saving = true;
                     const form = new FormData();
                     form.append('leave_type_id', this.leaveTypeId);
                     form.append('start_date', this.startDate);
                     form.append('end_date', this.endDate);
                     form.append('reason', this.reason);
                     const fileInput = document.getElementById('empLeaveDocInput');
                     if (fileInput.files[0]) form.append('document', fileInput.files[0]);
                     form.append('_token', document.querySelector('meta[name=csrf-token]').content);
                     const res = await fetch('{{ route('payroll_officer.leave.file') }}', { method: 'POST', body: form });
                     this.saving = false;
                     const data = await res.json();
                     if (res.ok) { window.location.reload(); }
                     else { this.errorMsg = data.message ?? 'Something went wrong.'; }
                 }
             }">
            <div class="modal-box">
                <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:22px;">
                    <div class="modal-title">Leave Request</div>
                    <button @click="showFileLeave = false" class="close-btn"><svg style="width:14px;height:14px;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"/></svg></button>
                </div>
                <template x-if="errorMsg">
                    <div style="background:#fee2e2;color:#b91c1c;border-radius:8px;padding:10px 14px;font-size:13px;margin-bottom:14px;" x-text="errorMsg"></div>
                </template>
                <div style="margin-bottom:16px;">
                    <label class="form-label">Leave Type</label>
                    <select class="form-input" x-model="leaveTypeId" style="appearance:none;width:100%;">
                        <option value="">Choose leave type</option>
                        @foreach($leaveTypes as $lt)
                        <option value="{{ $lt->id }}">{{ $lt->name }} ({{ $lt->code }})</option>
                        @endforeach
                    </select>
                </div>
                <div class="form-row" style="margin-bottom:16px;">
                    <div><label class="form-label">Start Date</label><input type="date" class="form-input" x-model="startDate"></div>
                    <div><label class="form-label">End Date</label><input type="date" class="form-input" x-model="endDate"></div>
                </div>
                <div style="margin-bottom:16px;">
                    <label class="form-label">Reason/Remarks</label>
                    <input type="text" class="form-input" x-model="reason" placeholder="Enter brief description of your leave reason">
                </div>
                <div style="margin-bottom:16px;">
                    <label class="form-label">Supporting Document</label>
                    <label style="display:flex;flex-direction:column;align-items:center;justify-content:center;gap:8px;border:2px dashed #d1d5db;border-radius:10px;padding:28px 20px;background:#f9fafb;cursor:pointer;">
                        <svg style="width:28px;height:28px;color:#9ca3af;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 13h6m-3-3v6m5 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                        <div style="font-size:13px;font-weight:600;color:#374151;" x-text="fileName || 'Choose a file to upload'"></div>
                        <div style="font-size:12px;color:#9ca3af;">PDF or DOCX, max 10MB</div>
                        <input id="empLeaveDocInput" type="file" accept=".pdf,.docx" style="display:none;" @change="handleFile($event)">
                    </label>
                </div>
                <div class="modal-actions">
                    <button class="btn-cancel" @click="showFileLeave = false">Cancel</button>
                    <button class="btn-save" @click="submit()" :disabled="saving"><span x-show="saving" style="display:inline-flex;align-items:center;gap:5px;"><svg style="width:13px;height:13px;animation:spin 0.8s linear infinite;flex-shrink:0;" viewBox="0 0 24 24" fill="none"><circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" style="opacity:.3"/><path fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg> Submitting…</span><span x-show="!saving">Submit</span></button>
                </div>
            </div>
        </div>

        {{-- Leave Details Modal --}}
        <div x-show="showLeaveDetails" class="modal-overlay" x-cloak @click.self="showLeaveDetails = false">
            <div class="modal-box">
                <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:22px;">
                    <div class="modal-title">Leave Details</div>
                    <button @click="showLeaveDetails = false" class="close-btn"><svg style="width:14px;height:14px;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"/></svg></button>
                </div>
                <template x-if="leaveError">
                    <div style="background:#fee2e2;color:#b91c1c;border-radius:8px;padding:10px 14px;font-size:13px;margin-bottom:14px;" x-text="leaveError"></div>
                </template>
                <div style="margin-bottom:16px;">
                    <label class="form-label">Ref #</label>
                    <div class="form-input" style="background:#f9fafb;color:#6b7280;cursor:default;" x-text="selectedLeave.ref_no || '—'"></div>
                </div>
                <div style="margin-bottom:16px;">
                    <label class="form-label">Leave Type</label>
                    <div class="form-input" style="background:#f9fafb;color:#6b7280;cursor:default;" x-text="selectedLeave.leave_type || '—'"></div>
                </div>
                <div class="form-row" style="margin-bottom:16px;">
                    <div>
                        <label class="form-label">Start Date</label>
                        <div class="form-input" style="background:#f9fafb;color:#6b7280;cursor:default;" x-text="selectedLeave.start_date || '—'"></div>
                    </div>
                    <div>
                        <label class="form-label">End Date</label>
                        <div class="form-input" style="background:#f9fafb;color:#6b7280;cursor:default;" x-text="selectedLeave.end_date || '—'"></div>
                    </div>
                </div>
                <div style="margin-bottom:16px;">
                    <label class="form-label">Total Days</label>
                    <div class="form-input" style="background:#f9fafb;color:#6b7280;cursor:default;" x-text="selectedLeave.total_days || '—'"></div>
                </div>
                <div style="margin-bottom:16px;">
                    <label class="form-label">Reason/Remarks</label>
                    <div class="form-input" style="background:#f9fafb;color:#6b7280;cursor:default;" x-text="selectedLeave.reason || '—'"></div>
                </div>
                <div style="margin-bottom:16px;">
                    <label class="form-label">Status</label>
                    <div class="form-input" style="background:#f9fafb;color:#6b7280;cursor:default;" x-text="selectedLeave.status ? selectedLeave.status.charAt(0).toUpperCase() + selectedLeave.status.slice(1) : '—'"></div>
                </div>
                <template x-if="selectedLeave.rejection_reason">
                    <div style="margin-bottom:16px;">
                        <label class="form-label">Rejection Reason</label>
                        <div class="form-input" style="background:#fef2f2;color:#dc2626;cursor:default;" x-text="selectedLeave.rejection_reason"></div>
                    </div>
                </template>
                <div class="modal-actions">
                    <template x-if="selectedLeave.status === 'pending'">
                        <button class="btn-cancel" style="background:#fef2f2;color:#dc2626;border-color:#fecaca;"
                            @click="cancelLeave(selectedLeave.id)" x-text="cancelling ? 'Cancelling…' : 'Cancel Request'"></button>
                    </template>
                    <button class="btn-save" @click="showLeaveDetails = false">Close</button>
                </div>
            </div>
        </div>

    </div>
</div>
</body>
</html>
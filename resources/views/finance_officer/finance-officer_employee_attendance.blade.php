@php
    $currentRoute = request()->route()->getName();
    $sidebarUser = auth()->user();
    $sidebarEmployee = $sidebarUser ? \App\Models\Employee::with('jobTitle')->where('user_id', $sidebarUser->id)->first() : null;
    $sidebarInitials = $sidebarEmployee
        ? strtoupper(substr($sidebarEmployee->fname, 0, 1) . substr($sidebarEmployee->lname, 0, 1))
        : ($sidebarUser ? strtoupper(substr($sidebarUser->email, 0, 2)) : 'U');
    $sidebarName = $sidebarEmployee
        ? trim($sidebarEmployee->fname . ' ' . $sidebarEmployee->lname)
        : ($sidebarUser?->email ?? 'User');
    $sidebarRole = $sidebarEmployee?->jobTitle?->title ?? ($sidebarUser?->role ?? '—');

    $attendanceRoutes = ['hr.attendance.reports', 'finance_officer.attendance.employee', 'hr.attendance.shift', 'hr.attendance.leave'];
    $payrollRoutes    = ['hr.payroll', 'hr.payslips', 'hr.contributions'];
    $requestRoutes    = ['hr.requests.pending', 'hr.requests.approved'];

    $currentView = request('view', 'daily');

    // Detail view: active when employee_id is set in monthly mode
    $viewingDetail  = $currentView === 'monthly' && request()->filled('employee_id');
    $detailEmployee = null;
    $empName        = '';
    $empInitials    = 'JD';
    $empDept        = '';
    $empTitle       = '';

    if ($viewingDetail) {
        $detailEmployee = \App\Models\Employee::with(['department','jobTitle'])
            ->find(request('employee_id'));
        if ($detailEmployee) {
            $empName     = trim(($detailEmployee->fname ?? '') . ' ' . ($detailEmployee->lname ?? ''));
            $empInitials = strtoupper(substr($detailEmployee->fname ?? 'J', 0, 1) . substr($detailEmployee->lname ?? 'D', 0, 1));
            $empDept     = $detailEmployee->department?->name ?? '—';
            $empTitle    = $detailEmployee->jobTitle?->title  ?? '—';
        }
    }

    $selectedMonth  = request('month', now()->format('Y-m'));
    $selectedPeriod = request('period', '1');
    $period1Start   = \Carbon\Carbon::parse($selectedMonth . '-01');
    $period1End     = \Carbon\Carbon::parse($selectedMonth . '-15');
    $period2Start   = \Carbon\Carbon::parse($selectedMonth . '-16');
    $period2End     = \Carbon\Carbon::parse($selectedMonth . '-01')->endOfMonth();

    $totals              = $totals ?? [];
    $totalWorkHours      = $totals['work_hours']      ?? '162h 00m';
    $totalOvertimeHours  = $totals['overtime_hours']  ?? '00h 00m';
    $totalUndertimeHours = $totals['undertime_hours'] ?? '00h 00m';
    $workingDays         = $totals['working_days']    ?? '15/15';
@endphp

<!DOCTYPE html>
<html lang="en">
<head>
    <link rel="icon" type="image/png" href="{{ asset('images/HRISLogo-Icon.png') }}">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Employee Attendance</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        * { font-family: 'DM Sans', sans-serif; }

        .nav-item { transition: background 0.15s, color 0.15s; }
        .chevron-icon { transition: transform 0.25s cubic-bezier(0.4,0,0.2,1); }
        [x-cloak] { display: none !important; }

        @keyframes pulseDot {
            0%, 100% { opacity: 1; transform: scale(1); }
            50%       { opacity: .5; transform: scale(1.3); }
        }

        :root {
            --blue: #3b82f6;
            --blue-dark: #1d4ed8;
            --blue-light: #eff6ff;
            --green: #22c55e;
            --green-bg: #f0fdf4;
            --orange: #f97316;
            --orange-bg: #fff7ed;
            --red: #ef4444;
            --red-bg: #fef2f2;
            --yellow: #eab308;
            --yellow-bg: #fefce8;
            --purple: #8b5cf6;
            --purple-bg: #f5f3ff;
            --muted: #6b7280;
            --border: #e5e7eb;
        }

        /* ── STAT CARDS ── */
        .stat-cards-grid {
            display: grid;
            grid-template-columns: repeat(5, 1fr);
            gap: 16px;
            margin-bottom: 28px;
        }

        .stat-card {
            border-radius: 14px;
            padding: 18px 20px;
            border: 1px solid transparent;
            position: relative;
            overflow: hidden;
            transition: box-shadow .2s, transform .2s;
        }
        .stat-card:hover { box-shadow: 0 6px 24px rgba(0,0,0,.09); transform: translateY(-2px); }

        .stat-card .sc-icon {
            width: 36px; height: 36px; border-radius: 10px;
            display: flex; align-items: center; justify-content: center;
            margin-bottom: 14px;
            background: rgba(255,255,255,0.55);
        }
        .stat-card .sc-icon svg { width: 18px; height: 18px; }

        .stat-card .sc-label {
            font-size: 11px; font-weight: 700; letter-spacing: .7px;
            text-transform: uppercase; margin-bottom: 4px;
        }
        .stat-card .sc-value {
            font-size: 28px; font-weight: 800; line-height: 1; margin-bottom: 4px;
        }
        .stat-card .sc-sub { font-size: 11.5px; font-weight: 500; opacity: .7; }

        /* Present — light green */
        .stat-card.present  { background: #dcfce7; border-color: #bbf7d0; }
        .stat-card.present  .sc-icon  { color: #16a34a; }
        .stat-card.present  .sc-label { color: #15803d; }
        .stat-card.present  .sc-value { color: #15803d; }
        .stat-card.present  .sc-sub   { color: #166534; }

        /* Late — light orange */
        .stat-card.late     { background: #ffedd5; border-color: #fed7aa; }
        .stat-card.late     .sc-icon  { color: #ea580c; }
        .stat-card.late     .sc-label { color: #c2410c; }
        .stat-card.late     .sc-value { color: #c2410c; }
        .stat-card.late     .sc-sub   { color: #9a3412; }

        /* Absent — light red/pink */
        .stat-card.absent   { background: #fee2e2; border-color: #fecaca; }
        .stat-card.absent   .sc-icon  { color: #dc2626; }
        .stat-card.absent   .sc-label { color: #b91c1c; }
        .stat-card.absent   .sc-value { color: #b91c1c; }
        .stat-card.absent   .sc-sub   { color: #991b1b; }

        /* Overtime — light blue */
        .stat-card.overtime { background: #dbeafe; border-color: #bfdbfe; }
        .stat-card.overtime .sc-icon  { color: #2563eb; }
        .stat-card.overtime .sc-label { color: #1d4ed8; }
        .stat-card.overtime .sc-value { color: #1d4ed8; }
        .stat-card.overtime .sc-sub   { color: #1e40af; }

        /* Undertime — light purple */
        .stat-card.undertime { background: #ede9fe; border-color: #ddd6fe; }
        .stat-card.undertime .sc-icon  { color: #7c3aed; }
        .stat-card.undertime .sc-label { color: #6d28d9; }
        .stat-card.undertime .sc-value { color: #6d28d9; }
        .stat-card.undertime .sc-sub   { color: #5b21b6; }

        /* ── TABLE CARD ── */
        .table-card {
            background: #fff;
            border-radius: 14px;
            border: 1px solid var(--border);
            overflow: hidden;
            box-shadow: 0 1px 8px rgba(0,0,0,.04);
        }

        .table-toolbar {
            display: flex; align-items: center; justify-content: space-between;
            padding: 18px 20px 14px; gap: 12px; flex-wrap: wrap;
            border-bottom: 1px solid var(--border);
        }
        .table-toolbar h2 { font-size: 16px; font-weight: 700; color: #111827; }
        .toolbar-right { display: flex; align-items: center; gap: 10px; flex-wrap: wrap; }

        .search-box {
            display: flex; align-items: center; gap: 7px;
            background: #f9fafb; border: 1px solid var(--border);
            border-radius: 8px; padding: 7px 12px;
        }
        .search-box svg { color: var(--muted); width: 14px; height: 14px; flex-shrink: 0; }
        .search-box input {
            border: none; background: transparent; outline: none;
            font-size: 13px; color: #111827; width: 140px;
        }
        .search-box input::placeholder { color: var(--muted); }

        .date-picker {
            display: flex; align-items: center; gap: 7px;
            background: #f9fafb; border: 1px solid var(--border);
            border-radius: 8px; padding: 7px 12px;
        }
        .date-picker svg { color: var(--muted); width: 14px; height: 14px; flex-shrink: 0; }
        .date-picker input {
            border: none; background: transparent; outline: none;
            font-size: 13px; color: #111827; cursor: pointer; font-family: inherit;
        }

        .dept-select {
            appearance: none; background: #f9fafb; border: 1px solid var(--border);
            border-radius: 8px; padding: 7px 28px 7px 12px; font-size: 13px;
            color: #111827; cursor: pointer; outline: none; font-family: inherit;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='10' height='6' fill='none'%3E%3Cpath d='M1 1l4 4 4-4' stroke='%236b7280' stroke-width='1.5' stroke-linecap='round'/%3E%3C/svg%3E");
            background-repeat: no-repeat; background-position: right 10px center;
        }

        .toggle-btns {
            display: flex; border: 1px solid var(--border);
            border-radius: 8px; overflow: hidden;
        }
        .toggle-btn {
            padding: 7px 18px; font-size: 13px; font-family: inherit;
            font-weight: 500; border: none; background: #fff; color: var(--muted);
            cursor: pointer; transition: background .15s, color .15s;
        }
        .toggle-btn.active { background: var(--blue); color: #fff; }

        /* ── DAILY TABLE ── */
        .att-table { width: 100%; border-collapse: collapse; }
        .att-table thead tr { background: #f9fafb; }
        .att-table th {
            padding: 11px 16px; font-size: 11.5px; font-weight: 600;
            color: var(--muted); text-align: left;
            border-bottom: 1px solid var(--border); white-space: nowrap;
            letter-spacing: .4px; text-transform: uppercase;
        }
        .att-table td {
            padding: 13px 16px; font-size: 13px; color: #111827;
            border-bottom: 1px solid #f3f4f6; vertical-align: middle;
        }
        .att-table tr:last-child td { border-bottom: none; }
        .att-table tbody tr:hover { background: #fafafa; }

        /* ── MONTHLY TABLE ── */
        .monthly-table { width: 100%; border-collapse: collapse; }
        .monthly-table thead tr { background: #f9fafb; }
        .monthly-table th {
            padding: 11px 14px; font-size: 11.5px; font-weight: 600;
            color: var(--muted); text-align: left;
            border-bottom: 1px solid var(--border); white-space: nowrap;
            letter-spacing: .4px; text-transform: uppercase;
        }
        .monthly-table td {
            padding: 13px 14px; font-size: 13px; color: #111827;
            border-bottom: 1px solid #f3f4f6; vertical-align: middle;
        }
        .monthly-table tr:last-child td { border-bottom: none; }
        .monthly-table tbody tr:hover { background: #fafafa; }

        .monthly-stat {
            display: inline-flex; align-items: center; justify-content: center;
            min-width: 36px; height: 28px; border-radius: 7px;
            font-size: 13px; font-weight: 700; padding: 0 8px;
        }
        .ms-present { background: var(--green-bg); color: #16a34a; }
        .ms-late    { background: var(--orange-bg); color: #ea580c; }
        .ms-absent  { background: var(--red-bg); color: #dc2626; }
        .ms-leave   { background: var(--yellow-bg); color: #a16207; }

        .hours-pill {
            display: inline-block; font-size: 12.5px; font-weight: 600;
            color: #374151; background: #f3f4f6; border-radius: 6px;
            padding: 3px 9px;
        }
        .hours-pill.ot { background: var(--blue-light); color: var(--blue-dark); }
        .hours-pill.ut { background: var(--purple-bg); color: #6d28d9; }

        .view-monthly-btn {
            display: inline-flex; align-items: center; gap: 5px;
            padding: 6px 13px; border-radius: 7px; font-size: 12px;
            font-weight: 600; font-family: inherit; cursor: pointer;
            border: 1px solid #dbeafe; background: var(--blue-light);
            color: var(--blue-dark); transition: background .15s, border-color .15s;
        }
        .view-monthly-btn:hover { background: #dbeafe; border-color: #93c5fd; }
        .view-monthly-btn svg { width: 12px; height: 12px; }

        /* ── EMPLOYEE CELL ── */
        .emp-avatar {
            width: 33px; height: 33px; border-radius: 50%;
            background: linear-gradient(135deg, #3b82f6, #1d4ed8);
            color: #fff; font-weight: 700; font-size: 12px;
            display: flex; align-items: center; justify-content: center;
            flex-shrink: 0;
        }
        .emp-name { font-weight: 600; font-size: 13px; color: #111827; line-height: 1.3; }
        .emp-dept { font-size: 11.5px; color: var(--muted); margin-top: 1px; }

        /* ── SHIFT BADGE ── */
        .shift-badge {
            display: inline-flex; flex-direction: column; align-items: center;
            background: #eff6ff; color: #1d4ed8; border-radius: 6px;
            padding: 4px 9px; font-size: 11px; font-weight: 700; line-height: 1.4;
        }
        .shift-badge .shift-sub { font-size: 10px; font-weight: 500; opacity: .7; }

        /* ── STATUS BADGE ── */
        .status-badge {
            display: inline-block; padding: 4px 12px;
            border-radius: 20px; font-size: 12px; font-weight: 600;
        }
        .badge-present  { background: var(--green-bg); color: #16a34a; }
        .badge-late     { background: var(--orange-bg); color: #ea580c; }
        .badge-absent   { background: var(--red-bg); color: #dc2626; }
        .badge-overtime { background: var(--blue-light); color: #1d4ed8; }
        .badge-undertime { background: var(--purple-bg); color: #6d28d9; }
        .badge-on_leave  { background: var(--yellow-bg); color: #a16207; }
        .badge-holiday   { background: #ede9fe; color: #7c3aed; }

        /* ── PAGINATION ── */
        .att-pagination {
            display: flex; align-items: center; justify-content: flex-end;
            gap: 5px; padding: 14px 20px; border-top: 1px solid var(--border);
        }
        .page-btn {
            width: 32px; height: 32px; border: 1px solid var(--border);
            border-radius: 7px; background: #fff; font-size: 13px;
            color: var(--muted); cursor: pointer;
            display: flex; align-items: center; justify-content: center;
            text-decoration: none; transition: background .15s, color .15s;
        }
        .page-btn:hover  { background: #eff6ff; color: var(--blue); }
        .page-btn.active { background: var(--blue); color: #fff; border-color: var(--blue); }

        /* ── DETAIL VIEW ── */
        .breadcrumb {
            display: flex; align-items: center; gap: 6px;
            font-size: 13px; color: var(--muted); margin-bottom: 20px;
        }
        .breadcrumb a {
            color: var(--muted); text-decoration: none;
            display: flex; align-items: center; gap: 5px;
            padding: 5px 11px; background: #fff;
            border: 1px solid var(--border); border-radius: 7px;
            font-weight: 500; transition: all .15s;
        }
        .breadcrumb a:hover { background: var(--blue-light); color: var(--blue); border-color: #bfdbfe; }
        .breadcrumb a svg  { width: 13px; height: 13px; }
        .breadcrumb .sep   { color: #d1d5db; }
        .breadcrumb .crumb-current { font-weight: 600; color: #374151; }

        .profile-card {
            background: #fff; border: 1px solid var(--border); border-radius: 14px;
            padding: 20px 24px; display: flex; align-items: center; gap: 18px;
            margin-bottom: 20px; box-shadow: 0 1px 6px rgba(0,0,0,.04);
        }
        .profile-avatar {
            width: 62px; height: 62px; border-radius: 50%;
            background: linear-gradient(135deg, #3b82f6, #1d4ed8);
            color: #fff; font-weight: 800; font-size: 20px;
            display: flex; align-items: center; justify-content: center; flex-shrink: 0;
        }
        .profile-name  { font-size: 18px; font-weight: 800; color: #111827; line-height: 1.2; }
        .profile-meta  { font-size: 13px; color: var(--muted); margin-top: 3px; font-weight: 500; }

        .period-btns { display: flex; border: 1px solid var(--border); border-radius: 8px; overflow: hidden; }
        .period-btn  {
            padding: 7px 16px; font-size: 13px; font-family: inherit; font-weight: 500;
            border: none; background: #fff; color: var(--muted); cursor: pointer;
            transition: background .15s, color .15s;
        }
        .period-btn.active { background: var(--blue); color: #fff; }

        .export-btn {
            display: inline-flex; align-items: center; gap: 6px;
            padding: 7px 16px; border-radius: 8px; font-size: 13px;
            font-weight: 600; font-family: inherit; cursor: pointer;
            border: none; background: var(--blue); color: #fff; text-decoration: none;
            transition: background .15s;
        }
        .export-btn:hover { background: var(--blue-dark); }
        .export-btn svg { width: 14px; height: 14px; }

        .detail-table { width: 100%; border-collapse: collapse; }
        .detail-table thead tr { background: #f9fafb; }
        .detail-table th {
            padding: 10px 16px; font-size: 11px; font-weight: 600;
            color: var(--muted); text-align: left; border-bottom: 1px solid var(--border);
            white-space: nowrap; letter-spacing: .5px; text-transform: uppercase;
        }
        .detail-table td {
            padding: 11px 16px; font-size: 13px; color: #374151;
            border-bottom: 1px solid #f3f4f6; vertical-align: middle;
        }
        .detail-table tr:last-child td { border-bottom: none; }
        .detail-table tbody tr:hover   { background: #fafafa; }
        .detail-table .rest-row        { background: #fafafa; }

        .hp-zero { display:inline-block; font-size:12px; font-weight:600; color:#9ca3af; background:#f9fafb; border-radius:6px; padding:3px 9px; }
        .hp-ot   { display:inline-block; font-size:12px; font-weight:600; color:#1d4ed8; background:#dbeafe; border-radius:6px; padding:3px 9px; }
        .hp-ut   { display:inline-block; font-size:12px; font-weight:600; color:#6d28d9; background:#ede9fe; border-radius:6px; padding:3px 9px; }
        .hp-norm { display:inline-block; font-size:12px; font-weight:600; color:#374151; background:#f3f4f6; border-radius:6px; padding:3px 9px; }

        .badge-rest    { background: #f3f4f6; color: #6b7280; }
        .badge-leave   { background: #fef9c3; color: #a16207; }
        .badge-holiday { background: #ede9fe; color: #7c3aed; }

        .totals-row {
            display: grid; grid-template-columns: repeat(4, 1fr);
            border-top: 2px solid var(--border);
        }
        .total-cell {
            padding: 16px 20px; border-right: 1px solid var(--border);
        }
        .total-cell:last-child { border-right: none; }
        .total-label { font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: .6px; color: var(--muted); margin-bottom: 4px; }
        .total-value { font-size: 18px; font-weight: 800; color: #111827; }
    </style>
</head>
<body class="bg-gray-50">

{{-- ═══════════ HR SIDEBAR ═══════════ --}}
@include('finance_officer.finance_sidebar')


{{-- ══════════ MAIN CONTENT ══════════ --}}
<div x-data="{ collapsed: localStorage.getItem('sidebarCollapsed') === 'true' }"
     x-init="window.addEventListener('sidebar-toggle', e => { collapsed = e.detail.collapsed })"
     :style="collapsed ? 'margin-left:5rem' : 'margin-left:16rem'"
     style="transition: margin-left 0.35s cubic-bezier(0.4, 0, 0.2, 1); min-height:100vh;">

   <!-- Blue Header -->
<header class="bg-gradient-to-br from-blue-500 to-blue-700 sticky top-0 z-10 shadow-lg mt-4 mx-4 rounded-2xl overflow-hidden">
    <div class="flex items-center justify-between px-8 py-[22px]">
        <div>
            <h1 class="text-white text-[22px] font-bold tracking-[0.3px] m-0">Employee Attendance</h1>
            <p class="text-white/65 text-[13px] mt-[3px] mb-0">Track and manage workforce attendance records</p>
        </div>
        <div class="flex items-center gap-2.5">
            <div class="w-9 h-9 bg-white/15 rounded-full flex items-center justify-center cursor-pointer">
                <svg class="w-[18px] h-[18px] text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>
                </svg>
            </div>
        </div>
    </div>
</header>

    <div style="padding:24px 32px;">

        @if($viewingDetail)
        {{-- ══════════ DETAIL VIEW ══════════ --}}

        {{-- Breadcrumb --}}
        <div class="breadcrumb">
            <a href="{{ route('finance_officer.attendance.employee', ['view' => 'monthly', 'month' => $selectedMonth]) }}">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                </svg>
                Back to Employee Attendance
            </a>
            <span class="sep">›</span>
            <span>Employee Attendance</span>
            <span class="sep">›</span>
            <span class="crumb-current">{{ $empName ?: 'Employee' }}</span>
        </div>

        {{-- Profile Card --}}
        <div class="profile-card">
            <div class="profile-avatar">{{ $empInitials }}</div>
            <div>
                <div class="profile-name">{{ $empName ?: 'Juan Dela Cruz' }}</div>
                <div class="profile-meta">{{ $empDept ?: 'IT/Information Technology Department' }}</div>
                <div class="profile-meta">{{ $empTitle ?: 'Senior Programmer' }}</div>
            </div>
        </div>

        {{-- Attendance Detail Card --}}
        <div class="table-card">
            <div class="table-toolbar">
                <h2>Attendance Records</h2>
                <div class="toolbar-right">
                    <form method="GET" action="{{ route('finance_officer.attendance.employee') }}" id="detailForm" style="display:contents;">
                        <input type="hidden" name="view" value="monthly">
                        <input type="hidden" name="employee_id" value="{{ request('employee_id') }}">

                        {{-- Period toggle --}}
                        <div class="period-btns">
                            <button type="button" class="period-btn {{ $selectedPeriod == '1' ? 'active' : '' }}" onclick="setPeriod('1')">Period 1</button>
                            <button type="button" class="period-btn {{ $selectedPeriod == '2' ? 'active' : '' }}" onclick="setPeriod('2')">Period 2</button>
                        </div>
                        <input type="hidden" name="period" id="periodInput" value="{{ $selectedPeriod }}">

                        {{-- Month picker --}}
                        <div class="date-picker">
                            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                            </svg>
                            <input type="month" name="month" value="{{ $selectedMonth }}"
                                onchange="document.getElementById('detailForm').submit()">
                        </div>
                    </form>

                    {{-- Export --}}
                    <button onclick="exportAttendancePdf()" class="export-btn" style="border:none;cursor:pointer;">
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                        </svg>
                        Export
                    </button>
                </div>
            </div>

            <div style="overflow-x:auto;">
                <table class="detail-table">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Work Setup</th>
                            <th>Shift</th>
                            <th>Schedule</th>
                            <th>Time In</th>
                            <th>Time Out</th>
                            <th>Overtime</th>
                            <th>Undertime</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @php
                            $start = $selectedPeriod == '1' ? $period1Start : $period2Start;
                            $end   = $selectedPeriod == '1' ? $period1End   : $period2End;
                        @endphp

                        @forelse($dailyRecords ?? [] as $rec)
                        @php
                            $st      = strtolower($rec->status ?? 'present');
                            $isWknd  = \Carbon\Carbon::parse($rec->date)->isWeekend();
                            $ot      = $rec->overtime_formatted ?? '00h 00m';
                            $ut      = $rec->undertime_formatted ?? '00h 00m';
                        @endphp
                        <tr class="{{ $isWknd ? 'rest-row' : '' }}">
                            <td style="font-weight:600; color:#111827; white-space:nowrap;">
                                {{ \Carbon\Carbon::parse($rec->date)->format('F j, Y') }}
                                @if($isWknd)<span style="font-size:10px; color:#9ca3af; font-weight:500; margin-left:4px;">(Rest Day)</span>@endif
                            </td>
                            <td style="color:var(--muted); font-size:12.5px;">{{ $rec->work_setup ?? 'WFH' }}</td>
                            <td style="color:var(--muted); font-size:12.5px;">{{ $rec->shift_type ?? 'Day Shift' }}</td>
                            <td style="color:var(--muted); font-size:12.5px;">{{ $rec->schedule ?? '7:00 AM – 4:00 PM' }}</td>
                            <td style="font-weight:700;">{{ $rec->time_in  ? \Carbon\Carbon::parse($rec->time_in)->format('g:i A')  : '—' }}</td>
                            <td style="font-weight:700;">{{ $rec->time_out ? \Carbon\Carbon::parse($rec->time_out)->format('g:i A') : '—' }}</td>
                            <td><span class="{{ $ot === '00h 00m' ? 'hp-zero' : 'hp-ot' }}">{{ $ot }}</span></td>
                            <td><span class="{{ $ut === '00h 00m' ? 'hp-zero' : 'hp-ut' }}">{{ $ut }}</span></td>
                            <td><span class="status-badge badge-{{ $st }}">{{ ucfirst($rec->status ?? 'Present') }}</span></td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="9" style="text-align:center; padding:40px 16px; color:#9ca3af; font-size:13px;">
                                No attendance records found for this period.
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{-- Totals Footer --}}
            <div class="totals-row">
                <div class="total-cell">
                    <div class="total-label">Total Work Hours</div>
                    <div class="total-value">{{ $totalWorkHours }}</div>
                </div>
                <div class="total-cell">
                    <div class="total-label">Total Overtime Hours</div>
                    <div class="total-value" style="color:#1d4ed8;">{{ $totalOvertimeHours }}</div>
                </div>
                <div class="total-cell">
                    <div class="total-label">Total Undertime Hours</div>
                    <div class="total-value" style="color:#6d28d9;">{{ $totalUndertimeHours }}</div>
                </div>
                <div class="total-cell">
                    <div class="total-label">Working Days</div>
                    <div class="total-value">{{ $workingDays }}</div>
                </div>
            </div>
        </div>

        @else
        {{-- ══════════ STAT CARDS + LIST VIEW ══════════ --}}

        <!-- Stat Cards -->
        <div class="stat-cards-grid">

            <!-- Present -->
            <div class="stat-card present">
                <div class="sc-icon">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
                <div class="sc-label">Present Today</div>
                <div class="sc-value">{{ $presentCount ?? 0 }}</div>
                <div class="sc-sub">{{ $presentRate ?? '0%' }} attendance rate</div>
            </div>

            <!-- Late -->
            <div class="stat-card late">
                <div class="sc-icon">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
                <div class="sc-label">Late</div>
                <div class="sc-value">{{ $lateCount ?? 0 }}</div>
                <div class="sc-sub">{{ $lateRate ?? '0%' }} of workforce</div>
            </div>

            <!-- Absent -->
            <div class="stat-card absent">
                <div class="sc-icon">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
                <div class="sc-label">Absent</div>
                <div class="sc-value">{{ $absentCount ?? 0 }}</div>
                <div class="sc-sub">{{ $absentRate ?? '0%' }} absent today</div>
            </div>

            <!-- Overtime -->
            <div class="stat-card overtime">
                <div class="sc-icon">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/>
                    </svg>
                </div>
                <div class="sc-label">Overtime</div>
                <div class="sc-value">{{ $overtimeHours ?? '0 hrs' }}</div>
                <div class="sc-sub">Across {{ $overtimeEmployees ?? 0 }} employees</div>
            </div>

            <!-- Undertime -->
            <div class="stat-card undertime">
                <div class="sc-icon">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 17h8m0 0V9m0 8l-8-8-4 4-6-6"/>
                    </svg>
                </div>
                <div class="sc-label">Undertime</div>
                <div class="sc-value">{{ $undertimeHours ?? '0 hrs' }}</div>
                <div class="sc-sub">Across {{ $undertimeEmployees ?? 0 }} employees</div>
            </div>

        </div>

        <!-- Table Card -->
        <div class="table-card">
            <div class="table-toolbar">
                <h2>Attendance Records</h2>
                <div class="toolbar-right">
                    <form method="GET" action="{{ route('finance_officer.attendance.employee') }}" id="filterForm" style="display:contents">

                        {{-- Search (hidden in monthly) --}}
                        @if($currentView === 'daily')
                        <div class="search-box">
                            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                            </svg>
                            <input type="text" name="search" placeholder="Search employee…"
                                value="{{ request('search') }}"
                                oninput="document.getElementById('filterForm').submit()">
                        </div>

                        <div class="date-picker">
                            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                            </svg>
                            <input type="date" name="date"
                                value="{{ request('date', now()->format('Y-m-d')) }}"
                                onchange="document.getElementById('filterForm').submit()">
                        </div>
                        @else
                        {{-- Month picker for monthly view --}}
                        <div class="date-picker">
                            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                            </svg>
                            <input type="month" name="month"
                                value="{{ request('month', now()->format('Y-m')) }}"
                                onchange="document.getElementById('filterForm').submit()">
                        </div>
                        @endif

                        <select name="department" class="dept-select"
                            onchange="document.getElementById('filterForm').submit()">
                            <option value="">All Departments</option>
                            @foreach($departments ?? [] as $dept)
                                <option value="{{ $dept->id }}" {{ request('department') == $dept->id ? 'selected' : '' }}>
                                    {{ $dept->name }}
                                </option>
                            @endforeach
                        </select>

                        <div class="toggle-btns">
                            <button type="button" class="toggle-btn {{ $currentView === 'daily' ? 'active' : '' }}"
                                onclick="setView('daily')">Daily</button>
                            <button type="button" class="toggle-btn {{ $currentView === 'monthly' ? 'active' : '' }}"
                                onclick="setView('monthly')">Monthly</button>
                        </div>
                        <input type="hidden" name="view" id="viewInput" value="{{ $currentView }}">

                    </form>
                </div>
            </div>

            <div style="overflow-x:auto;">

                {{-- ── DAILY VIEW TABLE ── --}}
                @if($currentView === 'daily')
                <table class="att-table">
                    <thead>
                        <tr>
                            <th>Employee</th>
                            <th>Department</th>
                            <th>Shift</th>
                            <th>Schedule</th>
                            <th>Time In</th>
                            <th>Time Out</th>
                            <th>Overtime</th>
                            <th>Undertime</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($records ?? [] as $rec)
                        <tr>
                            <td>
                                <div style="display:flex; align-items:center; gap:10px;">
                                    <div class="emp-avatar">
                                        {{ strtoupper(substr($rec->employee->fname ?? 'J', 0, 1) . substr($rec->employee->lname ?? 'D', 0, 1)) }}
                                    </div>
                                    <div>
                                        <div class="emp-name">{{ trim(($rec->employee->fname ?? '') . ' ' . ($rec->employee->lname ?? '')) ?: '—' }}</div>
                                    </div>
                                </div>
                            </td>
                            <td class="emp-dept">{{ $rec->employee->department->name ?? '—' }}</td>
                            <td>
                                <div class="shift-badge">
                                    {{ $rec->shift->name ?? '—' }}
                                    <span class="shift-sub">{{ $rec->work_setup ? strtoupper($rec->work_setup) : '—' }}</span>
                                </div>
                            </td>
                            <td style="color:#374151; font-size:13px;">{{ $rec->shift ? \Carbon\Carbon::parse($rec->shift->start_time)->format('g:i A') . ' – ' . \Carbon\Carbon::parse($rec->shift->end_time)->format('g:i A') : '—' }}</td>
                            <td style="font-weight:600;">{{ $rec->clock_in  ? \Carbon\Carbon::parse($rec->clock_in)->format('g:i A')  : '—' }}</td>
                            <td style="font-weight:600;">{{ $rec->clock_out ? \Carbon\Carbon::parse($rec->clock_out)->format('g:i A') : '—' }}</td>
                            <td><span class="hours-pill ot">{{ $rec->overtime_minutes > 0 ? floor($rec->overtime_minutes/60).'h '.str_pad($rec->overtime_minutes%60,2,'0',STR_PAD_LEFT).'m' : '00h 00m' }}</span></td>
                            <td><span class="hours-pill ut">{{ $rec->undertime_minutes > 0 ? floor($rec->undertime_minutes/60).'h '.str_pad($rec->undertime_minutes%60,2,'0',STR_PAD_LEFT).'m' : '00h 00m' }}</span></td>
                            <td>
                                @php $st = strtolower($rec->status ?? 'present'); @endphp
                                <span class="status-badge badge-{{ $st }}">{{ ucfirst($rec->status ?? 'Present') }}</span>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="9" style="text-align:center; padding:40px 16px; color:#9ca3af; font-size:13px;">
                                No attendance records found for this date.
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>

                {{-- ── MONTHLY VIEW TABLE ── --}}
                @else
                <table class="monthly-table">
                    <thead>
                        <tr>
                            <th>Employee</th>
                            <th>Department</th>
                            <th style="text-align:center;">Present</th>
                            <th style="text-align:center;">Late</th>
                            <th style="text-align:center;">Absent</th>
                            <th style="text-align:center;">Leave</th>
                            <th style="text-align:center;">Total Hours</th>
                            <th style="text-align:center;">OT Hours</th>
                            <th style="text-align:center;">UT Hours</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($monthlyRecords ?? [] as $rec)
                        <tr>
                            <td>
                                <div style="display:flex; align-items:center; gap:10px;">
                                    <div class="emp-avatar">
                                        {{ strtoupper(substr($rec->employee->fname ?? 'J', 0, 1) . substr($rec->employee->lname ?? 'D', 0, 1)) }}
                                    </div>
                                    <div><div class="emp-name">{{ trim(($rec->employee->fname ?? '') . ' ' . ($rec->employee->lname ?? '')) ?: '—' }}</div></div>
                                </div>
                            </td>
                            <td class="emp-dept">{{ $rec->employee->department->name ?? '—' }}</td>
                            <td style="text-align:center;"><span class="monthly-stat ms-present">{{ $rec->present_days ?? 0 }}</span></td>
                            <td style="text-align:center;"><span class="monthly-stat ms-late">{{ $rec->late_days ?? 0 }}</span></td>
                            <td style="text-align:center;"><span class="monthly-stat ms-absent">{{ $rec->absent_days ?? 0 }}</span></td>
                            <td style="text-align:center;"><span class="monthly-stat ms-leave">{{ $rec->leave_days ?? 0 }}</span></td>
                            <td style="text-align:center;"><span class="hours-pill">{{ $rec->total_hours ?? '0h' }}</span></td>
                            <td style="text-align:center;"><span class="hours-pill ot">{{ $rec->ot_hours ?? '0h' }}</span></td>
                            <td style="text-align:center;"><span class="hours-pill ut">{{ $rec->ut_hours ?? '0h' }}</span></td>
                            <td>
                                <a href="{{ route('finance_officer.attendance.employee', ['employee_id' => $rec->employee_id ?? '', 'view' => 'monthly', 'month' => request('month', now()->format('Y-m'))]) }}"
                                   class="view-monthly-btn">
                                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                    </svg>
                                    View Monthly
                                </a>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="10" style="text-align:center; padding:40px 16px; color:#9ca3af; font-size:13px;">
                                No attendance records found for this month.
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
                @endif

            </div>

            {{-- Pagination --}}
            @if(isset($records) && $records instanceof \Illuminate\Pagination\LengthAwarePaginator)
            <div class="att-pagination">
                @for($p = 1; $p <= $records->lastPage(); $p++)
                    <a href="{{ $records->url($p) }}" class="page-btn {{ $records->currentPage()==$p ? 'active' : '' }}">{{ $p }}</a>
                @endfor
            </div>
            @elseif(isset($monthlyRecords) && $monthlyRecords instanceof \Illuminate\Pagination\LengthAwarePaginator)
            <div class="att-pagination">
                @for($p = 1; $p <= $monthlyRecords->lastPage(); $p++)
                    <a href="{{ $monthlyRecords->url($p) }}" class="page-btn {{ $monthlyRecords->currentPage()==$p ? 'active' : '' }}">{{ $p }}</a>
                @endfor
            </div>
            @endif

        </div>
        @endif {{-- end @else (list view) --}}
    </div>
</div>

<script>
    function setView(v) {
        document.getElementById('viewInput').value = v;
        document.querySelectorAll('.toggle-btn').forEach(b => b.classList.remove('active'));
        event.target.classList.add('active');
        document.getElementById('filterForm').submit();
    }
    function setPeriod(p) {
        document.getElementById('periodInput').value = p;
        document.querySelectorAll('.period-btn').forEach(b => b.classList.remove('active'));
        event.target.classList.add('active');
        document.getElementById('detailForm').submit();
    }

    @if($viewingDetail)
    @php
        $pdfRows = $dailyRecords->map(fn($r) => [
            'date'     => \Carbon\Carbon::parse($r->date)->format('F j, Y'),
            'setup'    => $r->work_setup,
            'shift'    => $r->shift_type,
            'schedule' => $r->schedule,
            'clockIn'  => $r->time_in  ? \Carbon\Carbon::parse($r->time_in)->format('g:i A')  : '—',
            'clockOut' => $r->time_out ? \Carbon\Carbon::parse($r->time_out)->format('g:i A') : '—',
            'overtime' => $r->overtime_formatted,
            'status'   => ucfirst($r->status ?? '—'),
        ]);
        $pdfPeriod = \Carbon\Carbon::parse($selectedMonth . '-01')->format('F Y');
    @endphp
    const _attendanceRows = @json($pdfRows);
    const _empName  = @json($empName);
    const _period   = @json($pdfPeriod);

    function exportAttendancePdf() {
        const sBg  = s=>({'Present':'#dcfce7','Late':'#fef3c7','Absent':'#fee2e2','On Leave':'#ede9fe','Undertime':'#fef9c3','Overtime':'#dbeafe'}[s]||'#f1f5f9');
        const sClr = s=>({'Present':'#16a34a','Late':'#d97706','Absent':'#dc2626','On Leave':'#4f46e5','Undertime':'#b45309','Overtime':'#1d4ed8'}[s]||'#64748b');
        const tbody = _attendanceRows.map((r,i) => `<tr style="background:${i%2===0?'#fff':'#f8faff'}">
            <td style="padding:8px 10px;border-bottom:1px solid #f1f5f9;font-size:12px;white-space:nowrap;">${r.date}</td>
            <td style="padding:8px 10px;border-bottom:1px solid #f1f5f9;"><span style="background:${r.setup==='WFH'?'#dbeafe':'#f1f5f9'};color:${r.setup==='WFH'?'#1d4ed8':'#475569'};padding:2px 7px;border-radius:4px;font-size:11px;font-weight:600;">${r.setup}</span></td>
            <td style="padding:8px 10px;border-bottom:1px solid #f1f5f9;font-size:12px;">${r.shift}</td>
            <td style="padding:8px 10px;border-bottom:1px solid #f1f5f9;font-size:12px;white-space:nowrap;">${r.schedule}</td>
            <td style="padding:8px 10px;border-bottom:1px solid #f1f5f9;font-size:12px;font-weight:600;white-space:nowrap;">${r.clockIn}</td>
            <td style="padding:8px 10px;border-bottom:1px solid #f1f5f9;font-size:12px;white-space:nowrap;">${r.clockOut}</td>
            <td style="padding:8px 10px;border-bottom:1px solid #f1f5f9;font-size:12px;white-space:nowrap;">${r.overtime}</td>
            <td style="padding:8px 10px;border-bottom:1px solid #f1f5f9;"><span style="background:${sBg(r.status)};color:${sClr(r.status)};padding:2px 7px;border-radius:4px;font-size:11px;font-weight:600;">${r.status}</span></td>
        </tr>`).join('');
        const html = `<!DOCTYPE html><html><head><meta charset="UTF-8"><title>${_empName} – ${_period}</title><style>*{font-family:Arial,sans-serif;box-sizing:border-box;margin:0;padding:0;}body{padding:32px;color:#1e293b;}.hdr{border-bottom:2px solid #2563eb;padding-bottom:14px;margin-bottom:20px;}.co{font-size:20px;font-weight:700;color:#2563eb;}.sub{font-size:12px;color:#64748b;margin-top:2px;}.badge{display:inline-block;background:#eff6ff;color:#1d4ed8;border-radius:5px;padding:3px 12px;font-size:11px;font-weight:600;margin-top:6px;}table{width:100%;border-collapse:collapse;}thead tr{background:#1d4ed8;}thead th{padding:9px 10px;text-align:left;color:#fff;font-size:10.5px;font-weight:700;text-transform:uppercase;letter-spacing:.04em;white-space:nowrap;}.footer{margin-top:20px;font-size:10px;color:#94a3b8;text-align:center;border-top:1px solid #e2e8f0;padding-top:10px;}@media print{body{padding:16px;}@page{margin:.8cm;size:landscape;}}</style></head><body><div class="hdr"><div class="co">MediSource</div><div class="sub">Attendance Report — ${_empName}</div><div class="badge">${_period}</div></div><table><thead><tr><th>Date</th><th>Setup</th><th>Shift</th><th>Schedule</th><th>Clock In</th><th>Clock Out</th><th>Overtime</th><th>Status</th></tr></thead><tbody>${tbody}</tbody></table><div class="footer">System-generated attendance report — MediSource HRIS · Generated ${new Date().toLocaleDateString('en-US',{year:'numeric',month:'long',day:'numeric'})}</div></body></html>`;
        const w = window.open('', '_blank', 'width=1050,height=820,scrollbars=yes');
        if (!w) return;
        w.document.write(html);
        w.document.close();
        w.document.querySelectorAll('[x-show],[x-cloak]').forEach(el => el.remove());
        w.focus();
        setTimeout(() => w.print(), 400);
    }
    @endif
</script>
</body>
</html>
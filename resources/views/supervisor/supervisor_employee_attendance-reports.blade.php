@php
    $currentRoute = request()->route()->getName();

    $currentView = request('view', 'daily');

    // Detail view: active when employee_id is set in monthly mode
    $viewingDetail  = $currentView === 'monthly' && request()->filled('employee_id');
    $detailEmployee = null;
    $empName        = '';
    $empInitials    = 'JD';
    $empDept        = '';
    $empTitle       = '';

    if ($viewingDetail) {
        $detailEmployee = \App\Models\Employee::with(['department', 'jobTitle'])
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
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Employee Attendance</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        * { font-family: 'DM Sans', sans-serif; box-sizing: border-box; }
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
            border-radius: 14px; padding: 18px 20px;
            border: 1px solid transparent; position: relative;
            overflow: hidden; transition: box-shadow .2s, transform .2s;
        }
        .stat-card:hover { box-shadow: 0 6px 24px rgba(0,0,0,.09); transform: translateY(-2px); }
        .stat-card .sc-icon {
            width: 36px; height: 36px; border-radius: 10px;
            display: flex; align-items: center; justify-content: center;
            margin-bottom: 14px; background: rgba(255,255,255,0.55);
        }
        .stat-card .sc-icon svg { width: 18px; height: 18px; }
        .stat-card .sc-label { font-size: 11px; font-weight: 700; letter-spacing: .7px; text-transform: uppercase; margin-bottom: 4px; }
        .stat-card .sc-value { font-size: 28px; font-weight: 800; line-height: 1; margin-bottom: 4px; }
        .stat-card .sc-sub   { font-size: 11.5px; font-weight: 500; opacity: .7; }

        .stat-card.present  { background: #dcfce7; border-color: #bbf7d0; }
        .stat-card.present  .sc-icon  { color: #16a34a; }
        .stat-card.present  .sc-label { color: #15803d; }
        .stat-card.present  .sc-value { color: #15803d; }
        .stat-card.present  .sc-sub   { color: #166534; }

        .stat-card.late     { background: #ffedd5; border-color: #fed7aa; }
        .stat-card.late     .sc-icon  { color: #ea580c; }
        .stat-card.late     .sc-label { color: #c2410c; }
        .stat-card.late     .sc-value { color: #c2410c; }
        .stat-card.late     .sc-sub   { color: #9a3412; }

        .stat-card.absent   { background: #fee2e2; border-color: #fecaca; }
        .stat-card.absent   .sc-icon  { color: #dc2626; }
        .stat-card.absent   .sc-label { color: #b91c1c; }
        .stat-card.absent   .sc-value { color: #b91c1c; }
        .stat-card.absent   .sc-sub   { color: #991b1b; }

        .stat-card.overtime { background: #dbeafe; border-color: #bfdbfe; }
        .stat-card.overtime .sc-icon  { color: #2563eb; }
        .stat-card.overtime .sc-label { color: #1d4ed8; }
        .stat-card.overtime .sc-value { color: #1d4ed8; }
        .stat-card.overtime .sc-sub   { color: #1e40af; }

        .stat-card.undertime { background: #ede9fe; border-color: #ddd6fe; }
        .stat-card.undertime .sc-icon  { color: #7c3aed; }
        .stat-card.undertime .sc-label { color: #6d28d9; }
        .stat-card.undertime .sc-value { color: #6d28d9; }
        .stat-card.undertime .sc-sub   { color: #5b21b6; }

        /* ── TABLE CARD ── */
        .table-card {
            background: #fff; border-radius: 14px;
            border: 1px solid var(--border); overflow: hidden;
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

        .toggle-btns { display: flex; border: 1px solid var(--border); border-radius: 8px; overflow: hidden; }
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
            color: var(--muted); text-align: left; border-bottom: 1px solid var(--border);
            white-space: nowrap; letter-spacing: .4px; text-transform: uppercase;
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
            color: var(--muted); text-align: left; border-bottom: 1px solid var(--border);
            white-space: nowrap; letter-spacing: .4px; text-transform: uppercase;
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
        .ms-present { background: var(--green-bg);  color: #16a34a; }
        .ms-late    { background: var(--orange-bg); color: #ea580c; }
        .ms-absent  { background: var(--red-bg);    color: #dc2626; }
        .ms-leave   { background: var(--yellow-bg); color: #a16207; }

        .hours-pill {
            display: inline-block; font-size: 12.5px; font-weight: 600;
            color: #374151; background: #f3f4f6; border-radius: 6px; padding: 3px 9px;
        }
        .hours-pill.ot { background: var(--blue-light); color: var(--blue-dark); }
        .hours-pill.ut { background: var(--purple-bg);  color: #6d28d9; }

        .view-monthly-btn {
            display: inline-flex; align-items: center; gap: 5px;
            padding: 6px 13px; border-radius: 7px; font-size: 12px;
            font-weight: 600; font-family: inherit; cursor: pointer;
            border: 1px solid #dbeafe; background: var(--blue-light);
            color: var(--blue-dark); transition: background .15s, border-color .15s;
            text-decoration: none;
        }
        .view-monthly-btn:hover { background: #dbeafe; border-color: #93c5fd; }
        .view-monthly-btn svg { width: 12px; height: 12px; }

        /* ── EMPLOYEE CELL ── */
        .emp-avatar {
            width: 33px; height: 33px; border-radius: 50%;
            background: linear-gradient(135deg, #3b82f6, #1d4ed8);
            color: #fff; font-weight: 700; font-size: 12px;
            display: flex; align-items: center; justify-content: center; flex-shrink: 0;
        }
        .emp-name { font-weight: 600; font-size: 13px; color: #111827; line-height: 1.3; }
        .emp-dept { font-size: 11.5px; color: var(--muted); }

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
        .badge-present { background: #dcfce7; color: #16a34a; }
        .badge-late    { background: #ffedd5; color: #ea580c; }
        .badge-absent  { background: #fee2e2; color: #dc2626; }
        .badge-leave   { background: #fef9c3; color: #a16207; }
        .badge-rest    { background: #f3f4f6; color: #6b7280; }
        .badge-holiday { background: #ede9fe; color: #7c3aed; }

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
        .profile-name { font-size: 18px; font-weight: 800; color: #111827; line-height: 1.2; }
        .profile-meta { font-size: 13px; color: var(--muted); margin-top: 3px; font-weight: 500; }

        .period-btns { display: flex; border: 1px solid var(--border); border-radius: 8px; overflow: hidden; }
        .period-btn {
            padding: 7px 16px; font-size: 13px; font-family: inherit; font-weight: 500;
            border: none; background: #fff; color: var(--muted); cursor: pointer;
            transition: background .15s, color .15s;
        }
        .period-btn.active { background: var(--blue); color: #fff; }

        .export-btn {
            display: inline-flex; align-items: center; gap: 6px;
            padding: 7px 16px; border-radius: 8px; font-size: 13px;
            font-weight: 600; font-family: inherit; cursor: pointer;
            border: none; background: var(--blue); color: #fff;
            text-decoration: none; transition: background .15s;
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
        .detail-table tbody tr:hover { background: #fafafa; }
        .detail-table .rest-row { background: #fafafa; }

        .hp-zero { display:inline-block; font-size:12px; font-weight:600; color:#9ca3af; background:#f9fafb; border-radius:6px; padding:3px 9px; }
        .hp-ot   { display:inline-block; font-size:12px; font-weight:600; color:#1d4ed8; background:#dbeafe; border-radius:6px; padding:3px 9px; }
        .hp-ut   { display:inline-block; font-size:12px; font-weight:600; color:#6d28d9; background:#ede9fe; border-radius:6px; padding:3px 9px; }

        .totals-row {
            display: grid; grid-template-columns: repeat(4, 1fr);
            border-top: 2px solid var(--border);
        }
        .total-cell { padding: 16px 20px; border-right: 1px solid var(--border); }
        .total-cell:last-child { border-right: none; }
        .total-label { font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: .6px; color: var(--muted); margin-bottom: 4px; }
        .total-value { font-size: 18px; font-weight: 800; color: #111827; }
    </style>
</head>
<body class="bg-gray-50">


{{-- ══════════ SIDEBAR ══════════ --}}
@php
    $sidebarUser = auth()->user();
    $sidebarEmployee = $sidebarUser ? \App\Models\Employee::with('jobTitle')->where('user_id', $sidebarUser->id)->first() : null;
    $sidebarInitials = $sidebarEmployee
        ? strtoupper(substr($sidebarEmployee->fname, 0, 1) . substr($sidebarEmployee->lname, 0, 1))
        : ($sidebarUser ? strtoupper(substr($sidebarUser->email, 0, 2)) : 'U');
    $sidebarName = $sidebarEmployee
        ? trim($sidebarEmployee->fname . ' ' . $sidebarEmployee->lname)
        : ($sidebarUser?->email ?? 'User');
    $sidebarRole = $sidebarEmployee?->jobTitle?->title ?? ($sidebarUser?->role ?? '—');

    $attendanceRoutes = ['supervisor.attendance.reports', 'supervisor.attendance.employee', 'supervisor.attendance.shift', 'supervisor.attendance.leave'];
    $payrollRoutes    = ['supervisor.payroll', 'supervisor.payslips', 'supervisor.contributions'];
    $requestRoutes    = ['supervisor.requests.pending', 'supervisor.requests.approved'];
@endphp

<aside
    class="bg-white border-r border-gray-200 h-screen fixed left-0 top-0 overflow-y-auto z-50 flex flex-col"
    x-data="{
        sidebarCollapsed: localStorage.getItem('sidebarCollapsed') === 'true',
        employeesOpen: false,
        attendanceOpen: {{ in_array($currentRoute, $attendanceRoutes) ? 'true' : 'false' }},
        payrollOpen: {{ in_array($currentRoute, $payrollRoutes) ? 'true' : 'false' }},
        requestsOpen: {{ in_array($currentRoute, $requestRoutes) ? 'true' : 'false' }}
    }"
    x-init="$watch('sidebarCollapsed', value => localStorage.setItem('sidebarCollapsed', value))"
    :class="sidebarCollapsed ? 'w-20' : 'w-64'"
    style="transition: width 0.35s cubic-bezier(0.4, 0, 0.2, 1); box-shadow: 2px 0 20px rgba(0,0,0,0.06);">

    <!-- Logo -->
    <div class="px-6 py-5 border-b border-gray-100">
        <div class="flex items-center space-x-3" :class="sidebarCollapsed ? 'justify-center' : ''">
            <div class="w-9 h-9 border-2 border-gray-800 flex items-center justify-center flex-shrink-0" style="border-radius:6px;">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                </svg>
            </div>
            <h1 x-show="!sidebarCollapsed"
                x-transition:enter="transition ease-out duration-300 delay-100"
                x-transition:enter-start="opacity-0 -translate-x-4"
                x-transition:enter-end="opacity-100 translate-x-0"
                x-transition:leave="transition ease-in duration-100"
                x-transition:leave-start="opacity-100"
                x-transition:leave-end="opacity-0"
                class="font-bold text-gray-900 text-lg tracking-widest whitespace-nowrap">MEDISOURCE</h1>
        </div>
    </div>

    <!-- User Profile -->
    <div class="px-4 py-4 border-b border-gray-100" :class="sidebarCollapsed ? 'flex justify-center' : 'flex items-center space-x-3'">
        <div class="w-11 h-11 rounded-full flex items-center justify-center text-white font-bold flex-shrink-0 text-sm"
             style="background: linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%); box-shadow: 0 0 0 3px rgba(59,130,246,.25);">
            {{ $sidebarInitials }}
        </div>
        <div x-show="!sidebarCollapsed"
            x-transition:enter="transition ease-out duration-300 delay-100"
            x-transition:enter-start="opacity-0 -translate-x-3"
            x-transition:enter-end="opacity-100 translate-x-0"
            x-transition:leave="transition ease-in duration-100"
            x-transition:leave-start="opacity-100"
            x-transition:leave-end="opacity-0"
            class="overflow-hidden">
            <p class="font-semibold text-gray-800 text-sm leading-tight truncate">{{ $sidebarName }}</p>
            <p class="text-xs mt-0.5 font-semibold" style="color:#3b82f6;">{{ $sidebarRole }}</p>
        </div>
    </div>

    <!-- Navigation -->
    <nav class="p-3 space-y-0.5 flex-1 overflow-y-auto">
        <p x-show="!sidebarCollapsed"
           class="text-xs text-gray-400 font-semibold px-3 py-2 uppercase tracking-widest">Main Menu</p>

        <!-- Dashboard -->
        <a href="{{ route('supervisor.dashboard') }}"
            class="nav-item flex items-center space-x-3 px-3 py-2.5 rounded-lg group
                {{ $currentRoute === 'supervisor.dashboard' ? 'text-white' : 'text-gray-600 hover:bg-gray-50' }}"
            style="{{ $currentRoute === 'supervisor.dashboard' ? 'background:#3b82f6;' : '' }}">
            <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"/>
            </svg>
            <span x-show="!sidebarCollapsed" class="text-sm font-medium whitespace-nowrap">Dashboard</span>
        </a>

        <!-- Employees -->
        <div>
            <button @click="employeesOpen = !employeesOpen"
                class="nav-item w-full flex items-center justify-between px-3 py-2.5 rounded-lg text-gray-600 hover:bg-gray-50">
                <div class="flex items-center space-x-3">
                    <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/>
                    </svg>
                    <span x-show="!sidebarCollapsed" class="text-sm font-medium whitespace-nowrap">Employees</span>
                </div>
                <svg x-show="!sidebarCollapsed" class="w-4 h-4 chevron-icon" :class="{'rotate-180': employeesOpen}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                </svg>
            </button>
            <div x-show="employeesOpen && !sidebarCollapsed"
                 x-transition:enter="transition ease-out duration-250"
                 x-transition:enter-start="opacity-0 -translate-y-3 scale-y-95"
                 x-transition:enter-end="opacity-100 translate-y-0 scale-y-100"
                 x-transition:leave="transition ease-in duration-200"
                 x-transition:leave-start="opacity-100 translate-y-0 scale-y-100"
                 x-transition:leave-end="opacity-0 -translate-y-3 scale-y-95"
                 class="ml-8 mt-1 space-y-0.5 origin-top">
                <a href="#" class="submenu-item block px-3 py-2 text-sm text-gray-500 hover:text-gray-800 hover:bg-gray-50 rounded-lg">Employee Directory</a>
                <a href="#" class="submenu-item block px-3 py-2 text-sm text-gray-500 hover:text-gray-800 hover:bg-gray-50 rounded-lg">Employee Profile</a>
                <a href="#" class="submenu-item block px-3 py-2 text-sm text-gray-500 hover:text-gray-800 hover:bg-gray-50 rounded-lg">Employee Documents</a>
            </div>
        </div>

        <!-- Time & Attendance -->
        <div>
            <button @click="attendanceOpen = !attendanceOpen"
                class="nav-item w-full flex items-center justify-between px-3 py-2.5 rounded-lg
                    {{ in_array($currentRoute, $attendanceRoutes) ? 'text-blue-600' : 'text-gray-600 hover:bg-gray-50' }}">
                <div class="flex items-center space-x-3">
                    <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <span x-show="!sidebarCollapsed" class="text-sm font-medium whitespace-nowrap">Time & Attendance</span>
                </div>
                <svg x-show="!sidebarCollapsed" class="w-4 h-4 chevron-icon" :class="{'rotate-180': attendanceOpen}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                </svg>
            </button>
            <div x-show="attendanceOpen && !sidebarCollapsed"
                 x-transition:enter="transition ease-out duration-250"
                 x-transition:enter-start="opacity-0 -translate-y-3 scale-y-95"
                 x-transition:enter-end="opacity-100 translate-y-0 scale-y-100"
                 x-transition:leave="transition ease-in duration-200"
                 x-transition:leave-start="opacity-100 translate-y-0 scale-y-100"
                 x-transition:leave-end="opacity-0 -translate-y-3 scale-y-95"
                 class="ml-8 mt-1 space-y-0.5 origin-top">
                <a href="{{ route('supervisor.attendance.reports') }}"
                   class="submenu-item flex items-center px-3 py-2 text-sm rounded-lg
                       {{ $currentRoute === 'supervisor.attendance.reports' ? 'text-blue-600 font-semibold bg-blue-50' : 'text-gray-500 hover:text-gray-800 hover:bg-gray-50' }}">
                    @if($currentRoute === 'supervisor.attendance.reports')
                        <span class="w-2 h-2 rounded-full mr-2.5 flex-shrink-0" style="background:#3b82f6; animation:pulseDot 2s ease-in-out infinite;"></span>
                    @endif
                    My Attendance
                </a>
                <a href="{{ route('supervisor.attendance.employee') }}"
                   class="submenu-item block px-3 py-2 text-sm rounded-lg
                       {{ $currentRoute === 'supervisor.attendance.employee' ? 'text-blue-600 font-semibold bg-blue-50' : 'text-gray-500 hover:text-gray-800 hover:bg-gray-50' }}">
                    Employee Attendance
                </a>
                <a href="#" class="submenu-item block px-3 py-2 text-sm text-gray-500 hover:text-gray-800 hover:bg-gray-50 rounded-lg">Shift Scheduling</a>
                <a href="#" class="submenu-item block px-3 py-2 text-sm text-gray-500 hover:text-gray-800 hover:bg-gray-50 rounded-lg">Leave Management</a>
            </div>
        </div>

        <!-- Payroll -->
        <div>
            <button @click="payrollOpen = !payrollOpen"
                class="nav-item w-full flex items-center justify-between px-3 py-2.5 rounded-lg text-gray-600 hover:bg-gray-50">
                <div class="flex items-center space-x-3">
                    <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/>
                    </svg>
                    <span x-show="!sidebarCollapsed" class="text-sm font-medium whitespace-nowrap">Payroll</span>
                </div>
                <svg x-show="!sidebarCollapsed" class="w-4 h-4 chevron-icon" :class="{'rotate-180': payrollOpen}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                </svg>
            </button>
            <div x-show="payrollOpen && !sidebarCollapsed"
                 x-transition:enter="transition ease-out duration-250"
                 x-transition:enter-start="opacity-0 -translate-y-3 scale-y-95"
                 x-transition:enter-end="opacity-100 translate-y-0 scale-y-100"
                 x-transition:leave="transition ease-in duration-200"
                 x-transition:leave-start="opacity-100 translate-y-0 scale-y-100"
                 x-transition:leave-end="opacity-0 -translate-y-3 scale-y-95"
                 class="ml-8 mt-1 space-y-0.5 origin-top">
                <a href="#" class="submenu-item block px-3 py-2 text-sm text-gray-500 hover:text-gray-800 hover:bg-gray-50 rounded-lg">Payroll</a>
                <a href="#" class="submenu-item block px-3 py-2 text-sm text-gray-500 hover:text-gray-800 hover:bg-gray-50 rounded-lg">Payslips</a>
                <a href="#" class="submenu-item block px-3 py-2 text-sm text-gray-500 hover:text-gray-800 hover:bg-gray-50 rounded-lg">Govt. Contributions</a>
            </div>
        </div>

        <!-- Requests & Approval -->
        <div>
            <button @click="requestsOpen = !requestsOpen"
                class="nav-item w-full flex items-center justify-between px-3 py-2.5 rounded-lg text-gray-600 hover:bg-gray-50">
                <div class="flex items-center space-x-3">
                    <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                    </svg>
                    <span x-show="!sidebarCollapsed" class="text-sm font-medium whitespace-nowrap">Requests & Approval</span>
                </div>
                <svg x-show="!sidebarCollapsed" class="w-4 h-4 chevron-icon" :class="{'rotate-180': requestsOpen}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                </svg>
            </button>
            <div x-show="requestsOpen && !sidebarCollapsed"
                 x-transition:enter="transition ease-out duration-250"
                 x-transition:enter-start="opacity-0 -translate-y-3 scale-y-95"
                 x-transition:enter-end="opacity-100 translate-y-0 scale-y-100"
                 x-transition:leave="transition ease-in duration-200"
                 x-transition:leave-start="opacity-100 translate-y-0 scale-y-100"
                 x-transition:leave-end="opacity-0 -translate-y-3 scale-y-95"
                 class="ml-8 mt-1 space-y-0.5 origin-top">
                <a href="#" class="submenu-item block px-3 py-2 text-sm text-gray-500 hover:text-gray-800 hover:bg-gray-50 rounded-lg">Pending Requests</a>
                <a href="#" class="submenu-item block px-3 py-2 text-sm text-gray-500 hover:text-gray-800 hover:bg-gray-50 rounded-lg">Approved Logs</a>
            </div>
        </div>

        <!-- Others -->
        <div class="pt-3 mt-2 border-t border-gray-100">
            <p x-show="!sidebarCollapsed"
               class="text-xs text-gray-400 font-semibold px-3 py-2 uppercase tracking-widest">Others</p>

            <a href="#" class="nav-item flex items-center space-x-3 px-3 py-2.5 rounded-lg text-gray-600 hover:bg-gray-50 group">
                <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                </svg>
                <span x-show="!sidebarCollapsed" class="text-sm font-medium whitespace-nowrap">Settings</span>
            </a>

            <a href="{{ route('logout') }}" onclick="event.preventDefault(); document.getElementById('supervisor-logout-form').submit();"
                class="nav-item flex items-center space-x-3 px-3 py-2.5 rounded-lg text-gray-600 hover:bg-red-50 hover:text-red-500">
                <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
                </svg>
                <span x-show="!sidebarCollapsed" class="text-sm font-medium whitespace-nowrap">Logout</span>
            </a>
            <form id="supervisor-logout-form" action="{{ route('logout') }}" method="POST" class="hidden">@csrf</form>
        </div>
    </nav>

    <!-- Collapse Button -->
    <button @click="sidebarCollapsed = !sidebarCollapsed"
        class="m-3 w-8 h-8 bg-gray-100 rounded-lg flex items-center justify-center text-gray-500 hover:bg-gray-200 self-end"
        style="transition: background .15s;">
        <svg class="w-4 h-4 chevron-icon" :class="{'rotate-180': sidebarCollapsed}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 19l-7-7 7-7m8 14l-7-7 7-7"/>
        </svg>
    </button>
</aside>

{{-- ══════════ MAIN CONTENT ══════════ --}}
<div x-data="{ collapsed: localStorage.getItem('sidebarCollapsed') === 'true' }"
     x-init="window.addEventListener('storage', e => { if(e.key==='sidebarCollapsed') collapsed = e.newValue==='true' })"
     :style="collapsed ? 'margin-left:5rem' : 'margin-left:16rem'"
     style="transition: margin-left 0.35s cubic-bezier(0.4, 0, 0.2, 1); min-height:100vh;">

    <!-- Blue Header -->
    <div style="background: linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%); padding: 22px 32px; display:flex; align-items:center; justify-content:space-between;">
        <div>
            <h1 style="color:#fff; font-size:22px; font-weight:700; letter-spacing:.3px; margin:0;">Employee Attendance</h1>
            <p style="color:rgba(255,255,255,.65); font-size:13px; margin:3px 0 0;">Track and manage workforce attendance records</p>
        </div>
        <div style="display:flex; align-items:center; gap:10px;">
            <div style="font-size:12.5px; color:rgba(255,255,255,.8); background:rgba(255,255,255,.15); border-radius:8px; padding:6px 13px; font-weight:500;">
                {{ now()->format('l, F j, Y') }}
            </div>
            <div style="width:36px;height:36px;background:rgba(255,255,255,0.15);border-radius:50%;display:flex;align-items:center;justify-content:center;cursor:pointer;">
                <svg style="width:18px;height:18px;color:#fff;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>
                </svg>
            </div>
        </div>
    </div>

    <div style="padding:24px 32px;">

        @if($viewingDetail)
        {{-- ══════════ EMPLOYEE DETAIL VIEW ══════════ --}}

        <div class="breadcrumb">
            <a href="{{ route('supervisor.attendance.employee', ['view' => 'monthly', 'month' => $selectedMonth]) }}">
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

        <div class="profile-card">
            <div class="profile-avatar">{{ $empInitials }}</div>
            <div>
                <div class="profile-name">{{ $empName ?: 'Juan Dela Cruz' }}</div>
                <div class="profile-meta">{{ $empDept ?: 'IT/Information Technology Department' }}</div>
                <div class="profile-meta">{{ $empTitle ?: 'Senior Programmer' }}</div>
            </div>
        </div>

        <div class="table-card">
            <div class="table-toolbar">
                <h2>Attendance Records</h2>
                <div class="toolbar-right">
                    <form method="GET" action="{{ route('supervisor.attendance.employee') }}" id="detailForm" style="display:contents;">
                        <input type="hidden" name="view" value="monthly">
                        <input type="hidden" name="employee_id" value="{{ request('employee_id') }}">

                        <div class="period-btns">
                            <button type="button" class="period-btn {{ $selectedPeriod == '1' ? 'active' : '' }}" onclick="setPeriod('1')">Period 1</button>
                            <button type="button" class="period-btn {{ $selectedPeriod == '2' ? 'active' : '' }}" onclick="setPeriod('2')">Period 2</button>
                        </div>
                        <input type="hidden" name="period" id="periodInput" value="{{ $selectedPeriod }}">

                        <div class="date-picker">
                            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                            </svg>
                            <input type="month" name="month" value="{{ $selectedMonth }}"
                                onchange="document.getElementById('detailForm').submit()">
                        </div>
                    </form>

                    <a href="{{ route('supervisor.attendance.employee', ['view' => 'monthly', 'employee_id' => request('employee_id'), 'month' => $selectedMonth, 'period' => $selectedPeriod, 'export' => 1]) }}"
                       class="export-btn">
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                        </svg>
                        Export
                    </a>
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
                            $st     = strtolower($rec->status ?? 'present');
                            $isWknd = \Carbon\Carbon::parse($rec->date)->isWeekend();
                            $ot     = $rec->overtime_formatted  ?? '00h 00m';
                            $ut     = $rec->undertime_formatted ?? '00h 00m';
                        @endphp
                        <tr class="{{ $isWknd ? 'rest-row' : '' }}">
                            <td style="font-weight:600; color:#111827; white-space:nowrap;">
                                {{ \Carbon\Carbon::parse($rec->date)->format('F j, Y') }}
                                @if($isWknd)<span style="font-size:10px;color:#9ca3af;font-weight:500;margin-left:4px;">(Rest Day)</span>@endif
                            </td>
                            <td style="color:var(--muted);font-size:12.5px;">{{ $rec->work_setup ?? 'WFH' }}</td>
                            <td style="color:var(--muted);font-size:12.5px;">{{ $rec->shift_type ?? 'Day Shift' }}</td>
                            <td style="color:var(--muted);font-size:12.5px;">{{ $rec->schedule ?? '7:00 AM – 4:00 PM' }}</td>
                            <td style="font-weight:700;">{{ $rec->time_in  ? \Carbon\Carbon::parse($rec->time_in)->format('g:i A')  : '—' }}</td>
                            <td style="font-weight:700;">{{ $rec->time_out ? \Carbon\Carbon::parse($rec->time_out)->format('g:i A') : '—' }}</td>
                            <td><span class="{{ $ot === '00h 00m' ? 'hp-zero' : 'hp-ot' }}">{{ $ot }}</span></td>
                            <td><span class="{{ $ut === '00h 00m' ? 'hp-zero' : 'hp-ut' }}">{{ $ut }}</span></td>
                            <td><span class="status-badge badge-{{ $st }}">{{ ucfirst($rec->status ?? 'Present') }}</span></td>
                        </tr>
                        @empty
                        @php $cursor = $start->copy(); @endphp
                        @while($cursor->lte($end))
                        @php $isWknd = $cursor->isWeekend(); @endphp
                        <tr class="{{ $isWknd ? 'rest-row' : '' }}">
                            <td style="font-weight:600;color:#111827;white-space:nowrap;">
                                {{ $cursor->format('F j, Y') }}
                                @if($isWknd)<span style="font-size:10px;color:#9ca3af;font-weight:500;margin-left:4px;">(Rest Day)</span>@endif
                            </td>
                            <td style="color:var(--muted);font-size:12.5px;">WFH</td>
                            <td style="color:var(--muted);font-size:12.5px;">Day Shift</td>
                            <td style="color:var(--muted);font-size:12.5px;">7:00 AM – 4:00 PM</td>
                            <td style="font-weight:700;">{{ $isWknd ? '—' : '7:00 AM' }}</td>
                            <td style="font-weight:700;">{{ $isWknd ? '—' : '4:00 PM' }}</td>
                            <td><span class="hp-zero">00h 00m</span></td>
                            <td><span class="hp-zero">00h 00m</span></td>
                            <td>
                                @if($isWknd)
                                    <span class="status-badge badge-rest">Rest Day</span>
                                @else
                                    <span class="status-badge badge-present">Present</span>
                                @endif
                            </td>
                        </tr>
                        @php $cursor->addDay(); @endphp
                        @endwhile
                        @endforelse
                    </tbody>
                </table>
            </div>

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

        <div class="stat-cards-grid">
            <div class="stat-card present">
                <div class="sc-icon">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
                <div class="sc-label">Present Today</div>
                <div class="sc-value">{{ $presentCount ?? 218 }}</div>
                <div class="sc-sub">{{ $presentRate ?? '88.3%' }} attendance rate</div>
            </div>

            <div class="stat-card late">
                <div class="sc-icon">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
                <div class="sc-label">Late</div>
                <div class="sc-value">{{ $lateCount ?? 2 }}</div>
                <div class="sc-sub">{{ $lateRate ?? '3.2%' }} of workforce</div>
            </div>

            <div class="stat-card absent">
                <div class="sc-icon">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
                <div class="sc-label">Absent</div>
                <div class="sc-value">{{ $absentCount ?? 15 }}</div>
                <div class="sc-sub">{{ $absentRate ?? '6.1%' }} absent today</div>
            </div>

            <div class="stat-card overtime">
                <div class="sc-icon">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/>
                    </svg>
                </div>
                <div class="sc-label">Overtime</div>
                <div class="sc-value">{{ $overtimeHours ?? '47 hrs' }}</div>
                <div class="sc-sub">Across {{ $overtimeEmployees ?? 12 }} employees</div>
            </div>

            <div class="stat-card undertime">
                <div class="sc-icon">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 17h8m0 0V9m0 8l-8-8-4 4-6-6"/>
                    </svg>
                </div>
                <div class="sc-label">Undertime</div>
                <div class="sc-value">{{ $undertimeHours ?? '2 hrs' }}</div>
                <div class="sc-sub">Across {{ $undertimeEmployees ?? 2 }} employees</div>
            </div>
        </div>

        <div class="table-card">
            <div class="table-toolbar">
                <h2>Attendance Records</h2>
                <div class="toolbar-right">
                    <form method="GET" action="{{ route('supervisor.attendance.employee') }}" id="filterForm" style="display:contents">

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

                @if($currentView === 'daily')
                {{-- ── DAILY TABLE ── --}}
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
                                <div style="display:flex;align-items:center;gap:10px;">
                                    <div class="emp-avatar">
                                        {{ strtoupper(substr($rec->employee->fname ?? 'J', 0, 1) . substr($rec->employee->lname ?? 'D', 0, 1)) }}
                                    </div>
                                    <div><div class="emp-name">{{ trim(($rec->employee->fname ?? '') . ' ' . ($rec->employee->lname ?? '')) ?: 'Juan Dela Cruz' }}</div></div>
                                </div>
                            </td>
                            <td class="emp-dept">{{ $rec->employee->department->name ?? '—' }}</td>
                            <td>
                                <div class="shift-badge">
                                    {{ $rec->shift->type ?? 'Day Shift' }}
                                    <span class="shift-sub">{{ $rec->shift->mode ?? 'WFH' }}</span>
                                </div>
                            </td>
                            <td style="color:#374151;font-size:13px;">{{ $rec->schedule ?? '7:00 AM – 4:00 PM' }}</td>
                            <td style="font-weight:600;">{{ $rec->time_in  ? \Carbon\Carbon::parse($rec->time_in)->format('g:i A')  : '—' }}</td>
                            <td style="font-weight:600;">{{ $rec->time_out ? \Carbon\Carbon::parse($rec->time_out)->format('g:i A') : '—' }}</td>
                            <td><span class="hours-pill ot">{{ $rec->overtime_formatted  ?? '00h 00m' }}</span></td>
                            <td><span class="hours-pill ut">{{ $rec->undertime_formatted ?? '00h 00m' }}</span></td>
                            <td>
                                @php $st = strtolower($rec->status ?? 'present'); @endphp
                                <span class="status-badge badge-{{ $st }}">{{ ucfirst($rec->status ?? 'Present') }}</span>
                            </td>
                        </tr>
                        @empty
                        @for($i = 0; $i < 10; $i++)
                        <tr>
                            <td>
                                <div style="display:flex;align-items:center;gap:10px;">
                                    <div class="emp-avatar">JD</div>
                                    <div><div class="emp-name">Juan Dela Cruz</div></div>
                                </div>
                            </td>
                            <td class="emp-dept">IT/Information Technology</td>
                            <td><div class="shift-badge">Day Shift<span class="shift-sub">WFH</span></div></td>
                            <td style="color:#374151;font-size:13px;">7:00 AM – 4:00 PM</td>
                            <td style="font-weight:600;">7:00 AM</td>
                            <td style="font-weight:600;">4:00 PM</td>
                            <td><span class="hours-pill ot">00h 00m</span></td>
                            <td><span class="hours-pill ut">00h 00m</span></td>
                            <td><span class="status-badge badge-present">Present</span></td>
                        </tr>
                        @endfor
                        @endforelse
                    </tbody>
                </table>

                @else
                {{-- ── MONTHLY TABLE ── --}}
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
                                <div style="display:flex;align-items:center;gap:10px;">
                                    <div class="emp-avatar">
                                        {{ strtoupper(substr($rec->employee->fname ?? 'J', 0, 1) . substr($rec->employee->lname ?? 'D', 0, 1)) }}
                                    </div>
                                    <div><div class="emp-name">{{ trim(($rec->employee->fname ?? '') . ' ' . ($rec->employee->lname ?? '')) ?: 'Juan Dela Cruz' }}</div></div>
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
                                <a href="{{ route('supervisor.attendance.employee', ['employee_id' => $rec->employee_id ?? '', 'view' => 'monthly', 'month' => request('month', now()->format('Y-m'))]) }}"
                                   class="view-monthly-btn">
                                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                    </svg>
                                    View Monthly
                                </a>
                            </td>
                        </tr>
                        @empty
                        @for($i = 0; $i < 11; $i++)
                        <tr>
                            <td>
                                <div style="display:flex;align-items:center;gap:10px;">
                                    <div class="emp-avatar">JD</div>
                                    <div><div class="emp-name">Juan Dela Cruz</div></div>
                                </div>
                            </td>
                            <td class="emp-dept">IT/Information Technology</td>
                            <td style="text-align:center;"><span class="monthly-stat ms-present">24</span></td>
                            <td style="text-align:center;"><span class="monthly-stat ms-late">2</span></td>
                            <td style="text-align:center;"><span class="monthly-stat ms-absent">2</span></td>
                            <td style="text-align:center;"><span class="monthly-stat ms-leave">1</span></td>
                            <td style="text-align:center;"><span class="hours-pill">162h</span></td>
                            <td style="text-align:center;"><span class="hours-pill ot">20h</span></td>
                            <td style="text-align:center;"><span class="hours-pill ut">28m</span></td>
                            <td>
                                <a href="{{ route('supervisor.attendance.employee', ['employee_id' => $i + 1, 'view' => 'monthly', 'month' => request('month', now()->format('Y-m'))]) }}"
                                   class="view-monthly-btn">
                                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                    </svg>
                                    View Monthly
                                </a>
                            </td>
                        </tr>
                        @endfor
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
        @endif {{-- end @if($viewingDetail) --}}

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
</script>
</body>
</html>
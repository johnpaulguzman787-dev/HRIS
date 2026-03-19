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

    $attendanceRoutes = ['supervisor.attendance.reports','supervisor.attendance.employee','supervisor.attendance.shift','supervisor.attendance.leave','supervisor.shift.scheduling','supervisor.leave.management'];
    $employeeRoutes   = ['supervisor.employees.directory','supervisor.employees.profile'];
    $payrollRoutes    = ['supervisor.payroll','supervisor.payslips','supervisor.contributions'];
    $requestRoutes    = ['supervisor.requests.pending','supervisor.requests.approved'];

    // ── Calendar data ──────────────────────────────────────────────────────
    $calMonth = request('month') ? \Carbon\Carbon::parse(request('month').'-01') : \Carbon\Carbon::now()->startOfMonth();
    $calPrev  = $calMonth->copy()->subMonth()->format('Y-m');
    $calNext  = $calMonth->copy()->addMonth()->format('Y-m');
    $calStart = $calMonth->copy()->startOfMonth();
    $calEnd   = $calMonth->copy()->endOfMonth();
    $firstDow = $calStart->dayOfWeek; // 0=Sun
    $currentYear = request('year', now()->year);
@endphp

<!DOCTYPE html>
<html lang="en">
<head>
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
        :root {
            --blue: #3b82f6; --blue-dark: #1d4ed8; --blue-light: #eff6ff;
            --muted: #6b7280; --border: #e5e7eb;
        }
        .nav-item    { transition: background 0.15s, color 0.15s; }
        .chevron-icon { transition: transform 0.25s cubic-bezier(0.4,0,0.2,1); }

        /* ── TABS ── */
        .tab-nav { display:flex; border-bottom:2px solid var(--border); margin-bottom:24px; }
        .tab-btn {
            padding:10px 24px; font-size:14px; font-weight:500; color:var(--muted);
            border:none; background:none; cursor:pointer; font-family:inherit;
            border-bottom:3px solid transparent; margin-bottom:-2px;
            transition:color .15s,border-color .15s; text-decoration:none; display:inline-block;
        }
        .tab-btn:hover { color:#374151; }
        .tab-btn.active { color:var(--blue); border-bottom-color:var(--blue); font-weight:600; }

        /* ── LEAVE STAT CARDS ── */
        .leave-cards { display:grid; grid-template-columns:repeat(4,1fr); gap:16px; margin-bottom:28px; }
        .leave-card {
            background:#fff; border:1px solid var(--border); border-radius:14px;
            padding:20px 22px; position:relative; box-shadow:0 1px 6px rgba(0,0,0,.04);
        }
        .leave-card-label { font-size:13px; color:var(--muted); font-weight:500; margin-bottom:6px; }
        .leave-card-value { font-size:34px; font-weight:800; color:#111827; line-height:1; }
        .leave-card-sub   { font-size:11.5px; color:#9ca3af; margin-top:6px; }
        .leave-badge {
            position:absolute; top:18px; right:18px;
            padding:3px 10px; border-radius:20px; font-size:12px; font-weight:700;
        }
        .lb-vl   { background:#dbeafe; color:#1d4ed8; }
        .lb-sl   { background:#fce7f3; color:#be185d; }
        .lb-lwop { background:#ffedd5; color:#c2410c; }

        /* ── TOOLBAR ── */
        .toolbar { display:flex; align-items:center; justify-content:space-between; margin-bottom:16px; gap:12px; flex-wrap:wrap; }
        .toolbar-title { font-size:16px; font-weight:700; color:#111827; }
        .toolbar-right { display:flex; align-items:center; gap:10px; flex-wrap:wrap; }

        .filter-select {
            appearance:none; background:#f9fafb; border:1px solid var(--border);
            border-radius:8px; padding:8px 28px 8px 12px; font-size:13px;
            color:#374151; cursor:pointer; outline:none; font-family:inherit;
            background-image:url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='10' height='6' fill='none'%3E%3Cpath d='M1 1l4 4 4-4' stroke='%236b7280' stroke-width='1.5' stroke-linecap='round'/%3E%3C/svg%3E");
            background-repeat:no-repeat; background-position:right 10px center;
        }

        .btn-primary {
            display:inline-flex; align-items:center; gap:6px;
            padding:8px 18px; border-radius:8px; font-size:13px; font-weight:600;
            font-family:inherit; cursor:pointer; border:none;
            background:var(--blue); color:#fff; transition:background .15s;
        }
        .btn-primary:hover { background:var(--blue-dark); }
        .btn-primary svg { width:14px; height:14px; }

        .btn-outline-sm {
            display:inline-flex; align-items:center; gap:4px;
            padding:5px 14px; border-radius:7px; font-size:12px; font-weight:600;
            font-family:inherit; cursor:pointer;
            border:1.5px solid #bfdbfe; background:var(--blue-light); color:var(--blue-dark);
            transition:all .15s;
        }
        .btn-outline-sm:hover { background:#dbeafe; }

        /* ── TABLE ── */
        .table-card {
            background:#fff; border-radius:14px; border:1px solid var(--border);
            overflow:hidden; box-shadow:0 1px 8px rgba(0,0,0,.04);
        }
        .data-table { width:100%; border-collapse:collapse; }
        .data-table thead tr { background:#f9fafb; }
        .data-table th {
            padding:11px 16px; font-size:11.5px; font-weight:600; color:var(--muted);
            text-align:left; border-bottom:1px solid var(--border);
            white-space:nowrap; letter-spacing:.4px; text-transform:uppercase;
        }
        .data-table td {
            padding:14px 16px; font-size:13px; color:#111827;
            border-bottom:1px solid #f3f4f6; vertical-align:middle;
        }
        .data-table tr:last-child td { border-bottom:none; }
        .data-table tbody tr:hover   { background:#fafafa; }

        /* ── LEAVE TYPE PILLS ── */
        .lt-vl   { background:#dbeafe; color:#1d4ed8; padding:3px 11px; border-radius:20px; font-size:12px; font-weight:600; display:inline-block; }
        .lt-sl   { background:#fce7f3; color:#be185d; padding:3px 11px; border-radius:20px; font-size:12px; font-weight:600; display:inline-block; }
        .lt-lwop { background:#ffedd5; color:#c2410c; padding:3px 11px; border-radius:20px; font-size:12px; font-weight:600; display:inline-block; }
        .lt-ml   { background:#dcfce7; color:#15803d; padding:3px 11px; border-radius:20px; font-size:12px; font-weight:600; display:inline-block; }
        .lt-pl   { background:#ede9fe; color:#6d28d9; padding:3px 11px; border-radius:20px; font-size:12px; font-weight:600; display:inline-block; }
        .lt-spl  { background:#fef9c3; color:#a16207; padding:3px 11px; border-radius:20px; font-size:12px; font-weight:600; display:inline-block; }

        /* ── STATUS BADGES ── */
        .status-pending  { background:#ffedd5; color:#c2410c; padding:3px 12px; border-radius:20px; font-size:12px; font-weight:600; display:inline-block; }
        .status-approved { background:#dcfce7; color:#15803d; padding:3px 12px; border-radius:20px; font-size:12px; font-weight:600; display:inline-block; }
        .status-rejected { background:#fee2e2; color:#dc2626; padding:3px 12px; border-radius:20px; font-size:12px; font-weight:600; display:inline-block; }

        /* ── LEAVE CREDITS SECTION ── */
        .credits-3col { display:grid; grid-template-columns:repeat(3,1fr); gap:16px; margin-bottom:28px; }
        .search-box {
            display:flex; align-items:center; gap:7px;
            background:#f9fafb; border:1px solid var(--border);
            border-radius:8px; padding:7px 13px;
        }
        .search-box svg { color:var(--muted); width:14px; height:14px; flex-shrink:0; }
        .search-box input {
            border:none; background:transparent; outline:none;
            font-size:13px; color:#111827; width:150px; font-family:inherit;
        }

        /* ── CALENDAR ── */
        .cal-nav { display:flex; align-items:center; gap:10px; }
        .cal-month-label { font-size:20px; font-weight:800; color:#111827; }
        .cal-nav-btn {
            display:inline-flex; align-items:center; gap:5px;
            padding:6px 12px; border-radius:7px; font-size:12.5px;
            font-weight:500; font-family:inherit; cursor:pointer;
            border:1px solid var(--border); background:#fff; color:#374151;
            transition:background .15s; text-decoration:none;
        }
        .cal-nav-btn:hover { background:#f9fafb; }
        .cal-nav-btn svg { width:12px; height:12px; }

        .cal-grid { width:100%; border-collapse:collapse; background:#fff; border-radius:14px; overflow:hidden; border:1px solid var(--border); }
        .cal-grid th {
            padding:10px; font-size:12px; font-weight:700; color:var(--muted);
            text-align:center; background:#f9fafb; border-bottom:1px solid var(--border);
            letter-spacing:.5px; text-transform:uppercase;
        }
        .cal-grid td {
            width:14.28%; height:100px; padding:8px; border:1px solid #f3f4f6;
            vertical-align:top; font-size:13px; font-weight:600; color:#374151;
        }
        .cal-grid td.other-month { background:#fafafa; color:#d1d5db; }
        .cal-event {
            display:inline-block; padding:2px 8px; border-radius:5px;
            font-size:11px; font-weight:600; margin-top:4px; width:100%;
            overflow:hidden; text-overflow:ellipsis; white-space:nowrap;
        }
        .cal-vl      { background:#dbeafe; color:#1d4ed8; }
        .cal-sl      { background:#fce7f3; color:#be185d; }
        .cal-lwop    { background:#ffedd5; color:#c2410c; }
        .cal-holiday { background:#fee2e2; color:#dc2626; }

        .cal-legend { display:flex; align-items:center; gap:20px; margin-top:16px; flex-wrap:wrap; }
        .cal-legend-item { display:flex; align-items:center; gap:6px; font-size:12px; color:#374151; }
        .cal-legend-dot  { width:12px; height:12px; border-radius:3px; flex-shrink:0; }

        /* ── LEAVE TYPES TABLE ── */
        .lt-code-badge {
            padding:3px 10px; border-radius:20px; font-size:12px; font-weight:700; display:inline-block;
        }
        .pay-paid   { background:#dcfce7; color:#15803d; padding:3px 12px; border-radius:20px; font-size:12px; font-weight:600; display:inline-block; }
        .pay-unpaid { background:#fee2e2; color:#dc2626; padding:3px 12px; border-radius:20px; font-size:12px; font-weight:600; display:inline-block; }
        .doc-req    { background:#ffedd5; color:#c2410c; padding:3px 12px; border-radius:20px; font-size:12px; font-weight:600; display:inline-block; }
        .doc-not    { background:#f3f4f6; color:#6b7280; padding:3px 12px; border-radius:20px; font-size:12px; font-weight:600; display:inline-block; }

        /* ── MODAL ── */
        .modal-overlay {
            position:fixed; inset:0; background:rgba(0,0,0,.4);
            display:flex; align-items:center; justify-content:center; z-index:999;
        }
        .modal-box {
            background:#fff; border-radius:16px; padding:28px 32px;
            width:520px; max-width:95vw; box-shadow:0 20px 60px rgba(0,0,0,.15);
        }
        .modal-title { font-size:17px; font-weight:800; color:#111827; }
        .form-label  { font-size:12px; font-weight:600; color:#374151; text-transform:uppercase; letter-spacing:.5px; margin-bottom:5px; display:block; }
        .form-input  {
            width:100%; border:1.5px solid var(--border); border-radius:8px;
            padding:9px 13px; font-size:13px; font-family:inherit; outline:none;
            color:#111827; transition:border-color .15s;
        }
        .form-input:focus { border-color:var(--blue); }
        .form-row    { display:grid; grid-template-columns:1fr 1fr; gap:14px; }
        .modal-actions { display:flex; justify-content:flex-end; gap:10px; margin-top:22px; }
        .btn-cancel  {
            padding:8px 18px; border-radius:8px; font-size:13px; font-weight:600;
            font-family:inherit; cursor:pointer; border:1.5px solid var(--border); background:#fff; color:#374151;
        }
        .btn-save    {
            padding:8px 20px; border-radius:8px; font-size:13px; font-weight:600;
            font-family:inherit; cursor:pointer; border:none; background:var(--blue); color:#fff;
        }
        .close-btn {
            width:32px; height:32px; border:2px solid #374151; border-radius:50%;
            display:flex; align-items:center; justify-content:center;
            background:none; cursor:pointer; flex-shrink:0;
        }
    </style>
</head>
<body class="bg-gray-50">

{{-- ══════════ HR SIDEBAR ══════════ --}}
<aside
    class="bg-white border-r border-gray-200 h-screen fixed left-0 top-0 overflow-y-auto z-50 flex flex-col"
    x-data="{
        sidebarCollapsed: localStorage.getItem('sidebarCollapsed') === 'true',
        employeesOpen:  {{ in_array($currentRoute, $employeeRoutes)   ? 'true' : 'false' }},
        attendanceOpen: {{ in_array($currentRoute, $attendanceRoutes) ? 'true' : 'false' }},
        payrollOpen:    {{ in_array($currentRoute, $payrollRoutes)    ? 'true' : 'false' }},
        requestsOpen:   {{ in_array($currentRoute, $requestRoutes)    ? 'true' : 'false' }}
    }"
    x-init="$watch('sidebarCollapsed', value => localStorage.setItem('sidebarCollapsed', value))"
    :class="sidebarCollapsed ? 'w-20' : 'w-64'"
    style="transition:width 0.35s cubic-bezier(0.4,0,0.2,1); box-shadow:2px 0 20px rgba(0,0,0,0.06);">

    <div class="px-6 py-5 border-b border-gray-100">
        <div class="flex items-center space-x-3" :class="sidebarCollapsed ? 'justify-center' : ''">
            <div class="w-9 h-9 border-2 border-gray-800 flex items-center justify-center flex-shrink-0" style="border-radius:6px;">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
            </div>
            <h1 x-show="!sidebarCollapsed" x-transition:enter="transition ease-out duration-300 delay-100" x-transition:enter-start="opacity-0 -translate-x-4" x-transition:enter-end="opacity-100 translate-x-0" x-transition:leave="transition ease-in duration-100" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0" class="font-bold text-gray-900 text-lg tracking-widest whitespace-nowrap">MEDISOURCE</h1>
        </div>
    </div>

    <div class="px-4 py-4 border-b border-gray-100" :class="sidebarCollapsed ? 'flex justify-center' : 'flex items-center space-x-3'">
        <div class="w-11 h-11 rounded-full flex items-center justify-center text-white font-bold flex-shrink-0 text-sm" style="background:linear-gradient(135deg,#3b82f6 0%,#1d4ed8 100%); box-shadow:0 0 0 3px rgba(59,130,246,.25);">{{ $sidebarInitials }}</div>
        <div x-show="!sidebarCollapsed" x-transition:enter="transition ease-out duration-300 delay-100" x-transition:enter-start="opacity-0 -translate-x-3" x-transition:enter-end="opacity-100 translate-x-0" x-transition:leave="transition ease-in duration-100" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0" class="overflow-hidden">
            <p class="font-semibold text-gray-800 text-sm leading-tight truncate">{{ $sidebarName }}</p>
            <p class="text-xs mt-0.5 font-semibold" style="color:#3b82f6;">{{ $sidebarRole }}</p>
        </div>
    </div>

    <nav class="p-3 space-y-0.5 flex-1 overflow-y-auto">
        <p x-show="!sidebarCollapsed" class="text-xs text-gray-400 font-semibold px-3 py-2 uppercase tracking-widest">Main Menu</p>

        <a href="{{ route('supervisor.dashboard') }}" class="nav-item flex items-center space-x-3 px-3 py-2.5 rounded-lg {{ $currentRoute === 'supervisor.dashboard' ? 'text-white' : 'text-gray-600 hover:bg-gray-50' }}" style="{{ $currentRoute === 'supervisor.dashboard' ? 'background:#3b82f6;' : '' }}">
            <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"/></svg>
            <span x-show="!sidebarCollapsed" class="text-sm font-medium whitespace-nowrap">Dashboard</span>
        </a>

        <div>
            <button @click="employeesOpen = !employeesOpen" class="nav-item w-full flex items-center justify-between px-3 py-2.5 rounded-lg {{ in_array($currentRoute, $employeeRoutes) ? 'text-blue-600 bg-blue-50' : 'text-gray-600 hover:bg-gray-50' }}">
                <div class="flex items-center space-x-3">
                    <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
                    <span x-show="!sidebarCollapsed" class="text-sm font-medium whitespace-nowrap">Employees</span>
                </div>
                <svg x-show="!sidebarCollapsed" class="w-4 h-4 chevron-icon" :class="{'rotate-180': employeesOpen}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
            </button>
            <div x-show="employeesOpen && !sidebarCollapsed" x-transition:enter="transition ease-out duration-250" x-transition:enter-start="opacity-0 -translate-y-3 scale-y-95" x-transition:enter-end="opacity-100 translate-y-0 scale-y-100" x-transition:leave="transition ease-in duration-200" x-transition:leave-start="opacity-100 translate-y-0 scale-y-100" x-transition:leave-end="opacity-0 -translate-y-3 scale-y-95" class="ml-8 mt-1 space-y-0.5 origin-top">
                <a href="{{ route('supervisor.employees.directory') }}" class="block px-3 py-2 text-sm rounded-lg {{ $currentRoute === 'supervisor.employees.directory' ? 'text-blue-600 font-semibold bg-blue-50' : 'text-gray-500 hover:text-gray-800 hover:bg-gray-50' }}">Employee Directory</a>
                <a href="{{ route('supervisor.employees.profile') }}"   class="block px-3 py-2 text-sm rounded-lg {{ $currentRoute === 'supervisor.employees.profile'   ? 'text-blue-600 font-semibold bg-blue-50' : 'text-gray-500 hover:text-gray-800 hover:bg-gray-50' }}">Employee Profile</a>
            </div>
        </div>

        <div>
            <button @click="attendanceOpen = !attendanceOpen" class="nav-item w-full flex items-center justify-between px-3 py-2.5 rounded-lg {{ in_array($currentRoute, $attendanceRoutes) ? 'text-blue-600 bg-blue-50' : 'text-gray-600 hover:bg-gray-50' }}">
                <div class="flex items-center space-x-3">
                    <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    <span x-show="!sidebarCollapsed" class="text-sm font-medium whitespace-nowrap">Time & Attendance</span>
                </div>
                <svg x-show="!sidebarCollapsed" class="w-4 h-4 chevron-icon" :class="{'rotate-180': attendanceOpen}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
            </button>
            <div x-show="attendanceOpen && !sidebarCollapsed" x-transition:enter="transition ease-out duration-250" x-transition:enter-start="opacity-0 -translate-y-3 scale-y-95" x-transition:enter-end="opacity-100 translate-y-0 scale-y-100" x-transition:leave="transition ease-in duration-200" x-transition:leave-start="opacity-100 translate-y-0 scale-y-100" x-transition:leave-end="opacity-0 -translate-y-3 scale-y-95" class="ml-8 mt-1 space-y-0.5 origin-top">
                <a href="{{ route('supervisor.attendance.reports') }}" class="flex items-center px-3 py-2 text-sm rounded-lg {{ $currentRoute === 'supervisor.attendance.reports' ? 'text-blue-600 font-semibold bg-blue-50' : 'text-gray-500 hover:text-gray-800 hover:bg-gray-50' }}">
                    @if($currentRoute === 'supervisor.attendance.reports')<span class="w-2 h-2 rounded-full mr-2.5 flex-shrink-0" style="background:#3b82f6;animation:pulseDot 2s ease-in-out infinite;"></span>@endif
                    My Attendance
                </a>
                <a href="{{ route('supervisor.attendance.employee') }}"  class="block px-3 py-2 text-sm rounded-lg {{ $currentRoute === 'supervisor.attendance.employee'  ? 'text-blue-600 font-semibold bg-blue-50' : 'text-gray-500 hover:text-gray-800 hover:bg-gray-50' }}">Employee Attendance</a>
                <a href="{{ route('supervisor.shift.scheduling') }}"     class="block px-3 py-2 text-sm rounded-lg {{ $currentRoute === 'supervisor.shift.scheduling'     ? 'text-blue-600 font-semibold bg-blue-50' : 'text-gray-500 hover:text-gray-800 hover:bg-gray-50' }}">Shift Scheduling</a>
                <a href="{{ route('supervisor.leave.management') }}"     class="block px-3 py-2 text-sm rounded-lg {{ $currentRoute === 'supervisor.leave.management'     ? 'text-blue-600 font-semibold bg-blue-50' : 'text-gray-500 hover:text-gray-800 hover:bg-gray-50' }}">Leave Management</a>
            </div>
        </div>

        <div>
            <button @click="payrollOpen = !payrollOpen" class="nav-item w-full flex items-center justify-between px-3 py-2.5 rounded-lg {{ in_array($currentRoute, $payrollRoutes) ? 'text-blue-600 bg-blue-50' : 'text-gray-600 hover:bg-gray-50' }}">
                <div class="flex items-center space-x-3">
                    <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                    <span x-show="!sidebarCollapsed" class="text-sm font-medium whitespace-nowrap">Payroll</span>
                </div>
                <svg x-show="!sidebarCollapsed" class="w-4 h-4 chevron-icon" :class="{'rotate-180': payrollOpen}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
            </button>
            <div x-show="payrollOpen && !sidebarCollapsed" x-transition:enter="transition ease-out duration-250" x-transition:enter-start="opacity-0 -translate-y-3 scale-y-95" x-transition:enter-end="opacity-100 translate-y-0 scale-y-100" x-transition:leave="transition ease-in duration-200" x-transition:leave-start="opacity-100 translate-y-0 scale-y-100" x-transition:leave-end="opacity-0 -translate-y-3 scale-y-95" class="ml-8 mt-1 space-y-0.5 origin-top">
                <a href="#" class="block px-3 py-2 text-sm text-gray-500 hover:text-gray-800 hover:bg-gray-50 rounded-lg">Payroll</a>
                <a href="#" class="block px-3 py-2 text-sm text-gray-500 hover:text-gray-800 hover:bg-gray-50 rounded-lg">Payslips</a>
                <a href="#" class="block px-3 py-2 text-sm text-gray-500 hover:text-gray-800 hover:bg-gray-50 rounded-lg">Govt. Contributions</a>
            </div>
        </div>

        <div>
            <button @click="requestsOpen = !requestsOpen" class="nav-item w-full flex items-center justify-between px-3 py-2.5 rounded-lg {{ in_array($currentRoute, $requestRoutes) ? 'text-blue-600 bg-blue-50' : 'text-gray-600 hover:bg-gray-50' }}">
                <div class="flex items-center space-x-3">
                    <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
                    <span x-show="!sidebarCollapsed" class="text-sm font-medium whitespace-nowrap">Requests & Approval</span>
                </div>
                <svg x-show="!sidebarCollapsed" class="w-4 h-4 chevron-icon" :class="{'rotate-180': requestsOpen}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
            </button>
            <div x-show="requestsOpen && !sidebarCollapsed" x-transition:enter="transition ease-out duration-250" x-transition:enter-start="opacity-0 -translate-y-3 scale-y-95" x-transition:enter-end="opacity-100 translate-y-0 scale-y-100" x-transition:leave="transition ease-in duration-200" x-transition:leave-start="opacity-100 translate-y-0 scale-y-100" x-transition:leave-end="opacity-0 -translate-y-3 scale-y-95" class="ml-8 mt-1 space-y-0.5 origin-top">
                <a href="#" class="block px-3 py-2 text-sm text-gray-500 hover:text-gray-800 hover:bg-gray-50 rounded-lg">Pending Requests</a>
                <a href="#" class="block px-3 py-2 text-sm text-gray-500 hover:text-gray-800 hover:bg-gray-50 rounded-lg">Approved Logs</a>
            </div>
        </div>

        <div class="pt-3 mt-2 border-t border-gray-100">
            <p x-show="!sidebarCollapsed" class="text-xs text-gray-400 font-semibold px-3 py-2 uppercase tracking-widest">Others</p>
            <a href="#" class="nav-item flex items-center space-x-3 px-3 py-2.5 rounded-lg text-gray-600 hover:bg-gray-50">
                <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                <span x-show="!sidebarCollapsed" class="text-sm font-medium whitespace-nowrap">Settings</span>
            </a>
            <a href="{{ route('logout') }}" onclick="event.preventDefault(); document.getElementById('supervisor-logout-form').submit();" class="nav-item flex items-center space-x-3 px-3 py-2.5 rounded-lg text-gray-600 hover:bg-red-50 hover:text-red-500">
                <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>
                <span x-show="!sidebarCollapsed" class="text-sm font-medium whitespace-nowrap">Logout</span>
            </a>
            <form id="supervisor-logout-form" action="{{ route('logout') }}" method="POST" class="hidden">@csrf</form>
        </div>
    </nav>

    <button @click="sidebarCollapsed = !sidebarCollapsed" class="m-3 w-8 h-8 bg-gray-100 rounded-lg flex items-center justify-center text-gray-500 hover:bg-gray-200 self-end" style="transition:background .15s;">
        <svg class="w-4 h-4 chevron-icon" :class="{'rotate-180': sidebarCollapsed}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 19l-7-7 7-7m8 14l-7-7 7-7"/></svg>
    </button>
</aside>

{{-- ══════════ MAIN CONTENT ══════════ --}}
<div x-data="{ collapsed: localStorage.getItem('sidebarCollapsed') === 'true', showFileLeave: false, showAddLeaveType: false, showLeaveDetails: false, selectedLeave: {} }"
     x-init="window.addEventListener('storage', e => { if(e.key==='sidebarCollapsed') collapsed = e.newValue==='true' })"
     :style="collapsed ? 'margin-left:5rem' : 'margin-left:16rem'"
     style="transition:margin-left 0.35s cubic-bezier(0.4,0,0.2,1); min-height:100vh;">

    {{-- Blue Header --}}
    <header class="bg-gradient-to-br from-blue-500 to-blue-700 sticky top-0 z-10 shadow-lg mt-4 mx-4 rounded-2xl overflow-hidden">
        <div class="flex items-center justify-between px-8 py-4">
            <h1 class="text-white font-bold text-xl">Leave Management</h1>
            <button class="w-9 h-9 rounded-full flex items-center justify-center transition-all hover:bg-white/20">
                <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/></svg>
            </button>
        </div>
    </header>

    <div style="padding:24px 32px;">

        {{-- Tab Nav --}}
        <div class="tab-nav">
            <a href="{{ route('supervisor.leave.management', ['tab' => 'my-leave']) }}"      class="tab-btn {{ $activeTab === 'my-leave'      ? 'active' : '' }}">My Leave</a>
            <a href="{{ route('supervisor.leave.management', ['tab' => 'leave-credits']) }}" class="tab-btn {{ $activeTab === 'leave-credits'  ? 'active' : '' }}">Leave Credits</a>
            <a href="{{ route('supervisor.leave.management', ['tab' => 'leave-calendar']) }}" class="tab-btn {{ $activeTab === 'leave-calendar' ? 'active' : '' }}">Leave Calendar</a>
        </div>

        {{-- ══════════ MY LEAVE ══════════ --}}
        @if($activeTab === 'my-leave')

        <div class="leave-cards">
            <div class="leave-card">
                <span class="leave-badge lb-vl">VL</span>
                <div class="leave-card-label">Vacation Leave</div>
                <div class="leave-card-value">{{ $myLeaveStats['vl_used'] ?? 7 }}</div>
                <div class="leave-card-sub">{{ $myLeaveStats['vl_remaining'] ?? 8 }} remaining</div>
            </div>
            <div class="leave-card">
                <span class="leave-badge lb-sl">SL</span>
                <div class="leave-card-label">Sick Leave</div>
                <div class="leave-card-value">{{ $myLeaveStats['sl_used'] ?? 2 }}</div>
                <div class="leave-card-sub">{{ $myLeaveStats['sl_remaining'] ?? 13 }} remaining</div>
            </div>
            <div class="leave-card">
                <span class="leave-badge lb-lwop">LWOP</span>
                <div class="leave-card-label">Leave Without Pay</div>
                <div class="leave-card-value">{{ $myLeaveStats['lwop_used'] ?? 0 }}</div>
                <div class="leave-card-sub">&nbsp;</div>
            </div>
            <div class="leave-card">
                <div class="leave-card-label">Pending Request</div>
                <div class="leave-card-value">{{ $myLeaveStats['pending'] ?? 1 }}</div>
                <div class="leave-card-sub">Waiting for Approval</div>
            </div>
        </div>

        <div class="toolbar">
            <div class="toolbar-title">My Leave Requests</div>
            <div class="toolbar-right">
                <select class="filter-select"><option>All Status</option><option>Pending</option><option>Approved</option><option>Rejected</option></select>
                <select class="filter-select"><option>All Types</option><option>Vacation Leave</option><option>Sick Leave</option><option>LWOP</option></select>
                <button class="btn-primary" @click="showFileLeave = true">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                    File Leave
                </button>
            </div>
        </div>

        <div class="table-card">
            <table class="data-table">
                <thead><tr>
                    <th>Ref #</th><th>Leave Type</th><th>Filed On</th><th>Date From</th><th>Date To</th><th>Days</th><th>Reason</th><th>Approver</th><th>Status</th><th></th>
                </tr></thead>
                <tbody>
                @forelse($myLeaveRequests ?? [] as $req)
                <tr>
                    <td style="font-weight:600;color:#6b7280;">{{ $req->ref_no }}</td>
                    <td><span class="lt-{{ strtolower(str_replace(' ','_',$req->leave_type ?? 'vl')) }}">{{ $req->leave_type ?? '—' }}</span></td>
                    <td>{{ \Carbon\Carbon::parse($req->filed_on)->format('m/d/Y') }}</td>
                    <td>{{ \Carbon\Carbon::parse($req->date_from)->format('m/d/Y') }}</td>
                    <td>{{ \Carbon\Carbon::parse($req->date_to)->format('m/d/Y') }}</td>
                    <td>{{ $req->days }}</td>
                    <td style="color:#6b7280;">{{ $req->reason }}</td>
                    <td><div style="font-weight:700;font-size:13px;">{{ $req->approver_name }}</div><div style="font-size:11px;color:#9ca3af;">{{ $req->approver_role }}</div></td>
                    <td><span class="status-{{ strtolower($req->status) }}">{{ ucfirst($req->status) }}</span></td>
                    <td><button class="btn-outline-sm" @click="selectedLeave = { leave_type: 'Vacation Leave (VL)', date_from: '03/10/2026', date_to: '03/12/2026', reason: 'Family Trip', status: 'Pending Approval' }; showLeaveDetails = true">View</button></td>
                </tr>
                @empty
                @foreach([
                    ['REQ-0002','Vacation Leave','lt-vl','03/06/2026','03/10/2026','03/12/2026',3,'Family Trip to Cebu','John Dee','HR Manager','pending'],
                    ['REQ-0001','Sick Leave','lt-sl','02/25/2026','02/25/2026','02/26/2026',2,'Medical Appointment','John Dee','HR Manager','approved'],
                ] as $r)
                <tr>
                    <td style="font-weight:600;color:#6b7280;">{{ $r[0] }}</td>
                    <td><span class="{{ $r[2] }}">{{ $r[1] }}</span></td>
                    <td>{{ $r[3] }}</td><td>{{ $r[4] }}</td><td>{{ $r[5] }}</td><td>{{ $r[6] }}</td>
                    <td style="color:#6b7280;">{{ $r[7] }}</td>
                    <td><div style="font-weight:700;font-size:13px;">{{ $r[8] }}</div><div style="font-size:11px;color:#9ca3af;">{{ $r[9] }}</div></td>
                    <td><span class="status-{{ $r[10] }}">{{ ucfirst($r[10]) }}</span></td>
                    <td><button class="btn-outline-sm" @click="selectedLeave = { leave_type: 'Vacation Leave (VL)', date_from: '03/10/2026', date_to: '03/12/2026', reason: 'Family Trip', status: 'Pending Approval' }; showLeaveDetails = true">View</button></td>
                </tr>
                @endforeach
                @endforelse
                </tbody>
            </table>
        </div>

        {{-- ══════════ LEAVE CREDITS ══════════ --}}
        @elseif($activeTab === 'leave-credits')

        <div class="toolbar" style="margin-bottom:20px;">
            <div class="toolbar-title">Employee Leave Credits</div>
        </div>
        <div style="display:flex;align-items:center;gap:12px;margin-bottom:20px;flex-wrap:wrap;">
            <div style="font-size:13px;color:#6b7280;font-weight:500;">View credits for:</div>
            <select class="filter-select" style="min-width:180px;">
                <option>Juan Dela Cruz</option>
                @foreach($employees ?? [] as $emp)
                    <option value="{{ $emp->id }}">{{ trim($emp->fname.' '.$emp->lname) }}</option>
                @endforeach
            </select>
            <select class="filter-select"><option>All Departments</option>@foreach($departments ?? [] as $d)<option>{{ $d->name }}</option>@endforeach</select>
            <select class="filter-select"><option>{{ now()->year }}</option><option>{{ now()->year - 1 }}</option></select>
        </div>

        <div class="credits-3col">
            <div class="leave-card">
                <span class="leave-badge lb-vl">VL</span>
                <div class="leave-card-label">Vacation Leave</div>
                <div class="leave-card-value">{{ $creditStats['vl_used'] ?? 7 }}</div>
                <div class="leave-card-sub">{{ $creditStats['vl_remaining'] ?? 8 }} remaining</div>
            </div>
            <div class="leave-card">
                <span class="leave-badge lb-sl">SL</span>
                <div class="leave-card-label">Sick Leave</div>
                <div class="leave-card-value">{{ $creditStats['sl_used'] ?? 2 }}</div>
                <div class="leave-card-sub">{{ $creditStats['sl_remaining'] ?? 13 }} remaining</div>
            </div>
            <div class="leave-card">
                <span class="leave-badge lb-lwop">LWOP</span>
                <div class="leave-card-label">Leave Without Pay</div>
                <div class="leave-card-value">{{ $creditStats['lwop_used'] ?? 0 }}</div>
                <div class="leave-card-sub">&nbsp;</div>
            </div>
        </div>

        <div class="toolbar">
            <div class="toolbar-title">Leave History</div>
            <div class="toolbar-right">
                <select class="filter-select"><option>All Status</option><option>Pending</option><option>Approved</option><option>Rejected</option></select>
                <select class="filter-select"><option>All Types</option><option>Vacation Leave</option><option>Sick Leave</option><option>LWOP</option></select>
            </div>
        </div>

        <div class="table-card">
            <table class="data-table">
                <thead><tr>
                    <th>Ref #</th><th>Leave Type</th><th>Filed On</th><th>Date From</th><th>Date To</th><th>Days</th><th>Reason</th><th>Approver</th><th>Status</th><th></th>
                </tr></thead>
                <tbody>
                @forelse($leaveHistory ?? [] as $req)
                <tr>
                    <td style="font-weight:600;color:#6b7280;">{{ $req->ref_no }}</td>
                    <td><span class="lt-vl">{{ $req->leave_type }}</span></td>
                    <td>{{ \Carbon\Carbon::parse($req->filed_on)->format('m/d/Y') }}</td>
                    <td>{{ \Carbon\Carbon::parse($req->date_from)->format('m/d/Y') }}</td>
                    <td>{{ \Carbon\Carbon::parse($req->date_to)->format('m/d/Y') }}</td>
                    <td>{{ $req->days }}</td>
                    <td style="color:#6b7280;">{{ $req->reason }}</td>
                    <td><div style="font-weight:700;font-size:13px;">{{ $req->approver_name }}</div><div style="font-size:11px;color:#9ca3af;">{{ $req->approver_role }}</div></td>
                    <td><span class="status-{{ strtolower($req->status) }}">{{ ucfirst($req->status) }}</span></td>
                    <td><button class="btn-outline-sm" @click="selectedLeave = { leave_type: 'Vacation Leave (VL)', date_from: '03/10/2026', date_to: '03/12/2026', reason: 'Family Trip', status: 'Pending Approval' }; showLeaveDetails = true">View</button></td>
                </tr>
                @empty
                @foreach([
                    ['REQ-0002','Vacation Leave','lt-vl','03/06/2026','03/10/2026','03/12/2026',3,'Family Trip','Jan Dy','Supervisor','pending'],
                    ['REQ-0001','Sick Leave','lt-sl','02/25/2026','02/25/2026','02/26/2026',2,'Medical Appointment','John Dee','HR Manager','approved'],
                ] as $r)
                <tr>
                    <td style="font-weight:600;color:#6b7280;">{{ $r[0] }}</td>
                    <td><span class="{{ $r[2] }}">{{ $r[1] }}</span></td>
                    <td>{{ $r[3] }}</td><td>{{ $r[4] }}</td><td>{{ $r[5] }}</td><td>{{ $r[6] }}</td>
                    <td style="color:#6b7280;">{{ $r[7] }}</td>
                    <td><div style="font-weight:700;font-size:13px;">{{ $r[8] }}</div><div style="font-size:11px;color:#9ca3af;">{{ $r[9] }}</div></td>
                    <td><span class="status-{{ $r[10] }}">{{ ucfirst($r[10]) }}</span></td>
                    <td><button class="btn-outline-sm" @click="selectedLeave = { leave_type: 'Vacation Leave (VL)', date_from: '03/10/2026', date_to: '03/12/2026', reason: 'Family Trip', status: 'Pending Approval' }; showLeaveDetails = true">View</button></td>
                </tr>
                @endforeach
                @endforelse
                </tbody>
            </table>
        </div>

        {{-- ══════════ LEAVE CALENDAR ══════════ --}}
        @elseif($activeTab === 'leave-calendar')

        <div class="toolbar">
            <div class="cal-nav">
                <a href="{{ route('supervisor.leave.management', ['tab' => 'leave-calendar', 'month' => $calPrev]) }}" class="cal-nav-btn">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                    Previous
                </a>
                <span class="cal-month-label">{{ $calMonth->format('F') }}</span>
                <a href="{{ route('supervisor.leave.management', ['tab' => 'leave-calendar', 'month' => $calNext]) }}" class="cal-nav-btn">
                    Next
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                </a>
            </div>
            <div class="toolbar-right">
                <select class="filter-select"><option>All Departments</option>@foreach($departments ?? [] as $d)<option>{{ $d->name }}</option>@endforeach</select>
                <select class="filter-select"><option>All Types</option><option>Vacation Leave</option><option>Sick Leave</option><option>LWOP</option></select>
            </div>
        </div>

        @php
            $calEvents = $calendarEvents ?? [];
            // Demo events
            if(empty($calEvents)) {
                $calEvents = [
                    ['day' => 6,  'type' => 'cal-sl',   'label' => 'SL - Ana Reyes'],
                    ['day' => 6,  'type' => 'cal-vl',   'label' => 'VL - Lisa Valdez'],
                    ['day' => 10, 'type' => 'cal-vl',   'label' => 'VL - Juan Dela Cruz'],
                    ['day' => 11, 'type' => 'cal-vl',   'label' => 'VL - Juan Dela Cruz'],
                    ['day' => 12, 'type' => 'cal-vl',   'label' => 'VL - Juan Dela Cruz'],
                    ['day' => 20, 'type' => 'cal-holiday','label' => 'Holiday'],
                ];
            }
            $eventsByDay = collect($calEvents)->groupBy('day');
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
            <div class="cal-legend-item"><div class="cal-legend-dot" style="background:#dbeafe;"></div> VL – Vacation Leave</div>
            <div class="cal-legend-item"><div class="cal-legend-dot" style="background:#fce7f3;"></div> SL – Sick Leave</div>
            <div class="cal-legend-item"><div class="cal-legend-dot" style="background:#ffedd5;"></div> LWOP – Leave Without Pay</div>
        </div>

        @endif

        {{-- ══════════ MODALS ══════════ --}}

        {{-- File Leave / Leave Request Modal --}}
        <div x-show="showFileLeave" class="modal-overlay" x-cloak @click.self="showFileLeave = false">
            <div class="modal-box">
                <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:22px;">
                    <div class="modal-title">Leave Request</div>
                    <button @click="showFileLeave = false" class="close-btn">
                        <svg style="width:14px;height:14px;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>

                <div style="margin-bottom:16px;">
                    <label class="form-label">Leave Type</label>
                    <select class="form-input" style="appearance:none;background-image:url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='10' height='6' fill='none'%3E%3Cpath d='M1 1l4 4 4-4' stroke='%236b7280' stroke-width='1.5' stroke-linecap='round'/%3E%3C/svg%3E");background-repeat:no-repeat;background-position:right 12px center;width:100%;">
                        <option value="">Choose leave type</option>
                        <option>Vacation Leave</option>
                        <option>Sick Leave</option>
                        <option>Leave Without Pay</option>
                        <option>Maternity Leave</option>
                        <option>Paternity Leave</option>
                        <option>Solo Parent Leave</option>
                    </select>
                </div>

                <div class="form-row" style="margin-bottom:16px;">
                    <div>
                        <label class="form-label">Start Date</label>
                        <input type="date" class="form-input" placeholder="MM/DD/YYYY">
                    </div>
                    <div>
                        <label class="form-label">End Date</label>
                        <input type="date" class="form-input" placeholder="MM/DD/YYYY">
                    </div>
                </div>

                <div style="margin-bottom:16px;">
                    <label class="form-label">Reason/Remarks</label>
                    <input type="text" class="form-input" placeholder="Enter brief description of your leave reason">
                </div>

                <div style="margin-bottom:16px;">
                    <label class="form-label">Supporting Document</label>
                    <label style="display:flex;flex-direction:column;align-items:center;justify-content:center;gap:8px;border:2px dashed #d1d5db;border-radius:10px;padding:28px 20px;background:#f9fafb;cursor:pointer;">
                        <svg style="width:28px;height:28px;color:#9ca3af;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 13h6m-3-3v6m5 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                        <div style="font-size:13px;font-weight:600;color:#374151;">Choose a file to upload</div>
                        <div style="font-size:12px;color:#9ca3af;">PDF or DOCX file size no more than 10MB</div>
                        <input type="file" accept=".pdf,.docx" style="display:none;">
                    </label>
                </div>

                <div class="modal-actions">
                    <button class="btn-cancel" @click="showFileLeave = false">Cancel</button>
                    <button class="btn-save">Submit</button>
                </div>
            </div>
        </div>

        {{-- Add Leave Type Modal --}}
        <div x-show="showAddLeaveType" class="modal-overlay" x-cloak @click.self="showAddLeaveType = false">
            <div class="modal-box">
                <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:22px;">
                    <div class="modal-title">Add Leave Type</div>
                    <button @click="showAddLeaveType = false" class="close-btn">
                        <svg style="width:14px;height:14px;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>

                <div class="form-row" style="margin-bottom:16px;">
                    <div>
                        <label class="form-label">Leave Type</label>
                        <input type="text" class="form-input" placeholder="Enter leave type name">
                    </div>
                    <div>
                        <label class="form-label">Code</label>
                        <input type="text" class="form-input" placeholder="Enter leave type code">
                    </div>
                </div>

                <div style="margin-bottom:16px;">
                    <label class="form-label">Days Entitled</label>
                    <input type="number" class="form-input" placeholder="Enter how many days entitled" min="1" max="365">
                </div>

                <div class="form-row" style="margin-bottom:16px;">
                    <div>
                        <label class="form-label">Pay</label>
                        <div style="display:flex;gap:16px;margin-top:8px;">
                            <label style="display:flex;align-items:center;gap:6px;font-size:13px;color:#374151;cursor:pointer;">
                                <input type="checkbox" style="width:15px;height:15px;accent-color:#3b82f6;"> Paid
                            </label>
                            <label style="display:flex;align-items:center;gap:6px;font-size:13px;color:#374151;cursor:pointer;">
                                <input type="checkbox" style="width:15px;height:15px;accent-color:#3b82f6;"> Unpaid
                            </label>
                        </div>
                    </div>
                    <div>
                        <label class="form-label">Document</label>
                        <div style="display:flex;gap:16px;margin-top:8px;">
                            <label style="display:flex;align-items:center;gap:6px;font-size:13px;color:#374151;cursor:pointer;">
                                <input type="checkbox" style="width:15px;height:15px;accent-color:#3b82f6;"> Required
                            </label>
                            <label style="display:flex;align-items:center;gap:6px;font-size:13px;color:#374151;cursor:pointer;">
                                <input type="checkbox" style="width:15px;height:15px;accent-color:#3b82f6;"> Not Required
                            </label>
                        </div>
                    </div>
                </div>

                <div style="margin-bottom:16px;">
                    <label class="form-label">Applicable To</label>
                    <input type="text" class="form-input" placeholder="Enter who the leave type is applicable to">
                </div>

                <div class="modal-actions">
                    <button class="btn-cancel" @click="showAddLeaveType = false">Cancel</button>
                    <button class="btn-save">Submit</button>
                </div>
            </div>
        </div>

        {{-- View Leave Details Modal --}}
        <div x-show="showLeaveDetails" class="modal-overlay" x-cloak @click.self="showLeaveDetails = false">
            <div class="modal-box">
                <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:22px;">
                    <div class="modal-title">Leave Details</div>
                    <button @click="showLeaveDetails = false" class="close-btn">
                        <svg style="width:14px;height:14px;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>

                <div style="margin-bottom:16px;">
                    <label class="form-label">Leave Type</label>
                    <div class="form-input" style="background:#f9fafb;color:#6b7280;cursor:default;" x-text="selectedLeave.leave_type || 'Vacation Leave (VL)'"></div>
                </div>

                <div class="form-row" style="margin-bottom:16px;">
                    <div>
                        <label class="form-label">Start Date</label>
                        <div class="form-input" style="background:#f9fafb;color:#6b7280;cursor:default;" x-text="selectedLeave.date_from || '03/10/2026'"></div>
                    </div>
                    <div>
                        <label class="form-label">End Date</label>
                        <div class="form-input" style="background:#f9fafb;color:#6b7280;cursor:default;" x-text="selectedLeave.date_to || '03/12/2026'"></div>
                    </div>
                </div>

                <div style="margin-bottom:16px;">
                    <label class="form-label">Reason/Remarks</label>
                    <div class="form-input" style="background:#f9fafb;color:#6b7280;cursor:default;" x-text="selectedLeave.reason || 'Family Trip'"></div>
                </div>

                <div style="margin-bottom:16px;">
                    <label class="form-label">Progress</label>
                    <div class="form-input" style="background:#f9fafb;color:#6b7280;cursor:default;" x-text="selectedLeave.status || 'Pending Approval'"></div>
                </div>

                <div class="modal-actions">
                    <button class="btn-cancel" @click="showLeaveDetails = false">Cancel Request</button>
                    <button class="btn-save" @click="showLeaveDetails = false">Close</button>
                </div>
            </div>
        </div>

    </div>
</div>
</body>
</html>
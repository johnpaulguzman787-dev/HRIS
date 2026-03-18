@php
    $currentRoute = request()->route()->getName();
    $activeTab    = request('tab', 'weekly');

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

    $attendanceRoutes = ['supervisor.attendance.reports','supervisor.attendance.employee','supervisor.attendance.shift','supervisor.attendance.leave','supervisor.shift.scheduling'];
    $employeeRoutes   = ['supervisor.employees.directory'];
    $payrollRoutes    = ['supervisor.payroll','supervisor.payslips','supervisor.contributions'];
    $requestRoutes    = ['supervisor.requests.pending','supervisor.requests.approved'];

    // ── Weekly schedule data ───────────────────────────────────────────────
    $selectedWeekStart = request('week_start')
        ? \Carbon\Carbon::parse(request('week_start'))->startOfWeek(\Carbon\Carbon::MONDAY)
        : \Carbon\Carbon::now()->startOfWeek(\Carbon\Carbon::MONDAY);
    $selectedWeekEnd = $selectedWeekStart->copy()->endOfWeek(\Carbon\Carbon::SUNDAY);
    $weekLabel = $selectedWeekStart->format('F j') . '–' . $selectedWeekEnd->format('j');

    // Days of the week
    $weekDays = [];
    for ($i = 0; $i < 7; $i++) {
        $weekDays[] = $selectedWeekStart->copy()->addDays($i);
    }

    // ── Holiday Calendar ───────────────────────────────────────────────────
    $currentYear = request('year', now()->year);
@endphp

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Shift Scheduling — MEDISOURCE</title>
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
            --muted: #6b7280;
            --border: #e5e7eb;
        }

        /* ── SIDEBAR ── */
        .nav-item    { transition: background 0.15s, color 0.15s; }
        .chevron-icon { transition: transform 0.25s cubic-bezier(0.4,0,0.2,1); }

        /* ── TAB NAV ── */
        .tab-nav {
            display: flex; gap: 0; border-bottom: 2px solid var(--border);
            margin-bottom: 24px;
        }
        .tab-btn {
            padding: 10px 24px; font-size: 14px; font-weight: 500;
            color: var(--muted); border: none; background: none;
            cursor: pointer; font-family: inherit;
            border-bottom: 3px solid transparent; margin-bottom: -2px;
            transition: color .15s, border-color .15s;
            text-decoration: none; display: inline-block;
        }
        .tab-btn:hover { color: #374151; }
        .tab-btn.active { color: var(--blue); border-bottom-color: var(--blue); font-weight: 600; }

        /* ── ACTION BUTTONS ── */
        .btn-outline {
            display: inline-flex; align-items: center; gap: 6px;
            padding: 8px 16px; border-radius: 8px; font-size: 13px;
            font-weight: 600; font-family: inherit; cursor: pointer;
            border: 1.5px solid #bfdbfe; background: var(--blue-light);
            color: var(--blue-dark); transition: all .15s; text-decoration: none;
        }
        .btn-outline:hover { background: #dbeafe; border-color: #93c5fd; }
        .btn-outline svg { width: 14px; height: 14px; }

        .btn-primary {
            display: inline-flex; align-items: center; gap: 6px;
            padding: 8px 16px; border-radius: 8px; font-size: 13px;
            font-weight: 600; font-family: inherit; cursor: pointer;
            border: none; background: var(--blue); color: #fff;
            transition: background .15s; text-decoration: none;
        }
        .btn-primary:hover { background: var(--blue-dark); }
        .btn-primary svg { width: 14px; height: 14px; }

        /* ── TOOLBAR ── */
        .toolbar {
            display: flex; align-items: center; justify-content: space-between;
            margin-bottom: 20px; gap: 12px; flex-wrap: wrap;
        }
        .toolbar-left  { display: flex; align-items: center; gap: 10px; }
        .toolbar-right { display: flex; align-items: center; gap: 10px; }

        .search-box {
            display: flex; align-items: center; gap: 7px;
            background: #f9fafb; border: 1px solid var(--border);
            border-radius: 8px; padding: 7px 13px;
        }
        .search-box svg { color: var(--muted); width: 14px; height: 14px; flex-shrink: 0; }
        .search-box input {
            border: none; background: transparent; outline: none;
            font-size: 13px; color: #111827; width: 180px; font-family: inherit;
        }
        .search-box input::placeholder { color: var(--muted); }

        .dept-select {
            appearance: none; background: #f9fafb; border: 1px solid var(--border);
            border-radius: 8px; padding: 7px 28px 7px 12px; font-size: 13px;
            color: #111827; cursor: pointer; outline: none; font-family: inherit;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='10' height='6' fill='none'%3E%3Cpath d='M1 1l4 4 4-4' stroke='%236b7280' stroke-width='1.5' stroke-linecap='round'/%3E%3C/svg%3E");
            background-repeat: no-repeat; background-position: right 10px center;
        }

        /* ── WEEK NAV ── */
        .week-nav {
            display: flex; align-items: center; gap: 8px;
        }
        .week-label {
            font-size: 20px; font-weight: 800; color: #111827; min-width: 160px;
        }
        .week-btn {
            display: inline-flex; align-items: center; gap: 5px;
            padding: 6px 12px; border-radius: 7px; font-size: 12.5px;
            font-weight: 500; font-family: inherit; cursor: pointer;
            border: 1px solid var(--border); background: #fff; color: #374151;
            transition: background .15s; text-decoration: none;
        }
        .week-btn:hover { background: #f9fafb; }
        .week-btn svg { width: 12px; height: 12px; }

        /* ── WEEKLY SCHEDULE TABLE ── */
        .schedule-card {
            background: #fff; border-radius: 14px; border: 1px solid var(--border);
            overflow: hidden; box-shadow: 0 1px 8px rgba(0,0,0,.04);
        }
        .schedule-table { width: 100%; border-collapse: collapse; }
        .schedule-table th {
            padding: 10px 12px; font-size: 11px; font-weight: 700;
            color: var(--muted); text-align: center; border-bottom: 1px solid var(--border);
            background: #f9fafb; letter-spacing: .5px; text-transform: uppercase;
            white-space: nowrap;
        }
        .schedule-table th.emp-col { text-align: left; min-width: 160px; }
        .schedule-table td {
            padding: 10px 12px; border-bottom: 1px solid #f3f4f6;
            vertical-align: middle; text-align: center;
        }
        .schedule-table td.emp-cell { text-align: left; }
        .schedule-table tr:last-child td { border-bottom: none; }
        .schedule-table tbody tr:hover { background: #fafafa; }

        .emp-info .emp-name { font-size: 13px; font-weight: 600; color: #111827; }
        .emp-info .emp-dept { font-size: 11.5px; color: var(--muted); }

        /* ── SHIFT PILLS ── */
        .shift-cell { display: flex; flex-direction: column; gap: 4px; align-items: center; }

        .pill-setup {
            display: inline-block; padding: 3px 10px; border-radius: 5px;
            font-size: 11px; font-weight: 600; min-width: 54px; text-align: center;
        }
        .pill-wfh    { background: #dbeafe; color: #1d4ed8; }
        .pill-office { background: #dcfce7; color: #15803d; }

        .pill-shift {
            display: inline-block; padding: 3px 10px; border-radius: 5px;
            font-size: 11px; font-weight: 600; min-width: 54px; text-align: center;
        }
        .pill-day   { background: #fef9c3; color: #a16207; }
        .pill-night { background: #fce7f3; color: #be185d; }
        .pill-mid   { background: #ede9fe; color: #6d28d9; }

        .pill-leave  {
            display: inline-block; padding: 6px 10px; border-radius: 5px;
            font-size: 11.5px; font-weight: 600; min-width: 54px; text-align: center;
            background: #fce7f3; color: #be185d;
        }
        .day-off-text { font-size: 12px; color: #9ca3af; font-weight: 500; }

        /* ── SHIFT TYPES TABLE ── */
        .data-table { width: 100%; border-collapse: collapse; }
        .data-table thead tr { background: #f9fafb; }
        .data-table th {
            padding: 11px 16px; font-size: 11.5px; font-weight: 600;
            color: var(--muted); text-align: left; border-bottom: 1px solid var(--border);
            white-space: nowrap; letter-spacing: .4px; text-transform: uppercase;
        }
        .data-table td {
            padding: 14px 16px; font-size: 13px; color: #111827;
            border-bottom: 1px solid #f3f4f6; vertical-align: middle;
        }
        .data-table tr:last-child td { border-bottom: none; }
        .data-table tbody tr:hover { background: #fafafa; }

        .night-diff-badge {
            display: inline-block; padding: 3px 10px; border-radius: 20px;
            font-size: 12px; font-weight: 600;
        }
        .nd-none { background: #f3f4f6; color: #6b7280; }
        .nd-10   { background: #dbeafe; color: #1d4ed8; }
        .nd-20   { background: #ede9fe; color: #6d28d9; }

        .edit-btn {
            display: inline-flex; align-items: center; gap: 4px;
            padding: 5px 14px; border-radius: 7px; font-size: 12px;
            font-weight: 600; font-family: inherit; cursor: pointer;
            border: 1.5px solid #bfdbfe; background: var(--blue-light);
            color: var(--blue-dark); transition: all .15s;
        }
        .edit-btn:hover { background: #dbeafe; }

        /* ── HOLIDAY CALENDAR ── */
        .holiday-stat-grid {
            display: grid; grid-template-columns: repeat(3, 1fr);
            gap: 16px; margin-bottom: 24px;
        }
        .holiday-stat-card {
            border-radius: 12px; padding: 20px 22px;
            border: 1px solid transparent;
        }
        .hsc-label { font-size: 10.5px; font-weight: 700; letter-spacing: .7px; text-transform: uppercase; margin-bottom: 8px; }
        .hsc-value { font-size: 36px; font-weight: 800; line-height: 1; }
        .hsc-sub   { font-size: 11.5px; font-weight: 500; margin-top: 6px; opacity: .7; }

        .hsc-regular { background: #dbeafe; border-color: #bfdbfe; }
        .hsc-regular .hsc-label { color: #1d4ed8; }
        .hsc-regular .hsc-value { color: #1e40af; }
        .hsc-regular .hsc-sub   { color: #1e40af; }

        .hsc-special { background: #dcfce7; border-color: #bbf7d0; }
        .hsc-special .hsc-label { color: #15803d; }
        .hsc-special .hsc-value { color: #166534; }
        .hsc-special .hsc-sub   { color: #166534; }

        .hsc-local   { background: #fce7f3; border-color: #fbcfe8; }
        .hsc-local   .hsc-label { color: #be185d; }
        .hsc-local   .hsc-value { color: #9d174d; }
        .hsc-local   .hsc-sub   { color: #9d174d; }

        .type-badge {
            display: inline-block; padding: 3px 12px; border-radius: 20px;
            font-size: 12px; font-weight: 600;
        }
        .tb-regular { background: #dbeafe; color: #1d4ed8; }
        .tb-special { background: #dcfce7; color: #15803d; }
        .tb-local   { background: #fce7f3; color: #be185d; }

        /* ── MODAL ── */
        .modal-overlay {
            position: fixed; inset: 0; background: rgba(0,0,0,.4);
            display: flex; align-items: center; justify-content: center; z-index: 999;
        }
        .modal-box {
            background: #fff; border-radius: 16px; padding: 28px 32px;
            width: 480px; max-width: 95vw; box-shadow: 0 20px 60px rgba(0,0,0,.15);
        }
        .modal-title { font-size: 17px; font-weight: 800; color: #111827; margin-bottom: 20px; }
        .form-label { font-size: 12px; font-weight: 600; color: #374151; text-transform: uppercase; letter-spacing: .5px; margin-bottom: 5px; display: block; }
        .form-input {
            width: 100%; border: 1.5px solid var(--border); border-radius: 8px;
            padding: 9px 13px; font-size: 13px; font-family: inherit; outline: none;
            color: #111827; transition: border-color .15s;
        }
        .form-input:focus { border-color: var(--blue); }
        .form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 14px; }
        .modal-actions { display: flex; justify-content: flex-end; gap: 10px; margin-top: 22px; }
        .btn-cancel {
            padding: 8px 18px; border-radius: 8px; font-size: 13px;
            font-weight: 600; font-family: inherit; cursor: pointer;
            border: 1.5px solid var(--border); background: #fff; color: #374151;
        }
        .btn-save {
            padding: 8px 20px; border-radius: 8px; font-size: 13px;
            font-weight: 600; font-family: inherit; cursor: pointer;
            border: none; background: var(--blue); color: #fff;
        }

        /* ── TABLE CARD ── */
        .table-card {
            background: #fff; border-radius: 14px; border: 1px solid var(--border);
            overflow: hidden; box-shadow: 0 1px 8px rgba(0,0,0,.04);
        }
        .table-toolbar {
            display: flex; align-items: center; justify-content: space-between;
            padding: 16px 20px; border-bottom: 1px solid var(--border); gap: 12px; flex-wrap: wrap;
        }
    </style>
</head>
<body class="bg-gray-50">

{{-- ══════════ HR SIDEBAR (inline) ══════════ --}}
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
    <div class="px-4 py-4 border-b border-gray-100"
         :class="sidebarCollapsed ? 'flex justify-center' : 'flex items-center space-x-3'">
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
        <p x-show="!sidebarCollapsed" class="text-xs text-gray-400 font-semibold px-3 py-2 uppercase tracking-widest">Main Menu</p>

        <!-- Dashboard -->
        <a href="{{ route('supervisor.dashboard') }}"
           class="nav-item flex items-center space-x-3 px-3 py-2.5 rounded-lg {{ $currentRoute === 'supervisor.dashboard' ? 'text-white' : 'text-gray-600 hover:bg-gray-50' }}"
           style="{{ $currentRoute === 'supervisor.dashboard' ? 'background:#3b82f6;' : '' }}">
            <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"/>
            </svg>
            <span x-show="!sidebarCollapsed" class="text-sm font-medium whitespace-nowrap">Dashboard</span>
        </a>

        <!-- Employees -->
        <div>
            <button @click="employeesOpen = !employeesOpen"
                    class="nav-item w-full flex items-center justify-between px-3 py-2.5 rounded-lg {{ in_array($currentRoute, $employeeRoutes) ? 'text-blue-600 bg-blue-50' : 'text-gray-600 hover:bg-gray-50' }}">
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
                <a href="{{ route('supervisor.employees.directory') }}" class="block px-3 py-2 text-sm rounded-lg {{ $currentRoute === 'supervisor.employees.directory' ? 'text-blue-600 font-semibold bg-blue-50' : 'text-gray-500 hover:text-gray-800 hover:bg-gray-50' }}">Employee Directory</a>
                <a href="{{ route('supervisor.employees.profile') }}"   class="block px-3 py-2 text-sm rounded-lg {{ $currentRoute === 'supervisor.employees.profile'   ? 'text-blue-600 font-semibold bg-blue-50' : 'text-gray-500 hover:text-gray-800 hover:bg-gray-50' }}">Employee Profile</a>
            </div>
        </div>

        <!-- Time & Attendance -->
        <div>
            <button @click="attendanceOpen = !attendanceOpen"
                    class="nav-item w-full flex items-center justify-between px-3 py-2.5 rounded-lg {{ in_array($currentRoute, $attendanceRoutes) ? 'text-blue-600 bg-blue-50' : 'text-gray-600 hover:bg-gray-50' }}">
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
                   class="flex items-center px-3 py-2 text-sm rounded-lg {{ $currentRoute === 'supervisor.attendance.reports' ? 'text-blue-600 font-semibold bg-blue-50' : 'text-gray-500 hover:text-gray-800 hover:bg-gray-50' }}">
                    @if($currentRoute === 'supervisor.attendance.reports')
                        <span class="w-2 h-2 rounded-full mr-2.5 flex-shrink-0" style="background:#3b82f6; animation:pulseDot 2s ease-in-out infinite;"></span>
                    @endif
                    My Attendance
                </a>
                <a href="{{ route('supervisor.attendance.employee') }}" class="block px-3 py-2 text-sm rounded-lg {{ $currentRoute === 'supervisor.attendance.employee' ? 'text-blue-600 font-semibold bg-blue-50' : 'text-gray-500 hover:text-gray-800 hover:bg-gray-50' }}">Employee Attendance</a>
                <a href="{{ route('supervisor.shift.scheduling') }}"   class="block px-3 py-2 text-sm rounded-lg {{ $currentRoute === 'supervisor.shift.scheduling'   ? 'text-blue-600 font-semibold bg-blue-50' : 'text-gray-500 hover:text-gray-800 hover:bg-gray-50' }}">Shift Scheduling</a>
                <a href="#" class="block px-3 py-2 text-sm text-gray-500 hover:text-gray-800 hover:bg-gray-50 rounded-lg">Leave Management</a>
            </div>
        </div>

        <!-- Payroll -->
        <div>
            <button @click="payrollOpen = !payrollOpen"
                    class="nav-item w-full flex items-center justify-between px-3 py-2.5 rounded-lg {{ in_array($currentRoute, $payrollRoutes) ? 'text-blue-600 bg-blue-50' : 'text-gray-600 hover:bg-gray-50' }}">
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
                <a href="#" class="block px-3 py-2 text-sm text-gray-500 hover:text-gray-800 hover:bg-gray-50 rounded-lg">Payroll</a>
                <a href="#" class="block px-3 py-2 text-sm text-gray-500 hover:text-gray-800 hover:bg-gray-50 rounded-lg">Payslips</a>
                <a href="#" class="block px-3 py-2 text-sm text-gray-500 hover:text-gray-800 hover:bg-gray-50 rounded-lg">Govt. Contributions</a>
            </div>
        </div>

        <!-- Requests & Approval -->
        <div>
            <button @click="requestsOpen = !requestsOpen"
                    class="nav-item w-full flex items-center justify-between px-3 py-2.5 rounded-lg {{ in_array($currentRoute, $requestRoutes) ? 'text-blue-600 bg-blue-50' : 'text-gray-600 hover:bg-gray-50' }}">
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
                <a href="#" class="block px-3 py-2 text-sm text-gray-500 hover:text-gray-800 hover:bg-gray-50 rounded-lg">Pending Requests</a>
                <a href="#" class="block px-3 py-2 text-sm text-gray-500 hover:text-gray-800 hover:bg-gray-50 rounded-lg">Approved Logs</a>
            </div>
        </div>

        <!-- Others -->
        <div class="pt-3 mt-2 border-t border-gray-100">
            <p x-show="!sidebarCollapsed" class="text-xs text-gray-400 font-semibold px-3 py-2 uppercase tracking-widest">Others</p>
            <a href="#" class="nav-item flex items-center space-x-3 px-3 py-2.5 rounded-lg text-gray-600 hover:bg-gray-50">
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

    {{-- Blue Header --}}
    <header class="bg-gradient-to-br from-blue-500 to-blue-700 sticky top-0 z-10 shadow-lg mt-4 mx-4 rounded-2xl overflow-hidden">
        <div class="flex items-center justify-between px-8 py-4">
            <h1 class="text-white font-bold text-xl">Shift Scheduling</h1>
            <button class="w-9 h-9 rounded-full flex items-center justify-center transition-all hover:bg-white/20">
                <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>
                </svg>
            </button>
        </div>
    </header>

    <!-- Page Content -->
    <div style="padding:24px 32px;" x-data="{ showEditShiftModal: false, showAssignShiftModal: false, showAddShiftTypeModal: false, showAddHolidayModal: false }">

        <!-- Tab Nav + Action Buttons -->
        <div style="display:flex; align-items:flex-end; justify-content:space-between; margin-bottom:4px;">
            <div class="tab-nav" style="margin-bottom:0; flex:1;">
                <a href="{{ route('supervisor.shift.scheduling', ['tab' => 'weekly']) }}"
                   class="tab-btn {{ $activeTab === 'weekly' ? 'active' : '' }}">Weekly Schedule</a>
                <a href="{{ route('supervisor.shift.scheduling', ['tab' => 'shift-types']) }}"
                   class="tab-btn {{ $activeTab === 'shift-types' ? 'active' : '' }}">Shift Types</a>
                <a href="{{ route('supervisor.shift.scheduling', ['tab' => 'holidays']) }}"
                   class="tab-btn {{ $activeTab === 'holidays' ? 'active' : '' }}">Holiday Calendar</a>
            </div>
            <div style="display:flex; gap:10px; margin-bottom:2px; padding-left:20px;">
                <button class="btn-outline" @click="showEditShiftModal = true">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                    Edit Shift
                </button>
                <button class="btn-primary" @click="showAssignShiftModal = true">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                    Assign Shift
                </button>
            </div>
        </div>
        <div style="border-bottom:2px solid #e5e7eb; margin-bottom:24px;"></div>

        {{-- ══════════ TAB: WEEKLY SCHEDULE ══════════ --}}
        @if($activeTab === 'weekly')

        <div class="toolbar">
            <div class="toolbar-left">
                <div class="week-nav">
                    <a href="{{ route('supervisor.shift.scheduling', ['tab' => 'weekly', 'week_start' => $selectedWeekStart->copy()->subWeek()->format('Y-m-d')]) }}"
                       class="week-btn">
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                        Previous
                    </a>
                    <span class="week-label">{{ $selectedWeekStart->format('F j') }}–{{ $selectedWeekEnd->format('j') }}</span>
                    <a href="{{ route('supervisor.shift.scheduling', ['tab' => 'weekly', 'week_start' => $selectedWeekStart->copy()->addWeek()->format('Y-m-d')]) }}"
                       class="week-btn">
                        Next
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                    </a>
                </div>
            </div>
            <div class="toolbar-right">
                <div class="search-box">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                    <input type="text" placeholder="Search employee…">
                </div>
                <select class="dept-select">
                    <option>All Departments</option>
                    @foreach($departments ?? [] as $dept)
                        <option value="{{ $dept->id }}">{{ $dept->name }}</option>
                    @endforeach
                </select>
            </div>
        </div>

        <div class="schedule-card">
            <div style="overflow-x:auto;">
                <table class="schedule-table">
                    <thead>
                        <tr>
                            <th class="emp-col">Employee</th>
                            @foreach($weekDays as $day)
                            <th style="{{ $day->isWeekend() ? 'background:#f3f4f6;' : '' }}">
                                <div>{{ strtoupper($day->format('D')) }}</div>
                                <div style="font-size:12px; font-weight:700; color:#111827; letter-spacing:0;">{{ $day->format('M j') }}</div>
                            </th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($scheduleRecords ?? [] as $rec)
                        <tr>
                            <td class="emp-cell">
                                <div class="emp-info">
                                    <div class="emp-name">{{ trim(($rec->employee->fname ?? '') . ' ' . ($rec->employee->lname ?? '')) }}</div>
                                    <div class="emp-dept">{{ $rec->employee->department->name ?? '—' }}</div>
                                </div>
                            </td>
                            @foreach($weekDays as $day)
                            @php
                                $dayKey   = $day->format('Y-m-d');
                                $dayShift = $rec->shifts[$dayKey] ?? null;
                                $isWknd   = $day->isWeekend();
                            @endphp
                            <td style="{{ $isWknd ? 'background:#f9fafb;' : '' }}">
                                @if($isWknd)
                                    <span class="day-off-text">Day Off</span>
                                @elseif($dayShift)
                                    <div class="shift-cell">
                                        <span class="pill-setup {{ strtolower($dayShift->setup ?? 'wfh') === 'office' ? 'pill-office' : 'pill-wfh' }}">
                                            {{ $dayShift->setup ?? 'WFH' }}
                                        </span>
                                        @php $st = strtolower($dayShift->type ?? 'day'); @endphp
                                        <span class="pill-shift {{ $st === 'night' ? 'pill-night' : ($st === 'mid' ? 'pill-mid' : 'pill-day') }}">
                                            {{ ucfirst($dayShift->type ?? 'Day') }}
                                        </span>
                                    </div>
                                @else
                                    <span class="day-off-text">—</span>
                                @endif
                            </td>
                            @endforeach
                        </tr>
                        @empty
                        {{-- Placeholder rows --}}
                        @php
                            $demoEmployees = [
                                ['name' => 'Juan Dela Cruz',  'dept' => 'IT', 'shifts' => ['wfh','wfh','wfh','wfh','wfh',null,null], 'types' => ['Day','Day','Day','Day','Day',null,null]],
                                ['name' => 'Jan Dela Cruz',   'dept' => 'IT', 'shifts' => ['wfh','office','wfh','office','wfh',null,null], 'types' => ['Day','Day','Day','Day','Day',null,null]],
                                ['name' => 'Jane Dela Cruz',  'dept' => 'IT', 'shifts' => ['office','wfh','wfh','wfh','office',null,null], 'types' => ['Night','Night','Night','Night','Night',null,null]],
                                ['name' => 'Jam Dela Cruz',   'dept' => 'IT', 'shifts' => ['office','office','office','office','office',null,null], 'types' => ['Night','Night','Night','Night','Night',null,null]],
                                ['name' => 'Jimmy Dela Cruz', 'dept' => 'IT', 'shifts' => ['leave','leave','leave','leave','leave',null,null], 'types' => [null,null,null,null,null,null,null]],
                                ['name' => 'New Dela Cruz',   'dept' => 'IT', 'shifts' => [null,null,null,null,null,null,null], 'types' => [null,null,null,null,null,null,null]],
                            ];
                        @endphp
                        @foreach($demoEmployees as $demo)
                        <tr>
                            <td class="emp-cell">
                                <div class="emp-info">
                                    <div class="emp-name">{{ $demo['name'] }}</div>
                                    <div class="emp-dept">{{ $demo['dept'] }}</div>
                                </div>
                            </td>
                            @foreach($weekDays as $idx => $day)
                            @php
                                $setup = $demo['shifts'][$idx] ?? null;
                                $type  = $demo['types'][$idx]  ?? null;
                                $isWknd = $day->isWeekend();
                            @endphp
                            <td style="{{ $isWknd ? 'background:#f9fafb;' : '' }}">
                                @if($isWknd)
                                    <span class="day-off-text">Day Off</span>
                                @elseif($setup === 'leave')
                                    <span class="pill-leave">Leave</span>
                                @elseif($setup)
                                    <div class="shift-cell">
                                        <span class="pill-setup {{ $setup === 'office' ? 'pill-office' : 'pill-wfh' }}">{{ strtoupper($setup) }}</span>
                                        @if($type)
                                        <span class="pill-shift {{ strtolower($type) === 'night' ? 'pill-night' : ($type === 'Mid' ? 'pill-mid' : 'pill-day') }}">{{ $type }}</span>
                                        @endif
                                    </div>
                                @else
                                    <span class="day-off-text">—</span>
                                @endif
                            </td>
                            @endforeach
                        </tr>
                        @endforeach
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        {{-- ══════════ TAB: SHIFT TYPES ══════════ --}}
        @elseif($activeTab === 'shift-types')

        <div class="table-card">
            <div class="table-toolbar">
                <div class="toolbar-left">
                    <div class="search-box">
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                        <input type="text" placeholder="Search…">
                    </div>
                </div>
                <div class="toolbar-right">
                    <select class="dept-select">
                        <option>All Departments</option>
                        @foreach($departments ?? [] as $dept)
                            <option value="{{ $dept->id }}">{{ $dept->name }}</option>
                        @endforeach
                    </select>
                    <button class="btn-primary" @click="showAddShiftTypeModal = true">
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                        Add Shift Type
                    </button>
                </div>
            </div>
            <div style="overflow-x:auto;">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Shift Name</th>
                            <th>Time In</th>
                            <th>Break</th>
                            <th>Time Out</th>
                            <th>Work Hours</th>
                            <th>Night Diff</th>
                            <th>Assigned</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($shiftTypes ?? [] as $shift)
                        <tr>
                            <td style="font-weight:600;">{{ $shift->name }}</td>
                            <td>{{ \Carbon\Carbon::parse($shift->time_in)->format('g:i A') }}</td>
                            <td>{{ \Carbon\Carbon::parse($shift->break_start)->format('g:i A') }} – {{ \Carbon\Carbon::parse($shift->break_end)->format('g:i A') }}</td>
                            <td>{{ \Carbon\Carbon::parse($shift->time_out)->format('g:i A') }}</td>
                            <td>{{ $shift->work_hours }}h</td>
                            <td>
                                @php $nd = $shift->night_diff ?? 'None'; @endphp
                                <span class="night-diff-badge {{ $nd === 'None' ? 'nd-none' : ($nd === '10%' ? 'nd-10' : 'nd-20') }}">{{ $nd }}</span>
                            </td>
                            <td>{{ $shift->assigned ?? 0 }}</td>
                            <td><button class="edit-btn">Edit</button></td>
                        </tr>
                        @empty
                        @foreach([
                            ['Day Shift',   '7:00 AM',  '12:00 PM - 1:00 PM', '4:00 PM',  '8h', 'None', 42],
                            ['Mid Shift',   '2:00 PM',  '6:00 PM - 7:00 PM',  '11:00 PM', '8h', '10%',  12],
                            ['Night Shift', '10:00 PM', '2:00 AM - 3:00 AM',  '6:00 PM',  '8h', '20%',  48],
                        ] as $row)
                        <tr>
                            <td style="font-weight:600;">{{ $row[0] }}</td>
                            <td>{{ $row[1] }}</td>
                            <td>{{ $row[2] }}</td>
                            <td>{{ $row[3] }}</td>
                            <td>{{ $row[4] }}</td>
                            <td><span class="night-diff-badge {{ $row[5] === 'None' ? 'nd-none' : ($row[5] === '10%' ? 'nd-10' : 'nd-20') }}">{{ $row[5] }}</span></td>
                            <td>{{ $row[6] }}</td>
                            <td><button class="edit-btn">Edit</button></td>
                        </tr>
                        @endforeach
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        {{-- ══════════ TAB: HOLIDAY CALENDAR ══════════ --}}
        @elseif($activeTab === 'holidays')

        {{-- Holiday Stats --}}
        <div class="holiday-stat-grid">
            <div class="holiday-stat-card hsc-regular">
                <div class="hsc-label">Regular Holidays</div>
                <div class="hsc-value">{{ $regularHolidays ?? 12 }}</div>
                <div class="hsc-sub">200% pay rate</div>
            </div>
            <div class="holiday-stat-card hsc-special">
                <div class="hsc-label">Special Non-Working</div>
                <div class="hsc-value">{{ $specialHolidays ?? 3 }}</div>
                <div class="hsc-sub">130% pay rate</div>
            </div>
            <div class="holiday-stat-card hsc-local">
                <div class="hsc-label">Local Holidays</div>
                <div class="hsc-value">{{ $localHolidays ?? 2 }}</div>
                <div class="hsc-sub">{{ $localRegion ?? 'Pangasinan' }}</div>
            </div>
        </div>

        <div class="table-card">
            <div class="table-toolbar">
                <div style="font-size:15px; font-weight:800; color:#111827;">{{ $currentYear }} HOLIDAYS</div>
                <div class="toolbar-right">
                    <div class="search-box">
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                        <input type="text" placeholder="Search holiday…">
                    </div>
                    <select class="dept-select">
                        <option>All Types</option>
                        <option>Regular</option>
                        <option>Special Non-Working</option>
                        <option>Local</option>
                    </select>
                    <button class="btn-primary" @click="showAddHolidayModal = true">
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                        Add Holiday
                    </button>
                </div>
            </div>
            <div style="overflow-x:auto;">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Holiday Name</th>
                            <th>Date</th>
                            <th>Day</th>
                            <th>Type</th>
                            <th>Pay Rate</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($holidays ?? [] as $holiday)
                        <tr>
                            <td style="font-weight:600;">{{ $holiday->name }}</td>
                            <td>{{ \Carbon\Carbon::parse($holiday->date)->format('M j') }}</td>
                            <td>{{ \Carbon\Carbon::parse($holiday->date)->format('D') }}</td>
                            <td>
                                @php $ht = strtolower($holiday->type ?? 'regular'); @endphp
                                <span class="type-badge {{ $ht === 'special' ? 'tb-special' : ($ht === 'local' ? 'tb-local' : 'tb-regular') }}">
                                    {{ ucfirst($holiday->type ?? 'Regular') }}
                                </span>
                            </td>
                            <td>{{ $holiday->pay_rate ?? '200%' }}</td>
                            <td><button class="edit-btn">Edit</button></td>
                        </tr>
                        @empty
                        @foreach([
                            ["New Year's Day",       'Jan 1',  'Thu', 'Regular', '200%'],
                            ["Araw ng Kagitingan",   'Apr 9',  'Thu', 'Regular', '200%'],
                            ["Maundy Thursday",      'Apr 17', 'Thu', 'Regular', '200%'],
                            ["Good Friday",          'Apr 18', 'Fri', 'Regular', '200%'],
                            ["Labor Day",            'May 1',  'Thu', 'Regular', '200%'],
                            ["Independence Day",     'Jun 12', 'Thu', 'Regular', '200%'],
                            ["National Heroes Day",  'Aug 25', 'Mon', 'Regular', '200%'],
                            ["Bonifacio Day",        'Nov 30', 'Sun', 'Regular', '200%'],
                            ["Christmas Day",        'Dec 25', 'Thu', 'Regular', '200%'],
                            ["Rizal Day",            'Dec 30', 'Tue', 'Regular', '200%'],
                            ["EDSA People Power",    'Feb 25', 'Tue', 'Special',  '130%'],
                            ["Black Saturday",       'Apr 19', 'Sat', 'Special',  '130%'],
                            ["All Saints Day",       'Nov 1',  'Sat', 'Special',  '130%'],
                            ["Feast of Immaculate",  'Dec 8',  'Mon', 'Special',  '130%'],
                            ["Linggo ng Wika",       'Aug 19', 'Tue', 'Local',    '130%'],
                            ["Founding Anniversary", 'Jul 15', 'Tue', 'Local',    '130%'],
                            ["Charter Day",          'Nov 10', 'Mon', 'Local',    '130%'],
                        ] as $row)
                        <tr>
                            <td style="font-weight:600;">{{ $row[0] }}</td>
                            <td>{{ $row[1] }}</td>
                            <td>{{ $row[2] }}</td>
                            <td>
                                @php $ht = strtolower($row[3]); @endphp
                                <span class="type-badge {{ $ht === 'special' ? 'tb-special' : ($ht === 'local' ? 'tb-local' : 'tb-regular') }}">{{ $row[3] }}</span>
                            </td>
                            <td>{{ $row[4] }}</td>
                            <td><button class="edit-btn">Edit</button></td>
                        </tr>
                        @endforeach
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @endif

        {{-- ══════════ MODALS ══════════ --}}

        {{-- Edit Shift Modal --}}
        <div x-show="showEditShiftModal" class="modal-overlay" x-cloak @click.self="showEditShiftModal = false">
            <div class="modal-box" style="width:520px;">
                <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:22px;">
                    <div class="modal-title" style="margin-bottom:0;">Edit Shift</div>
                    <button @click="showEditShiftModal = false" style="width:32px;height:32px;border:2px solid #374151;border-radius:50%;display:flex;align-items:center;justify-content:center;background:none;cursor:pointer;">
                        <svg style="width:14px;height:14px;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>

                <div style="margin-bottom:16px;">
                    <label class="form-label">Employee</label>
                    <select class="form-input dept-select" style="width:100%;">
                        <option value="">Juan  Dela Cruz</option>
                        @foreach($employees ?? [] as $emp)
                            <option value="{{ $emp->id }}">{{ trim($emp->fname . ' ' . $emp->lname) }}</option>
                        @endforeach
                    </select>
                </div>

                {{-- Current Shift Assignment info box --}}
                <div style="background:#f0f9ff;border-radius:8px;padding:14px 16px;margin-bottom:18px;">
                    <div style="font-size:12px;color:#6b7280;font-weight:500;margin-bottom:8px;">Current Shift Assignment</div>
                    <div style="display:grid;grid-template-columns:repeat(6,1fr);gap:8px;">
                        <div><div style="font-size:11px;color:#9ca3af;">Shift Type</div><div style="font-size:13px;font-weight:600;color:#111827;">Day</div></div>
                        <div><div style="font-size:11px;color:#9ca3af;">Work Setup</div><div style="font-size:13px;font-weight:600;color:#111827;">WFH</div></div>
                        <div><div style="font-size:11px;color:#9ca3af;">Day Off</div><div style="font-size:13px;font-weight:600;color:#111827;">Sat, Sun</div></div>
                        <div><div style="font-size:11px;color:#9ca3af;">Effective From</div><div style="font-size:13px;font-weight:600;color:#111827;">January 1, 2026</div></div>
                        <div><div style="font-size:11px;color:#9ca3af;">Effective Until</div><div style="font-size:13px;font-weight:600;color:#111827;">June 1, 2026</div></div>
                        <div><div style="font-size:11px;color:#9ca3af;">Schedule</div><div style="font-size:13px;font-weight:600;color:#111827;">7:00 AM - 4:00 PM</div></div>
                    </div>
                </div>

                <div style="margin-bottom:16px;">
                    <label class="form-label">Change Shift Type</label>
                    <select class="form-input dept-select" style="width:100%;">
                        <option>Day Shift</option>
                        <option>Mid Shift</option>
                        <option>Night Shift</option>
                    </select>
                </div>

                <div style="margin-bottom:16px;">
                    <label class="form-label">Change Work Setup</label>
                    <select class="form-input dept-select" style="width:100%;">
                        <option>WFH</option>
                        <option>Office</option>
                    </select>
                </div>

                <div class="form-row" style="margin-bottom:16px;">
                    <div>
                        <label class="form-label">Effective From</label>
                        <input type="date" class="form-input" value="2026-01-01">
                    </div>
                    <div>
                        <label class="form-label">Effective Until</label>
                        <input type="date" class="form-input" value="2026-06-01">
                    </div>
                </div>

                <div style="margin-bottom:16px;">
                    <label class="form-label">Day Off</label>
                    <div style="display:flex;gap:16px;margin-top:6px;flex-wrap:wrap;">
                        @foreach(['Mon','Tue','Wed','Thurs','Fri','Sat','Sun'] as $day)
                        <label style="display:flex;align-items:center;gap:5px;font-size:13px;color:#374151;cursor:pointer;">
                            <input type="checkbox" {{ in_array($day,['Sat','Sun']) ? 'checked' : '' }}
                                style="width:15px;height:15px;accent-color:#3b82f6;">
                            {{ $day }}
                        </label>
                        @endforeach
                    </div>
                </div>

                <div style="margin-bottom:16px;">
                    <label class="form-label">Reason For Change</label>
                    <input type="text" class="form-input" placeholder="e.g. Medical, Operational Leave">
                </div>

                <div class="modal-actions">
                    <button class="btn-cancel" @click="showEditShiftModal = false">Cancel</button>
                    <button class="btn-save">Save</button>
                </div>
            </div>
        </div>

        {{-- Assign Shift Modal --}}
        <div x-show="showAssignShiftModal" class="modal-overlay" x-cloak @click.self="showAssignShiftModal = false">
            <div class="modal-box" style="width:520px;">
                <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:22px;">
                    <div class="modal-title" style="margin-bottom:0;">Assign Shift</div>
                    <button @click="showAssignShiftModal = false" style="width:32px;height:32px;border:2px solid #374151;border-radius:50%;display:flex;align-items:center;justify-content:center;background:none;cursor:pointer;">
                        <svg style="width:14px;height:14px;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>

                <div style="margin-bottom:16px;">
                    <label class="form-label">Department</label>
                    <select class="form-input dept-select" style="width:100%;">
                        <option value="">Choose department</option>
                        @foreach($departments ?? [] as $dept)
                            <option value="{{ $dept->id }}">{{ $dept->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div style="margin-bottom:16px;">
                    <label class="form-label">Employee</label>
                    <select class="form-input dept-select" style="width:100%;">
                        <option value="">Choose employee</option>
                        @foreach($employees ?? [] as $emp)
                            <option value="{{ $emp->id }}">{{ trim($emp->fname . ' ' . $emp->lname) }}</option>
                        @endforeach
                    </select>
                </div>

                <div style="margin-bottom:16px;">
                    <label class="form-label">Shift Type</label>
                    <select class="form-input dept-select" style="width:100%;">
                        <option value="">Choose shift type</option>
                        <option>Day Shift</option>
                        <option>Mid Shift</option>
                        <option>Night Shift</option>
                    </select>
                </div>

                <div style="margin-bottom:16px;">
                    <label class="form-label">Work Setup</label>
                    <select class="form-input dept-select" style="width:100%;">
                        <option value="">Choose work setup</option>
                        <option>WFH</option>
                        <option>Office</option>
                    </select>
                </div>

                <div class="form-row" style="margin-bottom:16px;">
                    <div>
                        <label class="form-label">Effective From</label>
                        <input type="date" class="form-input" placeholder="MM/DD/YYYY">
                    </div>
                    <div>
                        <label class="form-label">Effective Until</label>
                        <input type="date" class="form-input" placeholder="MM/DD/YYYY">
                    </div>
                </div>

                <div style="margin-bottom:16px;">
                    <label class="form-label">Day Off</label>
                    <div style="display:flex;gap:16px;margin-top:6px;flex-wrap:wrap;">
                        @foreach(['Mon','Tue','Wed','Thurs','Fri','Sat','Sun'] as $day)
                        <label style="display:flex;align-items:center;gap:5px;font-size:13px;color:#374151;cursor:pointer;">
                            <input type="checkbox" style="width:15px;height:15px;accent-color:#3b82f6;">
                            {{ $day }}
                        </label>
                        @endforeach
                    </div>
                </div>

                <div class="modal-actions">
                    <button class="btn-cancel" @click="showAssignShiftModal = false">Cancel</button>
                    <button class="btn-save">Add Shift</button>
                </div>
            </div>
        </div>

        {{-- Add Shift Type Modal --}}
        <div x-show="showAddShiftTypeModal" class="modal-overlay" x-cloak @click.self="showAddShiftTypeModal = false">
            <div class="modal-box" style="width:520px;">
                <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:22px;">
                    <div class="modal-title" style="margin-bottom:0;">Add Shift Type</div>
                    <button @click="showAddShiftTypeModal = false" style="width:32px;height:32px;border:2px solid #374151;border-radius:50%;display:flex;align-items:center;justify-content:center;background:none;cursor:pointer;">
                        <svg style="width:14px;height:14px;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>

                <div style="margin-bottom:16px;">
                    <label class="form-label">Shift Name</label>
                    <input type="text" class="form-input" placeholder="Enter shift name e.g. Early Morning Shift">
                </div>

                <div class="form-row" style="margin-bottom:16px;">
                    <div>
                        <label class="form-label">Time In</label>
                        <input type="time" class="form-input" value="06:00">
                    </div>
                    <div>
                        <label class="form-label">Time Out</label>
                        <input type="time" class="form-input" value="15:00">
                    </div>
                </div>

                <div class="form-row" style="margin-bottom:16px;">
                    <div>
                        <label class="form-label">Break Start</label>
                        <input type="time" class="form-input" value="10:00">
                    </div>
                    <div>
                        <label class="form-label">Break End</label>
                        <input type="time" class="form-input" value="11:00">
                    </div>
                </div>

                <div style="margin-bottom:16px;">
                    <label class="form-label">Night Differential</label>
                    <select class="form-input dept-select" style="width:100%;">
                        <option value="">None</option>
                        <option>10%</option>
                        <option>20%</option>
                    </select>
                </div>

                <div class="modal-actions">
                    <button class="btn-cancel" @click="showAddShiftTypeModal = false">Cancel</button>
                    <button class="btn-save">Add Shift Type</button>
                </div>
            </div>
        </div>

        {{-- Add Holiday Modal --}}
        <div x-show="showAddHolidayModal" class="modal-overlay" x-cloak @click.self="showAddHolidayModal = false">
            <div class="modal-box" style="width:520px;">
                <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:22px;">
                    <div class="modal-title" style="margin-bottom:0;">Add Holiday</div>
                    <button @click="showAddHolidayModal = false" style="width:32px;height:32px;border:2px solid #374151;border-radius:50%;display:flex;align-items:center;justify-content:center;background:none;cursor:pointer;">
                        <svg style="width:14px;height:14px;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>

                <div style="margin-bottom:16px;">
                    <label class="form-label">Holiday Name</label>
                    <input type="text" class="form-input" placeholder="Enter holiday name">
                </div>

                <div style="margin-bottom:16px;">
                    <label class="form-label">Date</label>
                    <input type="date" class="form-input" placeholder="MM/DD/YYYY">
                </div>

                <div style="margin-bottom:16px;">
                    <label class="form-label">Type</label>
                    <select class="form-input dept-select" style="width:100%;">
                        <option value="">Choose type</option>
                        <option>Regular</option>
                        <option>Special Non-Working</option>
                        <option>Local</option>
                    </select>
                </div>

                <div style="margin-bottom:16px;">
                    <label class="form-label">Choose Pay Rate</label>
                    <select class="form-input dept-select" style="width:100%;">
                        <option value="">Choose rate</option>
                        <option>200%</option>
                        <option>130%</option>
                        <option>100%</option>
                    </select>
                </div>

                <div style="margin-bottom:16px;">
                    <label class="form-label">Repeat</label>
                    <label style="display:flex;align-items:center;gap:8px;margin-top:6px;font-size:13px;color:#374151;cursor:pointer;">
                        <input type="checkbox" style="width:15px;height:15px;accent-color:#3b82f6;">
                        Yearly
                    </label>
                </div>

                <div class="modal-actions">
                    <button class="btn-cancel" @click="showAddHolidayModal = false">Cancel</button>
                    <button class="btn-save">Add Holiday</button>
                </div>
            </div>
        </div>

    </div>
</div>

</body>
</html>
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
    <link rel="icon" type="image/png" href="{{ asset('images/HRISLogo-Icon.png') }}">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=yes, viewport-fit=cover">
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

        /* ── TAB NAV (Mobile Friendly - Scrollable) ── */
        .tab-nav {
            display: flex;
            gap: 0;
            border-bottom: 2px solid var(--border);
            margin-bottom: 24px;
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
            scrollbar-width: none;
        }
        .tab-nav::-webkit-scrollbar { display: none; }
        .tab-btn {
            padding: 10px 20px;
            font-size: 14px;
            font-weight: 500;
            color: var(--muted);
            border: none;
            background: none;
            cursor: pointer;
            font-family: inherit;
            border-bottom: 3px solid transparent;
            margin-bottom: -2px;
            transition: color .15s, border-color .15s;
            text-decoration: none;
            display: inline-block;
            white-space: nowrap;
            flex-shrink: 0;
        }
        .tab-btn:hover { color: #374151; }
        .tab-btn.active { color: var(--blue); border-bottom-color: var(--blue); font-weight: 600; }

        /* ── ACTION BUTTONS ── */
        .btn-outline {
            display: inline-flex; align-items: center; gap: 6px;
            padding: 8px 14px; border-radius: 8px; font-size: 13px;
            font-weight: 600; font-family: inherit; cursor: pointer;
            border: 1.5px solid #bfdbfe; background: var(--blue-light);
            color: var(--blue-dark); transition: all .15s; text-decoration: none;
            white-space: nowrap;
        }
        .btn-outline:hover { background: #dbeafe; border-color: #93c5fd; }
        .btn-outline svg { width: 14px; height: 14px; }

        .btn-primary {
            display: inline-flex; align-items: center; gap: 6px;
            padding: 8px 14px; border-radius: 8px; font-size: 13px;
            font-weight: 600; font-family: inherit; cursor: pointer;
            border: none; background: var(--blue); color: #fff;
            transition: background .15s; text-decoration: none;
            white-space: nowrap;
        }
        .btn-primary:hover { background: var(--blue-dark); }
        .btn-primary svg { width: 14px; height: 14px; }

        /* ── TOOLBAR (Responsive Stacking) ── */
        .toolbar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 20px;
            gap: 12px;
            flex-wrap: wrap;
        }
        .toolbar-left  { display: flex; align-items: center; gap: 10px; flex-wrap: wrap; }
        .toolbar-right { display: flex; align-items: center; gap: 10px; flex-wrap: wrap; }

        .search-box {
            display: flex; align-items: center; gap: 7px;
            background: #f9fafb; border: 1px solid var(--border);
            border-radius: 8px; padding: 7px 12px;
            min-width: 0;
        }
        .search-box svg { color: var(--muted); width: 14px; height: 14px; flex-shrink: 0; }
        .search-box input {
            border: none; background: transparent; outline: none;
            font-size: 13px; color: #111827; width: 100%; font-family: inherit;
            min-width: 0;
        }
        .search-box input::placeholder { color: var(--muted); }

        .dept-select {
            appearance: none; background: #f9fafb; border: 1px solid var(--border);
            border-radius: 8px; padding: 7px 28px 7px 12px; font-size: 13px;
            color: #111827; cursor: pointer; outline: none; font-family: inherit;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='10' height='6' fill='none'%3E%3Cpath d='M1 1l4 4 4-4' stroke='%236b7280' stroke-width='1.5' stroke-linecap='round'/%3E%3C/svg%3E");
            background-repeat: no-repeat; background-position: right 10px center;
        }

        /* ── WEEK NAV (Responsive) ── */
        .week-nav {
            display: flex;
            align-items: center;
            gap: 8px;
            flex-wrap: wrap;
        }
        .week-label {
            font-size: 18px;
            font-weight: 800;
            color: #111827;
            white-space: nowrap;
        }
        .week-btn {
            display: inline-flex; align-items: center; gap: 5px;
            padding: 6px 12px; border-radius: 7px; font-size: 12.5px;
            font-weight: 500; font-family: inherit; cursor: pointer;
            border: 1px solid var(--border); background: #fff; color: #374151;
            transition: background .15s; text-decoration: none;
            white-space: nowrap;
        }
        .week-btn:hover { background: #f9fafb; }
        .week-btn svg { width: 12px; height: 12px; }

        /* ── WEEKLY SCHEDULE TABLE (Horizontal Scroll on Mobile) ── */
        .schedule-card {
            background: #fff; border-radius: 14px; border: 1px solid var(--border);
            overflow: hidden; box-shadow: 0 1px 8px rgba(0,0,0,.04);
        }
        .schedule-scroll-wrap {
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
        }
        .schedule-table {
            width: 100%;
            border-collapse: collapse;
            min-width: 600px;
        }
        .schedule-table th {
            padding: 10px 10px;
            font-size: 11px;
            font-weight: 700;
            color: var(--muted);
            text-align: center;
            border-bottom: 1px solid var(--border);
            background: #f9fafb;
            letter-spacing: .5px;
            text-transform: uppercase;
            white-space: nowrap;
        }
        .schedule-table th.emp-col {
            text-align: left;
            min-width: 150px;
            position: sticky;
            left: 0;
            z-index: 2;
            background: #f9fafb;
            box-shadow: 2px 0 4px rgba(0,0,0,.04);
        }
        .schedule-table td {
            padding: 10px 10px;
            border-bottom: 1px solid #f3f4f6;
            vertical-align: middle;
            text-align: center;
        }
        .schedule-table td.emp-cell {
            text-align: left;
            position: sticky;
            left: 0;
            background: #fff;
            z-index: 1;
            box-shadow: 2px 0 4px rgba(0,0,0,.04);
        }
        .schedule-table tbody tr:hover td { background: #fafafa; }
        .schedule-table tbody tr:hover td.emp-cell { background: #fafafa; }
        .schedule-table tr:last-child td { border-bottom: none; }

        .emp-info .emp-name { font-size: 13px; font-weight: 600; color: #111827; white-space: nowrap; }
        .emp-info .emp-dept { font-size: 11.5px; color: var(--muted); }

        /* ── SHIFT PILLS ── */
        .shift-cell { display: flex; flex-direction: column; gap: 4px; align-items: center; }

        .pill-setup {
            display: inline-block; padding: 3px 8px; border-radius: 5px;
            font-size: 10px; font-weight: 600; min-width: 48px; text-align: center;
        }
        .pill-wfh    { background: #dbeafe; color: #1d4ed8; }
        .pill-office { background: #dcfce7; color: #15803d; }

        .pill-shift {
            display: inline-block; padding: 3px 8px; border-radius: 5px;
            font-size: 10px; font-weight: 600; min-width: 48px; text-align: center;
        }
        .pill-day   { background: #fef9c3; color: #a16207; }
        .pill-night { background: #fce7f3; color: #be185d; }
        .pill-mid   { background: #ede9fe; color: #6d28d9; }

        .pill-leave  {
            display: inline-block; padding: 5px 8px; border-radius: 5px;
            font-size: 11px; font-weight: 600; min-width: 48px; text-align: center;
            background: #fce7f3; color: #be185d;
        }
        .day-off-text { font-size: 11px; color: #9ca3af; font-weight: 500; }

        /* ── MODAL (Mobile First - Slide Up) ── */
        .modal-overlay {
            position: fixed;
            inset: 0;
            background: rgba(0,0,0,.4);
            display: flex;
            align-items: flex-end;
            justify-content: center;
            z-index: 999;
            padding: 0;
        }
        @media (min-width: 640px) {
            .modal-overlay {
                align-items: center;
                padding: 20px;
            }
        }
        .modal-box {
            background: #fff;
            border-radius: 20px 20px 0 0;
            padding: 24px 20px 32px;
            width: 100%;
            max-width: 100%;
            max-height: 90vh;
            overflow-y: auto;
            -webkit-overflow-scrolling: touch;
            box-shadow: 0 -8px 40px rgba(0,0,0,.15);
        }
        @media (min-width: 640px) {
            .modal-box {
                border-radius: 16px;
                padding: 28px 28px 32px;
                max-width: 520px;
                max-height: 85vh;
            }
        }
        /* Drag indicator for mobile */
        .modal-box::before {
            content: '';
            display: block;
            width: 40px;
            height: 4px;
            background: #e5e7eb;
            border-radius: 2px;
            margin: 0 auto 20px;
        }
        @media (min-width: 640px) {
            .modal-box::before {
                display: none;
            }
        }
        .modal-title {
            font-size: 17px;
            font-weight: 800;
            color: #111827;
            margin-bottom: 20px;
        }
        .form-label {
            font-size: 12px;
            font-weight: 600;
            color: #374151;
            text-transform: uppercase;
            letter-spacing: .5px;
            margin-bottom: 5px;
            display: block;
        }
        .form-input {
            width: 100%;
            border: 1.5px solid var(--border);
            border-radius: 8px;
            padding: 10px 13px;
            font-size: 13px;
            font-family: inherit;
            outline: none;
            color: #111827;
            transition: border-color .15s;
        }
        .form-input:focus { border-color: var(--blue); }
        .form-row {
            display: grid;
            grid-template-columns: 1fr;
            gap: 12px;
        }
        @media (min-width: 640px) {
            .form-row {
                grid-template-columns: 1fr 1fr;
            }
        }
        .modal-actions {
            display: flex;
            justify-content: flex-end;
            gap: 10px;
            margin-top: 22px;
            flex-direction: column-reverse;
        }
        @media (min-width: 640px) {
            .modal-actions {
                flex-direction: row;
            }
        }
        .btn-cancel {
            padding: 10px 18px;
            border-radius: 8px;
            font-size: 13px;
            font-weight: 600;
            font-family: inherit;
            cursor: pointer;
            border: 1.5px solid var(--border);
            background: #fff;
            color: #374151;
            text-align: center;
        }
        .btn-save {
            padding: 10px 20px;
            border-radius: 8px;
            font-size: 13px;
            font-weight: 600;
            font-family: inherit;
            cursor: pointer;
            border: none;
            background: var(--blue);
            color: #fff;
            text-align: center;
        }
        @media (min-width: 640px) {
            .btn-cancel, .btn-save {
                width: auto;
            }
        }
        .close-btn {
            width: 32px;
            height: 32px;
            border: 2px solid #374151;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            background: none;
            cursor: pointer;
            flex-shrink: 0;
        }

        /* ── HEADER TABS + ACTIONS AREA (Responsive) ── */
        .header-actions-row {
            display: flex;
            align-items: flex-end;
            justify-content: space-between;
            margin-bottom: 4px;
            gap: 8px;
            flex-wrap: wrap;
        }
        .action-btns {
            display: flex;
            gap: 8px;
            margin-bottom: 2px;
            flex-shrink: 0;
            flex-wrap: wrap;
        }

        /* ========== MOBILE RESPONSIVENESS ========== */
        @media (max-width: 1024px) {
            .desktop-sidebar { display: none !important; }

            /* Toolbar stacking */
            .toolbar {
                flex-direction: column;
                align-items: stretch;
            }
            .toolbar-left, .toolbar-right {
                width: 100%;
            }
            .toolbar-right {
                flex-wrap: wrap;
            }
            .search-box {
                flex: 1;
                min-width: 0;
            }
            .dept-select {
                flex: 1;
                min-width: 0;
            }

            .header-actions-row {
                flex-direction: column;
                align-items: stretch;
            }
            .action-btns {
                justify-content: flex-start;
                margin-top: 4px;
            }

            /* Table containers */
            .schedule-scroll-wrap {
                overflow-x: auto;
                -webkit-overflow-scrolling: touch;
            }
            .schedule-table { min-width: 700px; }

            /* Header padding */
            header .px-8 { padding-left: 1rem !important; padding-right: 1rem !important; }

            /* Content area padding */
            .main-content-padding { padding: 14px !important; }
        }

        @media (max-width: 640px) {
            .week-label { font-size: 15px; }
            .week-btn { padding: 6px 10px; font-size: 12px; }
            .form-row { grid-template-columns: 1fr; }
            .modal-actions { flex-direction: column-reverse; }
            .btn-cancel { text-align: center; }
            .tab-btn { padding: 8px 14px; font-size: 13px; }
            .btn-outline, .btn-primary { padding: 6px 12px; font-size: 12px; }
        }

        /* Sidebar responsive */
        .desktop-sidebar { display: none; }
        @media (min-width: 1024px) {
            .desktop-sidebar { display: block; }
        }
        @media (max-width: 1023px) {
            .main-content {
                margin-left: 0 !important;
            }
        }
    </style>
</head>
<body class="bg-gray-50" x-data="{ mobileMenuOpen: false, collapsed: localStorage.getItem('sidebarCollapsed') === 'true' }"
     x-init="window.addEventListener('sidebar-toggle', e => { collapsed = e.detail.collapsed })">

{{-- DESKTOP SIDEBAR --}}
<div class="hidden lg:block desktop-sidebar">
    @include('supervisor.supervisor_sidebar')
</div>

{{-- MOBILE SLIDE-OUT DRAWER --}}
<div x-show="mobileMenuOpen"
     x-transition:enter="transition ease-out duration-200"
     x-transition:enter-start="opacity-0"
     x-transition:enter-end="opacity-100"
     x-transition:leave="transition ease-in duration-150"
     x-transition:leave-start="opacity-100"
     x-transition:leave-end="opacity-0"
     class="fixed inset-0 z-50 lg:hidden"
     style="display:none;">
    <div class="absolute inset-0 bg-black/40" @click="mobileMenuOpen = false"></div>
    <div x-show="mobileMenuOpen"
         x-transition:enter="transition ease-out duration-250"
         x-transition:enter-start="-translate-x-full"
         x-transition:enter-end="translate-x-0"
         x-transition:leave="transition ease-in duration-200"
         x-transition:leave-start="translate-x-0"
         x-transition:leave-end="-translate-x-full"
         class="relative w-72 h-full bg-white shadow-2xl overflow-y-auto">
        @include('supervisor.supervisor_sidebar')
    </div>
</div>

{{-- ══════════ MAIN CONTENT ══════════ --}}
<div x-data="{ collapsed: localStorage.getItem('sidebarCollapsed') === 'true' }"
     x-init="window.addEventListener('sidebar-toggle', e => { collapsed = e.detail.collapsed })"
     :style="window.innerWidth >= 1024 ? (collapsed ? 'margin-left:5rem' : 'margin-left:16rem') : 'margin-left:0'"
     x-on:resize.window="$el.style.marginLeft = window.innerWidth >= 1024 ? (collapsed ? '5rem' : '16rem') : '0'"
     style="transition: margin-left 0.35s cubic-bezier(0.4, 0, 0.2, 1); min-height:100vh;">

    {{-- Blue Header with Hamburger --}}
    <header class="bg-gradient-to-br from-blue-500 to-blue-700 sticky top-0 z-10 shadow-lg mt-4 mx-4 rounded-2xl overflow-visible">
        <div class="flex items-center justify-between px-6 py-4">
            <div class="flex items-center gap-3">
                <button @click="mobileMenuOpen = true" class="lg:hidden p-1.5 rounded-lg hover:bg-white/20 transition-colors text-white">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M4 6h16M4 12h16M4 18h16"/>
                    </svg>
                </button>
                <h1 class="text-white font-bold text-lg">Shift Scheduling</h1>
            </div>
            <x-supervisor-notif />
        </div>
    </header>

    <!-- Page Content -->
    <div class="main-content-padding" style="padding:20px 16px;" x-data="{ showEditShiftModal: false, showAssignShiftModal: false }">

        <!-- Tab Nav + Action Buttons -->
        <div class="header-actions-row">
            <div class="tab-nav" style="margin-bottom:0; flex:1; min-width:0;">
                <a href="{{ route('supervisor.shift.scheduling', ['tab' => 'weekly']) }}"
                   class="tab-btn {{ $activeTab === 'weekly' ? 'active' : '' }}">Weekly Schedule</a>
            </div>
            <div class="action-btns">
                @canDo('Time & Attendance', 'edit')
                <button class="btn-outline" @click="showEditShiftModal = true">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                    Edit Shift
                </button>
                @endcanDo
                @canDo('Time & Attendance', 'create')
                <button class="btn-primary" @click="showAssignShiftModal = true">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                    Assign Shift
                </button>
                @endcanDo
            </div>
        </div>
        <div style="border-bottom:2px solid #e5e7eb; margin-bottom:20px;"></div>

        {{-- ══════════ TAB: WEEKLY SCHEDULE ══════════ --}}
        @if($activeTab === 'weekly')

        <div x-data="{
            search: '',
            deptFilter: '',
            visible(name, dept) {
                const s = this.search.toLowerCase();
                const d = this.deptFilter.toLowerCase();
                return (!s || name.toLowerCase().includes(s)) &&
                       (!d || dept.toLowerCase() === d);
            }
        }">
        <div class="toolbar">
            <div class="toolbar-left">
                <div class="week-nav">
                    <a href="{{ route('supervisor.shift.scheduling', ['tab' => 'weekly', 'week_start' => $selectedWeekStart->copy()->subWeek()->format('Y-m-d')]) }}"
                       class="week-btn">
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                        Previous
                    </a>
                    <span class="week-label">{{ $selectedWeekStart->format('M j') }}–{{ $selectedWeekEnd->format('j') }}</span>
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
                    <input type="text" placeholder="Search employee…" x-model="search">
                </div>
                <select class="dept-select" x-model="deptFilter">
                    <option value="">All Departments</option>
                    @foreach($departments ?? [] as $dept)
                        <option value="{{ strtolower($dept->name) }}">{{ $dept->name }}</option>
                    @endforeach
                </select>
            </div>
        </div>

        <div class="schedule-card">
            <div class="schedule-scroll-wrap">
                <table class="schedule-table">
                    <thead>
                        <tr>
                            <th class="emp-col">Employee</th>
                            @foreach($weekDays as $day)
                            <th style="{{ $day->isWeekend() ? 'background:#f3f4f6;' : '' }}">
                                <div>{{ strtoupper($day->format('D')) }}</div>
                                <div style="font-size:11px; font-weight:700; color:#111827; letter-spacing:0;">{{ $day->format('M j') }}</div>
                            </th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($scheduleRecords ?? [] as $rec)
                        @php
                            $recName = trim(($rec->employee->fname ?? '') . ' ' . ($rec->employee->lname ?? ''));
                            $recDept = strtolower($rec->employee->department->name ?? '');
                        @endphp
                        <tr x-show="visible('{{ addslashes($recName) }}', '{{ $recDept }}')">
                            <td class="emp-cell">
                                <div class="emp-info">
                                    <div class="emp-name">{{ $recName }}</div>
                                    <div class="emp-dept">{{ $rec->employee->department->name ?? '—' }}</div>
                                </div>
                            </td>
                            @foreach($weekDays as $day)
                            @php
                                $dayKey   = $day->toDateString();
                                $cell     = $rec->days[$dayKey] ?? ['type' => 'none'];
                                $cellType = $cell['type'];
                            @endphp
                            <td style="{{ $cellType === 'day_off' ? 'background:#f9fafb;' : '' }}">
                                @if($cellType === 'day_off')
                                    <span class="day-off-text">Day Off</span>
                                @elseif($cellType === 'leave')
                                    <span class="pill-leave">Leave</span>
                                @elseif($cellType === 'shift')
                                    @php
                                        $setup    = strtolower($cell['work_setup'] ?? 'wfh');
                                        $sName    = $cell['shift_name'] ?? '—';
                                        $sLow     = strtolower($sName);
                                    @endphp
                                    <div class="shift-cell">
                                        <span class="pill-setup {{ $setup === 'office' ? 'pill-office' : 'pill-wfh' }}">
                                            {{ strtoupper($setup) }}
                                        </span>
                                        <span class="pill-shift {{ str_contains($sLow,'night') ? 'pill-night' : (str_contains($sLow,'mid') ? 'pill-mid' : 'pill-day') }}">
                                            {{ $sName }}
                                        </span>
                                    </div>
                                @else
                                    <span class="day-off-text">—</span>
                                @endif
                            </td>
                            @endforeach
                        </tr>
                        @empty
                        <tr>
                            <td colspan="8" style="text-align:center; padding:40px 16px; color:#9ca3af; font-size:13px;">
                                No employees found in your department.
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        </div>{{-- end x-data wrapper --}}
        @endif

        {{-- ══════════ MODALS ══════════ --}}

        {{-- Edit Shift Modal --}}
        <div x-show="showEditShiftModal" class="modal-overlay" x-cloak @click.self="showEditShiftModal = false"
             x-data="{
                 empId: '',
                 empShiftId: '',
                 shiftId: '',
                 workSetup: '',
                 effectiveDate: '',
                 endDate: '',
                 daysOff: ['Sat','Sun'],
                 currentInfo: null,
                 saving: false,
                 errorMsg: '',
                 async onEmpChange() {
                     this.currentInfo = null;
                     this.empShiftId = '';
                     if (!this.empId) return;
                     const res = await fetch('{{ route('supervisor.shift.employees-by-dept') }}?employee_id=' + this.empId, {
                         headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content }
                     });
                     const data = await res.json();
                     if (data.shift) {
                         this.currentInfo   = data.shift;
                         this.empShiftId    = data.shift.id;
                         this.shiftId       = data.shift.shift_id;
                         this.workSetup     = data.shift.work_setup ?? '';
                         this.effectiveDate = data.shift.effective_date ?? '';
                         this.endDate       = data.shift.end_date ?? '';
                         this.daysOff       = data.shift.days_off ? JSON.parse(data.shift.days_off) : ['Sat','Sun'];
                     }
                 },
                 toggleDay(day) {
                     if (this.daysOff.includes(day)) this.daysOff = this.daysOff.filter(d => d !== day);
                     else this.daysOff.push(day);
                 },
                 async submit() {
                     this.errorMsg = '';
                     if (!this.empShiftId || !this.shiftId || !this.workSetup || !this.effectiveDate) {
                         this.errorMsg = 'Please fill in all required fields.'; return;
                     }
                     this.saving = true;
                     const res = await fetch('{{ route('supervisor.shift.update') }}', {
                         method: 'POST',
                         headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content },
                         body: JSON.stringify({
                             employee_shift_id: this.empShiftId,
                             shift_id: this.shiftId,
                             work_setup: this.workSetup,
                             effective_date: this.effectiveDate,
                             end_date: this.endDate || null,
                             days_off: this.daysOff,
                         })
                     });
                     this.saving = false;
                     const data = await res.json();
                     if (res.ok) { window.location.reload(); }
                     else { this.errorMsg = data.message ?? 'Something went wrong.'; }
                 }
             }">
            <div class="modal-box">
                <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:18px;">
                    <div class="modal-title" style="margin-bottom:0;">Edit Shift</div>
                    <button @click="showEditShiftModal = false" class="close-btn">
                        <svg style="width:14px;height:14px;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>

                <template x-if="errorMsg">
                    <div style="background:#fee2e2;color:#b91c1c;border-radius:8px;padding:10px 14px;font-size:13px;margin-bottom:14px;" x-text="errorMsg"></div>
                </template>

                <div style="margin-bottom:16px;">
                    <label class="form-label">Employee</label>
                    <select class="form-input dept-select" style="width:100%;" x-model="empId" @change="onEmpChange()">
                        <option value="">Choose employee</option>
                        @foreach($employees ?? [] as $emp)
                            <option value="{{ $emp->id }}">{{ trim($emp->fname . ' ' . $emp->lname) }}</option>
                        @endforeach
                    </select>
                </div>

                <template x-if="currentInfo">
                    <div style="background:#f0f9ff;border-radius:8px;padding:14px 16px;margin-bottom:18px;">
                        <div style="font-size:12px;color:#6b7280;font-weight:500;margin-bottom:8px;">Current Shift Assignment</div>
                        <div style="display:grid;grid-template-columns:repeat(2,1fr);gap:10px;">
                            <div><div style="font-size:11px;color:#9ca3af;">Shift</div><div style="font-size:13px;font-weight:600;color:#111827;" x-text="currentInfo.shift_name ?? '—'"></div></div>
                            <div><div style="font-size:11px;color:#9ca3af;">Work Setup</div><div style="font-size:13px;font-weight:600;color:#111827;" x-text="currentInfo.work_setup ? currentInfo.work_setup.toUpperCase() : '—'"></div></div>
                            <div><div style="font-size:11px;color:#9ca3af;">Day Off</div><div style="font-size:13px;font-weight:600;color:#111827;" x-text="currentInfo.days_off ? JSON.parse(currentInfo.days_off).join(', ') : '—'"></div></div>
                            <div><div style="font-size:11px;color:#9ca3af;">Effective From</div><div style="font-size:13px;font-weight:600;color:#111827;" x-text="currentInfo.effective_date ? new Date(currentInfo.effective_date).toLocaleDateString('en-PH', {year:'numeric',month:'long',day:'numeric'}) : '—'"></div></div>
                            <div><div style="font-size:11px;color:#9ca3af;">Effective Until</div><div style="font-size:13px;font-weight:600;color:#111827;" x-text="currentInfo.end_date ? new Date(currentInfo.end_date).toLocaleDateString('en-PH', {year:'numeric',month:'long',day:'numeric'}) : 'Ongoing'"></div></div>
                            <div><div style="font-size:11px;color:#9ca3af;">Schedule</div><div style="font-size:13px;font-weight:600;color:#111827;" x-text="currentInfo.schedule ?? '—'"></div></div>
                        </div>
                    </div>
                </template>

                <div style="margin-bottom:16px;">
                    <label class="form-label">Change Shift Type</label>
                    <select class="form-input dept-select" style="width:100%;" x-model="shiftId">
                        <option value="">Choose shift type</option>
                        @foreach($shiftTypes ?? [] as $shift)
                            <option value="{{ $shift->id }}">
                                {{ $shift->name }} ({{ \Carbon\Carbon::parse($shift->start_time)->format('g:i A') }} – {{ \Carbon\Carbon::parse($shift->end_time)->format('g:i A') }})
                            </option>
                        @endforeach
                    </select>
                </div>

                <div style="margin-bottom:16px;">
                    <label class="form-label">Change Work Setup</label>
                    <select class="form-input dept-select" style="width:100%;" x-model="workSetup">
                        <option value="">Choose work setup</option>
                        <option value="wfh">WFH</option>
                        <option value="office">Office</option>
                    </select>
                </div>

                <div class="form-row" style="margin-bottom:16px;">
                    <div>
                        <label class="form-label">Effective From</label>
                        <input type="date" class="form-input" x-model="effectiveDate">
                    </div>
                    <div>
                        <label class="form-label">Effective Until (optional)</label>
                        <input type="date" class="form-input" x-model="endDate">
                    </div>
                </div>

                <div style="margin-bottom:16px;">
                    <label class="form-label">Day Off</label>
                    <div style="display:flex;gap:12px;margin-top:6px;flex-wrap:wrap;">
                        @foreach(['Mon','Tue','Wed','Thu','Fri','Sat','Sun'] as $d)
                        <label style="display:flex;align-items:center;gap:5px;font-size:13px;color:#374151;cursor:pointer;">
                            <input type="checkbox" style="width:15px;height:15px;accent-color:#3b82f6;"
                                :checked="daysOff.includes('{{ $d }}')"
                                @change="toggleDay('{{ $d }}')">
                            {{ $d }}
                        </label>
                        @endforeach
                    </div>
                </div>

                <div class="modal-actions">
                    <button class="btn-cancel" @click="showEditShiftModal = false">Cancel</button>
                    <button class="btn-save" @click="submit()" :disabled="saving" x-text="saving ? 'Saving…' : 'Save Changes'"></button>
                </div>
            </div>
        </div>

        {{-- Assign Shift Modal --}}
        <div x-show="showAssignShiftModal" class="modal-overlay" x-cloak @click.self="showAssignShiftModal = false"
             x-data="{
                 empId: '',
                 shiftId: '',
                 workSetup: '',
                 effectiveDate: '',
                 endDate: '',
                 daysOff: ['Sat','Sun'],
                 saving: false,
                 errorMsg: '',
                 toggleDay(day) {
                     if (this.daysOff.includes(day)) this.daysOff = this.daysOff.filter(d => d !== day);
                     else this.daysOff.push(day);
                 },
                 async submit() {
                     this.errorMsg = '';
                     if (!this.empId || !this.shiftId || !this.workSetup || !this.effectiveDate) {
                         this.errorMsg = 'Please fill in all required fields.'; return;
                     }
                     this.saving = true;
                     const res = await fetch('{{ route('supervisor.shift.assign') }}', {
                         method: 'POST',
                         headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content },
                         body: JSON.stringify({
                             employee_id: this.empId,
                             shift_id: this.shiftId,
                             work_setup: this.workSetup,
                             effective_date: this.effectiveDate,
                             end_date: this.endDate || null,
                             days_off: this.daysOff,
                         })
                     });
                     this.saving = false;
                     const data = await res.json();
                     if (res.ok) { window.location.reload(); }
                     else { this.errorMsg = data.message ?? 'Something went wrong.'; }
                 }
             }">
            <div class="modal-box">
                <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:18px;">
                    <div class="modal-title" style="margin-bottom:0;">Assign Shift</div>
                    <button @click="showAssignShiftModal = false" class="close-btn">
                        <svg style="width:14px;height:14px;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>

                <template x-if="errorMsg">
                    <div style="background:#fee2e2;color:#b91c1c;border-radius:8px;padding:10px 14px;font-size:13px;margin-bottom:14px;" x-text="errorMsg"></div>
                </template>

                <div style="margin-bottom:16px;">
                    <label class="form-label">Employee</label>
                    <select class="form-input dept-select" style="width:100%;" x-model="empId">
                        <option value="">Choose employee</option>
                        @foreach($employees ?? [] as $emp)
                            <option value="{{ $emp->id }}">{{ trim($emp->fname . ' ' . $emp->lname) }}</option>
                        @endforeach
                    </select>
                </div>

                <div style="margin-bottom:16px;">
                    <label class="form-label">Shift Type</label>
                    <select class="form-input dept-select" style="width:100%;" x-model="shiftId">
                        <option value="">Choose shift type</option>
                        @foreach($shiftTypes ?? [] as $shift)
                            <option value="{{ $shift->id }}">
                                {{ $shift->name }} ({{ \Carbon\Carbon::parse($shift->start_time)->format('g:i A') }} – {{ \Carbon\Carbon::parse($shift->end_time)->format('g:i A') }})
                            </option>
                        @endforeach
                    </select>
                </div>

                <div style="margin-bottom:16px;">
                    <label class="form-label">Work Setup</label>
                    <select class="form-input dept-select" style="width:100%;" x-model="workSetup">
                        <option value="">Choose work setup</option>
                        <option value="wfh">WFH</option>
                        <option value="office">Office</option>
                    </select>
                </div>

                <div class="form-row" style="margin-bottom:16px;">
                    <div>
                        <label class="form-label">Effective From</label>
                        <input type="date" class="form-input" x-model="effectiveDate">
                    </div>
                    <div>
                        <label class="form-label">Effective Until (optional)</label>
                        <input type="date" class="form-input" x-model="endDate">
                    </div>
                </div>

                <div style="margin-bottom:16px;">
                    <label class="form-label">Day Off</label>
                    <div style="display:flex;gap:12px;margin-top:6px;flex-wrap:wrap;">
                        @foreach(['Mon','Tue','Wed','Thu','Fri','Sat','Sun'] as $d)
                        <label style="display:flex;align-items:center;gap:5px;font-size:13px;color:#374151;cursor:pointer;">
                            <input type="checkbox" style="width:15px;height:15px;accent-color:#3b82f6;"
                                :checked="daysOff.includes('{{ $d }}')"
                                @change="toggleDay('{{ $d }}')">
                            {{ $d }}
                        </label>
                        @endforeach
                    </div>
                </div>

                <div class="modal-actions">
                    <button class="btn-cancel" @click="showAssignShiftModal = false">Cancel</button>
                    <button class="btn-save" @click="submit()" :disabled="saving" x-text="saving ? 'Saving…' : 'Add Shift'"></button>
                </div>
            </div>
        </div>

    </div>
</div>

<script>
    // Fix sidebar margin on load and resize
    (function() {
        var mainEl = document.querySelector('.main-content');
        if (!mainEl) return;
        function updateMargin() {
            var collapsed = localStorage.getItem('sidebarCollapsed') === 'true';
            if (window.innerWidth < 1024) {
                mainEl.style.marginLeft = '0';
            } else {
                mainEl.style.marginLeft = collapsed ? '5rem' : '16rem';
            }
        }
        updateMargin();
        window.addEventListener('resize', updateMargin);
        window.addEventListener('sidebar-toggle', function(e) {
            if (window.innerWidth >= 1024) {
                mainEl.style.marginLeft = e.detail.collapsed ? '5rem' : '16rem';
            }
        });
    })();
</script>
</body>
</html>
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

    $attendanceRoutes = ['hr.attendance.reports','hr.attendance.employee','hr.attendance.shift','hr.attendance.leave','hr.shift.scheduling'];
    $employeeRoutes   = ['hr.employees.directory','hr.employees.profile'];
    $payrollRoutes    = ['hr.payroll','hr.payslips','hr.contributions'];
    $requestRoutes    = ['hr.requests.pending','hr.requests.approved'];

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

        /* ── TAB NAV ── */
        .tab-nav {
            display: flex; gap: 0;
            overflow-x: auto; -webkit-overflow-scrolling: touch;
            scrollbar-width: none;
        }
        .tab-nav::-webkit-scrollbar { display: none; }
        .tab-btn {
            padding: 10px 20px; font-size: 14px; font-weight: 500;
            color: var(--muted); border: none; background: none;
            cursor: pointer; font-family: inherit;
            border-bottom: 3px solid transparent;
            transition: color .15s, border-color .15s;
            display: inline-flex; align-items: center; white-space: nowrap;
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
            color: var(--blue-dark); transition: all .15s;
            white-space: nowrap;
        }
        .btn-outline:hover { background: #dbeafe; border-color: #93c5fd; }
        .btn-outline svg { width: 14px; height: 14px; }

        .btn-primary {
            display: inline-flex; align-items: center; gap: 6px;
            padding: 8px 14px; border-radius: 8px; font-size: 13px;
            font-weight: 600; font-family: inherit; cursor: pointer;
            border: none; background: var(--blue); color: #fff;
            transition: background .15s;
            white-space: nowrap;
        }
        .btn-primary:hover { background: var(--blue-dark); }
        .btn-primary svg { width: 14px; height: 14px; }

        /* ── TOOLBAR ── */
        .toolbar {
            display: flex; align-items: center; justify-content: space-between;
            margin-bottom: 20px; gap: 12px; flex-wrap: wrap;
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

        /* ── WEEK NAV ── */
        .week-nav {
            display: flex; align-items: center; gap: 8px; flex-wrap: wrap;
        }
        .week-label {
            font-size: 18px; font-weight: 800; color: #111827; white-space: nowrap;
        }
        .week-btn {
            display: inline-flex; align-items: center; gap: 5px;
            padding: 6px 12px; border-radius: 7px; font-size: 12.5px;
            font-weight: 500; font-family: inherit; cursor: pointer;
            border: 1px solid var(--border); background: #fff; color: #374151;
            transition: background .15s; white-space: nowrap;
        }
        .week-btn:hover { background: #f9fafb; }
        .week-btn svg { width: 12px; height: 12px; }

        /* ── WEEKLY SCHEDULE TABLE ── */
        .schedule-card {
            background: #fff; border-radius: 14px; border: 1px solid var(--border);
            overflow: hidden; box-shadow: 0 1px 8px rgba(0,0,0,.04);
        }
        .schedule-scroll-wrap {
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
            /* Force scrollbar visible on mobile */
            scrollbar-width: thin;
            scrollbar-color: #d1d5db transparent;
        }
        .schedule-scroll-wrap::-webkit-scrollbar { height: 6px; }
        .schedule-scroll-wrap::-webkit-scrollbar-track { background: transparent; }
        .schedule-scroll-wrap::-webkit-scrollbar-thumb { background: #d1d5db; border-radius: 3px; }

        .schedule-table {
            width: 100%;
            border-collapse: collapse;
            /* min-width ensures scroll kicks in on mobile */
            min-width: 680px;
            table-layout: fixed;
        }
        .schedule-table th {
            padding: 10px 8px; font-size: 11px; font-weight: 700;
            color: var(--muted); text-align: center; border-bottom: 1px solid var(--border);
            background: #f9fafb; letter-spacing: .5px; text-transform: uppercase;
            white-space: nowrap;
        }
        .schedule-table th.emp-col {
            text-align: left;
            width: 160px;
            min-width: 140px;
            position: sticky;
            left: 0;
            z-index: 3;
            background: #f9fafb;
            /* Crisp shadow so it looks separated */
            box-shadow: 2px 0 6px -1px rgba(0,0,0,.08);
        }
        .schedule-table td {
            padding: 10px 8px; border-bottom: 1px solid #f3f4f6;
            vertical-align: middle; text-align: center;
        }
        .schedule-table td.emp-cell {
            text-align: left;
            position: sticky;
            left: 0;
            background: #fff;
            z-index: 2;
            box-shadow: 2px 0 6px -1px rgba(0,0,0,.08);
            width: 160px;
            min-width: 140px;
        }
        .schedule-table tbody tr:hover td { background: #fafafa; }
        .schedule-table tbody tr:hover td.emp-cell { background: #fafafa; }
        .schedule-table tr:last-child td { border-bottom: none; }

        .emp-info .emp-name { font-size: 13px; font-weight: 600; color: #111827; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .emp-info .emp-dept { font-size: 11px; color: var(--muted); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }

        /* ── SHIFT PILLS ── */
        .shift-cell { display: flex; flex-direction: column; gap: 4px; align-items: center; }

        .pill-setup {
            display: inline-block; padding: 2px 7px; border-radius: 5px;
            font-size: 10px; font-weight: 600; min-width: 44px; text-align: center;
        }
        .pill-wfh    { background: #dbeafe; color: #1d4ed8; }
        .pill-office { background: #dcfce7; color: #15803d; }

        .pill-shift {
            display: inline-block; padding: 2px 7px; border-radius: 5px;
            font-size: 10px; font-weight: 600; min-width: 44px; text-align: center;
        }
        .pill-day   { background: #fef9c3; color: #a16207; }
        .pill-night { background: #fce7f3; color: #be185d; }
        .pill-mid   { background: #ede9fe; color: #6d28d9; }

        .pill-leave  {
            display: inline-block; padding: 5px 8px; border-radius: 5px;
            font-size: 11px; font-weight: 600; min-width: 44px; text-align: center;
            background: #fce7f3; color: #be185d;
        }
        .day-off-text { font-size: 11px; color: #9ca3af; font-weight: 500; }

        /* ── SHIFT TYPES TABLE ── */
        .data-table { width: 100%; border-collapse: collapse; min-width: 580px; }
        .data-table thead tr { background: #f9fafb; }
        .data-table th {
            padding: 11px 14px; font-size: 11px; font-weight: 600;
            color: var(--muted); text-align: left; border-bottom: 1px solid var(--border);
            white-space: nowrap; letter-spacing: .4px; text-transform: uppercase;
        }
        .data-table td {
            padding: 13px 14px; font-size: 13px; color: #111827;
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
            padding: 5px 12px; border-radius: 7px; font-size: 12px;
            font-weight: 600; font-family: inherit; cursor: pointer;
            border: 1.5px solid #bfdbfe; background: var(--blue-light);
            color: var(--blue-dark); transition: all .15s;
        }
        .edit-btn:hover { background: #dbeafe; }

        .delete-btn {
            display: inline-flex; align-items: center; gap: 4px;
            padding: 5px 12px; border-radius: 7px; font-size: 12px;
            font-weight: 600; font-family: inherit; cursor: pointer;
            border: 1.5px solid #fecaca; background: #fef2f2;
            color: #dc2626; transition: all .15s;
        }
        .delete-btn:hover { background: #fee2e2; }

        /* ── HOLIDAY CALENDAR ── */
        .holiday-stat-grid {
            display: grid; grid-template-columns: repeat(3, 1fr);
            gap: 16px; margin-bottom: 24px;
        }
        .holiday-stat-card {
            border-radius: 12px; padding: 18px 16px;
            border: 1px solid transparent;
        }
        .hsc-label { font-size: 10px; font-weight: 700; letter-spacing: .7px; text-transform: uppercase; margin-bottom: 8px; }
        .hsc-value { font-size: 32px; font-weight: 800; line-height: 1; }
        .hsc-sub   { font-size: 11px; font-weight: 500; margin-top: 6px; opacity: .7; }

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
            display: inline-block; padding: 3px 10px; border-radius: 20px;
            font-size: 11px; font-weight: 600; white-space: nowrap;
        }
        .tb-regular { background: #dbeafe; color: #1d4ed8; }
        .tb-special { background: #dcfce7; color: #15803d; }
        .tb-local   { background: #fce7f3; color: #be185d; }

        /* ── MODAL ── */
        .modal-overlay {
            position: fixed; inset: 0; background: rgba(0,0,0,.4);
            display: flex; align-items: center; justify-content: center;
            z-index: 999; padding: 16px;
        }
        .modal-box {
            background: #fff;
            border-radius: 16px;
            padding: 24px 20px 32px;
            width: 100%;
            max-width: 520px;
            max-height: 90vh;
            overflow-y: auto;
            -webkit-overflow-scrolling: touch;
            box-shadow: 0 4px 30px rgba(0,0,0,.15);
        }
        .modal-box::before {
            content: '';
            display: block;
            width: 40px; height: 4px;
            background: #e5e7eb; border-radius: 2px;
            margin: 0 auto 20px;
        }
        .modal-title { font-size: 17px; font-weight: 800; color: #111827; margin-bottom: 20px; }
        .form-label { font-size: 12px; font-weight: 600; color: #374151; text-transform: uppercase; letter-spacing: .5px; margin-bottom: 5px; display: block; }
        .form-input {
            width: 100%; border: 1.5px solid var(--border); border-radius: 8px;
            padding: 10px 13px; font-size: 13px; font-family: inherit; outline: none;
            color: #111827; transition: border-color .15s;
        }
        .form-input:focus { border-color: var(--blue); }
        .form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; }
        .modal-actions { display: flex; justify-content: flex-end; gap: 10px; margin-top: 22px; flex-wrap: wrap; }
        .btn-cancel {
            padding: 8px 16px; border-radius: 8px; font-size: 13px;
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
            padding: 14px 16px; border-bottom: 1px solid var(--border);
            gap: 12px; flex-wrap: wrap;
        }
        /* Scrollable wrapper for data tables */
        .table-scroll-wrap {
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
            scrollbar-width: thin;
            scrollbar-color: #d1d5db transparent;
        }
        .table-scroll-wrap::-webkit-scrollbar { height: 6px; }
        .table-scroll-wrap::-webkit-scrollbar-thumb { background: #d1d5db; border-radius: 3px; }

        /* ── HEADER TABS + ACTIONS AREA ── */
        .header-actions-row {
            display: flex;
            align-items: flex-end;
            justify-content: space-between;
            margin-bottom: 0;
            gap: 8px;
            flex-wrap: wrap;
        }
        .tab-nav-wrap {
            flex: 1;
            min-width: 0;
            border-bottom: 2px solid var(--border);
        }
        .action-btns {
            display: flex;
            gap: 8px;
            padding-bottom: 2px;
            flex-shrink: 0;
            flex-wrap: wrap;    
        }
        .section-divider { margin-bottom: 20px; }

        /* ── MOBILE RESPONSIVE ── */
        @media (max-width: 1024px) {
            .desktop-sidebar { display: none !important; }

            .holiday-stat-grid {
                grid-template-columns: 1fr 1fr;
                gap: 10px;
            }

            .toolbar { flex-direction: column; align-items: stretch; }
            .toolbar-left, .toolbar-right { width: 100%; }
            .search-box { flex: 1; min-width: 0; }
            .dept-select { flex: 1; min-width: 0; }

            .table-toolbar { flex-direction: column; align-items: stretch; }

            .header-actions-row { flex-direction: column; align-items: stretch; }
            .tab-nav-wrap { order: 1; }
            .action-btns { order: 2; justify-content: flex-start; padding-top: 8px; }
        }

        @media (max-width: 640px) {
            .holiday-stat-grid { grid-template-columns: 1fr; gap: 10px; }
            .hsc-value { font-size: 28px; }

            .week-label { font-size: 15px; }
            .week-btn { padding: 6px 10px; font-size: 12px; }

            .form-row { grid-template-columns: 1fr; }
            .modal-actions { flex-direction: column-reverse; }
            .btn-cancel { text-align: center; }

            .data-table th, .data-table td { padding: 10px 10px; font-size: 12px; }
            .tab-btn { padding: 8px 14px; font-size: 13px; }
            .btn-outline, .btn-primary { padding: 6px 12px; font-size: 12px; }

            /* Stack employee info on very small screens */
            .emp-info .emp-name { font-size: 12px; }
            .emp-info .emp-dept { font-size: 10px; }
        }
    </style>
</head>
<body class="bg-gray-50"
      x-data="{ mobileMenuOpen: false, collapsed: localStorage.getItem('sidebarCollapsed') === 'true' }"
      x-init="window.addEventListener('sidebar-toggle', e => { collapsed = e.detail.collapsed })">

{{-- DESKTOP SIDEBAR --}}
<div class="hidden lg:block desktop-sidebar">
    @include('hr.hr_sidebar')
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
        @include('hr.hr_sidebar')
    </div>
</div>

{{-- ══════════ MAIN CONTENT ══════════ --}}
<div x-data="{ collapsed: localStorage.getItem('sidebarCollapsed') === 'true' }"
     x-init="window.addEventListener('sidebar-toggle', e => { collapsed = e.detail.collapsed })"
     :style="window.innerWidth >= 1024 ? (collapsed ? 'margin-left:5rem' : 'margin-left:16rem') : 'margin-left:0'"
     x-on:resize.window="$el.style.marginLeft = window.innerWidth >= 1024 ? (collapsed ? '5rem' : '16rem') : '0'"
     style="transition: margin-left 0.35s cubic-bezier(0.4, 0, 0.2, 1); min-height:100vh;">

    {{-- Blue Header --}}
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
            <x-hr-notif />
        </div>
    </header>

    <!-- Page Content -->
    {{--
        KEY FIX: activeTab is now an Alpine reactive variable.
        Tab buttons set x-data.activeTab directly — NO page reload.
        Week navigation (prev/next) still uses links since it needs server data.
    --}}
    <div class="main-content-padding" style="padding:20px 16px;"
         x-data="{
             activeTab: '{{ $activeTab }}',
             showEditShiftModal: false,
             showAssignShiftModal: false,
             showAddShiftTypeModal: false,
             showAddHolidayModal: false
         }">

        <!-- Tab Nav + Action Buttons -->
        <div class="header-actions-row">
            <div class="tab-nav-wrap">
                <div class="tab-nav">
                    <button type="button"
                            class="tab-btn"
                            :class="{ active: activeTab === 'weekly' }"
                            @click="activeTab = 'weekly'">
                        Weekly Schedule
                    </button>
                    <button type="button"
                            class="tab-btn"
                            :class="{ active: activeTab === 'shift-types' }"
                            @click="activeTab = 'shift-types'">
                        Shift Types
                    </button>
                    <button type="button"
                            class="tab-btn"
                            :class="{ active: activeTab === 'holidays' }"
                            @click="activeTab = 'holidays'">
                        Holiday Calendar
                    </button>
                </div>
            </div>
            <div class="action-btns">
                @canDo('Time & Attendance', 'edit')
                <button class="btn-outline" @click="showEditShiftModal = true">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                    </svg>
                    Edit Shift
                </button>
                @endcanDo
                @canDo('Time & Attendance', 'create')
                <button class="btn-primary" @click="showAssignShiftModal = true">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                    </svg>
                    Assign Shift
                </button>
                @endcanDo
            </div>
        </div>
        <div class="section-divider"></div>

        {{-- ══════════ TAB: WEEKLY SCHEDULE ══════════ --}}
        <div x-show="activeTab === 'weekly'" x-cloak
             x-data="{
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
                        <a href="{{ route('hr.shift.scheduling', ['tab' => 'weekly', 'week_start' => $selectedWeekStart->copy()->subWeek()->format('Y-m-d')]) }}"
                           class="week-btn">
                            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                            </svg>
                            Prev
                        </a>
                        <span class="week-label">{{ $selectedWeekStart->format('M j') }}–{{ $selectedWeekEnd->format('j') }}</span>
                        <a href="{{ route('hr.shift.scheduling', ['tab' => 'weekly', 'week_start' => $selectedWeekStart->copy()->addWeek()->format('Y-m-d')]) }}"
                           class="week-btn">
                            Next
                            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                            </svg>
                        </a>
                    </div>
                </div>
                <div class="toolbar-right">
                    <div class="search-box" style="flex:1; min-width:140px;">
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                        </svg>
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
                                            $setup = strtolower($cell['work_setup'] ?? 'wfh');
                                            $sName = $cell['shift_name'] ?? '—';
                                            $sLow  = strtolower($sName);
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
                                    No employees found.
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>{{-- end weekly tab --}}

        {{-- ══════════ TAB: SHIFT TYPES ══════════ --}}
        <div x-show="activeTab === 'shift-types'" x-cloak
             x-data="{
                 shiftSearch: '',
                 showEditShiftTypeModal: false,
                 editShift: { id: null, name: '', code: '', start_time: '', end_time: '', break_start: '', break_end: '', is_flexi: false, required_hours: '' },
                 saving: false,
                 errorMsg: '',
                 async openEdit(id) {
                     this.errorMsg = '';
                     const res = await fetch(`/hr/shift/type/${id}`, {
                         headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content }
                     });
                     const data = await res.json();
                     const bs = data.break_schedule ? JSON.parse(data.break_schedule) : {};
                     this.editShift = {
                         id:             data.id,
                         name:           data.name,
                         code:           data.code,
                         start_time:     data.start_time ? data.start_time.substring(0,5) : '',
                         end_time:       data.end_time   ? data.end_time.substring(0,5)   : '',
                         break_start:    bs.start ?? '',
                         break_end:      bs.end   ?? '',
                         is_flexi:       data.is_flexi    ?? false,
                         required_hours: data.required_hours ?? '',
                     };
                     this.showEditShiftTypeModal = true;
                 },
                 async submitEdit() {
                     this.errorMsg = '';
                     if (this.editShift.is_flexi && !this.editShift.required_hours) {
                         this.errorMsg = 'Required hours is needed for flexi schedule.'; return;
                     }
                     this.saving = true;
                     const res = await fetch(`/hr/shift/type/${this.editShift.id}/update`, {
                         method: 'POST',
                         headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content },
                         body: JSON.stringify({
                             name:           this.editShift.name,
                             code:           this.editShift.code,
                             start_time:     this.editShift.start_time,
                             end_time:       this.editShift.end_time,
                             break_start:    this.editShift.break_start || null,
                             break_end:      this.editShift.break_end   || null,
                             is_flexi:       this.editShift.is_flexi,
                             required_hours: this.editShift.is_flexi ? this.editShift.required_hours : null,
                         })
                     });
                     this.saving = false;
                     const data = await res.json();
                     if (res.ok) { window.location.reload(); }
                     else { this.errorMsg = data.message ?? 'Something went wrong.'; }
                 }
             }">

            <div class="table-card">
                <div class="table-toolbar">
                    <div class="toolbar-left" style="flex:1; min-width:0;">
                        <div class="search-box" style="flex:1; min-width:140px;">
                            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                      d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                            </svg>
                            <input type="text" placeholder="Search…" x-model="shiftSearch">
                        </div>
                    </div>
                    <div class="toolbar-right">
                        <form method="GET" action="{{ route('hr.shift.scheduling') }}" id="shiftTypeFilterForm" style="display:contents;">
                            <input type="hidden" name="tab" value="shift-types">
                            <select class="dept-select" name="department" onchange="document.getElementById('shiftTypeFilterForm').submit()">
                                <option value="">All Departments</option>
                                @foreach($departments ?? [] as $dept)
                                    <option value="{{ $dept->id }}" {{ request('department') == $dept->id ? 'selected' : '' }}>
                                        {{ $dept->name }}
                                    </option>
                                @endforeach
                            </select>
                        </form>
                        @canDo('Time & Attendance', 'create')
                        <button class="btn-primary" @click="showAddShiftTypeModal = true">
                            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                            </svg>
                            Add Shift Type
                        </button>
                        @endcanDo
                    </div>
                </div>
                <div class="table-scroll-wrap">
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
                            @php
                                $bs = is_string($shift->break_schedule)
                                    ? json_decode($shift->break_schedule, true)
                                    : $shift->break_schedule;
                            @endphp
                            <tr x-show="!shiftSearch || '{{ strtolower($shift->name) }}'.includes(shiftSearch.toLowerCase())">
                                <td style="font-weight:600;">
                                    {{ $shift->name }}
                                    <span style="font-size:11px;color:#9ca3af;font-weight:400;">({{ $shift->code }})</span>
                                    @if($shift->is_flexi)
                                    <span style="display:inline-block;font-size:10px;font-weight:600;color:#7c3aed;background:#ede9fe;border-radius:4px;padding:1px 6px;margin-left:4px;letter-spacing:.3px;">FLEXI</span>
                                    @endif
                                </td>
                                <td>{{ $shift->is_flexi ? '—' : \Carbon\Carbon::parse($shift->start_time)->format('g:i A') }}</td>
                                <td>
                                    {{ $shift->is_flexi ? '—' : (isset($bs['start'], $bs['end'])
                                        ? \Carbon\Carbon::parse($bs['start'])->format('g:i A') . ' – ' . \Carbon\Carbon::parse($bs['end'])->format('g:i A')
                                        : '—') }}
                                </td>
                                <td>{{ $shift->is_flexi ? '—' : \Carbon\Carbon::parse($shift->end_time)->format('g:i A') }}</td>
                                <td>{{ $shift->is_flexi ? ($shift->required_hours . 'h required') : ($shift->work_hours . 'h') }}</td>
                                <td><span class="night-diff-badge nd-none">None</span></td>
                                <td>{{ $shift->assigned ?? 0 }}</td>
                                <td>
                                    <button class="edit-btn" @click="openEdit({{ $shift->id }})">Edit</button>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="8" style="text-align:center; padding:40px 16px; color:#9ca3af; font-size:13px;">
                                    No shift types found. Add one using the button above.
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                {{-- Edit Shift Type Modal --}}
                <div x-show="showEditShiftTypeModal" class="modal-overlay" x-cloak @click.self="showEditShiftTypeModal = false">
                    <div class="modal-box">
                        <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:18px;">
                            <div class="modal-title" style="margin-bottom:0;">Edit Shift Type</div>
                            <button @click="showEditShiftTypeModal = false" style="width:32px;height:32px;border:2px solid #374151;border-radius:50%;display:flex;align-items:center;justify-content:center;background:none;cursor:pointer;">
                                <svg style="width:14px;height:14px;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"/>
                                </svg>
                            </button>
                        </div>
                        <template x-if="errorMsg">
                            <div style="background:#fee2e2;color:#b91c1c;border-radius:8px;padding:10px 14px;font-size:13px;margin-bottom:14px;" x-text="errorMsg"></div>
                        </template>
                        <div style="margin-bottom:16px;">
                            <label class="form-label">Shift Name</label>
                            <input type="text" class="form-input" x-model="editShift.name" placeholder="e.g. Day Shift">
                        </div>
                        <div style="margin-bottom:16px;">
                            <label class="form-label">Shift Code</label>
                            <input type="text" class="form-input" x-model="editShift.code" placeholder="e.g. DS-001">
                        </div>
                        <div style="margin-bottom:16px;display:flex;align-items:center;gap:10px;">
                            <label style="display:flex;align-items:center;gap:8px;cursor:pointer;font-size:13px;font-weight:500;color:#374151;">
                                <input type="checkbox" x-model="editShift.is_flexi" style="width:16px;height:16px;accent-color:#7c3aed;">
                                Flexi Schedule
                            </label>
                            <span style="font-size:11px;color:#9ca3af;">(no fixed start/end enforcement)</span>
                        </div>
                        <div x-show="editShift.is_flexi" style="margin-bottom:16px;">
                            <label class="form-label">Required Hours <span style="color:#dc2626;">*</span></label>
                            <input type="number" class="form-input" x-model="editShift.required_hours" min="1" max="24" step="0.5" placeholder="e.g. 8">
                        </div>
                        <div x-show="!editShift.is_flexi" class="form-row" style="margin-bottom:16px;">
                            <div>
                                <label class="form-label">Time In</label>
                                <input type="time" class="form-input" x-model="editShift.start_time">
                            </div>
                            <div>
                                <label class="form-label">Time Out</label>
                                <input type="time" class="form-input" x-model="editShift.end_time">
                            </div>
                        </div>
                        <div x-show="!editShift.is_flexi" class="form-row" style="margin-bottom:16px;">
                            <div>
                                <label class="form-label">Break Start</label>
                                <input type="time" class="form-input" x-model="editShift.break_start">
                            </div>
                            <div>
                                <label class="form-label">Break End</label>
                                <input type="time" class="form-input" x-model="editShift.break_end">
                            </div>
                        </div>
                        <div x-show="!editShift.is_flexi" style="margin-bottom:16px;">
                            <label class="form-label">Night Differential</label>
                            <input type="text" class="form-input" value="None" disabled style="background:#f9fafb;color:#9ca3af;cursor:not-allowed;">
                        </div>
                        <div class="modal-actions">
                            <button class="btn-cancel" @click="showEditShiftTypeModal = false">Cancel</button>
                            <button class="btn-save" @click="submitEdit()" :disabled="saving" x-text="saving ? 'Saving…' : 'Save Changes'"></button>
                        </div>
                    </div>
                </div>
            </div>
        </div>{{-- end shift-types tab --}}

        {{-- ══════════ TAB: HOLIDAY CALENDAR ══════════ --}}
        <div x-show="activeTab === 'holidays'" x-cloak
             x-data="{
                 holidaySearch: '',
                 typeFilter: '',
                 showEditHolidayModal: false,
                 editHoliday: { id: null, name: '', date: '', type: '', pay_rate: '', region: '', yearly: false },
                 saving: false,
                 errorMsg: '',
                 visible(name, type) {
                     const s = this.holidaySearch.toLowerCase();
                     const t = this.typeFilter.toLowerCase();
                     return (!s || name.toLowerCase().includes(s)) &&
                            (!t || type.toLowerCase() === t);
                 },
                 async openEdit(id) {
                     this.errorMsg = '';
                     const res = await fetch(`/hr/holidays/${id}`, {
                         headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content }
                     });
                     const data = await res.json();
                     this.editHoliday = {
                         id:       data.id,
                         name:     data.name,
                         date:     data.date,
                         type:     data.type,
                         pay_rate: data.pay_rate,
                         region:   data.region ?? '',
                         yearly:   data.yearly ?? false,
                     };
                     this.showEditHolidayModal = true;
                 },
                 async submitEdit() {
                     this.errorMsg = '';
                     this.saving = true;
                     const res = await fetch(`/hr/holidays/${this.editHoliday.id}/update`, {
                         method: 'POST',
                         headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content },
                         body: JSON.stringify(this.editHoliday)
                     });
                     this.saving = false;
                     const data = await res.json();
                     if (res.ok) { window.location.reload(); }
                     else { this.errorMsg = data.message ?? 'Something went wrong.'; }
                 },
                 async deleteHoliday(id) {
                     if (!confirm('Delete this holiday?')) return;
                     const res = await fetch(`/hr/holidays/${id}`, {
                         method: 'DELETE',
                         headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content }
                     });
                     if (res.ok) { window.location.reload(); }
                 }
             }">

            {{-- Holiday Stats --}}
            <div class="holiday-stat-grid">
                <div class="holiday-stat-card hsc-regular">
                    <div class="hsc-label">Regular Holidays</div>
                    <div class="hsc-value">{{ $regularHolidays ?? 0 }}</div>
                    <div class="hsc-sub">200% pay rate</div>
                </div>
                <div class="holiday-stat-card hsc-special">
                    <div class="hsc-label">Special Non-Working</div>
                    <div class="hsc-value">{{ $specialHolidays ?? 0 }}</div>
                    <div class="hsc-sub">130% pay rate</div>
                </div>
                <div class="holiday-stat-card hsc-local">
                    <div class="hsc-label">Local Holidays</div>
                    <div class="hsc-value">{{ $localHolidays ?? 0 }}</div>
                    <div class="hsc-sub">{{ $localRegion !== '—' ? $localRegion : 'None yet' }}</div>
                </div>
            </div>

            <div class="table-card">
                <div class="table-toolbar">
                    <div style="font-size:15px; font-weight:800; color:#111827;">{{ $currentYear }} HOLIDAYS</div>
                    <div class="toolbar-right">
                        <div class="search-box">
                            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                      d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                            </svg>
                            <input type="text" placeholder="Search holiday…" x-model="holidaySearch">
                        </div>
                        <select class="dept-select" x-model="typeFilter">
                            <option value="">All Types</option>
                            <option value="regular">Regular</option>
                            <option value="special">Special Non-Working</option>
                            <option value="local">Local</option>
                        </select>
                        @canDo('Time & Attendance', 'create')
                        <button class="btn-primary" @click="showAddHolidayModal = true">
                            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                            </svg>
                            Add Holiday
                        </button>
                        @endcanDo
                    </div>
                </div>
                <div class="table-scroll-wrap">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Holiday Name</th>
                                <th>Date</th>
                                <th>Day</th>
                                <th>Type</th>
                                <th>Pay Rate</th>
                                <th>Yearly</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($holidays ?? [] as $holiday)
                            @php $ht = strtolower($holiday->type ?? 'regular'); @endphp
                            <tr x-show="visible('{{ addslashes($holiday->name) }}', '{{ $ht }}')">
                                <td style="font-weight:600;">{{ $holiday->name }}</td>
                                <td>{{ \Carbon\Carbon::parse($holiday->date)->format('M j') }}</td>
                                <td>{{ \Carbon\Carbon::parse($holiday->date)->format('D') }}</td>
                                <td>
                                    <span class="type-badge {{ $ht === 'special' ? 'tb-special' : ($ht === 'local' ? 'tb-local' : 'tb-regular') }}">
                                        {{ $ht === 'special' ? 'Special' : ucfirst($ht) }}
                                    </span>
                                </td>
                                <td>{{ $holiday->pay_rate ?? '—' }}</td>
                                <td>
                                    <span style="font-size:12px; font-weight:600; color:{{ $holiday->yearly ? '#16a34a' : '#9ca3af' }}">
                                        {{ $holiday->yearly ? 'Yes' : 'No' }}
                                    </span>
                                </td>
                                <td style="display:flex; gap:6px; flex-wrap:wrap;">
                                    <button class="edit-btn" @click="openEdit({{ $holiday->id }})">Edit</button>
                                    <button class="delete-btn" @click="deleteHoliday({{ $holiday->id }})">Delete</button>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="7" style="text-align:center; padding:40px 16px; color:#9ca3af; font-size:13px;">
                                    No holidays found for {{ $currentYear }}. Add one using the button above.
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                {{-- Edit Holiday Modal --}}
                <div x-show="showEditHolidayModal" class="modal-overlay" x-cloak @click.self="showEditHolidayModal = false">
                    <div class="modal-box">
                        <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:18px;">
                            <div class="modal-title" style="margin-bottom:0;">Edit Holiday</div>
                            <button @click="showEditHolidayModal = false" style="width:32px;height:32px;border:2px solid #374151;border-radius:50%;display:flex;align-items:center;justify-content:center;background:none;cursor:pointer;">
                                <svg style="width:14px;height:14px;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"/>
                                </svg>
                            </button>
                        </div>
                        <template x-if="errorMsg">
                            <div style="background:#fee2e2;color:#b91c1c;border-radius:8px;padding:10px 14px;font-size:13px;margin-bottom:14px;" x-text="errorMsg"></div>
                        </template>
                        <div style="margin-bottom:16px;">
                            <label class="form-label">Holiday Name</label>
                            <input type="text" class="form-input" x-model="editHoliday.name" placeholder="Enter holiday name">
                        </div>
                        <div style="margin-bottom:16px;">
                            <label class="form-label">Date</label>
                            <input type="date" class="form-input" x-model="editHoliday.date">
                        </div>
                        <div style="margin-bottom:16px;">
                            <label class="form-label">Type</label>
                            <select class="form-input dept-select" style="width:100%;" x-model="editHoliday.type">
                                <option value="regular">Regular</option>
                                <option value="special">Special Non-Working</option>
                                <option value="local">Local</option>
                            </select>
                        </div>
                        <div style="margin-bottom:16px;">
                            <label class="form-label">Pay Rate</label>
                            <select class="form-input dept-select" style="width:100%;" x-model="editHoliday.pay_rate">
                                <option value="200%">200%</option>
                                <option value="130%">130%</option>
                                <option value="100%">100%</option>
                            </select>
                        </div>
                        <div style="margin-bottom:16px;">
                            <label class="form-label">Region (for local holidays)</label>
                            <input type="text" class="form-input" x-model="editHoliday.region" placeholder="e.g. Pangasinan">
                        </div>
                        <div style="margin-bottom:16px;">
                            <label style="display:flex;align-items:center;gap:8px;font-size:13px;color:#374151;cursor:pointer;">
                                <input type="checkbox" style="width:15px;height:15px;accent-color:#3b82f6;" x-model="editHoliday.yearly">
                                Repeat Yearly
                            </label>
                        </div>
                        <div class="modal-actions">
                            <button class="btn-cancel" @click="showEditHolidayModal = false">Cancel</button>
                            <button class="btn-save" @click="submitEdit()" :disabled="saving" x-text="saving ? 'Saving…' : 'Save Changes'"></button>
                        </div>
                    </div>
                </div>
            </div>
        </div>{{-- end holidays tab --}}

        {{-- ══════════ SHARED MODALS (outside tab panels) ══════════ --}}

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
                     const res = await fetch('{{ route('hr.shift.employees-by-dept') }}?employee_id=' + this.empId, {
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
                     const res = await fetch('{{ route('hr.shift.update') }}', {
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
                    <button @click="showEditShiftModal = false" style="width:32px;height:32px;border:2px solid #374151;border-radius:50%;display:flex;align-items:center;justify-content:center;background:none;cursor:pointer;">
                        <svg style="width:14px;height:14px;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
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
                                @if($shift->is_flexi)
                                    {{ $shift->name }} ({{ $shift->required_hours }}h required)
                                @else
                                    {{ $shift->name }} ({{ \Carbon\Carbon::parse($shift->start_time)->format('g:i A') }} – {{ \Carbon\Carbon::parse($shift->end_time)->format('g:i A') }})
                                @endif
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
        <script>window._hrShiftMeta = {!! json_encode(collect($shiftTypes ?? [])->keyBy('id')->map(fn($s) => ['is_flexi' => (bool)$s->is_flexi, 'required_hours' => $s->required_hours])) !!};</script>
        <div x-show="showAssignShiftModal" class="modal-overlay" x-cloak @click.self="showAssignShiftModal = false"
             x-data="{
                 deptId: '',
                 empId: '',
                 shiftId: '',
                 workSetup: '',
                 effectiveDate: '',
                 endDate: '',
                 daysOff: ['Sat','Sun'],
                 empList: [],
                 saving: false,
                 errorMsg: '',
                 shiftMeta: window._hrShiftMeta,
                 get selectedShift() { return this.shiftMeta[this.shiftId] ?? null; },
                 async onDeptChange() {
                     this.empId = '';
                     this.empList = [];
                     if (!this.deptId) return;
                     const res = await fetch('{{ route('hr.shift.employees-by-dept') }}?department_id=' + this.deptId, {
                         headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content }
                     });
                     this.empList = await res.json();
                 },
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
                     const res = await fetch('{{ route('hr.shift.assign') }}', {
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
                    <button @click="showAssignShiftModal = false" style="width:32px;height:32px;border:2px solid #374151;border-radius:50%;display:flex;align-items:center;justify-content:center;background:none;cursor:pointer;">
                        <svg style="width:14px;height:14px;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>
                <template x-if="errorMsg">
                    <div style="background:#fee2e2;color:#b91c1c;border-radius:8px;padding:10px 14px;font-size:13px;margin-bottom:14px;" x-text="errorMsg"></div>
                </template>
                <div style="margin-bottom:16px;">
                    <label class="form-label">Department</label>
                    <select class="form-input dept-select" style="width:100%;" x-model="deptId" @change="onDeptChange()">
                        <option value="">Choose department</option>
                        @foreach($departments ?? [] as $dept)
                            <option value="{{ $dept->id }}">{{ $dept->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div style="margin-bottom:16px;">
                    <label class="form-label">Employee</label>
                    <select class="form-input dept-select" style="width:100%;" x-model="empId"
                        :disabled="!deptId || empList.length === 0"
                        :style="!deptId ? 'opacity:0.5; cursor:not-allowed;' : ''">
                        <option value="">Choose a department first</option>
                        <template x-for="emp in empList" :key="emp.id">
                            <option :value="emp.id" x-text="emp.fname + ' ' + emp.lname"></option>
                        </template>
                    </select>
                    <p x-show="!deptId" style="font-size:11.5px;color:#9ca3af;margin-top:4px;">Select a department to load employees.</p>
                </div>
                <div style="margin-bottom:16px;">
                    <label class="form-label">Shift Type</label>
                    <select class="form-input dept-select" style="width:100%;" x-model="shiftId">
                        <option value="">Choose shift type</option>
                        @foreach($shiftTypes ?? [] as $shift)
                            <option value="{{ $shift->id }}">
                                @if($shift->is_flexi)
                                    {{ $shift->name }} ({{ $shift->required_hours }}h required)
                                @else
                                    {{ $shift->name }} ({{ \Carbon\Carbon::parse($shift->start_time)->format('g:i A') }} – {{ \Carbon\Carbon::parse($shift->end_time)->format('g:i A') }})
                                @endif
                            </option>
                        @endforeach
                    </select>
                    <template x-if="selectedShift && selectedShift.is_flexi">
                        <div style="margin-top:8px;padding:10px 14px;background:#ede9fe;border-radius:8px;font-size:13px;color:#5b21b6;display:flex;align-items:center;gap:8px;">
                            <svg style="width:15px;height:15px;flex-shrink:0;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                            Flexi schedule — employee must complete <strong style="margin-left:4px;" x-text="selectedShift.required_hours + 'h'"></strong>&nbsp;per day.
                        </div>
                    </template>
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

        {{-- Add Shift Type Modal --}}
        <div x-show="showAddShiftTypeModal" class="modal-overlay" x-cloak @click.self="showAddShiftTypeModal = false"
             x-data="{
                 name: '', code: '', start_time: '07:00', end_time: '16:00',
                 break_start: '12:00', break_end: '13:00',
                 is_flexi: false, required_hours: '',
                 saving: false, errorMsg: '',
                 async submit() {
                     this.errorMsg = '';
                     if (!this.name || !this.code) {
                         this.errorMsg = 'Please fill in all required fields.'; return;
                     }
                     if (!this.is_flexi && (!this.start_time || !this.end_time)) {
                         this.errorMsg = 'Please fill in all required fields.'; return;
                     }
                     if (this.is_flexi && !this.required_hours) {
                         this.errorMsg = 'Required hours is needed for flexi schedule.'; return;
                     }
                     this.saving = true;
                     const res = await fetch('{{ route('hr.shift.type.store') }}', {
                         method: 'POST',
                         headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content },
                         body: JSON.stringify({
                             name:           this.name,
                             code:           this.code,
                             start_time:     this.start_time,
                             end_time:       this.end_time,
                             break_start:    this.break_start || null,
                             break_end:      this.break_end   || null,
                             is_flexi:       this.is_flexi,
                             required_hours: this.is_flexi ? this.required_hours : null,
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
                    <div class="modal-title" style="margin-bottom:0;">Add Shift Type</div>
                    <button @click="showAddShiftTypeModal = false" style="width:32px;height:32px;border:2px solid #374151;border-radius:50%;display:flex;align-items:center;justify-content:center;background:none;cursor:pointer;">
                        <svg style="width:14px;height:14px;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>
                <template x-if="errorMsg">
                    <div style="background:#fee2e2;color:#b91c1c;border-radius:8px;padding:10px 14px;font-size:13px;margin-bottom:14px;" x-text="errorMsg"></div>
                </template>
                <div style="margin-bottom:16px;">
                    <label class="form-label">Shift Name</label>
                    <input type="text" class="form-input" x-model="name" placeholder="e.g. Early Morning Shift">
                </div>
                <div style="margin-bottom:16px;">
                    <label class="form-label">Shift Code</label>
                    <input type="text" class="form-input" x-model="code" placeholder="e.g. EMS-001">
                </div>
                <div style="margin-bottom:16px;display:flex;align-items:center;gap:10px;">
                    <label style="display:flex;align-items:center;gap:8px;cursor:pointer;font-size:13px;font-weight:500;color:#374151;">
                        <input type="checkbox" x-model="is_flexi" style="width:16px;height:16px;accent-color:#7c3aed;">
                        Flexi Schedule
                    </label>
                    <span style="font-size:11px;color:#9ca3af;">(no fixed start/end enforcement)</span>
                </div>
                <div x-show="!is_flexi" class="form-row" style="margin-bottom:16px;">
                    <div>
                        <label class="form-label">Time In</label>
                        <input type="time" class="form-input" x-model="start_time">
                    </div>
                    <div>
                        <label class="form-label">Time Out</label>
                        <input type="time" class="form-input" x-model="end_time">
                    </div>
                </div>
                <div x-show="!is_flexi" class="form-row" style="margin-bottom:16px;">
                    <div>
                        <label class="form-label">Break Start</label>
                        <input type="time" class="form-input" x-model="break_start">
                    </div>
                    <div>
                        <label class="form-label">Break End</label>
                        <input type="time" class="form-input" x-model="break_end">
                    </div>
                </div>
                <div x-show="!is_flexi" style="margin-bottom:16px;">
                    <label class="form-label">Night Differential</label>
                    <input type="text" class="form-input" value="None" disabled style="background:#f9fafb;color:#9ca3af;cursor:not-allowed;">
                </div>
                <div x-show="is_flexi" style="margin-bottom:16px;">
                    <label class="form-label">Required Hours <span style="color:#dc2626;">*</span></label>
                    <input type="number" class="form-input" x-model="required_hours" min="1" max="24" step="0.5" placeholder="e.g. 8">
                </div>
                <div class="modal-actions">
                    <button class="btn-cancel" @click="showAddShiftTypeModal = false">Cancel</button>
                    <button class="btn-save" @click="submit()" :disabled="saving" x-text="saving ? 'Saving…' : 'Add Shift Type'"></button>
                </div>
            </div>
        </div>

        {{-- Add Holiday Modal --}}
        <div x-show="showAddHolidayModal" class="modal-overlay" x-cloak @click.self="showAddHolidayModal = false"
             x-data="{
                 name: '', date: '', type: '', pay_rate: '', region: '', yearly: false,
                 saving: false, errorMsg: '',
                 async submit() {
                     this.errorMsg = '';
                     if (!this.name || !this.date || !this.type || !this.pay_rate) {
                         this.errorMsg = 'Please fill in all required fields.'; return;
                     }
                     this.saving = true;
                     const res = await fetch('{{ route('hr.holidays.store') }}', {
                         method: 'POST',
                         headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content },
                         body: JSON.stringify({
                             name: this.name, date: this.date, type: this.type,
                             pay_rate: this.pay_rate, region: this.region, yearly: this.yearly
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
                    <div class="modal-title" style="margin-bottom:0;">Add Holiday</div>
                    <button @click="showAddHolidayModal = false" style="width:32px;height:32px;border:2px solid #374151;border-radius:50%;display:flex;align-items:center;justify-content:center;background:none;cursor:pointer;">
                        <svg style="width:14px;height:14px;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>
                <template x-if="errorMsg">
                    <div style="background:#fee2e2;color:#b91c1c;border-radius:8px;padding:10px 14px;font-size:13px;margin-bottom:14px;" x-text="errorMsg"></div>
                </template>
                <div style="margin-bottom:16px;">
                    <label class="form-label">Holiday Name</label>
                    <input type="text" class="form-input" x-model="name" placeholder="Enter holiday name">
                </div>
                <div style="margin-bottom:16px;">
                    <label class="form-label">Date</label>
                    <input type="date" class="form-input" x-model="date">
                </div>
                <div style="margin-bottom:16px;">
                    <label class="form-label">Type</label>
                    <select class="form-input dept-select" style="width:100%;" x-model="type">
                        <option value="">Choose type</option>
                        <option value="regular">Regular</option>
                        <option value="special">Special Non-Working</option>
                        <option value="local">Local</option>
                    </select>
                </div>
                <div style="margin-bottom:16px;">
                    <label class="form-label">Pay Rate</label>
                    <select class="form-input dept-select" style="width:100%;" x-model="pay_rate">
                        <option value="">Choose rate</option>
                        <option value="200%">200%</option>
                        <option value="130%">130%</option>
                        <option value="100%">100%</option>
                    </select>
                </div>
                <div style="margin-bottom:16px;">
                    <label class="form-label">Region (for local holidays)</label>
                    <input type="text" class="form-input" x-model="region" placeholder="e.g. Pangasinan">
                </div>
                <div style="margin-bottom:16px;">
                    <label style="display:flex;align-items:center;gap:8px;font-size:13px;color:#374151;cursor:pointer;">
                        <input type="checkbox" style="width:15px;height:15px;accent-color:#3b82f6;" x-model="yearly">
                        Repeat Yearly
                    </label>
                </div>
                <div class="modal-actions">
                    <button class="btn-cancel" @click="showAddHolidayModal = false">Cancel</button>
                    <button class="btn-save" @click="submit()" :disabled="saving" x-text="saving ? 'Saving…' : 'Add Holiday'"></button>
                </div>
            </div>
        </div>

    </div>{{-- end page content --}}
</div>{{-- end main content --}}

<script>
    // Fix sidebar margin on load and resize
    (function() {
        var mainEl = document.querySelector('[x-on\\:resize\\.window]');
        if (!mainEl) return;
        function updateMargin() {
            var collapsed = localStorage.getItem('sidebarCollapsed') === 'true';
            mainEl.style.marginLeft = window.innerWidth < 1024 ? '0' : (collapsed ? '5rem' : '16rem');
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
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
    <link rel="icon" type="image/png" href="{{ asset('images/HRISLogo-Icon.png') }}">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=yes, viewport-fit=cover">
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
            grid-template-columns: repeat(2, 1fr);
            gap: 12px;
            margin-bottom: 18px;
        }
        @@media screen and (min-width: 1024px) {
            .stat-cards-grid {
                grid-template-columns: repeat(5, 1fr);
                gap: 16px;
                margin-bottom: 28px;
            }
            .stat-card .sc-icon { display: flex !important; }
            .stat-card .sc-label { font-size: 11px !important; letter-spacing: .7px !important; }
            .stat-card .sc-value { font-size: 28px !important; }
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

        .main-content-padding { padding: 24px 32px; }

        /* ========== MOBILE RESPONSIVENESS ========== */
        @@media (max-width: 1024px) {
            .desktop-sidebar { display: none !important; }

            /* ── Stat Cards: 2 columns on tablet/mobile ── */
            .stat-cards-grid {
                grid-template-columns: repeat(2, 1fr) !important;
                gap: 12px !important;
                margin-bottom: 18px !important;
            }
            .stat-card { padding: 14px 16px !important; }
            .stat-card .sc-icon { display: none !important; }
            .stat-card .sc-label { font-size: 10px !important; letter-spacing: .3px !important; }
            .stat-card .sc-value { font-size: 24px !important; }
            .stat-card .sc-sub { font-size: 11px !important; }
        }

        @@media (max-width: 768px) {
            /* ── Stat Cards already 2-col from above ── */
            .stat-card { padding: 14px 16px !important; }
            .stat-card .sc-value { font-size: 24px !important; }
            .stat-card .sc-label { font-size: 10px !important; letter-spacing: .3px !important; }
            .stat-card .sc-icon { display: none !important; }

            /* ── Table toolbar: title row + controls row ── */
            .table-toolbar {
                flex-direction: column !important;
                align-items: stretch !important;
                padding: 14px 14px 12px !important;
                gap: 10px !important;
            }
            .table-toolbar h2 { margin-bottom: 0; }

            /* ── toolbar-right: wrap into a 2-row grid ── */
            .toolbar-right {
                display: grid !important;
                grid-template-columns: 1fr 1fr !important;
                gap: 8px !important;
                align-items: stretch !important;
            }

            /* Search box spans full width (row 1) */
            .search-box {
                grid-column: 1 / -1 !important;
                width: 100% !important;
            }
            .search-box input { width: 100% !important; min-width: 0 !important; }

            /* Date picker - left cell (row 2) */
            .date-picker {
                width: 100% !important;
                min-width: 0 !important;
            }
            .date-picker input { width: 100% !important; min-width: 0 !important; }

            /* Dept select - right cell (row 2) */
            .dept-select {
                width: 100% !important;
                min-width: 0 !important;
                box-sizing: border-box !important;
            }

            /* Toggle btns span full width (row 3) */
            .toggle-btns {
                grid-column: 1 / -1 !important;
                display: flex !important;
                width: 100% !important;
            }
            .toggle-btn { flex: 1 !important; text-align: center !important; }

            /* detail view toolbar-right (period btns + date + export) */
            .period-btns {
                grid-column: 1 / -1 !important;
                display: flex !important;
                width: 100% !important;
            }
            .period-btn { flex: 1 !important; text-align: center !important; }

            .export-btn {
                grid-column: 1 / -1 !important;
                justify-content: center !important;
                width: 100% !important;
            }

            /* ── Tables: horizontal scroll ── */
            .att-table, .monthly-table, .detail-table { min-width: 650px; }
            .overflow-x-auto { overflow-x: auto; -webkit-overflow-scrolling: touch; }

            /* ── Header padding ── */
            header .px-8 { padding-left: 1rem !important; padding-right: 1rem !important; }

            /* ── Content area padding ── */
            .main-content-padding { padding: 14px !important; }

            /* ── Profile card ── */
            .profile-card { flex-direction: column; text-align: center; }
            .profile-avatar { margin: 0 auto; }

            /* ── Totals row: 2 columns ── */
            .totals-row { grid-template-columns: repeat(2, 1fr) !important; }
            .total-cell { border-right: none !important; border-bottom: 1px solid var(--border); }
            .total-cell:nth-child(odd) { border-right: 1px solid var(--border) !important; }
            .total-cell:last-child { border-bottom: none; }
            .total-cell:nth-last-child(2) { border-bottom: none; }

            /* ── Pagination ── */
            .att-pagination { justify-content: center; flex-wrap: wrap; }
        }

        @@media (max-width: 480px) {
            .stat-cards-grid { gap: 8px !important; }
            .stat-card .sc-value { font-size: 20px !important; }
            .breadcrumb .sep,
            .breadcrumb .crumb-current:not(:last-child) { display: none; }
        }
    </style>
</head>
<body class="bg-gray-50" x-data="{ mobileMenuOpen: false, collapsed: localStorage.getItem('sidebarCollapsed') === 'true' }"
     x-init="window.addEventListener('sidebar-toggle', e => { collapsed = e.detail.collapsed })">

{{-- ══════════ SIDEBAR ══════════ --}}
@php
    $sidebarUser     = auth()->user();
    $sidebarEmployee = $sidebarUser ? \App\Models\Employee::with('jobTitle')->where('user_id', $sidebarUser->id)->first() : null;
    $sidebarInitials = $sidebarEmployee
        ? strtoupper(substr($sidebarEmployee->fname, 0, 1) . substr($sidebarEmployee->lname, 0, 1))
        : ($sidebarUser ? strtoupper(substr($sidebarUser->email, 0, 2)) : 'U');
    $sidebarName = $sidebarEmployee
        ? trim($sidebarEmployee->fname . ' ' . $sidebarEmployee->lname)
        : ($sidebarUser?->email ?? 'User');
    $sidebarRole = $sidebarEmployee?->jobTitle?->title ?? ($sidebarUser?->role ?? '—');
    $isAttendanceSection = in_array($currentRoute, ['admin.attendance.reports','admin.attendance.employee','admin.attendance.today','admin.attendance.records']);
    $isEmployeesSection  = in_array($currentRoute, ['employees.directory','employees.profile']);
@endphp

{{-- DESKTOP SIDEBAR (visible on large screens) --}}
<div class="hidden lg:block desktop-sidebar">
    @include('admin.admin_sidebar')
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
        @include('admin.admin_sidebar')
    </div>
</div>

{{-- ══════════ MAIN CONTENT ══════════ --}}
<div x-data="{
        collapsed: localStorage.getItem('sidebarCollapsed') === 'true',
        editModal: false, editLogId: null, editClockIn: '', editClockOut: '', editError: '', editSaving: false,
        openEdit(id, ci, co) { this.editLogId = id; this.editClockIn = ci; this.editClockOut = co; this.editError = ''; this.editModal = true; },
        async saveEdit() {
            this.editSaving = true; this.editError = '';
            const res = await fetch(`/admin/attendance/log/${this.editLogId}/update`, {
                method: 'PUT',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content },
                body: JSON.stringify({ clock_in: this.editClockIn, clock_out: this.editClockOut || null })
            });
            this.editSaving = false;
            if (res.ok) { this.editModal = false; window.location.reload(); }
            else { const d = await res.json(); this.editError = d.message || 'Update failed.'; }
        }
    }"
     x-init="window.addEventListener('sidebar-toggle', e => { collapsed = e.detail.collapsed })"
     :style="window.innerWidth >= 1024 ? (collapsed ? 'margin-left:5rem' : 'margin-left:16rem') : 'margin-left:0'"
     x-on:resize.window="$el.style.marginLeft = window.innerWidth >= 1024 ? (collapsed ? '5rem' : '16rem') : '0'"
     style="transition: margin-left 0.35s cubic-bezier(0.4, 0, 0.2, 1); min-height:100vh;">

    <header class="bg-gradient-to-br from-blue-500 to-blue-700 sticky top-0 z-10 shadow-lg mt-4 mx-4 rounded-2xl">
        <div class="flex items-center justify-between px-8 py-[22px]">
            <div class="flex items-center gap-3">
                {{-- Hamburger: only visible on mobile --}}
                <button @click="mobileMenuOpen = true" class="lg:hidden p-1.5 rounded-lg hover:bg-white/20 transition-colors text-white">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M4 6h16M4 12h16M4 18h16"/>
                    </svg>
                </button>
                <div>
                    <h1 class="text-white text-[22px] font-bold tracking-[0.3px] m-0">Employee Attendance</h1>
                    <p class="text-white/65 text-[13px] mt-[3px] mb-0">Track and manage workforce attendance records</p>
                </div>
            </div>
            <div class="flex items-center gap-2.5">
                <x-notification-bell />
            </div>
        </div>
    </header>

    <div class="main-content-padding">

        @if($viewingDetail)
        {{-- ══════════ EMPLOYEE DETAIL VIEW ══════════ --}}

        <div class="breadcrumb">
            <a href="{{ route('admin.attendance.employee', ['view' => 'monthly', 'month' => $selectedMonth]) }}">
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
                    <form method="GET" action="{{ route('admin.attendance.employee') }}" id="detailForm" style="display:contents;">
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

                    <button onclick="exportAttendancePdf()" class="export-btn" style="border:none;cursor:pointer;">
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                        </svg>
                        Export
                    </button>
                </div>
            </div>

            <div style="overflow-x:auto; -webkit-overflow-scrolling:touch;">
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
                            <th></th>
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
                            $isPast = \Carbon\Carbon::parse($rec->date)->lt(\Carbon\Carbon::today());
                            $ot     = $rec->overtime_formatted  ?? '00h 00m';
                            $ut     = $rec->undertime_formatted ?? '00h 00m';
                            $ciVal  = $rec->time_in  ? \Carbon\Carbon::parse($rec->time_in)->format('H:i')  : '';
                            $coVal  = $rec->time_out ? \Carbon\Carbon::parse($rec->time_out)->format('H:i') : '';
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
                            <td>
                                @if($isPast)
                                <button @click="openEdit({{ $rec->id }}, '{{ $ciVal }}', '{{ $coVal }}')"
                                    style="background:none;border:1px solid #d1d5db;border-radius:6px;padding:3px 8px;cursor:pointer;color:#6b7280;font-size:11px;display:inline-flex;align-items:center;gap:4px;"
                                    title="Edit time in/out">
                                    <svg style="width:12px;height:12px;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/></svg>
                                    Edit
                                </button>
                                @endif
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="10" style="text-align:center; padding:40px 16px; color:#9ca3af; font-size:13px;">
                                No attendance records found for this period.
                            </td>
                        </tr>
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
                <div class="sc-value">{{ $presentCount ?? 0 }}</div>
                <div class="sc-sub">{{ $presentRate ?? '0%' }} attendance rate</div>
            </div>

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

        <div class="table-card">
            <div class="table-toolbar">
                <h2>Attendance Records</h2>
                <div class="toolbar-right">
                    <form method="GET" action="{{ route('admin.attendance.employee') }}" id="filterForm" style="display:contents">

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

            <div style="overflow-x:auto; -webkit-overflow-scrolling:touch;">

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
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($records ?? [] as $rec)
                        @php
                            $isPast = \Carbon\Carbon::parse($rec->attendance_date)->lt(\Carbon\Carbon::today());
                            $ciVal  = $rec->clock_in  ? \Carbon\Carbon::parse($rec->clock_in)->format('H:i')  : '';
                            $coVal  = $rec->clock_out ? \Carbon\Carbon::parse($rec->clock_out)->format('H:i') : '';
                        @endphp
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
                                    {{ $rec->shift->name ?? '—' }}
                                    <span class="shift-sub">{{ $rec->work_setup ? strtoupper($rec->work_setup) : '—' }}</span>
                                </div>
                            </td>
                            <td style="color:#374151;font-size:13px;">{{ $rec->shift ? \Carbon\Carbon::parse($rec->shift->start_time)->format('g:i A') . ' – ' . \Carbon\Carbon::parse($rec->shift->end_time)->format('g:i A') : '—' }}</td>
                            <td style="font-weight:600;">{{ $rec->clock_in  ? \Carbon\Carbon::parse($rec->clock_in)->format('g:i A')  : '—' }}</td>
                            <td style="font-weight:600;">{{ $rec->clock_out ? \Carbon\Carbon::parse($rec->clock_out)->format('g:i A') : '—' }}</td>
                            <td><span class="hours-pill ot">{{ $rec->overtime_minutes > 0 ? floor($rec->overtime_minutes/60).'h '.str_pad($rec->overtime_minutes%60,2,'0',STR_PAD_LEFT).'m' : '00h 00m' }}</span></td>
                            <td><span class="hours-pill ut">{{ $rec->undertime_minutes > 0 ? floor($rec->undertime_minutes/60).'h '.str_pad($rec->undertime_minutes%60,2,'0',STR_PAD_LEFT).'m' : '00h 00m' }}</span></td>
                            <td>
                                @php $st = strtolower($rec->status ?? 'present'); @endphp
                                <span class="status-badge badge-{{ $st }}">{{ ucfirst($rec->status ?? 'Present') }}</span>
                            </td>
                            <td>
                                @if($isPast)
                                <button @click="openEdit({{ $rec->id }}, '{{ $ciVal }}', '{{ $coVal }}')"
                                    style="background:none;border:1px solid #d1d5db;border-radius:6px;padding:3px 8px;cursor:pointer;color:#6b7280;font-size:11px;display:inline-flex;align-items:center;gap:4px;"
                                    title="Edit time in/out">
                                    <svg style="width:12px;height:12px;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/></svg>
                                    Edit
                                </button>
                                @endif
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="10" style="text-align:center; padding:40px 16px; color:#9ca3af; font-size:13px;">
                                No attendance records found for this date.
                            </td>
                        </tr>
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
                                <a href="{{ route('admin.attendance.employee', ['employee_id' => $rec->employee_id ?? '', 'view' => 'monthly', 'month' => request('month', now()->format('Y-m'))]) }}"
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
        @endif {{-- end @if($viewingDetail) --}}

    </div>

    {{-- Edit Attendance Modal --}}
    <div x-show="editModal" x-cloak style="position:fixed;inset:0;z-index:9999;background:rgba(0,0,0,0.4);">
        <div @click.outside="editModal=false" style="position:fixed;top:50%;left:50%;transform:translate(-50%,-50%);background:#fff;border-radius:16px;padding:28px;width:90%;max-width:380px;box-shadow:0 20px 60px rgba(0,0,0,0.2);">
            <h3 style="font-size:15px;font-weight:700;color:#111827;margin-bottom:16px;">Edit Attendance</h3>
            <div style="margin-bottom:14px;">
                <label style="display:block;font-size:12px;font-weight:600;color:#374151;margin-bottom:6px;">Time In</label>
                <input type="time" x-model="editClockIn" style="width:100%;border:1px solid #d1d5db;border-radius:8px;padding:8px 12px;font-size:13px;outline:none;">
            </div>
            <div style="margin-bottom:18px;">
                <label style="display:block;font-size:12px;font-weight:600;color:#374151;margin-bottom:6px;">Time Out <span style="font-weight:400;color:#9ca3af;">(optional)</span></label>
                <input type="time" x-model="editClockOut" style="width:100%;border:1px solid #d1d5db;border-radius:8px;padding:8px 12px;font-size:13px;outline:none;">
            </div>
            <div x-show="editError" x-text="editError" style="font-size:12px;color:#dc2626;margin-bottom:12px;"></div>
            <div style="display:flex;justify-content:flex-end;gap:10px;">
                <button @click="editModal=false" style="padding:8px 18px;border:1px solid #d1d5db;border-radius:8px;font-size:13px;cursor:pointer;background:#fff;color:#374151;">Cancel</button>
                <button @click="saveEdit()" :disabled="editSaving" style="padding:8px 18px;border:none;border-radius:8px;font-size:13px;cursor:pointer;background:#2563eb;color:#fff;font-weight:600;" x-text="editSaving ? 'Saving…' : 'Save'"></button>
            </div>
        </div>
    </div>
</div>

<script>
    function setView(viewType) {
        document.getElementById('viewInput').value = viewType;
        const btns = document.querySelectorAll('.toggle-btn');
        btns.forEach(btn => {
            btn.classList.remove('active');
            if (btn.innerText.toLowerCase() === viewType) {
                btn.classList.add('active');
            }
        });
        document.getElementById('filterForm').submit();
    }

    function setPeriod(periodValue) {
        document.getElementById('periodInput').value = periodValue;
        const btns = document.querySelectorAll('.period-btn');
        btns.forEach(btn => {
            btn.classList.remove('active');
            if (btn.innerText === 'Period ' + periodValue) {
                btn.classList.add('active');
            }
        });
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
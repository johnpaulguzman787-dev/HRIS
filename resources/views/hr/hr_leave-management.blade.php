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

    $attendanceRoutes = ['hr.attendance.reports','hr.attendance.employee','hr.attendance.shift','hr.attendance.leave','hr.shift.scheduling','hr.leave.management'];
    $employeeRoutes   = ['hr.employees.directory','hr.employees.profile'];
    $payrollRoutes    = ['hr.payroll','hr.payslips','hr.contributions'];
    $requestRoutes    = ['hr.requests.pending','hr.requests.approved'];

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
    <link rel="icon" type="image/png" href="{{ asset('images/HRISLogo-Icon.png') }}">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=yes, viewport-fit=cover">
    <title>Leave Management — MEDISOURCE</title>
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
            --blue: #3b82f6; --blue-dark: #1d4ed8; --blue-light: #eff6ff;
            --muted: #6b7280; --border: #e5e7eb;
        }
        .nav-item    { transition: background 0.15s, color 0.15s; }
        .chevron-icon { transition: transform 0.25s cubic-bezier(0.4,0,0.2,1); }

        /* ── TABS (Mobile Friendly - Scrollable) ── */
        .tab-nav {
            display: flex;
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

        /* ── LEAVE STAT CARDS (Responsive Grid) ── */
        .leave-cards {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 12px;
            margin-bottom: 24px;
        }
        @media (min-width: 768px) {
            .leave-cards {
                grid-template-columns: repeat(5, 1fr);
                gap: 16px;
            }
        }
        .leave-card {
            background: #fff; border: 1px solid var(--border); border-radius: 14px;
            padding: 16px; position: relative; box-shadow: 0 1px 6px rgba(0,0,0,.04);
        }
        .leave-card-label { font-size: 12px; color: var(--muted); font-weight: 500; margin-bottom: 6px; }
        .leave-card-value { font-size: 28px; font-weight: 800; color: #111827; line-height: 1; }
        .leave-card-sub   { font-size: 10px; color: #9ca3af; margin-top: 6px; }
        .leave-badge {
            position: absolute; top: 12px; right: 12px;
            padding: 2px 8px; border-radius: 20px; font-size: 10px; font-weight: 700;
        }
        .lb-vl   { background: #dbeafe; color: #1d4ed8; }
        .lb-sl   { background: #fce7f3; color: #be185d; }
        .lb-lwop { background: #ffedd5; color: #c2410c; }

        /* ── TOOLBAR (Mobile Stacking) ── */
        .toolbar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 16px;
            gap: 12px;
            flex-wrap: wrap;
        }
        .toolbar-title { font-size: 16px; font-weight: 700; color: #111827; }
        .toolbar-right {
            display: flex;
            align-items: center;
            gap: 10px;
            flex-wrap: wrap;
        }
        @media (max-width: 640px) {
            .toolbar { flex-direction: column; align-items: stretch; }
            .toolbar-right { justify-content: flex-start; }
        }

        .filter-select {
            appearance: none; background: #f9fafb; border: 1px solid var(--border);
            border-radius: 8px; padding: 8px 28px 8px 12px; font-size: 13px;
            color: #374151; cursor: pointer; outline: none; font-family: inherit;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='10' height='6' fill='none'%3E%3Cpath d='M1 1l4 4 4-4' stroke='%236b7280' stroke-width='1.5' stroke-linecap='round'/%3E%3C/svg%3E");
            background-repeat: no-repeat; background-position: right 10px center;
        }

        .btn-primary {
            display: inline-flex; align-items: center; gap: 6px;
            padding: 8px 16px; border-radius: 8px; font-size: 13px; font-weight: 600;
            font-family: inherit; cursor: pointer; border: none;
            background: var(--blue); color: #fff; transition: background .15s;
            white-space: nowrap;
        }
        .btn-primary:hover { background: var(--blue-dark); }
        .btn-primary svg { width: 14px; height: 14px; }

        .btn-outline-sm {
            display: inline-flex; align-items: center; gap: 4px;
            padding: 5px 12px; border-radius: 7px; font-size: 12px; font-weight: 600;
            font-family: inherit; cursor: pointer;
            border: 1.5px solid #bfdbfe; background: var(--blue-light); color: var(--blue-dark);
            transition: all .15s;
        }
        .btn-outline-sm:hover { background: #dbeafe; }

        /* ── TABLE (Horizontal Scroll on Mobile) ── */
        .table-card {
            background: #fff; border-radius: 14px; border: 1px solid var(--border);
            overflow: hidden; box-shadow: 0 1px 8px rgba(0,0,0,.04);
        }
        .table-scroll {
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
        }
        .data-table {
            width: 100%;
            border-collapse: collapse;
            min-width: 800px;
        }
        .data-table thead tr { background: #f9fafb; }
        .data-table th {
            padding: 11px 14px; font-size: 11px; font-weight: 600; color: var(--muted);
            text-align: left; border-bottom: 1px solid var(--border);
            white-space: nowrap; letter-spacing: .4px; text-transform: uppercase;
        }
        .data-table td {
            padding: 12px 14px; font-size: 12px; color: #111827;
            border-bottom: 1px solid #f3f4f6; vertical-align: middle;
        }
        .data-table tr:last-child td { border-bottom: none; }
        .data-table tbody tr:hover   { background: #fafafa; }

        /* ── LEAVE TYPE PILLS ── */
        .lt-vl   { background: #dbeafe; color: #1d4ed8; padding: 3px 10px; border-radius: 20px; font-size: 11px; font-weight: 600; display: inline-block; white-space: nowrap; }
        .lt-sl   { background: #fce7f3; color: #be185d; padding: 3px 10px; border-radius: 20px; font-size: 11px; font-weight: 600; display: inline-block; white-space: nowrap; }
        .lt-lwop { background: #ffedd5; color: #c2410c; padding: 3px 10px; border-radius: 20px; font-size: 11px; font-weight: 600; display: inline-block; white-space: nowrap; }
        .lt-ml   { background: #dcfce7; color: #15803d; padding: 3px 10px; border-radius: 20px; font-size: 11px; font-weight: 600; display: inline-block; white-space: nowrap; }
        .lt-pl   { background: #ede9fe; color: #6d28d9; padding: 3px 10px; border-radius: 20px; font-size: 11px; font-weight: 600; display: inline-block; white-space: nowrap; }
        .lt-spl  { background: #fef9c3; color: #a16207; padding: 3px 10px; border-radius: 20px; font-size: 11px; font-weight: 600; display: inline-block; white-space: nowrap; }

        /* ── STATUS BADGES ── */
        .status-pending  { background: #ffedd5; color: #c2410c; padding: 3px 10px; border-radius: 20px; font-size: 11px; font-weight: 600; display: inline-block; white-space: nowrap; }
        .status-approved { background: #dcfce7; color: #15803d; padding: 3px 10px; border-radius: 20px; font-size: 11px; font-weight: 600; display: inline-block; white-space: nowrap; }
        .status-rejected { background: #fee2e2; color: #dc2626; padding: 3px 10px; border-radius: 20px; font-size: 11px; font-weight: 600; display: inline-block; white-space: nowrap; }

        /* ── LEAVE CREDITS SECTION ── */
        .credits-3col {
            display: grid;
            grid-template-columns: repeat(1, 1fr);
            gap: 12px;
            margin-bottom: 24px;
        }
        @media (min-width: 640px) {
            .credits-3col {
                grid-template-columns: repeat(2, 1fr);
            }
        }
        @media (min-width: 1024px) {
            .credits-3col {
                grid-template-columns: repeat(3, 1fr);
            }
        }
        .credits-filter-row {
            display: flex;
            flex-direction: column;
            gap: 12px;
            margin-bottom: 20px;
        }
        @media (min-width: 640px) {
            .credits-filter-row {
                flex-direction: row;
                align-items: center;
                flex-wrap: wrap;
            }
        }

        /* ── CALENDAR (Mobile Friendly) ── */
        .cal-nav {
            display: flex;
            align-items: center;
            gap: 8px;
            flex-wrap: wrap;
        }
        .cal-month-label { font-size: 18px; font-weight: 800; color: #111827; }
        @media (min-width: 640px) {
            .cal-month-label { font-size: 20px; }
        }
        .cal-nav-btn {
            display: inline-flex; align-items: center; gap: 5px;
            padding: 6px 12px; border-radius: 7px; font-size: 12px;
            font-weight: 500; font-family: inherit; cursor: pointer;
            border: 1px solid var(--border); background: #fff; color: #374151;
            transition: background .15s; text-decoration: none;
        }
        .cal-nav-btn:hover { background: #f9fafb; }
        .cal-nav-btn svg { width: 12px; height: 12px; }

        .cal-wrap {
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
        }
        .cal-grid {
            width: 100%;
            border-collapse: collapse;
            background: #fff;
            border-radius: 14px;
            overflow: hidden;
            border: 1px solid var(--border);
            min-width: 500px;
        }
        .cal-grid th {
            padding: 8px 4px;
            font-size: 11px;
            font-weight: 700;
            color: var(--muted);
            text-align: center;
            background: #f9fafb;
            border-bottom: 1px solid var(--border);
            letter-spacing: .5px;
            text-transform: uppercase;
        }
        .cal-grid td {
            width: 14.28%;
            height: 80px;
            padding: 6px 4px;
            border: 1px solid #f3f4f6;
            vertical-align: top;
            font-size: 12px;
            font-weight: 600;
            color: #374151;
        }
        @media (min-width: 640px) {
            .cal-grid td {
                height: 100px;
                padding: 8px;
            }
        }
        .cal-grid td.other-month { background: #fafafa; color: #d1d5db; }
        .cal-event {
            display: block;
            padding: 2px 5px;
            border-radius: 4px;
            font-size: 9px;
            font-weight: 600;
            margin-top: 3px;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }
        @media (min-width: 640px) {
            .cal-event {
                font-size: 10px;
                padding: 2px 6px;
            }
        }
        .cal-vl      { background: #dbeafe; color: #1d4ed8; }
        .cal-sl      { background: #fce7f3; color: #be185d; }
        .cal-lwop    { background: #ffedd5; color: #c2410c; }
        .cal-holiday { background: #fee2e2; color: #dc2626; }

        .cal-legend {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-top: 16px;
            flex-wrap: wrap;
        }
        .cal-legend-item {
            display: flex;
            align-items: center;
            gap: 6px;
            font-size: 11px;
            color: #374151;
        }
        .cal-legend-dot  { width: 10px; height: 10px; border-radius: 3px; flex-shrink: 0; }

        /* ── LEAVE TYPES TABLE ── */
        .lt-code-badge {
            padding: 3px 9px; border-radius: 20px; font-size: 11px; font-weight: 700; display: inline-block;
        }
        .pay-paid   { background: #dcfce7; color: #15803d; padding: 3px 10px; border-radius: 20px; font-size: 11px; font-weight: 600; display: inline-block; white-space: nowrap; }
        .pay-unpaid { background: #fee2e2; color: #dc2626; padding: 3px 10px; border-radius: 20px; font-size: 11px; font-weight: 600; display: inline-block; white-space: nowrap; }
        .doc-req    { background: #ffedd5; color: #c2410c; padding: 3px 10px; border-radius: 20px; font-size: 11px; font-weight: 600; display: inline-block; white-space: nowrap; }
        .doc-not    { background: #f3f4f6; color: #6b7280; padding: 3px 10px; border-radius: 20px; font-size: 11px; font-weight: 600; display: inline-block; white-space: nowrap; }

        /* ── MOBILE CARDS FOR LEAVE TYPES ── */
        .lt-mobile-card {
            background: #fff;
            border: 1px solid var(--border);
            border-radius: 12px;
            padding: 14px;
            margin-bottom: 10px;
        }
        .lt-mobile-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 8px;
            flex-wrap: wrap;
            gap: 8px;
        }
        .lt-mobile-name { font-size: 14px; font-weight: 700; color: #111827; }
        .lt-mobile-meta {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
            margin-top: 8px;
            align-items: center;
        }
        @media (max-width: 639px) {
            .lt-desktop-table { display: none; }
            .lt-mobile-list   { display: block; }
        }
        @media (min-width: 640px) {
            .lt-desktop-table { display: block; }
            .lt-mobile-list   { display: none; }
        }

        /* ── MODAL (Mobile First - Slide Up) ── */
        .modal-overlay {
            position: fixed;
            inset: 0;
            background: rgba(0,0,0,0.5);
            display: flex;
            align-items: flex-end;
            justify-content: center;
            z-index: 1000;
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
            border-radius: 28px 28px 0 0;
            padding: 24px 20px 32px;
            width: 100%;
            max-width: 100%;
            max-height: 90vh;
            overflow-y: auto;
            box-shadow: 0 -4px 20px rgba(0,0,0,0.15);
        }
        @media (min-width: 640px) {
            .modal-box {
                border-radius: 24px;
                padding: 28px;
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
            .modal-box::before { display: none; }
        }
        .modal-title {
            font-size: 18px;
            font-weight: 700;
            color: #111827;
            margin-bottom: 20px;
            text-align: left;
        }
        .form-label {
            font-size: 12px;
            font-weight: 600;
            color: #374151;
            text-transform: uppercase;
            letter-spacing: .5px;
            margin-bottom: 6px;
            display: block;
        }
        .form-input, .form-select {
            width: 100%;
            border: 1.5px solid var(--border);
            border-radius: 10px;
            padding: 11px 13px;
            font-size: 14px;
            font-family: inherit;
            outline: none;
            color: #111827;
            background: #fff;
            transition: border-color .15s;
        }
        .form-input:focus, .form-select:focus { border-color: var(--blue); }
        .form-select {
            appearance: none;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='14' height='14' viewBox='0 0 24 24' fill='none' stroke='%236b7280' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpolyline points='6 9 12 15 18 9'%3E%3C/polyline%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: right 13px center;
        }
        .form-row {
            display: grid;
            grid-template-columns: 1fr;
            gap: 14px;
            margin-bottom: 16px;
        }
        @media (min-width: 640px) {
            .form-row {
                grid-template-columns: 1fr 1fr;
            }
        }
        .modal-actions {
            display: flex;
            gap: 12px;
            margin-top: 24px;
            flex-direction: column-reverse;
        }
        @media (min-width: 640px) {
            .modal-actions {
                flex-direction: row;
                justify-content: flex-end;
            }
        }
        .btn-cancel {
            padding: 11px 20px;
            border-radius: 10px;
            font-size: 14px;
            font-weight: 600;
            font-family: inherit;
            cursor: pointer;
            border: 1.5px solid #e5e7eb;
            background: #fff;
            color: #374151;
            width: 100%;
            text-align: center;
        }
        .btn-save {
            padding: 11px 20px;
            border-radius: 10px;
            font-size: 14px;
            font-weight: 600;
            font-family: inherit;
            cursor: pointer;
            border: none;
            background: var(--blue);
            color: #fff;
            width: 100%;
            text-align: center;
        }
        @media (min-width: 640px) {
            .btn-cancel, .btn-save {
                width: auto;
                min-width: 100px;
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
        .upload-area {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            gap: 8px;
            border: 2px dashed #d1d5db;
            border-radius: 12px;
            padding: 24px 16px;
            background: #f9fafb;
            cursor: pointer;
            transition: all .15s;
        }
        .upload-area:hover {
            border-color: var(--blue);
            background: #eff6ff;
        }
        @keyframes spin {
            from { transform: rotate(0deg); }
            to { transform: rotate(360deg); }
        }

        /* ── SIDEBAR & MAIN CONTENT RESPONSIVE ── */
        .desktop-sidebar { display: none; }
        @media (min-width: 1024px) {
            .desktop-sidebar { display: block; }
        }
        @media (max-width: 1023px) {
            .main-content {
                margin-left: 0 !important;
            }
        }
        .content-pad {
            padding: 16px;
        }
        @media (min-width: 640px) {
            .content-pad {
                padding: 24px 32px;
            }
        }
    </style>
</head>
<body class="bg-gray-50" x-data="{ mobileMenuOpen: false, collapsed: localStorage.getItem('sidebarCollapsed') === 'true' }"
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
<div x-data="{
         collapsed: localStorage.getItem('sidebarCollapsed') === 'true',
         showFileLeave: false,
         showAddLeaveType: false,
         showLeaveDetails: false,
         showEditLeaveType: false,
         selectedLeave: {},
         leaveError: '',
         cancelling: false,
         async openLeaveDetails(id) {
             this.leaveError = '';
             this.selectedLeave = {};
             const res = await fetch(`/hr/leave/${id}`, {
                 headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content }
             });
             const data = await res.json();
             if (res.ok) {
                 this.selectedLeave = data;
                 this.showLeaveDetails = true;
             } else {
                 this.leaveError = data.message ?? 'Failed to load leave details.';
             }
         },
         async cancelLeave(id) {
             if (!confirm('Are you sure you want to cancel this leave request?')) return;
             this.cancelling = true;
             const res = await fetch(`/hr/leave/${id}/cancel`, {
                 method: 'POST',
                 headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content }
             });
             this.cancelling = false;
             if (res.ok) { window.location.reload(); }
             else { this.leaveError = 'Failed to cancel leave request.'; }
         },
         async openEditLeaveType(id) {
             const res = await fetch(`/hr/leave/types/${id}`, {
                 headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content }
             });
             const data = await res.json();
             if (res.ok) {
                 this.$dispatch('set-edit-lt', data);
                 this.showEditLeaveType = true;
             }
         }
     }"
     x-init="
         window.addEventListener('sidebar-toggle', e => { collapsed = e.detail.collapsed });
         window.addEventListener('set-edit-lt', e => {
             const d = e.detail;
             if (document.querySelector('[x-show=\'showEditLeaveType\']') && document.querySelector('[x-show=\'showEditLeaveType\']').__x) {
                 document.querySelector('[x-show=\'showEditLeaveType\']').__x.$data.editLt = {
                     id:                 d.id,
                     name:               d.name,
                     code:               d.code,
                     days_entitled:      d.days_entitled ?? '',
                     is_paid:            !!d.is_paid,
                     requires_document:  !!d.requires_document,
                     applicable_to:      d.applicable_to ?? '',
                     carry_over:         !!d.carry_over,
                 };
             }
         });
     "
     :style="window.innerWidth >= 1024 ? (collapsed ? 'margin-left:5rem' : 'margin-left:16rem') : 'margin-left:0'"
     x-on:resize.window="$el.style.marginLeft = window.innerWidth >= 1024 ? (collapsed ? '5rem' : '16rem') : '0'"
     style="transition: margin-left 0.35s cubic-bezier(0.4, 0, 0.2, 1); min-height:100vh;"
     class="main-content">

    {{-- HEADER with Hamburger --}}
    <header class="bg-gradient-to-br from-blue-500 to-blue-700 sticky top-0 z-10 shadow-lg mt-4 mx-4 rounded-2xl">
        <div class="flex items-center justify-between px-6 py-4">
            <div class="flex items-center gap-3">
                <button @click="mobileMenuOpen = true" class="lg:hidden p-1.5 rounded-lg hover:bg-white/20 transition-colors text-white">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M4 6h16M4 12h16M4 18h16"/>
                    </svg>
                </button>
                <h1 class="text-white font-bold text-lg">Leave Management</h1>
            </div>
            <x-hr-notif />
        </div>
    </header>

    <div class="content-pad">

        {{-- Tab Nav --}}
        <div class="tab-nav">
            <a href="{{ route('hr.leave.management', ['tab' => 'my-leave']) }}"      class="tab-btn {{ $activeTab === 'my-leave'      ? 'active' : '' }}">My Leave</a>
            <a href="{{ route('hr.leave.management', ['tab' => 'leave-credits']) }}" class="tab-btn {{ $activeTab === 'leave-credits'  ? 'active' : '' }}">Leave Credits</a>
            <a href="{{ route('hr.leave.management', ['tab' => 'leave-calendar']) }}" class="tab-btn {{ $activeTab === 'leave-calendar' ? 'active' : '' }}">Leave Calendar</a>
            <a href="{{ route('hr.leave.management', ['tab' => 'leave-types']) }}"   class="tab-btn {{ $activeTab === 'leave-types'    ? 'active' : '' }}">Leave Types</a>
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
                <select class="filter-select" onchange="window.location.href='{{ route('hr.leave.management') }}?tab=my-leave&status='+this.value">
                    <option value="">All Status</option>
                    <option value="pending" {{ request('status')==='pending' ? 'selected' : '' }}>Pending</option>
                    <option value="approved" {{ request('status')==='approved' ? 'selected' : '' }}>Approved</option>
                    <option value="rejected" {{ request('status')==='rejected' ? 'selected' : '' }}>Rejected</option>
                </select>
                <select class="filter-select" onchange="window.location.href='{{ route('hr.leave.management') }}?tab=my-leave&type='+this.value+'&status={{ request('status') }}'">
                    <option value="">All Types</option>
                    @foreach($leaveTypes as $lt)
                        <option value="{{ $lt->id }}" {{ request('type') == $lt->id ? 'selected' : '' }}>{{ $lt->name }}</option>
                    @endforeach
                </select>
                @canDo('Leave Management', 'create')
                <button class="btn-primary" @click="showFileLeave = true">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                    File Leave
                </button>
                @endcanDo
            </div>
        </div>

        <div class="table-card">
            <div class="table-scroll">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Ref #</th><th>Leave Type</th><th>Filed On</th><th>Date From</th><th>Date To</th><th>Days</th><th>Reason</th><th>Approver</th><th>Status</th><th></th>
                        </tr>
                    </thead>
                    <tbody>
                    @forelse($myLeaveRequests as $req)
                    <tr>
                        <td style="font-weight:600;color:#6b7280;">{{ $req->ref_no }}</td>
                        <td><span class="lt-{{ strtolower($req->leaveType->code ?? 'vl') }}" style="background:#dbeafe;color:#1d4ed8;padding:3px 10px;border-radius:20px;font-size:11px;font-weight:600;display:inline-block;">{{ $req->leaveType->name ?? '—' }}</span></td>
                        <td>{{ $req->created_at->format('m/d/Y') }}</td>
                        <td>{{ $req->start_date->format('m/d/Y') }}</td>
                        <td>{{ $req->end_date->format('m/d/Y') }}</td>
                        <td>{{ $req->total_days }}</td>
                        <td style="color:#6b7280;max-width:150px;">{{ $req->reason }}</td>
                        <td>
                            <div style="font-weight:700;font-size:12px;">{{ $req->approver ? trim($req->approver->fname.' '.$req->approver->lname) : '—' }}</div>
                            <div style="font-size:10px;color:#9ca3af;">{{ $req->approver?->jobTitle?->title ?? '—' }}</div>
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
        </div>

        {{-- ══════════ LEAVE CREDITS ══════════ --}}
        @elseif($activeTab === 'leave-credits')

        <div class="toolbar" style="margin-bottom:20px;">
            <div class="toolbar-title">Employee Leave Credits</div>
        </div>

        <div class="credits-filter-row">
            <form method="GET" action="{{ route('hr.leave.management') }}" id="creditsFilterForm" style="display:flex;flex-wrap:wrap;gap:12px;align-items:center;">
                <input type="hidden" name="tab" value="leave-credits">
                <div style="font-size:13px;color:#6b7280;font-weight:500;">View credits for:</div>
                <select class="filter-select" name="employee_id" style="min-width:180px;" onchange="document.getElementById('creditsFilterForm').submit()">
                    <option value="">Choose employee</option>
                    @foreach($employees as $emp)
                        <option value="{{ $emp->id }}" {{ request('employee_id') == $emp->id ? 'selected' : '' }}>
                            {{ trim($emp->fname.' '.$emp->lname) }}
                        </option>
                    @endforeach
                </select>
                <select class="filter-select" name="year" onchange="document.getElementById('creditsFilterForm').submit()">
                    <option value="{{ now()->year }}" {{ $currentYear == now()->year ? 'selected' : '' }}>{{ now()->year }}</option>
                    <option value="{{ now()->year - 1 }}" {{ $currentYear == now()->year - 1 ? 'selected' : '' }}>{{ now()->year - 1 }}</option>
                </select>
            </form>
        </div>

        <div class="credits-3col">
            @forelse($leaveTypes as $lt)
            @php $key = strtolower($lt->code); @endphp
            <div class="leave-card">
                <span class="leave-badge" style="background:#dbeafe;color:#1d4ed8;">{{ $lt->code }}</span>
                <div class="leave-card-label">{{ $lt->name }}</div>
                <div class="leave-card-value">{{ $creditStats[$key.'_used'] ?? 0 }}</div>
                <div class="leave-card-sub">{{ $creditStats[$key.'_remaining'] ?? 0 }} remaining of {{ $creditStats[$key.'_total'] ?? $lt->days_entitled ?? 0 }}</div>
            </div>
            @empty
            <div class="leave-card">
                <div class="leave-card-label">No leave types configured yet.</div>
            </div>
            @endforelse
        </div>

        <div class="toolbar">
            <div class="toolbar-title">Leave History</div>
            <div class="toolbar-right">
                <select class="filter-select" onchange="window.location.href='{{ route('hr.leave.management') }}?tab=my-leave&status='+this.value">
                    <option value="">All Status</option>
                    <option value="pending" {{ request('status')==='pending' ? 'selected' : '' }}>Pending</option>
                    <option value="approved" {{ request('status')==='approved' ? 'selected' : '' }}>Approved</option>
                    <option value="rejected" {{ request('status')==='rejected' ? 'selected' : '' }}>Rejected</option>
                </select>
                <select class="filter-select" onchange="window.location.href='{{ route('hr.leave.management') }}?tab=my-leave&type='+this.value+'&status={{ request('status') }}'">
                    <option value="">All Types</option>
                    @foreach($leaveTypes as $lt)
                        <option value="{{ $lt->id }}" {{ request('type') == $lt->id ? 'selected' : '' }}>{{ $lt->name }}</option>
                    @endforeach
                </select>
            </div>
        </div>

        <div class="table-card">
            <div class="table-scroll">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Ref #</th><th>Leave Type</th><th>Filed On</th><th>Date From</th><th>Date To</th><th>Days</th><th>Reason</th><th>Approver</th><th>Status</th><th></th>
                        </tr>
                    </thead>
                    <tbody>
                    @forelse($leaveHistory as $req)
                    <tr>
                        <td style="font-weight:600;color:#6b7280;">{{ $req->ref_no }}</td>
                        <td><span style="background:#dbeafe;color:#1d4ed8;padding:3px 10px;border-radius:20px;font-size:11px;font-weight:600;display:inline-block;">{{ $req->leaveType->name ?? '—' }}</span></td>
                        <td>{{ $req->created_at->format('m/d/Y') }}</td>
                        <td>{{ $req->start_date->format('m/d/Y') }}</td>
                        <td>{{ $req->end_date->format('m/d/Y') }}</td>
                        <td>{{ $req->total_days }}</td>
                        <td style="color:#6b7280;">{{ $req->reason }}</td>
                        <td>
                            <div style="font-weight:700;font-size:12px;">{{ $req->approver ? trim($req->approver->fname.' '.$req->approver->lname) : '—' }}</div>
                            <div style="font-size:10px;color:#9ca3af;">{{ $req->approver?->jobTitle?->title ?? '—' }}</div>
                        </td>
                        <td><span class="status-{{ $req->status }}">{{ ucfirst($req->status) }}</span></td>
                        <td><button class="btn-outline-sm" @click="openLeaveDetails({{ $req->id }})">View</button></td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="10" style="text-align:center;padding:40px 16px;color:#9ca3af;font-size:13px;">
                            No leave history found.
                        </td>
                    </tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        {{-- ══════════ LEAVE CALENDAR ══════════ --}}
        @elseif($activeTab === 'leave-calendar')

        <div class="toolbar">
            <div class="cal-nav">
                <a href="{{ route('hr.leave.management', ['tab' => 'leave-calendar', 'month' => $calPrev]) }}" class="cal-nav-btn">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                    Previous
                </a>
                <span class="cal-month-label">{{ $calMonth->format('F Y') }}</span>
                <a href="{{ route('hr.leave.management', ['tab' => 'leave-calendar', 'month' => $calNext]) }}" class="cal-nav-btn">
                    Next
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                </a>
            </div>
            <div class="toolbar-right">
                <form method="GET" action="{{ route('hr.leave.management') }}" id="calFilterForm" style="display:flex;gap:10px;flex-wrap:wrap;">
                    <input type="hidden" name="tab" value="leave-calendar">
                    <input type="hidden" name="month" value="{{ $calMonth->format('Y-m') }}">
                    <select class="filter-select" name="department" onchange="document.getElementById('calFilterForm').submit()">
                        <option value="">All Departments</option>
                        @foreach($departments as $d)
                            <option value="{{ $d->id }}" {{ request('department') == $d->id ? 'selected' : '' }}>{{ $d->name }}</option>
                        @endforeach
                    </select>
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

        <div class="cal-wrap">
            <table class="cal-grid">
                <thead>
                    <tr>
                        @foreach(['SUN','MON','TUE','WED','THU','FRI','SAT'] as $dh)
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
        </div>

        <div class="cal-legend">
            @foreach($leaveTypes as $lt)
            <div class="cal-legend-item">
                <div class="cal-legend-dot" style="background:#dbeafe;"></div>
                {{ $lt->code }} – {{ $lt->name }}
            </div>
            @endforeach
        </div>

        {{-- ══════════ LEAVE TYPES ══════════ --}}
        @elseif($activeTab === 'leave-types')

        <div class="toolbar">
            <div class="toolbar-title">Leave Types</div>
            <div class="toolbar-right">
                @canDo('Leave Management', 'edit')
                <button class="btn-primary" @click="showAddLeaveType = true">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                    Add Leave Type
                </button>
                @endcanDo
            </div>
        </div>

        {{-- DESKTOP TABLE --}}
        <div class="table-card lt-desktop-table">
            <div class="table-scroll">
                <table class="data-table" style="min-width:580px;">
                    <thead>
                        <tr>
                            <th>Leave Type</th><th>Code</th><th>Days Entitled</th><th>Pay</th><th>Document</th><th>Applicable To</th><th></th>
                        </tr>
                    </thead>
                    <tbody>
                    @forelse($leaveTypes as $lt)
                    <tr>
                        <td style="font-weight:600;">{{ $lt->name }}</td>
                        <td><span class="lt-code-badge" style="background:#f3f4f6;color:#374151;">{{ $lt->code }}</span></td>
                        <td>{{ $lt->days_entitled ?? '—' }}</td>
                        <td><span class="{{ $lt->is_paid ? 'pay-paid' : 'pay-unpaid' }}">{{ $lt->is_paid ? 'Paid' : 'Unpaid' }}</span></td>
                        <td><span class="{{ $lt->requires_document ? 'doc-req' : 'doc-not' }}">{{ $lt->requires_document ? 'Required' : 'Not Required' }}</span></td>
                        <td style="color:#6b7280;">{{ $lt->applicable_to ?? '—' }}</td>
                        <td><button class="btn-outline-sm" @click="openEditLeaveType({{ $lt->id }})">Edit</button></td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" style="text-align:center;padding:40px 16px;color:#9ca3af;font-size:13px;">
                            No leave types found. Add one using the button above.
                        </td>
                    </tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        {{-- MOBILE CARDS FOR LEAVE TYPES --}}
        <div class="lt-mobile-list">
            @foreach($leaveTypes as $lt)
            <div class="lt-mobile-card">
                <div class="lt-mobile-row">
                    <span class="lt-mobile-name">{{ $lt->name }}</span>
                    <span class="lt-code-badge" style="background:#f3f4f6;color:#374151;">{{ $lt->code }}</span>
                </div>
                <div style="font-size:12px;color:#6b7280;margin-bottom:8px;">
                    {{ $lt->days_entitled ?? '—' }} days · {{ $lt->applicable_to ?? 'All employees' }}
                </div>
                <div class="lt-mobile-meta">
                    <span class="{{ $lt->is_paid ? 'pay-paid' : 'pay-unpaid' }}">{{ $lt->is_paid ? 'Paid' : 'Unpaid' }}</span>
                    <span class="{{ $lt->requires_document ? 'doc-req' : 'doc-not' }}">{{ $lt->requires_document ? 'Required' : 'Not Required' }}</span>
                    <button class="btn-outline-sm" style="margin-left:auto;" @click="openEditLeaveType({{ $lt->id }})">Edit</button>
                </div>
            </div>
            @endforeach
        </div>
        @endif

        {{-- ══════════ MODALS ══════════ --}}

        {{-- File Leave / Leave Request Modal --}}
        <div x-show="showFileLeave" class="modal-overlay" x-cloak @click.self="showFileLeave = false"
             x-data="{
                 leaveTypeId: '',
                 startDate: '',
                 endDate: '',
                 reason: '',
                 fileName: '',
                 saving: false,
                 errorMsg: '',
                 handleFile(e) {
                     const f = e.target.files[0];
                     this.fileName = f ? f.name : '';
                 },
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
                     const fileInput = document.getElementById('leaveDocInput');
                     if (fileInput.files[0]) form.append('document', fileInput.files[0]);
                     form.append('_token', document.querySelector('meta[name=csrf-token]').content);
                     const res = await fetch('{{ route('hr.leave.file') }}', { method: 'POST', body: form });
                     this.saving = false;
                     const data = await res.json();
                     if (res.ok) { window.location.reload(); }
                     else { this.errorMsg = data.message ?? 'Something went wrong.'; }
                 }
             }">
            <div class="modal-box">
                <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:18px;">
                    <div class="modal-title">Leave Request</div>
                    <button @click="showFileLeave = false" class="close-btn">
                        <svg style="width:14px;height:14px;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>

                <template x-if="errorMsg">
                    <div style="background:#fee2e2;color:#b91c1c;border-radius:8px;padding:10px 14px;font-size:13px;margin-bottom:14px;" x-text="errorMsg"></div>
                </template>

                <div style="margin-bottom:16px;">
                    <label class="form-label">Leave Type</label>
                    <select class="form-select" x-model="leaveTypeId">
                        <option value="">Choose leave type</option>
                        @foreach($leaveTypes as $lt)
                        <option value="{{ $lt->id }}">{{ $lt->name }} ({{ $lt->code }})</option>
                        @endforeach
                    </select>
                </div>

                <div class="form-row" style="margin-bottom:16px;">
                    <div>
                        <label class="form-label">Start Date</label>
                        <input type="date" class="form-input" x-model="startDate">
                    </div>
                    <div>
                        <label class="form-label">End Date</label>
                        <input type="date" class="form-input" x-model="endDate">
                    </div>
                </div>

                <div style="margin-bottom:16px;">
                    <label class="form-label">Reason/Remarks</label>
                    <input type="text" class="form-input" x-model="reason" placeholder="Enter brief description of your leave reason">
                </div>

                <div style="margin-bottom:16px;">
                    <label class="form-label">Supporting Document</label>
                    <label class="upload-area" for="leaveDocInputMobile" style="cursor:pointer;">
                        <svg style="width:28px;height:28px;color:#9ca3af;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 13h6m-3-3v6m5 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                        <div style="font-size:13px;font-weight:600;color:#374151;" x-text="fileName || 'Choose a file to upload'"></div>
                        <div style="font-size:11px;color:#9ca3af;">PDF or DOCX, max 10MB</div>
                        <input id="leaveDocInputMobile" type="file" accept=".pdf,.docx" style="display:none;" @change="handleFile($event)">
                    </label>
                    <input id="leaveDocInput" type="file" accept=".pdf,.docx" style="display:none;" @change="handleFile($event)">
                </div>

                <div class="modal-actions">
                    <button class="btn-cancel" @click="showFileLeave = false">Cancel</button>
                    <button class="btn-save" @click="submit()" :disabled="saving">
                        <span x-show="saving" style="display:inline-flex;align-items:center;gap:5px;">
                            <svg style="width:13px;height:13px;animation:spin 0.8s linear infinite;flex-shrink:0;" viewBox="0 0 24 24" fill="none"><circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" style="opacity:.3"/><path fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg>
                            Submitting…
                        </span>
                        <span x-show="!saving">Submit</span>
                    </button>
                </div>
            </div>
        </div>

        {{-- Add Leave Type Modal --}}
        <div x-show="showAddLeaveType" class="modal-overlay" x-cloak @click.self="showAddLeaveType = false"
             x-data="{
                 name: '', code: '', days_entitled: '', is_paid: true,
                 requires_document: false, applicable_to: '', carry_over: false,
                 saving: false, errorMsg: '',
                 async submit() {
                     this.errorMsg = '';
                     if (!this.name || !this.code) {
                         this.errorMsg = 'Leave type name and code are required.'; return;
                     }
                     this.saving = true;
                     const res = await fetch('{{ route('hr.leave.type.store') }}', {
                         method: 'POST',
                         headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content },
                         body: JSON.stringify({
                             name:               this.name,
                             code:               this.code,
                             days_entitled:      this.days_entitled || null,
                             is_paid:            this.is_paid,
                             requires_document:  this.requires_document,
                             applicable_to:      this.applicable_to,
                             carry_over:         this.carry_over,
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
                    <div class="modal-title">Add Leave Type</div>
                    <button @click="showAddLeaveType = false" class="close-btn">
                        <svg style="width:14px;height:14px;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>

                <template x-if="errorMsg">
                    <div style="background:#fee2e2;color:#b91c1c;border-radius:8px;padding:10px 14px;font-size:13px;margin-bottom:14px;" x-text="errorMsg"></div>
                </template>

                <div class="form-row" style="margin-bottom:16px;">
                    <div>
                        <label class="form-label">Leave Type Name</label>
                        <input type="text" class="form-input" x-model="name" placeholder="e.g. Vacation Leave">
                    </div>
                    <div>
                        <label class="form-label">Code</label>
                        <input type="text" class="form-input" x-model="code" placeholder="e.g. VL">
                    </div>
                </div>

                <div style="margin-bottom:16px;">
                    <label class="form-label">Days Entitled</label>
                    <select class="form-select" x-model="days_entitled">
                        <option value="">No fixed limit</option>
                        @for($d = 1; $d <= 120; $d++)
                            <option value="{{ $d }}">{{ $d }}</option>
                        @endfor
                    </select>
                </div>

                <div class="form-row" style="margin-bottom:16px;">
                    <div>
                        <label class="form-label">Pay Type</label>
                        <div style="display:flex;gap:16px;margin-top:8px;">
                            <label style="display:flex;align-items:center;gap:6px;font-size:13px;color:#374151;cursor:pointer;">
                                <input type="radio" name="add_is_paid" style="accent-color:#3b82f6;" :checked="is_paid" @change="is_paid = true"> Paid
                            </label>
                            <label style="display:flex;align-items:center;gap:6px;font-size:13px;color:#374151;cursor:pointer;">
                                <input type="radio" name="add_is_paid" style="accent-color:#3b82f6;" :checked="!is_paid" @change="is_paid = false"> Unpaid
                            </label>
                        </div>
                    </div>
                    <div>
                        <label class="form-label">Document Required</label>
                        <div style="display:flex;gap:16px;margin-top:8px;">
                            <label style="display:flex;align-items:center;gap:6px;font-size:13px;color:#374151;cursor:pointer;">
                                <input type="checkbox" style="width:15px;height:15px;accent-color:#3b82f6;" x-model="requires_document"> Required
                            </label>
                        </div>
                    </div>
                </div>

                <div style="margin-bottom:16px;">
                    <label class="form-label">Applicable To</label>
                    <input type="text" class="form-input" x-model="applicable_to" placeholder="e.g. All regular employees">
                </div>

                <div style="margin-bottom:16px;">
                    <label style="display:flex;align-items:center;gap:8px;font-size:13px;color:#374151;cursor:pointer;">
                        <input type="checkbox" style="width:15px;height:15px;accent-color:#3b82f6;" x-model="carry_over">
                        Allow carry over to next year
                    </label>
                </div>

                <div class="modal-actions">
                    <button class="btn-cancel" @click="showAddLeaveType = false">Cancel</button>
                    <button class="btn-save" @click="submit()" :disabled="saving" x-text="saving ? 'Saving…' : 'Add Leave Type'"></button>
                </div>
            </div>
        </div>

        {{-- View Leave Details Modal --}}
        <div x-show="showLeaveDetails" class="modal-overlay" x-cloak @click.self="showLeaveDetails = false">
            <div class="modal-box">
                <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:18px;">
                    <div class="modal-title">Leave Details</div>
                    <button @click="showLeaveDetails = false" class="close-btn">
                        <svg style="width:14px;height:14px;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>

                <template x-if="leaveError">
                    <div style="background:#fee2e2;color:#b91c1c;border-radius:8px;padding:10px 14px;font-size:13px;margin-bottom:14px;" x-text="leaveError"></div>
                </template>

                <div style="margin-bottom:14px;">
                    <label class="form-label">Ref #</label>
                    <div class="form-input" style="background:#f9fafb;color:#6b7280;cursor:default;" x-text="selectedLeave.ref_no || '—'"></div>
                </div>

                <div style="margin-bottom:14px;">
                    <label class="form-label">Leave Type</label>
                    <div class="form-input" style="background:#f9fafb;color:#6b7280;cursor:default;" x-text="selectedLeave.leave_type || '—'"></div>
                </div>

                <div class="form-row" style="margin-bottom:14px;">
                    <div>
                        <label class="form-label">Start Date</label>
                        <div class="form-input" style="background:#f9fafb;color:#6b7280;cursor:default;" x-text="selectedLeave.start_date || '—'"></div>
                    </div>
                    <div>
                        <label class="form-label">End Date</label>
                        <div class="form-input" style="background:#f9fafb;color:#6b7280;cursor:default;" x-text="selectedLeave.end_date || '—'"></div>
                    </div>
                </div>

                <div style="margin-bottom:14px;">
                    <label class="form-label">Total Days</label>
                    <div class="form-input" style="background:#f9fafb;color:#6b7280;cursor:default;" x-text="selectedLeave.total_days || '—'"></div>
                </div>

                <div style="margin-bottom:14px;">
                    <label class="form-label">Reason/Remarks</label>
                    <div class="form-input" style="background:#f9fafb;color:#6b7280;cursor:default;" x-text="selectedLeave.reason || '—'"></div>
                </div>

                <div style="margin-bottom:14px;">
                    <label class="form-label">Status</label>
                    <div class="form-input" style="background:#f9fafb;color:#6b7280;cursor:default;" x-text="selectedLeave.status ? selectedLeave.status.charAt(0).toUpperCase() + selectedLeave.status.slice(1) : '—'"></div>
                </div>

                <template x-if="selectedLeave.rejection_reason">
                    <div style="margin-bottom:14px;">
                        <label class="form-label">Rejection Reason</label>
                        <div class="form-input" style="background:#fef2f2;color:#dc2626;cursor:default;" x-text="selectedLeave.rejection_reason"></div>
                    </div>
                </template>

                <div class="modal-actions">
                    @canDo('Leave Management', 'create')
                    <template x-if="selectedLeave.status === 'pending'">
                        <button class="btn-cancel" style="background:#fef2f2;color:#dc2626;border-color:#fecaca;"
                            @click="cancelLeave(selectedLeave.id)" x-text="cancelling ? 'Cancelling…' : 'Cancel Request'"></button>
                    </template>
                    @endcanDo
                    <button class="btn-save" @click="showLeaveDetails = false">Close</button>
                </div>
            </div>
        </div>

        {{-- Edit Leave Type Modal --}}
        <div x-show="showEditLeaveType" class="modal-overlay" x-cloak @click.self="showEditLeaveType = false"
             x-data="{
                 editLt: { id: null, name: '', code: '', days_entitled: '', is_paid: true, requires_document: false, applicable_to: '', carry_over: false },
                 saving: false, errorMsg: '',
                 async submitEdit() {
                     this.errorMsg = '';
                     if (!this.editLt.name || !this.editLt.code) {
                         this.errorMsg = 'Name and code are required.'; return;
                     }
                     this.saving = true;
                     const res = await fetch(`/hr/leave/types/${this.editLt.id}/update`, {
                         method: 'POST',
                         headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content },
                         body: JSON.stringify(this.editLt)
                     });
                     this.saving = false;
                     const data = await res.json();
                     if (res.ok) { window.location.reload(); }
                     else { this.errorMsg = data.message ?? 'Something went wrong.'; }
                 }
             }">
            <div class="modal-box">
                <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:18px;">
                    <div class="modal-title">Edit Leave Type</div>
                    <button @click="showEditLeaveType = false" class="close-btn">
                        <svg style="width:14px;height:14px;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>

                <template x-if="errorMsg">
                    <div style="background:#fee2e2;color:#b91c1c;border-radius:8px;padding:10px 14px;font-size:13px;margin-bottom:14px;" x-text="errorMsg"></div>
                </template>

                <div class="form-row" style="margin-bottom:16px;">
                    <div>
                        <label class="form-label">Leave Type Name</label>
                        <input type="text" class="form-input" x-model="editLt.name">
                    </div>
                    <div>
                        <label class="form-label">Code</label>
                        <input type="text" class="form-input" x-model="editLt.code">
                    </div>
                </div>

                <div style="margin-bottom:16px;">
                    <label class="form-label">Days Entitled</label>
                    <select class="form-select" x-model="editLt.days_entitled">
                        <option value="">No fixed limit</option>
                        @for($d = 1; $d <= 120; $d++)
                            <option value="{{ $d }}">{{ $d }}</option>
                        @endfor
                    </select>
                </div>

                <div class="form-row" style="margin-bottom:16px;">
                    <div>
                        <label class="form-label">Pay Type</label>
                        <div style="display:flex;gap:16px;margin-top:8px;">
                            <label style="display:flex;align-items:center;gap:6px;font-size:13px;color:#374151;cursor:pointer;">
                                <input type="radio" name="edit_is_paid" style="accent-color:#3b82f6;" :checked="editLt.is_paid" @change="editLt.is_paid = true"> Paid
                            </label>
                            <label style="display:flex;align-items:center;gap:6px;font-size:13px;color:#374151;cursor:pointer;">
                                <input type="radio" name="edit_is_paid" style="accent-color:#3b82f6;" :checked="!editLt.is_paid" @change="editLt.is_paid = false"> Unpaid
                            </label>
                        </div>
                    </div>
                    <div>
                        <label class="form-label">Document Required</label>
                        <div style="display:flex;gap:16px;margin-top:8px;">
                            <label style="display:flex;align-items:center;gap:6px;font-size:13px;color:#374151;cursor:pointer;">
                                <input type="checkbox" style="width:15px;height:15px;accent-color:#3b82f6;" x-model="editLt.requires_document"> Required
                            </label>
                        </div>
                    </div>
                </div>

                <div style="margin-bottom:16px;">
                    <label class="form-label">Applicable To</label>
                    <input type="text" class="form-input" x-model="editLt.applicable_to">
                </div>

                <div style="margin-bottom:16px;">
                    <label style="display:flex;align-items:center;gap:8px;font-size:13px;color:#374151;cursor:pointer;">
                        <input type="checkbox" style="width:15px;height:15px;accent-color:#3b82f6;" x-model="editLt.carry_over">
                        Allow carry over to next year
                    </label>
                </div>

                <div class="modal-actions">
                    <button class="btn-cancel" @click="showEditLeaveType = false">Cancel</button>
                    <button class="btn-save" @click="submitEdit()" :disabled="saving" x-text="saving ? 'Saving…' : 'Save Changes'"></button>
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
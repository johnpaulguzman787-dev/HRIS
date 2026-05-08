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
    $attendanceRoutes = ['finance_officer.attendance.reports', 'finance_officer.attendance.shift', 'finance_officer.attendance.leave', 'finance_officer.leave.management'];
    $payrollRoutes    = ['finance_officer.payroll', 'finance_officer.payslips', 'finance_officer.contributions'];
    $requestRoutes    = ['finance_officer.requests.pending', 'finance_officer.requests.approved'];

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
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=yes, viewport-fit=cover">
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

        /* DESKTOP SIDEBAR */
        .desktop-sidebar { display: none; }
        @media (min-width: 1024px) { .desktop-sidebar { display: block; } }
        @media (max-width: 1023px) {
            .main-content-margin { margin-left: 0 !important; }
        }

        /* ─── TABS (Responsive) ─────────────────────────────────────────────── */
        .tab-nav {
            display: flex;
            border-bottom: 2px solid var(--border);
            margin-bottom: 20px;
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
            scrollbar-width: none;
        }
        .tab-nav::-webkit-scrollbar { display: none; }
        .tab-btn {
            padding: 10px 16px;
            font-size: 13px;
            font-weight: 500;
            color: var(--muted);
            border: none;
            background: none;
            cursor: pointer;
            font-family: inherit;
            border-bottom: 3px solid transparent;
            margin-bottom: -2px;
            white-space: nowrap;
            text-decoration: none;
            display: inline-block;
            transition: color .15s, border-color .15s;
        }
        @media (min-width: 640px) {
            .tab-btn { padding: 10px 24px; font-size: 14px; }
        }
        .tab-btn:hover { color: #374151; }
        .tab-btn.active { color: var(--blue); border-bottom-color: var(--blue); font-weight: 700; }

        /* ─── STAT CARDS (Responsive Grid) ────────────────────────────────────────── */
        .leave-cards {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 12px;
            margin-bottom: 24px;
        }
        @media (min-width: 768px) {
            .leave-cards { grid-template-columns: repeat(5, 1fr); gap: 16px; }
        }
        .leave-card {
            background: #fff;
            border: 1px solid var(--border);
            border-radius: 14px;
            padding: 16px;
            position: relative;
            box-shadow: 0 1px 6px rgba(0,0,0,.04);
        }
        @media (min-width: 640px) { .leave-card { padding: 20px 22px; } }
        .leave-card-label { font-size: 12px; color: var(--muted); font-weight: 500; margin-bottom: 6px; line-height: 1.3; }
        @media (min-width: 640px) { .leave-card-label { font-size: 13px; } }
        .leave-card-value { font-size: 32px; font-weight: 800; color: #111827; line-height: 1; }
        @media (min-width: 640px) { .leave-card-value { font-size: 34px; } }
        .leave-card-sub   { font-size: 11px; color: #9ca3af; margin-top: 6px; }
        @media (min-width: 640px) { .leave-card-sub { font-size: 11.5px; } }
        .leave-badge {
            position: absolute;
            top: 14px;
            right: 14px;
            padding: 3px 9px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 700;
        }
        @media (min-width: 640px) { .leave-badge { top: 18px; right: 18px; padding: 3px 10px; font-size: 12px; } }

        /* ─── TOOLBAR (Responsive) ───────────────────────────────────────────── */
        .toolbar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 14px;
            gap: 10px;
            flex-wrap: wrap;
        }
        .toolbar-title { font-size: 15px; font-weight: 700; color: #111827; }
        @media (min-width: 640px) { .toolbar-title { font-size: 16px; } }
        .toolbar-right { display: flex; align-items: center; gap: 8px; flex-wrap: wrap; }
        @media (max-width: 640px) {
            .toolbar { flex-direction: column; align-items: stretch; }
            .toolbar-right { flex-direction: column; align-items: stretch; }
            .toolbar-right .filter-select { width: 100%; }
            .toolbar-right .btn-primary { width: 100%; justify-content: center; }
        }

        .filter-select {
            appearance: none;
            background: #f9fafb;
            border: 1px solid var(--border);
            border-radius: 8px;
            padding: 7px 26px 7px 11px;
            font-size: 12px;
            color: #374151;
            cursor: pointer;
            outline: none;
            font-family: inherit;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='10' height='6' fill='none'%3E%3Cpath d='M1 1l4 4 4-4' stroke='%236b7280' stroke-width='1.5' stroke-linecap='round'/%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: right 9px center;
        }
        @media (min-width: 640px) { .filter-select { font-size: 13px; padding: 8px 28px 8px 12px; } }

        .btn-primary {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            padding: 8px 16px;
            border-radius: 50px;
            font-size: 13px;
            font-weight: 600;
            font-family: inherit;
            cursor: pointer;
            border: none;
            background: var(--blue);
            color: #fff;
            white-space: nowrap;
            transition: background .15s;
        }
        .btn-primary:hover { background: var(--blue-dark); }
        .btn-primary svg { width: 14px; height: 14px; }

        .btn-outline-sm {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            padding: 5px 12px;
            border-radius: 7px;
            font-size: 11px;
            font-weight: 600;
            font-family: inherit;
            cursor: pointer;
            border: 1.5px solid #bfdbfe;
            background: var(--blue-light);
            color: var(--blue-dark);
            white-space: nowrap;
            transition: all .15s;
        }
        .btn-outline-sm:hover { background: #dbeafe; }
        @media (min-width: 640px) { .btn-outline-sm { padding: 5px 14px; font-size: 12px; } }

        /* ─── MOBILE CARD VIEW (for records) ─────────────────────────────────────────────── */
        .mobile-table { display: none; }
        .desktop-table { display: block; }
        @media (max-width: 768px) {
            .desktop-table { display: none; }
            .mobile-table { display: block; }
        }

        .req-card {
            background: #fff;
            border-radius: 14px;
            border: 1px solid var(--border);
            padding: 16px;
            margin-bottom: 10px;
        }
        .req-card-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 10px;
        }
        .req-card-ref { font-size: 14px; font-weight: 700; color: #111827; }
        .req-card-filed { font-size: 11px; color: #9ca3af; margin-top: 2px; }
        .req-card-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 8px;
            margin-bottom: 10px;
        }
        .req-card-label {
            font-size: 10px;
            font-weight: 700;
            color: #9ca3af;
            text-transform: uppercase;
            letter-spacing: .4px;
            margin-bottom: 2px;
        }
        .req-card-value { font-size: 12px; color: #374151; font-weight: 500; }
        .req-card-footer {
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-top: 1px solid #f3f4f6;
            padding-top: 10px;
            margin-top: 4px;
        }

        /* ─── DESKTOP TABLE ─────────────────────────────────────────────── */
        .table-card {
            background: #fff;
            border-radius: 14px;
            border: 1px solid var(--border);
            overflow: hidden;
            box-shadow: 0 1px 8px rgba(0,0,0,.04);
        }
        .table-scroll { overflow-x: auto; -webkit-overflow-scrolling: touch; }
        .data-table { width: 100%; border-collapse: collapse; min-width: 700px; }
        .data-table thead tr { background: #f9fafb; }
        .data-table th {
            padding: 10px 14px;
            font-size: 10.5px;
            font-weight: 600;
            color: var(--muted);
            text-align: left;
            border-bottom: 1px solid var(--border);
            white-space: nowrap;
            letter-spacing: .4px;
            text-transform: uppercase;
        }
        @media (min-width: 640px) { .data-table th { padding: 11px 16px; font-size: 11.5px; } }
        .data-table td {
            padding: 12px 14px;
            font-size: 12px;
            color: #111827;
            border-bottom: 1px solid #f3f4f6;
            vertical-align: middle;
        }
        @media (min-width: 640px) { .data-table td { padding: 14px 16px; font-size: 13px; } }
        .data-table tr:last-child td { border-bottom: none; }
        .data-table tbody tr:hover { background: #fafafa; }

        /* ─── STATUS BADGES ─────────────────────────────────────── */
        .status-pending  { background: #ffedd5; color: #c2410c; padding: 3px 10px; border-radius: 20px; font-size: 11px; font-weight: 600; display: inline-block; white-space: nowrap; }
        .status-approved { background: #dcfce7; color: #15803d; padding: 3px 10px; border-radius: 20px; font-size: 11px; font-weight: 600; display: inline-block; white-space: nowrap; }
        .status-rejected { background: #fee2e2; color: #dc2626; padding: 3px 10px; border-radius: 20px; font-size: 11px; font-weight: 600; display: inline-block; white-space: nowrap; }
        @media (min-width: 640px) {
            .status-pending, .status-approved, .status-rejected { padding: 3px 12px; font-size: 12px; }
        }

        /* ─── CALENDAR ──────────────────────────────────────────── */
        .cal-header {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 16px;
            flex-wrap: wrap;
        }
        .cal-month-label { font-size: 20px; font-weight: 800; color: #111827; }
        .cal-nav-btn {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            padding: 6px 12px;
            border-radius: 7px;
            font-size: 12px;
            font-weight: 500;
            font-family: inherit;
            cursor: pointer;
            border: 1px solid var(--border);
            background: #fff;
            color: #374151;
            text-decoration: none;
            white-space: nowrap;
            transition: background .15s;
        }
        .cal-nav-btn:hover { background: #f9fafb; }
        .cal-nav-btn svg { width: 12px; height: 12px; }

        .cal-filters {
            display: flex;
            gap: 10px;
            margin-bottom: 14px;
            flex-wrap: wrap;
        }
        .cal-filter-select {
            flex: 1;
            min-width: 120px;
            appearance: none;
            background: #f9fafb;
            border: 1px solid var(--border);
            border-radius: 8px;
            padding: 8px 26px 8px 12px;
            font-size: 13px;
            color: #374151;
            cursor: pointer;
            outline: none;
            font-family: inherit;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='10' height='6' fill='none'%3E%3Cpath d='M1 1l4 4 4-4' stroke='%236b7280' stroke-width='1.5' stroke-linecap='round'/%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: right 9px center;
        }

        .cal-wrap { overflow-x: auto; -webkit-overflow-scrolling: touch; }
        .cal-grid { width: 100%; border-collapse: collapse; background: #fff; border-radius: 14px; overflow: hidden; border: 1px solid var(--border); min-width: 500px; }
        .cal-grid th {
            padding: 8px 4px;
            font-size: 10px;
            font-weight: 700;
            color: var(--muted);
            text-align: center;
            background: #f9fafb;
            border-bottom: 1px solid var(--border);
            letter-spacing: .5px;
            text-transform: uppercase;
        }
        @media (min-width: 640px) { .cal-grid th { padding: 10px; font-size: 12px; } }
        .cal-grid td {
            width: 14.28%;
            height: 72px;
            padding: 6px 4px;
            border: 1px solid #f3f4f6;
            vertical-align: top;
            font-size: 12px;
            font-weight: 600;
            color: #374151;
        }
        @media (min-width: 640px) { .cal-grid td { height: 100px; padding: 8px; font-size: 13px; } }
        .cal-grid td.other-month { background: #fafafa; color: #d1d5db; }
        .cal-event {
            display: block;
            padding: 2px 5px;
            border-radius: 4px;
            font-size: 9.5px;
            font-weight: 600;
            margin-top: 3px;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
            line-height: 1.4;
        }
        @media (min-width: 640px) { .cal-event { padding: 2px 8px; font-size: 11px; margin-top: 4px; } }
        .cal-vl      { background: #dbeafe; color: #1d4ed8; }
        .cal-sl      { background: #fce7f3; color: #be185d; }
        .cal-lwop    { background: #ffedd5; color: #c2410c; }
        .cal-holiday { background: #fee2e2; color: #dc2626; }
        .cal-legend { display: flex; align-items: center; gap: 14px; margin-top: 14px; flex-wrap: wrap; }
        .cal-legend-item { display: flex; align-items: center; gap: 6px; font-size: 11px; color: #374151; }
        @media (min-width: 640px) { .cal-legend-item { font-size: 12px; gap: 8px; } }
        .cal-legend-dot  { width: 11px; height: 11px; border-radius: 3px; flex-shrink: 0; }
        @media (min-width: 640px) { .cal-legend-dot { width: 12px; height: 12px; } }

        /* ─── MODAL (Mobile First - Slide Up) ─────────────────────*/
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
                padding: 28px 28px 32px;
                max-width: 520px;
                max-height: 85vh;
            }
        }
        .modal-box::before {
            content: '';
            display: block;
            width: 40px;
            height: 4px;
            background: #e5e7eb;
            border-radius: 2px;
            margin: 0 auto 20px;
        }
        @media (min-width: 640px) { .modal-box::before { display: none; } }
        .modal-title { font-size: 20px; font-weight: 700; color: #111827; margin-bottom: 24px; text-align: left; }
        @media (min-width: 640px) { .modal-title { font-size: 20px; } }
        .form-label { font-size: 13px; font-weight: 600; color: #374151; margin-bottom: 6px; display: block; }
        .form-input, .form-select {
            width: 100%;
            border: 1.5px solid #e5e7eb;
            border-radius: 12px;
            padding: 12px 14px;
            font-size: 14px;
            font-family: inherit;
            outline: none;
            color: #111827;
            background: #fff;
            transition: border-color .15s;
        }
        .form-input:focus, .form-select:focus { border-color: var(--blue); ring: none; }
        .form-select {
            appearance: none;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='16' height='16' viewBox='0 0 24 24' fill='none' stroke='%236b7280' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpolyline points='6 9 12 15 18 9'%3E%3C/polyline%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: right 14px center;
        }
        .form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 14px; }
        @media (max-width: 640px) { .form-row { grid-template-columns: 1fr; gap: 12px; } }
        .modal-actions {
            display: flex;
            gap: 12px;
            margin-top: 28px;
            flex-direction: column-reverse;
        }
        @media (min-width: 640px) {
            .modal-actions { flex-direction: row; justify-content: flex-end; }
        }
        .btn-cancel {
            padding: 12px 20px;
            border-radius: 12px;
            font-size: 15px;
            font-weight: 600;
            font-family: inherit;
            cursor: pointer;
            border: 1.5px solid #e5e7eb;
            background: #fff;
            color: #374151;
            width: 100%;
        }
        .btn-save {
            padding: 12px 20px;
            border-radius: 12px;
            font-size: 15px;
            font-weight: 600;
            font-family: inherit;
            cursor: pointer;
            border: none;
            background: var(--blue);
            color: #fff;
            width: 100%;
        }
        @media (min-width: 640px) { .btn-cancel, .btn-save { width: auto; min-width: 100px; } }
        .close-btn {
            position: absolute;
            top: 20px;
            right: 20px;
            width: 36px;
            height: 36px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #f3f4f6;
            cursor: pointer;
            border: none;
        }
        .detail-field {
            background: #f9fafb;
            border: 1.5px solid #e5e7eb;
            border-radius: 12px;
            padding: 12px 14px;
            font-size: 14px;
            color: #111827;
            width: 100%;
        }
        .detail-label {
            font-size: 12px;
            font-weight: 600;
            color: #6b7280;
            margin-bottom: 4px;
            display: block;
        }
        .upload-area {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            gap: 10px;
            border: 2px dashed #d1d5db;
            border-radius: 16px;
            padding: 32px 20px;
            background: #f9fafb;
            cursor: pointer;
            transition: all .15s;
        }
        .upload-area:hover { border-color: var(--blue); background: #eff6ff; }
        @keyframes spin { from { transform: rotate(0deg); } to { transform: rotate(360deg); } }

        .main-content-padding { padding: 24px 32px; }
        @media (max-width: 768px) { .main-content-padding { padding: 14px !important; } }
    </style>
</head>
<body class="bg-gray-50" x-data="{
    mobileMenuOpen: false,
    sidebarCollapsed: localStorage.getItem('sidebarCollapsed') === 'true',
    showFileLeave: false,
    showLeaveDetails: false,
    selectedLeave: {},
    leaveError: '',
    cancelling: false,
    async openLeaveDetails(id) {
        this.leaveError = '';
        this.selectedLeave = {};
        const res = await fetch(`/finance_officer/leave/${id}`, {
            headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content }
        });
        const data = await res.json();
        if (res.ok) { this.selectedLeave = data; this.showLeaveDetails = true; }
        else { this.leaveError = data.message ?? 'Failed to load leave details.'; }
    },
    async cancelLeave(id) {
        if (!confirm('Are you sure you want to cancel this leave request?')) return;
        this.cancelling = true;
        const res = await fetch(`/finance_officer/leave/${id}/cancel`, {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content }
        });
        this.cancelling = false;
        if (res.ok) { window.location.reload(); }
        else { this.leaveError = 'Failed to cancel leave request.'; }
    }
}"
     x-init="window.addEventListener('sidebar-toggle', e => { sidebarCollapsed = e.detail.collapsed })">

{{-- ═══════════ DESKTOP SIDEBAR ═══════════ --}}
<div class="hidden lg:block desktop-sidebar">
    @include('finance_officer.finance_sidebar')
</div>

{{-- ═══════════ MOBILE SLIDE-OUT DRAWER ═══════════ --}}
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
        @include('finance_officer.finance_sidebar')
    </div>
</div>

{{-- ══════════ MAIN CONTENT ══════════ --}}
<div class="main-content-margin"
     :style="window.innerWidth >= 1024 ? (sidebarCollapsed ? 'margin-left:5rem' : 'margin-left:16rem') : 'margin-left:0'"
     x-on:resize.window="$el.style.marginLeft = window.innerWidth >= 1024 ? (sidebarCollapsed ? '5rem' : '16rem') : '0'"
     style="transition: margin-left 0.35s cubic-bezier(0.4, 0, 0.2, 1); min-height:100vh;">

    {{-- HEADER with Hamburger --}}
    <header class="anim-fade bg-gradient-to-br from-blue-500 to-blue-700 sticky top-0 z-10 shadow-lg mt-3 mx-3 lg:mt-4 lg:mx-4 rounded-2xl">
        <div class="px-4 sm:px-6 lg:px-8 py-4 flex items-center justify-between">
            <div class="flex items-center gap-3">
                <button @click="mobileMenuOpen = true" class="lg:hidden p-1.5 rounded-lg hover:bg-white/20 transition-colors text-white">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M4 6h16M4 12h16M4 18h16"/>
                    </svg>
                </button>
                <div>
                    <h1 class="text-xl sm:text-2xl font-bold text-white">Leave Management</h1>
                    <p class="text-xs sm:text-sm text-blue-100 mt-1">My leave records</p>
                </div>
            </div>
            <div class="flex items-center space-x-2 sm:space-x-4">
                <x-notification-bell />
            </div>
        </div>
    </header>

    <div class="main-content-padding">

        {{-- Tab Nav --}}
        <div class="tab-nav">
            <a href="{{ route('finance_officer.leave.management', ['tab' => 'my-leave']) }}" class="tab-btn {{ $activeTab === 'my-leave' ? 'active' : '' }}">My Leave</a>
            <a href="{{ route('finance_officer.leave.management', ['tab' => 'leave-calendar']) }}" class="tab-btn {{ $activeTab === 'leave-calendar' ? 'active' : '' }}">Leave Calendar</a>
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
                <select class="filter-select" onchange="window.location.href='{{ route('finance_officer.leave.management') }}?tab=my-leave&type='+this.value+'&status={{ request('status') }}'">
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

        {{-- DESKTOP TABLE (hidden on mobile) --}}
        <div class="table-card desktop-table">
            <div class="table-scroll">
                <table class="data-table">
                    <thead>
                        <tr><th>Ref #</th><th>Leave Type</th><th>Filed On</th><th>Date From</th><th>Date To</th><th>Days</th><th>Reason</th><th>Approver</th><th>Status</th><th></th></tr>
                    </thead>
                    <tbody>
                        @forelse($myLeaveRequests as $req)
                        <tr>
                            <td class="font-semibold text-gray-700">{{ $req->ref_no }}</td>
                            <td><span class="bg-blue-100 text-blue-800 px-2.5 py-1 rounded-full text-xs font-semibold">{{ $req->leaveType->name ?? '—' }}</span></td>
                            <td class="whitespace-nowrap">{{ $req->created_at->format('m/d/Y') }}</td>
                            <td class="whitespace-nowrap">{{ $req->start_date->format('m/d/Y') }}</td>
                            <td class="whitespace-nowrap">{{ $req->end_date->format('m/d/Y') }}</td>
                            <td>{{ $req->total_days }}</td>
                            <td class="text-gray-500 truncate max-w-[120px]">{{ $req->reason }}</td>
                            <td>
                                <div class="font-semibold text-sm">{{ $req->approver ? trim($req->approver->fname.' '.$req->approver->lname) : '—' }}</div>
                                <div class="text-xs text-gray-400">{{ $req->approver?->jobTitle?->title ?? '—' }}</div>
                            </td>
                            <td><span class="status-{{ $req->status }}">{{ ucfirst($req->status) }}</span></td>
                            <td><button class="btn-outline-sm" @click="openLeaveDetails({{ $req->id }})">View</button></td>
                        </tr>
                        @empty
                        <tr><td colspan="10" class="text-center py-10 text-gray-400">No leave requests found. Ru<br>@endforelse
                    </tbody>
                </table>
            </div>
        </div>

        {{-- MOBILE CARDS (visible on mobile only) --}}
        <div class="mobile-table">
            @forelse($myLeaveRequests as $req)
            <div class="req-card">
                <div class="req-card-header">
                    <div><div class="req-card-ref">{{ $req->ref_no }}</div><div class="req-card-filed">Filed on {{ $req->created_at->format('M j, Y') }}</div></div>
                    <div><span class="status-{{ $req->status }}">{{ ucfirst($req->status) }}</span></div>
                </div>
                <div class="req-card-grid">
                    <div><div class="req-card-label">Leave Type</div><div class="req-card-value">{{ $req->leaveType->name ?? '—' }}</div></div>
                    <div><div class="req-card-label">Duration</div><div class="req-card-value">{{ $req->start_date->format('m/d/Y') }} – {{ $req->end_date->format('m/d/Y') }}</div></div>
                    <div><div class="req-card-label">Days</div><div class="req-card-value">{{ $req->total_days }}</div></div>
                    <div><div class="req-card-label">Approver</div><div class="req-card-value">{{ $req->approver ? trim($req->approver->fname.' '.$req->approver->lname) : '—' }}</div></div>
                </div>
                <div class="req-card-footer"><div class="req-card-label">Reason</div><div class="text-sm text-gray-500 truncate max-w-[150px]">{{ $req->reason }}</div><button class="btn-outline-sm" @click="openLeaveDetails({{ $req->id }})">View</button></div>
            </div>
            @empty
            <div class="text-center py-10 text-gray-400">No leave requests found.</div>
            @endforelse
        </div>

        {{-- ══════════ LEAVE CALENDAR ══════════ --}}
        @elseif($activeTab === 'leave-calendar')

        <div class="cal-header">
            <span class="cal-month-label">{{ $calMonth->format('F Y') }}</span>
            <a href="{{ route('finance_officer.leave.management', ['tab' => 'leave-calendar', 'month' => $calPrev]) }}" class="cal-nav-btn"><svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>Prev</a>
            <a href="{{ route('finance_officer.leave.management', ['tab' => 'leave-calendar', 'month' => $calNext]) }}" class="cal-nav-btn">Next<svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg></a>
        </div>

        <form method="GET" action="{{ route('finance_officer.leave.management') }}" id="calFilterForm">
            <input type="hidden" name="tab" value="leave-calendar"><input type="hidden" name="month" value="{{ $calMonth->format('Y-m') }}">
            <div class="cal-filters">
                <select class="cal-filter-select" name="leave_type_id" onchange="document.getElementById('calFilterForm').submit()">
                    <option value="">All Types</option>
                    @foreach($leaveTypes as $lt)<option value="{{ $lt->id }}" {{ request('leave_type_id') == $lt->id ? 'selected' : '' }}>{{ $lt->name }}</option>@endforeach
                </select>
            </div>
        </form>

        @php $eventsByDay = $calendarEvents->groupBy('day'); @endphp
        <div class="cal-wrap"><table class="cal-grid"><thead><tr>@foreach(['SUN','MON','TUE','WED','THU','FRI','SAT'] as $dh)<th>{{ $dh }}</th>@endforeach</thead>
        <tbody>@php $day = 1; $startPad = $firstDow; $totalDays = $calEnd->day; @endphp
        @for($row = 0; $row < 6; $row++)@php if($day > $totalDays) break; @endphp
        <tr>@for($col = 0; $col < 7; $col++)@php $cellDay = ($row === 0 && $col < $startPad) ? null : ($day <= $totalDays ? $day++ : null); @endphp
        <td class="{{ $cellDay === null ? 'other-month' : '' }}">@if($cellDay)<div>{{ $cellDay }}</div>@foreach($eventsByDay->get($cellDay, []) as $ev)<div class="cal-event {{ $ev['type'] }}">{{ $ev['label'] }}</div>@endforeach @endif</td>@endfor
        </tr>@endfor
        </tbody></table></div>

        <div class="cal-legend">@foreach($leaveTypes as $lt)<div class="cal-legend-item"><div class="cal-legend-dot" style="background:#dbeafe;"></div>{{ $lt->code }} – {{ $lt->name }}</div>@endforeach</div>

        @endif

        {{-- ══════════ FILE LEAVE MODAL ══════════ --}}
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
                     const res = await fetch('{{ route('finance_officer.leave.file') }}', { method: 'POST', body: form });
                     this.saving = false;
                     const data = await res.json();
                     if (res.ok) { window.location.reload(); }
                     else { this.errorMsg = data.message ?? 'Something went wrong.'; }
                 }
             }">
            <div class="modal-box">
                <div class="modal-title">Leave Request</div>
                <template x-if="errorMsg"><div class="bg-red-100 text-red-700 p-3 rounded-xl text-sm mb-4" x-text="errorMsg"></div></template>
                <div class="mb-4"><label class="form-label">Leave Type</label><select class="form-select" x-model="leaveTypeId"><option value="">Choose leave type</option>@foreach($leaveTypes as $lt)<option value="{{ $lt->id }}">{{ $lt->name }} ({{ $lt->code }})</option>@endforeach</select></div>
                <div class="form-row mb-4"><div><label class="form-label">Start Date</label><input type="date" class="form-input" x-model="startDate"></div><div><label class="form-label">End Date</label><input type="date" class="form-input" x-model="endDate"></div></div>
                <div class="mb-4"><label class="form-label">Reason / Remarks</label><input type="text" class="form-input" x-model="reason" placeholder="Enter brief description of your leave reason"></div>
                <div class="mb-4"><label class="form-label">Supporting Document</label><label class="upload-area"><svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 13h6m-3-3v6m5 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg><div class="text-sm font-medium text-gray-600" x-text="fileName || 'Choose a file to upload'"></div><div class="text-xs text-gray-400">PDF or DOCX, max 10MB</div><input type="file" accept=".pdf,.docx" class="hidden" @change="handleFile($event)"></label></div>
                <div class="modal-actions"><button class="btn-cancel" @click="showFileLeave = false">Cancel</button><button class="btn-save" @click="submit()" :disabled="saving"><span x-show="saving"><svg class="inline animate-spin w-4 h-4 mr-1" viewBox="0 0 24 24" fill="none"><circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" opacity=".3"/><path fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg> Submitting...</span><span x-show="!saving">Submit</span></button></div>
            </div>
        </div>

        {{-- Leave Details Modal --}}
        <div x-show="showLeaveDetails" class="modal-overlay" x-cloak @click.self="showLeaveDetails = false">
            <div class="modal-box">
                <div class="modal-title">Leave Details</div>
                <template x-if="leaveError"><div class="bg-red-100 text-red-700 p-3 rounded-xl text-sm mb-4" x-text="leaveError"></div></template>
                <div class="mb-4"><div class="detail-label">Ref #</div><div class="detail-field" x-text="selectedLeave.ref_no || '—'"></div></div>
                <div class="mb-4"><div class="detail-label">Leave Type</div><div class="detail-field" x-text="selectedLeave.leave_type || '—'"></div></div>
                <div class="form-row mb-4"><div><div class="detail-label">Start Date</div><div class="detail-field" x-text="selectedLeave.start_date || '—'"></div></div><div><div class="detail-label">End Date</div><div class="detail-field" x-text="selectedLeave.end_date || '—'"></div></div></div>
                <div class="mb-4"><div class="detail-label">Total Days</div><div class="detail-field" x-text="selectedLeave.total_days || '—'"></div></div>
                <div class="mb-4"><div class="detail-label">Reason / Remarks</div><div class="detail-field" x-text="selectedLeave.reason || '—'"></div></div>
                <div class="mb-4"><div class="detail-label">Status</div><div class="detail-field" x-text="selectedLeave.status ? selectedLeave.status.charAt(0).toUpperCase() + selectedLeave.status.slice(1) : '—'"></div></div>
                <template x-if="selectedLeave.rejection_reason"><div class="mb-4"><div class="detail-label text-red-600">Rejection Reason</div><div class="bg-red-50 text-red-700 p-3 rounded-xl text-sm border border-red-200" x-text="selectedLeave.rejection_reason"></div></div></template>
                <div class="modal-actions"><template x-if="selectedLeave.status === 'pending'"><button class="btn-cancel bg-red-50 text-red-600 border-red-200" @click="cancelLeave(selectedLeave.id)" x-text="cancelling ? 'Cancelling...' : 'Cancel Request'"></button></template><button class="btn-save" @click="showLeaveDetails = false">Close</button></div>
            </div>
        </div>

    </div>
</div>

<script>
    (function() {
        var mainEl = document.querySelector('.main-content-margin') || document.querySelector('.main-content');
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
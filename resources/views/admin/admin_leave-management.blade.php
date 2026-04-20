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

    $isEmployeesSection  = in_array($currentRoute, ['employees.directory', 'employees.profile']);
    $isAttendanceSection = in_array($currentRoute, ['admin.attendance.reports','admin.attendance.employee','admin.attendance.today','admin.attendance.records','admin.leave.management']);
    $isPayrollSection    = in_array($currentRoute, ['admin.payroll','admin.payslips','admin.contributions']);
    $isRequestsSection   = in_array($currentRoute, ['admin.requests.pending','admin.requests.approved']);

    // ── Calendar data ──────────────────────────────────────────────────────
    $calMonth = request('month') ? \Carbon\Carbon::parse(request('month').'-01') : \Carbon\Carbon::now()->startOfMonth();
    $calPrev  = $calMonth->copy()->subMonth()->format('Y-m');
    $calNext  = $calMonth->copy()->addMonth()->format('Y-m');
    $calStart = $calMonth->copy()->startOfMonth();
    $calEnd   = $calMonth->copy()->endOfMonth();
    $firstDow = $calStart->dayOfWeek;
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

        :root {
            --blue: #3b82f6;
            --blue-dark: #1d4ed8;
            --blue-light: #eff6ff;
            --muted: #6b7280;
            --border: #e5e7eb;
        }

        /* ─── TABS ─────────────────────────────────────────────── */
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
        .tab-btn:hover { color: #374151; }
        .tab-btn.active { color: var(--blue); border-bottom-color: var(--blue); font-weight: 700; }

        /* ─── STAT CARDS ────────────────────────────────────────── */
        .leave-cards {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 12px;
            margin-bottom: 24px;
        }
        @media (min-width: 768px) {
            .leave-cards {
                grid-template-columns: repeat(5, 1fr);
            }
        }
        .leave-card {
            background: #fff;
            border: 1px solid var(--border);
            border-radius: 14px;
            padding: 16px;
            position: relative;
            box-shadow: 0 1px 6px rgba(0,0,0,.04);
        }
        .leave-card-label { font-size: 12px; color: var(--muted); font-weight: 500; margin-bottom: 6px; line-height: 1.3; }
        .leave-card-value { font-size: 32px; font-weight: 800; color: #111827; line-height: 1; }
        .leave-card-sub   { font-size: 11px; color: #9ca3af; margin-top: 6px; }
        .leave-badge {
            position: absolute;
            top: 14px;
            right: 14px;
            padding: 3px 9px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 700;
        }

        /* ─── TOOLBAR ───────────────────────────────────────────── */
        .toolbar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 14px;
            gap: 10px;
            flex-wrap: wrap;
        }
        .toolbar-title { font-size: 15px; font-weight: 700; color: #111827; }
        .toolbar-right { display: flex; align-items: center; gap: 8px; flex-wrap: wrap; }

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

        /* ─── TABLE ─────────────────────────────────────────────── */
        .table-card {
            background: #fff;
            border-radius: 14px;
            border: 1px solid var(--border);
            overflow: hidden;
            box-shadow: 0 1px 8px rgba(0,0,0,.04);
        }
        .table-scroll { overflow-x: auto; -webkit-overflow-scrolling: touch; }
        .data-table { width: 100%; border-collapse: collapse; min-width: 600px; }
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
        .data-table td {
            padding: 12px 14px;
            font-size: 12px;
            color: #111827;
            border-bottom: 1px solid #f3f4f6;
            vertical-align: middle;
        }
        .data-table tr:last-child td { border-bottom: none; }
        .data-table tbody tr:hover { background: #fafafa; }

        /* ─── STATUS BADGES ─────────────────────────────────────── */
        .status-pending  { background: #ffedd5; color: #c2410c; padding: 3px 10px; border-radius: 20px; font-size: 11px; font-weight: 600; display: inline-block; white-space: nowrap; }
        .status-approved { background: #dcfce7; color: #15803d; padding: 3px 10px; border-radius: 20px; font-size: 11px; font-weight: 600; display: inline-block; white-space: nowrap; }
        .status-rejected { background: #fee2e2; color: #dc2626; padding: 3px 10px; border-radius: 20px; font-size: 11px; font-weight: 600; display: inline-block; white-space: nowrap; }

        /* ─── LEAVE CREDITS SECTION ─────────────────────────────── */
        .credits-filter-select {
            flex: 1;
            min-width: 120px;
            appearance: none;
            background: #f9fafb;
            border: 1px solid var(--border);
            border-radius: 8px;
            padding: 9px 28px 9px 12px;
            font-size: 13px;
            color: #374151;
            cursor: pointer;
            outline: none;
            font-family: inherit;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='10' height='6' fill='none'%3E%3Cpath d='M1 1l4 4 4-4' stroke='%236b7280' stroke-width='1.5' stroke-linecap='round'/%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: right 10px center;
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
        .cal-grid { width: 100%; border-collapse: collapse; background: #fff; border-radius: 14px; overflow: hidden; border: 1px solid var(--border); min-width: 340px; }
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
        .cal-vl      { background: #dbeafe; color: #1d4ed8; }
        .cal-sl      { background: #fce7f3; color: #be185d; }
        .cal-lwop    { background: #ffedd5; color: #c2410c; }

        .cal-legend { display: flex; align-items: center; gap: 14px; margin-top: 14px; flex-wrap: wrap; }
        .cal-legend-item { display: flex; align-items: center; gap: 6px; font-size: 11px; color: #374151; }
        .cal-legend-dot  { width: 11px; height: 11px; border-radius: 3px; flex-shrink: 0; }

        /* ─── LEAVE TYPES ───────────────────────────────────────── */
        .leave-types-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 20px;
            flex-wrap: wrap;
            gap: 12px;
        }
        .leave-types-title { font-size: 20px; font-weight: 800; color: #111827; }

        .pay-paid   { background: #dcfce7; color: #15803d; padding: 3px 10px; border-radius: 20px; font-size: 11px; font-weight: 600; display: inline-block; white-space: nowrap; }
        .pay-unpaid { background: #fee2e2; color: #dc2626; padding: 3px 10px; border-radius: 20px; font-size: 11px; font-weight: 600; display: inline-block; white-space: nowrap; }
        .doc-req    { background: #ffedd5; color: #c2410c; padding: 3px 10px; border-radius: 20px; font-size: 11px; font-weight: 600; display: inline-block; white-space: nowrap; }
        .doc-not    { background: #f3f4f6; color: #6b7280; padding: 3px 10px; border-radius: 20px; font-size: 11px; font-weight: 600; display: inline-block; white-space: nowrap; }
        .lt-code-badge { padding: 3px 9px; border-radius: 20px; font-size: 11px; font-weight: 700; display: inline-block; white-space: nowrap; }

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
                max-width: 500px;
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
            font-size: 20px; 
            font-weight: 700; 
            color: #111827; 
            margin-bottom: 24px;
            text-align: left;
        }
        .form-label { 
            font-size: 13px; 
            font-weight: 600; 
            color: #374151; 
            margin-bottom: 6px; 
            display: block; 
        }
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
        .form-input:focus, .form-select:focus { 
            border-color: var(--blue); 
            ring: none;
        }
        .form-select {
            appearance: none;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='16' height='16' viewBox='0 0 24 24' fill='none' stroke='%236b7280' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpolyline points='6 9 12 15 18 9'%3E%3C/polyline%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: right 14px center;
        }
        .form-row { 
            display: grid; 
            grid-template-columns: 1fr 1fr; 
            gap: 14px; 
        }
        .modal-actions { 
            display: flex; 
            gap: 12px; 
            margin-top: 28px;
            flex-direction: column-reverse;
        }
        @media (min-width: 640px) {
            .modal-actions {
                flex-direction: row;
                justify-content: flex-end;
            }
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
        @media (min-width: 640px) {
            .btn-cancel, .btn-save {
                width: auto;
                min-width: 100px;
            }
        }
        .btn-danger {
            background: #ef4444;
            color: #fff;
        }
        .btn-danger:hover {
            background: #dc2626;
        }
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
        /* Checkbox group styles */
        .checkbox-group {
            display: flex;
            gap: 24px;
            margin-top: 8px;
        }
        .checkbox-label {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 14px;
            color: #374151;
            cursor: pointer;
        }
        .checkbox-label input {
            width: 18px;
            height: 18px;
            accent-color: var(--blue);
        }
        /* File upload area */
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
        .upload-area:hover {
            border-color: var(--blue);
            background: #eff6ff;
        }

        /* ─── LEAVE TYPES MOBILE CARDS ──────────────────────────── */
        .lt-mobile-card {
            background: #fff;
            border: 1px solid var(--border);
            border-radius: 12px;
            padding: 14px 16px;
            margin-bottom: 10px;
            box-shadow: 0 1px 4px rgba(0,0,0,.04);
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
            gap: 6px;
            flex-wrap: wrap;
            margin-top: 6px;
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

        /* ─── MOBILE RESPONSIVE SIDEBAR & PADDING ───────────────── */
        .desktop-sidebar { display: none; }
        @media (min-width: 1024px) {
            .desktop-sidebar { display: block; }
        }

        @media (max-width: 1023px) {
            .main-content {
                margin-left: 0 !important;
            }
        }

        @media (max-width: 1024px) {
            .content-pad {
                padding: 16px !important;
            }
        }
            @keyframes spin { from { transform: rotate(0deg); } to { transform: rotate(360deg); } }
    </style>
</head>
<body class="bg-gray-50" x-data="{ mobileMenuOpen: false, collapsed: localStorage.getItem('sidebarCollapsed') === 'true' }"
     x-init="window.addEventListener('sidebar-toggle', e => { collapsed = e.detail.collapsed })">

{{-- ══════════ SIDEBAR ══════════ --}}

{{-- DESKTOP SIDEBAR --}}
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
         showFileLeave: false,
         showLeaveDetails: false,
         showAddLeaveType: false,
         selectedLeave: {},
         leaveError: '',
         cancelling: false,
         // Add Leave Type form data
         newLeaveType: {
             name: '',
             code: '',
             days_entitled: '',
             is_paid: true,
             requires_document: false,
             applicable_to: '',
             carry_over: false
         },
         // File Leave form data
         fileLeave: {
             leave_type_id: '',
             start_date: '',
             end_date: '',
             reason: '',
             document: null,
             fileName: ''
         },
         saving: false,
         errorMsg: '',
         async openLeaveDetails(id) {
             this.leaveError = '';
             this.selectedLeave = {};
             const res = await fetch(`/admin/leave/${id}`, {
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
             const res = await fetch(`/admin/leave/${id}/cancel`, {
                 method: 'POST',
                 headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content }
             });
             this.cancelling = false;
             if (res.ok) { window.location.reload(); }
             else { this.leaveError = 'Failed to cancel leave request.'; }
         },
         handleFileUpload(e) {
             const file = e.target.files[0];
             if (file) {
                 this.fileLeave.document = file;
                 this.fileLeave.fileName = file.name;
             }
         },
         async submitFileLeave() {
             this.errorMsg = '';
             if (!this.fileLeave.leave_type_id || !this.fileLeave.start_date || !this.fileLeave.end_date || !this.fileLeave.reason) {
                 this.errorMsg = 'Please fill in all required fields.';
                 return;
             }
             this.saving = true;
             const formData = new FormData();
             formData.append('leave_type_id', this.fileLeave.leave_type_id);
             formData.append('start_date', this.fileLeave.start_date);
             formData.append('end_date', this.fileLeave.end_date);
             formData.append('reason', this.fileLeave.reason);
             if (this.fileLeave.document) {
                 formData.append('document', this.fileLeave.document);
             }
             formData.append('_token', document.querySelector('meta[name=csrf-token]').content);
             
             const res = await fetch('{{ route('admin.leave.file') }}', { method: 'POST', body: formData });
             this.saving = false;
             const data = await res.json();
             if (res.ok) { window.location.reload(); }
             else { this.errorMsg = data.message ?? 'Something went wrong.'; }
         },
         showEditLeaveType: false,
         editLt: { id: null, name: '', code: '', days_entitled: '', is_paid: true, requires_document: false, applicable_to: '', carry_over: false },
         async openEditLeaveType(id) {
             const res = await fetch(`/admin/leave/types/${id}`, {
                 headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content }
             });
             const data = await res.json();
             if (res.ok) { this.editLt = data; this.showEditLeaveType = true; }
         },
         async submitEditLeaveType() {
             this.errorMsg = '';
             if (!this.editLt.name || !this.editLt.code) { this.errorMsg = 'Name and code are required.'; return; }
             this.saving = true;
             const res = await fetch(`/admin/leave/types/${this.editLt.id}/update`, {
                 method: 'POST',
                 headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content },
                 body: JSON.stringify(this.editLt)
             });
             this.saving = false;
             const data = await res.json();
             if (res.ok) { window.location.reload(); }
             else { this.errorMsg = data.message ?? 'Something went wrong.'; }
         },
         async submitAddLeaveType() {
             this.errorMsg = '';
             if (!this.newLeaveType.name || !this.newLeaveType.code || !this.newLeaveType.days_entitled) {
                 this.errorMsg = 'Please fill in all required fields.';
                 return;
             }
             this.saving = true;
             const res = await fetch('{{ route('admin.leave.type.store') }}', {
                 method: 'POST',
                 headers: { 
                     'Content-Type': 'application/json',
                     'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content 
                 },
                 body: JSON.stringify(this.newLeaveType)
             });
             this.saving = false;
             const data = await res.json();
             if (res.ok) { window.location.reload(); }
             else { this.errorMsg = data.message ?? 'Something went wrong.'; }
         }
     }"
     x-init="window.addEventListener('sidebar-toggle', e => { collapsed = e.detail.collapsed })"
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
            <x-notification-bell />
        </div>
    </header>

    {{-- MAIN CONTENT PAD --}}
    <div class="content-pad" style="padding: 24px 32px;">

        {{-- TAB NAV --}}
        <div class="tab-nav">
            <a href="{{ route('admin.leave.management', ['tab' => 'my-leave']) }}"
               class="tab-btn {{ $activeTab === 'my-leave' ? 'active' : '' }}">My Leave</a>
            <a href="{{ route('admin.leave.management', ['tab' => 'leave-credits']) }}"
               class="tab-btn {{ $activeTab === 'leave-credits' ? 'active' : '' }}">Leave Credits</a>
            <a href="{{ route('admin.leave.management', ['tab' => 'leave-calendar']) }}"
               class="tab-btn {{ $activeTab === 'leave-calendar' ? 'active' : '' }}">Leave Calendar</a>
            <a href="{{ route('admin.leave.management', ['tab' => 'leave-types']) }}"
               class="tab-btn {{ $activeTab === 'leave-types' ? 'active' : '' }}">Leave Types</a>
        </div>

        {{-- ══════════ MY LEAVE ══════════ --}}
        @if($activeTab === 'my-leave')

        <div class="leave-cards">
            @foreach($leaveTypes as $lt)
            @php $key = strtolower($lt->code); @endphp
            <div class="leave-card">
                <span class="leave-badge" style="background:#dbeafe;color:#1d4ed8;">{{ $lt->code }}</span>
                <div class="leave-card-label">{{ $lt->name }}</div>
                <div class="leave-card-value">{{ $creditStats[$key.'_used'] ?? 0 }}</div>
                <div class="leave-card-sub">{{ $creditStats[$key.'_remaining'] ?? 0 }} remaining of {{ $creditStats[$key.'_total'] ?? $lt->days_entitled ?? 0 }}</div>
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
                <select class="filter-select" onchange="window.location.href='{{ route('admin.leave.management') }}?tab=my-leave&type='+this.value">
                    <option value="">All Types</option>
                    @foreach($leaveTypes as $lt)
                        <option value="{{ $lt->id }}" {{ request('type') == $lt->id ? 'selected' : '' }}>{{ $lt->name }}</option>
                    @endforeach
                </select>
                <button class="btn-primary" @click="showFileLeave = true">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                    </svg>
                    File Leave
                </button>
            </div>
        </div>

        <div class="table-card">
            <div class="table-scroll">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Ref #</th>
                            <th>Leave Type</th>
                            <th>Filed On</th>
                            <th>Date From</th>
                            <th>Date To</th>
                            <th>Days</th>
                            <th>Reason</th>
                            <th>Approver</th>
                            <th>Status</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                    @forelse($myLeaveRequests as $req)
                    <tr>
                        <td style="font-weight:600;color:#6b7280;white-space:nowrap;">{{ $req->ref_no }}</td>
                        <td>
                            <span style="background:#dbeafe;color:#1d4ed8;padding:3px 10px;border-radius:20px;font-size:11px;font-weight:600;display:inline-block;white-space:nowrap;">
                                {{ $req->leaveType->name ?? '—' }}
                            </span>
                        </td>
                        <td style="white-space:nowrap;">{{ $req->created_at->format('m/d/Y') }}</td>
                        <td style="white-space:nowrap;">{{ $req->start_date->format('m/d/Y') }}</td>
                        <td style="white-space:nowrap;">{{ $req->end_date->format('m/d/Y') }}</td>
                        <td>{{ $req->total_days }}</td>
                        <td style="color:#6b7280;max-width:140px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">{{ $req->reason }}</td>
                        <td style="white-space:nowrap;">
                            <div style="font-weight:700;font-size:12px;">{{ $req->approver ? trim($req->approver->fname.' '.$req->approver->lname) : '—' }}</div>
                            <div style="font-size:10px;color:#9ca3af;">{{ $req->approver?->jobTitle?->title ?? '—' }}</div>
                        </td>
                        <td><span class="status-{{ $req->status }}">{{ ucfirst($req->status) }}</span></td>
                        <td><button class="btn-outline-sm" @click="openLeaveDetails({{ $req->id }})">View</button></td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="10" style="text-align:center;padding:40px 16px;color:#9ca3af;font-size:13px;">No leave requests found.</td>
                    </tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        {{-- ══════════ LEAVE CREDITS ══════════ --}}
        @elseif($activeTab === 'leave-credits')

        <div style="font-size:16px;font-weight:700;color:#111827;margin-bottom:14px;">Employee Leave Credits</div>

        <div style="margin-bottom:10px;">
            <label style="font-size:12px;color:#6b7280;font-weight:500;margin-bottom:6px;display:block;">View credits for:</label>
            <form method="GET" action="{{ route('admin.leave.management') }}" id="creditsFilterForm">
                <input type="hidden" name="tab" value="leave-credits">
                <select class="credits-filter-select" name="employee_id" style="width:100%;margin-bottom:10px;" onchange="document.getElementById('creditsFilterForm').submit()">
                    <option value="">Select Employee</option>
                    @foreach($employees ?? [] as $emp)
                        <option value="{{ $emp->id }}" {{ request('employee_id') == $emp->id ? 'selected' : '' }}>{{ trim($emp->fname.' '.$emp->lname) }}</option>
                    @endforeach
                </select>
                <div style="display:flex;gap:10px;">
                    <select class="credits-filter-select" name="department" style="flex:1;" onchange="document.getElementById('creditsFilterForm').submit()">
                        <option value="">All Departments</option>
                        @foreach($departments ?? [] as $dept)
                            <option value="{{ $dept->id }}" {{ request('department') == $dept->id ? 'selected' : '' }}>{{ $dept->name }}</option>
                        @endforeach
                    </select>
                    <select class="credits-filter-select" name="year" style="flex:1;max-width:110px;" onchange="document.getElementById('creditsFilterForm').submit()">
                        <option value="{{ $currentYear }}" {{ request('year', now()->year) == $currentYear ? 'selected' : '' }}>{{ $currentYear }}</option>
                        <option value="{{ $currentYear - 1 }}" {{ request('year') == $currentYear - 1 ? 'selected' : '' }}>{{ $currentYear - 1 }}</option>
                    </select>
                </div>
            </form>
        </div>

        <div class="leave-cards" style="margin-top:18px;">
            @foreach($leaveTypes as $lt)
            @php $key = strtolower($lt->code); @endphp
            <div class="leave-card">
                <span class="leave-badge" style="background:#dbeafe;color:#1d4ed8;">{{ $lt->code }}</span>
                <div class="leave-card-label">{{ $lt->name }}</div>
                <div class="leave-card-value">{{ $creditStats[$key.'_used'] ?? 0 }}</div>
                <div class="leave-card-sub">{{ $creditStats[$key.'_remaining'] ?? 0 }} remaining of {{ $creditStats[$key.'_total'] ?? $lt->days_entitled ?? 0 }}</div>
            </div>
            @endforeach
        </div>

        <div class="toolbar" style="margin-top:8px;">
            <div class="toolbar-title">Leave Requests</div>
            <div class="toolbar-right">
                <select class="filter-select">
                    <option>All Status</option>
                    <option>Pending</option>
                    <option>Approved</option>
                    <option>Rejected</option>
                </select>
                <select class="filter-select">
                    <option>All Types</option>
                    @foreach($leaveTypes as $lt)
                    <option>{{ $lt->name }}</option>
                    @endforeach
                </select>
            </div>
        </div>

        <div class="table-card">
            <div class="table-scroll">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Ref #</th>
                            <th>Leave Type</th>
                            <th>Filed On</th>
                            <th>Date From</th>
                            <th>Date To</th>
                            <th>Days</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                    @forelse($leaveHistory as $req)
                    <tr>
                        <td style="font-weight:600;color:#6b7280;white-space:nowrap;">{{ $req->ref_no }}</td>
                        <td>
                            <span style="background:#dbeafe;color:#1d4ed8;padding:3px 10px;border-radius:20px;font-size:11px;font-weight:600;display:inline-block;">
                                {{ $req->leaveType->name ?? '—' }}
                            </span>
                        </td>
                        <td style="white-space:nowrap;">{{ $req->created_at->format('m/d/Y') }}</td>
                        <td style="white-space:nowrap;">{{ $req->start_date->format('m/d/Y') }}</td>
                        <td style="white-space:nowrap;">{{ $req->end_date->format('m/d/Y') }}</td>
                        <td>{{ $req->total_days }}</td>
                        <td><span class="status-{{ $req->status }}">{{ ucfirst($req->status) }}</span></td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" style="text-align:center;padding:32px 16px;color:#9ca3af;font-size:13px;">No leave requests found.</td>
                    </tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        {{-- ══════════ LEAVE CALENDAR ══════════ --}}
        @elseif($activeTab === 'leave-calendar')

        <div class="cal-header">
            <span class="cal-month-label">{{ $calMonth->format('F Y') }}</span>
            <a href="{{ route('admin.leave.management', ['tab' => 'leave-calendar', 'month' => $calPrev]) }}" class="cal-nav-btn">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                Previous
            </a>
            <a href="{{ route('admin.leave.management', ['tab' => 'leave-calendar', 'month' => $calNext]) }}" class="cal-nav-btn">
                Next
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
            </a>
        </div>

        <form method="GET" action="{{ route('admin.leave.management') }}" id="calFilterForm">
            <input type="hidden" name="tab" value="leave-calendar">
            <input type="hidden" name="month" value="{{ $calMonth->format('Y-m') }}">
            <div class="cal-filters">
                <select class="cal-filter-select" name="department_id" onchange="document.getElementById('calFilterForm').submit()">
                    <option value="">All Departments</option>
                    @foreach($departments ?? [] as $dept)
                        <option value="{{ $dept->id }}">{{ $dept->name }}</option>
                    @endforeach
                </select>
                <select class="cal-filter-select" name="leave_type_id" onchange="document.getElementById('calFilterForm').submit()">
                    <option value="">All Types</option>
                    @foreach($leaveTypes as $lt)
                        <option value="{{ $lt->id }}" {{ request('leave_type_id') == $lt->id ? 'selected' : '' }}>{{ $lt->name }}</option>
                    @endforeach
                </select>
            </div>
        </form>

        @php $eventsByDay = $calendarEvents->groupBy('day'); @endphp
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
                        <span class="cal-event {{ $ev['type'] }}">{{ $ev['label'] }}</span>
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

        <div class="leave-types-header">
            <div class="leave-types-title">Leave Types</div>
            <button class="btn-primary" @click="showAddLeaveType = true">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" style="width:14px;height:14px;">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                </svg>
                Add Leave Type
            </button>
        </div>

        {{-- DESKTOP TABLE --}}
        <div class="table-card lt-desktop-table">
            <div class="table-scroll">
                <table class="data-table" style="min-width:580px;">
                    <thead>
                        <tr>
                            <th>Leave Type</th>
                            <th>Code</th>
                            <th>Days Entitled</th>
                            <th>Pay</th>
                            <th>Document</th>
                            <th>Applicable To</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                    @foreach($leaveTypes as $lt)
                    <tr>
                        <td style="font-weight:600;">{{ $lt->name }}</td>
                        <td><span class="lt-code-badge" style="background:#dbeafe;color:#1d4ed8;">{{ $lt->code }}</span></td>
                        <td>{{ $lt->days_entitled ?? '—' }}</td>
                        <td>
                            @if($lt->is_paid)
                                <span class="pay-paid">Paid</span>
                            @else
                                <span class="pay-unpaid">Unpaid</span>
                            @endif
                        </td>
                        <td>
                            @if($lt->requires_document)
                                <span class="doc-req">Required</span>
                            @else
                                <span class="doc-not">Not Required</span>
                            @endif
                        </td>
                        <td style="color:#6b7280;font-size:12px;">{{ $lt->applicable_to ?? 'All employees' }}</td>
                        <td>
                            <button class="btn-outline-sm" @click="openEditLeaveType({{ $lt->id }})">Edit</button>
                        </td>
                    </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        {{-- MOBILE CARDS --}}
        <div class="lt-mobile-list">
            @foreach($leaveTypes as $lt)
            <div class="lt-mobile-card">
                <div class="lt-mobile-row">
                    <span class="lt-mobile-name">{{ $lt->name }}</span>
                    <span class="lt-code-badge" style="background:#dbeafe;color:#1d4ed8;">{{ $lt->code }}</span>
                </div>
                <div style="font-size:12px;color:#6b7280;margin-bottom:8px;">
                    {{ $lt->days_entitled ?? '—' }} days · {{ $lt->applicable_to ?? 'All employees' }}
                </div>
                <div class="lt-mobile-meta">
                    @if($lt->is_paid)
                        <span class="pay-paid">Paid</span>
                    @else
                        <span class="pay-unpaid">Unpaid</span>
                    @endif
                    @if($lt->requires_document)
                        <span class="doc-req">Required</span>
                    @else
                        <span class="doc-not">Not Required</span>
                    @endif
                    <button class="btn-outline-sm" style="margin-left:auto;" @click="openEditLeaveType({{ $lt->id }})">Edit</button>
                </div>
            </div>
            @endforeach
        </div>

        @endif

        {{-- ══════════ MODALS (Mobile First Design) ══════════ --}}

        {{-- FILE LEAVE MODAL --}}
        <div x-show="showFileLeave" class="modal-overlay" x-cloak @click.self="showFileLeave = false">
            <div class="modal-box">
                <div class="modal-title">Leave Request</div>
                
                <template x-if="errorMsg">
                    <div style="background:#fee2e2;color:#b91c1c;border-radius:12px;padding:12px;font-size:13px;margin-bottom:16px;" x-text="errorMsg"></div>
                </template>

                <div style="margin-bottom:18px;">
                    <label class="form-label">Leave Type</label>
                    <select class="form-select" x-model="fileLeave.leave_type_id">
                        <option value="">Choose leave type</option>
                        @foreach($leaveTypes as $lt)
                            <option value="{{ $lt->id }}">{{ $lt->name }} ({{ $lt->code }})</option>
                        @endforeach
                    </select>
                </div>

                <div class="form-row" style="margin-bottom:18px;">
                    <div>
                        <label class="form-label">Start Date</label>
                        <input type="date" class="form-input" x-model="fileLeave.start_date">
                    </div>
                    <div>
                        <label class="form-label">End Date</label>
                        <input type="date" class="form-input" x-model="fileLeave.end_date">
                    </div>
                </div>

                <div style="margin-bottom:18px;">
                    <label class="form-label">Reason / Remarks</label>
                    <input type="text" class="form-input" x-model="fileLeave.reason" placeholder="Enter brief description of your leave reason">
                </div>

                <div style="margin-bottom:18px;">
                    <label class="form-label">Supporting Document</label>
                    <label class="upload-area">
                        <svg style="width:32px;height:32px;color:#9ca3af;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 13h6m-3-3v6m5 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                        </svg>
                        <div style="font-size:14px;font-weight:500;color:#374151;" x-text="fileLeave.fileName || 'Choose a file to upload'"></div>
                        <div style="font-size:11px;color:#9ca3af;">PDF or DOCX file size no more than 10MB</div>
                        <input type="file" accept=".pdf,.docx" style="display:none;" @change="handleFileUpload($event)">
                    </label>
                </div>

                <div class="modal-actions">
                    <button class="btn-cancel" @click="showFileLeave = false">Cancel</button>
                    <button class="btn-save" @click="submitFileLeave()" :disabled="saving"><span x-show="saving" style="display:inline-flex;align-items:center;gap:5px;"><svg style="width:13px;height:13px;animation:spin 0.8s linear infinite;flex-shrink:0;" viewBox="0 0 24 24" fill="none"><circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" style="opacity:.3"/><path fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg> Submitting...</span><span x-show="!saving">Submit</span></button>
                </div>
            </div>
        </div>

        {{-- ADD LEAVE TYPE MODAL --}}
        <div x-show="showAddLeaveType" class="modal-overlay" x-cloak @click.self="showAddLeaveType = false">
            <div class="modal-box">
                <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:22px;">
                    <div class="modal-title" style="margin-bottom:0;">Add Leave Type</div>
                    <button @click="showAddLeaveType = false" style="width:32px;height:32px;border:2px solid #374151;border-radius:50%;display:flex;align-items:center;justify-content:center;background:none;cursor:pointer;flex-shrink:0;">
                        <svg style="width:14px;height:14px;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>

                <template x-if="errorMsg">
                    <div style="background:#fee2e2;color:#b91c1c;border-radius:8px;padding:10px 14px;font-size:13px;margin-bottom:14px;" x-text="errorMsg"></div>
                </template>

                <div class="form-row" style="margin-bottom:16px;">
                    <div>
                        <label class="form-label">Leave Type Name</label>
                        <input type="text" class="form-input" x-model="newLeaveType.name" placeholder="e.g. Vacation Leave">
                    </div>
                    <div>
                        <label class="form-label">Code</label>
                        <input type="text" class="form-input" x-model="newLeaveType.code" placeholder="e.g. VL">
                    </div>
                </div>

                <div style="margin-bottom:16px;">
                    <label class="form-label">Days Entitled</label>
                    <select class="form-input" x-model="newLeaveType.days_entitled" style="appearance:none;width:100%;">
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
                                <input type="radio" name="add_is_paid" style="accent-color:#3b82f6;" :checked="newLeaveType.is_paid" @change="newLeaveType.is_paid = true"> Paid
                            </label>
                            <label style="display:flex;align-items:center;gap:6px;font-size:13px;color:#374151;cursor:pointer;">
                                <input type="radio" name="add_is_paid" style="accent-color:#3b82f6;" :checked="!newLeaveType.is_paid" @change="newLeaveType.is_paid = false"> Unpaid
                            </label>
                        </div>
                    </div>
                    <div>
                        <label class="form-label">Document Required</label>
                        <div style="display:flex;gap:16px;margin-top:8px;">
                            <label style="display:flex;align-items:center;gap:6px;font-size:13px;color:#374151;cursor:pointer;">
                                <input type="checkbox" style="width:15px;height:15px;accent-color:#3b82f6;" x-model="newLeaveType.requires_document"> Required
                            </label>
                        </div>
                    </div>
                </div>

                <div style="margin-bottom:16px;">
                    <label class="form-label">Applicable To</label>
                    <input type="text" class="form-input" x-model="newLeaveType.applicable_to" placeholder="e.g. All regular employees">
                </div>

                <div style="margin-bottom:16px;">
                    <label style="display:flex;align-items:center;gap:8px;font-size:13px;color:#374151;cursor:pointer;">
                        <input type="checkbox" style="width:15px;height:15px;accent-color:#3b82f6;" x-model="newLeaveType.carry_over">
                        Allow carry over to next year
                    </label>
                </div>

                <div class="modal-actions">
                    <button class="btn-cancel" @click="showAddLeaveType = false">Cancel</button>
                    <button class="btn-save" @click="submitAddLeaveType()" :disabled="saving" x-text="saving ? 'Saving…' : 'Add Leave Type'"></button>
                </div>
            </div>
        </div>

        {{-- VIEW LEAVE DETAILS MODAL --}}
        <div x-show="showLeaveDetails" class="modal-overlay" x-cloak @click.self="showLeaveDetails = false">
            <div class="modal-box">
                <div class="modal-title">Leave Details</div>
                
                <template x-if="leaveError">
                    <div style="background:#fee2e2;color:#b91c1c;border-radius:12px;padding:12px;font-size:13px;margin-bottom:16px;" x-text="leaveError"></div>
                </template>

                <div style="margin-bottom:16px;">
                    <div class="detail-label">Leave Type</div>
                    <div class="detail-field" x-text="selectedLeave.leave_type || '—'"></div>
                </div>

                <div class="form-row" style="margin-bottom:16px;">
                    <div>
                        <div class="detail-label">Start Date</div>
                        <div class="detail-field" x-text="selectedLeave.start_date || '—'"></div>
                    </div>
                    <div>
                        <div class="detail-label">End Date</div>
                        <div class="detail-field" x-text="selectedLeave.end_date || '—'"></div>
                    </div>
                </div>

                <div style="margin-bottom:16px;">
                    <div class="detail-label">Reason / Remarks</div>
                    <div class="detail-field" x-text="selectedLeave.reason || '—'"></div>
                </div>

                <div style="margin-bottom:16px;">
                    <div class="detail-label">Progress</div>
                    <div class="detail-field" x-text="selectedLeave.status ? selectedLeave.status.charAt(0).toUpperCase() + selectedLeave.status.slice(1) : '—'"></div>
                </div>

                <template x-if="selectedLeave.rejection_reason">
                    <div style="margin-bottom:16px;">
                        <div class="detail-label">Rejection Reason</div>
                        <div style="background:#fef2f2;color:#dc2626;border:1.5px solid #fecaca;border-radius:12px;padding:12px;font-size:13px;" x-text="selectedLeave.rejection_reason"></div>
                    </div>
                </template>

                <div class="modal-actions">
                    <template x-if="selectedLeave.status === 'pending'">
                        <button class="btn-cancel" style="background:#fef2f2;color:#dc2626;border-color:#fecaca;"
                            @click="cancelLeave(selectedLeave.id)"
                            x-text="cancelling ? 'Cancelling...' : 'Cancel Request'"></button>
                    </template>
                    <button class="btn-save" @click="showLeaveDetails = false">Close</button>
                </div>
            </div>
        </div>

        {{-- EDIT LEAVE TYPE MODAL --}}
        <div x-show="showEditLeaveType" class="modal-overlay" x-cloak @click.self="showEditLeaveType = false">
            <div class="modal-box">
                <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:22px;">
                    <div class="modal-title" style="margin-bottom:0;">Edit Leave Type</div>
                    <button @click="showEditLeaveType = false" style="width:32px;height:32px;border:2px solid #374151;border-radius:50%;display:flex;align-items:center;justify-content:center;background:none;cursor:pointer;flex-shrink:0;">
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
                    <select class="form-input" x-model="editLt.days_entitled" style="appearance:none;width:100%;">
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
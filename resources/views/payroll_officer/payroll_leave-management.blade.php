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
    $attendanceRoutes = ['payroll_officer.attendance.reports', 'payroll_officer.attendance.shift', 'payroll_officer.attendance.leave', 'payroll_officer.leave.management'];
    $payrollRoutes    = ['payroll_officer.payroll', 'payroll_officer.payslips', 'payroll_officer.contributions'];
    $requestRoutes    = ['payroll_officer.requests.pending', 'payroll_officer.requests.approved'];

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

        :root {
            --blue: #3b82f6;
            --blue-dark: #1d4ed8;
            --blue-light: #eff6ff;
            --muted: #6b7280;
            --border: #e5e7eb;
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
        .status-pending   { background: #ffedd5; color: #c2410c; padding: 3px 10px; border-radius: 20px; font-size: 11px; font-weight: 600; display: inline-block; white-space: nowrap; }
        .status-approved  { background: #dcfce7; color: #15803d; padding: 3px 10px; border-radius: 20px; font-size: 11px; font-weight: 600; display: inline-block; white-space: nowrap; }
        .status-rejected  { background: #fee2e2; color: #dc2626; padding: 3px 10px; border-radius: 20px; font-size: 11px; font-weight: 600; display: inline-block; white-space: nowrap; }
        .status-cancelled { background: #f3f4f6; color: #6b7280; padding: 3px 10px; border-radius: 20px; font-size: 11px; font-weight: 600; display: inline-block; white-space: nowrap; }

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
        .cal-holiday { background: #fee2e2; color: #dc2626; }
        .cal-legend { display: flex; align-items: center; gap: 14px; margin-top: 14px; flex-wrap: wrap; }
        .cal-legend-item { display: flex; align-items: center; gap: 6px; font-size: 11px; color: #374151; }
        .cal-legend-dot  { width: 11px; height: 11px; border-radius: 3px; flex-shrink: 0; }

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
            .btn-cancel, .btn-save { width: auto; min-width: 100px; }
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

        /* DESKTOP SIDEBAR */
        .desktop-sidebar { display: none; }
        @media (min-width: 1024px) { .desktop-sidebar { display: block; } }
        @media (max-width: 1023px) { .main-content { margin-left: 0 !important; } }
        @media (max-width: 1024px) { .content-pad { padding: 16px !important; } }
    </style>
</head>
<body class="bg-gray-50" x-data="{ mobileMenuOpen: false, collapsed: localStorage.getItem('sidebarCollapsed') === 'true' }"
     x-init="window.addEventListener('sidebar-toggle', e => { collapsed = e.detail.collapsed })">

{{-- ══════════ SIDEBAR ══════════ --}}

{{-- DESKTOP SIDEBAR --}}
<div class="hidden lg:block desktop-sidebar">
    @include('payroll_officer.payroll_sidebar')
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
        @include('payroll_officer.payroll_sidebar')
    </div>
</div>

{{-- ══════════ MAIN CONTENT ══════════ --}}
<div x-data="{
         collapsed: localStorage.getItem('sidebarCollapsed') === 'true',
         showFileLeave: false,
         showLeaveDetails: false,
         selectedLeave: {},
         leaveError: '',
         cancelling: false,
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
         showCancelConfirm: false,
         cancelTargetId: null,
         async openLeaveDetails(id) {
             this.leaveError = '';
             this.selectedLeave = {};
             const res = await fetch(`/payroll_officer/leave/${id}`, {
                 headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content }
             });
             const data = await res.json();
             if (res.ok) { this.selectedLeave = data; this.showLeaveDetails = true; } 
             else { this.leaveError = data.message ?? 'Failed to load leave details.'; }
         },
         async cancelLeave(id) {
             this.cancelling = true;
             const res = await fetch(`/payroll_officer/leave/${id}/cancel`, {
                 method: 'POST',
                 headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content }
             });
             this.cancelling = false;
             if (res.ok) { window.location.reload(); }
             else { this.leaveError = 'Failed to cancel leave request.'; }
         },
         handleFileUpload(e) {
             const file = e.target.files[0];
             if (file) { this.fileLeave.document = file; this.fileLeave.fileName = file.name; }
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
             if (this.fileLeave.document) { formData.append('document', this.fileLeave.document); }
             formData.append('_token', document.querySelector('meta[name=csrf-token]').content);
             const res = await fetch('{{ route('payroll_officer.leave.file') }}', { method: 'POST', body: formData });
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
                    <p class="text-xs sm:text-sm text-blue-100 mt-1">My Records</p>
                </div>
            </div>
            <div class="flex items-center space-x-2 sm:space-x-4">
                <x-notification-bell />
            </div>
        </div>
    </header>

    {{-- MAIN CONTENT PAD --}}
    <div class="content-pad" style="padding: 24px 32px;">

        {{-- TAB NAV --}}
        <div class="tab-nav">
            <a href="{{ route('payroll_officer.leave.management', ['tab' => 'my-leave']) }}" class="tab-btn {{ $activeTab === 'my-leave' ? 'active' : '' }}">My Leave</a>
            <a href="{{ route('payroll_officer.leave.management', ['tab' => 'leave-calendar']) }}" class="tab-btn {{ $activeTab === 'leave-calendar' ? 'active' : '' }}">Leave Calendar</a>
        </div>

        {{-- ══════════ MY LEAVE ══════════ --}}
        @if($activeTab === 'my-leave')

        <div class="leave-cards">
            @foreach($leaveTypes as $lt)
            @php $key = strtolower($lt->code); @endphp
            <div class="leave-card">
                <span class="leave-badge" style="background:#dbeafe;color:#1d4ed8;">{{ $lt->code }}</span>
                <div class="leave-card-label">{{ $lt->name }}</div>
                <div class="leave-card-value">{{ $myLeaveStats[$key.'_remaining'] ?? 0 }}</div>
                <div class="leave-card-sub">{{ $myLeaveStats[$key.'_used'] ?? 0 }} used of {{ $myLeaveStats[$key.'_total'] ?? $lt->days_entitled ?? 0 }}</div>
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
                    <option value="rejected"   {{ request('status')==='rejected'   ? 'selected' : '' }}>Rejected</option>
                    <option value="cancelled" {{ request('status')==='cancelled' ? 'selected' : '' }}>Cancelled</option>
                </select>
                <select class="filter-select" onchange="window.location.href='{{ route('payroll_officer.leave.management') }}?tab=my-leave&type='+this.value+'&status={{ request('status') }}'">
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

        {{-- DESKTOP TABLE (hidden on mobile) --}}
        <div class="table-card desktop-table">
            <div class="table-scroll">
                <table class="data-table">
                    <thead><tr><th>Ref #</th><th>Leave Type</th><th>Filed On</th><th>Date From</th><th>Date To</th><th>Days</th><th>Reason</th><th>Approver</th><th>Status</th><th></th></tr></thead>
                    <tbody>
                        @forelse($myLeaveRequests as $req)
                        <tr>
                            <td class="font-semibold text-gray-700">{{ $req->ref_no }}</td>
                            <td><span class="bg-blue-100 text-blue-800 px-2.5 py-1 rounded-full text-xs font-semibold">{{ $req->leaveType->name ?? '—' }}</span></td>
                            <td>{{ $req->created_at->format('m/d/Y') }}</td><td>{{ $req->start_date->format('m/d/Y') }}</td><td>{{ $req->end_date->format('m/d/Y') }}</td><td>{{ $req->total_days }}</td>
                            <td class="text-gray-500 truncate max-w-[120px]">{{ $req->reason }}</td>
                            <td><div class="font-semibold text-sm">{{ $req->approver ? trim($req->approver->fname.' '.$req->approver->lname) : '—' }}</div></td>
                            <td><span class="status-{{ $req->status }}">{{ ucfirst($req->status) }}</span></td>
                            <td><button class="btn-outline-sm" @click="openLeaveDetails({{ $req->id }})">View</button></td>
                        </tr>
                        @empty<td colspan="10" class="text-center py-10 text-gray-400">No leave requests found.</td>@endforelse
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
                <div class="req-card-footer"><div class="req-card-label">Reason</div><div class="text-sm text-gray-500">{{ $req->reason }}</div><button class="btn-outline-sm" @click="openLeaveDetails({{ $req->id }})">View</button></div>
            </div>
            @empty
            <div class="text-center py-10 text-gray-400">No leave requests found.</div>
            @endforelse
        </div>

        {{-- ══════════ LEAVE CALENDAR ══════════ --}}
        @elseif($activeTab === 'leave-calendar')

        <div class="cal-header">
            <span class="cal-month-label">{{ $calMonth->format('F Y') }}</span>
            <a href="{{ route('payroll_officer.leave.management', ['tab' => 'leave-calendar', 'month' => $calPrev]) }}" class="cal-nav-btn"><svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>Prev</a>
            <a href="{{ route('payroll_officer.leave.management', ['tab' => 'leave-calendar', 'month' => $calNext]) }}" class="cal-nav-btn">Next<svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg></a>
        </div>

        <form method="GET" action="{{ route('payroll_officer.leave.management') }}" id="calFilterForm">
            <input type="hidden" name="tab" value="leave-calendar"><input type="hidden" name="month" value="{{ $calMonth->format('Y-m') }}">
            <div class="cal-filters">
                <select class="cal-filter-select" name="leave_type_id" onchange="document.getElementById('calFilterForm').submit()">
                    <option value="">All Types</option>
                    @foreach($leaveTypes as $lt)<option value="{{ $lt->id }}" {{ request('leave_type_id') == $lt->id ? 'selected' : '' }}>{{ $lt->name }}</option>@endforeach
                </select>
            </div>
        </form>

        @php $eventsByDay = $calendarEvents->groupBy('day'); @endphp
        <div class="cal-wrap"><table class="cal-grid"><thead><tr>@foreach(['SUN','MON','TUE','WED','THU','FRI','SAT'] as $dh)<th>{{ $dh }}</th>@endforeach</tr></thead>
        <tbody>@php $day = 1; $startPad = $firstDow; $totalDays = $calEnd->day; @endphp
        @for($row = 0; $row < 6; $row++)@php if($day > $totalDays) break; @endphp
        <tr>@for($col = 0; $col < 7; $col++)@php $cellDay = ($row === 0 && $col < $startPad) ? null : ($day <= $totalDays ? $day++ : null); @endphp
        <td class="{{ $cellDay === null ? 'other-month' : '' }}">@if($cellDay)<div>{{ $cellDay }}</div>@foreach($eventsByDay->get($cellDay, []) as $ev)<div class="cal-event {{ $ev['type'] }}">{{ $ev['label'] }}</div>@endforeach @endif</td>@endfor</tr>@endfor
        </tbody></table></div>

        <div class="cal-legend">@foreach($leaveTypes as $lt)<div class="cal-legend-item"><div class="cal-legend-dot" style="background:#dbeafe;"></div>{{ $lt->code }} – {{ $lt->name }}</div>@endforeach</div>

        @endif

        {{-- ══════════ FILE LEAVE MODAL ══════════ --}}
        <div x-show="showFileLeave" class="modal-overlay" x-cloak @click.self="showFileLeave = false">
            <div class="modal-box">
                <div class="modal-title">Leave Request</div>
                <template x-if="errorMsg"><div class="bg-red-100 text-red-700 p-3 rounded-xl text-sm mb-4" x-text="errorMsg"></div></template>
                <div class="mb-4"><label class="form-label">Leave Type</label><select class="form-select" x-model="fileLeave.leave_type_id"><option value="">Choose leave type</option>@foreach($leaveTypes as $lt)<option value="{{ $lt->id }}">{{ $lt->name }} ({{ $lt->code }})</option>@endforeach</select></div>
                <div class="form-row mb-4"><div><label class="form-label">Start Date</label><input type="date" class="form-input" x-model="fileLeave.start_date"></div><div><label class="form-label">End Date</label><input type="date" class="form-input" x-model="fileLeave.end_date"></div></div>
                <div class="mb-4"><label class="form-label">Reason / Remarks</label><input type="text" class="form-input" x-model="fileLeave.reason" placeholder="Enter brief description of your leave reason"></div>
                <div class="mb-4"><label class="form-label">Supporting Document</label><label class="upload-area"><svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 13h6m-3-3v6m5 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg><div class="text-sm font-medium text-gray-600" x-text="fileLeave.fileName || 'Choose a file to upload'"></div><div class="text-xs text-gray-400">PDF or DOCX, max 10MB</div><input type="file" accept=".pdf,.docx" class="hidden" @change="handleFileUpload($event)"></label></div>
                <div class="modal-actions"><button class="btn-cancel" @click="showFileLeave = false">Cancel</button><button class="btn-save" @click="submitFileLeave()" :disabled="saving"><span x-show="saving"><svg class="inline animate-spin w-4 h-4 mr-1" viewBox="0 0 24 24" fill="none"><circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" opacity=".3"/><path fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg> Submitting...</span><span x-show="!saving">Submit</span></button></div>
            </div>
        </div>

        {{-- ══════════ LEAVE DETAILS MODAL ══════════ --}}
        <div x-show="showLeaveDetails" class="modal-overlay" x-cloak @click.self="showLeaveDetails = false">
            <div class="modal-box">
                <div class="modal-title">Leave Details</div>
                <template x-if="leaveError"><div class="bg-red-100 text-red-700 p-3 rounded-xl text-sm mb-4" x-text="leaveError"></div></template>
                <div class="mb-4"><div class="detail-label">Leave Type</div><div class="detail-field" x-text="selectedLeave.leave_type || '—'"></div></div>
                <div class="form-row mb-4"><div><div class="detail-label">Start Date</div><div class="detail-field" x-text="selectedLeave.start_date || '—'"></div></div><div><div class="detail-label">End Date</div><div class="detail-field" x-text="selectedLeave.end_date || '—'"></div></div></div>
                <div class="mb-4"><div class="detail-label">Reason / Remarks</div><div class="detail-field" x-text="selectedLeave.reason || '—'"></div></div>
                <div class="mb-4"><div class="detail-label">Status</div><div class="detail-field" x-text="selectedLeave.status ? selectedLeave.status.charAt(0).toUpperCase() + selectedLeave.status.slice(1) : '—'"></div></div>
                <template x-if="selectedLeave.rejection_reason"><div class="mb-4"><div class="detail-label text-red-600">Rejection Reason</div><div class="bg-red-50 text-red-700 p-3 rounded-xl text-sm border border-red-200" x-text="selectedLeave.rejection_reason"></div></div></template>
                <div class="modal-actions"><template x-if="selectedLeave.status === 'pending' || selectedLeave.status === 'supervisor_approved'"><button class="btn-cancel bg-red-50 text-red-600 border-red-200" @click="window.dispatchEvent(new CustomEvent('open-cancel-confirm',{detail:{id:selectedLeave.id}}))">Cancel Request</button></template><button class="btn-save" @click="showLeaveDetails = false">Close</button></div>
            </div>
        </div>

    </div>

    {{-- ── Cancel Leave Confirmation Modal ── --}}
    <div x-data="{ open: false, targetId: null, cancelling: false }"
         @open-cancel-confirm.window="open = true; targetId = $event.detail.id"
         x-show="open"
         x-cloak
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         class="fixed inset-0 z-[1100] flex items-center justify-center p-4 bg-black/50">
        <div x-show="open"
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0 scale-95"
             x-transition:enter-end="opacity-100 scale-100"
             x-transition:leave="transition ease-in duration-150"
             x-transition:leave-start="opacity-100 scale-100"
             x-transition:leave-end="opacity-0 scale-95"
             class="bg-white rounded-2xl shadow-2xl w-full max-w-sm p-6">
            <div class="flex items-center gap-3 mb-4">
                <div class="w-10 h-10 rounded-full bg-red-100 flex items-center justify-center flex-shrink-0">
                    <svg class="w-5 h-5 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/>
                    </svg>
                </div>
                <div>
                    <h3 class="text-base font-bold text-gray-900">Cancel Leave Request</h3>
                    <p class="text-xs text-gray-500 mt-0.5">This action cannot be undone.</p>
                </div>
            </div>
            <p class="text-sm text-gray-600 mb-6">Are you sure you want to cancel this leave request?</p>
            <div class="flex gap-3 justify-end">
                <button @click="open = false; targetId = null"
                        class="px-4 py-2 text-sm font-semibold text-gray-700 bg-white border border-gray-200 rounded-xl hover:bg-gray-50 transition-all duration-200">
                    Keep Request
                </button>
                <button @click="cancelling = true; fetch('/payroll_officer/leave/'+targetId+'/cancel',{method:'POST',headers:{'X-CSRF-TOKEN':document.querySelector('meta[name=csrf-token]').content}}).then(r=>{cancelling=false;if(r.ok)location.reload();else alert('Failed to cancel.');open=false;})"
                        :disabled="cancelling"
                        class="px-4 py-2 text-sm font-semibold text-white bg-red-600 rounded-xl hover:bg-red-700 transition-all duration-200 disabled:opacity-60"
                        x-text="cancelling ? 'Cancelling…' : 'Yes, Cancel'">
                </button>
            </div>
        </div>
    </div>
</div>

<script>
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
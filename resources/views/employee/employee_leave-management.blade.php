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
    $attendanceRoutes = ['employee.attendance.reports', 'employee.attendance.shift', 'employee.attendance.leave', 'employee.leave.management'];
    $payrollRoutes    = ['employee.payroll', 'employee.payslips', 'employee.contributions'];
    $requestRoutes    = ['employee.requests.pending', 'employee.requests.approved'];

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

        /* TABS - Responsive */
        .tab-nav { display: flex; border-bottom: 2px solid var(--border); margin-bottom: 20px; gap: 4px; overflow-x: auto; -webkit-overflow-scrolling: touch; scrollbar-width: none; }
        .tab-nav::-webkit-scrollbar { display: none; }
        .tab-btn { padding: 10px 20px; font-size: 13px; font-weight: 600; color: var(--muted); border: none; background: none; cursor: pointer; font-family: inherit; border-bottom: 3px solid transparent; margin-bottom: -2px; white-space: nowrap; text-decoration: none; display: inline-block; transition: all .15s; }
        @media (min-width: 640px) { .tab-btn { padding: 10px 24px; font-size: 14px; } }
        .tab-btn:hover { color: #374151; }
        .tab-btn.active { color: var(--blue); border-bottom-color: var(--blue); font-weight: 700; }

        /* LEAVE CARDS - Responsive Grid */
        .leave-cards { display: grid; grid-template-columns: repeat(2, 1fr); gap: 12px; margin-bottom: 24px; }
        @media (min-width: 768px) { .leave-cards { grid-template-columns: repeat(4, 1fr); gap: 16px; } }
        @media (min-width: 1024px) { .leave-cards { grid-template-columns: repeat(5, 1fr); } }
        .leave-card { background: #fff; border: 1px solid var(--border); border-radius: 14px; padding: 14px 16px; position: relative; box-shadow: 0 1px 6px rgba(0,0,0,.04); }
        @media (min-width: 640px) { .leave-card { padding: 20px 22px; } }
        .leave-card-label { font-size: 11px; color: var(--muted); font-weight: 500; margin-bottom: 4px; }
        @media (min-width: 640px) { .leave-card-label { font-size: 13px; margin-bottom: 6px; } }
        .leave-card-value { font-size: 24px; font-weight: 800; color: #111827; line-height: 1; }
        @media (min-width: 640px) { .leave-card-value { font-size: 34px; } }
        .leave-card-sub { font-size: 10px; color: #9ca3af; margin-top: 4px; }
        @media (min-width: 640px) { .leave-card-sub { font-size: 11.5px; margin-top: 6px; } }
        .leave-badge { position: absolute; top: 10px; right: 10px; padding: 2px 8px; border-radius: 20px; font-size: 10px; font-weight: 700; }
        @media (min-width: 640px) { .leave-badge { top: 18px; right: 18px; padding: 3px 10px; font-size: 12px; } }
        .lb-vl   { background: #dbeafe; color: #1d4ed8; }
        .lb-sl   { background: #fce7f3; color: #be185d; }
        .lb-lwop { background: #ffedd5; color: #c2410c; }

        /* TOOLBAR - Responsive */
        .toolbar { display: flex; flex-direction: column; align-items: stretch; gap: 12px; margin-bottom: 16px; }
        @media (min-width: 640px) { .toolbar { flex-direction: row; align-items: center; justify-content: space-between; } }
        .toolbar-title { font-size: 16px; font-weight: 700; color: #111827; }
        .toolbar-right { display: flex; align-items: center; gap: 8px; flex-wrap: wrap; }
        .filter-select { appearance: none; background: #f9fafb; border: 1px solid var(--border); border-radius: 8px; padding: 8px 28px 8px 12px; font-size: 12px; color: #374151; cursor: pointer; outline: none; font-family: inherit; background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='10' height='6' fill='none'%3E%3Cpath d='M1 1l4 4 4-4' stroke='%236b7280' stroke-width='1.5' stroke-linecap='round'/%3E%3C/svg%3E"); background-repeat: no-repeat; background-position: right 10px center; }
        @media (min-width: 640px) { .filter-select { font-size: 13px; } }
        .btn-primary { display: inline-flex; align-items: center; gap: 6px; padding: 8px 16px; border-radius: 8px; font-size: 12px; font-weight: 600; font-family: inherit; cursor: pointer; border: none; background: var(--blue); color: #fff; transition: background .15s; white-space: nowrap; }
        @media (min-width: 640px) { .btn-primary { padding: 8px 18px; font-size: 13px; } }
        .btn-primary:hover { background: var(--blue-dark); }
        .btn-outline-sm { display: inline-flex; align-items: center; padding: 5px 12px; border-radius: 7px; font-size: 11px; font-weight: 600; font-family: inherit; cursor: pointer; border: 1.5px solid #bfdbfe; background: var(--blue-light); color: var(--blue-dark); }
        @media (min-width: 640px) { .btn-outline-sm { padding: 5px 14px; font-size: 12px; } }
        .btn-outline-sm:hover { background: #dbeafe; }

        /* TABLE - Responsive Scroll */
        .table-card { background: #fff; border-radius: 14px; border: 1px solid var(--border); overflow-x: auto; box-shadow: 0 1px 8px rgba(0,0,0,.04); -webkit-overflow-scrolling: touch; }
        .data-table { width: 100%; border-collapse: collapse; min-width: 700px; }
        .data-table thead tr { background: #f9fafb; }
        .data-table th { padding: 10px 12px; font-size: 10px; font-weight: 600; color: var(--muted); text-align: left; border-bottom: 1px solid var(--border); white-space: nowrap; letter-spacing: .4px; text-transform: uppercase; }
        @media (min-width: 640px) { .data-table th { padding: 11px 16px; font-size: 11.5px; } }
        .data-table td { padding: 12px 12px; font-size: 12px; color: #111827; border-bottom: 1px solid #f3f4f6; vertical-align: middle; }
        @media (min-width: 640px) { .data-table td { padding: 14px 16px; font-size: 13px; } }
        .data-table tr:last-child td { border-bottom: none; }
        .data-table tbody tr:hover { background: #fafafa; }

        /* STATUS BADGES */
        .lt-vl   { background: #dbeafe; color: #1d4ed8; padding: 3px 10px; border-radius: 20px; font-size: 11px; font-weight: 600; display: inline-block; white-space: nowrap; }
        .lt-sl   { background: #fce7f3; color: #be185d; padding: 3px 10px; border-radius: 20px; font-size: 11px; font-weight: 600; display: inline-block; white-space: nowrap; }
        .lt-lwop { background: #ffedd5; color: #c2410c; padding: 3px 10px; border-radius: 20px; font-size: 11px; font-weight: 600; display: inline-block; white-space: nowrap; }
        .status-pending  { background: #ffedd5; color: #c2410c; padding: 3px 10px; border-radius: 20px; font-size: 11px; font-weight: 600; display: inline-block; white-space: nowrap; }
        .status-approved { background: #dcfce7; color: #15803d; padding: 3px 10px; border-radius: 20px; font-size: 11px; font-weight: 600; display: inline-block; white-space: nowrap; }
        .status-rejected { background: #fee2e2; color: #dc2626; padding: 3px 10px; border-radius: 20px; font-size: 11px; font-weight: 600; display: inline-block; white-space: nowrap; }
        @media (min-width: 640px) {
            .lt-vl, .lt-sl, .lt-lwop, .status-pending, .status-approved, .status-rejected { padding: 3px 12px; font-size: 12px; }
        }

        /* CALENDAR - Responsive */
        .cal-nav { display: flex; align-items: center; gap: 8px; flex-wrap: wrap; }
        .cal-month-label { font-size: 18px; font-weight: 800; color: #111827; }
        @media (min-width: 640px) { .cal-month-label { font-size: 20px; } }
        .cal-nav-btn { display: inline-flex; align-items: center; gap: 4px; padding: 5px 10px; border-radius: 7px; font-size: 11px; font-weight: 500; font-family: inherit; cursor: pointer; border: 1px solid var(--border); background: #fff; color: #374151; text-decoration: none; }
        @media (min-width: 640px) { .cal-nav-btn { padding: 6px 12px; font-size: 12.5px; gap: 5px; } }
        .cal-nav-btn:hover { background: #f9fafb; }
        .cal-grid-container { overflow-x: auto; -webkit-overflow-scrolling: touch; border-radius: 14px; }
        .cal-grid { width: 100%; border-collapse: collapse; background: #fff; border: 1px solid var(--border); min-width: 500px; }
        .cal-grid th { padding: 8px 4px; font-size: 10px; font-weight: 700; color: var(--muted); text-align: center; background: #f9fafb; border-bottom: 1px solid var(--border); letter-spacing: .5px; text-transform: uppercase; }
        @media (min-width: 640px) { .cal-grid th { padding: 10px; font-size: 12px; } }
        .cal-grid td { width: 14.28%; height: 70px; padding: 4px; border: 1px solid #f3f4f6; vertical-align: top; font-size: 11px; font-weight: 600; color: #374151; }
        @media (min-width: 640px) { .cal-grid td { height: 100px; padding: 8px; font-size: 13px; } }
        .cal-grid td.other-month { background: #fafafa; color: #d1d5db; }
        .cal-event { display: inline-block; padding: 2px 5px; border-radius: 4px; font-size: 9px; font-weight: 600; margin-top: 3px; width: 100%; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
        @media (min-width: 640px) { .cal-event { padding: 2px 8px; font-size: 11px; margin-top: 4px; } }
        .cal-vl      { background: #dbeafe; color: #1d4ed8; }
        .cal-sl      { background: #fce7f3; color: #be185d; }
        .cal-lwop    { background: #ffedd5; color: #c2410c; }
        .cal-holiday { background: #fee2e2; color: #dc2626; }
        .cal-legend { display: flex; align-items: center; gap: 12px; margin-top: 16px; flex-wrap: wrap; }
        .cal-legend-item { display: flex; align-items: center; gap: 6px; font-size: 10px; color: #374151; }
        @media (min-width: 640px) { .cal-legend-item { font-size: 12px; gap: 8px; } }
        .cal-legend-dot  { width: 10px; height: 10px; border-radius: 3px; flex-shrink: 0; }
        @media (min-width: 640px) { .cal-legend-dot { width: 12px; height: 12px; } }

        /* MODAL - Mobile First (Slide Up) */
        .modal-overlay { position: fixed; inset: 0; background: rgba(0,0,0,0.5); display: flex; align-items: flex-end; justify-content: center; z-index: 1000; padding: 0; }
        @media (min-width: 640px) { .modal-overlay { align-items: center; padding: 20px; } }
        .modal-box { background: #fff; border-radius: 28px 28px 0 0; padding: 24px 20px 32px; width: 100%; max-width: 100%; max-height: 90vh; overflow-y: auto; box-shadow: 0 -4px 20px rgba(0,0,0,0.15); }
        @media (min-width: 640px) { .modal-box { border-radius: 24px; padding: 28px 32px; max-width: 520px; max-height: 85vh; } }
        .modal-box::before { content: ''; display: block; width: 40px; height: 4px; background: #e5e7eb; border-radius: 2px; margin: 0 auto 20px; }
        @media (min-width: 640px) { .modal-box::before { display: none; } }
        .modal-title { font-size: 18px; font-weight: 800; color: #111827; }
        @media (min-width: 640px) { .modal-title { font-size: 20px; } }
        .form-label  { font-size: 11px; font-weight: 600; color: #374151; text-transform: uppercase; letter-spacing: .5px; margin-bottom: 4px; display: block; }
        @media (min-width: 640px) { .form-label { font-size: 12px; margin-bottom: 5px; } }
        .form-input  { width: 100%; border: 1.5px solid var(--border); border-radius: 8px; padding: 9px 13px; font-size: 13px; font-family: inherit; outline: none; color: #111827; transition: border-color .15s; }
        .form-input:focus { border-color: var(--blue); }
        .form-row    { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; }
        @media (min-width: 640px) { .form-row { gap: 14px; } }
        .modal-actions { display: flex; flex-direction: column-reverse; gap: 10px; margin-top: 22px; }
        @media (min-width: 640px) { .modal-actions { flex-direction: row; justify-content: flex-end; } }
        .btn-cancel { padding: 10px 18px; border-radius: 8px; font-size: 13px; font-weight: 600; font-family: inherit; cursor: pointer; border: 1.5px solid var(--border); background: #fff; color: #374151; width: 100%; }
        .btn-save   { padding: 10px 20px; border-radius: 8px; font-size: 13px; font-weight: 600; font-family: inherit; cursor: pointer; border: none; background: var(--blue); color: #fff; width: 100%; }
        @media (min-width: 640px) { .btn-cancel, .btn-save { width: auto; } }
        .close-btn  { width: 32px; height: 32px; border: 2px solid #374151; border-radius: 50%; display: flex; align-items: center; justify-content: center; background: none; cursor: pointer; flex-shrink: 0; }
        @keyframes spin { from { transform: rotate(0deg); } to { transform: rotate(360deg); } }
        .upload-area { display: flex; flex-direction: column; align-items: center; justify-content: center; gap: 8px; border: 2px dashed #d1d5db; border-radius: 10px; padding: 24px 16px; background: #f9fafb; cursor: pointer; transition: all .15s; }
        @media (min-width: 640px) { .upload-area { padding: 28px 20px; gap: 10px; } }
        .upload-area:hover { border-color: var(--blue); background: #eff6ff; }

        /* SIDEBAR RESPONSIVE */
        .desktop-sidebar { display: none; }
        @media (min-width: 1024px) { .desktop-sidebar { display: block; } }
        @media (max-width: 1023px) { .main-content { margin-left: 0 !important; } }
        
        /* MAIN CONTENT PADDING - Responsive */
        .main-content > div:first-of-type {
            padding: 16px 20px;
        }
        @media (min-width: 640px) {
            .main-content > div:first-of-type {
                padding: 24px 32px;
            }
        }
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
        const res = await fetch(`/employee/leave/${id}`, {
            headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content }
        });
        const data = await res.json();
        if (res.ok) { this.selectedLeave = data; this.showLeaveDetails = true; }
        else { this.leaveError = data.message ?? 'Failed to load leave details.'; }
    },
    async cancelLeave(id) {
        if (!confirm('Are you sure you want to cancel this leave request?')) return;
        this.cancelling = true;
        const res = await fetch(`/employee/leave/${id}/cancel`, {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content }
        });
        this.cancelling = false;
        if (res.ok) { window.location.reload(); }
        else { this.leaveError = 'Failed to cancel leave request.'; }
    }
}"
     x-init="window.addEventListener('sidebar-toggle', e => { sidebarCollapsed = e.detail.collapsed })">

{{-- ══════════ DESKTOP SIDEBAR ══════════ --}}
<div class="hidden lg:block desktop-sidebar">
    @include('employee.employee_sidebar')
</div>

{{-- ══════════ MOBILE SLIDE-OUT DRAWER ══════════ --}}
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
        @include('employee.employee_sidebar')
    </div>
</div>

{{-- ══════════ MAIN CONTENT ══════════ --}}
<div class="main-content"
     :style="window.innerWidth >= 1024 ? (sidebarCollapsed ? 'margin-left:5rem' : 'margin-left:16rem') : 'margin-left:0'"
     x-on:resize.window="$el.style.marginLeft = window.innerWidth >= 1024 ? (sidebarCollapsed ? '5rem' : '16rem') : '0'"
     style="transition:margin-left 0.35s cubic-bezier(0.4,0,0.2,1); min-height:100vh;">

    {{-- HEADER with hamburger --}}
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
                    <p class="text-xs sm:text-sm text-blue-100 mt-1">Leave Management</p>
                </div>
            </div>
            <div class="flex items-center space-x-2 sm:space-x-4">
                <x-employee-notif />
            </div>
        </div>
    </header>

    {{-- MAIN CONTENT WRAPPER with responsive padding --}}
    <div>
        {{-- Tab Nav --}}
        <div class="tab-nav">
            <a href="{{ route('employee.leave.management', ['tab' => 'my-leave']) }}"       class="tab-btn {{ $activeTab === 'my-leave'      ? 'active' : '' }}">My Leave</a>
            <a href="{{ route('employee.leave.management', ['tab' => 'leave-calendar']) }}" class="tab-btn {{ $activeTab === 'leave-calendar' ? 'active' : '' }}">Leave Calendar</a>
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
                    <option value="rejected" {{ request('status')==='rejected' ? 'selected' : '' }}>Rejected</option>
                </select>
                <select class="filter-select" onchange="window.location.href='{{ route('employee.leave.management') }}?tab=my-leave&type='+this.value+'&status={{ request('status') }}'">
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

        <div class="table-card">
            <table class="data-table">
                <thead><tr>
                    <th>Ref #</th><th>Leave Type</th><th>Filed On</th><th>Date From</th><th>Date To</th><th>Days</th><th>Reason</th><th>Approver</th><th>Status</th><th></th>
                </tr></thead>
                <tbody>
                @forelse($myLeaveRequests as $req)
                <tr>
                    <td style="font-weight:600;color:#6b7280;">{{ $req->ref_no }}</td>
                    <td>
                        <span style="background:#dbeafe;color:#1d4ed8;padding:3px 11px;border-radius:20px;font-size:12px;font-weight:600;display:inline-block;white-space:nowrap;" title="{{ $req->leaveType->name ?? '' }}">{{ $req->leaveType->code ?? $req->leaveType->name ?? '—' }}</span>
                    </td>
                    <td>{{ $req->created_at->format('m/d/Y') }}</td>
                    <td>{{ $req->start_date->format('m/d/Y') }}</td>
                    <td>{{ $req->end_date->format('m/d/Y') }}</td>
                    <td>{{ $req->total_days }}</td>
                    <td style="color:#6b7280; max-width:150px; overflow:hidden; text-overflow:ellipsis; white-space:nowrap;">{{ $req->reason }}</td>
                    <td>
                        <div style="font-weight:700;font-size:13px;">{{ $req->approver ? trim($req->approver->fname.' '.$req->approver->lname) : '—' }}</div>
                        <div style="font-size:11px;color:#9ca3af;">{{ $req->approver?->jobTitle?->title ?? '—' }}</div>
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

        {{-- ══════════ LEAVE CALENDAR ══════════ --}}
        @elseif($activeTab === 'leave-calendar')

        <div class="toolbar">
            <div class="cal-nav">
                <a href="{{ route('employee.leave.management', ['tab' => 'leave-calendar', 'month' => $calPrev]) }}" class="cal-nav-btn">
                    <svg style="width:12px;height:12px;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                    Prev
                </a>
                <span class="cal-month-label">{{ $calMonth->format('F Y') }}</span>
                <a href="{{ route('employee.leave.management', ['tab' => 'leave-calendar', 'month' => $calNext]) }}" class="cal-nav-btn">
                    Next
                    <svg style="width:12px;height:12px;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                </a>
            </div>
            <div class="toolbar-right">
                <form method="GET" action="{{ route('employee.leave.management') }}" id="calFilterForm" style="display:contents;">
                    <input type="hidden" name="tab" value="leave-calendar">
                    <input type="hidden" name="month" value="{{ $calMonth->format('Y-m') }}">
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

        <div class="cal-grid-container">
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

        @endif

        {{-- ══════════ MODALS ══════════ --}}

        {{-- File Leave Modal --}}
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
                     const res = await fetch('{{ route('employee.leave.file') }}', { method: 'POST', body: form });
                     this.saving = false;
                     const data = await res.json();
                     if (res.ok) { window.location.reload(); }
                     else { this.errorMsg = data.message ?? 'Something went wrong.'; }
                 }
             }">
            <div class="modal-box">
                <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:22px;">
                    <div class="modal-title">Leave Request</div>
                    <button @click="showFileLeave = false" class="close-btn"><svg style="width:14px;height:14px;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"/></svg></button>
                </div>
                <template x-if="errorMsg">
                    <div style="background:#fee2e2;color:#b91c1c;border-radius:8px;padding:10px 14px;font-size:13px;margin-bottom:14px;" x-text="errorMsg"></div>
                </template>
                <div style="margin-bottom:16px;">
                    <label class="form-label">Leave Type</label>
                    <select class="form-input" x-model="leaveTypeId" style="appearance:none;width:100%;">
                        <option value="">Choose leave type</option>
                        @foreach($leaveTypes as $lt)
                        <option value="{{ $lt->id }}">{{ $lt->name }} ({{ $lt->code }})</option>
                        @endforeach
                    </select>
                </div>
                <div class="form-row" style="margin-bottom:16px;">
                    <div><label class="form-label">Start Date</label><input type="date" class="form-input" x-model="startDate"></div>
                    <div><label class="form-label">End Date</label><input type="date" class="form-input" x-model="endDate"></div>
                </div>
                <div style="margin-bottom:16px;">
                    <label class="form-label">Reason/Remarks</label>
                    <input type="text" class="form-input" x-model="reason" placeholder="Enter brief description of your leave reason">
                </div>
                <div style="margin-bottom:16px;">
                    <label class="form-label">Supporting Document</label>
                    <label class="upload-area">
                        <svg style="width:28px;height:28px;color:#9ca3af;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 13h6m-3-3v6m5 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                        <div style="font-size:13px;font-weight:600;color:#374151;" x-text="fileName || 'Choose a file to upload'"></div>
                        <div style="font-size:11px;color:#9ca3af;">PDF or DOCX, max 10MB</div>
                        <input id="empLeaveDocInput" type="file" accept=".pdf,.docx" style="display:none;" @change="handleFile($event)">
                    </label>
                </div>
                <div class="modal-actions">
                    <button class="btn-cancel" @click="showFileLeave = false">Cancel</button>
                    <button class="btn-save" @click="submit()" :disabled="saving"><span x-show="saving" style="display:inline-flex;align-items:center;gap:5px;"><svg style="width:13px;height:13px;animation:spin 0.8s linear infinite;flex-shrink:0;" viewBox="0 0 24 24" fill="none"><circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" style="opacity:.3"/><path fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg> Submitting…</span><span x-show="!saving">Submit</span></button>
                </div>
            </div>
        </div>

        {{-- Leave Details Modal --}}
        <div x-show="showLeaveDetails" class="modal-overlay" x-cloak @click.self="showLeaveDetails = false">
            <div class="modal-box">
                <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:22px;">
                    <div class="modal-title">Leave Details</div>
                    <button @click="showLeaveDetails = false" class="close-btn"><svg style="width:14px;height:14px;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"/></svg></button>
                </div>
                <template x-if="leaveError">
                    <div style="background:#fee2e2;color:#b91c1c;border-radius:8px;padding:10px 14px;font-size:13px;margin-bottom:14px;" x-text="leaveError"></div>
                </template>
                <div style="margin-bottom:16px;">
                    <label class="form-label">Ref #</label>
                    <div class="form-input" style="background:#f9fafb;color:#6b7280;cursor:default;" x-text="selectedLeave.ref_no || '—'"></div>
                </div>
                <div style="margin-bottom:16px;">
                    <label class="form-label">Leave Type</label>
                    <div class="form-input" style="background:#f9fafb;color:#6b7280;cursor:default;" x-text="selectedLeave.leave_type || '—'"></div>
                </div>
                <div class="form-row" style="margin-bottom:16px;">
                    <div>
                        <label class="form-label">Start Date</label>
                        <div class="form-input" style="background:#f9fafb;color:#6b7280;cursor:default;" x-text="selectedLeave.start_date || '—'"></div>
                    </div>
                    <div>
                        <label class="form-label">End Date</label>
                        <div class="form-input" style="background:#f9fafb;color:#6b7280;cursor:default;" x-text="selectedLeave.end_date || '—'"></div>
                    </div>
                </div>
                <div style="margin-bottom:16px;">
                    <label class="form-label">Total Days</label>
                    <div class="form-input" style="background:#f9fafb;color:#6b7280;cursor:default;" x-text="selectedLeave.total_days || '—'"></div>
                </div>
                <div style="margin-bottom:16px;">
                    <label class="form-label">Reason/Remarks</label>
                    <div class="form-input" style="background:#f9fafb;color:#6b7280;cursor:default;" x-text="selectedLeave.reason || '—'"></div>
                </div>
                <div style="margin-bottom:16px;">
                    <label class="form-label">Status</label>
                    <div class="form-input" style="background:#f9fafb;color:#6b7280;cursor:default;" x-text="selectedLeave.status ? selectedLeave.status.charAt(0).toUpperCase() + selectedLeave.status.slice(1) : '—'"></div>
                </div>
                <template x-if="selectedLeave.rejection_reason">
                    <div style="margin-bottom:16px;">
                        <label class="form-label">Rejection Reason</label>
                        <div class="form-input" style="background:#fef2f2;color:#dc2626;cursor:default;" x-text="selectedLeave.rejection_reason"></div>
                    </div>
                </template>
                <div class="modal-actions">
                    <template x-if="selectedLeave.status === 'pending'">
                        <button class="btn-cancel" style="background:#fef2f2;color:#dc2626;border-color:#fecaca;"
                            @click="cancelLeave(selectedLeave.id)" x-text="cancelling ? 'Cancelling…' : 'Cancel Request'"></button>
                    </template>
                    <button class="btn-save" @click="showLeaveDetails = false">Close</button>
                </div>
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
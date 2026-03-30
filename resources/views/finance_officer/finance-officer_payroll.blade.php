<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payroll — MEDISOURCE Finance Officer</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        [x-cloak] { display: none !important; }
        body { font-family: 'DM Sans', sans-serif; background: #f0f2f5; }

        /* ── Sidebar ── */
        .nav-item { transition: all 0.2s cubic-bezier(0.4,0,0.2,1); }
        .nav-item:hover { transform: translateX(2px); }
        .submenu-item { transition: all 0.18s ease; }
        .submenu-item:hover { transform: translateX(3px); }
        .chevron-icon { transition: transform 0.3s cubic-bezier(0.4,0,0.2,1); }
        .collapse-btn { transition: all 0.2s ease; }
        .collapse-btn:hover { transform: scale(1.08); }
        .settings-icon { transition: transform 0.5s ease; }
        .nav-item:hover .settings-icon { transform: rotate(60deg); }
        .logout-btn { transition: all 0.2s ease; }
        .avatar-ring { box-shadow: 0 0 0 3px rgba(59,130,246,0.2); transition: box-shadow 0.3s ease; }
        .avatar-ring:hover { box-shadow: 0 0 0 5px rgba(59,130,246,0.35); }
        .profile-card { transition: background 0.2s ease; }

        /* ── Main content ── */
        .main-content { transition: margin-left 0.35s cubic-bezier(0.4,0,0.2,1); }

        /* ── Tabs ── */
        .tab-bar { display: flex; gap: 0; border-bottom: 1px solid #e5e7eb; }
        .tab-btn {
            position: relative; padding: 12px 24px; font-size: 0.9rem; font-weight: 500;
            color: #9ca3af; border: none; background: none; cursor: pointer;
            white-space: nowrap; transition: color 0.2s ease;
            border-bottom: 2px solid transparent; margin-bottom: -1px;
        }
        .tab-btn:hover:not(.active) { color: #374151; }
        .tab-btn.active { color: #2563eb; font-weight: 600; border-bottom: 2px solid #2563eb; }

        /* ── Animations ── */
        @keyframes tabFadeIn { from { opacity:0; transform:translateY(8px); } to { opacity:1; transform:translateY(0); } }
        .tab-content { animation: tabFadeIn 0.28s cubic-bezier(0.4,0,0.2,1); }
        @keyframes fadeSlideUp { from { opacity:0; transform:translateY(16px); } to { opacity:1; transform:translateY(0); } }
        .anim-1 { animation: fadeSlideUp 0.38s ease both; }
        .anim-2 { animation: fadeSlideUp 0.38s 0.06s ease both; }
        .anim-3 { animation: fadeSlideUp 0.38s 0.12s ease both; }
        @keyframes slideInRight { from { opacity:0; transform:translateX(20px); } to { opacity:1; transform:translateX(0); } }
        .slide-in-right { animation: slideInRight 0.25s cubic-bezier(0.4,0,0.2,1) both; }

        /* ── Summary cards ── */
        .summary-card { background:#fff; border-radius:10px; border:1px solid #e5e7eb; padding:22px 26px; flex:1; transition:transform 0.2s ease, box-shadow 0.2s ease; }
        .summary-card:hover { transform:translateY(-2px); box-shadow:0 8px 24px rgba(59,130,246,0.10); }
        .summary-card .label { font-size:0.78rem; color:#9ca3af; margin-bottom:6px; }
        .summary-card .value { font-size:1.75rem; font-weight:700; color:#1e293b; letter-spacing:-0.5px; }
        .summary-card .sub   { font-size:0.72rem; color:#9ca3af; margin-top:4px; }

        /* ── Badges ── */
        .badge-pending    { background:#fff3e0; color:#e65100; }
        .badge-completed  { background:#e8f5e9; color:#2e7d32; }
        .badge-submitted  { background:#e3f2fd; color:#1565c0; }
        .badge-active     { background:#e8f5e9; color:#2e7d32; }
        .badge-inactive   { background:#fce4ec; color:#c62828; }
        .badge-addition   { background:#fce4ec; color:#ad1457; }
        .badge-deduction  { background:#e8eaf6; color:#283593; }
        .badge-allowance  { background:#fce4ec; color:#ad1457; }
        .badge-nontaxable { background:#e3f2fd; color:#1565c0; }
        .badge-taxable    { background:#e3f2fd; color:#1565c0; }
        .multiplier-badge { background:#fff8e1; color:#f57c00; padding:2px 10px; border-radius:20px; font-size:0.78rem; font-weight:600; }

        /* ── Tables ── */
        .data-table { width:100%; border-collapse:collapse; }
        .data-table thead tr { background:#f8fafc; border-bottom:1px solid #e5e7eb; }
        .data-table thead th { text-align:left; padding:11px 20px; font-size:0.75rem; font-weight:600; color:#9ca3af; text-transform:uppercase; letter-spacing:0.05em; }
        .data-table tbody tr { border-bottom:1px solid #f1f5f9; transition:background 0.15s; }
        .data-table tbody tr:hover { background:#f8faff; }
        .data-table tbody td { padding:14px 20px; font-size:0.875rem; color:#374151; }

        .pv-table { width:100%; border-collapse:collapse; }
        .pv-table thead tr { border-bottom:1px solid #e5e7eb; }
        .pv-table thead th { text-align:left; padding:12px 16px; font-size:0.8rem; font-weight:600; color:#374151; }
        .pv-table tbody tr { border-bottom:1px solid #f1f5f9; transition:background 0.15s; cursor:pointer; }
        .pv-table tbody tr:hover { background:#f8faff; }
        .pv-table tbody tr.row-active { background:#eff6ff; }
        .pv-table tbody td { padding:14px 16px; font-size:0.875rem; color:#374151; }

        /* ── Progress bars ── */
        .progress-track { height:4px; background:#e5e7eb; border-radius:99px; overflow:hidden; margin-top:5px; }
        .progress-fill  { height:100%; background:#3b82f6; border-radius:99px; transition:width 0.6s cubic-bezier(0.4,0,0.2,1); }

        /* ── Payslip panel ── */
        .payslip-header { background:linear-gradient(135deg,#3b82f6 0%,#2563eb 100%); border-radius:12px 12px 0 0; padding:18px 22px; color:#fff; }
        .payslip-body { padding:0 22px 22px; }
        .payslip-section-title { font-size:0.82rem; font-weight:700; color:#374151; margin-top:16px; margin-bottom:8px; }
        .payslip-line { display:flex; justify-content:space-between; font-size:0.82rem; color:#6b7280; padding:3px 0; }
        .payslip-line.bold { font-weight:700; color:#1e293b; font-size:0.875rem; border-top:1px solid #e5e7eb; padding-top:8px; margin-top:4px; }
        .info-label { font-size:0.72rem; color:rgba(255,255,255,0.75); }
        .info-value { font-size:0.82rem; color:#fff; font-weight:500; }

        /* ── Inputs / Selects ── */
        .ctrl { border:1px solid #e2e8f0; border-radius:8px; padding:9px 14px; font-size:0.875rem; background:#fff; color:#374151; outline:none; transition:border-color 0.2s, box-shadow 0.2s; }
        .ctrl:focus { border-color:#3b82f6; box-shadow:0 0 0 3px rgba(59,130,246,0.1); }
        .search-wrap { position:relative; }
        .search-wrap svg { position:absolute; left:12px; top:50%; transform:translateY(-50%); width:16px; height:16px; color:#9ca3af; }
        .search-wrap input { padding-left:38px; }
        select { appearance:none; background-image:url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 24 24' fill='none' stroke='%239ca3af' stroke-width='2'%3E%3Cpath d='M19 9l-7 7-7-7'/%3E%3C/svg%3E"); background-repeat:no-repeat; background-position:right 12px center; padding-right:32px; }

        /* ── Buttons ── */
        .btn-primary { background:#3b82f6; color:#fff; border-radius:8px; padding:9px 18px; font-size:0.875rem; font-weight:600; display:inline-flex; align-items:center; gap:6px; border:none; cursor:pointer; transition:background 0.2s; }
        .btn-primary:hover { background:#2563eb; }
        .btn-view { border:1px solid #e2e8f0; border-radius:7px; padding:5px 14px; font-size:0.8rem; font-weight:500; color:#374151; background:#fff; cursor:pointer; transition:all 0.15s; }
        .btn-view:hover { background:#eff6ff; color:#2563eb; border-color:#bfdbfe; }
        .btn-outline { border:1px solid #e2e8f0; border-radius:8px; padding:9px 18px; font-size:0.875rem; font-weight:500; color:#374151; background:#fff; cursor:pointer; transition:background 0.15s; }
        .btn-outline:hover { background:#f3f4f6; }
        .btn-back { display:inline-flex; align-items:center; gap:6px; padding:8px 16px; border:1px solid #e5e7eb; border-radius:8px; font-size:0.82rem; font-weight:500; color:#374151; background:#fff; cursor:pointer; transition:background 0.15s; text-decoration:none; }
        .btn-back:hover { background:#f3f4f6; }

        /* ── Modal ── */
        .modal-overlay { background:rgba(0,0,0,0.4); backdrop-filter:blur(2px); }
    </style>
</head>

@php
    $currentRoute = request()->route()->getName();
    $sidebarUser = auth()->user();
    $sidebarEmployee = $sidebarUser ? \App\Models\Employee::with('jobTitle')->where('user_id', $sidebarUser->id)->first() : null;
    $sidebarInitials = $sidebarEmployee
        ? strtoupper(substr($sidebarEmployee->fname, 0, 1) . substr($sidebarEmployee->lname, 0, 1))
        : ($sidebarUser ? strtoupper(substr($sidebarUser->email, 0, 2)) : 'U');
    $sidebarName = $sidebarEmployee
        ? trim($sidebarEmployee->fname . ' ' . $sidebarEmployee->lname)
        : ($sidebarUser?->email ?? 'User');
    $sidebarRole = $sidebarEmployee?->jobTitle?->title ?? ($sidebarUser?->role ?? '—');

    $attendanceRoutes = ['finance_officer.attendance.reports', 'finance_officer.attendance.shift', 'finance_officer.attendance.leave', 'finance_officer.leave.management'];
    $payrollRoutes    = ['finance_officer.payroll', 'finance_officer.payslips', 'finance_officer.govpay'];
    $requestRoutes    = ['finance_officer.requests.pending', 'finance_officer.requests.approved'];

    // ── Fallbacks for all variables (in case controller does not pass them) ──
    $defaultPayslips = [
        ["id"=>1,"employeeName"=>"Juan Dela Cruz","jobTitle"=>"Senior Programmer","department"=>"IT","basicPay"=>23655,"otPay"=>1425,"benefits"=>3000,"grossPay"=>28090,"sss"=>1125,"philhealth"=>500,"pagibig"=>200,"withholdingTax"=>2995,"totalDeductions"=>4820,"netPay"=>23680,"status"=>"Submitted"],
        ["id"=>2,"employeeName"=>"Maria Santos","jobTitle"=>"Nurse","department"=>"Medical","basicPay"=>23655,"otPay"=>845,"benefits"=>2000,"grossPay"=>26500,"sss"=>1125,"philhealth"=>500,"pagibig"=>200,"withholdingTax"=>2500,"totalDeductions"=>4325,"netPay"=>22175,"status"=>"Pending"],
        ["id"=>3,"employeeName"=>"Pedro Reyes","jobTitle"=>"Accountant","department"=>"Finance","basicPay"=>23655,"otPay"=>0,"benefits"=>2000,"grossPay"=>25655,"sss"=>1125,"philhealth"=>500,"pagibig"=>200,"withholdingTax"=>2200,"totalDeductions"=>4025,"netPay"=>21630,"status"=>"Pending"],
        ["id"=>4,"employeeName"=>"Ana Ramos","jobTitle"=>"HR Officer","department"=>"HR","basicPay"=>20000,"otPay"=>500,"benefits"=>1500,"grossPay"=>22000,"sss"=>900,"philhealth"=>440,"pagibig"=>200,"withholdingTax"=>1800,"totalDeductions"=>3340,"netPay"=>18660,"status"=>"Pending"],
        ["id"=>5,"employeeName"=>"Carlo Mendoza","jobTitle"=>"Sales Rep","department"=>"Sales","basicPay"=>18000,"otPay"=>1200,"benefits"=>1000,"grossPay"=>20200,"sss"=>810,"philhealth"=>404,"pagibig"=>200,"withholdingTax"=>1600,"totalDeductions"=>3014,"netPay"=>17186,"status"=>"Pending"],
        ["id"=>6,"employeeName"=>"Liza Cruz","jobTitle"=>"Pharmacist","department"=>"Pharmacy","basicPay"=>25000,"otPay"=>0,"benefits"=>2500,"grossPay"=>27500,"sss"=>1125,"philhealth"=>500,"pagibig"=>200,"withholdingTax"=>2800,"totalDeductions"=>4625,"netPay"=>22875,"status"=>"Pending"],
        ["id"=>7,"employeeName"=>"Rico Torres","jobTitle"=>"Driver","department"=>"Logistics","basicPay"=>15000,"otPay"=>750,"benefits"=>500,"grossPay"=>16250,"sss"=>675,"philhealth"=>325,"pagibig"=>200,"withholdingTax"=>900,"totalDeductions"=>2100,"netPay"=>14150,"status"=>"Pending"],
    ];
    $resolvedPayslips = $payslipsJson ?? $defaultPayslips;

    // Summary card fallbacks
    $grossPayroll    = $grossPayroll    ?? collect($defaultPayslips)->sum('grossPay');
    $netPay          = $netPay          ?? collect($defaultPayslips)->sum('netPay');
    $totalDeductions = $totalDeductions ?? collect($defaultPayslips)->sum('totalDeductions');
    $daysToCutoff    = $daysToCutoff    ?? \Carbon\Carbon::now()->diffInDays(\Carbon\Carbon::create(now()->year, now()->month, 28), false);
    $latestPeriod    = $latestPeriod    ?? null;
    $year            = $year            ?? now()->year;

    // Table data fallbacks
    $periods      = $periods      ?? collect([]);
    $payrollItems = $payrollItems ?? collect([]);
    $benefits     = $benefits     ?? collect([]);
@endphp

<body x-data="payrollApp()" x-init="init()">

    {{-- ══════════════════════════════════════
         SIDEBAR
    ══════════════════════════════════════ --}}
    <aside
        class="bg-white border-r border-gray-200 h-screen fixed left-0 top-0 overflow-y-auto z-50 flex flex-col"
        x-data="{
            sidebarCollapsed: localStorage.getItem('sidebarCollapsed') === 'true',
            attendanceOpen: {{ in_array($currentRoute, $attendanceRoutes) ? 'true' : 'false' }},
            payrollOpen: {{ in_array($currentRoute, $payrollRoutes) ? 'true' : 'false' }},
            requestsOpen: {{ in_array($currentRoute, $requestRoutes) ? 'true' : 'false' }}
        }"
        x-init="
            $watch('sidebarCollapsed', value => {
                localStorage.setItem('sidebarCollapsed', value);
                $dispatch('sidebar-toggle', { collapsed: value });
            })
        "
        @sidebar-toggle.window="sidebarCollapsed = $event.detail.collapsed"
        :class="sidebarCollapsed ? 'w-20' : 'w-64'"
        style="transition: width 0.35s cubic-bezier(0.4, 0, 0.2, 1); box-shadow: 2px 0 20px rgba(0,0,0,0.06);">

        {{-- Logo --}}
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

        {{-- User Profile --}}
        <div class="px-4 py-4 border-b border-gray-100 profile-card" :class="sidebarCollapsed ? 'flex justify-center' : 'flex items-center space-x-3'">
            <div class="w-11 h-11 rounded-full flex items-center justify-center text-white font-bold flex-shrink-0 text-sm avatar-ring"
                 style="background: linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%);">
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
                <p class="font-semibold text-gray-800 text-sm leading-tight">{{ $sidebarName }}</p>
                <p class="text-xs mt-0.5 font-semibold" style="color:#3b82f6;">{{ $sidebarRole }}</p>
            </div>
        </div>

        {{-- Navigation --}}
        <nav class="p-3 space-y-0.5 flex-1 overflow-y-auto">
            <p x-show="!sidebarCollapsed" class="text-xs text-gray-400 font-semibold px-3 py-2 uppercase tracking-widest">Main Menu</p>

            {{-- Dashboard --}}
            <a href="{{ route('finance_officer.dashboard') }}"
                class="nav-item flex items-center space-x-3 px-3 py-2.5 rounded-lg group
                    {{ $currentRoute === 'finance_officer.dashboard' ? 'text-white' : 'text-gray-600 hover:bg-gray-50' }}"
                style="{{ $currentRoute === 'finance_officer.dashboard' ? 'background:#3b82f6;' : '' }}">
                <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"/>
                </svg>
                <span x-show="!sidebarCollapsed" class="text-sm font-medium whitespace-nowrap">Dashboard</span>
            </a>

            {{-- Employee Profile --}}
            <a href="{{ route('finance_officer.profile') }}"
                class="nav-item flex items-center space-x-3 px-3 py-2.5 rounded-lg
                    {{ $currentRoute === 'finance_officer.profile' ? 'text-white' : 'text-gray-600 hover:bg-gray-50' }}"
                style="{{ $currentRoute === 'finance_officer.profile' ? 'background:#3b82f6;' : '' }}">
                <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                </svg>
                <span x-show="!sidebarCollapsed" class="text-sm font-medium whitespace-nowrap">Employee Profile</span>
            </a>

            {{-- Time & Attendance --}}
            <div>
                <button @click="attendanceOpen = !attendanceOpen"
                    class="nav-item w-full flex items-center justify-between px-3 py-2.5 rounded-lg
                        {{ in_array($currentRoute, $attendanceRoutes) ? 'text-blue-600 bg-blue-50' : 'text-gray-600 hover:bg-gray-50' }}">
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
                    <a href="{{ route('finance_officer.attendance.reports') }}"
                       class="submenu-item flex items-center px-3 py-2 text-sm rounded-lg
                           {{ $currentRoute === 'finance_officer.attendance.reports' ? 'font-semibold bg-blue-50' : 'text-gray-500 hover:text-gray-800 hover:bg-gray-50' }}"
                       style="{{ $currentRoute === 'finance_officer.attendance.reports' ? 'color:#3b82f6;' : '' }}">
                        @if($currentRoute === 'finance_officer.attendance.reports')
                            <span class="w-1.5 h-1.5 rounded-full mr-2 flex-shrink-0" style="background:#3b82f6;"></span>
                        @endif
                        My Attendance
                    </a>
                    <a href="{{ route('finance_officer.leave.management') }}"
                       class="submenu-item block px-3 py-2 text-sm rounded-lg
                           {{ $currentRoute === 'finance_officer.leave.management' ? 'text-blue-600 font-semibold bg-blue-50' : 'text-gray-500 hover:text-gray-800 hover:bg-gray-50' }}">
                        Leave Management
                    </a>
                </div>
            </div>

            {{-- Payroll --}}
            <div>
                <button @click="payrollOpen = !payrollOpen"
                    class="nav-item w-full flex items-center justify-between px-3 py-2.5 rounded-lg
                        {{ in_array($currentRoute, $payrollRoutes) ? 'text-blue-600 bg-blue-50' : 'text-gray-600 hover:bg-gray-50' }}">
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
                    <a href="{{ route('finance_officer.payroll') }}" class="submenu-item block px-3 py-2 text-sm rounded-lg {{ $currentRoute === 'finance_officer.payroll' ? 'text-blue-600 font-semibold bg-blue-50' : 'text-gray-500 hover:text-gray-800 hover:bg-gray-50' }}">Payroll</a>
                    <a href="{{ route('finance_officer.payslips') }}" class="submenu-item block px-3 py-2 text-sm rounded-lg {{ $currentRoute === 'finance_officer.payslips' ? 'text-blue-600 font-semibold bg-blue-50' : 'text-gray-500 hover:text-gray-800 hover:bg-gray-50' }}">Payslips</a>
                    <a href="{{ route('finance_officer.govpay') }}" class="submenu-item block px-3 py-2 text-sm rounded-lg {{ $currentRoute === 'finance_officer.govpay' ? 'text-blue-600 font-semibold bg-blue-50' : 'text-gray-500 hover:text-gray-800 hover:bg-gray-50' }}">Govt. Contributions</a>
                </div>
            </div>

            {{-- Requests & Approval --}}
            <div>
                <button @click="requestsOpen = !requestsOpen"
                    class="nav-item w-full flex items-center justify-between px-3 py-2.5 rounded-lg
                        {{ in_array($currentRoute, $requestRoutes) ? 'text-blue-600 bg-blue-50' : 'text-gray-600 hover:bg-gray-50' }}">
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
                    <a href="{{ route('finance_officer.requests.pending') }}" class="submenu-item block px-3 py-2 text-sm rounded-lg {{ $currentRoute === 'finance_officer.requests.pending' ? 'text-blue-600 font-semibold bg-blue-50' : 'text-gray-500 hover:text-gray-800 hover:bg-gray-50' }}">Pending Requests</a>
                    <a href="{{ route('finance_officer.requests.approved') }}" class="submenu-item block px-3 py-2 text-sm rounded-lg {{ $currentRoute === 'finance_officer.requests.approved' ? 'text-blue-600 font-semibold bg-blue-50' : 'text-gray-500 hover:text-gray-800 hover:bg-gray-50' }}">Approved Logs</a>
                </div>
            </div>

            {{-- Others --}}
            <div class="pt-3 mt-2 border-t border-gray-100">
                <p x-show="!sidebarCollapsed" class="text-xs text-gray-400 font-semibold px-3 py-2 uppercase tracking-widest">Others</p>
                <a href="#" class="nav-item flex items-center space-x-3 px-3 py-2.5 rounded-lg text-gray-600 hover:bg-gray-50 group">
                    <svg class="w-5 h-5 flex-shrink-0 settings-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                    </svg>
                    <span x-show="!sidebarCollapsed" class="text-sm font-medium whitespace-nowrap">Settings</span>
                </a>
                <a href="{{ route('logout') }}" onclick="event.preventDefault(); document.getElementById('fo-logout-form').submit();"
                    class="nav-item logout-btn flex items-center space-x-3 px-3 py-2.5 rounded-lg text-gray-600 hover:bg-red-50 hover:text-red-500">
                    <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
                    </svg>
                    <span x-show="!sidebarCollapsed" class="text-sm font-medium whitespace-nowrap">Logout</span>
                </a>
                <form id="fo-logout-form" action="{{ route('logout') }}" method="POST" class="hidden">@csrf</form>
            </div>
        </nav>

        {{-- Collapse Button --}}
        <button @click="sidebarCollapsed = !sidebarCollapsed"
            class="m-3 w-8 h-8 bg-gray-100 rounded-lg flex items-center justify-center text-gray-500 hover:bg-gray-200 self-end collapse-btn">
            <svg class="w-4 h-4 chevron-icon" :class="{'rotate-180': sidebarCollapsed}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 19l-7-7 7-7m8 14l-7-7 7-7"/>
            </svg>
        </button>
    </aside>


    {{-- ══════════════════════════════════════
         MAIN CONTENT
    ══════════════════════════════════════ --}}
    <div class="main-content min-h-screen"
         :style="'margin-left: ' + (sidebarCollapsed ? '80px' : '256px')">

        {{-- Header --}}
        <header class="bg-gradient-to-br from-blue-500 to-blue-700 sticky top-0 z-10 shadow-lg mt-4 mx-4 rounded-2xl overflow-hidden">
            <div class="flex items-center justify-between px-8 py-4">
                <h1 class="text-white font-bold text-xl">Payroll</h1>
                <div class="relative">
                    <button class="w-9 h-9 rounded-full flex items-center justify-center transition-all hover:bg-white/20">
                        <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6 6 0 00-5-5.917V5a1 1 0 10-2 0v.083A6 6 0 006 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>
                        </svg>
                        <span class="absolute -top-1 -right-1 w-4 h-4 bg-red-400 rounded-full text-white text-xs flex items-center justify-center font-bold">4</span>
                    </button>
                </div>
            </div>
        </header>

        {{-- Flash --}}
        @if(session('success'))
        <div class="mx-8 mt-4 p-3 bg-green-50 border border-green-200 rounded-lg text-green-700 text-sm flex items-center gap-2">
            <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
            <span>{{ session('success') }}</span>
        </div>
        @endif


        {{-- ══ PAGE: LIST ══ --}}
        <div x-show="page === 'list'" x-cloak>

            {{-- Tab Bar --}}
            <div class="bg-white px-8 pt-4 anim-2">
                <div class="tab-bar">
                    <button class="tab-btn" :class="activeTab==='payroll-period' && 'active'" @click="activeTab='payroll-period'">Payroll Period</button>
                    <button class="tab-btn" :class="activeTab==='salary-structure' && 'active'" @click="activeTab='salary-structure'">Salary Structure</button>
                    <button class="tab-btn" :class="activeTab==='benefits' && 'active'" @click="activeTab='benefits'">Benefits</button>
                    <button class="tab-btn" :class="activeTab==='contributions' && 'active'" @click="activeTab='contributions'">Contributions</button>
                </div>
            </div>

            {{-- TAB 1: PAYROLL PERIOD --}}
            <div x-show="activeTab==='payroll-period'" x-cloak class="p-8 tab-content">
                <div class="flex gap-4 mb-7">
                    <div class="summary-card"><div class="label">Gross Payroll</div><div class="value">₱ {{ number_format($grossPayroll) }}</div><div class="sub">{{ $latestPeriod->name ?? 'February 2026 Period 2' }}</div></div>
                    <div class="summary-card"><div class="label">Net Pay</div><div class="value">₱ {{ number_format($netPay) }}</div><div class="sub">{{ $latestPeriod->name ?? 'February 2026 Period 2' }}</div></div>
                    <div class="summary-card"><div class="label">Total Deductions</div><div class="value">₱ {{ number_format($totalDeductions) }}</div><div class="sub">{{ $latestPeriod->name ?? 'February 2026 Period 2' }}</div></div>
                    <div class="summary-card">
                        <div class="label">Days to Cutoff</div>
                        <div class="value">{{ $daysToCutoff }}</div>
                        @if($latestPeriod)
                        <div class="sub">Cutoff: {{ \Carbon\Carbon::parse($latestPeriod->start_date)->format('m/d/Y') }} – {{ \Carbon\Carbon::parse($latestPeriod->end_date)->format('m/d/Y') }}</div>
                        @else
                        <div class="sub">Cutoff Period: 02/16/2026 – 02/28/2026</div>
                        @endif
                    </div>
                </div>

                <div class="flex items-center justify-between gap-3 mb-4">
                    <div class="search-wrap flex-1 max-w-xs">
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-4.35-4.35M17 11A6 6 0 105 11a6 6 0 0012 0z"/></svg>
                        <input type="text" placeholder="Search" class="ctrl w-full" x-model="periodSearch">
                    </div>
                    <div class="flex items-center gap-3">
                        <select class="ctrl" x-model="periodStatusFilter"><option value="">Status</option><option value="Pending">Pending</option><option value="Completed">Completed</option><option value="Submitted">Submitted</option></select>
                        <select class="ctrl" x-model="periodYearFilter">
                            @for($y = now()->year; $y >= now()->year - 3; $y--)
                            <option value="{{ $y }}">{{ $y }}</option>
                            @endfor
                        </select>
                        <button class="btn-primary" @click="showAddPeriodModal=true">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                            Add Payroll Period
                        </button>
                    </div>
                </div>

                <div class="bg-white rounded-xl border border-gray-100 shadow-sm overflow-hidden">
                    <table class="data-table">
                        <thead><tr><th>Period Name</th><th>Start Date</th><th>End Date</th><th>Status</th><th></th></tr></thead>
                        <tbody>
                            @forelse($periods as $period)
                            <tr>
                                <td class="font-medium text-gray-700">{{ $period->name }}</td>
                                <td class="text-gray-500">{{ \Carbon\Carbon::parse($period->start_date)->format('m/d/Y') }}</td>
                                <td class="text-gray-500">{{ \Carbon\Carbon::parse($period->end_date)->format('m/d/Y') }}</td>
                                <td><span class="px-3 py-1 rounded-full text-xs font-medium @if($period->status==='Pending') badge-pending @elseif($period->status==='Completed') badge-completed @else badge-submitted @endif">{{ $period->status }}</span></td>
                                <td class="text-right"><button class="btn-view" @click="openPeriodView({{ $period->id }}, '{{ addslashes($period->name) }}', '{{ $period->start_date }}', '{{ $period->end_date }}', '{{ $period->status }}')">View</button></td>
                            </tr>
                            @empty
                            @foreach([['February Payroll Period 2','02/16/2026','02/28/2026','Pending',1],['February Payroll Period 1','02/01/2026','02/15/2026','Completed',2],['January Payroll Period 2','01/16/2026','01/31/2026','Completed',3],['January Payroll Period 1','01/01/2026','01/15/2026','Completed',4]] as [$pn,$ps,$pe,$pst,$pid])
                            <tr>
                                <td class="font-medium text-gray-700">{{ $pn }}</td>
                                <td class="text-gray-500">{{ $ps }}</td>
                                <td class="text-gray-500">{{ $pe }}</td>
                                <td><span class="px-3 py-1 rounded-full text-xs font-medium {{ $pst==='Pending'?'badge-pending':'badge-completed' }}">{{ $pst }}</span></td>
                                <td class="text-right"><button class="btn-view" @click="openPeriodView({{ $pid }}, '{{ $pn }}', '2026-02-16', '2026-02-28', '{{ $pst }}')">View</button></td>
                            </tr>
                            @endforeach
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            {{-- TAB 2: SALARY STRUCTURE --}}
            <div x-show="activeTab==='salary-structure'" x-cloak class="p-8 tab-content">
                <div class="flex items-center justify-between gap-3 mb-4">
                    <div class="search-wrap flex-1 max-w-xs">
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-4.35-4.35M17 11A6 6 0 105 11a6 6 0 0012 0z"/></svg>
                        <input type="text" placeholder="Search" class="ctrl w-full">
                    </div>
                    <div class="flex items-center gap-3">
                        <select class="ctrl"><option value="">All Status</option><option>Active</option><option>Inactive</option></select>
                        <select class="ctrl"><option value="">All Types</option><option>Addition</option><option>Deduction</option></select>
                        <button class="btn-primary" @click="showAddItemModal=true"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>Add Payroll Item</button>
                    </div>
                </div>
                <div class="bg-white rounded-xl border border-gray-100 shadow-sm overflow-hidden">
                    <table class="data-table">
                        <thead><tr><th>Payroll Item</th><th>Multiplier</th><th>Type</th><th>Basis</th><th>Status</th><th></th></tr></thead>
                        <tbody>
                            @forelse($payrollItems as $item)
                            <tr>
                                <td class="font-medium text-gray-700">{{ $item->name }}</td>
                                <td><span class="multiplier-badge">x{{ $item->multiplier }}</span></td>
                                <td><span class="px-3 py-1 rounded-full text-xs font-medium {{ strtolower($item->type)==='addition'?'badge-addition':'badge-deduction' }}">{{ $item->type }}</span></td>
                                <td class="text-gray-500">{{ $item->basis }}</td>
                                <td><span class="px-3 py-1 rounded-full text-xs font-medium {{ $item->status==='Active'?'badge-active':'badge-inactive' }}">{{ $item->status }}</span></td>
                                <td class="text-right"><button class="btn-view" @click="openEditItem({{ $item->id }}, '{{ addslashes($item->name) }}', {{ $item->multiplier }}, '{{ $item->type }}', '{{ $item->basis }}')">Edit</button></td>
                            </tr>
                            @empty
                            <tr><td colspan="6" class="text-center py-12 text-gray-400 text-sm">No payroll items found.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            {{-- TAB 3: BENEFITS --}}
            <div x-show="activeTab==='benefits'" x-cloak class="p-8 tab-content">
                <div class="flex items-center justify-between gap-3 mb-4">
                    <div class="search-wrap flex-1 max-w-xs">
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-4.35-4.35M17 11A6 6 0 105 11a6 6 0 0012 0z"/></svg>
                        <input type="text" placeholder="Search" class="ctrl w-full">
                    </div>
                    <div class="flex items-center gap-3">
                        <select class="ctrl"><option value="">Status</option><option>Active</option><option>Inactive</option></select>
                        <button class="btn-primary" @click="showAddBenefitModal=true"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>Add Benefit</button>
                    </div>
                </div>
                <div class="bg-white rounded-xl border border-gray-100 shadow-sm overflow-hidden">
                    <table class="data-table">
                        <thead><tr><th>Benefit Item</th><th>Type</th><th>Amount</th><th>Taxable</th><th>Frequency</th><th>Eligibility</th><th>Status</th><th></th></tr></thead>
                        <tbody>
                            @forelse($benefits as $benefit)
                            <tr>
                                <td class="font-medium text-gray-700">{{ $benefit->name }}</td>
                                <td><span class="px-3 py-1 rounded-full text-xs font-medium badge-allowance">{{ $benefit->type }}</span></td>
                                <td class="text-gray-600">₱{{ number_format($benefit->amount, 2) }}</td>
                                <td><span class="px-3 py-1 rounded-full text-xs font-medium {{ strtolower($benefit->tax)==='non-taxable'?'badge-nontaxable':'badge-taxable' }}">{{ $benefit->tax }}</span></td>
                                <td class="text-gray-500">{{ $benefit->frequency }}</td>
                                <td class="text-gray-500">{{ $benefit->eligibility }}</td>
                                <td><span class="px-3 py-1 rounded-full text-xs font-medium {{ $benefit->status==='Active'?'badge-active':'badge-inactive' }}">{{ $benefit->status }}</span></td>
                                <td class="text-right"><button class="btn-view" @click="openEditBenefit({{ $benefit->id }}, '{{ addslashes($benefit->name) }}', '{{ $benefit->type }}', {{ $benefit->amount }}, '{{ $benefit->tax }}', '{{ $benefit->frequency }}', '{{ addslashes($benefit->eligibility) }}')">Edit</button></td>
                            </tr>
                            @empty
                            <tr><td colspan="8" class="text-center py-12 text-gray-400 text-sm">No benefits found.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            {{-- TAB 4: CONTRIBUTIONS --}}
            <div x-show="activeTab==='contributions'" x-cloak class="p-8 tab-content">
                <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-12 text-center">
                    <svg class="w-12 h-12 text-gray-300 mx-auto mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 14l6-6m-5.5.5h.01m4.99 5h.01M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16l3.5-2 3.5 2 3.5-2 3.5 2z"/></svg>
                    <p class="text-gray-400 text-sm">Government contributions will appear here.</p>
                </div>
            </div>

        </div>{{-- /page list --}}


        {{-- ══ PAGE: VIEW PERIOD ══ --}}
        <div x-show="page === 'view'" x-cloak>

            {{-- Breadcrumb + Title --}}
            <div class="px-8 pt-5 pb-2 anim-1">
                <div class="flex items-center gap-3 mb-5">
                    <button class="btn-back" @click="closePeriodView()">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                        Back to Payroll Period
                    </button>
                    <span class="text-gray-300">|</span>
                    <nav class="flex items-center gap-1.5 text-sm text-gray-400">
                        <span>Payroll</span>
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                        <span>Payroll Period</span>
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                        <span class="font-semibold text-gray-700" x-text="viewPeriod.name"></span>
                    </nav>
                </div>

                <div class="flex items-center justify-between mb-6">
                    <h2 class="text-2xl font-bold text-gray-800" x-text="viewPeriod.name"></h2>
                    {{-- Release Payroll button (matches screenshot) --}}
                    <button class="btn-primary px-6 py-2.5 text-sm font-semibold" @click="showSubmitConfirm=true"
                        style="background:#3b82f6; border-radius:8px;">
                        Release Payroll
                    </button>
                </div>
            </div>

            {{-- Status + Summary cards --}}
            <div class="px-8 mb-6 anim-2">
                <div class="grid grid-cols-2 gap-4">
                    {{-- Processing Status --}}
                    <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-5">
                        <h3 class="text-sm font-semibold text-gray-700 mb-4">Payroll Processing Status</h3>
                        <div class="space-y-4">
                            <div>
                                <div class="flex justify-between mb-1">
                                    <span class="text-sm text-gray-500">Payslips Reviewed &amp; Submitted</span>
                                    <span class="text-sm font-medium text-gray-700"><span x-text="pvSubmittedCount"></span>/<span x-text="pvTotalCount"></span></span>
                                </div>
                                <div class="progress-track"><div class="progress-fill" :style="'width:' + (pvTotalCount > 0 ? (pvSubmittedCount/pvTotalCount*100) : 0) + '%'"></div></div>
                            </div>
                            <div>
                                <div class="flex justify-between mb-1">
                                    <span class="text-sm text-gray-500">Payroll Submitted for Approval</span>
                                    <span class="text-sm font-medium text-gray-700" x-text="(viewPeriod.status === 'Submitted' || viewPeriod.status === 'Completed') ? '1/1' : '0/1'"></span>
                                </div>
                                <div class="progress-track"><div class="progress-fill" :style="'width:' + ((viewPeriod.status === 'Submitted' || viewPeriod.status === 'Completed') ? 100 : 0) + '%'"></div></div>
                            </div>
                            <div>
                                <div class="flex justify-between mb-1">
                                    <span class="text-sm text-gray-500">Finance Approval</span>
                                    <span class="text-sm font-medium text-gray-700" x-text="viewPeriod.status === 'Completed' ? '1/1' : '0/1'"></span>
                                </div>
                                <div class="progress-track"><div class="progress-fill" :style="'width:' + (viewPeriod.status === 'Completed' ? 100 : 0) + '%'"></div></div>
                            </div>
                            <div>
                                <div class="flex justify-between mb-1">
                                    <span class="text-sm text-gray-500">Payroll Release</span>
                                    <span class="text-sm font-medium text-gray-700">0/1</span>
                                </div>
                                <div class="progress-track"><div class="progress-fill" style="width:0%"></div></div>
                            </div>
                        </div>
                    </div>

                    {{-- Payroll Summary --}}
                    <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-5">
                        <h3 class="text-sm font-semibold text-gray-700 mb-4">Payroll Summary</h3>
                        <div class="space-y-3">
                            <div class="flex justify-between items-center">
                                <span class="text-sm text-gray-500">Gross Payroll</span>
                                <span class="text-sm font-medium text-gray-700">₱ <span x-text="fmt(pvGrossPayroll)"></span></span>
                            </div>
                            <div class="flex justify-between items-center">
                                <span class="text-sm text-gray-500">Total Deductions</span>
                                <span class="text-sm font-medium text-gray-700">₱ <span x-text="fmt(pvTotalDeductions)"></span></span>
                            </div>
                            <div class="border-t border-gray-100 pt-3 flex justify-between items-center">
                                <span class="text-sm font-semibold text-gray-700">Net Payroll</span>
                                <span class="text-base font-bold text-gray-900">₱ <span x-text="fmt(pvNetPayroll)"></span></span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Employee Payroll table + Payslip side panel --}}
            <div class="px-8 pb-10 anim-3">
                <div class="flex gap-4 items-start">

                    {{-- Table --}}
                    <div class="flex-1 min-w-0">
                        <h3 class="text-base font-bold text-gray-800 mb-3">Employee Payroll</h3>
                        <div class="bg-white rounded-xl border border-gray-100 shadow-sm overflow-hidden">
                            <table class="pv-table">
                                <thead>
                                    <tr>
                                        <th>Employee</th>
                                        <th>Gross Pay</th>
                                        <th>Net Pay</th>
                                        <th>Status</th>
                                        <th></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <template x-for="ps in pvPayslips" :key="ps.id">
                                        <tr :class="pvSelectedId === ps.id && 'row-active'" @click="pvSelectPayslip(ps)">
                                            <td class="font-medium text-gray-700" x-text="ps.employeeName"></td>
                                            <td class="text-gray-600">₱<span x-text="fmt(ps.grossPay)"></span></td>
                                            <td class="text-gray-600">₱<span x-text="fmt(ps.netPay)"></span></td>
                                            <td>
                                                <span class="px-3 py-1 rounded-full text-xs font-medium"
                                                    :class="ps.status === 'Submitted' ? 'badge-submitted' : 'badge-pending'"
                                                    x-text="ps.status"></span>
                                            </td>
                                            <td class="text-right">
                                                <button class="btn-view" @click.stop="pvSelectPayslip(ps)">View</button>
                                            </td>
                                        </tr>
                                    </template>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    {{-- Payslip side panel (matches screenshot exactly) --}}
                    <div class="w-96 flex-shrink-0" x-show="pvSelectedId !== null" x-cloak>
                        <div class="bg-white rounded-xl border border-gray-100 shadow-sm overflow-hidden slide-in-right">

                            {{-- Blue header --}}
                            <div class="payslip-header">
                                <div class="text-lg font-bold mb-0.5">Medisource</div>
                                <div class="text-xs text-blue-100 mb-5" x-text="pvPeriodSubtitle"></div>
                                <div class="grid grid-cols-3 gap-2">
                                    <div>
                                        <div class="info-label">Employee</div>
                                        <div class="info-value" x-text="pvActive.employeeName || '—'"></div>
                                    </div>
                                    <div>
                                        <div class="info-label">Job Title</div>
                                        <div class="info-value" x-text="pvActive.jobTitle || '—'"></div>
                                    </div>
                                    <div>
                                        <div class="info-label">Department</div>
                                        <div class="info-value" x-text="pvActive.department || '—'"></div>
                                    </div>
                                </div>
                            </div>

                            {{-- Body --}}
                            <div class="px-6 pt-4 pb-2">

                                {{-- Earnings --}}
                                <div class="payslip-section-title">Earnings</div>
                                <div class="payslip-line">
                                    <span x-text="'Basic Pay (Salary Grade ' + (pvActive.salaryGrade || 1) + ')'"></span>
                                    <span x-text="'₱ ' + fmt(pvActive.basicPay || 0)"></span>
                                </div>
                                <div class="payslip-line">
                                    <span x-text="'OT Pay (' + (pvActive.otHours || 0) + ' hours OT)'"></span>
                                    <span x-text="'₱ ' + fmt(pvActive.otPay || 0)"></span>
                                </div>
                                <div class="payslip-line">
                                    <span x-text="'Benefits (' + (pvActive.benefitLabel || 'Allowance') + ')'"></span>
                                    <span x-text="'₱ ' + fmt(pvActive.benefits || 0)"></span>
                                </div>
                                <div class="payslip-line bold">
                                    <span>Gross Pay</span>
                                    <span x-text="'₱ ' + fmt(pvActive.grossPay || 0)"></span>
                                </div>

                                {{-- Deductions --}}
                                <div class="payslip-section-title">Deductions</div>
                                <div class="payslip-line">
                                    <span>SSS</span>
                                    <span class="text-red-500" x-text="'-₱ ' + fmt(pvActive.sss || 0)"></span>
                                </div>
                                <div class="payslip-line">
                                    <span>PhilHealth</span>
                                    <span class="text-red-500" x-text="'-₱ ' + fmt(pvActive.philhealth || 0)"></span>
                                </div>
                                <div class="payslip-line">
                                    <span>Pag-IBIG</span>
                                    <span class="text-red-500" x-text="'-₱ ' + fmt(pvActive.pagibig || 0)"></span>
                                </div>
                                <div class="payslip-line">
                                    <span>Withholding Tax</span>
                                    <span class="text-red-500" x-text="'-₱ ' + fmt(pvActive.withholdingTax || 0)"></span>
                                </div>
                                <div class="payslip-line bold">
                                    <span>Total Deductions</span>
                                    <span class="text-red-500" x-text="'-₱ ' + fmt(pvActive.totalDeductions || 0)"></span>
                                </div>

                                {{-- Calculation --}}
                                <div class="payslip-section-title">Calculation</div>
                                <div class="payslip-line">
                                    <span>Earnings</span>
                                    <span x-text="'₱ ' + fmt(pvActive.grossPay || 0)"></span>
                                </div>
                                <div class="payslip-line">
                                    <span>Deductions</span>
                                    <span class="text-red-500" x-text="'-₱ ' + fmt(pvActive.totalDeductions || 0)"></span>
                                </div>
                                <div class="payslip-line bold">
                                    <span>Net Pay</span>
                                    <span x-text="'₱ ' + fmt(pvActive.netPay || 0)"></span>
                                </div>
                            </div>

                            {{-- Close / Export buttons (matches screenshot) --}}
                            <div class="flex gap-3 px-6 py-4 border-t border-gray-100">
                                <button class="flex-1 py-2.5 text-sm font-semibold text-blue-600 border border-blue-200 rounded-lg hover:bg-blue-50 transition"
                                    @click="pvSelectedId = null; pvActive = {};">
                                    Close
                                </button>
                                <button class="flex-1 py-2.5 text-sm font-semibold text-white rounded-lg transition"
                                    style="background:#3b82f6;"
                                    @click="pvExportPayslip()">
                                    Export
                                </button>
                            </div>
                        </div>
                    </div>

                </div>
            </div>

        </div>{{-- /page view --}}

    </div>{{-- /main-content --}}


    {{-- ══ MODALS ══ --}}

    {{-- Add Payroll Period --}}
    <div x-show="showAddPeriodModal" x-cloak class="fixed inset-0 z-50 flex items-center justify-center modal-overlay" @click.self="showAddPeriodModal=false">
        <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md mx-4 p-7" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100" x-transition:leave="transition ease-in duration-150" x-transition:leave-start="opacity-100 scale-100" x-transition:leave-end="opacity-0 scale-95">
            <div class="flex items-center justify-between mb-6"><h2 class="text-lg font-bold text-gray-800">Add Payroll Period</h2><button @click="showAddPeriodModal=false" class="text-gray-400 hover:text-gray-600"><svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg></button></div>
            <form action="{{ route('payroll_officer.payroll.period.store') }}" method="POST" class="space-y-4">
                @csrf
                <div><label class="block text-sm font-medium text-gray-700 mb-1.5">Payroll Period Name</label><input type="text" name="name" required placeholder="Enter payroll period name" class="ctrl w-full"></div>
                <div class="grid grid-cols-2 gap-4"><div><label class="block text-sm font-medium text-gray-700 mb-1.5">Start Date</label><input type="date" name="start_date" required class="ctrl w-full"></div><div><label class="block text-sm font-medium text-gray-700 mb-1.5">End Date</label><input type="date" name="end_date" required class="ctrl w-full"></div></div>
                <div><label class="block text-sm font-medium text-gray-700 mb-1.5">Payout Date</label><input type="date" name="payout_date" required class="ctrl w-full"></div>
                <div class="flex justify-end gap-3 pt-2"><button type="button" @click="showAddPeriodModal=false" class="btn-outline">Cancel</button><button type="submit" class="btn-primary">Submit</button></div>
            </form>
        </div>
    </div>

    {{-- Add Payroll Item --}}
    <div x-show="showAddItemModal" x-cloak class="fixed inset-0 z-50 flex items-center justify-center modal-overlay" @click.self="showAddItemModal=false">
        <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md mx-4 p-7" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100">
            <div class="flex items-center justify-between mb-6"><h2 class="text-lg font-bold text-gray-800">Add Payroll Item</h2><button @click="showAddItemModal=false" class="text-gray-400 hover:text-gray-600"><svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg></button></div>
            <form action="{{ route('payroll_officer.payroll.item.store') }}" method="POST" class="space-y-4">
                @csrf
                <div><label class="block text-sm font-medium text-gray-700 mb-1.5">Payroll Item</label><input type="text" name="name" required placeholder="Enter payroll item name" class="ctrl w-full"></div>
                <div class="grid grid-cols-2 gap-4"><div><label class="block text-sm font-medium text-gray-700 mb-1.5">Multiplier</label><input type="number" name="multiplier" step="0.01" required placeholder="e.g. 1.25" class="ctrl w-full"></div><div><label class="block text-sm font-medium text-gray-700 mb-1.5">Type</label><select name="type" required class="ctrl w-full"><option value="">Choose type</option><option value="Addition">Addition</option><option value="Deduction">Deduction</option></select></div></div>
                <div><label class="block text-sm font-medium text-gray-700 mb-1.5">Basis</label><input type="text" name="basis" required placeholder="Enter basis" class="ctrl w-full"></div>
                <div class="flex justify-end gap-3 pt-2"><button type="button" @click="showAddItemModal=false" class="btn-outline">Cancel</button><button type="submit" class="btn-primary">Submit</button></div>
            </form>
        </div>
    </div>

    {{-- Edit Payroll Item --}}
    <div x-show="showEditItemModal" x-cloak class="fixed inset-0 z-50 flex items-center justify-center modal-overlay" @click.self="showEditItemModal=false">
        <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md mx-4 p-7" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100">
            <div class="flex items-center justify-between mb-6"><h2 class="text-lg font-bold text-gray-800">Edit Payroll Item</h2><button @click="showEditItemModal=false" class="text-gray-400 hover:text-gray-600"><svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg></button></div>
            <form :action="`/payroll_officer/payroll/item/${editItem.id}/update`" method="POST" class="space-y-4">
                @csrf @method('PUT')
                <div><label class="block text-sm font-medium text-gray-700 mb-1.5">Payroll Item</label><input type="text" name="name" x-model="editItem.name" required class="ctrl w-full"></div>
                <div class="grid grid-cols-2 gap-4"><div><label class="block text-sm font-medium text-gray-700 mb-1.5">Multiplier</label><input type="number" name="multiplier" step="0.01" x-model="editItem.multiplier" required class="ctrl w-full"></div><div><label class="block text-sm font-medium text-gray-700 mb-1.5">Type</label><select name="type" x-model="editItem.type" required class="ctrl w-full"><option value="Addition">Addition</option><option value="Deduction">Deduction</option></select></div></div>
                <div><label class="block text-sm font-medium text-gray-700 mb-1.5">Basis</label><input type="text" name="basis" x-model="editItem.basis" required class="ctrl w-full"></div>
                <div class="flex items-center justify-between pt-2">
                    <button type="button" @click="deactivateItem()" class="text-blue-500 border border-blue-200 px-4 py-2 rounded-lg text-sm font-medium hover:bg-blue-50 transition">Deactivate</button>
                    <div class="flex gap-3"><button type="button" @click="showEditItemModal=false" class="btn-outline">Cancel</button><button type="submit" class="btn-primary">Save</button></div>
                </div>
            </form>
        </div>
    </div>

    {{-- Add Benefit --}}
    <div x-show="showAddBenefitModal" x-cloak class="fixed inset-0 z-50 flex items-center justify-center modal-overlay" @click.self="showAddBenefitModal=false">
        <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md mx-4 p-7" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100">
            <div class="flex items-center justify-between mb-6"><h2 class="text-lg font-bold text-gray-800">Add Benefit</h2><button @click="showAddBenefitModal=false" class="text-gray-400 hover:text-gray-600"><svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg></button></div>
            <form action="{{ route('payroll_officer.payroll.benefit.store') }}" method="POST" class="space-y-4">
                @csrf
                <div><label class="block text-sm font-medium text-gray-700 mb-1.5">Benefit Name</label><input type="text" name="name" required placeholder="Enter benefit name" class="ctrl w-full"></div>
                <div class="grid grid-cols-2 gap-4"><div><label class="block text-sm font-medium text-gray-700 mb-1.5">Type</label><select name="type" required class="ctrl w-full"><option value="">Choose type</option><option value="Allowance">Allowance</option><option value="Bonus">Bonus</option><option value="Incentive">Incentive</option></select></div><div><label class="block text-sm font-medium text-gray-700 mb-1.5">Amount</label><input type="number" name="amount" step="0.01" required placeholder="₱0.00" class="ctrl w-full"></div></div>
                <div class="grid grid-cols-2 gap-4"><div><label class="block text-sm font-medium text-gray-700 mb-1.5">Tax</label><select name="tax" required class="ctrl w-full"><option value="Non-taxable">Non-taxable</option><option value="Taxable">Taxable</option></select></div><div><label class="block text-sm font-medium text-gray-700 mb-1.5">Frequency</label><input type="text" name="frequency" required placeholder="e.g. Monthly" class="ctrl w-full"></div></div>
                <div><label class="block text-sm font-medium text-gray-700 mb-1.5">Eligibility</label><input type="text" name="eligibility" required placeholder="e.g. All regular employees" class="ctrl w-full"></div>
                <div class="flex justify-end gap-3 pt-2"><button type="button" @click="showAddBenefitModal=false" class="btn-outline">Cancel</button><button type="submit" class="btn-primary">Submit</button></div>
            </form>
        </div>
    </div>

    {{-- Edit Benefit --}}
    <div x-show="showEditBenefitModal" x-cloak class="fixed inset-0 z-50 flex items-center justify-center modal-overlay" @click.self="showEditBenefitModal=false">
        <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md mx-4 p-7" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100">
            <div class="flex items-center justify-between mb-6"><h2 class="text-lg font-bold text-gray-800">Edit Benefit</h2><button @click="showEditBenefitModal=false" class="text-gray-400 hover:text-gray-600"><svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg></button></div>
            <form :action="`/payroll_officer/payroll/benefit/${editBenefit.id}/update`" method="POST" class="space-y-4">
                @csrf @method('PUT')
                <div><label class="block text-sm font-medium text-gray-700 mb-1.5">Benefit Name</label><input type="text" name="name" x-model="editBenefit.name" required class="ctrl w-full"></div>
                <div class="grid grid-cols-2 gap-4"><div><label class="block text-sm font-medium text-gray-700 mb-1.5">Type</label><select name="type" x-model="editBenefit.type" required class="ctrl w-full"><option value="Allowance">Allowance</option><option value="Bonus">Bonus</option><option value="Incentive">Incentive</option></select></div><div><label class="block text-sm font-medium text-gray-700 mb-1.5">Amount</label><input type="number" name="amount" step="0.01" x-model="editBenefit.amount" required class="ctrl w-full"></div></div>
                <div class="grid grid-cols-2 gap-4"><div><label class="block text-sm font-medium text-gray-700 mb-1.5">Tax</label><select name="tax" x-model="editBenefit.tax" required class="ctrl w-full"><option value="Non-taxable">Non-taxable</option><option value="Taxable">Taxable</option></select></div><div><label class="block text-sm font-medium text-gray-700 mb-1.5">Frequency</label><input type="text" name="frequency" x-model="editBenefit.frequency" required class="ctrl w-full"></div></div>
                <div><label class="block text-sm font-medium text-gray-700 mb-1.5">Eligibility</label><input type="text" name="eligibility" x-model="editBenefit.eligibility" required class="ctrl w-full"></div>
                <div class="flex items-center justify-between pt-2">
                    <button type="button" @click="deactivateBenefit()" class="text-blue-500 border border-blue-200 px-4 py-2 rounded-lg text-sm font-medium hover:bg-blue-50 transition">Deactivate</button>
                    <div class="flex gap-3"><button type="button" @click="showEditBenefitModal=false" class="btn-outline">Cancel</button><button type="submit" class="btn-primary">Submit</button></div>
                </div>
            </form>
        </div>
    </div>

    {{-- Submit Period Confirm --}}
    <div x-show="showSubmitConfirm" x-cloak class="fixed inset-0 z-50 flex items-center justify-center modal-overlay" @click.self="showSubmitConfirm=false">
        <div class="bg-white rounded-2xl shadow-2xl w-full max-w-sm mx-4 p-8 text-center" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100">
            <div class="w-12 h-12 bg-blue-50 rounded-full flex items-center justify-center mx-auto mb-4">
                <svg class="w-6 h-6 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            </div>
            <p class="text-gray-700 font-semibold text-base mb-2">Submit for Approval?</p>
            <p class="text-gray-400 text-sm mb-6">This will submit the payroll period for finance approval.</p>
            <div class="flex justify-center gap-3">
                <button @click="showSubmitConfirm=false" class="btn-outline px-8">Cancel</button>
                <form :action="`/payroll_officer/payroll/period/${viewPeriod.id}/submit`" method="POST">
                    @csrf
                    <button type="submit" class="btn-primary px-8">Confirm</button>
                </form>
            </div>
        </div>
    </div>


    <script>
    function payrollApp() {
        return {
            sidebarCollapsed: localStorage.getItem('sidebarCollapsed') === 'true',
            page: 'list',
            activeTab: '{{ request("tab", "payroll-period") }}',
            periodSearch: '',
            periodStatusFilter: '',
            periodYearFilter: '{{ $year }}',
            showAddPeriodModal: false,
            showAddItemModal: false,
            showEditItemModal: false,
            showAddBenefitModal: false,
            showEditBenefitModal: false,
            showSubmitConfirm: false,
            editItem: { id:null, name:'', multiplier:'', type:'', basis:'' },
            editBenefit: { id:null, name:'', type:'', amount:'', tax:'', frequency:'', eligibility:'' },
            viewPeriod: { id:null, name:'', startDate:'', endDate:'', status:'Pending' },
            pvPayslips: [],
            pvSelectedId: null,
            pvActive: {},
            pvGrossPayroll: 0,
            pvTotalDeductions: 0,
            pvNetPayroll: 0,
            pvSubmittedCount: 0,
            pvTotalCount: 0,
            pvPeriodSubtitle: '',

            samplePayslips: {!! json_encode($resolvedPayslips) !!},

            init() {
                window.addEventListener('sidebar-toggle', e => {
                    this.sidebarCollapsed = e.detail.collapsed;
                });
                window.addEventListener('storage', e => {
                    if (e.key === 'sidebarCollapsed') this.sidebarCollapsed = e.newValue === 'true';
                });
            },

            openPeriodView(id, name, startDate, endDate, status) {
                this.viewPeriod = { id, name, startDate, endDate, status };
                this.pvPayslips = this.samplePayslips;
                this.pvTotalCount = this.pvPayslips.length;
                this.pvSubmittedCount = this.pvPayslips.filter(p => p.status === 'Submitted').length;
                this.pvGrossPayroll = this.pvPayslips.reduce((s, p) => s + p.grossPay, 0);
                this.pvTotalDeductions = this.pvPayslips.reduce((s, p) => s + p.totalDeductions, 0);
                this.pvNetPayroll = this.pvPayslips.reduce((s, p) => s + p.netPay, 0);
                const start = new Date(startDate);
                const end   = new Date(endDate);
                this.pvPeriodSubtitle = `${start.toLocaleString('en-US',{month:'long'})} ${start.getFullYear()} · ${name} · ${start.toLocaleString('en-US',{month:'short'})} ${start.getDate()}–${end.getDate()}`;
                if (this.pvPayslips.length > 0) this.pvSelectPayslip(this.pvPayslips[0]);
                this.page = 'view';
                window.scrollTo({ top: 0, behavior: 'smooth' });
            },

            closePeriodView() {
                this.page = 'list';
                this.pvSelectedId = null;
                this.pvActive = {};
                window.scrollTo({ top: 0, behavior: 'smooth' });
            },

            pvSelectPayslip(ps) {
                this.pvSelectedId = ps.id;
                this.pvActive = { ...ps };
            },

            pvEditPayslip() { alert('Edit payslip for: ' + this.pvActive.employeeName); },

            pvExportPayslip() {
                alert('Export payslip for: ' + this.pvActive.employeeName);
            },

            pvSubmitPayslip() {
                if (!confirm('Submit payslip for ' + this.pvActive.employeeName + '?')) return;
                fetch(`/payroll_officer/payroll/payslip/${this.pvActive.id}/submit`, {
                    method: 'POST',
                    headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content }
                }).then(r => r.json()).then(() => {
                    const ps = this.pvPayslips.find(p => p.id === this.pvActive.id);
                    if (ps) ps.status = 'Submitted';
                    this.pvActive.status = 'Submitted';
                    this.pvSubmittedCount = this.pvPayslips.filter(p => p.status === 'Submitted').length;
                });
            },

            openEditItem(id, name, multiplier, type, basis) { this.editItem = { id, name, multiplier, type, basis }; this.showEditItemModal = true; },
            openEditBenefit(id, name, type, amount, tax, frequency, eligibility) { this.editBenefit = { id, name, type, amount, tax, frequency, eligibility }; this.showEditBenefitModal = true; },

            deactivateItem() {
                if (!confirm('Deactivate this payroll item?')) return;
                fetch(`/payroll_officer/payroll/item/${this.editItem.id}/deactivate`, { method:'POST', headers:{'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content} }).then(() => location.reload());
            },
            deactivateBenefit() {
                if (!confirm('Deactivate this benefit?')) return;
                fetch(`/payroll_officer/payroll/benefit/${this.editBenefit.id}/deactivate`, { method:'POST', headers:{'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content} }).then(() => location.reload());
            },

            fmt(n) { return Number(n).toLocaleString('en-PH', { minimumFractionDigits:2, maximumFractionDigits:2 }); },
        }
    }
    </script>

</body>
</html>
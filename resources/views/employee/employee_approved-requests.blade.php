@php
    $currentRoute     = request()->route()->getName();
    $attendanceRoutes = ['employee.attendance.reports', 'employee.attendance.shift', 'employee.attendance.leave', 'employee.leave.management'];
    $payrollRoutes    = ['employee.payroll', 'employee.payslips', 'employee.contributions'];
    $requestRoutes    = ['employee.requests.pending', 'employee.requests.approved'];

    $sidebarUser     = auth()->user();
    $sidebarEmployee = $sidebarUser ? \App\Models\Employee::with('jobTitle')->where('user_id', $sidebarUser->id)->first() : null;
    $sidebarInitials = $sidebarEmployee
        ? strtoupper(substr($sidebarEmployee->fname, 0, 1) . substr($sidebarEmployee->lname, 0, 1))
        : ($sidebarUser ? strtoupper(substr($sidebarUser->email, 0, 2)) : 'U');
    $sidebarName = $sidebarEmployee
        ? trim($sidebarEmployee->fname . ' ' . $sidebarEmployee->lname)
        : ($sidebarUser?->email ?? 'User');
    $sidebarRole = $sidebarEmployee?->jobTitle?->title ?? ($sidebarUser?->role ?? 'Admin');
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8"/>
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <title>Approved Logs — MEDISOURCE</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet"/>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <style>
        * { font-family: 'DM Sans', sans-serif; box-sizing: border-box; }
        body { background: #f3f4f6; margin: 0; }
        .nav-item { transition: all 0.2s ease; }
        .nav-item:hover { transform: translateX(2px); }
        .submenu-item { transition: all 0.18s ease; }
        .submenu-item:hover { transform: translateX(3px); }
        .chevron-icon { transition: transform 0.3s cubic-bezier(0.4,0,0.2,1); }
        .settings-icon { transition: transform 0.5s ease; }
        .nav-item:hover .settings-icon { transform: rotate(60deg); }
        .avatar-ring { box-shadow: 0 0 0 3px rgba(59,130,246,0.2); }
        @keyframes pulseDot { 0%,100%{opacity:1;transform:scale(1)} 50%{opacity:.5;transform:scale(1.3)} }
        .stat-card { background:#fff; border-radius:14px; padding:24px 28px; border:1px solid #e5e7eb; }
        .tbl { width:100%; border-collapse:collapse; }
        .tbl th { font-size:12px; color:#6b7280; font-weight:600; padding:11px 14px; border-bottom:1.5px solid #e5e7eb; text-align:left; white-space:nowrap; }
        .tbl td { font-size:13px; color:#111827; padding:13px 14px; border-bottom:1px solid #f3f4f6; vertical-align:middle; }
        .tbl tr:last-child td { border-bottom:none; }
        .tbl tr:hover td { background:#f9fafb; }
        .badge { display:inline-block; padding:3px 11px; border-radius:20px; font-size:11.5px; font-weight:600; }
        .badge-leave    { background:#fef3c7; color:#d97706; }
        .badge-ot       { background:#fce7f3; color:#db2777; }
        .badge-shift    { background:#ede9fe; color:#7c3aed; }
        .badge-approved { background:#dcfce7; color:#16a34a; }
        .badge-rejected { background:#fee2e2; color:#dc2626; }
        .approver-chip  { display:inline-block; background:#dbeafe; color:#1d4ed8; border-radius:20px; padding:2px 10px; font-size:11.5px; font-weight:600; margin:1px; }
        .btn-view { background:#fff; border:1.5px solid #e5e7eb; border-radius:8px; padding:5px 16px; font-size:12px; font-weight:600; color:#374151; cursor:pointer; font-family:inherit; }
        .btn-view:hover { border-color:#3b82f6; color:#3b82f6; }
        .fsel { background:#fff; border:1.5px solid #e5e7eb; border-radius:8px; padding:8px 32px 8px 12px; font-size:13px; font-family:inherit; color:#374151; appearance:none; cursor:pointer; outline:none; }
        .fsel:focus { border-color:#3b82f6; }
        .search-input { background:#fff; border:1.5px solid #e5e7eb; border-radius:8px; padding:8px 12px 8px 36px; font-size:13px; font-family:inherit; color:#374151; outline:none; }
        .search-input:focus { border-color:#3b82f6; }
        .modal-overlay { position:fixed; inset:0; background:rgba(0,0,0,0.35); z-index:200; display:flex; align-items:center; justify-content:center; padding:20px; }
        .modal-box { background:#fff; border-radius:18px; width:100%; max-width:540px; padding:32px; position:relative; box-shadow:0 24px 64px rgba(0,0,0,0.18); }
        .modal-field { background:#f3f4f6; border-radius:8px; padding:10px 14px; font-size:13px; color:#374151; }
        .modal-label { font-size:13px; font-weight:600; color:#111827; margin-bottom:6px; }
        .trail-item { display:flex; gap:14px; align-items:flex-start; padding:14px 16px; background:#f9fafb; border-radius:10px; border-left:3px solid #3b82f6; margin-bottom:8px; }
        .trail-check { width:30px; height:30px; border-radius:50%; background:#3b82f6; display:flex; align-items:center; justify-content:center; flex-shrink:0; }
        .anim-fade { animation: fadeSlideDown 0.4s ease both; }
        @keyframes fadeSlideDown { from { opacity:0; transform:translateY(-12px); } to { opacity:1; transform:translateY(0); } }
    </style>
</head>
<body>
<div class="flex min-h-screen" x-data="approvedLogs()">

    {{-- ═══════════ SIDEBAR ═══════════ --}}
    <aside
    class="bg-white border-r border-gray-200 h-screen fixed left-0 top-0 overflow-y-auto z-50 flex flex-col"
    x-data="{
        sidebarCollapsed: localStorage.getItem('sidebarCollapsed') === 'true',
        attendanceOpen: {{ in_array($currentRoute, $attendanceRoutes) ? 'true' : 'false' }},
        payrollOpen: {{ in_array($currentRoute, $payrollRoutes) ? 'true' : 'false' }},
        requestsOpen: {{ in_array($currentRoute, $requestRoutes) ? 'true' : 'false' }}
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

    <!-- Navigation -->
    <nav class="p-3 space-y-0.5 flex-1 overflow-y-auto">
        <p x-show="!sidebarCollapsed"
           class="text-xs text-gray-400 font-semibold px-3 py-2 uppercase tracking-widest">Main Menu</p>

        <!-- Dashboard -->
        <a href="{{ route('employee.dashboard') }}"
            class="nav-item flex items-center space-x-3 px-3 py-2.5 rounded-lg group
                {{ $currentRoute === 'employee.dashboard' ? 'text-white' : 'text-gray-600 hover:bg-gray-50' }}"
            style="{{ $currentRoute === 'employee.dashboard' ? 'background:#3b82f6;' : '' }}">
            <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"/>
            </svg>
            <span x-show="!sidebarCollapsed" class="text-sm font-medium whitespace-nowrap">Dashboard</span>
        </a>

        <!-- Employee Profile -->
        <a href="{{ route('employee.profile') }}"
            class="nav-item flex items-center space-x-3 px-3 py-2.5 rounded-lg
                {{ $currentRoute === 'employee.profile' ? 'text-white' : 'text-gray-600 hover:bg-gray-50' }}"
            style="{{ $currentRoute === 'employee.profile' ? 'background:#3b82f6;' : '' }}">
            <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
            </svg>
            <span x-show="!sidebarCollapsed" class="text-sm font-medium whitespace-nowrap">Employee Profile</span>
        </a>

        <!-- Time & Attendance -->
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

                <a href="{{ route('employee.attendance.reports') }}"
                   class="submenu-item flex items-center px-3 py-2 text-sm rounded-lg
                       {{ $currentRoute === 'employee.attendance.reports'
                           ? 'font-semibold bg-blue-50'
                           : 'text-gray-500 hover:text-gray-800 hover:bg-gray-50' }}"
                   style="{{ $currentRoute === 'employee.attendance.reports' ? 'color:#3b82f6;' : '' }}">
                    @if($currentRoute === 'employee.attendance.reports')
                        <span class="w-1.5 h-1.5 rounded-full mr-2 flex-shrink-0" style="background:#3b82f6;"></span>
                    @endif
                    My Attendance
                </a>

                <a href="#" class="submenu-item block px-3 py-2 text-sm text-gray-500 hover:text-gray-800 hover:bg-gray-50 rounded-lg">Shift Scheduling</a>

                <a href="{{ route('employee.leave.management') }}"
                   class="submenu-item block px-3 py-2 text-sm rounded-lg
                       {{ $currentRoute === 'employee.leave.management' ? 'text-blue-600 font-semibold bg-blue-50' : 'text-gray-500 hover:text-gray-800 hover:bg-gray-50' }}">
                    Leave Management
                </a>
            </div>
        </div>

        <!-- Payroll -->
        <div>
            <button @click="payrollOpen = !payrollOpen"
                class="nav-item w-full flex items-center justify-between px-3 py-2.5 rounded-lg text-gray-600 hover:bg-gray-50">
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
                <a href="#" class="submenu-item block px-3 py-2 text-sm text-gray-500 hover:text-gray-800 hover:bg-gray-50 rounded-lg">Payroll</a>
                <a href="#" class="submenu-item block px-3 py-2 text-sm text-gray-500 hover:text-gray-800 hover:bg-gray-50 rounded-lg">Payslips</a>
                <a href="#" class="submenu-item block px-3 py-2 text-sm text-gray-500 hover:text-gray-800 hover:bg-gray-50 rounded-lg">Govt. Contributions</a>
            </div>
        </div>

        <!-- Requests & Approval -->
        <div>
            <button @click="requestsOpen = !requestsOpen"
                class="nav-item w-full flex items-center justify-between px-3 py-2.5 rounded-lg text-gray-600 hover:bg-gray-50">
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
                <a href="{{ route('employee.requests.pending') }}" class="submenu-item block px-3 py-2 text-sm rounded-lg {{ $currentRoute === 'employee.requests.pending' ? 'text-blue-600 font-semibold bg-blue-50' : 'text-gray-500 hover:text-gray-800 hover:bg-gray-50' }}">Pending Requests</a>
                <a href="{{ route('employee.requests.approved') }}" class="submenu-item block px-3 py-2 text-sm rounded-lg {{ $currentRoute === 'employee.requests.approved' ? 'text-blue-600 font-semibold bg-blue-50' : 'text-gray-500 hover:text-gray-800 hover:bg-gray-50' }}">Approved Logs</a>
            </div>
        </div>

        <!-- Others -->
        <div class="pt-3 mt-2 border-t border-gray-100">
            <p x-show="!sidebarCollapsed"
               class="text-xs text-gray-400 font-semibold px-3 py-2 uppercase tracking-widest">Others</p>

            <a href="#" class="nav-item flex items-center space-x-3 px-3 py-2.5 rounded-lg text-gray-600 hover:bg-gray-50 group">
                <svg class="w-5 h-5 flex-shrink-0 settings-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                </svg>
                <span x-show="!sidebarCollapsed" class="text-sm font-medium whitespace-nowrap">Settings</span>
            </a>

            <a href="{{ route('logout') }}" onclick="event.preventDefault(); document.getElementById('employee-logout-form').submit();"
                class="nav-item logout-btn flex items-center space-x-3 px-3 py-2.5 rounded-lg text-gray-600 hover:bg-red-50 hover:text-red-500">
                <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
                </svg>
                <span x-show="!sidebarCollapsed" class="text-sm font-medium whitespace-nowrap">Logout</span>
            </a>
            <form id="employee-logout-form" action="{{ route('logout') }}" method="POST" class="hidden">@csrf</form>
        </div>
    </nav>

    <!-- Collapse Button -->
    <button @click="sidebarCollapsed = !sidebarCollapsed"
        class="m-3 w-8 h-8 bg-gray-100 rounded-lg flex items-center justify-center text-gray-500 hover:bg-gray-200 self-end collapse-btn">
        <svg class="w-4 h-4 chevron-icon" :class="{'rotate-180': sidebarCollapsed}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 19l-7-7 7-7m8 14l-7-7 7-7"/>
        </svg>
    </button>
</aside>

    {{-- ═══════════ MAIN ═══════════ --}}
    <div class="flex-1 flex flex-col" style="margin-left:256px;">

        {{-- ✅ UPDATED Top bar --}}
        <header class="anim-fade bg-gradient-to-br from-blue-500 to-blue-700 sticky top-0 z-10 shadow-lg mt-4 mx-4 rounded-2xl overflow-hidden">
            <div class="flex items-center justify-between px-8 py-4">
                <h1 class="text-white font-bold text-xl">Approved Logs</h1>
                <button class="relative w-9 h-9 rounded-full flex items-center justify-center transition-all hover:bg-white/20">
                    <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>
                    </svg>
                    <span class="absolute -top-1 -right-1 w-4 h-4 rounded-full flex items-center justify-center text-white font-bold" style="background:#ef4444;font-size:9px;">4</span>
                </button>
            </div>
        </header>

        <div class="p-8 space-y-6">

            {{-- Stat Cards --}}
            <div class="grid grid-cols-2 gap-5" style="max-width:680px;">
                <div class="stat-card">
                    <div class="text-sm text-gray-500 font-medium">Approved</div>
                    <div class="text-4xl font-extrabold text-gray-900 mt-2">{{ $approvedCount ?? 3 }}</div>
                    <div class="text-xs text-gray-400 mt-4">{{ now()->format('F Y') }}</div>
                </div>
                <div class="stat-card">
                    <div class="text-sm text-gray-500 font-medium">Rejected</div>
                    <div class="text-4xl font-extrabold text-gray-900 mt-2">{{ $rejectedCount ?? 1 }}</div>
                    <div class="text-xs text-gray-400 mt-4">With written reason</div>
                </div>
            </div>

            {{-- Table Card --}}
            <div class="bg-white rounded-2xl border border-gray-200" style="box-shadow:0 2px 12px rgba(0,0,0,0.06);">

                {{-- Toolbar --}}
                <div class="flex items-center gap-3 px-6 py-4 border-b border-gray-100 flex-wrap">
                    <div class="relative flex-1" style="min-width:200px;max-width:320px;">
                        <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                        <input type="text" placeholder="Search" class="search-input w-full"/>
                    </div>
                    <select class="fsel">
                        <option>Status</option>
                        <option>Approved</option>
                        <option>Rejected</option>
                    </select>
                    <select class="fsel">
                        <option>All Types</option>
                        <option>Leave Request</option>
                        <option>Overtime</option>
                        <option>Shift Arrangement</option>
                    </select>
                    <select class="fsel">
                        <option>All Departments</option>
                        @foreach($departments ?? [] as $d)
                            <option>{{ $d->name }}</option>
                        @endforeach
                    </select>
                </div>

                {{-- Table --}}
                <div class="overflow-x-auto">
                    <table class="tbl">
                        <thead>
                            <tr>
                                <th>Ref #</th>
                                <th>Employee</th>
                                <th>Type</th>
                                <th>Date Filed</th>
                                <th>Duration</th>
                                <th>Days</th>
                                <th>Approvers</th>
                                <th>Processed On</th>
                                <th>Status</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td class="font-semibold text-gray-700">REQ-0002</td>
                                <td>Juan Dela Cruz</td>
                                <td><span class="badge badge-leave">Leave Request</span></td>
                                <td>03/06/2026</td>
                                <td class="text-gray-500 text-xs">03-10-2026 &ndash;<br>03-12-2026</td>
                                <td class="font-semibold">3</td>
                                <td><span class="approver-chip">John Dee</span><br><span class="approver-chip">Jan Dy</span></td>
                                <td>03/10/2026</td>
                                <td><span class="badge badge-approved">Approved</span></td>
                                <td><button class="btn-view" onclick="openViewModal('REQ-002','IT Department','Juan Dela Cruz','March 6, 2026','Leave Request','Vacation Leave (VL)','03/10/2026','03/12/2026','Family trip','sup')">View</button></td>
                            </tr>
                            <tr>
                                <td class="font-semibold text-gray-700">REQ-0003</td>
                                <td>Lisa Valdez</td>
                                <td><span class="badge badge-leave">Leave Request</span></td>
                                <td>03/10/2026</td>
                                <td class="text-gray-500 text-xs">03-10-2026 &ndash;<br>03-12-2026</td>
                                <td class="font-semibold">3</td>
                                <td><span class="approver-chip">John Dee</span><br><span class="approver-chip">Jan Dy</span></td>
                                <td>03/10/2026</td>
                                <td><span class="badge badge-rejected">Rejected</span></td>
                                <td><button class="btn-view" onclick="openViewModal('REQ-003','IT Department','Lisa Valdez','March 10, 2026','Leave Request','Vacation Leave (VL)','03/10/2026','03/12/2026','Personal reasons','rej')">View</button></td>
                            </tr>
                            @forelse($requests ?? [] as $req)
                            <tr>
                                <td class="font-semibold text-gray-700">{{ $req->ref_no ?? '' }}</td>
                                <td>{{ optional($req->employee)->fname }} {{ optional($req->employee)->lname }}</td>
                                <td><span class="badge badge-leave">Leave Request</span></td>
                                <td>{{ optional($req->created_at)->format('m/d/Y') }}</td>
                                <td class="text-gray-500 text-xs">{{ optional($req->start_date)->format('m-d-Y') }} &ndash;<br>{{ optional($req->end_date)->format('m-d-Y') }}</td>
                                <td class="font-semibold">{{ $req->total_days }}</td>
                                <td><span class="approver-chip">{{ optional($req->approver)->fname }}</span></td>
                                <td>{{ optional($req->approved_at)->format('m/d/Y') }}</td>
                                <td>
                                    @if($req->status === 'approved')
                                        <span class="badge badge-approved">Approved</span>
                                    @elseif($req->status === 'rejected')
                                        <span class="badge badge-rejected">Rejected</span>
                                    @endif
                                </td>
                                <td><button class="btn-view">View</button></td>
                            </tr>
                            @empty
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- ═══════════ VIEW MODAL ═══════════ --}}
<div id="viewModal" style="display:none;" class="modal-overlay" onclick="if(event.target===this)closeViewModal()">
    <div class="modal-box">
        <button onclick="closeViewModal()" style="position:absolute;top:16px;right:16px;width:34px;height:34px;border-radius:50%;border:2px solid #d1d5db;background:none;cursor:pointer;display:flex;align-items:center;justify-content:center;color:#9ca3af;">
            <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"/></svg>
        </button>

        <div class="mb-5">
            <div id="mRef" style="font-size:26px;font-weight:900;color:#111827;line-height:1.1;"></div>
            <div id="mSub" style="font-size:13px;color:#374151;margin-top:4px;"></div>
            <div id="mFiled" style="font-size:12px;color:#9ca3af;margin-top:2px;"></div>
        </div>

        <div class="grid grid-cols-2 gap-4 mb-4">
            <div>
                <div class="modal-label">Request Type</div>
                <div id="mReqType" class="modal-field"></div>
            </div>
            <div>
                <div class="modal-label">Leave Type</div>
                <div id="mLeaveType" class="modal-field"></div>
            </div>
        </div>
        <div class="grid grid-cols-2 gap-4 mb-4">
            <div>
                <div class="modal-label">From</div>
                <div id="mFrom" class="modal-field"></div>
            </div>
            <div>
                <div class="modal-label">To</div>
                <div id="mTo" class="modal-field"></div>
            </div>
        </div>
        <div class="mb-5">
            <div class="modal-label">Reason/Remarks</div>
            <div id="mReason" class="modal-field"></div>
        </div>

        <div class="mb-6">
            <div class="modal-label mb-3">Approval Trail</div>
            <div id="mTrail"></div>
        </div>

        <div class="flex justify-end">
            <button onclick="closeViewModal()" style="background:#3b82f6;color:#fff;border:none;border-radius:10px;padding:10px 30px;font-size:14px;font-weight:700;font-family:inherit;cursor:pointer;">Confirm</button>
        </div>
    </div>
</div>

<script>
function openViewModal(ref, dept, name, filed, reqType, leaveType, from, to, reason, trailType) {
    document.getElementById('mRef').textContent      = ref;
    document.getElementById('mSub').textContent      = dept + ' - ' + name;
    document.getElementById('mFiled').textContent    = 'Filed on ' + filed;
    document.getElementById('mReqType').textContent  = reqType;
    document.getElementById('mLeaveType').textContent = leaveType;
    document.getElementById('mFrom').textContent     = from;
    document.getElementById('mTo').textContent       = to;
    document.getElementById('mReason').textContent   = reason;

    var trails = [];
    if (trailType === 'sup') {
        trails = [
            { label: 'Supervisor Approved', person: 'IT Supervisor - Jan Dy',   date: 'Approved on March 6, 2026' },
            { label: 'HR Manager Approved', person: 'HR Manager - John Dee',    date: 'Approved on March 6, 2026' }
        ];
    } else {
        trails = [
            { label: 'Supervisor Approved', person: 'IT Supervisor - Jan Dy',   date: 'Approved on March 10, 2026' },
            { label: 'HR Manager Rejected', person: 'HR Manager - John Dee',    date: 'Rejected on March 10, 2026' }
        ];
    }

    var html = '';
    trails.forEach(function(t) {
        html += '<div class="trail-item">';
        html += '<div class="trail-check"><svg width="14" height="14" fill="none" stroke="#fff" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg></div>';
        html += '<div>';
        html += '<div style="font-size:13px;font-weight:700;color:#111827;">' + t.label + '</div>';
        html += '<div style="font-size:12px;color:#6b7280;margin-top:2px;">' + t.person + '</div>';
        html += '<div style="font-size:11px;color:#9ca3af;">' + t.date + '</div>';
        html += '</div></div>';
    });
    document.getElementById('mTrail').innerHTML = html;
    document.getElementById('viewModal').style.display = 'flex';
}
function closeViewModal() {
    document.getElementById('viewModal').style.display = 'none';
}

function approvedLogs() {
    return {};
}
</script>
</body>
</html>
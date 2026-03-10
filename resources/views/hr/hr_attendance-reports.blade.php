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

    $attendanceRoutes = ['hr.attendance.reports', 'hr.attendance.shift', 'hr.attendance.leave'];
    $payrollRoutes    = ['hr.payroll', 'hr.payslips', 'hr.contributions'];
    $requestRoutes    = ['hr.requests.pending', 'hr.requests.approved'];
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Attendance Reports – MEDISOURCE HR</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <style>
        * { font-family: 'Inter', sans-serif; }
        .nav-item { transition: all 0.2s cubic-bezier(0.4,0,0.2,1); }
        .nav-item:hover { transform: translateX(2px); }
        .submenu-item { transition: all 0.18s ease; }
        .submenu-item:hover { transform: translateX(3px); }
        .chevron-icon { transition: transform 0.3s cubic-bezier(0.4,0,0.2,1); }
        .collapse-btn { transition: all 0.2s ease; }
        .collapse-btn:hover { transform: scale(1.08); }
        .settings-icon { transition: transform 0.5s ease; }
        .nav-item:hover .settings-icon { transform: rotate(60deg); }
        .avatar-ring { box-shadow: 0 0 0 3px rgba(59,130,246,0.2); transition: box-shadow 0.3s ease; }
        .avatar-ring:hover { box-shadow: 0 0 0 5px rgba(59,130,246,0.35); }
        @keyframes fadeUp { from{opacity:0;transform:translateY(14px)}to{opacity:1;transform:translateY(0)} }
        @keyframes fadeIn { from{opacity:0}to{opacity:1} }
        @keyframes rowSlide { from{opacity:0;transform:translateX(-8px)}to{opacity:1;transform:translateX(0)} }
        @keyframes pulseDot { 0%,100%{opacity:1}50%{opacity:0.3} }
        .anim-up   { animation: fadeUp 0.45s cubic-bezier(0.22,1,0.36,1) both; }
        .anim-fade { animation: fadeIn 0.35s ease both; }
        .stat-card { animation: fadeUp 0.45s cubic-bezier(0.22,1,0.36,1) both; transition: transform 0.2s ease, box-shadow 0.2s ease; border-radius: 18px; }
        .stat-card:hover { transform:translateY(-3px); box-shadow:0 12px 32px rgba(0,0,0,0.10); }
        .table-row { animation: rowSlide 0.3s cubic-bezier(0.22,1,0.36,1) both; transition: background 0.12s ease; }
        .table-row:hover { background:#f8faff; }
        .pulse-dot { animation: pulseDot 2s ease-in-out infinite; }
        .export-btn { transition: all 0.2s ease; background: linear-gradient(135deg,#3b82f6,#1d4ed8); }
        .export-btn:hover { transform:translateY(-1px); box-shadow:0 6px 18px rgba(59,130,246,0.40); }
        .clock-btn { transition: all 0.2s cubic-bezier(0.4,0,0.2,1); border-radius: 12px; }
        .clock-btn:hover:not(:disabled) { transform:translateY(-1px); box-shadow:0 6px 20px rgba(59,130,246,0.38); }
        .clock-btn:disabled { opacity:0.6; cursor:not-allowed; }
        .setup-btn { transition: all 0.2s ease; }
        ::-webkit-scrollbar{width:4px} ::-webkit-scrollbar-track{background:#f1f5f9} ::-webkit-scrollbar-thumb{background:#cbd5e1;border-radius:99px}
    </style>
</head>
<body class="bg-gray-100" x-data="attendancePage()" x-init="init()">

{{-- ═══════════ HR SIDEBAR ═══════════ --}}
<aside id="sidebar"
    class="bg-white border-r border-gray-200 h-screen fixed left-0 top-0 overflow-y-auto z-50 flex flex-col"
    x-data="{
        sidebarCollapsed: localStorage.getItem('sidebarCollapsed') === 'true',
        employeesOpen: false,
        attendanceOpen: {{ in_array($currentRoute, $attendanceRoutes) ? 'true' : 'false' }},
        payrollOpen: {{ in_array($currentRoute, $payrollRoutes) ? 'true' : 'false' }},
        requestsOpen: {{ in_array($currentRoute, $requestRoutes) ? 'true' : 'false' }}
    }"
    x-init="
        $watch('sidebarCollapsed', v => {
            localStorage.setItem('sidebarCollapsed', v);
            document.getElementById('main-content').style.marginLeft = v ? '5rem' : '16rem';
        });
        document.getElementById('main-content').style.marginLeft = sidebarCollapsed ? '5rem' : '16rem';
    "
    :class="sidebarCollapsed ? 'w-20' : 'w-64'"
    style="transition:width 0.35s cubic-bezier(0.4,0,0.2,1); box-shadow:2px 0 20px rgba(0,0,0,0.06);">

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
    <div class="px-4 py-4 border-b border-gray-100" :class="sidebarCollapsed ? 'flex justify-center' : 'flex items-center space-x-3'">
        <div class="w-11 h-11 rounded-full flex items-center justify-center text-white font-bold flex-shrink-0 text-sm avatar-ring"
             style="background:linear-gradient(135deg,#3b82f6,#1d4ed8);">{{ $sidebarInitials }}</div>
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
        <a href="{{ route('hr.dashboard') }}"
           class="nav-item flex items-center space-x-3 px-3 py-2.5 rounded-lg
               {{ $currentRoute === 'hr.dashboard' ? 'text-white' : 'text-gray-600 hover:bg-gray-50' }}"
           style="{{ $currentRoute === 'hr.dashboard' ? 'background:#3b82f6;' : '' }}">
            <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"/>
            </svg>
            <span x-show="!sidebarCollapsed" class="text-sm font-medium whitespace-nowrap">Dashboard</span>
        </a>

        {{-- Employees --}}
        <div>
            <button @click="employeesOpen = !employeesOpen"
                class="nav-item w-full flex items-center justify-between px-3 py-2.5 rounded-lg text-gray-600 hover:bg-gray-50">
                <div class="flex items-center space-x-3">
                    <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/>
                    </svg>
                    <span x-show="!sidebarCollapsed" class="text-sm font-medium whitespace-nowrap">Employees</span>
                </div>
                <svg x-show="!sidebarCollapsed" class="w-4 h-4 chevron-icon" :class="{'rotate-180': employeesOpen}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                </svg>
            </button>
            <div x-show="employeesOpen && !sidebarCollapsed"
                 x-transition:enter="transition ease-out duration-250" x-transition:enter-start="opacity-0 -translate-y-3 scale-y-95" x-transition:enter-end="opacity-100 translate-y-0 scale-y-100"
                 x-transition:leave="transition ease-in duration-200" x-transition:leave-start="opacity-100 translate-y-0 scale-y-100" x-transition:leave-end="opacity-0 -translate-y-3 scale-y-95"
                 class="ml-8 mt-1 space-y-0.5 origin-top">
                <a href="#" class="submenu-item block px-3 py-2 text-sm text-gray-500 hover:text-gray-800 hover:bg-gray-50 rounded-lg">Employee Directory</a>
                <a href="#" class="submenu-item block px-3 py-2 text-sm text-gray-500 hover:text-gray-800 hover:bg-gray-50 rounded-lg">Employee Profile</a>
            </div>
        </div>

        {{-- Time & Attendance --}}
        <div>
            <button @click="attendanceOpen = !attendanceOpen"
                class="nav-item w-full flex items-center justify-between px-3 py-2.5 rounded-lg
                    {{ in_array($currentRoute, $attendanceRoutes) ? 'text-blue-600' : 'text-gray-600 hover:bg-gray-50' }}">
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
                 x-transition:enter="transition ease-out duration-250" x-transition:enter-start="opacity-0 -translate-y-3 scale-y-95" x-transition:enter-end="opacity-100 translate-y-0 scale-y-100"
                 x-transition:leave="transition ease-in duration-200" x-transition:leave-start="opacity-100 translate-y-0 scale-y-100" x-transition:leave-end="opacity-0 -translate-y-3 scale-y-95"
                 class="ml-8 mt-1 space-y-0.5 origin-top">
                {{-- ✅ Active link --}}
                <a href="{{ route('hr.attendance.reports') }}"
                   class="submenu-item flex items-center px-3 py-2 text-sm rounded-lg font-semibold"
                   style="color:#3b82f6;">
                    <span class="w-2 h-2 rounded-full mr-2.5 flex-shrink-0 pulse-dot" style="background:#3b82f6;"></span>
                    Attendance Reports
                </a>
                <a href="#" class="submenu-item block px-3 py-2 text-sm text-gray-500 hover:text-gray-800 hover:bg-gray-50 rounded-lg">Shift Scheduling</a>
                <a href="#" class="submenu-item block px-3 py-2 text-sm text-gray-500 hover:text-gray-800 hover:bg-gray-50 rounded-lg">Leave Management</a>
            </div>
        </div>

        {{-- Payroll --}}
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
                 x-transition:enter="transition ease-out duration-250" x-transition:enter-start="opacity-0 -translate-y-3 scale-y-95" x-transition:enter-end="opacity-100 translate-y-0 scale-y-100"
                 x-transition:leave="transition ease-in duration-200" x-transition:leave-start="opacity-100 translate-y-0 scale-y-100" x-transition:leave-end="opacity-0 -translate-y-3 scale-y-95"
                 class="ml-8 mt-1 space-y-0.5 origin-top">
                <a href="#" class="submenu-item block px-3 py-2 text-sm text-gray-500 hover:text-gray-800 hover:bg-gray-50 rounded-lg">Payroll</a>
                <a href="#" class="submenu-item block px-3 py-2 text-sm text-gray-500 hover:text-gray-800 hover:bg-gray-50 rounded-lg">Payslips</a>
                <a href="#" class="submenu-item block px-3 py-2 text-sm text-gray-500 hover:text-gray-800 hover:bg-gray-50 rounded-lg">Govt. Contributions</a>
            </div>
        </div>

        {{-- Requests & Approval --}}
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
                 x-transition:enter="transition ease-out duration-250" x-transition:enter-start="opacity-0 -translate-y-3 scale-y-95" x-transition:enter-end="opacity-100 translate-y-0 scale-y-100"
                 x-transition:leave="transition ease-in duration-200" x-transition:leave-start="opacity-100 translate-y-0 scale-y-100" x-transition:leave-end="opacity-0 -translate-y-3 scale-y-95"
                 class="ml-8 mt-1 space-y-0.5 origin-top">
                <a href="#" class="submenu-item block px-3 py-2 text-sm text-gray-500 hover:text-gray-800 hover:bg-gray-50 rounded-lg">Pending Requests</a>
                <a href="#" class="submenu-item block px-3 py-2 text-sm text-gray-500 hover:text-gray-800 hover:bg-gray-50 rounded-lg">Approved Logs</a>
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
            <a href="{{ route('logout') }}" onclick="event.preventDefault(); document.getElementById('hr-logout-form').submit();"
               class="nav-item flex items-center space-x-3 px-3 py-2.5 rounded-lg text-gray-600 hover:bg-red-50 hover:text-red-500">
                <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
                </svg>
                <span x-show="!sidebarCollapsed" class="text-sm font-medium whitespace-nowrap">Logout</span>
            </a>
            <form id="hr-logout-form" action="{{ route('logout') }}" method="POST" class="hidden">@csrf</form>
        </div>
    </nav>

    <button @click="sidebarCollapsed = !sidebarCollapsed"
        class="m-3 w-8 h-8 bg-gray-100 rounded-lg flex items-center justify-center text-gray-500 hover:bg-gray-200 self-end collapse-btn">
        <svg class="w-4 h-4 chevron-icon" :class="{'rotate-180': sidebarCollapsed}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 19l-7-7 7-7m8 14l-7-7 7-7"/>
        </svg>
    </button>
</aside>

{{-- ═══════════ MAIN CONTENT ═══════════ --}}
<div id="main-content" class="min-h-screen bg-gray-100 ml-64"
     style="transition:margin-left 0.35s cubic-bezier(0.4,0,0.2,1);">

    {{-- Blue Header --}}
    <div class="anim-fade flex items-center justify-between px-8 py-4"
         style="background:linear-gradient(135deg,#3b82f6,#1d4ed8);">
        <h1 class="text-white font-bold text-xl">Attendance Reports</h1>
        <button class="w-9 h-9 rounded-full flex items-center justify-center transition-all hover:bg-white/20">
            <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>
            </svg>
        </button>
    </div>

    <div class="p-6 space-y-5">

        {{-- TOP ROW --}}
        <div class="flex gap-5 items-stretch">

            {{-- CLOCK PANEL --}}
            <div class="anim-up bg-white rounded-2xl p-6 flex-shrink-0 flex flex-col"
                 style="width:320px; animation-delay:0.05s; box-shadow:0 1px 12px rgba(0,0,0,0.07);">
                <p class="text-xs font-bold text-gray-500 uppercase tracking-widest mb-3">Time & Attendance</p>
                <div class="mb-1">
                    <p class="font-black text-gray-900 tabular-nums leading-none" style="font-size:3rem; letter-spacing:-1px;" x-text="liveTime"></p>
                    <p class="text-sm text-gray-400 font-medium mt-1.5" x-text="liveDate"></p>
                </div>
                <hr class="my-4 border-gray-100">
                <div class="mb-3">
                    <p class="text-xs font-semibold text-gray-500 mb-2">Shift Schedule</p>
                    <div class="flex items-center justify-between border border-gray-200 rounded-xl px-4 py-2.5 bg-gray-50">
                        <span class="text-sm font-semibold text-gray-700">Day Shift</span>
                        <span class="text-xs text-gray-400 font-medium">7:00 AM – 4:00 PM</span>
                    </div>
                </div>
                <div class="mb-3">
                    <p class="text-xs font-semibold text-gray-500 mb-2">Work Setup</p>
                    <div class="flex rounded-xl overflow-hidden border border-gray-200">
                        <button @click="workSetup='office'"
                                :class="workSetup==='office' ? 'bg-gray-800 text-white font-bold' : 'bg-white text-gray-500 font-medium hover:bg-gray-50'"
                                class="setup-btn flex-1 py-2.5 text-sm">Office</button>
                        <button @click="workSetup='wfh'"
                                :class="workSetup==='wfh' ? 'text-white font-bold' : 'bg-white text-gray-500 font-medium hover:bg-gray-50'"
                                :style="workSetup==='wfh' ? 'background:#3b82f6' : ''"
                                class="setup-btn flex-1 py-2.5 text-sm">WFH</button>
                    </div>
                </div>
                <div class="mb-3">
                    <p class="text-xs font-semibold text-gray-500 mb-2">Today's Attendance</p>
                    <div class="flex gap-2 mb-2">
                        <div class="flex-1 border border-gray-200 rounded-xl px-3 py-3 bg-gray-50">
                            <p class="text-xs text-gray-400 font-semibold tracking-wider mb-1.5">CLOCK IN</p>
                            <p class="text-sm font-bold text-gray-700 border-b border-gray-300 pb-0.5" x-text="clockedIn ? clockInTime : '–'"></p>
                        </div>
                        <div class="flex-1 border border-gray-200 rounded-xl px-3 py-3 bg-gray-50">
                            <p class="text-xs text-gray-400 font-semibold tracking-wider mb-1.5">CLOCK OUT</p>
                            <p class="text-sm font-bold text-gray-700 border-b border-gray-300 pb-0.5" x-text="clockedOut ? clockOutTime : '–'"></p>
                        </div>
                    </div>
                    <p class="text-xs text-center text-gray-400 font-medium" x-show="!clockedIn"><span class="mr-1">⏱</span><span x-text="elapsedDisplay"></span></p>
                    <p class="text-xs text-center font-medium" x-show="clockedIn && !clockedOut" style="color:#3b82f6;"><span class="mr-1">⏱</span><span x-text="elapsedDisplay"></span></p>
                    <p class="text-xs text-center font-semibold" x-show="clockedOut" style="color:#22c55e;">✓ Attendance recorded · <span x-text="elapsedDisplay"></span></p>
                </div>
                <button @click="handleClock()" :disabled="clockedOut"
                        class="clock-btn mt-auto w-full py-3.5 text-white font-bold text-sm tracking-widest uppercase"
                        :style="clockedOut ? 'background:#94a3b8;' : clockedIn ? 'background:linear-gradient(135deg,#ef4444,#dc2626)' : 'background:linear-gradient(135deg,#3b82f6,#1d4ed8)'"
                        x-text="clockedOut ? 'COMPLETED' : clockedIn ? 'CLOCK OUT' : 'CLOCK IN'">
                </button>
            </div>

            {{-- 2×3 STAT CARDS --}}
            <div class="flex-1 grid grid-cols-2 grid-rows-3 gap-4">
                <div class="stat-card p-5 flex flex-col justify-between" style="background:#c8f0d8; animation-delay:0.08s;">
                    <p class="text-xs font-bold uppercase tracking-wider" style="color:#14532d;">Total Days Present</p>
                    <div><p class="font-black leading-none mt-2" style="font-size:3.2rem; color:#2563eb;">26</p><p class="text-xs font-semibold mt-2 uppercase tracking-wider" style="color:#15803d;">February 2026</p></div>
                </div>
                <div class="stat-card p-5 flex flex-col justify-between" style="background:#fde8c8; animation-delay:0.11s;">
                    <p class="text-xs font-bold uppercase tracking-wider" style="color:#92400e;">Late</p>
                    <div><p class="font-black leading-none mt-2" style="font-size:3.2rem; color:#1f2937;">2</p><p class="text-xs font-semibold mt-2 uppercase tracking-wider" style="color:#b45309;">February 2026</p></div>
                </div>
                <div class="stat-card p-5 flex flex-col justify-between" style="background:#fbc8c8; animation-delay:0.14s;">
                    <p class="text-xs font-bold uppercase tracking-wider" style="color:#991b1b;">Absent</p>
                    <div><p class="font-black leading-none mt-2" style="font-size:3.2rem; color:#1f2937;">0</p><p class="text-xs font-semibold mt-2 uppercase tracking-wider" style="color:#dc2626;">February 2026</p></div>
                </div>
                <div class="stat-card p-5 flex flex-col justify-between" style="background:#f9c8e8; animation-delay:0.17s;">
                    <p class="text-xs font-bold uppercase tracking-wider" style="color:#831843;">Leave</p>
                    <div><p class="font-black leading-none mt-2" style="font-size:3.2rem; color:#1f2937;">2</p><p class="text-xs font-semibold mt-2 uppercase tracking-wider" style="color:#be185d;">February 2026</p></div>
                </div>
                <div class="stat-card p-5 flex flex-col justify-between" style="background:#c8dafa; animation-delay:0.20s;">
                    <p class="text-xs font-bold uppercase tracking-wider" style="color:#1e3a8a;">Overtime</p>
                    <div><p class="font-black leading-none mt-2" style="font-size:3.2rem; color:#1f2937;">2</p><p class="text-xs font-semibold mt-2 uppercase tracking-wider" style="color:#1d4ed8;">February 2026</p></div>
                </div>
                <div class="stat-card p-5 flex flex-col justify-between" style="background:#c8dafa; animation-delay:0.23s;">
                    <p class="text-xs font-bold uppercase tracking-wider" style="color:#1e3a8a;">Undertime</p>
                    <div><p class="font-black leading-none mt-2" style="font-size:3.2rem; color:#1f2937;">0</p><p class="text-xs font-semibold mt-2 uppercase tracking-wider" style="color:#1d4ed8;">February 2026</p></div>
                </div>
            </div>
        </div>

        {{-- TABLE --}}
        <div class="anim-up bg-white rounded-2xl overflow-hidden" style="animation-delay:0.26s; box-shadow:0 1px 12px rgba(0,0,0,0.07);">
            <div class="px-6 py-4 flex items-center justify-between">
                <h3 class="font-bold text-gray-800 text-base">Attendance Records</h3>
                <div class="flex items-center gap-3">
                    <div class="flex items-center gap-2 border border-gray-200 rounded-xl px-3 py-2 bg-gray-50 text-xs text-gray-600 cursor-pointer hover:border-blue-300 transition-colors">
                        <svg class="w-3.5 h-3.5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                        <span class="font-medium">February 2026</span>
                        <svg class="w-3 h-3 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                    </div>
                    <button class="export-btn flex items-center gap-2 px-4 py-2 rounded-xl text-xs font-bold text-white">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                        Export
                    </button>
                </div>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead>
                        <tr class="border-t border-b border-gray-100 bg-gray-50/60">
                            <th class="text-left px-6 py-3 text-xs font-bold text-gray-400 uppercase tracking-wider">Date</th>
                            <th class="text-left px-6 py-3 text-xs font-bold text-gray-400 uppercase tracking-wider">Work Setup</th>
                            <th class="text-left px-6 py-3 text-xs font-bold text-gray-400 uppercase tracking-wider">Shift</th>
                            <th class="text-left px-6 py-3 text-xs font-bold text-gray-400 uppercase tracking-wider">Schedule</th>
                            <th class="text-left px-6 py-3 text-xs font-bold text-gray-400 uppercase tracking-wider">Clock In</th>
                            <th class="text-left px-6 py-3 text-xs font-bold text-gray-400 uppercase tracking-wider">Clock Out</th>
                            <th class="text-left px-6 py-3 text-xs font-bold text-gray-400 uppercase tracking-wider">Overtime</th>
                            <th class="text-left px-6 py-3 text-xs font-bold text-gray-400 uppercase tracking-wider">Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-50">
                        <template x-for="(row, i) in rows" :key="row.date">
                            <tr class="table-row" :style="`animation-delay:${0.035*i}s`">
                                <td class="px-6 py-4 text-sm font-medium text-gray-700" x-text="row.date"></td>
                                <td class="px-6 py-4"><span class="px-3 py-1 rounded-lg text-xs font-bold" :style="row.setup==='WFH'?'background:#dbeafe;color:#1d4ed8;':'background:#f1f5f9;color:#475569;'" x-text="row.setup"></span></td>
                                <td class="px-6 py-4 text-sm text-gray-600" x-text="row.shift"></td>
                                <td class="px-6 py-4 text-sm text-gray-500" x-text="row.schedule"></td>
                                <td class="px-6 py-4 text-sm font-semibold text-gray-800" x-text="row.clockIn"></td>
                                <td class="px-6 py-4 text-sm text-gray-500" x-text="row.clockOut"></td>
                                <td class="px-6 py-4 text-sm text-gray-600" x-text="row.overtime"></td>
                                <td class="px-6 py-4">
                                    <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold" :style="statusStyle(row.status)">
                                        <span class="w-1.5 h-1.5 rounded-full" :style="dotStyle(row.status)"></span>
                                        <span x-text="row.status"></span>
                                    </span>
                                </td>
                            </tr>
                        </template>
                    </tbody>
                </table>
            </div>
            <div class="px-6 py-4 border-t border-gray-100 flex items-center justify-between">
                <p class="text-xs text-gray-400">Showing <span class="font-semibold text-gray-600">8</span> of <span class="font-semibold text-gray-600">26</span> records</p>
                <div class="flex items-center gap-1">
                    <button class="w-8 h-8 rounded-lg border border-gray-200 text-gray-400 text-xs flex items-center justify-center hover:bg-gray-50">&lsaquo;</button>
                    <button class="w-8 h-8 rounded-lg text-xs font-bold text-white flex items-center justify-center" style="background:#3b82f6;">1</button>
                    <button class="w-8 h-8 rounded-lg border border-gray-200 text-gray-500 text-xs flex items-center justify-center hover:bg-gray-50">2</button>
                    <button class="w-8 h-8 rounded-lg border border-gray-200 text-gray-500 text-xs flex items-center justify-center hover:bg-gray-50">3</button>
                    <button class="w-8 h-8 rounded-lg border border-gray-200 text-gray-400 text-xs flex items-center justify-center hover:bg-gray-50">&rsaquo;</button>
                </div>
            </div>
        </div>

    </div>
</div>

<script>
function attendancePage() {
    return {
        workSetup: 'wfh', clockedIn: false, clockedOut: false,
        clockInTime: '', clockOutTime: '', clockInTimestamp: null,
        liveTime: '', liveDate: '', elapsedSeconds: 0,

        get elapsedDisplay() {
            const h = String(Math.floor(this.elapsedSeconds/3600)).padStart(2,'0');
            const m = String(Math.floor((this.elapsedSeconds%3600)/60)).padStart(2,'0');
            const s = String(this.elapsedSeconds%60).padStart(2,'0');
            return `${h}h ${m}m ${s}s`;
        },

        init() {
            this.tick();
            setInterval(() => {
                this.tick();
                if (this.clockedIn && !this.clockedOut)
                    this.elapsedSeconds = Math.floor((Date.now()-this.clockInTimestamp)/1000);
            }, 1000);
        },

        tick() {
            const n = new Date();
            this.liveTime = String(n.getHours()).padStart(2,'0')+':'+String(n.getMinutes()).padStart(2,'0')+':'+String(n.getSeconds()).padStart(2,'0');
            this.liveDate = n.toLocaleDateString('en-US',{weekday:'long',year:'numeric',month:'long',day:'numeric'});
        },

        handleClock() {
            if (this.clockedOut) return;
            const t = new Date().toLocaleTimeString('en-US',{hour:'2-digit',minute:'2-digit',hour12:true});
            if (!this.clockedIn) { this.clockedIn=true; this.clockInTime=t; this.clockInTimestamp=Date.now(); }
            else { this.clockedOut=true; this.clockOutTime=t; }
        },

        rows: [
            {date:'February 26, 2026',setup:'WFH',   shift:'Day Shift',schedule:'7:00 AM – 4:00 PM',clockIn:'7:00 AM',clockOut:'4:00 PM',overtime:'00h 00m',status:'Present'},
            {date:'February 25, 2026',setup:'WFH',   shift:'Day Shift',schedule:'7:00 AM – 4:00 PM',clockIn:'7:00 AM',clockOut:'4:00 PM',overtime:'00h 03m',status:'Late'},
            {date:'February 24, 2026',setup:'Office',shift:'Day Shift',schedule:'7:00 AM – 4:00 PM',clockIn:'7:00 AM',clockOut:'4:00 PM',overtime:'00h 00m',status:'Present'},
            {date:'February 23, 2026',setup:'WFH',   shift:'Day Shift',schedule:'7:00 AM – 4:00 PM',clockIn:'7:00 AM',clockOut:'4:00 PM',overtime:'00h 23m',status:'Present'},
            {date:'February 22, 2026',setup:'Office',shift:'Day Shift',schedule:'7:00 AM – 4:00 PM',clockIn:'7:00 AM',clockOut:'4:00 PM',overtime:'00h 23m',status:'Late'},
            {date:'February 21, 2026',setup:'WFH',   shift:'Day Shift',schedule:'7:00 AM – 4:00 PM',clockIn:'7:00 AM',clockOut:'4:00 PM',overtime:'00h 23m',status:'Present'},
            {date:'February 20, 2026',setup:'Office',shift:'Day Shift',schedule:'7:00 AM – 4:00 PM',clockIn:'7:00 AM',clockOut:'4:00 PM',overtime:'00h 23m',status:'Present'},
            {date:'February 19, 2026',setup:'WFH',   shift:'Day Shift',schedule:'7:00 AM – 4:00 PM',clockIn:'7:00 AM',clockOut:'4:00 PM',overtime:'00h 03m',status:'Present'},
        ],

        statusStyle(s){return{'Present':'background:rgba(34,197,94,0.12);color:#16a34a;','Late':'background:rgba(245,158,11,0.12);color:#d97706;','Absent':'background:rgba(239,68,68,0.12);color:#dc2626;','On Leave':'background:rgba(99,102,241,0.12);color:#4f46e5;'}[s]||'background:#f1f5f9;color:#64748b;';},
        dotStyle(s){return{'Present':'background:#22c55e;','Late':'background:#f59e0b;','Absent':'background:#ef4444;','On Leave':'background:#6366f1;'}[s]||'background:#94a3b8;';}
    }
}
</script>
</body>
</html>
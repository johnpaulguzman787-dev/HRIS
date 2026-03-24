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

    $attendanceRoutes = ['hr.attendance.reports', 'hr.attendance.employee', 'hr.attendance.shift', 'hr.attendance.leave', 'hr.shift.scheduling', 'hr.leave.management'];
    $employeeRoutes   = ['hr.employees.directory', 'hr.employees.profile'];
    $payrollRoutes    = ['hr.payslips', 'hr.govpay'];
    $requestRoutes    = ['hr.requests.pending', 'hr.requests.approved'];
@endphp

<aside 
    class="bg-white border-r border-gray-200 h-screen fixed left-0 top-0 overflow-y-auto z-50 flex flex-col"
    x-data="{
        sidebarCollapsed: localStorage.getItem('sidebarCollapsed') === 'true',
        employeesOpen: {{ in_array($currentRoute, $employeeRoutes) ? 'true' : 'false' }},
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
        <a href="{{ route('hr.dashboard') }}"
            class="nav-item flex items-center space-x-3 px-3 py-2.5 rounded-lg group
                {{ $currentRoute === 'hr.dashboard' ? 'text-white' : 'text-gray-600 hover:bg-gray-50' }}"
            style="{{ $currentRoute === 'hr.dashboard' ? 'background:#3b82f6;' : '' }}">
            <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"/>
            </svg>
            <span x-show="!sidebarCollapsed" class="text-sm font-medium whitespace-nowrap">Dashboard</span>
        </a>

        <!-- Employees -->
        <div>
            <button @click="employeesOpen = !employeesOpen"
                class="nav-item w-full flex items-center justify-between px-3 py-2.5 rounded-lg {{ in_array($currentRoute, $employeeRoutes) ? 'text-blue-600' : 'text-gray-600 hover:bg-gray-50' }}">
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
                 x-transition:enter="transition ease-out duration-250"
                 x-transition:enter-start="opacity-0 -translate-y-3 scale-y-95"
                 x-transition:enter-end="opacity-100 translate-y-0 scale-y-100"
                 x-transition:leave="transition ease-in duration-200"
                 x-transition:leave-start="opacity-100 translate-y-0 scale-y-100"
                 x-transition:leave-end="opacity-0 -translate-y-3 scale-y-95"
                 class="ml-8 mt-1 space-y-0.5 origin-top">
                <a href="{{ route('hr.employees.directory') }}" class="submenu-item block px-3 py-2 text-sm rounded-lg {{ $currentRoute === 'hr.employees.directory' ? 'text-blue-600 font-semibold bg-blue-50' : 'text-gray-500 hover:text-gray-800 hover:bg-gray-50' }}">Employee Directory</a>
                <a href="{{ route('hr.employees.profile') }}"   class="submenu-item block px-3 py-2 text-sm rounded-lg {{ $currentRoute === 'hr.employees.profile'   ? 'text-blue-600 font-semibold bg-blue-50' : 'text-gray-500 hover:text-gray-800 hover:bg-gray-50' }}">Employee Profile</a>
            </div>
        </div>

        <!-- Time & Attendance -->
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
                 x-transition:enter="transition ease-out duration-250"
                 x-transition:enter-start="opacity-0 -translate-y-3 scale-y-95"
                 x-transition:enter-end="opacity-100 translate-y-0 scale-y-100"
                 x-transition:leave="transition ease-in duration-200"
                 x-transition:leave-start="opacity-100 translate-y-0 scale-y-100"
                 x-transition:leave-end="opacity-0 -translate-y-3 scale-y-95"
                 class="ml-8 mt-1 space-y-0.5 origin-top">

                <a href="{{ route('hr.attendance.reports') }}"
                   class="submenu-item flex items-center px-3 py-2 text-sm rounded-lg
                       {{ $currentRoute === 'hr.attendance.reports' ? 'font-semibold' : 'text-gray-500 hover:text-gray-800 hover:bg-gray-50' }}"
                   style="{{ $currentRoute === 'hr.attendance.reports' ? 'color:#3b82f6;' : '' }}">
                    @if($currentRoute === 'hr.attendance.reports')
                        <span class="w-2 h-2 rounded-full mr-2.5 flex-shrink-0" style="background:#3b82f6; animation: pulseDot 2s ease-in-out infinite;"></span>
                    @endif
                    My Attendance
                </a>
                <a href="{{ route('hr.attendance.employee') }}"
                   class="submenu-item block px-3 py-2 text-sm rounded-lg
                       {{ $currentRoute === 'hr.attendance.employee' ? 'text-blue-600 font-semibold bg-blue-50' : 'text-gray-500 hover:text-gray-800 hover:bg-gray-50' }}">
                    Employee Attendance
                </a>

                {{-- ✅ UPDATED: Shift Scheduling — may actual route na --}}
                <a href="{{ route('hr.shift.scheduling') }}"
                   class="submenu-item block px-3 py-2 text-sm rounded-lg
                       {{ $currentRoute === 'hr.shift.scheduling' ? 'text-blue-600 font-semibold bg-blue-50' : 'text-gray-500 hover:text-gray-800 hover:bg-gray-50' }}">
                    Shift Scheduling
                </a>

                <a href="{{ route('hr.leave.management') }}" class="submenu-item block px-3 py-2 text-sm rounded-lg {{ $currentRoute === 'hr.leave.management' ? 'text-blue-600 font-semibold bg-blue-50' : 'text-gray-500 hover:text-gray-800 hover:bg-gray-50' }}">Leave Management</a>
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
                <a href="{{ route('hr.payslips') }}" class="submenu-item block px-3 py-2 text-sm rounded-lg {{ $currentRoute === 'hr.payslips' ? 'text-blue-600 font-semibold bg-blue-50' : 'text-gray-500 hover:text-gray-800 hover:bg-gray-50' }}">Payslips</a>
                <a href="{{ route('hr.govpay') }}" class="submenu-item block px-3 py-2 text-sm rounded-lg {{ $currentRoute === 'hr.govpay' ? 'text-blue-600 font-semibold bg-blue-50' : 'text-gray-500 hover:text-gray-800 hover:bg-gray-50' }}">Govt. Contributions</a>
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
                <a href="{{ route('hr.requests.pending') }}" class="submenu-item block px-3 py-2 text-sm rounded-lg {{ $currentRoute === 'hr.requests.pending' ? 'text-blue-600 font-semibold bg-blue-50' : 'text-gray-500 hover:text-gray-800 hover:bg-gray-50' }}">Pending Requests</a>
                <a href="{{ route('hr.requests.approved') }}" class="submenu-item block px-3 py-2 text-sm rounded-lg {{ $currentRoute === 'hr.requests.approved' ? 'text-blue-600 font-semibold bg-blue-50' : 'text-gray-500 hover:text-gray-800 hover:bg-gray-50' }}">Approved Logs</a>
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

            <a href="{{ route('logout') }}" onclick="event.preventDefault(); document.getElementById('hr-logout-form').submit();"
                class="nav-item logout-btn flex items-center space-x-3 px-3 py-2.5 rounded-lg text-gray-600 hover:bg-red-50 hover:text-red-500">
                <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
                </svg>
                <span x-show="!sidebarCollapsed" class="text-sm font-medium whitespace-nowrap">Logout</span>
            </a>
            <form id="hr-logout-form" action="{{ route('logout') }}" method="POST" class="hidden">@csrf</form>
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
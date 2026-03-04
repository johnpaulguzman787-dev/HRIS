@props(['activeMenu' => 'dashboard'])

@php
    $currentRoute = request()->route()->getName();
@endphp

<aside 
    class="bg-white border-r border-gray-200 h-screen fixed left-0 top-0 overflow-y-auto z-50"
    x-data="{ 
        sidebarCollapsed: localStorage.getItem('sidebarCollapsed') === 'true',
        employeesOpen: {{ in_array($currentRoute, ['employees.directory']) ? 'true' : 'false' }},
        attendanceOpen: false,
        requestsOpen: false,
        reportsOpen: false,
        hoveredItem: null
    }" 
    x-init="$watch('sidebarCollapsed', value => localStorage.setItem('sidebarCollapsed', value))"
    :class="sidebarCollapsed ? 'w-20' : 'w-64'"
    style="box-shadow: 4px 0 10px rgba(0,0,0,0.02); transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);"
    @mouseleave="hoveredItem = null"
>
    <!-- Logo Section -->
    <div class="p-6 border-b border-gray-100 relative overflow-hidden group" :class="sidebarCollapsed ? 'text-center' : ''">
        <h1 class="font-bold text-blue-600 relative z-10 transition-all duration-300 group-hover:text-blue-700" :class="sidebarCollapsed ? 'text-sm' : 'text-xl'">
            <span :class="sidebarCollapsed ? 'hidden' : 'inline'">MEDISOURCE</span>
            <span :class="sidebarCollapsed ? 'inline' : 'hidden'">MS</span>
        </h1>
        <div class="absolute inset-0 bg-gradient-to-r from-blue-50 to-transparent opacity-0 group-hover:opacity-100 transition-opacity duration-500"></div>
    </div>
    
    <!-- User Profile Card -->
    <div class="p-4 border-b border-gray-100 relative group" :class="sidebarCollapsed ? 'text-center' : 'flex items-center space-x-3'">
        <div class="w-10 h-10 bg-gradient-to-r from-blue-500 to-blue-600 rounded-xl flex items-center justify-center text-white font-semibold flex-shrink-0 relative overflow-hidden">
            <span class="relative z-10 transition-transform duration-300 group-hover:scale-110">JD</span>
            <div class="absolute inset-0 bg-white opacity-0 group-hover:opacity-20 transition-opacity duration-300"></div>
        </div>
        <div x-show="!sidebarCollapsed" class="overflow-hidden">
            <p class="font-semibold text-gray-800 group-hover:text-blue-600 transition-colors duration-300">John Doe</p>
            <p class="text-xs text-gray-500">System Administrator</p>
        </div>
    </div>
    
    <!-- Navigation Menu -->
    <nav class="p-4 space-y-1">
        <!-- Dashboard -->
        <a href="{{ route('admin.dashboard') }}" 
            class="flex items-center space-x-3 px-4 py-3 rounded-xl transition-all duration-300 hover:bg-blue-50 group"
            :class="'{{ $currentRoute === 'admin.dashboard' }}' ? 'bg-blue-50 text-blue-600' : 'text-gray-600 hover:text-blue-600'"
        >
            <svg class="w-5 h-5 flex-shrink-0" :class="'{{ $currentRoute === 'admin.dashboard' }}' ? 'text-blue-600' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path>
            </svg>
            <span x-show="!sidebarCollapsed" class="text-sm font-medium">Dashboard</span>
        </a>

        <!-- Employees Dropdown -->
        <div class="relative">
            <button 
                @click="employeesOpen = !employeesOpen"
                class="w-full flex items-center justify-between px-4 py-3 rounded-xl transition-all duration-300 group"
                :class="employeesOpen || '{{ $currentRoute === 'employees.directory' }}' ? 'bg-blue-50 text-blue-600' : 'text-gray-600 hover:text-blue-600 hover:bg-blue-50'"
            >
                <div class="flex items-center space-x-3">
                    <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path>
                    </svg>
                    <span x-show="!sidebarCollapsed" class="text-sm font-medium">Employees</span>
                </div>
                <span x-show="!sidebarCollapsed">
                    <svg class="w-4 h-4 transition-all duration-500" :class="{ 'rotate-180': employeesOpen }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                    </svg>
                </span>
            </button>
            <div x-show="employeesOpen && !sidebarCollapsed" 
                 x-transition:enter="transition ease-out duration-300"
                 x-transition:enter-start="opacity-0 -translate-y-4 scale-95"
                 x-transition:enter-end="opacity-100 translate-y-0 scale-100"
                 x-transition:leave="transition ease-in duration-200"
                 x-transition:leave-start="opacity-100 translate-y-0 scale-100"
                 x-transition:leave-end="opacity-0 -translate-y-4 scale-95"
                 class="ml-11 mt-1 space-y-1">
                <a href="{{ route('employees.directory') }}" 
                   class="block px-4 py-2 text-sm rounded-lg transition-all duration-200"
                   :class="'{{ $currentRoute === 'employees.directory' }}' ? 'bg-blue-50 text-blue-600' : 'text-gray-600 hover:bg-blue-50 hover:text-blue-600'">
                    Employee Directory
                </a>
                <a href="#" class="block px-4 py-2 text-sm text-gray-600 hover:bg-blue-50 hover:text-blue-600 rounded-lg transition-all duration-200 hover:translate-x-2">Employee Documents</a>
            </div>
        </div>

        <!-- Time & Attendance Dropdown -->
        <div class="relative">
            <button 
                @click="attendanceOpen = !attendanceOpen"
                class="w-full flex items-center justify-between px-4 py-3 rounded-xl transition-all duration-300 group"
                :class="attendanceOpen ? 'bg-blue-50 text-blue-600' : 'text-gray-600 hover:text-blue-600 hover:bg-blue-50'"
            >
                <div class="flex items-center space-x-3">
                    <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    <span x-show="!sidebarCollapsed" class="text-sm font-medium">Time & Attendance</span>
                </div>
                <span x-show="!sidebarCollapsed">
                    <svg class="w-4 h-4 transition-all duration-500" :class="{ 'rotate-180': attendanceOpen }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                    </svg>
                </span>
            </button>
            <div x-show="attendanceOpen && !sidebarCollapsed" 
                 x-transition:enter="transition ease-out duration-300"
                 x-transition:enter-start="opacity-0 -translate-y-4 scale-95"
                 x-transition:enter-end="opacity-100 translate-y-0 scale-100"
                 x-transition:leave="transition ease-in duration-200"
                 x-transition:leave-start="opacity-100 translate-y-0 scale-100"
                 x-transition:leave-end="opacity-0 -translate-y-4 scale-95"
                 class="ml-11 mt-1 space-y-1">
                <a href="#" class="block px-4 py-2 text-sm text-gray-600 hover:bg-blue-50 hover:text-blue-600 rounded-lg transition-all duration-200 hover:translate-x-2">Time In / Time Out</a>
                <a href="#" class="block px-4 py-2 text-sm text-gray-600 hover:bg-blue-50 hover:text-blue-600 rounded-lg transition-all duration-200 hover:translate-x-2">Attendance Records</a>
                <a href="#" class="block px-4 py-2 text-sm text-gray-600 hover:bg-blue-50 hover:text-blue-600 rounded-lg transition-all duration-200 hover:translate-x-2">Shift Scheduling</a>
                <a href="#" class="block px-4 py-2 text-sm text-gray-600 hover:bg-blue-50 hover:text-blue-600 rounded-lg transition-all duration-200 hover:translate-x-2">Leave Management</a>
            </div>
        </div>

        <!-- Requests & Approval Dropdown -->
        <div class="relative">
            <button 
                @click="requestsOpen = !requestsOpen"
                class="w-full flex items-center justify-between px-4 py-3 rounded-xl transition-all duration-300 group"
                :class="requestsOpen ? 'bg-blue-50 text-blue-600' : 'text-gray-600 hover:text-blue-600 hover:bg-blue-50'"
            >
                <div class="flex items-center space-x-3">
                    <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                    </svg>
                    <span x-show="!sidebarCollapsed" class="text-sm font-medium">Requests & Approval</span>
                </div>
                <span x-show="!sidebarCollapsed">
                    <svg class="w-4 h-4 transition-all duration-500" :class="{ 'rotate-180': requestsOpen }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                    </svg>
                </span>
            </button>
            <div x-show="requestsOpen && !sidebarCollapsed" 
                 x-transition:enter="transition ease-out duration-300"
                 x-transition:enter-start="opacity-0 -translate-y-4 scale-95"
                 x-transition:enter-end="opacity-100 translate-y-0 scale-100"
                 x-transition:leave="transition ease-in duration-200"
                 x-transition:leave-start="opacity-100 translate-y-0 scale-100"
                 x-transition:leave-end="opacity-0 -translate-y-4 scale-95"
                 class="ml-11 mt-1 space-y-1">
                <a href="#" class="block px-4 py-2 text-sm text-gray-600 hover:bg-blue-50 hover:text-blue-600 rounded-lg transition-all duration-200 hover:translate-x-2">Leave Requests</a>
                <a href="#" class="block px-4 py-2 text-sm text-gray-600 hover:bg-blue-50 hover:text-blue-600 rounded-lg transition-all duration-200 hover:translate-x-2">Overtime Requests</a>
                <a href="#" class="block px-4 py-2 text-sm text-gray-600 hover:bg-blue-50 hover:text-blue-600 rounded-lg transition-all duration-200 hover:translate-x-2">Reimbursement</a>
                <a href="#" class="block px-4 py-2 text-sm text-gray-600 hover:bg-blue-50 hover:text-blue-600 rounded-lg transition-all duration-200 hover:translate-x-2">Schedule Changes</a>
            </div>
        </div>

        <!-- Reports Dropdown -->
        <div class="relative">
            <button 
                @click="reportsOpen = !reportsOpen"
                class="w-full flex items-center justify-between px-4 py-3 rounded-xl transition-all duration-300 group"
                :class="reportsOpen ? 'bg-blue-50 text-blue-600' : 'text-gray-600 hover:text-blue-600 hover:bg-blue-50'"
            >
                <div class="flex items-center space-x-3">
                    <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                    </svg>
                    <span x-show="!sidebarCollapsed" class="text-sm font-medium">Reports</span>
                </div>
                <span x-show="!sidebarCollapsed">
                    <svg class="w-4 h-4 transition-all duration-500" :class="{ 'rotate-180': reportsOpen }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                    </svg>
                </span>
            </button>
            <div x-show="reportsOpen && !sidebarCollapsed" 
                 x-transition:enter="transition ease-out duration-300"
                 x-transition:enter-start="opacity-0 -translate-y-4 scale-95"
                 x-transition:enter-end="opacity-100 translate-y-0 scale-100"
                 x-transition:leave="transition ease-in duration-200"
                 x-transition:leave-start="opacity-100 translate-y-0 scale-100"
                 x-transition:leave-end="opacity-0 -translate-y-4 scale-95"
                 class="ml-11 mt-1 space-y-1">
                <a href="#" class="block px-4 py-2 text-sm text-gray-600 hover:bg-blue-50 hover:text-blue-600 rounded-lg transition-all duration-200 hover:translate-x-2">Attendance Reports</a>
                <a href="#" class="block px-4 py-2 text-sm text-gray-600 hover:bg-blue-50 hover:text-blue-600 rounded-lg transition-all duration-200 hover:translate-x-2">Payroll Reports</a>
                <a href="#" class="block px-4 py-2 text-sm text-gray-600 hover:bg-blue-50 hover:text-blue-600 rounded-lg transition-all duration-200 hover:translate-x-2">Contribution Reports</a>
            </div>
        </div>
        
        <!-- Others Section -->
        <div class="pt-4 mt-4 border-t border-gray-100 space-y-1">
            <a href="{{ route('settings.index') }}" 
                class="flex items-center space-x-3 px-4 py-3 rounded-xl transition-all duration-300 group"
                :class="'{{ $currentRoute === 'settings.index' }}' ? 'bg-blue-50 text-blue-600' : 'text-gray-600 hover:bg-blue-50 hover:text-blue-600'"
            >
                <svg class="w-5 h-5 flex-shrink-0 transition-all duration-300 group-hover:rotate-90" :class="'{{ $currentRoute === 'settings.index' }}' ? 'text-blue-600' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path>
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                </svg>
                <span x-show="!sidebarCollapsed" class="text-sm font-medium">Settings</span>
            </a>
            <a href="#" class="flex items-center space-x-3 px-4 py-3 rounded-xl text-gray-600 hover:bg-red-50 transition-all duration-300 hover:text-red-600 group">
                <svg class="w-5 h-5 flex-shrink-0 transition-all duration-300 group-hover:translate-x-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path>
                </svg>
                <span x-show="!sidebarCollapsed" class="text-sm font-medium">Logout</span>
            </a>
        </div>
    </nav>
    
    <!-- Collapse Button -->
    <button 
        @click="sidebarCollapsed = !sidebarCollapsed; localStorage.setItem('sidebarCollapsed', !sidebarCollapsed)"
        class="absolute bottom-4 right-4 w-8 h-8 bg-gray-100 rounded-lg flex items-center justify-center text-gray-600 hover:bg-gray-200 transition-all duration-300 hover:scale-110 group"
    >
        <svg class="w-4 h-4 transition-all duration-500" :class="{ 'rotate-180': sidebarCollapsed }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 19l-7-7 7-7m8 14l-7-7 7-7"></path>
        </svg>
    </button>
</aside>
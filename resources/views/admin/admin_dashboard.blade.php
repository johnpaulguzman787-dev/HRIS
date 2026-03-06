@extends('layouts.app')

@section('title', 'Dashboard')

@section('content')
<div x-data="{ 
        sidebarCollapsed: false,
        employeesOpen: false,
        attendanceOpen: false,
        requestsOpen: false,
        reportsOpen: false,
        hoveredItem: null,
        activeMenu: 'dashboard'
    }" 
    class="flex h-screen overflow-hidden bg-gray-50">

    <!-- Fixed Sidebar -->
    <aside 
        class="bg-white border-r border-gray-200 h-screen fixed left-0 top-0 overflow-y-auto z-50"
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
@php
    $dashUser = auth()->user();
    $dashEmployee = \App\Models\Employee::with('jobTitle')->where('user_id', $dashUser->id)->first();
    $dashInitials = $dashEmployee ? strtoupper(substr($dashEmployee->fname, 0, 1) . substr($dashEmployee->lname, 0, 1)) : strtoupper(substr($dashUser->email, 0, 2));
    $dashName = $dashEmployee ? trim($dashEmployee->fname . ' ' . $dashEmployee->lname) : $dashUser->email;
    $dashRole = $dashEmployee?->jobTitle?->title ?? $dashUser->role;
@endphp
<div class="p-4 border-b border-gray-100 relative group" :class="sidebarCollapsed ? 'text-center' : 'flex items-center space-x-3'">
    <div class="w-10 h-10 bg-gradient-to-r from-blue-500 to-blue-600 rounded-xl flex items-center justify-center text-white font-semibold flex-shrink-0 relative overflow-hidden">
        <span class="relative z-10 transition-transform duration-300 group-hover:scale-110">{{ $dashInitials }}</span>
        <div class="absolute inset-0 bg-white opacity-0 group-hover:opacity-20 transition-opacity duration-300"></div>
    </div>
    <div x-show="!sidebarCollapsed" class="overflow-hidden">
        <p class="font-semibold text-gray-800 group-hover:text-blue-600 transition-colors duration-300">{{ $dashName }}</p>
        <p class="text-xs text-gray-500">{{ $dashRole }}</p>
    </div>
</div>
        
        <!-- Navigation Menu -->
        <nav class="p-4 space-y-1">
            <!-- Dashboard -->
            <a href="#" 
                class="flex items-center space-x-3 px-4 py-3 rounded-xl transition-all duration-300 hover:bg-blue-50 group"
                :class="activeMenu === 'dashboard' ? 'bg-blue-50 text-blue-600' : 'text-gray-600 hover:text-blue-600'"
                @click="activeMenu = 'dashboard'"
            >
                <svg class="w-5 h-5 flex-shrink-0" :class="{ 'text-blue-600': activeMenu === 'dashboard' }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path>
                </svg>
                <span x-show="!sidebarCollapsed" class="text-sm font-medium">Dashboard</span>
            </a>

            <!-- Employees Dropdown -->
            <div class="relative">
                <button 
                    @click="employeesOpen = !employeesOpen"
                    class="w-full flex items-center justify-between px-4 py-3 rounded-xl transition-all duration-300 group"
                    :class="employeesOpen ? 'bg-blue-50 text-blue-600' : 'text-gray-600 hover:text-blue-600 hover:bg-blue-50'"
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
                    <a href="{{ route('employees.directory') }}" class="block px-4 py-2 text-sm text-gray-600 hover:bg-blue-50 hover:text-blue-600 rounded-lg transition-all duration-200 hover:translate-x-1">Employee Directory</a>
                    <a href="{{ route('employees.profile') }}" class="block px-4 py-2 text-sm text-gray-600 hover:bg-blue-50 hover:text-blue-600 rounded-lg transition-all duration-200 hover:translate-x-1">Employee Profile</a>
                    <a href="#" class="block px-4 py-2 text-sm text-gray-600 hover:bg-blue-50 hover:text-blue-600 rounded-lg transition-all duration-200 hover:translate-x-1">Employee Documents</a>
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
                    <a href="#" class="block px-4 py-2 text-sm text-gray-600 hover:bg-blue-50 hover:text-blue-600 rounded-lg transition-all duration-200 hover:translate-x-1">Time In / Time Out</a>
                    <a href="#" class="block px-4 py-2 text-sm text-gray-600 hover:bg-blue-50 hover:text-blue-600 rounded-lg transition-all duration-200 hover:translate-x-1">Attendance Records</a>
                    <a href="#" class="block px-4 py-2 text-sm text-gray-600 hover:bg-blue-50 hover:text-blue-600 rounded-lg transition-all duration-200 hover:translate-x-1">Shift Scheduling</a>
                    <a href="#" class="block px-4 py-2 text-sm text-gray-600 hover:bg-blue-50 hover:text-blue-600 rounded-lg transition-all duration-200 hover:translate-x-1">Leave Management</a>
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
                    <a href="#" class="block px-4 py-2 text-sm text-gray-600 hover:bg-blue-50 hover:text-blue-600 rounded-lg transition-all duration-200 hover:translate-x-1">Leave Requests</a>
                    <a href="#" class="block px-4 py-2 text-sm text-gray-600 hover:bg-blue-50 hover:text-blue-600 rounded-lg transition-all duration-200 hover:translate-x-1">Overtime Requests</a>
                    <a href="#" class="block px-4 py-2 text-sm text-gray-600 hover:bg-blue-50 hover:text-blue-600 rounded-lg transition-all duration-200 hover:translate-x-1">Reimbursement</a>
                    <a href="#" class="block px-4 py-2 text-sm text-gray-600 hover:bg-blue-50 hover:text-blue-600 rounded-lg transition-all duration-200 hover:translate-x-1">Schedule Changes</a>
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
                    <a href="#" class="block px-4 py-2 text-sm text-gray-600 hover:bg-blue-50 hover:text-blue-600 rounded-lg transition-all duration-200 hover:translate-x-1">Attendance Reports</a>
                    <a href="#" class="block px-4 py-2 text-sm text-gray-600 hover:bg-blue-50 hover:text-blue-600 rounded-lg transition-all duration-200 hover:translate-x-1">Payroll Reports</a>
                    <a href="#" class="block px-4 py-2 text-sm text-gray-600 hover:bg-blue-50 hover:text-blue-600 rounded-lg transition-all duration-200 hover:translate-x-1">Contribution Reports</a>
                </div>
            </div>
            
            <!-- Others Section -->
            <div class="pt-4 mt-4 border-t border-gray-100 space-y-1">
                <a href="{{ route('settings.index') }}" 
                    class="flex items-center space-x-3 px-4 py-3 rounded-xl transition-all duration-300 group"
                    :class="'{{ request()->routeIs('settings.index') }}' ? 'bg-blue-50 text-blue-600' : 'text-gray-600 hover:bg-blue-50 hover:text-blue-600'"
                >
                    <svg class="w-5 h-5 flex-shrink-0 transition-all duration-300 group-hover:rotate-90" :class="'{{ request()->routeIs('settings.index') }}' ? 'text-blue-600' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24">
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
            @click="sidebarCollapsed = !sidebarCollapsed" 
            class="absolute bottom-4 right-4 w-8 h-8 bg-gray-100 rounded-lg flex items-center justify-center text-gray-600 hover:bg-gray-200 transition-all duration-300 hover:scale-110 group"
        >
            <svg class="w-4 h-4 transition-all duration-500" :class="{ 'rotate-180': sidebarCollapsed }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 19l-7-7 7-7m8 14l-7-7 7-7"></path>
            </svg>
        </button>
    </aside>

    <!-- Main Content -->
    <main 
        class="flex-1 overflow-y-auto min-h-screen"
        :class="sidebarCollapsed ? 'ml-20' : 'ml-64'"
        style="transition: margin-left 0.3s cubic-bezier(0.4, 0, 0.2, 1);"
    >
        <!-- Top Header -->
        <header class="bg-gradient-to-r from-blue-600 to-blue-700 text-white sticky top-0 z-10 shadow-lg mt-4 mx-4 rounded-2xl">
            <div class="px-4 sm:px-6 lg:px-8 py-4 flex items-center justify-between">
                <div>
                    <h1 class="text-xl sm:text-2xl font-bold">Dashboard</h1>
                </div>
                <div class="flex items-center space-x-4">
                    <button class="relative p-2 hover:bg-blue-500 rounded-lg transition-all duration-300 group">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"></path>
                        </svg>
                        <span class="absolute top-1 right-1 w-2 h-2 bg-red-400 rounded-full animate-ping"></span>
                        <span class="absolute top-1 right-1 w-2 h-2 bg-red-400 rounded-full"></span>
                    </button>
                   <div class="w-10 h-10 bg-gradient-to-r from-blue-500 to-blue-600 rounded-xl flex items-center justify-center text-white font-semibold shadow-md hover:shadow-lg transition-all duration-300 hover:scale-110 cursor-pointer">
    {{ $dashInitials }}
</div>
                </div>
            </div>
        </header>

        <!-- Dashboard Content -->
        <div class="p-8" 
             x-data="{ 
                selectedDepartment: 'All Departments',
                counters: {
                    totalEmployees: 156,
                    presentToday: 128,
                    lateAbsent: 28,
                    pendingRequests: 12
                },
                departmentData: {
                    'All Departments': {
                        present: 128,
                        late: 18,
                        absent: 10,
                        on_leave: 8,
                        departments: [
                            { name: 'IT', present: 42, total: 45, percentage: 93 },
                            { name: 'Finance', present: 28, total: 32, percentage: 88 },
                            { name: 'Nursing', present: 58, total: 78, percentage: 74 },
                            { name: 'HR', present: 15, total: 18, percentage: 83 },
                            { name: 'Administration', present: 12, total: 15, percentage: 80 }
                        ]
                    },
                    'IT': {
                        present: 42, late: 2, absent: 1, on_leave: 0,
                        departments: [{ name: 'IT', present: 42, total: 45, percentage: 93 }]
                    },
                    'Finance': {
                        present: 28, late: 2, absent: 1, on_leave: 1,
                        departments: [{ name: 'Finance', present: 28, total: 32, percentage: 88 }]
                    },
                    'Nursing': {
                        present: 58, late: 8, absent: 6, on_leave: 6,
                        departments: [{ name: 'Nursing', present: 58, total: 78, percentage: 74 }]
                    },
                    'HR': {
                        present: 15, late: 1, absent: 1, on_leave: 1,
                        departments: [{ name: 'HR', present: 15, total: 18, percentage: 83 }]
                    },
                    'Administration': {
                        present: 12, late: 1, absent: 1, on_leave: 1,
                        departments: [{ name: 'Administration', present: 12, total: 15, percentage: 80 }]
                    }
                },
                get currentData() {
                    return this.departmentData[this.selectedDepartment] || this.departmentData['All Departments'];
                }
             }"
        >
            <!-- Statistics Cards -->
            <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
                <!-- Total Employees -->
                <div class="bg-white rounded-xl p-6 card-hover animate-slide-in group" style="animation-delay: 0.1s; box-shadow: 0 4px 20px rgba(0,0,0,0.02);">
                    <div class="flex items-center justify-between mb-2">
                        <span class="text-gray-500 text-sm group-hover:text-blue-600 transition-colors duration-300">Total Employees</span>
                        <span class="w-10 h-10 bg-blue-50 rounded-lg flex items-center justify-center group-hover:bg-blue-100 group-hover:scale-110 transition-all duration-300">
                            <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path>
                            </svg>
                        </span>
                    </div>
                    <p class="text-3xl font-bold text-gray-800 group-hover:text-blue-600 transition-colors duration-300" x-text="{{ $totalEmployees }}"></p>
                    <p class="text-xs text-green-500 mt-2">↑ 12% from last month</p>
                </div>

                <!-- Present Today -->
                <div class="bg-white rounded-xl p-6 card-hover animate-slide-in group" style="animation-delay: 0.2s; box-shadow: 0 4px 20px rgba(0,0,0,0.02);">
                    <div class="flex items-center justify-between mb-2">
                        <span class="text-gray-500 text-sm group-hover:text-green-600 transition-colors duration-300">Present Today</span>
                        <span class="w-10 h-10 bg-green-50 rounded-lg flex items-center justify-center group-hover:bg-green-100 group-hover:scale-110 transition-all duration-300">
                            <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                        </span>
                    </div>
                    <p class="text-3xl font-bold text-gray-800 group-hover:text-green-600 transition-colors duration-300" x-text="counters.presentToday"></p>
                    <p class="text-xs text-gray-500 mt-2">82% attendance rate</p>
                </div>

                <!-- Late and Absent -->
                <div class="bg-white rounded-xl p-6 card-hover animate-slide-in group" style="animation-delay: 0.3s; box-shadow: 0 4px 20px rgba(0,0,0,0.02);">
                    <div class="flex items-center justify-between mb-2">
                        <span class="text-gray-500 text-sm group-hover:text-orange-600 transition-colors duration-300">Late & Absent</span>
                        <span class="w-10 h-10 bg-orange-50 rounded-lg flex items-center justify-center group-hover:bg-orange-100 group-hover:scale-110 transition-all duration-300">
                            <svg class="w-5 h-5 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                        </span>
                    </div>
                    <p class="text-3xl font-bold text-gray-800 group-hover:text-orange-600 transition-colors duration-300" x-text="counters.lateAbsent"></p>
                    <p class="text-xs text-red-500 mt-2">↑ 5% from yesterday</p>
                </div>

                <!-- Pending Requests -->
                <div class="bg-white rounded-xl p-6 card-hover animate-slide-in group" style="animation-delay: 0.4s; box-shadow: 0 4px 20px rgba(0,0,0,0.02);">
                    <div class="flex items-center justify-between mb-2">
                        <span class="text-gray-500 text-sm group-hover:text-purple-600 transition-colors duration-300">Pending Requests</span>
                        <span class="w-10 h-10 bg-purple-50 rounded-lg flex items-center justify-center group-hover:bg-purple-100 group-hover:scale-110 transition-all duration-300">
                            <svg class="w-5 h-5 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                            </svg>
                        </span>
                    </div>
                    <p class="text-3xl font-bold text-gray-800 group-hover:text-purple-600 transition-colors duration-300" x-text="counters.pendingRequests"></p>
                    <p class="text-xs text-yellow-500 mt-2">Requires attention</p>
                </div>
            </div>

            <!-- Two Column Layout -->
            <div class="grid grid-cols-3 gap-6">
                <!-- Left Column (2/3 width) -->
                <div class="col-span-2 space-y-6">
                    <!-- Today's Attendance Summary -->
                    <div class="bg-white rounded-xl p-6 animate-slide-in group" style="animation-delay: 0.5s; box-shadow: 0 4px 20px rgba(0,0,0,0.02);">
                        <div class="flex items-center justify-between mb-6">
                            <div>
                                <h2 class="text-lg font-semibold text-gray-800 group-hover:text-blue-600 transition-colors duration-300">Today's Attendance Summary</h2>
                                <p class="text-sm text-gray-500">{{ date('l, F j, Y') }}</p>
                            </div>
                            <div class="relative" x-data="{ open: false }">
                                <button 
                                    @click="open = !open" 
                                    class="flex items-center space-x-2 px-4 py-2 bg-gray-50 rounded-lg text-sm text-gray-600 hover:bg-gray-100 transition-all duration-300 min-w-[160px]"
                                >
                                    <span x-text="selectedDepartment" class="group-hover:text-blue-600 transition-colors duration-300"></span>
                                    <svg class="w-4 h-4 transition-all duration-300 ml-auto" :class="{ 'rotate-180': open }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                                    </svg>
                                </button>
                                <div 
                                    x-show="open" 
                                    @click.away="open = false"
                                    x-cloak
                                    class="absolute right-0 mt-2 w-48 bg-white rounded-lg shadow-lg border border-gray-100 py-1 z-10"
                                    x-transition:enter="transition ease-out duration-200"
                                    x-transition:enter-start="opacity-0 -translate-y-2 scale-95"
                                    x-transition:enter-end="opacity-100 translate-y-0 scale-100"
                                    x-transition:leave="transition ease-in duration-150"
                                    x-transition:leave-start="opacity-100 translate-y-0 scale-100"
                                    x-transition:leave-end="opacity-0 -translate-y-2 scale-95"
                                >
                                    @foreach(['All Departments' => 'All', 'IT' => '45', 'Finance' => '32', 'Nursing' => '78', 'HR' => '18', 'Administration' => '15'] as $dept => $count)
                                    <a href="#" class="block px-4 py-2 text-sm text-gray-700 hover:bg-blue-50 hover:text-blue-600 transition-all duration-200 hover:translate-x-1" 
                                       @click.prevent="selectedDepartment = '{{ $dept }}'; open = false">
                                        <span class="flex items-center">
                                            <span>{{ $dept }}</span>
                                            <span class="ml-auto text-xs text-gray-400">{{ $count }}</span>
                                        </span>
                                    </a>
                                    @endforeach
                                </div>
                            </div>
                        </div>

                        <!-- Summary Boxes -->
                        <div class="grid grid-cols-4 gap-4 mb-6">
                            <div class="bg-green-50 rounded-lg p-4 transform transition-all duration-300 hover:scale-105 hover:shadow-lg hover:bg-green-100 group">
                                <p class="text-sm text-green-600 font-medium">Present</p>
                                <p class="text-2xl font-bold text-gray-800 group-hover:text-green-600 transition-colors duration-300" x-text="currentData.present"></p>
                            </div>
                            <div class="bg-orange-50 rounded-lg p-4 transform transition-all duration-300 hover:scale-105 hover:shadow-lg hover:bg-orange-100 group">
                                <p class="text-sm text-orange-600 font-medium">Late</p>
                                <p class="text-2xl font-bold text-gray-800 group-hover:text-orange-600 transition-colors duration-300" x-text="currentData.late"></p>
                            </div>
                            <div class="bg-red-50 rounded-lg p-4 transform transition-all duration-300 hover:scale-105 hover:shadow-lg hover:bg-red-100 group">
                                <p class="text-sm text-red-600 font-medium">Absent</p>
                                <p class="text-2xl font-bold text-gray-800 group-hover:text-red-600 transition-colors duration-300" x-text="currentData.absent"></p>
                            </div>
                            <div class="bg-purple-50 rounded-lg p-4 transform transition-all duration-300 hover:scale-105 hover:shadow-lg hover:bg-purple-100 group">
                                <p class="text-sm text-purple-600 font-medium">On Leave</p>
                                <p class="text-2xl font-bold text-gray-800 group-hover:text-purple-600 transition-colors duration-300" x-text="currentData.on_leave"></p>
                            </div>
                        </div>

                        <!-- Department Progress Bars -->
                        <div class="space-y-4">
                            <template x-for="(dept, index) in currentData.departments" :key="index">
                                <div class="group">
                                    <div class="flex items-center justify-between mb-2">
                                        <span class="text-sm font-medium text-gray-600 group-hover:text-blue-600 transition-colors duration-300" x-text="dept.name"></span>
                                        <span class="text-sm font-medium text-gray-600" x-text="dept.present + '/' + dept.total"></span>
                                    </div>
                                    <div class="h-2 bg-gray-100 rounded-full overflow-hidden">
                                        <div 
                                            class="h-full rounded-full transition-all duration-1000 ease-out"
                                            :style="{ 
                                                width: dept.percentage + '%',
                                                background: dept.name === 'IT' ? 'linear-gradient(90deg, #3B82F6, #60A5FA)' : 
                                                           (dept.name === 'Finance' ? 'linear-gradient(90deg, #10B981, #34D399)' : 
                                                            dept.name === 'Nursing' ? 'linear-gradient(90deg, #8B5CF6, #A78BFA)' :
                                                            dept.name === 'HR' ? 'linear-gradient(90deg, #F59E0B, #FBBF24)' :
                                                            'linear-gradient(90deg, #EC4899, #F472B6)')
                                            }"
                                        ></div>
                                    </div>
                                </div>
                            </template>
                        </div>
                    </div>

                    <!-- Pending Requests -->
                    <div class="bg-white rounded-xl p-6 animate-slide-in" style="animation-delay: 0.6s; box-shadow: 0 4px 20px rgba(0,0,0,0.02);">
                        <h2 class="text-lg font-semibold text-gray-800 mb-4 hover:text-blue-600 transition-colors duration-300">Pending Requests</h2>
                        <div class="space-y-3">
                            <div class="flex items-center justify-between p-3 rounded-xl transition-all duration-300 hover:bg-blue-50 hover:shadow-lg hover:scale-[1.02] group">
                                <div class="flex items-center space-x-3">
                                    <div class="w-10 h-10 bg-gradient-to-br from-blue-500 to-blue-600 rounded-xl flex items-center justify-center text-white font-semibold transition-all duration-300 group-hover:scale-110">JD</div>
                                    <div>
                                        <p class="font-medium text-gray-800 group-hover:text-blue-600 transition-colors duration-300">John Doe</p>
                                        <p class="text-xs text-gray-500">Leave Request - 3 days</p>
                                    </div>
                                </div>
                                <div class="flex items-center space-x-2">
                                    <button class="px-4 py-2 bg-blue-600 text-white text-sm font-medium rounded-lg transition-all duration-300 hover:bg-blue-700 hover:scale-105">Approve</button>
                                    <button class="px-4 py-2 bg-red-600 text-white text-sm font-medium rounded-lg transition-all duration-300 hover:bg-red-700 hover:scale-105">Reject</button>
                                </div>
                            </div>
                            <div class="flex items-center justify-between p-3 rounded-xl transition-all duration-300 hover:bg-blue-50 hover:shadow-lg hover:scale-[1.02] group">
                                <div class="flex items-center space-x-3">
                                    <div class="w-10 h-10 bg-gradient-to-br from-green-500 to-green-600 rounded-xl flex items-center justify-center text-white font-semibold transition-all duration-300 group-hover:scale-110">MS</div>
                                    <div>
                                        <p class="font-medium text-gray-800 group-hover:text-blue-600 transition-colors duration-300">Maria Santos</p>
                                        <p class="text-xs text-gray-500">Overtime Request - 4 hours</p>
                                    </div>
                                </div>
                                <div class="flex items-center space-x-2">
                                    <button class="px-4 py-2 bg-blue-600 text-white text-sm font-medium rounded-lg transition-all duration-300 hover:bg-blue-700 hover:scale-105">Approve</button>
                                    <button class="px-4 py-2 bg-red-600 text-white text-sm font-medium rounded-lg transition-all duration-300 hover:bg-red-700 hover:scale-105">Reject</button>
                                </div>
                            </div>
                            <div class="flex items-center justify-between p-3 rounded-xl transition-all duration-300 hover:bg-blue-50 hover:shadow-lg hover:scale-[1.02] group">
                                <div class="flex items-center space-x-3">
                                    <div class="w-10 h-10 bg-gradient-to-br from-purple-500 to-purple-600 rounded-xl flex items-center justify-center text-white font-semibold transition-all duration-300 group-hover:scale-110">AR</div>
                                    <div>
                                        <p class="font-medium text-gray-800 group-hover:text-blue-600 transition-colors duration-300">Anna Reyes</p>
                                        <p class="text-xs text-gray-500">Reimbursement - $150.00</p>
                                    </div>
                                </div>
                                <div class="flex items-center space-x-2">
                                    <button class="px-4 py-2 bg-blue-600 text-white text-sm font-medium rounded-lg transition-all duration-300 hover:bg-blue-700 hover:scale-105">Approve</button>
                                    <button class="px-4 py-2 bg-red-600 text-white text-sm font-medium rounded-lg transition-all duration-300 hover:bg-red-700 hover:scale-105">Reject</button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Right Column (1/3 width) -->
                <div class="space-y-6">
                    <!-- Calendar -->
                    <div class="bg-white rounded-xl p-6 animate-slide-in group" style="animation-delay: 0.5s; box-shadow: 0 4px 20px rgba(0,0,0,0.02);">
                        <div class="flex items-center justify-between mb-4">
                            <h2 class="text-lg font-semibold text-gray-800 group-hover:text-blue-600 transition-colors duration-300">Calendar</h2>
                            <div class="flex items-center space-x-2">
                                <button class="p-1 hover:bg-gray-100 rounded-lg transition-all duration-300">
                                    <svg class="w-5 h-5 text-gray-600 hover:text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
                                    </svg>
                                </button>
                                <span class="text-sm font-medium">{{ date('F Y') }}</span>
                                <button class="p-1 hover:bg-gray-100 rounded-lg transition-all duration-300">
                                    <svg class="w-5 h-5 text-gray-600 hover:text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                                    </svg>
                                </button>
                            </div>
                        </div>
                        
                        <!-- Calendar Grid -->
                        <div class="grid grid-cols-7 gap-1 mb-2">
                            @foreach(['Su', 'Mo', 'Tu', 'We', 'Th', 'Fr', 'Sa'] as $day)
                            <div class="text-center text-xs font-medium text-gray-500 py-1">{{ $day }}</div>
                            @endforeach
                            
                            @php
                                $today = (int)date('j');
                                $daysInMonth = (int)date('t');
                                $firstDay = (int)date('w', strtotime(date('Y-m-01')));
                            @endphp
                            
                            @for($i = 0; $i < $firstDay; $i++)
                                <div></div>
                            @endfor
                            
                            @for($i = 1; $i <= $daysInMonth; $i++)
                            <button class="text-center text-sm py-2 rounded-lg transition-all duration-300 hover:bg-blue-50 hover:text-blue-600 {{ $i == $today ? 'bg-blue-600 text-white hover:bg-blue-700 hover:text-white' : 'text-gray-700' }}">
                                {{ $i }}
                            </button>
                            @endfor
                        </div>
                        
                        <!-- Today's Events -->
                        <div class="mt-4 pt-4 border-t border-gray-100 space-y-2">
                            <div class="flex items-center text-xs text-gray-500">
                                <span class="w-2 h-2 bg-green-500 rounded-full mr-2 animate-pulse flex-shrink-0"></span>
                                <span>Team Meeting • 10:00 AM</span>
                            </div>
                            <div class="flex items-center text-xs text-gray-500">
                                <span class="w-2 h-2 bg-blue-500 rounded-full mr-2 animate-pulse flex-shrink-0"></span>
                                <span>Training • 2:00 PM</span>
                            </div>
                        </div>
                    </div>

                    <!-- Upcoming Events -->
                    <div class="bg-white rounded-xl p-6 animate-slide-in" style="animation-delay: 0.6s; box-shadow: 0 4px 20px rgba(0,0,0,0.02);">
                        <h2 class="text-lg font-semibold text-gray-800 mb-4">Upcoming Events</h2>
                        <div class="space-y-3">
                            <div class="p-3 bg-gray-50 rounded-xl transition-all duration-300 hover:bg-blue-50 hover:shadow-lg hover:scale-[1.02] group">
                                <div class="flex items-start space-x-3">
                                    <div class="w-10 h-10 bg-blue-100 rounded-lg flex flex-col items-center justify-center flex-shrink-0">
                                        <span class="text-xs font-bold text-blue-600">MAR</span>
                                        <span class="text-sm font-bold text-blue-600">15</span>
                                    </div>
                                    <div>
                                        <p class="font-medium text-gray-800 group-hover:text-blue-600 transition-colors duration-300">Quarterly Meeting</p>
                                        <p class="text-xs text-gray-500 mt-1">10:00 AM - 11:30 AM</p>
                                        <p class="text-xs text-gray-400 mt-1">All Departments</p>
                                    </div>
                                </div>
                            </div>
                            <div class="p-3 bg-gray-50 rounded-xl transition-all duration-300 hover:bg-blue-50 hover:shadow-lg hover:scale-[1.02] group">
                                <div class="flex items-start space-x-3">
                                    <div class="w-10 h-10 bg-green-100 rounded-lg flex flex-col items-center justify-center flex-shrink-0">
                                        <span class="text-xs font-bold text-green-600">MAR</span>
                                        <span class="text-sm font-bold text-green-600">18</span>
                                    </div>
                                    <div>
                                        <p class="font-medium text-gray-800 group-hover:text-blue-600 transition-colors duration-300">IT Training</p>
                                        <p class="text-xs text-gray-500 mt-1">2:00 PM - 4:00 PM</p>
                                        <p class="text-xs text-gray-400 mt-1">IT Department</p>
                                    </div>
                                </div>
                            </div>
                            <div class="p-3 bg-gray-50 rounded-xl transition-all duration-300 hover:bg-blue-50 hover:shadow-lg hover:scale-[1.02] group">
                                <div class="flex items-start space-x-3">
                                    <div class="w-10 h-10 bg-purple-100 rounded-lg flex flex-col items-center justify-center flex-shrink-0">
                                        <span class="text-xs font-bold text-purple-600">MAR</span>
                                        <span class="text-sm font-bold text-purple-600">20</span>
                                    </div>
                                    <div>
                                        <p class="font-medium text-gray-800 group-hover:text-blue-600 transition-colors duration-300">Performance Review</p>
                                        <p class="text-xs text-gray-500 mt-1">9:30 AM - 11:00 AM</p>
                                        <p class="text-xs text-gray-400 mt-1">HR Department</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>
</div>

<style>
    @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap');
    
    * { font-family: 'Inter', sans-serif; }
    
    @keyframes fadeInUp {
        from { opacity: 0; transform: translateY(20px); }
        to { opacity: 1; transform: translateY(0); }
    }
    
    .animate-slide-in {
        animation: fadeInUp 0.6s ease-out forwards;
        opacity: 0;
    }
    
    .card-hover {
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    }
    
    .card-hover:hover {
        transform: translateY(-6px) scale(1.02);
        box-shadow: 0 25px 30px -10px rgba(0, 0, 0, 0.15);
    }

    ::-webkit-scrollbar { width: 8px; height: 8px; }
    ::-webkit-scrollbar-track { background: #f1f1f1; border-radius: 10px; }
    ::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 10px; }
    ::-webkit-scrollbar-thumb:hover { background: #94a3b8; }
    
    [x-cloak] { display: none !important; }
</style>
@endsection
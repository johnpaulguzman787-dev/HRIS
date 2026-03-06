@extends('layouts.app')

@section('title', 'Settings')

@section('content')
<div x-data="{ 
        sidebarCollapsed: localStorage.getItem('sidebarCollapsed') === 'true',
        employeesOpen: {{ in_array(request()->route()->getName(), ['employees.directory', 'employees.profile']) ? 'true' : 'false' }},
        attendanceOpen: false,
        requestsOpen: false,
        reportsOpen: false,
        hoveredItem: null,
        selectedRole: 'admin'
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
            <a href="{{ route('admin.dashboard') }}" 
                class="flex items-center space-x-3 px-4 py-3 rounded-xl transition-all duration-300 hover:bg-blue-50 group
                    {{ request()->routeIs('admin.dashboard') ? 'bg-blue-50 text-blue-600' : 'text-gray-600 hover:text-blue-600' }}"
            >
                <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path>
                </svg>
                <span x-show="!sidebarCollapsed" class="text-sm font-medium">Dashboard</span>
            </a>

            <!-- Employees Dropdown -->
            <div class="relative">
                <button 
                    @click="employeesOpen = !employeesOpen"
                    class="w-full flex items-center justify-between px-4 py-3 rounded-xl transition-all duration-300 group
                        {{ request()->routeIs('employees.*') ? 'bg-blue-50 text-blue-600' : 'text-gray-600 hover:text-blue-600 hover:bg-blue-50' }}"
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
                       class="block px-4 py-2 text-sm rounded-lg transition-all duration-200 hover:translate-x-1
                           {{ request()->routeIs('employees.directory') ? 'bg-blue-100 text-blue-700 font-semibold' : 'text-gray-600 hover:bg-blue-50 hover:text-blue-600' }}">
                        Employee Directory
                    </a>
                    <a href="{{ route('employees.profile') }}" 
                       class="block px-4 py-2 text-sm rounded-lg transition-all duration-200 hover:translate-x-1
                           {{ request()->routeIs('employees.profile') ? 'bg-blue-100 text-blue-700 font-semibold' : 'text-gray-600 hover:bg-blue-50 hover:text-blue-600' }}">
                        Employee Profile
                    </a>
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
                    class="flex items-center space-x-3 px-4 py-3 rounded-xl transition-all duration-300 group
                        {{ request()->routeIs('settings.index') ? 'bg-blue-50 text-blue-600' : 'text-gray-600 hover:bg-blue-50 hover:text-blue-600' }}"
                >
                    <svg class="w-5 h-5 flex-shrink-0 transition-all duration-300 group-hover:rotate-90 {{ request()->routeIs('settings.index') ? 'text-blue-600' : '' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                    </svg>
                    <span x-show="!sidebarCollapsed" class="text-sm font-medium">Settings</span>
                </a>
                <a href="{{ route('logout') }}" onclick="event.preventDefault(); document.getElementById('logout-form').submit();"
                    class="flex items-center space-x-3 px-4 py-3 rounded-xl text-gray-600 hover:bg-red-50 transition-all duration-300 hover:text-red-600 group">
                    <svg class="w-5 h-5 flex-shrink-0 transition-all duration-300 group-hover:translate-x-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path>
                    </svg>
                    <span x-show="!sidebarCollapsed" class="text-sm font-medium">Logout</span>
                </a>
                <form id="logout-form" action="{{ route('logout') }}" method="POST" class="hidden">
                    @csrf
                </form>
            </div>
        </nav>
        
        <!-- Collapse Button -->
        <button 
            @click="sidebarCollapsed = !sidebarCollapsed; localStorage.setItem('sidebarCollapsed', sidebarCollapsed)"
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
                    <h1 class="text-xl sm:text-2xl font-bold">Settings</h1>
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

        <!-- Settings Content -->
        <div class="p-8">
            <!-- Roles & Permissions Card -->
            <div class="bg-white rounded-2xl shadow-sm p-6 animate-slide-in" style="animation-delay: 0.1s;">
                <!-- Card Header -->
                <div class="flex items-center justify-between mb-6">
                    <h2 class="text-lg font-semibold text-gray-800 group-hover:text-blue-600 transition-colors duration-300">Roles & Permissions</h2>
                    <div class="relative">
                        <select 
                            x-model="selectedRole"
                            class="appearance-none bg-white border border-gray-300 rounded-lg pl-4 pr-10 py-2.5 text-sm font-medium text-gray-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent cursor-pointer hover:border-gray-400 transition-all duration-300 min-w-[180px]"
                        >
                            <option value="admin">Administrator</option>
                            <option value="hr_manager">HR Manager</option>
                            <option value="hr_staff">HR Staff</option>
                            <option value="department_head">Department Head</option>
                            <option value="supervisor">Supervisor</option>
                            <option value="employee">Employee</option>
                        </select>
                        <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-2 text-gray-700">
                            <svg class="w-4 h-4 fill-current transition-transform duration-300 group-hover:rotate-180" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20">
                                <path d="M9.293 12.95l.707.707L15.657 8l-1.414-1.414L10 10.828 5.757 6.586 4.343 8z"/>
                            </svg>
                        </div>
                    </div>
                </div>

                <!-- Permissions Table -->
                <div class="overflow-x-auto">
                    <table class="w-full divide-y divide-gray-200">
                        <thead class="bg-gray-100">
                            <tr>
                                <th scope="col" class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Module / Feature</th>
                                <th scope="col" class="px-6 py-4 text-center text-xs font-semibold text-gray-600 uppercase tracking-wider">View</th>
                                <th scope="col" class="px-6 py-4 text-center text-xs font-semibold text-gray-600 uppercase tracking-wider">Create</th>
                                <th scope="col" class="px-6 py-4 text-center text-xs font-semibold text-gray-600 uppercase tracking-wider">Edit</th>
                                <th scope="col" class="px-6 py-4 text-center text-xs font-semibold text-gray-600 uppercase tracking-wider">Archive</th>
                                <th scope="col" class="px-6 py-4 text-center text-xs font-semibold text-gray-600 uppercase tracking-wider">Import</th>
                                <th scope="col" class="px-6 py-4 text-center text-xs font-semibold text-gray-600 uppercase tracking-wider">Export</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <!-- Employee Management Row -->
                            <tr class="hover:bg-gray-50 transition-all duration-200 group">
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 group-hover:text-blue-600 transition-colors duration-200">Employee Management</td>
                                <td class="px-6 py-4 whitespace-nowrap text-center"><input type="checkbox" class="w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 rounded focus:ring-blue-500 focus:ring-2 transition-all duration-200 hover:scale-110 cursor-pointer" checked></td>
                                <td class="px-6 py-4 whitespace-nowrap text-center"><input type="checkbox" class="w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 rounded focus:ring-blue-500 focus:ring-2 transition-all duration-200 hover:scale-110 cursor-pointer" checked></td>
                                <td class="px-6 py-4 whitespace-nowrap text-center"><input type="checkbox" class="w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 rounded focus:ring-blue-500 focus:ring-2 transition-all duration-200 hover:scale-110 cursor-pointer" checked></td>
                                <td class="px-6 py-4 whitespace-nowrap text-center"><input type="checkbox" class="w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 rounded focus:ring-blue-500 focus:ring-2 transition-all duration-200 hover:scale-110 cursor-pointer"></td>
                                <td class="px-6 py-4 whitespace-nowrap text-center"><input type="checkbox" class="w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 rounded focus:ring-blue-500 focus:ring-2 transition-all duration-200 hover:scale-110 cursor-pointer" checked></td>
                                <td class="px-6 py-4 whitespace-nowrap text-center"><input type="checkbox" class="w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 rounded focus:ring-blue-500 focus:ring-2 transition-all duration-200 hover:scale-110 cursor-pointer" checked></td>
                            </tr>
                            <!-- Time & Attendance Row -->
                            <tr class="hover:bg-gray-50 transition-all duration-200 group">
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 group-hover:text-blue-600 transition-colors duration-200">Time & Attendance</td>
                                <td class="px-6 py-4 whitespace-nowrap text-center"><input type="checkbox" class="w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 rounded focus:ring-blue-500 focus:ring-2 transition-all duration-200 hover:scale-110 cursor-pointer" checked></td>
                                <td class="px-6 py-4 whitespace-nowrap text-center"><input type="checkbox" class="w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 rounded focus:ring-blue-500 focus:ring-2 transition-all duration-200 hover:scale-110 cursor-pointer"></td>
                                <td class="px-6 py-4 whitespace-nowrap text-center"><input type="checkbox" class="w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 rounded focus:ring-blue-500 focus:ring-2 transition-all duration-200 hover:scale-110 cursor-pointer" checked></td>
                                <td class="px-6 py-4 whitespace-nowrap text-center"><input type="checkbox" class="w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 rounded focus:ring-blue-500 focus:ring-2 transition-all duration-200 hover:scale-110 cursor-pointer"></td>
                                <td class="px-6 py-4 whitespace-nowrap text-center"><input type="checkbox" class="w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 rounded focus:ring-blue-500 focus:ring-2 transition-all duration-200 hover:scale-110 cursor-pointer"></td>
                                <td class="px-6 py-4 whitespace-nowrap text-center"><input type="checkbox" class="w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 rounded focus:ring-blue-500 focus:ring-2 transition-all duration-200 hover:scale-110 cursor-pointer" checked></td>
                            </tr>
                            <!-- Leave Management Row -->
                            <tr class="hover:bg-gray-50 transition-all duration-200 group">
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 group-hover:text-blue-600 transition-colors duration-200">Leave Management</td>
                                <td class="px-6 py-4 whitespace-nowrap text-center"><input type="checkbox" class="w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 rounded focus:ring-blue-500 focus:ring-2 transition-all duration-200 hover:scale-110 cursor-pointer" checked></td>
                                <td class="px-6 py-4 whitespace-nowrap text-center"><input type="checkbox" class="w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 rounded focus:ring-blue-500 focus:ring-2 transition-all duration-200 hover:scale-110 cursor-pointer" checked></td>
                                <td class="px-6 py-4 whitespace-nowrap text-center"><input type="checkbox" class="w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 rounded focus:ring-blue-500 focus:ring-2 transition-all duration-200 hover:scale-110 cursor-pointer" checked></td>
                                <td class="px-6 py-4 whitespace-nowrap text-center"><input type="checkbox" class="w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 rounded focus:ring-blue-500 focus:ring-2 transition-all duration-200 hover:scale-110 cursor-pointer"></td>
                                <td class="px-6 py-4 whitespace-nowrap text-center"><input type="checkbox" class="w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 rounded focus:ring-blue-500 focus:ring-2 transition-all duration-200 hover:scale-110 cursor-pointer"></td>
                                <td class="px-6 py-4 whitespace-nowrap text-center"><input type="checkbox" class="w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 rounded focus:ring-blue-500 focus:ring-2 transition-all duration-200 hover:scale-110 cursor-pointer" checked></td>
                            </tr>
                            <!-- Payroll Processing Row -->
                            <tr class="hover:bg-gray-50 transition-all duration-200 group">
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 group-hover:text-blue-600 transition-colors duration-200">Payroll Processing</td>
                                <td class="px-6 py-4 whitespace-nowrap text-center"><input type="checkbox" class="w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 rounded focus:ring-blue-500 focus:ring-2 transition-all duration-200 hover:scale-110 cursor-pointer"></td>
                                <td class="px-6 py-4 whitespace-nowrap text-center"><input type="checkbox" class="w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 rounded focus:ring-blue-500 focus:ring-2 transition-all duration-200 hover:scale-110 cursor-pointer"></td>
                                <td class="px-6 py-4 whitespace-nowrap text-center"><input type="checkbox" class="w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 rounded focus:ring-blue-500 focus:ring-2 transition-all duration-200 hover:scale-110 cursor-pointer"></td>
                                <td class="px-6 py-4 whitespace-nowrap text-center"><input type="checkbox" class="w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 rounded focus:ring-blue-500 focus:ring-2 transition-all duration-200 hover:scale-110 cursor-pointer"></td>
                                <td class="px-6 py-4 whitespace-nowrap text-center"><input type="checkbox" class="w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 rounded focus:ring-blue-500 focus:ring-2 transition-all duration-200 hover:scale-110 cursor-pointer"></td>
                                <td class="px-6 py-4 whitespace-nowrap text-center"><input type="checkbox" class="w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 rounded focus:ring-blue-500 focus:ring-2 transition-all duration-200 hover:scale-110 cursor-pointer"></td>
                            </tr>
                            <!-- Reports & Analytics Row -->
                            <tr class="hover:bg-gray-50 transition-all duration-200 group">
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 group-hover:text-blue-600 transition-colors duration-200">Reports & Analytics</td>
                                <td class="px-6 py-4 whitespace-nowrap text-center"><input type="checkbox" class="w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 rounded focus:ring-blue-500 focus:ring-2 transition-all duration-200 hover:scale-110 cursor-pointer" checked></td>
                                <td class="px-6 py-4 whitespace-nowrap text-center"><input type="checkbox" class="w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 rounded focus:ring-blue-500 focus:ring-2 transition-all duration-200 hover:scale-110 cursor-pointer"></td>
                                <td class="px-6 py-4 whitespace-nowrap text-center"><input type="checkbox" class="w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 rounded focus:ring-blue-500 focus:ring-2 transition-all duration-200 hover:scale-110 cursor-pointer"></td>
                                <td class="px-6 py-4 whitespace-nowrap text-center"><input type="checkbox" class="w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 rounded focus:ring-blue-500 focus:ring-2 transition-all duration-200 hover:scale-110 cursor-pointer"></td>
                                <td class="px-6 py-4 whitespace-nowrap text-center"><input type="checkbox" class="w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 rounded focus:ring-blue-500 focus:ring-2 transition-all duration-200 hover:scale-110 cursor-pointer" checked></td>
                                <td class="px-6 py-4 whitespace-nowrap text-center"><input type="checkbox" class="w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 rounded focus:ring-blue-500 focus:ring-2 transition-all duration-200 hover:scale-110 cursor-pointer" checked></td>
                            </tr>
                            <!-- System Settings Row -->
                            <tr class="hover:bg-gray-50 transition-all duration-200 group">
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 group-hover:text-blue-600 transition-colors duration-200">System Settings</td>
                                <td class="px-6 py-4 whitespace-nowrap text-center"><input type="checkbox" class="w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 rounded focus:ring-blue-500 focus:ring-2 transition-all duration-200 hover:scale-110 cursor-pointer"></td>
                                <td class="px-6 py-4 whitespace-nowrap text-center"><input type="checkbox" class="w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 rounded focus:ring-blue-500 focus:ring-2 transition-all duration-200 hover:scale-110 cursor-pointer"></td>
                                <td class="px-6 py-4 whitespace-nowrap text-center"><input type="checkbox" class="w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 rounded focus:ring-blue-500 focus:ring-2 transition-all duration-200 hover:scale-110 cursor-pointer"></td>
                                <td class="px-6 py-4 whitespace-nowrap text-center"><input type="checkbox" class="w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 rounded focus:ring-blue-500 focus:ring-2 transition-all duration-200 hover:scale-110 cursor-pointer"></td>
                                <td class="px-6 py-4 whitespace-nowrap text-center"><input type="checkbox" class="w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 rounded focus:ring-blue-500 focus:ring-2 transition-all duration-200 hover:scale-110 cursor-pointer"></td>
                                <td class="px-6 py-4 whitespace-nowrap text-center"><input type="checkbox" class="w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 rounded focus:ring-blue-500 focus:ring-2 transition-all duration-200 hover:scale-110 cursor-pointer"></td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Bottom Actions -->
                <div class="flex items-center justify-end space-x-3 mt-6 pt-4 border-t border-gray-200">
                    <button class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-all duration-300 hover:scale-105">
                        Cancel
                    </button>
                    <button class="px-4 py-2 text-sm font-medium text-white bg-blue-600 border border-transparent rounded-lg hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-all duration-300 hover:scale-105 hover:shadow-lg">
                        Save Changes
                    </button>
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

    ::-webkit-scrollbar { width: 8px; height: 8px; }
    ::-webkit-scrollbar-track { background: #f1f1f1; border-radius: 10px; }
    ::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 10px; }
    ::-webkit-scrollbar-thumb:hover { background: #94a3b8; }
    
    [x-cloak] { display: none !important; }
</style>
@endsection
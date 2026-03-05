@extends('layouts.app')

@section('title', 'Employee Profile - Medisource HRMS')

@section('content')
<div x-data="{
    sidebarCollapsed: localStorage.getItem('sidebarCollapsed') === 'true',
    employeesOpen: true,
    attendanceOpen: false,
    requestsOpen: false,
    reportsOpen: false,

    employee: {
        initials: 'JD',
        full_name: 'John Doe',
        job_title: 'System Administrator',
        email: 'johndoe@gmail.com',
        contact: '09287319873871',
        address: 'Urdaneta City, Pangasinan, Philippines',
        dob: '01/02/2000',
        gender: 'Male',
        status: 'Active',
        department: 'IT/Information Technology',
        employment_type: 'Full-time',
        employment_status: 'Active',
        contract_period: 'Indefinite',
        start_date: '01/01/2026',
        end_date: '',
    },

    documents: [
        { name: 'contract.pdf' },
        { name: 'resume.pdf' },
    ],

    // Document Preview Modal
    showDocModal: false,
    activeDoc: null,
    openDoc(doc) {
        this.activeDoc = doc;
        this.showDocModal = true;
        document.body.style.overflow = 'hidden';
    },
    closeDoc() {
        this.showDocModal = false;
        document.body.style.overflow = '';
        setTimeout(() => { this.activeDoc = null; }, 300);
    }
}" class="flex h-screen overflow-hidden bg-gray-50">

    {{-- ===================== SIDEBAR ===================== --}}
    <aside
        class="bg-white border-r border-gray-200 h-screen fixed left-0 top-0 overflow-y-auto z-50"
        :class="sidebarCollapsed ? 'w-20' : 'w-64'"
        style="box-shadow: 4px 0 10px rgba(0,0,0,0.02); transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);">

        <!-- Logo -->
        <div class="p-6 border-b border-gray-100 relative overflow-hidden group" :class="sidebarCollapsed ? 'text-center' : ''">
            <h1 class="font-bold text-blue-600 relative z-10 transition-all duration-300" :class="sidebarCollapsed ? 'text-sm' : 'text-xl'">
                <span :class="sidebarCollapsed ? 'hidden' : 'inline'">MEDISOURCE</span>
                <span :class="sidebarCollapsed ? 'inline' : 'hidden'">MS</span>
            </h1>
            <div class="absolute inset-0 bg-gradient-to-r from-blue-50 to-transparent opacity-0 group-hover:opacity-100 transition-opacity duration-500"></div>
        </div>

        <!-- User Card -->
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

        <!-- Nav -->
        <nav class="p-4 space-y-1">
            <!-- Dashboard -->
            <a href="{{ route('admin.dashboard') }}"
                class="flex items-center space-x-3 px-4 py-3 rounded-xl transition-all duration-300 hover:bg-blue-50 text-gray-600 hover:text-blue-600 group">
                <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path>
                </svg>
                <span x-show="!sidebarCollapsed" class="text-sm font-medium">Dashboard</span>
            </a>

            <!-- Employees Dropdown -->
            <div>
                <button @click="employeesOpen = !employeesOpen"
                    class="w-full flex items-center justify-between px-4 py-3 rounded-xl transition-all duration-300 bg-blue-50 text-blue-600">
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
                    <a href="{{ route('employees.profile') }}" class="block px-4 py-2 text-sm font-semibold text-blue-600 bg-blue-100 rounded-lg">Employee Profile</a>
                    <a href="#" class="block px-4 py-2 text-sm text-gray-600 hover:bg-blue-50 hover:text-blue-600 rounded-lg transition-all duration-200 hover:translate-x-1">Employee Documents</a>
                </div>
            </div>

            <!-- Time & Attendance -->
            <div>
                <button @click="attendanceOpen = !attendanceOpen"
                    class="w-full flex items-center justify-between px-4 py-3 rounded-xl transition-all duration-300 text-gray-600 hover:text-blue-600 hover:bg-blue-50">
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
                    x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0 -translate-y-4 scale-95" x-transition:enter-end="opacity-100 translate-y-0 scale-100"
                    x-transition:leave="transition ease-in duration-200" x-transition:leave-start="opacity-100 translate-y-0 scale-100" x-transition:leave-end="opacity-0 -translate-y-4 scale-95"
                    class="ml-11 mt-1 space-y-1">
                    <a href="#" class="block px-4 py-2 text-sm text-gray-600 hover:bg-blue-50 hover:text-blue-600 rounded-lg transition-all duration-200 hover:translate-x-1">Time In / Time Out</a>
                    <a href="#" class="block px-4 py-2 text-sm text-gray-600 hover:bg-blue-50 hover:text-blue-600 rounded-lg transition-all duration-200 hover:translate-x-1">Attendance Records</a>
                    <a href="#" class="block px-4 py-2 text-sm text-gray-600 hover:bg-blue-50 hover:text-blue-600 rounded-lg transition-all duration-200 hover:translate-x-1">Shift Scheduling</a>
                    <a href="#" class="block px-4 py-2 text-sm text-gray-600 hover:bg-blue-50 hover:text-blue-600 rounded-lg transition-all duration-200 hover:translate-x-1">Leave Management</a>
                </div>
            </div>

            <!-- Requests & Approval -->
            <div>
                <button @click="requestsOpen = !requestsOpen"
                    class="w-full flex items-center justify-between px-4 py-3 rounded-xl transition-all duration-300 text-gray-600 hover:text-blue-600 hover:bg-blue-50">
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
                    x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0 -translate-y-4 scale-95" x-transition:enter-end="opacity-100 translate-y-0 scale-100"
                    x-transition:leave="transition ease-in duration-200" x-transition:leave-start="opacity-100 translate-y-0 scale-100" x-transition:leave-end="opacity-0 -translate-y-4 scale-95"
                    class="ml-11 mt-1 space-y-1">
                    <a href="#" class="block px-4 py-2 text-sm text-gray-600 hover:bg-blue-50 hover:text-blue-600 rounded-lg transition-all duration-200 hover:translate-x-1">Leave Requests</a>
                    <a href="#" class="block px-4 py-2 text-sm text-gray-600 hover:bg-blue-50 hover:text-blue-600 rounded-lg transition-all duration-200 hover:translate-x-1">Overtime Requests</a>
                    <a href="#" class="block px-4 py-2 text-sm text-gray-600 hover:bg-blue-50 hover:text-blue-600 rounded-lg transition-all duration-200 hover:translate-x-1">Reimbursement</a>
                    <a href="#" class="block px-4 py-2 text-sm text-gray-600 hover:bg-blue-50 hover:text-blue-600 rounded-lg transition-all duration-200 hover:translate-x-1">Schedule Changes</a>
                </div>
            </div>

            <!-- Reports -->
            <div>
                <button @click="reportsOpen = !reportsOpen"
                    class="w-full flex items-center justify-between px-4 py-3 rounded-xl transition-all duration-300 text-gray-600 hover:text-blue-600 hover:bg-blue-50">
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
                    x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0 -translate-y-4 scale-95" x-transition:enter-end="opacity-100 translate-y-0 scale-100"
                    x-transition:leave="transition ease-in duration-200" x-transition:leave-start="opacity-100 translate-y-0 scale-100" x-transition:leave-end="opacity-0 -translate-y-4 scale-95"
                    class="ml-11 mt-1 space-y-1">
                    <a href="#" class="block px-4 py-2 text-sm text-gray-600 hover:bg-blue-50 hover:text-blue-600 rounded-lg transition-all duration-200 hover:translate-x-1">Attendance Reports</a>
                    <a href="#" class="block px-4 py-2 text-sm text-gray-600 hover:bg-blue-50 hover:text-blue-600 rounded-lg transition-all duration-200 hover:translate-x-1">Payroll Reports</a>
                    <a href="#" class="block px-4 py-2 text-sm text-gray-600 hover:bg-blue-50 hover:text-blue-600 rounded-lg transition-all duration-200 hover:translate-x-1">Contribution Reports</a>
                </div>
            </div>

            <!-- Others -->
            <div class="pt-4 mt-4 border-t border-gray-100 space-y-1">
                <a href="{{ route('settings.index') }}" class="flex items-center space-x-3 px-4 py-3 rounded-xl text-gray-600 hover:bg-blue-50 hover:text-blue-600 transition-all duration-300 group">
                    <svg class="w-5 h-5 flex-shrink-0 transition-all duration-300 group-hover:rotate-90" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                    </svg>
                    <span x-show="!sidebarCollapsed" class="text-sm font-medium">Settings</span>
                </a>
                <a href="{{ route('logout') }}" onclick="event.preventDefault(); document.getElementById('logout-form').submit();"
                    class="flex items-center space-x-3 px-4 py-3 rounded-xl text-gray-600 hover:bg-red-50 hover:text-red-600 transition-all duration-300 group">
                    <svg class="w-5 h-5 flex-shrink-0 transition-all duration-300 group-hover:translate-x-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path>
                    </svg>
                    <span x-show="!sidebarCollapsed" class="text-sm font-medium">Logout</span>
                </a>
                <form id="logout-form" action="{{ route('logout') }}" method="POST" class="hidden">@csrf</form>
            </div>
        </nav>

        <!-- Collapse Button -->
        <button @click="sidebarCollapsed = !sidebarCollapsed; localStorage.setItem('sidebarCollapsed', sidebarCollapsed)"
            class="absolute bottom-4 right-4 w-8 h-8 bg-gray-100 rounded-lg flex items-center justify-center text-gray-600 hover:bg-gray-200 transition-all duration-300 hover:scale-110">
            <svg class="w-4 h-4 transition-all duration-500" :class="{ 'rotate-180': sidebarCollapsed }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 19l-7-7 7-7m8 14l-7-7 7-7"></path>
            </svg>
        </button>
    </aside>

    {{-- ===================== MAIN CONTENT ===================== --}}
    <main class="flex-1 overflow-y-auto min-h-screen"
        :class="sidebarCollapsed ? 'ml-20' : 'ml-64'"
        style="transition: margin-left 0.3s cubic-bezier(0.4, 0, 0.2, 1);">

        <!-- Header -->
        <header class="bg-gradient-to-r from-blue-600 to-blue-700 text-white sticky top-0 z-10 shadow-lg">
            <div class="px-4 sm:px-6 lg:px-8 py-4 flex items-center justify-between">
                <div>
                    <h1 class="text-xl sm:text-2xl font-bold">Employee Profile</h1>
                    <p class="text-xs sm:text-sm text-blue-100 mt-0.5">View and manage employee information</p>
                </div>
                <div class="flex items-center space-x-4">
                    <button class="relative p-2 hover:bg-blue-500 rounded-lg transition-all duration-200">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"></path>
                        </svg>
                        <span class="absolute top-1 right-1 w-2 h-2 bg-red-400 rounded-full animate-ping"></span>
                        <span class="absolute top-1 right-1 w-2 h-2 bg-red-400 rounded-full"></span>
                    </button>
                    <div class="w-10 h-10 bg-white/20 rounded-lg flex items-center justify-center font-semibold cursor-pointer hover:bg-white/30 transition-all duration-200">JD</div>
                </div>
            </div>
        </header>

        <!-- Profile Content -->
        <div class="p-6 lg:p-8 space-y-6">

            {{-- ── Profile Card ── --}}
            <div class="profile-card bg-white rounded-2xl overflow-hidden" style="box-shadow: 0 4px 24px rgba(0,0,0,0.06);">

                {{--
                    FIXED: Banner height set to h-14 (56px) with -mt-8 offset
                    so the avatar partially overlaps but "John Doe" name sits fully below the blue.
                --}}
                <div class="h-14 bg-gradient-to-r from-blue-600 via-blue-500 to-indigo-500 relative">
                    <div class="absolute inset-0" style="background-image: radial-gradient(circle at 20% 50%, rgba(255,255,255,0.08) 0%, transparent 50%), radial-gradient(circle at 80% 20%, rgba(255,255,255,0.06) 0%, transparent 40%);"></div>
                    <div class="absolute inset-0 opacity-10" style="background-image: repeating-linear-gradient(45deg, transparent, transparent 15px, rgba(255,255,255,0.4) 15px, rgba(255,255,255,0.4) 16px);"></div>
                </div>

                <!-- Avatar + Name row — avatar uses negative margin to overlap banner -->
                <div class="px-8 pb-6">
                    <div class="flex items-end gap-5 -mt-8 mb-6">
                        <!-- Avatar -->
                        <div class="avatar-pop flex-shrink-0 w-24 h-24 rounded-2xl bg-gradient-to-br from-blue-500 to-blue-700 flex items-center justify-center text-white text-3xl font-bold shadow-xl" style="border: 4px solid white;"
                            x-text="employee.initials"></div>
                        <!-- Name — sits below the banner line, no overlap -->
                        <div class="pb-1 name-fade">
                            <h2 class="text-2xl font-bold text-gray-800 leading-tight" x-text="employee.full_name"></h2>
                            <p class="text-sm text-gray-500 mt-0.5" x-text="employee.job_title"></p>
                        </div>
                    </div>

                    <!-- Divider -->
                    <div class="border-t border-gray-100 mb-4"></div>

                    <!-- Info Grid -->
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-0">

                        <div class="info-row flex items-center gap-3 py-4 px-2 border-b border-gray-50 lg:border-b-0 lg:border-r border-r-gray-100">
                            <div class="w-9 h-9 rounded-xl bg-blue-50 flex items-center justify-center flex-shrink-0">
                                <svg class="w-4 h-4 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                                </svg>
                            </div>
                            <div class="min-w-0">
                                <p class="text-xs text-gray-400 font-medium uppercase tracking-wide">Email</p>
                                <p class="text-sm text-gray-700 font-semibold truncate" x-text="employee.email"></p>
                            </div>
                        </div>

                        <div class="info-row flex items-center gap-3 py-4 px-2 border-b border-gray-50 lg:border-b-0 lg:border-r border-r-gray-100 lg:pl-6">
                            <div class="w-9 h-9 rounded-xl bg-green-50 flex items-center justify-center flex-shrink-0">
                                <svg class="w-4 h-4 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/>
                                </svg>
                            </div>
                            <div>
                                <p class="text-xs text-gray-400 font-medium uppercase tracking-wide">Contact</p>
                                <p class="text-sm text-gray-700 font-semibold" x-text="employee.contact"></p>
                            </div>
                        </div>

                        <div class="info-row flex items-center gap-3 py-4 px-2 border-b border-gray-50 lg:border-b-0 lg:pl-6">
                            <div class="w-9 h-9 rounded-xl bg-purple-50 flex items-center justify-center flex-shrink-0">
                                <svg class="w-4 h-4 text-purple-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                </svg>
                            </div>
                            <div>
                                <p class="text-xs text-gray-400 font-medium uppercase tracking-wide">Date of Birth</p>
                                <p class="text-sm text-gray-700 font-semibold" x-text="employee.dob"></p>
                            </div>
                        </div>

                        <div class="info-row flex items-center gap-3 py-4 px-2 border-b border-gray-50 md:border-b-0 lg:border-r border-r-gray-100 lg:border-t border-t-gray-100">
                            <div class="w-9 h-9 rounded-xl bg-orange-50 flex items-center justify-center flex-shrink-0">
                                <svg class="w-4 h-4 text-orange-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                                </svg>
                            </div>
                            <div class="min-w-0">
                                <p class="text-xs text-gray-400 font-medium uppercase tracking-wide">Address</p>
                                <p class="text-sm text-gray-700 font-semibold truncate" x-text="employee.address"></p>
                            </div>
                        </div>

                        <div class="info-row flex items-center gap-3 py-4 px-2 border-b border-gray-50 md:border-b-0 lg:border-r border-r-gray-100 lg:border-t border-t-gray-100 lg:pl-6">
                            <div class="w-9 h-9 rounded-xl bg-pink-50 flex items-center justify-center flex-shrink-0">
                                <svg class="w-4 h-4 text-pink-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                                </svg>
                            </div>
                            <div>
                                <p class="text-xs text-gray-400 font-medium uppercase tracking-wide">Gender</p>
                                <p class="text-sm text-gray-700 font-semibold" x-text="employee.gender"></p>
                            </div>
                        </div>

                        <div class="info-row flex items-center gap-3 py-4 px-2 lg:border-t border-t-gray-100 lg:pl-6">
                            <div class="w-9 h-9 rounded-xl bg-green-50 flex items-center justify-center flex-shrink-0">
                                <svg class="w-4 h-4 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                            </div>
                            <div>
                                <p class="text-xs text-gray-400 font-medium uppercase tracking-wide">Status</p>
                                <span class="inline-flex items-center gap-1.5 px-3 py-0.5 rounded-full text-xs font-bold bg-green-100 text-green-700">
                                    <span class="w-1.5 h-1.5 rounded-full bg-green-500 animate-pulse inline-block"></span>
                                    <span x-text="employee.status"></span>
                                </span>
                            </div>
                        </div>

                    </div>
                </div>
            </div>

            {{-- ── Job Information ── --}}
            <div class="section-card bg-white rounded-2xl p-8" style="box-shadow: 0 4px 24px rgba(0,0,0,0.06);">
                <div class="flex items-center gap-3 mb-6">
                    <div class="w-1 h-6 bg-gradient-to-b from-blue-500 to-blue-700 rounded-full"></div>
                    <h3 class="text-lg font-bold text-gray-800">Job Information</h3>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-x-8 gap-y-6">
                    <div class="job-field group">
                        <p class="text-xs font-bold text-gray-400 uppercase tracking-widest mb-1.5">Department</p>
                        <p class="text-sm font-semibold text-gray-800 group-hover:text-blue-600 transition-colors duration-200" x-text="employee.department"></p>
                    </div>
                    <div class="job-field group">
                        <p class="text-xs font-bold text-gray-400 uppercase tracking-widest mb-1.5">Job Title</p>
                        <p class="text-sm font-semibold text-gray-800 group-hover:text-blue-600 transition-colors duration-200" x-text="employee.job_title"></p>
                    </div>
                    <div class="job-field group">
                        <p class="text-xs font-bold text-gray-400 uppercase tracking-widest mb-1.5">Employment Type</p>
                        <p class="text-sm font-semibold text-gray-800 group-hover:text-blue-600 transition-colors duration-200" x-text="employee.employment_type"></p>
                    </div>
                    <div class="job-field group">
                        <p class="text-xs font-bold text-gray-400 uppercase tracking-widest mb-1.5">Employment Status</p>
                        <span class="inline-flex items-center gap-1.5 px-3 py-0.5 rounded-full text-xs font-bold bg-green-100 text-green-700">
                            <span class="w-1.5 h-1.5 rounded-full bg-green-500 animate-pulse inline-block"></span>
                            <span x-text="employee.employment_status"></span>
                        </span>
                    </div>
                    <div class="job-field group">
                        <p class="text-xs font-bold text-gray-400 uppercase tracking-widest mb-1.5">Contract Period</p>
                        <p class="text-sm font-semibold text-gray-800 group-hover:text-blue-600 transition-colors duration-200" x-text="employee.contract_period"></p>
                    </div>
                    <div class="job-field group">
                        <p class="text-xs font-bold text-gray-400 uppercase tracking-widest mb-1.5">Start Date</p>
                        <p class="text-sm font-semibold text-gray-800 group-hover:text-blue-600 transition-colors duration-200" x-text="employee.start_date"></p>
                    </div>
                    <div class="job-field group">
                        <p class="text-xs font-bold text-gray-400 uppercase tracking-widest mb-1.5">End Date</p>
                        <p class="text-sm font-semibold text-gray-800 group-hover:text-blue-600 transition-colors duration-200" x-text="employee.end_date || '—'"></p>
                    </div>
                </div>
            </div>

            {{-- ── Documents ── --}}
            <div class="section-card bg-white rounded-2xl p-8" style="box-shadow: 0 4px 24px rgba(0,0,0,0.06);">
                <div class="flex items-center gap-3 mb-6">
                    <div class="w-1 h-6 bg-gradient-to-b from-blue-500 to-blue-700 rounded-full"></div>
                    <h3 class="text-lg font-bold text-gray-800">Documents</h3>
                </div>

                <div class="space-y-3">
                    <template x-for="(doc, index) in documents" :key="index">
                        <div class="doc-row flex items-center justify-between px-5 py-4 rounded-xl border border-gray-100 hover:border-blue-200 hover:bg-blue-50/60 transition-all duration-300 hover:shadow-md hover:-translate-y-0.5 group">
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 rounded-xl bg-red-50 flex items-center justify-center flex-shrink-0 group-hover:bg-red-100 group-hover:scale-110 transition-all duration-300">
                                    <svg class="w-5 h-5 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                    </svg>
                                </div>
                                <span class="text-sm font-semibold text-gray-700 group-hover:text-blue-700 transition-colors duration-200" x-text="doc.name"></span>
                            </div>
                            <div class="flex items-center gap-2">
                                <button @click="openDoc(doc)" class="px-4 py-1.5 text-xs font-semibold text-blue-600 bg-blue-50 border border-blue-100 rounded-lg hover:bg-blue-600 hover:text-white transition-all duration-200 hover:shadow-md">
                                    View
                                </button>
                                <button class="px-4 py-1.5 text-xs font-semibold text-gray-600 bg-gray-50 border border-gray-200 rounded-lg hover:bg-gray-700 hover:text-white transition-all duration-200 hover:shadow-md">
                                    Download
                                </button>
                            </div>
                        </div>
                    </template>
                </div>
            </div>

        </div>
    </main>

    {{-- ── Document Preview Modal ── --}}
    <div
        x-show="showDocModal"
        x-transition:enter="transition ease-out duration-300"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        x-transition:leave="transition ease-in duration-200"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
        class="fixed inset-0 z-[100] flex items-center justify-center p-4"
        style="display: none;"
        @keydown.escape.window="closeDoc()"
    >
        <!-- Backdrop -->
        <div class="absolute inset-0 bg-black/50 backdrop-blur-sm" @click="closeDoc()"></div>

        <!-- Modal Panel -->
        <div
            x-show="showDocModal"
            x-transition:enter="transition ease-out duration-300"
            x-transition:enter-start="opacity-0 scale-95 translate-y-4"
            x-transition:enter-end="opacity-100 scale-100 translate-y-0"
            x-transition:leave="transition ease-in duration-200"
            x-transition:leave-start="opacity-100 scale-100 translate-y-0"
            x-transition:leave-end="opacity-0 scale-95 translate-y-4"
            class="relative bg-white rounded-2xl shadow-2xl w-full max-w-2xl flex flex-col overflow-hidden"
            style="max-height: 90vh;"
        >
            <!-- Modal Header -->
            <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100 bg-gradient-to-r from-blue-600 to-blue-700">
                <div class="flex items-center gap-3">
                    <div class="w-9 h-9 rounded-xl bg-white/20 flex items-center justify-center flex-shrink-0">
                        <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                        </svg>
                    </div>
                    <div>
                        <p class="text-xs text-blue-200 font-medium">Document Preview</p>
                        <p class="text-sm font-bold text-white" x-text="activeDoc ? activeDoc.name : ''"></p>
                    </div>
                </div>
                <button @click="closeDoc()"
                    class="w-8 h-8 rounded-lg bg-white/20 hover:bg-white/30 flex items-center justify-center text-white transition-all duration-200 hover:scale-110">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>

            <!-- Modal Body — PDF preview area -->
            <div class="flex-1 overflow-auto bg-gray-100 flex flex-col items-center justify-center p-6" style="min-height: 420px;">
                <div class="w-full h-full flex flex-col items-center justify-center gap-4 modal-content-fade">
                    <div class="w-20 h-20 rounded-2xl bg-red-50 flex items-center justify-center shadow-md">
                        <svg class="w-10 h-10 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                        </svg>
                    </div>
                    <div class="text-center">
                        <p class="text-base font-bold text-gray-800" x-text="activeDoc ? activeDoc.name : ''"></p>
                        <p class="text-sm text-gray-400 mt-1">PDF Document</p>
                    </div>
                    <div class="bg-white rounded-xl px-6 py-4 shadow-sm border border-gray-200 text-center max-w-sm">
                        <p class="text-xs text-gray-500 leading-relaxed">
                            Preview is not available for this file type in demo mode.<br>
                            Use the <span class="font-semibold text-blue-600">Download</span> button to open the file.
                        </p>
                    </div>
                </div>
            </div>

            <!-- Modal Footer -->
            <div class="flex items-center justify-end gap-3 px-6 py-4 border-t border-gray-100 bg-gray-50">
                <button @click="closeDoc()"
                    class="px-5 py-2 text-sm font-semibold text-gray-600 bg-white border border-gray-200 rounded-xl hover:bg-gray-100 transition-all duration-200 hover:scale-105">
                    Close
                </button>
                <button class="px-5 py-2 text-sm font-semibold text-white bg-blue-600 rounded-xl hover:bg-blue-700 transition-all duration-200 hover:scale-105 hover:shadow-lg flex items-center gap-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                    </svg>
                    Download
                </button>
            </div>
        </div>
    </div>

</div>

<style>
    @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap');
    * { font-family: 'Inter', sans-serif; }

    /* ── Page entrance ── */
    .profile-card {
        animation: cardSlideUp 0.5s cubic-bezier(0.22, 1, 0.36, 1) both;
    }
    .section-card {
        animation: cardSlideUp 0.5s cubic-bezier(0.22, 1, 0.36, 1) both;
    }
    .section-card:nth-of-type(1) { animation-delay: 0.12s; }
    .section-card:nth-of-type(2) { animation-delay: 0.22s; }

    @keyframes cardSlideUp {
        from { opacity: 0; transform: translateY(28px); }
        to   { opacity: 1; transform: translateY(0); }
    }

    /* ── Avatar pop ── */
    .avatar-pop {
        animation: avatarBounce 0.7s 0.3s cubic-bezier(0.34, 1.56, 0.64, 1) both;
    }
    @keyframes avatarBounce {
        from { opacity: 0; transform: translateY(12px) scale(0.75); }
        to   { opacity: 1; transform: translateY(0) scale(1); }
    }

    /* ── Name fade in ── */
    .name-fade {
        animation: nameFade 0.5s 0.4s ease both;
    }
    @keyframes nameFade {
        from { opacity: 0; transform: translateX(-12px); }
        to   { opacity: 1; transform: translateX(0); }
    }

    /* ── Info rows stagger ── */
    .info-row { animation: rowFade 0.4s ease both; }
    .info-row:nth-child(1) { animation-delay: 0.38s; }
    .info-row:nth-child(2) { animation-delay: 0.44s; }
    .info-row:nth-child(3) { animation-delay: 0.50s; }
    .info-row:nth-child(4) { animation-delay: 0.56s; }
    .info-row:nth-child(5) { animation-delay: 0.62s; }
    .info-row:nth-child(6) { animation-delay: 0.68s; }

    /* ── Job fields stagger ── */
    .job-field { animation: rowFade 0.4s ease both; }
    .job-field:nth-child(1) { animation-delay: 0.18s; }
    .job-field:nth-child(2) { animation-delay: 0.24s; }
    .job-field:nth-child(3) { animation-delay: 0.30s; }
    .job-field:nth-child(4) { animation-delay: 0.36s; }
    .job-field:nth-child(5) { animation-delay: 0.42s; }
    .job-field:nth-child(6) { animation-delay: 0.48s; }
    .job-field:nth-child(7) { animation-delay: 0.54s; }

    /* ── Doc rows stagger ── */
    .doc-row { animation: rowFade 0.4s ease both; }
    .doc-row:nth-child(1) { animation-delay: 0.28s; }
    .doc-row:nth-child(2) { animation-delay: 0.38s; }

    @keyframes rowFade {
        from { opacity: 0; transform: translateY(12px); }
        to   { opacity: 1; transform: translateY(0); }
    }

    /* ── Info row hover ── */
    .info-row {
        border-radius: 10px;
        transition: background 0.2s ease, transform 0.2s ease;
    }
    .info-row:hover {
        background: #f0f7ff;
        transform: translateX(4px);
    }

    /* scrollbar */
    ::-webkit-scrollbar { width: 6px; height: 6px; }
    ::-webkit-scrollbar-track { background: #f1f1f1; border-radius: 10px; }
    ::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 10px; }
    ::-webkit-scrollbar-thumb:hover { background: #94a3b8; }

    [x-cloak] { display: none !important; }

    /* Modal content fade */
    .modal-content-fade {
        animation: modalContentFade 0.4s 0.15s ease both;
    }
    @keyframes modalContentFade {
        from { opacity: 0; transform: translateY(10px); }
        to   { opacity: 1; transform: translateY(0); }
    }
</style>
@endsection
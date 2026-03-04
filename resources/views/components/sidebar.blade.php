@props(['activeMenu' => 'dashboard'])

<aside 
    class="bg-white border-r border-gray-200 h-screen fixed left-0 top-0 overflow-y-auto sidebar-transition z-50"
    x-data="{ 
        sidebarCollapsed: localStorage.getItem('sidebarCollapsed') === 'true',
        employeesOpen: {{ in_array($activeMenu, ['employees', 'employees.directory']) ? 'true' : 'false' }},
        attendanceOpen: false,
        requestsOpen: false,
        reportsOpen: false
    }"
    x-init="$watch('sidebarCollapsed', value => localStorage.setItem('sidebarCollapsed', value))"
    :class="sidebarCollapsed ? 'w-20' : 'w-64'"
    style="box-shadow: 4px 0 20px rgba(0,0,0,0.03); transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);"
>
    <!-- Logo Section -->
    <div class="p-6 border-b border-gray-100" :class="sidebarCollapsed ? 'text-center' : ''">
        <h1 class="font-['Inter'] text-xl font-bold bg-gradient-to-r from-blue-600 to-blue-800 bg-clip-text text-transparent" :class="sidebarCollapsed ? 'text-sm' : 'text-xl'">
            <span :class="sidebarCollapsed ? 'hidden' : 'inline'">MEDISOURCE</span>
            <span :class="sidebarCollapsed ? 'inline' : 'hidden'">MS</span>
        </h1>
    </div>
    
    <!-- User Profile Card -->
    <div class="p-4 border-b border-gray-100" :class="sidebarCollapsed ? 'text-center' : 'flex items-center space-x-3'">
        <div class="w-10 h-10 bg-gradient-to-br from-blue-500 to-blue-700 rounded-xl flex items-center justify-center text-white font-['Inter'] font-semibold flex-shrink-0 shadow-md">
            <span>JD</span>
        </div>
        <div x-show="!sidebarCollapsed" class="overflow-hidden">
            <p class="font-['Inter'] font-semibold text-gray-800">John Doe</p>
            <p class="font-['Inter'] text-xs text-gray-500">System Administrator</p>
        </div>
    </div>
    
    <!-- Navigation Menu -->
    <nav class="p-4 space-y-1">
        <!-- Dashboard -->
        <a href="{{ route('admin.dashboard') }}" 
            class="flex items-center space-x-3 px-4 py-3 rounded-xl transition-all duration-200 group"
            :class="{
                'bg-blue-50 text-blue-600': '{{ $activeMenu }}' === 'dashboard',
                'text-gray-600 hover:bg-blue-50 hover:text-blue-600': '{{ $activeMenu }}' !== 'dashboard'
            }"
        >
            <svg class="w-5 h-5" :class="{ 'text-blue-600': '{{ $activeMenu }}' === 'dashboard' }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path>
            </svg>
            <span x-show="!sidebarCollapsed" class="font-['Inter'] text-sm font-medium">Dashboard</span>
        </a>

        <!-- Employees Dropdown -->
        <div class="relative">
            <button 
                @click="employeesOpen = !employeesOpen"
                class="w-full flex items-center justify-between px-4 py-3 rounded-xl transition-all duration-200 group"
                :class="{
                    'bg-blue-50 text-blue-600': employeesOpen || '{{ $activeMenu }}' === 'employees' || '{{ $activeMenu }}' === 'employees.directory',
                    'text-gray-600 hover:bg-blue-50 hover:text-blue-600': !(employeesOpen || '{{ $activeMenu }}' === 'employees' || '{{ $activeMenu }}' === 'employees.directory')
                }"
            >
                <div class="flex items-center space-x-3">
                    <svg class="w-5 h-5" :class="{ 'text-blue-600': employeesOpen || '{{ $activeMenu }}' === 'employees' || '{{ $activeMenu }}' === 'employees.directory' }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path>
                    </svg>
                    <span x-show="!sidebarCollapsed" class="font-['Inter'] text-sm font-medium">Employees</span>
                </div>
                <span x-show="!sidebarCollapsed">
                    <svg class="w-4 h-4 transition-transform duration-200" :class="{ 'rotate-180': employeesOpen }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                    </svg>
                </span>
            </button>
            
            <div x-show="employeesOpen && !sidebarCollapsed" 
                 x-transition:enter="transition ease-out duration-200"
                 x-transition:enter-start="opacity-0 transform -translate-y-2"
                 x-transition:enter-end="opacity-100 transform translate-y-0"
                 x-transition:leave="transition ease-in duration-150"
                 x-transition:leave-start="opacity-100 transform translate-y-0"
                 x-transition:leave-end="opacity-0 transform -translate-y-2"
                 class="ml-11 mt-1 space-y-1">
                <a href="{{ route('employees.directory') }}" 
                    class="block px-4 py-2 font-['Inter'] text-sm rounded-lg transition-all duration-200"
                    :class="{
                        'bg-blue-50 text-blue-600': '{{ request()->routeIs('employees.directory') ? 'true' : 'false' }}',
                        'text-gray-600 hover:bg-blue-50 hover:text-blue-600': !'{{ request()->routeIs('employees.directory') ? 'true' : 'false' }}'
                    }">
                    Employee Directory
                </a>
                <a href="#" class="block px-4 py-2 font-['Inter'] text-sm text-gray-600 hover:bg-blue-50 hover:text-blue-600 rounded-lg transition-all duration-200">
                    Employee Documents
                </a>
            </div>
        </div>

        <!-- Time & Attendance Dropdown -->
        <div class="relative">
            <button 
                @click="attendanceOpen = !attendanceOpen"
                class="w-full flex items-center justify-between px-4 py-3 rounded-xl transition-all duration-200 group"
                :class="{
                    'bg-blue-50 text-blue-600': attendanceOpen,
                    'text-gray-600 hover:bg-blue-50 hover:text-blue-600': !attendanceOpen
                }"
            >
                <div class="flex items-center space-x-3">
                    <svg class="w-5 h-5" :class="{ 'text-blue-600': attendanceOpen }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    <span x-show="!sidebarCollapsed" class="font-['Inter'] text-sm font-medium">Time & Attendance</span>
                </div>
                <span x-show="!sidebarCollapsed">
                    <svg class="w-4 h-4 transition-transform duration-200" :class="{ 'rotate-180': attendanceOpen }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                    </svg>
                </span>
            </button>
            
            <div x-show="attendanceOpen && !sidebarCollapsed" 
                 x-transition:enter="transition ease-out duration-200"
                 x-transition:enter-start="opacity-0 transform -translate-y-2"
                 x-transition:enter-end="opacity-100 transform translate-y-0"
                 x-transition:leave="transition ease-in duration-150"
                 x-transition:leave-start="opacity-100 transform translate-y-0"
                 x-transition:leave-end="opacity-0 transform -translate-y-2"
                 class="ml-11 mt-1 space-y-1">
                <a href="#" class="block px-4 py-2 font-['Inter'] text-sm text-gray-600 hover:bg-blue-50 hover:text-blue-600 rounded-lg transition-all duration-200">Time In / Time Out</a>
                <a href="#" class="block px-4 py-2 font-['Inter'] text-sm text-gray-600 hover:bg-blue-50 hover:text-blue-600 rounded-lg transition-all duration-200">Attendance Records</a>
                <a href="#" class="block px-4 py-2 font-['Inter'] text-sm text-gray-600 hover:bg-blue-50 hover:text-blue-600 rounded-lg transition-all duration-200">Shift Scheduling</a>
                <a href="#" class="block px-4 py-2 font-['Inter'] text-sm text-gray-600 hover:bg-blue-50 hover:text-blue-600 rounded-lg transition-all duration-200">Leave Management</a>
            </div>
        </div>

        <!-- Requests & Approval Dropdown -->
        <div class="relative">
            <button 
                @click="requestsOpen = !requestsOpen"
                class="w-full flex items-center justify-between px-4 py-3 rounded-xl transition-all duration-200 group"
                :class="{
                    'bg-blue-50 text-blue-600': requestsOpen,
                    'text-gray-600 hover:bg-blue-50 hover:text-blue-600': !requestsOpen
                }"
            >
                <div class="flex items-center space-x-3">
                    <svg class="w-5 h-5" :class="{ 'text-blue-600': requestsOpen }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                    </svg>
                    <span x-show="!sidebarCollapsed" class="font-['Inter'] text-sm font-medium">Requests & Approval</span>
                </div>
                <span x-show="!sidebarCollapsed">
                    <svg class="w-4 h-4 transition-transform duration-200" :class="{ 'rotate-180': requestsOpen }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                    </svg>
                </span>
            </button>
            
            <div x-show="requestsOpen && !sidebarCollapsed" 
                 x-transition:enter="transition ease-out duration-200"
                 x-transition:enter-start="opacity-0 transform -translate-y-2"
                 x-transition:enter-end="opacity-100 transform translate-y-0"
                 x-transition:leave="transition ease-in duration-150"
                 x-transition:leave-start="opacity-100 transform translate-y-0"
                 x-transition:leave-end="opacity-0 transform -translate-y-2"
                 class="ml-11 mt-1 space-y-1">
                <a href="#" class="block px-4 py-2 font-['Inter'] text-sm text-gray-600 hover:bg-blue-50 hover:text-blue-600 rounded-lg transition-all duration-200">Leave Requests</a>
                <a href="#" class="block px-4 py-2 font-['Inter'] text-sm text-gray-600 hover:bg-blue-50 hover:text-blue-600 rounded-lg transition-all duration-200">Overtime Requests</a>
                <a href="#" class="block px-4 py-2 font-['Inter'] text-sm text-gray-600 hover:bg-blue-50 hover:text-blue-600 rounded-lg transition-all duration-200">Reimbursement</a>
                <a href="#" class="block px-4 py-2 font-['Inter'] text-sm text-gray-600 hover:bg-blue-50 hover:text-blue-600 rounded-lg transition-all duration-200">Schedule Changes</a>
            </div>
        </div>

        <!-- Reports Dropdown -->
        <div class="relative">
            <button 
                @click="reportsOpen = !reportsOpen"
                class="w-full flex items-center justify-between px-4 py-3 rounded-xl transition-all duration-200 group"
                :class="{
                    'bg-blue-50 text-blue-600': reportsOpen,
                    'text-gray-600 hover:bg-blue-50 hover:text-blue-600': !reportsOpen
                }"
            >
                <div class="flex items-center space-x-3">
                    <svg class="w-5 h-5" :class="{ 'text-blue-600': reportsOpen }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                    </svg>
                    <span x-show="!sidebarCollapsed" class="font-['Inter'] text-sm font-medium">Reports</span>
                </div>
                <span x-show="!sidebarCollapsed">
                    <svg class="w-4 h-4 transition-transform duration-200" :class="{ 'rotate-180': reportsOpen }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                    </svg>
                </span>
            </button>
            
            <div x-show="reportsOpen && !sidebarCollapsed" 
                 x-transition:enter="transition ease-out duration-200"
                 x-transition:enter-start="opacity-0 transform -translate-y-2"
                 x-transition:enter-end="opacity-100 transform translate-y-0"
                 x-transition:leave="transition ease-in duration-150"
                 x-transition:leave-start="opacity-100 transform translate-y-0"
                 x-transition:leave-end="opacity-0 transform -translate-y-2"
                 class="ml-11 mt-1 space-y-1">
                <a href="#" class="block px-4 py-2 font-['Inter'] text-sm text-gray-600 hover:bg-blue-50 hover:text-blue-600 rounded-lg transition-all duration-200">Attendance Reports</a>
                <a href="#" class="block px-4 py-2 font-['Inter'] text-sm text-gray-600 hover:bg-blue-50 hover:text-blue-600 rounded-lg transition-all duration-200">Payroll Reports</a>
                <a href="#" class="block px-4 py-2 font-['Inter'] text-sm text-gray-600 hover:bg-blue-50 hover:text-blue-600 rounded-lg transition-all duration-200">Contribution Reports</a>
            </div>
        </div>
        
        <!-- Others Section -->
        <div class="pt-4 mt-4 border-t border-gray-100 space-y-1">
            <a href="#" class="flex items-center space-x-3 px-4 py-3 rounded-xl text-gray-600 hover:bg-blue-50 hover:text-blue-600 transition-all duration-200 group">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path>
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                </svg>
                <span x-show="!sidebarCollapsed" class="font-['Inter'] text-sm font-medium">Settings</span>
            </a>
            <a href="#" class="flex items-center space-x-3 px-4 py-3 rounded-xl text-gray-600 hover:bg-red-50 hover:text-red-600 transition-all duration-200 group">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path>
                </svg>
                <span x-show="!sidebarCollapsed" class="font-['Inter'] text-sm font-medium">Logout</span>
            </a>
        </div>
    </nav>
    
    <!-- Collapse Button -->
    <button 
        @click="sidebarCollapsed = !sidebarCollapsed" 
        class="absolute bottom-4 right-4 w-8 h-8 bg-gray-100 rounded-lg flex items-center justify-center text-gray-600 hover:bg-gray-200 transition-all duration-200 hover:scale-110"
    >
        <svg class="w-4 h-4 transition-transform duration-200" :class="{ 'rotate-180': sidebarCollapsed }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 19l-7-7 7-7m8 14l-7-7 7-7"></path>
        </svg>
    </button>
</aside>

<style>
    .rotate-180 {
        transform: rotate(180deg);
    }
</style>
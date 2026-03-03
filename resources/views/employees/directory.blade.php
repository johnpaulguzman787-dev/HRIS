@extends('layouts.app')

@section('title', 'Employees Directory - Medisource HRMS')

@section('content')
<div x-data="{ 
    sidebarCollapsed: localStorage.getItem('sidebarCollapsed') === 'true',
    showAddEmployee: false,
    showAddDepartment: false,
    showEmployeeDetails: false,
    selectedEmployee: null,
    isEditMode: false,
    searchQuery: '',
    employees: [
        { avatar: 'JD', name: 'John Doe', email: 'john.doe@medisource.com', department: 'IT', department_code: 'IT-001', job_title: 'Senior Developer', status: 'Active' },
        { avatar: 'MS', name: 'Maria Santos', email: 'maria.santos@medisource.com', department: 'Finance', department_code: 'FIN-002', job_title: 'Financial Analyst', status: 'Active' },
        { avatar: 'AR', name: 'Anna Reyes', email: 'anna.reyes@medisource.com', department: 'Nursing', department_code: 'NRS-003', job_title: 'Head Nurse', status: 'Active' },
        { avatar: 'MC', name: 'Mark Cruz', email: 'mark.cruz@medisource.com', department: 'IT', department_code: 'IT-004', job_title: 'System Administrator', status: 'Active' },
        { avatar: 'LV', name: 'Lisa Villanueva', email: 'lisa.villanueva@medisource.com', department: 'HR', department_code: 'HR-005', job_title: 'HR Manager', status: 'Active' },
        { avatar: 'CT', name: 'Carlos Tan', email: 'carlos.tan@medisource.com', department: 'Administration', department_code: 'ADM-006', job_title: 'Admin Officer', status: 'Active' },
        { avatar: 'JG', name: 'Julia Gomez', email: 'julia.gomez@medisource.com', department: 'Nursing', department_code: 'NRS-007', job_title: 'Staff Nurse', status: 'On Leave' },
        { avatar: 'RF', name: 'Robert Flores', email: 'robert.flores@medisource.com', department: 'Finance', department_code: 'FIN-008', job_title: 'Accountant', status: 'Active' },
    ],
    form: {
        first_name: '',
        last_name: '',
        mi: '',
        email: '',
        department: '',
        position: '',
        job_type: '',
        date_hired: '',
        contact_number: ''
    },
    departmentForm: {
        name: '',
        head: ''
    },
    get filteredEmployees() {
        if (!this.searchQuery.trim()) {
            return this.employees;
        }
        
        const query = this.searchQuery.toLowerCase().trim();
        return this.employees.filter(employee => {
            return (
                employee.name.toLowerCase().includes(query) ||
                employee.email.toLowerCase().includes(query) ||
                employee.department.toLowerCase().includes(query) ||
                employee.job_title.toLowerCase().includes(query) ||
                employee.department_code.toLowerCase().includes(query) ||
                employee.status.toLowerCase().includes(query)
            );
        });
    },
    get resultCount() {
        return this.filteredEmployees.length;
    },
    resetForm() {
        this.form = { first_name: '', last_name: '', mi: '', email: '', department: '', position: '', job_type: '', date_hired: '', contact_number: '' };
    },
    resetDepartmentForm() {
        this.departmentForm = { name: '', head: '' };
    },
    saveDepartment() {
        // Add your save logic here
        console.log('Department saved:', this.departmentForm);
        this.showAddDepartment = false;
        this.resetDepartmentForm();
        // Show success message or refresh department list
        alert('Department added successfully!');
    },
    viewEmployee(employee) {
        // Map the employee data to match the form fields
        this.selectedEmployee = {
            first_name: employee.name.split(' ')[0] || '',
            last_name: employee.name.split(' ').slice(1).join(' ') || '',
            mi: '',
            email: employee.email || '',
            department: employee.department || '',
            position: employee.job_title || '',
            job_type: 'Permanent',
            date_hired: '2024-01-15',
            contact_number: '090909090909'
        };
        this.isEditMode = false; // Default to view mode
        this.showEmployeeDetails = true;
        
        setTimeout(() => {
            document.body.style.overflow = 'hidden';
        }, 100);
    },
    enableEditMode() {
        this.isEditMode = true;
    },
    saveChanges() {
        // Here you can add your save logic
        // For now, just disable edit mode
        this.isEditMode = false;
        // You can show a success message here
        alert('Changes saved successfully!');
    },
    closeModal() {
        this.showEmployeeDetails = false;
        this.isEditMode = false;
        document.body.style.overflow = 'auto';
    },
    clearSearch() {
        this.searchQuery = '';
    }
}" class="flex h-screen overflow-hidden bg-gray-50" @keydown.escape.window="closeModal">
    
    <!-- Include Sidebar -->
    @include('components.sidebar', ['activeMenu' => 'employees'])

    <!-- Main Content -->
    <main class="flex-1 overflow-y-auto transition-all duration-300" 
          :class="sidebarCollapsed ? 'lg:ml-20' : 'lg:ml-64'">
        
        <!-- Top Header -->
        <header class="bg-gradient-to-r from-blue-600 to-blue-700 text-white sticky top-0 z-10 shadow-lg">
            <div class="px-4 sm:px-6 lg:px-8 py-4 flex items-center justify-between">
                <div>
                    <h1 class="text-xl sm:text-2xl font-bold">Employees</h1>
                    <p class="text-xs sm:text-sm text-blue-100 mt-1">Employee Directory</p>
                </div>
                <div class="flex items-center space-x-2 sm:space-x-4">
                    <button class="relative p-2 hover:bg-blue-500 rounded-lg transition-all duration-200">
                        <svg class="w-5 h-5 sm:w-6 sm:h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"></path>
                        </svg>
                        <span class="absolute top-1 right-1 w-2 h-2 bg-red-400 rounded-full animate-ping"></span>
                        <span class="absolute top-1 right-1 w-2 h-2 bg-red-400 rounded-full"></span>
                    </button>
                    <div class="w-8 h-8 sm:w-10 sm:h-10 bg-white/20 rounded-lg flex items-center justify-center">
                        <span class="text-sm sm:text-base font-semibold">JD</span>
                    </div>
                </div>
            </div>
        </header>

        <!-- Page Content -->
        <div class="p-4 sm:p-6 lg:p-8 page-fade-in">
            
            <!-- ADD DEPARTMENT MODAL - MATCHING EMPLOYEE DETAILS MODAL DESIGN -->
            <div 
                x-show="showAddDepartment"
                x-cloak
                class="fixed inset-0 z-50 flex items-center justify-center p-4"
                style="background: rgba(0,0,0,0.5); backdrop-filter: blur(8px);"
                @click.self="showAddDepartment = false; resetDepartmentForm()"
            >
                <div 
                    x-show="showAddDepartment"
                    x-transition:enter="transition-all duration-300 ease-out"
                    x-transition:enter-start="opacity-0 scale-95 translate-y-4"
                    x-transition:enter-end="opacity-100 scale-100 translate-y-0"
                    x-transition:leave="transition-all duration-200 ease-in"
                    x-transition:leave-start="opacity-100 scale-100 translate-y-0"
                    x-transition:leave-end="opacity-0 scale-95 translate-y-4"
                    class="bg-white rounded-3xl shadow-2xl w-full max-w-md overflow-hidden relative"
                    @click.stop
                >
                    <!-- Close (X) button inside a circle on top-right -->
                    <button 
                        @click="showAddDepartment = false; resetDepartmentForm()"
                        class="absolute top-6 right-6 w-10 h-10 flex items-center justify-center rounded-full border-2 border-gray-300 text-gray-500 hover:border-gray-600 hover:text-gray-700 hover:bg-gray-100 transition-all duration-200 z-10 bg-white"
                    >
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>

                    <!-- Modal Header with Title -->
                    <div class="px-8 pt-8 pb-2">
                        <h2 class="text-2xl font-semibold text-gray-800">Add Department</h2>
                    </div>

                    <!-- Form Layout -->
                    <div class="px-8 pb-6 space-y-5">
                        <!-- Department Name -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Department Name</label>
                            <input 
                                type="text" 
                                x-model="departmentForm.name"
                                placeholder="Enter department name"
                                class="w-full px-4 py-2.5 text-sm border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all duration-200"
                            >
                        </div>

                        <!-- Department Head -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Department Head</label>
                            <div class="relative">
                                <select 
                                    x-model="departmentForm.head"
                                    class="w-full px-4 py-2.5 text-sm border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent appearance-none bg-white transition-all duration-200"
                                >
                                    <option value="" disabled selected>Choose Department Head</option>
                                    <option value="John Doe">John Doe - IT Director</option>
                                    <option value="Maria Santos">Maria Santos - Finance Manager</option>
                                    <option value="Anna Reyes">Anna Reyes - Nursing Director</option>
                                    <option value="Lisa Villanueva">Lisa Villanueva - HR Manager</option>
                                    <option value="Carlos Tan">Carlos Tan - Admin Manager</option>
                                    <option value="Robert Flores">Robert Flores - Finance Supervisor</option>
                                </select>
                                <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-3 text-gray-500">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                                    </svg>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Modal Footer - Save button right-aligned (no border line) -->
                    <div class="px-8 py-5 flex justify-end">
                        <button 
                            @click="saveDepartment"
                            class="px-6 py-2.5 bg-blue-600 text-white text-sm font-medium rounded-lg hover:bg-blue-700 hover:shadow-md transition-all duration-200"
                        >
                            Save
                        </button>
                    </div>
                </div>
            </div>

            <!-- ADD EMPLOYEE MODAL -->
            <div 
                x-show="showAddEmployee"
                x-cloak
                class="fixed inset-0 z-50 flex items-center justify-center p-4"
                style="background: rgba(0,0,0,0.5); backdrop-filter: blur(8px);"
                @click.self="showAddEmployee = false; resetForm()"
            >
                <div 
                    x-show="showAddEmployee"
                    x-transition:enter="transition-all duration-300 ease-out"
                    x-transition:enter-start="opacity-0 scale-95 translate-y-4"
                    x-transition:enter-end="opacity-100 scale-100 translate-y-0"
                    x-transition:leave="transition-all duration-200 ease-in"
                    x-transition:leave-start="opacity-100 scale-100 translate-y-0"
                    x-transition:leave-end="opacity-0 scale-95 translate-y-4"
                    class="bg-white rounded-xl shadow-2xl w-full max-w-2xl overflow-hidden"
                    @click.stop
                >
                    <!-- Modal Header with X close button -->
                    <div class="px-6 py-4 flex items-center justify-between">
                        <h2 class="text-lg font-semibold text-gray-800">Add Employee</h2>
                        <button 
                            @click="showAddEmployee = false; resetForm()"
                            class="w-8 h-8 flex items-center justify-center rounded-full border-2 border-gray-400 text-gray-400 hover:border-gray-600 hover:text-gray-600 transition-all duration-200"
                        >
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"></path>
                            </svg>
                        </button>
                    </div>

                    <!-- Modal Body -->
                    <div class="px-6 pb-4 space-y-4">
                        <!-- Name Fields -->
                        <div>
                            <label class="block text-sm font-medium text-gray-800 mb-1">Name</label>
                            <div class="grid grid-cols-12 gap-2">
                                <div class="col-span-5">
                                    <input 
                                        type="text" 
                                        x-model="form.first_name"
                                        placeholder="First Name" 
                                        class="w-full px-3 py-2 text-sm border border-gray-300 rounded-md focus:outline-none focus:ring-1 focus:ring-blue-500 focus:border-blue-500"
                                    >
                                </div>
                                <div class="col-span-5">
                                    <input 
                                        type="text" 
                                        x-model="form.last_name"
                                        placeholder="Last Name" 
                                        class="w-full px-3 py-2 text-sm border border-gray-300 rounded-md focus:outline-none focus:ring-1 focus:ring-blue-500 focus:border-blue-500"
                                    >
                                </div>
                                <div class="col-span-2">
                                    <input 
                                        type="text" 
                                        x-model="form.mi"
                                        placeholder="MI" 
                                        maxlength="2"
                                        class="w-full px-2 py-2 text-sm border border-gray-300 rounded-md focus:outline-none focus:ring-1 focus:ring-blue-500 focus:border-blue-500 text-center uppercase"
                                    >
                                </div>
                            </div>
                        </div>

                        <!-- Email -->
                        <div>
                            <label class="block text-sm font-medium text-gray-800 mb-1">Email</label>
                            <input 
                                type="email" 
                                x-model="form.email"
                                placeholder="Enter email" 
                                class="w-full px-3 py-2 text-sm border border-gray-300 rounded-md focus:outline-none focus:ring-1 focus:ring-blue-500 focus:border-blue-500"
                            >
                        </div>

                        <!-- Department & Position - Side by Side -->
                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="block text-sm font-medium text-gray-800 mb-1">Department</label>
                                <div class="relative">
                                    <select 
                                        x-model="form.department"
                                        class="w-full px-3 py-2 text-sm border border-gray-300 rounded-md focus:outline-none focus:ring-1 focus:ring-blue-500 focus:border-blue-500 appearance-none bg-white"
                                    >
                                        <option value="" disabled selected>Choose department</option>
                                        <option value="IT">Information Technology</option>
                                        <option value="Finance">Finance</option>
                                        <option value="Nursing">Nursing</option>
                                        <option value="HR">Human Resources</option>
                                        <option value="Administration">Administration</option>
                                    </select>
                                    <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-2 text-gray-500">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                                        </svg>
                                    </div>
                                </div>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-800 mb-1">Position</label>
                                <div class="relative">
                                    <select 
                                        x-model="form.position"
                                        class="w-full px-3 py-2 text-sm border border-gray-300 rounded-md focus:outline-none focus:ring-1 focus:ring-blue-500 focus:border-blue-500 appearance-none bg-white"
                                    >
                                        <option value="" disabled selected>Choose position</option>
                                        <option value="Developer">Developer</option>
                                        <option value="Senior Developer">Senior Developer</option>
                                        <option value="Financial Analyst">Financial Analyst</option>
                                        <option value="Accountant">Accountant</option>
                                        <option value="Head Nurse">Head Nurse</option>
                                        <option value="Staff Nurse">Staff Nurse</option>
                                        <option value="HR Manager">HR Manager</option>
                                        <option value="Admin Officer">Admin Officer</option>
                                    </select>
                                    <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-2 text-gray-500">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                                        </svg>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Job Type -->
                        <div>
                            <label class="block text-sm font-medium text-gray-800 mb-1">Job Type</label>
                            <div class="relative">
                                <select 
                                    x-model="form.job_type"
                                    class="w-full px-3 py-2 text-sm border border-gray-300 rounded-md focus:outline-none focus:ring-1 focus:ring-blue-500 focus:border-blue-500 appearance-none bg-white"
                                >
                                    <option value="" disabled selected>Choose job type</option>
                                    <option value="Full-time">Full-time</option>
                                    <option value="Part-time">Part-time</option>
                                    <option value="Contractual">Contractual</option>
                                    <option value="Probationary">Probationary</option>
                                </select>
                                <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-2 text-gray-500">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                                    </svg>
                                </div>
                            </div>
                        </div>

                        <!-- Date Hired -->
                        <div>
                            <label class="block text-sm font-medium text-gray-800 mb-1">Date Hired</label>
                            <input 
                                type="date" 
                                x-model="form.date_hired"
                                class="w-full px-3 py-2 text-sm border border-gray-300 rounded-md focus:outline-none focus:ring-1 focus:ring-blue-500 focus:border-blue-500"
                            >
                        </div>

                        <!-- Contact Number -->
                        <div>
                            <label class="block text-sm font-medium text-gray-800 mb-1">Contact Number</label>
                            <input 
                                type="text" 
                                x-model="form.contact_number"
                                placeholder="Enter contact number" 
                                class="w-full px-3 py-2 text-sm border border-gray-300 rounded-md focus:outline-none focus:ring-1 focus:ring-blue-500 focus:border-blue-500"
                            >
                        </div>
                    </div>

                    <!-- Modal Footer - Save button right-aligned -->
                    <div class="px-6 py-4 flex justify-end">
                        <button 
                            class="px-10 py-2 bg-blue-600 text-white text-sm font-medium rounded-md hover:bg-blue-700 transition-all duration-200"
                        >
                            Save
                        </button>
                    </div>
                </div>
            </div>

            <!-- EMPLOYEE DETAILS MODAL WITH EDIT FUNCTIONALITY -->
            <div 
                x-show="showEmployeeDetails"
                x-cloak
                class="fixed inset-0 z-50 flex items-center justify-center p-4"
                style="background: rgba(0,0,0,0.5); backdrop-filter: blur(8px);"
                @click.self="closeModal"
            >
                <!-- Modal Container - Centered with white background -->
                <div 
                    x-show="showEmployeeDetails"
                    x-transition:enter="transition-all duration-300 ease-out"
                    x-transition:enter-start="opacity-0 scale-95 translate-y-4"
                    x-transition:enter-end="opacity-100 scale-100 translate-y-0"
                    x-transition:leave="transition-all duration-200 ease-in"
                    x-transition:leave-start="opacity-100 scale-100 translate-y-0"
                    x-transition:leave-end="opacity-0 scale-95 translate-y-4"
                    class="bg-white rounded-3xl shadow-2xl w-full max-w-2xl overflow-hidden relative"
                    @click.stop
                    x-show="selectedEmployee"
                >
                    <!-- Close (X) button inside a circle on top-right -->
                    <button 
                        @click="closeModal"
                        class="absolute top-6 right-6 w-10 h-10 flex items-center justify-center rounded-full border-2 border-gray-300 text-gray-500 hover:border-gray-600 hover:text-gray-700 hover:bg-gray-100 transition-all duration-200 z-10 bg-white"
                    >
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>

                    <!-- Modal Header with Title and Edit Mode Indicator -->
                    <div class="px-8 pt-8 pb-2 flex items-center justify-between">
                        <h2 class="text-2xl font-semibold text-gray-800">Employee Details</h2>
                        <span x-show="isEditMode" class="px-3 py-1 bg-yellow-100 text-yellow-800 text-xs font-medium rounded-full">
                            Edit Mode
                        </span>
                    </div>

                    <!-- Form Layout with proper spacing -->
                    <div class="px-8 pb-6 space-y-5">
                        <!-- Row 1: First Name, Last Name, Middle Initial -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Full Name</label>
                            <div class="grid grid-cols-12 gap-3">
                                <div class="col-span-5">
                                    <input 
                                        type="text" 
                                        x-model="selectedEmployee.first_name"
                                        placeholder="First Name"
                                        :readonly="!isEditMode"
                                        :class="{'bg-gray-50 cursor-not-allowed': !isEditMode, 'bg-white': isEditMode}"
                                        class="w-full px-4 py-2.5 text-sm border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all duration-200"
                                    >
                                </div>
                                <div class="col-span-5">
                                    <input 
                                        type="text" 
                                        x-model="selectedEmployee.last_name"
                                        placeholder="Last Name"
                                        :readonly="!isEditMode"
                                        :class="{'bg-gray-50 cursor-not-allowed': !isEditMode, 'bg-white': isEditMode}"
                                        class="w-full px-4 py-2.5 text-sm border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all duration-200"
                                    >
                                </div>
                                <div class="col-span-2">
                                    <input 
                                        type="text" 
                                        x-model="selectedEmployee.mi"
                                        placeholder="MI"
                                        maxlength="2"
                                        :readonly="!isEditMode"
                                        :class="{'bg-gray-50 cursor-not-allowed': !isEditMode, 'bg-white': isEditMode}"
                                        class="w-full px-3 py-2.5 text-sm border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent text-center uppercase transition-all duration-200"
                                    >
                                </div>
                            </div>
                        </div>

                        <!-- Row 2: Email (full width) -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Email Address</label>
                            <input 
                                type="email" 
                                x-model="selectedEmployee.email"
                                placeholder="Enter email address"
                                :readonly="!isEditMode"
                                :class="{'bg-gray-50 cursor-not-allowed': !isEditMode, 'bg-white': isEditMode}"
                                class="w-full px-4 py-2.5 text-sm border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all duration-200"
                            >
                        </div>

                        <!-- Row 3: Department (left half) and Position (right half) -->
                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Department</label>
                                <div class="relative">
                                    <select 
                                        x-model="selectedEmployee.department"
                                        :disabled="!isEditMode"
                                        :class="{'bg-gray-50 cursor-not-allowed': !isEditMode, 'bg-white': isEditMode}"
                                        class="w-full px-4 py-2.5 text-sm border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent appearance-none transition-all duration-200"
                                    >
                                        <option value="" disabled>Select department</option>
                                        <option value="IT">Information Technology</option>
                                        <option value="Finance">Finance</option>
                                        <option value="Nursing">Nursing</option>
                                        <option value="HR">Human Resources</option>
                                        <option value="Administration">Administration</option>
                                    </select>
                                    <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-2 text-gray-500">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                                        </svg>
                                    </div>
                                </div>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Position</label>
                                <div class="relative">
                                    <select 
                                        x-model="selectedEmployee.position"
                                        :disabled="!isEditMode"
                                        :class="{'bg-gray-50 cursor-not-allowed': !isEditMode, 'bg-white': isEditMode}"
                                        class="w-full px-4 py-2.5 text-sm border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent appearance-none transition-all duration-200"
                                    >
                                        <option value="" disabled>Select position</option>
                                        <option value="Developer">Developer</option>
                                        <option value="Senior Developer">Senior Developer</option>
                                        <option value="Financial Analyst">Financial Analyst</option>
                                        <option value="Accountant">Accountant</option>
                                        <option value="Head Nurse">Head Nurse</option>
                                        <option value="Staff Nurse">Staff Nurse</option>
                                        <option value="HR Manager">HR Manager</option>
                                        <option value="Admin Officer">Admin Officer</option>
                                    </select>
                                    <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-2 text-gray-500">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                                        </svg>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Row 4: Job Type (full width) -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Job Type</label>
                            <div class="relative">
                                <select 
                                    x-model="selectedEmployee.job_type"
                                    :disabled="!isEditMode"
                                    :class="{'bg-gray-50 cursor-not-allowed': !isEditMode, 'bg-white': isEditMode}"
                                    class="w-full px-4 py-2.5 text-sm border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent appearance-none transition-all duration-200"
                                >
                                    <option value="" disabled>Select job type</option>
                                    <option value="Full-time">Full-time</option>
                                    <option value="Part-time">Part-time</option>
                                    <option value="Contractual">Contractual</option>
                                    <option value="Probationary">Probationary</option>
                                    <option value="Permanent">Permanent</option>
                                </select>
                                <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-2 text-gray-500">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                                    </svg>
                                </div>
                            </div>
                        </div>

                        <!-- Row 5: Date Hired (full width) -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Date Hired</label>
                            <input 
                                type="date" 
                                x-model="selectedEmployee.date_hired"
                                :readonly="!isEditMode"
                                :class="{'bg-gray-50 cursor-not-allowed': !isEditMode, 'bg-white': isEditMode}"
                                class="w-full px-4 py-2.5 text-sm border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all duration-200"
                            >
                        </div>

                        <!-- Row 6: Contact Number (full width) -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Contact Number</label>
                            <input 
                                type="text" 
                                x-model="selectedEmployee.contact_number"
                                placeholder="Enter contact number"
                                :readonly="!isEditMode"
                                :class="{'bg-gray-50 cursor-not-allowed': !isEditMode, 'bg-white': isEditMode}"
                                class="w-full px-4 py-2.5 text-sm border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all duration-200"
                            >
                        </div>
                    </div>

                    <!-- Bottom right buttons: Gray "Edit" and Blue "Save" with conditional behavior -->
                    <div class="px-8 py-5 bg-gray-50 border-t border-gray-200 flex justify-end space-x-3">
                        <!-- Edit button - only visible when not in edit mode -->
                        <button 
                            x-show="!isEditMode"
                            @click="enableEditMode"
                            class="px-6 py-2.5 bg-gray-100 text-gray-700 text-sm font-medium rounded-lg border border-gray-300 hover:bg-gray-200 hover:shadow-md transition-all duration-200"
                        >
                            Edit
                        </button>
                        
                        <!-- Save button - visible in both modes but disabled when not in edit mode -->
                        <button 
                            @click="saveChanges"
                            :disabled="!isEditMode"
                            :class="{
                                'bg-blue-600 hover:bg-blue-700 cursor-pointer': isEditMode,
                                'bg-blue-300 cursor-not-allowed': !isEditMode
                            }"
                            class="px-6 py-2.5 text-white text-sm font-medium rounded-lg hover:shadow-md transition-all duration-200"
                        >
                            Save
                        </button>
                    </div>
                </div>
            </div>

            <!-- Department Summary Cards -->
            <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-5 gap-3 sm:gap-4 mb-6">
                @php
                $departments = [
                    ['name' => 'IT', 'count' => 45],
                    ['name' => 'Finance', 'count' => 32],
                    ['name' => 'Nursing', 'count' => 78],
                    ['name' => 'HR', 'count' => 18],
                    ['name' => 'Administration', 'count' => 15]
                ];
                @endphp
                
                @foreach($departments as $dept)
                <div class="bg-white rounded-xl p-3 sm:p-4 shadow-sm hover:shadow-xl transition-all duration-300 hover:scale-105 hover:-translate-y-1 group">
                    <p class="text-xs sm:text-sm font-medium text-gray-600 group-hover:text-blue-600 transition-colors duration-300">{{ $dept['name'] }}</p>
                    <p class="text-lg sm:text-2xl font-bold text-gray-800 mt-1 group-hover:text-blue-600 transition-colors duration-300">{{ $dept['count'] }}</p>
                    <p class="text-xs text-gray-500 mt-1">Employees</p>
                </div>
                @endforeach
            </div>

            <!-- Top Control Section -->
            <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 mb-6">
                <!-- Left side - Search and Filter -->
                <div class="flex items-center space-x-3 w-full sm:w-auto">
                    <!-- Search Bar with Clear Button -->
                    <div class="relative flex-1 sm:flex-none sm:w-80 group">
                        <input 
                            type="text" 
                            x-model="searchQuery"
                            placeholder="Search employees..." 
                            class="w-full pl-10 pr-10 py-3 text-sm border border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all duration-300 group-hover:shadow-md"
                        >
                        <svg class="absolute left-3 top-3.5 w-4 h-4 text-gray-400 group-hover:text-blue-500 transition-colors duration-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                        </svg>
                        <!-- Clear search button (appears when there's text) -->
                        <button 
                            x-show="searchQuery.length > 0"
                            @click="clearSearch"
                            class="absolute right-3 top-3.5 text-gray-400 hover:text-gray-600 transition-colors duration-200"
                        >
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                            </svg>
                        </button>
                    </div>
                    
                    <!-- Filter Button -->
                    <button class="p-3 bg-white border border-gray-200 rounded-xl hover:bg-gray-50 transition-all duration-300 hover:shadow-md transform hover:scale-105">
                        <svg class="w-5 h-5 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"></path>
                        </svg>
                    </button>
                </div>

                <!-- Right side - Action Buttons -->
                <div class="flex items-center space-x-3 w-full sm:w-auto">
                    <!-- Add Department Button -->
                    <button 
                        @click="showAddDepartment = true"
                        class="flex-1 sm:flex-none px-5 py-3 bg-white border border-gray-200 rounded-xl text-gray-700 text-sm font-medium hover:bg-gray-50 transition-all duration-300 hover:shadow-md transform hover:scale-105 group"
                    >
                        <span class="flex items-center justify-center space-x-2">
                            <svg class="w-4 h-4 text-gray-600 group-hover:rotate-90 transition-transform duration-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7v4m0 0v4m0-4h4m-4 0h-4M5 11h4m0 0v4m0-4v-4m0 4h4"></path>
                            </svg>
                            <span class="hidden sm:inline">Add Department</span>
                            <span class="sm:hidden">Dept</span>
                        </span>
                    </button>

                    <!-- Add Employee Button -->
                    <button 
                        @click="showAddEmployee = true"
                        class="flex-1 sm:flex-none px-5 py-3 bg-gradient-to-r from-blue-600 to-blue-700 text-white text-sm font-medium rounded-xl hover:from-blue-700 hover:to-blue-800 transition-all duration-300 hover:shadow-xl transform hover:scale-105 group"
                    >
                        <span class="flex items-center justify-center space-x-2">
                            <svg class="w-4 h-4 group-hover:rotate-90 transition-transform duration-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"></path>
                            </svg>
                            <span class="hidden sm:inline">Add Employee</span>
                            <span class="sm:hidden">Add</span>
                        </span>
                    </button>
                </div>
            </div>

            <!-- Search Results Info (shows when searching) -->
            <div x-show="searchQuery.length > 0" class="mb-4 flex items-center justify-between">
                <p class="text-sm text-gray-600">
                    Found <span class="font-semibold text-blue-600" x-text="resultCount"></span> 
                    result<span x-show="resultCount !== 1">s</span> for "<span class="font-semibold" x-text="searchQuery"></span>"
                </p>
                <button @click="clearSearch" class="text-xs text-gray-500 hover:text-blue-600 transition-colors duration-200">
                    Clear search
                </button>
            </div>

            <!-- Employee Table Card -->
            <div class="bg-white rounded-xl shadow-sm hover:shadow-xl transition-all duration-300 overflow-hidden">
                <!-- Table Header (Desktop) -->
                <div class="hidden md:block">
                    <div class="px-6 py-4 border-b border-gray-100 bg-gray-50">
                        <div class="grid grid-cols-12 gap-4">
                            <div class="col-span-4">
                                <span class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Employee Name</span>
                            </div>
                            <div class="col-span-3">
                                <span class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Department</span>
                            </div>
                            <div class="col-span-3">
                                <span class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Position</span>
                            </div>
                            <div class="col-span-1">
                                <span class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Status</span>
                            </div>
                            <div class="col-span-1">
                                <span class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Actions</span>
                            </div>
                        </div>
                    </div>

                    <!-- Table Body (Desktop) -->
                    <div class="divide-y divide-gray-100">
                        <template x-for="employee in filteredEmployees" :key="employee.email">
                            <div class="px-6 py-4 hover:bg-blue-50 transition-all duration-300 group">
                                <div class="grid grid-cols-12 gap-4 items-center">
                                    <div class="col-span-4">
                                        <div class="flex items-center space-x-3">
                                            <div class="w-10 h-10 bg-gradient-to-br from-blue-500 to-blue-600 rounded-xl flex items-center justify-center text-white font-semibold shadow-md transform group-hover:scale-110 transition-all duration-300">
                                                <span x-text="employee.avatar"></span>
                                            </div>
                                            <div>
                                                <p class="font-medium text-gray-800 group-hover:text-blue-600 transition-colors duration-300" x-text="employee.name"></p>
                                                <p class="text-xs text-gray-500" x-text="employee.email"></p>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-span-3">
                                        <p class="text-sm font-medium text-gray-800" x-text="employee.department"></p>
                                        <p class="text-xs text-gray-500" x-text="employee.department_code"></p>
                                    </div>
                                    <div class="col-span-3">
                                        <p class="text-sm font-medium text-gray-800" x-text="employee.job_title"></p>
                                    </div>
                                    <div class="col-span-1">
                                        <span class="inline-flex px-2 py-1 text-xs font-medium rounded-full"
                                              :class="employee.status === 'Active' ? 'bg-green-100 text-green-700' : 'bg-yellow-100 text-yellow-700'"
                                              x-text="employee.status">
                                        </span>
                                    </div>
                                    <div class="col-span-1">
                                        <button 
                                            @click="viewEmployee(employee)"
                                            class="px-3 py-1.5 bg-blue-50 text-blue-600 text-xs font-medium rounded-lg hover:bg-blue-600 hover:text-white transition-all duration-300 transform hover:scale-105 hover:shadow-md"
                                        >
                                            View
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </template>
                        
                        <!-- No results message -->
                        <div x-show="filteredEmployees.length === 0" class="px-6 py-12 text-center">
                            <svg class="w-16 h-16 mx-auto text-gray-300 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                            </svg>
                            <p class="text-gray-500 text-lg mb-2">No employees found</p>
                            <p class="text-gray-400 text-sm">
                                No results matching "<span x-text="searchQuery"></span>"
                            </p>
                            <button @click="clearSearch" class="mt-4 text-blue-600 hover:text-blue-700 text-sm font-medium">
                                Clear search
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Mobile Card View -->
                <div class="md:hidden">
                    <template x-for="employee in filteredEmployees" :key="employee.email">
                        <div class="p-4 border-b border-gray-100 hover:bg-blue-50 transition-all duration-300">
                            <div class="flex items-start space-x-3">
                                <div class="w-12 h-12 bg-gradient-to-br from-blue-500 to-blue-600 rounded-xl flex items-center justify-center text-white font-semibold shadow-md flex-shrink-0">
                                    <span x-text="employee.avatar"></span>
                                </div>
                                <div class="flex-1">
                                    <div class="flex items-start justify-between">
                                        <div>
                                            <p class="font-medium text-gray-800" x-text="employee.name"></p>
                                            <p class="text-xs text-gray-500" x-text="employee.email"></p>
                                        </div>
                                        <span class="px-2 py-1 text-xs font-medium rounded-full"
                                              :class="employee.status === 'Active' ? 'bg-green-100 text-green-700' : 'bg-yellow-100 text-yellow-700'"
                                              x-text="employee.status">
                                        </span>
                                    </div>
                                    <div class="mt-2 grid grid-cols-2 gap-2 text-sm">
                                        <div>
                                            <p class="text-xs text-gray-500">Department</p>
                                            <p class="font-medium text-gray-800" x-text="employee.department"></p>
                                        </div>
                                        <div>
                                            <p class="text-xs text-gray-500">Position</p>
                                            <p class="font-medium text-gray-800" x-text="employee.job_title"></p>
                                        </div>
                                    </div>
                                    <div class="mt-3">
                                        <button 
                                            @click="viewEmployee(employee)"
                                            class="w-full px-3 py-2 bg-blue-50 text-blue-600 text-sm font-medium rounded-lg hover:bg-blue-600 hover:text-white transition-all duration-300"
                                        >
                                            View Details
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </template>
                    
                    <!-- Mobile No results message -->
                    <div x-show="filteredEmployees.length === 0" class="p-8 text-center">
                        <svg class="w-16 h-16 mx-auto text-gray-300 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                        </svg>
                        <p class="text-gray-500 text-lg mb-2">No employees found</p>
                        <p class="text-gray-400 text-sm">
                            No results matching "<span x-text="searchQuery"></span>"
                        </p>
                        <button @click="clearSearch" class="mt-4 text-blue-600 hover:text-blue-700 text-sm font-medium">
                            Clear search
                        </button>
                    </div>
                </div>

                <!-- Table Footer (shows only when there are results) -->
                <div x-show="filteredEmployees.length > 0" class="px-4 sm:px-6 py-4 border-t border-gray-100 bg-gray-50">
                    <div class="flex flex-col sm:flex-row items-center justify-between gap-4">
                        <p class="text-sm text-gray-500">
                            Showing <span class="font-medium" x-text="filteredEmployees.length"></span> 
                            of <span class="font-medium" x-text="employees.length"></span> employees
                        </p>
                        <div class="flex items-center space-x-2">
                            <button class="w-8 h-8 flex items-center justify-center rounded-lg border border-gray-200 bg-white text-gray-600 hover:bg-blue-50 hover:text-blue-600 hover:border-blue-200 transition-all duration-200 disabled:opacity-50" disabled>
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
                                </svg>
                            </button>
                            <button class="w-8 h-8 flex items-center justify-center rounded-lg bg-blue-600 text-white text-sm font-medium hover:bg-blue-700 transition-all duration-200">1</button>
                            <button class="w-8 h-8 flex items-center justify-center rounded-lg border border-gray-200 bg-white text-gray-600 hover:bg-blue-50 hover:text-blue-600 hover:border-blue-200 transition-all duration-200">2</button>
                            <button class="w-8 h-8 flex items-center justify-center rounded-lg border border-gray-200 bg-white text-gray-600 hover:bg-blue-50 hover:text-blue-600 hover:border-blue-200 transition-all duration-200">3</button>
                            <button class="w-8 h-8 flex items-center justify-center rounded-lg border border-gray-200 bg-white text-gray-600 hover:bg-blue-50 hover:text-blue-600 hover:border-blue-200 transition-all duration-200">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                                </svg>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>
</div>

<style>
    .page-fade-in {
        animation: fadeIn 0.5s ease-out;
    }

    @keyframes fadeIn {
        from { opacity: 0; transform: translateY(10px); }
        to { opacity: 1; transform: translateY(0); }
    }

    /* Custom Scrollbar */
    ::-webkit-scrollbar { width: 6px; height: 6px; }
    ::-webkit-scrollbar-track { background: #f1f1f1; border-radius: 10px; }
    ::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 10px; }
    ::-webkit-scrollbar-thumb:hover { background: #94a3b8; }

    @media (max-width: 1024px) {
        .lg\:ml-20, .lg\:ml-64 { margin-left: 0 !important; }
    }

    [x-cloak] { display: none !important; }
</style>
@endsection
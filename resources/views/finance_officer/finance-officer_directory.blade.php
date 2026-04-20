@extends('layouts.app')

@section('title', 'Employee Directory - Medisource HRMS')

@section('content')

<script>
    window.employeeData = @json($employees->values());
    window.departmentData = @json($departments);
    window.jobTitleData = @json($jobTitles);
</script>

<div x-data="{
    sidebarCollapsed: localStorage.getItem('sidebarCollapsed') === 'true',
    init() {
        window.addEventListener('sidebar-toggle', e => { this.sidebarCollapsed = e.detail.collapsed; });
        this.$watch('searchQuery', () => { this.currentPage = 1; });
        this.$watch('selectedDepartments', () => { this.currentPage = 1; });
        this.$watch('selectedSort', () => { this.currentPage = 1; });
    },
    showEmployeeDetails: false,
    showFilters: false,
    showDepartmentDetails: false,
    selectedEmployee: null,
    selectedDepartment: null,
    empTab: 'basic',
    employeeDocuments: [],
    isLoadingDocs: false,
    searchQuery: '',
    selectedDepartments: [],
    selectedSort: '',
    currentPage: 1,
    perPage: 10,

    employees: window.employeeData,
    departments: window.departmentData,
    jobTitles: window.jobTitleData,

    viewEmployee(employee) {
        this.selectedEmployee = {
            id:                employee.id,
            first_name:        employee.first_name || '',
            last_name:         employee.last_name || '',
            mi:                employee.mi || '',
            email:             employee.email || '',
            department:        employee.department || '',
            position:          employee.job_title || '',
            date_hired:        employee.start_date || '',
            contact_number:    employee.contact_no || '',
            role:              employee.role || '',
            suffix:            employee.suffix || '',
            department_id:     employee.department_id,
            job_title_id:      employee.job_title_id,
            gender:            employee.gender || '',
            date_of_birth:     employee.date_of_birth || '',
            address:           employee.address || '',
            employment_type:   employee.employment_type || 'Full-time',
            employment_status: employee.employment_status || 'Active',
        };
        this.empTab = 'basic';
        this.employeeDocuments = [];
        this.showEmployeeDetails = true;
        this.loadDocuments(employee.id);
        setTimeout(() => { document.body.style.overflow = 'hidden'; }, 100);
    },

    closeModal() { this.showEmployeeDetails = false; document.body.style.overflow = 'auto'; },

    async loadDocuments(employeeId) {
        this.isLoadingDocs = true;
        try {
            const res = await fetch(`/hr/employees/${employeeId}/documents`, {
                headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').getAttribute('content') }
            });
            const data = await res.json();
            this.employeeDocuments = data.documents || [];
        } catch (e) { console.error('Failed to load documents', e); }
        this.isLoadingDocs = false;
    },

    viewDepartmentDetails(dept) {
        this.selectedDepartment = {
            id:        dept.id,
            name:      dept.name,
            jobTitles: dept.job_titles.map(j => ({ id: j.id, title: j.title })),
        };
        this.showDepartmentDetails = true;
    },

    get filteredEmployees() {
        let result = [...this.employees];
        if (this.searchQuery.trim()) {
            const query = this.searchQuery.toLowerCase().trim();
            result = result.filter(e =>
                e.name.toLowerCase().includes(query) ||
                e.email.toLowerCase().includes(query) ||
                e.department.toLowerCase().includes(query) ||
                e.job_title.toLowerCase().includes(query) ||
                e.department_code.toLowerCase().includes(query) ||
                e.status.toLowerCase().includes(query)
            );
        }
        if (this.selectedDepartments.length > 0) {
            result = result.filter(emp => this.selectedDepartments.includes(emp.department));
        }
        if (this.selectedSort) {
            result.sort((a, b) => {
                switch(this.selectedSort) {
                    case 'Employee Name': return a.name.localeCompare(b.name);
                    case 'Status': return a.status.localeCompare(b.status);
                    case 'Job Title': return a.job_title.localeCompare(b.job_title);
                    default: return 0;
                }
            });
        }
        return result;
    },

    get totalPages() { return Math.max(1, Math.ceil(this.filteredEmployees.length / this.perPage)); },
    get pagedEmployees() {
        const start = (this.currentPage - 1) * this.perPage;
        return this.filteredEmployees.slice(start, start + this.perPage);
    },
    get resultCount() { return this.filteredEmployees.length; },
    get activeFilterCount() { return this.selectedDepartments.length + (this.selectedSort ? 1 : 0); },

    clearSearch() { this.searchQuery = ''; },
    clearFilters() { this.selectedDepartments = []; this.selectedSort = ''; },
    applyFilters() { this.showFilters = false; },

    alertModal: { show: false, message: '', type: 'success' },
    showAlert(message, type = 'success') { this.alertModal = { show: true, message, type }; },
}"
    class="flex h-screen overflow-hidden bg-gray-50" @keydown.escape.window="closeModal()">

    @include('finance_officer.finance_sidebar', ['activeMenu' => 'employees'])

    <main class="flex-1 overflow-y-auto transition-all duration-300"
          :class="sidebarCollapsed ? 'lg:ml-20' : 'lg:ml-64'">

        <!-- Top Header -->
        <header class="bg-gradient-to-r from-blue-600 to-blue-700 text-white sticky top-0 z-10 shadow-lg mt-4 mx-4 rounded-2xl">
            <div class="px-4 sm:px-6 lg:px-8 py-4 flex items-center justify-between">
                <div>
                    <h1 class="text-xl sm:text-2xl font-bold text-white">Employees</h1>
                    <p class="text-xs sm:text-sm text-blue-100 mt-1">Employee Directory</p>
                </div>
                <div class="flex items-center space-x-2 sm:space-x-4">
                    <x-notification-bell />
                </div>
            </div>
        </header>

        <div class="p-4 sm:p-6 lg:p-8 mt-4 page-fade-in">

            <!-- ALERT MODAL -->
            <div x-show="alertModal.show" x-cloak
                class="fixed inset-0 z-[9999] flex items-center justify-center p-4"
                style="background: rgba(0,0,0,0.45); backdrop-filter: blur(6px);"
                @click.self="alertModal.show = false">
                <div x-show="alertModal.show"
                    x-transition:enter="transition-all duration-250 ease-out"
                    x-transition:enter-start="opacity-0 scale-90"
                    x-transition:enter-end="opacity-100 scale-100"
                    x-transition:leave="transition-all duration-150 ease-in"
                    x-transition:leave-start="opacity-100 scale-100"
                    x-transition:leave-end="opacity-0 scale-90"
                    class="bg-white rounded-2xl shadow-2xl flex flex-col items-center justify-center text-center p-10"
                    style="width: 460px; min-height: 320px;"
                    @click.stop>
                    <div class="w-16 h-16 rounded-2xl flex items-center justify-center mb-5 shadow-sm" :class="alertModal.type === 'success' ? 'bg-blue-50' : 'bg-red-50'">
                        <template x-if="alertModal.type === 'success'">
                            <svg class="w-8 h-8 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                        </template>
                        <template x-if="alertModal.type !== 'success'">
                            <svg class="w-8 h-8 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/></svg>
                        </template>
                    </div>
                    <p class="text-sm font-semibold text-gray-800 mb-1" x-text="alertModal.type === 'success' ? 'Success' : 'Notice'"></p>
                    <p class="text-sm text-gray-500 mb-7 leading-snug" x-text="alertModal.message"></p>
                    <button @click="alertModal.show = false"
                        class="px-8 py-2.5 bg-blue-600 text-white text-sm font-semibold rounded-xl hover:bg-blue-700 active:scale-95 transition-all duration-150 shadow-md hover:shadow-lg">
                        OK
                    </button>
                </div>
            </div>

            <!-- DEPARTMENT DETAILS MODAL (view-only) -->
            <div x-show="showDepartmentDetails" x-cloak
                class="fixed inset-0 z-50 flex items-center justify-center p-4"
                style="background: rgba(0,0,0,0.5); backdrop-filter: blur(8px);"
                @click.self="showDepartmentDetails = false">
                <div x-show="showDepartmentDetails"
                    x-transition:enter="transition-all duration-300 ease-out"
                    x-transition:enter-start="opacity-0 scale-95 translate-y-4"
                    x-transition:enter-end="opacity-100 scale-100 translate-y-0"
                    x-transition:leave="transition-all duration-200 ease-in"
                    x-transition:leave-start="opacity-100 scale-100 translate-y-0"
                    x-transition:leave-end="opacity-0 scale-95 translate-y-4"
                    class="bg-white rounded-2xl shadow-2xl w-full max-w-md overflow-hidden relative"
                    @click.stop>
                    <button @click="showDepartmentDetails = false"
                        class="absolute top-6 right-6 w-10 h-10 flex items-center justify-center rounded-full border-2 border-gray-300 text-gray-500 hover:border-gray-500 hover:text-gray-700 hover:bg-gray-100 transition-all duration-200 z-10 bg-white">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                    <div class="px-8 pt-8 pb-2">
                        <h2 class="text-2xl font-bold text-gray-900">Department Details</h2>
                    </div>
                    <div x-show="selectedDepartment" class="px-8 pb-8 space-y-5 mt-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Department Name</label>
                            <input type="text" :value="selectedDepartment && selectedDepartment.name" readonly
                                class="w-full px-4 py-2.5 text-sm border border-gray-300 rounded-lg bg-gray-50 cursor-not-allowed">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Job Titles</label>
                            <div class="border border-gray-300 rounded-lg px-4 py-3 space-y-1 min-h-[120px] bg-gray-50">
                                <template x-if="selectedDepartment && selectedDepartment.jobTitles.length === 0">
                                    <p class="text-sm text-gray-400 py-1">No job titles.</p>
                                </template>
                                <template x-for="(jt, index) in (selectedDepartment ? selectedDepartment.jobTitles : [])" :key="index">
                                    <div class="flex items-center gap-2 py-1">
                                        <span class="w-1.5 h-1.5 bg-blue-400 rounded-full flex-shrink-0"></span>
                                        <span class="text-sm text-gray-700" x-text="jt.title"></span>
                                    </div>
                                </template>
                            </div>
                        </div>
                        <div class="flex justify-end">
                            <button @click="showDepartmentDetails = false"
                                class="px-8 py-2.5 bg-gray-100 text-gray-700 text-sm font-medium rounded-xl hover:bg-gray-200 transition-all duration-200">
                                Close
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- EMPLOYEE DETAILS MODAL (view-only) -->
            <div x-show="showEmployeeDetails" x-cloak
                class="fixed inset-0 z-50 flex items-center justify-center p-4"
                style="background: rgba(0,0,0,0.5); backdrop-filter: blur(8px);"
                @click.self="closeModal">
                <div x-show="showEmployeeDetails"
                    x-transition:enter="transition-all duration-300 ease-out"
                    x-transition:enter-start="opacity-0 scale-95 translate-y-4"
                    x-transition:enter-end="opacity-100 scale-100 translate-y-0"
                    x-transition:leave="transition-all duration-200 ease-in"
                    x-transition:leave-start="opacity-100 scale-100 translate-y-0"
                    x-transition:leave-end="opacity-0 scale-95 translate-y-4"
                    class="bg-white rounded-2xl shadow-2xl w-full max-w-2xl overflow-hidden relative flex flex-col"
                    style="max-height: 92vh;"
                    @click.stop x-show="selectedEmployee">

                    <button @click="closeModal"
                        class="absolute top-5 right-5 w-9 h-9 flex items-center justify-center rounded-full border-2 border-gray-200 text-gray-400 hover:border-gray-400 hover:text-gray-600 transition-all duration-200 z-10 bg-white">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>

                    <div class="px-8 pt-7 pb-0 flex-shrink-0">
                        <template x-if="selectedEmployee">
                            <div class="flex items-center gap-4 mb-5">
                                <div class="w-14 h-14 bg-gradient-to-br from-blue-500 to-blue-600 rounded-2xl flex items-center justify-center text-white text-xl font-bold shadow-md flex-shrink-0"
                                    x-text="(selectedEmployee.first_name.charAt(0) + selectedEmployee.last_name.charAt(0)).toUpperCase()"></div>
                                <div>
                                    <h2 class="text-xl font-bold text-gray-900 leading-tight"
                                        x-text="selectedEmployee.first_name + ' ' + (selectedEmployee.mi ? selectedEmployee.mi + '. ' : '') + selectedEmployee.last_name + (selectedEmployee.suffix ? ' ' + selectedEmployee.suffix : '')"></h2>
                                    <p class="text-sm text-gray-500 mt-0.5" x-text="selectedEmployee.position"></p>
                                </div>
                            </div>
                        </template>

                        <div class="flex justify-center border-b border-gray-200">
                            <button @click="empTab = 'basic'"
                                class="px-5 pb-3 text-sm font-medium relative transition-colors duration-200"
                                :class="empTab === 'basic' ? 'text-blue-600' : 'text-gray-500 hover:text-gray-700'">
                                Basic Details
                                <span x-show="empTab === 'basic'" class="absolute bottom-0 left-0 right-0 h-0.5 bg-blue-600 rounded-t-full"></span>
                            </button>
                            <button @click="empTab = 'job'"
                                class="px-5 pb-3 text-sm font-medium relative transition-colors duration-200"
                                :class="empTab === 'job' ? 'text-blue-600' : 'text-gray-500 hover:text-gray-700'">
                                Job Information
                                <span x-show="empTab === 'job'" class="absolute bottom-0 left-0 right-0 h-0.5 bg-blue-600 rounded-t-full"></span>
                            </button>
                            <button @click="empTab = 'docs'"
                                class="px-5 pb-3 text-sm font-medium relative transition-colors duration-200"
                                :class="empTab === 'docs' ? 'text-blue-600' : 'text-gray-500 hover:text-gray-700'">
                                Documents
                                <span x-show="empTab === 'docs'" class="absolute bottom-0 left-0 right-0 h-0.5 bg-blue-600 rounded-t-full"></span>
                            </button>
                        </div>
                    </div>

                    <div class="flex-1 overflow-y-auto">
                        <template x-if="selectedEmployee">
                            <div>
                                <!-- BASIC DETAILS TAB -->
                                <div x-show="empTab === 'basic'" class="px-8 py-5 space-y-4">
                                    <div>
                                        <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wide mb-2">Full Name</label>
                                        <div class="grid grid-cols-12 gap-2">
                                            <div class="col-span-5">
                                                <input type="text" :value="selectedEmployee.first_name" readonly
                                                    class="w-full px-3 py-2.5 text-sm border border-gray-200 rounded-lg bg-gray-50 cursor-not-allowed">
                                            </div>
                                            <div class="col-span-5">
                                                <input type="text" :value="selectedEmployee.last_name" readonly
                                                    class="w-full px-3 py-2.5 text-sm border border-gray-200 rounded-lg bg-gray-50 cursor-not-allowed">
                                            </div>
                                            <div class="col-span-2">
                                                <input type="text" :value="selectedEmployee.mi" readonly
                                                    class="w-full px-2 py-2.5 text-sm border border-gray-200 rounded-lg bg-gray-50 cursor-not-allowed text-center uppercase">
                                            </div>
                                        </div>
                                        <div class="mt-2">
                                            <input type="text" :value="selectedEmployee.suffix || 'None'" readonly
                                                class="w-full px-3 py-2.5 text-sm border border-gray-200 rounded-lg bg-gray-50 cursor-not-allowed">
                                        </div>
                                    </div>
                                    <div>
                                        <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wide mb-2">Email Address</label>
                                        <input type="email" :value="selectedEmployee.email" readonly
                                            class="w-full px-3 py-2.5 text-sm border border-gray-200 rounded-lg bg-gray-50 cursor-not-allowed">
                                    </div>
                                    <div>
                                        <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wide mb-2">Contact Number</label>
                                        <input type="text" :value="selectedEmployee.contact_number" readonly
                                            class="w-full px-3 py-2.5 text-sm border border-gray-200 rounded-lg bg-gray-50 cursor-not-allowed">
                                    </div>
                                    <div class="grid grid-cols-2 gap-3">
                                        <div>
                                            <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wide mb-2">Date of Birth</label>
                                            <input type="date" :value="selectedEmployee.date_of_birth" readonly
                                                class="w-full px-3 py-2.5 text-sm border border-gray-200 rounded-lg bg-gray-50 cursor-not-allowed">
                                        </div>
                                        <div>
                                            <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wide mb-2">Gender</label>
                                            <input type="text" :value="selectedEmployee.gender" readonly
                                                class="w-full px-3 py-2.5 text-sm border border-gray-200 rounded-lg bg-gray-50 cursor-not-allowed capitalize">
                                        </div>
                                    </div>
                                    <div>
                                        <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wide mb-2">Address</label>
                                        <textarea readonly rows="2"
                                            class="w-full px-3 py-2.5 text-sm border border-gray-200 rounded-lg bg-gray-50 cursor-not-allowed resize-none"
                                            x-text="selectedEmployee.address"></textarea>
                                    </div>
                                </div>

                                <!-- JOB INFORMATION TAB -->
                                <div x-show="empTab === 'job'" class="px-8 py-5 space-y-4">
                                    <div class="grid grid-cols-2 gap-3">
                                        <div>
                                            <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wide mb-2">Department</label>
                                            <input type="text" :value="selectedEmployee.department" readonly
                                                class="w-full px-3 py-2.5 text-sm border border-gray-200 rounded-lg bg-gray-50 cursor-not-allowed">
                                        </div>
                                        <div>
                                            <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wide mb-2">Job Title</label>
                                            <input type="text" :value="selectedEmployee.position" readonly
                                                class="w-full px-3 py-2.5 text-sm border border-gray-200 rounded-lg bg-gray-50 cursor-not-allowed">
                                        </div>
                                    </div>
                                    <div>
                                        <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wide mb-2">Role</label>
                                        <input type="text" :value="selectedEmployee.role" readonly
                                            class="w-full px-3 py-2.5 text-sm border border-gray-200 rounded-lg bg-gray-50 cursor-not-allowed capitalize">
                                    </div>
                                    <div class="grid grid-cols-2 gap-3">
                                        <div>
                                            <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wide mb-2">Date Hired</label>
                                            <input type="date" :value="selectedEmployee.date_hired" readonly
                                                class="w-full px-3 py-2.5 text-sm border border-gray-200 rounded-lg bg-gray-50 cursor-not-allowed">
                                        </div>
                                        <div>
                                            <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wide mb-2">Employment Type</label>
                                            <input type="text" :value="selectedEmployee.employment_type" readonly
                                                class="w-full px-3 py-2.5 text-sm border border-gray-200 rounded-lg bg-gray-50 cursor-not-allowed">
                                        </div>
                                    </div>
                                    <div>
                                        <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wide mb-2">Employment Status</label>
                                        <input type="text" :value="selectedEmployee.employment_status" readonly
                                            class="w-full px-3 py-2.5 text-sm border border-gray-200 rounded-lg bg-gray-50 cursor-not-allowed">
                                    </div>
                                </div>

                                <!-- DOCUMENTS TAB -->
                                <div x-show="empTab === 'docs'" class="px-8 py-5">
                                    <div x-show="isLoadingDocs" class="flex items-center justify-center py-8">
                                        <svg class="animate-spin w-6 h-6 text-blue-500" fill="none" viewBox="0 0 24 24">
                                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                                        </svg>
                                    </div>
                                    <div x-show="!isLoadingDocs">
                                        <template x-if="employeeDocuments.length === 0">
                                            <div class="text-center py-10 text-gray-400">
                                                <svg class="w-12 h-12 mx-auto mb-3 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                                </svg>
                                                <p class="text-sm">No documents attached yet.</p>
                                            </div>
                                        </template>
                                        <div class="space-y-2">
                                            <template x-for="doc in employeeDocuments" :key="doc.id">
                                                <a :href="`/hr/employees/documents/${doc.id}/download`" target="_blank"
                                                    class="flex items-center gap-3 p-3 border border-gray-200 rounded-xl hover:bg-blue-50 hover:border-blue-200 transition-all group">
                                                    <div class="w-10 h-10 rounded-lg flex items-center justify-center flex-shrink-0"
                                                        :class="doc.file_type === 'pdf' ? 'bg-red-100' : 'bg-blue-100'">
                                                        <svg class="w-5 h-5" :class="doc.file_type === 'pdf' ? 'text-red-500' : 'text-blue-500'" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                                        </svg>
                                                    </div>
                                                    <div class="flex-1 min-w-0">
                                                        <p class="text-sm font-medium text-gray-800 truncate group-hover:text-blue-600" x-text="doc.file_name"></p>
                                                        <p class="text-xs text-gray-400" x-text="doc.file_size + ' · ' + doc.created_at"></p>
                                                    </div>
                                                    <svg class="w-4 h-4 text-gray-400 group-hover:text-blue-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                                                    </svg>
                                                </a>
                                            </template>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </template>
                    </div>
                </div>
            </div>

            <!-- Department Summary Cards -->
            <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-5 gap-3 sm:gap-4 mb-6 mx-8">
                <template x-for="dept in departments" :key="dept.id">
                    <div @click="viewDepartmentDetails(dept)"
                        class="bg-white rounded-xl p-4 shadow-sm border border-gray-100 hover:shadow-md hover:border-blue-200 cursor-pointer transition-all duration-200">
                        <p class="text-sm text-gray-500 mb-1" x-text="dept.name"></p>
                        <p class="text-3xl font-bold text-gray-800" x-text="dept.employees_count"></p>
                        <p class="text-xs text-gray-400 mt-1">Employees</p>
                    </div>
                </template>
            </div>

            <!-- Top Control Section -->
            <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 mb-6 mx-8">
                <div class="flex items-center space-x-3 w-full sm:w-auto">
                    <div class="relative flex-1 sm:flex-none sm:w-80 group">
                        <input type="text" x-model="searchQuery" placeholder="Search employees..."
                            class="w-full pl-10 pr-10 py-3 text-sm border border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all duration-300 group-hover:shadow-md">
                        <svg class="absolute left-3 top-3.5 w-4 h-4 text-gray-400 group-hover:text-blue-500 transition-colors duration-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                        </svg>
                        <button x-show="searchQuery.length > 0" @click="clearSearch" class="absolute right-3 top-3.5 text-gray-400 hover:text-gray-600 transition-colors duration-200">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                        </button>
                    </div>

                    <div class="relative">
                        <button @click="showFilters = !showFilters"
                            class="w-[180px] py-2.5 bg-white border border-gray-200 rounded-xl hover:bg-gray-50 transition-all duration-200 hover:shadow-sm relative flex items-center gap-2 text-sm text-gray-400 justify-start pl-4"
                            :class="{ 'bg-blue-50 border-blue-300': showFilters || activeFilterCount > 0 }">
                            <svg class="w-4 h-4 transition-colors duration-200"
                                :class="{ 'text-blue-600': showFilters || activeFilterCount > 0, 'text-gray-500': !showFilters && activeFilterCount === 0 }"
                                fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"/>
                            </svg>
                            <span>Filter</span>
                            <span x-show="activeFilterCount > 0"
                                class="absolute -top-1.5 -right-1.5 w-4 h-4 bg-blue-600 text-white text-[10px] font-bold rounded-full flex items-center justify-center border-2 border-white"
                                x-text="activeFilterCount"></span>
                        </button>

                        <div x-show="showFilters" @click.away="showFilters = false"
                            x-transition:enter="transition ease-out duration-200"
                            x-transition:enter-start="opacity-0 transform scale-95 -translate-y-2"
                            x-transition:enter-end="opacity-100 transform scale-100 translate-y-0"
                            x-transition:leave="transition ease-in duration-150"
                            x-transition:leave-start="opacity-100 transform scale-100 translate-y-0"
                            x-transition:leave-end="opacity-0 transform scale-95 -translate-y-2"
                            class="absolute left-0 mt-2 w-64 bg-white rounded-2xl shadow-lg border border-gray-100 z-50 overflow-hidden">
                            <div class="px-5 pt-5 pb-3 flex items-center gap-2">
                                <svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"/>
                                </svg>
                                <span class="text-base font-semibold text-gray-800">Filter</span>
                            </div>
                            <div class="px-5 pb-4 space-y-4">
                                <div>
                                    <p class="text-xs font-medium text-gray-400 mb-2">By Department</p>
                                    <div class="space-y-1">
                                        <template x-for="dept in departments" :key="dept.id">
                                            <label class="flex items-center gap-3 py-1 cursor-pointer">
                                                <input type="checkbox" :value="dept.name" x-model="selectedDepartments" class="w-4 h-4 rounded border-gray-300 text-blue-600 focus:ring-0 focus:ring-offset-0">
                                                <span class="text-sm text-gray-700" x-text="dept.name"></span>
                                            </label>
                                        </template>
                                    </div>
                                </div>
                                <div>
                                    <p class="text-xs font-medium text-gray-400 mb-2">Sort</p>
                                    <div class="space-y-1">
                                        <label class="flex items-center gap-3 py-1 cursor-pointer">
                                            <input type="radio" name="sort" value="Employee Name" x-model="selectedSort" class="w-4 h-4 border-gray-300 text-blue-600 focus:ring-0 focus:ring-offset-0">
                                            <span class="text-sm text-gray-700">Employee Name</span>
                                        </label>
                                        <label class="flex items-center gap-3 py-1 cursor-pointer">
                                            <input type="radio" name="sort" value="Status" x-model="selectedSort" class="w-4 h-4 border-gray-300 text-blue-600 focus:ring-0 focus:ring-offset-0">
                                            <span class="text-sm text-gray-700">Employment Status</span>
                                        </label>
                                        <label class="flex items-center gap-3 py-1 cursor-pointer">
                                            <input type="radio" name="sort" value="Job Title" x-model="selectedSort" class="w-4 h-4 border-gray-300 text-blue-600 focus:ring-0 focus:ring-offset-0">
                                            <span class="text-sm text-gray-700">Job Title</span>
                                        </label>
                                    </div>
                                </div>
                            </div>
                            <div class="px-5 py-3 border-t border-gray-100 flex items-center justify-between">
                                <button @click="clearFilters" class="text-sm text-gray-400 hover:text-gray-600 transition-colors duration-150">Reset all</button>
                                <button @click="applyFilters" class="px-5 py-2 bg-blue-600 text-white text-sm font-medium rounded-lg hover:bg-blue-700 transition-all duration-200">Apply</button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Active Filters -->
            <div x-show="selectedDepartments.length > 0 || selectedSort" class="mb-4 flex flex-wrap items-center gap-2 mx-8">
                <span class="text-xs text-gray-500">Active filters:</span>
                <template x-for="dept in selectedDepartments" :key="dept">
                    <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-blue-50 text-blue-700 border border-blue-100">
                        <span x-text="dept"></span>
                        <button @click="selectedDepartments = selectedDepartments.filter(d => d !== dept)" class="ml-1.5 hover:text-blue-900">
                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                        </button>
                    </span>
                </template>
                <span x-show="selectedSort" class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-blue-50 text-blue-700 border border-blue-100">
                    <span>Sort: <span x-text="selectedSort"></span></span>
                    <button @click="selectedSort = ''" class="ml-1.5 hover:text-blue-900">
                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </span>
                <button @click="clearFilters" class="text-xs text-gray-500 hover:text-gray-700 underline">Clear all</button>
            </div>

            <!-- Search Results Info -->
            <div x-show="searchQuery.length > 0" class="mb-4 flex items-center justify-between mx-8">
                <p class="text-sm text-gray-600">Found <span class="font-semibold text-blue-600" x-text="resultCount"></span> result<span x-show="resultCount !== 1">s</span> for "<span class="font-semibold" x-text="searchQuery"></span>"</p>
                <button @click="clearSearch" class="text-xs text-gray-500 hover:text-blue-600 transition-colors duration-200">Clear search</button>
            </div>

            <!-- Employee Table -->
            <div class="bg-white rounded-xl shadow-sm hover:shadow-xl transition-all duration-300 overflow-hidden mx-8">
                <div class="hidden md:block">
                    <div class="px-6 py-4 border-b border-gray-100 bg-gray-50">
                        <div class="grid grid-cols-12 gap-4">
                            <div class="col-span-3"><span class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Employee Name</span></div>
                            <div class="col-span-3"><span class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Department</span></div>
                            <div class="col-span-3"><span class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Position</span></div>
                            <div class="col-span-1 pr-6"><span class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Status</span></div>
                            <div class="col-span-2 pl-12"><span class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Actions</span></div>
                        </div>
                    </div>
                    <div class="divide-y divide-gray-100">
                        <template x-for="employee in pagedEmployees" :key="employee.email">
                            <div class="px-6 py-4 hover:bg-blue-50 transition-all duration-300 group">
                                <div class="grid grid-cols-12 gap-4 items-center">
                                    <div class="col-span-3">
                                        <div class="flex items-center space-x-3">
                                            <div class="w-10 h-10 bg-gradient-to-br from-blue-500 to-blue-600 rounded-xl flex items-center justify-center text-white font-semibold shadow-md transform group-hover:scale-110 transition-all duration-300 flex-shrink-0">
                                                <span x-text="employee.avatar"></span>
                                            </div>
                                            <div class="min-w-0">
                                                <p class="font-medium text-gray-800 group-hover:text-blue-600 transition-colors duration-300 truncate" x-text="employee.name"></p>
                                                <p class="text-xs text-gray-500 truncate" x-text="employee.email"></p>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-span-3">
                                        <p class="text-sm font-medium text-gray-800 truncate" x-text="employee.department"></p>
                                        <p class="text-xs text-gray-500" x-text="employee.department_code"></p>
                                    </div>
                                    <div class="col-span-3">
                                        <p class="text-sm font-medium text-gray-800 truncate" x-text="employee.job_title"></p>
                                    </div>
                                    <div class="col-span-1 pr-6">
                                        <span class="inline-flex px-2.5 py-1 text-xs font-medium rounded-full whitespace-nowrap"
                                            :class="employee.status === 'Active' ? 'bg-green-100 text-green-700' : 'bg-yellow-100 text-yellow-700'"
                                            x-text="employee.status"></span>
                                    </div>
                                    <div class="col-span-2 pl-12">
                                        <button @click="viewEmployee(employee)"
                                            class="whitespace-nowrap px-4 py-2 bg-blue-500 text-white text-sm font-medium rounded-xl hover:bg-blue-600 transition-all duration-200 shadow-md hover:shadow-lg">View Details</button>
                                    </div>
                                </div>
                            </div>
                        </template>
                        <div x-show="filteredEmployees.length === 0" class="px-6 py-12 text-center">
                            <svg class="w-16 h-16 mx-auto text-gray-300 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                            </svg>
                            <p class="text-gray-500 text-lg mb-2">No employees found</p>
                            <p class="text-gray-400 text-sm">No results matching "<span x-text="searchQuery"></span>"</p>
                            <button @click="clearSearch(); clearFilters()" class="mt-4 text-blue-600 hover:text-blue-700 text-sm font-medium">Clear all filters</button>
                        </div>
                    </div>
                </div>

                <!-- Mobile Cards -->
                <div class="md:hidden">
                    <template x-for="employee in pagedEmployees" :key="employee.email">
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
                                            x-text="employee.status"></span>
                                    </div>
                                    <div class="mt-2 grid grid-cols-2 gap-2 text-sm">
                                        <div><p class="text-xs text-gray-500">Department</p><p class="font-medium text-gray-800" x-text="employee.department"></p></div>
                                        <div><p class="text-xs text-gray-500">Position</p><p class="font-medium text-gray-800" x-text="employee.job_title"></p></div>
                                    </div>
                                    <div class="mt-3">
                                        <button @click="viewEmployee(employee)"
                                            class="w-full whitespace-nowrap px-4 py-2 bg-blue-500 text-white text-sm font-medium rounded-xl hover:bg-blue-600 transition-all duration-200 shadow-md hover:shadow-lg">View Details</button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </template>
                    <div x-show="filteredEmployees.length === 0" class="p-8 text-center">
                        <svg class="w-16 h-16 mx-auto text-gray-300 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                        </svg>
                        <p class="text-gray-500 text-lg mb-2">No employees found</p>
                        <p class="text-gray-400 text-sm">No results matching "<span x-text="searchQuery"></span>"</p>
                        <button @click="clearSearch(); clearFilters()" class="mt-4 text-blue-600 hover:text-blue-700 text-sm font-medium">Clear all filters</button>
                    </div>
                </div>

                <!-- Table Footer / Pagination -->
                <div x-show="filteredEmployees.length > 0" class="px-4 sm:px-6 py-4 border-t border-gray-100 bg-gray-50">
                    <div class="flex flex-col sm:flex-row items-center justify-between gap-4">
                        <p class="text-sm text-gray-500">
                            Showing
                            <span class="font-medium" x-text="Math.min((currentPage - 1) * perPage + 1, filteredEmployees.length)"></span>–<span class="font-medium" x-text="Math.min(currentPage * perPage, filteredEmployees.length)"></span>
                            of <span class="font-medium" x-text="filteredEmployees.length"></span> employees
                        </p>
                        <div x-show="totalPages > 1" class="flex items-center space-x-2 overflow-x-auto">
                            <button @click="currentPage > 1 && currentPage--"
                                    :disabled="currentPage === 1"
                                    class="w-8 h-8 flex items-center justify-center rounded-lg border border-gray-200 bg-white text-gray-600 hover:bg-blue-50 hover:text-blue-600 hover:border-blue-200 transition-all duration-200 disabled:opacity-50">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                            </button>
                            <template x-for="page in Array.from({length: totalPages}, (_, i) => i + 1)" :key="page">
                                <button @click="currentPage = page"
                                        :class="currentPage === page ? 'bg-blue-600 text-white hover:bg-blue-700' : 'border border-gray-200 bg-white text-gray-600 hover:bg-blue-50 hover:text-blue-600 hover:border-blue-200'"
                                        class="w-8 h-8 flex items-center justify-center rounded-lg text-sm font-medium transition-all duration-200"
                                        x-text="page">
                                </button>
                            </template>
                            <button @click="currentPage < totalPages && currentPage++"
                                    :disabled="currentPage === totalPages"
                                    class="w-8 h-8 flex items-center justify-center rounded-lg border border-gray-200 bg-white text-gray-600 hover:bg-blue-50 hover:text-blue-600 hover:border-blue-200 transition-all duration-200 disabled:opacity-50">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                            </button>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </main>
</div>

<style>
    .page-fade-in { animation: fadeIn 0.5s ease-out; }
    @keyframes fadeIn { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }
    ::-webkit-scrollbar { width: 6px; height: 6px; }
    ::-webkit-scrollbar-track { background: #f1f1f1; border-radius: 10px; }
    ::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 10px; }
    ::-webkit-scrollbar-thumb:hover { background: #94a3b8; }
    @media (max-width: 1024px) { .lg\:ml-20, .lg\:ml-64 { margin-left: 0 !important; } }
    [x-cloak] { display: none !important; }
</style>

@endsection

@extends('layouts.app')

@section('title', 'Employees Directory - Medisource HRMS')

@section('content')

<script>
    window.employeeData = @json($employees->values());
    window.departmentData = @json($departments);
    window.jobTitleData = @json($jobTitles);
    window.supervisorDeptId = {{ $departments->first()['id'] ?? 'null' }};
    window.supervisorDeptName = "{{ $departments->first()['name'] ?? '' }}";
</script>

<div x-data="{
    sidebarCollapsed: localStorage.getItem('sidebarCollapsed') === 'true',
    showAddEmployee: false,
    showManageDepartment: false,
    showEmployeeDetails: false,
    showFilters: false,
    selectedEmployee: null,
    isEditMode: false,
    searchQuery: '',
    selectedSort: '',

    addStep: 1,
    uploadedFiles: [],
    isSaving: false,
    formErrors: {},
    step1Attempted: false,
    step2Attempted: false,

    newEmployeeForm: {
        first_name: '', last_name: '', mi: '', suffix: '', suffix_other: '',
        date_of_birth: '', gender: '',
        email: '', address: '', contact_no: '',
        department_id: window.supervisorDeptId,
        job_title_id: '',
        employment_type: '', employment_status: '',
        contract_period: '', end_date: ''
    },

    employees: window.employeeData,
    departments: window.departmentData,
    jobTitles: window.jobTitleData,

    get jobTitlesForDept() {
        return this.jobTitles.filter(j => j.department_id == window.supervisorDeptId);
    },

    openAddEmployee() {
        this.addStep = 1;
        this.uploadedFiles = [];
        this.formErrors = {};
        this.isSaving = false;
        this.step1Attempted = false;
        this.step2Attempted = false;
        this.newEmployeeForm = {
            first_name: '', last_name: '', mi: '', suffix: '', suffix_other: '',
            date_of_birth: '', gender: '',
            email: '', address: '', contact_no: '',
            department_id: window.supervisorDeptId,
            job_title_id: '',
            employment_type: '', employment_status: '',
            contract_period: '', end_date: ''
        };
        this.showAddEmployee = true;
    },

    closeAddEmployee() {
        this.showAddEmployee = false;
        this.formErrors = {};
        this.isSaving = false;
    },

    nextStep() { if (this.addStep < 3) this.addStep++; },
    saveAndContinue() { this.nextStep(); },

    handleFileUpload(event) {
        const files = Array.from(event.target.files);
        files.forEach(file => {
            if (!this.uploadedFiles.find(f => f.name === file.name)) {
                this.uploadedFiles.push({ name: file.name, size: (file.size / (1024*1024)).toFixed(1) + ' MB' });
            }
        });
    },

    removeFile(index) { this.uploadedFiles.splice(index, 1); },

    fieldError(field) {
        return this.formErrors[field] ? this.formErrors[field][0] : null;
    },

    hasError(field) {
        return !!this.formErrors[field];
    },

    async saveEmployee() {
        this.isSaving = true;
        this.formErrors = {};

        const suffix = this.newEmployeeForm.suffix === 'Other'
            ? this.newEmployeeForm.suffix_other
            : (this.newEmployeeForm.suffix === 'None' ? '' : this.newEmployeeForm.suffix);

        const payload = {
            first_name:        this.newEmployeeForm.first_name,
            last_name:         this.newEmployeeForm.last_name,
            mi:                this.newEmployeeForm.mi,
            suffix:            suffix,
            email:             this.newEmployeeForm.email,
            address:           this.newEmployeeForm.address,
            contact_no:        this.newEmployeeForm.contact_no,
            gender:            this.newEmployeeForm.gender,
            date_of_birth:     this.newEmployeeForm.date_of_birth,
            department_id:     window.supervisorDeptId,
            job_title_id:      this.newEmployeeForm.job_title_id,
            employment_type:   this.newEmployeeForm.employment_type,
            employment_status: this.newEmployeeForm.employment_status,
            contract_period:   this.newEmployeeForm.contract_period,
            end_date:          this.newEmployeeForm.end_date,
            _token:            document.querySelector('meta[name=csrf-token]').getAttribute('content')
        };

        try {
            const response = await fetch('{{ route('supervisor.employees.store') }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').getAttribute('content')
                },
                body: JSON.stringify(payload)
            });

            if (response.ok) {
                this.closeAddEmployee();
                this.showToast('Employee added successfully!', 'success');
                setTimeout(() => window.location.reload(), 1500);
                return;
            }

            if (response.status === 422) {
                const data = await response.json();
                this.formErrors = data.errors || {};
                const step1Fields = ['first_name','last_name','mi','suffix','email','address','contact_no','gender','date_of_birth'];
                const hasStep1Error = step1Fields.some(f => this.formErrors[f]);
                if (hasStep1Error) {
                    this.addStep = 1;
                } else {
                    this.addStep = 2;
                }
                this.isSaving = false;
                return;
            }

            const errData = await response.json().catch(() => ({}));
            this.showToast(errData.message || 'Something went wrong. Please try again.', 'error');
            this.isSaving = false;

        } catch (err) {
            console.error('Save employee error:', err);
            this.showToast('Network error. Please check your connection.', 'error');
            this.isSaving = false;
        }
    },

    showDepartmentDetails: false,
    selectedDepartment: null,
    isDeptEditMode: false,

    viewDepartmentDetails(dept) {
        this.selectedDepartment = {
            id:          dept.id,
            name:        dept.name,
            jobTitles:   dept.job_titles.map(j => ({ id: j.id, title: j.title })),
            newJobTitle: ''
        };
        this.isDeptEditMode = false;
        this.showDepartmentDetails = true;
    },

    addJobTitle() {
        if (this.selectedDepartment.newJobTitle.trim()) {
            this.selectedDepartment.jobTitles.push({ id: null, title: this.selectedDepartment.newJobTitle.trim() });
            this.selectedDepartment.newJobTitle = '';
        }
    },

    removeJobTitle(index) {
        this.selectedDepartment.jobTitles.splice(index, 1);
    },

    async saveDepartmentDetails() {
        if (!this.selectedDepartment.name.trim()) {
            this.showToast('Department name cannot be empty.', 'error');
            return;
        }

        if (this.selectedDepartment.newJobTitle && this.selectedDepartment.newJobTitle.trim()) {
            this.selectedDepartment.jobTitles.push({ id: null, title: this.selectedDepartment.newJobTitle.trim() });
            this.selectedDepartment.newJobTitle = '';
        }

        this.isSaving = true;

        const payload = {
            name:       this.selectedDepartment.name,
            job_titles: this.selectedDepartment.jobTitles.map(jt => ({
                id:    jt.id ?? null,
                title: jt.title
            })),
            _method: 'PUT',
            _token:  document.querySelector('meta[name=csrf-token]').getAttribute('content')
        };

        try {
            const response = await fetch(`/supervisor/employees/departments/${this.selectedDepartment.id}`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').getAttribute('content')
                },
                body: JSON.stringify(payload)
            });

            const data = await response.json();

            if (response.ok && data.success) {
                this.isDeptEditMode = false;
                this.isSaving = false;
                this.showToast('Department updated successfully!', 'success');

                const idx = this.departments.findIndex(d => d.id === this.selectedDepartment.id);
                if (idx !== -1) {
                    this.departments[idx].name = this.selectedDepartment.name;
                    this.departments[idx].job_titles = this.selectedDepartment.jobTitles.map(jt => ({
                        id:    jt.id,
                        title: jt.title
                    }));
                }

                this.jobTitles = this.jobTitles.filter(jt => jt.department_id != this.selectedDepartment.id);
                data.job_titles.forEach(jt => {
                    this.jobTitles.push({
                        id:            jt.id,
                        title:         jt.title,
                        department_id: this.selectedDepartment.id
                    });
                });

                this.selectedDepartment.jobTitles = data.job_titles.map(jt => ({
                    id:    jt.id,
                    title: jt.title
                }));
                return;
            }

            this.showToast(data.message || 'Something went wrong.', 'error');
            this.isSaving = false;

        } catch (err) {
            this.showToast('Network error. Please check your connection.', 'error');
            this.isSaving = false;
        }
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

    get resultCount() { return this.filteredEmployees.length; },
    get activeFilterCount() { return (this.selectedSort ? 1 : 0); },

    viewEmployee(employee) {
        this.selectedEmployee = {
            id:             employee.id,
            first_name:     employee.first_name || '',
            last_name:      employee.last_name || '',
            mi:             employee.mi || '',
            email:          employee.email || '',
            department:     employee.department || '',
            position:       employee.job_title || '',
            date_hired:     employee.start_date || '',
            contact_number: employee.contact_no || '',
            role:           employee.role || '',
            suffix:         employee.suffix || '',
            department_id:  employee.department_id,
            job_title_id:   employee.job_title_id,
        };
        this.isEditMode = false;
        this.showEmployeeDetails = true;
        setTimeout(() => { document.body.style.overflow = 'hidden'; }, 100);
    },

    enableEditMode() { this.isEditMode = true; },

    async saveChanges() {
        if (!this.selectedEmployee.contact_number || this.selectedEmployee.contact_number.length < 10) {
            this.showToast('Contact number must be at least 10 digits.', 'error');
            return;
        }

        this.isSaving = true;

        const payload = {
            first_name:    this.selectedEmployee.first_name,
            last_name:     this.selectedEmployee.last_name,
            mi:            this.selectedEmployee.mi,
            suffix:        this.selectedEmployee.suffix,
            email:         this.selectedEmployee.email,
            contact_no:    this.selectedEmployee.contact_number,
            department_id: this.selectedEmployee.department_id,
            job_title_id:  this.selectedEmployee.job_title_id,
            role:          this.selectedEmployee.role,
            start_date:    this.selectedEmployee.date_hired,
            _method:       'PUT',
            _token:        document.querySelector('meta[name=csrf-token]').getAttribute('content')
        };

        try {
            const response = await fetch(`/supervisor/employees/${this.selectedEmployee.id}`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').getAttribute('content')
                },
                body: JSON.stringify(payload)
            });

            if (response.ok) {
                this.isEditMode = false;
                this.isSaving = false;

                const empIdx = this.employees.findIndex(e => e.id === this.selectedEmployee.id);
                if (empIdx !== -1) {
                    const newJobTitle = this.jobTitles.find(j => j.id == this.selectedEmployee.job_title_id);
                    const dept = this.departments.find(d => d.id == this.selectedEmployee.department_id);
                    this.employees[empIdx].first_name    = this.selectedEmployee.first_name;
                    this.employees[empIdx].last_name     = this.selectedEmployee.last_name;
                    this.employees[empIdx].mi            = this.selectedEmployee.mi;
                    this.employees[empIdx].suffix        = this.selectedEmployee.suffix;
                    this.employees[empIdx].email         = this.selectedEmployee.email;
                    this.employees[empIdx].contact_no    = this.selectedEmployee.contact_number;
                    this.employees[empIdx].department_id = this.selectedEmployee.department_id;
                    this.employees[empIdx].department    = dept ? dept.name : this.employees[empIdx].department;
                    this.employees[empIdx].job_title_id  = this.selectedEmployee.job_title_id;
                    this.employees[empIdx].job_title     = newJobTitle ? newJobTitle.title : this.employees[empIdx].job_title;
                    this.employees[empIdx].start_date    = this.selectedEmployee.date_hired;
                    this.employees[empIdx].name          = [this.selectedEmployee.first_name, this.selectedEmployee.mi ? this.selectedEmployee.mi + '.' : '', this.selectedEmployee.last_name].filter(Boolean).join(' ');
                    this.employees[empIdx].avatar        = (this.selectedEmployee.first_name.charAt(0) + this.selectedEmployee.last_name.charAt(0)).toUpperCase();
                }

                this.showToast('Employee updated successfully!', 'success');
                return;
            }

            const errData = await response.json().catch(() => ({}));
            this.showToast(errData.message || 'Something went wrong.', 'error');
            this.isSaving = false;

        } catch (err) {
            this.showToast('Network error. Please check your connection.', 'error');
            this.isSaving = false;
        }
    },

    closeModal() { this.showEmployeeDetails = false; this.isEditMode = false; document.body.style.overflow = 'auto'; },
    clearSearch() { this.searchQuery = ''; },
    clearFilters() { this.selectedSort = ''; },
    applyFilters() { this.showFilters = false; },

    toast: { show: false, message: '', type: 'success' },
    showToast(message, type = 'success') {
        this.toast = { show: true, message, type };
        setTimeout(() => { this.toast.show = false; }, 3000);
    }
}" class="flex h-screen overflow-hidden bg-gray-50" @keydown.escape.window="closeModal(); closeAddEmployee()">

    @include('supervisor.supervisor_sidebar')

    <main class="flex-1 overflow-y-auto transition-all duration-300"
          :class="sidebarCollapsed ? 'lg:ml-20' : 'lg:ml-72'">

        <!-- Top Header -->
        <header class="bg-gradient-to-r from-blue-600 to-blue-700 text-white sticky top-0 z-10 shadow-lg mt-4 mx-4 rounded-2xl">
            <div class="px-4 sm:px-6 lg:px-8 py-4 flex items-center justify-between">
                <div>
                    <h1 class="text-xl sm:text-2xl font-bold text-white">Employees</h1>
                    <p class="text-xs sm:text-sm text-blue-100 mt-1">Employee Directory</p>
                </div>
                <div class="flex items-center space-x-2 sm:space-x-4">
                    <x-supervisor-notif />
                    <div class="w-8 h-8 sm:w-10 sm:h-10 bg-white/20 rounded-lg flex items-center justify-center">
                        <span class="text-sm sm:text-base font-semibold">{{ $authEmployee ? strtoupper(substr($authEmployee->fname, 0, 1) . substr($authEmployee->lname, 0, 1)) : strtoupper(substr($authUser->email, 0, 2)) }}</span>
                    </div>
                </div>
            </div>
        </header>

        <div class="p-4 sm:p-6 lg:p-8 mt-4 page-fade-in">

            <!-- Toast Notification -->
            <div x-show="toast.show" x-cloak
                x-transition:enter="transition ease-out duration-300"
                x-transition:enter-start="opacity-0 translate-y-[-20px]"
                x-transition:enter-end="opacity-100 translate-y-0"
                x-transition:leave="transition ease-in duration-200"
                x-transition:leave-start="opacity-100 translate-y-0"
                x-transition:leave-end="opacity-0 translate-y-[-20px]"
                class="fixed top-6 right-6 z-[9999] flex items-center gap-3 px-5 py-4 rounded-2xl shadow-xl border"
                :class="toast.type === 'success' ? 'bg-white border-green-100 text-gray-800' : 'bg-white border-red-100 text-gray-800'">
                <div class="w-8 h-8 rounded-full flex items-center justify-center flex-shrink-0"
                    :class="toast.type === 'success' ? 'bg-green-100' : 'bg-red-100'">
                    <template x-if="toast.type === 'success'">
                        <svg class="w-4 h-4 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                    </template>
                    <template x-if="toast.type === 'error'">
                        <svg class="w-4 h-4 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                    </template>
                </div>
                <div>
                    <p class="text-sm font-semibold" x-text="toast.type === 'success' ? 'Success' : 'Error'"></p>
                    <p class="text-xs text-gray-500" x-text="toast.message"></p>
                </div>
                <button @click="toast.show = false" class="ml-2 text-gray-400 hover:text-gray-600 transition-colors">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>

            <!-- ===================== MANAGE DEPARTMENT MODAL (no Add Dept button) ===================== -->
            <div x-show="showManageDepartment" x-cloak
                class="fixed inset-0 z-50 flex items-center justify-center p-4"
                style="background: rgba(0,0,0,0.5); backdrop-filter: blur(8px);"
                @click.self="showManageDepartment = false">
                <div x-show="showManageDepartment"
                    x-transition:enter="transition-all duration-300 ease-out"
                    x-transition:enter-start="opacity-0 scale-95 translate-y-4"
                    x-transition:enter-end="opacity-100 scale-100 translate-y-0"
                    x-transition:leave="transition-all duration-200 ease-in"
                    x-transition:leave-start="opacity-100 scale-100 translate-y-0"
                    x-transition:leave-end="opacity-0 scale-95 translate-y-4"
                    class="bg-white rounded-2xl shadow-2xl w-full max-w-2xl overflow-hidden relative"
                    @click.stop>
                    <div class="px-8 pt-8 pb-6 flex items-center justify-between">
                        <h2 class="text-2xl font-bold text-gray-900">Manage Department</h2>
                        <button @click="showManageDepartment = false"
                            class="w-10 h-10 flex items-center justify-center rounded-full border-2 border-gray-300 text-gray-500 hover:border-gray-500 hover:text-gray-700 hover:bg-gray-100 transition-all duration-200 bg-white">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                        </button>
                    </div>
                    <div class="px-8 pb-8 overflow-y-auto" style="max-height: calc(90vh - 140px);">
                        <table class="w-full border border-gray-200 rounded-xl overflow-hidden">
                            <thead>
                                <tr class="bg-gray-50 border-b border-gray-200">
                                    <th class="text-left px-5 py-3 text-sm font-semibold text-gray-700 w-1/3">Department</th>
                                    <th class="text-left px-5 py-3 text-sm font-semibold text-gray-700">Job Titles</th>
                                    <th class="text-center px-5 py-3 text-sm font-semibold text-gray-700 w-32">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                <template x-for="dept in departments" :key="dept.id">
                                    <tr class="hover:bg-gray-50 transition-colors duration-150">
                                        <td class="px-5 py-4 text-sm font-medium text-gray-800 align-top" x-text="dept.name"></td>
                                        <td class="px-5 py-4 text-sm text-gray-500 align-top">
                                            <span x-text="dept.job_titles.map(j => j.title).join(', ')"></span>
                                        </td>
                                        <td class="px-5 py-4 text-center align-middle w-36">
                                            <button @click="viewDepartmentDetails(dept)"
                                                class="whitespace-nowrap px-5 py-2 bg-blue-500 text-white text-sm font-medium rounded-xl hover:bg-blue-600 transition-all duration-200 shadow-md hover:shadow-lg">View Details</button>
                                        </td>
                                    </tr>
                                </template>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- ===================== DEPARTMENT DETAILS MODAL ===================== -->
            <div x-show="showDepartmentDetails" x-cloak
                class="fixed inset-0 z-50 flex items-center justify-center p-4"
                style="background: rgba(0,0,0,0.5); backdrop-filter: blur(8px);"
                @click.self="showDepartmentDetails = false; isDeptEditMode = false">
                <div x-show="showDepartmentDetails"
                    x-transition:enter="transition-all duration-300 ease-out"
                    x-transition:enter-start="opacity-0 scale-95 translate-y-4"
                    x-transition:enter-end="opacity-100 scale-100 translate-y-0"
                    x-transition:leave="transition-all duration-200 ease-in"
                    x-transition:leave-start="opacity-100 scale-100 translate-y-0"
                    x-transition:leave-end="opacity-0 scale-95 translate-y-4"
                    class="bg-white rounded-2xl shadow-2xl w-full max-w-md overflow-hidden relative"
                    @click.stop>
                    <button @click="showDepartmentDetails = false; isDeptEditMode = false"
                        class="absolute top-6 right-6 w-10 h-10 flex items-center justify-center rounded-full border-2 border-gray-300 text-gray-500 hover:border-gray-500 hover:text-gray-700 hover:bg-gray-100 transition-all duration-200 z-10 bg-white">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                    <div class="px-8 pt-8 pb-2">
                        <h2 class="text-2xl font-bold text-gray-900">Department Details</h2>
                    </div>
                    <div x-show="selectedDepartment" class="px-8 pb-4 space-y-5 mt-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Department Name</label>
                            <input type="text" x-model="selectedDepartment.name"
                                :readonly="!isDeptEditMode"
                                :class="{'bg-gray-50 cursor-not-allowed': !isDeptEditMode, 'bg-white': isDeptEditMode}"
                                class="w-full px-4 py-2.5 text-sm border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all duration-200">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Job Titles</label>
                            <div class="border border-gray-300 rounded-lg px-4 py-3 space-y-1 min-h-[120px]">
                                <template x-for="(jt, index) in selectedDepartment.jobTitles" :key="index">
                                    <div class="flex items-center justify-between py-1">
                                        <div class="flex items-center gap-2">
                                            <span class="w-1.5 h-1.5 bg-blue-400 rounded-full flex-shrink-0"></span>
                                            <span class="text-sm text-gray-700" x-text="jt.title"></span>
                                        </div>
                                        <button x-show="isDeptEditMode" @click="removeJobTitle(index)"
                                            class="text-gray-400 hover:text-red-500 transition-colors duration-150 ml-2">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                                            </svg>
                                        </button>
                                    </div>
                                </template>
                                <input x-show="isDeptEditMode" type="text"
                                    x-model="selectedDepartment.newJobTitle"
                                    @keydown.enter.prevent="addJobTitle()"
                                    placeholder="Add Job Title"
                                    class="w-full text-sm text-gray-600 placeholder-gray-400 border-none outline-none bg-transparent pt-1">
                            </div>
                        </div>
                    </div>
                    <div class="px-8 py-5 flex justify-end space-x-3">
                        <button x-show="!isDeptEditMode" @click="isDeptEditMode = true"
                            class="px-8 py-2.5 bg-white text-gray-700 text-sm font-medium rounded-xl border border-gray-300 hover:bg-gray-50 transition-all duration-200">
                            Edit
                        </button>
                        <button @click="saveDepartmentDetails()" :disabled="isSaving"
                            :class="isSaving ? 'bg-gray-400 cursor-not-allowed' : 'bg-blue-500 hover:bg-blue-600'"
                            class="px-8 py-2.5 text-white text-sm font-medium rounded-xl transition-all duration-200">
                            <span x-show="!isSaving">Save</span>
                            <span x-show="isSaving" class="flex items-center gap-2">
                                <svg class="animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                                </svg>
                                Saving...
                            </span>
                        </button>
                    </div>
                </div>
            </div>

            <!-- ===================== ADD EMPLOYEE MODAL ===================== -->
            <div x-show="showAddEmployee" x-cloak
                class="fixed inset-0 z-50 flex items-center justify-center p-4"
                style="background: rgba(0,0,0,0.5); backdrop-filter: blur(4px);"
                @click.self="closeAddEmployee()">
                <div x-show="showAddEmployee"
                    x-transition:enter="transition-all duration-300 ease-out"
                    x-transition:enter-start="opacity-0 scale-95 translate-y-4"
                    x-transition:enter-end="opacity-100 scale-100 translate-y-0"
                    x-transition:leave="transition-all duration-200 ease-in"
                    x-transition:leave-start="opacity-100 scale-100 translate-y-0"
                    x-transition:leave-end="opacity-0 scale-95 translate-y-4"
                    class="bg-white rounded-2xl shadow-2xl w-full max-w-lg overflow-hidden relative"
                    style="max-height: 92vh;"
                    @click.stop>

                    <div class="px-8 pt-7 pb-0 flex items-center justify-between">
                        <h2 class="text-2xl font-bold text-gray-900">Add Employee</h2>
                        <button @click="closeAddEmployee()"
                            class="w-9 h-9 flex items-center justify-center rounded-full border-2 border-gray-300 text-gray-400 hover:border-gray-500 hover:text-gray-600 transition-all duration-150">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                        </button>
                    </div>

                    <div class="px-8 pt-5 pb-0">
                        <div class="flex gap-4">
                            <button @click="addStep = 1" class="flex-1 pb-3 text-sm font-medium transition-all duration-200 relative text-left"
                                :class="addStep === 1 ? 'text-blue-600' : addStep > 1 ? 'text-gray-500' : 'text-gray-300'">
                                Basic Details
                                <span class="absolute bottom-0 left-0 right-0 h-1 rounded-full transition-all duration-300"
                                    :class="addStep === 1 ? 'bg-blue-600' : addStep > 1 ? 'bg-gray-400' : 'bg-gray-200'"></span>
                            </button>
                            <button @click="addStep >= 2 ? addStep = 2 : null" class="flex-1 pb-3 text-sm font-medium transition-all duration-200 relative text-center"
                                :class="addStep === 2 ? 'text-blue-600' : addStep > 2 ? 'text-gray-500' : 'text-gray-300'">
                                Job Information
                                <span class="absolute bottom-0 left-0 right-0 h-1 rounded-full transition-all duration-300"
                                    :class="addStep === 2 ? 'bg-blue-600' : addStep > 2 ? 'bg-gray-400' : 'bg-gray-200'"></span>
                            </button>
                            <button @click="addStep >= 3 ? addStep = 3 : null" class="flex-1 pb-3 text-sm font-medium transition-all duration-200 relative text-right"
                                :class="addStep === 3 ? 'text-blue-600' : 'text-gray-300'">
                                Documents
                                <span class="absolute bottom-0 left-0 right-0 h-1 rounded-full transition-all duration-300"
                                    :class="addStep === 3 ? 'bg-blue-600' : 'bg-gray-200'"></span>
                            </button>
                        </div>
                    </div>

                    <!-- STEP 1: Basic Details -->
                    <div x-show="addStep === 1" class="px-8 pt-5 pb-0 space-y-4 overflow-y-auto" style="max-height: calc(92vh - 185px);">
                        <div>
                            <label class="block text-sm font-semibold text-gray-800 mb-1.5">Name</label>
                            <div class="flex gap-2">
                                <div class="flex-1 min-w-0">
                                    <input type="text" x-model="newEmployeeForm.first_name" placeholder="First Name"
                                        @input="newEmployeeForm.first_name = $event.target.value.replace(/[^a-zA-Z\s\-']/g, '')"
                                        minlength="2" maxlength="50"
                                        :class="hasError('first_name') ? 'border-red-400 focus:ring-red-400' : 'border-gray-200 focus:ring-blue-400'"
                                        class="w-full px-3 py-2.5 text-sm border rounded-lg focus:outline-none focus:ring-1 focus:border-transparent text-gray-700 placeholder-gray-400">
                                    <p x-show="hasError('first_name')" x-text="fieldError('first_name')" class="text-xs text-red-500 mt-1"></p>
                                </div>
                                <div class="flex-1 min-w-0">
                                    <input type="text" x-model="newEmployeeForm.last_name" placeholder="Last Name"
                                        @input="newEmployeeForm.last_name = $event.target.value.replace(/[^a-zA-Z\s\-']/g, '')"
                                        minlength="2" maxlength="50"
                                        :class="hasError('last_name') ? 'border-red-400 focus:ring-red-400' : 'border-gray-200 focus:ring-blue-400'"
                                        class="w-full px-3 py-2.5 text-sm border rounded-lg focus:outline-none focus:ring-1 focus:border-transparent text-gray-700 placeholder-gray-400">
                                    <p x-show="hasError('last_name')" x-text="fieldError('last_name')" class="text-xs text-red-500 mt-1"></p>
                                </div>
                                <div class="w-14">
                                    <input type="text" x-model="newEmployeeForm.mi" placeholder="MI" minlength="1" maxlength="2"
                                        @input="newEmployeeForm.mi = $event.target.value.replace(/[^a-zA-Z]/g, '')"
                                        class="w-full px-2 py-2.5 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-1 focus:ring-blue-400 text-center uppercase text-gray-700 placeholder-gray-400">
                                </div>
                                <div class="relative w-36">
                                    <select x-model="newEmployeeForm.suffix"
                                        class="w-full px-3 py-2.5 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-1 focus:ring-blue-400 appearance-none bg-white text-gray-400">
                                        <option value="" disabled selected>Choose suffix</option>
                                        <option value="None">None</option>
                                        <option value="Sr.">Sr.</option>
                                        <option value="Jr.">Jr.</option>
                                        <option value="Other">Other</option>
                                    </select>
                                    <div class="pointer-events-none absolute inset-y-0 right-2 flex items-center text-gray-400">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                                    </div>
                                </div>
                            </div>
                            <div x-show="newEmployeeForm.suffix === 'Other'" class="mt-2">
                                <input type="text" x-model="newEmployeeForm.suffix_other" placeholder="Please specify suffix"
                                    class="w-full px-3 py-2.5 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-1 focus:ring-blue-400 text-gray-700 placeholder-gray-400">
                            </div>
                        </div>

                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="block text-sm font-semibold text-gray-800 mb-1.5">Date of Birth</label>
                                <input type="date" x-model="newEmployeeForm.date_of_birth"
                                    :class="hasError('date_of_birth') ? 'border-red-400 focus:ring-red-400' : 'border-gray-200 focus:ring-blue-400'"
                                    class="w-full px-3 py-2.5 text-sm border rounded-lg focus:outline-none focus:ring-1 focus:border-transparent text-gray-400">
                                <p x-show="hasError('date_of_birth')" x-text="fieldError('date_of_birth')" class="text-xs text-red-500 mt-1"></p>
                            </div>
                            <div>
                                <label class="block text-sm font-semibold text-gray-800 mb-1.5">Gender</label>
                                <div class="relative">
                                    <select x-model="newEmployeeForm.gender"
                                        :class="hasError('gender') ? 'border-red-400 focus:ring-red-400' : 'border-gray-200 focus:ring-blue-400'"
                                        class="w-full px-3 py-2.5 text-sm border rounded-lg focus:outline-none focus:ring-1 appearance-none bg-white text-gray-400">
                                        <option value="" disabled selected>Choose gender</option>
                                        <option value="Male">Male</option>
                                        <option value="Female">Female</option>
                                        <option value="Other">Other</option>
                                        <option value="Prefer not to say">Prefer not to say</option>
                                    </select>
                                    <div class="pointer-events-none absolute inset-y-0 right-2 flex items-center text-gray-400">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                                    </div>
                                </div>
                                <p x-show="hasError('gender')" x-text="fieldError('gender')" class="text-xs text-red-500 mt-1"></p>
                            </div>
                        </div>

                        <div>
                            <label class="block text-sm font-semibold text-gray-800 mb-1.5">Email</label>
                            <input type="email" x-model="newEmployeeForm.email" placeholder="Enter email"
                                :class="hasError('email') ? 'border-red-400 focus:ring-red-400' : 'border-gray-200 focus:ring-blue-400'"
                                class="w-full px-3 py-2.5 text-sm border rounded-lg focus:outline-none focus:ring-1 focus:border-transparent text-gray-700 placeholder-gray-400">
                            <p x-show="hasError('email')" x-text="fieldError('email')" class="text-xs text-red-500 mt-1"></p>
                        </div>

                        <div>
                            <label class="block text-sm font-semibold text-gray-800 mb-1.5">Address</label>
                            <input type="text" x-model="newEmployeeForm.address" placeholder="Enter address"
                                :class="hasError('address') ? 'border-red-400 focus:ring-red-400' : 'border-gray-200 focus:ring-blue-400'"
                                class="w-full px-3 py-2.5 text-sm border rounded-lg focus:outline-none focus:ring-1 focus:border-transparent text-gray-700 placeholder-gray-400">
                            <p x-show="hasError('address')" x-text="fieldError('address')" class="text-xs text-red-500 mt-1"></p>
                        </div>

                        <div>
                            <label class="block text-sm font-semibold text-gray-800 mb-1.5">Contact Number</label>
                            <input type="text" x-model="newEmployeeForm.contact_no" placeholder="Enter contact number"
                                maxlength="15"
                                @input="newEmployeeForm.contact_no = newEmployeeForm.contact_no.replace(/[^0-9]/g, '')"
                                :class="hasError('contact_no') ? 'border-red-400 focus:ring-red-400' : 'border-gray-200 focus:ring-blue-400'"
                                class="w-full px-3 py-2.5 text-sm border rounded-lg focus:outline-none focus:ring-1 focus:border-transparent text-gray-700 placeholder-gray-400">
                            <p x-show="hasError('contact_no')" x-text="fieldError('contact_no')" class="text-xs text-red-500 mt-1"></p>
                        </div>

                        <div class="h-2"></div>
                    </div>

                    <!-- STEP 2: Job Information -->
                    <div x-show="addStep === 2" class="px-8 pt-5 pb-0 space-y-4 overflow-y-auto" style="max-height: calc(92vh - 185px);">

                        <!-- Department: locked to supervisor's department -->
                        <div>
                            <label class="block text-sm font-semibold text-gray-800 mb-1.5">Department</label>
                            <input type="text" value="{{ $departments->first()['name'] ?? '' }}" readonly
                                class="w-full px-3 py-2.5 text-sm border border-gray-200 rounded-lg bg-gray-50 cursor-not-allowed text-gray-600">
                        </div>

                        <div>
                            <label class="block text-sm font-semibold text-gray-800 mb-1.5">Job Title</label>
                            <div class="relative">
                                <select x-model="newEmployeeForm.job_title_id"
                                    :class="hasError('job_title_id') ? 'border-red-400 focus:ring-red-400' : 'border-gray-200 focus:ring-blue-400'"
                                    class="w-full px-3 py-2.5 text-sm border rounded-lg focus:outline-none focus:ring-1 appearance-none bg-white text-gray-400">
                                    <option value="" disabled selected>Choose job title</option>
                                    <template x-for="jt in jobTitlesForDept" :key="jt.id">
                                        <option :value="jt.id" x-text="jt.title"></option>
                                    </template>
                                </select>
                                <div class="pointer-events-none absolute inset-y-0 right-2 flex items-center text-gray-400">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                                </div>
                            </div>
                            <p x-show="hasError('job_title_id')" x-text="fieldError('job_title_id')" class="text-xs text-red-500 mt-1"></p>
                        </div>

                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="block text-sm font-semibold text-gray-800 mb-1.5">Employment Type</label>
                                <div class="relative">
                                    <select x-model="newEmployeeForm.employment_type"
                                        :class="hasError('employment_type') ? 'border-red-400 focus:ring-red-400' : 'border-gray-200 focus:ring-blue-400'"
                                        class="w-full px-3 py-2.5 text-sm border rounded-lg focus:outline-none focus:ring-1 appearance-none bg-white text-gray-400">
                                        <option value="" disabled selected>Choose type</option>
                                        <option value="Full-time">Full-time</option>
                                        <option value="Part-time">Part-time</option>
                                        <option value="Contractual">Contractual</option>
                                        <option value="Internship">Internship</option>
                                    </select>
                                    <div class="pointer-events-none absolute inset-y-0 right-2 flex items-center text-gray-400">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                                    </div>
                                </div>
                                <p x-show="hasError('employment_type')" x-text="fieldError('employment_type')" class="text-xs text-red-500 mt-1"></p>
                            </div>
                            <div>
                                <label class="block text-sm font-semibold text-gray-800 mb-1.5">Employment Status</label>
                                <div class="relative">
                                    <select x-model="newEmployeeForm.employment_status"
                                        :class="hasError('employment_status') ? 'border-red-400 focus:ring-red-400' : 'border-gray-200 focus:ring-blue-400'"
                                        class="w-full px-3 py-2.5 text-sm border rounded-lg focus:outline-none focus:ring-1 appearance-none bg-white text-gray-400">
                                        <option value="" disabled selected>Choose status</option>
                                        <option value="Active">Active</option>
                                        <option value="Resigned">Resigned</option>
                                        <option value="Retired">Retired</option>
                                        <option value="Suspended">Suspended</option>
                                        <option value="Terminated">Terminated</option>
                                        <option value="End Contract">End Contract</option>
                                    </select>
                                    <div class="pointer-events-none absolute inset-y-0 right-2 flex items-center text-gray-400">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                                    </div>
                                </div>
                                <p x-show="hasError('employment_status')" x-text="fieldError('employment_status')" class="text-xs text-red-500 mt-1"></p>
                            </div>
                        </div>

                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="block text-sm font-semibold text-gray-800 mb-1.5">Contract Period</label>
                                <div class="relative">
                                    <select x-model="newEmployeeForm.contract_period"
                                        class="w-full px-3 py-2.5 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-1 focus:ring-blue-400 appearance-none bg-white text-gray-400">
                                        <option value="" disabled selected>Choose period</option>
                                        <option value="3 months">3 months</option>
                                        <option value="6 months">6 months</option>
                                        <option value="1 year">1 year</option>
                                        <option value="2 years">2 years</option>
                                        <option value="Indefinite">Indefinite</option>
                                    </select>
                                    <div class="pointer-events-none absolute inset-y-0 right-2 flex items-center text-gray-400">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                                    </div>
                                </div>
                            </div>
                            <div>
                                <label class="block text-sm font-semibold text-gray-800 mb-1.5">End Date <span class="text-gray-400 font-normal">(optional)</span></label>
                                <input type="date" x-model="newEmployeeForm.end_date"
                                    class="w-full px-3 py-2.5 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-1 focus:ring-blue-400 text-gray-400">
                            </div>
                        </div>

                        <div class="h-2"></div>
                    </div>

                    <!-- STEP 3: Documents -->
                    <div x-show="addStep === 3" class="px-8 pt-5 pb-0 space-y-4 overflow-y-auto" style="max-height: calc(92vh - 185px);">
                        <div>
                            <label class="block text-sm font-semibold text-gray-800 mb-2">Upload Documents</label>
                            <label for="file-upload-wiz"
                                class="flex flex-col items-center justify-center w-full rounded-xl cursor-pointer bg-gray-50 border-2 border-dashed border-gray-200 hover:bg-gray-100 hover:border-gray-300 transition-colors duration-200"
                                style="min-height: 130px;">
                                <div class="flex flex-col items-center justify-center py-6 space-y-1 pointer-events-none">
                                    <svg class="w-8 h-8 text-gray-400 mb-1" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5m-13.5-9L12 3m0 0l4.5 4.5M12 3v13.5"/>
                                    </svg>
                                    <p class="text-sm font-medium text-gray-600">Choose a file to upload</p>
                                    <p class="text-xs text-gray-400">PDF or DOCX file size no more than 10MB</p>
                                </div>
                                <input id="file-upload-wiz" type="file" class="hidden" multiple accept=".pdf,.docx" @change="handleFileUpload($event)">
                            </label>
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-gray-800 mb-2">Uploads</label>
                            <div class="space-y-2">
                                <template x-for="(file, index) in uploadedFiles" :key="index">
                                    <div class="flex items-center justify-between px-4 py-3 border border-gray-200 rounded-lg bg-white">
                                        <div class="flex items-center gap-3 min-w-0">
                                            <span class="text-sm text-gray-700 truncate" x-text="file.name"></span>
                                            <span class="text-xs text-gray-400 flex-shrink-0" x-text="file.size"></span>
                                        </div>
                                        <button @click="removeFile(index)"
                                            class="w-6 h-6 flex items-center justify-center rounded-full border-2 border-gray-300 text-gray-400 hover:border-gray-500 hover:text-gray-600 transition-all duration-150 flex-shrink-0 ml-3">
                                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                                            </svg>
                                        </button>
                                    </div>
                                </template>
                                <template x-if="uploadedFiles.length === 0">
                                    <p class="text-sm text-gray-400 py-1">No documents uploaded yet.</p>
                                </template>
                            </div>
                        </div>
                        <div class="h-2"></div>
                    </div>

                    <!-- Footer -->
                    <div class="px-8 py-5 flex items-center justify-between border-t border-gray-100 bg-white">
                        <div>
                            <p x-show="Object.keys(formErrors).length > 0" class="text-xs text-red-500">Please fix the highlighted errors.</p>
                        </div>
                        <div class="flex items-center gap-3">
                            <button x-show="addStep > 1" @click="addStep--"
                                class="px-7 py-2.5 text-sm font-medium text-gray-600 bg-white rounded-lg border border-gray-300 hover:bg-gray-50 transition-all duration-200">
                                Back
                            </button>
                            <button x-show="addStep < 3"
                                @click="
                                    addStep === 1 ? (step1Attempted = true, (newEmployeeForm.first_name && newEmployeeForm.last_name && newEmployeeForm.email && newEmployeeForm.contact_no.length >= 10) ? saveAndContinue() : null) :
                                    addStep === 2 ? (step2Attempted = true, (newEmployeeForm.job_title_id && newEmployeeForm.employment_type && newEmployeeForm.employment_status) ? saveAndContinue() : null) : null
                                "
                                :disabled="
                                    (addStep === 1 && !(newEmployeeForm.first_name && newEmployeeForm.last_name && newEmployeeForm.email && newEmployeeForm.contact_no.length >= 10)) ||
                                    (addStep === 2 && !(newEmployeeForm.job_title_id && newEmployeeForm.employment_type && newEmployeeForm.employment_status))
                                "
                                class="px-7 py-2.5 text-sm font-semibold rounded-lg transition-all duration-200"
                                :class="{
                                    'bg-blue-600 text-white hover:bg-blue-700 cursor-pointer':
                                        (addStep === 1 && newEmployeeForm.first_name && newEmployeeForm.last_name && newEmployeeForm.email && newEmployeeForm.contact_no.length >= 10) ||
                                        (addStep === 2 && newEmployeeForm.job_title_id && newEmployeeForm.employment_type && newEmployeeForm.employment_status),
                                    'bg-gray-200 text-gray-400 cursor-not-allowed':
                                        (addStep === 1 && !(newEmployeeForm.first_name && newEmployeeForm.last_name && newEmployeeForm.email && newEmployeeForm.contact_no.length >= 10)) ||
                                        (addStep === 2 && !(newEmployeeForm.job_title_id && newEmployeeForm.employment_type && newEmployeeForm.employment_status))
                                }">
                                Save &amp; Continue
                            </button>
                            <button x-show="addStep === 3" @click="saveEmployee()" :disabled="isSaving"
                                class="px-7 py-2.5 text-sm font-semibold rounded-lg transition-all duration-200"
                                :class="isSaving ? 'bg-gray-400 text-white cursor-not-allowed' : 'bg-blue-600 text-white hover:bg-blue-700'">
                                <span x-show="!isSaving">Save Employee</span>
                                <span x-show="isSaving" class="flex items-center gap-2">
                                    <svg class="animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                                    </svg>
                                    Saving...
                                </span>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
            <!-- ===================== END ADD EMPLOYEE MODAL ===================== -->

            <!-- ===================== EMPLOYEE DETAILS MODAL ===================== -->
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
                    class="bg-white rounded-2xl shadow-2xl w-full max-w-2xl overflow-hidden relative"
                    @click.stop x-show="selectedEmployee">
                    <button @click="closeModal"
                        class="absolute top-6 right-6 w-10 h-10 flex items-center justify-center rounded-full border-2 border-gray-300 text-gray-500 hover:border-gray-500 hover:text-gray-700 hover:bg-gray-100 transition-all duration-200 z-10 bg-white">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                    <div class="px-8 pt-8 pb-2">
                        <h2 class="text-2xl font-bold text-gray-900">Employee Details</h2>
                    </div>
                    <div class="px-8 pb-6 space-y-5 overflow-y-auto" style="max-height: calc(90vh - 200px);">

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Full Name</label>
                            <div class="grid grid-cols-12 gap-3">
                                <div class="col-span-5">
                                    <input type="text" x-model="selectedEmployee.first_name" placeholder="First Name"
                                        :readonly="!isEditMode"
                                        @input="if(isEditMode) selectedEmployee.first_name = $event.target.value.replace(/[^a-zA-Z\s\-']/g, '')"
                                        minlength="2" maxlength="50"
                                        :class="{'bg-gray-50 cursor-not-allowed': !isEditMode, 'bg-white': isEditMode}"
                                        class="w-full px-4 py-2.5 text-sm border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all duration-200">
                                </div>
                                <div class="col-span-5">
                                    <input type="text" x-model="selectedEmployee.last_name" placeholder="Last Name"
                                        :readonly="!isEditMode"
                                        @input="if(isEditMode) selectedEmployee.last_name = $event.target.value.replace(/[^a-zA-Z\s\-']/g, '')"
                                        minlength="2" maxlength="50"
                                        :class="{'bg-gray-50 cursor-not-allowed': !isEditMode, 'bg-white': isEditMode}"
                                        class="w-full px-4 py-2.5 text-sm border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all duration-200">
                                </div>
                                <div class="col-span-2">
                                    <input type="text" x-model="selectedEmployee.mi" placeholder="MI" minlength="1" maxlength="2"
                                        :readonly="!isEditMode"
                                        @input="if(isEditMode) selectedEmployee.mi = selectedEmployee.mi.replace(/[^a-zA-Z]/g, '')"
                                        :class="{'bg-gray-50 cursor-not-allowed': !isEditMode, 'bg-white': isEditMode}"
                                        class="w-full px-3 py-2.5 text-sm border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent text-center uppercase transition-all duration-200">
                                </div>
                            </div>
                            <div class="mt-2 relative">
                                <select x-model="selectedEmployee.suffix" :disabled="!isEditMode"
                                    :class="{'bg-gray-50 cursor-not-allowed': !isEditMode, 'bg-white': isEditMode}"
                                    class="w-full px-4 py-2.5 text-sm border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 appearance-none transition-all duration-200">
                                    <option value="">None</option>
                                    <option value="Sr.">Sr.</option>
                                    <option value="Jr.">Jr.</option>
                                    <option value="II">II</option>
                                    <option value="III">III</option>
                                </select>
                                <div class="pointer-events-none absolute inset-y-0 right-2 flex items-center text-gray-400">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                                </div>
                            </div>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Email Address</label>
                            <input type="email" x-model="selectedEmployee.email"
                                :readonly="!isEditMode"
                                :class="{'bg-gray-50 cursor-not-allowed': !isEditMode, 'bg-white': isEditMode}"
                                class="w-full px-4 py-2.5 text-sm border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all duration-200">
                            <p x-show="isEditMode" class="text-xs text-yellow-600 mt-1">⚠ Changing email will send a new verification link.</p>
                        </div>

                        <!-- Department: locked display -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Department</label>
                            <input type="text" x-model="selectedEmployee.department" readonly
                                class="w-full px-4 py-2.5 text-sm border border-gray-300 rounded-lg bg-gray-50 cursor-not-allowed transition-all duration-200">
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Job Title</label>
                            <div class="relative">
                                <select x-model="selectedEmployee.job_title_id"
                                    :disabled="!isEditMode"
                                    :class="{'bg-gray-50 cursor-not-allowed': !isEditMode, 'bg-white': isEditMode}"
                                    class="w-full px-4 py-2.5 text-sm border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 appearance-none transition-all duration-200">
                                    <option value="" disabled>Choose job title</option>
                                    <template x-for="jt in jobTitles" :key="jt.id">
                                        <option :value="jt.id" x-text="jt.title"></option>
                                    </template>
                                </select>
                                <div class="pointer-events-none absolute inset-y-0 right-2 flex items-center text-gray-400">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                                </div>
                            </div>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Role</label>
                            <div class="relative">
                                <select x-model="selectedEmployee.role"
                                    :disabled="!isEditMode"
                                    :class="{'bg-gray-50 cursor-not-allowed': !isEditMode, 'bg-white': isEditMode}"
                                    class="w-full px-4 py-2.5 text-sm border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 appearance-none transition-all duration-200">
                                    <option value="supervisor">Supervisor</option>
                                    <option value="employee">Employee</option>
                                </select>
                                <div class="pointer-events-none absolute inset-y-0 right-2 flex items-center text-gray-400">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                                </div>
                            </div>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Date Hired</label>
                            <input type="date" x-model="selectedEmployee.date_hired"
                                :readonly="!isEditMode"
                                :class="{'bg-gray-50 cursor-not-allowed': !isEditMode, 'bg-white': isEditMode}"
                                class="w-full px-4 py-2.5 text-sm border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 transition-all duration-200">
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Contact Number</label>
                            <input type="text" x-model="selectedEmployee.contact_number"
                                :readonly="!isEditMode"
                                maxlength="15"
                                @input="if(isEditMode) selectedEmployee.contact_number = selectedEmployee.contact_number.replace(/[^0-9]/g, '')"
                                :class="{'bg-gray-50 cursor-not-allowed': !isEditMode, 'bg-white': isEditMode}"
                                class="w-full px-4 py-2.5 text-sm border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 transition-all duration-200">
                        </div>

                    </div>
                    <div class="px-8 py-5 bg-white border-t border-gray-100 flex justify-end space-x-3">
                        <button x-show="!isEditMode" @click="enableEditMode"
                            class="px-8 py-2.5 bg-white text-gray-700 text-sm font-medium rounded-xl border border-gray-300 hover:bg-gray-50 transition-all duration-200">
                            Edit
                        </button>
                        <button x-show="isEditMode" @click="isEditMode = false"
                            class="px-8 py-2.5 bg-white text-gray-700 text-sm font-medium rounded-xl border border-gray-300 hover:bg-gray-50 transition-all duration-200">
                            Cancel
                        </button>
                        <button x-show="isEditMode" @click="saveChanges()" :disabled="isSaving"
                            :class="isSaving ? 'bg-gray-400 cursor-not-allowed' : 'bg-blue-500 hover:bg-blue-600 cursor-pointer'"
                            class="px-8 py-2.5 text-white text-sm font-medium rounded-xl transition-all duration-200">
                            <span x-show="!isSaving">Save</span>
                            <span x-show="isSaving" class="flex items-center gap-2">
                                <svg class="animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                                </svg>
                                Saving...
                            </span>
                        </button>
                    </div>
                </div>
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

                    <!-- Sort only filter (no department filter since supervisor sees one dept) -->
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
                            class="absolute left-0 mt-2 w-56 bg-white rounded-2xl shadow-lg border border-gray-100 z-50 overflow-hidden">
                            <div class="px-5 pt-5 pb-3 flex items-center gap-2">
                                <svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"/>
                                </svg>
                                <span class="text-base font-semibold text-gray-800">Sort</span>
                            </div>
                            <div class="px-5 pb-4 space-y-1">
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
                            <div class="px-5 py-3 border-t border-gray-100 flex items-center justify-between">
                                <button @click="clearFilters" class="text-sm text-gray-400 hover:text-gray-600 transition-colors duration-150">Reset</button>
                                <button @click="applyFilters" class="px-5 py-2 bg-blue-600 text-white text-sm font-medium rounded-lg hover:bg-blue-700 transition-all duration-200">Apply</button>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="flex items-center space-x-3">
                    <button @click="showManageDepartment = true"
                        class="flex-1 sm:flex-none px-5 py-3 bg-gradient-to-r from-blue-600 to-blue-700 text-white text-sm font-medium rounded-xl hover:from-blue-700 hover:to-blue-800 transition-all duration-300 hover:shadow-xl transform hover:scale-105">
                        <span class="flex items-center justify-center">
                            <span class="hidden sm:inline">Manage Department</span>
                            <span class="sm:hidden">Dept</span>
                        </span>
                    </button>
                    <button @click="openAddEmployee()"
                        class="flex-1 sm:flex-none px-5 py-3 bg-gradient-to-r from-blue-600 to-blue-700 text-white text-sm font-medium rounded-xl hover:from-blue-700 hover:to-blue-800 transition-all duration-300 hover:shadow-xl transform hover:scale-105 group">
                        <span class="flex items-center justify-center space-x-2">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v3m0 0v3m0-3h3m-3 0H9m12 0a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                            <span class="hidden sm:inline">Add Employee</span>
                            <span class="sm:hidden">Add</span>
                        </span>
                    </button>
                </div>
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
                        <template x-for="employee in filteredEmployees" :key="employee.email">
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

                <!-- Table Footer -->
                <div x-show="filteredEmployees.length > 0" class="px-4 sm:px-6 py-4 border-t border-gray-100 bg-gray-50">
                    <div class="flex flex-col sm:flex-row items-center justify-between gap-4">
                        <p class="text-sm text-gray-500">Showing <span class="font-medium" x-text="filteredEmployees.length"></span> of <span class="font-medium" x-text="employees.length"></span> employees</p>
                        <div class="flex items-center space-x-2">
                            <button class="w-8 h-8 flex items-center justify-center rounded-lg border border-gray-200 bg-white text-gray-600 hover:bg-blue-50 hover:text-blue-600 hover:border-blue-200 transition-all duration-200 disabled:opacity-50" disabled>
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                            </button>
                            <button class="w-8 h-8 flex items-center justify-center rounded-lg bg-blue-600 text-white text-sm font-medium hover:bg-blue-700 transition-all duration-200">1</button>
                            <button class="w-8 h-8 flex items-center justify-center rounded-lg border border-gray-200 bg-white text-gray-600 hover:bg-blue-50 hover:text-blue-600 hover:border-blue-200 transition-all duration-200">
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
    @media (max-width: 1024px) { .lg\:ml-20, .lg\:ml-72 { margin-left: 0 !important; } }
    [x-cloak] { display: none !important; }
</style>

@endsection
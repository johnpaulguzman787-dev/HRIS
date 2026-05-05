@php
    // This directive ensures the layout is used, but the blade content is the main focus
@endphp
@extends('layouts.app')

@section('title', 'Employees Directory - Medisource HRMS')

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
        this.$watch('verificationFilter', () => { this.currentPage = 1; });
    },
    mobileMenuOpen: false,
    showAddEmployee: false,
    showAddDepartment: false,
    showManageDepartment: false,
    showEmployeeDetails: false,
    showFilters: false,
    selectedEmployee: null,
    _originalEmployee: null,
    isEditMode: false,
    empTab: 'basic',
    employeeDocuments: [],
    isLoadingDocs: false,
    isUploadingDoc: false,
    searchQuery: '',
    selectedDepartments: [],
    selectedSort: '',
    verificationFilter: '',
    currentPage: 1,
    perPage: 10,

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
        department_id: '', job_title_id: '',
        employment_type: '', employment_status: '',
        contract_period: '', end_date: ''
    },

    employees: window.employeeData,
    departments: window.departmentData,
    jobTitles: window.jobTitleData,

    get jobTitlesForDept() {
        if (!this.newEmployeeForm.department_id) return [];
        return this.jobTitles.filter(j => j.department_id == this.newEmployeeForm.department_id);
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
            department_id: '', job_title_id: '',
            employment_type: '', employment_status: '',
            contract_period: '', end_date: ''
        };
        this.showAddEmployee = true;
        document.body.style.overflow = 'hidden';
    },

    closeAddEmployee() {
        this.showAddEmployee = false;
        this.formErrors = {};
        this.isSaving = false;
        document.body.style.overflow = '';
    },

    nextStep() { if (this.addStep < 3) this.addStep++; },
    saveAndContinue() { this.nextStep(); },
    validEmail(email) { return /^[^\s@]+@[a-zA-Z][^\s@]*\.[a-zA-Z]{2,}$/.test(email); },
    validateContactNo(val) {
        const digits = val.replace(/[^0-9]/g, '');
        if (/[^0-9]/.test(val)) {
            this.formErrors = {...this.formErrors, contact_no: ['Contact number must contain digits only.']};
        } else if (digits.length > 0 && digits.length !== 11) {
            this.formErrors = {...this.formErrors, contact_no: ['Contact number must be exactly 11 digits (e.g. 09XXXXXXXXX).']};
        } else {
            const {contact_no, ...rest} = this.formErrors;
            this.formErrors = rest;
        }
        return digits;
    },
    validateEmail(val) {
        if (val && !this.validEmail(val)) {
            this.formErrors = {...this.formErrors, email: ['Please enter a valid email address.']};
        } else {
            const {email, ...rest} = this.formErrors;
            this.formErrors = rest;
        }
    },

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
            department_id:     this.newEmployeeForm.department_id,
            job_title_id:      this.newEmployeeForm.job_title_id,
            employment_type:   this.newEmployeeForm.employment_type,
            employment_status: this.newEmployeeForm.employment_status,
            contract_period:   this.newEmployeeForm.contract_period,
            end_date:          this.newEmployeeForm.end_date,
            _token:            document.querySelector('meta[name=csrf-token]').getAttribute('content')
        };

        try {
            const response = await fetch('{{ route('employees.store') }}', {
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

    departmentForm: { name: '', jobTitles: [], newJobTitle: '' },

    showDepartmentDetails: false,
    selectedDepartment: null,
    isDeptEditMode: false,

    viewDepartmentDetails(dept) {
        this.selectedDepartment = {
            id:           dept.id,
            name:         dept.name,
            jobTitles:    dept.job_titles.map(j => ({ id: j.id, title: j.title })),
            newJobTitle:  ''
        };
        this.isDeptEditMode = false;
        this.showDepartmentDetails = true;
        document.body.style.overflow = 'hidden';
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
            const response = await fetch(`/employees/departments/${this.selectedDepartment.id}`, {
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
        if (this.selectedDepartments.length > 0) {
            result = result.filter(emp =>
                this.selectedDepartments.includes(emp.department)
            );
        }
        if (this.verificationFilter === 'verified') {
            result = result.filter(e => e.is_verified);
        } else if (this.verificationFilter === 'unverified') {
            result = result.filter(e => !e.is_verified);
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
    get activeFilterCount() { return this.selectedDepartments.length + (this.selectedSort ? 1 : 0) + (this.verificationFilter ? 1 : 0); },
    resetDepartmentForm() { this.departmentForm = { name: '', jobTitles: [], newJobTitle: '' }; },

    async saveDepartment() {
        if (!this.departmentForm.name.trim()) {
            this.showToast('Department name cannot be empty.', 'error');
            return;
        }

        if (this.departmentForm.newJobTitle.trim()) {
            this.departmentForm.jobTitles.push(this.departmentForm.newJobTitle.trim());
            this.departmentForm.newJobTitle = '';
        }

        this.isSaving = true;

        try {
            const response = await fetch('/employees/departments', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').getAttribute('content')
                },
                body: JSON.stringify({ name: this.departmentForm.name.trim(), job_titles: this.departmentForm.jobTitles })
            });

            const data = await response.json();

            if (response.ok && data.success) {
                this.departments.push(data.department);
                if (data.department.job_titles) {
                    data.department.job_titles.forEach(jt => {
                        this.jobTitles.push({
                            id:            jt.id,
                            title:         jt.title,
                            department_id: data.department.id
                        });
                    });
                }
                this.showAddDepartment = false;
                this.resetDepartmentForm();
                this.isSaving = false;
                this.showToast('Department added successfully!', 'success');
                setTimeout(() => window.location.reload(), 1500);
                return;
            }

            this.showToast(data.message || 'Something went wrong.', 'error');
            this.isSaving = false;

        } catch (err) {
            this.showToast('Network error. Please check your connection.', 'error');
            this.isSaving = false;
        }
    },

    isResendingVerification: false,

    async resendVerificationEmail() {
        if (!this.selectedEmployee) return;
        this.isResendingVerification = true;
        try {
            const res = await fetch(`/employees/${this.selectedEmployee.id}/resend-verification`, {
                method: 'POST',
                headers: {
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').getAttribute('content')
                }
            });
            const data = await res.json();
            this.showToast(data.message || (res.ok ? 'Verification email sent!' : 'Failed to send.'), res.ok ? 'success' : 'error');
        } catch(e) {
            this.showToast('Network error. Please try again.', 'error');
        }
        this.isResendingVerification = false;
    },

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
            is_verified:       employee.is_verified ?? true,
        };
        this.isEditMode = false;
        this.empTab = 'basic';
        this.employeeDocuments = [];
        this.showEmployeeDetails = true;
        this.loadDocuments(employee.id);
        setTimeout(() => { document.body.style.overflow = 'hidden'; }, 100);
    },

    enableEditMode() { this._originalEmployee = { ...this.selectedEmployee }; this.isEditMode = true; },
    cancelEdit() { if (this._originalEmployee) Object.assign(this.selectedEmployee, this._originalEmployee); this.isEditMode = false; },

    async saveChanges() {
        if (!this.selectedEmployee.contact_number || this.selectedEmployee.contact_number.length < 10) {
            this.showToast('Contact number must be at least 10 digits.', 'error');
            return;
        }

        this.isSaving = true;

        const payload = {
            first_name:      this.selectedEmployee.first_name,
            last_name:       this.selectedEmployee.last_name,
            mi:              this.selectedEmployee.mi,
            suffix:          this.selectedEmployee.suffix,
            email:           this.selectedEmployee.email,
            contact_no:      this.selectedEmployee.contact_number,
            department_id:   this.selectedEmployee.department_id,
            job_title_id:    this.selectedEmployee.job_title_id,
            role:            this.selectedEmployee.role,
            start_date:        this.selectedEmployee.date_hired,
            employment_type:   this.selectedEmployee.employment_type,
            employment_status: this.selectedEmployee.employment_status,
            _method:           'PUT',
            _token:            document.querySelector('meta[name=csrf-token]').getAttribute('content')
        };

        try {
            const response = await fetch(`/employees/${this.selectedEmployee.id}`, {
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
                    const emailChanged = this.employees[empIdx].email !== this.selectedEmployee.email;
                    this.employees[empIdx].first_name   = this.selectedEmployee.first_name;
                    this.employees[empIdx].last_name    = this.selectedEmployee.last_name;
                    this.employees[empIdx].mi           = this.selectedEmployee.mi;
                    this.employees[empIdx].suffix       = this.selectedEmployee.suffix;
                    this.employees[empIdx].email        = this.selectedEmployee.email;
                    this.employees[empIdx].contact_no   = this.selectedEmployee.contact_number;
                    if (emailChanged) { this.employees[empIdx].is_verified = false; this.selectedEmployee.is_verified = false; }
                    this.employees[empIdx].department_id = this.selectedEmployee.department_id;
                    this.employees[empIdx].department   = dept ? dept.name : this.employees[empIdx].department;
                    this.employees[empIdx].job_title_id = this.selectedEmployee.job_title_id;
                    this.employees[empIdx].job_title    = newJobTitle ? newJobTitle.title : this.employees[empIdx].job_title;
                    this.employees[empIdx].role              = this.selectedEmployee.role;
                    this.employees[empIdx].start_date        = this.selectedEmployee.date_hired;
                    this.employees[empIdx].employment_type   = this.selectedEmployee.employment_type;
                    this.employees[empIdx].employment_status = this.selectedEmployee.employment_status;
                    this.employees[empIdx].status            = this.selectedEmployee.employment_status;
                    this.employees[empIdx].name              = [this.selectedEmployee.first_name, this.selectedEmployee.mi ? this.selectedEmployee.mi + '.' : '', this.selectedEmployee.last_name].filter(Boolean).join(' ');
                    this.employees[empIdx].avatar            = (this.selectedEmployee.first_name.charAt(0) + this.selectedEmployee.last_name.charAt(0)).toUpperCase();
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

    async loadDocuments(employeeId) {
        this.isLoadingDocs = true;
        try {
            const res = await fetch(`/employees/${employeeId}/documents`, {
                headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').getAttribute('content') }
            });
            const data = await res.json();
            this.employeeDocuments = data.documents || [];
        } catch (e) { console.error('Failed to load documents', e); }
        this.isLoadingDocs = false;
    },

    async handleDocUpload(event) {
        const file = event.target.files[0];
        if (!file) return;
        const ext = file.name.split('.').pop().toLowerCase();
        if (!['pdf', 'doc', 'docx'].includes(ext)) {
            this.showToast('Only PDF and Word documents (doc/docx) are allowed.', 'error');
            event.target.value = '';
            return;
        }
        this.isUploadingDoc = true;
        const formData = new FormData();
        formData.append('document', file);
        try {
            const res = await fetch(`/employees/${this.selectedEmployee.id}/documents`, {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').getAttribute('content'), 'Accept': 'application/json' },
                body: formData
            });
            const data = await res.json();
            if (res.ok && data.success) {
                this.employeeDocuments.push(data.document);
                this.showToast('Document uploaded!', 'success');
            } else {
                this.showToast(data.message || 'Upload failed.', 'error');
            }
        } catch (e) { this.showToast('Network error during upload.', 'error'); }
        this.isUploadingDoc = false;
        event.target.value = '';
    },

    async deleteDocument(docId) {
        this.showConfirm('Are you sure you want to delete this document? This cannot be undone.', async () => {
        try {
            const res = await fetch(`/employees/documents/${docId}`, {
                method: 'DELETE',
                headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').getAttribute('content'), 'Accept': 'application/json' }
            });
            const data = await res.json();
            if (res.ok && data.success) {
                this.employeeDocuments = this.employeeDocuments.filter(d => d.id !== docId);
                this.showToast('Document deleted.', 'success');
            } else {
                this.showToast(data.message || 'Delete failed.', 'error');
            }
        } catch (e) { this.showToast('Network error.', 'error'); }
        });
    },

    deleteDepartment(dept) {
        if (dept.employees_count > 0) {
            this.showAlert('This department still has verified employees. Please reassign them to another department before deleting.', 'error');
            return;
        }
        this.showConfirm('Delete department: ' + dept.name + '? Any unverified employees in this department will have their department cleared.', async () => {
            try {
                const res = await fetch(`/employees/departments/${dept.id}`, {
                    method: 'DELETE',
                    headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').getAttribute('content'), 'Accept': 'application/json' }
                });
                const data = await res.json();
                if (res.ok && data.success) {
                    this.departments = this.departments.filter(d => d.id !== dept.id);
                    this.jobTitles   = this.jobTitles.filter(j => !dept.job_titles.some(jt => jt.id === j.id));
                    // Clear dept from any unverified employees shown in the list
                    this.employees.forEach(e => {
                        if (!e.is_verified && e.department_id === dept.id) {
                            e.department_id = null;
                            e.department = '';
                            e.job_title_id = null;
                            e.job_title = '';
                        }
                    });
                    this.showAlert('Department deleted successfully.', 'success');
                } else {
                    this.showAlert(data.message || 'Could not delete department.', 'error');
                }
            } catch (e) { this.showAlert('Network error. Please try again.', 'error'); }
        });
    },

    clearSearch() { this.searchQuery = ''; },
    clearFilters() { this.selectedDepartments = []; this.selectedSort = ''; this.verificationFilter = ''; },
    applyFilters() { this.showFilters = false; },

    alertModal: { show: false, message: '', type: 'success' },
    showAlert(message, type = 'success') { this.alertModal = { show: true, message, type }; },
    showToast(message, type = 'success') { this.showAlert(message, type); },

    confirmModal: { show: false, message: '', onConfirm: null },
    showConfirm(message, onConfirm) { this.confirmModal = { show: true, message, onConfirm }; },
    confirmOk() { if (this.confirmModal.onConfirm) this.confirmModal.onConfirm(); this.confirmModal.show = false; },
    confirmCancel() { this.confirmModal.show = false; },
}"
    x-init="if (new URLSearchParams(window.location.search).get('action') === 'add') openAddEmployee()"
    class="flex h-screen overflow-hidden bg-gray-50" @keydown.escape.window="closeModal(); closeAddEmployee()">

    {{-- DESKTOP SIDEBAR (hidden on mobile) --}}
    <div class="hidden lg:block">
        @include('admin.admin_sidebar', ['activeMenu' => 'employees'])
    </div>

    {{-- MOBILE DRAWER --}}
    <div x-show="mobileMenuOpen"
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         class="fixed inset-0 z-50 lg:hidden"
         style="display:none;">
        <div class="absolute inset-0 bg-black/40" @click="mobileMenuOpen = false"></div>
        <div x-show="mobileMenuOpen"
             x-transition:enter="transition ease-out duration-250"
             x-transition:enter-start="-translate-x-full"
             x-transition:enter-end="translate-x-0"
             x-transition:leave="transition ease-in duration-200"
             x-transition:leave-start="translate-x-0"
             x-transition:leave-end="-translate-x-full"
             class="relative w-72 h-full bg-white shadow-2xl overflow-y-auto">
            @include('admin.admin_sidebar', ['activeMenu' => 'employees'])
        </div>
    </div>

    <main class="flex-1 overflow-y-auto transition-all duration-300 w-full"
          :class="sidebarCollapsed ? 'lg:ml-20' : 'lg:ml-64'">

        <!-- Top Header -->
        <header class="anim-fade bg-gradient-to-br from-blue-500 to-blue-700 sticky top-0 z-10 shadow-lg mt-3 mx-3 lg:mt-4 lg:mx-4 rounded-2xl">
            <div class="px-4 sm:px-6 lg:px-8 py-4 flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <button @click="mobileMenuOpen = true"
                            class="lg:hidden p-1.5 rounded-lg hover:bg-white/20 transition-colors">
                        <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M4 6h16M4 12h16M4 18h16"/>
                        </svg>
                    </button>
                    <div>
                        <h1 class="text-xl sm:text-2xl font-bold text-white">Employees</h1>
                        <p class="text-xs sm:text-sm text-blue-100 mt-1">Employee Directory</p>
                    </div>
                </div>
                <div class="flex items-center space-x-2 sm:space-x-4">
                    <x-notification-bell />
                </div>
            </div>
        </header>

        <div class="p-4 sm:p-6 lg:p-8 mt-4 page-fade-in">

            <!-- ===================== ALERT MODAL ===================== -->
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

                    <div class="w-16 h-16 rounded-2xl flex items-center justify-center mb-5 shadow-sm"
                        :class="alertModal.type === 'success' ? 'bg-blue-50' : 'bg-red-50'">
                        <template x-if="alertModal.type === 'success'">
                            <svg class="w-8 h-8 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
                            </svg>
                        </template>
                        <template x-if="alertModal.type === 'error'">
                            <svg class="w-8 h-8 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/>
                            </svg>
                        </template>
                    </div>

                    <p class="text-sm font-semibold text-gray-800 mb-1"
                        x-text="alertModal.type === 'success' ? 'Success' : 'Action Failed'"></p>
                    <p class="text-sm text-gray-500 mb-7 leading-snug" x-text="alertModal.message"></p>

                    <button @click="alertModal.show = false"
                        class="px-8 py-2.5 text-white text-sm font-semibold rounded-xl active:scale-95 transition-all duration-150 shadow-md hover:shadow-lg"
                        :class="alertModal.type === 'success' ? 'bg-blue-600 hover:bg-blue-700' : 'bg-blue-600 hover:bg-blue-700'">
                        OK
                    </button>
                </div>
            </div>

            <!-- ===================== CONFIRM MODAL ===================== -->
            <div x-show="confirmModal.show" x-cloak
                class="fixed inset-0 z-[9999] flex items-center justify-center p-4"
                style="background: rgba(0,0,0,0.45); backdrop-filter: blur(6px);">
                <div x-show="confirmModal.show"
                    x-transition:enter="transition-all duration-200 ease-out"
                    x-transition:enter-start="opacity-0 scale-90"
                    x-transition:enter-end="opacity-100 scale-100"
                    x-transition:leave="transition-all duration-150 ease-in"
                    x-transition:leave-start="opacity-100 scale-100"
                    x-transition:leave-end="opacity-0 scale-90"
                    class="bg-white rounded-2xl shadow-2xl flex flex-col items-center justify-center text-center p-10"
                    style="width: 420px; min-height: 260px;"
                    @click.stop>
                    <div class="w-16 h-16 rounded-2xl bg-blue-50 flex items-center justify-center mb-5 shadow-sm">
                        <svg class="w-8 h-8 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/>
                        </svg>
                    </div>
                    <p class="text-sm font-semibold text-gray-800 mb-1">Confirm Action</p>
                    <p class="text-sm text-gray-500 mb-7 leading-snug" x-text="confirmModal.message"></p>
                    <div class="flex gap-3">
                        <button @click="confirmCancel()"
                            class="px-7 py-2.5 text-sm font-semibold rounded-xl border border-gray-200 text-gray-600 hover:bg-gray-50 transition-all">
                            Cancel
                        </button>
                        <button @click="confirmOk()"
                            class="px-7 py-2.5 text-sm font-semibold rounded-xl bg-blue-600 hover:bg-blue-700 text-white transition-all shadow-md">
                            Confirm
                        </button>
                    </div>
                </div>
            </div>

            <!-- ===================== MANAGE DEPARTMENTS MODAL - FULLY MOBILE RESPONSIVE ===================== -->
            <div x-show="showManageDepartment" x-cloak
                class="fixed inset-0 z-50 flex items-center justify-center p-2 sm:p-4"
                style="background: rgba(0,0,0,0.5); backdrop-filter: blur(8px);"
                @click.self="showManageDepartment = false; document.body.style.overflow = 'auto'">
                <div x-show="showManageDepartment"
                    x-transition:enter="transition-all duration-300 ease-out"
                    x-transition:enter-start="opacity-0 scale-95 translate-y-4"
                    x-transition:enter-end="opacity-100 scale-100 translate-y-0"
                    x-transition:leave="transition-all duration-200 ease-in"
                    x-transition:leave-start="opacity-100 scale-100 translate-y-0"
                    x-transition:leave-end="opacity-0 scale-95 translate-y-4"
                    class="bg-white rounded-2xl shadow-2xl w-full max-w-2xl overflow-hidden relative flex flex-col"
                    style="max-height: 92vh;"
                    @click.stop>

                    <!-- Header -->
                    <div class="px-4 sm:px-8 pt-5 sm:pt-7 pb-3 sm:pb-4 flex items-center justify-between border-b border-gray-100 flex-shrink-0">
                        <h2 class="text-xl sm:text-2xl font-bold text-gray-900">Manage Departments</h2>
                        <button @click="showManageDepartment = false; document.body.style.overflow = 'auto'"
                            class="w-9 h-9 sm:w-10 sm:h-10 flex items-center justify-center rounded-full border-2 border-gray-300 text-gray-500 hover:border-gray-500 hover:text-gray-700 hover:bg-gray-100 transition-all duration-200 bg-white">
                            <svg class="w-4 h-4 sm:w-5 sm:h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                        </button>
                    </div>

                    <!-- Add Department Button -->
                    <div class="px-4 sm:px-8 py-3 sm:py-4 border-b border-gray-100 flex-shrink-0">
                        <button @click="showManageDepartment = false; showAddDepartment = true; resetDepartmentForm()"
                            class="w-full sm:w-auto flex items-center justify-center gap-2 px-4 sm:px-5 py-2.5 bg-blue-600 text-white text-sm font-semibold rounded-lg hover:bg-blue-700 transition-all duration-200 shadow-sm">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/>
                            </svg>
                            Add New Department
                        </button>
                    </div>

                    <!-- Departments List - Mobile Optimized -->
                    <div class="flex-1 overflow-y-auto p-4 sm:p-6">
                        <!-- Mobile Card View (visible on small screens) -->
                        <div class="space-y-3 sm:hidden">
                            <template x-for="dept in departments" :key="dept.id">
                                <div class="bg-white border border-gray-200 rounded-xl p-4 shadow-sm hover:shadow-md transition-all duration-200">
                                    <div class="flex items-start justify-between mb-3">
                                        <h3 class="text-base font-bold text-gray-800" x-text="dept.name"></h3>
                                        <div class="flex gap-2">
                                            <button @click="viewDepartmentDetails(dept)"
                                                class="px-3 py-1.5 bg-blue-500 text-white text-xs font-medium rounded-lg hover:bg-blue-600 transition-all duration-200 shadow-sm">
                                                Edit
                                            </button>
                                            <button @click="deleteDepartment(dept)"
                                                class="px-3 py-1.5 bg-red-500 text-white text-xs font-medium rounded-lg hover:bg-red-600 transition-all duration-200 shadow-sm">
                                                Delete
                                            </button>
                                        </div>
                                    </div>
                                    <div class="pt-2 border-t border-gray-100">
                                        <p class="text-xs text-gray-500 mb-2">Job Titles</p>
                                        <div class="flex flex-wrap gap-1.5">
                                            <template x-for="job in dept.job_titles" :key="job.id">
                                                <span class="inline-block px-2 py-1 bg-gray-100 text-gray-600 text-xs rounded-md" x-text="job.title"></span>
                                            </template>
                                            <template x-if="dept.job_titles.length === 0">
                                                <span class="text-xs text-gray-400 italic">No job titles</span>
                                            </template>
                                        </div>
                                    </div>
                                </div>
                            </template>
                            <template x-if="departments.length === 0">
                                <div class="text-center py-8">
                                    <p class="text-sm text-gray-400">No departments found.</p>
                                </div>
                            </template>
                        </div>

                        <!-- Desktop Table View (visible on medium and up) -->
                        <div class="hidden sm:block overflow-x-auto">
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
                                                <div class="flex flex-wrap gap-1">
                                                    <template x-for="job in dept.job_titles" :key="job.id">
                                                        <span class="inline-block px-2 py-0.5 bg-gray-100 rounded text-xs" x-text="job.title"></span>
                                                    </template>
                                                    <template x-if="dept.job_titles.length === 0">
                                                        <span class="text-gray-400 text-xs">—</span>
                                                    </template>
                                                </div>
                                            </td>
                                            <td class="px-5 py-4 text-center align-middle">
                                                <div class="flex items-center justify-center gap-2">
                                                    <button @click="viewDepartmentDetails(dept)"
                                                        class="whitespace-nowrap px-4 py-2 bg-blue-500 text-white text-sm font-medium rounded-xl hover:bg-blue-600 transition-all duration-200 shadow-md hover:shadow-lg">Edit</button>
                                                    <button @click="deleteDepartment(dept)"
                                                        class="whitespace-nowrap px-4 py-2 bg-red-500 text-white text-sm font-medium rounded-xl hover:bg-red-600 transition-all duration-200 shadow-md hover:shadow-lg">Delete</button>
                                                </div>
                                            </td>
                                        </tr>
                                    </template>
                                    <template x-if="departments.length === 0">
                                        <tr>
                                            <td colspan="3" class="px-5 py-8 text-center text-sm text-gray-400">No departments found.</td>
                                        </tr>
                                    </template>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Footer Note (optional) -->
                    <div class="px-4 sm:px-8 py-3 border-t border-gray-100 bg-gray-50 flex-shrink-0">
                        <p class="text-xs text-gray-400 text-center sm:text-left">
                            <span x-text="departments.length"></span> department<span x-show="departments.length !== 1">s</span> total
                        </p>
                    </div>
                </div>
            </div>

            <!-- ===================== DEPARTMENT DETAILS MODAL ===================== -->
            <div x-show="showDepartmentDetails" x-cloak
                class="fixed inset-0 z-50 flex items-center justify-center p-4"
                style="background: rgba(0,0,0,0.5); backdrop-filter: blur(8px);"
                @click.self="showDepartmentDetails = false; isDeptEditMode = false; document.body.style.overflow = 'auto'">
                <div x-show="showDepartmentDetails"
                    x-transition:enter="transition-all duration-300 ease-out"
                    x-transition:enter-start="opacity-0 scale-95 translate-y-4"
                    x-transition:enter-end="opacity-100 scale-100 translate-y-0"
                    x-transition:leave="transition-all duration-200 ease-in"
                    x-transition:leave-start="opacity-100 scale-100 translate-y-0"
                    x-transition:leave-end="opacity-0 scale-95 translate-y-4"
                    class="bg-white rounded-2xl shadow-2xl w-full max-w-md overflow-hidden relative"
                    @click.stop>
                    <button @click="showDepartmentDetails = false; isDeptEditMode = false; document.body.style.overflow = 'auto'"
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

            <!-- ===================== ADD DEPARTMENT MODAL ===================== -->
            <div x-show="showAddDepartment" x-cloak
                class="fixed inset-0 z-50 flex items-center justify-center p-4"
                style="background: rgba(0,0,0,0.5); backdrop-filter: blur(8px);"
                @click.self="showAddDepartment = false; resetDepartmentForm(); document.body.style.overflow = 'auto'">
                <div x-show="showAddDepartment"
                    x-transition:enter="transition-all duration-300 ease-out"
                    x-transition:enter-start="opacity-0 scale-95 translate-y-4"
                    x-transition:enter-end="opacity-100 scale-100 translate-y-0"
                    x-transition:leave="transition-all duration-200 ease-in"
                    x-transition:leave-start="opacity-100 scale-100 translate-y-0"
                    x-transition:leave-end="opacity-0 scale-95 translate-y-4"
                    class="bg-white rounded-2xl shadow-2xl w-full max-w-md overflow-hidden relative"
                    @click.stop>
                    <button @click="showAddDepartment = false; resetDepartmentForm(); document.body.style.overflow = 'auto'"
                        class="absolute top-6 right-6 w-10 h-10 flex items-center justify-center rounded-full border-2 border-gray-300 text-gray-500 hover:border-gray-500 hover:text-gray-700 hover:bg-gray-100 transition-all duration-200 z-10 bg-white">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                    <div class="px-8 pt-8 pb-2">
                        <h2 class="text-2xl font-bold text-gray-900">Add Department</h2>
                    </div>
                    <div class="px-8 pb-6 space-y-5 mt-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Department Name</label>
                            <input type="text" x-model="departmentForm.name" placeholder="Enter department name"
                                class="w-full px-4 py-2.5 text-sm border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all duration-200">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Job Titles</label>
                            <div class="border border-gray-300 rounded-lg px-4 py-3 space-y-1 min-h-[120px]">
                                <template x-for="(jt, index) in departmentForm.jobTitles" :key="index">
                                    <div class="flex items-center justify-between py-1">
                                        <div class="flex items-center gap-2">
                                            <span class="w-1.5 h-1.5 bg-blue-400 rounded-full flex-shrink-0"></span>
                                            <span class="text-sm text-gray-700" x-text="jt"></span>
                                        </div>
                                        <button @click="departmentForm.jobTitles.splice(index, 1)"
                                            class="text-gray-400 hover:text-red-500 transition-colors duration-150 ml-2">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                                            </svg>
                                        </button>
                                    </div>
                                </template>
                                <input type="text" x-model="departmentForm.newJobTitle"
                                    @keydown.enter.prevent="departmentForm.newJobTitle.trim() && (departmentForm.jobTitles.push(departmentForm.newJobTitle.trim()), departmentForm.newJobTitle = '')"
                                    placeholder="Type job title and press Enter"
                                    class="w-full text-sm text-gray-600 placeholder-gray-400 border-none outline-none bg-transparent pt-1">
                            </div>
                        </div>
                    </div>
                    <div class="px-8 py-5 flex justify-end">
                        <button @click="saveDepartment()" :disabled="isSaving"
                            :class="isSaving ? 'bg-gray-400 cursor-not-allowed' : 'bg-blue-600 hover:bg-blue-700'"
                            class="px-6 py-2.5 text-white text-sm font-medium rounded-lg transition-all duration-200">
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
                    class="bg-white rounded-2xl shadow-2xl w-full max-w-lg overflow-hidden relative flex flex-col"
                    style="max-height: 92vh; max-height: 90dvh;"
                    @click.stop>

                    <div class="px-8 pt-7 pb-0 flex items-center justify-between shrink-0">
                        <h2 class="text-2xl font-bold text-gray-900">Add Employee</h2>
                        <button @click="closeAddEmployee()"
                            class="w-9 h-9 flex items-center justify-center rounded-full border-2 border-gray-300 text-gray-400 hover:border-gray-500 hover:text-gray-600 transition-all duration-150">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                        </button>
                    </div>

                    <div class="px-8 pt-5 pb-0 shrink-0">
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
                    <div x-show="addStep === 1" class="px-8 pt-5 pb-0 space-y-4 overflow-y-auto flex-1">

                        <div>
                            <label class="block text-sm font-semibold text-gray-800 mb-1.5">Name</label>
                            <div class="flex flex-col sm:flex-row gap-2">
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
                                <div class="w-full sm:w-14">
                                    <input type="text" x-model="newEmployeeForm.mi" placeholder="MI" minlength="1" maxlength="2"
                                        @input="newEmployeeForm.mi = $event.target.value.replace(/[^a-zA-Z]/g, '')"
                                        class="w-full px-2 py-2.5 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-1 focus:ring-blue-400 text-center uppercase text-gray-700 placeholder-gray-400">
                                </div>
                                <div class="relative w-full sm:w-36">
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

                        <div class="flex flex-col sm:flex-row gap-3">
                            <div class="flex-1">
                                <label class="block text-sm font-semibold text-gray-800 mb-1.5">Date of Birth</label>
                                <input type="date" x-model="newEmployeeForm.date_of_birth"
                                    :class="hasError('date_of_birth') ? 'border-red-400 focus:ring-red-400' : 'border-gray-200 focus:ring-blue-400'"
                                    class="w-full px-3 py-2.5 text-sm border rounded-lg focus:outline-none focus:ring-1 focus:border-transparent text-gray-400 placeholder-gray-400">
                                <p x-show="hasError('date_of_birth')" x-text="fieldError('date_of_birth')" class="text-xs text-red-500 mt-1"></p>
                            </div>
                            <div class="flex-1">
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
                                @input="validateEmail($event.target.value)"
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
                            <input type="text" x-model="newEmployeeForm.contact_no" placeholder="09XXXXXXXXX"
                                maxlength="11"
                                @input="newEmployeeForm.contact_no = validateContactNo($event.target.value)"
                                :class="hasError('contact_no') ? 'border-red-400 focus:ring-red-400' : 'border-gray-200 focus:ring-blue-400'"
                                class="w-full px-3 py-2.5 text-sm border rounded-lg focus:outline-none focus:ring-1 focus:border-transparent text-gray-700 placeholder-gray-400">
                            <p x-show="hasError('contact_no')" x-text="fieldError('contact_no')" class="text-xs text-red-500 mt-1"></p>
                        </div>

                        <div class="h-2"></div>
                    </div>

                    <!-- STEP 2: Job Information -->
                    <div x-show="addStep === 2" class="px-8 pt-5 pb-0 space-y-4 overflow-y-auto flex-1">

                        <div class="flex flex-col sm:flex-row gap-3">
                            <div class="flex-1">
                                <label class="block text-sm font-semibold text-gray-800 mb-1.5">Department</label>
                                <div class="relative">
                                    <select x-model="newEmployeeForm.department_id"
                                        @change="newEmployeeForm.job_title_id = ''"
                                        :class="hasError('department_id') ? 'border-red-400 focus:ring-red-400' : 'border-gray-200 focus:ring-blue-400'"
                                        class="w-full px-3 py-2.5 text-sm border rounded-lg focus:outline-none focus:ring-1 appearance-none bg-white text-gray-400">
                                        <option value="" disabled selected>Choose department</option>
                                        <template x-for="dept in departments" :key="dept.id">
                                            <option :value="dept.id" x-text="dept.name"></option>
                                        </template>
                                    </select>
                                    <div class="pointer-events-none absolute inset-y-0 right-2 flex items-center text-gray-400">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                                    </div>
                                </div>
                                <p x-show="hasError('department_id')" x-text="fieldError('department_id')" class="text-xs text-red-500 mt-1"></p>
                            </div>
                            <div class="flex-1">
                                <label class="block text-sm font-semibold text-gray-800 mb-1.5">Job Title</label>
                                <div class="relative">
                                    <select x-model="newEmployeeForm.job_title_id"
                                        :disabled="!newEmployeeForm.department_id"
                                        :class="hasError('job_title_id') ? 'border-red-400 focus:ring-red-400' : 'border-gray-200 focus:ring-blue-400'"
                                        class="w-full px-3 py-2.5 text-sm border rounded-lg focus:outline-none focus:ring-1 appearance-none bg-white text-gray-400 disabled:bg-gray-50 disabled:cursor-not-allowed">
                                        <option value="" disabled selected x-text="!newEmployeeForm.department_id ? 'Choose department first' : 'Choose job title'"></option>
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
                        </div>

                        <div class="flex flex-col sm:flex-row gap-3">
                            <div class="flex-1">
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
                            <div class="flex-1">
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

                        <div class="flex flex-col sm:flex-row gap-3">
                            <div class="flex-1">
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
                            <div class="flex-1">
                                <label class="block text-sm font-semibold text-gray-800 mb-1.5">End Date <span class="text-gray-400 font-normal">(optional)</span></label>
                                <input type="date" x-model="newEmployeeForm.end_date"
                                    class="w-full px-3 py-2.5 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-1 focus:ring-blue-400 text-gray-400">
                            </div>
                        </div>

                        <div class="h-2"></div>
                    </div>

                    <!-- STEP 3: Documents -->
                    <div x-show="addStep === 3" class="px-8 pt-5 pb-0 space-y-4 overflow-y-auto flex-1">
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

                    <div class="px-8 py-5 flex items-center justify-between border-t border-gray-100 bg-white shrink-0">

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
                                    addStep === 1 ? (step1Attempted = true, (newEmployeeForm.first_name && newEmployeeForm.last_name && validEmail(newEmployeeForm.email) && newEmployeeForm.contact_no.length === 11) ? saveAndContinue() : null) :
                                    addStep === 2 ? (step2Attempted = true, (newEmployeeForm.department_id && newEmployeeForm.job_title_id && newEmployeeForm.employment_type && newEmployeeForm.employment_status) ? saveAndContinue() : null) : null
                                "
                                :disabled="
                                    (addStep === 1 && !(newEmployeeForm.first_name && newEmployeeForm.last_name && validEmail(newEmployeeForm.email) && newEmployeeForm.contact_no.length === 11)) ||
                                    (addStep === 2 && !(newEmployeeForm.department_id && newEmployeeForm.job_title_id && newEmployeeForm.employment_type && newEmployeeForm.employment_status))
                                "
                                class="px-7 py-2.5 text-sm font-semibold rounded-lg transition-all duration-200"
                                :class="{
                                    'bg-blue-600 text-white hover:bg-blue-700 cursor-pointer':
                                        (addStep === 1 && newEmployeeForm.first_name && newEmployeeForm.last_name && validEmail(newEmployeeForm.email) && newEmployeeForm.contact_no.length === 11) ||
                                        (addStep === 2 && newEmployeeForm.department_id && newEmployeeForm.job_title_id && newEmployeeForm.employment_type && newEmployeeForm.employment_status),
                                    'bg-gray-200 text-gray-400 cursor-not-allowed':
                                        (addStep === 1 && !(newEmployeeForm.first_name && newEmployeeForm.last_name && validEmail(newEmployeeForm.email) && newEmployeeForm.contact_no.length === 11)) ||
                                        (addStep === 2 && !(newEmployeeForm.department_id && newEmployeeForm.job_title_id && newEmployeeForm.employment_type && newEmployeeForm.employment_status))
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
                            <div class="flex flex-col sm:flex-row items-center sm:items-start gap-4 mb-5 text-center sm:text-left">
                                <div class="w-14 h-14 bg-gradient-to-br from-blue-500 to-blue-600 rounded-2xl flex items-center justify-center text-white text-xl font-bold shadow-md flex-shrink-0"
                                    x-text="(selectedEmployee.first_name.charAt(0) + selectedEmployee.last_name.charAt(0)).toUpperCase()"></div>
                                <div>
                                    <h2 class="text-xl font-bold text-gray-900 leading-tight"
                                        x-text="selectedEmployee.first_name + ' ' + (selectedEmployee.mi ? selectedEmployee.mi + '. ' : '') + selectedEmployee.last_name + (selectedEmployee.suffix ? ' ' + selectedEmployee.suffix : '')"></h2>
                                    <p class="text-sm text-gray-500 mt-0.5" x-text="selectedEmployee.position"></p>
                                </div>
                            </div>
                        </template>

                        <div class="flex justify-center border-b border-gray-200 overflow-x-auto">
                            <button @click="empTab = 'basic'; isEditMode = false"
                                class="px-5 pb-3 text-sm font-medium relative transition-colors duration-200 whitespace-nowrap"
                                :class="empTab === 'basic' ? 'text-blue-600' : 'text-gray-500 hover:text-gray-700'">
                                Basic Details
                                <span x-show="empTab === 'basic'" class="absolute bottom-0 left-0 right-0 h-0.5 bg-blue-600 rounded-t-full"></span>
                            </button>
                            <button @click="empTab = 'job'; isEditMode = false"
                                class="px-5 pb-3 text-sm font-medium relative transition-colors duration-200 whitespace-nowrap"
                                :class="empTab === 'job' ? 'text-blue-600' : 'text-gray-500 hover:text-gray-700'">
                                Job Information
                                <span x-show="empTab === 'job'" class="absolute bottom-0 left-0 right-0 h-0.5 bg-blue-600 rounded-t-full"></span>
                            </button>
                            <button @click="empTab = 'docs'; isEditMode = false"
                                class="px-5 pb-3 text-sm font-medium relative transition-colors duration-200 whitespace-nowrap"
                                :class="empTab === 'docs' ? 'text-blue-600' : 'text-gray-500 hover:text-gray-700'">
                                Documents
                                <span x-show="empTab === 'docs'" class="absolute bottom-0 left-0 right-0 h-0.5 bg-blue-600 rounded-t-full"></span>
                            </button>
                        </div>
                    </div>

                    <div class="flex-1 overflow-y-auto">
                        <template x-if="selectedEmployee">
                            <div>

                                <div x-show="empTab === 'basic'" class="px-8 py-5 space-y-4">

                                    <div>
                                        <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wide mb-2">Full Name</label>
                                        <div class="flex flex-col sm:flex-row gap-2">
                                            <div class="flex-1">
                                                <input type="text" x-model="selectedEmployee.first_name" placeholder="First Name"
                                                    :readonly="!isEditMode"
                                                    @input="if(isEditMode) selectedEmployee.first_name = $event.target.value.replace(/[^a-zA-Z\s\-']/g, '')"
                                                    minlength="2" maxlength="50"
                                                    :class="{'bg-gray-50 cursor-not-allowed': !isEditMode, 'bg-white': isEditMode}"
                                                    class="w-full px-3 py-2.5 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 transition-all">
                                            </div>
                                            <div class="flex-1">
                                                <input type="text" x-model="selectedEmployee.last_name" placeholder="Last Name"
                                                    :readonly="!isEditMode"
                                                    @input="if(isEditMode) selectedEmployee.last_name = $event.target.value.replace(/[^a-zA-Z\s\-']/g, '')"
                                                    minlength="2" maxlength="50"
                                                    :class="{'bg-gray-50 cursor-not-allowed': !isEditMode, 'bg-white': isEditMode}"
                                                    class="w-full px-3 py-2.5 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 transition-all">
                                            </div>
                                            <div class="w-full sm:w-20">
                                                <input type="text" x-model="selectedEmployee.mi" placeholder="MI" maxlength="2"
                                                    :readonly="!isEditMode"
                                                    :class="{'bg-gray-50 cursor-not-allowed': !isEditMode, 'bg-white': isEditMode}"
                                                    class="w-full px-2 py-2.5 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 text-center uppercase transition-all">
                                            </div>
                                        </div>
                                        <div class="mt-2 relative">
                                            <select x-model="selectedEmployee.suffix" :disabled="!isEditMode"
                                                :class="{'bg-gray-50 cursor-not-allowed': !isEditMode, 'bg-white': isEditMode}"
                                                class="w-full px-3 py-2.5 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 appearance-none transition-all">
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
                                        <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wide mb-2">Email Address</label>
                                        <input type="email" x-model="selectedEmployee.email"
                                            :readonly="!isEditMode"
                                            @input="if(isEditMode) validateEmail($event.target.value)"
                                            :class="{'bg-gray-50 cursor-not-allowed': !isEditMode, 'border-red-400': isEditMode && hasError('email'), 'bg-white': isEditMode && !hasError('email'), 'bg-white': isEditMode}"
                                            class="w-full px-3 py-2.5 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 transition-all">
                                        <p x-show="isEditMode && hasError('email')" x-text="fieldError('email')" class="text-xs text-red-500 mt-1"></p>
                                        <p x-show="isEditMode && !hasError('email')" class="text-xs text-yellow-600 mt-1">⚠ Changing email will send a new verification link.</p>
                                        <div x-show="!isEditMode && selectedEmployee && !selectedEmployee.is_verified" class="mt-2 flex items-center gap-2 p-2.5 bg-amber-50 border border-amber-200 rounded-lg">
                                            <svg class="w-4 h-4 text-amber-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/>
                                            </svg>
                                            <span class="text-xs text-amber-700 flex-1">Email not yet verified.</span>
                                            <button @click="resendVerificationEmail()"
                                                :disabled="isResendingVerification"
                                                class="text-xs font-semibold text-blue-600 hover:text-blue-800 disabled:opacity-50 underline whitespace-nowrap"
                                                x-text="isResendingVerification ? 'Sending...' : 'Resend Link'">
                                            </button>
                                        </div>
                                    </div>

                                    <div>
                                        <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wide mb-2">Contact Number</label>
                                        <input type="text" x-model="selectedEmployee.contact_number"
                                            :readonly="!isEditMode"
                                            maxlength="11"
                                            placeholder="09XXXXXXXXX"
                                            @input="if(isEditMode) selectedEmployee.contact_number = validateContactNo($event.target.value)"
                                            :class="{'bg-gray-50 cursor-not-allowed': !isEditMode, 'border-red-400': isEditMode && hasError('contact_no'), 'bg-white': isEditMode}"
                                            class="w-full px-3 py-2.5 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 transition-all">
                                        <p x-show="isEditMode && hasError('contact_no')" x-text="fieldError('contact_no')" class="text-xs text-red-500 mt-1"></p>
                                    </div>

                                    <div class="flex flex-col sm:flex-row gap-3">
                                        <div class="flex-1">
                                            <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wide mb-2">Date of Birth</label>
                                            <input type="date" x-model="selectedEmployee.date_of_birth" readonly
                                                class="w-full px-3 py-2.5 text-sm border border-gray-200 rounded-lg bg-gray-50 cursor-not-allowed">
                                        </div>
                                        <div class="flex-1">
                                            <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wide mb-2">Gender</label>
                                            <input type="text" x-model="selectedEmployee.gender" readonly
                                                class="w-full px-3 py-2.5 text-sm border border-gray-200 rounded-lg bg-gray-50 cursor-not-allowed capitalize">
                                        </div>
                                    </div>

                                    <div>
                                        <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wide mb-2">Address</label>
                                        <textarea x-model="selectedEmployee.address" readonly rows="2"
                                            class="w-full px-3 py-2.5 text-sm border border-gray-200 rounded-lg bg-gray-50 cursor-not-allowed resize-none"></textarea>
                                    </div>

                                </div>

                                <div x-show="empTab === 'job'" class="px-8 py-5 space-y-4">

                                    <div class="flex flex-col sm:flex-row gap-3">
                                        <div class="flex-1">
                                            <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wide mb-2">Department</label>
                                            <div class="relative">
                                                <select x-model="selectedEmployee.department_id"
                                                    @change="selectedEmployee.job_title_id = ''"
                                                    :disabled="!isEditMode"
                                                    :class="{'bg-gray-50 cursor-not-allowed': !isEditMode, 'bg-white': isEditMode}"
                                                    class="w-full px-3 py-2.5 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 appearance-none transition-all">
                                                    <option value="" disabled>Choose department</option>
                                                    <template x-for="dept in departments" :key="dept.id">
                                                        <option :value="dept.id" x-text="dept.name"></option>
                                                    </template>
                                                </select>
                                                <div class="pointer-events-none absolute inset-y-0 right-2 flex items-center text-gray-400">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="flex-1">
                                            <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wide mb-2">Job Title</label>
                                            <div class="relative">
                                                <select x-model="selectedEmployee.job_title_id"
                                                    :disabled="!isEditMode || !selectedEmployee.department_id"
                                                    :class="{'bg-gray-50 cursor-not-allowed': !isEditMode || !selectedEmployee.department_id, 'bg-white': isEditMode && selectedEmployee.department_id}"
                                                    class="w-full px-3 py-2.5 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 appearance-none transition-all">
                                                    <option value="" disabled x-text="!selectedEmployee.department_id ? 'Choose department first' : 'Choose job title'"></option>
                                                    <template x-for="jt in jobTitles.filter(j => j.department_id == selectedEmployee.department_id)" :key="jt.id">
                                                        <option :value="jt.id" x-text="jt.title"></option>
                                                    </template>
                                                </select>
                                                <div class="pointer-events-none absolute inset-y-0 right-2 flex items-center text-gray-400">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <div>
                                        <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wide mb-2">Role</label>
                                        <div class="relative">
                                            <select x-model="selectedEmployee.role"
                                                :disabled="!isEditMode"
                                                :class="{'bg-gray-50 cursor-not-allowed': !isEditMode, 'bg-white': isEditMode}"
                                                class="w-full px-3 py-2.5 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 appearance-none transition-all">
                                                <option value="admin">Admin</option>
                                                <option value="hr_manager">HR Manager</option>
                                                <option value="supervisor">Supervisor</option>
                                                <option value="finance_officer">Finance Officer</option>
                                                <option value="payroll_officer">Payroll Officer</option>
                                                <option value="employee">Employee</option>
                                            </select>
                                            <div class="pointer-events-none absolute inset-y-0 right-2 flex items-center text-gray-400">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="flex flex-col sm:flex-row gap-3">
                                        <div class="flex-1">
                                            <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wide mb-2">Date Hired</label>
                                            <input type="date" x-model="selectedEmployee.date_hired"
                                                :readonly="!isEditMode"
                                                :class="{'bg-gray-50 cursor-not-allowed': !isEditMode, 'bg-white': isEditMode}"
                                                class="w-full px-3 py-2.5 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 transition-all">
                                        </div>
                                        <div class="flex-1">
                                            <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wide mb-2">Employment Type</label>
                                            <div class="relative">
                                                <select x-model="selectedEmployee.employment_type"
                                                    :disabled="!isEditMode"
                                                    :class="{'bg-gray-50 cursor-not-allowed': !isEditMode, 'bg-white': isEditMode}"
                                                    class="w-full px-3 py-2.5 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 appearance-none transition-all">
                                                    <option value="Full-time">Full-time</option>
                                                    <option value="Part-time">Part-time</option>
                                                    <option value="Contractual">Contractual</option>
                                                    <option value="Internship">Internship</option>
                                                </select>
                                                <div class="pointer-events-none absolute inset-y-0 right-2 flex items-center text-gray-400">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <div>
                                        <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wide mb-2">Employment Status</label>
                                        <div class="relative">
                                            <select x-model="selectedEmployee.employment_status"
                                                :disabled="!isEditMode"
                                                :class="{'bg-gray-50 cursor-not-allowed': !isEditMode, 'bg-white': isEditMode}"
                                                class="w-full px-3 py-2.5 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 appearance-none transition-all">
                                                <option value="Active">Active</option>
                                                <option value="Resigned">Resigned</option>
                                                <option value="Retired">Retired</option>
                                                <option value="Terminated">Terminated</option>
                                            </select>
                                            <div class="pointer-events-none absolute inset-y-0 right-2 flex items-center text-gray-400">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                                            </div>
                                        </div>
                                    </div>

                                </div>

                                <div x-show="empTab === 'docs'" class="px-8 py-5">

                                    <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3 mb-4">
                                        <p class="text-sm text-gray-500">PDF and Word documents only (max 10 MB)</p>
                                        <label class="cursor-pointer flex items-center gap-2 px-4 py-2 bg-blue-600 text-white text-sm font-medium rounded-lg hover:bg-blue-700 transition-all"
                                            :class="isUploadingDoc ? 'opacity-60 pointer-events-none' : ''">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/></svg>
                                            <span x-show="!isUploadingDoc">Upload</span>
                                            <span x-show="isUploadingDoc">Uploading...</span>
                                            <input type="file" class="hidden" accept=".pdf,.doc,.docx" @change="handleDocUpload($event)" :disabled="isUploadingDoc">
                                        </label>
                                    </div>

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
                                                <div class="flex items-center gap-2">
                                                    <a :href="`/employees/documents/${doc.id}/download`" target="_blank"
                                                        class="flex-1 flex items-center gap-3 p-3 border border-gray-200 rounded-xl hover:bg-blue-50 hover:border-blue-200 transition-all group">
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
                                                    <button @click.prevent="deleteDocument(doc.id)"
                                                        class="flex-shrink-0 w-8 h-8 flex items-center justify-center rounded-lg text-gray-400 hover:text-red-500 hover:bg-red-50 transition-all"
                                                        title="Delete document">
                                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                                                    </button>
                                                </div>
                                            </template>
                                        </div>
                                    </div>
                                </div>

                            </div>
                        </template>
                    </div>

                    <div x-show="empTab !== 'docs'" class="px-8 py-4 bg-white border-t border-gray-100 flex justify-end gap-3 flex-shrink-0">
                        <button x-show="!isEditMode" @click="enableEditMode"
                            class="px-7 py-2.5 bg-white text-gray-700 text-sm font-medium rounded-xl border border-gray-300 hover:bg-gray-50 transition-all">
                            Edit
                        </button>
                        <button x-show="isEditMode" @click="cancelEdit()"
                            class="px-7 py-2.5 bg-white text-gray-700 text-sm font-medium rounded-xl border border-gray-300 hover:bg-gray-50 transition-all">
                            Cancel
                        </button>
                        <button x-show="isEditMode" @click="saveChanges()" :disabled="isSaving"
                            :class="isSaving ? 'bg-gray-400 cursor-not-allowed' : 'bg-blue-500 hover:bg-blue-600 cursor-pointer'"
                            class="px-7 py-2.5 text-white text-sm font-medium rounded-xl transition-all">
                            <span x-show="!isSaving">Save Changes</span>
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

            <!-- Department Summary Cards - Responsive Grid -->
            <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 gap-3 sm:gap-4 mb-6 mx-4 sm:mx-8">
                <template x-for="dept in departments" :key="dept.id">
                    <div class="bg-white rounded-xl p-4 shadow-sm border border-gray-100 hover:shadow-md transition-all duration-200">
                        <p class="text-sm text-gray-500 mb-1" x-text="dept.name"></p>
                        <p class="text-3xl font-bold text-gray-800" x-text="dept.employees_count"></p>
                        <p class="text-xs text-gray-400 mt-1">Employees</p>
                    </div>
                </template>
            </div>

            <!-- Top Control Section - Responsive Layout -->
            <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 mb-6 mx-4 sm:mx-8">
                <div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-3 w-full sm:w-auto">
                    <div class="relative flex-1 sm:flex-none sm:w-80 group">
                        <input type="text" x-model="searchQuery" @input="searchQuery = $event.target.value" placeholder="Search employees..."
                            autocomplete="off" autocorrect="off" autocapitalize="off" spellcheck="false"
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
                            class="w-full sm:w-[180px] py-2.5 bg-white border border-gray-200 rounded-xl hover:bg-gray-50 transition-all duration-200 hover:shadow-sm relative flex items-center gap-2 text-sm text-gray-400 justify-center sm:justify-start pl-4"
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
                                    <p class="text-xs font-medium text-gray-400 mb-2">Verification</p>
                                    <div class="space-y-1">
                                        <label class="flex items-center gap-3 py-1 cursor-pointer">
                                            <input type="radio" name="verif" value="verified" x-model="verificationFilter" class="w-4 h-4 border-gray-300 text-blue-600 focus:ring-0 focus:ring-offset-0">
                                            <span class="text-sm text-gray-700">Verified</span>
                                        </label>
                                        <label class="flex items-center gap-3 py-1 cursor-pointer">
                                            <input type="radio" name="verif" value="unverified" x-model="verificationFilter" class="w-4 h-4 border-gray-300 text-blue-600 focus:ring-0 focus:ring-offset-0">
                                            <span class="text-sm text-gray-700">Unverified</span>
                                        </label>
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

                <div class="flex items-center gap-3 w-full sm:w-auto">
                    <button @click="showManageDepartment = true"
                        class="flex-1 sm:flex-none px-5 py-3 bg-gradient-to-r from-blue-600 to-blue-700 text-white text-sm font-medium rounded-xl hover:from-blue-700 hover:to-blue-800 transition-all duration-300 hover:shadow-xl transform hover:scale-105">
                        <span class="flex items-center justify-center gap-2">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                            </svg>
                            <span class="hidden sm:inline">Manage Departments</span>
                            <span class="sm:hidden">Depts</span>
                        </span>
                    </button>
                    <button @click="openAddEmployee()"
                        class="flex-1 sm:flex-none px-5 py-3 bg-gradient-to-r from-blue-600 to-blue-700 text-white text-sm font-medium rounded-xl hover:from-blue-700 hover:to-blue-800 transition-all duration-300 hover:shadow-xl transform hover:scale-105 group">
                        <span class="flex items-center justify-center gap-2">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v3m0 0v3m0-3h3m-3 0H9m12 0a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                            <span class="hidden sm:inline">Add Employee</span>
                            <span class="sm:hidden">Add</span>
                        </span>
                    </button>
                </div>
            </div>

            <!-- Active Filters -->
            <div x-show="selectedDepartments.length > 0 || selectedSort || verificationFilter" class="mb-4 flex flex-wrap items-center gap-2 mx-4 sm:mx-8">
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
                <span x-show="verificationFilter" class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-blue-50 text-blue-700 border border-blue-100">
                    <span x-text="verificationFilter === 'verified' ? 'Verified only' : 'Unverified only'"></span>
                    <button @click="verificationFilter = ''" class="ml-1.5 hover:text-blue-900">
                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </span>
                <button @click="clearFilters" class="text-xs text-gray-500 hover:text-gray-700 underline">Clear all</button>
            </div>

            <!-- Search Results Info -->
            <div x-show="searchQuery.length > 0" class="mb-4 flex items-center justify-between mx-4 sm:mx-8">
                <p class="text-sm text-gray-600">Found <span class="font-semibold text-blue-600" x-text="resultCount"></span> result<span x-show="resultCount !== 1">s</span> for "<span class="font-semibold" x-text="searchQuery"></span>"</p>
                <button @click="clearSearch" class="text-xs text-gray-500 hover:text-blue-600 transition-colors duration-200">Clear search</button>
            </div>

            <!-- Employee Table - Responsive with mobile cards -->
            <div class="bg-white rounded-xl shadow-sm hover:shadow-xl transition-all duration-300 overflow-hidden mx-4 sm:mx-8">
                <!-- Desktop Table -->
                <div class="hidden lg:block">
                    <div class="px-6 py-4 border-b border-gray-100 bg-gray-50">
                        <div class="grid grid-cols-12 gap-4">
                            <div class="col-span-3"><span class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Employee Name</span></div>
                            <div class="col-span-3"><span class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Department</span></div>
                            <div class="col-span-3"><span class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Position</span></div>
                            <div class="col-span-1 lg:pr-6"><span class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Status</span></div>
                            <div class="col-span-2 md:pl-3 lg:pl-12"><span class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Actions</span></div>
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
                                                <div class="flex items-center gap-1.5">
                                                    <p class="font-medium text-gray-800 group-hover:text-blue-600 transition-colors duration-300 truncate" x-text="employee.name"></p>
                                                    <span x-show="!employee.is_verified" class="flex-shrink-0 inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-semibold bg-amber-100 text-amber-700">Unverified</span>
                                                </div>
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
                                    <div class="col-span-1 lg:pr-6">
                                        <span class="inline-flex px-2.5 py-1 text-xs font-medium rounded-full whitespace-nowrap"
                                            :class="employee.status === 'Active' ? 'bg-green-100 text-green-700' : 'bg-yellow-100 text-yellow-700'"
                                            x-text="employee.status"></span>
                                    </div>
                                    <div class="col-span-2 md:pl-3 lg:pl-12">
                                        <button @click="viewEmployee(employee)"
                                            class="whitespace-nowrap px-3 md:px-4 py-2 bg-blue-500 text-white text-xs md:text-sm font-medium rounded-xl hover:bg-blue-600 transition-all duration-200 shadow-md hover:shadow-lg">View Details</button>
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
                <div class="lg:hidden">
                    <template x-for="employee in pagedEmployees" :key="employee.email">
                        <div class="p-4 border-b border-gray-100 hover:bg-blue-50 transition-all duration-300">
                            <div class="flex items-start space-x-3">
                                <div class="w-12 h-12 bg-gradient-to-br from-blue-500 to-blue-600 rounded-xl flex items-center justify-center text-white font-semibold shadow-md flex-shrink-0">
                                    <span x-text="employee.avatar"></span>
                                </div>
                                <div class="flex-1">
                                    <div class="flex items-start justify-between flex-wrap gap-2">
                                        <div class="flex-1">
                                            <div class="flex items-center gap-1.5 flex-wrap">
                                                <p class="font-medium text-gray-800" x-text="employee.name"></p>
                                                <span x-show="!employee.is_verified" class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-semibold bg-amber-100 text-amber-700">Unverified</span>
                                            </div>
                                            <p class="text-xs text-gray-500" x-text="employee.email"></p>
                                        </div>
                                        <span class="px-2 py-1 text-xs font-medium rounded-full"
                                            :class="employee.status === 'Active' ? 'bg-green-100 text-green-700' : 'bg-yellow-100 text-yellow-700'"
                                            x-text="employee.status"></span>
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
    
    /* Mobile responsive overrides */
    @media (max-width: 1024px) { 
        .lg\:ml-20, .lg\:ml-64 { margin-left: 0 !important; } 
    }
    
    /* Hide scrollbar on mobile for cleaner look */
    @media (max-width: 768px) {
        .overflow-x-auto {
            -webkit-overflow-scrolling: touch;
            scrollbar-width: thin;
        }
    }
    
    [x-cloak] { display: none !important; }
</style>
@endsection
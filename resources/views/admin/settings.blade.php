@extends('layouts.app')

@section('title', 'Settings - Medisource HRMS')

@section('content')

<script>
    window.permissionsData = @json($permissions);
</script>

<div x-data="{
    sidebarCollapsed: localStorage.getItem('sidebarCollapsed') === 'true',
    selectedRole: 'admin',
    isEditMode: false,
    isSaving: false,
    permissions: window.permissionsData,

    currentPerms: {
        'Employee Management': { can_view: false, can_create: false, can_edit: false, can_archive: false, can_import: false, can_export: false }
    },

    get roleLabel() {
        const map = { admin: 'Administrator', hr_manager: 'HR Manager', supervisor: 'Supervisor', employee: 'Employee' };
        return map[this.selectedRole] || this.selectedRole;
    },

    loadPerms() {
        const roleData = this.permissions[this.selectedRole] || [];
        const emp = roleData.find(p => p.module === 'Employee Management');
        this.currentPerms['Employee Management'] = emp ? {
            can_view:    emp.can_view    ?? false,
            can_create:  emp.can_create  ?? false,
            can_edit:    emp.can_edit    ?? false,
            can_archive: emp.can_archive ?? false,
            can_import:  emp.can_import  ?? false,
            can_export:  emp.can_export  ?? false,
        } : { can_view: false, can_create: false, can_edit: false, can_archive: false, can_import: false, can_export: false };
        this.isEditMode = false;
    },

    async savePerms() {
        this.isSaving = true;
        const csrf = document.querySelector('meta[name=csrf-token]').getAttribute('content');

        try {
            const response = await fetch('{{ route('settings.permissions.update') }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrf
                },
                body: JSON.stringify({
                    role:        this.selectedRole,
                    module:      'Employee Management',
                    can_view:    this.currentPerms['Employee Management'].can_view,
                    can_create:  this.currentPerms['Employee Management'].can_create,
                    can_edit:    this.currentPerms['Employee Management'].can_edit,
                    can_archive: this.currentPerms['Employee Management'].can_archive,
                    can_import:  this.currentPerms['Employee Management'].can_import,
                    can_export:  this.currentPerms['Employee Management'].can_export,
                })
            });

            const data = await response.json();

            if (response.ok && data.success) {
                // Update local permissions data
                if (!this.permissions[this.selectedRole]) this.permissions[this.selectedRole] = [];
                const idx = this.permissions[this.selectedRole].findIndex(p => p.module === 'Employee Management');
                const updated = {
                    module:      'Employee Management',
                    can_view:    this.currentPerms['Employee Management'].can_view,
                    can_create:  this.currentPerms['Employee Management'].can_create,
                    can_edit:    this.currentPerms['Employee Management'].can_edit,
                    can_archive: this.currentPerms['Employee Management'].can_archive,
                    can_import:  this.currentPerms['Employee Management'].can_import,
                    can_export:  this.currentPerms['Employee Management'].can_export,
                };
                if (idx !== -1) this.permissions[this.selectedRole][idx] = updated;
                else this.permissions[this.selectedRole].push(updated);

                this.isEditMode = false;
                this.showToast('Permissions updated successfully!', 'success');
            } else {
                this.showToast(data.message || 'Something went wrong.', 'error');
            }
        } catch (err) {
            this.showToast('Network error. Please check your connection.', 'error');
        }

        this.isSaving = false;
    },

    cancelEdit() {
        this.loadPerms();
        this.isEditMode = false;
    },

    toast: { show: false, message: '', type: 'success' },
    showToast(message, type = 'success') {
        this.toast = { show: true, message, type };
        setTimeout(() => { this.toast.show = false; }, 3000);
    }
}"
x-init="loadPerms(); $watch('selectedRole', () => loadPerms())"
class="flex h-screen overflow-hidden bg-gray-50">

    @include('admin.admin_sidebar', ['activeMenu' => 'settings'])

    <main class="flex-1 overflow-y-auto min-h-screen transition-all duration-300"
          :class="sidebarCollapsed ? 'lg:ml-20' : 'lg:ml-64'">

        <!-- Top Header -->
        <header class="bg-gradient-to-r from-blue-600 to-blue-700 text-white sticky top-0 z-10 shadow-lg mt-4 mx-4 rounded-2xl">
            <div class="px-4 sm:px-6 lg:px-8 py-4 flex items-center justify-between">
                <div>
                    <h1 class="text-xl sm:text-2xl font-bold">Settings</h1>
                    <p class="text-xs sm:text-sm text-blue-100 mt-1">Roles & Permissions</p>
                </div>
                <div class="flex items-center space-x-4">
                    <button class="relative p-2 hover:bg-blue-500 rounded-lg transition-all duration-200">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>
                        </svg>
                        <span class="absolute top-1 right-1 w-2 h-2 bg-red-400 rounded-full animate-ping"></span>
                        <span class="absolute top-1 right-1 w-2 h-2 bg-red-400 rounded-full"></span>
                    </button>
                    <div class="w-8 h-8 sm:w-10 sm:h-10 bg-white/20 rounded-lg flex items-center justify-center">
                        <span class="text-sm sm:text-base font-semibold">{{ $authEmployee ? strtoupper(substr($authEmployee->fname, 0, 1) . substr($authEmployee->lname, 0, 1)) : strtoupper(substr($authUser->email, 0, 2)) }}</span>
                    </div>
                </div>
            </div>
        </header>

        <div class="p-6 lg:p-8 mt-4">

            <!-- Toast -->
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
                <button @click="toast.show = false" class="ml-2 text-gray-400 hover:text-gray-600">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>

            <!-- Roles & Permissions Card -->
            <div class="bg-white rounded-2xl shadow-sm p-6">

                <!-- Card Header -->
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-6">
                    <div>
                        <h2 class="text-lg font-semibold text-gray-800">Roles & Permissions</h2>
                        <p class="text-sm text-gray-500 mt-0.5">Control what each role can do per module</p>
                    </div>
                    <div class="flex items-center gap-3">
                        <!-- Role Selector -->
                        <div class="relative">
                            <select x-model="selectedRole"
                                :disabled="isEditMode"
                                :class="isEditMode ? 'opacity-50 cursor-not-allowed' : 'cursor-pointer'"
                                class="appearance-none bg-white border border-gray-300 rounded-lg pl-4 pr-10 py-2.5 text-sm font-medium text-gray-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent hover:border-gray-400 transition-all duration-200 min-w-[180px]">
                                <option value="admin">Administrator</option>
                                <option value="hr_manager">HR Manager</option>
                                <option value="supervisor">Supervisor</option>
                                <option value="employee">Employee</option>
                            </select>
                            <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-2 text-gray-500">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                            </div>
                        </div>

                        <!-- Edit Button (view mode) -->
                        <button x-show="!isEditMode" @click="isEditMode = true"
                            class="px-5 py-2.5 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition-all duration-200">
                            Edit
                        </button>
                    </div>
                </div>

                <!-- Role Badge -->
                <div class="mb-4 flex items-center gap-2">
                    <span class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Viewing permissions for:</span>
                    <span class="px-3 py-1 text-xs font-semibold rounded-full bg-blue-100 text-blue-700" x-text="roleLabel"></span>
                    <span x-show="isEditMode" class="px-3 py-1 text-xs font-semibold rounded-full bg-yellow-100 text-yellow-700">Edit Mode</span>
                </div>

                <!-- Permissions Table -->
                <div class="overflow-x-auto rounded-xl border border-gray-200">
                    <table class="w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Module / Feature</th>
                                <th class="px-6 py-4 text-center text-xs font-semibold text-gray-600 uppercase tracking-wider">View</th>
                                <th class="px-6 py-4 text-center text-xs font-semibold text-gray-600 uppercase tracking-wider">Create</th>
                                <th class="px-6 py-4 text-center text-xs font-semibold text-gray-600 uppercase tracking-wider">Edit</th>
                                <th class="px-6 py-4 text-center text-xs font-semibold text-gray-600 uppercase tracking-wider">Archive</th>
                                <th class="px-6 py-4 text-center text-xs font-semibold text-gray-600 uppercase tracking-wider">Import</th>
                                <th class="px-6 py-4 text-center text-xs font-semibold text-gray-600 uppercase tracking-wider">Export</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-100">

                            <!-- Employee Management Row -->
                            <tr class="hover:bg-blue-50 transition-colors duration-150">
                                <td class="px-6 py-4 text-sm font-medium text-gray-900">
                                    <div class="flex items-center gap-2">
                                        <div class="w-8 h-8 bg-blue-100 rounded-lg flex items-center justify-center flex-shrink-0">
                                            <svg class="w-4 h-4 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/>
                                            </svg>
                                        </div>
                                        Employee Management
                                    </div>
                                </td>
                                <td class="px-6 py-4 text-center">
                                    <input type="checkbox"
                                        x-model="currentPerms['Employee Management'].can_view"
                                        :disabled="!isEditMode"
                                        :class="isEditMode ? 'cursor-pointer' : 'cursor-not-allowed opacity-70'"
                                        class="w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 rounded focus:ring-blue-500 focus:ring-2 transition-all duration-200">
                                </td>
                                <td class="px-6 py-4 text-center">
                                    <input type="checkbox"
                                        x-model="currentPerms['Employee Management'].can_create"
                                        :disabled="!isEditMode"
                                        :class="isEditMode ? 'cursor-pointer' : 'cursor-not-allowed opacity-70'"
                                        class="w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 rounded focus:ring-blue-500 focus:ring-2 transition-all duration-200">
                                </td>
                                <td class="px-6 py-4 text-center">
                                    <input type="checkbox"
                                        x-model="currentPerms['Employee Management'].can_edit"
                                        :disabled="!isEditMode"
                                        :class="isEditMode ? 'cursor-pointer' : 'cursor-not-allowed opacity-70'"
                                        class="w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 rounded focus:ring-blue-500 focus:ring-2 transition-all duration-200">
                                </td>
                                <td class="px-6 py-4 text-center">
                                    <input type="checkbox"
                                        x-model="currentPerms['Employee Management'].can_archive"
                                        :disabled="!isEditMode"
                                        :class="isEditMode ? 'cursor-pointer' : 'cursor-not-allowed opacity-70'"
                                        class="w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 rounded focus:ring-blue-500 focus:ring-2 transition-all duration-200">
                                </td>
                                <td class="px-6 py-4 text-center">
                                    <input type="checkbox"
                                        x-model="currentPerms['Employee Management'].can_import"
                                        :disabled="!isEditMode"
                                        :class="isEditMode ? 'cursor-pointer' : 'cursor-not-allowed opacity-70'"
                                        class="w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 rounded focus:ring-blue-500 focus:ring-2 transition-all duration-200">
                                </td>
                                <td class="px-6 py-4 text-center">
                                    <input type="checkbox"
                                        x-model="currentPerms['Employee Management'].can_export"
                                        :disabled="!isEditMode"
                                        :class="isEditMode ? 'cursor-pointer' : 'cursor-not-allowed opacity-70'"
                                        class="w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 rounded focus:ring-blue-500 focus:ring-2 transition-all duration-200">
                                </td>
                            </tr>

                            <!-- Placeholder rows (not yet functional) -->
                            @foreach(['Time & Attendance', 'Leave Management', 'Payroll Processing', 'Reports & Analytics', 'System Settings'] as $module)
                            <tr class="hover:bg-gray-50 transition-colors duration-150 opacity-50">
                                <td class="px-6 py-4 text-sm font-medium text-gray-500">
                                    <div class="flex items-center gap-2">
                                        <div class="w-8 h-8 bg-gray-100 rounded-lg flex items-center justify-center flex-shrink-0">
                                            <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                                            </svg>
                                        </div>
                                        {{ $module }}
                                        <span class="text-xs text-gray-400 font-normal">(coming soon)</span>
                                    </div>
                                </td>
                                @for($i = 0; $i < 6; $i++)
                                <td class="px-6 py-4 text-center">
                                    <input type="checkbox" disabled class="w-4 h-4 text-gray-300 bg-gray-100 border-gray-200 rounded cursor-not-allowed">
                                </td>
                                @endfor
                            </tr>
                            @endforeach

                        </tbody>
                    </table>
                </div>

                <!-- Bottom Actions — only shown in edit mode -->
                <div x-show="isEditMode" x-cloak
                    x-transition:enter="transition ease-out duration-200"
                    x-transition:enter-start="opacity-0 translate-y-2"
                    x-transition:enter-end="opacity-100 translate-y-0"
                    class="flex items-center justify-between mt-6 pt-4 border-t border-gray-100">
                    <p class="text-xs text-gray-400">You are editing permissions for <span class="font-semibold text-gray-600" x-text="roleLabel"></span></p>
                    <div class="flex items-center gap-3">
                        <button @click="cancelEdit()"
                            class="px-5 py-2.5 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition-all duration-200">
                            Cancel
                        </button>
                        <button @click="savePerms()" :disabled="isSaving"
                            :class="isSaving ? 'bg-gray-400 cursor-not-allowed' : 'bg-blue-600 hover:bg-blue-700'"
                            class="px-5 py-2.5 text-sm font-medium text-white rounded-lg transition-all duration-200">
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
        </div>
    </main>
</div>

<style>
    [x-cloak] { display: none !important; }
    ::-webkit-scrollbar { width: 6px; height: 6px; }
    ::-webkit-scrollbar-track { background: #f1f1f1; border-radius: 10px; }
    ::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 10px; }
    ::-webkit-scrollbar-thumb:hover { background: #94a3b8; }
    @media (max-width: 1024px) { .lg\:ml-20, .lg\:ml-64 { margin-left: 0 !important; } }
</style>

@endsection
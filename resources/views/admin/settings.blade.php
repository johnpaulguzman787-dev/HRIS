@extends('layouts.app')

@section('title', 'Settings - Medisource HRMS')

@section('content')

<script>
    window.__matrix        = @json($matrix);
    window.__notApplicable = @json($notApplicable);
</script>

@php
    $roleLabels = [
        'admin'           => 'Administrator',
        'hr_manager'      => 'HR Manager',
        'supervisor'      => 'Supervisor',
        'payroll_officer' => 'Payroll Officer',
        'finance_officer' => 'Finance Officer',
        'employee'        => 'Employee',
    ];
    $moduleStyles = [
        'Employee Management' => ['header_bg' => 'bg-blue-50',   'header_border' => 'border-blue-100',   'header_text' => 'text-blue-700',   'icon_bg' => 'bg-blue-100',   'icon_color' => 'text-blue-600'],
        'Time & Attendance'   => ['header_bg' => 'bg-green-50',  'header_border' => 'border-green-100',  'header_text' => 'text-green-700',  'icon_bg' => 'bg-green-100',  'icon_color' => 'text-green-600'],
        'Leave Management'    => ['header_bg' => 'bg-yellow-50', 'header_border' => 'border-yellow-100', 'header_text' => 'text-yellow-700', 'icon_bg' => 'bg-yellow-100', 'icon_color' => 'text-yellow-600'],
        'Requests & Approval' => ['header_bg' => 'bg-purple-50', 'header_border' => 'border-purple-100', 'header_text' => 'text-purple-700', 'icon_bg' => 'bg-purple-100', 'icon_color' => 'text-purple-600'],
        'Payroll'             => ['header_bg' => 'bg-indigo-50', 'header_border' => 'border-indigo-100', 'header_text' => 'text-indigo-700', 'icon_bg' => 'bg-indigo-100', 'icon_color' => 'text-indigo-600'],
    ];
    $isNA = fn($role, $module, $action) => in_array($action, $notApplicable[$role][$module] ?? []);
    $nonAdminRoles = ['hr_manager', 'supervisor', 'payroll_officer', 'finance_officer', 'employee'];
@endphp

<div x-data="{
    sidebarCollapsed: localStorage.getItem('sidebarCollapsed') === 'true',
    matrix: window.__matrix,
    saving: null,
    toast: { show: false, message: '', type: 'success' },

    getValue(module, role, action) {
        return this.matrix[module]?.[role]?.[action] ?? false;
    },

    async toggle(module, role, action) {
        const saveKey = `${module}|${role}`;
        if (this.saving === saveKey) return;

        // Optimistic update
        if (!this.matrix[module])        this.matrix[module]        = {};
        if (!this.matrix[module][role])  this.matrix[module][role]  = {};
        this.matrix[module][role][action] = !this.matrix[module][role][action];

        await this.saveRow(module, role);
    },

    async saveRow(module, role) {
        const saveKey = `${module}|${role}`;
        this.saving   = saveKey;

        const perms = this.matrix[module]?.[role] ?? {};
        const csrf  = document.querySelector('meta[name=csrf-token]').content;

        try {
            const res = await fetch('{{ route('settings.permissions.update') }}', {
                method:  'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept':       'application/json',
                    'X-CSRF-TOKEN': csrf,
                },
                body: JSON.stringify({
                    role,
                    module,
                    can_view:    perms.can_view    ?? false,
                    can_create:  perms.can_create  ?? false,
                    can_edit:    perms.can_edit    ?? false,
                    can_archive: perms.can_archive ?? false,
                    can_import:  perms.can_import  ?? false,
                    can_export:  perms.can_export  ?? false,
                }),
            });

            const data = await res.json();
            if (data.success) {
                this.showToast('Permission updated!', 'success');
            } else {
                this.showToast('Failed to save permission.', 'error');
            }
        } catch (e) {
            this.showToast('Network error. Please try again.', 'error');
        }

        this.saving = null;
    },

    showToast(message, type) {
        this.toast = { show: true, message, type };
        setTimeout(() => this.toast.show = false, 3000);
    },
}"
class="flex h-screen overflow-hidden bg-gray-50">

    @include('admin.admin_sidebar', ['activeMenu' => 'settings'])

    <main class="flex-1 overflow-y-auto min-h-screen transition-all duration-300"
          :class="sidebarCollapsed ? 'lg:ml-20' : 'lg:ml-64'">

        <!-- Header -->
        <header class="anim-fade bg-gradient-to-br from-blue-500 to-blue-700 sticky top-0 z-10 shadow-lg mt-4 mx-4 rounded-2xl">
            <div class="px-4 sm:px-6 lg:px-8 py-4 flex items-center justify-between">
                <div>
                    <h1 class="text-xl sm:text-2xl font-bold text-white">Settings</h1>
                    <p class="text-xs sm:text-sm text-blue-100 mt-1">Roles &amp; Permissions</p>
                </div>
                <div class="flex items-center space-x-4">
                    <x-notification-bell />
                </div>
            </div>
        </header>

        <div class="p-6 lg:p-8 mt-4">

            <!-- Toast -->
            <div x-show="toast.show" x-cloak
                x-transition:enter="transition ease-out duration-300"
                x-transition:enter-start="opacity-0 -translate-y-3"
                x-transition:enter-end="opacity-100 translate-y-0"
                x-transition:leave="transition ease-in duration-200"
                x-transition:leave-start="opacity-100 translate-y-0"
                x-transition:leave-end="opacity-0 -translate-y-3"
                class="fixed top-6 right-6 z-9999 flex items-center gap-3 px-5 py-4 rounded-2xl shadow-xl border"
                :class="toast.type === 'success' ? 'bg-white border-green-100' : 'bg-white border-red-100'">
                <div class="w-8 h-8 rounded-full flex items-center justify-center shrink-0"
                     :class="toast.type === 'success' ? 'bg-green-100' : 'bg-red-100'">
                    <template x-if="toast.type === 'success'">
                        <svg class="w-4 h-4 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
                        </svg>
                    </template>
                    <template x-if="toast.type === 'error'">
                        <svg class="w-4 h-4 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </template>
                </div>
                <div>
                    <p class="text-sm font-semibold text-gray-800" x-text="toast.type === 'success' ? 'Saved' : 'Error'"></p>
                    <p class="text-xs text-gray-500" x-text="toast.message"></p>
                </div>
                <button @click="toast.show = false" class="ml-2 text-gray-400 hover:text-gray-600">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>

            <!-- Page description -->
            <div class="mb-5">
                <p class="text-sm text-gray-500 leading-relaxed">
                    Control which actions each role can perform in the system. Changes take effect immediately.
                    Disabling a permission hides or disables the corresponding button in that role's interface.
                </p>
            </div>

            <!-- Legend -->
            <div class="mb-5 flex flex-wrap items-center gap-5 text-xs">
                <span class="font-semibold text-gray-500 uppercase tracking-wide">Legend:</span>

                <div class="flex items-center gap-1.5 text-gray-600">
                    <div class="relative inline-flex h-5 w-9 items-center rounded-full bg-blue-600">
                        <span class="inline-block h-4 w-4 transform translate-x-4 rounded-full bg-white shadow"></span>
                    </div>
                    <span>Allowed</span>
                </div>

                <div class="flex items-center gap-1.5 text-gray-600">
                    <div class="relative inline-flex h-5 w-9 items-center rounded-full bg-gray-300">
                        <span class="inline-block h-4 w-4 transform translate-x-0.5 rounded-full bg-white shadow"></span>
                    </div>
                    <span>Not Allowed</span>
                </div>

                <div class="flex items-center gap-1.5 text-gray-600">
                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-semibold bg-gray-100 text-gray-400">N/A</span>
                    <span>No UI for this role — not applicable</span>
                </div>

                <div class="flex items-center gap-1.5 text-gray-600">
                    <div class="w-6 h-6 bg-blue-100 rounded-full flex items-center justify-center">
                        <svg class="w-3.5 h-3.5 text-blue-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                            <rect x="3" y="11" width="18" height="11" rx="2" ry="2"/>
                            <path d="M7 11V7a5 5 0 0110 0v4"/>
                        </svg>
                    </div>
                    <span>Admin — always full access, locked</span>
                </div>
            </div>

            <!-- Permissions Matrix -->
            <div class="bg-white rounded-2xl shadow-sm border border-gray-200 overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full">

                        <!-- Column headers -->
                        <thead>
                            <tr class="bg-gray-50 border-b-2 border-gray-200">
                                <th class="px-6 py-4 text-left text-xs font-bold text-gray-600 uppercase tracking-wider" style="min-width:230px;">
                                    Feature
                                </th>
                                {{-- Admin column --}}
                                <th class="px-4 py-4 text-center" style="min-width:110px;">
                                    <div class="flex flex-col items-center gap-1">
                                        <span class="text-xs font-bold text-gray-600 uppercase tracking-wider">Administrator</span>
                                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-semibold bg-blue-100 text-blue-700">
                                            <svg class="w-3 h-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                                                <rect x="3" y="11" width="18" height="11" rx="2" ry="2"/>
                                                <path d="M7 11V7a5 5 0 0110 0v4"/>
                                            </svg>
                                            Locked
                                        </span>
                                    </div>
                                </th>
                                {{-- Other role columns --}}
                                @foreach($nonAdminRoles as $role)
                                <th class="px-4 py-4 text-center text-xs font-bold text-gray-600 uppercase tracking-wider" style="min-width:110px;">
                                    {{ $roleLabels[$role] }}
                                </th>
                                @endforeach
                            </tr>
                        </thead>

                        <tbody>
                            @foreach($features as $module => $moduleData)

                            {{-- Module section header --}}
                            @php $s = $moduleStyles[$module]; @endphp
                            <tr class="{{ $s['header_bg'] }} border-y {{ $s['header_border'] }}">
                                <td colspan="{{ 2 + count($nonAdminRoles) }}" class="px-6 py-3">
                                    <div class="flex items-center gap-2.5">
                                        <div class="w-7 h-7 {{ $s['icon_bg'] }} rounded-lg flex items-center justify-center shrink-0">
                                            <svg class="w-4 h-4 {{ $s['icon_color'] }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $moduleData['icon'] }}"/>
                                            </svg>
                                        </div>
                                        <span class="text-sm font-bold {{ $s['header_text'] }} uppercase tracking-wide">{{ $module }}</span>
                                    </div>
                                </td>
                            </tr>

                            {{-- Feature rows --}}
                            @foreach($moduleData['actions'] as $action => $label)
                            <tr class="border-b border-gray-100 hover:bg-gray-50 transition-colors duration-100">

                                {{-- Feature label --}}
                                <td class="px-6 py-3.5 pl-14 text-sm text-gray-700">
                                    {{ $label }}
                                </td>

                                {{-- Admin cell — always locked / fully enabled --}}
                                <td class="px-4 py-3.5 text-center">
                                    <div class="flex justify-center">
                                        <div class="w-6 h-6 bg-blue-100 rounded-full flex items-center justify-center" title="Admin always has full access">
                                            <svg class="w-3.5 h-3.5 text-blue-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                                                <rect x="3" y="11" width="18" height="11" rx="2" ry="2"/>
                                                <path d="M7 11V7a5 5 0 0110 0v4"/>
                                            </svg>
                                        </div>
                                    </div>
                                </td>

                                {{-- Non-admin role cells --}}
                                @foreach($nonAdminRoles as $role)
                                <td class="px-4 py-3.5 text-center">

                                    @if($isNA($role, $module, $action))
                                    {{-- N/A: this role has no UI for this feature --}}
                                    <div class="flex justify-center">
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-semibold bg-gray-100 text-gray-400 select-none"
                                              title="This role has no interface for this feature">
                                            N/A
                                        </span>
                                    </div>

                                    @else
                                    {{-- Toggle switch --}}
                                    <div class="flex justify-center">
                                        <button
                                            @click="toggle('{{ $module }}', '{{ $role }}', '{{ $action }}')"
                                            :disabled="saving === '{{ $module }}|{{ $role }}'"
                                            :class="{
                                                'bg-blue-600':  getValue('{{ $module }}', '{{ $role }}', '{{ $action }}'),
                                                'bg-gray-300': !getValue('{{ $module }}', '{{ $role }}', '{{ $action }}'),
                                                'opacity-60 cursor-wait': saving === '{{ $module }}|{{ $role }}',
                                                'cursor-pointer': saving !== '{{ $module }}|{{ $role }}',
                                            }"
                                            class="relative inline-flex h-5 w-9 shrink-0 items-center rounded-full transition-colors duration-200 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-1"
                                            :title="getValue('{{ $module }}', '{{ $role }}', '{{ $action }}') ? 'Click to disable' : 'Click to enable'">
                                            <span
                                                :class="getValue('{{ $module }}', '{{ $role }}', '{{ $action }}') ? 'translate-x-4' : 'translate-x-0.5'"
                                                class="inline-block h-4 w-4 transform rounded-full bg-white shadow transition-transform duration-200">
                                            </span>
                                        </button>
                                    </div>
                                    @endif

                                </td>
                                @endforeach

                            </tr>
                            @endforeach

                            @endforeach
                        </tbody>

                    </table>
                </div>
            </div>

            <!-- How permissions work note -->
            <div class="mt-5 p-4 bg-amber-50 border border-amber-200 rounded-xl flex items-start gap-3">
                <svg class="w-4 h-4 text-amber-600 mt-0.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <div>
                    <p class="text-xs font-bold text-amber-700 mb-0.5">How it works</p>
                    <p class="text-xs text-amber-600 leading-relaxed">
                        Turning a permission <strong>off</strong> hides or disables the corresponding button/action in that role's interface.
                        Rows marked <strong>N/A</strong> mean the role has no page or button for that feature — no changes are needed for those.
                        <strong>Administrator</strong> always retains full access regardless of these settings.
                    </p>
                </div>
            </div>

        </div>
    </main>
</div>

<style>
    [x-cloak] { display: none !important; }
    ::-webkit-scrollbar       { width: 6px; height: 6px; }
    ::-webkit-scrollbar-track { background: #f1f1f1; border-radius: 10px; }
    ::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 10px; }
    ::-webkit-scrollbar-thumb:hover { background: #94a3b8; }
    @media (max-width: 1024px) { .lg\:ml-20, .lg\:ml-64 { margin-left: 0 !important; } }
</style>

@endsection

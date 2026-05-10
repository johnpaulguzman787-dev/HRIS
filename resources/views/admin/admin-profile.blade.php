@extends('layouts.app')

@section('title', 'Employee Profile - Medisource HRMS')

@section('content')
@php
$_employeeData = [
    'initials'          => $employee ? strtoupper(substr($employee->fname, 0, 1) . substr($employee->lname, 0, 1)) : strtoupper(substr($user->email, 0, 2)),
    'full_name'         => $employee ? trim($employee->fname . ' ' . ($employee->mi ? $employee->mi . '. ' : '') . $employee->lname) : $user->email,
    'job_title'         => $employee?->jobTitle?->title ?? '—',
    'email'             => $user->email,
    'contact'           => $employee?->contact_no ?? '—',
    'address'           => $employee?->address ?? '—',
    'dob'               => $employee?->date_of_birth ? \Carbon\Carbon::parse($employee->date_of_birth)->format('m/d/Y') : '—',
    'gender'            => $employee?->gender ?? '—',
    'status'            => $employee?->employment_status ?? '—',
    'department'        => $employee?->department?->name ?? '—',
    'employment_type'   => $employee?->employment_type ?? '—',
    'employment_status' => $employee?->employment_status ?? '—',
    'contract_period'   => $employee?->contract_period ?? '—',
    'start_date'        => $employee?->start_date ? \Carbon\Carbon::parse($employee->start_date)->format('m/d/Y') : '—',
    'end_date'          => $employee?->end_date ? \Carbon\Carbon::parse($employee->end_date)->format('m/d/Y') : '',
    'position'          => $employee?->jobTitle?->title ?? '—',
];
@endphp
<script>
window._profileDocs = @json($documents);
window._employeeData = @json($_employeeData);
</script>
<div x-data="{
    sidebarCollapsed: localStorage.getItem('sidebarCollapsed') === 'true',
    init() { window.addEventListener('sidebar-toggle', e => { this.sidebarCollapsed = e.detail.collapsed; }); },
    employeesOpen: true,
    attendanceOpen: false,
    requestsOpen: false,
    reportsOpen: false,
    mobileMenuOpen: false,

    employee: window._employeeData,

    documents: window._profileDocs,

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

    {{-- ═══════════════════════════════════════ --}}
    {{-- DESKTOP SIDEBAR (hidden on mobile)      --}}
    {{-- ═══════════════════════════════════════ --}}
    <div class="hidden lg:block">
        @include('admin.admin_sidebar', ['activeMenu' => 'profile'])
    </div>

    {{-- ═══════════════════════════════════════ --}}
    {{-- MOBILE SLIDE-OUT DRAWER                 --}}
    {{-- ═══════════════════════════════════════ --}}
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
            @include('admin.admin_sidebar', ['activeMenu' => 'profile'])
        </div>
    </div>

    {{-- ═══════════════════════════════════════ --}}
    {{-- MAIN CONTENT                            --}}
    {{-- ═══════════════════════════════════════ --}}
    <main class="flex-1 overflow-y-auto min-h-screen w-full"
        :class="sidebarCollapsed ? 'lg:ml-20' : 'lg:ml-64'"
        style="transition: margin-left 0.35s cubic-bezier(0.4, 0, 0.2, 1);">

        {{-- ── HEADER ── --}}
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
                        <h1 class="text-xl sm:text-2xl font-bold text-white">Employee Profile</h1>
                        <p class="text-xs sm:text-sm text-blue-100 mt-1">View employee information</p>
                    </div>
                </div>
                <div class="flex items-center space-x-2 sm:space-x-4">
                    <x-notification-bell />
                </div>
            </div>
        </header>

        <div class="p-3 lg:p-6 space-y-4 lg:space-y-6">

            {{-- ── Profile Card (Mobile: centered layout) ── --}}
            <div class="profile-card bg-white rounded-2xl overflow-hidden" style="box-shadow: 0 4px 24px rgba(0,0,0,0.06);">

                <div class="h-14 bg-gradient-to-r from-blue-600 via-blue-500 to-indigo-500 relative">
                    <div class="absolute inset-0" style="background-image: radial-gradient(circle at 20% 50%, rgba(255,255,255,0.08) 0%, transparent 50%), radial-gradient(circle at 80% 20%, rgba(255,255,255,0.06) 0%, transparent 40%);"></div>
                    <div class="absolute inset-0 opacity-10" style="background-image: repeating-linear-gradient(45deg, transparent, transparent 15px, rgba(255,255,255,0.4) 15px, rgba(255,255,255,0.4) 16px);"></div>
                </div>

                <div class="px-4 sm:px-6 pb-5">
                    <!-- Avatar + Name row (mobile: centered column, desktop: row) -->
                    <div class="flex flex-col sm:flex-row sm:items-end gap-4 -mt-8 mb-4">
                        <div class="avatar-pop flex-shrink-0 w-16 h-16 sm:w-20 sm:h-20 rounded-xl sm:rounded-2xl bg-gradient-to-br from-blue-500 to-blue-700 flex items-center justify-center text-white text-xl sm:text-2xl font-bold shadow-xl mx-auto sm:mx-0"
                             style="border: 4px solid white;"
                             x-text="employee.initials"></div>
                        <div class="pb-1 name-fade text-center sm:text-left">
                            <h2 class="text-lg sm:text-xl font-bold text-gray-800 leading-tight" x-text="employee.full_name"></h2>
                            <p class="text-xs text-gray-500 mt-0.5" x-text="employee.position"></p>
                        </div>
                    </div>

                    <div class="border-t border-gray-100 my-3"></div>

                    <!-- Info Grid - Responsive: mobile 2col, tablet 2col, desktop 3col -->
                    <div class="grid grid-cols-2 lg:grid-cols-3 gap-2 lg:gap-0">
                        <!-- Email -->
                        <div class="info-row flex items-center gap-2 py-2 px-1">
                            <div class="w-7 h-7 rounded-xl bg-blue-50 flex items-center justify-center flex-shrink-0">
                                <svg class="w-3.5 h-3.5 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                                </svg>
                            </div>
                            <div class="min-w-0">
                                <p class="text-[10px] text-gray-400 font-medium uppercase tracking-wide">Email</p>
                                <p class="text-xs text-gray-700 font-semibold truncate" x-text="employee.email"></p>
                            </div>
                        </div>

                        <!-- Contact -->
                        <div class="info-row flex items-center gap-2 py-2 px-1">
                            <div class="w-7 h-7 rounded-xl bg-green-50 flex items-center justify-center flex-shrink-0">
                                <svg class="w-3.5 h-3.5 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/>
                                </svg>
                            </div>
                            <div>
                                <p class="text-[10px] text-gray-400 font-medium uppercase tracking-wide">Contact</p>
                                <p class="text-xs text-gray-700 font-semibold" x-text="employee.contact"></p>
                            </div>
                        </div>

                        <!-- Date of Birth -->
                        <div class="info-row flex items-center gap-2 py-2 px-1">
                            <div class="w-7 h-7 rounded-xl bg-purple-50 flex items-center justify-center flex-shrink-0">
                                <svg class="w-3.5 h-3.5 text-purple-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                </svg>
                            </div>
                            <div>
                                <p class="text-[10px] text-gray-400 font-medium uppercase tracking-wide">Date of Birth</p>
                                <p class="text-xs text-gray-700 font-semibold" x-text="employee.dob"></p>
                            </div>
                        </div>

                        <!-- Gender -->
                        <div class="info-row flex items-center gap-2 py-2 px-1">
                            <div class="w-7 h-7 rounded-xl bg-pink-50 flex items-center justify-center flex-shrink-0">
                                <svg class="w-3.5 h-3.5 text-pink-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                                </svg>
                            </div>
                            <div>
                                <p class="text-[10px] text-gray-400 font-medium uppercase tracking-wide">Gender</p>
                                <p class="text-xs text-gray-700 font-semibold" x-text="employee.gender"></p>
                            </div>
                        </div>

                        <!-- Address -->
                        <div class="info-row flex items-center gap-2 py-2 px-1">
                            <div class="w-7 h-7 rounded-xl bg-orange-50 flex items-center justify-center flex-shrink-0">
                                <svg class="w-3.5 h-3.5 text-orange-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                                </svg>
                            </div>
                            <div class="min-w-0">
                                <p class="text-[10px] text-gray-400 font-medium uppercase tracking-wide">Address</p>
                                <p class="text-xs text-gray-700 font-semibold truncate" x-text="employee.address"></p>
                            </div>
                        </div>

                        <!-- Status -->
                        <div class="info-row flex items-center gap-2 py-2 px-1">
                            <div class="w-7 h-7 rounded-xl bg-green-50 flex items-center justify-center flex-shrink-0">
                                <svg class="w-3.5 h-3.5 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                            </div>
                            <div>
                                <p class="text-[10px] text-gray-400 font-medium uppercase tracking-wide">Status</p>
                                <span class="inline-flex items-center gap-1 px-1.5 py-0.5 rounded-full text-[10px] font-bold bg-green-100 text-green-700">
                                    <span class="w-1 h-1 rounded-full bg-green-500 animate-pulse inline-block"></span>
                                    <span x-text="employee.status"></span>
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- ── Job Information (Responsive) ── --}}
            <div class="section-card bg-white rounded-2xl p-4 sm:p-6" style="box-shadow: 0 4px 24px rgba(0,0,0,0.06);">
                <div class="flex items-center gap-3 mb-4">
                    <div class="w-1 h-5 bg-gradient-to-b from-blue-500 to-blue-700 rounded-full"></div>
                    <h3 class="text-base font-bold text-gray-800">Job Information</h3>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-x-4 gap-y-3">
                    <div class="job-field">
                        <p class="text-[10px] font-bold text-gray-400 uppercase tracking-widest mb-0.5">Department</p>
                        <p class="text-xs font-semibold text-gray-800" x-text="employee.department"></p>
                    </div>
                    <div class="job-field">
                        <p class="text-[10px] font-bold text-gray-400 uppercase tracking-widest mb-0.5">Position</p>
                        <p class="text-xs font-semibold text-gray-800" x-text="employee.position"></p>
                    </div>
                    <div class="job-field">
                        <p class="text-[10px] font-bold text-gray-400 uppercase tracking-widest mb-0.5">Employment Type</p>
                        <p class="text-xs font-semibold text-gray-800" x-text="employee.employment_type"></p>
                    </div>
                    <div class="job-field">
                        <p class="text-[10px] font-bold text-gray-400 uppercase tracking-widest mb-0.5">Employment Status</p>
                        <span class="inline-flex items-center gap-1 px-1.5 py-0.5 rounded-full text-[10px] font-bold bg-green-100 text-green-700">
                            <span class="w-1 h-1 rounded-full bg-green-500 animate-pulse inline-block"></span>
                            <span x-text="employee.employment_status"></span>
                        </span>
                    </div>
                    <div class="job-field">
                        <p class="text-[10px] font-bold text-gray-400 uppercase tracking-widest mb-0.5">Contract Period</p>
                        <p class="text-xs font-semibold text-gray-800" x-text="employee.contract_period"></p>
                    </div>
                    <div class="job-field">
                        <p class="text-[10px] font-bold text-gray-400 uppercase tracking-widest mb-0.5">Start Date</p>
                        <p class="text-xs font-semibold text-gray-800" x-text="employee.start_date"></p>
                    </div>
                    <div class="job-field">
                        <p class="text-[10px] font-bold text-gray-400 uppercase tracking-widest mb-0.5">End Date</p>
                        <p class="text-xs font-semibold text-gray-800" x-text="employee.end_date || '—'"></p>
                    </div>
                </div>
            </div>

            {{-- ── Documents (Responsive) ── --}}
            <div class="section-card bg-white rounded-2xl p-4 sm:p-6" style="box-shadow: 0 4px 24px rgba(0,0,0,0.06);">
                <div class="flex items-center gap-3 mb-4">
                    <div class="w-1 h-5 bg-gradient-to-b from-blue-500 to-blue-700 rounded-full"></div>
                    <h3 class="text-base font-bold text-gray-800">Documents</h3>
                </div>

                <div class="space-y-2">
                    <template x-for="(doc, index) in documents" :key="index">
                        <div class="doc-row flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2 p-3 rounded-xl border border-gray-100 hover:border-blue-200 hover:bg-blue-50/60 transition-all duration-300">
                            <div class="flex items-center gap-2">
                                <div class="w-7 h-7 rounded-xl bg-red-50 flex items-center justify-center flex-shrink-0">
                                    <svg class="w-3.5 h-3.5 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                    </svg>
                                </div>
                                <span class="text-xs font-semibold text-gray-700 truncate max-w-[150px] sm:max-w-none" x-text="doc.name"></span>
                            </div>
                            <div class="flex items-center gap-2">
                                <button @click="window.open(doc.download_url, '_blank')" class="px-2 py-1 text-[10px] font-semibold text-gray-600 bg-gray-50 border border-gray-200 rounded-lg hover:bg-gray-700 hover:text-white transition-all duration-200">
                                    Download
                                </button>
                            </div>
                        </div>
                    </template>

                    {{-- Fallback when no documents --}}
                    <template x-if="documents.length === 0">
                        <div class="text-center py-6 text-gray-400">
                            <svg class="w-10 h-10 mx-auto mb-2 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                            </svg>
                            <p class="text-xs">No documents uploaded yet.</p>
                        </div>
                    </template>
                </div>
            </div>

        </div>
    </main>

    {{-- ── Document Preview Modal (Mobile Optimized) ── --}}
    <div
        x-show="showDocModal"
        x-transition:enter="transition ease-out duration-300"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        x-transition:leave="transition ease-in duration-200"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
        class="fixed inset-0 z-[100] flex items-end sm:items-center justify-center p-0 sm:p-4"
        style="display: none;"
        @keydown.escape.window="closeDoc()"
    >
        <div class="absolute inset-0 bg-black/50 backdrop-blur-sm" @click="closeDoc()"></div>

        <div
            x-show="showDocModal"
            x-transition:enter="transition ease-out duration-300"
            x-transition:enter-start="opacity-0 translate-y-full sm:translate-y-4 sm:scale-95"
            x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
            x-transition:leave="transition ease-in duration-200"
            x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
            x-transition:leave-end="opacity-0 translate-y-full sm:translate-y-4 sm:scale-95"
            class="relative bg-white rounded-t-2xl sm:rounded-2xl shadow-2xl w-full max-w-2xl flex flex-col overflow-hidden"
            style="max-height: 90vh;"
        >
            <div class="flex items-center justify-between px-4 py-3 border-b border-gray-100 bg-gradient-to-r from-blue-600 to-blue-700">
                <div class="flex items-center gap-2">
                    <div class="w-7 h-7 rounded-xl bg-white/20 flex items-center justify-center flex-shrink-0">
                        <svg class="w-3.5 h-3.5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                        </svg>
                    </div>
                    <div>
                        <p class="text-[10px] text-blue-200 font-medium">Document Preview</p>
                        <p class="text-xs font-bold text-white truncate max-w-[180px]" x-text="activeDoc ? activeDoc.name : ''"></p>
                    </div>
                </div>
                <button @click="closeDoc()"
                    class="w-7 h-7 rounded-lg bg-white/20 hover:bg-white/30 flex items-center justify-center text-white transition-all duration-200">
                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>

            <div class="flex-1 overflow-auto bg-gray-100 flex flex-col items-center justify-center p-4" style="min-height: 320px;">
                <div class="w-full h-full flex flex-col items-center justify-center gap-3">
                    <div class="w-14 h-14 rounded-2xl bg-red-50 flex items-center justify-center shadow-md">
                        <svg class="w-7 h-7 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                        </svg>
                    </div>
                    <div class="text-center">
                        <p class="text-sm font-bold text-gray-800" x-text="activeDoc ? activeDoc.name : ''"></p>
                        <p class="text-xs text-gray-400 mt-1">Document</p>
                    </div>
                    <div class="bg-white rounded-xl px-4 py-2 shadow-sm border border-gray-200 text-center max-w-[250px]">
                        <p class="text-[10px] text-gray-500">Use the <span class="font-semibold text-blue-600">Download</span> button to open the file.</p>
                    </div>
                </div>
            </div>

            <div class="flex items-center justify-end gap-2 px-4 py-3 border-t border-gray-100 bg-gray-50">
                <button @click="closeDoc()"
                    class="px-3 py-1.5 text-xs font-semibold text-gray-600 bg-white border border-gray-200 rounded-xl hover:bg-gray-100 transition-all duration-200">
                    Close
                </button>
                <button @click="activeDoc && window.open(activeDoc.download_url, '_blank')" class="px-3 py-1.5 text-xs font-semibold text-white bg-blue-600 rounded-xl hover:bg-blue-700 transition-all duration-200 flex items-center gap-1">
                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                    </svg>
                    Download
                </button>
            </div>
        </div>
    </div>

</div>

<style>
@import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap');
* { font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif; }
[x-cloak] { display: none !important; }

::-webkit-scrollbar { width: 4px; }
::-webkit-scrollbar-track { background: #f1f1f1; border-radius: 10px; }
::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 10px; }

.anim-fade { animation: fadeSlideDown 0.4s ease both; }
@keyframes fadeSlideDown { from { opacity:0; transform:translateY(-12px); } to { opacity:1; transform:translateY(0); } }
.profile-card { animation: cardSlideUp 0.5s cubic-bezier(0.22, 1, 0.36, 1) both; }
.section-card { animation: cardSlideUp 0.5s cubic-bezier(0.22, 1, 0.36, 1) both; }
.section-card:nth-of-type(1) { animation-delay: 0.12s; }
.section-card:nth-of-type(2) { animation-delay: 0.22s; }

@keyframes cardSlideUp {
    from { opacity: 0; transform: translateY(28px); }
    to   { opacity: 1; transform: translateY(0); }
}

.avatar-pop { animation: avatarBounce 0.7s 0.3s cubic-bezier(0.34, 1.56, 0.64, 1) both; }
@keyframes avatarBounce {
    from { opacity: 0; transform: translateY(12px) scale(0.75); }
    to   { opacity: 1; transform: translateY(0) scale(1); }
}

.name-fade { animation: nameFade 0.5s 0.4s ease both; }
@keyframes nameFade {
    from { opacity: 0; transform: translateX(-12px); }
    to   { opacity: 1; transform: translateX(0); }
}

.info-row { animation: rowFade 0.4s ease both; border-radius: 10px; transition: all 0.2s ease; }
.info-row:nth-child(1) { animation-delay: 0.38s; }
.info-row:nth-child(2) { animation-delay: 0.44s; }
.info-row:nth-child(3) { animation-delay: 0.50s; }
.info-row:nth-child(4) { animation-delay: 0.56s; }
.info-row:nth-child(5) { animation-delay: 0.62s; }
.info-row:nth-child(6) { animation-delay: 0.68s; }
.info-row:hover { background: #f0f7ff; transform: translateX(4px); }

.job-field { animation: rowFade 0.4s ease both; }
.job-field:nth-child(1) { animation-delay: 0.18s; }
.job-field:nth-child(2) { animation-delay: 0.24s; }
.job-field:nth-child(3) { animation-delay: 0.30s; }
.job-field:nth-child(4) { animation-delay: 0.36s; }
.job-field:nth-child(5) { animation-delay: 0.42s; }
.job-field:nth-child(6) { animation-delay: 0.48s; }
.job-field:nth-child(7) { animation-delay: 0.54s; }

.doc-row { animation: rowFade 0.4s ease both; }
.doc-row:nth-child(1) { animation-delay: 0.28s; }
.doc-row:nth-child(2) { animation-delay: 0.38s; }

@keyframes rowFade {
    from { opacity: 0; transform: translateY(12px); }
    to   { opacity: 1; transform: translateY(0); }
}

/* Mobile specific adjustments */
@media (max-width: 640px) {
    .info-row {
        padding: 0.5rem 0.25rem;
    }
}
</style>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        document.querySelectorAll('.download-sample').forEach(btn => {
            btn.addEventListener('click', function() {
                alert('Download demo. In production, this would download the actual file.');
            });
        });
    });
</script>
@endsection
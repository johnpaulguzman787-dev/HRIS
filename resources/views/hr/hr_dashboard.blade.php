@extends('layouts.app')

@section('title', 'Dashboard')

@section('content')
@php
    $openSession = $todayLog?->sessions()->whereNull('clock_out')->first();
    $completedWorkSeconds = $todayLog
        ? (int) $todayLog->sessions()->whereNotNull('clock_out')->get()->sum(function($s) {
            return max(0, \Carbon\Carbon::parse($s->clock_in)->diffInSeconds(\Carbon\Carbon::parse($s->clock_out)) - ($s->break_minutes * 60));
          })
        : 0;
@endphp
<div x-data="{
        sidebarCollapsed: localStorage.getItem('sidebarCollapsed') === 'true',
        workSetup: '{{ $todayLog?->work_setup ?? ($employeeShift?->work_setup ?? "wfh") }}',
        onLeave: {{ $todayLog?->status === 'on_leave' ? 'true' : 'false' }},
        clockedIn: {{ $openSession ? 'true' : 'false' }},
        clockedOut: {{ ($todayLog?->clock_out && !$openSession) ? 'true' : 'false' }},
        onBreak: {{ ($openSession?->break_start && !$openSession?->break_end) ? 'true' : 'false' }},
        resumed: false,
        breakReminder: '',
        breakTime: '{{ $openSession?->break_start ? \Carbon\Carbon::parse($openSession->break_start)->setTimezone(config("app.timezone"))->format("h:i A") : "" }}',
        breakMinutes: {{ $todayLog?->break_minutes ?? 0 }},
        completedWorkSeconds: {{ $completedWorkSeconds }},
        currentSessionStart: {{ $openSession?->clock_in ? \Carbon\Carbon::parse($openSession->clock_in)->valueOf() : 'null' }},
        currentSessionBreakMinutes: {{ $openSession?->break_minutes ?? 0 }},
        breakStartTimestamp: {{ ($openSession?->break_start && !$openSession?->break_end) ? \Carbon\Carbon::parse($openSession->break_start)->valueOf() : 'null' }},
        clockInTime:  '{{ $todayLog?->clock_in ? \Carbon\Carbon::parse($todayLog->clock_in)->setTimezone(config("app.timezone"))->format("h:i A") : "" }}',
        clockOutTime: '{{ $todayLog?->clock_out ? \Carbon\Carbon::parse($todayLog->clock_out)->setTimezone(config("app.timezone"))->format("h:i A") : "" }}',
        errorMessage: '',
        showError(msg) { this.errorMessage = msg; },
        elapsedSeconds: 0,
        assignedShiftId: {{ $employeeShift?->shift_id ?? 'null' }},
        breakAllowed: true,
        currentTime: '',
        currentDate: '',
        mobileMenuOpen: false,

        showAttendancePopup: {{ $employeeShift ? 'true' : 'false' }},

        showAnnouncement: false,
        annType: '',
        annTypeOpen: false,
        annTitle: '',
        annMessage: '',
        annAudience: '',
        annDept: '',
        annSaving: false,
        annError: '',
        annTypeOptions: [
            { value: 'notice',       label: 'Notice',       icon: 'info',    bg: 'bg-blue-100',   color: 'text-blue-600'  },
            { value: 'process_done', label: 'Process Done', icon: 'check',   bg: 'bg-green-100',  color: 'text-green-600' },
            { value: 'caution',      label: 'Caution',      icon: 'caution', bg: 'bg-orange-100', color: 'text-orange-500'},
            { value: 'warning',      label: 'Warning',      icon: 'warning', bg: 'bg-red-100',    color: 'text-red-500'   },
        ],
        get selectedTypeObj() {
            return this.annTypeOptions.find(t => t.value === this.annType) || null;
        },
        openAnnouncement() {
            this.showAnnouncement = true;
            this.annType = ''; this.annTypeOpen = false; this.annTitle = '';
            this.annMessage = ''; this.annAudience = ''; this.annDept = ''; this.annError = '';
            document.body.style.overflow = 'hidden';
        },
        closeAnnouncement() { this.showAnnouncement = false; document.body.style.overflow = ''; },
        async submitAnnouncement() {
            this.annError = '';
            if (!this.annType || !this.annTitle || !this.annMessage || !this.annAudience) {
                this.annError = 'Please fill in all required fields.'; return;
            }
            if (this.annAudience === 'department' && !this.annDept) {
                this.annError = 'Please select a department.'; return;
            }
            this.annSaving = true;
            const csrf = document.querySelector('meta[name=csrf-token]').getAttribute('content');
            try {
                const res = await fetch('{{ route('hr.announcements.store') }}', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf },
                    body: JSON.stringify({ type: this.annType, title: this.annTitle, message: this.annMessage, audience: this.annAudience, department_id: this.annDept || null })
                });
                const data = await res.json();
                if (res.ok) { this.closeAnnouncement(); this.showAlert('Announcement Created!', 'Your announcement has been sent to the intended audience.', () => window.location.reload()); }
                else { this.annError = data.message ?? 'Something went wrong.'; }
            } catch(e) { this.annError = 'An error occurred. Please try again.'; }
            this.annSaving = false;
        },
        alertModal: { show: false, title: '', message: '', callback: null },
        showAlert(title, message, callback = null) {
            this.alertModal = { show: true, title, message, callback };
        },
        alertOk() {
            const cb = this.alertModal.callback;
            this.alertModal.show = false;
            if (cb) cb();
        },
        get elapsedDisplay() {
            const h = String(Math.floor(this.elapsedSeconds/3600)).padStart(2,'0');
            const m = String(Math.floor((this.elapsedSeconds%3600)/60)).padStart(2,'0');
            const s = String(this.elapsedSeconds%60).padStart(2,'0');
            return `${h}h ${m}m ${s}s`;
        },
        initClock() {
            this.updateTime();
            setInterval(() => {
                this.updateTime();
                if (this.clockedIn && !this.onBreak) {
                    this.elapsedSeconds = this.currentSessionStart
                        ? this.completedWorkSeconds + Math.floor((Date.now() - this.currentSessionStart) / 1000) - (this.currentSessionBreakMinutes * 60)
                        : this.completedWorkSeconds;
                }
            }, 1000);
            window.addEventListener('sidebar-toggle', e => {
                this.sidebarCollapsed = e.detail.collapsed;
            });
        },
        updateTime() {
            const now = new Date();
            this.currentTime = String(now.getHours()).padStart(2,'0') + ':' + String(now.getMinutes()).padStart(2,'0') + ':' + String(now.getSeconds()).padStart(2,'0');
            const days = ['Sunday','Monday','Tuesday','Wednesday','Thursday','Friday','Saturday'];
            const months = ['January','February','March','April','May','June','July','August','September','October','November','December'];
            this.currentDate = days[now.getDay()] + ', ' + months[now.getMonth()] + ' ' + now.getDate() + ', ' + now.getFullYear();
        },
        async handleClock() {
            const csrf = document.querySelector('meta[name=csrf-token]').getAttribute('content');
            if (this.onBreak) {
                const res = await fetch('{{ route("hr.attendance.clock-in") }}', {
                    method: 'POST', headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf },
                    body: JSON.stringify({ work_setup: this.workSetup, shift_id: this.assignedShiftId })
                });
                const data = await res.json();
                if (res.ok) {
                    const breakSecs = data.break_seconds ?? (data.break_minutes * 60);
                    const snapped = this.currentSessionStart
                        ? Math.max(0, this.completedWorkSeconds + Math.floor((Date.now() - this.currentSessionStart) / 1000) - breakSecs)
                        : this.completedWorkSeconds;
                    this.completedWorkSeconds = snapped;
                    this.currentSessionStart = Date.now();
                    this.currentSessionBreakMinutes = 0;
                    this.onBreak = false; this.resumed = true; this.clockedIn = true; this.breakMinutes = data.break_minutes;
                } else { this.showError(data.message ?? 'Resume failed.'); }
                return;
            }
            if (!this.clockedIn) {
                const res = await fetch('{{ route("hr.attendance.clock-in") }}', {
                    method: 'POST', headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf },
                    body: JSON.stringify({ work_setup: this.workSetup, shift_id: this.assignedShiftId })
                });
                const data = await res.json();
                if (res.ok) {
                    this.clockedIn = true;
                    this.clockedOut = false;
                    this.resumed = false;
                    this.clockInTime = data.clock_in;
                    this.completedWorkSeconds = 0;
                    this.currentSessionStart = Date.now();
                    this.currentSessionBreakMinutes = 0;
                } else { this.showError(data.message ?? 'Clock-in failed.'); }
            }
        },
        async handleBreak() {
            if (!this.clockedIn || this.onBreak) return;
            const csrf = document.querySelector('meta[name=csrf-token]').getAttribute('content');
            const res = await fetch('{{ route("hr.attendance.break") }}', {
                method: 'POST', headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf }, body: JSON.stringify({})
            });
            const data = await res.json();
            if (res.ok) {
                this.onBreak = true;
                this.resumed = false;
                this.breakTime = data.break_start;
                this.breakStartTimestamp = Date.now();
                if (data.reminder) { this.breakReminder = data.reminder; setTimeout(() => { this.breakReminder = ''; }, 5000); } else { this.breakReminder = ''; }
            } else { this.showError(data.message ?? 'Break failed.'); }
        },
        async handleClockOut() {
            if (!this.clockedIn) return;
            const csrf = document.querySelector('meta[name=csrf-token]').getAttribute('content');
            const res = await fetch('{{ route("hr.attendance.clock-out") }}', {
                method: 'POST', headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf }, body: JSON.stringify({})
            });
            const data = await res.json();
            if (res.ok) {
                this.clockedIn = false;
                this.clockedOut = true;
                this.clockOutTime = data.clock_out;
                this.onBreak = false;
            } else { this.showError(data.message ?? 'Clock-out failed.'); }
        }
    }"
    x-init="initClock()"
    class="flex h-screen overflow-hidden" style="background:#eef2f7;">

    {{-- ═══════════════════════════════════════ --}}
    {{-- DESKTOP SIDEBAR (hidden on mobile)      --}}
    {{-- ═══════════════════════════════════════ --}}
    <div class="hidden lg:block">
        @include('hr.hr_sidebar', ['activeMenu' => 'dashboard'])
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
            @include('hr.hr_sidebar', ['activeMenu' => 'dashboard'])
        </div>
    </div>

    {{-- ═══════════════════════════════════════ --}}
    {{-- MAIN CONTENT                            --}}
    {{-- ═══════════════════════════════════════ --}}
    <main class="flex-1 overflow-y-auto min-h-screen w-full"
        :class="sidebarCollapsed ? 'lg:ml-20' : 'lg:ml-72'"
        style="transition: margin-left 0.35s cubic-bezier(0.4, 0, 0.2, 1);">

        {{-- ── HEADER ── --}}
        <header class="bg-gradient-to-r from-blue-600 to-blue-700 text-white sticky top-0 z-10 shadow-lg mt-3 mx-3 rounded-2xl overflow-visible">
            <div class="px-5 py-4 flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <button @click="mobileMenuOpen = true"
                            class="lg:hidden p-1.5 rounded-lg hover:bg-white/20 transition-colors">
                        <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M4 6h16M4 12h16M4 18h16"/>
                        </svg>
                    </button>
                    <h1 class="text-xl font-bold text-white header-title">Dashboard</h1>
                </div>
                <div class="flex items-center space-x-3">
                    <x-hr-notif />
                </div>
            </div>
        </header>

        <div class="p-3 lg:p-6">

            {{-- ── STAT CARDS ── --}}
            <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 lg:gap-5 mb-4 lg:mb-5">
                <div class="stat-card bg-white rounded-xl p-4 lg:p-6 card-anim" style="animation-delay:0.05s; border:1px solid #e5e7eb;">
                    <p class="text-xs font-semibold text-gray-400 uppercase tracking-widest mb-2">Total Employees</p>
                    <p class="text-3xl lg:text-5xl font-bold" style="color:#3b82f6;">{{ $totalEmployees }}</p>
                    <div class="stat-bar mt-3"><div class="stat-bar-fill" style="width:100%; background:#3b82f6;"></div></div>
                </div>
                <div class="stat-card bg-white rounded-xl p-4 lg:p-6 card-anim" style="animation-delay:0.12s; border:1px solid #e5e7eb;">
                    <p class="text-xs font-semibold text-gray-400 uppercase tracking-widest mb-2">Present Today</p>
                    <p class="text-3xl lg:text-5xl font-bold text-gray-900">{{ $presentToday }}</p>
                    <div class="stat-bar mt-3"><div class="stat-bar-fill" style="width:82%; background:#22c55e;"></div></div>
                </div>
                <div class="stat-card bg-white rounded-xl p-4 lg:p-6 card-anim" style="animation-delay:0.19s; border:1px solid #e5e7eb;">
                    <p class="text-xs font-semibold text-gray-400 uppercase tracking-widest mb-2">Late and Absent</p>
                    <p class="text-3xl lg:text-5xl font-bold text-gray-900">{{ $lateToday }}</p>
                    <div class="stat-bar mt-3"><div class="stat-bar-fill" style="width:18%; background:#ef4444;"></div></div>
                </div>
                @if(isset($pendingRequests))
                <div class="stat-card bg-white rounded-xl p-4 lg:p-6 card-anim" style="animation-delay:0.26s; border:1px solid #e5e7eb;">
                    <p class="text-xs font-semibold text-gray-400 uppercase tracking-widest mb-2">Pending Requests</p>
                    <p class="text-3xl lg:text-5xl font-bold text-gray-900">{{ $pendingRequests }}</p>
                    <div class="stat-bar mt-3"><div class="stat-bar-fill" style="width:9%; background:#f59e0b;"></div></div>
                </div>
                @endif
            </div>

            {{-- ── MAIN GRID ── --}}
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-4 lg:gap-5">

                {{-- ── LEFT COLUMN ── --}}
                <div class="space-y-4 lg:space-y-5">

                    {{-- TODAY'S ATTENDANCE SUMMARY --}}
                    <div class="bg-white rounded-xl p-4 lg:p-6 card-anim" style="animation-delay:0.3s; border:1px solid #e5e7eb;">
                        <h2 class="text-xs font-bold text-gray-700 uppercase tracking-widest">Today's Attendance Summary</h2>
                        <p class="text-xs text-gray-400 mt-1 mb-3">{{ date('F d, Y') }}</p>

                        <div class="mb-3">
                            <select id="attendance-dept-filter" onchange="filterAttendanceSummary(this.value)" class="text-xs border border-gray-200 rounded-lg px-3 py-1.5 text-gray-600 bg-white focus:outline-none focus:ring-2 focus:ring-blue-100 focus:border-blue-400 w-full lg:w-auto">
                                <option value="all">All Departments</option>
                                @foreach($departments ?? [] as $dept)
                                <option value="{{ $dept->id }}">{{ $dept->name }}</option>
                                @endforeach
                            </select>
                        </div>

                        {{-- Mobile: vertical stacked big stat boxes --}}
                        <div class="space-y-2 mb-4 lg:hidden">
                            <div class="rounded-xl p-4 text-center" style="background:#dcfce7;">
                                <p id="stat-present-mobile" class="text-2xl font-bold" style="color:#16a34a;">{{ $attendanceSummary['present'] }}</p>
                                <p class="text-xs font-bold uppercase mt-1" style="color:#16a34a;">Present</p>
                            </div>
                            <div class="rounded-xl p-4 text-center" style="background:#fef9c3;">
                                <p id="stat-late-mobile" class="text-2xl font-bold" style="color:#ca8a04;">{{ $attendanceSummary['late'] }}</p>
                                <p class="text-xs font-bold uppercase mt-1" style="color:#ca8a04;">Late</p>
                            </div>
                            <div class="rounded-xl p-4 text-center" style="background:#fee2e2;">
                                <p id="stat-absent-mobile" class="text-2xl font-bold" style="color:#dc2626;">{{ $attendanceSummary['absent'] }}</p>
                                <p class="text-xs font-bold uppercase mt-1" style="color:#dc2626;">Absent</p>
                            </div>
                            <div class="rounded-xl p-4 text-center" style="background:#fce7f3;">
                                <p id="stat-onleave-mobile" class="text-2xl font-bold" style="color:#db2777;">{{ $attendanceSummary['on_leave'] }}</p>
                                <p class="text-xs font-bold uppercase mt-1" style="color:#db2777;">On Leave</p>
                            </div>
                        </div>

                        {{-- Desktop: 4-col grid --}}
                        <div class="hidden lg:grid grid-cols-4 gap-2 mb-5">
                            <div class="stat-box rounded-xl p-2 text-center" style="background:#dcfce7;">
                                <p id="stat-present-desktop" class="text-base font-bold" style="color:#16a34a;">{{ $attendanceSummary['present'] }}</p>
                                <p class="text-xs font-semibold uppercase" style="color:#16a34a;">Present</p>
                            </div>
                            <div class="stat-box rounded-xl p-2 text-center" style="background:#fef9c3;">
                                <p id="stat-late-desktop" class="text-base font-bold" style="color:#ca8a04;">{{ $attendanceSummary['late'] }}</p>
                                <p class="text-xs font-semibold uppercase" style="color:#ca8a04;">Late</p>
                            </div>
                            <div class="stat-box rounded-xl p-2 text-center" style="background:#fee2e2;">
                                <p id="stat-absent-desktop" class="text-base font-bold" style="color:#dc2626;">{{ $attendanceSummary['absent'] }}</p>
                                <p class="text-xs font-semibold uppercase" style="color:#dc2626;">Absent</p>
                            </div>
                            <div class="stat-box rounded-xl p-2 text-center" style="background:#fce7f3;">
                                <p id="stat-onleave-desktop" class="text-base font-bold" style="color:#db2777;">{{ $attendanceSummary['on_leave'] }}</p>
                                <p class="text-xs font-semibold uppercase" style="color:#db2777;">On Leave</p>
                            </div>
                        </div>

                        <p class="text-xs font-semibold text-gray-600 uppercase tracking-wider mb-3">Overview</p>
                        <div id="overview-rows" class="space-y-3">
                            @foreach($departmentProgress as $dept)
                            <div data-dept-id="{{ $dept['id'] }}">
                                <div class="flex justify-between mb-1">
                                    <span class="text-xs text-gray-600 font-medium">{{ $dept['name'] }}</span>
                                    <span class="text-xs text-gray-400">{{ $dept['percentage'] }}%</span>
                                </div>
                                <div class="h-1.5 bg-gray-100 rounded-full overflow-hidden">
                                    <div class="progress-fill h-full rounded-full"
                                         style="--tw: {{ $dept['percentage'] }}%; background: {{ $dept['percentage'] >= 80 ? '#22c55e' : ($dept['percentage'] >= 60 ? '#f59e0b' : '#ef4444') }};"></div>
                                </div>
                            </div>
                            @endforeach
                        </div>
                    </div>

                    {{-- QUICK ACTIONS --}}
                    <div class="bg-white rounded-xl p-4 lg:p-6 card-anim" style="animation-delay:0.42s; border:1px solid #e5e7eb;">
                        <h2 class="text-xs font-bold text-gray-700 uppercase tracking-widest mb-4">Quick Actions</h2>
                        <div class="grid grid-cols-2 gap-3">
                            <button @click="window.location='{{ route('hr.employees.directory') }}?action=add'" class="action-btn px-4 py-3 border border-gray-200 rounded-lg text-sm text-gray-600 font-medium">Add Employee</button>
                            <button @click="openAnnouncement()"
                                class="action-btn px-4 py-3 border border-gray-200 rounded-lg text-sm text-gray-600 font-medium">
                                Create Announcement
                            </button>
                        </div>
                    </div>
                </div>

                {{-- ── CENTER COLUMN: TIME & ATTENDANCE ── --}}
                <div class="bg-white rounded-xl p-4 lg:p-6 card-anim flex flex-col" style="animation-delay:0.35s; border:1px solid #e5e7eb;">
                    <p class="text-sm font-bold text-gray-700 uppercase tracking-widest mb-1">Time & Attendance</p>
                    <div class="mb-1">
                        <p class="text-xs text-gray-400 font-medium mb-1" x-text="currentDate"></p>
                        <p class="font-black tabular-nums leading-none" style="font-size:2.4rem; letter-spacing:-1px; color:#3b82f6;" x-text="currentTime"></p>
                    </div>
                    <hr class="my-4 border-gray-100">

                    {{-- Shift Schedule --}}
                    <div class="mb-3">
                        <p class="text-xs font-semibold text-gray-500 mb-2">Shift Schedule</p>
                        @if($employeeShift)
                        <div class="border border-gray-200 rounded-xl overflow-hidden bg-gray-50">
                            <div class="flex items-center justify-between px-3 py-2">
                                <span class="text-xs font-semibold text-gray-600">{{ $employeeShift->shift->name ?? '—' }}</span>
                                <span class="text-xs text-gray-400">
                                    @if($employeeShift->shift)
                                        @if($employeeShift->shift->is_flexi)
                                            {{ $employeeShift->shift->required_hours }}h required
                                        @else
                                            {{ \Carbon\Carbon::parse($employeeShift->shift->start_time)->format('g:i A') }} – {{ \Carbon\Carbon::parse($employeeShift->shift->end_time)->format('g:i A') }}
                                        @endif
                                    @else
                                        —
                                    @endif
                                </span>
                            </div>
                            <div class="flex items-center justify-between px-3 py-2 border-t border-gray-100">
                                <span class="text-xs text-gray-400">Work Setup</span>
                                <span class="text-xs font-semibold" style="color:#1d4ed8;">{{ strtoupper($employeeShift->work_setup ?? '—') }}</span>
                            </div>
                        </div>
                        @else
                        <div class="border border-dashed border-gray-200 rounded-xl px-3 py-4 bg-gray-50 text-center">
                            <p class="text-xs text-gray-400 font-medium">No shift assigned yet.</p>
                            <p class="text-xs text-gray-300 mt-1">Contact your HR to assign a shift.</p>
                        </div>
                        @endif
                    </div>

                    {{-- Today's Attendance --}}
                    <div class="mb-3">
                        <p class="text-xs font-semibold text-gray-500 mb-2">Today's Attendance</p>
                        <div class="flex gap-2 mb-2">
                            <div class="flex-1 border border-gray-200 rounded-xl px-3 py-3 bg-gray-50">
                                <p class="text-xs text-gray-400 font-semibold tracking-wider mb-1.5">TIME IN</p>
                                <p class="text-sm font-bold text-gray-700 border-b border-gray-300 pb-0.5" x-text="clockInTime || '–'"></p>
                            </div>
                            <div class="flex-1 border border-gray-200 rounded-xl px-3 py-3 bg-gray-50">
                                <p class="text-xs text-gray-400 font-semibold tracking-wider mb-1.5">BREAK</p>
                                <p class="text-sm font-bold text-gray-700 border-b border-gray-300 pb-0.5" x-text="breakTime ? breakTime : '–'"></p>
                            </div>
                            <div class="flex-1 border border-gray-200 rounded-xl px-3 py-3 bg-gray-50">
                                <p class="text-xs text-gray-400 font-semibold tracking-wider mb-1.5">TIME OUT</p>
                                <p class="text-sm font-bold text-gray-700 border-b border-gray-300 pb-0.5" x-text="clockOutTime || '–'"></p>
                            </div>
                        </div>
                        <p class="text-xs text-center text-gray-400 font-medium" x-show="!clockedIn && !clockedOut"><span class="mr-1">⏱</span><span x-text="elapsedDisplay"></span></p>
                        <p class="text-xs text-center font-medium" x-show="clockedIn && !onBreak" style="color:#3b82f6;"><span class="mr-1">⏱</span><span x-text="elapsedDisplay"></span></p>
                        <p class="text-xs text-center font-medium" x-show="onBreak" style="color:#f59e0b;"> On break · timer paused</p>
                        <p class="text-xs text-center font-semibold" x-show="clockedOut" style="color:#22c55e;">✓ Attendance recorded · <span x-text="elapsedDisplay"></span></p>
                        <p x-show="onLeave" class="text-xs text-center font-semibold" style="color:#6366f1;">You are on approved leave today.</p>
                        <p x-show="breakReminder" x-cloak x-text="breakReminder" class="text-xs text-center font-medium mt-1" style="color:#d97706;"></p>
                    </div>

                    {{-- ── ACTION BUTTONS ── --}}
                    {{-- FIX: TIME IN enabled when: not clocked in OR on break (resume). Disabled only when clocked in and NOT on break, or onLeave, or no shift. --}}
                    <div class="mt-auto flex gap-2">
                        <button @click="handleClock()"
                                :disabled="onLeave || (clockedIn && !onBreak) || (!clockedIn && clockedOut) || !assignedShiftId"
                                class="clock-btn flex-1 py-3 text-white font-bold text-xs tracking-widest uppercase"
                                :style="(onLeave || (clockedIn && !onBreak) || (!clockedIn && clockedOut) || !assignedShiftId) ? 'background:#94a3b8; cursor:not-allowed;' : 'background:#3b82f6; cursor:pointer;'">
                            <span x-text="onBreak ? 'RESUME' : 'TIME IN'"></span>
                        </button>
                        <button @click="handleBreak()"
                                :disabled="!clockedIn || onBreak || onLeave || clockedOut"
                                class="clock-btn flex-1 py-3 font-bold text-xs tracking-widest uppercase"
                                :style="(!clockedIn || onBreak || onLeave || clockedOut) ? 'background:#94a3b8; color:white; cursor:not-allowed;' : 'background:#dbeafe; color:#1d4ed8; cursor:pointer;'">
                            BREAK
                        </button>
                        <button @click="handleClockOut()"
                                :disabled="!clockedIn || clockedOut"
                                class="clock-btn flex-1 py-3 text-white font-bold text-xs tracking-widest uppercase"
                                :style="(!clockedIn || clockedOut) ? 'background:#94a3b8; cursor:not-allowed;' : 'background:#3b82f6; cursor:pointer;'">
                            TIME OUT
                        </button>
                    </div>
                </div>

                {{-- ── RIGHT COLUMN: CALENDAR ── --}}
                <div x-data="calendarWidget({{ json_encode($allHolidays) }})"
                     class="bg-white rounded-xl p-4 lg:p-6 card-anim" style="animation-delay:0.4s; border:1px solid #e5e7eb;">
                    <div class="flex items-center justify-between mb-4">
                        <button @click="prevMonth()" class="cal-nav-btn p-1.5 hover:bg-gray-100 rounded-lg">
                            <svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                            </svg>
                        </button>
                        <p class="text-xs font-bold uppercase tracking-widest" style="color:#3b82f6;" x-text="calMonthName"></p>
                        <button @click="nextMonth()" class="cal-nav-btn p-1.5 hover:bg-gray-100 rounded-lg">
                            <svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                            </svg>
                        </button>
                    </div>
                    <div class="grid grid-cols-7 mb-1">
                        <template x-for="d in ['Su','Mo','Tu','We','Th','Fr','Sa']">
                            <div class="text-center text-xs text-gray-400 font-semibold py-1" x-text="d"></div>
                        </template>
                    </div>
                    <div class="grid grid-cols-7 gap-0.5 mb-5">
                        <template x-for="_ in range(firstDay)"><div></div></template>
                        <template x-for="day in days()">
                            <div class="cal-cell">
                                <button class="cal-day w-full text-center text-xs py-2 rounded-full"
                                    :class="isToday(day) ? 'today-pill text-white font-bold' : 'text-gray-600 hover:bg-gray-100'"
                                    :style="isToday(day) ? 'background:#3b82f6;' : ''"
                                    x-text="day"></button>
                                <template x-if="holidayMap[day]">
                                    <span class="holiday-dot"></span>
                                </template>
                                <template x-if="holidayMap[day]">
                                    <div class="holiday-tooltip" x-text="holidayMap[day]"></div>
                                </template>
                            </div>
                        </template>
                    </div>
                    <div class="border-t border-gray-100 mb-4"></div>
                    <h2 class="text-xs font-bold text-gray-700 uppercase tracking-widest mb-3">Upcoming Events</h2>
                    <div class="space-y-3">
                        <template x-if="upcomingEvents.length === 0">
                            <p class="text-xs text-gray-400 text-center py-3">No upcoming events this month.</p>
                        </template>
                        <template x-for="h in upcomingEvents" :key="h.date">
                            <div class="flex items-center gap-3 p-3 rounded-lg" style="background:#eff6ff;">
                                <div class="w-2 h-2 rounded-full flex-shrink-0" style="background:#3b82f6;"></div>
                                <div class="flex-1 min-w-0">
                                    <p class="text-xs font-semibold text-gray-700 truncate" x-text="h.name"></p>
                                    <p class="text-xs text-gray-400" x-text="new Date(h.date + 'T00:00:00').toLocaleDateString('en-US',{month:'long',day:'2-digit',year:'numeric'}) + ' · ' + h.type.charAt(0).toUpperCase() + h.type.slice(1)"></p>
                                </div>
                            </div>
                        </template>
                    </div>
                </div>

            </div>
        </div>
    </main>

    {{-- ═══════════════════════════════════════ --}}
    {{-- CREATE ANNOUNCEMENT MODAL              --}}
    {{-- ═══════════════════════════════════════ --}}
    <div x-show="showAnnouncement"
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         class="fixed inset-0 z-[999] flex items-end sm:items-center justify-center p-0 sm:p-4"
         style="display:none;">
        <div class="absolute inset-0 bg-black/40" @click="closeAnnouncement()"></div>
        <div x-show="showAnnouncement"
             x-transition:enter="transition ease-out duration-250"
             x-transition:enter-start="opacity-0 translate-y-full sm:scale-95 sm:translate-y-3"
             x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
             x-transition:leave="transition ease-in duration-150"
             x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
             x-transition:leave-end="opacity-0 translate-y-full sm:scale-95 sm:translate-y-3"
             class="relative bg-white w-full sm:max-w-lg sm:rounded-2xl rounded-t-2xl shadow-2xl overflow-y-auto"
             style="max-height: 90vh; padding: 24px 20px 28px;">
            <button @click="closeAnnouncement()"
                class="absolute top-4 right-4 w-9 h-9 rounded-full border-2 border-gray-300 flex items-center justify-center text-gray-400 hover:border-gray-400 hover:text-gray-600 transition-all">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
            <div class="sm:hidden w-10 h-1 bg-gray-200 rounded-full mx-auto mb-5"></div>
            <h2 class="text-xl font-bold text-gray-900 mb-5">Create Announcement</h2>
            <template x-if="annError">
                <div class="mb-4 px-4 py-3 bg-red-50 border border-red-100 rounded-xl text-sm text-red-600" x-text="annError"></div>
            </template>
            <div class="mb-4">
                <label class="block text-sm font-semibold text-gray-800 mb-2">Announcement Type</label>
                <div class="relative" @click.outside="annTypeOpen = false">
                    <button type="button" @click="annTypeOpen = !annTypeOpen"
                        class="w-full flex items-center justify-between px-4 py-3 border border-gray-200 rounded-xl text-sm bg-white hover:border-gray-300 transition-colors focus:outline-none"
                        :class="annTypeOpen ? 'border-blue-400 ring-2 ring-blue-100' : ''">
                        <div class="flex items-center gap-2.5">
                            <template x-if="!selectedTypeObj"><span class="text-gray-400">Choose announcement type</span></template>
                            <template x-if="selectedTypeObj">
                                <div class="flex items-center gap-2.5">
                                    <div class="w-6 h-6 rounded-md flex items-center justify-center flex-shrink-0" :class="selectedTypeObj.bg">
                                        <template x-if="selectedTypeObj.icon === 'info'"><svg class="w-3.5 h-3.5" :class="selectedTypeObj.color" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg></template>
                                        <template x-if="selectedTypeObj.icon === 'check'"><svg class="w-3.5 h-3.5" :class="selectedTypeObj.color" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg></template>
                                        <template x-if="selectedTypeObj.icon === 'caution'"><svg class="w-3.5 h-3.5" :class="selectedTypeObj.color" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg></template>
                                        <template x-if="selectedTypeObj.icon === 'warning'"><svg class="w-3.5 h-3.5" :class="selectedTypeObj.color" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg></template>
                                    </div>
                                    <span class="text-gray-800 font-medium" x-text="selectedTypeObj.label"></span>
                                </div>
                            </template>
                        </div>
                        <svg class="w-4 h-4 text-gray-400 transition-transform duration-200" :class="annTypeOpen ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                    </button>
                    <div x-show="annTypeOpen" x-transition class="absolute left-0 right-0 top-full mt-1.5 bg-white border border-gray-200 rounded-xl shadow-lg z-50 overflow-hidden py-1.5">
                        <template x-for="opt in annTypeOptions" :key="opt.value">
                            <button type="button" @click="annType = opt.value; annTypeOpen = false"
                                class="w-full flex items-center gap-3 px-4 py-2.5 hover:bg-gray-50 transition-colors text-left"
                                :class="annType === opt.value ? 'bg-blue-50/60' : ''">
                                <div class="w-7 h-7 rounded-lg flex items-center justify-center flex-shrink-0" :class="opt.bg">
                                    <template x-if="opt.icon === 'info'"><svg class="w-4 h-4" :class="opt.color" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg></template>
                                    <template x-if="opt.icon === 'check'"><svg class="w-4 h-4" :class="opt.color" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg></template>
                                    <template x-if="opt.icon === 'caution'"><svg class="w-4 h-4" :class="opt.color" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg></template>
                                    <template x-if="opt.icon === 'warning'"><svg class="w-4 h-4" :class="opt.color" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg></template>
                                </div>
                                <span class="text-sm text-gray-700 font-medium" x-text="opt.label"></span>
                            </button>
                        </template>
                    </div>
                </div>
            </div>
            <div class="mb-4">
                <label class="block text-sm font-semibold text-gray-800 mb-2">Title</label>
                <input type="text" x-model="annTitle" placeholder="Enter title"
                    class="w-full px-4 py-3 border border-gray-200 rounded-xl text-sm text-gray-800 placeholder-gray-400 focus:outline-none focus:border-blue-400 focus:ring-2 focus:ring-blue-100 transition-all">
            </div>
            <div class="mb-4">
                <label class="block text-sm font-semibold text-gray-800 mb-2">Message</label>
                <textarea x-model="annMessage" placeholder="Write your announcement" rows="3"
                    class="w-full px-4 py-3 border border-gray-200 rounded-xl text-sm text-gray-800 placeholder-gray-400 focus:outline-none focus:border-blue-400 focus:ring-2 focus:ring-blue-100 transition-all resize-none"></textarea>
            </div>
            <div class="mb-4">
                <label class="block text-sm font-semibold text-gray-800 mb-2">Audience</label>
                <div class="flex items-center gap-6">
                    <label class="flex items-center gap-2 cursor-pointer select-none">
                        <input type="checkbox" :checked="annAudience === 'everyone'" @change="annAudience = annAudience === 'everyone' ? '' : 'everyone'"
                            class="w-4 h-4 rounded border-gray-300 text-blue-600 focus:ring-blue-400 cursor-pointer">
                        <span class="text-sm text-gray-700">Everyone</span>
                    </label>
                    <label class="flex items-center gap-2 cursor-pointer select-none">
                        <input type="checkbox" :checked="annAudience === 'department'" @change="annAudience = annAudience === 'department' ? '' : 'department'"
                            class="w-4 h-4 rounded border-gray-300 text-blue-600 focus:ring-blue-400 cursor-pointer">
                        <span class="text-sm text-gray-700">Selected Department</span>
                    </label>
                </div>
            </div>
            <div class="mb-5" x-show="annAudience === 'department'" x-transition>
                <label class="block text-sm font-semibold text-gray-800 mb-2">Department</label>
                <div class="relative">
                    <select x-model="annDept"
                        class="w-full appearance-none px-4 py-3 border border-gray-200 rounded-xl text-sm text-gray-800 bg-white focus:outline-none focus:border-blue-400 focus:ring-2 focus:ring-blue-100 transition-all cursor-pointer">
                        <option value="" disabled selected>Select department</option>
                        @foreach($departments ?? [] as $dept)
                        <option value="{{ $dept->id }}">{{ $dept->name }}</option>
                        @endforeach
                    </select>
                    <div class="pointer-events-none absolute inset-y-0 right-3 flex items-center">
                        <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                    </div>
                </div>
            </div>
            <div class="flex items-center gap-3">
                <button @click="closeAnnouncement()"
                    class="flex-1 sm:flex-none px-6 py-2.5 text-sm font-semibold text-gray-700 bg-white border border-gray-200 rounded-xl hover:bg-gray-50 hover:border-gray-300 transition-all">
                    Cancel
                </button>
                <button @click="submitAnnouncement()"
                    :disabled="annSaving"
                    class="flex-1 sm:flex-none px-7 py-2.5 text-sm font-semibold text-white bg-blue-600 rounded-xl hover:bg-blue-700 transition-all hover:shadow-md disabled:opacity-60 disabled:cursor-not-allowed"
                    x-text="annSaving ? 'Submitting…' : 'Submit'">
                </button>
            </div>
        </div>
    </div>

    @include('partials.attendance-popup')

    {{-- ═══════════════════════════════════════ --}}
    {{-- ALERT MODAL                            --}}
    {{-- ═══════════════════════════════════════ --}}
    <div x-show="alertModal.show" x-cloak
         class="fixed inset-0 z-[1100] flex items-center justify-center p-4"
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         style="display:none;">
        <div class="absolute inset-0 bg-black/40" @click="alertOk()"></div>
        <div class="relative bg-white rounded-2xl shadow-2xl p-8 max-w-sm w-full text-center"
             x-transition:enter="transition ease-out duration-250"
             x-transition:enter-start="opacity-0 scale-95"
             x-transition:enter-end="opacity-100 scale-100">
            <div class="flex items-center justify-center w-14 h-14 rounded-full bg-blue-100 mx-auto mb-4">
                <svg class="w-7 h-7 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                </svg>
            </div>
            <h3 class="text-lg font-bold text-gray-900 mb-2" x-text="alertModal.title"></h3>
            <p class="text-sm text-gray-500 mb-6" x-text="alertModal.message"></p>
            <button @click="alertOk()"
                class="w-full py-2.5 text-sm font-semibold text-white bg-blue-600 rounded-xl hover:bg-blue-700 transition-all">
                OK
            </button>
        </div>
    </div>
</div>

<style>
@import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap');
* { font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif; }
[x-cloak] { display: none !important; }
::-webkit-scrollbar { width: 5px; }
::-webkit-scrollbar-track { background: #f8fafc; }
::-webkit-scrollbar-thumb { background: #e2e8f0; border-radius: 10px; }
::-webkit-scrollbar-thumb:hover { background: #cbd5e1; }
.header-title { animation: slideDown 0.5s cubic-bezier(0.22,1,0.36,1) both; }
@keyframes slideDown { from { opacity:0; transform:translateY(-10px); } to { opacity:1; transform:translateY(0); } }
.card-anim { opacity: 0; animation: cardUp 0.55s cubic-bezier(0.22,1,0.36,1) forwards; }
@keyframes cardUp { from { opacity:0; transform:translateY(24px) scale(0.97); } to { opacity:1; transform:translateY(0) scale(1); } }
.stat-card { transition: transform 0.25s cubic-bezier(0.34,1.56,0.64,1), box-shadow 0.25s ease; }
.stat-card:hover { transform: translateY(-4px) scale(1.02); box-shadow: 0 12px 32px rgba(59,130,246,0.12); }
.stat-bar { height:3px; background:#f1f5f9; border-radius:99px; overflow:hidden; }
.stat-bar-fill { height:100%; border-radius:99px; transform:scaleX(0); transform-origin:left; animation:growBar 1.3s cubic-bezier(0.22,1,0.36,1) 0.4s forwards; }
@keyframes growBar { to { transform:scaleX(1); } }
.progress-fill { width:0; animation:pfill 1.5s cubic-bezier(0.22,1,0.36,1) 0.7s forwards; }
@keyframes pfill { to { width: var(--tw); } }
.stat-box { transition: transform 0.2s cubic-bezier(0.34,1.56,0.64,1), box-shadow 0.2s ease; }
.stat-box:hover { transform: translateY(-3px) scale(1.06); box-shadow:0 6px 18px rgba(0,0,0,0.1); }

/* ── CLOCK BUTTONS ── */
.clock-btn {
    transition: all 0.25s cubic-bezier(0.34,1.56,0.64,1);
    position: relative;
    overflow: hidden;
    border: none;
    border-radius: 12px;
    /* CRITICAL: ensure buttons are always visible and tappable */
    display: flex;
    align-items: center;
    justify-content: center;
    min-height: 48px;
    z-index: 1;
}
.clock-btn:hover:not(:disabled) { transform:translateY(-2px); box-shadow:0 8px 24px rgba(59,130,246,0.35); }
.clock-btn:active:not(:disabled) { transform:translateY(0) scale(0.97); }
.clock-btn:disabled { opacity: 0.55; cursor: not-allowed !important; pointer-events: none; }

.action-btn { transition: background 0.2s ease, border-color 0.2s ease, transform 0.2s cubic-bezier(0.34,1.56,0.64,1), box-shadow 0.2s ease; }
.action-btn:hover { background:#eff6ff; border-color:#93c5fd; color:#3b82f6; transform:translateY(-3px); box-shadow:0 6px 18px rgba(59,130,246,0.15); }
.action-btn:active { transform:translateY(0) scale(0.97); }
.cal-nav-btn { transition: background 0.15s ease, transform 0.15s ease; }
.cal-nav-btn:hover { transform:scale(1.12); }
.cal-day { transition: background 0.15s ease, color 0.15s ease, transform 0.15s ease; }
.cal-day:hover:not(.today-pill) { transform:scale(1.18); background:#eff6ff; }
.cal-cell { position: relative; display: flex; flex-direction: column; align-items: center; }
.holiday-dot { width: 5px; height: 5px; border-radius: 50%; background: #f59e0b; margin-top: 2px; }
.holiday-tooltip { visibility: hidden; position: absolute; bottom: calc(100% + 4px); left: 50%; transform: translateX(-50%); background: #1e293b; color: #fff; font-size: 9px; padding: 3px 8px; border-radius: 5px; white-space: nowrap; z-index: 50; pointer-events: none; }
.holiday-tooltip::after { content: ''; position: absolute; top: 100%; left: 50%; transform: translateX(-50%); border: 4px solid transparent; border-top-color: #1e293b; }
.cal-cell:hover .holiday-tooltip { visibility: visible; }
.today-pill { animation: todayGlow 2.5s ease-in-out infinite; }
@keyframes todayGlow { 0%,100% { box-shadow:0 2px 8px rgba(59,130,246,0.4); } 50% { box-shadow:0 2px 18px rgba(59,130,246,0.7); } }

/* ── MOBILE RESPONSIVE ── */
@media (max-width: 767px) {
    /* Attendance boxes in mobile — vertical stack already handled by lg:hidden / lg:grid */
    .clock-btn {
        min-height: 44px !important;
        font-size: 0.65rem !important;
        padding: 0.5rem 0.25rem !important;
        border-radius: 10px !important;
    }
    /* Make time display readable */
    .tabular-nums { font-size: 1.8rem !important; }
    /* Stat cards */
    .text-3xl { font-size: 1.5rem !important; }
    /* Prevent overflow on mobile */
    body { overflow-x: hidden; }
    /* Action buttons */
    .action-btn { padding: 0.625rem 0.5rem !important; font-size: 0.75rem !important; }
    /* Header */
    .px-5 { padding-left: 0.75rem !important; padding-right: 0.75rem !important; }
    /* Calendar cells touch-friendly */
    .cal-day { min-height: 32px !important; padding: 0.4rem 0 !important; }
    /* Stat card hover off on mobile */
    .stat-card:hover { transform: none; box-shadow: none; }
}

@media (max-width: 480px) {
    .clock-btn { font-size: 0.6rem !important; min-height: 40px !important; }
    .tabular-nums { font-size: 1.5rem !important; }
}

/* Disable hover transforms on touch devices */
@media (hover: none) and (pointer: coarse) {
    .stat-card:hover { transform: none; box-shadow: none; }
    .clock-btn:hover:not(:disabled) { transform: none; box-shadow: none; }
    .action-btn:hover { transform: none; }
    .cal-day:hover:not(.today-pill) { transform: none; background: none; }
}
</style>

<script>
const _deptStats = @json($departmentAttendance);
function filterAttendanceSummary(deptId) {
    const s = _deptStats[deptId] ?? { present: 0, late: 0, absent: 0, on_leave: 0 };
    ['mobile','desktop'].forEach(v => {
        document.getElementById('stat-present-' + v).textContent = s.present;
        document.getElementById('stat-late-' + v).textContent    = s.late;
        document.getElementById('stat-absent-' + v).textContent  = s.absent;
        document.getElementById('stat-onleave-' + v).textContent = s.on_leave;
    });
    document.querySelectorAll('#overview-rows [data-dept-id]').forEach(row => {
        row.style.display = (deptId === 'all' || row.dataset.deptId === deptId) ? '' : 'none';
    });
}
</script>
@include('partials.attendance-error-modal')
@endsection
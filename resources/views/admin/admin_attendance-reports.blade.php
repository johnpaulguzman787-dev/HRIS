@php
    $currentRoute = request()->route()->getName();
    $isEmployeesSection = in_array($currentRoute, ['employees.directory', 'employees.profile']);
    $sidebarUser = auth()->user();
    $sidebarEmployee = $sidebarUser ? \App\Models\Employee::with('jobTitle')->where('user_id', $sidebarUser->id)->first() : null;
    $sidebarInitials = $sidebarEmployee
        ? strtoupper(substr($sidebarEmployee->fname, 0, 1) . substr($sidebarEmployee->lname, 0, 1))
        : ($sidebarUser ? strtoupper(substr($sidebarUser->email, 0, 2)) : 'U');
    $sidebarName = $sidebarEmployee
        ? trim($sidebarEmployee->fname . ' ' . $sidebarEmployee->lname)
        : ($sidebarUser?->email ?? 'User');
    $sidebarRole = $sidebarEmployee?->jobTitle?->title ?? ($sidebarUser?->role ?? '—');

    $attendanceRoutes = ['admin.attendance.reports'];
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>My Attendance – MEDISOURCE Admin</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <style>
        * { font-family: 'Inter', sans-serif; }
        .nav-item { transition: all 0.2s cubic-bezier(0.4,0,0.2,1); }
        .nav-item:hover { transform: translateX(2px); }
        .submenu-item { transition: all 0.18s ease; }
        .submenu-item:hover { transform: translateX(3px); }
        .chevron-icon { transition: transform 0.3s cubic-bezier(0.4,0,0.2,1); }
        .collapse-btn { transition: all 0.2s ease; }
        .collapse-btn:hover { transform: scale(1.08); }
        .settings-icon { transition: transform 0.5s ease; }
        .nav-item:hover .settings-icon { transform: rotate(60deg); }
        .avatar-ring { box-shadow: 0 0 0 3px rgba(59,130,246,0.2); transition: box-shadow 0.3s ease; }
        .avatar-ring:hover { box-shadow: 0 0 0 5px rgba(59,130,246,0.35); }
        @keyframes fadeUp { from{opacity:0;transform:translateY(14px)}to{opacity:1;transform:translateY(0)} }
        @keyframes fadeIn { from{opacity:0}to{opacity:1} }
        @keyframes rowSlide { from{opacity:0;transform:translateX(-8px)}to{opacity:1;transform:translateX(0)} }
        @keyframes pulseDot { 0%,100%{opacity:1}50%{opacity:0.3} }
        .anim-up   { animation: fadeUp 0.45s cubic-bezier(0.22,1,0.36,1) both; }
        .anim-fade { animation: fadeIn 0.35s ease both; }
        .stat-card { animation: fadeUp 0.45s cubic-bezier(0.22,1,0.36,1) both; transition: transform 0.2s ease, box-shadow 0.2s ease; border-radius: 18px; }
        .stat-card:hover { transform:translateY(-3px); box-shadow:0 12px 32px rgba(0,0,0,0.10); }
        .table-row { animation: rowSlide 0.3s cubic-bezier(0.22,1,0.36,1) both; transition: background 0.12s ease; }
        .table-row:hover { background:#f8faff; }
        .pulse-dot { animation: pulseDot 2s ease-in-out infinite; }
        .export-btn { transition: all 0.2s ease; background: linear-gradient(135deg,#3b82f6,#1d4ed8); }
        .export-btn:hover { transform:translateY(-1px); box-shadow:0 6px 18px rgba(59,130,246,0.40); }
        .clock-btn { transition: all 0.2s cubic-bezier(0.4,0,0.2,1); border-radius: 12px; }
        .clock-btn:hover:not(:disabled) { transform:translateY(-1px); box-shadow:0 6px 20px rgba(59,130,246,0.38); }
        .clock-btn:disabled { opacity:0.6; cursor:not-allowed; }
        .setup-btn { transition: all 0.2s ease; }
        ::-webkit-scrollbar{width:4px} ::-webkit-scrollbar-track{background:#f1f5f9} ::-webkit-scrollbar-thumb{background:#cbd5e1;border-radius:99px}
    </style>
</head>
<body class="bg-gray-100" x-data="attendancePage()" x-init="init()">

{{-- ═══════════ ADMIN SIDEBAR ═══════════ --}}
@include('admin.admin_sidebar')
{{-- ═══════════ MAIN CONTENT ═══════════ --}}
<div id="main-content" class="min-h-screen bg-gray-100 ml-64"
     style="transition:margin-left 0.35s cubic-bezier(0.4,0,0.2,1);">

    {{-- Blue Header --}}
    <div class="anim-fade flex items-center justify-between px-8 py-4"
         style="background:linear-gradient(135deg,#3b82f6,#1d4ed8);">
        <h1 class="text-white font-bold text-xl">My Attendance</h1>
        <button class="w-9 h-9 rounded-full flex items-center justify-center transition-all hover:bg-white/20">
            <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>
            </svg>
        </button>
    </div>

    <div class="p-6 space-y-5">

        {{-- TOP ROW --}}
        <div class="flex gap-5 items-stretch">

            {{-- CLOCK PANEL --}}
            <div class="anim-up bg-white rounded-2xl p-6 flex-shrink-0 flex flex-col"
                 style="width:320px; animation-delay:0.05s; box-shadow:0 1px 12px rgba(0,0,0,0.07);">
                <p class="text-xs font-bold text-gray-500 uppercase tracking-widest mb-3">Time & Attendance</p>
                <div class="mb-1">
                    <p class="font-black text-gray-900 tabular-nums leading-none" style="font-size:3rem; letter-spacing:-1px;" x-text="liveTime"></p>
                    <p class="text-sm text-gray-400 font-medium mt-1.5" x-text="liveDate"></p>
                </div>
                <hr class="my-4 border-gray-100">
                <template x-if="!clockedIn">
    <div class="mb-3">
        <p class="text-xs font-semibold text-gray-500 mb-2">Shift Schedule</p>
        <div class="relative">
            <select x-model="selectedShiftId"
                class="w-full px-4 py-2.5 text-sm border border-gray-200 rounded-xl bg-gray-50 font-semibold text-gray-700 appearance-none focus:outline-none focus:ring-2 focus:ring-blue-300">
                @forelse($availableShifts as $shift)
                    <option value="{{ $shift->id }}">
                        {{ $shift->name }} ({{ \Carbon\Carbon::parse($shift->start_time)->format('g:i A') }} – {{ \Carbon\Carbon::parse($shift->end_time)->format('g:i A') }})
                    </option>
                @empty
                    <option value="">No shifts available</option>
                @endforelse
            </select>
            <div class="pointer-events-none absolute inset-y-0 right-3 flex items-center text-gray-400">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
            </div>
        </div>
    </div>
</template>
                <div class="mb-3">
                    <p class="text-xs font-semibold text-gray-500 mb-2">Work Setup</p>
                    <div class="flex rounded-xl overflow-hidden border border-gray-200">
                        <button @click="workSetup='office'"
                                :class="workSetup==='office' ? 'bg-gray-800 text-white font-bold' : 'bg-white text-gray-500 font-medium hover:bg-gray-50'"
                                class="setup-btn flex-1 py-2.5 text-sm">Office</button>
                        <button @click="workSetup='wfh'"
                                :class="workSetup==='wfh' ? 'text-white font-bold' : 'bg-white text-gray-500 font-medium hover:bg-gray-50'"
                                :style="workSetup==='wfh' ? 'background:#3b82f6' : ''"
                                class="setup-btn flex-1 py-2.5 text-sm">WFH</button>
                    </div>
                </div>
                <div class="mb-3">
                    <p class="text-xs font-semibold text-gray-500 mb-2">Today's Attendance</p>
                    <div class="flex gap-2 mb-2">
                        <div class="flex-1 border border-gray-200 rounded-xl px-3 py-3 bg-gray-50">
                            <p class="text-xs text-gray-400 font-semibold tracking-wider mb-1.5">CLOCK IN</p>
                            <p class="text-sm font-bold text-gray-700 border-b border-gray-300 pb-0.5" x-text="clockedIn ? clockInTime : '–'"></p>
                        </div>
                        <div class="flex-1 border border-gray-200 rounded-xl px-3 py-3 bg-gray-50">
                            <p class="text-xs text-gray-400 font-semibold tracking-wider mb-1.5">CLOCK OUT</p>
                            <p class="text-sm font-bold text-gray-700 border-b border-gray-300 pb-0.5" x-text="clockedOut ? clockOutTime : '–'"></p>
                        </div>
                    </div>
                    <p class="text-xs text-center text-gray-400 font-medium" x-show="!clockedIn"><span class="mr-1">⏱</span><span x-text="elapsedDisplay"></span></p>
                    <p class="text-xs text-center font-medium" x-show="clockedIn && !clockedOut" style="color:#3b82f6;"><span class="mr-1">⏱</span><span x-text="elapsedDisplay"></span></p>
                    <p class="text-xs text-center font-semibold" x-show="clockedOut" style="color:#22c55e;">✓ Attendance recorded · <span x-text="elapsedDisplay"></span></p>
                </div>
                <button @click="handleClock()" :disabled="clockedOut"
                        class="clock-btn mt-auto w-full py-3.5 text-white font-bold text-sm tracking-widest uppercase"
                        :style="clockedOut ? 'background:#94a3b8;' : clockedIn ? 'background:linear-gradient(135deg,#ef4444,#dc2626)' : 'background:linear-gradient(135deg,#3b82f6,#1d4ed8)'"
                        x-text="clockedOut ? 'COMPLETED' : clockedIn ? 'CLOCK OUT' : 'CLOCK IN'">
                </button>
            </div>

            {{-- 2×3 STAT CARDS --}}
            <div class="flex-1 grid grid-cols-2 grid-rows-3 gap-4">
                <div class="stat-card p-5 flex flex-col justify-between" style="background:#c8f0d8; animation-delay:0.08s;">
                    <p class="text-xs font-bold uppercase tracking-wider" style="color:#14532d;">Total Days Present</p>
                    <div><p class="font-black leading-none mt-2" style="font-size:3.2rem; color:#2563eb;">{{ $stats['present'] }}</p><p class="text-xs font-semibold mt-2 uppercase tracking-wider" style="color:#15803d;">{{ \Carbon\Carbon::create($year, $month)->format('F Y') }}</p></div>
                </div>
                <div class="stat-card p-5 flex flex-col justify-between" style="background:#fde8c8; animation-delay:0.11s;">
                    <p class="text-xs font-bold uppercase tracking-wider" style="color:#92400e;">Late</p>
                    <div><p class="font-black leading-none mt-2" style="font-size:3.2rem; color:#1f2937;">{{ $stats['late'] }}</p><p class="text-xs font-semibold mt-2 uppercase tracking-wider" style="color:#b45309;">{{ \Carbon\Carbon::create($year, $month)->format('F Y') }}</p></div>
                </div>
                <div class="stat-card p-5 flex flex-col justify-between" style="background:#fbc8c8; animation-delay:0.14s;">
                    <p class="text-xs font-bold uppercase tracking-wider" style="color:#991b1b;">Absent</p>
                    <div><p class="font-black leading-none mt-2" style="font-size:3.2rem; color:#1f2937;">{{ $stats['absent'] }}</p><p class="text-xs font-semibold mt-2 uppercase tracking-wider" style="color:#dc2626;">{{ \Carbon\Carbon::create($year, $month)->format('F Y') }}</p></div>
                </div>
                <div class="stat-card p-5 flex flex-col justify-between" style="background:#f9c8e8; animation-delay:0.17s;">
                    <p class="text-xs font-bold uppercase tracking-wider" style="color:#831843;">Leave</p>
                    <div><p class="font-black leading-none mt-2" style="font-size:3.2rem; color:#1f2937;">{{ $stats['on_leave'] }}</p><p class="text-xs font-semibold mt-2 uppercase tracking-wider" style="color:#be185d;">{{ \Carbon\Carbon::create($year, $month)->format('F Y') }}</p></div>
                </div>
                <div class="stat-card p-5 flex flex-col justify-between" style="background:#c8dafa; animation-delay:0.20s;">
                    <p class="text-xs font-bold uppercase tracking-wider" style="color:#1e3a8a;">Overtime</p>
                    <div><p class="font-black leading-none mt-2" style="font-size:3.2rem; color:#1f2937;">{{ $stats['overtime'] }}</p><p class="text-xs font-semibold mt-2 uppercase tracking-wider" style="color:#1d4ed8;">{{ \Carbon\Carbon::create($year, $month)->format('F Y') }}</p></div>
                </div>
                <div class="stat-card p-5 flex flex-col justify-between" style="background:#c8dafa; animation-delay:0.23s;">
                    <p class="text-xs font-bold uppercase tracking-wider" style="color:#1e3a8a;">Undertime</p>
                    <div><p class="font-black leading-none mt-2" style="font-size:3.2rem; color:#1f2937;">{{ $stats['undertime'] }}</p><p class="text-xs font-semibold mt-2 uppercase tracking-wider" style="color:#1d4ed8;">{{ \Carbon\Carbon::create($year, $month)->format('F Y') }}</p></div>
                </div>
            </div>
        </div>

        {{-- TABLE --}}
        <div class="anim-up bg-white rounded-2xl overflow-hidden" style="animation-delay:0.26s; box-shadow:0 1px 12px rgba(0,0,0,0.07);">
            <div class="px-6 py-4 flex items-center justify-between">
                <h3 class="font-bold text-gray-800 text-base">Attendance Records</h3>
                <div class="flex items-center gap-3">
                    <div class="relative">
    <select x-model="selectedMonth" @change="changeMonth()"
        class="appearance-none flex items-center gap-2 border border-gray-200 rounded-xl pl-8 pr-8 py-2 bg-gray-50 text-xs text-gray-600 font-medium cursor-pointer hover:border-blue-300 transition-colors focus:outline-none focus:ring-2 focus:ring-blue-300">
        <option value="1">January</option>
        <option value="2">February</option>
        <option value="3">March</option>
        <option value="4">April</option>
        <option value="5">May</option>
        <option value="6">June</option>
        <option value="7">July</option>
        <option value="8">August</option>
        <option value="9">September</option>
        <option value="10">October</option>
        <option value="11">November</option>
        <option value="12">December</option>
    </select>
    <div class="pointer-events-none absolute inset-y-0 left-2.5 flex items-center text-gray-400">
        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
    </div>
    <div class="pointer-events-none absolute inset-y-0 right-2.5 flex items-center text-gray-400">
        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
    </div>
</div>
                    <button class="export-btn flex items-center gap-2 px-4 py-2 rounded-xl text-xs font-bold text-white">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                        Export
                    </button>
                </div>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead>
                        <tr class="border-t border-b border-gray-100 bg-gray-50/60">
                            <th class="text-left px-6 py-3 text-xs font-bold text-gray-400 uppercase tracking-wider">Date</th>
                            <th class="text-left px-6 py-3 text-xs font-bold text-gray-400 uppercase tracking-wider">Work Setup</th>
                            <th class="text-left px-6 py-3 text-xs font-bold text-gray-400 uppercase tracking-wider">Shift</th>
                            <th class="text-left px-6 py-3 text-xs font-bold text-gray-400 uppercase tracking-wider">Schedule</th>
                            <th class="text-left px-6 py-3 text-xs font-bold text-gray-400 uppercase tracking-wider">Clock In</th>
                            <th class="text-left px-6 py-3 text-xs font-bold text-gray-400 uppercase tracking-wider">Clock Out</th>
                            <th class="text-left px-6 py-3 text-xs font-bold text-gray-400 uppercase tracking-wider">Overtime</th>
                            <th class="text-left px-6 py-3 text-xs font-bold text-gray-400 uppercase tracking-wider">Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-50">
                        <template x-for="(row, i) in rows" :key="row.date">
                            <tr class="table-row" :style="`animation-delay:${0.035*i}s`">
                                <td class="px-6 py-4 text-sm font-medium text-gray-700" x-text="row.date"></td>
                                <td class="px-6 py-4"><span class="px-3 py-1 rounded-lg text-xs font-bold" :style="row.setup==='WFH'?'background:#dbeafe;color:#1d4ed8;':'background:#f1f5f9;color:#475569;'" x-text="row.setup"></span></td>
                                <td class="px-6 py-4 text-sm text-gray-600" x-text="row.shift"></td>
                                <td class="px-6 py-4 text-sm text-gray-500" x-text="row.schedule"></td>
                                <td class="px-6 py-4 text-sm font-semibold text-gray-800" x-text="row.clockIn"></td>
                                <td class="px-6 py-4 text-sm text-gray-500" x-text="row.clockOut"></td>
                                <td class="px-6 py-4 text-sm text-gray-600" x-text="row.overtime"></td>
                                <td class="px-6 py-4">
                                    <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold" :style="statusStyle(row.status)">
                                        <span class="w-1.5 h-1.5 rounded-full" :style="dotStyle(row.status)"></span>
                                        <span x-text="row.status"></span>
                                    </span>
                                </td>
                            </tr>
                        </template>
                    </tbody>
                </table>
            </div>
            <div class="px-6 py-4 border-t border-gray-100 flex items-center justify-between">
                <p class="text-xs text-gray-400">Showing <span class="font-semibold text-gray-600">8</span> of <span class="font-semibold text-gray-600">26</span> records</p>
                <div class="flex items-center gap-1">
                    <button class="w-8 h-8 rounded-lg border border-gray-200 text-gray-400 text-xs flex items-center justify-center hover:bg-gray-50">&lsaquo;</button>
                    <button class="w-8 h-8 rounded-lg text-xs font-bold text-white flex items-center justify-center" style="background:#3b82f6;">1</button>
                    <button class="w-8 h-8 rounded-lg border border-gray-200 text-gray-500 text-xs flex items-center justify-center hover:bg-gray-50">2</button>
                    <button class="w-8 h-8 rounded-lg border border-gray-200 text-gray-500 text-xs flex items-center justify-center hover:bg-gray-50">3</button>
                    <button class="w-8 h-8 rounded-lg border border-gray-200 text-gray-400 text-xs flex items-center justify-center hover:bg-gray-50">&rsaquo;</button>
                </div>
            </div>
        </div>

    </div>
</div>

<script>
function attendancePage() {
    return {
        workSetup: '{{ $todayLog?->work_setup ?? "wfh" }}',
selectedShiftId: {{ $todayLog?->shift_id ?? ($availableShifts->first()?->id ?? 'null') }},
shifts: @json($availableShifts),
        clockedIn:  {{ $todayLog?->clock_in  ? 'true' : 'false' }},
        clockedOut: {{ $todayLog?->clock_out ? 'true' : 'false' }},
        clockInTime:  '{{ $todayLog?->clock_in  ? \Carbon\Carbon::parse($todayLog->clock_in)->setTimezone(config("app.timezone"))->format("h:i A")  : "" }}',
clockOutTime: '{{ $todayLog?->clock_out ? \Carbon\Carbon::parse($todayLog->clock_out)->setTimezone(config("app.timezone"))->format("h:i A") : "" }}',
        clockInTimestamp: {{ $todayLog?->clock_in ? \Carbon\Carbon::parse($todayLog->clock_in)->valueOf() : 'null' }},
        liveTime: '', liveDate: '', elapsedSeconds: 0,
        rows: [], currentPage: 1, totalRecords: 0, perPage: 10,
        currentMonth: {{ $month }}, currentYear: {{ $year }},
        selectedMonth: {{ $month }},

        get elapsedDisplay() {
            const h = String(Math.floor(this.elapsedSeconds/3600)).padStart(2,'0');
            const m = String(Math.floor((this.elapsedSeconds%3600)/60)).padStart(2,'0');
            const s = String(this.elapsedSeconds%60).padStart(2,'0');
            return `${h}h ${m}m ${s}s`;
        },

        async init() {
            this.tick();
            setInterval(() => {
                this.tick();
                if (this.clockedIn && !this.clockedOut)
                    this.elapsedSeconds = this.clockInTimestamp
                        ? Math.floor((Date.now() - this.clockInTimestamp) / 1000)
                        : 0;
            }, 1000);
            await this.loadRecords();
        },

       tick() {
            const n = new Date();
            this.liveTime = String(n.getHours()).padStart(2,'0')+':'+String(n.getMinutes()).padStart(2,'0')+':'+String(n.getSeconds()).padStart(2,'0');
            this.liveDate = n.toLocaleDateString('en-US',{weekday:'long',year:'numeric',month:'long',day:'numeric'});
        },

        async changeMonth() {
            this.currentMonth = this.selectedMonth;
            await this.loadRecords(1);
        },

        async handleClock() {
            if (this.clockedOut) return;
            const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

            if (!this.clockedIn) {
                const res = await fetch('{{ route("admin.attendance.clock-in") }}', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken },
                    body: JSON.stringify({ work_setup: this.workSetup, shift_id: this.selectedShiftId })
                });
                const data = await res.json();
                if (res.ok) {
                    this.clockedIn = true;
                    this.clockInTime = data.clock_in;
                    this.clockInTimestamp = Date.now();
                } else {
                    alert(data.message ?? 'Clock-in failed.');
                }
            } else {
                const res = await fetch('{{ route("admin.attendance.clock-out") }}', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken },
                    body: JSON.stringify({})
                });
                const data = await res.json();
                if (res.ok) {
                    this.clockedOut = true;
                    this.clockOutTime = data.clock_out;
                    await this.loadRecords();
                } else {
                    alert(data.message ?? 'Clock-out failed.');
                }
            }
        },

        async loadRecords(page = 1) {
    try {
        const res = await fetch(`{{ route("admin.attendance.records") }}?month=${this.currentMonth}&year=${this.currentYear}&page=${page}`);
        const data = await res.json();
        if (!data.data) { this.rows = []; this.totalRecords = 0; return; }
        this.rows = data.data.map(log => ({
            date:     new Date(log.attendance_date).toLocaleDateString('en-US',{year:'numeric',month:'long',day:'numeric'}),
            setup:    log.work_setup ? log.work_setup.toUpperCase() : '—',
            shift:    log.shift?.name ?? '—',
            schedule: log.shift ? this.formatTime(log.shift.start_time) + ' – ' + this.formatTime(log.shift.end_time) : '—',
            clockIn:  log.clock_in  ? new Date(log.clock_in).toLocaleTimeString('en-US',{hour:'2-digit',minute:'2-digit',hour12:true})  : '—',
            clockOut: log.clock_out ? new Date(log.clock_out).toLocaleTimeString('en-US',{hour:'2-digit',minute:'2-digit',hour12:true}) : '—',
            overtime: log.overtime_minutes > 0 ? Math.floor(log.overtime_minutes/60)+'h '+String(log.overtime_minutes%60).padStart(2,'0')+'m' : '00h 00m',
            status:   log.status ? log.status.replace('_',' ').replace(/\b\w/g,c=>c.toUpperCase()) : '—',
        }));
        this.totalRecords = data.total;
        this.perPage      = data.per_page;
        this.currentPage  = data.current_page;
    } catch(e) { console.error('loadRecords error:', e); }
},

        formatTime(t) {
            if (!t) return '—';
            const [h, m] = t.split(':');
            const d = new Date(); d.setHours(h, m);
            return d.toLocaleTimeString('en-US',{hour:'numeric',minute:'2-digit',hour12:true});
        },

        statusStyle(s){return{'Present':'background:rgba(34,197,94,0.12);color:#16a34a;','Late':'background:rgba(245,158,11,0.12);color:#d97706;','Absent':'background:rgba(239,68,68,0.12);color:#dc2626;','On Leave':'background:rgba(99,102,241,0.12);color:#4f46e5;','Undertime':'background:rgba(251,191,36,0.12);color:#b45309;','Overtime':'background:rgba(59,130,246,0.12);color:#1d4ed8;'}[s]||'background:#f1f5f9;color:#64748b;';},
        dotStyle(s){return{'Present':'background:#22c55e;','Late':'background:#f59e0b;','Absent':'background:#ef4444;','On Leave':'background:#6366f1;','Undertime':'background:#fbbf24;','Overtime':'background:#3b82f6;'}[s]||'background:#94a3b8;';}
    }
}
</script>
</body>
</html>

        
   
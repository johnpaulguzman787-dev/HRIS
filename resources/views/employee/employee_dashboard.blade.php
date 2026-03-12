@extends('layouts.app')

@section('title', 'Dashboard')

@section('content')
<div x-data="{
        sidebarCollapsed: localStorage.getItem('sidebarCollapsed') === 'true',
        workSetup: '{{ $todayLog?->work_setup ?? "wfh" }}',
        clockedIn: {{ $todayLog?->clock_in ? 'true' : 'false' }},
        clockedOut: {{ $todayLog?->clock_out ? 'true' : 'false' }},
       clockInTime:  '{{ $todayLog?->clock_in  ? \Carbon\Carbon::parse($todayLog->clock_in)->setTimezone(config("app.timezone"))->format("h:i A")  : "" }}',
clockOutTime: '{{ $todayLog?->clock_out ? \Carbon\Carbon::parse($todayLog->clock_out)->setTimezone(config("app.timezone"))->format("h:i A") : "" }}',
        clockInTimestamp: {{ $todayLog?->clock_in ? \Carbon\Carbon::parse($todayLog->clock_in)->valueOf() : 'null' }},
        selectedShiftId: {{ $todayLog?->shift_id ?? ($availableShifts->first()?->id ?? 'null') }},
        elapsedSeconds: 0,
        currentTime: '',
        currentDate: '',
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
                if (this.clockedIn && !this.clockedOut && this.clockInTimestamp) {
                    this.elapsedSeconds = Math.floor((Date.now() - this.clockInTimestamp) / 1000);
                }
            }, 1000);
            window.addEventListener('storage', () => {
                this.sidebarCollapsed = localStorage.getItem('sidebarCollapsed') === 'true';
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
            if (this.clockedOut) return;
            const csrf = document.querySelector('meta[name=csrf-token]').getAttribute('content');
            if (!this.clockedIn) {
                const res = await fetch('{{ route("employee.attendance.clock-in") }}', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf },
                    body: JSON.stringify({ work_setup: this.workSetup, shift_id: this.selectedShiftId })
                });
                const data = await res.json();
                if (res.ok) {
                    this.clockedIn = true;
                    this.clockInTime = data.clock_in;
                    this.clockInTimestamp = Date.now();
                } else { alert(data.message ?? 'Clock-in failed.'); }
            } else {
                const res = await fetch('{{ route("employee.attendance.clock-out") }}', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf },
                    body: JSON.stringify({})
                });
                const data = await res.json();
                if (res.ok) {
                    this.clockedOut = true;
                    this.clockOutTime = data.clock_out;
                } else { alert(data.message ?? 'Clock-out failed.'); }
            }
        }
    }"
    x-init="initClock()"
    class="flex h-screen overflow-hidden" style="background:#eef2f7;">

    <!-- ===================== SIDEBAR ===================== -->
    @include('employee.employee_sidebar')

    <!-- ===================== MAIN CONTENT ===================== -->
<main class="flex-1 overflow-y-auto min-h-screen"
    :class="sidebarCollapsed ? 'ml-20' : 'ml-72'"
    style="transition: margin-left 0.35s cubic-bezier(0.4, 0, 0.2, 1);">

        <!-- Header -->
        <header class="bg-gradient-to-r from-blue-600 to-blue-700 text-white sticky top-0 z-10 shadow-lg mt-4 mx-4 rounded-2xl">
            <div class="px-8 py-5 flex items-center justify-between">
                <h1 class="text-2xl font-bold text-white header-title">Dashboard</h1>
                <div class="flex items-center space-x-3">
                    <button class="bell-btn p-2 rounded-lg relative">
                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>
                        </svg>
                        <span class="absolute top-1.5 right-1.5 w-2 h-2 bg-white rounded-full bell-dot"></span>
                    </button>
                    <div class="w-10 h-10 bg-white bg-opacity-20 rounded-xl flex items-center justify-center text-white font-bold text-sm cursor-pointer hover:scale-110 transition-all duration-300">
                        {{ $dashInitials }}
                    </div>
                </div>
            </div>
        </header>

        <!-- Body -->
        <div class="p-6">

            <!-- ROW 1: 3 Stat Cards -->
            <div class="grid grid-cols-3 gap-5 mb-5">
                <!-- Total Days Present -->
                <div class="stat-card bg-white rounded-xl p-6 card-anim" style="animation-delay:0.05s; border:1px solid #e5e7eb;">
                    <p class="text-xs font-semibold text-gray-400 uppercase tracking-widest mb-3">Total Days Present</p>
                    <p class="text-5xl font-bold" style="color:#3b82f6;">{{ $stats['present'] }}</p>
                    <p class="text-xs text-gray-400 mt-3 uppercase tracking-wider font-medium">{{ strtoupper(date('F Y')) }}</p>
                    <div class="stat-bar mt-2"><div class="stat-bar-fill" style="width:86.6%; background:#3b82f6;"></div></div>
                </div>
                <!-- Total Days Late -->
                <div class="stat-card bg-white rounded-xl p-6 card-anim" style="animation-delay:0.15s; border:1px solid #e5e7eb;">
                    <p class="text-xs font-semibold text-gray-400 uppercase tracking-widest mb-3">Total Days Late</p>
                    <p class="text-5xl font-bold text-gray-900">{{ $stats['late'] }}</p>
                    <p class="text-xs text-gray-400 mt-3 uppercase tracking-wider font-medium">{{ strtoupper(date('F Y')) }}</p>
                    <div class="stat-bar mt-2"><div class="stat-bar-fill" style="width:40%; background:#f59e0b;"></div></div>
                </div>
                <!-- Leave Balance -->
                <div class="stat-card bg-white rounded-xl p-6 card-anim" style="animation-delay:0.25s; border:1px solid #e5e7eb;">
                    <p class="text-xs font-semibold text-gray-400 uppercase tracking-widest mb-3">Leave Balance</p>
                    <p class="text-5xl font-bold text-gray-900">3</p>
                    <a href="#" class="text-xs mt-3 block uppercase tracking-wider font-semibold" style="color:#3b82f6;">View All</a>
                    <div class="stat-bar mt-2"><div class="stat-bar-fill" style="width:30%; background:#22c55e;"></div></div>
                </div>
            </div>

            <!-- ROW 2: Three columns -->
            <div class="grid grid-cols-3 gap-5">

                <!-- COL 1: My Attendance Summary + Quick Actions -->
                <div class="space-y-5">

                    <!-- My Attendance Summary -->
                    <div class="bg-white rounded-xl p-6 card-anim" style="animation-delay:0.3s; border:1px solid #e5e7eb;">
                        <h2 class="text-xs font-bold text-gray-700 uppercase tracking-widest">My Attendance Summary</h2>
                        <p class="text-xs text-gray-400 mt-1 mb-3">{{ date('F d, Y') }}</p>

                        <!-- Stat Boxes -->
                        <div class="grid grid-cols-4 gap-2 mb-5">
                            <div class="stat-box rounded-xl p-2 text-center" style="background:#dcfce7;">
                                <p class="text-base font-bold" style="color:#16a34a;">{{ $stats['present'] }}</p>
                                <p class="text-xs font-semibold uppercase" style="color:#16a34a;">Present</p>
                            </div>
                            <div class="stat-box rounded-xl p-2 text-center" style="background:#fef9c3;">
                                <p class="text-base font-bold" style="color:#ca8a04;">{{ $stats['late'] }}</p>
                                <p class="text-xs font-semibold uppercase" style="color:#ca8a04;">Late</p>
                            </div>
                            <div class="stat-box rounded-xl p-2 text-center" style="background:#fee2e2;">
                                <p class="text-base font-bold" style="color:#dc2626;">{{ $stats['absent'] }}</p>
                                <p class="text-xs font-semibold uppercase" style="color:#dc2626;">Absent</p>
                            </div>
                            <div class="stat-box rounded-xl p-2 text-center" style="background:#fce7f3;">
                                <p class="text-base font-bold" style="color:#db2777;">{{ $stats['on_leave'] }}</p>
                                <p class="text-xs font-semibold uppercase" style="color:#db2777;">On Leave</p>
                            </div>
                        </div>

                        <!-- Overview Bar -->
                        <p class="text-xs font-semibold text-gray-600 uppercase tracking-wider mb-3">Overview</p>
                        <div class="mb-3">
                            @php
                                $total = $stats['present'] + $stats['late'] + $stats['absent'] + $stats['on_leave'];
                                $pPresent  = $total > 0 ? round($stats['present']  / $total * 100) : 0;
                                $pLate     = $total > 0 ? round($stats['late']     / $total * 100) : 0;
                                $pAbsent   = $total > 0 ? round($stats['absent']   / $total * 100) : 0;
                                $pOnLeave  = $total > 0 ? round($stats['on_leave'] / $total * 100) : 0;
                            @endphp
                            <div class="flex h-3 rounded-full overflow-hidden w-full" style="background:#f1f5f9;">
                                <div class="bg-green-400 overview-segment" style="--seg-w: {{ $pPresent }}%;"></div>
                                <div class="bg-yellow-400 overview-segment" style="--seg-w: {{ $pLate }}%;"></div>
                                <div class="bg-red-400 overview-segment" style="--seg-w: {{ $pAbsent }}%;"></div>
                                <div class="bg-pink-400 overview-segment" style="--seg-w: {{ $pOnLeave }}%;"></div>
                            </div>
                            <div class="flex items-center space-x-3 mt-2">
                                <span class="flex items-center text-xs text-gray-500"><span class="w-2 h-2 rounded-full bg-green-400 inline-block mr-1"></span>Present</span>
                                <span class="flex items-center text-xs text-gray-500"><span class="w-2 h-2 rounded-full bg-yellow-400 inline-block mr-1"></span>Late</span>
                                <span class="flex items-center text-xs text-gray-500"><span class="w-2 h-2 rounded-full bg-red-400 inline-block mr-1"></span>Absent</span>
                                <span class="flex items-center text-xs text-gray-500"><span class="w-2 h-2 rounded-full bg-pink-400 inline-block mr-1"></span>On Leave</span>
                            </div>
                        </div>
                    </div>

                    <!-- Quick Actions -->
                    <div class="bg-white rounded-xl p-6 card-anim" style="animation-delay:0.42s; border:1px solid #e5e7eb;">
                        <h2 class="text-xs font-bold text-gray-700 uppercase tracking-widest mb-4">Quick Actions</h2>
                        <div class="grid grid-cols-2 gap-3">
                            <button class="action-btn px-4 py-3 border border-gray-200 rounded-lg text-sm text-gray-600 font-medium">OT Request</button>
                            <button class="action-btn px-4 py-3 border border-gray-200 rounded-lg text-sm text-gray-600 font-medium">View Attendance Reports</button>
                        </div>
                    </div>
                </div>

                <!-- COL 2: Time & Attendance -->
                <div class="bg-white rounded-xl p-6 card-anim flex flex-col" style="animation-delay:0.35s; border:1px solid #e5e7eb;">
                    <p class="text-xs font-bold text-gray-500 uppercase tracking-widest mb-3">Time & Attendance</p>
                    <div class="mb-1">
                        <p class="font-black text-gray-900 tabular-nums leading-none" style="font-size:3rem; letter-spacing:-1px;" x-text="currentTime"></p>
                        <p class="text-sm text-gray-400 font-medium mt-1.5" x-text="currentDate"></p>
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
                                class="flex-1 py-2.5 text-sm">Office</button>
                            <button @click="workSetup='wfh'"
                                :class="workSetup==='wfh' ? 'text-white font-bold' : 'bg-white text-gray-500 font-medium hover:bg-gray-50'"
                                :style="workSetup==='wfh' ? 'background:#3b82f6' : ''"
                                class="flex-1 py-2.5 text-sm">WFH</button>
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

                <!-- COL 3: Calendar + Upcoming Events -->
                <div class="space-y-5">
                    <div class="bg-white rounded-xl p-6 card-anim" style="animation-delay:0.4s; border:1px solid #e5e7eb;">
                        <div class="flex items-center justify-between mb-4">
                            <button class="cal-nav-btn p-1.5 hover:bg-gray-100 rounded-lg">
                                <svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                                </svg>
                            </button>
                            <p class="text-xs font-bold uppercase tracking-widest" style="color:#3b82f6;">{{ strtoupper(date('F Y')) }}</p>
                            <button class="cal-nav-btn p-1.5 hover:bg-gray-100 rounded-lg">
                                <svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                                </svg>
                            </button>
                        </div>
                        <div class="grid grid-cols-7 mb-1">
                            @foreach(['Su','Mo','Tu','We','Th','Fr','Sa'] as $d)
                            <div class="text-center text-xs text-gray-400 font-semibold py-1">{{ $d }}</div>
                            @endforeach
                        </div>
                        @php
                            $today    = (int)date('j');
                            $total    = (int)date('t');
                            $firstDay = (int)date('w', strtotime(date('Y-m-01')));
                        @endphp
                        <div class="grid grid-cols-7 gap-0.5">
                            @for($i = 0; $i < $firstDay; $i++)<div></div>@endfor
                            @for($i = 1; $i <= $total; $i++)
                            <button class="cal-day text-center text-xs py-2 rounded-full {{ $i == $today ? 'today-pill text-white font-bold' : 'text-gray-600 hover:bg-gray-100' }}"
                                style="{{ $i == $today ? 'background:#3b82f6;' : '' }}">{{ $i }}</button>
                            @endfor
                        </div>
                    </div>

                    <div class="bg-white rounded-xl p-6 card-anim" style="animation-delay:0.5s; border:1px solid #e5e7eb;">
                        <h2 class="text-xs font-bold text-gray-700 uppercase tracking-widest mb-4">Upcoming Events</h2>
                        <div class="space-y-3">
                            <div class="shimmer h-10 rounded-lg"></div>
                            <div class="shimmer h-10 rounded-lg" style="animation-delay:0.15s"></div>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </main>
</div>

<style>
@import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap');
* { font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif; }
[x-cloak] { display: none !important; }

::-webkit-scrollbar { width: 5px; }
::-webkit-scrollbar-track { background: #f8fafc; }
::-webkit-scrollbar-thumb { background: #e2e8f0; border-radius: 10px; }
::-webkit-scrollbar-thumb:hover { background: #cbd5e1; }

.chevron-icon { transition: transform 0.3s cubic-bezier(0.4,0,0.2,1); }
.nav-item { transition: background 0.18s ease, color 0.18s ease, transform 0.15s ease; }
.nav-item:hover { transform: translateX(2px); }
.nav-item:active { transform: scale(0.98); }
.submenu-item { transition: background 0.15s ease, color 0.15s ease, padding-left 0.2s ease; }
.submenu-item:hover { padding-left: 18px; }
a:hover .settings-icon { animation: spinOnce 0.45s ease forwards; }
@keyframes spinOnce { to { transform: rotate(90deg); } }
.logout-btn { transition: background 0.2s ease, color 0.2s ease, transform 0.18s ease; }
.logout-btn:hover { transform: translateX(3px); }
.collapse-btn { transition: background 0.18s ease, transform 0.2s ease; }
.collapse-btn:hover { transform: scale(1.12); }
.collapse-btn:active { transform: scale(0.93); }
.avatar-ring { transition: box-shadow 0.2s ease; }
.avatar-ring:hover { box-shadow: 0 0 0 3px #dbeafe; }
.profile-card { transition: background 0.2s ease; }
.profile-card:hover { background: #f9fafb; }
.header-title { animation: slideDown 0.5s cubic-bezier(0.22,1,0.36,1) both; }
@keyframes slideDown { from { opacity:0; transform:translateY(-10px); } to { opacity:1; transform:translateY(0); } }
.bell-btn { transition: background 0.18s ease, transform 0.18s ease; }
.bell-btn:hover { transform: scale(1.08); }
.bell-btn:hover svg { animation: shake 0.4s ease; }
@keyframes shake { 0%,100% { transform:rotate(0); } 25% { transform:rotate(-18deg); } 75% { transform:rotate(18deg); } }
.bell-dot { animation: blink 2.2s ease-in-out infinite; }
@keyframes blink { 0%,100% { opacity:1; transform:scale(1); } 50% { opacity:0.5; transform:scale(1.4); } }
.card-anim { opacity: 0; animation: cardUp 0.55s cubic-bezier(0.22,1,0.36,1) forwards; }
@keyframes cardUp { from { opacity:0; transform:translateY(24px) scale(0.97); } to { opacity:1; transform:translateY(0) scale(1); } }
.stat-card { transition: transform 0.25s cubic-bezier(0.34,1.56,0.64,1), box-shadow 0.25s ease; }
.stat-card:hover { transform: translateY(-6px) scale(1.02); box-shadow: 0 20px 45px rgba(59,130,246,0.13); }
.stat-bar { height:3px; background:#f1f5f9; border-radius:99px; overflow:hidden; }
.stat-bar-fill { height:100%; border-radius:99px; transform:scaleX(0); transform-origin:left; animation:growBar 1.3s cubic-bezier(0.22,1,0.36,1) 0.4s forwards; }
@keyframes growBar { to { transform:scaleX(1); } }
.overview-segment { width: 0; animation: segGrow 1.4s cubic-bezier(0.22,1,0.36,1) 0.6s forwards; }
@keyframes segGrow { to { width: var(--seg-w); } }
.stat-box { transition: transform 0.2s cubic-bezier(0.34,1.56,0.64,1), box-shadow 0.2s ease; }
.stat-box:hover { transform: translateY(-3px) scale(1.06); box-shadow:0 6px 18px rgba(0,0,0,0.1); }
.clock-display { letter-spacing:0.05em; animation: clockIn 0.5s ease; }
@keyframes clockIn { from{opacity:0;transform:scale(0.94);} to{opacity:1;transform:scale(1);} }
.shift-card { transition: border-color 0.2s ease, box-shadow 0.2s ease; }
.shift-card:hover { border-color:#93c5fd; box-shadow:0 2px 10px rgba(59,130,246,0.1); }
.toggle-btn { transition: background 0.25s ease, color 0.25s ease; }
.time-box { transition: border-color 0.2s ease, transform 0.18s ease; }
.time-box:hover { border-color:#93c5fd; transform:translateY(-1px); }
.clock-btn { transition: all 0.25s cubic-bezier(0.34,1.56,0.64,1); position:relative; overflow:hidden; border:none; cursor:pointer; }
.clock-btn::after { content:''; position:absolute; inset:0; background:rgba(255,255,255,0.12); opacity:0; transition:opacity 0.2s ease; }
.clock-btn:hover::after { opacity:1; }
.clock-btn:hover { transform:translateY(-2px); box-shadow:0 8px 24px rgba(59,130,246,0.35); }
.clock-btn:active { transform:translateY(0) scale(0.97); }
.action-btn { transition: background 0.2s ease, border-color 0.2s ease, transform 0.2s cubic-bezier(0.34,1.56,0.64,1), box-shadow 0.2s ease; }
.action-btn:hover { background:#eff6ff; border-color:#93c5fd; color:#3b82f6; transform:translateY(-3px); box-shadow:0 6px 18px rgba(59,130,246,0.15); }
.action-btn:active { transform:translateY(0) scale(0.97); }
.cal-nav-btn { transition: background 0.15s ease, transform 0.15s ease; }
.cal-nav-btn:hover { transform:scale(1.12); }
.cal-nav-btn:active { transform:scale(0.9); }
.cal-day { transition: background 0.15s ease, color 0.15s ease, transform 0.15s ease; }
.cal-day:hover:not(.today-pill) { transform:scale(1.18); background:#eff6ff; }
.today-pill { animation: todayGlow 2.5s ease-in-out infinite; }
@keyframes todayGlow { 0%,100% { box-shadow:0 2px 8px rgba(59,130,246,0.4); } 50% { box-shadow:0 2px 18px rgba(59,130,246,0.7); } }
.shimmer { background: linear-gradient(90deg, #f1f5f9 25%, #e2e8f0 50%, #f1f5f9 75%); background-size: 200% 100%; animation: shimmer 1.8s infinite linear; }
@keyframes shimmer { 0%{background-position:200% 0} 100%{background-position:-200% 0} }
</style>
@endsection
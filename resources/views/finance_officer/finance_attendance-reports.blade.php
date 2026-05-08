@php
    $currentRoute = request()->route()->getName();
    $sidebarUser = auth()->user();
    $sidebarEmployee = $sidebarUser ? \App\Models\Employee::with('jobTitle')->where('user_id', $sidebarUser->id)->first() : null;
    $sidebarInitials = $sidebarEmployee
        ? strtoupper(substr($sidebarEmployee->fname, 0, 1) . substr($sidebarEmployee->lname, 0, 1))
        : ($sidebarUser ? strtoupper(substr($sidebarUser->email, 0, 2)) : 'U');
    $sidebarName = $sidebarEmployee
        ? trim($sidebarEmployee->fname . ' ' . $sidebarEmployee->lname)
        : ($sidebarUser?->email ?? 'User');
    $sidebarRole = $sidebarEmployee?->jobTitle?->title ?? ($sidebarUser?->role ?? '—');

    $attendanceRoutes = ['finance_officer.attendance.reports', 'finance_officer.attendance.shift', 'finance_officer.attendance.leave'];
    $payrollRoutes    = ['finance_officer.payroll', 'finance_officer.payslips', 'finance_officer.contributions'];
    $requestRoutes    = ['finance_officer.requests.pending', 'finance_officer.requests.approved'];
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
    <link rel="icon" type="image/png" href="{{ asset('images/HRISLogo-Icon.png') }}">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=yes, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>My Attendance – MEDISOURCE</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <style>
        * { font-family: 'Inter', sans-serif; }
        [x-cloak] { display: none !important; }

        /* Sidebar */
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

        /* Desktop Sidebar */
        .desktop-sidebar { display: none; }
        @media (min-width: 1024px) {
            .desktop-sidebar { display: block; }
        }
        @media (max-width: 1023px) {
            .main-content-margin { margin-left: 0 !important; }
        }

        /* Animations */
        @keyframes fadeUp {
            from { opacity:0; transform:translateY(14px); }
            to   { opacity:1; transform:translateY(0); }
        }
        @keyframes fadeIn { from{opacity:0}to{opacity:1} }
        @keyframes rowSlide {
            from { opacity:0; transform:translateX(-8px); }
            to   { opacity:1; transform:translateX(0); }
        }
        @keyframes pulseDot {
            0%,100%{opacity:1} 50%{opacity:0.3}
        }

        .anim-up   { animation: fadeUp 0.45s cubic-bezier(0.22,1,0.36,1) both; }
        .anim-fade { animation: fadeIn 0.35s ease both; }

        .stat-card {
            animation: fadeUp 0.45s cubic-bezier(0.22,1,0.36,1) both;
            transition: transform 0.2s ease, box-shadow 0.2s ease;
            border-radius: 18px;
        }
        .stat-card:hover { transform:translateY(-3px); box-shadow:0 12px 32px rgba(0,0,0,0.10); }

        .table-row {
            animation: rowSlide 0.3s cubic-bezier(0.22,1,0.36,1) both;
            transition: background 0.12s ease;
        }
        .table-row:hover { background:#f8faff; }

        .pulse-dot { animation: pulseDot 2s ease-in-out infinite; }

        .export-btn {
            transition: all 0.2s ease;
            background: linear-gradient(135deg,#3b82f6,#1d4ed8);
        }
        .export-btn:hover { transform:translateY(-1px); box-shadow:0 6px 18px rgba(59,130,246,0.40); }

        .clock-btn { transition: all 0.2s cubic-bezier(0.4,0,0.2,1); border-radius: 12px; }
        .clock-btn:hover:not(:disabled) { transform:translateY(-1px); box-shadow:0 6px 20px rgba(59,130,246,0.38); }
        .clock-btn:disabled { opacity:0.6; cursor:not-allowed; }

        .setup-btn { transition: all 0.2s ease; }

        ::-webkit-scrollbar { width:4px; }
        ::-webkit-scrollbar-track { background:#f1f5f9; }
        ::-webkit-scrollbar-thumb { background:#cbd5e1; border-radius:99px; }

        /* Mobile Responsive */
        @media (max-width: 768px) {
            .grid-cols-6 { grid-template-columns: repeat(2, 1fr) !important; gap: 12px !important; }
            .stat-card { padding: 12px 14px !important; }
            .stat-card .font-black { font-size: 1.5rem !important; }
            .p-6 { padding: 1rem !important; }
            .px-6 { padding-left: 1rem !important; padding-right: 1rem !important; }
            header .px-8 { padding-left: 1rem !important; padding-right: 1rem !important; }
            header h1 { font-size: 1.2rem !important; }
            .overflow-x-auto { overflow-x: auto; -webkit-overflow-scrolling: touch; }
            table { min-width: 700px; }
            th, td { padding-left: 0.75rem !important; padding-right: 0.75rem !important; }
            .border-t.flex.items-center.justify-between { flex-direction: column; gap: 0.75rem; align-items: center; }
            .inline-flex.items-center.gap-1\.5 { padding: 0.25rem 0.6rem !important; font-size: 0.7rem !important; }
            header.sticky { margin-left: 0.75rem; margin-right: 0.75rem; margin-top: 0.75rem; }
        }
        
        @media (max-width: 480px) {
            .stat-card .font-black { font-size: 1.25rem !important; }
        }
        
        .main-content-padding { padding: 24px 32px; }
        @media (max-width: 768px) { .main-content-padding { padding: 14px !important; } }
    </style>
</head>
<body class="bg-gray-100" x-data="{ mobileMenuOpen: false, sidebarCollapsed: localStorage.getItem('sidebarCollapsed') === 'true' }"
     x-init="window.addEventListener('sidebar-toggle', e => { sidebarCollapsed = e.detail.collapsed })">

{{-- ═══════════ DESKTOP SIDEBAR ═══════════ --}}
<div class="hidden lg:block desktop-sidebar">
    @include('finance_officer.finance_sidebar')
</div>

{{-- ═══════════ MOBILE SLIDE-OUT DRAWER ═══════════ --}}
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
        @include('finance_officer.finance_sidebar')
    </div>
</div>

{{-- ═══════════ MAIN CONTENT ═══════════ --}}
<div class="main-content-margin"
     :style="window.innerWidth >= 1024 ? (sidebarCollapsed ? 'margin-left:5rem' : 'margin-left:16rem') : 'margin-left:0'"
     x-on:resize.window="$el.style.marginLeft = window.innerWidth >= 1024 ? (sidebarCollapsed ? '5rem' : '16rem') : '0'"
     style="transition:margin-left 0.35s cubic-bezier(0.4,0,0.2,1); min-height:100vh;">

    {{-- Blue Header with Hamburger --}}
    <header class="anim-fade bg-gradient-to-br from-blue-500 to-blue-700 sticky top-0 z-10 shadow-lg mt-3 mx-3 lg:mt-4 lg:mx-4 rounded-2xl">
        <div class="px-4 sm:px-6 lg:px-8 py-4 flex items-center justify-between">
            <div class="flex items-center gap-3">
                <button @click="mobileMenuOpen = true"
                        class="lg:hidden p-1.5 rounded-lg hover:bg-white/20 transition-colors text-white">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M4 6h16M4 12h16M4 18h16"/>
                    </svg>
                </button>
                <div>
                    <h1 class="text-xl sm:text-2xl font-bold text-white">My Attendance</h1>
                    <p class="text-xs sm:text-sm text-blue-100 mt-1">Attendance Records</p>
                </div>
            </div>
            <div class="flex items-center space-x-2 sm:space-x-4">
                <x-notification-bell />
            </div>
        </div>
    </header>

    <div class="main-content-padding space-y-5">

        {{-- STAT CARDS (Responsive Grid - Mobile 2 columns) --}}
        <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-3 mb-6">
            <div class="stat-card p-4 flex flex-col justify-between" style="background:#c8f0d8; animation-delay:0.08s;">
                <p class="text-xs font-bold uppercase tracking-wider" style="color:#14532d;">Present</p>
                <div><p class="font-black leading-none mt-2" style="font-size:1.8rem; color:#15803d;">{{ $stats['present'] }}</p><p class="text-xs font-semibold mt-1" style="color:#166534;">{{ \Carbon\Carbon::create($year, $month)->format('F Y') }}</p></div>
            </div>
            <div class="stat-card p-4 flex flex-col justify-between" style="background:#fde8c8; animation-delay:0.11s;">
                <p class="text-xs font-bold uppercase tracking-wider" style="color:#92400e;">Late</p>
                <div><p class="font-black leading-none mt-2" style="font-size:1.8rem; color:#b45309;">{{ $stats['late'] }}</p><p class="text-xs font-semibold mt-1" style="color:#9a3412;">{{ \Carbon\Carbon::create($year, $month)->format('F Y') }}</p></div>
            </div>
            <div class="stat-card p-4 flex flex-col justify-between" style="background:#fbc8c8; animation-delay:0.14s;">
                <p class="text-xs font-bold uppercase tracking-wider" style="color:#991b1b;">Absent</p>
                <div><p class="font-black leading-none mt-2" style="font-size:1.8rem; color:#dc2626;">{{ $stats['absent'] }}</p><p class="text-xs font-semibold mt-1" style="color:#991b1b;">{{ \Carbon\Carbon::create($year, $month)->format('F Y') }}</p></div>
            </div>
            <div class="stat-card p-4 flex flex-col justify-between" style="background:#f9c8e8; animation-delay:0.17s;">
                <p class="text-xs font-bold uppercase tracking-wider" style="color:#831843;">Leave</p>
                <div><p class="font-black leading-none mt-2" style="font-size:1.8rem; color:#be185d;">{{ $stats['on_leave'] }}</p><p class="text-xs font-semibold mt-1" style="color:#831843;">{{ \Carbon\Carbon::create($year, $month)->format('F Y') }}</p></div>
            </div>
            <div class="stat-card p-4 flex flex-col justify-between" style="background:#c8dafa; animation-delay:0.20s;">
                <p class="text-xs font-bold uppercase tracking-wider" style="color:#1e3a8a;">Overtime</p>
                <div><p class="font-black leading-none mt-2" style="font-size:1.8rem; color:#1d4ed8;">{{ $stats['overtime'] }}</p><p class="text-xs font-semibold mt-1" style="color:#1e40af;">{{ \Carbon\Carbon::create($year, $month)->format('F Y') }}</p></div>
            </div>
            <div class="stat-card p-4 flex flex-col justify-between" style="background:#c8dafa; animation-delay:0.23s;">
                <p class="text-xs font-bold uppercase tracking-wider" style="color:#1e3a8a;">Undertime</p>
                <div><p class="font-black leading-none mt-2" style="font-size:1.8rem; color:#6d28d9;">{{ $stats['undertime'] }}</p><p class="text-xs font-semibold mt-1" style="color:#5b21b6;">{{ \Carbon\Carbon::create($year, $month)->format('F Y') }}</p></div>
            </div>
        </div>

        {{-- ── ATTENDANCE RECORDS TABLE (Responsive) ── --}}
        <div class="anim-up bg-white rounded-2xl overflow-hidden"
             style="animation-delay:0.26s; box-shadow:0 1px 12px rgba(0,0,0,0.07);">

            <div class="px-4 sm:px-6 py-4 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
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
                    @canDo('Time & Attendance', 'export')
                    <button @click="exportPdf()"
                            :disabled="totalRecords === 0"
                            :class="totalRecords > 0 ? 'export-btn' : ''"
                            :style="totalRecords > 0 ? '' : 'background:#d1d5db;cursor:not-allowed;'"
                            class="flex items-center gap-2 px-4 py-2 rounded-xl text-xs font-bold text-white border-none">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                        Export PDF
                    </button>
                    @endcanDo
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead>
                        <tr class="border-t border-b border-gray-100 bg-gray-50/60">
                            <th class="text-left px-4 sm:px-6 py-3 text-xs font-bold text-gray-400 uppercase tracking-wider">Date</th>
                            <th class="text-left px-4 sm:px-6 py-3 text-xs font-bold text-gray-400 uppercase tracking-wider">Work Setup</th>
                            <th class="text-left px-4 sm:px-6 py-3 text-xs font-bold text-gray-400 uppercase tracking-wider">Shift</th>
                            <th class="text-left px-4 sm:px-6 py-3 text-xs font-bold text-gray-400 uppercase tracking-wider">Schedule</th>
                            <th class="text-left px-4 sm:px-6 py-3 text-xs font-bold text-gray-400 uppercase tracking-wider">Clock In</th>
                            <th class="text-left px-4 sm:px-6 py-3 text-xs font-bold text-gray-400 uppercase tracking-wider">Clock Out</th>
                            <th class="text-left px-4 sm:px-6 py-3 text-xs font-bold text-gray-400 uppercase tracking-wider">Overtime</th>
                            <th class="text-left px-4 sm:px-6 py-3 text-xs font-bold text-gray-400 uppercase tracking-wider">Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-50">
                        @php
                            $attendanceRecords = $records ?? [];
                        @endphp
                        @forelse($attendanceRecords as $index => $log)
                        @php
                            $rowDelay = 0.035 * $index;
                        @endphp
                        <tr class="table-row" style="animation-delay: {{ $rowDelay }}s">
                            <td class="px-4 sm:px-6 py-4 text-sm font-medium text-gray-700 whitespace-nowrap">
                                {{ \Carbon\Carbon::parse($log->attendance_date)->format('M d, Y') }}
                            </td>
                            <td class="px-4 sm:px-6 py-4">
                                <span class="px-3 py-1 rounded-lg text-xs font-bold"
                                    style="background:#dbeafe;color:#1d4ed8;">
                                    {{ $log->work_setup ? strtoupper($log->work_setup) : 'WFH' }}
                                </span>
                            </td>
                            <td class="px-4 sm:px-6 py-4 text-sm text-gray-600">{{ $log->shift->name ?? '—' }}</td>
                            <td class="px-4 sm:px-6 py-4 text-sm text-gray-500 whitespace-nowrap">
                                {{ $log->shift ? ($log->shift->is_flexi ? ($log->shift->required_hours . 'h required') : \Carbon\Carbon::parse($log->shift->start_time)->format('g:i A') . ' – ' . \Carbon\Carbon::parse($log->shift->end_time)->format('g:i A')) : '—' }}
                            </td>
                            <td class="px-4 sm:px-6 py-4 text-sm font-semibold text-gray-800 whitespace-nowrap">
                                {{ $log->clock_in ? \Carbon\Carbon::parse($log->clock_in)->format('g:i A') : '—' }}
                            </td>
                            <td class="px-4 sm:px-6 py-4 text-sm text-gray-500 whitespace-nowrap">
                                {{ $log->clock_out ? \Carbon\Carbon::parse($log->clock_out)->format('g:i A') : '—' }}
                            </td>
                            <td class="px-4 sm:px-6 py-4 text-sm text-gray-600 whitespace-nowrap">
                                @php
                                    $otMins = $log->overtime_minutes ?? 0;
                                @endphp
                                {{ $otMins > 0 ? floor($otMins/60).'h '.str_pad($otMins%60,2,'0',STR_PAD_LEFT).'m' : '00h 00m' }}
                            </td>
                            <td class="px-4 sm:px-6 py-4">
                                @php
                                    $status = $log->status ?? 'present';
                                    $displayStatus = ucfirst(str_replace('_', ' ', $status));
                                    $bgColor = match($status) {
                                        'present' => '#dcfce7', 'late' => '#ffedd5', 'absent' => '#fee2e2',
                                        'on_leave' => '#ede9fe', 'undertime' => '#fef9c3', 'overtime' => '#dbeafe',
                                        default => '#f1f5f9'
                                    };
                                    $textColor = match($status) {
                                        'present' => '#16a34a', 'late' => '#d97706', 'absent' => '#dc2626',
                                        'on_leave' => '#4f46e5', 'undertime' => '#b45309', 'overtime' => '#1d4ed8',
                                        default => '#64748b'
                                    };
                                @endphp
                                <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold"
                                      style="background:{{ $bgColor }}; color:{{ $textColor }};">
                                    <span class="w-1.5 h-1.5 rounded-full" style="background:{{ $textColor }};"></span>
                                    {{ $displayStatus }}
                                </span>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="8" class="px-4 sm:px-6 py-10 text-center text-gray-400">
                                No attendance records found for this month.
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{-- Pagination --}}
            @if(isset($records) && $records instanceof \Illuminate\Pagination\LengthAwarePaginator)
            <div class="px-4 sm:px-6 py-4 border-t border-gray-100 flex flex-col sm:flex-row items-center justify-between gap-3">
                <p class="text-xs text-gray-400">
                    Showing <span class="font-semibold text-gray-600">{{ $records->firstItem() ?? 0 }}</span> to 
                    <span class="font-semibold text-gray-600">{{ $records->lastItem() ?? 0 }}</span> of 
                    <span class="font-semibold text-gray-600">{{ $records->total() }}</span> records
                </p>
                <div class="flex items-center gap-1">
                    @if($records->onFirstPage())
                        <button disabled class="w-8 h-8 rounded-lg border border-gray-200 text-gray-300 text-xs flex items-center justify-center">&lsaquo;</button>
                    @else
                        <a href="{{ $records->previousPageUrl() }}" class="w-8 h-8 rounded-lg border border-gray-200 text-gray-500 text-xs flex items-center justify-center hover:bg-blue-50 hover:text-blue-600 transition">&lsaquo;</a>
                    @endif
                    
                    @for($i = 1; $i <= $records->lastPage(); $i++)
                        @if($i == $records->currentPage())
                            <span class="w-8 h-8 rounded-lg text-xs font-bold flex items-center justify-center" style="background:#3b82f6;color:#fff;">{{ $i }}</span>
                        @else
                            <a href="{{ $records->url($i) }}" class="w-8 h-8 rounded-lg border border-gray-200 text-gray-500 text-xs flex items-center justify-center hover:bg-blue-50 hover:text-blue-600 transition">{{ $i }}</a>
                        @endif
                    @endfor
                    
                    @if($records->hasMorePages())
                        <a href="{{ $records->nextPageUrl() }}" class="w-8 h-8 rounded-lg border border-gray-200 text-gray-500 text-xs flex items-center justify-center hover:bg-blue-50 hover:text-blue-600 transition">&rsaquo;</a>
                    @else
                        <button disabled class="w-8 h-8 rounded-lg border border-gray-200 text-gray-300 text-xs flex items-center justify-center">&rsaquo;</button>
                    @endif
                </div>
            </div>
            @endif
        </div>

    </div>
</div>

@php
    $openSession = $todayLog?->sessions()->whereNull('clock_out')->first();
    $completedWorkSeconds = $todayLog
        ? (int) $todayLog->sessions()->whereNotNull('clock_out')->get()->sum(function($s) {
            return max(0, \Carbon\Carbon::parse($s->clock_in)->diffInSeconds(\Carbon\Carbon::parse($s->clock_out)) - ($s->break_minutes * 60));
          })
        : 0;
@endphp

<script>
function attendancePage() {
    return {
        sidebarCollapsed: localStorage.getItem('sidebarCollapsed') === 'true',
        workSetup: '{{ $todayLog?->work_setup ?? ($employeeShift?->work_setup ?? "wfh") }}',
        assignedShiftId: {{ $employeeShift?->shift_id ?? 'null' }},
        breakAllowed: {{ $employeeShift?->shift?->break_schedule ? 'true' : 'false' }},
        onLeave:     {{ $todayLog?->status === 'on_leave' ? 'true' : 'false' }},
        clockedIn:   {{ $openSession ? 'true' : 'false' }},
        clockedOut:  {{ ($todayLog?->clock_out && !$openSession) ? 'true' : 'false' }},
        onBreak:     {{ ($openSession?->break_start && !$openSession?->break_end) ? 'true' : 'false' }},
        resumed:     {{ $openSession?->break_end ? 'true' : 'false' }},
        breakTime:   '{{ $openSession?->break_start ? \Carbon\Carbon::parse($openSession->break_start)->setTimezone(config("app.timezone"))->format("h:i A") : "" }}',
        breakMinutes: {{ $todayLog?->break_minutes ?? 0 }},
        clockInTime:  '{{ $todayLog?->clock_in ? \Carbon\Carbon::parse($todayLog->clock_in)->setTimezone(config("app.timezone"))->format("h:i A") : "" }}',
        clockOutTime: '{{ $todayLog?->clock_out ? \Carbon\Carbon::parse($todayLog->clock_out)->setTimezone(config("app.timezone"))->format("h:i A") : "" }}',
        completedWorkSeconds: {{ $completedWorkSeconds }},
        currentSessionStart: {{ $openSession?->clock_in ? \Carbon\Carbon::parse($openSession->clock_in)->valueOf() : 'null' }},
        currentSessionBreakMinutes: {{ $openSession?->break_minutes ?? 0 }},
        liveTime: '', liveDate: '', elapsedSeconds: 0,
        rows: [], currentPage: 1, totalRecords: 0, perPage: 10,
        currentMonth: {{ $month }}, currentYear: {{ $year }},
        selectedMonth: {{ $month }},
        errorMessage: '',

        showError(msg) { this.errorMessage = msg; },

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
                if (this.clockedIn && !this.onBreak)
                    this.elapsedSeconds = this.currentSessionStart
                        ? this.completedWorkSeconds + Math.floor((Date.now() - this.currentSessionStart) / 1000) - (this.currentSessionBreakMinutes * 60)
                        : this.completedWorkSeconds;
            }, 1000);
            window.addEventListener('sidebar-toggle', e => { this.sidebarCollapsed = e.detail.collapsed; });
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
            const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

            if (this.onBreak) {
                const res = await fetch('{{ route("finance_officer.attendance.clock-in") }}', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken },
                    body: JSON.stringify({ work_setup: this.workSetup, shift_id: this.assignedShiftId })
                });
                const data = await res.json();
                if (res.ok) {
                    this.onBreak = false;
                    this.resumed = true;
                    this.clockedIn = true;
                    this.breakMinutes = data.break_minutes;
                    this.currentSessionBreakMinutes = data.break_minutes;
                } else { this.showError(data.message ?? 'Resume failed.'); }
                return;
            }

            if (!this.clockedIn) {
                const res = await fetch('{{ route("finance_officer.attendance.clock-in") }}', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken },
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
                return;
            }
        },

        async handleBreak() {
            if (!this.clockedIn || this.onBreak || this.resumed) return;
            const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
            const res = await fetch('{{ route("finance_officer.attendance.break") }}', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken },
                body: JSON.stringify({})
            });
            const data = await res.json();
            if (res.ok) {
                this.onBreak = true;
                this.breakTime = data.break_start;
            } else { this.showError(data.message ?? 'Break failed.'); }
        },

        async handleClockOut() {
            if (!this.clockedIn) return;
            const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
            const res = await fetch('{{ route("finance_officer.attendance.clock-out") }}', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken },
                body: JSON.stringify({})
            });
            const data = await res.json();
            if (res.ok) {
                this.clockedIn = false;
                this.clockedOut = true;
                this.clockOutTime = data.clock_out;
                this.onBreak = false;
                await this.loadRecords();
            } else { this.showError(data.message ?? 'Clock-out failed.'); }
        },

        async loadRecords(page = 1) {
            try {
                const res = await fetch(`{{ route("finance_officer.attendance.records") }}?month=${this.currentMonth}&year=${this.currentYear}&page=${page}`);
                const data = await res.json();
                if (!data.data) { this.rows = []; this.totalRecords = 0; return; }
                this.rows = data.data.map(log => ({
                    date:     new Date(log.attendance_date).toLocaleDateString('en-US',{year:'numeric',month:'long',day:'numeric'}),
                    setup:    log.work_setup ? log.work_setup.toUpperCase() : '—',
                    shift:    log.shift?.name ?? '—',
                    schedule: log.shift ? (log.shift.is_flexi ? (log.shift.required_hours + 'h required') : this.formatTime(log.shift.start_time) + ' – ' + this.formatTime(log.shift.end_time)) : '—',
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
        dotStyle(s){return{'Present':'background:#22c55e;','Late':'background:#f59e0b;','Absent':'background:#ef4444;','On Leave':'background:#6366f1;','Undertime':'background:#fbbf24;','Overtime':'background:#3b82f6;'}[s]||'background:#94a3b8;';},

        async exportPdf() {
            const res = await fetch(`{{ route("finance_officer.attendance.records") }}?month=${this.currentMonth}&year=${this.currentYear}&per_page=500`);
            const data = await res.json();
            const allRows = (data.data || []).map(log => ({
                date:     new Date(log.attendance_date).toLocaleDateString('en-US',{year:'numeric',month:'long',day:'numeric'}),
                setup:    log.work_setup ? log.work_setup.toUpperCase() : '—',
                shift:    log.shift?.name ?? '—',
                schedule: log.shift ? (log.shift.is_flexi ? (log.shift.required_hours + 'h required') : this.formatTime(log.shift.start_time)+' – '+this.formatTime(log.shift.end_time)) : '—',
                clockIn:  log.clock_in  ? new Date(log.clock_in).toLocaleTimeString('en-US',{hour:'2-digit',minute:'2-digit',hour12:true})  : '—',
                clockOut: log.clock_out ? new Date(log.clock_out).toLocaleTimeString('en-US',{hour:'2-digit',minute:'2-digit',hour12:true}) : '—',
                overtime: log.overtime_minutes > 0 ? Math.floor(log.overtime_minutes/60)+'h '+String(log.overtime_minutes%60).padStart(2,'0')+'m' : '—',
                status:   log.status ? log.status.replace('_',' ').replace(/\b\w/g,c=>c.toUpperCase()) : '—',
            }));
            const period = new Date(this.currentYear, this.currentMonth-1, 1).toLocaleDateString('en-US',{month:'long',year:'numeric'});
            this._printHtml(this._buildAttendancePdfHtml(allRows, period));
        },

        _buildAttendancePdfHtml(rows, period) {
            const sBg  = s=>({'Present':'#dcfce7','Late':'#fef3c7','Absent':'#fee2e2','On Leave':'#ede9fe','Undertime':'#fef9c3','Overtime':'#dbeafe'}[s]||'#f1f5f9');
            const sClr = s=>({'Present':'#16a34a','Late':'#d97706','Absent':'#dc2626','On Leave':'#4f46e5','Undertime':'#b45309','Overtime':'#1d4ed8'}[s]||'#64748b');
            const tbody = rows.map((r,i)=>`<tr style="background:${i%2===0?'#fff':'#f8faff'}">
                <td style="padding:8px 10px;border-bottom:1px solid #f1f5f9;font-size:12px;white-space:nowrap;">${r.date}</td>
                <td style="padding:8px 10px;border-bottom:1px solid #f1f5f9;"><span style="background:${r.setup==='WFH'?'#dbeafe':'#f1f5f9'};color:${r.setup==='WFH'?'#1d4ed8':'#475569'};padding:2px 7px;border-radius:4px;font-size:11px;font-weight:600;">${r.setup}</span></td>
                <td style="padding:8px 10px;border-bottom:1px solid #f1f5f9;font-size:12px;">${r.shift}</td>
                <td style="padding:8px 10px;border-bottom:1px solid #f1f5f9;font-size:12px;white-space:nowrap;">${r.schedule}</td>
                <td style="padding:8px 10px;border-bottom:1px solid #f1f5f9;font-size:12px;font-weight:600;white-space:nowrap;">${r.clockIn}</td>
                <td style="padding:8px 10px;border-bottom:1px solid #f1f5f9;font-size:12px;white-space:nowrap;">${r.clockOut}</td>
                <td style="padding:8px 10px;border-bottom:1px solid #f1f5f9;font-size:12px;white-space:nowrap;">${r.overtime}</td>
                <td style="padding:8px 10px;border-bottom:1px solid #f1f5f9;"><span style="background:${sBg(r.status)};color:${sClr(r.status)};padding:2px 7px;border-radius:4px;font-size:11px;font-weight:600;">${r.status}</span></td>
            </tr>`).join('');
            return `<!DOCTYPE html><html><head><meta charset="UTF-8"><title>Attendance – ${period}</title><style>*{font-family:Arial,sans-serif;box-sizing:border-box;margin:0;padding:0;}body{padding:32px;color:#1e293b;}.hdr{border-bottom:2px solid #2563eb;padding-bottom:14px;margin-bottom:20px;}.co{font-size:20px;font-weight:700;color:#2563eb;}.sub{font-size:12px;color:#64748b;margin-top:2px;}.badge{display:inline-block;background:#eff6ff;color:#1d4ed8;border-radius:5px;padding:3px 12px;font-size:11px;font-weight:600;margin-top:6px;}table{width:100%;border-collapse:collapse;}thead tr{background:#1d4ed8;}thead th{padding:9px 10px;text-align:left;color:#fff;font-size:10.5px;font-weight:700;text-transform:uppercase;letter-spacing:.04em;white-space:nowrap;}.footer{margin-top:20px;font-size:10px;color:#94a3b8;text-align:center;border-top:1px solid #e2e8f0;padding-top:10px;}@media print{body{padding:16px;}@page{margin:.8cm;size:landscape;}}</style></head><body><div class="hdr"><div class="co">MediSource</div><div class="sub">Attendance Report</div><div class="badge">${period}</div></div><table><thead><tr><th>Date</th><th>Setup</th><th>Shift</th><th>Schedule</th><th>Clock In</th><th>Clock Out</th><th>Overtime</th><th>Status</th></tr></thead><tbody>${tbody}</tbody></table><div class="footer">System-generated attendance report — MediSource HRIS · Generated ${new Date().toLocaleDateString('en-US',{year:'numeric',month:'long',day:'numeric'})}</div></body></html>`;
        },

        _printHtml(html) {
            const w = window.open('', '_blank', 'width=1050,height=820,scrollbars=yes');
            if (!w) return;
            w.document.write(html);
            w.document.close();
            w.document.querySelectorAll('[x-show],[x-cloak]').forEach(el => el.remove());
            w.focus();
            setTimeout(() => w.print(), 400);
        },
    }
}
</script>

@include('partials.attendance-error-modal')
</body>
</html>
<!DOCTYPE html>
<html lang="en">
<head>
    <link rel="icon" type="image/png" href="{{ asset('images/HRISLogo-Icon.png') }}">
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Payroll – MediSource</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <style>
        [x-cloak] { display: none !important; }
        body { background: #f0f2f5; }

        .tab-bar { display:flex; gap:0; border-bottom:1px solid #e5e7eb; }
        .tab-btn { position:relative; padding:12px 24px; font-size:0.9rem; font-weight:500; color:#9ca3af; border:none; background:none; cursor:pointer; white-space:nowrap; transition:color 0.2s ease; border-bottom:2px solid transparent; margin-bottom:-1px; }
        .tab-btn:hover:not(.active) { color:#374151; }
        .tab-btn.active { color:#2563eb; font-weight:600; border-bottom:2px solid #2563eb; }

        @keyframes tabFadeIn { from{opacity:0;transform:translateY(8px)} to{opacity:1;transform:translateY(0)} }
        .tab-content { animation:tabFadeIn 0.28s cubic-bezier(0.4,0,0.2,1); }
        @keyframes fadeSlideUp { from{opacity:0;transform:translateY(16px)} to{opacity:1;transform:translateY(0)} }
        .anim-1 { animation:fadeSlideUp 0.38s ease both; }
        .anim-2 { animation:fadeSlideUp 0.38s 0.06s ease both; }
        .anim-3 { animation:fadeSlideUp 0.38s 0.12s ease both; }
        @keyframes slideInRight { from{opacity:0;transform:translateX(20px)} to{opacity:1;transform:translateX(0)} }
        .slide-in-right { animation:slideInRight 0.25s cubic-bezier(0.4,0,0.2,1) both; }

        .summary-card { background:#fff; border-radius:10px; border:1px solid #e5e7eb; padding:22px 26px; flex:1; transition:transform 0.2s ease,box-shadow 0.2s ease; }
        .summary-card:hover { transform:translateY(-2px); box-shadow:0 8px 24px rgba(59,130,246,0.10); }
        .summary-card .label { font-size:0.78rem; color:#9ca3af; margin-bottom:6px; }
        .summary-card .value { font-size:1.75rem; font-weight:700; color:#1e293b; letter-spacing:-0.5px; }
        .summary-card .sub   { font-size:0.72rem; color:#9ca3af; margin-top:4px; }

        .badge-pending    { background:#fff3e0; color:#e65100; }
        .badge-submitted  { background:#e3f2fd; color:#1565c0; }
        .badge-released   { background:#e8f5e9; color:#2e7d32; }
        .badge-active     { background:#e8f5e9; color:#2e7d32; }
        .badge-inactive   { background:#fce4ec; color:#c62828; }
        .badge-addition   { background:#fce4ec; color:#ad1457; }
        .badge-deduction  { background:#e8eaf6; color:#283593; }
        .badge-allowance  { background:#fce4ec; color:#ad1457; }
        .badge-nontaxable { background:#e3f2fd; color:#1565c0; }
        .badge-taxable    { background:#e3f2fd; color:#1565c0; }
        .multiplier-badge { background:#fff8e1; color:#f57c00; padding:2px 10px; border-radius:20px; font-size:0.78rem; font-weight:600; }

        .data-table { width:100%; border-collapse:collapse; }
        .data-table thead tr { background:#f8fafc; border-bottom:1px solid #e5e7eb; }
        .data-table thead th { text-align:left; padding:11px 20px; font-size:0.75rem; font-weight:600; color:#9ca3af; text-transform:uppercase; letter-spacing:0.05em; }
        .data-table tbody tr { border-bottom:1px solid #f1f5f9; transition:background 0.15s; }
        .data-table tbody tr:hover { background:#f8faff; }
        .data-table tbody td { padding:14px 20px; font-size:0.875rem; color:#374151; }

        .pv-table { width:100%; border-collapse:collapse; }
        .pv-table thead tr { border-bottom:1px solid #e5e7eb; }
        .pv-table thead th { text-align:left; padding:12px 16px; font-size:0.8rem; font-weight:600; color:#374151; }
        .pv-table tbody tr { border-bottom:1px solid #f1f5f9; transition:background 0.15s; cursor:pointer; }
        .pv-table tbody tr:hover { background:#f8faff; }
        .pv-table tbody tr.row-active { background:#eff6ff; }
        .pv-table tbody td { padding:14px 16px; font-size:0.875rem; color:#374151; }

        .progress-track { height:4px; background:#e5e7eb; border-radius:99px; overflow:hidden; margin-top:5px; }
        .progress-fill  { height:100%; background:#3b82f6; border-radius:99px; transition:width 0.6s cubic-bezier(0.4,0,0.2,1); }

        .payslip-header { background:linear-gradient(135deg,#3b82f6 0%,#2563eb 100%); border-radius:12px 12px 0 0; padding:18px 22px; color:#fff; }
        .payslip-body { padding:0 22px 22px; }
        .payslip-section-title { font-size:0.82rem; font-weight:700; color:#374151; margin-top:16px; margin-bottom:8px; }
        .payslip-line { display:flex; justify-content:space-between; font-size:0.82rem; color:#6b7280; padding:3px 0; }
        .payslip-line.bold { font-weight:700; color:#1e293b; font-size:0.875rem; border-top:1px solid #e5e7eb; padding-top:8px; margin-top:4px; }
        .info-label { font-size:0.72rem; color:rgba(255,255,255,0.75); }
        .info-value { font-size:0.82rem; color:#fff; font-weight:500; }

        .ctrl { border:1px solid #e2e8f0; border-radius:8px; padding:9px 14px; font-size:0.875rem; background:#fff; color:#374151; outline:none; transition:border-color 0.2s,box-shadow 0.2s; }
        .ctrl:focus { border-color:#3b82f6; box-shadow:0 0 0 3px rgba(59,130,246,0.1); }
        .search-wrap { position:relative; }
        .search-wrap svg { position:absolute; left:12px; top:50%; transform:translateY(-50%); width:16px; height:16px; color:#9ca3af; }
        .search-wrap input { padding-left:38px; }
        select { appearance:none; background-image:url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 24 24' fill='none' stroke='%239ca3af' stroke-width='2'%3E%3Cpath d='M19 9l-7 7-7-7'/%3E%3C/svg%3E"); background-repeat:no-repeat; background-position:right 12px center; padding-right:32px; }

        .btn-primary { background:#3b82f6; color:#fff; border-radius:8px; padding:9px 18px; font-size:0.875rem; font-weight:600; display:inline-flex; align-items:center; gap:6px; border:none; cursor:pointer; transition:background 0.2s; }
        .btn-primary:hover { background:#2563eb; }
        .btn-green { background:#16a34a; color:#fff; border-radius:8px; padding:9px 18px; font-size:0.875rem; font-weight:600; display:inline-flex; align-items:center; gap:6px; border:none; cursor:pointer; transition:background 0.2s; }
        .btn-green:hover { background:#15803d; }
        .btn-view { border:1px solid #e2e8f0; border-radius:7px; padding:5px 14px; font-size:0.8rem; font-weight:500; color:#374151; background:#fff; cursor:pointer; transition:all 0.15s; }
        .btn-view:hover { background:#eff6ff; color:#2563eb; border-color:#bfdbfe; }
        .btn-delete { border:1px solid #fee2e2; border-radius:7px; padding:5px 14px; font-size:0.8rem; font-weight:500; color:#ef4444; background:#fff; cursor:pointer; transition:all 0.15s; }
        .btn-delete:hover { background:#fef2f2; border-color:#fca5a5; }
        .btn-outline { border:1px solid #e2e8f0; border-radius:8px; padding:9px 18px; font-size:0.875rem; font-weight:500; color:#374151; background:#fff; cursor:pointer; transition:background 0.15s; }
        .btn-outline:hover { background:#f3f4f6; }
        .btn-back { display:inline-flex; align-items:center; gap:6px; padding:8px 16px; border:1px solid #e5e7eb; border-radius:8px; font-size:0.82rem; font-weight:500; color:#374151; background:#fff; cursor:pointer; transition:background 0.15s; text-decoration:none; }
        .btn-back:hover { background:#f3f4f6; }
        .btn-close-ps { border:1px solid #e2e8f0; border-radius:8px; padding:9px 18px; font-size:0.875rem; font-weight:500; color:#374151; background:#fff; cursor:pointer; transition:background 0.15s; flex:1; }
        .btn-close-ps:hover { background:#f3f4f6; }
        .btn-export-ps { background:#2563eb; color:#fff; border-radius:8px; padding:10px 18px; font-size:0.875rem; font-weight:600; display:inline-flex; align-items:center; gap:7px; border:none; cursor:pointer; transition:background 0.18s,transform 0.15s; flex:1; justify-content:center; }
        .btn-export-ps:hover { background:#1d4ed8; transform:translateY(-1px); }
        .cards-wrap { display: flex; gap: 16px; flex-wrap: wrap }
        .modal-overlay { background:rgba(0,0,0,0.4); backdrop-filter:blur(2px); }
        .main-content { transition:margin-left 0.35s cubic-bezier(0.4,0,0.2,1); }
@media (max-width: 768px) {
    .px-8 { padding-left: 16px; padding-right: 16px; }

    .tab-btn {
        padding: 12px 20px;
        font-size: 0.8rem;
    }

    .cards-wrap {
        flex-direction: column;
        gap: 12px;
    }
    .toolbar-period {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 0.75rem;
    }
    .toolbar-period > * {
        width: 100%;
        min-width: 0;
    }
    .toolbar-period .right-group {
        display: contents;
    }

@media (max-width: 768px) {
    .payslip-header {
        padding: 1rem;
    }
    .payslip-body {
        padding: 0 1rem 1rem;
    }
    .btn-back {
        font-size: 0.7rem;
        padding: 0.5rem 0.75rem;
    }
    nav.flex.items-center.gap-1\.5 {
        font-size: 0.7rem;
        flex-wrap: wrap;
    }
    .pv-table td,
    .pv-table th {
        white-space: normal; 
        word-break: break-word;
    }
    .pv-table .btn-view,
    .pv-table .rounded-full {
        white-space: nowrap;
    }
}
}

@media (min-width: 769px) {
    .toolbar-period {
        display: flex;
        flex-wrap: nowrap;
        justify-content: space-between;
        align-items: center;
        gap: 0.75rem;
    }
    /* Search bar fixed 320px */
    .toolbar-period .search-wrap {
        flex: 0 0 320px;
        width: 320px;
    }
    /* Right group stays together */
    .toolbar-period .right-group {
        display: flex;
        gap: 0.75rem;
        align-items: center;
        flex: 0 0 auto;
    }
}

@media (max-width: 768px) {

}
    </style>
</head>

{{-- Alpine root wraps everything ── --}}
<body x-data="payrollApp()" x-init="init()" class="flex h-screen overflow-hidden">

    <!-- ===================== DESKTOP SIDEBAR ===================== -->
    <div class="hidden lg:block">
        @include('admin.admin_sidebar')
    </div>

    <!-- ===================== MOBILE DRAWER ===================== -->
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
            @include('admin.admin_sidebar')
        </div>
    </div>


    {{-- ══════════════════════════════════════════════════
         MAIN CONTENT
    ══════════════════════════════════════════════════ --}}
    <!-- ===================== MAIN CONTENT ===================== -->
    <div class="flex-1 overflow-y-auto min-h-screen w-full transition-margin p-3 lg:p-6"
         :class="sidebarCollapsed ? 'lg:ml-20' : 'lg:ml-64'"
         style="transition: margin-left 0.35s cubic-bezier(0.4, 0, 0.2, 1);">

        {{-- Header with Hamburger --}}
        <header class="bg-gradient-to-r from-blue-500 to-blue-600 sticky top-0 z-40 shadow-lg -mt-3 -mx-3 lg:-mt-6 lg:-mx-6 mb-3 lg:mb-6 rounded-2xl">
            <div class="flex items-center justify-between px-8 py-4">
                <div class="flex items-center gap-3">
                    <button @click="mobileMenuOpen = true"
                            class="lg:hidden p-2 rounded-lg hover:bg-white/20 transition-colors">
                        <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M4 6h16M4 12h16M4 18h16"/>
                        </svg>
                    </button>
                    <h1 class="text-white font-bold text-xl tracking-tight">Payslips</h1>
                </div>
                <x-notification-bell />
            </div>
        </header>


        {{-- ══════════════════════════════════════
             PAGE: LIST (tabs)
        ══════════════════════════════════════ --}}
        <div x-show="page === 'list'" x-cloak>

            {{-- Tab Bar --}}
            <div class="bg-white -mx-3 lg:-mx-6 px-3 lg:px-6 pt-2 anim-2">
                <div class="tab-bar">
                    <button class="tab-btn" :class="activeTab==='payroll-period' && 'active'"    @click="activeTab='payroll-period'">Payroll Period</button>
                    <button class="tab-btn" :class="activeTab==='salary-structure' && 'active'"  @click="activeTab='salary-structure'">Salary Structure</button>
                    <button class="tab-btn" :class="activeTab==='benefits' && 'active'"          @click="activeTab='benefits'">Benefits</button>
                    <button class="tab-btn" :class="activeTab==='contributions' && 'active'"     @click="activeTab='contributions'">Contributions</button>
                </div>
            </div>

            {{-- ── TAB 1: PAYROLL PERIOD ── --}}
            <div x-show="activeTab==='payroll-period'" x-cloak class=" tab-content">

                {{-- Summary Cards --}}
                <div class="pt-6 pb-4">
                    <div class="cards-wrap">
                        <div class="summary-card">
                            <div class="label">Gross Payroll</div>
                            <div class="value">₱ {{ number_format($grossPayroll) }}</div>
                            <div class="sub">{{ $latestPeriod->name ?? 'February 2026 Period 2' }}</div>
                        </div>
                        <div class="summary-card">
                            <div class="label">Net Pay</div>
                            <div class="value">₱ {{ number_format($netPay) }}</div>
                            <div class="sub">{{ $latestPeriod->name ?? 'February 2026 Period 2' }}</div>
                        </div>
                        <div class="summary-card">
                            <div class="label">Total Deductions</div>
                            <div class="value">₱ {{ number_format($totalDeductions) }}</div>
                            <div class="sub">{{ $latestPeriod->name ?? 'February 2026 Period 2' }}</div>
                        </div>
                        <div class="summary-card">
                            <div class="label">Days to Cutoff</div>
                            <div class="value">{{ $daysToCutoff }}</div>
                            @if($activePeriod)
                            <div class="sub">Cutoff: {{ \Carbon\Carbon::parse($activePeriod->start_date)->format('m/d/Y') }} – {{ \Carbon\Carbon::parse($activePeriod->end_date)->format('m/d/Y') }}</div>
                            @elseif($latestPeriod)
                            <div class="sub">Cutoff: {{ \Carbon\Carbon::parse($latestPeriod->start_date)->format('m/d/Y') }} – {{ \Carbon\Carbon::parse($latestPeriod->end_date)->format('m/d/Y') }}</div>
                            @else
                            <div class="sub">No active period</div>
                            @endif
                        </div>
                    </div>
                </div>

                {{-- Toolbar --}}
                <div class="toolbar-period mb-4 anim-2">
                    <div class="search-wrap">
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-4.35-4.35M17 11A6 6 0 105 11a6 6 0 0012 0z"/>
                        </svg>
                        <input type="text" placeholder="Search..." class="ctrl w-full" x-model="periodSearch">
                    </div>

                    <div class="right-group">
                        <select class="ctrl" x-model="periodStatusFilter">
                            <option value="">All status</option>
                            <option value="Pending">Pending</option>
                            <option value="Submitted">Submitted</option>
                            <option value="Released">Released</option>
                        </select>

                        <select class="ctrl" x-model="periodYearFilter">
                            @for($y = now()->year; $y >= now()->year - 3; $y--)
                            <option value="{{ $y }}">{{ $y }}</option>
                            @endfor
                        </select>

                        @canDo('Payroll', 'create')
                        <button class="btn-primary" @click="showAddPeriodModal=true">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                            </svg>
                            Add Payroll Period
                        </button>
                        @endcanDo
                    </div>
                </div>

                {{-- Table --}}
                <div class="bg-white rounded-xl border border-gray-100 shadow-sm overflow-hidden">
                    <div class="bg-white rounded-xl border border-gray-100 shadow-sm overflow-x-auto">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>Period Name</th><th>Start Date</th><th>End Date</th><th>Status</th><th style="text-align:center"></th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($periods as $period)
                                <tr x-show="
                                    (!periodSearch || '{{ strtolower($period->name) }}'.includes(periodSearch.toLowerCase())) &&
                                    (!periodStatusFilter || '{{ $period->status }}' === periodStatusFilter) &&
                                    (!periodYearFilter || '{{ \Carbon\Carbon::parse($period->start_date)->year }}' === periodYearFilter)
                                ">
                                    <td class="font-medium text-gray-700">{{ $period->name }}</td>
                                    <td class="text-gray-500">{{ \Carbon\Carbon::parse($period->start_date)->format('m/d/Y') }}</td>
                                    <td class="text-gray-500">{{ \Carbon\Carbon::parse($period->end_date)->format('m/d/Y') }}</td>
                                    <td>
                                        <span class="px-3 py-1 rounded-full text-xs font-medium
                                            @if($period->status==='Pending') badge-pending
                                            @elseif($period->status==='Submitted') badge-submitted
                                            @else badge-released @endif">
                                            {{ $period->status }}
                                        </span>
                                    </td>
                                    <td style="text-align:center">
                                        <div style="display:flex;align-items:center;justify-content:center;gap:6px;">
                                            <button class="btn-view"
                                                @click="openPeriodView(
                                                    {{ $period->id }},
                                                    '{{ addslashes($period->name) }}',
                                                    '{{ $period->start_date }}',
                                                    '{{ $period->end_date }}',
                                                    '{{ $period->status }}'
                                                )">View</button>
                                            <button class="btn-view"
                                                @click="openEditPeriod(
                                                    {{ $period->id }},
                                                    '{{ addslashes($period->name) }}',
                                                    '{{ $period->start_date }}',
                                                    '{{ $period->end_date }}',
                                                    '{{ $period->payout_date }}',
                                                    '{{ $period->status }}'
                                                )">Edit</button>
                                        </div>
                                    </td>
                                </tr>
                                @empty
                                <tr><td colspan="5" class="text-center py-12 text-gray-400 text-sm">No payroll periods found.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>{{-- /tab payroll-period --}}


            {{-- ── TAB 2: SALARY STRUCTURE ── --}}
            <div x-show="activeTab==='salary-structure'" x-cloak class="tab-content space-y-8">

                {{-- ── Salary Grade ── --}}
                <div>
                    <div class="pt-6 pb-4">
                        <div class="flex items-center justify-between mb-4">
                            <h3 class="text-base font-bold text-gray-800">Salary Grade</h3>
                            @canDo('Payroll', 'create')
                            <button class="btn-primary" @click="openAddGrade()">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                                Add Salary Grade
                            </button>
                            @endcanDo
                        </div>
                        <div class="bg-white rounded-xl border border-gray-100 shadow-sm overflow-hidden">
                            <div class="bg-white rounded-xl border border-gray-100 shadow-sm overflow-x-auto">
                                <table class="data-table">
                                    <thead>
                                        <tr>
                                            <th>Grade Code</th><th>Level Name</th><th>Monthly Basic Salary</th><th>Semi-Monthly Pay</th><th>Assigned Employees</th><th></th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse($salaryGrades ?? [] as $grade)
                                        <tr>
                                            <td><span class="inline-flex items-center px-2 py-0.5 rounded text-[0.7rem] sm:text-xs font-semibold bg-blue-50 text-blue-600 whitespace-nowrap"> {{ $grade->grade_code }}</span></td>
                                            <td class="text-gray-600">{{ $grade->level_name }}</td>
                                            <td class="font-medium text-gray-700">₱{{ number_format($grade->monthly_basic_salary, 2) }}</td>
                                            <td class="text-gray-600">₱{{ number_format($grade->monthly_basic_salary / 2, 2) }}</td>
                                            <td><span class="px-2 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-600">{{ $grade->employees_count ?? 0 }} employee{{ ($grade->employees_count ?? 0) !== 1 ? 's' : '' }}</span></td>
                                            <td class="text-right">
                                                <div class="flex items-center justify-end gap-1">
                                                    @canDo('Payroll', 'create')
                                                    <button class="btn-view"
                                                        data-emps="{{ json_encode($grade->employees->map(fn($e) => ['id' => $e->id, 'name' => $e->fname.' '.$e->lname])->values()) }}"
                                                        @click="openEditGrade({{ $grade->id }}, '{{ addslashes($grade->grade_code) }}', '{{ addslashes($grade->level_name) }}', {{ $grade->monthly_basic_salary }}, JSON.parse($el.getAttribute('data-emps')))">Edit</button>
                                                    <button class="btn-delete" @click="deleteGrade({{ $grade->id }})">Delete</button>
                                                    @endcanDo
                                                </div>
                                            </td>
                                        </tr>
                                        @empty
                                        <tr><td colspan="6" class="text-center py-12 text-gray-400 text-sm">No salary grades found.</td></tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                    {{-- ── Payroll Items ── --}}
                    <div>
                        <div class="flex items-center justify-between mb-4">
                            <h3 class="text-base font-bold text-gray-800">Payroll Items</h3>
                            <div class="flex items-center gap-3">
                                <select class="ctrl" x-model="itemStatusFilter"><option value="">Status</option><option value="Active">Active</option><option value="Inactive">Inactive</option></select>
                                @canDo('Payroll', 'create')
                                <button class="btn-primary" @click="showAddItemModal=true">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                                    Add Payroll Item
                                </button>
                                @endcanDo
                            </div>
                        </div>
                        <div class="bg-white rounded-xl border border-gray-100 shadow-sm overflow-hidden">
                            <div class="bg-white rounded-xl border border-gray-100 shadow-sm overflow-x-auto">
                                <table class="data-table">
                                    <thead>
                                        <tr>
                                            <th>Payroll Item</th><th>Multiplier</th><th>Type</th><th>Basis</th><th>Status</th><th></th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse($payrollItems as $item)
                                        <tr x-show="!itemStatusFilter || itemStatusFilter === '{{ $item->status }}'">
                                            <td class="font-medium text-gray-700">{{ $item->name }}</td>
                                            <td><span class="multiplier-badge">x{{ $item->multiplier }}</span></td>
                                            <td>
                                                <span class="px-3 py-1 rounded-full text-xs font-medium {{ strtolower($item->type)==='addition' ? 'badge-addition' : 'badge-deduction' }}">
                                                    {{ $item->type }}
                                                </span>
                                            </td>
                                            <td class="text-gray-500">{{ $item->basis }}</td>
                                            <td>
                                                <span class="px-3 py-1 rounded-full text-xs font-medium {{ $item->status==='Active' ? 'badge-active' : 'badge-inactive' }}">
                                                    {{ $item->status }}
                                                </span>
                                            </td>
                                            <td class="text-right">
                                                <div class="flex items-center justify-end gap-1">
                                                    @canDo('Payroll', 'create')
                                                    <button class="btn-view" @click="openEditItem({{ $item->id }}, '{{ addslashes($item->name) }}', {{ $item->multiplier }}, '{{ $item->type }}', '{{ $item->basis }}', '{{ $item->status }}')">Edit</button>
                                                    <button class="btn-delete" @click="deleteItem({{ $item->id }})">Delete</button>
                                                    @endcanDo
                                                </div>
                                            </td>
                                        </tr>
                                        @empty
                                        <tr><td colspan="6" class="text-center py-12 text-gray-400 text-sm">No payroll items found.</td></tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

            </div>{{-- /tab salary-structure --}}


            {{-- ── TAB 3: BENEFITS ── --}}
            <div x-show="activeTab==='benefits'" x-cloak class="tab-content">
                <div class="pt-6 pb-4">
                    <div class="flex items-center justify-between gap-3 mb-4">
                        <div class="search-wrap flex-1 max-w-xs">
                            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-4.35-4.35M17 11A6 6 0 105 11a6 6 0 0012 0z"/></svg>
                            <input type="text" placeholder="Search" class="ctrl w-full">
                        </div>
                        <div class="flex items-center gap-3">
                            <select class="ctrl" x-model="benefitStatusFilter"><option value="">Status</option><option value="Active">Active</option><option value="Inactive">Inactive</option></select>
                            @canDo('Payroll', 'create')
                            <button class="btn-primary" @click="showAddBenefitModal=true">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                                Add Benefit
                            </button>
                            @endcanDo
                        </div>
                    </div>
                    <div class="bg-white rounded-xl border border-gray-100 shadow-sm overflow-hidden">
                        <div class="bg-white rounded-xl border border-gray-100 shadow-sm overflow-x-auto">
                            <table class="data-table">
                                <thead>
                                    <tr>
                                        <th>Benefit Item</th><th>Type</th><th>Amount</th><th>Taxable</th><th>Frequency</th><th>Eligibility</th><th>Status</th><th></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($benefits as $benefit)
                                    <tr x-show="!benefitStatusFilter || benefitStatusFilter === '{{ $benefit->status }}'">
                                        <td class="font-medium text-gray-700">{{ $benefit->name }}</td>
                                        <td><span class="px-3 py-1 rounded-full text-xs font-medium badge-allowance">{{ $benefit->type }}</span></td>
                                        <td class="text-gray-600">₱{{ number_format($benefit->amount, 2) }}</td>
                                        <td>
                                            <span class="px-3 py-1 rounded-full text-xs font-medium {{ strtolower($benefit->tax)==='non-taxable' ? 'badge-nontaxable' : 'badge-taxable' }}">
                                                {{ $benefit->tax }}
                                            </span>
                                        </td>
                                        <td class="text-gray-500">{{ $benefit->frequency }}</td>
                                        <td class="text-gray-500">{{ $benefit->eligibility }}</td>
                                        <td>
                                            <span class="px-3 py-1 rounded-full text-xs font-medium {{ $benefit->status==='Active' ? 'badge-active' : 'badge-inactive' }}">
                                                {{ $benefit->status }}
                                            </span>
                                        </td>
                                        <td class="text-right">
                                            <div class="flex items-center justify-end gap-2">
                                                @canDo('Payroll', 'create')
                                                @if($benefit->status === 'Active')
                                                <button class="btn-view" @click="openAssignBenefit({{ $benefit->id }}, '{{ addslashes($benefit->name) }}')">Assign</button>
                                                @endif
                                                <button class="btn-view" @click="openEditBenefit({{ $benefit->id }}, '{{ addslashes($benefit->name) }}', '{{ $benefit->type }}', {{ $benefit->amount }}, '{{ $benefit->tax }}', '{{ $benefit->frequency }}', '{{ addslashes($benefit->eligibility) }}', '{{ $benefit->status }}')">Edit</button>
                                                <button class="btn-delete" @click="deleteBenefit({{ $benefit->id }})">Delete</button>
                                                @endcanDo
                                            </div>
                                        </td>
                                    </tr>
                                    @empty
                                    <tr><td colspan="8" class="text-center py-12 text-gray-400 text-sm">No benefits found.</td></tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>


            {{-- ── TAB 4: CONTRIBUTIONS ── --}}
            <div x-show="activeTab==='contributions'" x-cloak class="tab-content space-y-8">

                {{-- Rate Cards --}}
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                    {{-- SSS --}}
                    <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-5">
                        <div class="flex items-center justify-between mb-3">
                            <h4 class="text-sm font-bold text-gray-700 uppercase tracking-wide">SSS Rates</h4>
                            <button @click="openEditContrib('sss')" class="text-gray-400 hover:text-blue-500 transition-colors">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/></svg>
                            </button>
                        </div>
                        <div class="space-y-1.5 text-xs max-h-36 overflow-y-auto pr-1">
                            @forelse($sssRows as $row)
                            <div class="flex justify-between items-center py-0.5">
                                <span class="text-gray-500">
                                    ₱{{ number_format($row->salary_from, 2) }} –
                                    {{ $row->salary_to !== null ? '₱'.number_format($row->salary_to, 2) : 'above' }}
                                </span>
                                <span class="font-semibold text-orange-500">₱{{ number_format($row->employee_share, 2) }}</span>
                            </div>
                            @empty
                            <p class="text-gray-400 text-xs italic">No SSS brackets found. Click edit to add.</p>
                            @endforelse
                        </div>
                    </div>

                    {{-- PhilHealth --}}
                    <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-5">
                        <div class="flex items-center justify-between mb-4">
                            <h4 class="text-sm font-bold text-gray-700">PhilHealth Rates</h4>
                            <button @click="openEditContrib('philhealth')" class="text-gray-400 hover:text-blue-500 transition-colors">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/></svg>
                            </button>
                        </div>
                        <div class="space-y-2.5 text-sm">
                            <div class="flex justify-between"><span class="text-gray-500">Premium</span><span class="font-medium text-gray-700">{{ $contrib['philhealth_rate'] }}%</span></div>
                            <div class="flex justify-between"><span class="text-gray-500">Employee Share</span><span class="font-medium text-gray-700">{{ $contrib['philhealth_rate'] / 2 }}%</span></div>
                            <div class="flex justify-between"><span class="text-gray-500">Employer Share</span><span class="font-medium text-gray-700">{{ $contrib['philhealth_rate'] / 2 }}%</span></div>
                            <div class="flex justify-between pt-2.5 border-t border-gray-100"><span class="text-gray-500">Salary Floor</span><span class="font-medium text-gray-700">₱{{ number_format($contrib['philhealth_floor']) }}</span></div>
                            <div class="flex justify-between"><span class="text-gray-500">Salary Ceiling</span><span class="font-medium text-gray-700">₱{{ number_format($contrib['philhealth_ceiling']) }}</span></div>
                        </div>
                    </div>

                    {{-- Pag-IBIG --}}
                    <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-5">
                        <div class="flex items-center justify-between mb-4">
                            <h4 class="text-sm font-bold text-gray-700">PAG-IBIG</h4>
                            <button @click="openEditContrib('pagibig')" class="text-gray-400 hover:text-blue-500 transition-colors">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/></svg>
                            </button>
                        </div>
                        <div class="space-y-2.5 text-sm">
                            <div class="flex justify-between items-center"><span class="text-gray-500 text-xs">Fixed Amount:</span><span class="font-medium text-orange-500">₱{{ number_format($contrib['pagibig_high_amount']) }}/mo</span></div>
                            <div class="flex justify-between items-center pt-2.5 border-t border-gray-100"><span class="text-gray-500 text-xs">Per Payslip</span><span class="font-medium text-gray-700">₱{{ number_format($contrib['pagibig_high_amount'] / 2, 2) }}</span></div>
                        </div>
                    </div>

                    {{-- W/Tax --}}
                    <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-5">
                        <div class="flex items-center justify-between mb-4">
                            <h4 class="text-sm font-bold text-gray-700">WITHHOLDING TAX</h4>
                            <button @click="openEditContrib('wtax')" class="text-gray-400 hover:text-blue-500 transition-colors">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/></svg>
                            </button>
                        </div>
                        <div class="space-y-1.5 text-xs max-h-36 overflow-y-auto pr-1">
                            <div class="flex justify-between"><span class="text-gray-500">₱0 – ₱{{ number_format($contrib['wtax_bracket_1']) }}</span><span class="font-medium text-gray-700">₱0.00 + 0%</span></div>
                            <div class="flex justify-between"><span class="text-gray-500">₱{{ number_format($contrib['wtax_bracket_1']) }} – ₱{{ number_format($contrib['wtax_bracket_2']) }}</span><span class="font-medium text-gray-700">{{ $contrib['wtax_rate_1'] }}% (Excess over ₱{{ number_format($contrib['wtax_bracket_1']) }})</span></div>
                            <div class="flex justify-between"><span class="text-gray-500">₱{{ number_format($contrib['wtax_bracket_2']) }} – ₱{{ number_format($contrib['wtax_bracket_3']) }}</span><span class="font-medium text-gray-700">{{ $contrib['wtax_rate_2'] }}% (Excess over ₱{{ number_format($contrib['wtax_bracket_2']) }})</span></div>
                            <div class="flex justify-between"><span class="text-gray-500">₱{{ number_format($contrib['wtax_bracket_3']) }} – ₱{{ number_format($contrib['wtax_bracket_4']) }}</span><span class="font-medium text-gray-700">{{ $contrib['wtax_rate_3'] }}% (Excess over ₱{{ number_format($contrib['wtax_bracket_3']) }})</span></div>
                            <div class="flex justify-between"><span class="text-gray-500">₱{{ number_format($contrib['wtax_bracket_4']) }} – ₱{{ number_format($contrib['wtax_bracket_5']) }}</span><span class="font-medium text-gray-700">{{ $contrib['wtax_rate_4'] }}% (Excess over ₱{{ number_format($contrib['wtax_bracket_4']) }})</span></div>
                            <div class="flex justify-between"><span class="text-gray-500">₱{{ number_format($contrib['wtax_bracket_5']) }}+</span><span class="font-medium text-gray-700">{{ $contrib['wtax_rate_5'] }}% (Excess over ₱{{ number_format($contrib['wtax_bracket_5']) }})</span></div>
                        </div>
                    </div>
                </div>

                {{-- Contribution Rates by Salary Grade --}}
                <div>
                    <h3 class="text-base font-bold text-gray-800 mb-4">Contribution Rates by Salary Grade</h3>
                    <div class="bg-white rounded-xl border border-gray-100 shadow-sm overflow-x-auto">
                        <table class="data-table" style="min-width:900px">
                            <thead>
                                <tr>
                                    <th>Grade Code</th><th>Level Name</th><th>Monthly Basic Salary</th>
                                    <th>SSS (Employee)</th><th>SSS (Employer)</th><th>PhilHealth</th>
                                    <th>Pag-IBIG</th><th>Monthly W/Tax</th>
                                    <th>Total Deduction / Month</th><th>Total Deduction / Cut-off</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($salaryGrades as $grade)
                                @php
                                    $sal    = (float) $grade->monthly_basic_salary;

                                    // SSS — table-based lookup
                                    $sssRow = $sssRows->first(fn($r) => $sal >= $r->salary_from && ($r->salary_to === null || $sal <= $r->salary_to));
                                    $sssEmp = $sssRow ? (float) $sssRow->employee_share : 0;
                                    $sssEmr = $sssRow ? (float) $sssRow->employer_share : 0;

                                    // PhilHealth — percentage, employee half, monthly
                                    $phBase = max((float) $contrib['philhealth_floor'], min($sal, (float) $contrib['philhealth_ceiling']));
                                    $ph     = round($phBase * ((float) $contrib['philhealth_rate'] / 100 / 2), 2);

                                    // Pag-IBIG — flat fixed monthly amount
                                    $pi = (float) $contrib['pagibig_high_amount'];

                                    // W/Tax — TRAIN Law 6-bracket annualized
                                    $b1 = (float) $contrib['wtax_bracket_1']; $b2 = (float) $contrib['wtax_bracket_2'];
                                    $b3 = (float) $contrib['wtax_bracket_3']; $b4 = (float) $contrib['wtax_bracket_4'];
                                    $b5 = (float) $contrib['wtax_bracket_5'];
                                    $r1 = (float) $contrib['wtax_rate_1']/100; $r2 = (float) $contrib['wtax_rate_2']/100;
                                    $r3 = (float) $contrib['wtax_rate_3']/100; $r4 = (float) $contrib['wtax_rate_4']/100;
                                    $r5 = (float) $contrib['wtax_rate_5']/100;
                                    $annualDeductions = ($sssEmp + $ph + $pi) * 12;
                                    $annualTaxable    = max(0, ($sal * 12) - $annualDeductions);
                                    if ($annualTaxable <= $b1)      $wt = 0;
                                    elseif ($annualTaxable <= $b2)  $wt = ($annualTaxable - $b1) * $r1;
                                    elseif ($annualTaxable <= $b3)  $wt = ($b2-$b1)*$r1 + ($annualTaxable-$b2)*$r2;
                                    elseif ($annualTaxable <= $b4)  $wt = ($b2-$b1)*$r1 + ($b3-$b2)*$r2 + ($annualTaxable-$b3)*$r3;
                                    elseif ($annualTaxable <= $b5)  $wt = ($b2-$b1)*$r1 + ($b3-$b2)*$r2 + ($b4-$b3)*$r3 + ($annualTaxable-$b4)*$r4;
                                    else                            $wt = ($b2-$b1)*$r1 + ($b3-$b2)*$r2 + ($b4-$b3)*$r3 + ($b5-$b4)*$r4 + ($annualTaxable-$b5)*$r5;
                                    $wt = round($wt / 12, 2);
                                    $totalMonth = $sssEmp + $ph + $pi + $wt;
                                @endphp
                                <tr>
                                   <td><span class="inline-flex items-center px-2 py-0.5 rounded text-[0.7rem] sm:text-xs font-semibold bg-blue-50 text-blue-600 whitespace-nowrap"> {{ $grade->grade_code }}</span></td>
                                    <td class="text-gray-600">{{ $grade->level_name }}</td>
                                    <td class="font-medium text-gray-700">₱{{ number_format($sal, 2) }}</td>
                                    <td class="text-gray-600">₱{{ number_format($sssEmp, 2) }}</td>
                                    <td class="text-gray-600">₱{{ number_format($sssEmr, 2) }}</td>
                                    <td class="text-gray-600">₱{{ number_format($ph, 2) }}</td>
                                    <td class="text-gray-600">₱{{ number_format($pi, 2) }}</td>
                                    <td class="text-gray-600">₱{{ number_format($wt, 2) }}</td>
                                    <td class="font-medium text-gray-700">₱{{ number_format($totalMonth, 2) }}</td>
                                    <td class="font-medium text-gray-700">₱{{ number_format($totalMonth / 2, 2) }}</td>
                                </tr>
                                @empty
                                <tr><td colspan="10" class="text-center py-12 text-gray-400 text-sm">No salary grades found.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

            </div>{{-- /tab contributions --}}

        </div>{{-- /page list --}}


        {{-- ══════════════════════════════════════
             PAGE: VIEW PAYROLL PERIOD
        ══════════════════════════════════════ --}}
        <div x-show="page === 'view'" x-cloak>

            <div class="pt-5 pb-2 anim-1">
                <div class="flex items-center gap-3 mb-5">
                    <button class="btn-back" @click="closePeriodView()">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                        Back to Payroll Period
                    </button>
                    <span class="text-gray-300">|</span>
                    <nav class="flex items-center gap-1.5 text-sm text-gray-400">
                        <span>Payroll</span>
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                        <span>Payroll Period</span>
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                        <span class="font-semibold text-gray-700" x-text="viewPeriod.name"></span>
                    </nav>
                </div>

                <div class="px-8 flex items-center justify-between mb-6">
                    <h2 class="text-2xl font-bold text-gray-800" x-text="viewPeriod.name"></h2>
                    <div class="flex items-center gap-2">
                        <template x-if="viewPeriod.status === 'Pending'">
                            <button class="btn-primary" @click="adminSubmitPeriod()">
                                Submit for Approval
                            </button>
                        </template>
                        <template x-if="viewPeriod.status === 'Submitted'">
                            <button class="btn-primary" @click="showReleaseConfirm=true">
                                Release Payroll
                            </button>
                        </template>
                        <template x-if="viewPeriod.status === 'Released'">
                            <button @click="pvExportAllPayslips()"
                                    :disabled="pvPayslips.length === 0"
                                    :style="pvPayslips.length === 0 ? 'opacity:0.45;cursor:not-allowed;' : ''"
                                    class="flex items-center gap-2 px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-semibold rounded-lg border-none cursor-pointer transition-colors">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 16v4a2 2 0 01-2 2H7a2 2 0 01-2-2V4a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414A1 1 0 0119 8.414V12M12 10v6m0 0l-3-3m3 3l3-3"/></svg>
                                Export All PDF
                            </button>
                        </template>
                    </div>
                </div>
            </div>

            {{-- Status + Summary Cards --}}
            <div class=" mb-6 anim-2">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-5">
                        <h3 class="text-sm font-semibold text-gray-700 mb-4">Payroll Processing Status</h3>
                        <div class="space-y-4">
                            <div>
                                <div class="flex justify-between mb-1">
                                    <span class="text-sm text-gray-500">Payslips Submitted by Payroll Officer</span>
                                    <span class="text-sm font-medium text-gray-700"><span x-text="pvSubmittedCount"></span>/<span x-text="pvTotalCount"></span></span>
                                </div>
                                <div class="progress-track"><div class="progress-fill" :style="'width:' + (pvTotalCount > 0 ? (pvSubmittedCount/pvTotalCount*100) : 0) + '%'"></div></div>
                            </div>
                            <div>
                                <div class="flex justify-between mb-1">
                                    <span class="text-sm text-gray-500">Payroll Received for Review</span>
                                    <span class="text-sm font-medium text-gray-700" x-text="(viewPeriod.status==='Submitted'||viewPeriod.status==='Released') ? '1/1' : '0/1'"></span>
                                </div>
                                <div class="progress-track"><div class="progress-fill" :style="'width:' + ((viewPeriod.status==='Submitted'||viewPeriod.status==='Released') ? 100 : 0) + '%'"></div></div>
                            </div>
                            <div>
                                <div class="flex justify-between mb-1">
                                    <span class="text-sm text-gray-500">Payroll Released</span>
                                    <span class="text-sm font-medium text-gray-700" x-text="viewPeriod.status==='Released' ? '1/1' : '0/1'"></span>
                                </div>
                                <div class="progress-track"><div class="progress-fill" :style="'width:' + (viewPeriod.status==='Released' ? 100 : 0) + '%'"></div></div>
                            </div>
                        </div>
                    </div>
                    <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-5">
                        <h3 class="text-sm font-semibold text-gray-700 mb-4">Payroll Summary</h3>
                        <div class="space-y-3">
                            <div class="flex justify-between items-center"><span class="text-sm text-gray-500">Gross Payroll</span><span class="text-sm font-medium text-gray-700">₱ <span x-text="fmt(pvGrossPayroll)"></span></span></div>
                            <div class="flex justify-between items-center"><span class="text-sm text-gray-500">Total Deductions</span><span class="text-sm font-medium text-gray-700">₱ <span x-text="fmt(pvTotalDeductions)"></span></span></div>
                            <div class="border-t border-gray-100 pt-3 flex justify-between items-center">
                                <span class="text-sm font-semibold text-gray-700">Net Payroll</span>
                                <span class="text-base font-bold text-gray-900">₱ <span x-text="fmt(pvNetPayroll)"></span></span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Employee Payroll Table + Payslip Panel --}}
            <div class=" pb-10 anim-3">
                <div class="flex flex-col md:flex-row gap-4 items-start">

                    {{-- Employee Table --}}
                    <div class="flex-1 min-w-0">
                        <h3 class="text-base font-bold text-gray-800 mb-3">Employee Payroll</h3>
                        <div class="bg-white rounded-xl border border-gray-100 shadow-sm overflow-x-auto">
                        <table class="pv-table w-full">
                                <thead><tr><th>Employee</th><th>Gross Pay</th><th>Net Pay</th><th>Status</th><th></th></tr></thead>
                                <tbody>
                                    <template x-for="ps in pvPayslips" :key="ps.id">
                                        <tr :class="pvSelectedId===ps.id&&'row-active'" @click="pvSelectPayslip(ps)">
                                            <td class="font-medium text-gray-700" x-text="ps.employeeName"></td>
                                            <td class="text-gray-600">₱<span x-text="fmt(ps.grossPay)"></span></td>
                                            <td class="text-gray-600">₱<span x-text="fmt(ps.netPay)"></span></td>
                                            <td>
                                                <span class="px-3 py-1 rounded-full text-xs font-medium"
                                                    :class="ps.status==='Released' ? 'badge-released' : (ps.status==='Submitted' ? 'badge-submitted' : 'badge-pending')"
                                                    x-text="ps.status"></span>
                                            </td>
                                            <td class="text-right"><button class="btn-view" @click.stop="pvSelectPayslip(ps)">View</button></td>
                                        </tr>
                                    </template>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    {{-- Payslip Detail Panel --}}
                    <div class="w-full md:w-80 flex-shrink-0" x-show="pvSelectedId !== null" x-cloak>
                        <div class="bg-white rounded-xl border border-gray-100 shadow-sm overflow-hidden slide-in-right">
                            <div class="payslip-header">
                                <div class="text-lg font-bold mb-0.5">Medisource</div>
                                <div class="text-xs text-blue-100 mb-4" x-text="pvPeriodSubtitle"></div>
                                <div class="grid grid-cols-3 gap-1">
                                    <div><div class="info-label">Employee</div><div class="info-value" x-text="pvActive.employeeName||'—'"></div></div>
                                    <div><div class="info-label">Job Title</div><div class="info-value" x-text="pvActive.jobTitle||'—'"></div></div>
                                    <div><div class="info-label">Department</div><div class="info-value" x-text="pvActive.department||'—'"></div></div>
                                </div>
                            </div>
                            <div class="payslip-body">
                                <div class="payslip-section-title">Earnings</div>
                                <div class="payslip-line"><span>Basic Pay</span><span x-text="'₱ '+fmt(pvActive.basicPay||0)"></span></div>
                                <div class="payslip-line"><span>OT Pay</span><span x-text="'₱ '+fmt(pvActive.otPay||0)"></span></div>
                                <div class="payslip-line"><span>Benefits</span><span x-text="'₱ '+fmt(pvActive.benefits||0)"></span></div>
                                <div class="payslip-line bold"><span>Gross Pay</span><span x-text="'₱ '+fmt(pvActive.grossPay||0)"></span></div>
                                <div class="payslip-section-title">Deductions</div>
                                <div class="payslip-line"><span>SSS</span><span class="text-red-500" x-text="'-₱ '+fmt(pvActive.sss||0)"></span></div>
                                <div class="payslip-line"><span>PhilHealth</span><span class="text-red-500" x-text="'-₱ '+fmt(pvActive.philhealth||0)"></span></div>
                                <div class="payslip-line"><span>Pag-IBIG</span><span class="text-red-500" x-text="'-₱ '+fmt(pvActive.pagibig||0)"></span></div>
                                <div class="payslip-line"><span>Withholding Tax</span><span class="text-red-500" x-text="'-₱ '+fmt(pvActive.withholdingTax||0)"></span></div>
                                <div class="payslip-line bold"><span>Total Deductions</span><span class="text-red-500" x-text="'-₱ '+fmt(pvActive.totalDeductions||0)"></span></div>
                                <div class="payslip-section-title">Calculation</div>
                                <div class="payslip-line"><span>Earnings</span><span x-text="'₱ '+fmt(pvActive.grossPay||0)"></span></div>
                                <div class="payslip-line"><span>Deductions</span><span class="text-red-500" x-text="'-₱ '+fmt(pvActive.totalDeductions||0)"></span></div>
                                <div class="payslip-line bold"><span>Net Pay</span><span x-text="'₱ '+fmt(pvActive.netPay||0)"></span></div>
                                <div class="flex gap-2 mt-5 flex-wrap">
                                    <button class="btn-close-ps" @click="pvSelectedId=null;pvActive={}">Close</button>
                                    <template x-if="viewPeriod.status === 'Pending' && pvActive.status !== 'Submitted'">
                                        <button style="flex:1;padding:8px;border-radius:8px;font-size:12px;font-weight:600;font-family:inherit;cursor:pointer;border:1.5px solid #e5e7eb;background:#fff;color:#374151;" @click="adminEditPayslip()">Edit</button>
                                    </template>
                                    <template x-if="viewPeriod.status === 'Pending' && pvActive.status !== 'Submitted'">
                                        <button style="flex:1;padding:8px;border-radius:8px;font-size:12px;font-weight:600;font-family:inherit;cursor:pointer;border:none;background:#dbeafe;color:#1d4ed8;" @click="adminSubmitPayslip()">Submit</button>
                                    </template>
                                    <template x-if="viewPeriod.status === 'Released'">
                                        <button class="btn-export-ps" @click="exportPayslip(pvActive)">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                                            Export PDF
                                        </button>
                                    </template>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

        </div>{{-- /page view --}}

    </div>{{-- /main-content --}}


    {{-- ════════════════════════════════════════════
         MODALS
    ════════════════════════════════════════════ --}}

    {{-- ── Centered Error/Info Dialog ── --}}
    <div x-show="alertModal.show" x-cloak
         class="fixed inset-0 z-[999] flex items-center justify-center modal-overlay"
         x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100" x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0">
        <div class="bg-white rounded-2xl shadow-2xl w-full max-w-sm mx-4 p-8 text-center"
             x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 scale-95"
             x-transition:enter-end="opacity-100 scale-100">
            <div class="w-12 h-12 rounded-full flex items-center justify-center mx-auto mb-4"
                 :class="alertModal.type==='error' ? 'bg-red-50' : 'bg-green-50'">
                <svg class="w-6 h-6" :class="alertModal.type==='error' ? 'text-red-500' : 'text-green-500'"
                     fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <template x-if="alertModal.type==='error'">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/>
                    </template>
                    <template x-if="alertModal.type!=='error'">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </template>
                </svg>
            </div>
            <p class="text-gray-800 font-semibold text-base mb-2" x-text="alertModal.title"></p>
            <p class="text-gray-500 text-sm mb-6" x-text="alertModal.message"></p>
            <button @click="alertModal.show=false"
                    class="w-full py-2.5 rounded-xl text-sm font-semibold text-white transition"
                    :class="alertModal.type==='error' ? 'bg-red-500 hover:bg-red-600' : 'bg-green-500 hover:bg-green-600'">
                OK
            </button>
        </div>
    </div>

    {{-- Server-side error from redirect --}}
    @if(session('error'))
    <div x-data="{ show: true }" x-show="show" x-cloak
         class="fixed inset-0 z-[999] flex items-center justify-center modal-overlay"
         x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100">
        <div class="bg-white rounded-2xl shadow-2xl w-full max-w-sm mx-4 p-8 text-center"
             x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 scale-95"
             x-transition:enter-end="opacity-100 scale-100">
            <div class="w-12 h-12 bg-red-50 rounded-full flex items-center justify-center mx-auto mb-4">
                <svg class="w-6 h-6 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/>
                </svg>
            </div>
            <p class="text-gray-800 font-semibold text-base mb-2">Action Not Allowed</p>
            <p class="text-gray-500 text-sm mb-6">{{ session('error') }}</p>
            <button @click="show=false" class="w-full py-2.5 rounded-xl text-sm font-semibold text-white bg-red-500 hover:bg-red-600 transition">OK</button>
        </div>
    </div>
    @endif

    {{-- Edit Payslip Modal --}}
    <div x-show="showEditPayslipModal" x-cloak class="fixed inset-0 z-50 flex items-center justify-center modal-overlay" @click.self="showEditPayslipModal=false">
        <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md mx-4 p-8"
             x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100">
            <div class="flex items-center justify-between mb-5">
                <h3 class="text-lg font-bold text-gray-900">Edit Payslip — <span x-text="editPayslip.employeeName"></span></h3>
                <button @click="showEditPayslipModal=false" class="w-8 h-8 flex items-center justify-center rounded-full border border-gray-300 text-gray-500 hover:bg-gray-50">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>
            <div class="space-y-3">
                <div class="text-xs font-bold text-gray-500 uppercase tracking-wide mb-1">Earnings</div>
                <div class="grid grid-cols-3 gap-3">
                    <div><label class="block text-xs text-gray-500 mb-1">Basic Pay</label><input type="number" step="0.01" min="0" class="ctrl w-full" x-model.number="editPayslip.basicPay"></div>
                    <div><label class="block text-xs text-gray-500 mb-1">OT Pay</label><input type="number" step="0.01" min="0" class="ctrl w-full" x-model.number="editPayslip.otPay"></div>
                    <div><label class="block text-xs text-gray-500 mb-1">Benefits</label><input type="number" step="0.01" min="0" class="ctrl w-full" x-model.number="editPayslip.benefits"></div>
                </div>
                <div class="text-xs font-bold text-gray-500 uppercase tracking-wide mb-1 mt-3">Deductions</div>
                <div class="grid grid-cols-2 gap-3">
                    <div><label class="block text-xs text-gray-500 mb-1">SSS</label><input type="number" step="0.01" min="0" class="ctrl w-full" x-model.number="editPayslip.sss"></div>
                    <div><label class="block text-xs text-gray-500 mb-1">PhilHealth</label><input type="number" step="0.01" min="0" class="ctrl w-full" x-model.number="editPayslip.philhealth"></div>
                    <div><label class="block text-xs text-gray-500 mb-1">Pag-IBIG</label><input type="number" step="0.01" min="0" class="ctrl w-full" x-model.number="editPayslip.pagibig"></div>
                    <div><label class="block text-xs text-gray-500 mb-1">Withholding Tax</label><input type="number" step="0.01" min="0" class="ctrl w-full" x-model.number="editPayslip.withholdingTax"></div>
                </div>
            </div>
            <div class="flex justify-end gap-3 mt-6">
                <button type="button" @click="showEditPayslipModal=false" class="px-4 py-2 text-sm font-semibold text-gray-600 border border-gray-200 rounded-lg hover:bg-gray-50">Cancel</button>
                <button type="button" @click="saveEditPayslip()" class="px-4 py-2 text-sm font-semibold text-white bg-blue-600 rounded-lg hover:bg-blue-700">Save Changes</button>
            </div>
        </div>
    </div>

    {{-- Edit Payroll Period --}}
    <div x-show="showEditPeriodModal" x-cloak class="fixed inset-0 z-50 flex items-center justify-center modal-overlay" @click.self="showEditPeriodModal=false">
        <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md mx-4 p-8"
             x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100">
            <div class="flex items-center justify-between mb-6">
                <h3 class="text-lg font-bold text-gray-900">Edit Payroll Period</h3>
                <button @click="showEditPeriodModal=false" class="w-8 h-8 flex items-center justify-center rounded-full border border-gray-300 text-gray-500 hover:bg-gray-50">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>
            <div class="space-y-4">
                <div>
                    <label class="block text-xs font-semibold text-gray-500 mb-1">Period Name</label>
                    <input type="text" class="ctrl w-full" x-model="editPeriod.name" placeholder="e.g. April 2026 – 1st Half" required maxlength="200">
                </div>
                <template x-if="editPeriod.status === 'Pending'">
                    <div class="space-y-4">
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-semibold text-gray-500 mb-1">Start Date</label>
                                <input type="date" class="ctrl w-full" x-model="editPeriod.startDate">
                            </div>
                            <div>
                                <label class="block text-xs font-semibold text-gray-500 mb-1">End Date</label>
                                <input type="date" class="ctrl w-full" x-model="editPeriod.endDate">
                            </div>
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-gray-500 mb-1">Payout Date</label>
                            <input type="date" class="ctrl w-full" x-model="editPeriod.payoutDate">
                        </div>
                    </div>
                </template>
                <template x-if="editPeriod.status !== 'Pending'">
                    <p class="text-xs text-amber-600 bg-amber-50 border border-amber-200 rounded-lg px-3 py-2">
                        Start date, end date, and payout date cannot be changed once the period has been submitted or released.
                    </p>
                </template>
            </div>
            <div class="flex justify-end gap-3 mt-6">
                <button type="button" @click="showEditPeriodModal=false" class="btn-outline">Cancel</button>
                <button type="button" @click="savePeriodEdit()" class="btn-primary" :disabled="!editPeriod.name.trim()" :class="{ 'opacity-50 cursor-not-allowed': !editPeriod.name.trim() }">Save Changes</button>
            </div>
        </div>
    </div>

    {{-- Create Payroll Period --}}
    <div x-show="showCreatePeriod" x-cloak class="fixed inset-0 z-50 flex items-center justify-center modal-overlay" @click.self="showCreatePeriod=false">
        <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md mx-4 p-8"
             x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100">
            <div class="flex items-center justify-between mb-6">
                <h3 class="text-lg font-bold text-gray-900">Create Payroll Period</h3>
                <button @click="showCreatePeriod=false" class="w-8 h-8 flex items-center justify-center rounded-full border border-gray-300 text-gray-500 hover:bg-gray-50">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>
            <form method="POST" action="{{ route('admin.payroll.period.store') }}">
                @csrf
                <div class="space-y-4">
                    <div>
                        <label class="block text-xs font-semibold text-gray-500 mb-1">Period Name</label>
                        <input type="text" name="name" class="ctrl w-full" placeholder="e.g. March 2026 – 1st Half" required>
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-semibold text-gray-500 mb-1">Start Date</label>
                            <input type="date" name="start_date" class="ctrl w-full" required>
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-gray-500 mb-1">End Date</label>
                            <input type="date" name="end_date" class="ctrl w-full" required>
                        </div>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-gray-500 mb-1">Payout Date</label>
                        <input type="date" name="payout_date" class="ctrl w-full" required>
                    </div>
                </div>
                <div class="flex justify-end gap-3 mt-6">
                    <button type="button" @click="showCreatePeriod=false" class="px-4 py-2 text-sm font-semibold text-gray-600 border border-gray-200 rounded-lg hover:bg-gray-50">Cancel</button>
                    <button type="submit" class="px-4 py-2 text-sm font-semibold text-white bg-blue-600 rounded-lg hover:bg-blue-700">Create & Generate Payslips</button>
                </div>
            </form>
        </div>
    </div>

    {{-- Submit Period Confirm --}}
    <div x-show="showSubmitConfirm" x-cloak class="fixed inset-0 z-50 flex items-center justify-center modal-overlay" @click.self="showSubmitConfirm=false">
        <div class="bg-white rounded-2xl shadow-2xl w-full max-w-sm mx-4 p-8 text-center"
             x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100">
            <div class="w-12 h-12 bg-blue-50 rounded-full flex items-center justify-center mx-auto mb-4">
                <svg class="w-6 h-6 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
            </div>
            <p class="text-gray-700 font-semibold text-base mb-2">Submit for Approval?</p>
            <p class="text-gray-400 text-sm mb-6">This will submit the payroll period for finance approval.</p>
            <div class="flex justify-center gap-3">
                <button @click="showSubmitConfirm=false" class="btn-outline px-8">Cancel</button>
                <form :action="`/admin/payroll/period/${viewPeriod.id}/submit`" method="POST">
                    @csrf
                    <button type="submit" class="btn-primary px-8">Confirm</button>
                </form>
            </div>
        </div>
    </div>

    {{-- Submit Payslip Confirm --}}
    <div x-show="showSubmitPayslipConfirm" x-cloak class="fixed inset-0 z-50 flex items-center justify-center modal-overlay" @click.self="showSubmitPayslipConfirm=false">
        <div class="bg-white rounded-2xl shadow-2xl w-full max-w-sm mx-4 p-8 text-center"
             x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100">
            <div class="w-12 h-12 bg-blue-50 rounded-full flex items-center justify-center mx-auto mb-4">
                <svg class="w-6 h-6 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
            </div>
            <p class="text-gray-700 font-semibold text-base mb-2">Submit Payslip?</p>
            <p class="text-gray-400 text-sm mb-6">Submit payslip for <span class="font-medium text-gray-600" x-text="pvActive.employeeName"></span>?</p>
            <div class="flex justify-center gap-3">
                <button @click="showSubmitPayslipConfirm=false" class="btn-outline px-8">Cancel</button>
                <button @click="adminSubmitPayslipConfirmed()" class="btn-primary px-8">Confirm</button>
            </div>
        </div>
    </div>

    {{-- Release Confirm --}}
    <div x-show="showReleaseConfirm" x-cloak class="fixed inset-0 z-50 flex items-center justify-center modal-overlay" @click.self="showReleaseConfirm=false">
        <div class="bg-white rounded-2xl shadow-2xl w-full max-w-sm mx-4 p-8 text-center"
             x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100">
            <div class="w-12 h-12 bg-green-50 rounded-full flex items-center justify-center mx-auto mb-4">
                <svg class="w-6 h-6 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
            </div>
            <p class="text-gray-700 font-semibold text-base mb-2">Release Payroll?</p>
            <p class="text-gray-400 text-sm mb-6">This will release the payroll and make all payslips available to employees. This cannot be undone.</p>
            <div class="flex justify-center gap-3">
                <button @click="showReleaseConfirm=false" class="btn-outline px-8">Cancel</button>
                <form :action="`/admin/payroll/period/${viewPeriod.id}/release`" method="POST">
                    @csrf
                    <button type="submit" class="btn-primary px-8">Confirm Release</button>
                </form>
            </div>
        </div>
    </div>

    {{-- Add Payroll Item --}}
    <div x-show="showAddItemModal" x-cloak class="fixed inset-0 z-50 flex items-center justify-center modal-overlay" @click.self="showAddItemModal=false">
        <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md mx-4 p-7"
             x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100">
            <div class="flex items-center justify-between mb-6">
                <h2 class="text-lg font-bold text-gray-800">Add Payroll Item</h2>
                <button @click="showAddItemModal=false" class="text-gray-400 hover:text-gray-600"><svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg></button>
            </div>
            <form action="{{ route('admin.payroll.item.store') }}" method="POST" class="space-y-4">
                @csrf
                <div><label class="block text-sm font-medium text-gray-700 mb-1.5">Payroll Item</label><input type="text" name="name" required placeholder="Enter payroll item name" class="ctrl w-full"></div>
                <div class="grid grid-cols-2 gap-4">
                    <div><label class="block text-sm font-medium text-gray-700 mb-1.5">Multiplier</label><input type="number" name="multiplier" step="0.01" required placeholder="e.g. 1.25" class="ctrl w-full"></div>
                    <div><label class="block text-sm font-medium text-gray-700 mb-1.5">Type</label><select name="type" required class="ctrl w-full"><option value="">Choose type</option><option value="Addition">Addition</option><option value="Deduction">Deduction</option></select></div>
                </div>
                <div><label class="block text-sm font-medium text-gray-700 mb-1.5">Basis</label><input type="text" name="basis" required placeholder="Enter basis" class="ctrl w-full"></div>
                <div class="flex justify-end gap-3 pt-2">
                    <button type="button" @click="showAddItemModal=false" class="btn-outline">Cancel</button>
                    <button type="submit" class="btn-primary">Submit</button>
                </div>
            </form>
        </div>
    </div>

    {{-- Edit Payroll Item --}}
    <div x-show="showEditItemModal" x-cloak class="fixed inset-0 z-50 flex items-center justify-center modal-overlay" @click.self="showEditItemModal=false">
        <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md mx-4 p-7"
             x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100">
            <div class="flex items-center justify-between mb-6">
                <h2 class="text-lg font-bold text-gray-800">Edit Payroll Item</h2>
                <button @click="showEditItemModal=false" class="text-gray-400 hover:text-gray-600"><svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg></button>
            </div>
            <form :action="`/admin/payroll/item/${editItem.id}/update`" method="POST" class="space-y-4">
                @csrf @method('PUT')
                <div><label class="block text-sm font-medium text-gray-700 mb-1.5">Payroll Item</label><input type="text" name="name" x-model="editItem.name" required class="ctrl w-full"></div>
                <div class="grid grid-cols-2 gap-4">
                    <div><label class="block text-sm font-medium text-gray-700 mb-1.5">Multiplier</label><input type="number" name="multiplier" step="0.01" x-model="editItem.multiplier" required class="ctrl w-full"></div>
                    <div><label class="block text-sm font-medium text-gray-700 mb-1.5">Type</label><select name="type" x-model="editItem.type" required class="ctrl w-full"><option value="Addition">Addition</option><option value="Deduction">Deduction</option></select></div>
                </div>
                <div><label class="block text-sm font-medium text-gray-700 mb-1.5">Basis</label><input type="text" name="basis" x-model="editItem.basis" required class="ctrl w-full"></div>
                <div class="flex items-center justify-between pt-2">
                    <button type="button" @click="deactivateItem()" class="text-blue-500 border border-blue-200 px-4 py-2 rounded-lg text-sm font-medium hover:bg-blue-50 transition" x-text="editItem.status==='Active' ? 'Deactivate' : 'Activate'"></button>
                    <div class="flex gap-3">
                        <button type="button" @click="showEditItemModal=false" class="btn-outline">Cancel</button>
                        <button type="submit" class="btn-primary">Save</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    {{-- Add Benefit --}}
    <div x-show="showAddBenefitModal" x-cloak class="fixed inset-0 z-50 flex items-center justify-center modal-overlay" @click.self="showAddBenefitModal=false">
        <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md mx-4 p-7"
             x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100">
            <div class="flex items-center justify-between mb-6">
                <h2 class="text-lg font-bold text-gray-800">Add Benefit</h2>
                <button @click="showAddBenefitModal=false" class="text-gray-400 hover:text-gray-600"><svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg></button>
            </div>
            <form action="{{ route('admin.payroll.benefit.store') }}" method="POST" class="space-y-4">
                @csrf
                <div><label class="block text-sm font-medium text-gray-700 mb-1.5">Benefit Name</label><input type="text" name="name" required placeholder="Enter benefit name" class="ctrl w-full"></div>
                <div class="grid grid-cols-2 gap-4">
                    <div><label class="block text-sm font-medium text-gray-700 mb-1.5">Type</label><select name="type" required class="ctrl w-full"><option value="">Choose type</option><option value="Allowance">Allowance</option><option value="Bonus">Bonus</option><option value="Incentive">Incentive</option></select></div>
                    <div><label class="block text-sm font-medium text-gray-700 mb-1.5">Amount</label><input type="number" name="amount" step="0.01" required placeholder="₱0.00" class="ctrl w-full"></div>
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div><label class="block text-sm font-medium text-gray-700 mb-1.5">Tax</label><select name="tax" required class="ctrl w-full"><option value="Non-taxable">Non-taxable</option><option value="Taxable">Taxable</option></select></div>
                    <div><label class="block text-sm font-medium text-gray-700 mb-1.5">Frequency</label><input type="text" name="frequency" required placeholder="e.g. Monthly" class="ctrl w-full"></div>
                </div>
                <div><label class="block text-sm font-medium text-gray-700 mb-1.5">Eligibility</label><input type="text" name="eligibility" required placeholder="e.g. All regular employees" class="ctrl w-full"></div>
                <div class="flex justify-end gap-3 pt-2">
                    <button type="button" @click="showAddBenefitModal=false" class="btn-outline">Cancel</button>
                    <button type="submit" class="btn-primary">Submit</button>
                </div>
            </form>
        </div>
    </div>

    {{-- Edit Benefit --}}
    <div x-show="showEditBenefitModal" x-cloak class="fixed inset-0 z-50 flex items-center justify-center modal-overlay" @click.self="showEditBenefitModal=false">
        <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md mx-4 p-7"
             x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100">
            <div class="flex items-center justify-between mb-6">
                <h2 class="text-lg font-bold text-gray-800">Edit Benefit</h2>
                <button @click="showEditBenefitModal=false" class="text-gray-400 hover:text-gray-600"><svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg></button>
            </div>
            <form :action="`/admin/payroll/benefit/${editBenefit.id}/update`" method="POST" class="space-y-4">
                @csrf @method('PUT')
                <div><label class="block text-sm font-medium text-gray-700 mb-1.5">Benefit Name</label><input type="text" name="name" x-model="editBenefit.name" required class="ctrl w-full"></div>
                <div class="grid grid-cols-2 gap-4">
                    <div><label class="block text-sm font-medium text-gray-700 mb-1.5">Type</label><select name="type" x-model="editBenefit.type" required class="ctrl w-full"><option value="Allowance">Allowance</option><option value="Bonus">Bonus</option><option value="Incentive">Incentive</option></select></div>
                    <div><label class="block text-sm font-medium text-gray-700 mb-1.5">Amount</label><input type="number" name="amount" step="0.01" x-model="editBenefit.amount" required class="ctrl w-full"></div>
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div><label class="block text-sm font-medium text-gray-700 mb-1.5">Tax</label><select name="tax" x-model="editBenefit.tax" required class="ctrl w-full"><option value="Non-taxable">Non-taxable</option><option value="Taxable">Taxable</option></select></div>
                    <div><label class="block text-sm font-medium text-gray-700 mb-1.5">Frequency</label><input type="text" name="frequency" x-model="editBenefit.frequency" required class="ctrl w-full"></div>
                </div>
                <div><label class="block text-sm font-medium text-gray-700 mb-1.5">Eligibility</label><input type="text" name="eligibility" x-model="editBenefit.eligibility" required class="ctrl w-full"></div>
                <div class="flex items-center justify-between pt-2">
                    <button type="button" @click="deactivateBenefit()" class="text-blue-500 border border-blue-200 px-4 py-2 rounded-lg text-sm font-medium hover:bg-blue-50 transition" x-text="editBenefit.status==='Active' ? 'Deactivate' : 'Activate'"></button>
                    <div class="flex gap-3">
                        <button type="button" @click="showEditBenefitModal=false" class="btn-outline">Cancel</button>
                        <button type="submit" class="btn-primary">Submit</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    {{-- Assign Benefit Employees --}}
    <div x-show="showAssignBenefitModal" x-cloak class="fixed inset-0 z-50 flex items-center justify-center modal-overlay" @click.self="showAssignBenefitModal=false">
        <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md mx-4 p-7"
             x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100">
            <div class="flex items-center justify-between mb-6">
                <h2 class="text-lg font-bold text-gray-800">Assign Employees — <span x-text="assignBenefit.name" class="text-blue-600"></span></h2>
                <button @click="showAssignBenefitModal=false" class="text-gray-400 hover:text-gray-600"><svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg></button>
            </div>
            <form :action="`/admin/payroll/benefit/${assignBenefit.id}/assign`" method="POST" class="space-y-4">
                @csrf
                <div>
                    <div class="flex items-center justify-between mb-1.5">
                        <label class="block text-sm font-medium text-gray-700">Assigned Employees</label>
                        <button type="button"
                            @click="benefitSelectedEmps = [...allEmps]"
                            class="text-xs text-blue-600 hover:text-blue-800 font-semibold border border-blue-200 rounded-lg px-2.5 py-1 hover:bg-blue-50 transition">
                            Assign to All
                        </button>
                    </div>
                    <div class="flex flex-wrap gap-1.5 mb-2" x-show="benefitSelectedEmps.length > 0">
                        <template x-for="s in benefitSelectedEmps" :key="s.id">
                            <span class="inline-flex items-center gap-1 bg-blue-100 text-blue-700 text-xs font-medium px-2.5 py-1 rounded-full">
                                <span x-text="s.name"></span>
                                <button type="button" @click="benefitRemoveEmp(s.id)" class="hover:text-blue-900 font-bold ml-0.5">×</button>
                                <input type="hidden" name="employee_ids[]" :value="s.id">
                            </span>
                        </template>
                    </div>
                    <div class="relative">
                        <input type="text" x-model="benefitEmpSearch"
                               @focus="benefitShowDrop=true" @blur="setTimeout(()=>{benefitShowDrop=false},200)"
                               placeholder="Search and add employees…" class="ctrl w-full">
                        <div x-show="benefitShowDrop && benefitFiltered.length > 0"
                             class="absolute top-full left-0 right-0 mt-1 border border-gray-200 rounded-lg bg-white shadow-md max-h-44 overflow-y-auto z-20">
                            <template x-for="opt in benefitFiltered" :key="opt.id">
                                <div class="px-3 py-2.5 text-sm text-gray-700 hover:bg-blue-50 cursor-pointer" @mousedown.prevent="benefitAddEmp(opt)" x-text="opt.name"></div>
                            </template>
                        </div>
                    </div>
                </div>
                <div class="flex justify-end gap-3 pt-2">
                    <button type="button" @click="showAssignBenefitModal=false" class="btn-outline">Cancel</button>
                    <button type="submit" class="btn-primary">Save</button>
                </div>
            </form>
        </div>
    </div>

    {{-- Add Salary Grade --}}
    <div x-show="showAddGradeModal" x-cloak class="fixed inset-0 z-50 flex items-center justify-center modal-overlay" @click.self="showAddGradeModal=false">
        <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md mx-4 p-7"
             x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100">
            <div class="flex items-center justify-between mb-6">
                <h2 class="text-lg font-bold text-gray-800">Add Salary Grade</h2>
                <button @click="showAddGradeModal=false" class="text-gray-400 hover:text-gray-600"><svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg></button>
            </div>
            <form action="{{ route('admin.payroll.grade.store') }}" method="POST" class="space-y-4"
                  @submit.prevent="if(!addGradeCodeDuplicate) $el.submit()">
                @csrf
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1.5">Grade Code</label>
                        <input type="text" name="grade_code" required placeholder="e.g. Grade 1" class="ctrl w-full"
                               x-model="addGradeCode"
                               :class="addGradeCodeDuplicate ? 'border-red-400 focus:border-red-400 focus:shadow-none' : ''">
                        <p x-show="addGradeCodeDuplicate" class="text-red-500 text-xs mt-1">Grade code already exists.</p>
                    </div>
                    <div><label class="block text-sm font-medium text-gray-700 mb-1.5">Monthly Basic Salary (₱)</label><input type="number" name="monthly_basic_salary" step="0.01" required placeholder="₱22,000.00" class="ctrl w-full"></div>
                </div>
                <div><label class="block text-sm font-medium text-gray-700 mb-1.5">Salary Level Name</label><input type="text" name="level_name" required placeholder="e.g. Entry Level" class="ctrl w-full"></div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1.5">Assign Employees</label>
                    <div class="flex flex-wrap gap-1.5 mb-2" x-show="gradeSelectedEmps.length > 0">
                        <template x-for="s in gradeSelectedEmps" :key="s.id">
                            <span class="inline-flex items-center gap-1 bg-blue-100 text-blue-700 text-xs font-medium px-2.5 py-1 rounded-full">
                                <span x-text="s.name"></span>
                                <button type="button" @click="gradeRemoveEmp(s.id)" class="hover:text-blue-900 font-bold ml-0.5">×</button>
                                <input type="hidden" name="employee_ids[]" :value="s.id">
                            </span>
                        </template>
                    </div>
                    <div class="relative">
                        <input type="text" x-ref="empInput" x-model="gradeEmpSearch"
                               @focus="gradeShowDrop=true" @blur="setTimeout(()=>{gradeShowDrop=false},200)"
                               placeholder="Search and add employees…" class="ctrl w-full">
                        <div x-show="gradeShowDrop && gradeFiltered.length > 0"
                             class="absolute top-full left-0 right-0 mt-1 border border-gray-200 rounded-lg bg-white shadow-md max-h-44 overflow-y-auto z-20">
                            <template x-for="opt in gradeFiltered" :key="opt.id">
                                <div class="px-3 py-2.5 text-sm text-gray-700 hover:bg-blue-50 cursor-pointer" @mousedown.prevent="gradeAddEmp(opt)" x-text="opt.name"></div>
                            </template>
                        </div>
                    </div>
                </div>
                <div class="flex justify-end gap-3 pt-2">
                    <button type="button" @click="showAddGradeModal=false" class="btn-outline">Cancel</button>
                    <button type="submit" class="btn-primary">Submit</button>
                </div>
            </form>
        </div>
    </div>

    {{-- Edit Salary Grade --}}
    <div x-show="showEditGradeModal" x-cloak class="fixed inset-0 z-50 flex items-center justify-center modal-overlay" @click.self="showEditGradeModal=false">
        <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md mx-4 p-7"
             x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100">
            <div class="flex items-center justify-between mb-6">
                <h2 class="text-lg font-bold text-gray-800">Edit Salary Grade</h2>
                <button @click="showEditGradeModal=false" class="text-gray-400 hover:text-gray-600"><svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg></button>
            </div>
            <form :action="`/admin/payroll/grade/${editGrade.id}/update`" method="POST" class="space-y-4">
                @csrf @method('PUT')
                <div class="grid grid-cols-2 gap-4">
                    <div><label class="block text-sm font-medium text-gray-700 mb-1.5">Grade Code</label><input type="text" name="grade_code" x-model="editGrade.gradeCode" required class="ctrl w-full"></div>
                    <div><label class="block text-sm font-medium text-gray-700 mb-1.5">Monthly Basic Salary (₱)</label><input type="number" name="monthly_basic_salary" step="0.01" x-model="editGrade.monthlySalary" required class="ctrl w-full"></div>
                </div>
                <div><label class="block text-sm font-medium text-gray-700 mb-1.5">Salary Level Name</label><input type="text" name="level_name" x-model="editGrade.levelName" required class="ctrl w-full"></div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1.5">Assign Employees</label>
                    <div class="flex flex-wrap gap-1.5 mb-2" x-show="gradeSelectedEmps.length > 0">
                        <template x-for="s in gradeSelectedEmps" :key="s.id">
                            <span class="inline-flex items-center gap-1 bg-blue-100 text-blue-700 text-xs font-medium px-2.5 py-1 rounded-full">
                                <span x-text="s.name"></span>
                                <button type="button" @click="gradeRemoveEmp(s.id)" class="hover:text-blue-900 font-bold ml-0.5">×</button>
                                <input type="hidden" name="employee_ids[]" :value="s.id">
                            </span>
                        </template>
                    </div>
                    <div class="relative">
                        <input type="text" x-ref="empInputEdit" x-model="gradeEmpSearch"
                               @focus="gradeShowDrop=true" @blur="setTimeout(()=>{gradeShowDrop=false},200)"
                               placeholder="Search and add employees…" class="ctrl w-full">
                        <div x-show="gradeShowDrop && gradeFiltered.length > 0"
                             class="absolute top-full left-0 right-0 mt-1 border border-gray-200 rounded-lg bg-white shadow-md max-h-44 overflow-y-auto z-20">
                            <template x-for="opt in gradeFiltered" :key="opt.id">
                                <div class="px-3 py-2.5 text-sm text-gray-700 hover:bg-blue-50 cursor-pointer" @mousedown.prevent="gradeAddEmp(opt)" x-text="opt.name"></div>
                            </template>
                        </div>
                    </div>
                </div>
                <div class="flex justify-end gap-3 pt-2">
                    <button type="button" @click="showEditGradeModal=false" class="btn-outline">Cancel</button>
                    <button type="submit" class="btn-primary">Submit</button>
                </div>
            </form>
        </div>
    </div>

    {{-- Edit Contribution Rate --}}
    <div x-show="showEditContribModal" x-cloak class="fixed inset-0 z-50 flex items-center justify-center modal-overlay" @click.self="showEditContribModal=false">
        <div class="bg-white rounded-2xl shadow-2xl w-full max-w-2xl mx-4 p-7"
             x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100">
            <div class="flex items-center justify-between mb-6">
                <h2 class="text-lg font-bold text-gray-800" x-text="{ sss:'Edit SSS Rates', philhealth:'Edit PhilHealth Rates', pagibig:'Edit Pag-IBIG Rates', wtax:'Edit W/Tax Brackets' }[editContribType]"></h2>
                <button @click="showEditContribModal=false" class="text-gray-400 hover:text-gray-600"><svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg></button>
            </div>
            <div x-show="editContribType==='sss'" class="space-y-3">
                <p class="text-xs text-gray-400 uppercase font-semibold tracking-wider">Salary Brackets → Fixed Monthly Contribution</p>
                <div class="max-h-64 overflow-y-auto overflow-x-hidden space-y-2 pr-1">
                    <template x-for="(row, i) in sssRows" :key="i">
                        <div class="flex gap-2 items-center">
                            <input type="number" step="0.01" x-model="row.salary_from" placeholder="From" class="ctrl text-xs px-2 py-1.5 min-w-0 flex-1">
                            <input type="number" step="0.01" x-model="row.salary_to"   placeholder="To (blank=∞)" class="ctrl text-xs px-2 py-1.5 min-w-0 flex-1">
                            <input type="number" step="0.01" x-model="row.employee_share" placeholder="Employee" class="ctrl text-xs px-2 py-1.5 min-w-0 flex-1">
                            <input type="number" step="0.01" x-model="row.employer_share" placeholder="Employer" class="ctrl text-xs px-2 py-1.5 min-w-0 flex-1">
                            <button type="button" @click="sssRows.splice(i,1)" class="text-red-400 hover:text-red-600 text-xs font-bold">✕</button>
                        </div>
                    </template>
                </div>
                <div class="flex gap-2 items-center text-xs text-gray-400 font-medium px-0.5">
                    <span class="flex-1 text-center">Salary From</span><span class="flex-1 text-center">Salary To</span><span class="flex-1 text-center">Emp. Share</span><span class="flex-1 text-center">Emr. Share</span><span class="w-4"></span>
                </div>
                <button type="button" @click="sssRows.push({salary_from:'',salary_to:'',employee_share:'',employer_share:''})"
                        class="text-xs text-blue-500 hover:text-blue-700 font-medium">+ Add Row</button>
            </div>
            <div x-show="editContribType==='philhealth'" class="space-y-4">
                <div class="grid grid-cols-2 gap-4">
                    <div><label class="block text-sm font-medium text-gray-700 mb-1.5">Total Rate (%)</label><input type="number" step="0.01" x-model="contrib.philhealth_rate" class="ctrl w-full"></div>
                    <div><label class="block text-sm font-medium text-gray-700 mb-1.5">Employer Share (%)</label><input type="number" step="0.01" value="2.5" class="ctrl w-full" disabled></div>
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div><label class="block text-sm font-medium text-gray-700 mb-1.5">Salary Floor (₱)</label><input type="number" step="0.01" x-model="contrib.philhealth_floor" class="ctrl w-full"></div>
                    <div><label class="block text-sm font-medium text-gray-700 mb-1.5">Salary Ceiling (₱)</label><input type="number" step="0.01" x-model="contrib.philhealth_ceiling" class="ctrl w-full"></div>
                </div>
            </div>
            <div x-show="editContribType==='pagibig'" class="space-y-4">
                <div><label class="block text-sm font-medium text-gray-700 mb-1.5">Fixed Amount (₱/month)</label><input type="number" step="0.01" x-model="contrib.pagibig_high_amount" class="ctrl w-full"></div>
            </div>
            <div x-show="editContribType==='wtax'" class="space-y-3">
                <p class="text-xs text-gray-400 uppercase font-semibold tracking-wider">Annual Income Brackets (TRAIN Law)</p>
                <div class="flex items-center gap-3"><span class="text-sm text-gray-600 w-40">Up to ₱250K</span><input type="number" step="0.01" value="0" class="ctrl flex-1" disabled><span class="text-sm text-gray-400">%</span></div>
                <div class="flex items-center gap-3"><span class="text-sm text-gray-600 w-40">₱250K – ₱400K</span><input type="number" step="0.01" x-model="contrib.wtax_rate_1" class="ctrl flex-1"><span class="text-sm text-gray-400">%</span></div>
                <div class="flex items-center gap-3"><span class="text-sm text-gray-600 w-40">₱400K – ₱800K</span><input type="number" step="0.01" x-model="contrib.wtax_rate_2" class="ctrl flex-1"><span class="text-sm text-gray-400">%</span></div>
                <div class="flex items-center gap-3"><span class="text-sm text-gray-600 w-40">₱800K – ₱2M</span><input type="number" step="0.01" x-model="contrib.wtax_rate_3" class="ctrl flex-1"><span class="text-sm text-gray-400">%</span></div>
                <div class="flex items-center gap-3"><span class="text-sm text-gray-600 w-40">₱2M – ₱8M</span><input type="number" step="0.01" x-model="contrib.wtax_rate_4" class="ctrl flex-1"><span class="text-sm text-gray-400">%</span></div>
                <div class="flex items-center gap-3"><span class="text-sm text-gray-600 w-40">₱8M+</span><input type="number" step="0.01" x-model="contrib.wtax_rate_5" class="ctrl flex-1"><span class="text-sm text-gray-400">%</span></div>
            </div>
            <div class="flex justify-end gap-3 pt-5">
                <button type="button" @click="showEditContribModal=false" class="btn-outline">Cancel</button>
                <button type="button" class="btn-primary" @click="saveContrib()">Save</button>
            </div>
        </div>
    </div>

    {{-- Custom Confirm Modal (for delete/deactivate) --}}
    <div x-show="confirmModal.show" x-cloak class="fixed inset-0 z-[999] flex items-center justify-center modal-overlay">
        <div class="bg-white rounded-2xl shadow-2xl w-full max-w-sm mx-4 p-7"
             x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100">
            <div class="flex items-start gap-4 mb-6">
                <div class="w-10 h-10 rounded-full flex items-center justify-center flex-shrink-0"
                     :class="confirmModal.danger ? 'bg-red-50' : 'bg-orange-50'">
                    <svg class="w-5 h-5" :class="confirmModal.danger ? 'text-red-500' : 'text-orange-500'" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/>
                    </svg>
                </div>
                <div class="pt-0.5">
                    <p class="font-semibold text-gray-800 text-base leading-snug" x-text="confirmModal.title"></p>
                    <p class="text-sm text-gray-500 mt-1" x-text="confirmModal.message"></p>
                </div>
            </div>
            <div class="flex justify-end gap-3">
                <button class="btn-outline" @click="confirmModal.show=false">Cancel</button>
                <button class="px-5 py-2 rounded-lg text-sm font-semibold text-white transition"
                        :class="confirmModal.danger ? 'bg-red-500 hover:bg-red-600' : 'bg-blue-500 hover:bg-blue-600'"
                        @click="confirmAction()">Confirm</button>
            </div>
        </div>
    </div>


    {{-- ════════════════════════════════════════
         ALPINE JS
    ════════════════════════════════════════ --}}
    <script>
    function payrollApp() {
        return {
            sidebarCollapsed: localStorage.getItem('sidebarCollapsed') === 'true',
            mobileMenuOpen: false,
            page: 'list',
            activeTab: location.hash.replace('#','') || new URLSearchParams(location.search).get('tab') || 'payroll-period',

            periodSearch: '', periodStatusFilter: '', periodYearFilter: '{{ $year }}',
            benefitStatusFilter: '', itemStatusFilter: '',

            showCreatePeriod:     false,
            showSubmitConfirm:        false,
            showSubmitPayslipConfirm: false,
            showReleaseConfirm:       false,
            showAddItemModal:     false,
            showEditItemModal:    false,
            showAddBenefitModal:    false,
            showEditBenefitModal:   false,
            showAssignBenefitModal: false,
            showAddGradeModal:    false,
            showEditGradeModal:   false,
            showEditContribModal: false,
            editContribType: '',
            showEditPeriodModal: false,
            editPeriod: { id: null, name: '', startDate: '', endDate: '', payoutDate: '', status: '' },
            contrib: @json($contrib),
            sssRows: @json($sssRowsForJs),

            alertModal: { show: false, type: 'error', title: '', message: '' },
            confirmModal: { show: false, title: '', message: '', action: null, danger: true },

            showEditPayslipModal: false,
            editPayslip: {},

            editItem:    { id: null, name: '', multiplier: '', type: '', basis: '', status: '' },
            editBenefit:   { id: null, name: '', type: '', amount: '', tax: '', frequency: '', eligibility: '', status: '' },
            assignBenefit: { id: null, name: '' },
            assignedBenefitEmpMap: @json($benefits->mapWithKeys(fn($b) => [$b->id => $b->employees->pluck('id')->toArray()])),
            benefitSelectedEmps: [], benefitEmpSearch: '', benefitShowDrop: false,
            editGrade:   { id: null, gradeCode: '', levelName: '', monthlySalary: 0 },

            gradeSelectedEmps: [], gradeEmpSearch: '', gradeShowDrop: false,
            allEmps: @json($employees->map(fn($e) => ['id' => $e->id, 'name' => $e->fname.' '.$e->lname])->values()),
            existingGradeCodes: @json($salaryGrades->pluck('grade_code')->map(fn($c) => strtolower($c))->values()),
            assignedEmpMap: @json($salaryGrades->mapWithKeys(fn($g) => [$g->id => $g->employees->pluck('id')->toArray()])),
            addGradeCode: '',
            addGradeCodeError: '',
            editingGradeId: null,

            viewPeriod:        { id: null, name: '', startDate: '', endDate: '', status: '' },
            pvPayslips:        [], pvSelectedId: null, pvActive: {},
            pvGrossPayroll: 0, pvTotalDeductions: 0, pvNetPayroll: 0,
            pvSubmittedCount: 0, pvTotalCount: 0, pvPeriodSubtitle: '',

            get gradeFiltered() {
                const takenIds = Object.entries(this.assignedEmpMap)
                    .filter(([gId]) => String(gId) !== String(this.editingGradeId))
                    .flatMap(([, ids]) => ids);
                return this.allEmps.filter(e =>
                    !this.gradeSelectedEmps.find(s => s.id === e.id) &&
                    !takenIds.includes(e.id) &&
                    e.name.toLowerCase().includes(this.gradeEmpSearch.toLowerCase())
                );
            },

            get benefitFiltered() {
                return this.allEmps.filter(e =>
                    !this.benefitSelectedEmps.find(s => s.id === e.id) &&
                    e.name.toLowerCase().includes(this.benefitEmpSearch.toLowerCase())
                );
            },

            get addGradeCodeDuplicate() {
                return this.addGradeCode.trim() !== '' &&
                       this.existingGradeCodes.includes(this.addGradeCode.trim().toLowerCase());
            },

            init() {
                window.addEventListener('sidebar-toggle', e => { this.sidebarCollapsed = e.detail.collapsed; });
                this.$watch('activeTab', val => { location.hash = val; });
            },

            showAlert(type, title, message) {
                this.alertModal = { show: true, type, title, message };
            },

            // ── Period View ──
            async openPeriodView(id, name, startDate, endDate, status) {
                this.viewPeriod   = { id, name, startDate, endDate, status };
                this.pvPayslips   = []; this.pvSelectedId = null; this.pvActive = {};
                this.page         = 'view';
                window.scrollTo({ top: 0, behavior: 'smooth' });

                try {
                    const res  = await fetch(`/admin/payroll/period/${id}/all-payslips`, {
                        headers: { 'X-Requested-With': 'XMLHttpRequest' }
                    });
                    const data = await res.json();
                    this.pvPayslips = data.payslips;
                } catch (e) { this.pvPayslips = []; }

                this.pvTotalCount      = this.pvPayslips.length;
                this.pvSubmittedCount  = this.pvPayslips.filter(p => p.status === 'Submitted' || p.status === 'Released').length;
                this.pvGrossPayroll    = this.pvPayslips.reduce((s, p) => s + p.grossPay, 0);
                this.pvTotalDeductions = this.pvPayslips.reduce((s, p) => s + p.totalDeductions, 0);
                this.pvNetPayroll      = this.pvPayslips.reduce((s, p) => s + p.netPay, 0);

                const start = new Date(startDate), end = new Date(endDate);
                const month = start.toLocaleString('en-US', { month: 'long' });
                this.pvPeriodSubtitle = `${month} ${start.getFullYear()} · ${name} · ${start.toLocaleString('en-US',{month:'short'})} ${start.getDate()}–${end.getDate()}`;

                if (this.pvPayslips.length > 0) this.pvSelectPayslip(this.pvPayslips[0]);
            },

            closePeriodView() {
                this.page = 'list'; this.pvSelectedId = null; this.pvActive = {};
                window.scrollTo({ top: 0, behavior: 'smooth' });
            },

            openEditPeriod(id, name, startDate, endDate, payoutDate, status) {
                this.editPeriod = { id, name, startDate, endDate, payoutDate, status };
                this.showEditPeriodModal = true;
            },

            async savePeriodEdit() {
                const ep = this.editPeriod;
                if (!ep.name.trim()) return;
                const body = { name: ep.name };
                if (ep.status === 'Pending') {
                    body.start_date  = ep.startDate;
                    body.end_date    = ep.endDate;
                    body.payout_date = ep.payoutDate;
                }
                try {
                    const res  = await fetch(`/admin/payroll/period/${ep.id}/update`, {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content },
                        body: JSON.stringify(body)
                    });
                    const data = await res.json();
                    if (res.ok) { this.showEditPeriodModal = false; window.location.reload(); }
                    else { this.showAlert('error', 'Error', data.message ?? 'Could not update period.'); }
                } catch (e) { this.showAlert('error', 'Error', 'Network error. Please try again.'); }
            },

            pvSelectPayslip(ps) { this.pvSelectedId = ps.id; this.pvActive = { ...ps }; },

            // ── Admin Payslip Actions ──
            adminEditPayslip() {
                this.editPayslip       = { ...this.pvActive };
                this.showEditPayslipModal = true;
            },

            adminSubmitPayslip() {
                this.showSubmitPayslipConfirm = true;
            },

            async adminSubmitPayslipConfirmed() {
                this.showSubmitPayslipConfirm = false;
                const csrf = document.querySelector('meta[name="csrf-token"]').content;
                const res  = await fetch(`/admin/payroll/payslip/${this.pvActive.id}/submit`, {
                    method: 'POST',
                    headers: { 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' }
                });
                const data = await res.json();
                if (data.success) {
                    const idx = this.pvPayslips.findIndex(p => p.id === this.pvActive.id);
                    if (idx !== -1) { this.pvPayslips[idx].status = 'Submitted'; this.pvActive.status = 'Submitted'; }
                    this.pvSubmittedCount = this.pvPayslips.filter(p => p.status === 'Submitted' || p.status === 'Released').length;
                    this.showAlert('success', 'Payslip Submitted', 'Payslip marked as submitted.');
                } else {
                    this.showAlert('error', 'Error', data.message || 'Could not submit payslip.');
                }
            },

            adminSubmitPeriod() {
                const submitted = this.pvPayslips.filter(p => p.status === 'Submitted').length;
                const total     = this.pvPayslips.length;
                if (submitted < total) {
                    this.showAlert('error', 'Cannot Submit', `All payslips must be submitted first (${submitted}/${total} done).`);
                    return;
                }
                this.showSubmitConfirm = true;
            },

            async saveEditPayslip() {
                const csrf = document.querySelector('meta[name="csrf-token"]').content;
                const res  = await fetch(`/admin/payroll/payslip/${this.editPayslip.id}/save`, {
                    method: 'PUT',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' },
                    body: JSON.stringify({
                        basicPay:       this.editPayslip.basicPay,
                        otPay:          this.editPayslip.otPay,
                        benefits:       this.editPayslip.benefits,
                        sss:            this.editPayslip.sss,
                        philhealth:     this.editPayslip.philhealth,
                        pagibig:        this.editPayslip.pagibig,
                        withholdingTax: this.editPayslip.withholdingTax,
                    })
                });
                const data = await res.json();
                if (data.success) {
                    const idx = this.pvPayslips.findIndex(p => p.id === this.editPayslip.id);
                    if (idx !== -1) { this.pvPayslips[idx] = { ...this.pvPayslips[idx], ...this.editPayslip }; }
                    this.pvActive = { ...this.editPayslip };
                    this.showEditPayslipModal = false;
                    this.showAlert('success', 'Saved', 'Payslip updated successfully.');
                } else {
                    this.showAlert('error', 'Error', data.message || 'Could not save payslip.');
                }
            },

            // ── Grade helpers ──
            gradeAddEmp(emp) { this.gradeSelectedEmps.push(emp); this.gradeEmpSearch = ''; this.gradeShowDrop = false; },
            gradeRemoveEmp(id) { this.gradeSelectedEmps = this.gradeSelectedEmps.filter(e => e.id !== id); },
            benefitAddEmp(emp) { this.benefitSelectedEmps.push(emp); this.benefitEmpSearch = ''; this.benefitShowDrop = false; },
            benefitRemoveEmp(id) { this.benefitSelectedEmps = this.benefitSelectedEmps.filter(e => e.id !== id); },
            openAssignBenefit(id, name) {
                this.assignBenefit = { id, name };
                const assignedIds = this.assignedBenefitEmpMap[id] || [];
                this.benefitSelectedEmps = this.allEmps.filter(e => assignedIds.includes(e.id));
                this.benefitEmpSearch = ''; this.benefitShowDrop = false;
                this.showAssignBenefitModal = true;
            },
            openAddGrade() {
                this.gradeSelectedEmps = []; this.gradeEmpSearch = '';
                this.addGradeCode = ''; this.addGradeCodeError = '';
                this.editingGradeId = null;
                this.showAddGradeModal = true;
            },
            openEditGrade(id, gradeCode, levelName, monthlySalary, assignedEmps) {
                this.editGrade = { id, gradeCode, levelName, monthlySalary };
                this.gradeSelectedEmps = Array.isArray(assignedEmps) ? assignedEmps : [];
                this.gradeEmpSearch = ''; this.editingGradeId = id; this.showEditGradeModal = true;
            },
            deleteGrade(id) {
                this.askConfirm('Delete Salary Grade', 'Assigned employees will be unassigned. This cannot be undone.', () => {
                    fetch(`/admin/payroll/grade/${id}/delete`, {
                        method: 'DELETE', headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content }
                    }).then(() => this.reloadTab());
                });
            },

            // ── Item helpers ──
            openEditItem(id, name, multiplier, type, basis, status) {
                this.editItem = { id, name, multiplier, type, basis, status: status || '' };
                this.showEditItemModal = true;
            },
            deleteItem(id) {
                this.askConfirm('Delete Payroll Item', 'This will permanently remove this payroll item.', () => {
                    fetch(`/admin/payroll/item/${id}/delete`, {
                        method: 'DELETE', headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content }
                    }).then(() => this.reloadTab());
                });
            },
            deactivateItem() {
                const label = this.editItem.status === 'Active' ? 'Deactivate' : 'Activate';
                this.askConfirm(`${label} Payroll Item`, `This will ${label.toLowerCase()} "${this.editItem.name}".`, () => {
                    fetch(`/admin/payroll/item/${this.editItem.id}/deactivate`, {
                        method: 'POST', headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content }
                    }).then(() => this.reloadTab());
                }, this.editItem.status === 'Active');
            },

            // ── Benefit helpers ──
            openEditBenefit(id, name, type, amount, tax, frequency, eligibility, status) {
                this.editBenefit = { id, name, type, amount, tax, frequency, eligibility, status: status || '' };
                this.showEditBenefitModal = true;
            },
            deleteBenefit(id) {
                this.askConfirm('Delete Benefit', 'This will permanently remove this benefit.', () => {
                    fetch(`/admin/payroll/benefit/${id}/delete`, {
                        method: 'DELETE', headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content }
                    }).then(() => this.reloadTab());
                });
            },
            deactivateBenefit() {
                const label = this.editBenefit.status === 'Active' ? 'Deactivate' : 'Activate';
                this.askConfirm(`${label} Benefit`, `This will ${label.toLowerCase()} "${this.editBenefit.name}".`, () => {
                    fetch(`/admin/payroll/benefit/${this.editBenefit.id}/deactivate`, {
                        method: 'POST', headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content }
                    }).then(() => this.reloadTab());
                }, this.editBenefit.status === 'Active');
            },

            // ── Contribution helpers ──
            openEditContrib(type) { this.editContribType = type; this.showEditContribModal = true; },
            async saveContrib() {
                const csrf = document.querySelector('meta[name="csrf-token"]').content;
                const headers = { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf, 'X-Requested-With': 'XMLHttpRequest' };
                if (this.editContribType === 'sss') {
                    await fetch('/admin/payroll/sss/save', {
                        method: 'POST', headers, body: JSON.stringify({ rows: this.sssRows }),
                    });
                } else {
                    const keys   = Object.keys(this.contrib);
                    const values = Object.values(this.contrib);
                    await fetch('/admin/payroll/contrib/update', {
                        method: 'POST', headers, body: JSON.stringify({ keys, values }),
                    });
                }
                this.reloadTab();
            },

            // ── Confirm modal ──
            askConfirm(title, message, action, danger = true) {
                this.confirmModal = { show: true, title, message, action, danger };
            },
            confirmAction() {
                if (this.confirmModal.action) this.confirmModal.action();
                this.confirmModal.show = false;
            },

            reloadTab() { location.hash = this.activeTab; location.reload(); },

            // ── PDF Export ──
            exportPayslip(ps) {
                this._printHtml(this._buildPayslipHtml(ps.employeeName, ps.jobTitle, ps.department, ps.period || this.viewPeriod.name, ps));
            },

            pvExportAllPayslips() {
                const slips = this.pvPayslips;
                if (!slips.length) return;
                const f = n => Number(n||0).toLocaleString('en-PH',{minimumFractionDigits:2,maximumFractionDigits:2});
                const period = this.viewPeriod.name || '—';
                const pages = slips.map((ps, i) => {
                    const pb = i < slips.length - 1 ? 'page-break-after:always;' : '';
                    return `<div style="padding:40px 48px;max-width:620px;margin:0 auto;${pb}">
<div style="margin-bottom:20px;"><div style="font-size:20px;font-weight:700;color:#2563eb;">MediSource</div><div style="font-size:12px;color:#64748b;margin-top:2px;">${period}</div></div>
<div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:16px;background:#f8faff;border:1px solid #e2e8f0;border-radius:10px;padding:16px 20px;margin-bottom:20px;">
  <div><div style="font-size:10px;color:#94a3b8;text-transform:uppercase;letter-spacing:.05em;margin-bottom:3px;">Employee</div><div style="font-size:13px;font-weight:600;color:#1e293b;">${ps.employeeName||'—'}</div></div>
  <div><div style="font-size:10px;color:#94a3b8;text-transform:uppercase;letter-spacing:.05em;margin-bottom:3px;">Job Title</div><div style="font-size:13px;font-weight:600;color:#1e293b;">${ps.jobTitle||'—'}</div></div>
  <div><div style="font-size:10px;color:#94a3b8;text-transform:uppercase;letter-spacing:.05em;margin-bottom:3px;">Department</div><div style="font-size:13px;font-weight:600;color:#1e293b;">${ps.department||'—'}</div></div>
</div>
<div style="font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.06em;color:#64748b;margin:16px 0 8px;padding-bottom:4px;border-bottom:1px solid #e2e8f0;">Earnings</div>
<div style="display:flex;justify-content:space-between;font-size:13px;color:#475569;padding:4px 0;"><span>Basic Pay</span><span>₱ ${f(ps.basicPay)}</span></div>
<div style="display:flex;justify-content:space-between;font-size:13px;color:#475569;padding:4px 0;"><span>OT Pay</span><span>₱ ${f(ps.otPay)}</span></div>
<div style="display:flex;justify-content:space-between;font-size:13px;color:#475569;padding:4px 0;"><span>Benefits</span><span>₱ ${f(ps.benefits)}</span></div>
<div style="display:flex;justify-content:space-between;font-size:14px;font-weight:700;color:#0f172a;padding:8px 0 4px;border-top:2px solid #e2e8f0;margin-top:4px;"><span>Gross Pay</span><span>₱ ${f(ps.grossPay)}</span></div>
<div style="font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.06em;color:#64748b;margin:16px 0 8px;padding-bottom:4px;border-bottom:1px solid #e2e8f0;">Deductions</div>
<div style="display:flex;justify-content:space-between;font-size:13px;color:#475569;padding:4px 0;"><span>SSS</span><span style="color:#dc2626;">-₱ ${f(ps.sss)}</span></div>
<div style="display:flex;justify-content:space-between;font-size:13px;color:#475569;padding:4px 0;"><span>PhilHealth</span><span style="color:#dc2626;">-₱ ${f(ps.philhealth)}</span></div>
<div style="display:flex;justify-content:space-between;font-size:13px;color:#475569;padding:4px 0;"><span>Pag-IBIG</span><span style="color:#dc2626;">-₱ ${f(ps.pagibig)}</span></div>
<div style="display:flex;justify-content:space-between;font-size:13px;color:#475569;padding:4px 0;"><span>Withholding Tax</span><span style="color:#dc2626;">-₱ ${f(ps.withholdingTax)}</span></div>
<div style="display:flex;justify-content:space-between;font-size:14px;font-weight:700;color:#0f172a;padding:8px 0 4px;border-top:2px solid #e2e8f0;margin-top:4px;"><span>Total Deductions</span><span style="color:#dc2626;">-₱ ${f(ps.totalDeductions)}</span></div>
<div style="font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.06em;color:#64748b;margin:16px 0 8px;padding-bottom:4px;border-bottom:1px solid #e2e8f0;">Summary</div>
<div style="display:flex;justify-content:space-between;font-size:13px;color:#475569;padding:4px 0;"><span>Gross Pay</span><span>₱ ${f(ps.grossPay)}</span></div>
<div style="display:flex;justify-content:space-between;font-size:13px;color:#475569;padding:4px 0;"><span>Total Deductions</span><span style="color:#dc2626;">-₱ ${f(ps.totalDeductions)}</span></div>
<div style="display:flex;justify-content:space-between;font-size:14px;font-weight:700;color:#0f172a;padding:8px 0 4px;border-top:2px solid #e2e8f0;margin-top:4px;"><span>Net Pay</span><span>₱ ${f(ps.netPay)}</span></div>
<div style="margin-top:24px;font-size:10px;color:#94a3b8;text-align:center;border-top:1px solid #e2e8f0;padding-top:10px;">This is a system-generated payslip from MediSource HRIS.</div>
</div>`;
                }).join('');
                const html = `<!DOCTYPE html><html><head><meta charset="UTF-8"><title>All Payslips – ${period}</title><style>*{font-family:Arial,sans-serif;box-sizing:border-box;margin:0;padding:0;}body{color:#1e293b;}@media print{@page{margin:.5cm;}}</style></head><body>${pages}</body></html>`;
                this._printHtml(html);
            },

            _buildPayslipHtml(name, jobTitle, department, period, ps) {
                const f = n => Number(n||0).toLocaleString('en-PH', { minimumFractionDigits:2, maximumFractionDigits:2 });
                return `<!DOCTYPE html><html><head>
    <link rel="icon" type="image/png" href="{{ asset('images/HRISLogo-Icon.png') }}"><meta charset="UTF-8"><title>Payslip – ${name}</title>
<style>*{font-family:Arial,sans-serif;box-sizing:border-box;margin:0;padding:0}body{padding:48px;max-width:620px;margin:0 auto;color:#1e293b}.company{font-size:20px;font-weight:700;color:#2563eb}.period-label{font-size:12px;color:#64748b;margin-top:2px;margin-bottom:28px}.info-grid{display:grid;grid-template-columns:1fr 1fr 1fr;gap:16px;background:#f8faff;border:1px solid #e2e8f0;border-radius:10px;padding:16px 20px;margin-bottom:24px}.info-label{font-size:10px;color:#94a3b8;text-transform:uppercase;letter-spacing:.05em;margin-bottom:3px}.info-value{font-size:13px;font-weight:600;color:#1e293b}.section-title{font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.06em;color:#64748b;margin:20px 0 8px;padding-bottom:4px;border-bottom:1px solid #e2e8f0}.line{display:flex;justify-content:space-between;font-size:13px;color:#475569;padding:5px 0}.line.bold{font-weight:700;color:#0f172a;font-size:14px;border-top:2px solid #e2e8f0;padding-top:8px;margin-top:4px}.red{color:#dc2626}.footer{margin-top:32px;font-size:10px;color:#94a3b8;text-align:center;border-top:1px solid #e2e8f0;padding-top:12px}@media print{body{padding:24px}@page{margin:1cm}}</style>
</head><body>
<div class="company">MediSource</div><div class="period-label">${period||'—'}</div>
<div class="info-grid"><div><div class="info-label">Employee</div><div class="info-value">${name||'—'}</div></div><div><div class="info-label">Job Title</div><div class="info-value">${jobTitle||'—'}</div></div><div><div class="info-label">Department</div><div class="info-value">${department||'—'}</div></div></div>
<div class="section-title">Earnings</div>
<div class="line"><span>Basic Pay</span><span>₱ ${f(ps.basicPay)}</span></div>
<div class="line"><span>OT Pay</span><span>₱ ${f(ps.otPay)}</span></div>
<div class="line"><span>Benefits</span><span>₱ ${f(ps.benefits)}</span></div>
<div class="line bold"><span>Gross Pay</span><span>₱ ${f(ps.grossPay)}</span></div>
<div class="section-title">Deductions</div>
<div class="line"><span>SSS</span><span class="red">-₱ ${f(ps.sss)}</span></div>
<div class="line"><span>PhilHealth</span><span class="red">-₱ ${f(ps.philhealth)}</span></div>
<div class="line"><span>Pag-IBIG</span><span class="red">-₱ ${f(ps.pagibig)}</span></div>
<div class="line"><span>Withholding Tax</span><span class="red">-₱ ${f(ps.withholdingTax)}</span></div>
<div class="line bold"><span>Total Deductions</span><span class="red">-₱ ${f(ps.totalDeductions)}</span></div>
<div class="section-title">Summary</div>
<div class="line"><span>Gross Pay</span><span>₱ ${f(ps.grossPay)}</span></div>
<div class="line"><span>Total Deductions</span><span class="red">-₱ ${f(ps.totalDeductions)}</span></div>
<div class="line bold"><span>Net Pay</span><span>₱ ${f(ps.netPay)}</span></div>
<div class="footer">This is a system-generated payslip from MediSource HRIS.</div>
</body></html>`;
            },

            _printHtml(html) {
                const w = window.open('', '_blank', 'width=700,height=860,scrollbars=yes');
                if (!w) { this.showAlert('error', 'Pop-up Blocked', 'Please allow pop-ups to export payslips.'); return; }
                w.document.write(html); w.document.close(); w.document.querySelectorAll('[x-show],[x-cloak]').forEach(el => el.remove()); w.focus();
                setTimeout(() => { w.print(); }, 400);
            },

            fmt(n) { return Number(n).toLocaleString('en-PH', { minimumFractionDigits:2, maximumFractionDigits:2 }); },
        }
    }
    </script>

</body>
</html>

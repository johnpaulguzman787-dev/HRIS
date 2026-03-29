<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Payroll – MediSource</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <style>
        [x-cloak] { display: none !important; }
        body { background: #f0f2f5; }

        /* ── Tabs (underline style) ── */
        .tab-bar {
            display: flex;
            gap: 0;
            border-bottom: 1px solid #e5e7eb;
        }
        .tab-btn {
            position: relative;
            padding: 12px 24px;
            font-size: 0.9rem;
            font-weight: 500;
            color: #9ca3af;
            border: none;
            background: none;
            cursor: pointer;
            white-space: nowrap;
            transition: color 0.2s ease;
            border-bottom: 2px solid transparent;
            margin-bottom: -1px;
        }
        .tab-btn:hover:not(.active) { color: #374151; }
        .tab-btn.active {
            color: #2563eb;
            font-weight: 600;
            border-bottom: 2px solid #2563eb;
        }

        /* ── Animations ── */
        @keyframes tabFadeIn {
            from { opacity: 0; transform: translateY(8px); }
            to   { opacity: 1; transform: translateY(0); }
        }
        .tab-content { animation: tabFadeIn 0.28s cubic-bezier(0.4,0,0.2,1); }

        @keyframes fadeSlideUp {
            from { opacity: 0; transform: translateY(16px); }
            to   { opacity: 1; transform: translateY(0); }
        }
        .anim-1 { animation: fadeSlideUp 0.38s ease both; }
        .anim-2 { animation: fadeSlideUp 0.38s 0.06s ease both; }
        .anim-3 { animation: fadeSlideUp 0.38s 0.12s ease both; }
        .anim-4 { animation: fadeSlideUp 0.38s 0.18s ease both; }

        @keyframes slideInRight {
            from { opacity: 0; transform: translateX(20px); }
            to   { opacity: 1; transform: translateX(0); }
        }
        .slide-in-right { animation: slideInRight 0.25s cubic-bezier(0.4,0,0.2,1) both; }

        /* ── Summary cards ── */
        .summary-card {
            background: #fff;
            border-radius: 10px;
            border: 1px solid #e5e7eb;
            padding: 22px 26px;
            flex: 1;
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }
        .summary-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 24px rgba(59,130,246,0.10);
        }
        .summary-card .label { font-size: 0.78rem; color: #9ca3af; margin-bottom: 6px; }
        .summary-card .value { font-size: 1.75rem; font-weight: 700; color: #1e293b; letter-spacing: -0.5px; }
        .summary-card .sub   { font-size: 0.72rem; color: #9ca3af; margin-top: 4px; }

        /* ── Badges ── */
        .badge-pending    { background: #fff3e0; color: #e65100; }
        .badge-completed  { background: #e8f5e9; color: #2e7d32; }
        .badge-submitted  { background: #e3f2fd; color: #1565c0; }
        .badge-active     { background: #e8f5e9; color: #2e7d32; }
        .badge-inactive   { background: #fce4ec; color: #c62828; }
        .badge-addition   { background: #fce4ec; color: #ad1457; }
        .badge-deduction  { background: #e8eaf6; color: #283593; }
        .badge-allowance  { background: #fce4ec; color: #ad1457; }
        .badge-nontaxable { background: #e3f2fd; color: #1565c0; }
        .badge-taxable    { background: #e3f2fd; color: #1565c0; }

        .multiplier-badge {
            background: #fff8e1; color: #f57c00;
            padding: 2px 10px; border-radius: 20px;
            font-size: 0.78rem; font-weight: 600;
        }

        /* ── Tables ── */
        .data-table { width: 100%; border-collapse: collapse; }
        .data-table thead tr { background: #f8fafc; border-bottom: 1px solid #e5e7eb; }
        .data-table thead th {
            text-align: left; padding: 11px 20px;
            font-size: 0.75rem; font-weight: 600;
            color: #9ca3af; text-transform: uppercase; letter-spacing: 0.05em;
        }
        .data-table tbody tr { border-bottom: 1px solid #f1f5f9; transition: background 0.15s; }
        .data-table tbody tr:hover { background: #f8faff; }
        .data-table tbody td { padding: 14px 20px; font-size: 0.875rem; color: #374151; }
        .data-table tbody tr.row-active { background: #eff6ff; }

        /* Period view table — lighter header */
        .pv-table { width: 100%; border-collapse: collapse; }
        .pv-table thead tr { border-bottom: 1px solid #e5e7eb; }
        .pv-table thead th {
            text-align: left; padding: 12px 16px;
            font-size: 0.8rem; font-weight: 600; color: #374151;
        }
        .pv-table tbody tr { border-bottom: 1px solid #f1f5f9; transition: background 0.15s; cursor: pointer; }
        .pv-table tbody tr:hover { background: #f8faff; }
        .pv-table tbody tr.row-active { background: #eff6ff; }
        .pv-table tbody td { padding: 14px 16px; font-size: 0.875rem; color: #374151; }

        /* ── Progress bar ── */
        .progress-track { height: 4px; background: #e5e7eb; border-radius: 99px; overflow: hidden; margin-top: 5px; }
        .progress-fill  { height: 100%; background: #3b82f6; border-radius: 99px; transition: width 0.6s cubic-bezier(0.4,0,0.2,1); }

        /* ── Payslip panel ── */
        .payslip-header {
            background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
            border-radius: 12px 12px 0 0;
            padding: 18px 22px; color: #fff;
        }
        .payslip-body { padding: 0 22px 22px; }
        .payslip-section-title { font-size: 0.82rem; font-weight: 700; color: #374151; margin-top: 16px; margin-bottom: 8px; }
        .payslip-line { display: flex; justify-content: space-between; font-size: 0.82rem; color: #6b7280; padding: 3px 0; }
        .payslip-line.bold { font-weight: 700; color: #1e293b; font-size: 0.875rem; border-top: 1px solid #e5e7eb; padding-top: 8px; margin-top: 4px; }
        .info-label { font-size: 0.72rem; color: rgba(255,255,255,0.75); }
        .info-value { font-size: 0.82rem; color: #fff; font-weight: 500; }

        /* ── Inputs / Selects ── */
        .ctrl {
            border: 1px solid #e2e8f0; border-radius: 8px; padding: 9px 14px;
            font-size: 0.875rem; background: #fff; color: #374151; outline: none;
            transition: border-color 0.2s, box-shadow 0.2s;
        }
        .ctrl:focus { border-color: #3b82f6; box-shadow: 0 0 0 3px rgba(59,130,246,0.1); }
        .search-wrap { position: relative; }
        .search-wrap svg { position: absolute; left: 12px; top: 50%; transform: translateY(-50%); width: 16px; height: 16px; color: #9ca3af; }
        .search-wrap input { padding-left: 38px; }
        select { appearance: none; background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 24 24' fill='none' stroke='%239ca3af' stroke-width='2'%3E%3Cpath d='M19 9l-7 7-7-7'/%3E%3C/svg%3E"); background-repeat: no-repeat; background-position: right 12px center; padding-right: 32px; }

        /* ── Buttons ── */
        .btn-primary {
            background: #3b82f6; color: #fff; border-radius: 8px; padding: 9px 18px;
            font-size: 0.875rem; font-weight: 600; display: inline-flex; align-items: center;
            gap: 6px; border: none; cursor: pointer; transition: background 0.2s;
        }
        .btn-primary:hover { background: #2563eb; }
        .btn-view {
            border: 1px solid #e2e8f0; border-radius: 7px; padding: 5px 14px;
            font-size: 0.8rem; font-weight: 500; color: #374151; background: #fff;
            cursor: pointer; transition: all 0.15s;
        }
        .btn-view:hover { background: #eff6ff; color: #2563eb; border-color: #bfdbfe; }
        .btn-outline {
            border: 1px solid #e2e8f0; border-radius: 8px; padding: 9px 18px;
            font-size: 0.875rem; font-weight: 500; color: #374151; background: #fff;
            cursor: pointer; transition: background 0.15s;
        }
        .btn-outline:hover { background: #f3f4f6; }
        .btn-back {
            display: inline-flex; align-items: center; gap: 6px;
            padding: 8px 16px; border: 1px solid #e5e7eb; border-radius: 8px;
            font-size: 0.82rem; font-weight: 500; color: #374151; background: #fff;
            cursor: pointer; transition: background 0.15s; text-decoration: none;
        }
        .btn-back:hover { background: #f3f4f6; }

        /* ── Modal ── */
        .modal-overlay { background: rgba(0,0,0,0.4); backdrop-filter: blur(2px); }

        /* ── Sidebar offset ── */
        .main-content { transition: margin-left 0.35s cubic-bezier(0.4,0,0.2,1); }
    </style>
</head>

{{-- Alpine root wraps everything ── --}}
<body x-data="payrollApp()" x-init="init()">

    {{-- SIDEBAR --}}
    @include('payroll_officer.payroll_sidebar')

    {{-- ══════════════════════════════════════════════════
         MAIN CONTENT
    ══════════════════════════════════════════════════ --}}
    <div class="main-content min-h-screen"
         :style="'margin-left: ' + (sidebarCollapsed ? '80px' : '256px')">

        {{-- ── Header ── --}}
        <header class="bg-gradient-to-br from-blue-500 to-blue-700 sticky top-0 z-10 shadow-lg mt-4 mx-4 rounded-2xl overflow-hidden">
            <div class="flex items-center justify-between px-8 py-4">
                <h1 class="text-white font-bold text-xl">Payroll</h1>
                <div class="relative">
                    <button class="w-9 h-9 rounded-full flex items-center justify-center transition-all hover:bg-white/20">
                        <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6 6 0 00-5-5.917V5a1 1 0 10-2 0v.083A6 6 0 006 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>
                        </svg>
                        <span class="absolute -top-1 -right-1 w-4 h-4 bg-red-400 rounded-full text-white text-xs flex items-center justify-center font-bold">4</span>
                    </button>
                </div>
            </div>
        </header>

        {{-- Flash --}}
        @if(session('success'))
        <div class="mx-8 mt-4 p-3 bg-green-50 border border-green-200 rounded-lg text-green-700 text-sm flex items-center gap-2">
            <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
            </svg>
            <span>{{ session('success') }}</span>
        </div>
        @endif


        {{-- ══════════════════════════════════════
             PAGE: PAYROLL LIST (tabs view)
        ══════════════════════════════════════ --}}
        <div x-show="page === 'list'" x-cloak>

            {{-- Tab Bar --}}
            <div class="bg-white px-8 pt-4 anim-2">
                <div class="tab-bar">
                    <button class="tab-btn" :class="activeTab==='payroll-period' && 'active'"
                        @click="activeTab='payroll-period'">Payroll Period</button>
                    <button class="tab-btn" :class="activeTab==='salary-structure' && 'active'"
                        @click="activeTab='salary-structure'">Salary Structure</button>
                    <button class="tab-btn" :class="activeTab==='benefits' && 'active'"
                        @click="activeTab='benefits'">Benefits</button>
                    <button class="tab-btn" :class="activeTab==='contributions' && 'active'"
                        @click="activeTab='contributions'">Contributions</button>
                </div>
            </div>

            {{-- ── TAB 1: PAYROLL PERIOD ── --}}
            <div x-show="activeTab==='payroll-period'" x-cloak class="p-8 tab-content">
                {{-- Summary Cards --}}
                <div class="flex gap-4 mb-7">
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
                        @if($latestPeriod)
                        <div class="sub">Cutoff: {{ \Carbon\Carbon::parse($latestPeriod->start_date)->format('m/d/Y') }} – {{ \Carbon\Carbon::parse($latestPeriod->end_date)->format('m/d/Y') }}</div>
                        @else
                        <div class="sub">Cutoff Period: 02/16/2026 – 02/28/2026</div>
                        @endif
                    </div>
                </div>

                {{-- Toolbar --}}
                <div class="flex items-center justify-between gap-3 mb-4">
                    <div class="search-wrap flex-1 max-w-xs">
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-4.35-4.35M17 11A6 6 0 105 11a6 6 0 0012 0z"/></svg>
                        <input type="text" placeholder="Search" class="ctrl w-full" x-model="periodSearch">
                    </div>
                    <div class="flex items-center gap-3">
                        <select class="ctrl" x-model="periodStatusFilter">
                            <option value="">Status</option>
                            <option value="Pending">Pending</option>
                            <option value="Completed">Completed</option>
                            <option value="Submitted">Submitted</option>
                        </select>
                        <select class="ctrl" x-model="periodYearFilter">
                            @for($y = now()->year; $y >= now()->year - 3; $y--)
                            <option value="{{ $y }}">{{ $y }}</option>
                            @endfor
                        </select>
                        <button class="btn-primary" @click="showAddPeriodModal=true">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                            Add Payroll Period
                        </button>
                    </div>
                </div>

                {{-- Table --}}
                <div class="bg-white rounded-xl border border-gray-100 shadow-sm overflow-hidden">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Period Name</th>
                                <th>Start Date</th>
                                <th>End Date</th>
                                <th>Status</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($periods as $period)
                            <tr>
                                <td class="font-medium text-gray-700">{{ $period->name }}</td>
                                <td class="text-gray-500">{{ \Carbon\Carbon::parse($period->start_date)->format('m/d/Y') }}</td>
                                <td class="text-gray-500">{{ \Carbon\Carbon::parse($period->end_date)->format('m/d/Y') }}</td>
                                <td>
                                    <span class="px-3 py-1 rounded-full text-xs font-medium
                                        @if($period->status==='Pending') badge-pending
                                        @elseif($period->status==='Completed') badge-completed
                                        @else badge-submitted @endif">
                                        {{ $period->status }}
                                    </span>
                                </td>
                                <td class="text-right">
                                    <button class="btn-view"
                                        @click="openPeriodView(
                                            {{ $period->id }},
                                            '{{ addslashes($period->name) }}',
                                            '{{ $period->start_date }}',
                                            '{{ $period->end_date }}',
                                            '{{ $period->status }}'
                                        )">View</button>
                                </td>
                            </tr>
                            @empty
                            {{-- Sample fallback rows --}}
                            @foreach([
                                ['February Payroll Period 2','02/16/2026','02/28/2026','Pending',1],
                                ['February Payroll Period 1','02/01/2026','02/15/2026','Completed',2],
                                ['January Payroll Period 2','01/16/2026','01/31/2026','Completed',3],
                                ['January Payroll Period 1','01/01/2026','01/15/2026','Completed',4],
                            ] as [$pname,$pstart,$pend,$pstatus,$pid])
                            <tr>
                                <td class="font-medium text-gray-700">{{ $pname }}</td>
                                <td class="text-gray-500">{{ $pstart }}</td>
                                <td class="text-gray-500">{{ $pend }}</td>
                                <td>
                                    <span class="px-3 py-1 rounded-full text-xs font-medium
                                        {{ $pstatus==='Pending' ? 'badge-pending' : 'badge-completed' }}">
                                        {{ $pstatus }}
                                    </span>
                                </td>
                                <td class="text-right">
                                    <button class="btn-view"
                                        @click="openPeriodView({{ $pid }}, '{{ $pname }}', '2026-02-16', '2026-02-28', '{{ $pstatus }}')">
                                        View
                                    </button>
                                </td>
                            </tr>
                            @endforeach
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>{{-- /tab payroll-period --}}


            {{-- ── TAB 2: SALARY STRUCTURE ── --}}
            <div x-show="activeTab==='salary-structure'" x-cloak class="p-8 tab-content">
                <div class="flex items-center justify-between gap-3 mb-4">
                    <div class="search-wrap flex-1 max-w-xs">
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-4.35-4.35M17 11A6 6 0 105 11a6 6 0 0012 0z"/></svg>
                        <input type="text" placeholder="Search" class="ctrl w-full">
                    </div>
                    <div class="flex items-center gap-3">
                        <select class="ctrl"><option value="">All Status</option><option>Active</option><option>Inactive</option></select>
                        <select class="ctrl"><option value="">All Types</option><option>Addition</option><option>Deduction</option></select>
                        <button class="btn-primary" @click="showAddItemModal=true">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                            Add Payroll Item
                        </button>
                    </div>
                </div>
                <div class="bg-white rounded-xl border border-gray-100 shadow-sm overflow-hidden">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Payroll Item</th><th>Multiplier</th><th>Type</th><th>Basis</th><th>Status</th><th></th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($payrollItems as $item)
                            <tr>
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
                                    <button class="btn-view" @click="openEditItem({{ $item->id }}, '{{ addslashes($item->name) }}', {{ $item->multiplier }}, '{{ $item->type }}', '{{ $item->basis }}')">Edit</button>
                                </td>
                            </tr>
                            @empty
                            <tr><td colspan="6" class="text-center py-12 text-gray-400 text-sm">No payroll items found.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>


            {{-- ── TAB 3: BENEFITS ── --}}
            <div x-show="activeTab==='benefits'" x-cloak class="p-8 tab-content">
                <div class="flex items-center justify-between gap-3 mb-4">
                    <div class="search-wrap flex-1 max-w-xs">
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-4.35-4.35M17 11A6 6 0 105 11a6 6 0 0012 0z"/></svg>
                        <input type="text" placeholder="Search" class="ctrl w-full">
                    </div>
                    <div class="flex items-center gap-3">
                        <select class="ctrl"><option value="">Status</option><option>Active</option><option>Inactive</option></select>
                        <button class="btn-primary" @click="showAddBenefitModal=true">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                            Add Benefit
                        </button>
                    </div>
                </div>
                <div class="bg-white rounded-xl border border-gray-100 shadow-sm overflow-hidden">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Benefit Item</th><th>Type</th><th>Amount</th><th>Taxable</th><th>Frequency</th><th>Eligibility</th><th>Status</th><th></th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($benefits as $benefit)
                            <tr>
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
                                    <button class="btn-view" @click="openEditBenefit({{ $benefit->id }}, '{{ addslashes($benefit->name) }}', '{{ $benefit->type }}', {{ $benefit->amount }}, '{{ $benefit->tax }}', '{{ $benefit->frequency }}', '{{ addslashes($benefit->eligibility) }}')">Edit</button>
                                </td>
                            </tr>
                            @empty
                            <tr><td colspan="8" class="text-center py-12 text-gray-400 text-sm">No benefits found.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>


            {{-- ── TAB 4: CONTRIBUTIONS ── --}}
            <div x-show="activeTab==='contributions'" x-cloak class="p-8 tab-content">
                <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-12 text-center">
                    <svg class="w-12 h-12 text-gray-300 mx-auto mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 14l6-6m-5.5.5h.01m4.99 5h.01M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16l3.5-2 3.5 2 3.5-2 3.5 2z"/>
                    </svg>
                    <p class="text-gray-400 text-sm">Government contributions will appear here.</p>
                </div>
            </div>

        </div>{{-- /page list --}}


        {{-- ══════════════════════════════════════
             PAGE: VIEW PAYROLL PERIOD
        ══════════════════════════════════════ --}}
        <div x-show="page === 'view'" x-cloak>

            {{-- Breadcrumb + Back --}}
            <div class="px-8 pt-5 pb-2 anim-1">
                <div class="flex items-center gap-3 mb-5">
                    <button class="btn-back" @click="closePeriodView()">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                        </svg>
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

                {{-- Title + Submit Button --}}
                <div class="flex items-center justify-between mb-6">
                    <h2 class="text-2xl font-bold text-gray-800" x-text="viewPeriod.name"></h2>
                    <template x-if="viewPeriod.status !== 'Submitted' && viewPeriod.status !== 'Completed'">
                        <button class="btn-primary" @click="showSubmitConfirm=true">
                            Submit for Approval
                        </button>
                    </template>
                    <template x-if="viewPeriod.status === 'Submitted' || viewPeriod.status === 'Completed'">
                        <span class="px-4 py-2 rounded-lg text-sm font-medium"
                            :class="viewPeriod.status === 'Completed' ? 'bg-green-100 text-green-700' : 'bg-blue-100 text-blue-700'"
                            x-text="viewPeriod.status">
                        </span>
                    </template>
                </div>
            </div>

            {{-- Status + Summary Cards --}}
            <div class="px-8 mb-6 anim-2">
                <div class="grid grid-cols-2 gap-4">

                    {{-- Payroll Processing Status --}}
                    <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-5">
                        <h3 class="text-sm font-semibold text-gray-700 mb-4">Payroll Processing Status</h3>
                        <div class="space-y-4">
                            <div>
                                <div class="flex justify-between mb-1">
                                    <span class="text-sm text-gray-500">Payslips Reviewed &amp; Submitted</span>
                                    <span class="text-sm font-medium text-gray-700">
                                        <span x-text="pvSubmittedCount"></span>/<span x-text="pvTotalCount"></span>
                                    </span>
                                </div>
                                <div class="progress-track">
                                    <div class="progress-fill" :style="'width:' + (pvTotalCount > 0 ? (pvSubmittedCount/pvTotalCount*100) : 0) + '%'"></div>
                                </div>
                            </div>
                            <div>
                                <div class="flex justify-between mb-1">
                                    <span class="text-sm text-gray-500">Payroll Submitted for Approval</span>
                                    <span class="text-sm font-medium text-gray-700"
                                        x-text="(viewPeriod.status === 'Submitted' || viewPeriod.status === 'Completed') ? '1/1' : '0/1'">
                                    </span>
                                </div>
                                <div class="progress-track">
                                    <div class="progress-fill" :style="'width:' + ((viewPeriod.status === 'Submitted' || viewPeriod.status === 'Completed') ? 100 : 0) + '%'"></div>
                                </div>
                            </div>
                            <div>
                                <div class="flex justify-between mb-1">
                                    <span class="text-sm text-gray-500">Finance Approval</span>
                                    <span class="text-sm font-medium text-gray-700"
                                        x-text="viewPeriod.status === 'Completed' ? '1/1' : '0/1'">
                                    </span>
                                </div>
                                <div class="progress-track">
                                    <div class="progress-fill" :style="'width:' + (viewPeriod.status === 'Completed' ? 100 : 0) + '%'"></div>
                                </div>
                            </div>
                            <div>
                                <div class="flex justify-between mb-1">
                                    <span class="text-sm text-gray-500">Disbursement</span>
                                    <span class="text-sm font-medium text-gray-700">0/1</span>
                                </div>
                                <div class="progress-track">
                                    <div class="progress-fill" style="width:0%"></div>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Payroll Summary --}}
                    <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-5">
                        <h3 class="text-sm font-semibold text-gray-700 mb-4">Payroll Summary</h3>
                        <div class="space-y-3">
                            <div class="flex justify-between items-center">
                                <span class="text-sm text-gray-500">Gross Payroll</span>
                                <span class="text-sm font-medium text-gray-700">₱ <span x-text="fmt(pvGrossPayroll)"></span></span>
                            </div>
                            <div class="flex justify-between items-center">
                                <span class="text-sm text-gray-500">Total Deductions</span>
                                <span class="text-sm font-medium text-gray-700">₱ <span x-text="fmt(pvTotalDeductions)"></span></span>
                            </div>
                            <div class="border-t border-gray-100 pt-3 flex justify-between items-center">
                                <span class="text-sm font-semibold text-gray-700">Net Payroll</span>
                                <span class="text-base font-bold text-gray-900">₱ <span x-text="fmt(pvNetPayroll)"></span></span>
                            </div>
                        </div>
                    </div>

                </div>
            </div>

            {{-- Employee Payroll Table + Payslip Panel --}}
            <div class="px-8 pb-10 anim-3">
                <div class="flex gap-4 items-start">

                    {{-- Employee Table --}}
                    <div class="flex-1 min-w-0">
                        <h3 class="text-base font-bold text-gray-800 mb-3">Employee Payroll</h3>
                        <div class="bg-white rounded-xl border border-gray-100 shadow-sm overflow-hidden">
                            <table class="pv-table">
                                <thead>
                                    <tr>
                                        <th>Employee</th>
                                        <th>Gross Pay</th>
                                        <th>Net Pay</th>
                                        <th>Status</th>
                                        <th></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <template x-for="ps in pvPayslips" :key="ps.id">
                                        <tr :class="pvSelectedId === ps.id && 'row-active'"
                                            @click="pvSelectPayslip(ps)">
                                            <td class="font-medium text-gray-700" x-text="ps.employeeName"></td>
                                            <td class="text-gray-600">₱<span x-text="fmt(ps.grossPay)"></span></td>
                                            <td class="text-gray-600">₱<span x-text="fmt(ps.netPay)"></span></td>
                                            <td>
                                                <span class="px-3 py-1 rounded-full text-xs font-medium"
                                                    :class="ps.status === 'Submitted' ? 'badge-submitted' : 'badge-pending'"
                                                    x-text="ps.status">
                                                </span>
                                            </td>
                                            <td class="text-right">
                                                <button class="btn-view" @click.stop="pvSelectPayslip(ps)">View</button>
                                            </td>
                                        </tr>
                                    </template>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    {{-- Payslip Detail Panel --}}
                    <div class="w-80 flex-shrink-0" x-show="pvSelectedId !== null" x-cloak>
                        <div class="bg-white rounded-xl border border-gray-100 shadow-sm overflow-hidden slide-in-right">

                            {{-- Blue Header --}}
                            <div class="payslip-header">
                                <div class="text-lg font-bold mb-0.5">Medisource</div>
                                <div class="text-xs text-blue-100 mb-4" x-text="pvPeriodSubtitle"></div>
                                <div class="grid grid-cols-3 gap-1">
                                    <div>
                                        <div class="info-label">Employee</div>
                                        <div class="info-value" x-text="pvActive.employeeName || '—'"></div>
                                    </div>
                                    <div>
                                        <div class="info-label">Job Title</div>
                                        <div class="info-value" x-text="pvActive.jobTitle || '—'"></div>
                                    </div>
                                    <div>
                                        <div class="info-label">Department</div>
                                        <div class="info-value" x-text="pvActive.department || '—'"></div>
                                    </div>
                                </div>
                            </div>

                            {{-- Body --}}
                            <div class="payslip-body">
                                {{-- Earnings --}}
                                <div class="payslip-section-title">Earnings</div>
                                <div class="payslip-line"><span>Basic Pay</span><span x-text="'₱ ' + fmt(pvActive.basicPay || 0)"></span></div>
                                <div class="payslip-line"><span>OT Pay</span><span x-text="'₱ ' + fmt(pvActive.otPay || 0)"></span></div>
                                <div class="payslip-line"><span>Benefits</span><span x-text="'₱ ' + fmt(pvActive.benefits || 0)"></span></div>
                                <div class="payslip-line bold"><span>Gross Pay</span><span x-text="'₱ ' + fmt(pvActive.grossPay || 0)"></span></div>

                                {{-- Deductions --}}
                                <div class="payslip-section-title">Deductions</div>
                                <div class="payslip-line"><span>SSS</span><span class="text-red-500" x-text="'-₱ ' + fmt(pvActive.sss || 0)"></span></div>
                                <div class="payslip-line"><span>PhilHealth</span><span class="text-red-500" x-text="'-₱ ' + fmt(pvActive.philhealth || 0)"></span></div>
                                <div class="payslip-line"><span>Pag-IBIG</span><span class="text-red-500" x-text="'-₱ ' + fmt(pvActive.pagibig || 0)"></span></div>
                                <div class="payslip-line"><span>Withholding Tax</span><span class="text-red-500" x-text="'-₱ ' + fmt(pvActive.withholdingTax || 0)"></span></div>
                                <div class="payslip-line bold"><span>Total Deductions</span><span class="text-red-500" x-text="'-₱ ' + fmt(pvActive.totalDeductions || 0)"></span></div>

                                {{-- Calculation --}}
                                <div class="payslip-section-title">Calculation</div>
                                <div class="payslip-line"><span>Earnings</span><span x-text="'₱ ' + fmt(pvActive.grossPay || 0)"></span></div>
                                <div class="payslip-line"><span>Deductions</span><span class="text-red-500" x-text="'-₱ ' + fmt(pvActive.totalDeductions || 0)"></span></div>
                                <div class="payslip-line bold"><span>Net Pay</span><span x-text="'₱ ' + fmt(pvActive.netPay || 0)"></span></div>

                                {{-- Actions --}}
                                <div class="flex gap-2 mt-5">
                                    <button class="btn-outline flex-1" @click="pvEditPayslip()">Edit</button>
                                    <template x-if="pvActive.status !== 'Submitted'">
                                        <button class="btn-primary flex-1 justify-center" @click="pvSubmitPayslip()">Submit</button>
                                    </template>
                                    <template x-if="pvActive.status === 'Submitted'">
                                        <span class="flex-1 text-center py-2 text-sm font-medium text-green-600 bg-green-50 rounded-lg border border-green-100">
                                            Submitted ✓
                                        </span>
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

    {{-- Add Payroll Period --}}
    <div x-show="showAddPeriodModal" x-cloak class="fixed inset-0 z-50 flex items-center justify-center modal-overlay" @click.self="showAddPeriodModal=false">
        <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md mx-4 p-7"
             x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100"
             x-transition:leave="transition ease-in duration-150"  x-transition:leave-start="opacity-100 scale-100" x-transition:leave-end="opacity-0 scale-95">
            <div class="flex items-center justify-between mb-6">
                <h2 class="text-lg font-bold text-gray-800">Add Payroll Period</h2>
                <button @click="showAddPeriodModal=false" class="text-gray-400 hover:text-gray-600">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>
            <form action="{{ route('payroll_officer.payroll.period.store') }}" method="POST" class="space-y-4">
                @csrf
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1.5">Payroll Period Name</label>
                    <input type="text" name="name" required placeholder="Enter payroll period name" class="ctrl w-full">
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1.5">Start Date</label>
                        <input type="date" name="start_date" required class="ctrl w-full">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1.5">End Date</label>
                        <input type="date" name="end_date" required class="ctrl w-full">
                    </div>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1.5">Payout Date</label>
                    <input type="date" name="payout_date" required class="ctrl w-full">
                </div>
                <div class="flex justify-end gap-3 pt-2">
                    <button type="button" @click="showAddPeriodModal=false" class="btn-outline">Cancel</button>
                    <button type="submit" class="btn-primary">Submit</button>
                </div>
            </form>
        </div>
    </div>

    {{-- Add Payroll Item --}}
    <div x-show="showAddItemModal" x-cloak class="fixed inset-0 z-50 flex items-center justify-center modal-overlay" @click.self="showAddItemModal=false">
        <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md mx-4 p-7"
             x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100">
            <div class="flex items-center justify-between mb-6">
                <h2 class="text-lg font-bold text-gray-800">Add Payroll Item</h2>
                <button @click="showAddItemModal=false" class="text-gray-400 hover:text-gray-600">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>
            <form action="{{ route('payroll_officer.payroll.item.store') }}" method="POST" class="space-y-4">
                @csrf
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1.5">Payroll Item</label>
                    <input type="text" name="name" required placeholder="Enter payroll item name" class="ctrl w-full">
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1.5">Multiplier</label>
                        <input type="number" name="multiplier" step="0.01" required placeholder="e.g. 1.25" class="ctrl w-full">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1.5">Type</label>
                        <select name="type" required class="ctrl w-full">
                            <option value="">Choose type</option>
                            <option value="Addition">Addition</option>
                            <option value="Deduction">Deduction</option>
                        </select>
                    </div>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1.5">Basis</label>
                    <input type="text" name="basis" required placeholder="Enter basis" class="ctrl w-full">
                </div>
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
                <button @click="showEditItemModal=false" class="text-gray-400 hover:text-gray-600">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>
            <form :action="`/payroll_officer/payroll/item/${editItem.id}/update`" method="POST" class="space-y-4">
                @csrf
                @method('PUT')
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1.5">Payroll Item</label>
                    <input type="text" name="name" x-model="editItem.name" required class="ctrl w-full">
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1.5">Multiplier</label>
                        <input type="number" name="multiplier" step="0.01" x-model="editItem.multiplier" required class="ctrl w-full">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1.5">Type</label>
                        <select name="type" x-model="editItem.type" required class="ctrl w-full">
                            <option value="Addition">Addition</option>
                            <option value="Deduction">Deduction</option>
                        </select>
                    </div>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1.5">Basis</label>
                    <input type="text" name="basis" x-model="editItem.basis" required class="ctrl w-full">
                </div>
                <div class="flex items-center justify-between pt-2">
                    <button type="button" @click="deactivateItem()" class="text-blue-500 border border-blue-200 px-4 py-2 rounded-lg text-sm font-medium hover:bg-blue-50 transition">Deactivate</button>
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
                <button @click="showAddBenefitModal=false" class="text-gray-400 hover:text-gray-600">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>
            <form action="{{ route('payroll_officer.payroll.benefit.store') }}" method="POST" class="space-y-4">
                @csrf
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1.5">Benefit Name</label>
                    <input type="text" name="name" required placeholder="Enter benefit name" class="ctrl w-full">
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1.5">Type</label>
                        <select name="type" required class="ctrl w-full">
                            <option value="">Choose type</option>
                            <option value="Allowance">Allowance</option>
                            <option value="Bonus">Bonus</option>
                            <option value="Incentive">Incentive</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1.5">Amount</label>
                        <input type="number" name="amount" step="0.01" required placeholder="₱0.00" class="ctrl w-full">
                    </div>
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1.5">Tax</label>
                        <select name="tax" required class="ctrl w-full">
                            <option value="Non-taxable">Non-taxable</option>
                            <option value="Taxable">Taxable</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1.5">Frequency</label>
                        <input type="text" name="frequency" required placeholder="e.g. Monthly" class="ctrl w-full">
                    </div>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1.5">Eligibility</label>
                    <input type="text" name="eligibility" required placeholder="e.g. All regular employees" class="ctrl w-full">
                </div>
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
                <button @click="showEditBenefitModal=false" class="text-gray-400 hover:text-gray-600">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>
            <form :action="`/payroll_officer/payroll/benefit/${editBenefit.id}/update`" method="POST" class="space-y-4">
                @csrf
                @method('PUT')
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1.5">Benefit Name</label>
                    <input type="text" name="name" x-model="editBenefit.name" required class="ctrl w-full">
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1.5">Type</label>
                        <select name="type" x-model="editBenefit.type" required class="ctrl w-full">
                            <option value="Allowance">Allowance</option>
                            <option value="Bonus">Bonus</option>
                            <option value="Incentive">Incentive</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1.5">Amount</label>
                        <input type="number" name="amount" step="0.01" x-model="editBenefit.amount" required class="ctrl w-full">
                    </div>
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1.5">Tax</label>
                        <select name="tax" x-model="editBenefit.tax" required class="ctrl w-full">
                            <option value="Non-taxable">Non-taxable</option>
                            <option value="Taxable">Taxable</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1.5">Frequency</label>
                        <input type="text" name="frequency" x-model="editBenefit.frequency" required class="ctrl w-full">
                    </div>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1.5">Eligibility</label>
                    <input type="text" name="eligibility" x-model="editBenefit.eligibility" required class="ctrl w-full">
                </div>
                <div class="flex items-center justify-between pt-2">
                    <button type="button" @click="deactivateBenefit()" class="text-blue-500 border border-blue-200 px-4 py-2 rounded-lg text-sm font-medium hover:bg-blue-50 transition">Deactivate</button>
                    <div class="flex gap-3">
                        <button type="button" @click="showEditBenefitModal=false" class="btn-outline">Cancel</button>
                        <button type="submit" class="btn-primary">Submit</button>
                    </div>
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
                <form :action="`/payroll_officer/payroll/period/${viewPeriod.id}/submit`" method="POST">
                    @csrf
                    <button type="submit" class="btn-primary px-8">Confirm</button>
                </form>
            </div>
        </div>
    </div>


    {{-- Edit Payslip Modal --}}
    <div x-show="showEditPayslipModal" x-cloak class="fixed inset-0 z-50 flex items-center justify-center modal-overlay" @click.self="showEditPayslipModal=false">
        <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md mx-4 p-7"
             x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100">
            <div class="flex items-center justify-between mb-6">
                <div>
                    <h2 class="text-lg font-bold text-gray-800">Edit Payslip</h2>
                    <p class="text-sm text-gray-400" x-text="editPayslip.employeeName"></p>
                </div>
                <button @click="showEditPayslipModal=false" class="text-gray-400 hover:text-gray-600">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>
            <div class="space-y-4">
                <p class="text-xs font-bold text-gray-400 uppercase tracking-widest">Earnings</p>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1.5">Basic Pay</label>
                        <div class="relative">
                            <span class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 text-sm">&#8369;</span>
                            <input type="number" step="0.01" x-model="editPayslip.basicPay" @input="recalcPayslip()" class="ctrl w-full pl-7">
                        </div>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1.5">OT Pay <span class="text-gray-300 font-normal">(read-only)</span></label>
                        <div class="relative">
                            <span class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 text-sm">&#8369;</span>
                            <input type="text" :value="fmt(editPayslip.otPay)" readonly class="ctrl w-full pl-7 bg-gray-50 text-gray-400 cursor-not-allowed">
                        </div>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1.5">Benefits</label>
                        <div class="relative">
                            <span class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 text-sm">&#8369;</span>
                            <input type="number" step="0.01" x-model="editPayslip.benefits" @input="recalcPayslip()" class="ctrl w-full pl-7">
                        </div>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1.5">Gross Pay <span class="text-gray-300 font-normal">(auto)</span></label>
                        <div class="relative">
                            <span class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 text-sm">&#8369;</span>
                            <input type="text" :value="fmt(editPayslip.grossPay)" readonly class="ctrl w-full pl-7 bg-gray-50 text-gray-400 cursor-not-allowed">
                        </div>
                    </div>
                </div>
                <p class="text-xs font-bold text-gray-400 uppercase tracking-widest pt-2">Deductions</p>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1.5">SSS</label>
                        <div class="relative">
                            <span class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 text-sm">&#8369;</span>
                            <input type="number" step="0.01" x-model="editPayslip.sss" @input="recalcPayslip()" class="ctrl w-full pl-7">
                        </div>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1.5">PhilHealth</label>
                        <div class="relative">
                            <span class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 text-sm">&#8369;</span>
                            <input type="number" step="0.01" x-model="editPayslip.philhealth" @input="recalcPayslip()" class="ctrl w-full pl-7">
                        </div>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1.5">Pag-IBIG</label>
                        <div class="relative">
                            <span class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 text-sm">&#8369;</span>
                            <input type="number" step="0.01" x-model="editPayslip.pagibig" @input="recalcPayslip()" class="ctrl w-full pl-7">
                        </div>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1.5">Withholding Tax</label>
                        <div class="relative">
                            <span class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 text-sm">&#8369;</span>
                            <input type="number" step="0.01" x-model="editPayslip.withholdingTax" @input="recalcPayslip()" class="ctrl w-full pl-7">
                        </div>
                    </div>
                </div>
                <div class="bg-gray-50 rounded-xl p-4 space-y-2">
                    <div class="flex justify-between text-sm text-gray-500">
                        <span>Total Deductions</span>
                        <span class="text-red-500 font-medium">-&#8369; <span x-text="fmt(editPayslip.totalDeductions)"></span></span>
                    </div>
                    <div class="flex justify-between text-sm font-bold text-gray-800 border-t border-gray-200 pt-2">
                        <span>Net Pay</span>
                        <span>&#8369; <span x-text="fmt(editPayslip.netPay)"></span></span>
                    </div>
                </div>
                <div class="flex justify-end gap-3 pt-2">
                    <button type="button" @click="showEditPayslipModal=false" class="btn-outline">Cancel</button>
                    <button type="button" class="btn-primary" @click="saveEditPayslip()">Save Changes</button>
                </div>
            </div>
        </div>
    </div>

    {{-- ════════════════════════════════════════
         ALPINE JS
    ════════════════════════════════════════ --}}
    <script>
    function payrollApp() {
        return {
            // ── Sidebar ──
            sidebarCollapsed: localStorage.getItem('sidebarCollapsed') === 'true',

            // ── Page routing (list | view) ──
            page: 'list',

            // ── List page state ──
            activeTab:          '{{ request("tab", "payroll-period") }}',
            periodSearch:       '',
            periodStatusFilter: '',
            periodYearFilter:   '{{ $year }}',

            // ── Modals ──
            showAddPeriodModal:   false,
            showAddItemModal:     false,
            showEditItemModal:    false,
            showAddBenefitModal:  false,
            showEditBenefitModal: false,
            showSubmitConfirm:    false,
            showEditPayslipModal: false,

            editPayslip: { id: null, employeeName: '', basicPay: 0, otPay: 0, benefits: 0, grossPay: 0, sss: 0, philhealth: 0, pagibig: 0, withholdingTax: 0, totalDeductions: 0, netPay: 0 },

            // ── Edit item/benefit forms ──
            editItem:    { id: null, name: '', multiplier: '', type: '', basis: '' },
            editBenefit: { id: null, name: '', type: '', amount: '', tax: '', frequency: '', eligibility: '' },

            // ── Period View state ──
            viewPeriod:        { id: null, name: '', startDate: '', endDate: '', status: 'Pending' },
            pvPayslips:        [],
            pvSelectedId:      null,
            pvActive:          {},
            pvGrossPayroll:    0,
            pvTotalDeductions: 0,
            pvNetPayroll:      0,
            pvSubmittedCount:  0,
            pvTotalCount:      0,
            pvPeriodSubtitle:  '',

            samplePayslips: @json($payslipsJson ?? null),

            defaultPayslips: [
                {id:1,employeeName:'Juan Dela Cruz',jobTitle:'Senior Programmer',department:'IT',basicPay:23655,otPay:1425,benefits:3000,grossPay:28090,sss:1125,philhealth:500,pagibig:200,withholdingTax:2995,totalDeductions:4820,netPay:23680,status:'Submitted'},
                {id:2,employeeName:'Maria Santos',jobTitle:'Nurse',department:'Medical',basicPay:23655,otPay:845,benefits:2000,grossPay:26500,sss:1125,philhealth:500,pagibig:200,withholdingTax:2500,totalDeductions:4325,netPay:22175,status:'Pending'},
                {id:3,employeeName:'Pedro Reyes',jobTitle:'Accountant',department:'Finance',basicPay:23655,otPay:0,benefits:2000,grossPay:25655,sss:1125,philhealth:500,pagibig:200,withholdingTax:2200,totalDeductions:4025,netPay:21630,status:'Pending'},
                {id:4,employeeName:'Ana Ramos',jobTitle:'HR Officer',department:'HR',basicPay:20000,otPay:500,benefits:1500,grossPay:22000,sss:900,philhealth:440,pagibig:200,withholdingTax:1800,totalDeductions:3340,netPay:18660,status:'Pending'},
                {id:5,employeeName:'Carlo Mendoza',jobTitle:'Sales Rep',department:'Sales',basicPay:18000,otPay:1200,benefits:1000,grossPay:20200,sss:810,philhealth:404,pagibig:200,withholdingTax:1600,totalDeductions:3014,netPay:17186,status:'Pending'},
                {id:6,employeeName:'Liza Cruz',jobTitle:'Pharmacist',department:'Pharmacy',basicPay:25000,otPay:0,benefits:2500,grossPay:27500,sss:1125,philhealth:500,pagibig:200,withholdingTax:2800,totalDeductions:4625,netPay:22875,status:'Pending'},
                {id:7,employeeName:'Rico Torres',jobTitle:'Driver',department:'Logistics',basicPay:15000,otPay:750,benefits:500,grossPay:16250,sss:675,philhealth:325,pagibig:200,withholdingTax:900,totalDeductions:2100,netPay:14150,status:'Pending'},
            ],

            init() {
                window.addEventListener('storage', () => {
                    this.sidebarCollapsed = localStorage.getItem('sidebarCollapsed') === 'true';
                });
            },

            // ── Open period view ──
            openPeriodView(id, name, startDate, endDate, status) {
                this.viewPeriod = { id, name, startDate, endDate, status };

                // Use server-injected payslips if available, else fallback to defaults
                this.pvPayslips = (this.samplePayslips && this.samplePayslips.length > 0)
                    ? this.samplePayslips
                    : this.defaultPayslips;

                // Compute summary totals
                this.pvTotalCount      = this.pvPayslips.length;
                this.pvSubmittedCount  = this.pvPayslips.filter(p => p.status === 'Submitted').length;
                this.pvGrossPayroll    = this.pvPayslips.reduce((s, p) => s + p.grossPay, 0);
                this.pvTotalDeductions = this.pvPayslips.reduce((s, p) => s + p.totalDeductions, 0);
                this.pvNetPayroll      = this.pvPayslips.reduce((s, p) => s + p.netPay, 0);

                // Period subtitle for payslip header
                const start = new Date(startDate);
                const end   = new Date(endDate);
                const month = start.toLocaleString('en-US', { month: 'long' });
                const year  = start.getFullYear();
                this.pvPeriodSubtitle = `${month} ${year} · ${name} · ${start.toLocaleString('en-US',{month:'short'})} ${start.getDate()}–${end.getDate()}`;

                // Auto-select first row
                if (this.pvPayslips.length > 0) {
                    this.pvSelectPayslip(this.pvPayslips[0]);
                }

                this.page = 'view';
                window.scrollTo({ top: 0, behavior: 'smooth' });
            },

            closePeriodView() {
                this.page         = 'list';
                this.pvSelectedId = null;
                this.pvActive     = {};
                window.scrollTo({ top: 0, behavior: 'smooth' });
            },

            pvSelectPayslip(ps) {
                this.pvSelectedId = ps.id;
                this.pvActive     = { ...ps };
            },

            pvEditPayslip() {
                this.editPayslip = { ...this.pvActive };
                this.showEditPayslipModal = true;
            },

            recalcPayslip() {
                const bp  = parseFloat(this.editPayslip.basicPay)       || 0;
                const ot  = parseFloat(this.editPayslip.otPay)          || 0;
                const ben = parseFloat(this.editPayslip.benefits)        || 0;
                const sss = parseFloat(this.editPayslip.sss)            || 0;
                const ph  = parseFloat(this.editPayslip.philhealth)      || 0;
                const pi  = parseFloat(this.editPayslip.pagibig)         || 0;
                const wt  = parseFloat(this.editPayslip.withholdingTax)  || 0;

                this.editPayslip.grossPay       = bp + ot + ben;
                this.editPayslip.totalDeductions = sss + ph + pi + wt;
                this.editPayslip.netPay          = this.editPayslip.grossPay - this.editPayslip.totalDeductions;
            },

            saveEditPayslip() {
                // Update the payslips array
                const idx = this.pvPayslips.findIndex(p => p.id === this.editPayslip.id);
                if (idx !== -1) {
                    this.pvPayslips[idx] = { ...this.pvPayslips[idx], ...this.editPayslip };
                }
                // Update the active panel
                this.pvActive = { ...this.editPayslip };
                // Recompute summary totals
                this.pvGrossPayroll    = this.pvPayslips.reduce((s, p) => s + p.grossPay, 0);
                this.pvTotalDeductions = this.pvPayslips.reduce((s, p) => s + p.totalDeductions, 0);
                this.pvNetPayroll      = this.pvPayslips.reduce((s, p) => s + p.netPay, 0);
                this.showEditPayslipModal = false;
            },

            pvSubmitPayslip() {
                if (!confirm('Submit payslip for ' + this.pvActive.employeeName + '?')) return;
                fetch(`/payroll_officer/payroll/payslip/${this.pvActive.id}/submit`, {
                    method: 'POST',
                    headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content }
                })
                .then(r => r.json())
                .then(() => {
                    const ps = this.pvPayslips.find(p => p.id === this.pvActive.id);
                    if (ps) ps.status = 'Submitted';
                    this.pvActive.status  = 'Submitted';
                    this.pvSubmittedCount = this.pvPayslips.filter(p => p.status === 'Submitted').length;
                });
            },

            // ── Edit item/benefit helpers ──
            openEditItem(id, name, multiplier, type, basis) {
                this.editItem = { id, name, multiplier, type, basis };
                this.showEditItemModal = true;
            },
            openEditBenefit(id, name, type, amount, tax, frequency, eligibility) {
                this.editBenefit = { id, name, type, amount, tax, frequency, eligibility };
                this.showEditBenefitModal = true;
            },
            deactivateItem() {
                if (!confirm('Deactivate this payroll item?')) return;
                fetch(`/payroll_officer/payroll/item/${this.editItem.id}/deactivate`, {
                    method: 'POST',
                    headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content }
                }).then(() => location.reload());
            },
            deactivateBenefit() {
                if (!confirm('Deactivate this benefit?')) return;
                fetch(`/payroll_officer/payroll/benefit/${this.editBenefit.id}/deactivate`, {
                    method: 'POST',
                    headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content }
                }).then(() => location.reload());
            },

            fmt(n) {
                return Number(n).toLocaleString('en-PH', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
            },
        }
    }
    </script>

</body>
</html>
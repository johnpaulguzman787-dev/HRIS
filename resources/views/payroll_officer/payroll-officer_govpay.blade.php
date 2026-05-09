{{-- resources/views/payroll_officer/payroll-officer_govpay.blade.php --}}
<!DOCTYPE html>
<html lang="en">
<head>
    <link rel="icon" type="image/png" href="{{ asset('images/HRISLogo-Icon.png') }}">
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Government Contributions – MediSource</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet"/>
    <style>
        * { font-family: 'DM Sans', sans-serif; box-sizing: border-box; }
        body { background: #eef2f7; }
        :root {
            --blue: #3b82f6;
            --blue-dark: #1d4ed8;
            --blue-light: #eff6ff;
            --muted: #6b7280;
            --border: #e5e7eb;
        }

        .tabs-wrapper {display: flex; border-bottom: 2px solid var(--border); margin-bottom:20px ;}
        .tab-btn {
            padding: 10px 16px;
            font-size: 13px;
            font-weight: 500 !important;
            color: var(--muted);
            border: none;
            background: none;
            cursor: pointer;
            font-family: inherit;
            border-bottom: 1px solid transparent;
            margin-bottom: -2px;
            white-space: nowrap;
            text-decoration: none;
            display: inline-block;
            transition: color .15s, border-color .15s;
        }
        .tab-btn:hover { color: #374151; }
        .tab-btn.active { color: var(--blue); border-bottom-color: var(--blue); font-weight: 700 !important; }

        /* ── Card ── */
        .card {
            background: #fff;
            border-radius: 14px;
            border: 1px solid #e5e7eb;
            box-shadow: 0 1px 4px rgba(0,0,0,0.05);
            overflow: hidden;
        }
        .card-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 20px 28px;
            border-bottom: 1px solid #f1f5f9;
        }
        .card-title { font-size: 1.05rem; font-weight: 700; color: #111827; }

        .table-wrapper {
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
        }
        .contrib-table {
            width: 100%;
            border-collapse: collapse;
            min-width: 700px;
        }
        .contrib-table thead th {
            padding: 13px 24px;
            font-size: 0.75rem;
            font-weight: 600;
            color: #9ca3af;
            text-transform: uppercase;
            letter-spacing: 0.06em;
            white-space: nowrap;
            border-bottom: 1px solid #f1f5f9;
        }
        .contrib-table tbody tr {
            border-bottom: 1px solid #f3f4f6;
            transition: background 0.1s;
        }
        .contrib-table tbody tr:last-child { border-bottom: none; }
        .contrib-table tbody tr:hover { background: #f8faff; }
        .contrib-table tbody td {
            padding: 16px 24px;
            font-size: 0.875rem;
            color: #4b5563;
        }
        .contrib-table tbody td.td-name { font-weight: 500; color: #1f2937; }
        .td-center { text-align: center; }
        .td-right  { text-align: right; }

        /* ── Badges ── */
        .badge-released {
            display: inline-flex; align-items: center;
            background: #d1fae5; color: #065f46;
            padding: 4px 14px; border-radius: 9999px;
            font-size: 0.73rem; font-weight: 600; white-space: nowrap;
        }
        .badge-pending {
            display: inline-flex; align-items: center;
            background: #fef9c3; color: #92400e;
            padding: 4px 14px; border-radius: 9999px;
            font-size: 0.73rem; font-weight: 600; white-space: nowrap;
        }

        /* ── Year select ── */
        .year-select {
            appearance: none; -webkit-appearance: none;
            background: #f8fafc;
            border: 1.5px solid #e2e8f0;
            border-radius: 9px;
            padding: 8px 36px 8px 14px;
            font-size: 0.85rem; color: #374151; font-weight: 600;
            cursor: pointer; outline: none; font-family: inherit;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 24 24' stroke='%236b7280'%3E%3Cpath stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M19 9l-7 7-7-7'/%3E%3C/svg%3E");
            background-repeat: no-repeat; background-position: right 10px center; background-size: 15px;
        }
        .year-select:focus { border-color: #3b82f6; box-shadow: 0 0 0 3px rgba(59,130,246,0.1); }

        .btn-view {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            background: #eff6ff !important;
            color: #2563eb !important;
            border: 1.5px solid #93c5fd;
            padding: 8px 18px;
            border-radius: 7px;
            font-size: 0.775rem;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            white-space: nowrap;
            transition: background 0.15s, color 0.15s, border-color 0.15s;
        }
        .btn-view:hover {
            background: #2563eb !important;
            color: #fff !important;
            border-color: #2563eb !important;
        }

        /* ── Back button ── */
        .btn-back {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            background: #f8fafc;
            border: 1.5px solid #e2e8f0;
            border-radius: 9px;
            padding: 8px 18px;
            color: #374151;
            font-size: 0.84rem;
            font-weight: 500;
            cursor: pointer;
            white-space: nowrap;
            text-decoration: none;
            transition: background 0.15s, color 0.15s;
        }
        .btn-back:hover {
            background: #e2e8f0;
            color: #1d4ed8;
        }

        /* ── Breadcrumb ── */
        .breadcrumb {
            display: flex;
            align-items: center;
            gap: 6px;
            font-size: 0.875rem;
        }
        .breadcrumb a {
            color: #9ca3af;
            text-decoration: none;
        }
        .breadcrumb a:hover {
            color: #6b7280;
        }
        .breadcrumb .sep {
            color: #d1d5db;
        }
        .breadcrumb .current {
            font-weight: 600;
            color: #374151;
        }

        /* ── Transition for margin ── */
        .transition-margin {
            transition: margin-left 0.35s cubic-bezier(0.4, 0, 0.2, 1);
        }

        /* ── Mobile responsiveness ── */
        @media (max-width: 768px) {
            .card-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 12px;
                padding: 16px 20px;
            }
            .card-header .flex.items-center.gap-3 {
                width: 100%;
            }
            .card-title {
                font-size: 0.95rem;
            }
            .year-select {
                width: 100%;
            }
            .tab-btn {
                font-size: 0.8rem;
            }
            .btn-back {
                font-size: 0.75rem;
                padding: 6px 12px;
            }
            .breadcrumb {
                font-size: 0.75rem;
            }
            .p-8 {
                padding: 1rem;
            }
            .card-header {
                flex-direction: column;
                align-items: stretch;
                gap: 12px;
                padding: 16px 20px;
            }
            .card-header .card-title {
                margin-bottom: 0;
                display: block;
                width: 100%;
            }
            .card-header .flex.items-center.gap-3 {
                width: 100%;
                display: flex;
                flex-wrap: nowrap;
                gap: 12px;
            }
            .card-header .flex.items-center.gap-3 > * {
                flex: 1;
                min-width: 0;
            }
            .card-header .flex.items-center.gap-3 form {
                flex: 1;
                display: flex;
            }
            .card-header .flex.items-center.gap-3 .year-select {
                width: 100%;
                min-width: 0;
            }
            .btn-view {
                justify-content: center;
            }
        }
    </style>
</head>
@php
    $isView = isset($records);
    if ($isView) {
        $exportRecords = collect($records)->map(fn($r) => [
            'name'       => $r->fname . ' ' . $r->lname,
            'sss'        => number_format($r->sss ?? 0, 2),
            'philhealth' => number_format($r->philhealth ?? 0, 2),
            'pagibig'    => number_format($r->pagibig ?? 0, 2),
            'tax'        => number_format($r->tax ?? 0, 2),
            'status'     => ucfirst($r->status ?? 'Pending'),
        ])->values()->toArray();
        $exportTotals = [
            'sss'        => number_format(collect($records)->sum('sss'), 2),
            'philhealth' => number_format(collect($records)->sum('philhealth'), 2),
            'pagibig'    => number_format(collect($records)->sum('pagibig'), 2),
            'tax'        => number_format(collect($records)->sum('tax'), 2),
        ];
    } else {
        $exportContributions = collect($contributions)->map(fn($r) => [
            'period'     => $r->period_name,
            'sss'        => number_format($r->sss_total, 2),
            'philhealth' => number_format($r->philhealth_total, 2),
            'pagibig'    => number_format($r->pagibig_total, 2),
            'tax'        => number_format($r->tax_total, 2),
            'status'     => ucfirst($r->status ?? 'Pending'),
        ])->values()->toArray();
        $exportTotals = [
            'sss'        => number_format(collect($contributions)->sum('sss_total'), 2),
            'philhealth' => number_format(collect($contributions)->sum('philhealth_total'), 2),
            'pagibig'    => number_format(collect($contributions)->sum('pagibig_total'), 2),
            'tax'        => number_format(collect($contributions)->sum('tax_total'), 2),
        ];
        $exportMyContributions = collect($myContributions)->map(fn($r) => [
            'period'     => $r->period_name,
            'sss'        => number_format($r->sss ?? 0, 2),
            'philhealth' => number_format($r->philhealth ?? 0, 2),
            'pagibig'    => number_format($r->pagibig ?? 0, 2),
            'tax'        => number_format($r->tax ?? 0, 2),
            'status'     => ucfirst($r->status ?? 'Pending'),
        ])->values()->toArray();
        $exportMyTotals = [
            'sss'        => number_format(collect($myContributions)->sum('sss'), 2),
            'philhealth' => number_format(collect($myContributions)->sum('philhealth'), 2),
            'pagibig'    => number_format(collect($myContributions)->sum('pagibig'), 2),
            'tax'        => number_format(collect($myContributions)->sum('tax'), 2),
        ];
    }
@endphp

<body x-data="govpayApp()" x-init="init()" class="flex h-screen overflow-hidden bg-gray-50">

    <!-- ===================== DESKTOP SIDEBAR ===================== -->
    <div class="hidden lg:block">
        @include('payroll_officer.payroll_sidebar')
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
            @include('payroll_officer.payroll_sidebar')
        </div>
    </div>

    <!-- ===================== MAIN CONTENT ===================== -->
    <div class="flex-1 overflow-y-auto min-h-screen w-full transition-margin"
         :class="sidebarCollapsed ? 'lg:ml-20' : 'lg:ml-64'"
         style="transition: margin-left 0.35s cubic-bezier(0.4, 0, 0.2, 1);">

        <!-- Blue Header with Hamburger -->
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
                        <h1 class="text-xl sm:text-2xl font-bold text-white">Government Contributions</h1>
                        <p class="text-xs sm:text-sm text-blue-100 mt-1">Contributions Overview</p>
                    </div>
                </div>
                <div class="flex items-center space-x-2 sm:space-x-4">
                    <x-notification-bell />
                </div>
            </div>
        </header>

        {{-- ══════════════════════════════════════
             LIST VIEW
        ══════════════════════════════════════ --}}
        @if(!$isView)

        <div class="p-3 lg:p-6">
            <!-- Tab Bar -->
            <div class="tabs-wrapper">
                    <button id="tabAll"  class="tab-btn active" onclick="switchTab('all')">All Contributions</button>
                    <button id="tabMine" class="tab-btn"        onclick="switchTab('mine')">My Contributions</button>
            </div>



                {{-- ALL CONTRIBUTIONS --}}
                <div id="panelAll">
                    <div class="card">
                        <div class="card-header">
                            <span class="card-title">Contribution Summary</span>
                            <div class="flex items-center gap-3">
                                <button onclick="exportGovpay()" class="btn-view" style="background:#2563eb;color:#fff;border-color:#2563eb;">
                                    <svg style="width:14px;height:14px;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                                    Export PDF
                                </button>
                                <form method="GET" action="{{ route('payroll_officer.govpay') }}">
                                    <select name="year" class="year-select" onchange="this.form.submit()">
                                        @foreach($years as $y)
                                            <option value="{{ $y }}" {{ $year == $y ? 'selected' : '' }}>{{ $y }}</option>
                                        @endforeach
                                    </select>
                                </form>
                            </div>
                        </div>
                        <div class="table-wrapper">
                            <table class="contrib-table">
                                <thead>
                                    <tr class="border-t border-b border-gray-100 bg-gray-50/60">
                                        <th class="text-left">Period Name</th>
                                        <th class="td-center">SSS Total</th>
                                        <th class="td-center">PhilHealth Total</th>
                                        <th class="td-center">Pag-IBIG Total</th>
                                        <th class="td-center">W/ Tax Total</th>
                                        <th class="td-center">Status</th>
                                        <th></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($contributions as $row)
                                    <tr>
                                        <td class="td-name">{{ $row->period_name }}</td>
                                        <td class="td-center">₱{{ number_format($row->sss_total, 2) }}</td>
                                        <td class="td-center">₱{{ number_format($row->philhealth_total, 2) }}</td>
                                        <td class="td-center">₱{{ number_format($row->pagibig_total, 2) }}</td>
                                        <td class="td-center">₱{{ number_format($row->tax_total, 2) }}</td>
                                        <td class="td-center">
                                            <span class="{{ strtolower($row->status ?? '') === 'released' ? 'badge-released' : 'badge-pending' }}">
                                                {{ ucfirst($row->status ?? 'Pending') }}
                                            </span>
                                        </td>
                                        <td class="td-right pr-6">
                                            <a href="{{ route('payroll_officer.govpay.view', $row->payroll_period_id) }}" class="btn-view">View</a>
                                        </td>
                                    </tr>
                                    @empty
                                    <tr>
                                        <td colspan="7" class="text-center py-12 text-gray-400 text-sm">No contributions found for {{ $year }}.</td>
                                    </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                {{-- MY CONTRIBUTIONS --}}
                <div id="panelMine" style="display:none;">
                    <div class="card">
                        <div class="card-header">
                            <span class="card-title">My Contribution Summary</span>
                            <div class="flex items-center gap-3">
                                <button onclick="exportMyGovpay()" class="btn-view" style="background:#2563eb;color:#fff;border-color:#2563eb;">
                                    <svg style="width:14px;height:14px;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                                    Export PDF
                                </button>
                            </div>
                        </div>
                        <div class="table-wrapper">
                            <table class="contrib-table">
                                <thead>
                                    <tr class="border-t border-b border-gray-100 bg-gray-50/60">
                                        <th class="text-left">Period Name</th>
                                        <th class="td-center">SSS</th>
                                        <th class="td-center">PhilHealth</th>
                                        <th class="td-center">Pag-IBIG</th>
                                        <th class="td-center">W/ Tax</th>
                                        <th class="td-center">Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($myContributions as $row)
                                    <tr>
                                        <td class="td-name">{{ $row->period_name }}</td>
                                        <td class="td-center">₱{{ number_format($row->sss ?? 0, 2) }}</td>
                                        <td class="td-center">₱{{ number_format($row->philhealth ?? 0, 2) }}</td>
                                        <td class="td-center">₱{{ number_format($row->pagibig ?? 0, 2) }}</td>
                                        <td class="td-center">₱{{ number_format($row->tax ?? 0, 2) }}</td>
                                        <td class="td-center">
                                            <span class="{{ strtolower($row->status ?? '') === 'released' ? 'badge-released' : 'badge-pending' }}">
                                                {{ ucfirst($row->status ?? 'Pending') }}
                                            </span>
                                        </td>
                                    </tr>
                                    @empty
                                    <td>
                                        <td colspan="6" class="text-center py-12 text-gray-400 text-sm">No personal contributions found for {{ $year }}.</td>
                                    </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

            </div>

        @endif

        {{-- ══════════════════════════════════════
             DETAIL VIEW
        ══════════════════════════════════════ --}}
        @if($isView)
        <div class="p-8">

            <!-- Back + Breadcrumb -->
            <div class="flex flex-wrap items-center gap-5 mb-6">
                <a href="{{ route('payroll_officer.govpay') }}" class="btn-back">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M15 19l-7-7 7-7"/>
                    </svg>
                    Back to All Contributions
                </a>
                <div class="breadcrumb">
                    <a href="{{ route('payroll_officer.govpay') }}">All Contributions</a>
                    <span class="sep">›</span>
                    <span class="current">{{ $periodName }}</span>
                </div>
            </div>

            <!-- Employee Contributions Card -->
            <div class="card">
                <div class="card-header">
                    <span class="card-title">Employee Contributions - {{ $periodName }}</span>
                    <div class="flex items-center gap-3">
                        <button onclick="exportGovpay()" class="btn-view" style="background:#2563eb;color:#fff;border-color:#2563eb;">
                            <svg style="width:14px;height:14px;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                            Export PDF
                        </button>
                        <form method="GET" action="{{ route('payroll_officer.govpay') }}">
                            <select name="year" class="year-select" onchange="this.form.submit()">
                                @foreach($years as $y)
                                    <option value="{{ $y }}" {{ $year == $y ? 'selected' : '' }}>{{ $y }}</option>
                                @endforeach
                            </select>
                        </form>
                    </div>
                </div>
                <div class="table-wrapper">
                    <table class="contrib-table">
                        <thead>
                            <tr class="border-t border-b border-gray-100 bg-gray-50/60">
                                <th class="text-left">Employee</th>
                                <th class="td-center">SSS</th>
                                <th class="td-center">PhilHealth</th>
                                <th class="td-center">Pag-IBIG</th>
                                <th class="td-center">W/ Tax</th>
                                <th class="td-center">Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($records as $row)
                            <tr>
                                <td class="td-name">{{ $row->fname }} {{ $row->lname }}</td>
                                <td class="td-center">₱{{ number_format($row->sss ?? 0, 2) }}</td>
                                <td class="td-center">₱{{ number_format($row->philhealth ?? 0, 2) }}</td>
                                <td class="td-center">₱{{ number_format($row->pagibig ?? 0, 2) }}</td>
                                <td class="td-center">₱{{ number_format($row->tax ?? 0, 2) }}</td>
                                <td class="td-center">
                                    <span class="{{ strtolower($row->status ?? '') === 'released' ? 'badge-released' : 'badge-pending' }}">
                                        {{ ucfirst($row->status ?? 'Pending') }}
                                    </span>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="6" class="text-center py-12 text-gray-400 text-sm">No employee contributions found for this period.</td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

        </div>
        @endif

    </div>

    <script>
        const _isView       = @json($isView);
        const _periodName   = @json($isView ? $periodName : ('Government Contributions ' . $year));
        const _records      = @json($isView ? $exportRecords : $exportContributions);
        const _totals       = @json($exportTotals);
        const _myRecords    = @json($isView ? [] : $exportMyContributions);
        const _myTotals     = @json($isView ? [] : $exportMyTotals);
        const _year         = @json($year);

        function exportGovpay() {
            const f  = v => '₱ ' + v;
            const isDetail = _isView;
            const rows = _records.map(r => {
                const cols = isDetail
                    ? `<td>${r.name}</td><td>${f(r.sss)}</td><td>${f(r.philhealth)}</td><td>${f(r.pagibig)}</td><td>${f(r.tax)}</td><td>${r.status}</td>`
                    : `<td>${r.period}</td><td>${f(r.sss)}</td><td>${f(r.philhealth)}</td><td>${f(r.pagibig)}</td><td>${f(r.tax)}</td><td>${r.status}</td>`;
                return `<tr>${cols}</tr>`;
            }).join('');
            const header = isDetail
                ? `<tr><th>Employee</th><th>SSS</th><th>PhilHealth</th><th>Pag-IBIG</th><th>W/ Tax</th><th>Status</th></tr>`
                : `<tr><th>Period</th><th>SSS Total</th><th>PhilHealth Total</th><th>Pag-IBIG Total</th><th>W/ Tax Total</th><th>Status</th></tr>`;
            const totalsRow = `<tr style="font-weight:700;background:#f0f9ff;border-top:2px solid #bfdbfe;">
                <td>TOTAL</td><td>${f(_totals.sss)}</td><td>${f(_totals.philhealth)}</td><td>${f(_totals.pagibig)}</td><td>${f(_totals.tax)}</td><td></td>
            </tr>`;
            const html = `<!DOCTYPE html><html><head><meta charset="UTF-8"><title>${_periodName}</title>
    <style>*{font-family:Arial,sans-serif;box-sizing:border-box;margin:0;padding:0;}body{padding:40px;color:#1e293b;}
    h1{font-size:18px;font-weight:700;color:#2563eb;margin-bottom:4px;}
    .sub{font-size:12px;color:#64748b;margin-bottom:24px;}
    table{width:100%;border-collapse:collapse;font-size:13px;}
    th{background:#f1f5f9;padding:10px 14px;text-align:left;font-size:11px;text-transform:uppercase;letter-spacing:.05em;color:#64748b;border-bottom:2px solid #e2e8f0;}
    td{padding:10px 14px;border-bottom:1px solid #f1f5f9;color:#374151;}
    tr:hover td{background:#f8faff;}
    .footer{margin-top:24px;font-size:10px;color:#94a3b8;text-align:center;border-top:1px solid #e2e8f0;padding-top:10px;}
    @media print{@page{margin:.8cm;}body{padding:20px;}}</style>
    </head><body>
    <div style="font-size:20px;font-weight:700;color:#2563eb;">MediSource</div>
    <div class="sub">${_periodName}</div>
    <table><thead>${header}</thead><tbody>${rows}${totalsRow}</tbody></td>
    <div class="footer">This is a system-generated government contributions report from MediSource HRIS.</div>
    </body></html>`;
            const w = window.open('', '_blank', 'width=1000,height=750,scrollbars=yes');
            if (!w) return;
            w.document.write(html);
            w.document.close();
            w.document.querySelectorAll('[x-show],[x-cloak]').forEach(el => el.remove());
            w.focus();
            setTimeout(() => w.print(), 400);
        }

        function exportMyGovpay() {
            const f = v => '₱ ' + v;
            const rows = _myRecords.map(r =>
                `<tr><td class="td-name">${r.period}</td><td class="td-center">${f(r.sss)}</td><td class="td-center">${f(r.philhealth)}</td><td class="td-center">${f(r.pagibig)}</td><td class="td-center">${f(r.tax)}</td><td class="td-center">${r.status}</td></tr>`
            ).join('');
            const totalsRow = `<tr style="font-weight:700;background:#f0f9ff;border-top:2px solid #bfdbfe;">
                <td>TOTAL</td><td>${f(_myTotals.sss)}</td><td>${f(_myTotals.philhealth)}</td><td>${f(_myTotals.pagibig)}</td><td>${f(_myTotals.tax)}</td><td></td>
            </tr>`;
            const html = `<!DOCTYPE html><html><head><meta charset="UTF-8"><title>My Contributions ${_year}</title>
    <style>*{font-family:Arial,sans-serif;box-sizing:border-box;margin:0;padding:0;}body{padding:40px;color:#1e293b;}
    h1{font-size:18px;font-weight:700;color:#2563eb;margin-bottom:4px;}
    .sub{font-size:12px;color:#64748b;margin-bottom:24px;}
    table{width:100%;border-collapse:collapse;font-size:13px;}
    th{background:#f1f5f9;padding:10px 14px;text-align:left;font-size:11px;text-transform:uppercase;letter-spacing:.05em;color:#64748b;border-bottom:2px solid #e2e8f0;}
    td{padding:10px 14px;border-bottom:1px solid #f1f5f9;color:#374151;}
    tr:hover td{background:#f8faff;}
    .footer{margin-top:24px;font-size:10px;color:#94a3b8;text-align:center;border-top:1px solid #e2e8f0;padding-top:10px;}
    @media print{@page{margin:.8cm;}body{padding:20px;}}</style>
    </head><body>
    <div style="font-size:20px;font-weight:700;color:#2563eb;">MediSource</div>
    <div class="sub">My Government Contributions – ${_year}</div>
    <tr><thead><tr><th>Period</th><th>SSS</th><th>PhilHealth</th><th>Pag-IBIG</th><th>W/ Tax</th><th>Status</th></tr></thead>
    <tbody>${rows}${totalsRow}</tbody></table>
    <div class="footer">This is a system-generated government contributions report from MediSource HRIS.</div>
    </body></html>`;
            const w = window.open('', '_blank', 'width=1000,height=750,scrollbars=yes');
            if (!w) return;
            w.document.write(html);
            w.document.close();
            w.focus();
            setTimeout(() => w.print(), 400);
        }

        function govpayApp() {
            return {
                sidebarCollapsed: localStorage.getItem('sidebarCollapsed') === 'true',
                mobileMenuOpen: false,

                init() {
                    window.addEventListener('sidebar-toggle', e => {
                        this.sidebarCollapsed = e.detail.collapsed;
                    });
                },

                toggleSidebar() {
                    this.sidebarCollapsed = !this.sidebarCollapsed;
                    localStorage.setItem('sidebarCollapsed', this.sidebarCollapsed);
                    window.dispatchEvent(new CustomEvent('sidebar-toggle', {
                        detail: { collapsed: this.sidebarCollapsed }
                    }));
                }
            };
        }

        // ── Tab switch (list view only) ──
        function switchTab(tab) {
            const panelAll  = document.getElementById('panelAll');
            const panelMine = document.getElementById('panelMine');
            const tabAll    = document.getElementById('tabAll');
            const tabMine   = document.getElementById('tabMine');
            if (!panelAll) return;
            if (tab === 'all') {
                panelAll.style.display  = 'block';
                panelMine.style.display = 'none';
                if (tabAll)  tabAll.classList.add('active');
                if (tabMine) tabMine.classList.remove('active');
            } else {
                panelAll.style.display  = 'none';
                panelMine.style.display = 'block';
                if (tabAll)  tabAll.classList.remove('active');
                if (tabMine) tabMine.classList.add('active');
            }
        }
    </script>
</body>
</html>
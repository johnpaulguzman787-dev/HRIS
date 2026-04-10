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
        body { background: #f3f4f6; margin: 0; }

        /* ── Tab bar ── */
        .tabs-wrapper {
            background: #fff;
            border-bottom: 2px solid #e5e7eb;
            padding: 0 32px;
        }
        .tabs-row { display: flex; }
        .tab-btn {
            padding: 16px 0;
            margin-right: 32px;
            font-size: 0.875rem;
            font-weight: 600;
            background: none;
            border: none;
            border-bottom: 3px solid transparent;
            margin-bottom: -2px;
            cursor: pointer;
            color: #9ca3af;
            transition: color 0.15s, border-color 0.15s;
            white-space: nowrap;
        }
        .tab-btn.active { color: #3b82f6; border-bottom-color: #3b82f6; }
        .tab-btn:hover:not(.active) { color: #6b7280; }

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

        /* ── Table ── */
        .contrib-table { width: 100%; border-collapse: collapse; }
        .contrib-table thead tr { background: #f9fafb; }
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

        /* ── View button ── */
        .btn-view {
            display: inline-block;
            background: #eff6ff; color: #2563eb;
            border: 1.5px solid #93c5fd;
            padding: 5px 18px; border-radius: 7px;
            font-size: 0.775rem; font-weight: 600;
            cursor: pointer; text-decoration: none; white-space: nowrap;
            transition: background 0.15s, color 0.15s, border-color 0.15s;
        }
        .btn-view:hover { background: #2563eb; color: #fff; border-color: #2563eb; }

        /* ── Back button ── */
        .btn-back {
            display: inline-flex; align-items: center; gap: 6px;
            background: #f8fafc; border: 1.5px solid #e2e8f0;
            border-radius: 9px; padding: 8px 18px;
            color: #374151; font-size: 0.84rem; font-weight: 500;
            cursor: pointer; white-space: nowrap; text-decoration: none;
            transition: background 0.15s, color 0.15s;
        }
        .btn-back:hover { background: #e2e8f0; color: #1d4ed8; }

        /* ── Breadcrumb ── */
        .breadcrumb { display: flex; align-items: center; gap: 6px; font-size: 0.875rem; }
        .breadcrumb a { color: #9ca3af; text-decoration: none; }
        .breadcrumb a:hover { color: #6b7280; }
        .breadcrumb .sep { color: #d1d5db; }
        .breadcrumb .current { font-weight: 600; color: #374151; }

    </style>
</head>
@php $isView = isset($records); @endphp

<body x-data="govpayApp()" x-init="init()">


    @include('payroll_officer.payroll_sidebar')

    {{-- MAIN CONTENT --}}
    <div class="min-h-screen transition-all duration-300"
         :style="'margin-left: ' + (sidebarCollapsed ? '80px' : '256px')">


    <!-- Blue Header -->
    <header class="bg-gradient-to-br from-blue-500 to-blue-700 sticky top-0 z-40 shadow-lg mt-4 mx-4 rounded-2xl">
        <div class="flex items-center justify-between px-8 py-4">
            <h1 class="text-white font-bold text-xl">Government Contributions</h1>
            <x-notification-bell />
        </div>
    </header>

    {{-- ══════════════════════════════════════
         LIST VIEW
    ══════════════════════════════════════ --}}
    @if(!$isView)

        <!-- Tab Bar -->
        <div class="tabs-wrapper">
            <div class="tabs-row">
                <button id="tabAll"  class="tab-btn active" onclick="switchTab('all')">All Contributions</button>
                <button id="tabMine" class="tab-btn"        onclick="switchTab('mine')">My Contributions</button>
            </div>
        </div>

        <div class="p-8 space-y-6">

            {{-- ALL CONTRIBUTIONS --}}
            <div id="panelAll">
                <div class="card">
                    <div class="card-header">
                        <span class="card-title">Contribution Summary</span>
                        <form method="GET" action="{{ route('payroll_officer.govpay') }}">
                            <select name="year" class="year-select" onchange="this.form.submit()">
                                @foreach($years as $y)
                                    <option value="{{ $y }}" {{ $year == $y ? 'selected' : '' }}>{{ $y }}</option>
                                @endforeach
                            </select>
                        </form>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="contrib-table">
                            <thead>
                                <tr>
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
                    </div>
                    <div class="overflow-x-auto">
                        <table class="contrib-table">
                            <thead>
                                <tr>
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
                                <tr>
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
        <div class="flex items-center gap-5 mb-6">
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
                <form method="GET" action="{{ route('payroll_officer.govpay') }}">
                    <select name="year" class="year-select" onchange="this.form.submit()">
                        @foreach($years as $y)
                            <option value="{{ $y }}" {{ $year == $y ? 'selected' : '' }}>{{ $y }}</option>
                        @endforeach
                    </select>
                </form>
            </div>
            <div class="overflow-x-auto">
                <table class="contrib-table">
                    <thead>
                        <tr>
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
    function govpayApp() {
        return {
            sidebarCollapsed: localStorage.getItem('sidebarCollapsed') === 'true',

            init() {
                window.addEventListener('storage', () => {
                    this.sidebarCollapsed = localStorage.getItem('sidebarCollapsed') === 'true';
                });
            },
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
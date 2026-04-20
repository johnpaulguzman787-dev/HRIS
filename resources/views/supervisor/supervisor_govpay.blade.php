{{-- resources/views/supervisor/supervisor_govpay.blade.php --}}
<!DOCTYPE html>
<html lang="en">
<head>
    <link rel="icon" type="image/png" href="{{ asset('images/HRISLogo-Icon.png') }}">
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>My Contributions – MediSource</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet"/>
    <style>
        * { font-family: 'DM Sans', sans-serif; box-sizing: border-box; }
        body { background: #f3f4f6; margin: 0; }

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
            display: inline-block;
            background: #eff6ff; color: #2563eb;
            border: 1.5px solid #93c5fd;
            padding: 5px 18px; border-radius: 7px;
            font-size: 0.775rem; font-weight: 600;
            cursor: pointer; text-decoration: none; white-space: nowrap;
            transition: background 0.15s, color 0.15s, border-color 0.15s;
        }
        .btn-view:hover { background: #2563eb; color: #fff; border-color: #2563eb; }
    </style>
</head>
@php
    $exportData = collect($myContributions)->map(fn($r) => [
        'period'     => $r->period_name,
        'sss'        => number_format($r->sss ?? 0, 2),
        'philhealth' => number_format($r->philhealth ?? 0, 2),
        'pagibig'    => number_format($r->pagibig ?? 0, 2),
        'tax'        => number_format($r->tax ?? 0, 2),
        'status'     => ucfirst($r->status ?? 'Pending'),
    ])->values()->toArray();
    $exportTotals = [
        'sss'        => number_format(collect($myContributions)->sum('sss'), 2),
        'philhealth' => number_format(collect($myContributions)->sum('philhealth'), 2),
        'pagibig'    => number_format(collect($myContributions)->sum('pagibig'), 2),
        'tax'        => number_format(collect($myContributions)->sum('tax'), 2),
    ];
@endphp

<body x-data="supervisorGovpayApp()" x-init="init()">

    @include('supervisor.supervisor_sidebar')

    <div class="min-h-screen transition-all duration-300"
         :style="'margin-left: ' + (sidebarCollapsed ? '80px' : '256px')">

        <!-- Header -->
        <header class="bg-gradient-to-br from-blue-500 to-blue-700 sticky top-0 z-40 shadow-lg mt-4 mx-4 rounded-2xl">
            <div class="flex items-center justify-between px-8 py-4">
                <h1 class="text-white font-bold text-xl">My Contributions</h1>
                <x-notification-bell />
            </div>
        </header>

        <div class="p-8">
            <div class="card">
                <div class="card-header">
                    <span class="card-title">My Government Contributions</span>
                    <div class="flex items-center gap-3">
                        <button onclick="exportGovpay()" class="btn-view" style="background:#2563eb;color:#fff;border-color:#2563eb;display:inline-flex;align-items:center;gap:6px;">
                            <svg style="width:14px;height:14px;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                            Export PDF
                        </button>
                        <form method="GET" action="{{ route('supervisor.govpay') }}">
                            <select name="year" class="year-select" onchange="this.form.submit()">
                                @foreach($years as $y)
                                    <option value="{{ $y }}" {{ $year == $y ? 'selected' : '' }}>{{ $y }}</option>
                                @endforeach
                            </select>
                        </form>
                    </div>
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
                                <td colspan="6" class="text-center py-12 text-gray-400 text-sm">No contributions found for {{ $year }}.</td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

    </div>

    <script>
        const _exportData   = @json($exportData);
        const _exportTotals = @json($exportTotals);
        const _exportYear   = @json($year);

        function exportGovpay() {
            const f = v => '₱ ' + v;
            const rows = _exportData.map(r =>
                `<tr><td>${r.period}</td><td>${f(r.sss)}</td><td>${f(r.philhealth)}</td><td>${f(r.pagibig)}</td><td>${f(r.tax)}</td><td>${r.status}</td></tr>`
            ).join('');
            const totalsRow = `<tr style="font-weight:700;background:#f0f9ff;border-top:2px solid #bfdbfe;">
                <td>TOTAL</td><td>${f(_exportTotals.sss)}</td><td>${f(_exportTotals.philhealth)}</td><td>${f(_exportTotals.pagibig)}</td><td>${f(_exportTotals.tax)}</td><td></td></tr>`;
            const html = `<!DOCTYPE html><html><head><meta charset="UTF-8"><title>My Contributions ${_exportYear}</title>
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
<div class="sub">My Government Contributions – ${_exportYear}</div>
<table><thead><tr><th>Period</th><th>SSS</th><th>PhilHealth</th><th>Pag-IBIG</th><th>W/ Tax</th><th>Status</th></tr></thead>
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

        function supervisorGovpayApp() {
            return {
                sidebarCollapsed: localStorage.getItem('sidebarCollapsed') === 'true',
                init() {
                    window.addEventListener('sidebar-toggle', e => { this.sidebarCollapsed = e.detail.collapsed; });
                },
            };
        }
    </script>
</body>
</html>

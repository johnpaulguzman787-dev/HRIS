{{-- resources/views/hr/hr_govpay.blade.php --}}
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
    </style>
</head>

<body x-data="hrGovpayApp()" x-init="init()">

    @include('hr.hr_sidebar')

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
                    <form method="GET" action="{{ route('hr.govpay') }}">
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
        function hrGovpayApp() {
            return {
                sidebarCollapsed: localStorage.getItem('sidebarCollapsed') === 'true',
                init() {
                    window.addEventListener('storage', () => {
                        this.sidebarCollapsed = localStorage.getItem('sidebarCollapsed') === 'true';
                    });
                },
            };
        }
    </script>
</body>
</html>

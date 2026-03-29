<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Payslips – MediSource</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <style>
        [x-cloak] { display: none !important; }
        * { font-family: 'Inter', sans-serif; }
        body { background: #f0f2f5; }

        /* ── Tabs ── */
        .tab-bar { display:flex; gap:0; border-bottom:2px solid #e5e7eb; }
        .tab-btn {
            padding:14px 32px; font-size:0.92rem; font-weight:500;
            color:#9ca3af; border:none; background:none; cursor:pointer;
            white-space:nowrap; transition:color 0.22s;
            border-bottom:3px solid transparent; margin-bottom:-2px;
        }
        .tab-btn:hover:not(.active) { color:#374151; }
        .tab-btn.active { color:#2563eb; font-weight:700; border-bottom:3px solid #2563eb; }

        /* ── Animations ── */
        @keyframes fadeUp {
            from { opacity:0; transform:translateY(18px); }
            to   { opacity:1; transform:translateY(0);    }
        }
        @keyframes scaleIn {
            from { opacity:0; transform:scale(0.93) translateY(10px); }
            to   { opacity:1; transform:scale(1)    translateY(0);    }
        }
        @keyframes slideRight {
            from { opacity:0; transform:translateX(22px); }
            to   { opacity:1; transform:translateX(0);    }
        }
        @keyframes tabIn {
            from { opacity:0; transform:translateY(8px); }
            to   { opacity:1; transform:translateY(0);   }
        }
        @keyframes valueReveal {
            0%   { opacity:0; transform:translateY(10px) scale(0.96); }
            100% { opacity:1; transform:translateY(0)    scale(1);    }
        }
        @keyframes labelReveal {
            from { opacity:0; transform:translateY(-6px); }
            to   { opacity:1; transform:translateY(0);    }
        }
        @keyframes subReveal {
            from { opacity:0; }
            to   { opacity:1; }
        }

        .anim-1 { animation:fadeUp 0.44s cubic-bezier(0.22,1,0.36,1) both; }
        .anim-2 { animation:fadeUp 0.44s 0.07s cubic-bezier(0.22,1,0.36,1) both; }
        .anim-3 { animation:fadeUp 0.44s 0.14s cubic-bezier(0.22,1,0.36,1) both; }
        .slide-in-right { animation:slideRight 0.28s cubic-bezier(0.22,1,0.36,1) both; }
        .tab-content    { animation:tabIn 0.28s cubic-bezier(0.22,1,0.36,1) both; }

        /* ── Summary Cards ── */
        .cards-wrap { display:flex; gap:16px; }

        .summary-card {
            background:#fff; border-radius:10px; border:1px solid #e5e7eb;
            padding:22px 26px; flex:1;
            transition:transform 0.2s ease, box-shadow 0.2s ease;
            animation:scaleIn 0.44s cubic-bezier(0.22,1,0.36,1) both;
        }
        .summary-card:nth-child(1) { animation-delay:0.04s; }
        .summary-card:nth-child(2) { animation-delay:0.11s; }
        .summary-card:nth-child(3) { animation-delay:0.18s; }
        .summary-card:hover {
            transform:translateY(-2px);
            box-shadow:0 8px 24px rgba(59,130,246,0.10);
        }

        .s-label { font-size:0.78rem; color:#9ca3af; margin-bottom:6px; }
        .s-value { font-size:1.75rem; font-weight:700; color:#1e293b; letter-spacing:-0.5px; }
        .s-sub   { font-size:0.72rem; color:#9ca3af; margin-top:4px; }

        /* ── Badges ── */
        .badge-released  { background:#e8f5e9; color:#2e7d32; }
        .badge-pending   { background:#fff3e0; color:#e65100; }
        .badge-submitted { background:#e3f2fd; color:#1565c0; }

        /* ── Tables ── */
        .data-table { width:100%; border-collapse:collapse; }
        .data-table thead tr { border-bottom:1px solid #e5e7eb; }
        .data-table thead th {
            text-align:left; padding:13px 16px;
            font-size:0.82rem; font-weight:700; color:#374151;
        }
        .data-table tbody tr {
            border-bottom:1px solid #f1f5f9;
            transition:background 0.14s ease;
            cursor:pointer;
        }
        .data-table tbody tr:hover { background:#f8faff; }
        .data-table tbody tr.row-active { background:#eff6ff; }
        .data-table tbody td { padding:15px 16px; font-size:0.875rem; color:#374151; }

        /* ── Payslip Panel ── */
        .payslip-header {
            background:linear-gradient(135deg,#3b82f6 0%,#2563eb 100%);
            border-radius:12px 12px 0 0; padding:20px 24px; color:#fff;
        }
        .payslip-body { padding:0 22px 22px; }
        .ps-section-title {
            font-size:0.8rem; font-weight:700; color:#374151;
            margin-top:16px; margin-bottom:8px;
        }
        .ps-line {
            display:flex; justify-content:space-between;
            font-size:0.82rem; color:#6b7280; padding:4px 0;
        }
        .ps-line.bold {
            font-weight:700; color:#1e293b; font-size:0.875rem;
            border-top:1px solid #e5e7eb; padding-top:8px; margin-top:4px;
        }
        .info-label { font-size:0.7rem; color:rgba(255,255,255,0.72); margin-bottom:2px; }
        .info-value { font-size:0.8rem; color:#fff; font-weight:500; }

        /* ── Controls ── */
        .ctrl {
            border:1px solid #e2e8f0; border-radius:8px; padding:10px 14px;
            font-size:0.875rem; background:#fff; color:#374151; outline:none;
            transition:border-color 0.2s,box-shadow 0.2s; box-sizing:border-box;
        }
        .ctrl:focus { border-color:#3b82f6; box-shadow:0 0 0 3px rgba(59,130,246,0.10); }
        .ctrl-select {
            appearance:none;
            background-image:url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 24 24' fill='none' stroke='%239ca3af' stroke-width='2'%3E%3Cpath d='M19 9l-7 7-7-7'/%3E%3C/svg%3E");
            background-repeat:no-repeat; background-position:right 12px center; padding-right:36px;
        }
        .search-wrap { position:relative; }
        .search-wrap svg { position:absolute; left:12px; top:50%; transform:translateY(-50%); width:16px; height:16px; color:#9ca3af; pointer-events:none; }
        .search-wrap input { padding-left:40px; }

        /* ── Buttons ── */
        .btn-primary {
            background:#2563eb; color:#fff; border-radius:8px; padding:10px 24px;
            font-size:0.875rem; font-weight:600; display:inline-flex; align-items:center;
            gap:7px; border:none; cursor:pointer;
            transition:background 0.18s, transform 0.15s;
            white-space:nowrap; width:100%; justify-content:center;
        }
        .btn-primary:hover { background:#1d4ed8; transform:translateY(-1px); }
        .btn-primary:active { transform:translateY(0); }
        .btn-close {
            border:1px solid #e2e8f0; border-radius:8px; padding:10px 24px;
            font-size:0.875rem; font-weight:500; color:#374151; background:#fff;
            cursor:pointer; transition:background 0.15s; width:100%;
        }
        .btn-close:hover { background:#f3f4f6; }
        .btn-view {
            border:1px solid #e2e8f0; border-radius:7px; padding:5px 16px;
            font-size:0.8rem; font-weight:500; color:#374151; background:#fff;
            cursor:pointer; transition:all 0.15s;
        }
        .btn-view:hover { background:#eff6ff; color:#2563eb; border-color:#bfdbfe; }

        .main-content { transition:margin-left 0.3s cubic-bezier(0.22,1,0.36,1); }
    </style>
</head>

<body x-data="payslipsApp()" x-init="init()">

    @include('payroll_officer.payroll_sidebar')

    <div class="main-content min-h-screen" :style="'margin-left: ' + (sidebarCollapsed ? '80px' : '256px')">

        {{-- Header --}}
        <header class="bg-gradient-to-r from-blue-500 to-blue-600 sticky top-0 z-10 shadow-lg mt-4 mx-4 rounded-2xl">
            <div class="flex items-center justify-between px-8 py-4">
                <h1 class="text-white font-bold text-xl tracking-tight">Payslips</h1>
                <div class="relative">
                    <button class="w-9 h-9 rounded-full flex items-center justify-center hover:bg-white/20 transition-all">
                        <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6 6 0 00-5-5.917V5a1 1 0 10-2 0v.083A6 6 0 006 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>
                        </svg>
                        <span class="absolute -top-1 -right-1 w-4 h-4 bg-red-400 rounded-full text-white text-xs flex items-center justify-center font-bold">4</span>
                    </button>
                </div>
            </div>
        </header>

        {{-- Tab Bar --}}
        <div class="bg-white px-8 pt-2 anim-2">
            <div class="tab-bar">
                <button class="tab-btn" :class="activeTab==='all'&&'active'" @click="activeTab='all'">All Payslips</button>
                <button class="tab-btn" :class="activeTab==='my'&&'active'"  @click="activeTab='my'">My Payslip</button>
            </div>
        </div>

        {{-- ══════════════════════════
             ALL PAYSLIPS
        ══════════════════════════ --}}
        <div x-show="activeTab==='all'" x-cloak class="tab-content">

            {{-- Summary Cards --}}
            <div class="px-8 pt-6 pb-4">
                <div class="cards-wrap">
                    <div class="summary-card">
                        <div class="s-label">Gross Payroll</div>
                        <div class="s-value">&#8369; {{ number_format($allGrossPayroll ?? 263082) }}</div>
                        <div class="s-sub">{{ $latestPeriod->name ?? 'February 2026 Period 2' }}</div>
                    </div>
                    <div class="summary-card">
                        <div class="s-label">Net Pay</div>
                        <div class="s-value">&#8369; {{ number_format($allNetPay ?? 200000) }}</div>
                        <div class="s-sub">{{ $latestPeriod->name ?? 'February 2026 Period 2' }}</div>
                    </div>
                    <div class="summary-card">
                        <div class="s-label">Total Deductions</div>
                        <div class="s-value">&#8369; {{ number_format($allTotalDeductions ?? 63082) }}</div>
                        <div class="s-sub">{{ $latestPeriod->name ?? 'February 2026 Period 2' }}</div>
                    </div>
                </div>
            </div>

            {{-- Toolbar --}}
            <div class="px-8 pb-4 flex items-center gap-3 anim-2">
                <div class="search-wrap" style="flex:1;max-width:380px">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-4.35-4.35M17 11A6 6 0 105 11a6 6 0 0012 0z"/>
                    </svg>
                    <input type="text" placeholder="Search" class="ctrl" style="width:100%" x-model="allSearch">
                </div>
                <select class="ctrl ctrl-select" style="width:220px" x-model="allPeriodFilter">
                    <option value="">February Payroll Period 2</option>
                    <option value="feb2">February Payroll Period 2</option>
                    <option value="feb1">February Payroll Period 1</option>
                    <option value="jan2">January Payroll Period 2</option>
                    <option value="jan1">January Payroll Period 1</option>
                </select>
                <select class="ctrl ctrl-select" style="width:100px" x-model="allYearFilter">
                    @for($y=now()->year; $y>=now()->year-3; $y--)
                    <option value="{{ $y }}">{{ $y }}</option>
                    @endfor
                </select>
            </div>

            {{-- Table + Panel --}}
            <div class="px-8 pb-10 anim-3">
                <div class="flex gap-5 items-start">

                    <div class="flex-1 min-w-0">
                        <h3 class="text-base font-bold text-gray-800 mb-3">Employee Payroll</h3>
                        <div class="bg-white rounded-xl border border-gray-100 shadow-sm overflow-hidden">
                            <table class="data-table">
                                <thead><tr>
                                    <th>Employee</th><th>Gross Pay</th><th>Net Pay</th><th>Status</th><th></th>
                                </tr></thead>
                                <tbody>
                                    <template x-for="ps in filteredAllPayslips" :key="ps.id">
                                        <tr :class="allSelectedId===ps.id&&'row-active'" @click="allSelect(ps)">
                                            <td class="font-medium" x-text="ps.employeeName"></td>
                                            <td>&#8369;<span x-text="fmt(ps.grossPay)"></span></td>
                                            <td>&#8369;<span x-text="fmt(ps.netPay)"></span></td>
                                            <td><span class="px-3 py-1 rounded-full text-xs font-semibold badge-released">Released</span></td>
                                            <td class="text-right"><button class="btn-view" @click.stop="allSelect(ps)">View</button></td>
                                        </tr>
                                    </template>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    {{-- Payslip Panel --}}
                    <div class="w-80 flex-shrink-0" x-show="allSelectedId!==null" x-cloak>
                        <div class="bg-white rounded-xl border border-gray-100 shadow-sm overflow-hidden slide-in-right">
                            <div class="payslip-header">
                                <div class="text-lg font-bold mb-0.5">Medisource</div>
                                <div class="text-xs text-blue-100 mb-4" x-text="allSubtitle"></div>
                                <div class="grid grid-cols-3 gap-1">
                                    <div><div class="info-label">Employee</div><div class="info-value" x-text="allActive.employeeName||'—'"></div></div>
                                    <div><div class="info-label">Job Title</div><div class="info-value" x-text="allActive.jobTitle||'—'"></div></div>
                                    <div><div class="info-label">Department</div><div class="info-value" x-text="allActive.department||'—'"></div></div>
                                </div>
                            </div>
                            <div class="payslip-body">
                                <div class="ps-section-title">Earnings</div>
                                <div class="ps-line"><span>Basic Pay</span><span x-text="'&#8369; '+fmt(allActive.basicPay||0)"></span></div>
                                <div class="ps-line"><span>OT Pay</span><span x-text="'&#8369; '+fmt(allActive.otPay||0)"></span></div>
                                <div class="ps-line"><span>Benefits</span><span x-text="'&#8369; '+fmt(allActive.benefits||0)"></span></div>
                                <div class="ps-line bold"><span>Gross Pay</span><span x-text="'&#8369; '+fmt(allActive.grossPay||0)"></span></div>
                                <div class="ps-section-title">Deductions</div>
                                <div class="ps-line"><span>SSS</span><span class="text-red-500" x-text="'-&#8369; '+fmt(allActive.sss||0)"></span></div>
                                <div class="ps-line"><span>PhilHealth</span><span class="text-red-500" x-text="'-&#8369; '+fmt(allActive.philhealth||0)"></span></div>
                                <div class="ps-line"><span>Pag-IBIG</span><span class="text-red-500" x-text="'-&#8369; '+fmt(allActive.pagibig||0)"></span></div>
                                <div class="ps-line"><span>Withholding Tax</span><span class="text-red-500" x-text="'-&#8369; '+fmt(allActive.withholdingTax||0)"></span></div>
                                <div class="ps-line bold"><span>Total Deductions</span><span class="text-red-500" x-text="'-&#8369; '+fmt(allActive.totalDeductions||0)"></span></div>
                                <div class="ps-section-title">Calculation</div>
                                <div class="ps-line"><span>Earnings</span><span x-text="'&#8369; '+fmt(allActive.grossPay||0)"></span></div>
                                <div class="ps-line"><span>Deductions</span><span class="text-red-500" x-text="'-&#8369; '+fmt(allActive.totalDeductions||0)"></span></div>
                                <div class="ps-line bold"><span>Net Pay</span><span x-text="'&#8369; '+fmt(allActive.netPay||0)"></span></div>
                                <div class="flex gap-2 mt-5">
                                    <button class="btn-close" @click="allSelectedId=null;allActive={}">Close</button>
                                    <button class="btn-primary" @click="exportPayslip(allActive)">Export</button>
                                </div>
                            </div>
                        </div>
                    </div>

                </div>
            </div>

        </div>{{-- /all --}}


        {{-- ══════════════════════════
             MY PAYSLIP
        ══════════════════════════ --}}
        <div x-show="activeTab==='my'" x-cloak class="tab-content">

            {{-- Summary Cards --}}
            <div class="px-8 pt-6 pb-4">
                <div class="cards-wrap">
                    <div class="summary-card">
                        <div class="s-label">Gross Payroll</div>
                        <div class="s-value">&#8369; {{ number_format($myGrossPayroll ?? 23082) }}</div>
                        <div class="s-sub">{{ now()->year }}</div>
                    </div>
                    <div class="summary-card">
                        <div class="s-label">Net Pay</div>
                        <div class="s-value">&#8369; {{ number_format($myNetPay ?? 20000) }}</div>
                        <div class="s-sub">{{ now()->year }}</div>
                    </div>
                    <div class="summary-card">
                        <div class="s-label">Total Deductions</div>
                        <div class="s-value">&#8369; {{ number_format($myTotalDeductions ?? 3082) }}</div>
                        <div class="s-sub">{{ now()->year }}</div>
                    </div>
                </div>
            </div>

            {{-- Toolbar --}}
            <div class="px-8 pb-4 flex items-center gap-3 anim-2">
                <div class="search-wrap" style="flex:1;max-width:380px">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-4.35-4.35M17 11A6 6 0 105 11a6 6 0 0012 0z"/>
                    </svg>
                    <input type="text" placeholder="Search" class="ctrl" style="width:100%" x-model="mySearch">
                </div>
                <select class="ctrl ctrl-select" style="width:100px" x-model="myYearFilter">
                    @for($y=now()->year; $y>=now()->year-3; $y--)
                    <option value="{{ $y }}">{{ $y }}</option>
                    @endfor
                </select>
            </div>

            {{-- Table + Panel --}}
            <div class="px-8 pb-10 anim-3">
                <div class="flex gap-5 items-start">

                    <div class="flex-1 min-w-0">
                        <div class="flex items-center justify-between mb-3">
                            <h3 class="text-base font-bold text-gray-800">Payroll Period</h3>
                            <select class="ctrl ctrl-select" style="width:100px" x-model="myYearFilter">
                                @for($y=now()->year; $y>=now()->year-3; $y--)
                                <option value="{{ $y }}">{{ $y }}</option>
                                @endfor
                            </select>
                        </div>
                        <div class="bg-white rounded-xl border border-gray-100 shadow-sm overflow-hidden">
                            <table class="data-table">
                                <thead><tr>
                                    <th>Period Name</th><th>Start Date</th><th>End Date</th><th>Status</th><th></th>
                                </tr></thead>
                                <tbody>
                                    <template x-for="p in filteredMyPeriods" :key="p.id">
                                        <tr :class="mySelectedId===p.id&&'row-active'" @click="mySelect(p)">
                                            <td class="font-medium" x-text="p.name"></td>
                                            <td class="text-gray-500" x-text="p.startDate"></td>
                                            <td class="text-gray-500" x-text="p.endDate"></td>
                                            <td><span class="px-3 py-1 rounded-full text-xs font-semibold badge-released">Released</span></td>
                                            <td class="text-right"><button class="btn-view" @click.stop="mySelect(p)">View</button></td>
                                        </tr>
                                    </template>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    {{-- My Payslip Panel --}}
                    <div class="w-80 flex-shrink-0" x-show="mySelectedId!==null" x-cloak>
                        <div class="bg-white rounded-xl border border-gray-100 shadow-sm overflow-hidden slide-in-right">
                            <div class="payslip-header">
                                <div class="text-lg font-bold mb-0.5">Medisource</div>
                                <div class="text-xs text-blue-100 mb-4" x-text="mySubtitle"></div>
                                <div class="grid grid-cols-3 gap-1">
                                    <div><div class="info-label">Employee</div><div class="info-value" x-text="myActive.employeeName||'—'"></div></div>
                                    <div><div class="info-label">Job Title</div><div class="info-value" x-text="myActive.jobTitle||'—'"></div></div>
                                    <div><div class="info-label">Department</div><div class="info-value" x-text="myActive.department||'—'"></div></div>
                                </div>
                            </div>
                            <div class="payslip-body">
                                <div class="ps-section-title">Earnings</div>
                                <div class="ps-line">
                                    <span>Basic Pay <span class="text-gray-400 text-xs" x-text="myActive.salaryGradeLabel?'('+myActive.salaryGradeLabel+')':''"></span></span>
                                    <span x-text="'&#8369; '+fmt(myActive.basicPay||0)"></span>
                                </div>
                                <div class="ps-line">
                                    <span>OT Pay <span class="text-gray-400 text-xs" x-text="myActive.otLabel?'('+myActive.otLabel+')':''"></span></span>
                                    <span x-text="'&#8369; '+fmt(myActive.otPay||0)"></span>
                                </div>
                                <div class="ps-line">
                                    <span>Benefits <span class="text-gray-400 text-xs" x-text="myActive.benefitsLabel?'('+myActive.benefitsLabel+')':''"></span></span>
                                    <span x-text="'&#8369; '+fmt(myActive.benefits||0)"></span>
                                </div>
                                <div class="ps-line bold"><span>Gross Pay</span><span x-text="'&#8369; '+fmt(myActive.grossPay||0)"></span></div>
                                <div class="ps-section-title">Deductions</div>
                                <div class="ps-line"><span>SSS</span><span class="text-red-500" x-text="'-&#8369; '+fmt(myActive.sss||0)"></span></div>
                                <div class="ps-line"><span>PhilHealth</span><span class="text-red-500" x-text="'-&#8369; '+fmt(myActive.philhealth||0)"></span></div>
                                <div class="ps-line"><span>Pag-IBIG</span><span class="text-red-500" x-text="'-&#8369; '+fmt(myActive.pagibig||0)"></span></div>
                                <div class="ps-line"><span>Withholding Tax</span><span class="text-red-500" x-text="'-&#8369; '+fmt(myActive.withholdingTax||0)"></span></div>
                                <div class="ps-line bold"><span>Total Deductions</span><span class="text-red-500" x-text="'-&#8369; '+fmt(myActive.totalDeductions||0)"></span></div>
                                <div class="ps-section-title">Calculation</div>
                                <div class="ps-line"><span>Earnings</span><span x-text="'&#8369; '+fmt(myActive.grossPay||0)"></span></div>
                                <div class="ps-line"><span>Deductions</span><span class="text-red-500" x-text="'-&#8369; '+fmt(myActive.totalDeductions||0)"></span></div>
                                <div class="ps-line bold"><span>Net Pay</span><span x-text="'&#8369; '+fmt(myActive.netPay||0)"></span></div>
                                <div class="flex gap-2 mt-5">
                                    <button class="btn-close" @click="mySelectedId=null;myActive={}">Close</button>
                                    <button class="btn-primary" @click="exportPayslip(myActive)">Export</button>
                                </div>
                            </div>
                        </div>
                    </div>

                </div>
            </div>

        </div>{{-- /my --}}

    </div>{{-- /main-content --}}

    <script>
    function payslipsApp() {
        return {
            sidebarCollapsed: localStorage.getItem('sidebarCollapsed') === 'true',
            activeTab: 'all',

            // ── All Payslips ──
            allSearch:       '',
            allPeriodFilter: '',
            allYearFilter:   '{{ now()->year }}',
            allSelectedId:   null,
            allActive:       {},
            allSubtitle:     'February 2026 · Period 2 · Feb 16–28',

            allPayslips: [
                {id:1,employeeName:'Juan Dela Cruz',jobTitle:'Senior Programmer',department:'IT',basicPay:23655,otPay:2425,benefits:1000,grossPay:28090,sss:1125,philhealth:500,pagibig:200,withholdingTax:2995,totalDeductions:4820,netPay:23680},
                {id:2,employeeName:'Maria Santos',jobTitle:'Nurse',department:'Medical',basicPay:23655,otPay:845,benefits:2000,grossPay:26500,sss:1125,philhealth:500,pagibig:200,withholdingTax:2500,totalDeductions:4325,netPay:22175},
                {id:3,employeeName:'Pedro Reyes',jobTitle:'Accountant',department:'Finance',basicPay:23655,otPay:0,benefits:2000,grossPay:25655,sss:1125,philhealth:500,pagibig:200,withholdingTax:2200,totalDeductions:4025,netPay:21630},
                {id:4,employeeName:'Ana Ramos',jobTitle:'HR Officer',department:'HR',basicPay:20000,otPay:500,benefits:1500,grossPay:22000,sss:900,philhealth:440,pagibig:200,withholdingTax:1800,totalDeductions:3340,netPay:18660},
                {id:5,employeeName:'Carlo Mendoza',jobTitle:'Sales Rep',department:'Sales',basicPay:18000,otPay:1200,benefits:1000,grossPay:20200,sss:810,philhealth:404,pagibig:200,withholdingTax:1600,totalDeductions:3014,netPay:17186},
                {id:6,employeeName:'Liza Cruz',jobTitle:'Pharmacist',department:'Pharmacy',basicPay:25000,otPay:0,benefits:2500,grossPay:27500,sss:1125,philhealth:500,pagibig:200,withholdingTax:2800,totalDeductions:4625,netPay:22875},
                {id:7,employeeName:'Rico Torres',jobTitle:'Driver',department:'Logistics',basicPay:15000,otPay:750,benefits:500,grossPay:16250,sss:675,philhealth:325,pagibig:200,withholdingTax:900,totalDeductions:2100,netPay:14150},
            ],

            get filteredAllPayslips() {
                return this.allPayslips.filter(p =>
                    !this.allSearch || p.employeeName.toLowerCase().includes(this.allSearch.toLowerCase())
                );
            },

            allSelect(ps) {
                this.allSelectedId = ps.id;
                this.allActive = { ...ps };
                this.allSubtitle = 'February 2026 · Period 2 · Feb 16–28';
            },

            // ── My Payslip ──
            mySearch:     '',
            myYearFilter: '{{ now()->year }}',
            mySelectedId: null,
            myActive:     {},
            mySubtitle:   'February 2026 · Period 2 · Feb 16–28',

            myPeriods: [
                {id:1,name:'February Payroll Period 1',startDate:'02/01/2026',endDate:'02/15/2026',year:2026},
                {id:2,name:'February Payroll Period 1',startDate:'02/01/2026',endDate:'02/15/2026',year:2026},
                {id:3,name:'February Payroll Period 1',startDate:'02/01/2026',endDate:'02/15/2026',year:2026},
                {id:4,name:'February Payroll Period 1',startDate:'02/01/2026',endDate:'02/15/2026',year:2026},
                {id:5,name:'February Payroll Period 1',startDate:'02/01/2026',endDate:'02/15/2026',year:2026},
                {id:6,name:'February Payroll Period 1',startDate:'02/01/2026',endDate:'02/15/2026',year:2026},
            ],

            myPayslipData: {
                employeeName:'Jim Dela Cruz', jobTitle:'Payroll Officer', department:'IT',
                salaryGradeLabel:'Salary Grade 1', otLabel:'20 hours OT',
                benefitsLabel:'Transportation Allowance',
                basicPay:23655, otPay:2425, benefits:1000, grossPay:28090,
                sss:1125, philhealth:500, pagibig:200, withholdingTax:2995,
                totalDeductions:4820, netPay:23680,
            },

            get filteredMyPeriods() {
                return this.myPeriods.filter(p =>
                    (!this.mySearch || p.name.toLowerCase().includes(this.mySearch.toLowerCase())) &&
                    (!this.myYearFilter || String(p.year) === String(this.myYearFilter))
                );
            },

            mySelect(period) {
                this.mySelectedId = period.id;
                this.myActive = { ...this.myPayslipData };
                this.mySubtitle = `February 2026 · Period 2 · ${period.startDate} – ${period.endDate}`;
            },

            exportPayslip(ps) {
                alert('Exporting payslip for: ' + (ps.employeeName || 'employee'));
            },

            init() {
                window.addEventListener('storage', () => {
                    this.sidebarCollapsed = localStorage.getItem('sidebarCollapsed') === 'true';
                });
                if (this.allPayslips.length > 0) this.allSelect(this.allPayslips[0]);
            },

            fmt(n) {
                return Number(n).toLocaleString('en-PH', { minimumFractionDigits:2, maximumFractionDigits:2 });
            },
        }
    }
    </script>

</body>
</html>
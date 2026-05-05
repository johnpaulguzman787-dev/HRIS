<!DOCTYPE html>
<html lang="en">
<head>
    <link rel="icon" type="image/png" href="{{ asset('images/HRISLogo-Icon.png') }}">
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>My Payslips – MediSource</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <style>
        [x-cloak] { display: none !important; }
        * { font-family: 'Inter', sans-serif; }
        body { background: #f0f2f5; }

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

        .anim-1 { animation:fadeUp 0.44s cubic-bezier(0.22,1,0.36,1) both; }
        .anim-2 { animation:fadeUp 0.44s 0.07s cubic-bezier(0.22,1,0.36,1) both; }
        .anim-3 { animation:fadeUp 0.44s 0.14s cubic-bezier(0.22,1,0.36,1) both; }
        .slide-in-right { animation:slideRight 0.28s cubic-bezier(0.22,1,0.36,1) both; }

        /* ── Summary Cards ── */
        .cards-wrap {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 16px;
        }
        .summary-card {
            background:#fff; border-radius:10px; border:1px solid #e5e7eb;
            padding:22px 26px; flex:1 1 200px;
            transition:transform 0.2s ease, box-shadow 0.2s ease;
            animation:scaleIn 0.44s cubic-bezier(0.22,1,0.36,1) both;
        }
        .summary-card:nth-child(1) { animation-delay:0.04s; }
        .summary-card:nth-child(2) { animation-delay:0.11s; }
        .summary-card:nth-child(3) { animation-delay:0.18s; }
        .summary-card:hover { transform:translateY(-2px); box-shadow:0 8px 24px rgba(59,130,246,0.10); }
        .s-label { font-size:0.78rem; color:#9ca3af; margin-bottom:6px; }
        .s-value { font-size:1.75rem; font-weight:700; color:#1e293b; letter-spacing:-0.5px; }
        .s-sub   { font-size:0.72rem; color:#9ca3af; margin-top:4px; }

        /* ── Badges ── */
        .badge-pending   { background:#fff3e0; color:#e65100; }
        .badge-submitted { background:#e3f2fd; color:#1565c0; }
        .badge-released  { background:#e8f5e9; color:#2e7d32; }

        /* ── Tables ── */
        .data-table { width:100%; border-collapse:collapse; min-width:600px; }
        .data-table thead th {
            padding: 13px 24px;
            font-size: 0.75rem;
            font-weight: 600;
            color: #9ca3af;
            text-transform: uppercase;
            letter-spacing: 0.06em;
            white-space: nowrap;
            border-bottom: 1px solid #f1f5f9;
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

        /* ── Buttons (unified) ── */
        .btn-primary {
            background:#2563eb;
            color:#fff;
            border-radius:8px;
            padding:10px 24px;
            font-size:0.875rem;
            font-weight:600;
            display:inline-flex;
            align-items:center;
            gap:7px;
            border:none;
            cursor:pointer;
            transition:background 0.18s, transform 0.15s;
            white-space:nowrap;
            justify-content:center;
        }
        .btn-primary:hover { background:#1d4ed8; transform:translateY(-1px); }
        .btn-primary:active { transform:translateY(0); }
        .btn-primary:disabled {
            background:#d1d5db;
            cursor:not-allowed;
            transform:none;
        }
        .btn-close {
            border:1px solid #e2e8f0;
            border-radius:8px;
            padding:10px 24px;
            font-size:0.875rem;
            font-weight:500;
            color:#374151;
            background:#fff;
            cursor:pointer;
            transition:background 0.15s;
            text-align:center;
            display:inline-flex;
            align-items:center;
            justify-content:center;
            gap:7px;
        }
        .btn-primary, .btn-close {
            flex: 1;
            width: auto;
        }
        .btn-view {
            border:1px solid #e2e8f0;
            border-radius:7px;
            padding:5px 16px;
            font-size:0.8rem;
            font-weight:500;
            color:#374151;
            background:#fff;
            cursor:pointer;
            transition:all 0.15s;
            display:inline-flex;
            align-items:center;
            gap:4px;
        }
        .btn-view:hover { background:#eff6ff; color:#2563eb; border-color:#bfdbfe; }

        .main-content { transition:margin-left 0.3s cubic-bezier(0.22,1,0.36,1); }

        /* ── Empty State ── */
        .empty-state { text-align:center; padding:48px 24px; color:#9ca3af; }
        .empty-state svg { width:40px; height:40px; margin:0 auto 12px; opacity:0.4; }

        /* ── Transition for margin ── */
        .transition-margin {
            transition: margin-left 0.35s cubic-bezier(0.4, 0, 0.2, 1);
        }

        /* ── Mobile responsiveness ── */
        @media (max-width: 768px) {
            .cards-wrap {
                grid-template-columns: 1fr;
                gap: 12px;
            }
            .summary-card {
                padding: 16px 20px;
            }
            .s-value {
                font-size: 1.5rem;
            }
            .flex.gap-5.items-start {
                flex-direction: column;
            }
            .w-80 {
                width: 100% !important;
                margin-top: 24px;
            }
            .ctrl {
                font-size: 0.8rem;
                padding: 8px 12px;
            }
            .search-wrap {
                max-width: 100% !important;
                flex: 1;
            }
            .px-8 {
                padding-left: 16px;
                padding-right: 16px;
            }
            .data-table thead th,
            .data-table tbody td {
                padding: 10px 12px;
                font-size: 0.75rem;
            }
            .btn-view {
                padding: 4px 12px;
                font-size: 0.7rem;
            }
            .btn-primary, .btn-close {
                padding: 8px 16px;
                font-size: 0.75rem;
            }
            /* My Payslip toolbar: single row, no scroll, search longer */
            .my-toolbar {
                flex-wrap: nowrap !important;
                overflow: hidden !important;
                gap: 8px;
            }
            .my-toolbar .search-wrap {
                flex: 3 !important;
                min-width: 0 !important;
            }
            .my-toolbar .ctrl-select {
                flex: 1 !important;
                min-width: 80px !important;
                width: auto !important;
            }
            .my-toolbar .btn-primary {
                flex: 0 0 auto !important;
                white-space: nowrap;
            }
        }
    </style>
</head>

<body x-data="supervisorPayslipsApp()" x-init="init()" class="flex h-screen overflow-hidden bg-gray-50">

    <!-- ===================== DESKTOP SIDEBAR ===================== -->
    <div class="hidden lg:block">
        @include('supervisor.supervisor_sidebar')
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
            @include('supervisor.supervisor_sidebar')
        </div>
    </div>

    <!-- ===================== MAIN CONTENT ===================== -->
    <div class="flex-1 overflow-y-auto min-h-screen w-full transition-margin"
         :class="sidebarCollapsed ? 'lg:ml-20' : 'lg:ml-72'"
         style="transition: margin-left 0.35s cubic-bezier(0.4, 0, 0.2, 1);">

        {{-- Header --}}
        <header class="bg-gradient-to-r from-blue-600 to-blue-700 text-white sticky top-0 z-10 shadow-lg mt-3 mx-3 rounded-2xl overflow-visible">
            <div class="flex items-center justify-between px-8 py-4">
                <div class="flex items-center gap-3">
                    <button @click="mobileMenuOpen = true"
                            class="lg:hidden p-2 rounded-lg hover:bg-white/20 transition-colors">
                        <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M4 6h16M4 12h16M4 18h16"/>
                        </svg>
                    </button>
                    <h1 class="text-white font-bold text-xl">My Payslips</h1>
                </div>
                <x-notification-bell />
            </div>
        </header>

        <div class = "p-3 lg:p-6">
        {{-- Summary Cards --}}
        <div class="pb-4 anim-1">
            <div class="cards-wrap">
                <div class="summary-card">
                    <div class="s-label">Gross Pay <span x-text="'('+myYearFilter+')'"></span></div>
                    <div class="s-value">&#8369; <span x-text="fmt(myYearGross)"></span></div>
                    <div class="s-sub" x-text="filteredMyPayslips.length + ' payslip(s)'"></div>
                </div>
                <div class="summary-card">
                    <div class="s-label">Net Pay <span x-text="'('+myYearFilter+')'"></span></div>
                    <div class="s-value">&#8369; <span x-text="fmt(myYearNet)"></span></div>
                    <div class="s-sub" x-text="filteredMyPayslips.length + ' payslip(s)'"></div>
                </div>
                <div class="summary-card">
                    <div class="s-label">Total Deductions <span x-text="'('+myYearFilter+')'"></span></div>
                    <div class="s-value">&#8369; <span x-text="fmt(myYearDeductions)"></span></div>
                    <div class="s-sub" x-text="filteredMyPayslips.length + ' payslip(s)'"></div>
                </div>
            </div>
        </div>

        {{-- Toolbar --}}
        <div class="pb-4 flex items-center gap-2 anim-2 my-toolbar">
            <div class="search-wrap" style="flex:3;">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-4.35-4.35M17 11A6 6 0 105 11a6 6 0 0012 0z"/>
                </svg>
                <input type="text" placeholder="Search period…" class="ctrl w-full" x-model="mySearch">
            </div>
            <select class="ctrl ctrl-select" style="flex:1; min-width:90px; width:auto;" x-model="myYearFilter">
                @for($y=now()->year; $y>=now()->year-3; $y--)
                <option value="{{ $y }}">{{ $y }}</option>
                @endfor
            </select>
            <button @click="exportMyAllPayslips()"
                    :disabled="filteredMyPayslips.length === 0"
                    :style="filteredMyPayslips.length === 0 ? 'background:#d1d5db;cursor:not-allowed;' : ''"
                    class="flex items-center gap-2 px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-xs font-bold rounded-xl border-none cursor-pointer transition-colors whitespace-nowrap">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                Export All PDF
            </button>
        </div>

        {{-- Table + Panel --}}
        <div class="pb-10 anim-3">
            <div class="flex flex-col lg:flex-row gap-5 items-start">

                <div class="flex-1 min-w-0 w-full">
                    <h3 class="text-base font-bold text-gray-800 mb-3">My Payroll Periods</h3>
                    <div class="bg-white rounded-xl border border-gray-100 shadow-sm overflow-x-auto">
                        <table class="data-table min-w-[500px]">
                            <thead><tr class="border-t border-b border-gray-100 bg-gray-50/60">
                                <th>Period Name</th><th>Start Date</th><th>End Date</th><th>Status</th><th></th>
                            </tr></thead>
                            <tbody>
                                <template x-if="filteredMyPayslips.length === 0">
                                    <tr><td colspan="5" class="empty-state">
                                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                                        No payslips for <span x-text="myYearFilter"></span>
                                    </td></tr>
                                </template>
                                <template x-for="p in filteredMyPayslips" :key="p.id">
                                    <tr :class="mySelectedId===p.id&&'row-active'" @click="mySelect(p)">
                                        <td class="font-medium" x-text="p.periodName"></td>
                                        <td class="text-gray-500" x-text="p.startDate"></td>
                                        <td class="text-gray-500" x-text="p.endDate"></td>
                                        <td>
                                            <span class="px-3 py-1 rounded-full text-xs font-semibold"
                                                  :class="p.status==='Released' ? 'badge-released' : p.status==='Submitted' ? 'badge-submitted' : 'badge-pending'"
                                                  x-text="p.status"></span>
                                        </td>
                                        <td class="text-right"><button class="btn-view" @click.stop="mySelect(p)">View</button></td>
                                    </tr>
                                </template>
                            </tbody>
                        </table>
                    </div>
                </div>

                {{-- My Payslip Panel  --}}
                <div class="w-full lg:w-80 flex-shrink-0" x-show="mySelectedId!==null" x-cloak>
                    <div class="bg-white rounded-xl border border-gray-100 shadow-sm overflow-hidden slide-in-right">
                        <div class="payslip-header">
                            <div class="text-lg font-bold mb-0.5">MediSource</div>
                            <div class="text-xs text-blue-100 mb-4" x-text="myActive.periodName||'—'"></div>
                            <div class="grid grid-cols-3 gap-1">
                                <div><div class="info-label">Employee</div><div class="info-value" x-text="myEmployeeName||'—'"></div></div>
                                <div><div class="info-label">Job Title</div><div class="info-value" x-text="myJobTitle||'—'"></div></div>
                                <div><div class="info-label">Department</div><div class="info-value" x-text="myDepartment||'—'"></div></div>
                            </div>
                        </div>
                        <div class="payslip-body">
                            <div class="ps-section-title">Earnings</div>
                            <div class="ps-line"><span>Basic Pay</span><span x-text="'&#8369; '+fmt(myActive.basicPay||0)"></span></div>
                            <div class="ps-line"><span>OT Pay</span><span x-text="'&#8369; '+fmt(myActive.otPay||0)"></span></div>
                            <div class="ps-line"><span>Benefits</span><span x-text="'&#8369; '+fmt(myActive.benefits||0)"></span></div>
                            <template x-if="(myActive.otherAdditions||0) > 0">
                                <div class="ps-line"><span>Other Additions</span><span x-text="'&#8369; '+fmt(myActive.otherAdditions||0)"></span></div>
                            </template>
                            <div class="ps-line bold"><span>Gross Pay</span><span x-text="'&#8369; '+fmt(myActive.grossPay||0)"></span></div>
                            <div class="ps-section-title">Deductions</div>
                            <div class="ps-line"><span>SSS</span><span class="text-red-500" x-text="'-&#8369; '+fmt(myActive.sss||0)"></span></div>
                            <div class="ps-line"><span>PhilHealth</span><span class="text-red-500" x-text="'-&#8369; '+fmt(myActive.philhealth||0)"></span></div>
                            <div class="ps-line"><span>Pag-IBIG</span><span class="text-red-500" x-text="'-&#8369; '+fmt(myActive.pagibig||0)"></span></div>
                            <div class="ps-line"><span>Withholding Tax</span><span class="text-red-500" x-text="'-&#8369; '+fmt(myActive.withholdingTax||0)"></span></div>
                            <template x-if="(myActive.lateDeduction||0) > 0">
                                <div class="ps-line"><span>Late Deduction</span><span class="text-red-500" x-text="'-&#8369; '+fmt(myActive.lateDeduction||0)"></span></div>
                            </template>
                            <template x-if="(myActive.otherDeductions||0) > 0">
                                <div class="ps-line"><span>Other Deductions</span><span class="text-red-500" x-text="'-&#8369; '+fmt(myActive.otherDeductions||0)"></span></div>
                            </template>
                            <div class="ps-line bold"><span>Total Deductions</span><span class="text-red-500" x-text="'-&#8369; '+fmt(myActive.totalDeductions||0)"></span></div>
                            <div class="ps-section-title">Calculation</div>
                            <div class="ps-line"><span>Gross Pay</span><span x-text="'&#8369; '+fmt(myActive.grossPay||0)"></span></div>
                            <div class="ps-line"><span>Total Deductions</span><span class="text-red-500" x-text="'-&#8369; '+fmt(myActive.totalDeductions||0)"></span></div>
                            <div class="ps-line bold"><span>Net Pay</span><span x-text="'&#8369; '+fmt(myActive.netPay||0)"></span></div>
                            <div class="flex gap-2 mt-5">
                                <button class="btn-close" @click="mySelectedId=null;myActive={}">Close</button>
                                <button class="btn-primary" @click="exportMyPayslip()">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                                    Export PDF
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </div>
        </div>

    </div>{{-- /main-content --}}

    <script>
    function supervisorPayslipsApp() {
        return {
            sidebarCollapsed: localStorage.getItem('sidebarCollapsed') === 'true',
            mobileMenuOpen: false,

            myEmployeeId:   {{ $authEmployee?->id ?? 'null' }},
            myEmployeeName: '{{ addslashes($authEmployee?->full_name ?? '') }}',
            myJobTitle:     '{{ addslashes($authEmployee?->jobTitle?->title ?? '—') }}',
            myDepartment:   '{{ addslashes($authEmployee?->department?->name ?? '—') }}',

            mySearch:     '',
            myYearFilter: '{{ now()->year }}',
            mySelectedId: null,
            myActive:     {},
            myPayslips:   @json($myPayslips),

            get filteredMyPayslips() {
                return this.myPayslips.filter(p =>
                    (!this.mySearch || p.periodName.toLowerCase().includes(this.mySearch.toLowerCase())) &&
                    (!this.myYearFilter || String(p.year) === String(this.myYearFilter))
                );
            },

            get myYearGross()      { return this.filteredMyPayslips.reduce((s, p) => s + p.grossPay, 0); },
            get myYearNet()        { return this.filteredMyPayslips.reduce((s, p) => s + p.netPay, 0); },
            get myYearDeductions() { return this.filteredMyPayslips.reduce((s, p) => s + p.totalDeductions, 0); },

            mySelect(payslip) {
                this.mySelectedId = payslip.id;
                this.myActive = { ...payslip };
            },

            exportMyPayslip() {
                const ps = this.myActive;
                const html = this._buildPayslipHtml(
                    this.myEmployeeName, this.myJobTitle, this.myDepartment,
                    ps.periodName, ps
                );
                this._printHtml(html);
            },

            exportMyAllPayslips() {
                const slips = this.filteredMyPayslips;
                if (!slips.length) return;
                const f = n => Number(n||0).toLocaleString('en-PH',{minimumFractionDigits:2,maximumFractionDigits:2});
                const title = `${this.myEmployeeName||'My'} \u2013 Payslips ${this.myYearFilter}`;
                const pages = slips.map((ps, i) => {
                    const pb = i < slips.length - 1 ? 'page-break-after:always;' : '';
                    return `<div style="padding:40px 48px;max-width:620px;margin:0 auto;${pb}">
<div style="margin-bottom:28px;"><div style="font-size:20px;font-weight:700;color:#2563eb;">MediSource</div><div style="font-size:12px;color:#64748b;margin-top:2px;">${ps.periodName||'—'}</div></div>
<div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:16px;background:#f8faff;border:1px solid #e2e8f0;border-radius:10px;padding:16px 20px;margin-bottom:24px;">
<div><div style="font-size:10px;color:#94a3b8;text-transform:uppercase;letter-spacing:0.05em;margin-bottom:3px;">Employee</div><div style="font-size:13px;font-weight:600;color:#1e293b;">${this.myEmployeeName||'—'}</div></div>
<div><div style="font-size:10px;color:#94a3b8;text-transform:uppercase;letter-spacing:0.05em;margin-bottom:3px;">Job Title</div><div style="font-size:13px;font-weight:600;color:#1e293b;">${this.myJobTitle||'—'}</div></div>
<div><div style="font-size:10px;color:#94a3b8;text-transform:uppercase;letter-spacing:0.05em;margin-bottom:3px;">Department</div><div style="font-size:13px;font-weight:600;color:#1e293b;">${this.myDepartment||'—'}</div></div>
</div>
<div style="font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:0.06em;color:#64748b;margin:20px 0 8px;padding-bottom:4px;border-bottom:1px solid #e2e8f0;">Earnings</div>
<div style="display:flex;justify-content:space-between;font-size:13px;color:#475569;padding:5px 0;"><span>Basic Pay</span><span>&#8369; ${f(ps.basicPay)}</span></div>
<div style="display:flex;justify-content:space-between;font-size:13px;color:#475569;padding:5px 0;"><span>OT Pay</span><span>&#8369; ${f(ps.otPay)}</span></div>
<div style="display:flex;justify-content:space-between;font-size:13px;color:#475569;padding:5px 0;"><span>Benefits</span><span>&#8369; ${f(ps.benefits)}</span></div>
${ps.otherAdditions > 0 ? `<div style="display:flex;justify-content:space-between;font-size:13px;color:#475569;padding:5px 0;"><span>Other Additions</span><span>&#8369; ${f(ps.otherAdditions)}</span></div>` : ''}
<div style="display:flex;justify-content:space-between;font-size:14px;font-weight:700;color:#0f172a;padding:8px 0 5px;margin-top:4px;border-top:2px solid #e2e8f0;"><span>Gross Pay</span><span>&#8369; ${f(ps.grossPay)}</span></div>
<div style="font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:0.06em;color:#64748b;margin:20px 0 8px;padding-bottom:4px;border-bottom:1px solid #e2e8f0;">Deductions</div>
<div style="display:flex;justify-content:space-between;font-size:13px;color:#475569;padding:5px 0;"><span>SSS</span><span style="color:#dc2626;">-&#8369; ${f(ps.sss)}</span></div>
<div style="display:flex;justify-content:space-between;font-size:13px;color:#475569;padding:5px 0;"><span>PhilHealth</span><span style="color:#dc2626;">-&#8369; ${f(ps.philhealth)}</span></div>
<div style="display:flex;justify-content:space-between;font-size:13px;color:#475569;padding:5px 0;"><span>Pag-IBIG</span><span style="color:#dc2626;">-&#8369; ${f(ps.pagibig)}</span></div>
<div style="display:flex;justify-content:space-between;font-size:13px;color:#475569;padding:5px 0;"><span>Withholding Tax</span><span style="color:#dc2626;">-&#8369; ${f(ps.withholdingTax)}</span></div>
${ps.lateDeduction > 0 ? `<div style="display:flex;justify-content:space-between;font-size:13px;color:#475569;padding:5px 0;"><span>Late Deduction</span><span style="color:#dc2626;">-&#8369; ${f(ps.lateDeduction)}</span></div>` : ''}
${ps.otherDeductions > 0 ? `<div style="display:flex;justify-content:space-between;font-size:13px;color:#475569;padding:5px 0;"><span>Other Deductions</span><span style="color:#dc2626;">-&#8369; ${f(ps.otherDeductions)}</span></div>` : ''}
<div style="display:flex;justify-content:space-between;font-size:14px;font-weight:700;color:#0f172a;padding:8px 0 5px;margin-top:4px;border-top:2px solid #e2e8f0;"><span>Total Deductions</span><span style="color:#dc2626;">-&#8369; ${f(ps.totalDeductions)}</span></div>
<div style="font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:0.06em;color:#64748b;margin:20px 0 8px;padding-bottom:4px;border-bottom:1px solid #e2e8f0;">Summary</div>
<div style="display:flex;justify-content:space-between;font-size:13px;color:#475569;padding:5px 0;"><span>Gross Pay</span><span>&#8369; ${f(ps.grossPay)}</span></div>
<div style="display:flex;justify-content:space-between;font-size:13px;color:#475569;padding:5px 0;"><span>Total Deductions</span><span style="color:#dc2626;">-&#8369; ${f(ps.totalDeductions)}</span></div>
<div style="display:flex;justify-content:space-between;font-size:14px;font-weight:700;color:#0f172a;padding:8px 0 5px;margin-top:4px;border-top:2px solid #e2e8f0;"><span>Net Pay</span><span>&#8369; ${f(ps.netPay)}</span></div>
<div style="margin-top:32px;font-size:10px;color:#94a3b8;text-align:center;border-top:1px solid #e2e8f0;padding-top:12px;">This is a system-generated payslip from MediSource HRIS.</div>
</div>`;
                }).join('');
                const html = `<!DOCTYPE html><html><head><meta charset="UTF-8"><title>${title}</title><style>*{font-family:Arial,sans-serif;box-sizing:border-box;margin:0;padding:0;}body{color:#1e293b;}@media print{@page{margin:.5cm;}}</style></head><body>${pages}</body></html>`;
                this._printHtml(html);
            },

            _buildPayslipHtml(name, jobTitle, department, period, ps) {
                const f = n => Number(n||0).toLocaleString('en-PH', { minimumFractionDigits:2, maximumFractionDigits:2 });
                return `<!DOCTYPE html>
<html>
<head>
    <link rel="icon" type="image/png" href="{{ asset('images/HRISLogo-Icon.png') }}">
    <meta charset="UTF-8">
    <title>Payslip – ${name}</title>
    <style>
        * { font-family: Arial, sans-serif; box-sizing: border-box; margin:0; padding:0; }
        body { padding: 48px; max-width: 620px; margin: 0 auto; color: #1e293b; }
        .header { margin-bottom: 28px; }
        .company { font-size: 20px; font-weight: 700; color: #2563eb; }
        .period-label { font-size: 12px; color: #64748b; margin-top: 2px; }
        .info-grid { display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 16px; background: #f8faff; border: 1px solid #e2e8f0; border-radius: 10px; padding: 16px 20px; margin-bottom: 24px; }
        .info-label { font-size: 10px; color: #94a3b8; text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 3px; }
        .info-value { font-size: 13px; font-weight: 600; color: #1e293b; }
        .section-title { font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.06em; color: #64748b; margin: 20px 0 8px; padding-bottom: 4px; border-bottom: 1px solid #e2e8f0; }
        .line { display: flex; justify-content: space-between; font-size: 13px; color: #475569; padding: 5px 0; }
        .line.bold { font-weight: 700; color: #0f172a; font-size: 14px; border-top: 2px solid #e2e8f0; padding-top: 8px; margin-top: 4px; }
        .red { color: #dc2626; }
        .footer { margin-top: 32px; font-size: 10px; color: #94a3b8; text-align: center; border-top: 1px solid #e2e8f0; padding-top: 12px; }
        @media print { body { padding: 24px; } @page { margin: 1cm; } }
    </style>
</head>
<body>
    <div class="header">
        <div class="company">MediSource</div>
        <div class="period-label">${period || '—'}</div>
    </div>
    <div class="info-grid">
        <div><div class="info-label">Employee</div><div class="info-value">${name || '—'}</div></div>
        <div><div class="info-label">Job Title</div><div class="info-value">${jobTitle || '—'}</div></div>
        <div><div class="info-label">Department</div><div class="info-value">${department || '—'}</div></div>
    </div>
    <div class="section-title">Earnings</div>
    <div class="line"><span>Basic Pay</span><span>₱ ${f(ps.basicPay)}</span></div>
    <div class="line"><span>OT Pay</span><span>₱ ${f(ps.otPay)}</span></div>
    <div class="line"><span>Benefits</span><span>₱ ${f(ps.benefits)}</span></div>
    ${ps.otherAdditions > 0 ? `<div class="line"><span>Other Additions</span><span>₱ ${f(ps.otherAdditions)}</span></div>` : ''}
    <div class="line bold"><span>Gross Pay</span><span>₱ ${f(ps.grossPay)}</span></div>
    <div class="section-title">Deductions</div>
    <div class="line"><span>SSS</span><span class="red">-₱ ${f(ps.sss)}</span></div>
    <div class="line"><span>PhilHealth</span><span class="red">-₱ ${f(ps.philhealth)}</span></div>
    <div class="line"><span>Pag-IBIG</span><span class="red">-₱ ${f(ps.pagibig)}</span></div>
    <div class="line"><span>Withholding Tax</span><span class="red">-₱ ${f(ps.withholdingTax)}</span></div>
    ${ps.lateDeduction > 0 ? `<div class="line"><span>Late Deduction</span><span class="red">-₱ ${f(ps.lateDeduction)}</span></div>` : ''}
    ${ps.otherDeductions > 0 ? `<div class="line"><span>Other Deductions</span><span class="red">-₱ ${f(ps.otherDeductions)}</span></div>` : ''}
    <div class="line bold"><span>Total Deductions</span><span class="red">-₱ ${f(ps.totalDeductions)}</span></div>
    <div class="section-title">Summary</div>
    <div class="line"><span>Gross Pay</span><span>₱ ${f(ps.grossPay)}</span></div>
    <div class="line"><span>Total Deductions</span><span class="red">-₱ ${f(ps.totalDeductions)}</span></div>
    <div class="line bold"><span>Net Pay</span><span>₱ ${f(ps.netPay)}</span></div>
    <div class="footer">This is a system-generated payslip from MediSource HRIS.</div>
</body>
</html>`;
            },

            _printHtml(html) {
                const w = window.open('', '_blank', 'width=700,height=860,scrollbars=yes');
                if (!w) { alert('Please allow pop-ups to export payslips.'); return; }
                w.document.write(html);
                w.document.close();
                w.document.querySelectorAll('[x-show],[x-cloak]').forEach(el => el.remove());
                w.focus();
                setTimeout(() => { w.print(); }, 400);
            },

            init() {
                window.addEventListener('sidebar-toggle', e => { this.sidebarCollapsed = e.detail.collapsed; });
                if (this.myPayslips.length > 0) this.mySelect(this.myPayslips[0]);
            },

            fmt(n) {
                return Number(n).toLocaleString('en-PH', { minimumFractionDigits:2, maximumFractionDigits:2 });
            },
        };
    }
    </script>

</body>
</html>
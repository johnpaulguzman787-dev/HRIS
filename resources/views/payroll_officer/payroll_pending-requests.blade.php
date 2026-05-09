@php
    $currentRoute = request()->route()->getName();

    $sidebarUser     = auth()->user();
    $sidebarEmployee = $sidebarUser
        ? \App\Models\Employee::with('jobTitle')->where('user_id', $sidebarUser->id)->first()
        : null;
    $sidebarInitials = $sidebarEmployee
        ? strtoupper(substr($sidebarEmployee->fname, 0, 1) . substr($sidebarEmployee->lname, 0, 1))
        : ($sidebarUser ? strtoupper(substr($sidebarUser->email, 0, 2)) : 'U');
    $sidebarName = $sidebarEmployee
        ? trim($sidebarEmployee->fname . ' ' . $sidebarEmployee->lname)
        : ($sidebarUser?->email ?? 'User');
    $sidebarRole = $sidebarEmployee?->jobTitle?->title ?? ($sidebarUser?->role ?? '—');

    $attendanceRoutes = ['payroll_officer.attendance.reports', 'payroll_officer.attendance.shift', 'payroll_officer.attendance.leave', 'payroll_officer.leave.management'];
    $employeeRoutes   = ['payroll_officer.dashboard','payroll_officer.profile'];
    $payrollRoutes    = ['payroll_officer.payroll', 'payroll_officer.payslips', 'payroll_officer.contributions'];
    $requestRoutes    = ['payroll_officer.requests.pending','payroll_officer.requests.approved'];
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
    <link rel="icon" type="image/png" href="{{ asset('images/HRISLogo-Icon.png') }}">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=yes, viewport-fit=cover">
    <title>Pending Requests — MEDISOURCE</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        *{font-family:'DM Sans',sans-serif;box-sizing:border-box;}
        [x-cloak]{display:none!important;}
        @@keyframes pulseDot{0%,100%{opacity:1;transform:scale(1);}50%{opacity:.5;transform:scale(1.3);}}
        :root{--blue:#3b82f6;--blue-dark:#1d4ed8;--blue-light:#eff6ff;--muted:#6b7280;--border:#e5e7eb;}
        .nav-item{transition:background .15s,color .15s;}
        .chevron-icon{transition:transform .25s cubic-bezier(.4,0,.2,1);}
        .avatar-ring{box-shadow:0 0 0 3px rgba(59,130,246,.25);}

        /* DESKTOP SIDEBAR */
        .desktop-sidebar { display: none; }
        @media (min-width: 1024px) {
            .desktop-sidebar { display: block; }
        }
        @media (max-width: 1023px) {
            .main-content {
                margin-left: 0 !important;
            }
        }

        /* stat cards - responsive grid */
        .stat-cards{display:grid;grid-template-columns:repeat(4,1fr);gap:16px;margin-bottom:28px;}
        .stat-card{border-radius:14px;padding:20px 22px;}
        .sc-blue{background:#dbeafe;} .sc-orange{background:#ffedd5;} .sc-purple{background:#ede9fe;} .sc-red{background:#fee2e2;}
        .sc-blue .slabel{color:#1d4ed8;} .sc-orange .slabel{color:#c2410c;} .sc-purple .slabel{color:#6d28d9;} .sc-red .slabel{color:#dc2626;}
        .slabel{font-size:13px;font-weight:500;margin-bottom:6px;}
        .sval{font-size:36px;font-weight:800;color:#111827;line-height:1;margin-bottom:4px;}
        .sc-blue .ssub{color:#3b82f6;} .sc-orange .ssub{color:#f97316;} .sc-purple .ssub{color:#7c3aed;} .sc-red .ssub{color:#ef4444;}
        .ssub{font-size:12px;}
        @media (max-width: 768px) {
            .stat-cards{grid-template-columns:repeat(2,1fr)!important;gap:10px!important;margin-bottom:18px!important;}
            .stat-card{padding:14px 14px!important;border-radius:12px!important;}
            .sval{font-size:28px!important;}
            .slabel{font-size:11.5px!important;line-height:1.3;}
            .ssub{font-size:11px!important;}
        }
        @media (max-width: 375px) {.sval{font-size:24px!important;}}

        /* toolbar - responsive */
        .toolbar{display:flex;align-items:center;justify-content:space-between;margin-bottom:18px;gap:12px;flex-wrap:wrap;}
        .toolbar-title{font-size:18px;font-weight:700;color:#111827;}
        .toolbar-right{display:flex;align-items:center;gap:10px;flex-wrap:wrap;}
        .search-box{display:flex;align-items:center;gap:8px;background:#fff;border:1px solid var(--border);border-radius:9px;padding:8px 14px;}
        .search-box input{border:none;background:transparent;outline:none;font-size:13px;color:#111827;width:150px;font-family:inherit;}
        .fsel{appearance:none;background:#f9fafb;border:1px solid var(--border);border-radius:8px;padding:8px 28px 8px 12px;font-size:13px;color:#374151;cursor:pointer;outline:none;font-family:inherit;background-image:url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='10' height='6' fill='none'%3E%3Cpath d='M1 1l4 4 4-4' stroke='%236b7280' stroke-width='1.5' stroke-linecap='round'/%3E%3C/svg%3E");background-repeat:no-repeat;background-position:right 10px center;}
        .btn-primary{display:inline-flex;align-items:center;gap:6px;padding:9px 20px;border-radius:9px;font-size:13px;font-weight:600;font-family:inherit;cursor:pointer;border:none;background:var(--blue);color:#fff;transition:background .15s;white-space:nowrap;}
        .btn-primary:hover{background:var(--blue-dark);}
        @media (max-width: 768px) {
            .toolbar{flex-direction:column!important;align-items:stretch!important;gap:10px!important;}
            .toolbar-right{flex-direction:column!important;align-items:stretch!important;gap:8px!important;}
            .search-box{width:100%!important;border-radius:10px!important;}
            .search-box input{width:100%!important;}
            .fsel{width:100%!important;padding:8px 24px 8px 10px!important;}
            .btn-primary{width:100%!important;justify-content:center!important;padding:12px 16px!important;}
        }

        /* Filters row - horizontal scroll on mobile */
        .toolbar-filters-row {
            display: flex !important;
            flex-wrap: nowrap !important;
            overflow-x: auto !important;
            gap: 8px !important;
            -webkit-overflow-scrolling: touch;
            scrollbar-width: none;
        }
        .toolbar-filters-row::-webkit-scrollbar { display: none; }
        .toolbar-filters-row .fsel {
            flex-shrink: 0 !important;
            font-size: 12px !important;
            padding: 8px 24px 8px 10px !important;
            white-space: nowrap;
        }

        /* request card - responsive */
        .rcard{background:#fff;border:1px solid var(--border);border-radius:14px;margin-bottom:16px;overflow:hidden;box-shadow:0 1px 6px rgba(0,0,0,.04);}
        .rcard-head{display:flex;align-items:center;justify-content:space-between;padding:16px 20px 12px;border-bottom:1px solid #f3f4f6;flex-wrap:wrap;gap:8px;}
        .rcard-left{display:flex;align-items:center;gap:12px;}
        .emp-av{width:40px;height:40px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:14px;font-weight:700;color:#fff;flex-shrink:0;}
        .emp-name{font-size:15px;font-weight:700;color:#111827;}
        .emp-dept{font-size:12px;color:var(--muted);margin-top:1px;}
        .rcard-right{display:flex;align-items:center;gap:8px;flex-wrap:wrap;}
        .req-id{font-size:12px;color:var(--muted);font-weight:500;}
        .badge{padding:3px 11px;border-radius:20px;font-size:11.5px;font-weight:600;display:inline-block;white-space:nowrap;}
        .b-leave{background:#dbeafe;color:#1d4ed8;}
        .b-ot{background:#ffedd5;color:#c2410c;}
        .b-shift{background:#fce7f3;color:#be185d;}
        .b-await{background:#f0fdf4;color:#15803d;border:1px solid #bbf7d0;}
        @media (max-width: 768px) {
            .rcard-head{flex-direction:column!important;align-items:flex-start!important;gap:10px!important;padding:14px 14px 12px!important;}
            .emp-name{font-size:15px!important;}
            .emp-dept{font-size:11px!important;}
            .rcard-right{flex-wrap:wrap!important;gap:6px!important;}
            .badge{font-size:10px!important;padding:2px 8px!important;}
        }

        /* card body - responsive */
        .rcard-body{padding:16px 20px;}
        .dgrid{display:grid;grid-template-columns:repeat(4,1fr);gap:12px;background:#f9fafb;border-radius:10px;padding:14px 16px;margin-bottom:12px;}
        .dlabel{font-size:11px;color:var(--muted);font-weight:500;margin-bottom:3px;text-transform:uppercase;letter-spacing:.3px;}
        .dval{font-size:13px;font-weight:700;color:#111827;}
        .rsection{margin-bottom:10px;}
        .rlabel{font-size:12px;color:var(--muted);font-weight:500;margin-bottom:3px;}
        .rtext{font-size:13px;color:#374151;}
        @media (max-width: 768px) {
            .rcard-body{padding:12px 14px!important;}
            .dgrid{grid-template-columns:repeat(2,1fr)!important;gap:10px!important;padding:12px!important;margin-bottom:10px!important;}
            .dlabel{font-size:10px!important;}
            .dval{font-size:12.5px!important;}
            .rlabel{font-size:11px!important;}
            .rtext{font-size:12px!important;}
        }

        /* progress - responsive */
        .prog-steps{display:flex;align-items:flex-start;gap:0;margin-top:6px;flex-wrap:wrap;justify-content:center;}
        .pstep{display:flex;flex-direction:column;align-items:center;gap:4px;}
        .pcircle{width:30px;height:30px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:13px;flex-shrink:0;}
        .pc-done{background:#dcfce7;border:2px solid #16a34a;color:#16a34a;}
        .pc-active{background:#eff6ff;border:2px solid var(--blue);color:var(--blue);}
        .pname{font-size:11px;font-weight:600;color:#374151;text-align:center;}
        .pstatus{font-size:10px;color:var(--muted);text-align:center;}
        .pline{height:2px;width:60px;background:#16a34a;flex-shrink:0;margin-top:14px;}
        @media (max-width: 768px) {
            .pline{width:30px!important;margin-top:14px!important;}
            .pcircle{width:28px!important;height:28px!important;}
            .pname{font-size:9px!important;}
            .pstatus{font-size:8px!important;}
        }
        @media (max-width: 480px) {
            .prog-steps{flex-direction:column!important;align-items:flex-start!important;gap:8px!important;}
            .pline{width:2px!important;height:20px!important;margin-left:14px!important;margin-top:0!important;}
            .pstep{flex-direction:row!important;gap:12px!important;width:100%!important;justify-content:flex-start!important;}
        }

        /* action buttons */
        .action-row{display:grid;grid-template-columns:1fr;gap:10px;padding:0 20px 16px;}
        .btn-cancel-req{display:flex;align-items:center;justify-content:center;gap:6px;padding:10px;border-radius:9px;font-size:13px;font-weight:600;font-family:inherit;cursor:pointer;border:none;background:#fee2e2;color:#dc2626;transition:background .15s;width:100%;}
        .btn-cancel-req:hover{background:#fecaca;}
        @media (max-width: 768px) {
            .action-row{padding:0 14px 14px!important;}
            .btn-cancel-req{padding:12px!important;font-size:14px!important;border-radius:10px!important;}
        }

        /* modal - responsive (slide up on mobile) */
        .modal-overlay{position:fixed;inset:0;background:rgba(0,0,0,.4);display:flex;align-items:center;justify-content:center;z-index:999;}
        .modal-box{background:#fff;border-radius:16px;padding:28px 32px;width:520px;max-width:95vw;box-shadow:0 20px 60px rgba(0,0,0,.15);}
        .modal-title{font-size:17px;font-weight:800;color:#111827;}
        .flabel{font-size:12px;font-weight:600;color:#374151;text-transform:uppercase;letter-spacing:.5px;margin-bottom:5px;display:block;}
        .finput{width:100%;border:1.5px solid var(--border);border-radius:8px;padding:9px 13px;font-size:13px;font-family:inherit;outline:none;color:#111827;transition:border-color .15s;}
        .finput:focus{border-color:var(--blue);}
        .frow{display:grid;grid-template-columns:1fr 1fr;gap:14px;}
        .mactions{display:flex;justify-content:flex-end;gap:10px;margin-top:22px;}
        .btn-cancel{padding:8px 18px;border-radius:8px;font-size:13px;font-weight:600;font-family:inherit;cursor:pointer;border:1.5px solid var(--border);background:#fff;color:#374151;}
        .btn-save{padding:8px 20px;border-radius:8px;font-size:13px;font-weight:600;font-family:inherit;cursor:pointer;border:none;background:var(--blue);color:#fff;}
        .close-btn{width:32px;height:32px;border:2px solid #374151;border-radius:50%;display:flex;align-items:center;justify-content:center;background:none;cursor:pointer;flex-shrink:0;}
        .fsel-modal{appearance:none;background-image:url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='10' height='6' fill='none'%3E%3Cpath d='M1 1l4 4 4-4' stroke='%236b7280' stroke-width='1.5' stroke-linecap='round'/%3E%3C/svg%3E");background-repeat:no-repeat;background-position:right 12px center;}
        .doc-upload{display:flex;flex-direction:column;align-items:center;justify-content:center;gap:8px;border:2px dashed #d1d5db;border-radius:10px;padding:24px 20px;background:#f9fafb;cursor:pointer;}
        @keyframes spin { from { transform: rotate(0deg); } to { transform: rotate(360deg); } }
        @media (max-width: 768px) {
            .modal-overlay{align-items:flex-end!important;}
            .modal-box{width:100%!important;max-width:100%!important;border-radius:20px 20px 0 0!important;padding:24px 20px 32px!important;max-height:92vh!important;overflow-y:auto!important;}
            .modal-box::before{content:'';display:block;width:40px;height:4px;background:#e5e7eb;border-radius:2px;margin:0 auto 20px;}
            .mactions{flex-direction:column-reverse!important;gap:8px!important;}
            .mactions .btn-save,.mactions .btn-cancel{width:100%!important;text-align:center!important;padding:12px!important;font-size:14px!important;}
            .modal-title{font-size:17px!important;}
            .frow{grid-template-columns:1fr!important;}
        }

        .page-content { padding: 24px 32px; }
        @media (max-width: 768px) { .page-content { padding: 14px 14px !important; } }
    </style>
</head>
<body class="bg-gray-50" x-data="{ mobileMenuOpen: false, sidebarCollapsed: localStorage.getItem('sidebarCollapsed') === 'true', showFileReq: false, reqType: '', showCancel: false, selId: null, selName: '', selCancelUrl: '', showResult: false, resultType: 'success', resultTitle: '', resultMessage: '' }"
     x-init="window.addEventListener('sidebar-toggle', e => { sidebarCollapsed = e.detail.collapsed })">

{{-- ═══════════ DESKTOP SIDEBAR ═══════════ --}}
<div class="hidden lg:block desktop-sidebar">
    @include('payroll_officer.payroll_sidebar')
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
        @include('payroll_officer.payroll_sidebar')
    </div>
</div>

{{-- ══════════ MAIN CONTENT ══════════ --}}
<div class="main-content"
     :style="window.innerWidth >= 1024 ? (sidebarCollapsed ? 'margin-left:5rem' : 'margin-left:16rem') : 'margin-left:0'"
     x-on:resize.window="$el.style.marginLeft = window.innerWidth >= 1024 ? (sidebarCollapsed ? '5rem' : '16rem') : '0'"
     style="transition:margin-left .35s cubic-bezier(.4,0,.2,1);min-height:100vh;">

    {{-- HEADER with Hamburger --}}
    <header class="anim-fade bg-gradient-to-br from-blue-500 to-blue-700 sticky top-0 z-10 shadow-lg mt-3 mx-3 lg:mt-4 lg:mx-4 rounded-2xl">
        <div class="px-4 sm:px-6 lg:px-8 py-4 flex items-center justify-between">
            <div class="flex items-center gap-3">
                <button @click="mobileMenuOpen = true" class="lg:hidden p-1.5 rounded-lg hover:bg-white/20 transition-colors text-white">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M4 6h16M4 12h16M4 18h16"/>
                    </svg>
                </button>
                <div>
                    <h1 class="text-xl sm:text-2xl font-bold text-white">Pending Requests</h1>
                    <p class="text-xs sm:text-sm text-blue-100 mt-1">Requests & Approvals</p>
                </div>
            </div>
            <div class="flex items-center space-x-2 sm:space-x-4">
                <x-notification-bell />
            </div>
        </div>
    </header>

    <div class="page-content">

        {{-- STAT CARDS --}}
        <div class="stat-cards">
            <div class="stat-card sc-blue">
                <div class="slabel">Pending Requests</div>
                <div class="sval">{{ $awaitingCount ?? 3 }}</div>
                <div class="ssub">Waiting for action</div>
            </div>
            <div class="stat-card sc-orange">
                <div class="slabel">Leave Requests</div>
                <div class="sval">{{ $leaveCount ?? 1 }}</div>
                <div class="ssub">{{ now()->format('F Y') }}</div>
            </div>
            <div class="stat-card sc-purple">
                <div class="slabel">Shift Arrangement Requests</div>
                <div class="sval">{{ $shiftCount ?? 1 }}</div>
                <div class="ssub">{{ now()->format('F Y') }}</div>
            </div>
            <div class="stat-card sc-red">
                <div class="slabel">Overtime Requests</div>
                <div class="sval">{{ $overtimeCount ?? 1 }}</div>
                <div class="ssub">{{ now()->format('F Y') }}</div>
            </div>
        </div>

        {{-- TOOLBAR --}}
        <div class="toolbar">
            <div class="toolbar-title">Pending Requests</div>
            <div class="toolbar-right">
                <div class="search-box">
                    <svg style="width:15px;height:15px;color:#6b7280;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                    <input type="text" placeholder="Search">
                </div>
                {{-- Filters row — horizontal-scroll on mobile --}}
                <div class="toolbar-filters-row" style="display:flex;gap:10px;">
                    <select class="fsel"><option>All Stages</option><option>Awaiting Approval</option><option>Approved</option><option>Rejected</option></select>
                    <select class="fsel"><option>All Types</option><option>Leave Request</option><option>Overtime Request</option><option>Shift Arrangement</option></select>
                    <select class="fsel"><option>All Departments</option></select>
                </div>
                @canDo('Requests & Approval', 'create')
                <button class="btn-primary" @click="showFileReq=true">
                    <svg style="width:14px;height:14px;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                    File a Request
                </button>
                @endcanDo
            </div>
        </div>

        {{-- REQUEST CARDS --}}
        @forelse($requests as $req)
        <div class="rcard">
            <div class="rcard-head">
                <div class="rcard-left">
                    <div class="emp-av" style="background:{{ $req->type === 'overtime' ? '#ef4444' : ($req->type === 'shift' ? '#8b5cf6' : '#3b82f6') }};">
                        {{ strtoupper(substr(auth()->user()->employee->fname ?? 'M', 0, 1) . substr(auth()->user()->employee->lname ?? 'E', 0, 1)) }}
                    </div>
                    <div>
                        <div class="emp-name">{{ trim((auth()->user()->employee->fname ?? '') . ' ' . (auth()->user()->employee->lname ?? '')) }}</div>
                        <div class="emp-dept">{{ auth()->user()->employee->department->name ?? '—' }}</div>
                    </div>
                </div>
                <div class="rcard-right">
                    <span class="req-id">{{ $req->ref_no }}</span>
                    @if($req->type === 'leave')   <span class="badge b-leave">Leave Request</span>
                    @elseif($req->type === 'overtime') <span class="badge b-ot">Overtime Request</span>
                    @elseif($req->type === 'shift')    <span class="badge b-shift">Shift Arrangement Request</span>
                    @elseif($req->type === 'adjustment') <span class="badge" style="background:#fef3c7;color:#92400e;">Attendance Adjustment</span>
                    @endif
                    <span class="badge b-await">Awaiting Approval</span>
                </div>
            </div>
            <div class="rcard-body">
                @if($req->type === 'leave')
                <div class="dgrid">
                    <div><div class="dlabel">Leave Type</div><div class="dval">{{ $req->leaveType->name ?? '—' }}</div></div>
                    <div><div class="dlabel">Date</div><div class="dval">{{ \Carbon\Carbon::parse($req->start_date)->format('F j') }} – {{ \Carbon\Carbon::parse($req->end_date)->format('j, Y') }}</div></div>
                    <div><div class="dlabel">Filed On</div><div class="dval">{{ $req->created_at->format('F j, Y') }}</div></div>
                    <div><div class="dlabel">Balance</div></div>
                </div>
                @elseif($req->type === 'overtime')
                <div class="dgrid">
                    <div><div class="dlabel">OT Date</div><div class="dval">{{ \Carbon\Carbon::parse($req->ot_date)->format('F j, Y') }}</div></div>
                    <div><div class="dlabel">OT Hours</div><div class="dval">{{ $req->requested_hours }}h</div></div>
                    <div><div class="dlabel">Time Range</div><div class="dval">{{ $req->ot_start_time ? \Carbon\Carbon::parse($req->ot_start_time)->format('g:i A') . ' – ' . \Carbon\Carbon::parse($req->ot_end_time)->format('g:i A') : '—' }}</div></div>
                    <div><div class="dlabel">Filed On</div><div class="dval">{{ $req->created_at->format('F j, Y') }}</div></div>
                </div>
                @elseif($req->type === 'shift')
                <div class="dgrid">
                    <div><div class="dlabel">Current Shift</div><div class="dval">{{ $req->current_shift->name ?? '—' }}</div></div>
                    <div><div class="dlabel">Requested Shift</div><div class="dval">{{ $req->requested_shift->name ?? '—' }}</div></div>
                    <div><div class="dlabel">Effective From</div><div class="dval">{{ \Carbon\Carbon::parse($req->effective_from)->format('F j, Y') }}</div></div>
                    <div><div class="dlabel">Until</div><div class="dval">{{ $req->effective_until ? \Carbon\Carbon::parse($req->effective_until)->format('F j, Y') : 'Ongoing' }}</div></div>
                </div>
                @elseif($req->type === 'adjustment')
                <div class="dgrid">
                    <div><div class="dlabel">Date</div><div class="dval">{{ \Carbon\Carbon::parse($req->attendance_date)->format('F j, Y') }}</div></div>
                    <div><div class="dlabel">Original Time</div><div class="dval">{{ $req->original_clock_in ? \Carbon\Carbon::parse($req->original_clock_in)->format('g:i A') : '—' }} – {{ $req->original_clock_out ? \Carbon\Carbon::parse($req->original_clock_out)->format('g:i A') : '—' }}</div></div>
                    <div><div class="dlabel">Requested Time</div><div class="dval">{{ \Carbon\Carbon::parse($req->requested_clock_in)->format('g:i A') }} – {{ $req->requested_clock_out ? \Carbon\Carbon::parse($req->requested_clock_out)->format('g:i A') : 'N/A' }}</div></div>
                    <div><div class="dlabel">Filed On</div><div class="dval">{{ $req->created_at->format('F j, Y') }}</div></div>
                </div>
                @endif
                <div class="rsection"><div class="rlabel">Reason</div><div class="rtext">{{ $req->reason ?? '—' }}</div></div>
                @if($req->document_path)<div style="margin-bottom:10px;"><div class="rlabel">Documents</div><div style="font-size:13px;color:#3b82f6;font-weight:500;">{{ basename($req->document_path) }}</div></div>@endif
                <div><div class="rlabel">Approval Progress</div>
                <div class="prog-steps">
                    <div class="pstep"><div class="pcircle pc-done"><svg style="width:14px;height:14px;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg></div><div class="pname">You</div><div class="pstatus">Filed</div></div>
                    <div class="pline" style="background:#d1d5db;"></div>
                    @if($req->status === 'supervisor_approved')
                    <div class="pstep"><div class="pcircle pc-done"><svg style="width:14px;height:14px;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg></div><div class="pname">Supervisor</div><div class="pstatus">Approved</div></div>
                    @else
                    <div class="pstep"><div class="pcircle" style="background:#f9fafb;border:2px solid #d1d5db;color:#9ca3af;"><svg style="width:14px;height:14px;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 12h.01M12 12h.01M19 12h.01"/></svg></div><div class="pname">Supervisor</div><div class="pstatus">Pending</div></div>
                    @endif
                    <div class="pline" style="background:#d1d5db;"></div>
                    <div class="pstep"><div class="pcircle" style="background:#f9fafb;border:2px solid #d1d5db;color:#9ca3af;"><svg style="width:14px;height:14px;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 12h.01M12 12h.01M19 12h.01"/></svg></div><div class="pname">HR Manager</div><div class="pstatus">Pending</div></div>
                </div></div>
            </div>
            <div class="action-row">
                <button class="btn-cancel-req" @click="selId={{ $req->id }};selName='{{ addslashes(trim((auth()->user()->employee->fname ?? '').' '.(auth()->user()->employee->lname ?? ''))) }}';selCancelUrl='{{ route('payroll_officer.requests.cancel', $req->id) }}';showCancel=true">
                    <svg style="width:14px;height:14px;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg> Cancel Request
                </button>
            </div>
        </div>
        @empty
        <div style="text-align:center;padding:60px 16px;">
            <div style="font-size:15px;font-weight:600;color:#9ca3af;">No pending requests at this time.</div>
            <div style="font-size:13px;color:#d1d5db;margin-top:4px;">Filed requests will appear here while awaiting approval.</div>
        </div>
        @endforelse

    </div>{{-- /page-content --}}

    {{-- ══ FILE REQUEST MODAL ══ --}}
    <div x-show="showFileReq" class="modal-overlay" x-cloak @click.self="showFileReq=false;reqType=''">
        <div class="modal-box" style="max-height:90vh;overflow-y:auto;">
            <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:22px;">
                <div class="modal-title">File Request</div>
                <button @click="showFileReq=false;reqType=''" class="close-btn"><svg style="width:14px;height:14px;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"/></svg></button>
            </div>

            <div style="margin-bottom:16px;">
                <label class="flabel">Request Type</label>
                <select class="finput fsel-modal" x-model="reqType" style="appearance:none;background-image:url(\"data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='10' height='6' fill='none'%3E%3Cpath d='M1 1l4 4 4-4' stroke='%236b7280' stroke-width='1.5' stroke-linecap='round'/%3E%3C/svg%3E\");background-repeat:no-repeat;background-position:right 12px center;">
                    <option value="">Choose request type</option>
                    <option value="leave">Leave Request</option>
                    <option value="overtime">Overtime Request</option>
                    <option value="shift">Shift Arrangement</option>
                    <option value="adjustment">Attendance Adjustment</option>
                </select>
            </div>

            {{-- LEAVE --}}
            <div x-show="reqType==='leave'" x-transition
                 x-data="{ leaveTypeId:'', startDate:'', endDate:'', reason:'', fileName:'', saving:false, errorMsg:'',
                     handleFile(e){ const f=e.target.files[0]; this.fileName=f?f.name:''; },
                     async submit(){
                         this.errorMsg='';
                         if(!this.leaveTypeId||!this.startDate||!this.endDate||!this.reason){ $root.resultType='error'; $root.resultTitle='Missing Fields'; $root.resultMessage='Please fill in all required fields.'; $root.showResult=true; return; }
                         this.saving=true;
                         const form=new FormData();
                         form.append('leave_type_id',this.leaveTypeId);
                         form.append('start_date',this.startDate);
                         form.append('end_date',this.endDate);
                         form.append('reason',this.reason);
                         const fi=document.getElementById('empReqLeaveDoc');
                         if(fi.files[0]) form.append('document',fi.files[0]);
                         form.append('_token',document.querySelector('meta[name=csrf-token]').content);
                         const res=await fetch('{{ route('payroll_officer.leave.file') }}',{method:'POST',body:form});
                         this.saving=false;
                         const data=await res.json();
                         if(res.ok){ $root.showFileReq=false; $root.reqType=''; $root.resultType='success'; $root.resultTitle='Leave Request Filed'; $root.resultMessage='Your leave request has been submitted successfully.'; $root.showResult=true; setTimeout(()=>window.location.reload(),2500); }
                         else{ $root.resultType='error'; $root.resultTitle='Submission Failed'; $root.resultMessage=data.message??'Something went wrong.'; $root.showResult=true; }
                     }
                 }">
                <div style="margin-bottom:16px;">
                    <label class="flabel">Leave Type</label>
                    <select class="finput" x-model="leaveTypeId" style="appearance:none;width:100%;">
                        <option value="">Choose leave type</option>
                        @foreach($leaveTypes as $lt)
                        <option value="{{ $lt->id }}">{{ $lt->name }} ({{ $lt->code }})</option>
                        @endforeach
                    </select>
                </div>
                <div class="frow" style="margin-bottom:16px;">
                    <div><label class="flabel">From</label><input type="date" class="finput" x-model="startDate"></div>
                    <div><label class="flabel">To</label><input type="date" class="finput" x-model="endDate"></div>
                </div>
                <div style="margin-bottom:16px;">
                    <label class="flabel">Reason/Remarks</label>
                    <input type="text" class="finput" x-model="reason" placeholder="Enter details">
                </div>
                <div style="margin-bottom:16px;">
                    <label class="flabel">Supporting Document (optional)</label>
                    <label class="doc-upload">
                        <svg style="width:28px;height:28px;color:#9ca3af;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 13h6m-3-3v6m5 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                        <div style="font-size:13px;font-weight:600;color:#374151;" x-text="fileName||'Choose a file to upload'"></div>
                        <div style="font-size:12px;color:#9ca3af;">PDF or DOCX, max 10MB</div>
                        <input id="empReqLeaveDoc" type="file" accept=".pdf,.docx" style="display:none;" @change="handleFile($event)">
                    </label>
                </div>
                <div class="mactions">
                    <button class="btn-cancel" @click="showFileReq=false;reqType=''">Cancel</button>
                    <button class="btn-save" @click="submit()" :disabled="saving"><span x-show="saving" style="display:inline-flex;align-items:center;gap:5px;"><svg style="width:13px;height:13px;animation:spin 0.8s linear infinite;flex-shrink:0;" viewBox="0 0 24 24" fill="none"><circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" style="opacity:.3"/><path fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg> Submitting…</span><span x-show="!saving">Submit</span></button>
                </div>
            </div>

            {{-- OVERTIME --}}
            <div x-show="reqType==='overtime'" x-transition
                 x-data="{ otDate:'', otStart:'', otEnd:'', reason:'', fileName:'', saving:false, errorMsg:'',
                     handleFile(e){ const f=e.target.files[0]; this.fileName=f?f.name:''; },
                     async submit(){
                         this.errorMsg='';
                         if(!this.otDate||!this.otStart||!this.otEnd||!this.reason){ $root.resultType='error'; $root.resultTitle='Missing Fields'; $root.resultMessage='Please fill in all required fields.'; $root.showResult=true; return; }
                         this.saving=true;
                         const form=new FormData();
                         form.append('ot_date',this.otDate);
                         form.append('ot_start_time',this.otStart);
                         form.append('ot_end_time',this.otEnd);
                         form.append('reason',this.reason);
                         const fi=document.getElementById('empReqOtDoc');
                         if(fi.files[0]) form.append('document',fi.files[0]);
                         form.append('_token',document.querySelector('meta[name=csrf-token]').content);
                         const res=await fetch('{{ route('payroll_officer.requests.overtime.file') }}',{method:'POST',body:form});
                         this.saving=false;
                         const data=await res.json();
                         if(res.ok){ $root.showFileReq=false; $root.reqType=''; $root.resultType='success'; $root.resultTitle='Overtime Request Filed'; $root.resultMessage='Your overtime request has been submitted successfully.'; $root.showResult=true; setTimeout(()=>window.location.reload(),2500); }
                         else{ $root.resultType='error'; $root.resultTitle='Submission Failed'; $root.resultMessage=data.message??'Something went wrong.'; $root.showResult=true; }
                     }
                 }">
                <div style="margin-bottom:16px;">
                    <label class="flabel">Date</label>
                    <input type="date" class="finput" x-model="otDate">
                </div>
                <div class="frow" style="margin-bottom:16px;">
                    <div><label class="flabel">OT Start</label><input type="time" class="finput" x-model="otStart"></div>
                    <div><label class="flabel">OT End</label><input type="time" class="finput" x-model="otEnd"></div>
                </div>
                <div style="margin-bottom:16px;">
                    <label class="flabel">Reason/Remarks</label>
                    <input type="text" class="finput" x-model="reason" placeholder="Enter details">
                </div>
                <div style="margin-bottom:16px;">
                    <label class="flabel">Supporting Document (optional)</label>
                    <label class="doc-upload">
                        <svg style="width:28px;height:28px;color:#9ca3af;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 13h6m-3-3v6m5 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                        <div style="font-size:13px;font-weight:600;color:#374151;" x-text="fileName||'Choose a file to upload'"></div>
                        <div style="font-size:12px;color:#9ca3af;">PDF or DOCX, max 10MB</div>
                        <input id="empReqOtDoc" type="file" accept=".pdf,.docx" style="display:none;" @change="handleFile($event)">
                    </label>
                </div>
                <div class="mactions">
                    <button class="btn-cancel" @click="showFileReq=false;reqType=''">Cancel</button>
                    <button class="btn-save" @click="submit()" :disabled="saving"><span x-show="saving" style="display:inline-flex;align-items:center;gap:5px;"><svg style="width:13px;height:13px;animation:spin 0.8s linear infinite;flex-shrink:0;" viewBox="0 0 24 24" fill="none"><circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" style="opacity:.3"/><path fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg> Submitting…</span><span x-show="!saving">Submit</span></button>
                </div>
            </div>

            {{-- SHIFT --}}
            <div x-show="reqType==='shift'" x-transition
                 x-data="{ requestedShiftId:'', effectiveFrom:'', effectiveUntil:'', reason:'', fileName:'', saving:false, errorMsg:'',
                     handleFile(e){ const f=e.target.files[0]; this.fileName=f?f.name:''; },
                     async submit(){
                         this.errorMsg='';
                         if(!this.requestedShiftId||!this.effectiveFrom||!this.reason){ $root.resultType='error'; $root.resultTitle='Missing Fields'; $root.resultMessage='Please fill in all required fields.'; $root.showResult=true; return; }
                         this.saving=true;
                         const form=new FormData();
                         form.append('requested_shift_id',this.requestedShiftId);
                         form.append('effective_from',this.effectiveFrom);
                         if(this.effectiveUntil) form.append('effective_until',this.effectiveUntil);
                         form.append('reason',this.reason);
                         const fi=document.getElementById('empReqShiftDoc');
                         if(fi.files[0]) form.append('document',fi.files[0]);
                         form.append('_token',document.querySelector('meta[name=csrf-token]').content);
                         const res=await fetch('{{ route('payroll_officer.requests.shift.file') }}',{method:'POST',body:form});
                         this.saving=false;
                         const data=await res.json();
                         if(res.ok){ $root.showFileReq=false; $root.reqType=''; $root.resultType='success'; $root.resultTitle='Shift Request Filed'; $root.resultMessage='Your shift change request has been submitted successfully.'; $root.showResult=true; setTimeout(()=>window.location.reload(),2500); }
                         else{ $root.resultType='error'; $root.resultTitle='Submission Failed'; $root.resultMessage=data.message??'Something went wrong.'; $root.showResult=true; }
                     }
                 }">
                <div style="margin-bottom:16px;">
                    <label class="flabel">Change Shift To</label>
                    <select class="finput" x-model="requestedShiftId" style="appearance:none;width:100%;">
                        <option value="">Choose shift type</option>
                        @foreach($shiftTypes as $shift)
                        <option value="{{ $shift->id }}">{{ $shift->name }} ({{ \Carbon\Carbon::parse($shift->start_time)->format('g:i A') }} – {{ \Carbon\Carbon::parse($shift->end_time)->format('g:i A') }})</option>
                        @endforeach
                    </select>
                </div>
                <div class="frow" style="margin-bottom:16px;">
                    <div><label class="flabel">Effective From</label><input type="date" class="finput" x-model="effectiveFrom"></div>
                    <div><label class="flabel">Effective Until (optional)</label><input type="date" class="finput" x-model="effectiveUntil"></div>
                </div>
                <div style="margin-bottom:16px;">
                    <label class="flabel">Reason/Remarks</label>
                    <input type="text" class="finput" x-model="reason" placeholder="Enter details">
                </div>
                <div style="margin-bottom:16px;">
                    <label class="flabel">Supporting Document (optional)</label>
                    <label class="doc-upload">
                        <svg style="width:28px;height:28px;color:#9ca3af;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 13h6m-3-3v6m5 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                        <div style="font-size:13px;font-weight:600;color:#374151;" x-text="fileName||'Choose a file to upload'"></div>
                        <div style="font-size:12px;color:#9ca3af;">PDF or DOCX, max 10MB</div>
                        <input id="empReqShiftDoc" type="file" accept=".pdf,.docx" style="display:none;" @change="handleFile($event)">
                    </label>
                </div>
                <div class="mactions">
                    <button class="btn-cancel" @click="showFileReq=false;reqType=''">Cancel</button>
                    <button class="btn-save" @click="submit()" :disabled="saving"><span x-show="saving" style="display:inline-flex;align-items:center;gap:5px;"><svg style="width:13px;height:13px;animation:spin 0.8s linear infinite;flex-shrink:0;" viewBox="0 0 24 24" fill="none"><circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" style="opacity:.3"/><path fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg> Submitting…</span><span x-show="!saving">Submit</span></button>
                </div>
            </div>

            {{-- ATTENDANCE ADJUSTMENT --}}
            <div x-show="reqType==='adjustment'" x-transition
                 x-data="{ adjDate:'', adjIn:'', adjOut:'', reason:'', fileName:'', saving:false,
                     handleFile(e){ const f=e.target.files[0]; this.fileName=f?f.name:''; },
                     async submit(){
                         if(!this.adjDate||!this.adjIn||!this.reason){ $root.resultType='error'; $root.resultTitle='Missing Fields'; $root.resultMessage='Please fill in all required fields.'; $root.showResult=true; return; }
                         this.saving=true;
                         const form=new FormData();
                         form.append('attendance_date',this.adjDate);
                         form.append('requested_clock_in',this.adjIn);
                         if(this.adjOut) form.append('requested_clock_out',this.adjOut);
                         form.append('reason',this.reason);
                         const fi=document.getElementById('payReqAdjDoc');
                         if(fi.files[0]) form.append('document',fi.files[0]);
                         form.append('_token',document.querySelector('meta[name=csrf-token]').content);
                         const res=await fetch('{{ route('payroll_officer.requests.adjustment.file') }}',{method:'POST',body:form});
                         this.saving=false;
                         const data=await res.json();
                         if(res.ok){ $root.showFileReq=false; $root.reqType=''; $root.resultType='success'; $root.resultTitle='Adjustment Request Filed'; $root.resultMessage='Ref: '+(data.ref_no??''); $root.showResult=true; setTimeout(()=>window.location.reload(),2500); }
                         else{ $root.resultType='error'; $root.resultTitle='Submission Failed'; $root.resultMessage=data.message??'Something went wrong.'; $root.showResult=true; }
                     }
                 }">
                <div style="margin-bottom:16px;"><label class="flabel">Attendance Date</label><input type="date" class="finput" x-model="adjDate" :max="new Date(Date.now()-86400000).toISOString().split('T')[0]"></div>
                <div class="frow" style="margin-bottom:16px;">
                    <div><label class="flabel">Correct Time In</label><input type="time" class="finput" x-model="adjIn"></div>
                    <div><label class="flabel">Correct Time Out <span style="font-weight:400;color:#9ca3af;">(optional)</span></label><input type="time" class="finput" x-model="adjOut"></div>
                </div>
                <div style="margin-bottom:16px;"><label class="flabel">Reason/Remarks</label><input type="text" class="finput" x-model="reason" placeholder="Why is this adjustment needed?"></div>
                <div style="margin-bottom:16px;">
                    <label class="flabel">Supporting Document (optional)</label>
                    <label class="doc-upload">
                        <svg style="width:28px;height:28px;color:#9ca3af;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 13h6m-3-3v6m5 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                        <div style="font-size:13px;font-weight:600;color:#374151;" x-text="fileName||'Choose a file to upload'"></div>
                        <div style="font-size:12px;color:#9ca3af;">PDF or DOCX, max 10MB</div>
                        <input id="payReqAdjDoc" type="file" accept=".pdf,.docx" style="display:none;" @change="handleFile($event)">
                    </label>
                </div>
                <div class="mactions">
                    <button class="btn-cancel" @click="showFileReq=false;reqType=''">Cancel</button>
                    <button class="btn-save" @click="submit()" :disabled="saving"><span x-show="saving" style="display:inline-flex;align-items:center;gap:5px;"><svg style="width:13px;height:13px;animation:spin 0.8s linear infinite;flex-shrink:0;" viewBox="0 0 24 24" fill="none"><circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" style="opacity:.3"/><path fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg> Submitting…</span><span x-show="!saving">Submit</span></button>
                </div>
            </div>

            <div x-show="reqType===''">
                <div class="mactions">
                    <button class="btn-cancel" @click="showFileReq=false;reqType=''">Cancel</button>
                </div>
            </div>
        </div>
    </div>

    {{-- ══ CANCEL CONFIRM MODAL ══ --}}
    <div x-show="showCancel" class="modal-overlay" x-cloak @click.self="showCancel=false">
        <div class="modal-box" style="width:420px;text-align:center;">
            <div style="width:56px;height:56px;background:#fee2e2;border-radius:50%;display:flex;align-items:center;justify-content:center;margin:0 auto 16px;">
                <svg style="width:26px;height:26px;color:#dc2626;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </div>
            <div style="font-size:17px;font-weight:800;color:#111827;margin-bottom:8px;">Cancel Request?</div>
            <div style="font-size:13px;color:#6b7280;margin-bottom:24px;">Are you sure you want to cancel this request? This action cannot be undone.</div>
            <div style="display:flex;justify-content:center;gap:12px;">
                <button class="btn-cancel" style="min-width:100px;" @click="showCancel=false">Back</button>
                <button style="min-width:100px;padding:8px 20px;border-radius:8px;font-size:13px;font-weight:600;font-family:inherit;cursor:pointer;border:none;background:#dc2626;color:#fff;"
                    @click="fetch(selCancelUrl,{method:'POST',headers:{'X-CSRF-TOKEN':document.querySelector('meta[name=csrf-token]').content,'Content-Type':'application/json','Accept':'application/json'},body:JSON.stringify({})}).then(async r=>{const d=await r.json();showCancel=false;if(r.ok){resultType='success';resultTitle='Request Cancelled';resultMessage=d.message??'Your request has been cancelled successfully.';showResult=true;setTimeout(()=>window.location.reload(),2500);}else{resultType='error';resultTitle='Cancellation Failed';resultMessage=d.message??'Something went wrong. Please try again.';showResult=true;}}).catch(()=>{showCancel=false;resultType='error';resultTitle='Error';resultMessage='An error occurred. Please try again.';showResult=true;})">
                    Yes, Cancel
                </button>
            </div>
        </div>
    </div>

    {{-- ══ RESULT MODAL ══ --}}
    <div x-show="showResult" class="modal-overlay" x-cloak @click.self="showResult=false">
        <div class="modal-box" style="width:400px;text-align:center;">
            <div :style="resultType==='success' ? 'width:64px;height:64px;background:#dcfce7;border-radius:50%;display:flex;align-items:center;justify-content:center;margin:0 auto 18px;' : 'width:64px;height:64px;background:#fee2e2;border-radius:50%;display:flex;align-items:center;justify-content:center;margin:0 auto 18px;'">
                <template x-if="resultType==='success'">
                    <svg style="width:30px;height:30px;color:#16a34a;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                </template>
                <template x-if="resultType==='error'">
                    <svg style="width:30px;height:30px;color:#dc2626;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </template>
            </div>
            <div style="font-size:18px;font-weight:800;color:#111827;margin-bottom:8px;" x-text="resultTitle"></div>
            <div style="font-size:13px;color:#6b7280;margin-bottom:24px;" x-text="resultMessage"></div>
            <button @click="showResult=false" style="padding:10px 32px;border-radius:9px;font-size:13px;font-weight:600;font-family:inherit;cursor:pointer;border:none;background:#111827;color:#fff;">OK</button>
        </div>
    </div>

</div>

<script>
    // Fix sidebar margin on load and resize
    (function() {
        var mainEl = document.querySelector('.main-content');
        if (!mainEl) return;
        function updateMargin() {
            var collapsed = localStorage.getItem('sidebarCollapsed') === 'true';
            if (window.innerWidth < 1024) {
                mainEl.style.marginLeft = '0';
            } else {
                mainEl.style.marginLeft = collapsed ? '5rem' : '16rem';
            }
        }
        updateMargin();
        window.addEventListener('resize', updateMargin);
        window.addEventListener('sidebar-toggle', function(e) {
            if (window.innerWidth >= 1024) {
                mainEl.style.marginLeft = e.detail.collapsed ? '5rem' : '16rem';
            }
        });
    })();
</script>
</body>
</html>
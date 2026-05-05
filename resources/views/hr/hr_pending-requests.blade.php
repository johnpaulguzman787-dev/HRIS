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

    $attendanceRoutes = ['hr.attendance.reports','hr.attendance.employee','hr.attendance.shift','hr.attendance.leave','hr.shift.scheduling','hr.leave.management'];
    $employeeRoutes   = ['hr.employees.directory','hr.employees.profile'];
    $payrollRoutes    = ['hr.payroll','hr.payslips','hr.contributions'];
    $requestRoutes    = ['hr.requests.pending','hr.requests.approved'];
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
        @keyframes pulseDot{0%,100%{opacity:1;transform:scale(1);}50%{opacity:.5;transform:scale(1.3);}}
        :root{--blue:#3b82f6;--blue-dark:#1d4ed8;--blue-light:#eff6ff;--muted:#6b7280;--border:#e5e7eb;}
        .nav-item{transition:background .15s,color .15s;}
        .chevron-icon{transition:transform .25s cubic-bezier(.4,0,.2,1);}
        .avatar-ring{box-shadow:0 0 0 3px rgba(59,130,246,.25);}

        /* stat cards - responsive grid */
        .stat-cards{display:grid;grid-template-columns:repeat(2,1fr);gap:12px;margin-bottom:24px;}
        @media (min-width: 640px) {
            .stat-cards{grid-template-columns:repeat(4,1fr);gap:16px;margin-bottom:28px;}
        }
        .stat-card{border-radius:14px;padding:16px;}
        @media (min-width: 640px) {
            .stat-card{padding:20px 22px;}
        }
        .sc-blue{background:#dbeafe;} .sc-orange{background:#ffedd5;} .sc-purple{background:#ede9fe;} .sc-red{background:#fee2e2;}
        .sc-blue .slabel{color:#1d4ed8;} .sc-orange .slabel{color:#c2410c;} .sc-purple .slabel{color:#6d28d9;} .sc-red .slabel{color:#dc2626;}
        .slabel{font-size:12px;font-weight:500;margin-bottom:4px;}
        @media (min-width: 640px) {
            .slabel{font-size:13px;margin-bottom:6px;}
        }
        .sval{font-size:28px;font-weight:800;color:#111827;line-height:1;margin-bottom:2px;}
        @media (min-width: 640px) {
            .sval{font-size:36px;margin-bottom:4px;}
        }
        .sc-blue .ssub{color:#3b82f6;} .sc-orange .ssub{color:#f97316;} .sc-purple .ssub{color:#7c3aed;} .sc-red .ssub{color:#ef4444;}
        .ssub{font-size:11px;}
        @media (min-width: 640px) {
            .ssub{font-size:12px;}
        }

        /* toolbar - responsive stacking */
        .toolbar{display:flex;flex-direction:column;align-items:stretch;margin-bottom:18px;gap:12px;}
        @media (min-width: 768px) {
            .toolbar{flex-direction:row;align-items:center;justify-content:space-between;}
        }
        .toolbar-title{font-size:16px;font-weight:700;color:#111827;}
        @media (min-width: 640px) {
            .toolbar-title{font-size:18px;}
        }
        .toolbar-right{display:flex;flex-direction:column;gap:8px;}
        @media (min-width: 640px) {
            .toolbar-right{flex-direction:row;align-items:center;gap:10px;flex-wrap:wrap;}
        }
        .search-box{display:flex;align-items:center;gap:8px;background:#fff;border:1px solid var(--border);border-radius:9px;padding:8px 14px;width:100%;}
        @media (min-width: 640px) {
            .search-box{width:auto;}
        }
        .search-box input{border:none;background:transparent;outline:none;font-size:13px;color:#111827;width:100%;font-family:inherit;}
        @media (min-width: 640px) {
            .search-box input{width:150px;}
        }
        .filters-row{display:flex;flex-wrap:nowrap;overflow-x:auto;gap:8px;-webkit-overflow-scrolling:touch;scrollbar-width:none;}
        .filters-row::-webkit-scrollbar{display:none;}
        .fsel{appearance:none;background:#f9fafb;border:1px solid var(--border);border-radius:8px;padding:8px 24px 8px 10px;font-size:12px;color:#374151;cursor:pointer;outline:none;font-family:inherit;white-space:nowrap;flex-shrink:0;background-image:url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='10' height='6' fill='none'%3E%3Cpath d='M1 1l4 4 4-4' stroke='%236b7280' stroke-width='1.5' stroke-linecap='round'/%3E%3C/svg%3E");background-repeat:no-repeat;background-position:right 8px center;}
        @media (min-width: 640px) {
            .fsel{font-size:13px;padding:8px 28px 8px 12px;}
        }
        .btn-primary{display:inline-flex;align-items:center;justify-content:center;gap:6px;padding:10px 16px;border-radius:9px;font-size:13px;font-weight:600;font-family:inherit;cursor:pointer;border:none;background:var(--blue);color:#fff;transition:background .15s;white-space:nowrap;width:100%;}
        @media (min-width: 640px) {
            .btn-primary{width:auto;padding:9px 20px;}
        }
        .btn-primary:hover{background:var(--blue-dark);}

        /* request card - responsive */
        .rcard{background:#fff;border:1px solid var(--border);border-radius:14px;margin-bottom:14px;overflow:hidden;box-shadow:0 1px 6px rgba(0,0,0,.04);}
        @media (min-width: 640px) {
            .rcard{margin-bottom:16px;}
        }
        .rcard-head{display:flex;flex-direction:column;align-items:flex-start;padding:14px;border-bottom:1px solid #f3f4f6;gap:10px;}
        @media (min-width: 640px) {
            .rcard-head{flex-direction:row;align-items:center;justify-content:space-between;padding:16px 20px 12px;gap:8px;}
        }
        .rcard-left{display:flex;align-items:center;gap:12px;}
        .emp-av{width:40px;height:40px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:14px;font-weight:700;color:#fff;flex-shrink:0;}
        .emp-name{font-size:14px;font-weight:700;color:#111827;}
        @media (min-width: 640px) {
            .emp-name{font-size:15px;}
        }
        .emp-dept{font-size:11px;color:var(--muted);margin-top:1px;}
        @media (min-width: 640px) {
            .emp-dept{font-size:12px;}
        }
        .rcard-right{display:flex;align-items:center;gap:6px;flex-wrap:wrap;}
        .req-id{font-size:11px;color:var(--muted);font-weight:500;}
        @media (min-width: 640px) {
            .req-id{font-size:12px;}
        }
        .badge{padding:2px 8px;border-radius:20px;font-size:10px;font-weight:600;display:inline-block;white-space:nowrap;}
        @media (min-width: 640px) {
            .badge{padding:3px 11px;font-size:11.5px;}
        }
        .b-leave{background:#dbeafe;color:#1d4ed8;}
        .b-ot{background:#ffedd5;color:#c2410c;}
        .b-shift{background:#fce7f3;color:#be185d;}
        .b-adjustment{background:#fef3c7;color:#92400e;}
        .b-await{background:#f0fdf4;color:#15803d;border:1px solid #bbf7d0;}

        /* card body */
        .rcard-body{padding:14px;}
        @media (min-width: 640px) {
            .rcard-body{padding:16px 20px;}
        }
        .dgrid{display:grid;grid-template-columns:repeat(2,1fr);gap:10px;background:#f9fafb;border-radius:10px;padding:12px;margin-bottom:12px;}
        @media (min-width: 640px) {
            .dgrid{grid-template-columns:repeat(4,1fr);gap:12px;padding:14px 16px;}
        }
        .dlabel{font-size:10px;color:var(--muted);font-weight:500;margin-bottom:2px;text-transform:uppercase;letter-spacing:.3px;}
        @media (min-width: 640px) {
            .dlabel{font-size:11px;margin-bottom:3px;}
        }
        .dval{font-size:12px;font-weight:700;color:#111827;}
        @media (min-width: 640px) {
            .dval{font-size:13px;}
        }
        .rsection{margin-bottom:8px;}
        .rlabel{font-size:11px;color:var(--muted);font-weight:500;margin-bottom:2px;}
        @media (min-width: 640px) {
            .rlabel{font-size:12px;margin-bottom:3px;}
        }
        .rtext{font-size:12px;color:#374151;}
        @media (min-width: 640px) {
            .rtext{font-size:13px;}
        }

        /* progress steps - responsive */
        .prog-steps{display:flex;align-items:flex-start;gap:0;margin-top:6px;flex-wrap:wrap;justify-content:center;}
        .pstep{display:flex;flex-direction:column;align-items:center;gap:3px;}
        .pcircle{width:26px;height:26px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:12px;flex-shrink:0;}
        @media (min-width: 640px) {
            .pcircle{width:30px;height:30px;font-size:13px;}
        }
        .pc-done{background:#dcfce7;border:2px solid #16a34a;color:#16a34a;}
        .pc-active{background:#eff6ff;border:2px solid var(--blue);color:var(--blue);}
        .pname{font-size:9px;font-weight:600;color:#374151;text-align:center;}
        @media (min-width: 640px) {
            .pname{font-size:11px;}
        }
        .pstatus{font-size:8px;color:var(--muted);text-align:center;}
        @media (min-width: 640px) {
            .pstatus{font-size:10px;}
        }
        .pline{height:2px;width:30px;background:#16a34a;flex-shrink:0;margin-top:12px;}
        @media (min-width: 640px) {
            .pline{width:60px;margin-top:14px;}
        }

        /* action buttons - responsive */
        .action-row{display:grid;grid-template-columns:1fr;gap:8px;padding:0 14px 14px;}
        @media (min-width: 640px) {
            .action-row{grid-template-columns:1fr 1fr;gap:10px;padding:0 20px 16px;}
        }
        .btn-approve,.btn-reject{display:flex;align-items:center;justify-content:center;gap:6px;padding:10px;border-radius:9px;font-size:13px;font-weight:600;font-family:inherit;cursor:pointer;border:none;transition:background .15s;}
        @media (min-width: 640px) {
            .btn-approve,.btn-reject{padding:10px;}
        }
        .btn-approve{background:#dcfce7;color:#15803d;}
        .btn-approve:hover{background:#bbf7d0;}
        .btn-reject{background:#fee2e2;color:#dc2626;}
        .btn-reject:hover{background:#fecaca;}

        /* modal - mobile first slide up */
        .modal-overlay{position:fixed;inset:0;background:rgba(0,0,0,.4);display:flex;align-items:flex-end;justify-content:center;z-index:999;padding:0;}
        @media (min-width: 640px) {
            .modal-overlay{align-items:center;padding:20px;}
        }
        .modal-box{background:#fff;border-radius:28px 28px 0 0;padding:24px 20px 32px;width:100%;max-width:100%;max-height:90vh;overflow-y:auto;box-shadow:0 -4px 20px rgba(0,0,0,.15);}
        @media (min-width: 640px) {
            .modal-box{border-radius:16px;padding:28px 32px;width:520px;max-width:95vw;}
        }
        .modal-box::before{content:'';display:block;width:40px;height:4px;background:#e5e7eb;border-radius:2px;margin:0 auto 20px;}
        @media (min-width: 640px) {
            .modal-box::before{display:none;}
        }
        .modal-title{font-size:17px;font-weight:800;color:#111827;margin-bottom:20px;}
        .flabel{font-size:12px;font-weight:600;color:#374151;text-transform:uppercase;letter-spacing:.5px;margin-bottom:5px;display:block;}
        .finput{width:100%;border:1.5px solid var(--border);border-radius:8px;padding:9px 13px;font-size:13px;font-family:inherit;outline:none;color:#111827;transition:border-color .15s;}
        .finput:focus{border-color:var(--blue);}
        .frow{display:grid;grid-template-columns:1fr;gap:14px;}
        @media (min-width: 640px) {
            .frow{grid-template-columns:1fr 1fr;}
        }
        .mactions{display:flex;flex-direction:column-reverse;gap:8px;margin-top:22px;}
        @media (min-width: 640px) {
            .mactions{flex-direction:row;justify-content:flex-end;gap:10px;}
        }
        .btn-cancel{padding:10px;border-radius:8px;font-size:13px;font-weight:600;font-family:inherit;cursor:pointer;border:1.5px solid var(--border);background:#fff;color:#374151;text-align:center;width:100%;}
        .btn-save{padding:10px;border-radius:8px;font-size:13px;font-weight:600;font-family:inherit;cursor:pointer;border:none;background:var(--blue);color:#fff;text-align:center;width:100%;}
        @media (min-width: 640px) {
            .btn-cancel,.btn-save{width:auto;padding:8px 18px;}
            .btn-save{padding:8px 20px;}
        }
        .close-btn{width:32px;height:32px;border:2px solid #374151;border-radius:50%;display:flex;align-items:center;justify-content:center;background:none;cursor:pointer;flex-shrink:0;}
        .fsel-modal{appearance:none;background-image:url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='10' height='6' fill='none'%3E%3Cpath d='M1 1l4 4 4-4' stroke='%236b7280' stroke-width='1.5' stroke-linecap='round'/%3E%3C/svg%3E");background-repeat:no-repeat;background-position:right 12px center;}
        .doc-upload{display:flex;flex-direction:column;align-items:center;justify-content:center;gap:8px;border:2px dashed #d1d5db;border-radius:10px;padding:20px 16px;background:#f9fafb;cursor:pointer;}
        @media (min-width: 640px) {
            .doc-upload{padding:24px 20px;}
        }
        @keyframes spin{from{transform:rotate(0deg);}to{transform:rotate(360deg);}}

        /* page content padding */
        .page-content{padding:16px;}
        @media (min-width: 640px) {
            .page-content{padding:24px 32px;}
        }

        /* sidebar responsive */
        .desktop-sidebar{display:none;}
        @media (min-width: 1024px){
            .desktop-sidebar{display:block;}
        }
        @media (max-width: 1023px){
            .main-content{margin-left:0!important;}
        }
    </style>
</head>
<body class="bg-gray-50" x-data="{ mobileMenuOpen: false, collapsed: localStorage.getItem('sidebarCollapsed') === 'true' }"
     x-init="window.addEventListener('sidebar-toggle', e => { collapsed = e.detail.collapsed })">

{{-- ══════════ SIDEBAR ══════════ --}}

{{-- DESKTOP SIDEBAR --}}
<div class="hidden lg:block desktop-sidebar">
    @include('hr.hr_sidebar')
</div>

{{-- MOBILE SLIDE-OUT DRAWER --}}
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
        @include('hr.hr_sidebar')
    </div>
</div>

{{-- ══════════ MAIN ══════════ --}}
<div x-data="{
        collapsed:localStorage.getItem('sidebarCollapsed')==='true',
        showFileReq:false,
        reqType:'',
        showApprove:false,
        showReject:false,
        selName:'',
        selId:null,
        rejectReason:'',
        selApproveUrl:'',
        selRejectUrl:'',
        showResult:false,
        resultType:'success',
        resultTitle:'',
        resultMessage:''
     }"
     x-init="window.addEventListener('sidebar-toggle',e=>{collapsed=e.detail.collapsed})"
     :style="window.innerWidth >= 1024 ? (collapsed ? 'margin-left:5rem' : 'margin-left:16rem') : 'margin-left:0'"
     x-on:resize.window="$el.style.marginLeft = window.innerWidth >= 1024 ? (collapsed ? '5rem' : '16rem') : '0'"
     style="transition:margin-left .35s cubic-bezier(.4,0,.2,1);min-height:100vh;"
     class="main-content">

    <header class="bg-gradient-to-br from-blue-500 to-blue-700 sticky top-0 z-10 shadow-lg mt-4 mx-4 rounded-2xl overflow-visible">
        <div class="flex items-center justify-between px-6 py-4">
            <div class="flex items-center gap-3">
                <button @click="mobileMenuOpen = true" class="lg:hidden p-1.5 rounded-lg hover:bg-white/20 transition-colors text-white">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M4 6h16M4 12h16M4 18h16"/>
                    </svg>
                </button>
                <h1 class="text-white font-bold text-lg">Pending Requests</h1>
            </div>
            <x-hr-notif />
        </div>
    </header>

    <div class="page-content">

        {{-- STAT CARDS --}}
        <div class="stat-cards">
            <div class="stat-card sc-blue">
                <div class="slabel">Awaiting My Approval</div>
                <div class="sval">{{ $awaitingCount ?? 3 }}</div>
                <div class="ssub">Waiting for action</div>
            </div>
            <div class="stat-card sc-orange">
                <div class="slabel">Leave Requests</div>
                <div class="sval">{{ $leaveCount ?? 1 }}</div>
                <div class="ssub">{{ now()->format('F Y') }}</div>
            </div>
            <div class="stat-card sc-purple">
                <div class="slabel">Shift Arrangement</div>
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
                <div class="filters-row">
                    <select class="fsel"><option>All Stages</option><option>Awaiting Approval</option><option>Approved</option><option>Rejected</option></select>
                    <select class="fsel"><option>All Types</option><option>Leave Request</option><option>Overtime Request</option><option>Shift Arrangement</option></select>
                    <select class="fsel"><option>All Departments</option>@foreach($departments ?? [] as $d)<option>{{ $d->name }}</option>@endforeach</select>
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
        @php
            $empName  = trim(($req->employee?->fname ?? '') . ' ' . ($req->employee?->lname ?? ''));
            $initials = strtoupper(substr($req->employee?->fname ?? 'U', 0, 1) . substr($req->employee?->lname ?? 'K', 0, 1));
            $approveUrl = match($req->type) {
                'overtime'   => '/hr/requests/overtime/'   . $req->id . '/approve',
                'shift'      => '/hr/requests/shift/'      . $req->id . '/approve',
                'adjustment' => '/hr/requests/adjustment/' . $req->id . '/approve',
                default      => '/hr/requests/'            . $req->id . '/approve',
            };
            $rejectUrl = match($req->type) {
                'overtime'   => '/hr/requests/overtime/'   . $req->id . '/reject',
                'shift'      => '/hr/requests/shift/'      . $req->id . '/reject',
                'adjustment' => '/hr/requests/adjustment/' . $req->id . '/reject',
                default      => '/hr/requests/'            . $req->id . '/reject',
            };
        @endphp
        <div class="rcard">
            <div class="rcard-head">
                <div class="rcard-left">
                    <div class="emp-av" style="background:{{ $req->type === 'overtime' ? '#ef4444' : ($req->type === 'shift' ? '#8b5cf6' : '#3b82f6') }};">{{ $initials }}</div>
                    <div>
                        <div class="emp-name">{{ $empName }}</div>
                        <div class="emp-dept">{{ $req->employee?->department?->name ?? '—' }} · {{ $req->employee?->jobTitle?->title ?? '—' }}</div>
                    </div>
                </div>
                <div class="rcard-right">
                    <span class="req-id">{{ $req->ref_no }}</span>
                    @if($req->type === 'leave')        <span class="badge b-leave">Leave Request</span>
                    @elseif($req->type === 'overtime') <span class="badge b-ot">Overtime Request</span>
                    @elseif($req->type === 'shift')    <span class="badge b-shift">Shift Arrangement</span>
                    @elseif($req->type === 'adjustment') <span class="badge b-adjustment">Attendance Adjustment</span>
                    @endif
                    <span class="badge b-await">Awaiting Your Approval</span>
                </div>
            </div>
            <div class="rcard-body">
                @if($req->type === 'leave')
                <div class="dgrid">
                    <div><div class="dlabel">Leave Type</div><div class="dval">{{ $req->leaveType->name ?? '—' }}</div></div>
                    <div><div class="dlabel">Date</div><div class="dval">{{ \Carbon\Carbon::parse($req->start_date)->format('M j') }} – {{ \Carbon\Carbon::parse($req->end_date)->format('j, Y') }}</div></div>
                    <div><div class="dlabel">Filed On</div><div class="dval">{{ $req->created_at->format('M j, Y') }}</div></div>
                    <div><div class="dlabel">Balance</div><div class="dval">{{ $req->credit ? $req->credit->remaining_days . ' days' : '—' }}</div></div>
                </div>
                @elseif($req->type === 'overtime')
                <div class="dgrid">
                    <div><div class="dlabel">OT Date</div><div class="dval">{{ \Carbon\Carbon::parse($req->ot_date)->format('M j, Y') }}</div></div>
                    <div><div class="dlabel">OT Hours</div><div class="dval">{{ $req->requested_hours }}h</div></div>
                    <div><div class="dlabel">Time Range</div><div class="dval">{{ $req->ot_start_time ? \Carbon\Carbon::parse($req->ot_start_time)->format('g:i A') . ' – ' . \Carbon\Carbon::parse($req->ot_end_time)->format('g:i A') : '—' }}</div></div>
                    <div><div class="dlabel">Filed On</div><div class="dval">{{ $req->created_at->format('M j, Y') }}</div></div>
                </div>
                @elseif($req->type === 'shift')
                <div class="dgrid">
                    <div><div class="dlabel">Current Shift</div><div class="dval">{{ $req->current_shift->name ?? '—' }}</div></div>
                    <div><div class="dlabel">Requested Shift</div><div class="dval">{{ $req->requested_shift->name ?? '—' }}</div></div>
                    <div><div class="dlabel">Effective From</div><div class="dval">{{ \Carbon\Carbon::parse($req->effective_from)->format('M j, Y') }}</div></div>
                    <div><div class="dlabel">Until</div><div class="dval">{{ $req->effective_until ? \Carbon\Carbon::parse($req->effective_until)->format('M j, Y') : 'Ongoing' }}</div></div>
                </div>
                @elseif($req->type === 'adjustment')
                <div class="dgrid">
                    <div><div class="dlabel">Date</div><div class="dval">{{ \Carbon\Carbon::parse($req->attendance_date)->format('M j, Y') }}</div></div>
                    <div><div class="dlabel">Original Time</div><div class="dval">{{ $req->original_clock_in ? \Carbon\Carbon::parse($req->original_clock_in)->format('g:i A') . ' – ' . ($req->original_clock_out ? \Carbon\Carbon::parse($req->original_clock_out)->format('g:i A') : 'N/A') : '—' }}</div></div>
                    <div><div class="dlabel">Requested Time</div><div class="dval">{{ \Carbon\Carbon::parse($req->requested_clock_in)->format('g:i A') }} – {{ $req->requested_clock_out ? \Carbon\Carbon::parse($req->requested_clock_out)->format('g:i A') : 'N/A' }}</div></div>
                    <div><div class="dlabel">Filed On</div><div class="dval">{{ $req->created_at->format('M j, Y') }}</div></div>
                </div>
                @if(!$req->original_clock_in)
                <div style="margin-top:8px;padding:8px 12px;background:#fef9c3;border:1px solid #fde68a;border-radius:6px;display:flex;align-items:center;gap:8px;font-size:12px;color:#92400e;">
                    <svg style="width:14px;height:14px;flex-shrink:0;" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/></svg>
                    No existing attendance record for this date. Approving will create a new entry.
                </div>
                @endif
                @endif
                <div class="rsection"><div class="rlabel">Reason</div><div class="rtext">{{ $req->reason ?? '—' }}</div></div>
                @if($req->document_path)
                <div style="margin-bottom:8px;"><div class="rlabel">Documents</div><div style="font-size:12px;color:#3b82f6;font-weight:500;">{{ basename($req->document_path) }}</div></div>
                @endif
                <div><div class="rlabel">Approval Progress</div>
                <div class="prog-steps">
                    <div class="pstep"><div class="pcircle pc-done"><svg style="width:12px;height:12px;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg></div><div class="pname">Employee</div><div class="pstatus">Filed</div></div>
                    @if(isset($req->employee->department_id))
                    <div class="pline"></div>
                    @if($req->status === 'supervisor_approved')
                    <div class="pstep"><div class="pcircle pc-done"><svg style="width:12px;height:12px;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg></div><div class="pname">Supervisor</div><div class="pstatus">Approved</div></div>
                    @else
                    <div class="pstep"><div class="pcircle" style="background:#f9fafb;border:2px solid #d1d5db;color:#9ca3af;"><svg style="width:12px;height:12px;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 12h.01M12 12h.01M19 12h.01"/></svg></div><div class="pname">Supervisor</div><div class="pstatus">Bypassed</div></div>
                    @endif
                    @endif
                    <div class="pline"></div>
                    <div class="pstep"><div class="pcircle pc-active"><svg style="width:12px;height:12px;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 12h.01M12 12h.01M19 12h.01"/></svg></div><div class="pname">HR Manager</div><div class="pstatus">Pending</div></div>
                </div></div>
            </div>
            @canDo('Requests & Approval', 'edit')
            <div class="action-row">
                <button class="btn-approve" @click="selId={{ $req->id }};selName='{{ addslashes($empName) }}';selApproveUrl='{{ $approveUrl }}';showApprove=true">
                    <svg style="width:14px;height:14px;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg> Approve
                </button>
                <button class="btn-reject" @click="selId={{ $req->id }};selName='{{ addslashes($empName) }}';selRejectUrl='{{ $rejectUrl }}';showReject=true">
                    <svg style="width:14px;height:14px;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg> Reject
                </button>
            </div>
            @endcanDo
        </div>
        @empty
        <div style="text-align:center;padding:40px 16px;">
            <div style="font-size:14px;font-weight:600;color:#9ca3af;">No pending requests at this time.</div>
            <div style="font-size:12px;color:#d1d5db;margin-top:4px;">All leave requests will appear here when submitted.</div>
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
                         const fi=document.getElementById('hrLeaveDocInput');
                         if(fi.files[0]) form.append('document',fi.files[0]);
                         form.append('_token',document.querySelector('meta[name=csrf-token]').content);
                         const res=await fetch('{{ route('hr.leave.file') }}',{method:'POST',body:form});
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
                        <input id="hrLeaveDocInput" type="file" accept=".pdf,.docx" style="display:none;" @change="handleFile($event)">
                    </label>
                </div>
                <div class="mactions">
                    <button class="btn-cancel" @click="$root.showFileReq=false;$root.reqType=''">Cancel</button>
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
                         const fi=document.getElementById('hrOtDocInput');
                         if(fi.files[0]) form.append('document',fi.files[0]);
                         form.append('_token',document.querySelector('meta[name=csrf-token]').content);
                         const res=await fetch('{{ route('hr.requests.overtime.file') }}',{method:'POST',body:form});
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
                        <input id="hrOtDocInput" type="file" accept=".pdf,.docx" style="display:none;" @change="handleFile($event)">
                    </label>
                </div>
                <div class="mactions">
                    <button class="btn-cancel" @click="$root.showFileReq=false;$root.reqType=''">Cancel</button>
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
                         const fi=document.getElementById('hrShiftDocInput');
                         if(fi.files[0]) form.append('document',fi.files[0]);
                         form.append('_token',document.querySelector('meta[name=csrf-token]').content);
                         const res=await fetch('{{ route('hr.requests.shift.file') }}',{method:'POST',body:form});
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
                        <input id="hrShiftDocInput" type="file" accept=".pdf,.docx" style="display:none;" @change="handleFile($event)">
                    </label>
                </div>
                <div class="mactions">
                    <button class="btn-cancel" @click="$root.showFileReq=false;$root.reqType=''">Cancel</button>
                    <button class="btn-save" @click="submit()" :disabled="saving"><span x-show="saving" style="display:inline-flex;align-items:center;gap:5px;"><svg style="width:13px;height:13px;animation:spin 0.8s linear infinite;flex-shrink:0;" viewBox="0 0 24 24" fill="none"><circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" style="opacity:.3"/><path fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg> Submitting…</span><span x-show="!saving">Submit</span></button>
                </div>
            </div>

            {{-- ADJUSTMENT --}}
            <div x-show="reqType==='adjustment'" x-transition
                 x-data="{ adjDate:'', adjIn:'', adjOut:'', reason:'', fileName:'', saving:false,
                     handleFile(e){ const f=e.target.files[0]; this.fileName=f?f.name:''; },
                     async submit(){
                         if(!this.adjDate||!this.adjIn||!this.reason){ $root.resultType='error'; $root.resultTitle='Missing Fields'; $root.resultMessage='Date, clock-in time, and reason are required.'; $root.showResult=true; return; }
                         this.saving=true;
                         const form=new FormData();
                         form.append('attendance_date',this.adjDate);
                         form.append('requested_clock_in',this.adjIn);
                         if(this.adjOut) form.append('requested_clock_out',this.adjOut);
                         form.append('reason',this.reason);
                         const fi=document.getElementById('hrAdjDocInput');
                         if(fi&&fi.files[0]) form.append('document',fi.files[0]);
                         form.append('_token',document.querySelector('meta[name=csrf-token]').content);
                         const res=await fetch('{{ route('hr.requests.adjustment.file') }}',{method:'POST',body:form});
                         this.saving=false;
                         const data=await res.json();
                         if(res.ok){ $root.showFileReq=false; $root.reqType=''; $root.resultType='success'; $root.resultTitle='Request Filed'; $root.resultMessage='Your attendance adjustment request has been submitted.'; $root.showResult=true; setTimeout(()=>window.location.reload(),2500); }
                         else{ $root.resultType='error'; $root.resultTitle='Submission Failed'; $root.resultMessage=data.message??'Something went wrong.'; $root.showResult=true; }
                     }
                 }">
                <div style="margin-bottom:16px;">
                    <label class="flabel">Attendance Date</label>
                    <input type="date" class="finput" x-model="adjDate">
                </div>
                <div class="frow" style="margin-bottom:16px;">
                    <div><label class="flabel">Clock-In Time</label><input type="time" class="finput" x-model="adjIn"></div>
                    <div><label class="flabel">Clock-Out Time (optional)</label><input type="time" class="finput" x-model="adjOut"></div>
                </div>
                <div style="margin-bottom:16px;">
                    <label class="flabel">Reason / Remarks</label>
                    <input type="text" class="finput" x-model="reason" placeholder="Why is the adjustment needed?">
                </div>
                <div style="margin-bottom:16px;">
                    <label class="flabel">Supporting Document (optional)</label>
                    <label class="doc-upload">
                        <svg style="width:28px;height:28px;color:#9ca3af;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 13h6m-3-3v6m5 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                        <div style="font-size:13px;font-weight:600;color:#374151;" x-text="fileName||'Choose a file to upload'"></div>
                        <div style="font-size:12px;color:#9ca3af;">PDF or DOCX, max 10MB</div>
                        <input id="hrAdjDocInput" type="file" accept=".pdf,.docx" style="display:none;" @change="handleFile($event)">
                    </label>
                </div>
                <div class="mactions">
                    <button class="btn-cancel" @click="$root.showFileReq=false;$root.reqType=''">Cancel</button>
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

    {{-- ══ APPROVE CONFIRM ══ --}}
    <div x-show="showApprove" class="modal-overlay" x-cloak @click.self="showApprove=false">
        <div class="modal-box" style="width:400px;text-align:center;">
            <div style="width:56px;height:56px;background:#dcfce7;border-radius:50%;display:flex;align-items:center;justify-content:center;margin:0 auto 16px;">
                <svg style="width:26px;height:26px;color:#16a34a;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
            </div>
            <div style="font-size:17px;font-weight:800;color:#111827;margin-bottom:8px;">Approve Request?</div>
            <div style="font-size:13px;color:#6b7280;margin-bottom:24px;">Are you sure you want to approve the request from <strong x-text="selName"></strong>? This action cannot be undone.</div>
            <div style="display:flex;justify-content:center;gap:12px;">
                <button class="btn-cancel" style="min-width:100px;" @click="showApprove=false">Cancel</button>
                <button style="min-width:100px;padding:8px 20px;border-radius:8px;font-size:13px;font-weight:600;font-family:inherit;cursor:pointer;border:none;background:#16a34a;color:#fff;" @click="fetch(selApproveUrl,{method:'POST',headers:{'X-CSRF-TOKEN':document.querySelector('meta[name=csrf-token]').content,'Content-Type':'application/json','Accept':'application/json'},body:JSON.stringify({})}).then(async r=>{const d=await r.json();showApprove=false;if(r.ok){resultType='success';resultTitle='Request Approved';resultMessage=d.message??'The request has been approved successfully.';showResult=true;setTimeout(()=>window.location.reload(),2500);}else{resultType='error';resultTitle='Approval Failed';resultMessage=d.message??'Something went wrong. Please try again.';showResult=true;}}).catch(()=>{showApprove=false;resultType='error';resultTitle='Error';resultMessage='An error occurred. Please try again.';showResult=true;})">Approve</button>
            </div>
        </div>
    </div>

    {{-- ══ REJECT CONFIRM ══ --}}
    <div x-show="showReject" class="modal-overlay" x-cloak @click.self="showReject=false">
        <div class="modal-box" style="width:420px;text-align:center;">
            <div style="width:56px;height:56px;background:#fee2e2;border-radius:50%;display:flex;align-items:center;justify-content:center;margin:0 auto 16px;">
                <svg style="width:26px;height:26px;color:#dc2626;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </div>
            <div style="font-size:17px;font-weight:800;color:#111827;margin-bottom:8px;">Reject Request?</div>
            <div style="font-size:13px;color:#6b7280;margin-bottom:14px;">Please provide a reason for rejecting <strong x-text="selName"></strong>'s request.</div>
            <textarea class="finput" rows="3" placeholder="Enter rejection reason..." style="resize:none;text-align:left;" x-model="rejectReason"></textarea>
            <div style="display:flex;justify-content:center;gap:12px;margin-top:16px;">
                <button class="btn-cancel" style="min-width:100px;" @click="showReject=false">Cancel</button>
                <button style="min-width:100px;padding:8px 20px;border-radius:8px;font-size:13px;font-weight:600;font-family:inherit;cursor:pointer;border:none;background:#dc2626;color:#fff;" @click="fetch(selRejectUrl,{method:'POST',headers:{'X-CSRF-TOKEN':document.querySelector('meta[name=csrf-token]').content,'Content-Type':'application/json','Accept':'application/json'},body:JSON.stringify({reason:rejectReason,rejection_reason:rejectReason})}).then(async r=>{const d=await r.json();showReject=false;rejectReason='';if(r.ok){resultType='success';resultTitle='Request Rejected';resultMessage=d.message??'The request has been rejected successfully.';showResult=true;setTimeout(()=>window.location.reload(),2500);}else{resultType='error';resultTitle='Rejection Failed';resultMessage=d.message??'Something went wrong. Please try again.';showResult=true;}}).catch(()=>{showReject=false;resultType='error';resultTitle='Error';resultMessage='An error occurred. Please try again.';showResult=true;})">Reject</button>
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
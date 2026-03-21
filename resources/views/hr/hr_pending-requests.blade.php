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
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
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

        /* stat cards */
        .stat-cards{display:grid;grid-template-columns:repeat(4,1fr);gap:16px;margin-bottom:28px;}
        .stat-card{border-radius:14px;padding:20px 22px;}
        .sc-blue{background:#dbeafe;} .sc-orange{background:#ffedd5;} .sc-purple{background:#ede9fe;} .sc-red{background:#fee2e2;}
        .sc-blue .slabel{color:#1d4ed8;} .sc-orange .slabel{color:#c2410c;} .sc-purple .slabel{color:#6d28d9;} .sc-red .slabel{color:#dc2626;}
        .slabel{font-size:13px;font-weight:500;margin-bottom:6px;}
        .sval{font-size:36px;font-weight:800;color:#111827;line-height:1;margin-bottom:4px;}
        .sc-blue .ssub{color:#3b82f6;} .sc-orange .ssub{color:#f97316;} .sc-purple .ssub{color:#7c3aed;} .sc-red .ssub{color:#ef4444;}
        .ssub{font-size:12px;}

        /* toolbar */
        .toolbar{display:flex;align-items:center;justify-content:space-between;margin-bottom:18px;gap:12px;flex-wrap:wrap;}
        .toolbar-title{font-size:18px;font-weight:700;color:#111827;}
        .toolbar-right{display:flex;align-items:center;gap:10px;flex-wrap:wrap;}
        .search-box{display:flex;align-items:center;gap:8px;background:#fff;border:1px solid var(--border);border-radius:9px;padding:8px 14px;}
        .search-box input{border:none;background:transparent;outline:none;font-size:13px;color:#111827;width:150px;font-family:inherit;}
        .fsel{appearance:none;background:#f9fafb;border:1px solid var(--border);border-radius:8px;padding:8px 28px 8px 12px;font-size:13px;color:#374151;cursor:pointer;outline:none;font-family:inherit;background-image:url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='10' height='6' fill='none'%3E%3Cpath d='M1 1l4 4 4-4' stroke='%236b7280' stroke-width='1.5' stroke-linecap='round'/%3E%3C/svg%3E");background-repeat:no-repeat;background-position:right 10px center;}
        .btn-primary{display:inline-flex;align-items:center;gap:6px;padding:9px 20px;border-radius:9px;font-size:13px;font-weight:600;font-family:inherit;cursor:pointer;border:none;background:var(--blue);color:#fff;transition:background .15s;white-space:nowrap;}
        .btn-primary:hover{background:var(--blue-dark);}

        /* request card */
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

        /* card body */
        .rcard-body{padding:16px 20px;}
        .dgrid{display:grid;grid-template-columns:repeat(4,1fr);gap:12px;background:#f9fafb;border-radius:10px;padding:14px 16px;margin-bottom:12px;}
        .dlabel{font-size:11px;color:var(--muted);font-weight:500;margin-bottom:3px;text-transform:uppercase;letter-spacing:.3px;}
        .dval{font-size:13px;font-weight:700;color:#111827;}
        .rsection{margin-bottom:10px;}
        .rlabel{font-size:12px;color:var(--muted);font-weight:500;margin-bottom:3px;}
        .rtext{font-size:13px;color:#374151;}

        /* progress */
        .prog-steps{display:flex;align-items:flex-start;gap:0;margin-top:6px;}
        .pstep{display:flex;flex-direction:column;align-items:center;gap:4px;}
        .pcircle{width:30px;height:30px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:13px;flex-shrink:0;}
        .pc-done{background:#dcfce7;border:2px solid #16a34a;color:#16a34a;}
        .pc-active{background:#eff6ff;border:2px solid var(--blue);color:var(--blue);}
        .pname{font-size:11px;font-weight:600;color:#374151;text-align:center;}
        .pstatus{font-size:10px;color:var(--muted);text-align:center;}
        .pline{height:2px;width:60px;background:#16a34a;flex-shrink:0;margin-top:14px;}

        /* actions */
        .action-row{display:grid;grid-template-columns:1fr 1fr;gap:10px;padding:0 20px 16px;}
        .btn-approve{display:flex;align-items:center;justify-content:center;gap:6px;padding:10px;border-radius:9px;font-size:13px;font-weight:600;font-family:inherit;cursor:pointer;border:none;background:#dcfce7;color:#15803d;transition:background .15s;}
        .btn-approve:hover{background:#bbf7d0;}
        .btn-reject{display:flex;align-items:center;justify-content:center;gap:6px;padding:10px;border-radius:9px;font-size:13px;font-weight:600;font-family:inherit;cursor:pointer;border:none;background:#fee2e2;color:#dc2626;transition:background .15s;}
        .btn-reject:hover{background:#fecaca;}

        /* modal */
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
    </style>
</head>
<body class="bg-gray-50">

{{-- ══════════ SIDEBAR ══════════ --}}
<aside class="bg-white border-r border-gray-200 h-screen fixed left-0 top-0 overflow-y-auto z-50 flex flex-col"
    x-data="{
        sidebarCollapsed:localStorage.getItem('sidebarCollapsed')==='true',
        employeesOpen:{{ in_array($currentRoute,$employeeRoutes)?'true':'false' }},
        attendanceOpen:{{ in_array($currentRoute,$attendanceRoutes)?'true':'false' }},
        payrollOpen:{{ in_array($currentRoute,$payrollRoutes)?'true':'false' }},
        requestsOpen:{{ in_array($currentRoute,$requestRoutes)?'true':'false' }}
    }"
    x-init="$watch('sidebarCollapsed',v=>localStorage.setItem('sidebarCollapsed',v))"
    :class="sidebarCollapsed?'w-20':'w-64'"
    style="transition:width .35s cubic-bezier(.4,0,.2,1);box-shadow:2px 0 20px rgba(0,0,0,.06);">

    <div class="px-6 py-5 border-b border-gray-100">
        <div class="flex items-center space-x-3" :class="sidebarCollapsed?'justify-center':''">
            <div class="w-9 h-9 border-2 border-gray-800 flex items-center justify-center flex-shrink-0" style="border-radius:6px;">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
            </div>
            <h1 x-show="!sidebarCollapsed" x-transition:enter="transition ease-out duration-300 delay-100" x-transition:enter-start="opacity-0 -translate-x-4" x-transition:enter-end="opacity-100 translate-x-0" x-transition:leave="transition ease-in duration-100" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0" class="font-bold text-gray-900 text-lg tracking-widest whitespace-nowrap">MEDISOURCE</h1>
        </div>
    </div>

    <div class="px-4 py-4 border-b border-gray-100" :class="sidebarCollapsed?'flex justify-center':'flex items-center space-x-3'">
        <div class="w-11 h-11 rounded-full flex items-center justify-center text-white font-bold flex-shrink-0 text-sm avatar-ring" style="background:linear-gradient(135deg,#3b82f6 0%,#1d4ed8 100%);">{{ $sidebarInitials }}</div>
        <div x-show="!sidebarCollapsed" x-transition:enter="transition ease-out duration-300 delay-100" x-transition:enter-start="opacity-0 -translate-x-3" x-transition:enter-end="opacity-100 translate-x-0" x-transition:leave="transition ease-in duration-100" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0" class="overflow-hidden">
            <p class="font-semibold text-gray-800 text-sm leading-tight">{{ $sidebarName }}</p>
            <p class="text-xs mt-0.5 font-semibold" style="color:#3b82f6;">{{ $sidebarRole }}</p>
        </div>
    </div>

    <nav class="p-3 space-y-0.5 flex-1 overflow-y-auto">
        <p x-show="!sidebarCollapsed" class="text-xs text-gray-400 font-semibold px-3 py-2 uppercase tracking-widest">Main Menu</p>

        <a href="{{ route('hr.dashboard') }}" class="nav-item flex items-center space-x-3 px-3 py-2.5 rounded-lg {{ $currentRoute==='hr.dashboard'?'text-white':'text-gray-600 hover:bg-gray-50' }}" style="{{ $currentRoute==='hr.dashboard'?'background:#3b82f6;':'' }}">
            <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"/></svg>
            <span x-show="!sidebarCollapsed" class="text-sm font-medium whitespace-nowrap">Dashboard</span>
        </a>

        <div>
            <button @click="employeesOpen=!employeesOpen" class="nav-item w-full flex items-center justify-between px-3 py-2.5 rounded-lg {{ in_array($currentRoute,$employeeRoutes)?'text-blue-600':'text-gray-600 hover:bg-gray-50' }}">
                <div class="flex items-center space-x-3">
                    <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
                    <span x-show="!sidebarCollapsed" class="text-sm font-medium whitespace-nowrap">Employees</span>
                </div>
                <svg x-show="!sidebarCollapsed" class="w-4 h-4 chevron-icon" :class="{'rotate-180':employeesOpen}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
            </button>
            <div x-show="employeesOpen&&!sidebarCollapsed" x-transition:enter="transition ease-out duration-250" x-transition:enter-start="opacity-0 -translate-y-3 scale-y-95" x-transition:enter-end="opacity-100 translate-y-0 scale-y-100" x-transition:leave="transition ease-in duration-200" x-transition:leave-start="opacity-100 translate-y-0 scale-y-100" x-transition:leave-end="opacity-0 -translate-y-3 scale-y-95" class="ml-8 mt-1 space-y-0.5 origin-top">
                <a href="{{ route('hr.employees.directory') }}" class="block px-3 py-2 text-sm rounded-lg {{ $currentRoute==='hr.employees.directory'?'text-blue-600 font-semibold bg-blue-50':'text-gray-500 hover:text-gray-800 hover:bg-gray-50' }}">Employee Directory</a>
                <a href="{{ route('hr.employees.profile') }}"   class="block px-3 py-2 text-sm rounded-lg {{ $currentRoute==='hr.employees.profile'?'text-blue-600 font-semibold bg-blue-50':'text-gray-500 hover:text-gray-800 hover:bg-gray-50' }}">Employee Profile</a>
            </div>
        </div>

        <div>
            <button @click="attendanceOpen=!attendanceOpen" class="nav-item w-full flex items-center justify-between px-3 py-2.5 rounded-lg {{ in_array($currentRoute,$attendanceRoutes)?'text-blue-600':'text-gray-600 hover:bg-gray-50' }}">
                <div class="flex items-center space-x-3">
                    <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    <span x-show="!sidebarCollapsed" class="text-sm font-medium whitespace-nowrap">Time & Attendance</span>
                </div>
                <svg x-show="!sidebarCollapsed" class="w-4 h-4 chevron-icon" :class="{'rotate-180':attendanceOpen}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
            </button>
            <div x-show="attendanceOpen&&!sidebarCollapsed" x-transition:enter="transition ease-out duration-250" x-transition:enter-start="opacity-0 -translate-y-3 scale-y-95" x-transition:enter-end="opacity-100 translate-y-0 scale-y-100" x-transition:leave="transition ease-in duration-200" x-transition:leave-start="opacity-100 translate-y-0 scale-y-100" x-transition:leave-end="opacity-0 -translate-y-3 scale-y-95" class="ml-8 mt-1 space-y-0.5 origin-top">
                <a href="{{ route('hr.attendance.reports') }}"  class="flex items-center px-3 py-2 text-sm rounded-lg {{ $currentRoute==='hr.attendance.reports'?'font-semibold bg-blue-50':'text-gray-500 hover:text-gray-800 hover:bg-gray-50' }}" style="{{ $currentRoute==='hr.attendance.reports'?'color:#3b82f6;':'' }}">
                    @if($currentRoute==='hr.attendance.reports')<span class="w-2 h-2 rounded-full mr-2.5 flex-shrink-0" style="background:#3b82f6;animation:pulseDot 2s ease-in-out infinite;"></span>@endif
                    My Attendance
                </a>
                <a href="{{ route('hr.attendance.employee') }}" class="block px-3 py-2 text-sm rounded-lg {{ $currentRoute==='hr.attendance.employee'?'text-blue-600 font-semibold bg-blue-50':'text-gray-500 hover:text-gray-800 hover:bg-gray-50' }}">Employee Attendance</a>
                <a href="{{ route('hr.shift.scheduling') }}"   class="block px-3 py-2 text-sm rounded-lg {{ $currentRoute==='hr.shift.scheduling'?'text-blue-600 font-semibold bg-blue-50':'text-gray-500 hover:text-gray-800 hover:bg-gray-50' }}">Shift Scheduling</a>
                <a href="{{ route('hr.leave.management') }}"   class="block px-3 py-2 text-sm rounded-lg {{ $currentRoute==='hr.leave.management'?'text-blue-600 font-semibold bg-blue-50':'text-gray-500 hover:text-gray-800 hover:bg-gray-50' }}">Leave Management</a>
            </div>
        </div>

        <div>
            <button @click="payrollOpen=!payrollOpen" class="nav-item w-full flex items-center justify-between px-3 py-2.5 rounded-lg text-gray-600 hover:bg-gray-50">
                <div class="flex items-center space-x-3">
                    <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                    <span x-show="!sidebarCollapsed" class="text-sm font-medium whitespace-nowrap">Payroll</span>
                </div>
                <svg x-show="!sidebarCollapsed" class="w-4 h-4 chevron-icon" :class="{'rotate-180':payrollOpen}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
            </button>
            <div x-show="payrollOpen&&!sidebarCollapsed" x-transition:enter="transition ease-out duration-250" x-transition:enter-start="opacity-0 -translate-y-3 scale-y-95" x-transition:enter-end="opacity-100 translate-y-0 scale-y-100" x-transition:leave="transition ease-in duration-200" x-transition:leave-start="opacity-100 translate-y-0 scale-y-100" x-transition:leave-end="opacity-0 -translate-y-3 scale-y-95" class="ml-8 mt-1 space-y-0.5 origin-top">
                <a href="#" class="block px-3 py-2 text-sm text-gray-500 hover:text-gray-800 hover:bg-gray-50 rounded-lg">Payroll</a>
                <a href="#" class="block px-3 py-2 text-sm text-gray-500 hover:text-gray-800 hover:bg-gray-50 rounded-lg">Payslips</a>
                <a href="#" class="block px-3 py-2 text-sm text-gray-500 hover:text-gray-800 hover:bg-gray-50 rounded-lg">Govt. Contributions</a>
            </div>
        </div>

        <div>
            <button @click="requestsOpen=!requestsOpen" class="nav-item w-full flex items-center justify-between px-3 py-2.5 rounded-lg {{ in_array($currentRoute,$requestRoutes)?'text-blue-600 bg-blue-50':'text-gray-600 hover:bg-gray-50' }}">
                <div class="flex items-center space-x-3">
                    <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
                    <span x-show="!sidebarCollapsed" class="text-sm font-medium whitespace-nowrap">Requests & Approval</span>
                </div>
                <svg x-show="!sidebarCollapsed" class="w-4 h-4 chevron-icon" :class="{'rotate-180':requestsOpen}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
            </button>
            <div x-show="requestsOpen&&!sidebarCollapsed" x-transition:enter="transition ease-out duration-250" x-transition:enter-start="opacity-0 -translate-y-3 scale-y-95" x-transition:enter-end="opacity-100 translate-y-0 scale-y-100" x-transition:leave="transition ease-in duration-200" x-transition:leave-start="opacity-100 translate-y-0 scale-y-100" x-transition:leave-end="opacity-0 -translate-y-3 scale-y-95" class="ml-8 mt-1 space-y-0.5 origin-top">
                <a href="{{ route('hr.requests.pending') }}"  class="block px-3 py-2 text-sm rounded-lg {{ $currentRoute==='hr.requests.pending'?'text-blue-600 font-semibold bg-blue-50':'text-gray-500 hover:text-gray-800 hover:bg-gray-50' }}">Pending Requests</a>
                <a href="{{ route('hr.requests.approved') }}" class="block px-3 py-2 text-sm rounded-lg {{ $currentRoute==='hr.requests.approved'?'text-blue-600 font-semibold bg-blue-50':'text-gray-500 hover:text-gray-800 hover:bg-gray-50' }}">Approved Logs</a>
            </div>
        </div>

        <div class="pt-3 mt-2 border-t border-gray-100">
            <p x-show="!sidebarCollapsed" class="text-xs text-gray-400 font-semibold px-3 py-2 uppercase tracking-widest">Others</p>
            <a href="#" class="nav-item flex items-center space-x-3 px-3 py-2.5 rounded-lg text-gray-600 hover:bg-gray-50">
                <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                <span x-show="!sidebarCollapsed" class="text-sm font-medium whitespace-nowrap">Settings</span>
            </a>
            <a href="{{ route('logout') }}" onclick="event.preventDefault();document.getElementById('hr-logout-form').submit();" class="nav-item flex items-center space-x-3 px-3 py-2.5 rounded-lg text-gray-600 hover:bg-red-50 hover:text-red-500">
                <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>
                <span x-show="!sidebarCollapsed" class="text-sm font-medium whitespace-nowrap">Logout</span>
            </a>
            <form id="hr-logout-form" action="{{ route('logout') }}" method="POST" class="hidden">@csrf</form>
        </div>
    </nav>

    <button @click="sidebarCollapsed=!sidebarCollapsed" class="m-3 w-8 h-8 bg-gray-100 rounded-lg flex items-center justify-center text-gray-500 hover:bg-gray-200 self-end" style="transition:background .15s;">
        <svg class="w-4 h-4 chevron-icon" :class="{'rotate-180':sidebarCollapsed}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 19l-7-7 7-7m8 14l-7-7 7-7"/></svg>
    </button>
</aside>

{{-- ══════════ MAIN ══════════ --}}
<div x-data="{
        collapsed:localStorage.getItem('sidebarCollapsed')==='true',
        showFileReq:false,
        reqType:'',
        showApprove:false,
        showReject:false,
        selName:'',
        selId:null
     }"
     x-init="window.addEventListener('storage',e=>{if(e.key==='sidebarCollapsed')collapsed=e.newValue==='true'})"
     :style="collapsed?'margin-left:5rem':'margin-left:16rem'"
     style="transition:margin-left .35s cubic-bezier(.4,0,.2,1);min-height:100vh;">

    <header class="bg-gradient-to-br from-blue-500 to-blue-700 sticky top-0 z-10 shadow-lg mt-4 mx-4 rounded-2xl overflow-hidden">
        <div class="flex items-center justify-between px-8 py-4">
            <h1 class="text-white font-bold text-xl">Pending Requests</h1>
            <button class="w-9 h-9 rounded-full flex items-center justify-center hover:bg-white/20 relative">
                <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/></svg>
                @if(!empty($totalPending))
                <span class="absolute -top-1 -right-1 w-4 h-4 bg-red-500 rounded-full text-white text-xs flex items-center justify-center font-bold">{{ $totalPending }}</span>
                @endif
            </button>
        </div>
    </header>

    <div style="padding:24px 32px;">

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
                <select class="fsel"><option>All Stages</option><option>Awaiting Approval</option><option>Approved</option><option>Rejected</option></select>
                <select class="fsel"><option>All Types</option><option>Leave Request</option><option>Overtime Request</option><option>Shift Arrangement</option></select>
                <select class="fsel"><option>All Departments</option>@foreach($departments ?? [] as $d)<option>{{ $d->name }}</option>@endforeach</select>
                <button class="btn-primary" @click="showFileReq=true">
                    <svg style="width:14px;height:14px;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                    File a Request
                </button>
            </div>
        </div>

        {{-- REQUEST CARDS --}}
        @forelse($requests ?? [] as $req)
        <div class="rcard">
            <div class="rcard-head">
                <div class="rcard-left">
                    <div class="emp-av" style="background:{{ $req->avatar_color ?? '#3b82f6' }};">
                        {{ strtoupper(substr($req->employee->fname??'U',0,1).substr($req->employee->lname??'K',0,1)) }}
                    </div>
                    <div>
                        <div class="emp-name">{{ trim(($req->employee->fname??'').(' ').($req->employee->lname??'')) }}</div>
                        <div class="emp-dept">{{ $req->employee->department->name??'—' }} · {{ $req->employee->jobTitle->title??'—' }}</div>
                    </div>
                </div>
                <div class="rcard-right">
                    <span class="req-id">{{ $req->ref_no }}</span>
                    @if($req->type==='leave')<span class="badge b-leave">Leave Request</span>
                    @elseif($req->type==='overtime')<span class="badge b-ot">Overtime Request</span>
                    @elseif($req->type==='shift')<span class="badge b-shift">Shift Arrangement Request</span>@endif
                    <span class="badge b-await">Awaiting Your Approval</span>
                </div>
            </div>
            <div class="rcard-body">
                @if($req->type==='leave')
                <div class="dgrid">
                    <div><div class="dlabel">Leave Type</div><div class="dval">{{ $req->leave_type }}</div></div>
                    <div><div class="dlabel">Date</div><div class="dval">{{ \Carbon\Carbon::parse($req->date_from)->format('F j').' - '.\Carbon\Carbon::parse($req->date_to)->format('j, Y') }}</div></div>
                    <div><div class="dlabel">Filed On</div><div class="dval">{{ \Carbon\Carbon::parse($req->filed_on)->format('F j, Y') }}</div></div>
                    <div><div class="dlabel">VL Balance</div><div class="dval">{{ $req->vl_balance??'—' }} days remaining</div></div>
                </div>
                @elseif($req->type==='overtime')
                <div class="dgrid">
                    <div><div class="dlabel">OT Date</div><div class="dval">{{ \Carbon\Carbon::parse($req->ot_date)->format('F j, Y') }}</div></div>
                    <div><div class="dlabel">OT Hours</div><div class="dval">{{ $req->ot_hours??'—' }}</div></div>
                    <div><div class="dlabel">Time Range</div><div class="dval">{{ $req->time_range??'—' }}</div></div>
                    <div></div>
                </div>
                @elseif($req->type==='shift')
                <div class="dgrid">
                    <div><div class="dlabel">Current Shift</div><div class="dval">{{ $req->current_shift??'—' }}</div></div>
                    <div><div class="dlabel">Requested Shift</div><div class="dval">{{ $req->requested_shift??'—' }}</div></div>
                    <div><div class="dlabel">Effective Date</div><div class="dval">{{ isset($req->effective_from)?\Carbon\Carbon::parse($req->effective_from)->format('F j, Y'):'—' }}</div></div>
                    <div><div class="dlabel">Duration</div><div class="dval">{{ $req->duration??'—' }}</div></div>
                </div>
                @endif
                <div class="rsection"><div class="rlabel">Reason</div><div class="rtext">{{ $req->reason??'—' }}</div></div>
                @if(!empty($req->document))<div style="margin-bottom:10px;"><div class="rlabel">Documents</div><div style="font-size:13px;color:#3b82f6;font-weight:500;">{{ $req->document }}</div></div>@endif
                <div><div class="rlabel">Approval Progress</div>
                <div class="prog-steps">
                    <div class="pstep"><div class="pcircle pc-done"><svg style="width:14px;height:14px;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg></div><div class="pname">Supervisor</div><div class="pstatus">Approved</div></div>
                    <div class="pline"></div>
                    <div class="pstep"><div class="pcircle pc-active"><svg style="width:14px;height:14px;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 12h.01M12 12h.01M19 12h.01"/></svg></div><div class="pname">HR Manager</div><div class="pstatus">Pending Approval</div></div>
                </div></div>
            </div>
            <div class="action-row">
                <button class="btn-approve" @click="selId={{ $req->id }};selName='{{ addslashes(trim(($req->employee->fname??'').(' ').($req->employee->lname??''))) }}';showApprove=true">
                    <svg style="width:14px;height:14px;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg> Approve
                </button>
                <button class="btn-reject" @click="selId={{ $req->id }};selName='{{ addslashes(trim(($req->employee->fname??'').(' ').($req->employee->lname??''))) }}';showReject=true">
                    <svg style="width:14px;height:14px;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg> Reject
                </button>
            </div>
        </div>
        @empty
        {{-- ── DEMO CARDS ── --}}
        @php
        $demos=[
            ['id'=>2,'type'=>'leave','av'=>'JD','color'=>'#3b82f6','name'=>'Juan Dela Cruz','dept'=>'IT - Senior Programmer','ref'=>'REQ-2026-002',
             'grid'=>[['Leave Type','Vacation Leave (VL)'],['Date','March 10-12, 2026'],['Filed On','March 6, 2026'],['VL Balance','8 days remaining']],
             'reason'=>'Family trip','doc'=>null],
            ['id'=>3,'type'=>'overtime','av'=>'RM','color'=>'#ef4444','name'=>'Roberto Mendoza','dept'=>'IT - Senior Programmer','ref'=>'REQ-2026-003',
             'grid'=>[['OT Date','March 6, 2026'],['OT Hours','2h'],['Time Range','6:00 - 7:00 PM'],['','']],
             'reason'=>'System migration project - server deployment before Monday operations.','doc'=>null],
            ['id'=>4,'type'=>'shift','av'=>'MS','color'=>'#8b5cf6','name'=>'Mary Santos','dept'=>'Finance & Accounting - Shift Arrangement','ref'=>'REQ-2026-004',
             'grid'=>[['Current Shift','Night (10PM-6AM)'],['Requested Shift','Day (8AM-5PM)'],['Effective Date','March 16, 2026'],['Duration','March 23, 2026 - March 23, 3037']],
             'reason'=>'Medical - doctor\'s recommendation to avoid night shift due to hypertension. Medical Certificate attached.','doc'=>'medicalcertificate.pdf'],
        ];
        $bmap=['leave'=>['b-leave','Leave Request'],'overtime'=>['b-ot','Overtime Request'],'shift'=>['b-shift','Shift Arrangement Request']];
        @endphp
        @foreach($demos as $d)
        <div class="rcard">
            <div class="rcard-head">
                <div class="rcard-left">
                    <div class="emp-av" style="background:{{ $d['color'] }};">{{ $d['av'] }}</div>
                    <div><div class="emp-name">{{ $d['name'] }}</div><div class="emp-dept">{{ $d['dept'] }}</div></div>
                </div>
                <div class="rcard-right">
                    <span class="req-id">{{ $d['ref'] }}</span>
                    <span class="badge {{ $bmap[$d['type']][0] }}">{{ $bmap[$d['type']][1] }}</span>
                    <span class="badge b-await">Awaiting Your Approval</span>
                </div>
            </div>
            <div class="rcard-body">
                <div class="dgrid">
                    @foreach($d['grid'] as $g)
                    <div>@if($g[0])<div class="dlabel">{{ $g[0] }}</div><div class="dval">{{ $g[1] }}</div>@endif</div>
                    @endforeach
                </div>
                <div class="rsection"><div class="rlabel">Reason</div><div class="rtext">{{ $d['reason'] }}</div></div>
                @if($d['doc'])<div style="margin-bottom:10px;"><div class="rlabel">Documents</div><div style="font-size:13px;color:#3b82f6;font-weight:500;">{{ $d['doc'] }}</div></div>@endif
                <div><div class="rlabel">Approval Progress</div>
                <div class="prog-steps">
                    <div class="pstep"><div class="pcircle pc-done"><svg style="width:14px;height:14px;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg></div><div class="pname">Supervisor</div><div class="pstatus">Approved</div></div>
                    <div class="pline"></div>
                    <div class="pstep"><div class="pcircle pc-active"><svg style="width:14px;height:14px;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 12h.01M12 12h.01M19 12h.01"/></svg></div><div class="pname">HR Manager</div><div class="pstatus">Pending Approval</div></div>
                </div></div>
            </div>
            <div class="action-row">
                <button class="btn-approve" @click="selId={{ $d['id'] }};selName='{{ $d['name'] }}';showApprove=true">
                    <svg style="width:14px;height:14px;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg> Approve
                </button>
                <button class="btn-reject" @click="selId={{ $d['id'] }};selName='{{ $d['name'] }}';showReject=true">
                    <svg style="width:14px;height:14px;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg> Reject
                </button>
            </div>
        </div>
        @endforeach
        @endforelse

    </div>{{-- /padding --}}

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
                </select>
            </div>

            {{-- LEAVE --}}
            <div x-show="reqType==='leave'" x-transition>
                <div style="margin-bottom:16px;">
                    <label class="flabel">Leave Type</label>
                    <select class="finput fsel-modal" style="appearance:none;background-image:url(\"data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='10' height='6' fill='none'%3E%3Cpath d='M1 1l4 4 4-4' stroke='%236b7280' stroke-width='1.5' stroke-linecap='round'/%3E%3C/svg%3E\");background-repeat:no-repeat;background-position:right 12px center;">
                        <option>Vacation Leave (VL)</option><option>Sick Leave (SL)</option><option>Leave Without Pay (LWOP)</option>
                        <option>Maternity Leave (ML)</option><option>Paternity Leave (PL)</option><option>Solo Parent Leave (SPL)</option>
                    </select>
                </div>
                <div class="frow" style="margin-bottom:16px;">
                    <div><label class="flabel">From</label><input type="date" class="finput"></div>
                    <div><label class="flabel">To</label><input type="date" class="finput"></div>
                </div>
                <div style="margin-bottom:16px;">
                    <label class="flabel">Reason/Remarks</label>
                    <input type="text" class="finput" placeholder="Enter details">
                </div>
            </div>

            {{-- OVERTIME --}}
            <div x-show="reqType==='overtime'" x-transition>
                <div style="margin-bottom:16px;">
                    <label class="flabel">Date</label>
                    <input type="date" class="finput">
                </div>
                <div class="frow" style="margin-bottom:16px;">
                    <div><label class="flabel">OT Start</label><input type="time" class="finput"></div>
                    <div><label class="flabel">OT End</label><input type="time" class="finput"></div>
                </div>
                <div style="margin-bottom:16px;">
                    <label class="flabel">Reason/Remarks</label>
                    <input type="text" class="finput" placeholder="Enter details">
                </div>
            </div>

            {{-- SHIFT --}}
            <div x-show="reqType==='shift'" x-transition>
                <div style="margin-bottom:16px;">
                    <label class="flabel">Change Shift To</label>
                    <select class="finput fsel-modal" style="appearance:none;background-image:url(\"data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='10' height='6' fill='none'%3E%3Cpath d='M1 1l4 4 4-4' stroke='%236b7280' stroke-width='1.5' stroke-linecap='round'/%3E%3C/svg%3E\");background-repeat:no-repeat;background-position:right 12px center;">
                        <option value="">Choose shift type</option>
                        <option>Day Shift (8AM-5PM)</option><option>Mid Shift (2PM-11PM)</option><option>Night Shift (10PM-6AM)</option>
                    </select>
                </div>
                <div class="frow" style="margin-bottom:16px;">
                    <div><label class="flabel">Effective From</label><input type="date" class="finput"></div>
                    <div><label class="flabel">Effective Until</label><input type="date" class="finput"></div>
                </div>
                <div style="margin-bottom:16px;">
                    <label class="flabel">Reason/Remarks</label>
                    <input type="text" class="finput" placeholder="Enter details">
                </div>
            </div>

            <div style="margin-bottom:16px;" x-show="reqType!==''">
                <label class="flabel">Supporting Document (optional)</label>
                <label class="doc-upload">
                    <svg style="width:28px;height:28px;color:#9ca3af;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 13h6m-3-3v6m5 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                    <div style="font-size:13px;font-weight:600;color:#374151;">Choose a file to upload</div>
                    <div style="font-size:12px;color:#9ca3af;">PDF or DOCX file size no more than 10MB</div>
                    <input type="file" accept=".pdf,.docx" style="display:none;">
                </label>
            </div>

            <div class="mactions">
                <button class="btn-cancel" @click="showFileReq=false;reqType=''">Cancel</button>
                <button class="btn-save" x-show="reqType!==''">Submit</button>
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
                <button style="min-width:100px;padding:8px 20px;border-radius:8px;font-size:13px;font-weight:600;font-family:inherit;cursor:pointer;border:none;background:#16a34a;color:#fff;" @click="showApprove=false">Approve</button>
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
            <textarea class="finput" rows="3" placeholder="Enter rejection reason..." style="resize:none;text-align:left;"></textarea>
            <div style="display:flex;justify-content:center;gap:12px;margin-top:16px;">
                <button class="btn-cancel" style="min-width:100px;" @click="showReject=false">Cancel</button>
                <button style="min-width:100px;padding:8px 20px;border-radius:8px;font-size:13px;font-weight:600;font-family:inherit;cursor:pointer;border:none;background:#dc2626;color:#fff;" @click="showReject=false">Reject</button>
            </div>
        </div>
    </div>

</div>
</body>
</html>
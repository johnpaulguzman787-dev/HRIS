<!DOCTYPE html>
<html lang="en">
<head>
    <link rel="icon" type="image/png" href="{{ asset('images/HRISLogo-Icon.png') }}">
    <meta charset="UTF-8"/>
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=yes, viewport-fit=cover"/>
    <title>Approved Logs — MEDISOURCE</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet"/>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <style>
        * { font-family: 'DM Sans', sans-serif; box-sizing: border-box; }
        body { background: #f3f4f6; margin: 0; }
        [x-cloak] { display: none !important; }
        .nav-item { transition: all 0.2s ease; }
        .nav-item:hover { transform: translateX(2px); }
        .submenu-item { transition: all 0.18s ease; }
        .submenu-item:hover { transform: translateX(3px); }
        .chevron-icon { transition: transform 0.3s cubic-bezier(0.4,0,0.2,1); }
        .settings-icon { transition: transform 0.5s ease; }
        .nav-item:hover .settings-icon { transform: rotate(60deg); }
        .avatar-ring { box-shadow: 0 0 0 3px rgba(59,130,246,0.2); }
        @keyframes pulseDot { 0%,100%{opacity:1;transform:scale(1)} 50%{opacity:.5;transform:scale(1.3)} }

        /* STAT CARDS */
        .stat-card { background:#fff; border-radius:14px; padding:20px 22px; border:1px solid #e5e7eb; }

        /* DESKTOP TABLE */
        .tbl { width:100%; border-collapse:collapse; }
        .tbl th { font-size:12px; color:#6b7280; font-weight:600; padding:11px 14px; border-bottom:1.5px solid #e5e7eb; text-align:left; white-space:nowrap; }
        .tbl td { font-size:13px; color:#111827; padding:13px 14px; border-bottom:1px solid #f3f4f6; vertical-align:middle; }
        .tbl tr:last-child td { border-bottom:none; }
        .tbl tr:hover td { background:#f9fafb; }

        /* BADGES */
        .badge { display:inline-block; padding:3px 11px; border-radius:20px; font-size:11.5px; font-weight:600; }
        .badge-leave    { background:#fef3c7; color:#d97706; }
        .badge-ot       { background:#fce7f3; color:#db2777; }
        .badge-shift    { background:#ede9fe; color:#7c3aed; }
        .badge-approved { background:#dcfce7; color:#16a34a; }
        .badge-rejected { background:#fee2e2; color:#dc2626; }
        .approver-chip  { display:inline-block; background:#dbeafe; color:#1d4ed8; border-radius:20px; padding:2px 10px; font-size:11.5px; font-weight:600; margin:1px; }

        /* BUTTONS / INPUTS */
        .btn-view { background:#fff; border:1.5px solid #e5e7eb; border-radius:8px; padding:5px 16px; font-size:12px; font-weight:600; color:#374151; cursor:pointer; font-family:inherit; }
        .btn-view:hover { border-color:#3b82f6; color:#3b82f6; }
        .fsel { background:#fff; border:1.5px solid #e5e7eb; border-radius:8px; padding:8px 28px 8px 10px; font-size:12px; font-family:inherit; color:#374151; appearance:none; cursor:pointer; outline:none; width:100%; }
        .fsel:focus { border-color:#3b82f6; }
        .search-input { background:#fff; border:1.5px solid #e5e7eb; border-radius:8px; padding:8px 12px 8px 36px; font-size:13px; font-family:inherit; color:#374151; outline:none; width:100%; }
        .search-input:focus { border-color:#3b82f6; }

        /* MOBILE CARD VIEW */
        .req-card { background:#fff; border-radius:14px; border:1px solid #e5e7eb; padding:16px; margin-bottom:10px; }
        .req-card-header { display:flex; justify-content:space-between; align-items:flex-start; margin-bottom:10px; }
        .req-card-ref { font-size:14px; font-weight:700; color:#111827; }
        .req-card-filed { font-size:11px; color:#9ca3af; margin-top:2px; }
        .req-card-grid { display:grid; grid-template-columns:1fr 1fr; gap:8px; margin-bottom:10px; }
        .req-card-label { font-size:10px; font-weight:700; color:#9ca3af; text-transform:uppercase; letter-spacing:.4px; margin-bottom:2px; }
        .req-card-value { font-size:12px; color:#374151; font-weight:500; }
        .req-card-footer { display:flex; justify-content:space-between; align-items:center; border-top:1px solid #f3f4f6; padding-top:10px; margin-top:4px; }

        /* MODAL */
        .modal-overlay { position:fixed; inset:0; background:rgba(0,0,0,0.45); z-index:200; display:flex; align-items:flex-end; justify-content:center; padding:0; }
        @media(min-width:640px){
            .modal-overlay { align-items:center; padding:20px; }
        }
        .modal-box {
            background:#fff;
            border-radius:20px 20px 0 0;
            width:100%;
            max-width:100%;
            padding:28px 20px 32px;
            position:relative;
            max-height:92vh;
            overflow-y:auto;
        }
        @media(min-width:640px){
            .modal-box { border-radius:18px; max-width:540px; padding:32px; max-height:90vh; }
        }
        .modal-drag-handle { width:40px; height:4px; background:#e5e7eb; border-radius:4px; margin:0 auto 20px; display:block; }
        @media(min-width:640px){ .modal-drag-handle { display:none; } }
        .modal-field { background:#f3f4f6; border-radius:8px; padding:10px 14px; font-size:13px; color:#374151; }
        .modal-label { font-size:13px; font-weight:600; color:#111827; margin-bottom:6px; }
        .trail-item { display:flex; gap:14px; align-items:flex-start; padding:14px 16px; background:#f9fafb; border-radius:10px; border-left:3px solid #3b82f6; margin-bottom:8px; }

        /* RESPONSIVE SWITCH */
        .desktop-table { display:none; }
        .mobile-cards  { display:block; }
        @media(min-width:768px){
            .desktop-table { display:block; }
            .mobile-cards  { display:none; }
        }

        /* LAYOUT TOOLBAR */
        .toolbar-wrap { display:flex; flex-direction:column; gap:10px; padding:14px 16px; border-bottom:1px solid #f3f4f6; }
        @media(min-width:640px){ .toolbar-wrap { flex-direction:row; align-items:center; padding:14px 20px; } }
        .filter-row { display:flex; gap:8px; width:100%; }
        @media(min-width:640px){ .filter-row { width:auto; margin-left:auto; } }
        .filter-wrap { position:relative; flex:1; }
        @media(min-width:640px){ .filter-wrap { flex:none; min-width:130px; } }
        .fsel-wrap { position:relative; }
        .fsel-wrap::after { content:''; position:absolute; right:10px; top:50%; transform:translateY(-50%); width:0; height:0; border-left:4px solid transparent; border-right:4px solid transparent; border-top:5px solid #6b7280; pointer-events:none; }

        .stat-grid { display:grid; grid-template-columns:1fr 1fr; gap:12px; margin-bottom:20px; }
        @media(min-width:1024px){ .stat-grid { grid-template-columns:repeat(4,1fr); } }

        .page-content { padding:16px; }
        @media(min-width:768px){ .page-content { padding:32px; } }

        .page-header { margin:12px 12px 0; border-radius:16px; background: linear-gradient(135deg, #2563eb, #1e40af); }
        @media(min-width:768px){ .page-header { margin:16px 16px 0; border-radius:18px; } }

        /* SIDEBAR DESKTOP */
        .desktop-sidebar { display: none; }
        @media (min-width: 1024px) {
            .desktop-sidebar { display: block; }
        }
        @media (max-width: 1023px) {
            .main-content {
                margin-left: 0 !important;
            }
        }
        .anim-fade { animation: fadeSlideDown 0.4s ease both; }
        @keyframes fadeSlideDown { from { opacity:0; transform:translateY(-12px); } to { opacity:1; transform:translateY(0); } }
        .modal-slide-up { animation: slideUp 0.3s cubic-bezier(0.32,0.72,0,1) both; }
        @keyframes slideUp { from { transform:translateY(100%); opacity:0; } to { transform:translateY(0); opacity:1; } }
    </style>
</head>
<body class="bg-gray-50" x-data="{ mobileMenuOpen: false, sidebarCollapsed: localStorage.getItem('sidebarCollapsed') === 'true' }"
     x-init="window.addEventListener('sidebar-toggle', e => { sidebarCollapsed = e.detail.collapsed })">

{{-- ═══════════ SIDEBAR (DESKTOP + MOBILE DRAWER) ═══════════ --}}
<div class="hidden lg:block desktop-sidebar">
    @include('supervisor.supervisor_sidebar')
</div>

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

{{-- ═══════════ MAIN CONTENT ═══════════ --}}
<div class="main-content"
     :style="window.innerWidth >= 1024 ? (sidebarCollapsed ? 'margin-left:5rem' : 'margin-left:16rem') : 'margin-left:0'"
     x-on:resize.window="$el.style.marginLeft = window.innerWidth >= 1024 ? (sidebarCollapsed ? '5rem' : '16rem') : '0'"
     style="transition:margin-left 0.35s cubic-bezier(0.4,0,0.2,1); min-height:100vh;">

    {{-- HEADER with hamburger --}}
    <header class="anim-fade page-header sticky top-0 z-10 shadow-lg">
        <div class="flex items-center justify-between px-5 py-4 md:px-8">
            <div class="flex items-center gap-3">
                <button @click="mobileMenuOpen = true" class="lg:hidden p-1.5 rounded-lg hover:bg-white/20 transition-colors text-white">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M4 6h16M4 12h16M4 18h16"/>
                    </svg>
                </button>
                <h1 class="text-white font-bold text-lg md:text-xl">Approved Logs</h1>
            </div>
            <x-supervisor-notif />
        </div>
    </header>

    <div class="page-content space-y-4">

        {{-- STAT CARDS --}}
        <div class="stat-grid">
            <div class="stat-card" style="background:#dcfce7;border-color:#bbf7d0;">
                <div style="font-size:11px;font-weight:700;color:#15803d;text-transform:uppercase;letter-spacing:.5px;margin-bottom:4px;">Approved</div>
                <div style="font-size:28px;font-weight:800;color:#15803d;line-height:1;">{{ $approvedCount ?? 0 }}</div>
                <div style="font-size:11px;color:#166534;margin-top:4px;">{{ now()->format('F Y') }}</div>
            </div>
            <div class="stat-card" style="background:#fee2e2;border-color:#fecaca;">
                <div style="font-size:11px;font-weight:700;color:#b91c1c;text-transform:uppercase;letter-spacing:.5px;margin-bottom:4px;">Rejected</div>
                <div style="font-size:28px;font-weight:800;color:#b91c1c;line-height:1;">{{ $rejectedCount ?? 0 }}</div>
                <div style="font-size:11px;color:#991b1b;margin-top:4px;">With written reason</div>
            </div>
            <div class="stat-card" style="background:#dbeafe;border-color:#bfdbfe;">
                <div style="font-size:11px;font-weight:700;color:#1d4ed8;text-transform:uppercase;letter-spacing:.5px;margin-bottom:4px;">Leave Requests</div>
                <div style="font-size:28px;font-weight:800;color:#1d4ed8;line-height:1;">{{ ($requests ?? collect())->where('type','leave')->count() }}</div>
                <div style="font-size:11px;color:#1e40af;margin-top:4px;">{{ now()->format('F Y') }}</div>
            </div>
            <div class="stat-card" style="background:#ede9fe;border-color:#ddd6fe;">
                <div style="font-size:11px;font-weight:700;color:#6d28d9;text-transform:uppercase;letter-spacing:.5px;margin-bottom:4px;">OT & Shift</div>
                <div style="font-size:28px;font-weight:800;color:#6d28d9;line-height:1;">{{ ($requests ?? collect())->whereIn('type',['overtime','shift'])->count() }}</div>
                <div style="font-size:11px;color:#5b21b6;margin-top:4px;">Requests</div>
            </div>
        </div>

        {{-- MAIN CARD with filter & table/cards --}}
        <div class="bg-white rounded-2xl border border-gray-200" style="box-shadow:0 2px 12px rgba(0,0,0,0.06);">

            {{-- TOOLBAR (responsive) --}}
            <form method="GET" action="{{ route('supervisor.requests.approved') }}" id="filterForm" class="toolbar-wrap">
                <div class="relative w-full" style="max-width:100%;">
                    <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                    </svg>
                    <input type="text" name="search" placeholder="Search employee…"
                           value="{{ $search ?? '' }}"
                           class="search-input"
                           onchange="document.getElementById('filterForm').submit()"/>
                </div>
                <div class="filter-row">
                    <div class="fsel-wrap filter-wrap">
                        <select name="status" class="fsel" onchange="document.getElementById('filterForm').submit()">
                            <option value="all"      {{ ($filterStatus ?? 'all') === 'all'      ? 'selected' : '' }}>All Status</option>
                            <option value="approved" {{ ($filterStatus ?? '') === 'approved'    ? 'selected' : '' }}>Approved</option>
                            <option value="rejected" {{ ($filterStatus ?? '') === 'rejected'    ? 'selected' : '' }}>Rejected</option>
                        </select>
                    </div>
                    <div class="fsel-wrap filter-wrap">
                        <select name="type" class="fsel" onchange="document.getElementById('filterForm').submit()">
                            <option value="all"      {{ ($filterType ?? 'all') === 'all'      ? 'selected' : '' }}>All Types</option>
                            <option value="leave"    {{ ($filterType ?? '') === 'leave'       ? 'selected' : '' }}>Leave Request</option>
                            <option value="overtime" {{ ($filterType ?? '') === 'overtime'    ? 'selected' : '' }}>Overtime</option>
                            <option value="shift"    {{ ($filterType ?? '') === 'shift'       ? 'selected' : '' }}>Shift Arrangement</option>
                        </select>
                    </div>
                </div>
            </form>

            {{-- DESKTOP TABLE --}}
            <div class="desktop-table overflow-x-auto">
                <table class="tbl">
                    <thead>
                        <tr><th>Ref #</th><th>Employee</th><th>Type</th><th>Date Filed</th><th>Duration</th><th>Days</th><th>Approvers</th><th>Processed On</th><th>Status</th><th></th></tr>
                    </thead>
                    <tbody>
                        @forelse($requests ?? [] as $req)
                        @php
                            $empName  = trim(($req->employee->fname ?? '') . ' ' . ($req->employee->lname ?? ''));
                            $deptName = $req->employee->department->name ?? '—';
                            $approverName = '';
                            if ($req->type === 'leave' && isset($req->approver)) {
                                $approverName = trim(($req->approver->fname ?? '') . ' ' . ($req->approver->lname ?? ''));
                            } elseif (in_array($req->type, ['overtime','shift']) && isset($req->approved_by)) {
                                $approver = \App\Models\Employee::find($req->approved_by);
                                $approverName = $approver ? trim($approver->fname . ' ' . $approver->lname) : '—';
                            }
                            $duration = match($req->type) {
                                'leave'    => \Carbon\Carbon::parse($req->start_date)->format('m/d/Y') . ' – ' . \Carbon\Carbon::parse($req->end_date)->format('m/d/Y'),
                                'overtime' => \Carbon\Carbon::parse($req->ot_date)->format('m/d/Y'),
                                'shift'    => \Carbon\Carbon::parse($req->effective_from)->format('m/d/Y') . ($req->effective_until ? ' – ' . \Carbon\Carbon::parse($req->effective_until)->format('m/d/Y') : ' (Ongoing)'),
                                default    => '—',
                            };
                            $days = match($req->type) {
                                'leave'    => $req->total_days . 'd',
                                'overtime' => ($req->approved_hours ?? $req->requested_hours) . 'h',
                                'shift'    => ($req->requested_shift->name ?? '—'),
                                default    => '—',
                            };
                            $typeLabel = match($req->type) {
                                'leave'    => 'Leave Request',
                                'overtime' => 'Overtime',
                                'shift'    => 'Shift Arrangement',
                                default    => '—',
                            };
                            $typeBadge = match($req->type) {
                                'leave'    => 'badge-leave',
                                'overtime' => 'badge-ot',
                                'shift'    => 'badge-shift',
                                default    => '',
                            };
                            $subDetail = match($req->type) {
                                'leave'    => $req->leaveType->name ?? '—',
                                'overtime' => ($req->ot_start_time ? \Carbon\Carbon::parse($req->ot_start_time)->format('g:i A') . ' – ' . \Carbon\Carbon::parse($req->ot_end_time)->format('g:i A') : '—'),
                                'shift'    => ($req->current_shift->name ?? '—') . ' → ' . ($req->requested_shift->name ?? '—'),
                                default    => '—',
                            };
                            $jsRef        = addslashes($req->ref_no ?? '');
                            $jsDept       = addslashes($deptName);
                            $jsName       = addslashes($empName);
                            $jsFiled      = $req->created_at ? $req->created_at->format('F j, Y') : '—';
                            $jsReqType    = addslashes($typeLabel);
                            $jsSubDetail  = addslashes($subDetail);
                            $jsDuration   = addslashes($duration);
                            $jsReason     = addslashes($req->reason ?? '—');
                            $jsRejReason  = addslashes($req->rejection_reason ?? '');
                            $jsHrNotes    = addslashes($req->hr_notes ?? '');
                            $jsApprover   = addslashes($approverName);
                            $jsApprovedAt = $req->approved_at ? \Carbon\Carbon::parse($req->approved_at)->format('F j, Y') : '—';
                            $jsStatus     = $req->status;
                        @endphp
                        <tr><td class="font-semibold text-gray-700">{{ $req->ref_no }}</td>
                            <td><div style="font-weight:600;font-size:13px;">{{ $empName }}</div><div style="font-size:11.5px;color:#6b7280;">{{ $deptName }}</div></td>
                            <td><span class="badge {{ $typeBadge }}">{{ $typeLabel }}</span></td>
                            <td style="font-size:12px;color:#6b7280;">{{ $req->created_at ? $req->created_at->format('m/d/Y') : '—' }}</td>
                            <td style="font-size:12px;color:#6b7280;">{{ $duration }}</td>
                            <td style="font-weight:600;">{{ $days }}</td>
                            <td>@if($approverName)<span class="approver-chip">{{ $approverName }}</span>@else<span style="font-size:12px;color:#9ca3af;">—</span>@endif</td>
                            <td style="font-size:12px;color:#6b7280;">{{ $req->approved_at ? \Carbon\Carbon::parse($req->approved_at)->format('m/d/Y') : '—' }}</td>
                            <td>@if($req->status === 'approved')<span class="badge badge-approved">Approved</span>@elseif($req->status === 'rejected')<span class="badge badge-rejected">Rejected</span>@elseif($req->status === 'cancelled')<span class="badge" style="background:#f3f4f6;color:#6b7280;">Cancelled</span>@elseif($req->status === 'supervisor_approved')<span class="badge" style="background:#dbeafe;color:#1d4ed8;">Forwarded</span>@endif</td>
                            <td><button class="btn-view" onclick="openViewModal('{{ $jsRef }}','{{ $jsDept }}','{{ $jsName }}','{{ $jsFiled }}','{{ $jsReqType }}','{{ $jsSubDetail }}','{{ $jsDuration }}','{{ $jsReason }}','{{ $jsRejReason }}','{{ $jsHrNotes }}','{{ $jsApprover }}','{{ $jsApprovedAt }}','{{ $jsStatus }}')">View</button></td>
                        </tr>
                        @empty
                        <tr><td colspan="10" style="text-align:center;padding:40px;color:#9ca3af;">No records found.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{-- MOBILE CARDS VIEW --}}
            <div class="mobile-cards p-3">
                @forelse($requests ?? [] as $req)
                @php
                    $empName  = trim(($req->employee->fname ?? '') . ' ' . ($req->employee->lname ?? ''));
                    $deptName = $req->employee->department->name ?? '—';
                    $approverName = '';
                    if ($req->type === 'leave' && isset($req->approver)) {
                        $approverName = trim(($req->approver->fname ?? '') . ' ' . ($req->approver->lname ?? ''));
                    } elseif (in_array($req->type, ['overtime','shift']) && isset($req->approved_by)) {
                        $approver = \App\Models\Employee::find($req->approved_by);
                        $approverName = $approver ? trim($approver->fname . ' ' . $approver->lname) : '—';
                    }
                    $duration = match($req->type) {
                        'leave'    => \Carbon\Carbon::parse($req->start_date)->format('m/d/Y') . ' – ' . \Carbon\Carbon::parse($req->end_date)->format('m/d/Y'),
                        'overtime' => \Carbon\Carbon::parse($req->ot_date)->format('m/d/Y'),
                        'shift'    => \Carbon\Carbon::parse($req->effective_from)->format('m/d/Y') . ($req->effective_until ? ' – ' . \Carbon\Carbon::parse($req->effective_until)->format('m/d/Y') : ' (Ongoing)'),
                        default    => '—',
                    };
                    $days = match($req->type) {
                        'leave'    => $req->total_days . 'd',
                        'overtime' => ($req->approved_hours ?? $req->requested_hours) . 'h',
                        'shift'    => ($req->requested_shift->name ?? '—'),
                        default    => '—',
                    };
                    $typeLabel = match($req->type) {
                        'leave'    => 'Leave Request',
                        'overtime' => 'Overtime',
                        'shift'    => 'Shift Arrangement',
                        default    => '—',
                    };
                    $typeBadge = match($req->type) {
                        'leave'    => 'badge-leave',
                        'overtime' => 'badge-ot',
                        'shift'    => 'badge-shift',
                        default    => '',
                    };
                    $subDetail = match($req->type) {
                        'leave'    => $req->leaveType->name ?? '—',
                        'overtime' => ($req->ot_start_time ? \Carbon\Carbon::parse($req->ot_start_time)->format('g:i A') . ' – ' . \Carbon\Carbon::parse($req->ot_end_time)->format('g:i A') : '—'),
                        'shift'    => ($req->current_shift->name ?? '—') . ' → ' . ($req->requested_shift->name ?? '—'),
                        default    => '—',
                    };
                    $jsRef        = addslashes($req->ref_no ?? '');
                    $jsDept       = addslashes($deptName);
                    $jsName       = addslashes($empName);
                    $jsFiled      = $req->created_at ? $req->created_at->format('F j, Y') : '—';
                    $jsReqType    = addslashes($typeLabel);
                    $jsSubDetail  = addslashes($subDetail);
                    $jsDuration   = addslashes($duration);
                    $jsReason     = addslashes($req->reason ?? '—');
                    $jsRejReason  = addslashes($req->rejection_reason ?? '');
                    $jsHrNotes    = addslashes($req->hr_notes ?? '');
                    $jsApprover   = addslashes($approverName);
                    $jsApprovedAt = $req->approved_at ? \Carbon\Carbon::parse($req->approved_at)->format('F j, Y') : '—';
                    $jsStatus     = $req->status;
                @endphp
                <div class="req-card">
                    <div class="req-card-header">
                        <div><div class="req-card-ref">{{ $req->ref_no }}</div><div class="req-card-filed">Filed on {{ $req->created_at ? $req->created_at->format('M j, Y') : '—' }}</div></div>
                        <div>@if($req->status === 'approved')<span class="badge badge-approved">Approved</span>@elseif($req->status === 'rejected')<span class="badge badge-rejected">Rejected</span>@elseif($req->status === 'cancelled')<span class="badge" style="background:#f3f4f6;color:#6b7280;">Cancelled</span>@elseif($req->status === 'supervisor_approved')<span class="badge" style="background:#dbeafe;color:#1d4ed8;">Forwarded</span>@endif</div>
                    </div>
                    <div class="req-card-grid">
                        <div><div class="req-card-label">Employee</div><div class="req-card-value" style="font-weight:600;">{{ $empName }}</div><div style="font-size:10px;color:#9ca3af;">{{ $deptName }}</div></div>
                        <div><div class="req-card-label">Type</div><span class="badge {{ $typeBadge }}" style="font-size:11px;">{{ $typeLabel }}</span></div>
                        <div><div class="req-card-label">Duration</div><div class="req-card-value">{{ $duration }}</div></div>
                        <div><div class="req-card-label">Days / Hours</div><div class="req-card-value" style="font-weight:700;">{{ $days }}</div></div>
                        <div><div class="req-card-label">Processed</div><div class="req-card-value">{{ $req->approved_at ? \Carbon\Carbon::parse($req->approved_at)->format('m/d/Y') : '—' }}</div></div>
                    </div>
                    <div class="req-card-footer"><div>@if($approverName)<span class="approver-chip">{{ $approverName }}</span>@else<span style="font-size:12px;color:#9ca3af;">—</span>@endif</div><button class="btn-view" onclick="openViewModal('{{ $jsRef }}','{{ $jsDept }}','{{ $jsName }}','{{ $jsFiled }}','{{ $jsReqType }}','{{ $jsSubDetail }}','{{ $jsDuration }}','{{ $jsReason }}','{{ $jsRejReason }}','{{ $jsHrNotes }}','{{ $jsApprover }}','{{ $jsApprovedAt }}','{{ $jsStatus }}')">View</button></div>
                </div>
                @empty
                <div style="text-align:center;padding:40px;color:#9ca3af;">No records found.</div>
                @endforelse
            </div>
        </div>
    </div>
</div>

{{-- MODAL VIEW (mobile-optimized drag handle) --}}
<div id="viewModal" style="display:none;" class="modal-overlay" onclick="if(event.target===this)closeViewModal()">
    <div class="modal-box modal-slide-up">
        <span class="modal-drag-handle"></span>
        <button onclick="closeViewModal()" style="position:absolute;top:16px;right:16px;width:34px;height:34px;border-radius:50%;border:2px solid #d1d5db;background:none;cursor:pointer;display:flex;align-items:center;justify-content:center;color:#9ca3af;"><svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"/></svg></button>
        <div class="mb-5" style="padding-right:40px;"><div style="display:flex;align-items:center;gap:10px;flex-wrap:wrap;margin-bottom:4px;"><div id="mRef" style="font-size:22px;font-weight:900;color:#111827;"></div><div id="mStatusBadge"></div></div><div id="mSub" style="font-size:13px;color:#374151;"></div><div id="mFiled" style="font-size:12px;color:#9ca3af;margin-top:2px;"></div></div>
        <div class="grid grid-cols-2 gap-3 mb-4"><div><div class="modal-label">Request Type</div><div id="mReqType" class="modal-field"></div></div><div><div class="modal-label">Leave Type / Detail</div><div id="mLeaveType" class="modal-field"></div></div></div>
        <div class="grid grid-cols-2 gap-3 mb-4"><div><div class="modal-label">From</div><div id="mFrom" class="modal-field"></div></div><div><div class="modal-label">To</div><div id="mTo" class="modal-field"></div></div></div>
        <div class="mb-4"><div class="modal-label">Reason/Remarks</div><div id="mReason" class="modal-field"></div></div>
        <div id="mRejBlock" style="display:none;margin-bottom:16px;"><div class="modal-label" style="color:#dc2626;">Rejection Reason</div><div id="mRejReason" class="modal-field" style="border-left:3px solid #ef4444;"></div></div>
        <div id="mNotesBlock" style="display:none;margin-bottom:16px;"><div class="modal-label">HR Notes</div><div id="mHrNotes" class="modal-field"></div></div>
        <div class="mb-5"><div class="modal-label mb-3">Approval Trail</div><div id="mTrail"></div></div>
        <div class="flex justify-end"><button onclick="closeViewModal()" style="background:#3b82f6;color:#fff;border:none;border-radius:10px;padding:12px 30px;font-size:14px;font-weight:700;cursor:pointer;">Confirm</button></div>
    </div>
</div>

<script>
function openViewModal(ref, dept, name, filed, reqType, subDetail, duration, reason, rejReason, hrNotes, approver, approvedAt, status) {
    document.getElementById('mRef').textContent = ref;
    document.getElementById('mSub').textContent = dept + ' · ' + name;
    document.getElementById('mFiled').textContent = 'Filed on ' + filed;
    document.getElementById('mReqType').textContent = reqType;
    document.getElementById('mLeaveType').textContent = subDetail;
    document.getElementById('mReason').textContent = reason;
    let parts = duration.split(' – ');
    document.getElementById('mFrom').textContent = parts[0] ? parts[0].trim() : duration;
    document.getElementById('mTo').textContent = parts[1] ? parts[1].trim() : '—';
    let badgeHtml = '';
    if (status === 'approved') badgeHtml = '<span style="display:inline-block;padding:3px 12px;border-radius:20px;font-size:12px;font-weight:700;background:#dcfce7;color:#16a34a;">Approved</span>';
    else if (status === 'rejected') badgeHtml = '<span style="display:inline-block;padding:3px 12px;border-radius:20px;font-size:12px;font-weight:700;background:#fee2e2;color:#dc2626;">Rejected</span>';
    else if (status === 'cancelled') badgeHtml = '<span style="display:inline-block;padding:3px 12px;border-radius:20px;font-size:12px;font-weight:700;background:#f3f4f6;color:#6b7280;">Cancelled</span>';
    else if (status === 'supervisor_approved') badgeHtml = '<span style="display:inline-block;padding:3px 12px;border-radius:20px;font-size:12px;font-weight:700;background:#dbeafe;color:#1d4ed8;">Forwarded to HR</span>';
    document.getElementById('mStatusBadge').innerHTML = badgeHtml;
    document.getElementById('mRejBlock').style.display = (rejReason && rejReason.trim() !== '') ? 'block' : 'none';
    if (rejReason && rejReason.trim() !== '') document.getElementById('mRejReason').textContent = rejReason;
    document.getElementById('mNotesBlock').style.display = (hrNotes && hrNotes.trim() !== '') ? 'block' : 'none';
    if (hrNotes && hrNotes.trim() !== '') document.getElementById('mHrNotes').textContent = hrNotes;
    let isApproved = status === 'approved';
    let trailColor = isApproved ? '#16a34a' : '#dc2626';
    let trailBg = isApproved ? '#dcfce7' : '#fee2e2';
    let actionWord = isApproved ? 'Approved' : (status === 'rejected' ? 'Rejected' : 'Processed');
    let icon = isApproved ? '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/>' : '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>';
    let html = '';
    html += '<div class="trail-item" style="border-left-color:#6b7280;"><div style="width:30px;height:30px;border-radius:50%;background:#e5e7eb;display:flex;align-items:center;justify-content:center;"><svg width="14" height="14" fill="none" stroke="#6b7280" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg></div><div><div style="font-size:13px;font-weight:700;color:#111827;">Filed by Employee</div><div style="font-size:12px;color:#6b7280;margin-top:2px;">' + name + '</div><div style="font-size:11px;color:#9ca3af;">' + filed + '</div></div></div>';
    html += '<div class="trail-item" style="border-left-color:#3b82f6;"><div style="width:30px;height:30px;border-radius:50%;background:#dbeafe;display:flex;align-items:center;justify-content:center;"><svg width="14" height="14" fill="none" stroke="#1d4ed8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg></div><div><div style="font-size:13px;font-weight:700;color:#111827;">Supervisor Forwarded to HR</div><div style="font-size:11px;color:#9ca3af;">Pending HR final approval</div></div></div>';
    if (approver && approver.trim() !== '' && approver !== '—') {
        html += '<div class="trail-item" style="border-left-color:' + trailColor + ';"><div style="width:30px;height:30px;border-radius:50%;background:' + trailBg + ';display:flex;align-items:center;justify-content:center;"><svg width="14" height="14" fill="none" stroke="' + trailColor + '" viewBox="0 0 24 24">' + icon + '</svg></div><div><div style="font-size:13px;font-weight:700;color:#111827;">HR Manager ' + actionWord + '</div><div style="font-size:12px;color:#6b7280;margin-top:2px;">' + approver + '</div><div style="font-size:11px;color:#9ca3af;">' + actionWord + ' on ' + approvedAt + '</div></div></div>';
    }
    document.getElementById('mTrail').innerHTML = html;
    let modal = document.getElementById('viewModal');
    modal.style.display = 'flex';
    modal.style.alignItems = (window.innerWidth < 640) ? 'flex-end' : 'center';
}
function closeViewModal() { document.getElementById('viewModal').style.display = 'none'; }
window.addEventListener('resize', function(){ let modal = document.getElementById('viewModal'); if(modal.style.display !== 'none') modal.style.alignItems = window.innerWidth < 640 ? 'flex-end' : 'center'; });
(function() { let mainEl = document.querySelector('.main-content'); if(!mainEl) return; function updateMargin() { let collapsed = localStorage.getItem('sidebarCollapsed') === 'true'; if(window.innerWidth < 1024) mainEl.style.marginLeft = '0'; else mainEl.style.marginLeft = collapsed ? '5rem' : '16rem'; } updateMargin(); window.addEventListener('resize', updateMargin); window.addEventListener('sidebar-toggle', function(e) { if(window.innerWidth >= 1024) mainEl.style.marginLeft = e.detail.collapsed ? '5rem' : '16rem'; }); })();
</script>
</body>
</html>
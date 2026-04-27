@php
    $currentRoute = request()->route()->getName();
    $sidebarUser = auth()->user();
    $sidebarEmployee = $sidebarUser ? \App\Models\Employee::with('jobTitle')->where('user_id', $sidebarUser->id)->first() : null;
    $sidebarInitials = $sidebarEmployee
        ? strtoupper(substr($sidebarEmployee->fname, 0, 1) . substr($sidebarEmployee->lname, 0, 1))
        : ($sidebarUser ? strtoupper(substr($sidebarUser->email, 0, 2)) : 'U');
    $sidebarName = $sidebarEmployee
        ? trim($sidebarEmployee->fname . ' ' . $sidebarEmployee->lname)
        : ($sidebarUser?->email ?? 'User');
    $sidebarRole = $sidebarEmployee?->jobTitle?->title ?? ($sidebarUser?->role ?? '—');

    $attendanceRoutes = ['hr.attendance.reports', 'hr.attendance.employee', 'hr.attendance.shift', 'hr.attendance.leave', 'hr.shift.scheduling', 'hr.leave.management'];
    $employeeRoutes   = ['hr.employees.directory', 'hr.employees.profile'];
    $payrollRoutes    = ['hr.payroll', 'hr.payslips', 'hr.contributions'];
    $requestRoutes    = ['hr.requests.pending', 'hr.requests.approved'];
@endphp
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

        /* ── STAT CARDS (Responsive Grid) ── */
        .stat-grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: 12px; margin-bottom: 20px; }
        @media (min-width: 768px) { .stat-grid { grid-template-columns: repeat(4, 1fr); gap: 16px; margin-bottom: 24px; } }
        .stat-card { background:#fff; border-radius:14px; padding:16px; border:1px solid #e5e7eb; }
        @media (min-width: 768px) { .stat-card { padding:20px 22px; } }

        /* ── TABLE (Responsive) ── */
        .desktop-table { display: none; }
        .mobile-cards { display: block; }
        @media (min-width: 768px) {
            .desktop-table { display: block; }
            .mobile-cards { display: none; }
        }
        .tbl { width:100%; border-collapse:collapse; min-width: 800px; }
        .tbl th { font-size:11px; color:#6b7280; font-weight:600; padding:10px 12px; border-bottom:1.5px solid #e5e7eb; text-align:left; white-space:nowrap; }
        .tbl td { font-size:12px; color:#111827; padding:12px 12px; border-bottom:1px solid #f3f4f6; vertical-align:middle; }
        .tbl tr:last-child td { border-bottom:none; }
        .tbl tr:hover td { background:#f9fafb; }

        /* ── MOBILE CARDS ── */
        .req-card { background:#fff; border-radius:14px; border:1px solid #e5e7eb; padding:14px; margin-bottom:10px; }
        .req-card-header { display:flex; justify-content:space-between; align-items:flex-start; margin-bottom:10px; flex-wrap:wrap; gap:8px; }
        .req-card-ref { font-size:14px; font-weight:700; color:#111827; }
        .req-card-filed { font-size:11px; color:#9ca3af; margin-top:2px; }
        .req-card-grid { display:grid; grid-template-columns:1fr 1fr; gap:10px; margin-bottom:10px; }
        .req-card-label { font-size:10px; font-weight:700; color:#9ca3af; text-transform:uppercase; letter-spacing:.4px; margin-bottom:2px; }
        .req-card-value { font-size:12px; color:#374151; font-weight:500; }
        .req-card-footer { display:flex; justify-content:space-between; align-items:center; border-top:1px solid #f3f4f6; padding-top:10px; margin-top:4px; flex-wrap:wrap; gap:8px; }

        /* ── BADGES ── */
        .badge { display:inline-block; padding:2px 9px; border-radius:20px; font-size:10.5px; font-weight:600; white-space:nowrap; }
        @media (min-width: 640px) { .badge { padding:3px 11px; font-size:11.5px; } }
        .badge-leave    { background:#fef3c7; color:#d97706; }
        .badge-ot       { background:#fce7f3; color:#db2777; }
        .badge-shift    { background:#ede9fe; color:#7c3aed; }
        .badge-adjustment { background:#fef3c7; color:#92400e; }
        .badge-approved { background:#dcfce7; color:#16a34a; }
        .badge-rejected { background:#fee2e2; color:#dc2626; }
        .approver-chip  { display:inline-block; background:#dbeafe; color:#1d4ed8; border-radius:20px; padding:2px 9px; font-size:10.5px; font-weight:600; margin:1px; white-space:nowrap; }

        /* ── BUTTONS / INPUTS ── */
        .btn-view { background:#fff; border:1.5px solid #e5e7eb; border-radius:8px; padding:4px 14px; font-size:12px; font-weight:600; color:#374151; cursor:pointer; font-family:inherit; transition:all .15s; }
        .btn-view:hover { border-color:#3b82f6; color:#3b82f6; }
        .fsel { background:#fff; border:1.5px solid #e5e7eb; border-radius:8px; padding:7px 28px 7px 10px; font-size:12px; font-family:inherit; color:#374151; appearance:none; cursor:pointer; outline:none; width:100%; }
        @media (min-width: 640px) { .fsel { font-size:13px; padding:8px 32px 8px 12px; width:auto; } }
        .fsel:focus { border-color:#3b82f6; }
        .fsel-wrap { position:relative; width:100%; }
        .fsel-wrap::after { content:''; position:absolute; right:10px; top:50%; transform:translateY(-50%); width:0; height:0; border-left:4px solid transparent; border-right:4px solid transparent; border-top:5px solid #6b7280; pointer-events:none; }
        .search-input { background:#fff; border:1.5px solid #e5e7eb; border-radius:8px; padding:7px 12px 7px 34px; font-size:12px; font-family:inherit; color:#374151; outline:none; width:100%; }
        @media (min-width: 640px) { .search-input { font-size:13px; padding:8px 12px 8px 36px; } }
        .search-input:focus { border-color:#3b82f6; }

        /* ── TOOLBAR (Responsive) ── */
        .toolbar-wrap { display:flex; flex-direction:column; gap:10px; padding:12px 14px; border-bottom:1px solid #f3f4f6; }
        @media (min-width: 640px) { .toolbar-wrap { flex-direction:row; align-items:center; padding:14px 20px; } }
        .filter-row { display:flex; gap:8px; width:100%; flex-wrap:wrap; }
        @media (min-width: 640px) { .filter-row { width:auto; margin-left:auto; } }
        .filter-item { flex:1; min-width:100px; }

        /* ── MODAL (Mobile First - Slide Up) ── */
        .modal-overlay { position:fixed; inset:0; background:rgba(0,0,0,0.45); z-index:200; display:flex; align-items:flex-end; justify-content:center; padding:0; }
        @media(min-width:640px){ .modal-overlay { align-items:center; padding:20px; } }
        .modal-box {
            background:#fff;
            border-radius:20px 20px 0 0;
            width:100%;
            max-width:100%;
            padding:24px 20px 32px;
            position:relative;
            max-height:90vh;
            overflow-y:auto;
        }
        @media(min-width:640px){
            .modal-box { border-radius:18px; max-width:540px; padding:32px; max-height:85vh; }
        }
        .modal-drag-handle { width:40px; height:4px; background:#e5e7eb; border-radius:4px; margin:0 auto 20px; display:block; }
        @media(min-width:640px){ .modal-drag-handle { display:none; } }
        .modal-field { background:#f3f4f6; border-radius:8px; padding:9px 13px; font-size:12px; color:#374151; }
        @media(min-width:640px){ .modal-field { padding:10px 14px; font-size:13px; } }
        .modal-label { font-size:12px; font-weight:600; color:#111827; margin-bottom:5px; }
        @media(min-width:640px){ .modal-label { font-size:13px; margin-bottom:6px; } }
        .trail-item { display:flex; gap:12px; align-items:flex-start; padding:12px 14px; background:#f9fafb; border-radius:10px; border-left:3px solid #3b82f6; margin-bottom:8px; }
        @media(min-width:640px){ .trail-item { gap:14px; padding:14px 16px; } }
        .trail-check { width:28px; height:28px; border-radius:50%; background:#3b82f6; display:flex; align-items:center; justify-content:center; flex-shrink:0; }
        @media(min-width:640px){ .trail-check { width:30px; height:30px; } }
        .modal-confirm-btn { width:100%; background:#3b82f6; color:#fff; border:none; border-radius:10px; padding:12px; font-size:14px; font-weight:700; font-family:inherit; cursor:pointer; }
        @media(min-width:640px){ .modal-confirm-btn { width:auto; padding:10px 30px; } }

        /* ── ANIMATIONS ── */
        .anim-fade { animation: fadeSlideDown 0.4s ease both; }
        @keyframes fadeSlideDown { from { opacity:0; transform:translateY(-12px); } to { opacity:1; transform:translateY(0); } }
        .modal-slide-up { animation: slideUp 0.3s cubic-bezier(0.32,0.72,0,1) both; }
        @keyframes slideUp { from { transform:translateY(100%); opacity:0; } to { transform:translateY(0); opacity:1; } }

        /* ── PAGE CONTENT PADDING ── */
        .page-content { padding:16px; }
        @media(min-width:768px){ .page-content { padding:24px 32px; } }
        .page-header { margin:12px 12px 0; border-radius:16px; }
        @media(min-width:768px){ .page-header { margin:16px 16px 0; border-radius:18px; } }

        /* Sidebar responsive */
        .desktop-sidebar { display: none; }
        @media (min-width: 1024px) {
            .desktop-sidebar { display: block; }
        }
        @media (max-width: 1023px) {
            .main-content {
                margin-left: 0 !important;
            }
        }
    </style>
</head>
<body class="bg-gray-50" x-data="{ mobileMenuOpen: false, sidebarCollapsed: localStorage.getItem('sidebarCollapsed') === 'true' }"
     x-init="window.addEventListener('sidebar-toggle', e => { sidebarCollapsed = e.detail.collapsed })">

{{-- ═══════════ SIDEBAR ═══════════ --}}

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

{{-- ═══════════ MAIN CONTENT ═══════════ --}}
<div class="main-content"
     :style="window.innerWidth >= 1024 ? (sidebarCollapsed ? 'margin-left:5rem' : 'margin-left:16rem') : 'margin-left:0'"
     x-on:resize.window="$el.style.marginLeft = window.innerWidth >= 1024 ? (sidebarCollapsed ? '5rem' : '16rem') : '0'"
     style="transition:margin-left 0.35s cubic-bezier(0.4,0,0.2,1); min-height:100vh;">

    {{-- ✅ Header with Hamburger --}}
    <header class="anim-fade bg-gradient-to-br from-blue-500 to-blue-700 sticky top-0 z-10 shadow-lg page-header">
        <div class="flex items-center justify-between px-5 py-4 md:px-8">
            <div class="flex items-center gap-3">
                <button @click="mobileMenuOpen = true" class="lg:hidden p-1.5 rounded-lg hover:bg-white/20 transition-colors text-white">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M4 6h16M4 12h16M4 18h16"/>
                    </svg>
                </button>
                <h1 class="text-white font-bold text-lg md:text-xl">Approved Logs</h1>
            </div>
            <x-hr-notif />
        </div>
    </header>

    <div class="page-content space-y-4">

        {{-- ── STAT CARDS ── --}}
        <div class="stat-grid">
            <div class="stat-card" style="background:#dcfce7;border-color:#bbf7d0;">
                <div style="font-size:11px;font-weight:700;color:#15803d;text-transform:uppercase;letter-spacing:.5px;margin-bottom:4px;">Approved</div>
                <div style="font-size:28px;font-weight:800;color:#15803d;line-height:1;">{{ $approvedCount ?? 0 }}</div>
                <div style="font-size:10px;color:#166534;margin-top:4px;">{{ now()->format('F Y') }}</div>
            </div>
            <div class="stat-card" style="background:#fee2e2;border-color:#fecaca;">
                <div style="font-size:11px;font-weight:700;color:#b91c1c;text-transform:uppercase;letter-spacing:.5px;margin-bottom:4px;">Rejected</div>
                <div style="font-size:28px;font-weight:800;color:#b91c1c;line-height:1;">{{ $rejectedCount ?? 0 }}</div>
                <div style="font-size:10px;color:#991b1b;margin-top:4px;">With written reason</div>
            </div>
            <div class="stat-card" style="background:#dbeafe;border-color:#bfdbfe;">
                <div style="font-size:11px;font-weight:700;color:#1d4ed8;text-transform:uppercase;letter-spacing:.5px;margin-bottom:4px;">Leave Requests</div>
                <div style="font-size:28px;font-weight:800;color:#1d4ed8;line-height:1;">{{ ($requests ?? collect())->where('type','leave')->count() }}</div>
                <div style="font-size:10px;color:#1e40af;margin-top:4px;">{{ now()->format('F Y') }}</div>
            </div>
            <div class="stat-card" style="background:#ede9fe;border-color:#ddd6fe;">
                <div style="font-size:11px;font-weight:700;color:#6d28d9;text-transform:uppercase;letter-spacing:.5px;margin-bottom:4px;">OT & Shift</div>
                <div style="font-size:28px;font-weight:800;color:#6d28d9;line-height:1;">{{ ($requests ?? collect())->whereIn('type',['overtime','shift'])->count() }}</div>
                <div style="font-size:10px;color:#5b21b6;margin-top:4px;">Requests</div>
            </div>
        </div>

        {{-- ── TABLE / CARDS CONTAINER ── --}}
        <div class="bg-white rounded-2xl border border-gray-200" style="box-shadow:0 2px 12px rgba(0,0,0,0.06);">

            {{-- Toolbar --}}
            <form method="GET" action="{{ route('hr.requests.approved') }}" id="filterForm" class="toolbar-wrap">
                <div class="relative w-full" style="max-width:100%;">
                    <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                    </svg>
                    <input type="text" name="search" placeholder="Search…"
                           value="{{ $search ?? '' }}"
                           class="search-input"
                           onchange="document.getElementById('filterForm').submit()"/>
                </div>
                <div class="filter-row">
                    <div class="fsel-wrap filter-item">
                        <select name="status" class="fsel" onchange="document.getElementById('filterForm').submit()">
                            <option value="all"                {{ ($filterStatus ?? 'all') === 'all'               ? 'selected' : '' }}>All Status</option>
                            <option value="approved"           {{ ($filterStatus ?? '') === 'approved'             ? 'selected' : '' }}>Approved</option>
                            <option value="rejected"           {{ ($filterStatus ?? '') === 'rejected'             ? 'selected' : '' }}>Rejected</option>
                            <option value="supervisor_approved"{{ ($filterStatus ?? '') === 'supervisor_approved'  ? 'selected' : '' }}>Forwarded</option>
                        </select>
                    </div>
                    <div class="fsel-wrap filter-item">
                        <select name="type" class="fsel" onchange="document.getElementById('filterForm').submit()">
                            <option value="all"        {{ ($filterType ?? 'all') === 'all'       ? 'selected' : '' }}>All Types</option>
                            <option value="leave"      {{ ($filterType ?? '') === 'leave'      ? 'selected' : '' }}>Leave</option>
                            <option value="overtime"   {{ ($filterType ?? '') === 'overtime'   ? 'selected' : '' }}>Overtime</option>
                            <option value="shift"      {{ ($filterType ?? '') === 'shift'      ? 'selected' : '' }}>Shift</option>
                            <option value="adjustment" {{ ($filterType ?? '') === 'adjustment' ? 'selected' : '' }}>Adjustment</option>
                        </select>
                    </div>
                    <div class="fsel-wrap filter-item">
                        <select name="department" class="fsel" onchange="document.getElementById('filterForm').submit()">
                            <option value="">All Departments</option>
                            @foreach($departments ?? [] as $d)
                                <option value="{{ $d->id }}" {{ ($filterDept ?? '') == $d->id ? 'selected' : '' }}>{{ $d->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
            </form>

            {{-- ── DESKTOP TABLE ── --}}
            <div class="desktop-table overflow-x-auto">
                <table class="tbl">
                    <thead>
                        <tr>
                            <th>Ref #</th>
                            <th>Employee</th>
                            <th>Type</th>
                            <th>Date Filed</th>
                            <th>Duration</th>
                            <th>Days/Hrs</th>
                            <th>Approver</th>
                            <th>Processed On</th>
                            <th>Status</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($requests ?? [] as $req)
                        @php
                            $empName     = trim(optional($req->employee)->fname . ' ' . optional($req->employee)->lname) ?: '—';
                            $empDept     = optional(optional($req->employee)->department)->name ?? '—';
                            $typeLabel   = match($req->type) {
                                'leave'      => 'Leave Request',
                                'overtime'   => 'Overtime',
                                'shift'      => 'Shift Arrangement',
                                'adjustment' => 'Attendance Adjustment',
                                default      => '—',
                            };
                            $subType = match($req->type) {
                                'leave'      => optional($req->leaveType)->name ?? '—',
                                'overtime'   => ($req->requested_hours ?? '?') . 'h OT',
                                'shift'      => optional($req->current_shift)->name . ' → ' . optional($req->requested_shift)->name,
                                'adjustment' => 'Was: ' . ($req->original_clock_in ? \Carbon\Carbon::parse($req->original_clock_in)->format('g:i A') : '—') . ' – ' . ($req->original_clock_out ? \Carbon\Carbon::parse($req->original_clock_out)->format('g:i A') : '—'),
                                default      => '—',
                            };
                            $fromDate    = match($req->type) {
                                'leave'      => optional($req->start_date)->format('m/d/Y'),
                                'overtime'   => optional($req->ot_date)->format('m/d/Y'),
                                'shift'      => optional($req->effective_from)->format('m/d/Y'),
                                'adjustment' => $req->attendance_date ? \Carbon\Carbon::parse($req->attendance_date)->format('m/d/Y') : null,
                                default      => null,
                            };
                            $toDate      = match($req->type) {
                                'leave'      => optional($req->end_date)->format('m/d/Y'),
                                'overtime'   => optional($req->ot_date)->format('m/d/Y'),
                                'shift'      => optional($req->effective_until)->format('m/d/Y'),
                                'adjustment' => null,
                                default      => null,
                            };
                            $approverName = $req->type === 'leave'
                                ? trim(optional($req->approver)->fname . ' ' . optional($req->approver)->lname)
                                : (in_array($req->type, ['overtime','shift','adjustment']) && $req->approved_by
                                    ? trim(optional(\App\Models\Employee::find($req->approved_by))->fname . ' ' . optional(\App\Models\Employee::find($req->approved_by))->lname)
                                    : '—');
                            $rejReason   = $req->rejection_reason ?? '';
                            $processedOn = optional($req->approved_at)->format('m/d/Y') ?? '';
                            $durationDisplay = ($fromDate && $toDate && $fromDate !== $toDate) ? $fromDate . ' – ' . $toDate : ($fromDate ?: '—');
                            $typeBadge = match($req->type) {
                                'leave'      => 'badge-leave',
                                'overtime'   => 'badge-ot',
                                'shift'      => 'badge-shift',
                                'adjustment' => 'badge-adjustment',
                                default      => '',
                            };
                            $daysHours = match($req->type) {
                                'leave'      => ($req->total_days ?? '—') . 'd',
                                'overtime'   => ($req->requested_hours ?? '—') . 'h',
                                'adjustment' => \Carbon\Carbon::parse($req->requested_clock_in)->format('g:i A') . ' – ' . ($req->requested_clock_out ? \Carbon\Carbon::parse($req->requested_clock_out)->format('g:i A') : 'N/A'),
                                default      => '—',
                            };
                            $jsRef        = addslashes($req->ref_no ?? '');
                            $jsFiled      = optional($req->created_at)->format('F j, Y') ?? '—';
                            $jsReqType    = addslashes($typeLabel);
                            $jsSubDetail  = addslashes($subType);
                            $jsDuration   = addslashes($durationDisplay);
                            $jsReason     = addslashes($req->reason ?? '—');
                            $jsRejReason  = addslashes($rejReason);
                            $jsHrNotes    = addslashes($req->hr_notes ?? '');
                            $jsApprover   = addslashes($approverName);
                            $jsApprovedAt = $processedOn;
                            $jsStatus     = $req->status;
                            $jsDept       = addslashes($empDept . ' — ' . $empName);
                        @endphp
                        <tr>
                            <td class="font-semibold text-gray-700">{{ $req->ref_no ?? '—' }}</td>
                            <td class="text-gray-800">{{ $empName }}<br><span class="text-gray-400 text-xs">{{ $empDept }}</span></td>
                            <td><span class="badge {{ $typeBadge }}">{{ $typeLabel }}</span></td>
                            <td class="text-gray-500 text-xs">{{ optional($req->created_at)->format('m/d/Y') }}</td>
                            <td class="text-gray-500 text-xs">{{ $durationDisplay }}</td>
                            <td class="font-semibold">{{ $daysHours }}</td>
                            <td>
                                @if($approverName && $approverName !== '—')
                                    <span class="approver-chip">{{ $approverName }}</span>
                                @else
                                    <span class="text-gray-400 text-xs">—</span>
                                @endif
                            </td>
                            <td>{{ $processedOn ?: '—' }}</td>
                            <td>
                                @if($req->status === 'approved')
                                    <span class="badge badge-approved">Approved</span>
                                @elseif($req->status === 'rejected')
                                    <span class="badge badge-rejected">Rejected</span>
                                @elseif($req->status === 'cancelled')
                                    <span class="badge" style="background:#f3f4f6;color:#6b7280;">Cancelled</span>
                                @elseif($req->status === 'supervisor_approved')
                                    <span class="badge" style="background:#dbeafe;color:#1d4ed8;">Forwarded</span>
                                @endif
                            </td>
                            <td>
                                <button class="btn-view"
                                    onclick="openViewModal(
                                        '{{ $jsRef }}','{{ $jsFiled }}','{{ $jsReqType }}',
                                        '{{ $jsSubDetail }}','{{ $jsDuration }}','{{ $jsReason }}',
                                        '{{ $jsRejReason }}','{{ $jsHrNotes }}',
                                        '{{ $jsApprover }}','{{ $jsApprovedAt }}','{{ $jsStatus }}',
                                        '{{ $jsDept }}'
                                    )">View</button>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="10" class="text-center text-gray-400 py-10">No approved or rejected requests found.</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{-- ── MOBILE CARDS ── --}}
            <div class="mobile-cards p-3">
                @forelse($requests ?? [] as $req)
                @php
                    $empName     = trim(optional($req->employee)->fname . ' ' . optional($req->employee)->lname) ?: '—';
                    $empDept     = optional(optional($req->employee)->department)->name ?? '—';
                    $typeLabel   = match($req->type) {
                        'leave'      => 'Leave Request',
                        'overtime'   => 'Overtime',
                        'shift'      => 'Shift Arrangement',
                        'adjustment' => 'Attendance Adjustment',
                        default      => '—',
                    };
                    $subType = match($req->type) {
                        'leave'      => optional($req->leaveType)->name ?? '—',
                        'overtime'   => ($req->requested_hours ?? '?') . 'h OT',
                        'shift'      => optional($req->current_shift)->name . ' → ' . optional($req->requested_shift)->name,
                        'adjustment' => 'Was: ' . ($req->original_clock_in ? \Carbon\Carbon::parse($req->original_clock_in)->format('g:i A') : '—') . ' – ' . ($req->original_clock_out ? \Carbon\Carbon::parse($req->original_clock_out)->format('g:i A') : '—'),
                        default      => '—',
                    };
                    $fromDate    = match($req->type) {
                        'leave'      => optional($req->start_date)->format('m/d/Y'),
                        'overtime'   => optional($req->ot_date)->format('m/d/Y'),
                        'shift'      => optional($req->effective_from)->format('m/d/Y'),
                        'adjustment' => $req->attendance_date ? \Carbon\Carbon::parse($req->attendance_date)->format('m/d/Y') : null,
                        default      => null,
                    };
                    $toDate      = match($req->type) {
                        'leave'      => optional($req->end_date)->format('m/d/Y'),
                        'overtime'   => optional($req->ot_date)->format('m/d/Y'),
                        'shift'      => optional($req->effective_until)->format('m/d/Y'),
                        'adjustment' => null,
                        default      => null,
                    };
                    $approverName = $req->type === 'leave'
                        ? trim(optional($req->approver)->fname . ' ' . optional($req->approver)->lname)
                        : (in_array($req->type, ['overtime','shift','adjustment']) && $req->approved_by
                            ? trim(optional(\App\Models\Employee::find($req->approved_by))->fname . ' ' . optional(\App\Models\Employee::find($req->approved_by))->lname)
                            : '—');
                    $processedOn = optional($req->approved_at)->format('m/d/Y') ?? '';
                    $durationDisplay = ($fromDate && $toDate && $fromDate !== $toDate) ? $fromDate . ' – ' . $toDate : ($fromDate ?: '—');
                    $typeBadge = match($req->type) {
                        'leave'      => 'badge-leave',
                        'overtime'   => 'badge-ot',
                        'shift'      => 'badge-shift',
                        'adjustment' => 'badge-adjustment',
                        default      => '',
                    };
                    $daysHours = match($req->type) {
                        'leave'      => ($req->total_days ?? '—') . 'd',
                        'overtime'   => ($req->requested_hours ?? '—') . 'h',
                        'adjustment' => \Carbon\Carbon::parse($req->requested_clock_in)->format('g:i A') . ' – ' . ($req->requested_clock_out ? \Carbon\Carbon::parse($req->requested_clock_out)->format('g:i A') : 'N/A'),
                        default      => '—',
                    };
                    $jsRef        = addslashes($req->ref_no ?? '');
                    $jsFiled      = optional($req->created_at)->format('F j, Y') ?? '—';
                    $jsReqType    = addslashes($typeLabel);
                    $jsSubDetail  = addslashes($subType);
                    $jsDuration   = addslashes($durationDisplay);
                    $jsReason     = addslashes($req->reason ?? '—');
                    $jsRejReason  = addslashes($req->rejection_reason ?? '');
                    $jsHrNotes    = addslashes($req->hr_notes ?? '');
                    $jsApprover   = addslashes($approverName);
                    $jsApprovedAt = $processedOn;
                    $jsStatus     = $req->status;
                    $jsDept       = addslashes($empDept . ' — ' . $empName);
                @endphp
                <div class="req-card">
                    <div class="req-card-header">
                        <div>
                            <div class="req-card-ref">{{ $req->ref_no ?? '—' }}</div>
                            <div class="req-card-filed">Filed on {{ optional($req->created_at)->format('M j, Y') ?? '—' }}</div>
                        </div>
                        <div>
                            @if($req->status === 'approved')
                                <span class="badge badge-approved">Approved</span>
                            @elseif($req->status === 'rejected')
                                <span class="badge badge-rejected">Rejected</span>
                            @elseif($req->status === 'cancelled')
                                <span class="badge" style="background:#f3f4f6;color:#6b7280;">Cancelled</span>
                            @elseif($req->status === 'supervisor_approved')
                                <span class="badge" style="background:#dbeafe;color:#1d4ed8;">Forwarded</span>
                            @endif
                        </div>
                    </div>

                    <div class="req-card-grid">
                        <div>
                            <div class="req-card-label">Employee</div>
                            <div class="req-card-value">{{ $empName }}</div>
                            <div class="req-card-value" style="font-size:10px;color:#9ca3af;">{{ $empDept }}</div>
                        </div>
                        <div>
                            <div class="req-card-label">Type</div>
                            <span class="badge {{ $typeBadge }}" style="font-size:10px;">{{ $typeLabel }}</span>
                        </div>
                        <div>
                            <div class="req-card-label">Duration</div>
                            <div class="req-card-value">{{ $durationDisplay }}</div>
                        </div>
                        <div>
                            <div class="req-card-label">Days / Hours</div>
                            <div class="req-card-value" style="font-weight:700;">{{ $daysHours }}</div>
                        </div>
                    </div>

                    <div class="req-card-footer">
                        <div>
                            @if($approverName && $approverName !== '—')
                                <span class="approver-chip">{{ $approverName }}</span>
                            @else
                                <span style="font-size:11px;color:#9ca3af;">No approver yet</span>
                            @endif
                        </div>
                        <button class="btn-view"
                            onclick="openViewModal(
                                '{{ $jsRef }}','{{ $jsFiled }}','{{ $jsReqType }}',
                                '{{ $jsSubDetail }}','{{ $jsDuration }}','{{ $jsReason }}',
                                '{{ $jsRejReason }}','{{ $jsHrNotes }}',
                                '{{ $jsApprover }}','{{ $jsApprovedAt }}','{{ $jsStatus }}',
                                '{{ $jsDept }}'
                            )">View</button>
                    </div>
                </div>
                @empty
                <div style="text-align:center;padding:40px;color:#9ca3af;font-size:13px;">No records found.</div>
                @endforelse
            </div>

        </div>{{-- end card --}}
    </div>{{-- end page-content --}}
</div>{{-- end main --}}

{{-- ═══════════ VIEW MODAL ═══════════ --}}
<div id="viewModal" style="display:none;" class="modal-overlay" onclick="if(event.target===this)closeViewModal()">
    <div class="modal-box modal-slide-up">
        <span class="modal-drag-handle"></span>

        <button onclick="closeViewModal()" style="position:absolute;top:16px;right:16px;width:34px;height:34px;border-radius:50%;border:2px solid #d1d5db;background:none;cursor:pointer;display:flex;align-items:center;justify-content:center;color:#9ca3af;">
            <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"/>
            </svg>
        </button>

        <div class="mb-4" style="padding-right:40px;">
            <div style="display:flex;align-items:center;gap:10px;flex-wrap:wrap;margin-bottom:4px;">
                <div id="mRef" style="font-size:18px;font-weight:900;color:#111827;line-height:1.1;"></div>
                <div id="mStatusBadge"></div>
            </div>
            <div id="mSub" style="font-size:12px;color:#374151;"></div>
            <div id="mFiled" style="font-size:11px;color:#9ca3af;margin-top:2px;"></div>
        </div>

        <div class="grid grid-cols-2 gap-3 mb-3">
            <div>
                <div class="modal-label">Request Type</div>
                <div id="mReqType" class="modal-field"></div>
            </div>
            <div>
                <div class="modal-label">Details</div>
                <div id="mLeaveType" class="modal-field"></div>
            </div>
        </div>
        <div class="grid grid-cols-2 gap-3 mb-3">
            <div>
                <div class="modal-label">From</div>
                <div id="mFrom" class="modal-field"></div>
            </div>
            <div>
                <div class="modal-label">To</div>
                <div id="mTo" class="modal-field"></div>
            </div>
        </div>
        <div class="mb-3">
            <div class="modal-label">Reason/Remarks</div>
            <div id="mReason" class="modal-field"></div>
        </div>

        <div id="mRejBlock" style="display:none;margin-bottom:12px;">
            <div class="modal-label" style="color:#dc2626;">Rejection Reason</div>
            <div id="mRejReason" class="modal-field" style="border-left:3px solid #ef4444;"></div>
        </div>

        <div id="mNotesBlock" style="display:none;margin-bottom:12px;">
            <div class="modal-label">HR Notes</div>
            <div id="mHrNotes" class="modal-field"></div>
        </div>

        <div class="mb-4">
            <div class="modal-label mb-2">Approval Trail</div>
            <div id="mTrail"></div>
        </div>

        <div class="flex justify-end">
            <button onclick="closeViewModal()" class="modal-confirm-btn">Confirm</button>
        </div>
    </div>
</div>

<script>
function openViewModal(ref, filed, reqType, subDetail, duration, reason, rejReason, hrNotes, approver, approvedAt, status, dept) {
    document.getElementById('mRef').textContent       = ref || '—';
    document.getElementById('mSub').textContent       = dept || (reqType + ' · ' + subDetail);
    document.getElementById('mFiled').textContent     = 'Filed on ' + (filed || '—');
    document.getElementById('mReqType').textContent   = reqType || '—';
    document.getElementById('mLeaveType').textContent = subDetail || '—';
    document.getElementById('mReason').textContent    = reason || '—';

    var parts = (duration || '—').split(' – ');
    document.getElementById('mFrom').textContent = parts[0] ? parts[0].trim() : (duration || '—');
    document.getElementById('mTo').textContent   = parts[1] ? parts[1].trim() : '—';

    var badgeHtml = '';
    if (status === 'approved') {
        badgeHtml = '<span style="display:inline-block;padding:2px 10px;border-radius:20px;font-size:11px;font-weight:700;background:#dcfce7;color:#16a34a;">Approved</span>';
    } else if (status === 'rejected') {
        badgeHtml = '<span style="display:inline-block;padding:2px 10px;border-radius:20px;font-size:11px;font-weight:700;background:#fee2e2;color:#dc2626;">Rejected</span>';
    } else if (status === 'cancelled') {
        badgeHtml = '<span style="display:inline-block;padding:2px 10px;border-radius:20px;font-size:11px;font-weight:700;background:#f3f4f6;color:#6b7280;">Cancelled</span>';
    } else if (status === 'supervisor_approved') {
        badgeHtml = '<span style="display:inline-block;padding:2px 10px;border-radius:20px;font-size:11px;font-weight:700;background:#dbeafe;color:#1d4ed8;">Forwarded to HR</span>';
    }
    document.getElementById('mStatusBadge').innerHTML = badgeHtml;

    if (rejReason && rejReason.trim() !== '' && rejReason !== '—') {
        document.getElementById('mRejBlock').style.display = 'block';
        document.getElementById('mRejReason').textContent  = rejReason;
    } else {
        document.getElementById('mRejBlock').style.display = 'none';
    }

    if (hrNotes && hrNotes.trim() !== '' && hrNotes !== '—') {
        document.getElementById('mNotesBlock').style.display = 'block';
        document.getElementById('mHrNotes').textContent      = hrNotes;
    } else {
        document.getElementById('mNotesBlock').style.display = 'none';
    }

    var isApproved = status === 'approved';
    var trailColor = isApproved ? '#16a34a' : '#dc2626';
    var trailBg    = isApproved ? '#dcfce7'  : '#fee2e2';
    var actionWord = isApproved ? 'Approved' : (status === 'rejected' ? 'Rejected' : 'Processed');
    var icon       = isApproved
        ? '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/>'
        : '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>';

    var html = '';
    html += '<div class="trail-item" style="border-left-color:#6b7280;margin-bottom:6px;">';
    html += '<div style="width:28px;height:28px;border-radius:50%;background:#e5e7eb;display:flex;align-items:center;justify-content:center;flex-shrink:0;">';
    html += '<svg width="12" height="12" fill="none" stroke="#6b7280" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>';
    html += '</div><div>';
    html += '<div style="font-size:12px;font-weight:700;color:#111827;">Filed by Employee</div>';
    html += '<div style="font-size:10px;color:#9ca3af;">' + (filed || '—') + '</div>';
    html += '</div></div>';

    html += '<div class="trail-item" style="border-left-color:#3b82f6;margin-bottom:6px;">';
    html += '<div style="width:28px;height:28px;border-radius:50%;background:#dbeafe;display:flex;align-items:center;justify-content:center;flex-shrink:0;">';
    html += '<svg width="12" height="12" fill="none" stroke="#1d4ed8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>';
    html += '</div><div>';
    html += '<div style="font-size:12px;font-weight:700;color:#111827;">Supervisor Approved</div>';
    html += '<div style="font-size:10px;color:#9ca3af;">Forwarded to HR Manager</div>';
    html += '</div></div>';

    if (approver && approver.trim() !== '' && approver !== '—') {
        html += '<div class="trail-item" style="border-left-color:' + trailColor + ';margin-bottom:6px;">';
        html += '<div style="width:28px;height:28px;border-radius:50%;background:' + trailBg + ';display:flex;align-items:center;justify-content:center;flex-shrink:0;">';
        html += '<svg width="12" height="12" fill="none" stroke="' + trailColor + '" viewBox="0 0 24 24">' + icon + '</svg>';
        html += '</div><div>';
        html += '<div style="font-size:12px;font-weight:700;color:#111827;">HR Manager ' + actionWord + '</div>';
        html += '<div style="font-size:11px;color:#6b7280;margin-top:2px;">HR Manager - ' + approver + '</div>';
        html += '<div style="font-size:10px;color:#9ca3af;">' + actionWord + ' on ' + (approvedAt || '—') + '</div>';
        html += '</div></div>';
    }

    document.getElementById('mTrail').innerHTML = html;

    var modal = document.getElementById('viewModal');
    modal.style.display = 'flex';
    modal.style.alignItems = window.innerWidth < 640 ? 'flex-end' : 'center';
}

function closeViewModal() {
    document.getElementById('viewModal').style.display = 'none';
}

window.addEventListener('resize', function(){
    var modal = document.getElementById('viewModal');
    if (modal.style.display !== 'none') {
        modal.style.alignItems = window.innerWidth < 640 ? 'flex-end' : 'center';
    }
});

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
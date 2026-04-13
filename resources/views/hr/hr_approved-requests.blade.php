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
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <title>Approved Logs — MEDISOURCE</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet"/>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <style>
        * { font-family: 'DM Sans', sans-serif; box-sizing: border-box; }
        body { background: #f3f4f6; margin: 0; }
        .nav-item { transition: all 0.2s ease; }
        .nav-item:hover { transform: translateX(2px); }
        .submenu-item { transition: all 0.18s ease; }
        .submenu-item:hover { transform: translateX(3px); }
        .chevron-icon { transition: transform 0.3s cubic-bezier(0.4,0,0.2,1); }
        .settings-icon { transition: transform 0.5s ease; }
        .nav-item:hover .settings-icon { transform: rotate(60deg); }
        .avatar-ring { box-shadow: 0 0 0 3px rgba(59,130,246,0.2); }
        @keyframes pulseDot { 0%,100%{opacity:1;transform:scale(1)} 50%{opacity:.5;transform:scale(1.3)} }
        .stat-card { background:#fff; border-radius:14px; padding:24px 28px; border:1px solid #e5e7eb; }
        .tbl { width:100%; border-collapse:collapse; }
        .tbl th { font-size:12px; color:#6b7280; font-weight:600; padding:11px 14px; border-bottom:1.5px solid #e5e7eb; text-align:left; white-space:nowrap; }
        .tbl td { font-size:13px; color:#111827; padding:13px 14px; border-bottom:1px solid #f3f4f6; vertical-align:middle; }
        .tbl tr:last-child td { border-bottom:none; }
        .tbl tr:hover td { background:#f9fafb; }
        .badge { display:inline-block; padding:3px 11px; border-radius:20px; font-size:11.5px; font-weight:600; }
        .badge-leave    { background:#fef3c7; color:#d97706; }
        .badge-ot       { background:#fce7f3; color:#db2777; }
        .badge-shift    { background:#ede9fe; color:#7c3aed; }
        .badge-approved { background:#dcfce7; color:#16a34a; }
        .badge-rejected { background:#fee2e2; color:#dc2626; }
        .approver-chip  { display:inline-block; background:#dbeafe; color:#1d4ed8; border-radius:20px; padding:2px 10px; font-size:11.5px; font-weight:600; margin:1px; }
        .btn-view { background:#fff; border:1.5px solid #e5e7eb; border-radius:8px; padding:5px 16px; font-size:12px; font-weight:600; color:#374151; cursor:pointer; font-family:inherit; }
        .btn-view:hover { border-color:#3b82f6; color:#3b82f6; }
        .fsel { background:#fff; border:1.5px solid #e5e7eb; border-radius:8px; padding:8px 32px 8px 12px; font-size:13px; font-family:inherit; color:#374151; appearance:none; cursor:pointer; outline:none; }
        .fsel:focus { border-color:#3b82f6; }
        .search-input { background:#fff; border:1.5px solid #e5e7eb; border-radius:8px; padding:8px 12px 8px 36px; font-size:13px; font-family:inherit; color:#374151; outline:none; }
        .search-input:focus { border-color:#3b82f6; }
        .modal-overlay { position:fixed; inset:0; background:rgba(0,0,0,0.35); z-index:200; display:flex; align-items:center; justify-content:center; padding:20px; }
        .modal-box { background:#fff; border-radius:18px; width:100%; max-width:540px; padding:32px; position:relative; box-shadow:0 24px 64px rgba(0,0,0,0.18); }
        .modal-field { background:#f3f4f6; border-radius:8px; padding:10px 14px; font-size:13px; color:#374151; }
        .modal-label { font-size:13px; font-weight:600; color:#111827; margin-bottom:6px; }
        .trail-item { display:flex; gap:14px; align-items:flex-start; padding:14px 16px; background:#f9fafb; border-radius:10px; border-left:3px solid #3b82f6; margin-bottom:8px; }
        .trail-check { width:30px; height:30px; border-radius:50%; background:#3b82f6; display:flex; align-items:center; justify-content:center; flex-shrink:0; }
        .anim-fade { animation: fadeSlideDown 0.4s ease both; }
        @keyframes fadeSlideDown { from { opacity:0; transform:translateY(-12px); } to { opacity:1; transform:translateY(0); } }
    </style>
</head>
<body>

{{-- ═══════════ SIDEBAR ═══════════ --}}
@include('hr.hr_sidebar')

{{-- ═══════════ MAIN ═══════════ --}}
<div x-data="{collapsed:localStorage.getItem('sidebarCollapsed')==='true'}"
     x-init="window.addEventListener('sidebar-toggle',e=>{collapsed=e.detail.collapsed})"
     :style="collapsed?'margin-left:5rem':'margin-left:16rem'"
     style="transition:margin-left .35s cubic-bezier(.4,0,.2,1);min-height:100vh;">

    <header class="bg-gradient-to-br from-blue-500 to-blue-700 sticky top-0 z-10 shadow-lg mt-4 mx-4 rounded-2xl overflow-visible">
        <div class="flex items-center justify-between px-8 py-4">
            <h1 class="text-white font-bold text-xl">Approved Logs</h1>
            <x-hr-notif />
        </div>
    </header>

    <div class="p-8 space-y-6">

        {{-- Stat Cards --}}
        <div class="grid grid-cols-4 gap-4">
            <div class="stat-card" style="background:#dcfce7;border-color:#bbf7d0;">
                <div style="font-size:12px;font-weight:700;color:#15803d;text-transform:uppercase;letter-spacing:.5px;margin-bottom:6px;">Approved</div>
                <div style="font-size:34px;font-weight:800;color:#15803d;line-height:1;">{{ $approvedCount }}</div>
                <div style="font-size:12px;color:#166534;margin-top:6px;">{{ now()->format('F Y') }}</div>
            </div>
            <div class="stat-card" style="background:#fee2e2;border-color:#fecaca;">
                <div style="font-size:12px;font-weight:700;color:#b91c1c;text-transform:uppercase;letter-spacing:.5px;margin-bottom:6px;">Rejected</div>
                <div style="font-size:34px;font-weight:800;color:#b91c1c;line-height:1;">{{ $rejectedCount }}</div>
                <div style="font-size:12px;color:#991b1b;margin-top:6px;">With written reason</div>
            </div>
            <div class="stat-card" style="background:#dbeafe;border-color:#bfdbfe;">
                <div style="font-size:12px;font-weight:700;color:#1d4ed8;text-transform:uppercase;letter-spacing:.5px;margin-bottom:6px;">Leave Requests</div>
                <div style="font-size:34px;font-weight:800;color:#1d4ed8;line-height:1;">{{ $requests->where('type','leave')->count() }}</div>
                <div style="font-size:12px;color:#1e40af;margin-top:6px;">{{ now()->format('F Y') }}</div>
            </div>
            <div class="stat-card" style="background:#ede9fe;border-color:#ddd6fe;">
                <div style="font-size:12px;font-weight:700;color:#6d28d9;text-transform:uppercase;letter-spacing:.5px;margin-bottom:6px;">OT & Shift Requests</div>
                <div style="font-size:34px;font-weight:800;color:#6d28d9;line-height:1;">{{ $requests->whereIn('type',['overtime','shift'])->count() }}</div>
                <div style="font-size:12px;color:#5b21b6;margin-top:6px;">{{ now()->format('F Y') }}</div>
            </div>
        </div>

        {{-- Table Card --}}
        <div class="bg-white rounded-2xl border border-gray-200" style="box-shadow:0 2px 12px rgba(0,0,0,0.06);">

            {{-- Toolbar --}}
            <form method="GET" action="{{ route('hr.requests.approved') }}" class="flex items-center gap-3 px-6 py-4 border-b border-gray-100">
                <div class="relative" style="min-width:220px;">
                    <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                    <input type="text" name="search" value="{{ $search ?? '' }}" placeholder="Search" class="search-input w-full" onchange="this.form.submit()"/>
                </div>
                <div class="flex items-center gap-3 ml-auto">
                    <select name="status" class="fsel" onchange="this.form.submit()">
                        <option value="all" {{ ($filterStatus ?? 'all') === 'all' ? 'selected' : '' }}>Status</option>
                        <option value="approved" {{ ($filterStatus ?? '') === 'approved' ? 'selected' : '' }}>Approved</option>
                        <option value="rejected" {{ ($filterStatus ?? '') === 'rejected' ? 'selected' : '' }}>Rejected</option>
                    </select>
                    <select name="type" class="fsel" onchange="this.form.submit()">
                        <option value="all" {{ ($filterType ?? 'all') === 'all' ? 'selected' : '' }}>All Types</option>
                        <option value="leave" {{ ($filterType ?? '') === 'leave' ? 'selected' : '' }}>Leave Request</option>
                        <option value="overtime" {{ ($filterType ?? '') === 'overtime' ? 'selected' : '' }}>Overtime</option>
                        <option value="shift" {{ ($filterType ?? '') === 'shift' ? 'selected' : '' }}>Shift Arrangement</option>
                    </select>
                    <select name="department" class="fsel" onchange="this.form.submit()">
                        <option value="">All Departments</option>
                        @foreach($departments as $d)
                            <option value="{{ $d->id }}" {{ ($filterDept ?? '') == $d->id ? 'selected' : '' }}>{{ $d->name }}</option>
                        @endforeach
                    </select>
                </div>
            </form>

            {{-- Table --}}
            <div class="overflow-x-auto">
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
                        @forelse($requests as $req)
                        @php
                            $empName     = trim(optional($req->employee)->fname . ' ' . optional($req->employee)->lname);
                            $empDept     = optional(optional($req->employee)->department)->name ?? '—';
                            $typeLabel   = $req->type === 'leave' ? 'Leave Request' : ($req->type === 'overtime' ? 'Overtime' : 'Shift Arrangement');
                            $subType     = $req->type === 'leave'
                                ? (optional($req->leaveType)->name ?? '—')
                                : ($req->type === 'overtime'
                                    ? (($req->requested_hours ?? '?') . 'h OT')
                                    : (optional($req->current_shift)->name . ' → ' . optional($req->requested_shift)->name));
                            $fromDate    = $req->type === 'leave'
                                ? optional($req->start_date)->format('m/d/Y')
                                : ($req->type === 'overtime'
                                    ? optional($req->ot_date)->format('m/d/Y')
                                    : optional($req->effective_from)->format('m/d/Y'));
                            $toDate      = $req->type === 'leave'
                                ? optional($req->end_date)->format('m/d/Y')
                                : ($req->type === 'overtime'
                                    ? optional($req->ot_date)->format('m/d/Y')
                                    : optional($req->effective_until)->format('m/d/Y'));
                            $approverName = $req->type === 'leave'
                                ? trim(optional($req->approver)->fname . ' ' . optional($req->approver)->lname)
                                : ($req->approved_by ?? '');
                            $rejReason   = $req->rejection_reason ?? '';
                            $processedOn = optional($req->approved_at)->format('m/d/Y') ?? '';
                        @endphp
                        <tr>
                            <td class="font-semibold text-gray-700">{{ $req->ref_no ?? '—' }}</td>
                            <td>{{ $empName ?: '—' }}</td>
                            <td>
                                @if($req->type === 'leave')
                                    <span class="badge badge-leave">Leave Request</span>
                                @elseif($req->type === 'overtime')
                                    <span class="badge badge-ot">Overtime</span>
                                @else
                                    <span class="badge badge-shift">Shift Arrangement</span>
                                @endif
                            </td>
                            <td>{{ optional($req->created_at)->format('m/d/Y') }}</td>
                            <td class="text-gray-500 text-xs">
                                @if($req->type === 'leave')
                                    {{ optional($req->start_date)->format('m-d-Y') }} &ndash;<br>{{ optional($req->end_date)->format('m-d-Y') }}
                                @elseif($req->type === 'overtime')
                                    {{ optional($req->ot_date)->format('m-d-Y') }}
                                @else
                                    {{ optional($req->effective_from)->format('m-d-Y') }} &ndash;<br>{{ optional($req->effective_until)->format('m-d-Y') }}
                                @endif
                            </td>
                            <td class="font-semibold">
                                @if($req->type === 'leave')
                                    {{ $req->total_days ?? '—' }}d
                                @elseif($req->type === 'overtime')
                                    {{ $req->requested_hours ?? '—' }}h
                                @else
                                    —
                                @endif
                            </td>
                            <td>
                                @if($approverName)
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
                                    <span class="badge" style="background:#dbeafe;color:#1d4ed8;">Sup. Approved</span>
                                @endif
                            </td>
                            <td>
                                <button class="btn-view"
                                    data-ref="{{ e($req->ref_no ?? '') }}"
                                    data-dept="{{ e($empDept) }}"
                                    data-name="{{ e($empName) }}"
                                    data-filed="{{ optional($req->created_at)->format('M d, Y') }}"
                                    data-type="{{ e($typeLabel) }}"
                                    data-subtype="{{ e($subType) }}"
                                    data-from="{{ $fromDate }}"
                                    data-to="{{ $toDate }}"
                                    data-reason="{{ e($req->reason ?? '') }}"
                                    data-status="{{ $req->status }}"
                                    data-approver="{{ e($approverName) }}"
                                    data-processed="{{ $processedOn }}"
                                    data-rejreason="{{ e($rejReason) }}"
                                    onclick="openViewModalFromBtn(this)">View</button>
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
        </div>
    </div>
</div>

{{-- ═══════════ VIEW MODAL ═══════════ --}}
<div id="viewModal" style="display:none;" class="modal-overlay" onclick="if(event.target===this)closeViewModal()">
    <div class="modal-box">
        <button onclick="closeViewModal()" style="position:absolute;top:16px;right:16px;width:34px;height:34px;border-radius:50%;border:2px solid #d1d5db;background:none;cursor:pointer;display:flex;align-items:center;justify-content:center;color:#9ca3af;">
            <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"/></svg>
        </button>

        <div class="mb-5">
            <div id="mRef" style="font-size:26px;font-weight:900;color:#111827;line-height:1.1;"></div>
            <div id="mSub" style="font-size:13px;color:#374151;margin-top:4px;"></div>
            <div id="mFiled" style="font-size:12px;color:#9ca3af;margin-top:2px;"></div>
        </div>

        <div class="grid grid-cols-2 gap-4 mb-4">
            <div>
                <div class="modal-label">Request Type</div>
                <div id="mReqType" class="modal-field"></div>
            </div>
            <div>
                <div class="modal-label">Details</div>
                <div id="mLeaveType" class="modal-field"></div>
            </div>
        </div>
        <div class="grid grid-cols-2 gap-4 mb-4">
            <div>
                <div class="modal-label">From</div>
                <div id="mFrom" class="modal-field"></div>
            </div>
            <div>
                <div class="modal-label">To</div>
                <div id="mTo" class="modal-field"></div>
            </div>
        </div>
        <div class="mb-5">
            <div class="modal-label">Reason/Remarks</div>
            <div id="mReason" class="modal-field"></div>
        </div>

        <div class="mb-6">
            <div class="modal-label mb-3">Approval Trail</div>
            <div id="mTrail"></div>
        </div>

        <div class="flex justify-end">
            <button onclick="closeViewModal()" style="background:#3b82f6;color:#fff;border:none;border-radius:10px;padding:10px 30px;font-size:14px;font-weight:700;font-family:inherit;cursor:pointer;">Close</button>
        </div>
    </div>
</div>

<script>
function openViewModalFromBtn(btn) {
    var d = btn.dataset;
    openViewModal(d.ref, d.dept, d.name, d.filed, d.type, d.subtype, d.from, d.to, d.reason, d.status, d.approver, d.processed, d.rejreason);
}

function openViewModal(ref, dept, name, filed, reqType, subType, from, to, reason, status, approver, processedOn, rejReason) {
    document.getElementById('mRef').textContent       = ref || '—';
    document.getElementById('mSub').textContent       = (dept && dept !== '—') ? dept + ' — ' + name : name;
    document.getElementById('mFiled').textContent     = 'Filed on ' + (filed || '—');
    document.getElementById('mReqType').textContent   = reqType || '—';
    document.getElementById('mLeaveType').textContent = subType || '—';
    document.getElementById('mFrom').textContent      = from || '—';
    document.getElementById('mTo').textContent        = to || '—';
    document.getElementById('mReason').textContent    = reason || '—';

    var html = '';
    if (status === 'approved') {
        html += '<div class="trail-item">';
        html += '<div class="trail-check"><svg width="14" height="14" fill="none" stroke="#fff" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg></div>';
        html += '<div>';
        html += '<div style="font-size:13px;font-weight:700;color:#111827;">Request Approved</div>';
        if (approver) html += '<div style="font-size:12px;color:#6b7280;margin-top:2px;">By: ' + approver + '</div>';
        if (processedOn) html += '<div style="font-size:11px;color:#9ca3af;">Processed on ' + processedOn + '</div>';
        html += '</div></div>';
    } else if (status === 'rejected') {
        html += '<div class="trail-item" style="border-left-color:#ef4444;">';
        html += '<div class="trail-check" style="background:#ef4444;"><svg width="14" height="14" fill="none" stroke="#fff" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"/></svg></div>';
        html += '<div>';
        html += '<div style="font-size:13px;font-weight:700;color:#111827;">Request Rejected</div>';
        if (approver) html += '<div style="font-size:12px;color:#6b7280;margin-top:2px;">By: ' + approver + '</div>';
        if (processedOn) html += '<div style="font-size:11px;color:#9ca3af;">Processed on ' + processedOn + '</div>';
        if (rejReason) html += '<div style="font-size:12px;color:#dc2626;margin-top:4px;">Reason: ' + rejReason + '</div>';
        html += '</div></div>';
    } else if (status === 'cancelled') {
        html += '<div style="font-size:13px;color:#6b7280;padding:12px;">This request was cancelled by the employee.</div>';
    } else if (status === 'supervisor_approved') {
        html += '<div class="trail-item">';
        html += '<div class="trail-check"><svg width="14" height="14" fill="none" stroke="#fff" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg></div>';
        html += '<div><div style="font-size:13px;font-weight:700;color:#111827;">Supervisor Approved — Awaiting HR</div>';
        if (approver) html += '<div style="font-size:12px;color:#6b7280;margin-top:2px;">By: ' + approver + '</div>';
        html += '</div></div>';
    } else {
        html += '<div style="font-size:13px;color:#6b7280;padding:12px;">Status: ' + status + '</div>';
    }
    document.getElementById('mTrail').innerHTML = html;
    document.getElementById('viewModal').style.display = 'flex';
}

function closeViewModal() {
    document.getElementById('viewModal').style.display = 'none';
}

function approvedLogs() { return {}; }
</script>
</body>
</html>

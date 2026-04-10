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

    $attendanceRoutes = ['supervisor.attendance.reports', 'supervisor.attendance.employee', 'supervisor.attendance.shift', 'supervisor.attendance.leave', 'supervisor.shift.scheduling', 'supervisor.leave.management'];
    $employeeRoutes   = ['supervisor.employees.directory'];
    $payrollRoutes    = ['supervisor.payroll', 'supervisor.payslips', 'supervisor.contributions'];
    $requestRoutes    = ['supervisor.requests.pending', 'supervisor.requests.approved'];
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
        .modal-box { background:#fff; border-radius:18px; width:600px; max-width:95vw; padding:32px; position:relative; box-shadow:0 24px 64px rgba(0,0,0,0.18); max-height:90vh; overflow-y:auto; }
        .modal-field { background:#f3f4f6; border-radius:8px; padding:10px 14px; font-size:13px; color:#374151; }
        .modal-label { font-size:13px; font-weight:600; color:#111827; margin-bottom:6px; }
        .trail-item { display:flex; gap:14px; align-items:flex-start; padding:14px 16px; background:#f9fafb; border-radius:10px; border-left:3px solid #3b82f6; margin-bottom:8px; }
        .trail-check { width:30px; height:30px; border-radius:50%; background:#3b82f6; display:flex; align-items:center; justify-content:center; flex-shrink:0; }
    </style>
</head>
<body>
<div class="flex min-h-screen" x-data="approvedLogs()">

    {{-- ═══════════ SIDEBAR ═══════════ --}}
    @include('supervisor.supervisor_sidebar')

    {{-- ═══════════ MAIN ═══════════ --}}
    <div class="flex-1 flex flex-col" style="margin-left:256px;">

      {{-- Top bar --}}
<header class="bg-gradient-to-br from-blue-500 to-blue-700 sticky top-0 z-10 shadow-lg mt-4 mx-4 rounded-2xl overflow-visible">
    <div class="flex items-center justify-between px-8 py-4">
        <h1 class="text-white font-bold text-xl">Approved Logs</h1>
        <x-supervisor-notif />
    </div>
</header>

        <div class="p-8 space-y-6">

            {{-- Stat Cards --}}
            <div class="grid grid-cols-4 gap-4" style="margin-bottom:28px;">
                <div class="stat-card" style="background:#dcfce7;border-color:#bbf7d0;">
                    <div style="font-size:12px;font-weight:700;color:#15803d;text-transform:uppercase;letter-spacing:.5px;margin-bottom:6px;">Approved</div>
                    <div style="font-size:34px;font-weight:800;color:#15803d;line-height:1;">{{ $approvedCount ?? 0 }}</div>
                    <div style="font-size:12px;color:#166534;margin-top:6px;">{{ now()->format('F Y') }}</div>
                </div>
                <div class="stat-card" style="background:#fee2e2;border-color:#fecaca;">
                    <div style="font-size:12px;font-weight:700;color:#b91c1c;text-transform:uppercase;letter-spacing:.5px;margin-bottom:6px;">Rejected</div>
                    <div style="font-size:34px;font-weight:800;color:#b91c1c;line-height:1;">{{ $rejectedCount ?? 0 }}</div>
                    <div style="font-size:12px;color:#991b1b;margin-top:6px;">With written reason</div>
                </div>
                <div class="stat-card" style="background:#dbeafe;border-color:#bfdbfe;">
                    <div style="font-size:12px;font-weight:700;color:#1d4ed8;text-transform:uppercase;letter-spacing:.5px;margin-bottom:6px;">Leave Requests</div>
                    <div style="font-size:34px;font-weight:800;color:#1d4ed8;line-height:1;">{{ ($requests ?? collect())->where('type','leave')->count() }}</div>
                    <div style="font-size:12px;color:#1e40af;margin-top:6px;">{{ now()->format('F Y') }}</div>
                </div>
                <div class="stat-card" style="background:#ede9fe;border-color:#ddd6fe;">
                    <div style="font-size:12px;font-weight:700;color:#6d28d9;text-transform:uppercase;letter-spacing:.5px;margin-bottom:6px;">OT & Shift Requests</div>
                    <div style="font-size:34px;font-weight:800;color:#6d28d9;line-height:1;">{{ ($requests ?? collect())->whereIn('type',['overtime','shift'])->count() }}</div>
                    <div style="font-size:12px;color:#5b21b6;margin-top:6px;">{{ now()->format('F Y') }}</div>
                </div>
            </div>

            {{-- Table Card --}}
            <div class="bg-white rounded-2xl border border-gray-200" style="box-shadow:0 2px 12px rgba(0,0,0,0.06);">

                {{-- Toolbar --}}
                <form method="GET" action="{{ route('supervisor.requests.approved') }}" id="filterForm"
                      class="flex items-center gap-3 px-6 py-4 border-b border-gray-100">
                    <div class="relative" style="min-width:220px;">
                        <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                        <input type="text" name="search" placeholder="Search employee…"
                               value="{{ $search ?? '' }}"
                               class="search-input w-full"
                               onchange="document.getElementById('filterForm').submit()"/>
                    </div>
                    <div class="flex items-center gap-3 ml-auto">
                        <select name="status" class="fsel" onchange="document.getElementById('filterForm').submit()">
                            <option value="all"      {{ ($filterStatus ?? 'all') === 'all'      ? 'selected' : '' }}>All Status</option>
                            <option value="approved" {{ ($filterStatus ?? '') === 'approved'    ? 'selected' : '' }}>Approved</option>
                            <option value="rejected" {{ ($filterStatus ?? '') === 'rejected'    ? 'selected' : '' }}>Rejected</option>
                        </select>
                        <select name="type" class="fsel" onchange="document.getElementById('filterForm').submit()">
                            <option value="all"      {{ ($filterType ?? 'all') === 'all'      ? 'selected' : '' }}>All Types</option>
                            <option value="leave"    {{ ($filterType ?? '') === 'leave'       ? 'selected' : '' }}>Leave Request</option>
                            <option value="overtime" {{ ($filterType ?? '') === 'overtime'    ? 'selected' : '' }}>Overtime</option>
                            <option value="shift"    {{ ($filterType ?? '') === 'shift'       ? 'selected' : '' }}>Shift Arrangement</option>
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
                                <th>Days</th>
                                <th>Approvers</th>
                                <th>Processed On</th>
                                <th>Status</th>
                                <th></th>
                            </tr>
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
                            <tr>
                                <td class="font-semibold text-gray-700">{{ $req->ref_no }}</td>
                                <td>
                                    <div style="font-weight:600;font-size:13px;color:#111827;">{{ $empName }}</div>
                                    <div style="font-size:11.5px;color:#6b7280;">{{ $deptName }}</div>
                                </td>
                                <td><span class="badge {{ $typeBadge }}">{{ $typeLabel }}</span></td>
                                <td style="font-size:12px;color:#6b7280;">{{ $req->created_at ? $req->created_at->format('m/d/Y') : '—' }}</td>
                                <td style="font-size:12px;color:#6b7280;">{{ $duration }}</td>
                                <td style="font-weight:600;">{{ $days }}</td>
                                <td>
                                    @if($approverName)
                                        <span class="approver-chip">{{ $approverName }}</span>
                                    @else
                                        <span style="font-size:12px;color:#9ca3af;">—</span>
                                    @endif
                                </td>
                                <td style="font-size:12px;color:#6b7280;">{{ $req->approved_at ? \Carbon\Carbon::parse($req->approved_at)->format('m/d/Y') : '—' }}</td>
                                <td>
                                    @if($req->status === 'approved')
                                        <span class="badge badge-approved">Approved</span>
                                    @elseif($req->status === 'rejected')
                                        <span class="badge badge-rejected">Rejected</span>
                                    @elseif($req->status === 'cancelled')
                                        <span class="badge" style="background:#f3f4f6;color:#6b7280;">Cancelled</span>
                                    @elseif($req->status === 'supervisor_approved')
                                        <span class="badge" style="background:#dbeafe;color:#1d4ed8;">Forwarded to HR</span>
                                    @endif
                                </td>
                                <td>
                                    <button class="btn-view"
                                        onclick="openViewModal(
                                            '{{ $jsRef }}','{{ $jsDept }}','{{ $jsName }}',
                                            '{{ $jsFiled }}','{{ $jsReqType }}','{{ $jsSubDetail }}',
                                            '{{ $jsDuration }}','{{ $jsReason }}',
                                            '{{ $jsRejReason }}','{{ $jsHrNotes }}',
                                            '{{ $jsApprover }}','{{ $jsApprovedAt }}','{{ $jsStatus }}'
                                        )">View</button>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="10" style="text-align:center;padding:40px;color:#9ca3af;font-size:13px;">No records found.</td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
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
            <div style="display:flex;align-items:center;gap:10px;margin-bottom:4px;">
                <div id="mRef" style="font-size:22px;font-weight:900;color:#111827;line-height:1.1;"></div>
                <div id="mStatusBadge"></div>
            </div>
            <div id="mSub" style="font-size:13px;color:#374151;"></div>
            <div id="mFiled" style="font-size:12px;color:#9ca3af;margin-top:2px;"></div>
        </div>

        <div class="grid grid-cols-2 gap-4 mb-4">
            <div>
                <div class="modal-label">Request Type</div>
                <div id="mReqType" class="modal-field"></div>
            </div>
            <div>
                <div class="modal-label">Leave Type</div>
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
        <div class="mb-4">
            <div class="modal-label">Reason/Remarks</div>
            <div id="mReason" class="modal-field"></div>
        </div>

        <div id="mRejBlock" style="display:none;margin-bottom:16px;">
            <div class="modal-label" style="color:#dc2626;">Rejection Reason</div>
            <div id="mRejReason" class="modal-field" style="border-left:3px solid #ef4444;"></div>
        </div>

        <div id="mNotesBlock" style="display:none;margin-bottom:16px;">
            <div class="modal-label">HR Notes</div>
            <div id="mHrNotes" class="modal-field"></div>
        </div>

        <div class="mb-5">
            <div class="modal-label mb-3">Approval Trail</div>
            <div id="mTrail"></div>
        </div>

        <div class="flex justify-end">
            <button onclick="closeViewModal()" style="background:#3b82f6;color:#fff;border:none;border-radius:10px;padding:10px 30px;font-size:14px;font-weight:700;font-family:inherit;cursor:pointer;">Confirm</button>
        </div>
    </div>
</div>

<script>
function openViewModal(ref, dept, name, filed, reqType, subDetail, duration, reason, rejReason, hrNotes, approver, approvedAt, status) {
    document.getElementById('mRef').textContent       = ref;
    document.getElementById('mSub').textContent       = dept + ' · ' + name;
    document.getElementById('mFiled').textContent     = 'Filed on ' + filed;
    document.getElementById('mReqType').textContent   = reqType;
    document.getElementById('mLeaveType').textContent = subDetail;
    document.getElementById('mReason').textContent    = reason;

    var parts = duration.split(' – ');
    document.getElementById('mFrom').textContent = parts[0] ? parts[0].trim() : duration;
    document.getElementById('mTo').textContent   = parts[1] ? parts[1].trim() : '—';

    // status badge
    var badgeHtml = '';
    if (status === 'approved') {
        badgeHtml = '<span style="display:inline-block;padding:3px 12px;border-radius:20px;font-size:12px;font-weight:700;background:#dcfce7;color:#16a34a;">Approved</span>';
    } else if (status === 'rejected') {
        badgeHtml = '<span style="display:inline-block;padding:3px 12px;border-radius:20px;font-size:12px;font-weight:700;background:#fee2e2;color:#dc2626;">Rejected</span>';
    } else if (status === 'cancelled') {
        badgeHtml = '<span style="display:inline-block;padding:3px 12px;border-radius:20px;font-size:12px;font-weight:700;background:#f3f4f6;color:#6b7280;">Cancelled</span>';
    } else if (status === 'supervisor_approved') {
        badgeHtml = '<span style="display:inline-block;padding:3px 12px;border-radius:20px;font-size:12px;font-weight:700;background:#dbeafe;color:#1d4ed8;">Forwarded to HR</span>';
    }
    document.getElementById('mStatusBadge').innerHTML = badgeHtml;

    // rejection reason
    if (rejReason && rejReason.trim() !== '') {
        document.getElementById('mRejBlock').style.display = 'block';
        document.getElementById('mRejReason').textContent  = rejReason;
    } else {
        document.getElementById('mRejBlock').style.display = 'none';
    }

    // hr notes
    if (hrNotes && hrNotes.trim() !== '') {
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
    // Filed step
    html += '<div class="trail-item" style="border-left-color:#6b7280;margin-bottom:8px;">';
    html += '<div style="width:30px;height:30px;border-radius:50%;background:#e5e7eb;display:flex;align-items:center;justify-content:center;flex-shrink:0;">';
    html += '<svg width="14" height="14" fill="none" stroke="#6b7280" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>';
    html += '</div><div>';
    html += '<div style="font-size:13px;font-weight:700;color:#111827;">Filed by Employee</div>';
    html += '<div style="font-size:12px;color:#6b7280;margin-top:2px;">' + name + '</div>';
    html += '<div style="font-size:11px;color:#9ca3af;">' + filed + '</div>';
    html += '</div></div>';

    // Supervisor forwarded step (always shown for supervisor view)
    html += '<div class="trail-item" style="border-left-color:#3b82f6;margin-bottom:8px;">';
    html += '<div style="width:30px;height:30px;border-radius:50%;background:#dbeafe;display:flex;align-items:center;justify-content:center;flex-shrink:0;">';
    html += '<svg width="14" height="14" fill="none" stroke="#1d4ed8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>';
    html += '</div><div>';
    
    html += '<div style="font-size:13px;font-weight:700;color:#111827;">Supervisor Forwarded to HR</div>';
    html += '<div style="font-size:11px;color:#9ca3af;">Pending HR final approval</div>';
    html += '</div></div>';

    // HR final action step
    if (approver && approver.trim() !== '' && approver !== '—') {
        html += '<div class="trail-item" style="border-left-color:' + trailColor + ';margin-bottom:8px;">';
        html += '<div style="width:30px;height:30px;border-radius:50%;background:' + trailBg + ';display:flex;align-items:center;justify-content:center;flex-shrink:0;">';
        html += '<svg width="14" height="14" fill="none" stroke="' + trailColor + '" viewBox="0 0 24 24">' + icon + '</svg>';
        html += '</div><div>';
        html += '<div style="font-size:13px;font-weight:700;color:#111827;">HR Manager ' + actionWord + '</div>';
        html += '<div style="font-size:12px;color:#6b7280;margin-top:2px;">' + approver + '</div>';
        html += '<div style="font-size:11px;color:#9ca3af;">' + actionWord + ' on ' + approvedAt + '</div>';
        html += '</div></div>';
    }

    document.getElementById('mTrail').innerHTML = html;
    document.getElementById('viewModal').style.display = 'flex';
    document.getElementById('viewModal').style.alignItems = 'center';
}

function closeViewModal() {
    document.getElementById('viewModal').style.display = 'none';
}

function approvedLogs() {
    return {};
}
</script>
</body>
</html>
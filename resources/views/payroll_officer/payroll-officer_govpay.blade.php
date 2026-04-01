{{-- resources/views/payroll_officer/payroll-officer_govpay.blade.php --}}
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Government Contributions – MediSource</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet"/>
    <style>
        * { font-family: 'DM Sans', sans-serif; box-sizing: border-box; }
        body { background: #f3f4f6; margin: 0; }

        /* ── Sidebar ── */
        aside {
            width: 256px;
            transition: width 0.35s cubic-bezier(0.4,0,0.2,1);
            box-shadow: 2px 0 20px rgba(0,0,0,0.06);
        }
        aside.collapsed { width: 80px; }
        .main-content {
            margin-left: 256px;
            transition: margin-left 0.35s cubic-bezier(0.4,0,0.2,1);
        }
        .main-content.collapsed { margin-left: 80px; }
        .sidebar-label { transition: opacity 0.2s; white-space: nowrap; }
        aside.collapsed .sidebar-label { opacity: 0; pointer-events: none; width: 0; overflow: hidden; }
        aside.collapsed .submenu { max-height: 0 !important; opacity: 0 !important; }

        /* ── Tab bar ── */
        .tabs-wrapper {
            background: #fff;
            border-bottom: 2px solid #e5e7eb;
            padding: 0 32px;
        }
        .tabs-row { display: flex; }
        .tab-btn {
            padding: 16px 0;
            margin-right: 32px;
            font-size: 0.875rem;
            font-weight: 600;
            background: none;
            border: none;
            border-bottom: 3px solid transparent;
            margin-bottom: -2px;
            cursor: pointer;
            color: #9ca3af;
            transition: color 0.15s, border-color 0.15s;
            white-space: nowrap;
        }
        .tab-btn.active { color: #3b82f6; border-bottom-color: #3b82f6; }
        .tab-btn:hover:not(.active) { color: #6b7280; }

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
        .td-right  { text-align: right; }

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

        /* ── View button ── */
        .btn-view {
            display: inline-block;
            background: #eff6ff; color: #2563eb;
            border: 1.5px solid #93c5fd;
            padding: 5px 18px; border-radius: 7px;
            font-size: 0.775rem; font-weight: 600;
            cursor: pointer; text-decoration: none; white-space: nowrap;
            transition: background 0.15s, color 0.15s, border-color 0.15s;
        }
        .btn-view:hover { background: #2563eb; color: #fff; border-color: #2563eb; }

        /* ── Back button ── */
        .btn-back {
            display: inline-flex; align-items: center; gap: 6px;
            background: #f8fafc; border: 1.5px solid #e2e8f0;
            border-radius: 9px; padding: 8px 18px;
            color: #374151; font-size: 0.84rem; font-weight: 500;
            cursor: pointer; white-space: nowrap; text-decoration: none;
            transition: background 0.15s, color 0.15s;
        }
        .btn-back:hover { background: #e2e8f0; color: #1d4ed8; }

        /* ── Breadcrumb ── */
        .breadcrumb { display: flex; align-items: center; gap: 6px; font-size: 0.875rem; }
        .breadcrumb a { color: #9ca3af; text-decoration: none; }
        .breadcrumb a:hover { color: #6b7280; }
        .breadcrumb .sep { color: #d1d5db; }
        .breadcrumb .current { font-weight: 600; color: #374151; }

        /* ── Sidebar nav ── */
        .nav-item { transition: all 0.2s ease; }
        .nav-item:hover { transform: translateX(2px); }
        .submenu-item { transition: all 0.18s ease; }
        .submenu-item:hover { transform: translateX(3px); }
        .chevron-icon { transition: transform 0.3s cubic-bezier(0.4,0,0.2,1); }
        .chevron-icon.rotated { transform: rotate(180deg); }
        .settings-icon { transition: transform 0.5s ease; }
        .nav-item:hover .settings-icon { transform: rotate(60deg); }
        .avatar-ring { box-shadow: 0 0 0 3px rgba(59,130,246,0.2); }

        .submenu {
            overflow: hidden;
            transition: max-height 0.25s ease, opacity 0.2s ease;
            max-height: 200px; opacity: 1;
        }
        .submenu.closed { max-height: 0; opacity: 0; }
    </style>
</head>
<body>

@php
    $currentRoute    = request()->route()->getName();
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
    $sidebarRole     = $sidebarEmployee?->jobTitle?->title ?? 'Payroll Officer';
    $isPayrollSection = in_array($currentRoute, [
        'payroll_officer.dashboard',
        'payroll_officer.payslips',
        'payroll_officer.govpay',
        'payroll_officer.govpay.view',
    ]);
    
    // Define variables with defaults if they don't exist
    if (!isset($isView)) {
        $isView = false;
    }
    
    if (!isset($years) || empty($years)) {
        // Add sample years if no data from database
        $years = [date('Y'), date('Y')-1, date('Y')-2];
    }
    
    if (!isset($year)) {
        $year = date('Y');
    }
    
    if (!isset($totalPending)) {
        $totalPending = 3; // Sample pending count
    }
    
    // SAMPLE DATA - This will show when no real data from database
    if (!isset($contributions) || empty($contributions)) {
        $contributions = [
            (object)[
                'payroll_period_id' => 1,
                'period_name' => 'January 2025',
                'sss_total' => 12500.00,
                'philhealth_total' => 8750.00,
                'pagibig_total' => 4200.00,
                'tax_total' => 15320.00,
                'status' => 'released'
            ],
            (object)[
                'payroll_period_id' => 2,
                'period_name' => 'February 2025',
                'sss_total' => 12450.00,
                'philhealth_total' => 8730.00,
                'pagibig_total' => 4190.00,
                'tax_total' => 15280.00,
                'status' => 'pending'
            ],
            (object)[
                'payroll_period_id' => 3,
                'period_name' => 'March 2025',
                'sss_total' => 12600.00,
                'philhealth_total' => 8800.00,
                'pagibig_total' => 4250.00,
                'tax_total' => 15450.00,
                'status' => 'pending'
            ]
        ];
    }
    
    if (!isset($myContributions) || empty($myContributions)) {
        $myContributions = [
            (object)[
                'period_name' => 'January 2025',
                'sss_ee' => 4500.00,
                'sss_er' => 8000.00,
                'philhealth' => 8750.00,
                'pagibig' => 2100.00,
                'tax' => 15320.00,
                'status' => 'released'
            ],
            (object)[
                'period_name' => 'February 2025',
                'sss_ee' => 4480.00,
                'sss_er' => 7970.00,
                'philhealth' => 8730.00,
                'pagibig' => 2095.00,
                'tax' => 15280.00,
                'status' => 'pending'
            ]
        ];
    }
    
    if (!isset($records) || empty($records)) {
        $records = [
            (object)[
                'fname' => 'John',
                'lname' => 'Dela Cruz',
                'sss_ee' => 4500.00,
                'sss_er' => 8000.00,
                'philhealth' => 875.00,
                'pagibig' => 100.00,
                'tax' => 3500.00,
                'status' => 'released'
            ],
            (object)[
                'fname' => 'Maria',
                'lname' => 'Santos',
                'sss_ee' => 4250.00,
                'sss_er' => 7850.00,
                'philhealth' => 825.00,
                'pagibig' => 100.00,
                'tax' => 2800.00,
                'status' => 'released'
            ],
            (object)[
                'fname' => 'Jose',
                'lname' => 'Reyes',
                'sss_ee' => 4350.00,
                'sss_er' => 7950.00,
                'philhealth' => 850.00,
                'pagibig' => 100.00,
                'tax' => 3100.00,
                'status' => 'pending'
            ]
        ];
    }
    
    if (!isset($periodName)) {
        $periodName = 'January 2025';
    }
    
    if (!isset($periodId)) {
        $periodId = 1;
    }
@endphp

<!-- ═══ SIDEBAR ═══ -->
<aside id="sidebar" class="bg-white border-r border-gray-200 h-screen fixed left-0 top-0 overflow-y-auto z-50 flex flex-col">

    <!-- Logo -->
    <div class="px-6 py-5 border-b border-gray-100">
        <div class="flex items-center space-x-3">
            <div class="w-9 h-9 border-2 border-gray-800 flex items-center justify-center flex-shrink-0" style="border-radius:6px;">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                </svg>
            </div>
            <h1 class="sidebar-label font-bold text-gray-900 text-lg tracking-widest">MEDISOURCE</h1>
        </div>
    </div>

    <!-- User -->
    <div class="px-4 py-4 border-b border-gray-100 flex items-center space-x-3">
        <div class="w-11 h-11 rounded-full flex items-center justify-center text-white font-bold flex-shrink-0 text-sm avatar-ring"
             style="background:linear-gradient(135deg,#3b82f6,#1d4ed8);">
            {{ $sidebarInitials }}
        </div>
        <div class="sidebar-label overflow-hidden">
            <p class="font-semibold text-gray-800 text-sm leading-tight truncate">{{ $sidebarName }}</p>
            <p class="text-xs mt-0.5 font-semibold" style="color:#3b82f6;">{{ $sidebarRole }}</p>
        </div>
    </div>

    <!-- Nav -->
    <nav class="p-3 space-y-0.5 flex-1 overflow-y-auto">
        <p class="sidebar-label text-xs text-gray-400 font-semibold px-3 py-2 uppercase tracking-widest">Main Menu</p>

        <!-- Dashboard -->
        <a href="{{ route('payroll_officer.dashboard') }}"
           class="nav-item flex items-center space-x-3 px-3 py-2.5 rounded-lg
                  {{ $currentRoute === 'payroll_officer.dashboard' ? 'text-white' : 'text-gray-600 hover:bg-gray-50' }}"
           style="{{ $currentRoute === 'payroll_officer.dashboard' ? 'background:#3b82f6;' : '' }}">
            <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6z
                         M14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6z
                         M4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2z
                         M14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"/>
            </svg>
            <span class="sidebar-label text-sm font-medium">Dashboard</span>
        </a>

        <!-- Employees -->
        <div>
            <button onclick="toggleSubmenu('empMenu','empChevron')"
                    class="nav-item w-full flex items-center justify-between px-3 py-2.5 rounded-lg text-gray-600 hover:bg-gray-50">
                <div class="flex items-center space-x-3">
                    <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/>
                    </svg>
                    <span class="sidebar-label text-sm font-medium">Employees</span>
                </div>
                <svg id="empChevron" class="sidebar-label w-4 h-4 chevron-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                </svg>
            </button>
            <div id="empMenu" class="submenu closed ml-8 mt-1 space-y-0.5">
                <a href="#" class="submenu-item block px-3 py-2 text-sm text-gray-500 hover:text-gray-800 hover:bg-gray-50 rounded-lg">Employee Directory</a>
                <a href="#" class="submenu-item block px-3 py-2 text-sm text-gray-500 hover:text-gray-800 hover:bg-gray-50 rounded-lg">Employee Profile</a>
            </div>
        </div>

        <!-- Time & Attendance -->
        <div>
            <button onclick="toggleSubmenu('attMenu','attChevron')"
                    class="nav-item w-full flex items-center justify-between px-3 py-2.5 rounded-lg text-gray-600 hover:bg-gray-50">
                <div class="flex items-center space-x-3">
                    <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <span class="sidebar-label text-sm font-medium">Time &amp; Attendance</span>
                </div>
                <svg id="attChevron" class="sidebar-label w-4 h-4 chevron-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                </svg>
            </button>
            <div id="attMenu" class="submenu closed ml-8 mt-1 space-y-0.5">
                <a href="#" class="submenu-item block px-3 py-2 text-sm text-gray-500 hover:text-gray-800 hover:bg-gray-50 rounded-lg">My Attendance</a>
            </div>
        </div>

        <!-- Payroll -->
        <div>
            <button onclick="toggleSubmenu('payMenu','payChevron')"
                    class="nav-item w-full flex items-center justify-between px-3 py-2.5 rounded-lg
                           {{ $isPayrollSection ? 'text-blue-600 bg-blue-50' : 'text-gray-600 hover:bg-gray-50' }}">
                <div class="flex items-center space-x-3">
                    <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/>
                    </svg>
                    <span class="sidebar-label text-sm font-medium">Payroll</span>
                </div>
                <svg id="payChevron"
                     class="sidebar-label w-4 h-4 chevron-icon {{ $isPayrollSection ? 'rotated' : '' }}"
                     fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                </svg>
            </button>
            <div id="payMenu" class="submenu {{ $isPayrollSection ? '' : 'closed' }} ml-8 mt-1 space-y-0.5">
                <a href="{{ route('payroll_officer.dashboard') }}"
                   class="submenu-item block px-3 py-2 text-sm rounded-lg
                          {{ $currentRoute === 'payroll_officer.dashboard' ? 'text-blue-600 font-semibold bg-blue-50' : 'text-gray-500 hover:text-gray-800 hover:bg-gray-50' }}">
                    Payroll
                </a>
                <a href="#"
                   class="submenu-item block px-3 py-2 text-sm rounded-lg
                          {{ $currentRoute === 'payroll_officer.payslips' ? 'text-blue-600 font-semibold bg-blue-50' : 'text-gray-500 hover:text-gray-800 hover:bg-gray-50' }}">
                    Payslips
                </a>
                <a href="{{ route('payroll_officer.govpay') }}"
                   class="submenu-item block px-3 py-2 text-sm rounded-lg
                          {{ in_array($currentRoute, ['payroll_officer.govpay','payroll_officer.govpay.view']) ? 'text-blue-600 font-semibold bg-blue-50' : 'text-gray-500 hover:text-gray-800 hover:bg-gray-50' }}">
                    Govt. Contributions
                </a>
            </div>
        </div>

        <!-- Requests & Approval -->
        <div>
            <button onclick="toggleSubmenu('reqMenu','reqChevron')"
                    class="nav-item w-full flex items-center justify-between px-3 py-2.5 rounded-lg text-gray-600 hover:bg-gray-50">
                <div class="flex items-center space-x-3">
                    <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2
                                 M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                    </svg>
                    <span class="sidebar-label text-sm font-medium">Requests &amp; Approval</span>
                </div>
                <svg id="reqChevron" class="sidebar-label w-4 h-4 chevron-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                </svg>
            </button>
            <div id="reqMenu" class="submenu closed ml-8 mt-1 space-y-0.5">
                <a href="#" class="submenu-item block px-3 py-2 text-sm text-gray-500 hover:text-gray-800 hover:bg-gray-50 rounded-lg">Pending Requests</a>
                <a href="#" class="submenu-item block px-3 py-2 text-sm text-gray-500 hover:text-gray-800 hover:bg-gray-50 rounded-lg">Approved Logs</a>
            </div>
        </div>

        <!-- Others -->
        <div class="pt-3 mt-2 border-t border-gray-100">
            <p class="sidebar-label text-xs text-gray-400 font-semibold px-3 py-2 uppercase tracking-widest">Others</p>
            <a href="#" class="nav-item flex items-center space-x-3 px-3 py-2.5 rounded-lg text-gray-600 hover:bg-gray-50">
                <svg class="w-5 h-5 flex-shrink-0 settings-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                </svg>
                <span class="sidebar-label text-sm font-medium">Settings</span>
            </a>
            <a href="{{ route('logout') }}"
               onclick="event.preventDefault();document.getElementById('payroll-logout-form').submit();"
               class="nav-item flex items-center space-x-3 px-3 py-2.5 rounded-lg text-gray-600 hover:bg-red-50 hover:text-red-500">
                <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
                </svg>
                <span class="sidebar-label text-sm font-medium">Logout</span>
            </a>
            <form id="payroll-logout-form" action="{{ route('logout') }}" method="POST" class="hidden">@csrf</form>
        </div>
    </nav>

    <!-- Collapse btn -->
    <button onclick="toggleSidebar()"
            class="m-3 w-8 h-8 bg-gray-100 rounded-lg flex items-center justify-center text-gray-500 hover:bg-gray-200 self-end">
        <svg id="collapseChevron" class="w-4 h-4 chevron-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 19l-7-7 7-7m8 14l-7-7 7-7"/>
        </svg>
    </button>
</aside>

<!-- ═══ MAIN CONTENT ═══ -->
<div class="main-content min-h-screen" id="mainContent">

    <!-- Blue Header -->
    <header class="bg-gradient-to-br from-blue-500 to-blue-700 sticky top-0 z-10 shadow-lg mt-4 mx-4 rounded-2xl overflow-hidden">
        <div class="flex items-center justify-between px-8 py-4">
            <h1 class="text-white font-bold text-xl">Government Contributions</h1>
            <button class="w-9 h-9 rounded-full flex items-center justify-center hover:bg-white/20 relative">
                <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>
                </svg>
                @if($totalPending > 0)
                    <span class="absolute -top-1 -right-1 w-4 h-4 bg-red-500 rounded-full text-white text-xs flex items-center justify-center font-bold">
                        {{ $totalPending }}
                    </span>
                @endif
            </button>
        </div>
    </header>

    {{-- ══════════════════════════════════════
         LIST VIEW
    ══════════════════════════════════════ --}}
    @if(!$isView)

        <!-- Tab Bar -->
        <div class="tabs-wrapper">
            <div class="tabs-row">
                <button id="tabAll"  class="tab-btn active" onclick="switchTab('all')">All Contributions</button>
                <button id="tabMine" class="tab-btn"        onclick="switchTab('mine')">My Contributions</button>
            </div>
        </div>

        <div class="p-8 space-y-6">

            {{-- ALL CONTRIBUTIONS --}}
            <div id="panelAll">
                <div class="card">
                    <div class="card-header">
                        <span class="card-title">Contribution Summary</span>
                        <form method="GET" action="{{ route('payroll_officer.govpay') }}">
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
                                    <th class="td-center">SSS Total</th>
                                    <th class="td-center">PhilHealth Total</th>
                                    <th class="td-center">Pag-IBIG Total</th>
                                    <th class="td-center">W/ Tax Total</th>
                                    <th class="td-center">Status</th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($contributions as $row)
                                <tr>
                                    <td class="td-name">{{ $row->period_name }}</td>
                                    <td class="td-center">₱{{ number_format($row->sss_total, 2) }}</td>
                                    <td class="td-center">₱{{ number_format($row->philhealth_total, 2) }}</td>
                                    <td class="td-center">₱{{ number_format($row->pagibig_total, 2) }}</td>
                                    <td class="td-center">₱{{ number_format($row->tax_total, 2) }}</td>
                                    <td class="td-center">
                                        <span class="{{ strtolower($row->status ?? '') === 'released' ? 'badge-released' : 'badge-pending' }}">
                                            {{ ucfirst($row->status ?? 'Pending') }}
                                        </span>
                                    </td>
                                    <td class="td-right pr-6">
                                        <a href="{{ route('payroll_officer.govpay.view', $row->payroll_period_id) }}" class="btn-view">View</a>
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="7" class="text-center py-12 text-gray-400 text-sm">No contributions found for {{ $year }}.</td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            {{-- MY CONTRIBUTIONS --}}
            <div id="panelMine" style="display:none;">
                <div class="card">
                    <div class="card-header">
                        <span class="card-title">My Contribution Summary</span>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="contrib-table">
                            <thead>
                                <tr>
                                    <th class="text-left">Period Name</th>
                                    <th class="td-center">SSS (EE / ER)</th>
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
                                    <td class="td-center">₱{{ number_format($row->sss_ee ?? 0, 2) }} / ₱{{ number_format($row->sss_er ?? 0, 2) }}</td>
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
                                    <td colspan="6" class="text-center py-12 text-gray-400 text-sm">No personal contributions found for {{ $year }}.</td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

        </div>

    @endif

    {{-- ══════════════════════════════════════
         DETAIL VIEW
    ══════════════════════════════════════ --}}
    @if($isView)
    <div class="p-8">

        <!-- Back + Breadcrumb -->
        <div class="flex items-center gap-5 mb-6">
            <a href="{{ route('payroll_officer.govpay') }}" class="btn-back">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M15 19l-7-7 7-7"/>
                </svg>
                Back to All Contributions
            </a>
            <div class="breadcrumb">
                <a href="{{ route('payroll_officer.govpay') }}">All Contributions</a>
                <span class="sep">›</span>
                <span class="current">{{ $periodName }}</span>
            </div>
        </div>

        <!-- Employee Contributions Card -->
        <div class="card">
            <div class="card-header">
                <span class="card-title">Employee Contributions - {{ $periodName }}</span>
                <form method="GET" action="{{ route('payroll_officer.govpay.view', $periodId) }}">
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
                            <th class="text-left">Employee</th>
                            <th class="td-center">SSS (EE / ER)</th>
                            <th class="td-center">PhilHealth</th>
                            <th class="td-center">Pag-IBIG</th>
                            <th class="td-center">W/ Tax</th>
                            <th class="td-center">Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($records as $row)
                        <tr>
                            <td class="td-name">{{ $row->fname }} {{ $row->lname }}</td>
                            <td class="td-center">₱{{ number_format($row->sss_ee ?? 0, 2) }} / ₱{{ number_format($row->sss_er ?? 0, 2) }}</td>
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
                            <td colspan="6" class="text-center py-12 text-gray-400 text-sm">No employee contributions found for this period.</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

    </div>
    @endif

</div>

<script>
    // ── Sidebar collapse ──
    let sidebarCollapsed = localStorage.getItem('sidebarCollapsed') === 'true';

    function applySidebarState() {
        const sidebar = document.getElementById('sidebar');
        const main    = document.getElementById('mainContent');
        const chevron = document.getElementById('collapseChevron');
        if (sidebar) sidebar.classList.toggle('collapsed', sidebarCollapsed);
        if (main) main.classList.toggle('collapsed', sidebarCollapsed);
        if (chevron) chevron.classList.toggle('rotated', sidebarCollapsed);
    }

    function toggleSidebar() {
        sidebarCollapsed = !sidebarCollapsed;
        localStorage.setItem('sidebarCollapsed', sidebarCollapsed);
        applySidebarState();
    }

    // ── Submenu toggle ──
    function toggleSubmenu(menuId, chevronId) {
        const menu    = document.getElementById(menuId);
        const chevron = document.getElementById(chevronId);
        if (!menu || !chevron) return;
        menu.classList.toggle('closed');
        chevron.classList.toggle('rotated');
    }

    // ── Tab switch (list view only) ──
    function switchTab(tab) {
        const panelAll  = document.getElementById('panelAll');
        const panelMine = document.getElementById('panelMine');
        const tabAll    = document.getElementById('tabAll');
        const tabMine   = document.getElementById('tabMine');
        if (!panelAll) return;
        if (tab === 'all') {
            panelAll.style.display  = 'block';
            panelMine.style.display = 'none';
            if (tabAll) tabAll.classList.add('active');
            if (tabMine) tabMine.classList.remove('active');
        } else {
            panelAll.style.display  = 'none';
            panelMine.style.display = 'block';
            if (tabAll) tabAll.classList.remove('active');
            if (tabMine) tabMine.classList.add('active');
        }
    }

    // Apply saved sidebar state on load
    document.addEventListener('DOMContentLoaded', function() {
        applySidebarState();
    });
</script>
</body>
</html>
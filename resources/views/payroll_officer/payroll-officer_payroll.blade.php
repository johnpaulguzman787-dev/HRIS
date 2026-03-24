<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payroll — MEDISOURCE Payroll Officer</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style> body { font-family: 'DM Sans', sans-serif; } </style>
</head>
<body class="bg-gray-50" x-data="{ sidebarCollapsed: localStorage.getItem('sidebarCollapsed') === 'true' }"
      x-init="window.addEventListener('storage', e => { if (e.key === 'sidebarCollapsed') sidebarCollapsed = e.newValue === 'true'; })">

    @include('payroll_officer.payroll_sidebar')

    <div class="transition-all duration-300" :class="sidebarCollapsed ? 'ml-20' : 'ml-64'">
        <div class="p-8">
            <h1 class="text-2xl font-bold text-gray-800">Payroll</h1>
            <p class="text-gray-500 mt-1">Manage employee payroll.</p>
        </div>
    </div>

</body>
</html>

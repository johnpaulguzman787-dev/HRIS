<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    
    <title>@yield('title', 'Medisource') -Medisource HRIS</title>
    
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    
    <!-- Alpine.js -->
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    
    <!-- Inter Font -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    
    <style>
        * {
            font-family: 'Inter', sans-serif;
        }
        
        [x-cloak] { 
            display: none !important; 
        }
    </style>
    
    @stack('styles')
</head>
<body class="bg-gray-50">
    @yield('content')
    
    @stack('scripts')

    {{-- Shared Alpine calendar widget used by all dashboard & leave-management pages --}}
    <script>
    function calendarWidget(holidays) {
        const today = new Date();
        return {
            allHolidays: holidays,
            calYear:  today.getFullYear(),
            calMonth: today.getMonth() + 1, // 1-12
            todayDay:   today.getDate(),
            todayMonth: today.getMonth() + 1,
            todayYear:  today.getFullYear(),

            get calMonthName() {
                return new Date(this.calYear, this.calMonth - 1, 1)
                    .toLocaleString('en-US', { month: 'long', year: 'numeric' })
                    .toUpperCase();
            },
            get firstDay() {
                return new Date(this.calYear, this.calMonth - 1, 1).getDay();
            },
            get totalDays() {
                return new Date(this.calYear, this.calMonth, 0).getDate();
            },
            get holidayMap() {
                const map = {};
                this.allHolidays.forEach(h => {
                    if (h.month === this.calMonth && h.year === this.calYear) {
                        map[h.day] = h.name;
                    }
                });
                return map;
            },
            get upcomingEvents() {
                return this.allHolidays
                    .filter(h => h.month === this.calMonth && h.year === this.calYear)
                    .sort((a, b) => a.day - b.day);
            },
            prevMonth() {
                if (this.calMonth === 1) { this.calMonth = 12; this.calYear--; }
                else { this.calMonth--; }
            },
            nextMonth() {
                if (this.calMonth === 12) { this.calMonth = 1; this.calYear++; }
                else { this.calMonth++; }
            },
            isToday(day) {
                return day === this.todayDay && this.calMonth === this.todayMonth && this.calYear === this.todayYear;
            },
            range(n) { return Array.from({ length: n }, (_, i) => i); },
            days() { return Array.from({ length: this.totalDays }, (_, i) => i + 1); },
        };
    }
    </script>
</body>
</html>
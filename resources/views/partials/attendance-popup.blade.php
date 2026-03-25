{{--
    Attendance Popup Modal
    - Auto-opens on dashboard load (controlled by showAttendancePopup in parent x-data)
    - Shows only if: user has a shift AND not on leave/holiday AND hasn't clocked in yet
    - Closes when clicking outside or pressing any action button
--}}

<div x-show="showAttendancePopup"
     x-cloak
     x-transition:enter="transition ease-out duration-200"
     x-transition:enter-start="opacity-0"
     x-transition:enter-end="opacity-100"
     x-transition:leave="transition ease-in duration-150"
     x-transition:leave-start="opacity-100"
     x-transition:leave-end="opacity-0"
     class="fixed inset-0 z-[9999] flex items-center justify-center p-6"
     style="background: rgba(0,0,0,0.55); display:none;"
     @click.self="showAttendancePopup = false">

    <div x-transition:enter="transition ease-out duration-250"
         x-transition:enter-start="opacity-0 scale-95 translate-y-3"
         x-transition:enter-end="opacity-100 scale-100 translate-y-0"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100 scale-100 translate-y-0"
         x-transition:leave-end="opacity-0 scale-95 translate-y-3"
         class="bg-white rounded-2xl w-full shadow-2xl"
         style="max-width: 560px; padding: 40px 44px 36px;">

        {{-- Header --}}
        <p class="text-base font-bold text-gray-700 uppercase tracking-widest mb-1">Time & Attendance</p>
        <p class="text-sm text-gray-400 mb-4" x-text="currentDate"></p>

        {{-- Live Clock --}}
        <p class="font-black tabular-nums leading-none mb-5"
           style="font-size: 4rem; letter-spacing: -2px; color: #3b82f6;"
           x-text="currentTime"></p>

        <hr class="mb-5 border-gray-100">

        {{-- Shift Schedule --}}
        @if($employeeShift ?? null)
        <div class="mb-5">
            <p class="text-sm font-semibold text-gray-500 uppercase tracking-wider mb-3">Shift Schedule</p>
            <div class="border border-gray-200 rounded-xl overflow-hidden bg-gray-50">
                <div class="flex items-center justify-between px-5 py-3">
                    <span class="text-base font-semibold text-gray-700">{{ $employeeShift->shift->name ?? '—' }}</span>
                    <span class="text-sm text-gray-400">
                        {{ $employeeShift->shift
                            ? \Carbon\Carbon::parse($employeeShift->shift->start_time)->format('g:i A')
                              . ' – '
                              . \Carbon\Carbon::parse($employeeShift->shift->end_time)->format('g:i A')
                            : '—' }}
                    </span>
                </div>
                <div class="flex items-center justify-between px-5 py-3 border-t border-gray-100">
                    <span class="text-sm text-gray-400">Work Setup</span>
                    <span class="text-sm font-bold" style="color:#1d4ed8;">
                        {{ strtoupper($employeeShift->work_setup ?? '—') }}
                    </span>
                </div>
            </div>
        </div>
        @endif

        {{-- Today's Attendance --}}
        <div class="mb-6">
            <p class="text-sm font-semibold text-gray-500 uppercase tracking-wider mb-3">Today's Attendance</p>
            <div class="grid grid-cols-3 gap-3">
                <div class="border border-gray-200 rounded-xl py-4 text-center bg-gray-50">
                    <p class="text-xs font-semibold text-gray-400 uppercase tracking-wider mb-2">Time In</p>
                    <p class="text-lg font-bold text-gray-700" x-text="clockedIn ? clockInTime : '–'"></p>
                </div>
                <div class="border border-gray-200 rounded-xl py-4 text-center bg-gray-50">
                    <p class="text-xs font-semibold text-gray-400 uppercase tracking-wider mb-2">Break</p>
                    <p class="text-lg font-bold text-gray-700" x-text="onBreak ? breakTime : '–'"></p>
                </div>
                <div class="border border-gray-200 rounded-xl py-4 text-center bg-gray-50">
                    <p class="text-xs font-semibold text-gray-400 uppercase tracking-wider mb-2">Time Out</p>
                    <p class="text-lg font-bold text-gray-700" x-text="clockedOut ? clockOutTime : '–'"></p>
                </div>
            </div>
        </div>

        {{-- Action Buttons --}}
        <div class="grid grid-cols-3 gap-3">

            {{-- TIME IN --}}
            <button @click="handleClock(); if(!clockedIn){ setTimeout(() => showAttendancePopup = false, 300) } else { showAttendancePopup = false }"
                :disabled="clockedIn || clockedOut || onLeave"
                class="py-4 rounded-xl text-sm font-bold uppercase tracking-widest transition-all"
                :class="(!clockedIn && !clockedOut && !onLeave)
                    ? 'bg-blue-600 text-white hover:bg-blue-700 shadow-sm hover:shadow-md'
                    : 'bg-gray-100 text-gray-400 cursor-not-allowed'">
                Time In
            </button>

            {{-- BREAK --}}
            <button @click="handleBreak(); showAttendancePopup = false"
                :disabled="!clockedIn || clockedOut || onBreak || resumed || onLeave"
                class="py-4 rounded-xl text-sm font-bold uppercase tracking-widest transition-all"
                :class="(clockedIn && !clockedOut && !onBreak && !resumed && !onLeave)
                    ? 'bg-gray-700 text-white hover:bg-gray-800 shadow-sm'
                    : 'bg-gray-100 text-gray-400 cursor-not-allowed'">
                Break
            </button>

            {{-- TIME OUT --}}
            <button @click="handleClockOut(); showAttendancePopup = false"
                :disabled="!clockedIn || clockedOut || onLeave"
                class="py-4 rounded-xl text-sm font-bold uppercase tracking-widest transition-all"
                :class="(clockedIn && !clockedOut && !onLeave)
                    ? 'bg-gray-700 text-white hover:bg-gray-800 shadow-sm'
                    : 'bg-gray-100 text-gray-400 cursor-not-allowed'">
                Time Out
            </button>
        </div>

    </div>
</div>

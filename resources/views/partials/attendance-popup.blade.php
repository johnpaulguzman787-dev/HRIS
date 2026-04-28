{{--
    Attendance Popup Modal
    - Auto-opens on every dashboard load (controlled by showAttendancePopup in parent x-data)
    - Shows if: user has a shift assigned
    - Closes only via the X button or action buttons (not by clicking outside)
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
     style="background: rgba(0,0,0,0.55); display:none;">

    <div x-transition:enter="transition ease-out duration-250"
         x-transition:enter-start="opacity-0 scale-95 translate-y-3"
         x-transition:enter-end="opacity-100 scale-100 translate-y-0"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100 scale-100 translate-y-0"
         x-transition:leave-end="opacity-0 scale-95 translate-y-3"
         class="bg-white rounded-2xl w-full shadow-2xl"
         style="max-width: 560px; padding: 40px 44px 36px;">

        {{-- Header --}}
        <div class="flex items-start justify-between mb-1">
            <p class="text-base font-bold text-gray-700 uppercase tracking-widest">Time & Attendance</p>
            <button @click="showAttendancePopup = false"
                    class="ml-4 shrink-0 text-gray-400 hover:text-gray-600 transition-colors"
                    style="margin-top: -4px;">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>
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
                        @if($employeeShift->shift)
                            @if($employeeShift->shift->is_flexi)
                                {{ $employeeShift->shift->required_hours }}h required
                            @else
                                {{ \Carbon\Carbon::parse($employeeShift->shift->start_time)->format('g:i A') }} – {{ \Carbon\Carbon::parse($employeeShift->shift->end_time)->format('g:i A') }}
                            @endif
                        @else
                            —
                        @endif
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
                    <p class="text-lg font-bold text-gray-700" x-text="clockInTime || '–'"></p>
                </div>
                <div class="border border-gray-200 rounded-xl py-4 text-center bg-gray-50">
                    <p class="text-xs font-semibold text-gray-400 uppercase tracking-wider mb-2">Break</p>
                    <p class="text-lg font-bold text-gray-700" x-text="breakTime || '–'"></p>
                </div>
                <div class="border border-gray-200 rounded-xl py-4 text-center bg-gray-50">
                    <p class="text-xs font-semibold text-gray-400 uppercase tracking-wider mb-2">Time Out</p>
                    <p class="text-lg font-bold text-gray-700" x-text="clockOutTime || '–'"></p>
                </div>
            </div>
        </div>

        {{-- Action Buttons --}}
        <div class="grid grid-cols-3 gap-3">

            {{-- TIME IN / RESUME --}}
            <button @click="handleClock().then(() => { showAttendancePopup = false })"
                :disabled="onLeave || (clockedIn && !onBreak) || !assignedShiftId"
                class="py-4 rounded-xl text-sm font-bold uppercase tracking-widest transition-all"
                :class="(!onLeave && (!clockedIn || onBreak) && assignedShiftId)
                    ? 'bg-blue-600 text-white hover:bg-blue-700 shadow-sm hover:shadow-md'
                    : 'bg-gray-100 text-gray-400 cursor-not-allowed'"
                x-text="onBreak ? 'Resume' : 'Time In'">
            </button>

            {{-- BREAK --}}
            <button @click="handleBreak().then(() => { showAttendancePopup = false })"
                :disabled="!clockedIn || onBreak || onLeave"
                class="py-4 rounded-xl text-sm font-bold uppercase tracking-widest transition-all"
                :class="(clockedIn && !onBreak && !onLeave)
                    ? 'bg-gray-700 text-white hover:bg-gray-800 shadow-sm'
                    : 'bg-gray-100 text-gray-400 cursor-not-allowed'">
                Break
            </button>

            {{-- TIME OUT --}}
            <button @click="handleClockOut().then(() => { showAttendancePopup = false })"
                :disabled="!clockedIn || onLeave"
                class="py-4 rounded-xl text-sm font-bold uppercase tracking-widest transition-all"
                :class="(clockedIn && !onLeave)
                    ? 'bg-red-600 text-white hover:bg-red-700 shadow-sm'
                    : 'bg-gray-100 text-gray-400 cursor-not-allowed'">
                Time Out
            </button>
        </div>

    </div>
</div>

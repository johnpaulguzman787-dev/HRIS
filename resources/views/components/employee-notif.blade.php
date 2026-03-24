{{-- resources/views/components/notification-bell.blade.php --}}
{{-- Same component — works for ALL roles (admin, supervisor, hr, etc.) --}}

<div x-data="{ open: false }" class="relative" @click.away="open = false">

    {{-- Bell Button --}}
    <button @click="open = !open"
        class="relative w-9 h-9 rounded-full flex items-center justify-center hover:bg-white/20 transition-all focus:outline-none">
        <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>
        </svg>
        <span class="absolute -top-1 -right-1 w-[18px] h-[18px] bg-red-500 rounded-full
                     text-white text-[10px] font-bold flex items-center justify-center border-2 border-white">
            4
        </span>
    </button>

    {{-- Dropdown Panel --}}
    <div x-show="open" x-cloak
        x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="opacity-0 -translate-y-2 scale-95"
        x-transition:enter-end="opacity-100 translate-y-0 scale-100"
        x-transition:leave="transition ease-in duration-150"
        x-transition:leave-start="opacity-100 translate-y-0 scale-100"
        x-transition:leave-end="opacity-0 -translate-y-2 scale-95"
        class="absolute right-0 mt-3 w-[400px] bg-white rounded-2xl shadow-2xl border border-gray-100 z-[999]"
        style="top: 100%;">

        {{-- Header --}}
        <div class="px-6 py-5 border-b border-gray-100">
            <h3 class="text-2xl font-bold text-gray-900">Notifications</h3>
        </div>

        {{-- Notification Items --}}
        <div class="overflow-y-auto divide-y divide-gray-100" style="max-height: 480px;">

            {{-- Item 1: System Maintenance --}}
            <div class="flex items-start gap-4 px-6 py-4 hover:bg-gray-50 transition-colors group">
                <div class="w-10 h-10 rounded-xl bg-orange-100 flex items-center justify-center flex-shrink-0 mt-0.5">
                    <svg class="w-5 h-5 text-orange-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
                <div class="flex-1 min-w-0">
                    <p class="text-sm font-semibold text-gray-900">System Maintenance</p>
                    <p class="text-sm text-gray-500 mt-0.5 leading-relaxed">
                        Performance and security updates will be applied at 4:00 PM, March 16.
                    </p>
                    <p class="text-xs text-gray-400 mt-1.5">2h ago &bull; March 16, 2026 &bull; 9:42 AM</p>
                </div>
                <button class="opacity-0 group-hover:opacity-100 text-gray-300 hover:text-gray-500 transition-all flex-shrink-0 mt-0.5">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>

            {{-- Item 2: Payroll Processed --}}
            <div class="flex items-start gap-4 px-6 py-4 hover:bg-gray-50 transition-colors group">
                <div class="w-10 h-10 rounded-xl bg-green-100 flex items-center justify-center flex-shrink-0 mt-0.5">
                    <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
                <div class="flex-1 min-w-0">
                    <p class="text-sm font-semibold text-gray-900">Payroll Processed</p>
                    <p class="text-sm text-gray-500 mt-0.5 leading-relaxed">
                        February 16-28 payroll has been disbursed.
                    </p>
                    <p class="text-xs text-gray-400 mt-1.5">3h ago &bull; March 16, 2026 &bull; 8:42 AM</p>
                </div>
                <button class="opacity-0 group-hover:opacity-100 text-gray-300 hover:text-gray-500 transition-all flex-shrink-0 mt-0.5">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>

            {{-- Item 3: New Request --}}
            <div class="flex items-start gap-4 px-6 py-4 hover:bg-gray-50 transition-colors group">
                <div class="w-10 h-10 rounded-xl bg-blue-100 flex items-center justify-center flex-shrink-0 mt-0.5">
                    <svg class="w-5 h-5 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
                <div class="flex-1 min-w-0">
                    <p class="text-sm font-semibold text-gray-900">New Request</p>
                    <p class="text-sm text-gray-500 mt-0.5 leading-relaxed">
                        Juan Dela Cruz filed a leave request.
                    </p>
                    <p class="text-xs text-gray-400 mt-1.5">3h ago &bull; March 16, 2026 &bull; 8:42 AM</p>
                </div>
                <button class="opacity-0 group-hover:opacity-100 text-gray-300 hover:text-gray-500 transition-all flex-shrink-0 mt-0.5">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>

            {{-- Item 4: Time to Clock Out --}}
            <div class="flex items-start gap-4 px-6 py-4 hover:bg-gray-50 transition-colors group">
                <div class="w-10 h-10 rounded-xl bg-red-100 flex items-center justify-center flex-shrink-0 mt-0.5">
                    <svg class="w-5 h-5 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                    </svg>
                </div>
                <div class="flex-1 min-w-0">
                    <p class="text-sm font-semibold text-gray-900">Time to Clock out</p>
                    <p class="text-sm text-gray-500 mt-0.5 leading-relaxed">
                        You may file an overtime request if you are going to work beyond your shift.
                    </p>
                    <p class="text-xs text-gray-400 mt-1.5">3h ago &bull; March 16, 2026 &bull; 8:42 AM</p>
                </div>
                <button class="opacity-0 group-hover:opacity-100 text-gray-300 hover:text-gray-500 transition-all flex-shrink-0 mt-0.5">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>

        </div>
    </div>
</div>

<style>[x-cloak] { display: none !important; }</style>
{{-- Attendance Error Modal --}}
<div x-show="errorMessage"
     x-cloak
     class="fixed inset-0 z-[9999] flex items-center justify-center"
     style="background: rgba(0,0,0,0.45);">
    <div class="bg-white rounded-2xl shadow-2xl p-8 mx-4 text-center"
         style="max-width: 360px; width: 100%;">
        <div class="flex justify-center mb-4">
            <div class="w-14 h-14 rounded-full flex items-center justify-center"
                 style="background: rgba(239,68,68,0.1);">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-7 h-7" fill="none" viewBox="0 0 24 24"
                     stroke="#dc2626" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round"
                          d="M12 9v4m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/>
                </svg>
            </div>
        </div>
        <h3 class="text-base font-bold text-gray-800 mb-2">Action Failed</h3>
        <p x-text="errorMessage" class="text-sm text-gray-600 mb-6 leading-relaxed"></p>
        <button @click="errorMessage = ''"
                class="px-6 py-2 rounded-xl text-sm font-semibold text-white transition-colors"
                style="background: #dc2626;">
            OK
        </button>
    </div>
</div>

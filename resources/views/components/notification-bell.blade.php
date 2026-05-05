{{-- resources/views/components/notification-bell.blade.php --}}

<div x-data="notificationBell()" x-init="init()" @click.away="open = false" class="relative flex-shrink-0">

    {{-- Notification Popup Modal --}}
    <div x-show="popup.show" x-cloak
        class="fixed inset-0 z-[9999] flex items-center justify-center p-4"
        style="background: rgba(0,0,0,0.45); backdrop-filter: blur(6px);">
        <div
            x-transition:enter="transition-all duration-250 ease-out"
            x-transition:enter-start="opacity-0 scale-90"
            x-transition:enter-end="opacity-100 scale-100"
            x-transition:leave="transition-all duration-150 ease-in"
            x-transition:leave-start="opacity-100 scale-100"
            x-transition:leave-end="opacity-0 scale-90"
            class="bg-white rounded-2xl shadow-2xl flex flex-col items-center justify-center text-center p-10"
            style="width: 460px; min-height: 320px;">
            <div class="w-16 h-16 rounded-2xl flex items-center justify-center mb-5 shadow-sm"
                :class="popup.icon === 'warning' ? 'bg-red-50' : popup.icon === 'caution' ? 'bg-orange-50' : popup.icon === 'process_done' ? 'bg-green-50' : 'bg-blue-50'">
                <svg class="w-8 h-8" :class="popup.icon === 'warning' ? 'text-red-500' : popup.icon === 'caution' ? 'text-orange-500' : popup.icon === 'process_done' ? 'text-green-500' : 'text-blue-500'"
                    fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>
                </svg>
            </div>
            <p class="text-sm font-semibold text-gray-800 mb-1" x-text="popup.title"></p>
            <p class="text-sm text-gray-500 mb-7 leading-snug" x-text="popup.message"></p>
            <button @click="popup.show = false" class="px-8 py-2.5 bg-blue-600 text-white text-sm font-semibold rounded-xl hover:bg-blue-700 active:scale-95 transition-all duration-150 shadow-md hover:shadow-lg">OK</button>
        </div>
    </div>

    {{-- Bell Button --}}
    <button @click="toggle()"
        class="relative w-9 h-9 rounded-full flex items-center justify-center hover:bg-white/20 transition-all focus:outline-none flex-shrink-0">

        <svg class="w-5 h-5 text-white"
             :class="unreadCount > 0 ? 'bell-ring' : ''"
             fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>
        </svg>

        <template x-if="unreadCount > 0">
            <span class="absolute -top-1 -right-1 min-w-[18px] h-[18px] px-0.5 bg-red-500 rounded-full
                         text-white text-[10px] font-bold flex items-center justify-center border-2 border-white"
                  x-text="unreadCount > 99 ? '99+' : unreadCount">
            </span>
        </template>
    </button>

    {{-- Dropdown Panel -- Mobile responsive --}}
    <div x-show="open" x-cloak
        x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="opacity-0 -translate-y-2 scale-95"
        x-transition:enter-end="opacity-100 translate-y-0 scale-100"
        x-transition:leave="transition ease-in duration-150"
        x-transition:leave-start="opacity-100 translate-y-0 scale-100"
        x-transition:leave-end="opacity-0 -translate-y-2 scale-95"
        class="absolute right-0 mt-3 bg-white rounded-2xl shadow-2xl border border-gray-100 z-[999] notification-dropdown"
        style="top: 100%; width: 400px; max-width: 90vw;">

        {{-- Header --}}
        <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between">
            <h3 class="text-xl font-bold text-gray-900">Notifications</h3>
            <template x-if="unreadCount > 0">
                <button @click="markAllRead()"
                    class="text-xs font-medium text-blue-600 hover:text-blue-700 transition-colors">
                    Mark all as read
                </button>
            </template>
        </div>

        {{-- Loading --}}
        <template x-if="loading">
            <div class="flex items-center justify-center py-10">
                <svg class="w-6 h-6 text-gray-300 animate-spin" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"/>
                </svg>
            </div>
        </template>

        {{-- Empty state --}}
        <template x-if="!loading && notifications.length === 0">
            <div class="flex flex-col items-center justify-center py-12 px-6 text-center">
                <div class="w-12 h-12 rounded-full bg-gray-100 flex items-center justify-center mb-3">
                    <svg class="w-6 h-6 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                            d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>
                    </svg>
                </div>
                <p class="text-sm font-medium text-gray-500">No notifications</p>
                <p class="text-xs text-gray-400 mt-1">You're all caught up!</p>
            </div>
        </template>

        {{-- Notification Items --}}
        <template x-if="!loading && notifications.length > 0">
            <div class="overflow-y-auto divide-y divide-gray-100" style="max-height: 420px;">
                <template x-for="n in notifications" :key="n.id">
                    <div class="flex items-start gap-4 px-6 py-4 hover:bg-gray-50 transition-colors group cursor-pointer"
                         :class="!n.is_read ? 'bg-blue-50/40' : ''"
                         @click="markRead(n.id)">

                        {{-- Icon --}}
                        <div class="w-10 h-10 rounded-xl flex items-center justify-center flex-shrink-0 mt-0.5"
                             :class="iconBg(n.icon) + ' ' + iconColor(n.icon)">

                            {{-- notice --}}
                            <template x-if="n.icon === 'notice'">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                            </template>
                            {{-- process_done --}}
                            <template x-if="n.icon === 'process_done'">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                            </template>
                            {{-- caution --}}
                            <template x-if="n.icon === 'caution'">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                            </template>
                            {{-- warning --}}
                            <template x-if="n.icon === 'warning'">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                                </svg>
                            </template>
                        </div>

                        {{-- Content --}}
                        <div class="flex-1 min-w-0">
                            <p class="text-sm font-semibold text-gray-900 flex items-center gap-1.5">
                                <span x-text="n.title"></span>
                                <template x-if="!n.is_read">
                                    <span class="inline-block w-1.5 h-1.5 rounded-full bg-blue-500 flex-shrink-0"></span>
                                </template>
                            </p>
                            <p class="text-sm text-gray-500 mt-0.5 leading-relaxed" x-text="n.message"></p>
                            <p class="text-xs text-gray-400 mt-1.5">
                                <span x-text="n.time_ago"></span>
                                &bull;
                                <span x-text="n.date_label"></span>
                                &bull;
                                <span x-text="n.time_label"></span>
                            </p>
                        </div>

                        {{-- Delete button --}}
                        <button @click.stop="deleteNotif(n.id)"
                            class="opacity-0 group-hover:opacity-100 text-gray-300 hover:text-gray-500 transition-all flex-shrink-0 mt-0.5">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                        </button>
                    </div>
                </template>
            </div>
        </template>

        {{-- Footer --}}
        <template x-if="!loading && notifications.length > 0">
            <div class="px-6 py-3 border-t border-gray-100 flex justify-end">
                <button @click="clearAll()"
                    class="text-xs text-gray-400 hover:text-red-500 transition-colors font-medium">
                    Clear all
                </button>
            </div>
        </template>

    </div>
</div>

<script>
function notificationBell() {
    return {
        open: false,
        notifications: [],
        unreadCount: 0,
        loading: false,
        csrf: '',
        popup: { show: false, title: '', message: '', icon: 'notice' },

        init() {
            this.csrf = document.querySelector('meta[name=csrf-token]')?.content ?? '';
            this.fetchNotifications();
            setInterval(() => this.poll(), 30000);
        },

        async poll() {
            try {
                await fetch('/notifications/check-shift', {
                    method: 'POST',
                    headers: { 'X-CSRF-TOKEN': this.csrf, 'X-Requested-With': 'XMLHttpRequest' }
                });
                const res  = await fetch('/notifications', { headers: { 'X-Requested-With': 'XMLHttpRequest' } });
                const data = await res.json();
                const prevCount = this.unreadCount;
                this.unreadCount = data.unread_count;
                if (data.unread_count > prevCount && !this.open && !this.popup.show) {
                    const newest = data.notifications.find(n => !n.is_read);
                    if (newest) {
                        this.popup = { show: true, title: newest.title, message: newest.message, icon: newest.icon };
                    }
                }
                if (this.open) {
                    this.notifications = data.notifications;
                }
            } catch(e) {}
        },

        async fetchNotifications() {
            this.loading = true;
            try {
                const res = await fetch('/notifications', {
                    headers: { 'X-Requested-With': 'XMLHttpRequest' }
                });
                const data = await res.json();
                this.notifications = data.notifications;
                this.unreadCount   = data.unread_count;
            } catch(e) {}
            this.loading = false;
        },

        async markRead(id) {
            await fetch('/notifications/' + id + '/read', {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': this.csrf, 'X-Requested-With': 'XMLHttpRequest' }
            });
            const n = this.notifications.find(n => n.id === id);
            if (n && !n.is_read) { n.is_read = true; this.unreadCount = Math.max(0, this.unreadCount - 1); }
        },

        async markAllRead() {
            await fetch('/notifications/read-all', {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': this.csrf, 'X-Requested-With': 'XMLHttpRequest' }
            });
            this.notifications.forEach(n => n.is_read = true);
            this.unreadCount = 0;
        },

        async deleteNotif(id) {
            await fetch('/notifications/' + id, {
                method: 'DELETE',
                headers: { 'X-CSRF-TOKEN': this.csrf, 'X-Requested-With': 'XMLHttpRequest' }
            });
            const wasUnread = this.notifications.find(n => n.id === id && !n.is_read);
            this.notifications = this.notifications.filter(n => n.id !== id);
            if (wasUnread) this.unreadCount = Math.max(0, this.unreadCount - 1);
        },

        async clearAll() {
            await fetch('/notifications/clear', {
                method: 'DELETE',
                headers: { 'X-CSRF-TOKEN': this.csrf, 'X-Requested-With': 'XMLHttpRequest' }
            });
            this.notifications = [];
            this.unreadCount   = 0;
        },

        toggle() {
            this.open = !this.open;
            if (this.open) this.fetchNotifications();
        },

        iconBg(icon) {
            return { notice: 'bg-blue-100', process_done: 'bg-green-100', caution: 'bg-orange-100', warning: 'bg-red-100' }[icon] ?? 'bg-blue-100';
        },

        iconColor(icon) {
            return { notice: 'text-blue-500', process_done: 'text-green-600', caution: 'text-orange-500', warning: 'text-red-500' }[icon] ?? 'text-blue-500';
        },
    };
}
</script>

<style>
[x-cloak] { display: none !important; }

@keyframes bell-shake {
    0%, 100% { transform: rotate(0deg); }
    10%       { transform: rotate(-18deg); }
    30%       { transform: rotate(18deg); }
    50%       { transform: rotate(-12deg); }
    70%       { transform: rotate(12deg); }
    90%       { transform: rotate(-6deg); }
}
.bell-ring {
    animation: bell-shake 2s ease-in-out infinite;
    transform-origin: top center;
}

/* Mobile responsiveness for notification */
@media (max-width: 640px) {
    .notification-dropdown {
        width: auto !important;
        min-width: 300px;
        max-width: calc(100vw - 32px) !important;
        right: 0 !important;
        left: auto !important;
        max-width: none !important;
    }
}	

</style>
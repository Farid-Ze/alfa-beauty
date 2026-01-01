{{-- Toast Notification Component --}}
{{-- Displays flash messages and Livewire notify events --}}
<div 
    x-data="{ 
        notifications: [],
        add(notification) {
            const id = Date.now();
            this.notifications.push({ id, ...notification });
            setTimeout(() => this.remove(id), notification.duration || 5000);
        },
        remove(id) {
            this.notifications = this.notifications.filter(n => n.id !== id);
        }
    }"
    x-on:notify.window="add($event.detail)"
    class="toast-container"
    aria-live="polite"
    aria-atomic="true"
>
    {{-- Session Flash Messages (from Laravel) --}}
    @if(session('success'))
        <div 
            x-data="{ show: true }" 
            x-show="show" 
            x-init="setTimeout(() => show = false, 5000)"
            x-transition:enter="toast-enter"
            x-transition:enter-start="toast-enter-start"
            x-transition:enter-end="toast-enter-end"
            x-transition:leave="toast-leave"
            x-transition:leave-start="toast-leave-start"
            x-transition:leave-end="toast-leave-end"
            class="toast toast-success"
            role="alert"
        >
            <div class="toast-icon">
                <svg width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                </svg>
            </div>
            <div class="toast-content">
                <p class="toast-message">{{ session('success') }}</p>
            </div>
            <button type="button" class="toast-close" @click="show = false" aria-label="Close">
                <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        </div>
    @endif

    @if(session('error'))
        <div 
            x-data="{ show: true }" 
            x-show="show" 
            x-init="setTimeout(() => show = false, 7000)"
            x-transition:enter="toast-enter"
            x-transition:enter-start="toast-enter-start"
            x-transition:enter-end="toast-enter-end"
            x-transition:leave="toast-leave"
            x-transition:leave-start="toast-leave-start"
            x-transition:leave-end="toast-leave-end"
            class="toast toast-error"
            role="alert"
        >
            <div class="toast-icon">
                <svg width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </div>
            <div class="toast-content">
                <p class="toast-message">{{ session('error') }}</p>
            </div>
            <button type="button" class="toast-close" @click="show = false" aria-label="Close">
                <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        </div>
    @endif

    @if(session('warning'))
        <div 
            x-data="{ show: true }" 
            x-show="show" 
            x-init="setTimeout(() => show = false, 6000)"
            x-transition:enter="toast-enter"
            x-transition:enter-start="toast-enter-start"
            x-transition:enter-end="toast-enter-end"
            x-transition:leave="toast-leave"
            x-transition:leave-start="toast-leave-start"
            x-transition:leave-end="toast-leave-end"
            class="toast toast-warning"
            role="alert"
        >
            <div class="toast-icon">
                <svg width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                </svg>
            </div>
            <div class="toast-content">
                <p class="toast-message">{{ session('warning') }}</p>
            </div>
            <button type="button" class="toast-close" @click="show = false" aria-label="Close">
                <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        </div>
    @endif

    @if(session('info'))
        <div 
            x-data="{ show: true }" 
            x-show="show" 
            x-init="setTimeout(() => show = false, 5000)"
            x-transition:enter="toast-enter"
            x-transition:enter-start="toast-enter-start"
            x-transition:enter-end="toast-enter-end"
            x-transition:leave="toast-leave"
            x-transition:leave-start="toast-leave-start"
            x-transition:leave-end="toast-leave-end"
            class="toast toast-info"
            role="alert"
        >
            <div class="toast-icon">
                <svg width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
            </div>
            <div class="toast-content">
                <p class="toast-message">{{ session('info') }}</p>
            </div>
            <button type="button" class="toast-close" @click="show = false" aria-label="Close">
                <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        </div>
    @endif

    {{-- Dynamic Livewire/Alpine Notifications --}}
    <template x-for="notification in notifications" :key="notification.id">
        <div 
            x-show="true"
            x-transition:enter="toast-enter"
            x-transition:enter-start="toast-enter-start"
            x-transition:enter-end="toast-enter-end"
            x-transition:leave="toast-leave"
            x-transition:leave-start="toast-leave-start"
            x-transition:leave-end="toast-leave-end"
            :class="'toast toast-' + (notification.type || 'info')"
            role="alert"
        >
            <div class="toast-icon">
                <template x-if="notification.type === 'success'">
                    <svg width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                    </svg>
                </template>
                <template x-if="notification.type === 'error'">
                    <svg width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </template>
                <template x-if="notification.type === 'warning'">
                    <svg width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                    </svg>
                </template>
                <template x-if="notification.type === 'info' || !notification.type">
                    <svg width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </template>
            </div>
            <div class="toast-content">
                <p class="toast-message" x-text="notification.message"></p>
            </div>
            <button type="button" class="toast-close" @click="remove(notification.id)" aria-label="Close">
                <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        </div>
    </template>
</div>

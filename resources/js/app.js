import './bootstrap';
import collapse from '@alpinejs/collapse';

// Register Alpine plugins before Livewire/Alpine starts
document.addEventListener('alpine:init', () => {
    window.Alpine.plugin(collapse);
});

// window.Alpine will be automatically set by Livewire if not already present.
// We do NOT call Alpine.start() here because Livewire 3+ handles it.


import './bootstrap';
import collapse from '@alpinejs/collapse';

// Alpine is already globally available in Livewire 3.
// We just need to register plugins before Livewire starts it.
document.addEventListener('livewire:init', () => {
    window.Alpine.plugin(collapse);
});

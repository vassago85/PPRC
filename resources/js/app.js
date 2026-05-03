// Livewire 4 bundles its own Alpine instance and exposes it as window.Alpine.
// Importing alpinejs here would mount a second instance, breaking x-data
// components and triggering "Detected multiple instances of Alpine running".

// Theme management
class ThemeManager {
    constructor() {
        this.theme = localStorage.getItem('theme') || 'light';
        this.init();
    }

    init() {
        this.applyTheme();
        this.setupToggle();
    }

    applyTheme() {
        if (this.theme === 'dark') {
            document.documentElement.classList.add('dark');
        } else {
            document.documentElement.classList.remove('dark');
        }
    }

    toggle() {
        this.theme = this.theme === 'light' ? 'dark' : 'light';
        localStorage.setItem('theme', this.theme);
        this.applyTheme();
        this.updateToggleState();
    }

    setupToggle() {
        const toggle = document.getElementById('theme-toggle');
        if (toggle) {
            toggle.checked = this.theme === 'dark';
            toggle.addEventListener('change', () => this.toggle());
        }
    }

    updateToggleState() {
        const toggle = document.getElementById('theme-toggle');
        if (toggle) {
            toggle.checked = this.theme === 'dark';
        }
    }
}

// Initialize theme on page load
document.addEventListener('DOMContentLoaded', () => {
    new ThemeManager();
});
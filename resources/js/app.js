import './bootstrap';

import Alpine from 'alpinejs';
import Chart from 'chart.js/auto';
import { getEffectiveTheme, initTheme, setTheme, toggleTheme } from './theme';

window.Alpine = Alpine;
window.Chart = Chart;

initTheme();

Alpine.data('themeToggle', () => ({
    theme: getEffectiveTheme(),
    init() {
        window.addEventListener('theme-changed', (event) => {
            this.theme = event.detail.theme;
        });
    },
    toggle() {
        this.theme = toggleTheme();
    },
    isDark() {
        return this.theme === 'dark';
    },
}));

window.setTheme = setTheme;
window.toggleTheme = toggleTheme;

Alpine.start();

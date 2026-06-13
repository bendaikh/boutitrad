const STORAGE_KEY = 'boutitrad-theme';

export function getStoredTheme() {
    return localStorage.getItem(STORAGE_KEY);
}

export function getSystemTheme() {
    return window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';
}

export function getEffectiveTheme() {
    return getStoredTheme() || getSystemTheme();
}

export function applyTheme(theme) {
    document.documentElement.classList.toggle('dark', theme === 'dark');
    document.documentElement.style.colorScheme = theme;
}

export function setTheme(theme) {
    localStorage.setItem(STORAGE_KEY, theme);
    applyTheme(theme);
    window.dispatchEvent(new CustomEvent('theme-changed', { detail: { theme } }));
}

export function toggleTheme() {
    const next = document.documentElement.classList.contains('dark') ? 'light' : 'dark';
    setTheme(next);
    return next;
}

export function initTheme() {
    applyTheme(getEffectiveTheme());

    window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', (event) => {
        if (! getStoredTheme()) {
            applyTheme(event.matches ? 'dark' : 'light');
        }
    });
}

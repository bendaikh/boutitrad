import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';

/** @type {import('tailwindcss').Config} */
export default {
    darkMode: 'class',

    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
    ],

    theme: {
        extend: {
            fontFamily: {
                sans: ['Inter', ...defaultTheme.fontFamily.sans],
                tradition: ['Scheherazade New', 'Amiri', 'Cormorant Garamond', ...defaultTheme.fontFamily.serif],
            },
            colors: {
                brand: {
                    50: '#eff6ff',
                    100: '#dbeafe',
                    200: '#bfdbfe',
                    300: '#93c5fd',
                    400: '#60a5fa',
                    500: '#3b82f6',
                    600: '#2563eb',
                    700: '#1d4ed8',
                    800: '#1e3a8a',
                    900: '#172554',
                },
                surface: {
                    DEFAULT: '#ffffff',
                    muted: '#f4f6f9',
                },
                royal: {
                    DEFAULT: '#00332B',
                    600: '#004D40',
                    700: '#00332B',
                    800: '#002820',
                },
                chart: {
                    validated: '#22c55e',
                    pending: '#3b82f6',
                    cancelled: '#ef4444',
                    returns: '#f97316',
                },
            },
            boxShadow: {
                card: '0 1px 3px 0 rgb(0 0 0 / 0.06), 0 1px 2px -1px rgb(0 0 0 / 0.06)',
                'card-md': '0 4px 6px -1px rgb(0 0 0 / 0.07), 0 2px 4px -2px rgb(0 0 0 / 0.05)',
            },
        },
    },

    plugins: [forms],
};

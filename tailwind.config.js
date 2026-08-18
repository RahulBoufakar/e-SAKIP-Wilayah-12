const defaultTheme = require('tailwindcss/defaultTheme');

/** @type {import('tailwindcss').Config} */
module.exports = {
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
    ],

    theme: {
        extend: {
            fontFamily: {
                sans: ['"Plus Jakarta Sans"', 'ui-sans-serif', 'system-ui', 'sans-serif'],
                mono: ['"IBM Plex Mono"', 'ui-monospace', 'monospace'],
            },
            colors: {
                ink: {
                    950: '#0a2233',
                    900: '#0d3145',
                    800: '#123b52',
                },
                // Dipakai di seluruh halaman Admin (bg-brand-600, text-brand-700, dst.)
                // — lihat resources/views/components/admin-layout.blade.php
                brand: {
                    50: '#effcfb',   // Diperbarui
                    100: '#c7f5f1',  // Diperbarui
                    200: '#a9e6e6',  // Nilai lama (dipertahankan agar tidak error jika terpakai)
                    300: '#5fd6cb',  // Diperbarui
                    400: '#3fb5b8',  // Nilai lama
                    500: '#22969c',  // Nilai lama
                    600: '#0f766e',  // Diperbarui
                    700: '#0e6b63',  // Diperbarui
                    800: '#134e4a',  // Diperbarui
                    900: '#0b3b38',  // Diperbarui
                },
            },
            boxShadow: {
                card: '0 1px 2px 0 rgb(13 49 69 / 0.06), 0 1px 3px 0 rgb(13 49 69 / 0.08)',
            },
        },
    },

    plugins: [require('@tailwindcss/forms')],
};

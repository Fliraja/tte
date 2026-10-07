import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';

/** @type {import('tailwindcss').Config} */
export default {
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
    ],

    theme: {
        extend: {
            fontFamily: {
                sans: ['Inter', ...defaultTheme.fontFamily.sans],
            },
            colors: {
                brand: {
                    400: '#38bdf8',
                    500: '#0ea5e9',
                    600: '#0284c7',
                },
            },
            animation: {
                scan: 'scan 3s cubic-bezier(0.4, 0, 0.2, 1) infinite',
                'pulse-slow': 'pulse 4s cubic-bezier(0.4, 0, 0.6, 1) infinite',
            },
            keyframes: {
                scan: {
                    '0%, 100%': { transform: 'translateY(-10px)' },
                    '50%': { transform: 'translateY(160px)' },
                },
            },
        },
    },

    plugins: [forms],
};

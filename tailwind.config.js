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
            colors: {
                dark: '#050505',
                'dark-100': '#111111',
                'dark-200': '#1a1a1a',
                'electric-blue': '#00b7ff',
                'electric-violet': '#7000ff',
            },
            fontFamily: {
                sans: ['Figtree', ...defaultTheme.fontFamily.sans],
            },
            animation: {
                'infinite-scroll': 'infinite-scroll 30s linear infinite',
                'pulse-slow': 'pulse 4s cubic-bezier(0.4, 0, 0.6, 1) infinite',
                'reveal': 'reveal 1s ease-out forwards',
                'float': 'float 8s ease-in-out infinite',
                'glow-pulse': 'glow-pulse 3s ease-in-out infinite',
                'slide-down': 'slide-down 0.3s ease-out',
                'fade-up': 'fade-up 0.8s ease-out forwards',
            },
            keyframes: {
                'infinite-scroll': {
                    from: { transform: 'translateX(0)' },
                    to: { transform: 'translateX(-50%)' },
                },
                'reveal': {
                    '0%': { opacity: '0', transform: 'translateY(20px)' },
                    '100%': { opacity: '1', transform: 'translateY(0)' },
                },
                'float': {
                    '0%, 100%': { transform: 'translateY(0px)' },
                    '50%': { transform: 'translateY(-20px)' },
                },
                'glow-pulse': {
                    '0%, 100%': { boxShadow: '0 0 20px rgba(0,183,255,0.3)' },
                    '50%': { boxShadow: '0 0 50px rgba(0,183,255,0.6)' },
                },
                'slide-down': {
                    from: { opacity: '0', transform: 'translateY(-8px)' },
                    to: { opacity: '1', transform: 'translateY(0)' },
                },
                'fade-up': {
                    from: { opacity: '0', transform: 'translateY(24px)' },
                    to: { opacity: '1', transform: 'translateY(0)' },
                },
            },
            boxShadow: {
                'glow-blue': '0 0 30px rgba(0,183,255,0.35)',
                'glow-blue-lg': '0 0 60px rgba(0,183,255,0.5)',
                'glow-violet': '0 0 30px rgba(112,0,255,0.35)',
                'glow-violet-lg': '0 0 60px rgba(112,0,255,0.5)',
            },
        },
    },

    plugins: [forms],
};

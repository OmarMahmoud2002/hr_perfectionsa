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
                sans: ['Cairo', 'Tajawal', ...defaultTheme.fontFamily.sans],
            },
            colors: {
                primary: {
                    50:  '#eef7f7',
                    100: '#d5ecec',
                    200: '#aad9d8',
                    300: '#75bfbe',
                    400: '#4d9b97',  // primary teal
                    500: '#3d8480',
                    600: '#317c77',  // dark teal
                    700: '#265e5b',
                    800: '#1d4542',
                    900: '#142f2e',
                    DEFAULT: '#4d9b97',
                },
                secondary: {
                    50:  '#eef5fb',
                    100: '#d3e6f5',
                    200: '#a7cdeb',
                    300: '#73b0db',
                    400: '#4596cf',  // primary blue
                    500: '#357cb8',
                    600: '#31719d',  // dark blue
                    700: '#255475',
                    800: '#1a3d57',
                    900: '#10273a',
                    DEFAULT: '#4596cf',
                },
                gold: {
                    50:  '#fefce8',
                    100: '#fef9c3',
                    200: '#fef08a',
                    300: '#fde047',
                    400: '#e7c539',  // gold/yellow
                    500: '#ca9a0a',
                    600: '#a37808',
                    700: '#7c5b06',
                    DEFAULT: '#e7c539',
                },
                neutral: {
                    400: '#a19f9e',  // gray
                    DEFAULT: '#a19f9e',
                },
            },
            backgroundImage: {
                'sidebar-gradient': 'linear-gradient(180deg, #31719d 0%, #317c77 100%)',
            },
            boxShadow: {
                'card': '0 2px 12px 0 rgba(49, 113, 157, 0.08)',
                'card-hover': '0 8px 24px 0 rgba(49, 113, 157, 0.15)',
            },
        },
    },

    plugins: [forms],
};

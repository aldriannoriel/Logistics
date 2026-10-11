/** @type {import('tailwindcss').Config} */
export default {
    content: [
        './resources/**/*.blade.php',
        './resources/**/*.js',
        './resources/**/*.vue',
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
    ],
    theme: {
        extend: {
            fontFamily: {
                sans: ['Inter', 'Segoe UI', 'ui-sans-serif', 'system-ui', 'sans-serif'],
            },
            colors: {
                primary: '#0d7377',
                'primary-strong': '#066268',
                secondary: '#d97b06',
                success: '#2d6a4f',
                warning: '#f77f00',
                error: '#c1121f',
                neutral: {
                    50: '#f5f7fa',
                    100: '#f8fafc',
                    200: '#e2e8f0',
                    300: '#cbd5e0',
                    700: '#4a5568',
                    900: '#1a202c',
                },
            },
        },
    },
    plugins: [require('@tailwindcss/forms')],
}
import type { Config } from 'tailwindcss';

export default {
    content: [
        './app.vue',
        './components/**/*.{vue,js,ts}',
        './composables/**/*.{js,ts}',
        './layouts/**/*.vue',
        './modules/**/*.{vue,js,ts}',
        './plugins/**/*.{js,ts}',
    ],

    theme: {
        extend: {
            fontFamily: {
                sans: ['Poppins', 'ui-sans-serif', 'system-ui', 'sans-serif'],
            },
            colors: {
                komm: {
                    50: '#F1F6F7',
                    100: '#E3ECEE',
                    200: '#C7D9DD',
                    300: '#A3BEC4',
                    400: '#7FA0A8',
                    500: '#4E6A70',
                    600: '#3A5258',
                    700: '#2D4347',
                    800: '#243C41',
                    900: '#192E31',
                    950: '#101F22',
                },
                sand: {
                    50: '#FAF7F2',
                    100: '#F5EBE1',
                    200: '#EDE4CE',
                    300: '#DDD0B5',
                    400: '#C4AD8A',
                    500: '#A89370',
                    600: '#8C7759',
                    700: '#705D44',
                    800: '#574832',
                    900: '#3D3222',
                    950: '#2A2217',
                },
            },
        },
    },
} satisfies Partial<Config>;

import vue from '@vitejs/plugin-vue';
import { defineConfig } from 'vitest/config';
import { resolve } from 'path';

export default defineConfig({
    plugins: [
        vue(),
        {
            name: 'nuxt-import-meta-stubs',
            transform(code) {
                return code.replace(/import\.meta\.client/g, 'true')
                    .replace(/import\.meta\.server/g, 'false');
            },
        },
    ],
    resolve: {
        alias: {
            '~': resolve(__dirname),
            '@': resolve(__dirname),
        },
    },
    test: {
        environment: 'happy-dom',
        include: ['tests/**/*.test.ts'],
        coverage: {
            provider: 'v8',
            reporter: ['text', 'cobertura', 'lcov'],
            reportsDirectory: 'coverage',
            include: ['app.vue', 'layouts/**/*.{vue,ts}', 'components/**/*.{vue,ts}', 'modules/**/*.{vue,ts}', 'composables/**', 'middleware/**'],
            exclude: ['tests/**'],
        },
    },
});

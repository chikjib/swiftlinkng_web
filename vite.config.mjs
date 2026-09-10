import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import vue from '@vitejs/plugin-vue';
import path from 'node:path';

export default defineConfig({
    envPrefix: ['VITE_', 'MIX_'],
    plugins: [
        laravel({
            input: [
                'resources/js/app.js',
                'resources/sass/app.scss',
                'resources/sass/swiftlink.scss',
            ],
            refresh: [
                'resources/views/**',
                'resources/js/**',
                'resources/sass/**',
            ],
        }),
        vue({
            template: {
                compilerOptions: {
                    compatConfig: { MODE: 2 },
                },
                transformAssetUrls: {
                    base: null,
                    includeAbsolute: false,
                },
            },
        }),
    ],
    resolve: {
        alias: {
            vue: '@vue/compat',
            '@': path.resolve(import.meta.dirname, 'resources/js'),
            'vue-content-loading': path.resolve(import.meta.dirname, 'resources/js/components/LoadingSkeletons.js'),
        },
    },
    css: {
        preprocessorOptions: {
            scss: {
                quietDeps: true,
            },
        },
    },
    build: {
        sourcemap: false,
        chunkSizeWarningLimit: 1600,
    },
});

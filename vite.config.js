import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import vue from '@vitejs/plugin-vue';

export default defineConfig({
    plugins: [
        laravel({
            input: 'resources/js/app.js',
            refresh: true,
        }),
        vue({
            template: {
                transformAssetUrls: {
                    base: null,
                    includeAbsolute: false,
                },
            },
        }),
    ],
    define: {
        'import.meta.env.VITE_PUSHER_APP_KEY': JSON.stringify('805676c1218fb0333ec3'),
        'import.meta.env.VITE_PUSHER_APP_CLUSTER': JSON.stringify('eu'),
    },
});

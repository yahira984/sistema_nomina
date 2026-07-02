import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import vue from '@vitejs/plugin-vue';

export default defineConfig({
    plugins: [
        laravel({
            input: 'resources/js/app.js',
            refresh: true,
        }),
        vue(),
    ],
    /*
    server: {
        host: '0.0.0.0', // Escuchar en todas las IPs
        hmr: {
            host: '10.0.0.83', // <--- CAMBIA ESTO POR LA IP QUE ANOTASTE (ej. 192.168.1.15)
        },
    },*/
});
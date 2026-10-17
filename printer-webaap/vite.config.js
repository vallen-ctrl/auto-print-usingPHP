import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import { bunny } from 'laravel-vite-plugin/fonts';
import tailwindcss from '@tailwindcss/vite';

export default defineConfig({
    plugins: [
        laravel({
            input: [
                'resources/css/app.css', 
                'resources/css/beranda.css', 
                'resources/css/upload-terjadwal.css', 
                'resources/css/formulir-terjadwal.css', 
                'resources/css/formulir-kilat.css',
                'resources/js/pages/beranda.js',
                'resources/js/pages/upload-terjadwal.js',
                'resources/js/pages/formulir-terjadwal.js',
                'resources/js/pages/formulir-kilat.js',
                'resources/js/dashboard/chart-pendapatan.js',
                'resources/js/dashboard/chart-layanan-kertas.js',
                'resources/js/dashboard/tabel-order.js',
                'resources/js/dashboard/tipe-file.js',
                'resources/js/shared/modal.js',
            ],
            refresh: true,
            fonts: [
                bunny('Instrument Sans', {
                    weights: [400, 500, 600],
                }),
            ],
        }),
        tailwindcss(),
    ],
    server: {
        watch: {
            ignored: ['**/storage/framework/views/**'],
        },
    },
});

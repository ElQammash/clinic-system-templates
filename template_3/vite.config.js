import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import { google } from 'laravel-vite-plugin/fonts';
import tailwindcss from '@tailwindcss/vite';

// Template 3 — "Warm & Friendly": Baloo Bhaijaan 2 (rounded Arabic) + Nunito.
// Fonts are downloaded from Google Fonts at build time and served locally.
export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.js', 'resources/css/print.css'],
            refresh: true,
            fonts: [
                google('Baloo Bhaijaan 2', {
                    alias: 'arabic',
                    variable: '--font-arabic',
                    weights: [400, 500, 600, 700, 800],
                    subsets: ['arabic', 'latin'],
                    preload: [{ weight: 500 }, { weight: 700 }],
                }),
                google('Nunito', {
                    alias: 'latin',
                    variable: '--font-latin',
                    weights: [400, 600, 700, 800],
                    preload: [{ weight: 600 }, { weight: 800 }],
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

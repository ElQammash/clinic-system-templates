import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import { google } from 'laravel-vite-plugin/fonts';
import tailwindcss from '@tailwindcss/vite';

// Template 1 — "Clinical Clean": IBM Plex Sans Arabic + Inter.
// Fonts are downloaded from Google Fonts at build time and served locally.
export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.js', 'resources/css/print.css'],
            refresh: true,
            fonts: [
                google('IBM Plex Sans Arabic', {
                    alias: 'arabic',
                    variable: '--font-arabic',
                    weights: [400, 500, 600, 700],
                    subsets: ['arabic', 'latin'],
                    preload: [{ weight: 400 }, { weight: 600 }],
                }),
                google('Inter', {
                    alias: 'latin',
                    variable: '--font-latin',
                    weights: [400, 500, 600, 700],
                    preload: [{ weight: 400 }, { weight: 600 }],
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

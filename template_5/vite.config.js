import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import { google } from 'laravel-vite-plugin/fonts';
import tailwindcss from '@tailwindcss/vite';

// Template 5 — "Tablet-First Reception": Cairo (clear Arabic at a distance) + Lexend (built for readability).
// Fonts are downloaded from Google Fonts at build time and served locally.
export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.js', 'resources/css/print.css'],
            refresh: true,
            fonts: [
                google('Cairo', {
                    alias: 'arabic',
                    variable: '--font-arabic',
                    weights: [400, 500, 600, 700, 800],
                    subsets: ['arabic', 'latin'],
                    preload: [{ weight: 600 }, { weight: 800 }],
                }),
                google('Lexend', {
                    alias: 'latin',
                    variable: '--font-latin',
                    weights: [400, 500, 600, 700],
                    preload: [{ weight: 500 }, { weight: 700 }],
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

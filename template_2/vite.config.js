import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import { google } from 'laravel-vite-plugin/fonts';
import tailwindcss from '@tailwindcss/vite';

// Template 2 — "Dark Pro": Readex Pro (Arabic) + Manrope (Latin) + JetBrains Mono (figures & IDs).
// Fonts are downloaded from Google Fonts at build time and served locally.
export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.js', 'resources/css/print.css'],
            refresh: true,
            fonts: [
                google('Readex Pro', {
                    alias: 'arabic',
                    variable: '--font-arabic',
                    weights: [400, 500, 600, 700],
                    subsets: ['arabic', 'latin'],
                    preload: [{ weight: 400 }, { weight: 600 }],
                }),
                google('Manrope', {
                    alias: 'latin',
                    variable: '--font-latin',
                    weights: [400, 500, 600, 700],
                    preload: [{ weight: 500 }],
                }),
                google('JetBrains Mono', {
                    alias: 'code',
                    variable: '--font-code',
                    weights: [400, 600],
                    preload: false,
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

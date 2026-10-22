import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import { google } from 'laravel-vite-plugin/fonts';
import tailwindcss from '@tailwindcss/vite';

// Template 4 — "Luxury Minimal": serif headings (Amiri + Cormorant Garamond),
// light sans body (Alexandria + Jost). Fonts are downloaded at build time and served locally.
export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.js', 'resources/css/print.css'],
            refresh: true,
            fonts: [
                google('Alexandria', {
                    alias: 'arabic',
                    variable: '--font-arabic',
                    weights: [300, 400, 500, 600],
                    subsets: ['arabic', 'latin'],
                    preload: [{ weight: 300 }, { weight: 500 }],
                }),
                google('Amiri', {
                    alias: 'arabic-serif',
                    variable: '--font-arabic-serif',
                    weights: [400, 700],
                    subsets: ['arabic', 'latin'],
                    preload: [{ weight: 400 }],
                }),
                google('Jost', {
                    alias: 'latin',
                    variable: '--font-latin',
                    weights: [300, 400, 500, 600],
                    preload: [{ weight: 300 }, { weight: 500 }],
                }),
                google('Cormorant Garamond', {
                    alias: 'latin-serif',
                    variable: '--font-latin-serif',
                    weights: [400, 500, 600],
                    preload: [{ weight: 500 }],
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

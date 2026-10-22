import Chart from 'chart.js/auto';

/*
 * Template 2 — "Dark Pro" front-end:
 *  - shell(): sidebar pin/hover state and the dark/light theme (both remembered in localStorage)
 *  - chart(config): Chart.js component that reads colors from the current theme tokens
 */
const store = {
    get(key) {
        try {
            return localStorage.getItem(key);
        } catch {
            return null;
        }
    },
    set(key, value) {
        try {
            localStorage.setItem(key, value);
        } catch {
            /* private mode */
        }
    },
};

function applyTheme() {
    document.documentElement.classList.toggle('dark', store.get('theme') !== 'light');
}

// Livewire's SPA navigation re-renders <html>; keep the user's theme choice.
document.addEventListener('livewire:navigated', applyTheme);

document.addEventListener('alpine:init', () => {
    window.Alpine.data('shell', () => ({
        pinned: store.get('sidebar') === 'pinned',
        hover: false,
        mobile: false,
        dark: store.get('theme') !== 'light',

        togglePin() {
            this.pinned = !this.pinned;
            this.hover = false;
            store.set('sidebar', this.pinned ? 'pinned' : 'rail');
        },

        toggleTheme() {
            this.dark = !this.dark;
            store.set('theme', this.dark ? 'dark' : 'light');
            applyTheme();
            window.dispatchEvent(new CustomEvent('theme-changed'));
        },
    }));

    window.Alpine.data('chart', (config) => ({
        chart: null,

        init() {
            this.draw();
            this.onTheme = () => {
                this.chart?.destroy();
                this.draw();
            };
            window.addEventListener('theme-changed', this.onTheme);
        },

        draw() {
            const styles = getComputedStyle(document.documentElement);
            const token = (name, fallback) => styles.getPropertyValue(name).trim() || fallback;
            const resolve = (value) =>
                ({
                    primary: token('--color-primary-500', '#10b981'),
                    'primary-soft': 'rgba(16, 185, 129, 0.15)',
                    secondary: token('--color-secondary-500', '#06b6d4'),
                })[value] ?? value;

            Chart.defaults.font.family = getComputedStyle(document.body).fontFamily;
            Chart.defaults.font.size = 11;
            Chart.defaults.color = token('--color-muted', '#7d8ba1');
            Chart.defaults.borderColor = token('--color-line', '#233047');

            const data = structuredClone(config.data);
            data.datasets.forEach((dataset) => {
                ['backgroundColor', 'borderColor'].forEach((key) => {
                    if (Array.isArray(dataset[key])) dataset[key] = dataset[key].map(resolve);
                    else if (dataset[key]) dataset[key] = resolve(dataset[key]);
                });
            });

            this.chart = new Chart(this.$el, { ...config, data });
        },

        destroy() {
            window.removeEventListener('theme-changed', this.onTheme);
            this.chart?.destroy();
        },
    }));
});

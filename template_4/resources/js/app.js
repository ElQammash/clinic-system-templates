import Chart from 'chart.js/auto';

/*
 * Template 4 chart component (Chart.js). Usage:
 *   <canvas x-data="chart(@js($config))"></canvas>
 * where $config is a Chart.js config object built in Blade. Colors follow the
 * current CSS palette so clinic branding applies to charts too.
 */
document.addEventListener('alpine:init', () => {
    window.Alpine.data('chart', (config) => ({
        chart: null,

        init() {
            const styles = getComputedStyle(document.documentElement);
            const token = (name, fallback) => styles.getPropertyValue(name).trim() || fallback;
            const font = getComputedStyle(document.body).fontFamily;

            Chart.defaults.font.family = font;
            Chart.defaults.color = token('--color-muted', '#64748b');
            Chart.defaults.borderColor = token('--color-line', '#e5eaf0');

            // "primary" / "primary-soft" placeholders resolve to the branding palette.
            const resolve = (value) => {
                if (value === 'primary') return token('--color-primary-600', '#b08d57');
                if (value === 'primary-soft') return token('--color-primary-100', '#efe6d6');
                if (value === 'secondary') return token('--color-secondary-500', '#171717');
                return value;
            };

            config.data.datasets.forEach((dataset) => {
                ['backgroundColor', 'borderColor'].forEach((key) => {
                    if (Array.isArray(dataset[key])) dataset[key] = dataset[key].map(resolve);
                    else if (dataset[key]) dataset[key] = resolve(dataset[key]);
                });
            });

            this.chart = new Chart(this.$el, config);
        },

        destroy() {
            this.chart?.destroy();
        },
    }));
});

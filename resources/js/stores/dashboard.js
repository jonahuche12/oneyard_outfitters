import {
    Chart,
    BarController,
    BarElement,
    CategoryScale,
    LinearScale,
    Tooltip,
} from 'chart.js';

Chart.register(
    BarController,
    BarElement,
    CategoryScale,
    LinearScale,
    Tooltip
);

document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('[data-store-chart]').forEach((canvas) => {
        let points;

        try {
            points = JSON.parse(canvas.dataset.chartData || '[]');
        } catch (error) {
            console.error('Unable to read store dashboard chart data.', error);
            return;
        }

        if (!Array.isArray(points) || points.length === 0) {
            return;
        }

        const isRevenue = canvas.dataset.storeChart === 'revenue';

        const compactCurrency = new Intl.NumberFormat('en-NG', {
            notation: 'compact',
            maximumFractionDigits: 1,
        });

        const exactCurrency = new Intl.NumberFormat('en-NG', {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2,
        });

        new Chart(canvas, {
            type: 'bar',

            data: {
                labels: points.map((point) => point.label),
                datasets: [{
                    label: isRevenue ? 'Sales revenue' : 'Payments collected',
                    data: points.map((point) => Number(point.value)),
                    backgroundColor: isRevenue ? '#334155' : '#4f46e5',
                    hoverBackgroundColor: isRevenue ? '#1e293b' : '#4338ca',
                    borderRadius: 6,
                    borderSkipped: false,
                    maxBarThickness: 44,
                }],
            },

            options: {
                responsive: true,
                maintainAspectRatio: false,

                interaction: {
                    mode: 'index',
                    intersect: false,
                },

                plugins: {
                    legend: {
                        display: false,
                    },

                    tooltip: {
                        callbacks: {
                            label(context) {
                                return `₦${exactCurrency.format(context.parsed.y)}`;
                            },
                        },
                    },
                },

                scales: {
                    x: {
                        grid: {
                            display: false,
                        },
                        ticks: {
                            maxRotation: 0,
                            autoSkip: true,
                            maxTicksLimit: 8,
                        },
                    },

                    y: {
                        beginAtZero: true,
                        grid: {
                            color: 'rgba(148, 163, 184, 0.18)',
                        },
                        ticks: {
                            callback(value) {
                                return `₦${compactCurrency.format(value)}`;
                            },
                        },
                    },
                },
            },
        });
    });
});

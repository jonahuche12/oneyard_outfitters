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

        const chartType = canvas.dataset.storeChart;
        const isThroughput = chartType === 'throughput';

        if (isThroughput) {
            if (
                !points
                || !Array.isArray(points.labels)
                || !Array.isArray(points.datasets)
                || points.labels.length === 0
                || points.datasets.length === 0
            ) {
                return;
            }
        } else if (!Array.isArray(points) || points.length === 0) {
            return;
        }

        const isRevenue = chartType === 'revenue';

        const compactCurrency = new Intl.NumberFormat('en-NG', {
            notation: 'compact',
            maximumFractionDigits: 1,
        });

        const exactCurrency = new Intl.NumberFormat('en-NG', {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2,
        });

        const exactQuantity = new Intl.NumberFormat('en-NG', {
            minimumFractionDigits: 0,
            maximumFractionDigits: 3,
        });

        const datasets = isThroughput
            ? points.datasets.map((dataset) => ({
                label: dataset.label,
                data: dataset.data.map((value) => Number(value)),
                backgroundColor: dataset.backgroundColor,
                borderRadius: 4,
                borderSkipped: false,
                maxBarThickness: 22,
            }))
            : [{
                label: isRevenue ? 'Sales revenue' : 'Payments collected',
                data: points.map((point) => Number(point.value)),
                backgroundColor: isRevenue ? '#334155' : '#4f46e5',
                hoverBackgroundColor: isRevenue ? '#1e293b' : '#4338ca',
                borderRadius: 6,
                borderSkipped: false,
                maxBarThickness: 44,
            }];

        new Chart(canvas, {
            type: 'bar',

            data: {
                labels: isThroughput
                    ? points.labels
                    : points.map((point) => point.label),
                datasets,
            },

            options: {
                indexAxis: isThroughput ? 'y' : 'x',
                responsive: true,
                maintainAspectRatio: false,

                interaction: {
                    mode: 'index',
                    intersect: false,
                },

                plugins: {
                    legend: {
                        display: isThroughput,
                        position: 'bottom',
                    },

                    tooltip: {
                        callbacks: {
                            label(context) {
                                if (isThroughput) {
                                    return `${context.dataset.label}: ${exactQuantity.format(context.parsed.x)}`;
                                }

                                return `₦${exactCurrency.format(context.parsed.y)}`;
                            },
                        },
                    },
                },

                scales: {
                    x: {
                        beginAtZero: true,
                        grid: {
                            display: isThroughput,
                            color: 'rgba(148, 163, 184, 0.18)',
                        },
                        ticks: {
                            maxRotation: 0,
                            autoSkip: !isThroughput,
                            maxTicksLimit: 8,
                            callback(value) {
                                return isThroughput
                                    ? exactQuantity.format(value)
                                    : value;
                            },
                        },
                    },

                    y: {
                        beginAtZero: !isThroughput,
                        grid: {
                            display: false,
                        },
                        ticks: {
                            maxRotation: 0,
                            autoSkip: !isThroughput,
                            maxTicksLimit: 8,
                            callback(value) {
                                return isThroughput
                                    ? this.getLabelForValue(value)
                                    : `₦${compactCurrency.format(value)}`;
                            },
                        },
                    },
                },
            },
        });
    });
});

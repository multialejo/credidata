import Chart from 'chart.js/auto';

let activityChart = null;

window.renderActivityChart = (canvas, points) => {
    if (activityChart) {
        activityChart.destroy();
    }

    activityChart = new Chart(canvas, {
        type: 'line',
        data: {
            labels: points.map((point) => point.etiqueta),
            datasets: [{
                data: points.map((point) => point.total),
                borderColor: '#3155d9',
                backgroundColor: 'rgba(49, 85, 217, 0.10)',
                borderWidth: 2.5,
                pointBackgroundColor: '#ffffff',
                pointBorderColor: '#3155d9',
                pointBorderWidth: 2,
                pointRadius: 3,
                pointHoverRadius: 5,
                fill: true,
                tension: 0.25,
            }],
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            interaction: {
                intersect: false,
                mode: 'index',
            },
            plugins: {
                legend: { display: false },
                tooltip: {
                    callbacks: {
                        title: (items) => points[items[0].dataIndex]?.fecha ?? '',
                        label: (item) => `${item.parsed.y} eventos`,
                    },
                },
            },
            scales: {
                x: {
                    grid: { display: false },
                    ticks: {
                        color: '#475569',
                        maxTicksLimit: 7,
                        maxRotation: 0,
                    },
                },
                y: {
                    beginAtZero: true,
                    ticks: {
                        precision: 0,
                        color: '#475569',
                    },
                    grid: {
                        color: '#e2e8f0',
                    },
                },
            },
        },
    });
};

window.addEventListener('activity-chart-updated', (event) => {
    const canvas = document.querySelector('[data-activity-chart] canvas');

    if (canvas && event.detail?.points) {
        window.renderActivityChart(canvas, event.detail.points);
    }
});

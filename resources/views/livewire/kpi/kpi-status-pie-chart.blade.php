<div>
    <canvas id="kpi-status-chart"></canvas>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    window.addEventListener('updated-chart-data', (event) => {
        const chartData = event.detail.chartData;
        const ctx = document.getElementById('kpi-status-chart').getContext('2d');

        if (window.kpiChart) {
            window.kpiChart.destroy();
        }

        window.kpiChart = new Chart(ctx, {
            type: 'pie',
            data: chartData,
            options: {
                responsive: true,
                plugins: {
                    title: {
                        display: true,
                        text: 'Biểu đồ tiến độ KPI theo đơn vị và kỳ đánh giá',
                        font: {
                            size: 18,
                            weight: 'bold'
                        },
                        color: '#111',
                        padding: {
                            top: 10,
                            bottom: 20
                        }
                    },
                    legend: {
                        position: 'bottom',
                        labels: {
                            font: {
                                size: 14
                            }
                        }
                    },
                },
            },
        });

        // console.log('Đã render biểu đồ KPI', chartData);
    });
</script>
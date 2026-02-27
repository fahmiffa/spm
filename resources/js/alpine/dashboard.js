export const dashboardCharts = (chartData, stats) => ({
    chartData: chartData,
    stats: stats,
    init() {
        this.$nextTick(() => {
            if (this.chartData && document.getElementById("monthlyChart")) {
                this.initMonthlyChart();
            }
            if (this.stats && document.getElementById("statusChart")) {
                this.initStatusChart();
            }
        });
    },
    initMonthlyChart() {
        const ctx = document.getElementById("monthlyChart");
        if (!ctx) return;

        new Chart(ctx, {
            type: "bar",
            data: {
                labels: [
                    "Jan",
                    "Feb",
                    "Mar",
                    "Apr",
                    "Mei",
                    "Jun",
                    "Jul",
                    "Agu",
                    "Sep",
                    "Okt",
                    "Nov",
                    "Des",
                ],
                datasets: [
                    {
                        label: "Pengajuan",
                        data: this.chartData,
                        backgroundColor: "#3b82f6",
                        borderRadius: 4,
                        barThickness: 12,
                    },
                ],
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { display: false } },
                scales: {
                    y: {
                        beginAtZero: true,
                        grid: { display: true, drawBorder: false },
                    },
                    x: { grid: { display: false } },
                },
            },
        });
    },
    initStatusChart() {
        const ctx = document.getElementById("statusChart");
        if (!ctx) return;

        new Chart(ctx, {
            type: "doughnut",
            data: {
                labels: ["Terakreditasi", "Ditolak"],
                datasets: [
                    {
                        data: [this.stats.terakreditasi, this.stats.ditolak],
                        backgroundColor: ["#10b981", "#f43f5e"],
                        borderWidth: 0,
                        cutout: "70%",
                    },
                ],
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { display: false } },
            },
        });
    },
});

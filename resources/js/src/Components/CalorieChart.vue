<template>
    <div>
        <canvas ref="donutChart"></canvas>
    </div>
</template>

<script>

export default {
    name: 'CaloriesChart',
    // props: {
    //     protein: {
    //         type: Number,
    //         required: true
    //     },
    //     carbs: {
    //         type: Number,
    //         required: true
    //     },
    //     fats: {
    //         type: Number,
    //         required: true
    //     },
    // },
    data() {
        return {
            chart: null, // Для хранения ссылки на график
        };
    },
    mounted() {
        this.createChart();
    },
    methods: {
        createChart() {
            const ctx = this.$refs.donutChart.getContext('2d');
            this.chart = new Chart(ctx, {
                type: 'doughnut',
                data: {
                    datasets: [{
                        data: [35, 45, 20],
                        backgroundColor: ['red', 'blue', 'yellow'],
                        borderColor: ['white', 'white', 'white'],
                        borderWidth: 2
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    legend: { position: 'right' },
                    tooltips: {
                        enabled: false
                    }
                }
            });
        },
        updateChart() {
            // this.chart.data.datasets[0].data = [this.protein, this.carbs, this.fats];
            // this.chart.update();
        }
    },
    watch: {
        protein() {
            this.updateChart();
        },
        carbs() {
            this.updateChart();
        },
        fats() {
            this.updateChart();
        }
    }
}
</script>

<style scoped lang="scss">
canvas {
    width: 400px;
    height: 400px;
}
</style>

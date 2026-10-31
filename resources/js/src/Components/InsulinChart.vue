<script>
import Chart from "chart.js/auto";
import { timeLabel, zoneOf, ZONE_COLORS } from "@/helpers/insulin.js";

const ZONE_BACKGROUNDS = {
    low: "rgba(79, 157, 85, 0.07)",
    medium: "rgba(217, 164, 0, 0.09)",
    high: "rgba(208, 67, 58, 0.09)",
};

/**
 * The line of the day: its color tells the zone, the part after now is dashed, a
 * dashed mark shows each meal and a blue band each activity.
 */
export default {
    name: "InsulinChart",
    props: {
        day: {
            type: Object,
            required: true,
        },
        partNames: {
            type: Object,
            required: true,
        },
    },
    watch: {
        day() {
            this.draw();
        },
    },
    mounted() {
        this.draw();
    },
    beforeUnmount() {
        this.chart?.destroy();
    },
    methods: {
        draw() {
            this.chart?.destroy();

            const { day, partNames } = this;
            const now = day.now?.minute ?? null;
            const top = Math.max(100, Math.ceil(day.peak.level * 1.15));

            this.chart = new Chart(this.$refs.canvas, {
                type: "line",
                data: {
                    datasets: [
                        {
                            data: day.points.map((point) => ({
                                x: point.minute,
                                y: point.level,
                            })),
                            borderWidth: 3,
                            pointRadius: 0,
                            pointHitRadius: 12,
                            tension: 0.25,
                            segment: {
                                borderColor: (context) =>
                                    ZONE_COLORS[
                                        zoneOf(context.p1.parsed.y, day.zones)
                                    ],
                                borderDash: (context) =>
                                    now !== null && context.p0.parsed.x >= now
                                        ? [6, 5]
                                        : undefined,
                            },
                        },
                    ],
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    animation: false,
                    interaction: { mode: "nearest", axis: "x", intersect: false },
                    scales: {
                        x: {
                            type: "linear",
                            min: 0,
                            max: 1440,
                            ticks: {
                                stepSize: 180,
                                callback: (value) => timeLabel(value),
                            },
                            grid: { color: "rgba(0, 0, 0, 0.05)" },
                        },
                        y: {
                            min: 0,
                            max: top,
                            afterBuildTicks: (axis) => {
                                axis.ticks = [
                                    day.zones.medium,
                                    day.zones.high,
                                    100,
                                ]
                                    .filter((value) => value <= top)
                                    .map((value) => ({ value }));
                            },
                            grid: { color: "rgba(0, 0, 0, 0.08)" },
                        },
                    },
                    plugins: {
                        legend: { display: false },
                        tooltip: {
                            displayColors: false,
                            callbacks: {
                                title: (items) =>
                                    timeLabel(items[0].parsed.x),
                                label: (item) =>
                                    `${this.$t("Insulin.level")}: ${Math.round(item.parsed.y)} · ${this.$t(
                                        `Insulin.zone.${zoneOf(item.parsed.y, day.zones)}`
                                    )}`,
                            },
                        },
                    },
                },
                plugins: [
                    {
                        id: "insulinMarks",
                        beforeDatasetsDraw(chart) {
                            const { ctx, chartArea: area, scales } = chart;
                            const y = (level) => scales.y.getPixelForValue(level);
                            const x = (minute) => scales.x.getPixelForValue(minute);
                            const bands = [
                                ["low", 0, day.zones.medium],
                                ["medium", day.zones.medium, day.zones.high],
                                ["high", day.zones.high, top],
                            ];

                            ctx.save();
                            bands.forEach(([zone, from, to]) => {
                                ctx.fillStyle = ZONE_BACKGROUNDS[zone];
                                ctx.fillRect(area.left, y(to), area.width, y(from) - y(to));
                            });

                            ctx.fillStyle = "rgba(66, 133, 244, 0.22)";
                            day.activities.forEach((activity) => {
                                const left = x(activity.minute);
                                const right = x(
                                    Math.min(1440, activity.minute + activity.minutes)
                                );
                                ctx.fillRect(left, area.top, Math.max(2, right - left), area.height);
                            });

                            ctx.font = "11px Roboto, sans-serif";
                            ctx.textAlign = "center";
                            day.meals.forEach((meal) => {
                                const left = x(meal.minute);
                                ctx.strokeStyle = "rgba(0, 0, 0, 0.35)";
                                ctx.setLineDash([3, 3]);
                                ctx.beginPath();
                                ctx.moveTo(left, area.top + 14);
                                ctx.lineTo(left, area.bottom);
                                ctx.stroke();
                                ctx.setLineDash([]);
                                ctx.fillStyle = "#444";
                                ctx.fillText(
                                    partNames[meal.part_of_day] ?? "",
                                    Math.max(area.left + 24, Math.min(area.right - 24, left)),
                                    area.top + 10
                                );
                            });
                            ctx.restore();
                        },
                        afterDatasetsDraw(chart) {
                            if (day.now === null) {
                                return;
                            }

                            const { ctx, chartArea: area, scales } = chart;
                            const left = scales.x.getPixelForValue(day.now.minute);
                            const top = scales.y.getPixelForValue(day.now.level);

                            ctx.save();
                            ctx.strokeStyle = "rgba(0, 0, 0, 0.55)";
                            ctx.beginPath();
                            ctx.moveTo(left, area.top);
                            ctx.lineTo(left, area.bottom);
                            ctx.stroke();
                            ctx.fillStyle = ZONE_COLORS[zoneOf(day.now.level, day.zones)];
                            ctx.beginPath();
                            ctx.arc(left, top, 5, 0, Math.PI * 2);
                            ctx.fill();
                            ctx.restore();
                        },
                    },
                ],
            });
        },
    },
};
</script>

<template>
    <div class="insulin-chart">
        <canvas ref="canvas"></canvas>
    </div>
</template>

<style scoped lang="scss">
.insulin-chart {
    position: relative;
    height: 300px;
    width: 100%;

    @media (max-width: 768px) {
        height: 240px;
    }
}
</style>

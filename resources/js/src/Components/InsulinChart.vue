<script>
import Chart from "chart.js/auto";
import {
    decimal,
    glucoseZones,
    timeLabel,
    zoneOf,
    ZONE_COLORS,
} from "@/helpers/insulin.js";

const ZONE_BACKGROUNDS = {
    low: "rgba(79, 157, 85, 0.07)",
    medium: "rgba(217, 164, 0, 0.09)",
    high: "rgba(208, 67, 58, 0.09)",
};

const ACTIVITY_BAND = "rgba(66, 133, 244, 0.22)";
const SLEEP_BAND = "rgba(110, 110, 140, 0.16)";

/**
 * One line of the day, sugar in mmol/L or insulin as an index: its color tells
 * the zone, the part after now is dashed, a dashed mark shows each meal, a blue
 * band each activity and a grey one sleep.
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
        series: {
            type: String,
            default: "glucose",
            validator: (value) => ["glucose", "insulin"].includes(value),
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
        scale() {
            const { day } = this;

            if (this.series === "glucose") {
                const zones = glucoseZones(day.glucose);

                return {
                    key: "glucose",
                    zones,
                    min: 3.5,
                    max: Math.max(zones.high - 0.5, Math.ceil((day.glucose.peak.level + 0.6) * 2) / 2),
                    ticks: [day.glucose.base, zones.medium, zones.high],
                    now: day.now?.glucose,
                    label: (value) => `${decimal(value, this.$i18n.locale)} ${this.$t("Insulin.mmol")}`,
                };
            }

            return {
                key: "level",
                zones: day.zones,
                min: 0,
                max: Math.max(100, Math.ceil(day.peak.level * 1.15)),
                ticks: [day.zones.medium, day.zones.high, 100],
                now: day.now?.level,
                label: (value) => `${this.$t("Insulin.level")}: ${Math.round(value)}`,
            };
        },
        draw() {
            this.chart?.destroy();

            const { day, partNames } = this;
            const scale = this.scale();
            const now = day.now?.minute ?? null;
            const showMeals = this.series === "glucose";

            this.chart = new Chart(this.$refs.canvas, {
                type: "line",
                data: {
                    datasets: [
                        {
                            data: day.points.map((point) => ({
                                x: point.minute,
                                y: point[scale.key],
                            })),
                            borderWidth: 3,
                            pointRadius: 0,
                            pointHitRadius: 12,
                            tension: 0.25,
                            segment: {
                                borderColor: (context) =>
                                    ZONE_COLORS[zoneOf(context.p1.parsed.y, scale.zones)],
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
                            min: scale.min,
                            max: scale.max,
                            afterBuildTicks: (axis) => {
                                axis.ticks = scale.ticks
                                    .filter((value) => value <= scale.max)
                                    .map((value) => ({ value }));
                            },
                            ticks: {
                                callback: (value) => decimal(value, this.$i18n.locale),
                            },
                            // The same width on both charts keeps their hours one above the other.
                            afterFit: (axis) => {
                                axis.width = 36;
                            },
                            grid: { color: "rgba(0, 0, 0, 0.08)" },
                        },
                    },
                    plugins: {
                        legend: { display: false },
                        tooltip: {
                            displayColors: false,
                            callbacks: {
                                title: (items) => timeLabel(items[0].parsed.x),
                                label: (item) =>
                                    `${scale.label(item.parsed.y)} · ${this.$t(
                                        `Insulin.${this.series === "glucose" ? "glucoseZone" : "zone"}.${zoneOf(item.parsed.y, scale.zones)}`
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
                                ["low", scale.min, scale.zones.medium],
                                ["medium", scale.zones.medium, scale.zones.high],
                                ["high", scale.zones.high, scale.max],
                            ];

                            ctx.save();
                            bands.forEach(([zone, from, to]) => {
                                if (to > from) {
                                    ctx.fillStyle = ZONE_BACKGROUNDS[zone];
                                    ctx.fillRect(area.left, y(to), area.width, y(from) - y(to));
                                }
                            });

                            day.activities.forEach((activity) => {
                                const left = x(Math.max(0, activity.minute));
                                const right = x(Math.min(1440, activity.minute + activity.minutes));
                                ctx.fillStyle = activity.sleep ? SLEEP_BAND : ACTIVITY_BAND;
                                ctx.fillRect(left, area.top, Math.max(2, right - left), area.height);
                            });

                            ctx.font = "11px Roboto, sans-serif";
                            ctx.textAlign = "center";
                            day.meals.forEach((meal) => {
                                const left = x(meal.minute);
                                ctx.strokeStyle = "rgba(0, 0, 0, 0.35)";
                                ctx.setLineDash([3, 3]);
                                ctx.beginPath();
                                ctx.moveTo(left, area.top + (showMeals ? 14 : 0));
                                ctx.lineTo(left, area.bottom);
                                ctx.stroke();
                                ctx.setLineDash([]);
                                if (showMeals) {
                                    ctx.fillStyle = "#444";
                                    ctx.fillText(
                                        partNames[meal.part_of_day] ?? "",
                                        Math.max(area.left + 24, Math.min(area.right - 24, left)),
                                        area.top + 10
                                    );
                                }
                            });
                            ctx.restore();
                        },
                        afterDatasetsDraw(chart) {
                            if (now === null || scale.now === undefined) {
                                return;
                            }

                            const { ctx, chartArea: area, scales } = chart;
                            const left = scales.x.getPixelForValue(now);
                            const top = scales.y.getPixelForValue(scale.now);

                            ctx.save();
                            ctx.strokeStyle = "rgba(0, 0, 0, 0.55)";
                            ctx.beginPath();
                            ctx.moveTo(left, area.top);
                            ctx.lineTo(left, area.bottom);
                            ctx.stroke();
                            ctx.fillStyle = ZONE_COLORS[zoneOf(scale.now, scale.zones)];
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
    <div class="insulin-chart" :class="`insulin-chart--${series}`">
        <canvas ref="canvas"></canvas>
    </div>
</template>

<style scoped lang="scss">
.insulin-chart {
    position: relative;
    width: 100%;

    &--glucose {
        height: 280px;

        @media (max-width: 768px) {
            height: 230px;
        }
    }

    &--insulin {
        height: 170px;

        @media (max-width: 768px) {
            height: 140px;
        }
    }
}
</style>

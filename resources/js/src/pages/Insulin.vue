<script>
import InsulinChart from "@/Components/InsulinChart.vue";
import CaloriesCross from "@/Components/DairyComponents/CaloriesCross.vue";
import CaloriesArrowLeft from "@/UI/CaloriesArrowLeft.vue";
import CaloriesArrowRight from "@/UI/CaloriesArrowRight.vue";
import {
    addActivity,
    getInsulinDay,
    saveLifestyle,
    saveMealTime,
} from "@/api/insulin.js";
import { removeActivity } from "@/api/body.js";
import { parseLocalDate, toLocalDateString } from "@/helpers/localDate.js";
import { timeLabel, zoneOf } from "@/helpers/insulin.js";

/**
 * The estimated insulin of a day: what the diary holds of food and movement, and
 * the way of life, as a line going up and down. Movement can be typed here or told
 * by voice or text on the "Приём пищи" page and to the bot.
 */
export default {
    name: "Insulin",
    components: {
        InsulinChart,
        CaloriesCross,
        CaloriesArrowLeft,
        CaloriesArrowRight,
    },
    data() {
        return {
            date: toLocalDateString(new Date()),
            day: null,
            failed: false,
            saving: false,
            activity: { title: "", minutes: "", intensity: "light", time: "" },
        };
    },
    computed: {
        partNames() {
            return {
                morning: this.$t("Insulin.parts.morning"),
                dinner: this.$t("Insulin.parts.dinner"),
                supper: this.$t("Insulin.parts.supper"),
            };
        },
        current() {
            return this.day?.now ?? this.day?.peak ?? null;
        },
        isToday() {
            return this.date === toLocalDateString(new Date());
        },
    },
    mounted() {
        this.load();
    },
    methods: {
        timeLabel,
        zone(level) {
            return zoneOf(level, this.day.zones);
        },
        show(response) {
            this.day = response.data;
            this.failed = false;
        },
        async load() {
            try {
                this.show(await getInsulinDay(this.date));
            } catch (error) {
                this.failed = true;
            }
        },
        move(days) {
            const next = parseLocalDate(this.date);
            next.setDate(next.getDate() + days);
            this.date = toLocalDateString(next);
            this.load();
        },
        async changeLifestyle(event) {
            this.show(await saveLifestyle(this.date, Number(event.target.value)));
        },
        async changeMealTime(meal, event) {
            if (event.target.value) {
                this.show(await saveMealTime(this.date, meal.part_of_day, event.target.value));
            }
        },
        async add() {
            const minutes = Number(this.activity.minutes);

            if (!this.activity.title.trim() || !minutes || minutes < 1 || minutes > 600 || this.saving) {
                this.$store.dispatch("setError", this.$t("Insulin.invalidActivity"));
                return;
            }

            this.saving = true;

            try {
                await addActivity({
                    date: this.date,
                    title: this.activity.title.trim(),
                    minutes,
                    intensity: this.activity.intensity,
                    time: this.activity.time || null,
                });
                this.activity = { title: "", minutes: "", intensity: "light", time: "" };
                await this.load();
            } finally {
                this.saving = false;
            }
        },
        async remove(activity) {
            await removeActivity(activity.id);
            await this.load();
        },
    },
};
</script>

<template>
    <section class="insulin">
        <h1 class="insulin_head">{{ $t("Insulin.title") }}</h1>
        <p class="insulin_note">{{ $t("Insulin.note") }}</p>

        <div class="insulin_date">
            <calories-arrow-left @click="move(-1)" />
            <span>{{ date }}</span>
            <calories-arrow-right v-if="!isToday" @click="move(1)" />
        </div>

        <p v-if="failed" class="insulin_note">{{ $t("Insulin.failed") }}</p>

        <template v-if="day">
            <div class="insulin_summary">
                <div v-if="day.now" class="insulin_stat">
                    <span class="insulin_stat-label">{{ $t("Insulin.now") }}</span>
                    <span class="insulin_stat-value" :class="`insulin_zone-${zone(day.now.level)}`">
                        {{ Math.round(day.now.level) }} · {{ $t(`Insulin.zone.${zone(day.now.level)}`) }}
                    </span>
                </div>
                <div class="insulin_stat">
                    <span class="insulin_stat-label">{{ $t("Insulin.peak", { time: timeLabel(day.peak.minute) }) }}</span>
                    <span class="insulin_stat-value" :class="`insulin_zone-${zone(day.peak.level)}`">
                        {{ Math.round(day.peak.level) }} · {{ $t(`Insulin.zone.${zone(day.peak.level)}`) }}
                    </span>
                </div>
            </div>

            <insulin-chart :day="day" :part-names="partNames" />

            <label class="insulin_lifestyle">
                <span>{{ $t("Insulin.lifestyle") }}</span>
                <select :value="day.lifestyle" @change="changeLifestyle">
                    <option v-for="level in 5" :key="level" :value="level">
                        {{ $t(`Insulin.lifestyles.level${level}`) }}
                    </option>
                </select>
            </label>

            <h2 class="insulin_subhead">{{ $t("Insulin.meals") }}</h2>
            <p v-if="!day.meals.length" class="insulin_note">{{ $t("Insulin.noMeals") }}</p>
            <div v-for="meal in day.meals" :key="meal.part_of_day" class="insulin_meal">
                <div class="insulin_row">
                    <b>{{ partNames[meal.part_of_day] }}</b>
                    <input
                        class="insulin_time"
                        type="time"
                        :value="timeLabel(meal.minute)"
                        @change="changeMealTime(meal, $event)"
                    />
                    <span class="insulin_rise">+{{ Math.round(meal.rise) }}</span>
                </div>
                <div v-for="item in meal.items" :key="item.id" class="insulin_row insulin_item">
                    <span>{{ item.name }}, {{ item.grams }} {{ $t("Insulin.grams") }}</span>
                    <span class="insulin_rise">+{{ Math.round(item.rise) }}</span>
                </div>
            </div>
            <p v-if="day.estimated_foods" class="insulin_note">
                {{ $t("Insulin.estimated", { count: day.estimated_foods }) }}
            </p>

            <h2 class="insulin_subhead">{{ $t("Insulin.movement") }}</h2>
            <p class="insulin_note">{{ $t("Insulin.movementHint") }}</p>
            <div v-for="item in day.activities" :key="item.id" class="insulin_row insulin_item">
                <span>{{ timeLabel(item.minute) }} {{ item.title }}, {{ item.minutes }} {{ $t("Insulin.minutes") }}</span>
                <span class="insulin_effect">{{ $t("Insulin.effect", { percent: item.effect }) }}</span>
                <calories-cross @click="remove(item)" />
            </div>

            <form class="insulin_form" @submit.prevent="add">
                <input v-model="activity.title" type="text" maxlength="150" :placeholder="$t('Insulin.activityTitle')" />
                <input v-model="activity.minutes" type="number" min="1" max="600" :placeholder="$t('Insulin.minutes')" />
                <select v-model="activity.intensity">
                    <option v-for="intensity in ['sitting', 'light', 'moderate', 'hard']" :key="intensity" :value="intensity">
                        {{ $t(`Insulin.intensity.${intensity}`) }}
                    </option>
                </select>
                <input v-model="activity.time" type="time" :aria-label="$t('Insulin.activityTime')" />
                <button type="submit" :disabled="saving">{{ $t("Insulin.add") }}</button>
            </form>
        </template>
    </section>
</template>

<style scoped lang="scss">
.insulin {
    max-width: 900px;
    margin: 0 auto;
    padding: 24px 16px 96px;
    text-align: left;

    &_head {
        font-size: 24px;
        font-weight: 700;
        text-align: center;
    }

    &_subhead {
        margin-top: 24px;
        font-size: 18px;
        font-weight: 700;
    }

    &_note {
        margin: 6px 0;
        color: #666;
        font-size: 13px;
    }

    &_date {
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 16px;
        margin: 12px 0;
        font-weight: 600;
    }

    &_summary {
        display: flex;
        flex-wrap: wrap;
        gap: 12px 32px;
        margin-bottom: 8px;
    }

    &_stat {
        display: flex;
        flex-direction: column;
    }

    &_stat-label {
        color: #666;
        font-size: 13px;
    }

    &_stat-value {
        font-size: 18px;
        font-weight: 700;
    }

    &_zone-low {
        color: #2f7d36;
    }

    &_zone-medium {
        color: #9a7500;
    }

    &_zone-high {
        color: #c2352c;
    }

    &_lifestyle {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        gap: 8px;
        margin-top: 12px;
    }

    &_meal {
        padding: 8px 0;
        border-bottom: 1px solid #e3e3e3;
    }

    &_row {
        display: flex;
        align-items: center;
        gap: 10px;
    }

    &_item {
        justify-content: space-between;
        padding: 3px 0;
        font-size: 14px;
    }

    &_time {
        padding: 1px 4px;
        border: 1px solid #ccc;
        border-radius: 4px;
    }

    &_rise {
        margin-left: auto;
        font-weight: 600;
        white-space: nowrap;
    }

    &_effect {
        margin-left: auto;
        color: #2f7d36;
        font-size: 13px;
        white-space: nowrap;
    }

    &_form {
        display: grid;
        grid-template-columns: 2fr 80px 1.4fr 100px auto;
        gap: 8px;
        margin-top: 12px;

        @media (max-width: 768px) {
            grid-template-columns: 1fr 1fr;

            input[type="text"] {
                grid-column: 1 / -1;
            }
        }

        input,
        select {
            min-width: 0;
            padding: 6px 8px;
            border: 1px solid #ccc;
            border-radius: 4px;
        }

        button {
            padding: 6px 14px;
            border: 0;
            border-radius: 4px;
            background: #73ad37;
            color: #fff;
            font-weight: 600;
            cursor: pointer;
        }
    }

    select {
        padding: 6px 8px;
        border: 1px solid #ccc;
        border-radius: 4px;
    }
}
</style>

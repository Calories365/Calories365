<script>
import InsulinChart from "@/Components/InsulinChart.vue";
import CaloriesCross from "@/Components/DairyComponents/CaloriesCross.vue";
import CaloriesVoiceButton from "@/Components/CaloriesVoiceButton.vue";
import CaloriesSuccessNotification from "@/Components/CaloriesSuccessNotification.vue";
import CaloriesErrorNotification from "@/Components/CaloriesErrorNotification.vue";
import CaloriesArrowLeft from "@/UI/CaloriesArrowLeft.vue";
import CaloriesArrowRight from "@/UI/CaloriesArrowRight.vue";
import {
    addActivity,
    getActivityTypes,
    getInsulinDay,
    saveLifestyle,
    saveMealTime,
    sayActivity,
    sayActivityByVoice,
    updateActivity,
} from "@/api/insulin.js";
import { removeActivity } from "@/api/body.js";
import { parseLocalDate, toLocalDateString } from "@/helpers/localDate.js";
import { decimal, glucoseZones, timeLabel, zoneOf } from "@/helpers/insulin.js";

const SLEEP = "sleep";
// The most common kinds come first; the rest wait behind "more".
const SHOWN_FIRST = 8;
const DURATIONS = [10, 15, 20, 30, 45, 60, 90, 120];
const SLEEP_DURATIONS = [300, 360, 420, 480, 540];

// Errors of the API and the text that explains each.
const ERRORS = {
    voice_rate_limited: "Meal.errors.rateLimited",
    voice_service_unavailable: "Meal.errors.unavailable",
    voice_not_recognized: "Meal.errors.notHeard",
    activity_not_read: "Insulin.record.notRead",
};

/**
 * Blood sugar and insulin of a day, estimated from what the diary holds of food,
 * movement, sleep and the way of life: two lines going up and down, what each
 * meal did and why, the totals of the day and what to change. Movement and sleep
 * are told here by voice or in words, or picked with two taps.
 */
export default {
    name: "Insulin",
    components: {
        InsulinChart,
        CaloriesCross,
        CaloriesVoiceButton,
        CaloriesSuccessNotification,
        CaloriesErrorNotification,
        CaloriesArrowLeft,
        CaloriesArrowRight,
    },
    data() {
        return {
            date: toLocalDateString(new Date()),
            day: null,
            failed: false,
            saving: false,
            types: [],
            picked: null,
            pickedMinutes: 30,
            pickedTime: "",
            custom: { title: "", intensity: "light" },
            said: "",
            allTypes: false,
            recording: false,
            successMessage: "",
            errorMessage: "",
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
        isToday() {
            return this.date === toLocalDateString(new Date());
        },
        sugarZones() {
            return glucoseZones(this.day.glucose);
        },
        durations() {
            return this.picked?.type === SLEEP ? SLEEP_DURATIONS : DURATIONS;
        },
        summary() {
            return this.day?.summary ?? null;
        },
        shownTypes() {
            return this.allTypes ? this.types : this.types.slice(0, SHOWN_FIRST);
        },
    },
    mounted() {
        this.load();
        this.loadTypes();
    },
    methods: {
        timeLabel,
        number(value, digits = 1) {
            return decimal(value, this.$i18n.locale, digits);
        },
        sugarZone(level) {
            return zoneOf(level, this.sugarZones);
        },
        insulinZone(level) {
            return zoneOf(level, this.day.zones);
        },
        duration(minutes) {
            const hours = Math.floor(minutes / 60);
            const rest = minutes % 60;

            if (!hours) {
                return this.$t("Insulin.record.minutesShort", { minutes });
            }

            return rest
                ? `${this.$t("Insulin.record.hours", { hours })} ${this.$t("Insulin.record.minutesShort", { minutes: rest })}`
                : this.$t("Insulin.record.hours", { hours });
        },
        effect(activity) {
            if (!activity.glucose_effect && !activity.effect) {
                return this.$t("Insulin.noEffect");
            }

            const more = activity.glucose_effect < 0;

            return this.$t(more ? "Insulin.effectMore" : "Insulin.effect", {
                glucose: Math.abs(activity.glucose_effect),
                insulin: Math.abs(activity.effect),
            });
        },
        notify(kind, message) {
            this[kind] = message;
            setTimeout(() => {
                this[kind] = "";
            }, 5000);
        },
        fail(error) {
            const code = error?.response?.data?.message;
            this.notify("errorMessage", this.$t(ERRORS[code] ?? "Insulin.failed"));
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
        async loadTypes() {
            try {
                this.types = (await getActivityTypes()).data.types;
            } catch (error) {
                this.types = [];
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
        pick(type) {
            this.picked = type;
            this.pickedMinutes = type?.minutes ?? 30;
            this.pickedTime = "";
        },
        tellRecorded(recorded) {
            const list = recorded
                .map((activity) =>
                    activity.time
                        ? `${activity.title}, ${this.duration(activity.minutes)}, ${activity.time}`
                        : `${activity.title}, ${this.duration(activity.minutes)}`
                )
                .join("; ");
            this.notify("successMessage", this.$t("Insulin.record.recorded", { list }));
        },
        async addPicked() {
            if (this.saving || !this.picked) {
                return;
            }

            const minutes = Number(this.pickedMinutes);
            const custom = this.picked.type === "other";
            const longest = this.picked.type === SLEEP ? 960 : 600;

            if (!minutes || minutes < 1 || minutes > longest || (custom && !this.custom.title.trim())) {
                this.notify("errorMessage", this.$t("Insulin.invalidActivity"));
                return;
            }

            this.saving = true;

            try {
                const { data } = await addActivity({
                    date: this.date,
                    minutes,
                    time: this.pickedTime || null,
                    ...(custom
                        ? { title: this.custom.title.trim(), intensity: this.custom.intensity }
                        : { type: this.picked.type }),
                });
                this.tellRecorded([data.activity]);
                this.picked = null;
                this.custom.title = "";
                await this.load();
            } catch (error) {
                this.fail(error);
            } finally {
                this.saving = false;
            }
        },
        async say(request) {
            if (this.saving) {
                return;
            }

            this.saving = true;

            try {
                const { data } = await request();

                if (!data.understood) {
                    this.notify("errorMessage", this.$t("Insulin.record.notUnderstood"));
                    return;
                }

                this.said = "";
                this.tellRecorded(data.recorded);
                this.date = toLocalDateString(new Date());
                await this.load();
            } catch (error) {
                this.fail(error);
            } finally {
                this.saving = false;
            }
        },
        sayText() {
            if (this.said.trim()) {
                this.say(() => sayActivity(this.said.trim()));
            }
        },
        sayVoice(blob) {
            this.say(() => sayActivityByVoice(blob));
        },
        async changeActivity(activity, changes) {
            try {
                await updateActivity(activity.id, changes);
                await this.load();
            } catch (error) {
                this.fail(error);
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

        <CaloriesSuccessNotification v-if="successMessage">{{ successMessage }}</CaloriesSuccessNotification>
        <CaloriesErrorNotification v-if="errorMessage">{{ errorMessage }}</CaloriesErrorNotification>

        <div class="insulin_date">
            <calories-arrow-left @click="move(-1)" />
            <span>{{ date }}</span>
            <calories-arrow-right v-if="!isToday" @click="move(1)" />
        </div>

        <p v-if="failed" class="insulin_note">{{ $t("Insulin.failed") }}</p>

        <template v-if="day">
            <div class="insulin_stats">
                <div v-if="day.now" class="insulin_stat">
                    <span class="insulin_stat-label">{{ $t("Insulin.glucoseNow") }}</span>
                    <span class="insulin_stat-value" :class="`insulin_zone-${sugarZone(day.now.glucose)}`">
                        {{ number(day.now.glucose) }} {{ $t("Insulin.mmol") }}
                    </span>
                </div>
                <div class="insulin_stat">
                    <span class="insulin_stat-label">{{ $t("Insulin.glucosePeak", { time: timeLabel(day.glucose.peak.minute) }) }}</span>
                    <span class="insulin_stat-value" :class="`insulin_zone-${sugarZone(day.glucose.peak.level)}`">
                        {{ number(day.glucose.peak.level) }} · {{ $t(`Insulin.glucoseZone.${sugarZone(day.glucose.peak.level)}`) }}
                    </span>
                </div>
                <div v-if="day.now" class="insulin_stat">
                    <span class="insulin_stat-label">{{ $t("Insulin.insulinNow") }}</span>
                    <span class="insulin_stat-value" :class="`insulin_zone-${insulinZone(day.now.level)}`">
                        {{ Math.round(day.now.level) }} · {{ $t(`Insulin.zone.${insulinZone(day.now.level)}`) }}
                    </span>
                </div>
                <div class="insulin_stat">
                    <span class="insulin_stat-label">{{ $t("Insulin.peak", { time: timeLabel(day.peak.minute) }) }}</span>
                    <span class="insulin_stat-value" :class="`insulin_zone-${insulinZone(day.peak.level)}`">
                        {{ Math.round(day.peak.level) }} · {{ $t(`Insulin.zone.${insulinZone(day.peak.level)}`) }}
                    </span>
                </div>
            </div>

            <h2 class="insulin_chart-title">{{ $t("Insulin.glucose") }}, {{ $t("Insulin.mmol") }}</h2>
            <insulin-chart :day="day" :part-names="partNames" series="glucose" />
            <h2 class="insulin_chart-title">{{ $t("Insulin.insulin") }}</h2>
            <insulin-chart :day="day" :part-names="partNames" series="insulin" />

            <div class="insulin_card insulin_record">
                <h2 class="insulin_subhead">{{ $t("Insulin.record.title") }}</h2>
                <p class="insulin_note">{{ $t("Insulin.record.hint") }}</p>
                <form class="insulin_say" @submit.prevent="sayText">
                    <calories-voice-button
                        :disabled="saving"
                        @recorded="sayVoice"
                        @recording="recording = $event"
                        @failed="notify('errorMessage', $t('Meal.errors.mic'))"
                    >
                        {{ recording ? $t("Insulin.record.stop") : $t("Insulin.record.voice") }}
                    </calories-voice-button>
                    <input v-model="said" type="text" maxlength="2000" :aria-label="$t('Insulin.record.prompt')" :disabled="saving" />
                    <button type="submit" :disabled="saving || !said.trim()">{{ $t("Insulin.record.send") }}</button>
                </form>
                <p v-if="saving" class="insulin_note">{{ $t("Insulin.record.reading") }}</p>

                <div class="insulin_types">
                    <button
                        v-for="type in shownTypes"
                        :key="type.type"
                        type="button"
                        class="insulin_type"
                        :class="{ 'insulin_type--picked': picked?.type === type.type }"
                        @click="pick(type)"
                    >
                        <span>{{ type.emoji }}</span> {{ type.title }}
                    </button>
                    <button v-if="!allTypes && types.length > shownTypes.length" type="button" class="insulin_type" @click="allTypes = true">
                        … {{ $t("Insulin.record.more") }}
                    </button>
                    <button
                        type="button"
                        class="insulin_type"
                        :class="{ 'insulin_type--picked': picked?.type === 'other' }"
                        @click="pick({ type: 'other', minutes: 30 })"
                    >
                        ✏️ {{ $t("Insulin.record.other") }}
                    </button>
                </div>

                <form v-if="picked" class="insulin_picked" @submit.prevent="addPicked">
                    <template v-if="picked.type === 'other'">
                        <label class="insulin_when">
                            <span>{{ $t("Insulin.activityTitle") }}</span>
                            <input v-model="custom.title" type="text" maxlength="150" />
                        </label>
                        <select v-model="custom.intensity" :aria-label="$t('Insulin.intensityLabel')">
                            <option v-for="intensity in ['sitting', 'light', 'moderate', 'hard']" :key="intensity" :value="intensity">
                                {{ $t(`Insulin.intensity.${intensity}`) }}
                            </option>
                        </select>
                    </template>
                    <div class="insulin_durations">
                        <button
                            v-for="minutes in durations"
                            :key="minutes"
                            type="button"
                            class="insulin_duration"
                            :class="{ 'insulin_duration--picked': Number(pickedMinutes) === minutes }"
                            @click="pickedMinutes = minutes"
                        >
                            {{ duration(minutes) }}
                        </button>
                        <input v-model="pickedMinutes" type="number" min="1" :max="picked.type === 'sleep' ? 960 : 600" class="insulin_minutes" :aria-label="$t('Insulin.minutes')" />
                        <span>{{ $t("Insulin.minutes") }}</span>
                    </div>
                    <label class="insulin_when">
                        <span>{{ picked.type === "sleep" ? $t("Insulin.record.wentToBed") : $t("Insulin.record.startedAt") }}</span>
                        <input v-model="pickedTime" type="time" />
                        <span v-if="!pickedTime && isToday" class="insulin_note">{{ $t("Insulin.record.endedNow") }}</span>
                    </label>
                    <div class="insulin_actions">
                        <button type="submit" :disabled="saving">{{ $t("Insulin.record.add") }}</button>
                        <button type="button" class="insulin_secondary" @click="picked = null">{{ $t("Insulin.record.cancel") }}</button>
                    </div>
                </form>
            </div>

            <div v-if="day.tips.length" class="insulin_card">
                <h2 class="insulin_subhead">{{ $t("Insulin.tips") }}</h2>
                <ul class="insulin_tips">
                    <li v-for="tip in day.tips" :key="tip">{{ tip }}</li>
                </ul>
            </div>

            <div v-if="summary" class="insulin_card">
                <h2 class="insulin_subhead">{{ $t("Insulin.summaryTitle") }}</h2>
                <div class="insulin_summary">
                    <div class="insulin_fact">
                        <span>{{ $t("Insulin.summary.proteins") }}</span>
                        <b :class="{ 'insulin_short': summary.proteins_target && summary.proteins < summary.proteins_target }">
                            {{
                                summary.proteins_target
                                    ? $t("Insulin.summary.proteinsValue", { grams: number(summary.proteins, 0), perKg: number(summary.proteins_per_kg), target: summary.proteins_target })
                                    : $t("Insulin.summary.gramsValue", { grams: number(summary.proteins, 0) })
                            }}
                        </b>
                    </div>
                    <div class="insulin_fact">
                        <span>{{ $t("Insulin.summary.fibers") }}</span>
                        <b :class="{ 'insulin_short': summary.fibers < summary.fibers_target && !summary.fibers_unknown }">
                            {{ $t("Insulin.summary.fibersValue", { grams: number(summary.fibers, 0), target: summary.fibers_target }) }}
                        </b>
                        <small v-if="summary.fibers_unknown">{{ $t("Insulin.summary.fibersUnknown", { count: summary.fibers_unknown }) }}</small>
                    </div>
                    <div class="insulin_fact">
                        <span>{{ $t("Insulin.summary.sugars") }}</span>
                        <b>{{ $t("Insulin.summary.gramsValue", { grams: number(summary.sugars, 0) }) }}</b>
                    </div>
                    <div class="insulin_fact">
                        <span>{{ $t("Insulin.summary.load") }}</span>
                        <b :class="`insulin_load-${summary.glycemic_load_level}`">
                            {{ number(summary.glycemic_load, 0) }} · {{ $t(`Insulin.summary.loadLevel.${summary.glycemic_load_level}`) }}
                        </b>
                    </div>
                    <div v-if="summary.ultra_processed_share !== null" class="insulin_fact">
                        <span>{{ $t("Insulin.summary.ultra") }}</span>
                        <b>{{ $t("Insulin.summary.ultraValue", { percent: summary.ultra_processed_share }) }}</b>
                    </div>
                    <div class="insulin_fact">
                        <span>{{ $t("Insulin.summary.high") }}</span>
                        <b>{{ $t("Insulin.summary.minutesValue", { minutes: summary.glucose_high_minutes }) }}</b>
                    </div>
                    <div class="insulin_fact">
                        <span>{{ $t("Insulin.summary.moved") }}</span>
                        <b>{{ $t("Insulin.summary.movedValue", { minutes: summary.moved_minutes, after: summary.moved_after_meals_minutes }) }}</b>
                    </div>
                    <div v-if="summary.sleep_hours !== null" class="insulin_fact">
                        <span>{{ $t("Insulin.summary.sleep") }}</span>
                        <b>{{ $t("Insulin.summary.sleepValue", { hours: number(summary.sleep_hours) }) }}</b>
                    </div>
                </div>
            </div>

            <h2 class="insulin_subhead">{{ $t("Insulin.meals") }}</h2>
            <p v-if="!day.meals.length" class="insulin_note">{{ $t("Insulin.noMeals") }}</p>
            <div v-for="meal in day.meals" :key="meal.part_of_day" class="insulin_meal">
                <div class="insulin_row">
                    <b>{{ partNames[meal.part_of_day] }}</b>
                    <input class="insulin_time" type="time" :value="timeLabel(meal.minute)" @change="changeMealTime(meal, $event)" />
                    <span class="insulin_meal-levels">
                        <span class="insulin_rise" :class="`insulin_zone-${sugarZone(meal.glucose_peak)}`">
                            {{ $t("Insulin.mealSugar", { level: number(meal.glucose_peak) }) }}
                        </span>
                        ·
                        <span class="insulin_rise" :class="`insulin_zone-${insulinZone(meal.rise + day.base)}`">
                            {{ $t("Insulin.mealInsulin", { rise: Math.round(meal.rise) }) }}
                        </span>
                    </span>
                </div>
                <p class="insulin_facts">
                    {{ $t("Insulin.mealFacts", { load: number(meal.glycemic_load, 0), proteins: number(meal.proteins, 0), fibers: number(meal.fibers, 0) }) }}
                </p>
                <div v-if="meal.factors.length" class="insulin_factors">
                    <span
                        v-for="factor in meal.factors"
                        :key="factor.key"
                        class="insulin_factor"
                        :class="factor.percent < 0 ? 'insulin_factor--down' : 'insulin_factor--up'"
                    >
                        {{ factor.label }}
                    </span>
                </div>
                <p v-if="meal.dip" class="insulin_note">
                    {{ $t("Insulin.dip", { time: timeLabel(meal.dip.minute), level: number(meal.dip.level) }) }}
                </p>
                <div v-for="item in meal.items" :key="item.id" class="insulin_row insulin_item">
                    <span>{{ item.name }}, <span class="insulin_nowrap">{{ item.grams }} {{ $t("Insulin.grams") }}</span></span>
                    <span class="insulin_rise">{{ $t("Insulin.itemSugar", { value: number(item.glucose) }) }}</span>
                    <span class="insulin_item-insulin">{{ $t("Insulin.itemInsulin", { value: Math.round(item.rise) }) }}</span>
                </div>
            </div>
            <p v-if="day.estimated_foods" class="insulin_note">
                {{ $t("Insulin.estimated", { count: day.estimated_foods }) }}
            </p>

            <h2 class="insulin_subhead">{{ $t("Insulin.movement") }}</h2>
            <p v-if="!day.activities.length" class="insulin_note">{{ $t("Insulin.movementHint") }}</p>
            <div v-for="item in day.activities" :key="item.id" class="insulin_row insulin_activity">
                <span>{{ item.emoji ?? "•" }}</span>
                <input
                    class="insulin_time"
                    type="time"
                    :value="timeLabel(item.minute)"
                    :aria-label="$t('Insulin.activityTime')"
                    @change="$event.target.value && changeActivity(item, { time: $event.target.value })"
                />
                <span class="insulin_activity-title">{{ item.title }}, {{ duration(item.minutes) }}</span>
                <span class="insulin_effect" :class="{ 'insulin_effect--more': item.glucose_effect < 0, 'insulin_effect--none': !item.glucose_effect && !item.effect }">{{ effect(item) }}</span>
                <calories-cross @click="remove(item)" />
            </div>

            <label class="insulin_lifestyle">
                <span>{{ $t("Insulin.lifestyle") }}</span>
                <select :value="day.lifestyle" @change="changeLifestyle">
                    <option v-for="level in 5" :key="level" :value="level">
                        {{ $t(`Insulin.lifestyles.level${level}`) }}
                    </option>
                </select>
            </label>

            <details class="insulin_how">
                <summary>{{ $t("Insulin.howTitle") }}</summary>
                <ul>
                    <li v-for="line in $tm('Insulin.how')" :key="line">{{ $rt(line) }}</li>
                </ul>
            </details>
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
        margin: 20px 0 6px;
        font-size: 18px;
        font-weight: 700;
    }

    &_card &_subhead {
        margin-top: 0;
    }

    &_chart-title {
        margin: 14px 0 2px;
        color: #555;
        font-size: 14px;
        font-weight: 600;
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

    &_stats {
        display: grid;
        grid-template-columns: repeat(4, minmax(0, 1fr));
        gap: 10px 20px;
        margin-bottom: 4px;

        @media (max-width: 768px) {
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }
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
        font-size: 17px;
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

    &_card {
        margin-top: 16px;
        padding: 12px 14px;
        border: 1px solid #e3e3e3;
        border-radius: 8px;
        background: #fafafa;
    }

    &_say {
        display: flex;
        flex-wrap: wrap;
        gap: 8px;
        margin-top: 8px;

        input {
            flex: 1 1 200px;
            min-width: 0;
            padding: 8px 10px;
            border: 1px solid #ccc;
            border-radius: 6px;
        }
    }

    &_types {
        display: flex;
        flex-wrap: wrap;
        gap: 6px;
        margin-top: 12px;
    }

    &_type,
    &_duration {
        padding: 6px 10px;
        border: 1px solid #d5d5d5;
        border-radius: 16px;
        background: #fff;
        font-size: 13px;
        cursor: pointer;

        &--picked {
            border-color: #73ad37;
            background: #eef6e6;
            font-weight: 600;
        }
    }

    &_picked {
        display: flex;
        flex-direction: column;
        gap: 10px;
        margin-top: 12px;
        padding-top: 10px;
        border-top: 1px dashed #ddd;

        select {
            align-self: flex-start;
            padding: 6px 8px;
            border: 1px solid #ccc;
            border-radius: 4px;
        }
    }

    &_durations {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        gap: 6px;
    }

    &_minutes {
        width: 72px;
        padding: 5px 6px;
        border: 1px solid #ccc;
        border-radius: 4px;
    }

    &_when {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        gap: 8px;

        input {
            min-width: 0;
            padding: 4px 6px;
            border: 1px solid #ccc;
            border-radius: 4px;
        }
    }

    &_actions {
        display: flex;
        gap: 8px;
    }

    button[type="submit"] {
        padding: 8px 14px;
        border: 0;
        border-radius: 6px;
        background: #73ad37;
        color: #fff;
        font-weight: 600;
        cursor: pointer;

        &:disabled {
            opacity: 0.5;
            cursor: default;
        }
    }

    &_secondary {
        padding: 8px 14px;
        border: 1px solid #ccc;
        border-radius: 6px;
        background: #fff;
        cursor: pointer;
    }

    &_tips {
        margin: 0;
        padding-left: 18px;
        font-size: 14px;

        li + li {
            margin-top: 6px;
        }
    }

    &_summary {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 8px 20px;

        @media (max-width: 560px) {
            grid-template-columns: 1fr;
        }
    }

    &_fact {
        display: flex;
        flex-direction: column;
        font-size: 13px;

        span {
            color: #666;
        }

        b {
            font-size: 15px;
        }

        small {
            color: #888;
            font-size: 12px;
        }
    }

    &_short,
    &_load-high {
        color: #c2352c;
    }

    &_load-medium {
        color: #9a7500;
    }

    &_load-low {
        color: #2f7d36;
    }

    &_meal {
        padding: 10px 0;
        border-bottom: 1px solid #e3e3e3;
    }

    &_row {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        gap: 6px 10px;
    }

    &_item {
        justify-content: space-between;
        padding: 3px 0;
        font-size: 14px;

        > span:first-child {
            flex: 1 1 180px;
        }
    }

    &_item-insulin {
        min-width: 70px;
        color: #777;
        font-size: 13px;
        text-align: right;
        white-space: nowrap;
    }

    &_facts {
        margin: 4px 0;
        color: #555;
        font-size: 13px;
    }

    &_factors {
        display: flex;
        flex-wrap: wrap;
        gap: 4px;
        margin: 4px 0;
    }

    &_factor {
        padding: 2px 8px;
        border-radius: 10px;
        font-size: 12px;

        &--down {
            background: #e8f3e3;
            color: #2f7d36;
        }

        &--up {
            background: #fbeaea;
            color: #c2352c;
        }
    }

    &_time {
        padding: 1px 4px;
        border: 1px solid #ccc;
        border-radius: 4px;
    }

    &_rise {
        font-weight: 600;
        white-space: nowrap;
    }

    &_meal-levels {
        margin-left: auto;
        white-space: nowrap;
    }

    &_nowrap {
        white-space: nowrap;
    }

    &_activity {
        padding: 4px 0;
        font-size: 14px;
    }

    &_activity-title {
        flex: 1 1 160px;
    }

    &_effect {
        color: #2f7d36;
        font-size: 13px;
        white-space: nowrap;

        &--more {
            color: #c2352c;
        }

        &--none {
            color: #888;
        }
    }

    &_lifestyle {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        gap: 8px;
        margin-top: 18px;

        select {
            padding: 6px 8px;
            border: 1px solid #ccc;
            border-radius: 4px;
        }
    }

    &_how {
        margin-top: 18px;
        color: #555;
        font-size: 13px;

        summary {
            cursor: pointer;
            font-weight: 600;
        }

        li + li {
            margin-top: 4px;
        }
    }
}
</style>

<script>
import { mapGetters } from "vuex";
import { getterTypes } from "@/store/modules/dairy.js";
import { getBodyDay, removeActivity, saveWeight } from "@/api/body.js";

/**
 * The body weight and the activities of the day open in the diary. The weight
 * can be typed here; both can be told by voice or text on the "Приём пищи"
 * page or to the bot.
 */
export default {
    name: "CaloriesBodyDay",
    data() {
        return {
            day: { weight: null, activities: [], calories: 0 },
            weight: "",
            saving: false,
        };
    },
    computed: {
        ...mapGetters({
            currentDate: getterTypes.currentDate,
        }),
    },
    watch: {
        currentDate: {
            immediate: true,
            handler(date) {
                if (date) {
                    this.load(date);
                }
            },
        },
    },
    methods: {
        show(day) {
            this.day = day;
            this.weight = day.weight ?? "";
        },
        async load(date) {
            try {
                const { data } = await getBodyDay(date);
                this.show(data);
            } catch (error) {
                // The diary works without this block.
            }
        },
        async save() {
            const weight = Number(String(this.weight).replace(",", "."));

            if (!weight || weight < 25 || weight > 350 || this.saving) {
                this.$store.dispatch("setError", this.$t("Body.invalid"));
                return;
            }

            this.saving = true;

            try {
                const { data } = await saveWeight(this.currentDate, weight);
                this.show(data);
                this.$store.dispatch("setSuccess", this.$t("Body.saved"));
            } catch (error) {
                this.$store.dispatch("setError", this.$t("Body.notSaved"));
            } finally {
                this.saving = false;
            }
        },
        async remove(activity) {
            try {
                const { data } = await removeActivity(activity.id);
                this.show(data);
            } catch (error) {
                this.$store.dispatch("setError", this.$t("Body.notSaved"));
            }
        },
    },
};
</script>

<template>
    <div class="body-day">
        <form class="body-day__row" @submit.prevent="save">
            <label class="body-day__label" for="body-day-weight">{{
                $t("Body.weight")
            }}</label>
            <input
                id="body-day-weight"
                v-model="weight"
                class="body-day__input"
                type="text"
                inputmode="decimal"
                maxlength="6"
            />
            <span class="body-day__unit">{{ $t("Body.kg") }}</span>
            <button class="body-day__button" type="submit" :disabled="saving">
                {{ day.weight ? $t("Body.update") : $t("Body.save") }}
            </button>
        </form>

        <div v-if="day.activities.length" class="body-day__activities">
            <span class="body-day__label">{{
                $t("Body.activity", { calories: day.calories })
            }}</span>
            <ul>
                <li
                    v-for="activity in day.activities"
                    :key="activity.id"
                    class="body-day__activity"
                >
                    <span>{{
                        $t("Body.activityItem", {
                            title: activity.title,
                            minutes: activity.minutes,
                            calories: activity.calories,
                        })
                    }}</span>
                    <button
                        type="button"
                        class="body-day__remove"
                        :title="$t('Body.remove')"
                        @click="remove(activity)"
                    >
                        ×
                    </button>
                </li>
            </ul>
        </div>

        <p class="body-day__hint">{{ $t("Body.hint") }}</p>
    </div>
</template>

<style scoped lang="scss">
.body-day {
    margin: 16px 0;
    padding: 12px 16px;
    background: #f0efef;
    color: #555;
    font-size: 15px;
    line-height: 1.4;

    &__row {
        display: flex;
        align-items: center;
        flex-wrap: wrap;
        gap: 8px;
    }

    &__label {
        font-weight: 600;
    }

    &__input {
        width: 80px;
        padding: 6px 8px;
        font-size: 15px;
        border: 1px solid $border-color;
        border-radius: 8px;
        background: #fff;
    }

    &__button {
        padding: 6px 14px;
        color: #fff;
        background: $main-color;
        border: none;
        border-radius: 8px;
        cursor: pointer;

        &:disabled {
            opacity: 0.6;
        }
    }

    &__activities {
        margin-top: 10px;

        ul {
            margin: 4px 0 0;
            padding: 0;
            list-style: none;
        }
    }

    &__activity {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 8px;
        padding: 4px 0;
    }

    &__remove {
        padding: 0 8px;
        font-size: 20px;
        color: #999;
        background: none;
        border: none;
        cursor: pointer;
    }

    &__hint {
        margin: 10px 0 0;
        font-size: 13px;
        color: #888;
    }
}
</style>

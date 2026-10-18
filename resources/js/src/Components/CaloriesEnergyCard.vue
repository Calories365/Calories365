<script>
import { mapState } from "vuex";
import { applyEnergyNorm, getEnergy } from "@/api/energy.js";
import { actionTypes as authActionTypes } from "@/store/modules/auth.js";

/**
 * The real daily expenditure the diary measured from what was eaten and how
 * the weight went, and the norm it gives for the goal; until the diary has
 * enough days, what it still needs.
 */
export default {
    name: "CaloriesEnergyCard",
    data() {
        return {
            energy: null,
            applying: false,
        };
    },
    computed: {
        ...mapState({
            currentUser: (state) => state.auth.currentUser,
        }),
        weightChange() {
            const change = this.energy?.weight_change_per_week ?? 0;

            return `${change > 0 ? "+" : ""}${change}`;
        },
    },
    async mounted() {
        if (!this.currentUser) {
            return;
        }

        try {
            const { data } = await getEnergy();
            this.energy = data;
        } catch (error) {
            // The calculator works without this card.
        }
    },
    methods: {
        async apply() {
            this.applying = true;

            try {
                const { data } = await applyEnergyNorm();
                this.energy = data;
                this.$store.dispatch(authActionTypes.getCurrentUser);
                this.$store.dispatch(
                    "setSuccess",
                    this.$t("Energy.applied", { norm: data.norm })
                );
            } catch (error) {
                this.$store.dispatch("setError", this.$t("Energy.notApplied"));
            } finally {
                this.applying = false;
            }
        },
    },
};
</script>

<template>
    <section v-if="energy" class="energy-card">
        <h2 class="energy-card__title">{{ $t("Energy.title") }}</h2>

        <template v-if="energy.status === 'ready'">
            <p class="energy-card__value">
                {{ $t("Energy.expenditure", { calories: energy.expenditure }) }}
            </p>
            <p class="energy-card__text">
                {{
                    $t("Energy.explained", {
                        days: energy.logged_days,
                        intake: energy.intake,
                        change: weightChange,
                    })
                }}
            </p>
            <p class="energy-card__text">
                {{
                    $t(`Energy.norm.${energy.goal}`, {
                        norm: energy.norm,
                    })
                }}
            </p>
            <button
                v-if="energy.norm !== energy.calories_limit"
                type="button"
                class="energy-card__button"
                :disabled="applying"
                @click="apply"
            >
                {{ $t("Energy.apply", { norm: energy.norm }) }}
            </button>
            <p v-else class="energy-card__text">{{ $t("Energy.isNorm") }}</p>
        </template>

        <p v-else-if="energy.status === 'unclear'" class="energy-card__text">
            {{
                $t("Energy.unclear", {
                    intake: energy.intake,
                    change: weightChange,
                })
            }}
        </p>

        <template v-else>
            <p class="energy-card__text">
                {{
                    $t("Energy.collecting", {
                        days: energy.needed.logged_days,
                        weights: energy.needed.weights,
                        span: energy.needed.weight_span_days,
                    })
                }}
            </p>
            <p class="energy-card__text">
                {{
                    $t("Energy.progress", {
                        days: energy.logged_days,
                        weights: energy.weights,
                    })
                }}
            </p>
        </template>
    </section>
</template>

<style scoped lang="scss">
.energy-card {
    max-width: 720px;
    margin: 16px auto;
    padding: 16px 20px;
    color: #555;
    line-height: 1.5;
    background: #f7f9f7;
    border-left: 4px solid $main-color;
    border-radius: 12px;

    &__title {
        margin: 0 0 6px;
        font-size: 1.2rem;
        font-weight: 700;
        color: #333;
    }

    &__value {
        margin: 0 0 6px;
        font-size: 1.4rem;
        font-weight: 700;
        color: $main-color;
    }

    &__text {
        margin: 0 0 6px;
    }

    &__button {
        margin-top: 6px;
        padding: 10px 18px;
        font-size: 1rem;
        font-weight: 600;
        color: #fff;
        background: $main-color;
        border: none;
        border-radius: 10px;
        cursor: pointer;

        &:disabled {
            opacity: 0.6;
        }
    }
}
</style>

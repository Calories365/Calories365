<script>
import CaloriesCard from "@/Components/CaloriesCard.vue";
import CaloriesCalculationSlider from "@/Components/CaloriesCalculationSlider.vue";
import CaloriesCalculationDropdown from "@/Components/CaloriesCalculationDropdown.vue";
import CalorieChart from "@/Components/CalorieChart.vue";
import CaloriesButton from "@/Components/CaloriesButton.vue";
import { mapState } from "vuex";
import { actionTypes } from "@/store/modules/calculation.js";
import { actionTypes as userActionTypes } from "@/store/modules/auth.js";
import i18n from "@/i18n.js";
import store from "@/store/store.js";

export default {
    name: "CaloriesCalculationsSectionResults",
    components: {
        CaloriesButton,
        CalorieChart,
        CaloriesCalculationDropdown,
        CaloriesCalculationSlider,
        CaloriesCard,
    },
    data() {
        return {
            isActive: false,
        };
    },
    props: {},
    computed: {
        ...mapState({
            userResults: (state) => state.calculation.userResults,
            currentUser: (state) => state.auth.currentUser,
        }),
    },
    methods: {
        toggleActive() {
            this.isActive = !this.isActive;
        },
        saveData() {
            if (this.currentUser) {
                this.$store
                    .dispatch(actionTypes.saveCalculationData)
                    .then(() => {
                        this.$store.dispatch(userActionTypes.getCurrentUser);
                    });
            } else {
                const message = i18n.global.t("Notification.Error.NeedAuth");
                store.dispatch("setError", message, { root: true });
            }
        },
    },
};
</script>

<template>
    <section class="calculator-section">
        <div class="results_with_arrows">
            <div class="arrow_mobile">↓</div>
            <p class="calculator-section_head">
                {{ $t("calculationResult.calculatorSectionHeadOne") }}
            </p>
            <div class="arrow_mobile">↓</div>
        </div>
        <div class="res-button">
            <calories-button
                passed-class="extra-padding"
                @click="saveData()"
                class="calculator-section_head-button"
            >
                {{ $t("calculationResult.Save") }}
            </calories-button>
        </div>

        <p class="calculator-section_head calories-padding-top">
            {{ $t("calculationResult.calculatorSectionHeadTwo") }}
        </p>

        <p class="calculator-section_calories">
            {{ userResults.bmr ? userResults.bmr : 0 }}
            {{ $t("calculationResult.calculatorSectionCalories") }}
        </p>

        <p class="calculator-section_joules">
            ({{ Math.round(userResults.bmr * 4, 1868) }}
            {{ $t("calculationResult.calculatorSectionJoules") }})
        </p>
        <div class="info-block">
            <div class="info-icon">?</div>
            <div class="info-text">
                {{ $t("calculationResult.infoBlockText") }}
            </div>
        </div>

        <p class="calculator-section_head calories-padding-top">
            {{ $t("calculationResult.calculatorSectionHeadOneBMI") }}
        </p>

        <p class="calculator-section_calories calories-padding-bottom">
            {{ userResults.bmi ? userResults.bmi : 0 }}
        </p>

        <div class="calculator-section_IMT">
            <div class="IMT-block-container">
                <div
                    class="IMT-block"
                    :class="{
                        'active-block-bad':
                            userResults.humanWeightClassifications === 1,
                    }"
                ></div>
                <span
                    class="IMT-block-text"
                    :class="{
                        active: userResults.humanWeightClassifications === 1,
                    }"
                    >{{ $t("calculationResult.IMTBlockTextUnderweight") }}</span
                >
            </div>

            <div class="IMT-block-container">
                <div
                    class="IMT-block"
                    :class="{
                        'active-block-good':
                            userResults.humanWeightClassifications === 2,
                    }"
                ></div>
                <span
                    class="IMT-block-text"
                    :class="{
                        active: userResults.humanWeightClassifications === 2,
                    }"
                    >{{ $t("calculationResult.IMTBlockTextNormal") }}</span
                >
            </div>

            <div class="IMT-block-container">
                <div
                    class="IMT-block"
                    :class="{
                        'active-block-not-good':
                            userResults.humanWeightClassifications === 3,
                    }"
                ></div>
                <span
                    class="IMT-block-text"
                    :class="{
                        active: userResults.humanWeightClassifications === 3,
                    }"
                    >{{ $t("calculationResult.IMTBlockTextOverweight") }}</span
                >
            </div>

            <div class="IMT-block-container">
                <div
                    class="IMT-block"
                    :class="{
                        'active-block-bad':
                            userResults.humanWeightClassifications === 4,
                    }"
                ></div>
                <span
                    class="IMT-block-text"
                    :class="{
                        active: userResults.humanWeightClassifications === 4,
                    }"
                    >{{ $t("calculationResult.IMTBlockTextObesity1") }}</span
                >
            </div>

            <div class="IMT-block-container">
                <div
                    class="IMT-block"
                    :class="{
                        'active-block-bad':
                            userResults.humanWeightClassifications === 5,
                    }"
                ></div>
                <span
                    class="IMT-block-text"
                    :class="{
                        active: userResults.humanWeightClassifications === 5,
                    }"
                    >{{ $t("calculationResult.IMTBlockTextObesity2") }}</span
                >
            </div>

            <div class="IMT-block-container">
                <div
                    class="IMT-block"
                    :class="{
                        'active-block-very-bad':
                            userResults.humanWeightClassifications === 6,
                    }"
                ></div>
                <span
                    class="IMT-block-text"
                    :class="{
                        active: userResults.humanWeightClassifications === 6,
                    }"
                    >{{ $t("calculationResult.IMTBlockTextObesity3") }}</span
                >
            </div>
        </div>

        <div class="info-block">
            <div class="info-icon">?</div>
            <div class="info-text">
                {{ $t("calculationResult.infoBlockTextBMI") }}
            </div>
        </div>
        <p class="calculator-section_head">
            {{ $t("calculationResult.goalWeight") }}
        </p>
        <p
            class="calculator-section_calories-blue calories-padding-top calories-padding-bottom"
        >
            {{ userResults.daysRequired ? userResults.daysRequired : 0 }}
            {{ $t("calculationResult.days") }}
        </p>

        <p class="calculator-section_head">
            {{
                $t(
                    "calculationResult.calculatorSectionHeadTwoCaloriesRecommendation"
                )
            }}
        </p>

        <p class="calculator-section_calories-blue calories-padding-top">
            {{ userResults.dailyCalories ? userResults.dailyCalories : 0 }}
            {{ $t("calculationResult.calculatorSectionCaloriesBlue") }}
        </p>

        <p class="calculator-section_joules-blue calories-padding-bottom">
            ({{ Math.round(userResults.dailyCalories * 4, 1868) }}
            {{ $t("calculationResult.calculatorSectionJoulesBlue") }})
        </p>
        <div class="info-block">
            <div class="info-icon">?</div>
            <div class="info-text">
                {{ $t("calculationResult.calculatorSectionCalorieReason") }}
            </div>
        </div>

        <p class="calculator-section_head" style="padding-bottom: 20px">
            {{
                $t(
                    "calculationResult.calculatorSectionHeadTwoCaloriesRecommendationDistribution"
                )
            }}:
        </p>

        <div class="nutrient-legend">
            <div class="nutrient-item">
                <span class="color-box" style="background-color: red"></span>
                <p class="nutrient-item_text">
                    {{ $t("calculationResult.protein") }}
                    {{
                        userResults.PFC.proteinPercent
                            ? userResults.PFC.proteinPercent
                            : 0
                    }}%
                </p>
            </div>
            <div class="nutrient-item">
                <span class="color-box" style="background-color: blue"></span>
                <p class="nutrient-item_text">
                    {{ $t("calculationResult.carbohydrates") }}
                    {{
                        userResults.PFC.carbsPercent
                            ? userResults.PFC.carbsPercent
                            : 0
                    }}%
                </p>
            </div>
            <div class="nutrient-item">
                <span class="color-box" style="background-color: yellow"></span>
                <p class="nutrient-item_text">
                    {{ $t("calculationResult.fats") }}
                    {{
                        userResults.PFC.fatPercent
                            ? userResults.PFC.fatPercent
                            : 0
                    }}%
                </p>
            </div>
        </div>

        <CalorieChart class="calculator-section_chart" />

        <p
            class="calculator-section_head calories-padding-bottom calories-padding-top"
        >
            {{ $t("calculationResult.calorieDiaryAppHeading") }}
        </p>
    </section>
</template>

<style scoped lang="scss">
.nutrient-legend {
    display: flex;
    justify-content: space-around;
    flex-wrap: wrap;
}

.nutrient-item {
    display: flex;
    align-items: center;
    font-size: 16px;

    &_text {
        font-family: Arial, Helvetica, sans-serif;
        font-size: $default-font-size;
        line-height: 1.5;
        color: #666666;
        text-align: justify;
        margin: 1.5vh 0;
        padding: 0 2vh;
    }
}

.color-box {
    width: 50px;
    height: 20px;
    display: inline-block;
    margin-right: 5px;
}


.calories-padding-top {
    padding-top: 30px
}

.calories-padding-bottom {
    padding-bottom: 30px
}

.calculator-section {
    width: 85%;
    margin: 0 auto;
    background-color: white;

    @media (max-width: 768px) {
        width: 100%;
        padding: 0 3vh 3vh 3vh;
    }

    &_head {
        font-size: $default-head-font-size;
        text-align: center;
        margin: auto;
    }

    &_head-button {
        display: block;
        margin: 0 auto;
    }

    &_head-button.inactive {
        opacity: 0.5;
        pointer-events: none;
    }

    &_head-two {
        font-size: 28px;
        text-align: center;
        margin: auto;
    }

    &_description {
        font-family: Arial, Helvetica, sans-serif;
        font-size: $default-font-size;
        line-height: 1.5;
        text-align: justify;
        margin: 1.5vh 0;
        padding: 0 2vh;
        color: #666666;
    }

    &_calories {
        //font-size: $default-font-size;
        text-align: center;
        padding-top: 2vh;
        font-size: 25px;
        color: #689f38;
    }

    &_calories-blue {
        text-align: center;
        padding-top: 2vh;
        font-size: 25px;
        color: #0091ea;
    }

    &_joules {
        text-align: center;
        padding-top: 2vh;
        font-size: 20px;
        color: #689f38;
    }

    &_joules-blue {
        text-align: center;
        padding-top: 2vh;
        font-size: 20px;
        color: #0091ea;
    }

    .info-block {
        font-size: $default-font-size;
        margin-top: 30px;
        display: flex;
        align-items: center;
        background: #FFFFFF;
        border: 1px solid #aaa;
        border-radius: 20px;
        padding-top: 20px;
        padding-bottom: 20px;
        margin-bottom: 30px;
        padding-left: 15px;

        .info-icon {
            font-size: 100px;
            color: #aaa;
            padding-left: 5vh;
            padding-right: 5vh;
            margin-right: 10px;
        }

        .info-text {
            font-size: $default-font-size;
            line-height: 1.5;
            color: #aaa;
        }

    }

    &_IMT {
        margin-top: 2vh;
        display: flex;
        justify-content: center;
        gap: 3vh;
        flex-wrap: wrap;
        @media (max-width: 768px) {
            gap: 0;
            margin-bottom: 6vh;
        }

        .IMT-block-container {
            position: relative;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: space-between;
        }


        .IMT-block {
            width: 100px;
            height: 20px;
            background: #ddd;
            @media (max-width: 768px) {
                display: none;
            }

            &.active-block-bad {
                background: red;
                @media (max-width: 768px) {
                    display: block;
                }
            }

            &.active-block-good {
                background: green;
                @media (max-width: 768px) {
                    display: block;
                }
            }

            &.active-block-not-good {
                background: yellow;
                @media (max-width: 768px) {
                    display: block;
                }
            }

            &.active-block-very-bad {
                background: #420000;
                @media (max-width: 768px) {
                    display: flex;
                }
            }
        }


        .IMT-block-text {
            font-size: $default-font-size;
            padding: 1vh;
            text-align: center;
            @media (max-width: 768px) {
                display: none;
                &.active {
                    display: block;
                }
            }


        }

        .IMT-block-text.active-block {
            @media (max-width: 768px) {
                display: inline;
                padding: 2vh;
            }
        }
    }

    &_chart {
        margin-top: 2vh;
        margin-bottom: 2vh;
    }


}
.results_with_arrows{
    display: flex;
    align-items: center;
}
.arrow_mobile {
    padding-right: 100px;
    padding-left: 100px;
    font-size: 5rem;
    color: $main-color;
    @media (max-width: 768px) {
        padding-right: 1px;
        padding-left: 1px;
        font-size: 3rem;
    }
}
.res-button{
    display: flex;
    justify-content: center;
    padding-top: 10px;
    padding-bottom: 25px;
}
</style>

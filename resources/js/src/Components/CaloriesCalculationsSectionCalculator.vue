<script>
import { debounce } from "lodash";
import CaloriesCard from "@/Components/CaloriesCard.vue";
import CaloriesCalculationSlider from "@/Components/CaloriesCalculationSlider.vue";
import CaloriesCalculationDropdown from "@/Components/CaloriesCalculationDropdown.vue";
import { actionTypes } from "@/store/modules/calculation.js";
import { mapState } from "vuex";
import CaloriesButton from "./CaloriesButton.vue";

export default {
    name: "CaloriesCalculationsSectionCalculator",
    components: {
        CaloriesButton,
        CaloriesCalculationDropdown,
        CaloriesCalculationSlider,
        CaloriesCard,
    },
    data() {
        return {
            isActive: false,
            gender: "female",
            birthYear: 1900,
            weight: 30,
            height: 100,
            goalWeight: 60,
            fat: 0,
            activity: 0,
            goal: 0,
            checkboxActive: false,
            properties: [
                "gender",
                "birthYear",
                "weight",
                "height",
                "goalWeight",
                "fat",
                "activity",
                "goal",
                "checkboxActive",
            ],
        };
    },
    methods: {
        transformData() {
            let transformedData = {};
            this.properties.forEach((key) => {
                transformedData[key] = this[key];
            });
            return transformedData;
        },
        toggleActive(gender) {
            this.gender = gender;
        },
        handleCheckboxChange(checkboxState) {
            this.checkboxActive = checkboxState;
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
        calculateResults: debounce(function () {
            this.$store.dispatch(
                actionTypes.countResults,
                this.transformData()
            );
        }, 1000),
    },
    computed: {
        ...mapState({
            currentUser: (state) => state.auth.currentUser,
            userData: (state) => state.calculation.userData,
        }),
        translatedActivityOptions() {
            return [
                this.$t("message.activityOptionLow"),
                this.$t("message.activityOptionModerate"),
                this.$t("message.activityOptionAverage"),
                this.$t("message.activityOptionHigh"),
                this.$t("message.activityOptionVeryHigh"),
            ];
        },
        translatedGoals() {
            return [
                this.$t("message.goalLoseWeight"),
                this.$t("message.goalStayFit"),
                this.$t("message.goalGainMass"),
            ];
        },
    },
    mounted() {
        if (this.currentUser) {
            this.$store
                .dispatch(actionTypes.getCalculationData)
                .then((data) => {
                    for (const key in data) {
                        this[key] = data[key];
                    }
                    this.$store.dispatch(actionTypes.countResults, data);
                });
        } else {
            this.$store
                .dispatch(actionTypes.getCalculationDataNotAuth)
                .then((data) => {
                    for (const key in data) {
                        this[key] = data[key];
                    }
                    this.$store.dispatch(actionTypes.countResults, data);
                });
        }
        this.properties.forEach((property) => {
            this.$watch(property, this.calculateResults);
        });
    },
};
</script>

<template>
    <section class="calculator-section">
        <p class="calculator-section_head">
            {{ $t("message.calculatorSectionHead") }}
        </p>
        <!--        <p class="calculator-section_description">-->
        <!--            {{ $t('message.calculatorSectionDescription') }}-->
        <!--        </p>-->
        <div class="calculator-section_gender-buttons-container">
            <button
                class="calculator-section_gender-button"
                :class="{ disabled: gender === 'male' }"
                @click="toggleActive('female')"
            >
                {{ $t("message.calculatorSectionGenderButtonFemale") }}
            </button>
            <button
                class="calculator-section_gender-button"
                :class="{ disabled: gender === 'female' }"
                @click="toggleActive('male')"
            >
                {{ $t("message.calculatorSectionGenderButtonMale") }}
            </button>
        </div>

        <div class="calculator-section_users-data">
            <calories-calculation-slider
                :from="1900"
                :to="2024"
                :name="$t('message.caloriesCalculationSliderBirthYear')"
                :value="birthYear"
                @update="birthYear = $event"
            />

            <calories-calculation-slider
                :from="10"
                :to="200"
                :name="$t('message.caloriesCalculationSliderWeight')"
                :value="weight"
                @update="weight = $event"
            />

            <calories-calculation-slider
                :from="20"
                :to="300"
                :name="$t('message.caloriesCalculationSliderHeight')"
                :value="height"
                @update="height = $event"
            />

            <calories-calculation-slider
                :from="10"
                :to="200"
                :name="$t('message.caloriesCalculationSliderTargetWeight')"
                :value="goalWeight"
                @update="goalWeight = $event"
            />

            <calories-calculation-slider
                :from="0"
                :to="100"
                :name="$t('message.caloriesCalculationSliderFatPercentage')"
                :value="fat"
                :checkBox="true"
                :checkBoxActive="Boolean(checkboxActive)"
                @update="fat = $event"
                @checkboxChanged="handleCheckboxChange"
            />
            <div class="calculator-section_dropdowns">
                <calories-calculation-dropdown
                    class="calculator-section_dropdown"
                    :name="$t('message.caloriesCalculationDropdownActivity')"
                    :subName="$t('message.caloriesCalculationDropdownActivity')"
                    :options="translatedActivityOptions"
                    :value="Number(activity)"
                    @update="activity = $event"
                />
            </div>
        </div>
        <!--        <div class="res-button">-->
        <!--        <calories-button-->
        <!--            passed-class="extra-padding"-->
        <!--            @click="saveData()" class="calculator-section_head-button">-->
        <!--            {{ $t('calculationResult.Save') }}-->
        <!--        </calories-button>-->
        <!--        </div>-->
    </section>
</template>

<style scoped lang="scss">
.second-section {
    margin-top: 10vh;
}

.calculator-section {
    width: 85%;
    margin: 0 auto;
    background-color: white;
    padding-bottom: 0;

    @media (max-width: 768px) {
        width: 100%;
        padding: 0 3vh 3vh 3vh;
        padding-bottom: 0;
    }


    &_head {
        font-weight: bold;
        padding-top: 30px;
        padding-bottom: 30px;
        font-size: $default-head-font-size;
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
        padding-bottom: 20px;
        color: #666666;
    }
    &_description_v2 {
        font-family: Arial, Helvetica, sans-serif;
        font-size: $default-font-size;
        line-height: 1.5;
        //text-align: justify;
        //margin: 1.5vh 0;
        text-align: center;
        padding: 0 2vh;
        color: #666666;
    }

    &_gender-buttons-container {
        margin-top: 2vh;
        display: flex;
        justify-content: center;
        align-items: center;
        gap: 1.5vh;
    }

    &_gender-button {
        width: 500px;
        height: 50px;
        background-color: #4CAF50;
        color: white;
        border: none;
        padding: 1vh 8vh;
        margin-bottom: 20px;
        font-size: $default-font-size;
        cursor: pointer;
        border-radius: 5px;
        transition: background-color 0.3s, box-shadow 0.3s, transform 0.3s;
        text-align: center;

        @media (max-width: 768px) {
            padding: 1vh 4vh;
            font-size: 14px;
        }
    }


    &_gender-button:hover {
        background-color: #367C39;
        box-shadow: inset 0 2px 4px rgba(0, 0, 0, 0.24);
    }

    &_gender-button.active {
        background-color: #367C39;
        box-shadow: inset 0 2px 4px rgba(0, 0, 0, 0.24);
    }

    &_gender-button.disabled {
        background-color: #e0e0e0;
        color: #000000;
        cursor: default;
        box-shadow: none;
    }

    &_gender-button.disabled:hover {
        background-color: rgba(140, 134, 134, 0.5);
        box-shadow: 0 2px 4px 0 rgba(0, 0, 0, 0.24), 0 4px 5px 0 rgba(0, 0, 0, 0.19);
    }

    &_dropdowns {
        display: flex;
        gap: 20px;
        margin-top: 2vh;


        @media (max-width: 768px) {
            display: block;
        }
    }

    &_dropdown {
        //max-width: 350px;
    }

    &_users-data {

    }


}
</style>

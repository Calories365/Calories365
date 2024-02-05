<script>
import {debounce} from 'lodash';
import CaloriesCard from "@/Components/CaloriesCard.vue";
import CaloriesCalculationSlider from "@/Components/CaloriesCalculationSlider.vue";
import CaloriesCalculationDropdown from "@/Components/CaloriesCalculationDropdown.vue";
import {actionTypes} from "@/store/modules/calculation.js";
import {mapState} from "vuex";

export default {
    name: "CaloriesCalculationsSectionCalculator",
    components: {CaloriesCalculationDropdown, CaloriesCalculationSlider, CaloriesCard},
    data() {
        return {
            isActive: false,
            gender: "female",
            birthYear: 0,
            weight: 0,
            height: 0,
            goalWeight: 0,
            fat: 0,
            activity: 0,
            goal: 0,
            checkboxActive: false,
            properties: ['gender', 'birthYear', 'weight', 'height',
                'goalWeight', 'fat', 'activity', 'goal', 'checkboxActive']
        };
    },
    methods: {
        transformData() {
            let transformedData = {};
            this.properties.forEach(key => {
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
        calculateResults: debounce(function () {
            this.$store.dispatch(actionTypes.countResults, this.transformData());
        }, 500),
    },
    computed: {
        ...mapState({
            currentUser: state => state.auth.currentUser,
            userData: state => state.calculation.userData,
        }),
        translatedActivityOptions() {
            return [
                this.$t('message.activityOptionLow'),
                this.$t('message.activityOptionModerate'),
                this.$t('message.activityOptionAverage'),
                this.$t('message.activityOptionHigh'),
                this.$t('message.activityOptionVeryHigh')
            ];
        },
        translatedGoals() {
            return [
                this.$t('message.goalLoseWeight'),
                this.$t('message.goalStayFit'),
                this.$t('message.goalGainMass')
            ];
        }
    },
    mounted() {
        if (this.currentUser) {
            this.$store.dispatch(actionTypes.getCalculationData)
                .then((data) => {
                    for (const key in data) {
                        this[key] = data[key]
                    }
                    this.$store.dispatch(actionTypes.countResults, data)
                });
        }
        this.properties.forEach(property => {
            this.$watch(property, this.calculateResults);
        });
    },
}
</script>

<template>
    <section class="calculator-section">
        <p class="calculator-section_head">{{ $t('message.calculatorSectionHead') }}</p>
        <div class="calculator-section_gender-buttons-container">
            <button
                class="calculator-section_gender-button"
                :class="{ 'disabled': gender === 'male' }"
                @click="toggleActive('female')"
            >
                {{ $t('message.calculatorSectionGenderButtonFemale') }}
            </button>
            <button
                class="calculator-section_gender-button"
                :class="{ 'disabled': gender === 'female' }"
                @click="toggleActive('male')"
            >
                {{ $t('message.calculatorSectionGenderButtonMale') }}
            </button>

        </div>

        <div class="calculator-section_users-data">
            <calories-calculation-slider :from="1900" :to="2024"
                                         :name="$t('message.caloriesCalculationSliderBirthYear')"
                                         :value="birthYear"
                                         @update="birthYear = $event"
            />

            <calories-calculation-slider :from="10" :to="200"
                                         :name="$t('message.caloriesCalculationSliderWeight')"
                                         :value="weight"
                                         @update="weight = $event"/>

            <calories-calculation-slider :from="20" :to="300"
                                         :name="$t('message.caloriesCalculationSliderHeight')"
                                         :value="height"
                                         @update="height = $event"/>

            <calories-calculation-slider :from="10" :to="200"
                                         :name="$t('message.caloriesCalculationSliderTargetWeight')"
                                         :value="goalWeight"
                                         @update="goalWeight = $event"/>

            <calories-calculation-slider :from="0" :to="100"
                                         :name="$t('message.caloriesCalculationSliderFatPercentage')"
                                         :value="fat"
                                         :checkBox="true"
                                         :checkBoxActive="Boolean(checkboxActive)"
                                         @update="fat = $event"
                                         @checkboxChanged="handleCheckboxChange"
            />
            <div class="calculator-section_dropdowns">
                <calories-calculation-dropdown class="calculator-section_dropdown"
                                               :name="$t('message.caloriesCalculationDropdownActivity')"
                                               :options="translatedActivityOptions"
                                               :value="activity"
                                               @update="activity = $event"
                />
                <calories-calculation-dropdown class="calculator-section_dropdown"
                                               :name="$t('message.caloriesCalculationDropdownGoal')"
                                               :options="translatedGoals"
                                               :value="goal"
                                               @update="goal = $event"
                />
            </div>
        </div>

<h1>222</h1>
        <p class="calculator-section_description">
            {{ $t('message.calculatorSectionDescription') }}
        </p>

<!--        <p class="calculator-section_head">{{ $t('message.calculatorSectionHeadBodyWeight') }}</p>-->


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

    @media (max-width: 768px) {
        width: 100%;
        padding: 0 3vh 3vh 3vh;
    }


    &_head {
        font-weight: bold;
        padding-top: 30px;
        padding-bottom: 30px;
        font-size: $default-head-font-size;
        text-align: center; /* Центрирование текста */
        margin: auto; /* Горизонтальное центрирование блока, если необходимо */
    }

    &_description {
        font-family: Arial, Helvetica, sans-serif;
        font-size: $default-font-size; /* Размер шрифта можно настроить по вашему усмотрению */
        line-height: 1.5; /* Межстрочный интервал */
        text-align: justify; /* Выравнивание текста по ширине для лучшего вида; можно заменить на 'left' */
        //color: #333; /* Темно-серый цвет для лучшего контраста, можно настроить */
        margin: 1.5vh 0; /* Отступы сверху и снизу */
        padding: 0 2vh; /* Внутренние поля справа и слева */
        color: #666666;

    }

    &_gender-buttons-container {
        margin-top: 2vh;
        display: flex; /* Применяем Flexbox */
        justify-content: center; /* Центрирование кнопок по горизонтали */
        align-items: center; /* Выравнивание кнопок по вертикали */
        gap: 1.5vh; /* Расстояние между кнопками */
    }

    &_gender-button {
        width: 500px;
        height: 50px;
        background-color: #4CAF50; /* Основной зелёный цвет кнопки */
        color: white; /* Цвет текста */
        border: none; /* Убрать границу */
        padding: 1vh 8vh; /* Внутренние отступы для увеличения размера кнопки */
        margin-bottom: 20px;
        font-size: $default-font-size; /* Размер шрифта */
        cursor: pointer; /* Курсор в виде руки при наведении на кнопку */
        border-radius: 5px; /* Скругление углов кнопки */
        transition: background-color 0.3s, box-shadow 0.3s, transform 0.3s; /* Плавные переходы для анимации */
        text-align: center;

        @media (max-width: 768px) {
            padding: 1vh 4vh; /* Уменьшенные внутренние отступы для мобильных устройств */
            font-size: 14px; /* Немного уменьшаем размер шрифта */
        }
    }


    &_gender-button:hover {
        background-color: #367C39; /* Ещё более темный зелёный для активного состояния */
        box-shadow: inset 0 2px 4px rgba(0, 0, 0, 0.24); /* Внутренняя тень для эффекта вдавленности */
    }

    &_gender-button.active {
        background-color: #367C39; /* Ещё более темный зелёный для активного состояния */
        box-shadow: inset 0 2px 4px rgba(0, 0, 0, 0.24); /* Внутренняя тень для эффекта вдавленности */
    }

    &_gender-button.disabled {
        background-color: #e0e0e0; /* Серый фон для неактивной кнопки */
        color: #000000; /* Серый цвет текста для неактивной кнопки */
        cursor: default; /* Стандартный курсор для неактивной кнопки */
        box-shadow: none; /* Убрать тень */
    }

    &_gender-button.disabled:hover {
        background-color: rgba(140, 134, 134, 0.5); /* Чуть темнее зелёный при наведении */
        box-shadow: 0 2px 4px 0 rgba(0, 0, 0, 0.24), 0 4px 5px 0 rgba(0, 0, 0, 0.19); /* Тень для эффекта приподнятости */
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


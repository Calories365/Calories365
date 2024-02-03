<script>

import {debounce} from "lodash";

export default {
    name: "CaloriesCalculationSlider",
    props: {
        from: {
            type: Number,
            required: true,
        },
        to: {
            type: Number,
            required: true,
        },
        measurementsType: {
            type: String,
        },
        name: {
            type: String,
            required: true,
        },
        checkBox: {
            type: Boolean,
        },
        checkBoxActive: {
            type: Boolean,
        },
        value: {
            type: Number,
        }
    },
    data() {
        return {
            isCheckboxActive: this.checkBoxActive,
            varriable: this.value,
        };
    },
    watch: {
        value(newValue) {
            this.varriable = newValue; // Обновите внутреннее состояние при изменении prop
        },
        checkBoxActive(newValue) {
            this.isCheckboxActive = newValue;
        }
    },

    methods: {
        updateVariable(event) {
            const newValue = event.target.value;
            this.varriable = newValue;
            this.emitData(newValue)
        },
        emitData: debounce(function (newValue) {
            this.$emit('update', Number(newValue));
        }, 500),
        emitCheckboxState(event) {
            this.isCheckboxActive = event.target.checked; // Задайте состояние в соответствии с состоянием чекбокса
            this.$emit('checkboxChanged', event.target.checked);
        }
    },
}
</script>

<template>
    <div class="users-data-section">

        <span :class="{ 'inactive': !isCheckboxActive && checkBox }" class="users-data-section_name">{{ name }}</span>

        <div :class="{ 'inactive': !isCheckboxActive && checkBox }" class="slider-container">
            <input type="range" :min="from" :max="to" class="slider" id="myRange" :value="varriable"
                   @input="updateVariable">
        </div>

        <div class="input-container">
            <input :class="{ 'inactive': !isCheckboxActive && checkBox }" type="text" class="styled-input"
                   :value="varriable"
                   @input="updateVariable">

            <input class="calories-checkBox" v-if="checkBox" type="checkbox" id="scales" name="scales"
                   v-model="isCheckboxActive" @change="emitCheckboxState"/>

            <span v-if="checkBox" class="calories-checkBox_description">{{
                    $t('message.fatPercentageDescription')
                }}</span>
        </div>

    </div>
</template>

<style scoped lang="scss">
.inactive {
    opacity: 0.5; /* делает содержимое полупрозрачным */
    pointer-events: none; /* предотвращает взаимодействие с элементами */
}

.calories-checkBox:hover + .calories-checkBox_description {
    display: inline-block; /* Показать при наведении на чекбокс */
}

.input-container {
    position: relative;
}

.calories-checkBox {
    background-color: green; /* Задает зеленый фон для чекбокса */
    border: 2px solid green; /* Задает зеленую границу для чекбокса */
    position: absolute;
    top: 18px;
    left: 110px;

    &_description {
        display: none;
        font-size: $default-font-size;
        color: white;
        padding: 5px;
        width: 300px; /* или любая другая ширина */
        position: absolute;
        background-color: rgba(0, 0, 0, 0.4);
        right: 0;
        top: -30px;
        z-index: 1234;
    }
}

.users-data-section {
    margin-top: 2vh;
    display: flex; // Используем flexbox для выравнивания элементов в ряд
    align-items: center; // Центрируем элементы по вертикали
    gap: 20px; // Добавляем небольшой промежуток между элементами

    @media (max-width: 768px) {
        justify-content: space-between;
    }

    &_name {
        font-size: $default-font-size;;
        flex: 0.6;
        //width: 11vh;
    }

    .slider-container {
        flex: 4;
        position: relative;
        width: 100%;

        @media (max-width: 600px) {
            display: none;
        }

        .slider {
            //flex: 1;
            -webkit-appearance: none;
            width: 100%;
            height: 10px;
            background: #ddd;
            outline: none;
            opacity: 0.7;
            -webkit-transition: .2s;
            transition: opacity .2s;

            &:hover {
                opacity: 1;
            }

            &::-webkit-slider-thumb {
                -webkit-appearance: none;
                width: 25px;
                height: 25px;
                background: #4CAF50;
                cursor: pointer;
                border-radius: 50%;
                border: solid 2px white;
            }

            &::-moz-range-thumb {
                width: 25px;
                height: 25px;
                background: #4CAF50;
                cursor: pointer;
                border-radius: 50%;
                border: solid 2px white;
            }
        }
    }

    .styled-input {
        border: 2px solid #4CAF50; /* Цвет рамки */
        border-radius: 20px; /* Радиус скругления углов */
        font-size: $default-font-size;; /* Размер шрифта */
        padding: 10px 20px; /* Внутренние отступы */
        box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1); /* Тень */
        outline: none; /* Убираем стандартный фокус */
        text-align: center; /* Текст по центру */
        width: 100px;

        &:focus {
            border-color: #3e8e41; /* Цвет рамки при фокусе */
        }
    }
}

</style>

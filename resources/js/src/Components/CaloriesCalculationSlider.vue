<script>
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
        value: {
            type: Number,
        },
        checkBox: {
            type: Boolean,
        },
        checkBoxActive: {
            type: Boolean,
        },
    },
    computed: {
        variable: {
            get() {
                return this.value;
            },
            set(newValue) {
                this.emitData(newValue);
            },
        },
        isCheckboxActive: {
            get() {
                return this.checkBoxActive;
            },
            set(newValue) {
                this.emitCheckboxState(newValue);
            },
        },
    },
    methods: {
        updateVariable(event) {
            this.variable = event.target.value;
        },
        emitData(newValue) {
            this.$emit("update", Number(newValue));
        },
        emitCheckboxState(newValue) {
            this.$emit("checkboxChanged", newValue);
        },
    },
};
</script>

<template>
    <div class="users-data-section">
        <span
            :class="{ inactive: !isCheckboxActive && checkBox }"
            class="users-data-section_name"
            >{{ name }}</span
        >

        <div
            :class="{ inactive: !isCheckboxActive && checkBox }"
            class="slider-container"
        >
            <input
                type="range"
                :min="from"
                :max="to"
                class="slider"
                id="myRange"
                :value="variable"
                @input="updateVariable"
            />
        </div>

        <div class="input-container">
            <input
                :class="{ inactive: !isCheckboxActive && checkBox }"
                type="number"
                class="styled-input"
                :value="variable"
                :min="from"
                :max="to"
                @input="updateVariable"
            />

            <input
                class="calories-checkBox"
                v-if="checkBox"
                type="checkbox"
                id="scales"
                name="scales"
                v-model="isCheckboxActive"
            />

            <span v-if="checkBox" class="calories-checkBox_description">{{
                $t("message.fatPercentageDescription")
            }}</span>
        </div>
    </div>
</template>

<style scoped lang="scss">
.inactive {
    opacity: 0.5;
    pointer-events: none;
}

.calories-checkBox:hover + .calories-checkBox_description {
    display: inline-block;
}

.input-container {
    position: relative;
}

.calories-checkBox {
    background-color: green;
    border: 2px solid green;
    position: absolute;
    top: 18px;
    left: 110px;
    @media (max-width: 600px) {
        left: -40px;
    }

    &_description {
        display: none;
        font-size: $default-font-size;
        color: white;
        padding: 5px;
        width: 300px;
        position: absolute;
        background-color: rgba(0, 0, 0, 0.4);
        right: 0;
        top: -30px;
        z-index: 1234;
        @media (max-width: 600px) {
            top: -135px;
        }
    }
}

.users-data-section {
    margin-top: 2vh;
    display: flex;
    align-items: center;
    gap: 20px;

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
        border: 2px solid lightgray;
        border-radius: 20px;
        font-size: $default-font-size;
        padding: 10px 20px;
        outline: none;
        text-align: center;
        width: 100px;

        &:focus {
            border-color: #3e8e41;
        }
    }
}
</style>

<script>
import { FontAwesomeIcon } from "@fortawesome/vue-fontawesome";

export default {
    name: "CaloriesCalculationDropdown",
    components: { FontAwesomeIcon },
    props: {
        options: {
            type: Array,
            required: true,
        },
        name: {
            type: String,
            required: true,
        },
        value: {
            type: Number,
            required: true,
        },
    },
    data() {
        return {
            isOpen: false,
            isActive: false,
            isHiding: false,
            selectedOption: null,
        };
    },
    methods: {
        toggleActive() {
            if (!this.isActive) {
                this.isActive = true;
            } else {
                this.startHideAnimation();
            }
        },
        startHideAnimation() {
            this.isHiding = true; // Новая переменная данных для контроля анимации
            setTimeout(() => {
                this.isActive = false;
                this.isHiding = false;
            }, 500); // Соответствует продолжительности анимации fadeOut
        },
        handleClickOutside(event) {
            if (!this.$el.contains(event.target) && this.isActive) {
                this.startHideAnimation();
            }
        },
        setName(option, event) {
            this.selectedOption = option;
            const dataIndex =
                event.currentTarget.getAttribute("data-attribute");
            this.updateVariable(dataIndex);
        },
        updateVariable(option) {
            console.log("Emitting update event with:", option);
            this.$emit("update", option);
        },
        updateSelectedOption(newValue) {
            const optionIndex = newValue - 1;
            this.selectedOption = this.options[optionIndex];
        },
    },
    watch: {
        value(newValue) {
            this.updateSelectedOption(newValue);
        },
    },
    mounted() {
        document.addEventListener("click", this.handleClickOutside);

        const optionIndex = this.value - 1;

        this.selectedOption = this.options[optionIndex];
    },
    beforeUnmount() {
        document.removeEventListener("click", this.handleClickOutside);
    },
};
</script>

<template>
    <div class="dropdown-section">
        <div class="calculation-select" @click.stop="toggleActive">
            <div class="calculation-select_text">
                {{ selectedOption ? selectedOption : name }}
            </div>
            <div
                class="calculation-select_active"
                :class="{
                    'calculation-select_show': isActive,
                    'calculation-select_hide': isHiding,
                }"
            >
                <ul class="calculation-select_list">
                    <li
                        v-for="(option, index) in options"
                        :key="index"
                        :data-attribute="index + 1"
                        @click="setName(option, $event)"
                        class="calculation-select_li"
                    >
                        {{ option }}
                    </li>
                </ul>
            </div>

            <div class="calculation-select_arrow">
                <font-awesome-icon :icon="['fas', 'angle-down']" />
            </div>
        </div>
    </div>
</template>

<style scoped lang="scss">
.dropdown-section {
    cursor: pointer;
    margin: 0 auto;
    width: 75%;
    align-items: center;
    padding: 10px;
    gap: 20px;

    &_name {
        margin: 0 auto;
    }

    .dropdown {
        width: 250px;
        border: 1px solid green;
        padding: 10px;
        border-radius: 5px;
    }

    .calculation-select {
        position: relative;
        display: flex;
        justify-content: center;
        gap: 3vh;
        padding: 2vh;
        border: 1px solid #4CAF50;
        border-radius: 0.75em;

        &_text {
            font-size: $default-font-size;
        }

        &_arrow {

        }

        &_active {
            display: none;
            position: absolute;
            z-index: 999;
            top: 100%;
            left: 50%;
            transform: translate(-50%, 0%);
            width: auto;
            min-width: 100%;
            background-color: white;
            border-radius: 5px;
            border: 1px solid #4CAF50;
            box-shadow: 0px 4px 8px rgba(0, 0, 0, 0.2), 0px 6px 10px rgba(0, 0, 0, 0.1);
        }

        &_show {
            display: block;
            animation: fadeIn 0.2s;
        }

        &_list {
            border-radius: 5px;
        }

        &_li {
            font-size: $default-font-size;
            padding: 1vh 0;
            //border: 1px solid #4CAF50;
            border-radius: 5px;
            text-align: center;
        }

        &_li:hover {
            background-color: #e0e0e0;
            box-shadow: 0px 4px 8px rgba(0, 0, 0, 0.2);
        }

        &_li:active {
            background-color: #4CAF50;
            color: white;
            transform: scale(0.95);
            box-shadow: inset 0 0 10px rgba(0, 0, 0, 0.2);
        }

        &_hide {
            animation: fadeOut 0.2s forwards;
        }
    }
}

@keyframes fadeIn {
    from {
        opacity: 0;
    }
    to {
        opacity: 1;
    }
}

@keyframes fadeOut {
    from {
        opacity: 1;
    }
    to {
        opacity: 0;
    }
}
</style>

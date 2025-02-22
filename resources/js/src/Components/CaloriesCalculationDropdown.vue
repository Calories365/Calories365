<script>
import {FontAwesomeIcon} from "@fortawesome/vue-fontawesome";
import clickOutside from "@/directives/clickOutside.js";


export default {
    name: "CaloriesCalculationDropdown",
    components: {FontAwesomeIcon},
    directives: {
        'click-outside': clickOutside
    },
    props: {
        options: {
            type: Array,
            required: true,
        },
        name: {
            type: String,
            required: true,
        },
        subName: {
            type: String,
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
            isActiveArrow: false,
            isHiding: false,
        }
    },
    computed: {
        selectedOptionComputed() {
            const optionIndex = this.value - 1;
            return this.options[optionIndex];
        }
    },
    methods: {
        toggleActive() {
            if (!this.isActive) {
                this.isActive = true;
                this.isActiveArrow = true;
            } else {
                this.startHideAnimation();
            }
        },
        startHideAnimation() {
            this.isHiding = true;
            this.isActiveArrow = false;

            setTimeout(() => {
                this.isActive = false;
                this.isHiding = false;
            }, 500);
        },
        handleClickOutside() {
            if (this.isActive) {
                this.startHideAnimation();
            }
        },
        selectOption(option, event) {
            this.selectedOption = option;
            const dataIndex = event.currentTarget.getAttribute('data-attribute');
            this.updateVariable(dataIndex);
        },
        updateVariable(option) {
            this.$emit('update', option);
        },
    },
}
</script>

<template>
    <div class="dropdown-section" v-click-outside="handleClickOutside">
        <div class="calculation-select" @click.stop="toggleActive">
            <div class="calculation-select_text">
                {{ selectedOptionComputed
                ? (selectedOptionComputed + ' ' + subName.toLowerCase())
                : name
                }}
            </div>

            <div class="calculation-select_active"
                 :class="{ 'calculation-select_show': isActive, 'calculation-select_hide': isHiding }">
                <ul class="calculation-select_list">
                    <li v-for="(option, index) in options"
                        :key="index"
                        :data-attribute="index + 1"
                        @click="selectOption(option, $event)"
                        class="calculation-select_li">
                        {{ option }}
                    </li>
                </ul>
            </div>

            <div class="calculation-select_arrow">
                <span class="dropdown-arrow"
                      :class="{ 'active': isActiveArrow}"></span>
            </div>
        </div>


    </div>

</template>

<style scoped lang="scss">

.dropdown-arrow {
    display: inline-block;
    //position: absolute;ч
    transform: translateY(-15%) rotate(-45deg);
    content: "";
    margin-left: 6px;
    width: 10px;
    height: 10px;
    border-left: 2px solid #666666;
    border-bottom: 2px solid #666666;
    transition: 0.3s ease;
    padding: 7px;

    &.active {
        transform: translateY(45%) rotate(135deg);
    }
}

.dropdown-section {
    cursor: pointer;
    margin: 0 auto;
    width: 75%;
    align-items: center;
    padding: 0 0 10px 0;
    gap: 20px;
    @media (max-width: 768px) {
        width: 100%;
    }

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
        gap: 20px;
        padding: 2vh;
        border: 1px solid lightgray;
        border-radius: 0.75em;

        &_text {
            font-size: $default-font-size;
            position: relative;
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
            border: 1px solid lightgray;
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

<script>
import {FontAwesomeIcon} from "@fortawesome/vue-fontawesome";
import clickOutside from "@/directives/clickOutside.js";


export default {
    name: "CaloriesCalculationLocaleChanger",
    components: {FontAwesomeIcon},
    directives: {
        'click-outside': clickOutside
    },
    data() {
        return {
            isOpen: false,
            isActive: false,
            isHiding: false,
            selectedLocale: localStorage.getItem('locale') || 'en',
            locales: ['en', 'ua', 'ru'],
        }
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
        changeLocale(locale) {
            this.selectedLocale = locale;
            this.$i18n.locale = locale;
            localStorage.setItem('locale', locale);
            this.isOpen = false; // Закрыть выпадающий список после выбора
        },

    },
    computed: {
        filteredLocales() {
            return this.locales.filter(locale => locale !== this.selectedLocale);
        },
    },

    mounted() {
        this.changeLocale(localStorage.getItem('locale') || 'en');
        document.addEventListener('click', this.handleClickOutside);
    },
    beforeUnmount() {
        document.removeEventListener('click', this.handleClickOutside);
    }


}

</script>

<template>
    <div class="dropdown-section">

        <div class="calculation-select" @click.stop="toggleActive">
            <div class="calculation-select_text">{{ selectedLocale }}</div>

            <div class="calculation-select_active"
                 :class="{ 'calculation-select_show': isActive, 'calculation-select_hide': isHiding }">
                <ul class="calculation-select_list">
                    <li @click="changeLocale(locale)" class="calculation-select_li" v-for="locale in filteredLocales">{{
                            locale
                        }}
                    </li>
                </ul>
            </div>

            <div class="calculation-select_arrow">
                <font-awesome-icon :icon="['fas', 'angle-down']"/>
            </div>
        </div>


    </div>

</template>

<style scoped lang="scss">
.dropdown-section {
    cursor: pointer;
    margin: 0 auto;
    align-items: center;

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
        max-width: 50px;
        max-height: 50px;
        position: relative;
        display: flex;
        justify-content: center;
        gap: 7px;
        padding: 2vh;

        &_text {
            background: transparent;
            align-self: center;
            max-height: 40px;
            padding: 10px 30px;
            color: black;
            font-weight: 600;
            font-size: 0.95em;
            border-radius: 2px;
            text-transform: uppercase;
        }

        &_arrow {
            position: absolute;
            left: 80%;
            top: 35%;
        }

        &_active {
            display: none;
            position: absolute;
            z-index: 999;
            top: 180%;
            left: 50%;
            transform: translate(-50%, -50%); // Сдвинуть список назад на 50% его собственной ширины и высоты
            min-width: 100%; // Убедитесь, что минимальная ширина не меньше родителя
            background-color: white;
        }

        &_show {
            display: block;
            animation: fadeIn 0.2s;
        }

        &_list {
            padding-left: 0;
            margin-bottom: 0;
        }

        &_li {
            width: 100px;
            padding: 1vh 0;
            text-align: center;
            background: transparent;
            align-self: center;
            max-height: 40px;
            color: black;
            font-weight: 600;
            font-size: 0.95em;
            border-radius: 2px;
            text-transform: uppercase;

        }

        &_li:hover {
            background-color: #e0e0e0; /* светло-серый цвет */
            box-shadow: 0px 4px 8px rgba(0, 0, 0, 0.2);
        }

        &_li:active {
            background-color: #4CAF50; /* Цвет фона при активации */
            color: white; /* Цвет текста при активации */
            transform: scale(0.95); /* Немного уменьшить элемент при нажатии */
            box-shadow: inset 0 0 10px rgba(0, 0, 0, 0.2); /* Внутренняя тень для эффекта нажатия */
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

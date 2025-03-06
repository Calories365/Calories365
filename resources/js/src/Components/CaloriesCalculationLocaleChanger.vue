<script>
import {FontAwesomeIcon} from "@fortawesome/vue-fontawesome";
import clickOutside from "@/directives/clickOutside.js";
import {actionTypes} from "@/store/modules/changeLocale.js";
import {mapState} from "vuex";


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
            this.isHiding = true;
            setTimeout(() => {
                this.isActive = false;
                this.isHiding = false;
            }, 500);
        },
        handleClickOutside(event) {
            if (this.isActive) {
                this.startHideAnimation();
            }
        },
        changeLocale(locale) {
            this.$store.dispatch(actionTypes.setLocale, {locale, i18n: this.$i18n})
            this.isOpen = false;
        },
    },
    computed: {
        ...mapState({
            selectedLocale: state => state.changeLocale.selectedLocale,
            locales: state => state.changeLocale.locales,
            russianEnabled: state => state.language ? state.language.russianLanguageEnabled : true
        }),
        filteredLocales() {
            // Filter out Russian when it's disabled
            const availableLocales = this.russianEnabled ? 
                this.locales : 
                this.locales.filter(locale => locale !== 'ru');
                
            return availableLocales.filter(locale => locale !== this.selectedLocale);
        },
    },
    mounted() {
        // Fetch language status when component is mounted
        this.$store.dispatch('language/fetchLanguageStatus');
        
        // Check if the current locale is Russian and it's disabled, switch to English
        this.$nextTick(() => {
            if (this.selectedLocale === 'ru' && !this.russianEnabled) {
                this.changeLocale('en');
            }
        });
    },
}

</script>

<template>
    <div class="dropdown-section" v-click-outside="handleClickOutside">

        <div class="calculation-select" @click.stop="toggleActive">
            <div class="calculation-select_text">{{ selectedLocale }}</div>

            <div class="calculation-select_active"
                 :class="{ 'calculation-select_show': isActive, 'calculation-select_hide': isHiding }">
                <ul class="calculation-select_list">
                    <li @click="changeLocale(locale)" class="calculation-select_li" v-for="locale in filteredLocales"
                        :key="locale">
                        {{ locale }}
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
        //gap: 7px;
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
            @media (max-width: $bp-small) {
                font-size: .85em;
            }

        }

        &_arrow {
            position: absolute;
            left: 80%;
            top: 35%;
        }

        &_active {
            display: none;
            position: absolute;
            z-index: 9999999;
            top: 140%;
            left: 50%;
            transform: translate(-50%, -50%);
            min-width: 100%;
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
            width: 100%;
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

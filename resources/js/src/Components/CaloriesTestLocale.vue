<script>
import {FontAwesomeIcon} from "@fortawesome/vue-fontawesome";

export default {
    name: "CaloriesTestLocale",
    components: {FontAwesomeIcon},
    data() {
        return {
            isOpen: false,
            isActive: false,
            selectedLocale: localStorage.getItem('locale') || 'en',
            locales: ['en', 'ua'],
        };
    },
    methods: {
        toggleDropdown() {
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
        changeLocale(locale) {
            this.selectedLocale = locale;
            this.$i18n.locale = locale;
            localStorage.setItem('locale', locale);
            this.isOpen = false;
        },
        handleClickOutside(event) {
            if (!this.$el.contains(event.target)) {
                this.isOpen = false;
            }
        },
    },
    mounted() {
        document.addEventListener('click', this.handleClickOutside);
    },
    beforeUnmount() {
        document.removeEventListener('click', this.handleClickOutside);
    }
}

</script>

<template>
    <div class="dropdown-section">
        <div class="calculation-select" @click.stop="toggleDropdown">
            <div class="calculation-select_text">{{ selectedLocale }}</div>
            <div class="calculation-select_active" v-if="isOpen">
                <ul class="calculation-select_list">
                    <li v-for="locale in locales" :key="locale"
                        @click="changeLocale(locale)"
                        class="calculation-select_li">
                        {{ locale.toUpperCase() }}
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

        &_arrow {

        }

        &_active {
            display: none;
            position: absolute;
            z-index: 999;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            width: auto;
            min-width: 100%;
            background-color: white;
            box-shadow: 0px 4px 8px rgba(0, 0, 0, 0.2), 0px 6px 10px rgba(0, 0, 0, 0.1);
        }

        &_show {
            display: block;
            animation: fadeIn 0.2s;
        }

        &_list {
        }

        &_li {
            padding: 1vh 0;
            border: 1px solid #4CAF50;
            text-align: center;
        }

        &_li:hover {
            background-color: #e0e0e0;
            box-shadow: 0px 4px 8px rgba(0, 0, 0, 0.2);
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



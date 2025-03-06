<script>
import {faAngleDown} from '@fortawesome/free-solid-svg-icons'
import {library} from '@fortawesome/fontawesome-svg-core'
import CaloriesTestLocale from "@/Components/CaloriesTestLocale.vue";
import CaloriesHeaderV2 from "@/Components/CaloriesHeaderV2.vue";
import CaloriesCalculationLocaleChanger from "@/Components/CaloriesCalculationLocaleChanger.vue";
import {actionTypes} from "@/store/modules/changeLocale.js";
import CaloriesErrorNotification from "@/Components/CaloriesErrorNotification.vue";
import CaloriesSuccessNotification from "@/Components/CaloriesSuccessNotification.vue";

library.add(faAngleDown)

export default {
    data() {
        return {
            username: 'Пена',
            locale: localStorage.getItem('locale') || 'ua',
        };
    },
    components: {
        CaloriesSuccessNotification,
        CaloriesErrorNotification,
        CaloriesCalculationLocaleChanger,
        CaloriesHeaderV2,
        CaloriesTestLocale,
    },
    computed: {
        successMessage() {
            return this.$store.getters.isSuccess;
        },
        errorMessage() {
            return this.$store.getters.isError;
        }
    },
    methods: {
        initializeLanguage() {
            // Safeguard against errors during language initialization
            try {
                this.$store.dispatch('language/fetchLanguageStatus')
                    .then(() => {
                        const language = this.$store.state.language;
                        // If Russian is disabled and current locale is Russian, switch to English
                        if (this.locale === 'ru' && language && language.russianLanguageEnabled === false) {
                            this.locale = 'en';
                        }
                        this.$store.dispatch(actionTypes.setLocale, {locale: this.locale, i18n: this.$i18n});
                    })
                    .catch(error => {
                        console.error('Error loading language settings:', error);
                        // If there's an error, proceed with default locale
                        this.$store.dispatch(actionTypes.setLocale, {locale: this.locale, i18n: this.$i18n});
                    });
            } catch (error) {
                console.error('Error in language initialization:', error);
                // If there's an error, proceed with default locale
                this.$store.dispatch(actionTypes.setLocale, {locale: this.locale, i18n: this.$i18n});
            }
        }
    },
    mounted() {
        this.initializeLanguage();
    }
}
</script>
<template>
    <div class="page-wrapper">
        <calories-success-notification v-if="successMessage">
            {{ successMessage }}
        </calories-success-notification>

        <calories-error-notification v-if="errorMessage">
            {{ errorMessage }}
        </calories-error-notification>

        <!-- Шапка -->
        <calories-header-v2/>

        <main id="primary" class="main-wrapper">
            <article class="main-article">
                <router-view/>
            </article>
        </main>

        <!-- Показуємо футер тільки якщо поточний маршрут — 'home' -->
<!--        <footer class="main-footer" v-if="$route.name === 'home'">-->
<!--            <router-link :to="{ name: 'privacyPolicy' }" class="privacy-link">-->
<!--                Privacy Policy-->
<!--            </router-link>-->
<!--            <router-link :to="{ name: 'termsOfService' }" class="privacy-link">-->
<!--                Terms of Service-->
<!--            </router-link>-->
<!--            <router-link :to="{ name: 'faq' }" class="privacy-link">-->
<!--                FAQ-->
<!--            </router-link>-->
<!--        </footer>-->
    </div>
</template>

<style lang="scss">
.page-wrapper {
    display: flex;
    flex-direction: column;
    min-height: 100vh;
}

.main-wrapper {
    flex: 1; /* Додаємо, щоб основний вміст розтягувався */
    font-family: 'Open Sans', sans-serif;
    font-style: normal;
    font-variant: normal;
    text-rendering: optimizeLegibility;
    font-feature-settings: "kern" 1;
    color: #666666;

    @media (max-width: 48em) {
        margin-top: 40px;
    }
}

.main-footer {
    margin-top: 2rem;
    padding: 1rem 0;
    text-align: center;
    background-color: #f8f9fa;
    color: #666;

    .privacy-link {
        padding-right: 10px;
        text-decoration: underline;
        color: #666;

        &:hover {
            color: #333;
        }
    }
}
</style>

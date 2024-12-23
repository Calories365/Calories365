<script>
import {faAngleDown} from '@fortawesome/free-solid-svg-icons'
import {library} from '@fortawesome/fontawesome-svg-core'
import CaloriesTestLocale from "@/Components/CaloriesTestLocale.vue";
import CaloriesHeaderV2 from "@/Components/CaloriesHeaderV2.vue";
import CaloriesCalculationLocaleChanger from "@/Components/CaloriesCalculationLocaleChanger.vue";
import {actionTypes} from "@/store/modules/changeLocale.js";
import CaloriesErrorNotification from "@/Components/CaloriesErrorNotification.vue";
import CaloriesSuccessNotification from "@/Components/CaloriesSuccessNotification.vue";

// Подключаем иконку
library.add(faAngleDown)

export default {
    data() {
        return {
            username: 'Пена',
            locale: localStorage.getItem('locale') || 'en',
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
    mounted() {
        this.$store.dispatch(actionTypes.setLocale, {locale: this.locale, i18n: this.$i18n})
    }
}
</script>

<template>
    <calories-success-notification
        v-if="successMessage">{{ successMessage }}
    </calories-success-notification>

    <calories-error-notification
        v-if="errorMessage">{{ errorMessage }}
    </calories-error-notification>

    <!-- Шапка -->
    <calories-header-v2/>

    <main id="primary" class="main-wrapper">
        <article class="main-article">
            <router-view/>
        </article>
    </main>

    <!-- Футер со ссылкой на Политику конфиденциальности -->
    <footer class="main-footer">
        <router-link :to="{ name: 'privacyPolicy' }" class="privacy-link">
            Privacy Policy
        </router-link>
        <router-link :to="{ name: 'termsOfService' }" class="privacy-link"
        >Terms of Service
        </router-link><router-link :to="{ name: 'faq' }" class="privacy-link"
        >FAQ
        </router-link>
    </footer>
</template>

<style lang="scss">


.main-wrapper {
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

.main-article {
    // ваш контент
}

/* Футер внизу */
.main-footer {
    margin-top: 2rem;
    padding: 1rem 0;
    text-align: center;
    background-color: #f8f9fa;
    color: #666;

    .privacy-link {
        padding-right: 10px;
        text-decoration: underline;
        color: #666; /* При желании можете поменять */
        &:hover {
            color: #333;
        }
    }
}
</style>

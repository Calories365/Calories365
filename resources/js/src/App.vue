<script>
//font awesome
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
            locale: localStorage.getItem('locale') || 'en',
        };
    },
    components: {
        CaloriesSuccessNotification, CaloriesErrorNotification,
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

    <calories-header-v2/>


    <main id="primary" class="main-wrapper">

        <article class="main-article">

            <router-view/>

        </article>

    </main>
</template>

<style lang="scss">


.calories_input {
    padding: 7px;
}

.main-wrapper {

    font-family: 'Open Sans', sans-serif;
    font-style: normal;
    font-variant: normal;
    text-rendering: optimizeLegibility;
    font-feature-settings: "kern" 1;
    color: #666666;
}

@media (max-width: 48em) {
    .main-wrapper {

        margin-top: 40px;
    }
}

.main-wrapper {
    //min-height: 80%;

    &.layout-fixed-margin {
        /*из-за того что header fixed*/
        @media (min-width: $bp-medium + 0.1em) {
            margin-top: 70px;
        }
        @media (max-width: $bp-small) {
            //margin-top: 40px;
        }
    }
}

.main-article {

}

</style>

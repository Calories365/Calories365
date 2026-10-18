<script>
import { faAngleDown } from "@fortawesome/free-solid-svg-icons";
import { library } from "@fortawesome/fontawesome-svg-core";
import CaloriesTestLocale from "@/Components/CaloriesTestLocale.vue";
import CaloriesHeaderV2 from "@/Components/CaloriesHeaderV2.vue";
import CaloriesCalculationLocaleChanger from "@/Components/CaloriesCalculationLocaleChanger.vue";
import { actionTypes } from "@/store/modules/changeLocale.js";
import CaloriesErrorNotification from "@/Components/CaloriesErrorNotification.vue";
import CaloriesSuccessNotification from "@/Components/CaloriesSuccessNotification.vue";
import CookieBanner from "./Components/CookieBanner.vue";
import MiniAppTabBar from "@/Components/MiniApp/MiniAppTabBar.vue";
import MiniAppAuthError from "@/Components/MiniApp/MiniAppAuthError.vue";
import MiniAppOpenHint from "@/Components/MiniApp/MiniAppOpenHint.vue";
import { isMiniApp, isTelegramWithoutData } from "@/telegram/miniApp.js";
import { getterTypes } from "@/store/modules/auth.js";

library.add(faAngleDown);

export default {
    data() {
        const urlParams = new URLSearchParams(window.location.search);
        const langParam = urlParams.get("lang");

        const validLocales = ["en", "ru", "ua"];
        let userLocale = localStorage.getItem("locale");

        if (langParam && validLocales.includes(langParam)) {
            userLocale = langParam;
            localStorage.setItem("locale", userLocale);
        } else if (!userLocale || !validLocales.includes(userLocale)) {
            userLocale = "ua";
            localStorage.setItem("locale", userLocale);
        }
        return {
            username: "Пена",
            locale: userLocale,
            isMiniApp: isMiniApp(),
            telegramWithoutData: isTelegramWithoutData(),
            openHintDismissed: false,
        };
    },
    components: {
        MiniAppAuthError,
        MiniAppOpenHint,
        MiniAppTabBar,
        CookieBanner,
        CaloriesSuccessNotification,
        CaloriesErrorNotification,
        CaloriesCalculationLocaleChanger,
        CaloriesHeaderV2,
        CaloriesTestLocale,
    },
    computed: {
        // Telegram signs the visitor in before the app mounts, so a logged-out
        // Mini App means that handshake failed.
        miniAppAuthFailed() {
            return (
                this.isMiniApp &&
                !this.telegramWithoutData &&
                !this.$store.getters[getterTypes.isLoggedIn]
            );
        },
        // Opened inside Telegram without the data to sign in: the site would ask
        // for a password the person never set, so first say how to open the app.
        showOpenHint() {
            return (
                this.telegramWithoutData &&
                !this.openHintDismissed &&
                !this.$store.getters[getterTypes.isLoggedIn]
            );
        },
        successMessage() {
            return this.$store.getters.isSuccess;
        },
        errorMessage() {
            return this.$store.getters.isError;
        },
    },
    methods: {
        applyLocale(locale) {
            this.locale = locale;
            return this.$store.dispatch(actionTypes.setLocale, {
                locale,
                i18n: this.$i18n,
            });
        },
        initializeLanguage() {
            const ensureLocale = () => {
                const storedLocale =
                    localStorage.getItem("locale") || this.locale;
                return this.applyLocale(storedLocale);
            };

            try {
                this.$store
                    .dispatch("language/fetchLanguageStatus")
                    .then(() => {
                        return ensureLocale();
                    })
                    .catch((error) => {
                        console.error(
                            "Error loading language settings:",
                            error
                        );
                        return ensureLocale();
                    });
            } catch (error) {
                console.error("Error in language initialization:", error);
                ensureLocale();
            }
        },
    },
    created() {
        this.applyLocale(this.locale);
    },
    mounted() {
        this.initializeLanguage();
    },
};
</script>
<template>
    <div class="page-wrapper" :class="{ 'page-wrapper--mini-app': isMiniApp }">
        <calories-success-notification v-if="successMessage">
            {{ successMessage }}
        </calories-success-notification>

        <calories-error-notification v-if="errorMessage">
            {{ errorMessage }}
        </calories-error-notification>

        <calories-header-v2 v-if="!isMiniApp && !showOpenHint" />

        <!-- PremiumNotice is off while payment is: it promised a limit of 10
             voice inputs that no longer exists. -->

        <cookie-banner v-if="!isMiniApp && !showOpenHint" />

        <mini-app-auth-error v-if="miniAppAuthFailed" />

        <mini-app-open-hint
            v-else-if="showOpenHint"
            :can-sign-in-with-email="!isMiniApp"
            @dismiss="openHintDismissed = true"
        />

        <main v-else id="primary" class="main-wrapper">
            <article class="main-article">
                <router-view />
            </article>
        </main>

        <mini-app-tab-bar
            v-if="isMiniApp && !miniAppAuthFailed && !showOpenHint"
        />
    </div>
</template>

<style lang="scss">
.page-wrapper {
    display: flex;
    flex-direction: column;
    min-height: 100vh;
}

/* Telegram draws its own header, and the tab bar sits on top of the content. */
.page-wrapper--mini-app {
    min-height: var(--tg-viewport-height, 100vh);
    background: #ffffff;

    .main-wrapper {
        margin-top: 0;
        padding-bottom: calc(64px + env(safe-area-inset-bottom, 0px));
    }
}

.main-wrapper {
    flex: 1;
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

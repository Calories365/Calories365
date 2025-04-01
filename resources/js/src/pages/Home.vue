<script>
import { mapState } from "vuex";
import { actionTypes } from "@/store/modules/auth.js";
import CaloriesButton from "../Components/CaloriesButton.vue";
import PremiumSection from "../Components/PremiumSection.vue";
import { useAcademic } from '@/composables/useAcademic';

export default {
    name: "LandingPage",
    components: { PremiumSection, CaloriesButton },
    data() {
        return {
            telegramAuth: null,
            telegramLink: null,
            termsAgreed: false
        };
    },
    computed: {
        ...mapState({
            currentUser: state => state.auth.currentUser,
        }),
        /**
         * Depending on the current locale, we return the corresponding video.
         */
        videoUrl() {
            const locale = this.$i18n.locale;
            if (locale === 'ua') {
                return 'https://www.youtube.com/embed/UJO-9fank5c';
            } else if (locale === 'ru') {
                return 'https://www.youtube.com/embed/_-fZHn5Xv8c';
            } else {
                return 'https://www.youtube.com/embed/q-42W0YCldk';
            }
        },
        telegramLinkText() {
            if (!this.currentUser) {
                return this.$t("Cabinet.Connect");
            }
            if (!this.currentUser.email_verified_at) {
                return this.$t("Cabinet.ConfirmEmail");
            }
            return this.telegramAuth
                ? this.$t("Cabinet.Connected")
                : this.$t("Cabinet.Connect");
        },
        isPremium() {
            if (!this.currentUser?.premium_until) {
                return false;
            }
            const premiumUntil = new Date(this.currentUser.premium_until);
            return premiumUntil > new Date();
        }
    },
    methods: {
        goToCalculator() {
            this.$router.push({ name: "calculation" });
        },
        goToDiary() {
            this.$router.push({ name: "diary" });
        },
        goToStats() {
            this.$router.push({ name: "stats" });
        },
        openTelegramLink() {
            if (this.currentUser && this.currentUser.email_verified_at) {
                this.$router.push({ name: "voice" });
            } else {
                this.$router.push("/login");
            }
        },
        buyPremium() {
            if (!this.termsAgreed) {
                this.$store.dispatch('setError', this.$t('Home.AgreeToTermsRequired'));
                return;
            }
            if (!this.currentUser) {
                this.$router.push({ name: "cabinet" });
                return;
            }
            this.$store.dispatch(actionTypes.buyPremium).then((url) => {
                window.location.href = url;
            });
        },
        goToPremium() {
            this.$router.push({ name: "premium" });
        }
    },
    mounted() {
        const savedLocale = localStorage.getItem('locale');
        if (!savedLocale) {
            this.$i18n.locale = 'uk';
            localStorage.setItem('locale', 'uk');
        } else {
            this.$i18n.locale = savedLocale;
        }

        const paymentStatus = this.$route.query.payment;
        if (paymentStatus === "success") {
            this.$store.dispatch("setSuccess", this.$t("Cabinet.PaymentSuccess"));
        } else if (paymentStatus === "error") {
            const errorMessage = this.$t("Cabinet.PaymentError");
            this.$store.dispatch("setError", errorMessage);
        }


        if (this.currentUser) {
            this.telegramAuth = this.currentUser.telegram_auth;
            this.$store.dispatch(actionTypes.getTelegramLink)
                .then(link => {
                    this.telegramLink = link;
                })
                .catch(error => {
                    console.error("Error fetching telegram link:", error);
                });
        }
    }
};
</script>

<template>
    <div class="landing-page">
        <section class="hero">
            <div class="container hero-content">
                <div class="hero-image-container">
                    <img v-if="!$isAcademic" src="@/assets/miaaa33.jpg" alt="Главное фото" class="hero-image" />
                    <img v-if="$isAcademic" src="@/assets/111111.jpg" alt="Главное фото" class="hero-image" />
                </div>
                <div class="hero-action">
                    <h2>{{ $t("Home.Title") }}</h2>
                    <p>
                        {{ $t("Home.DescriptionP1") }}
                        <span class="hero-action__desc-link" @click="openTelegramLink">{{ $t("message.voice") }}</span>
                        {{ $t("Home.DescriptionP2") }}
                    </p>
                    <div class="hero-buttons">
                        <calories-button @click="goToCalculator" class="calculator-section_head-button">
                            {{ $t("Home.LoseWeight") }}
                        </calories-button>
                    </div>
                </div>
            </div>
        </section>
        <section class="features">
            <div class="container features-grid">
                <div class="feature">
                    <h3>{{ $t("Home.VoiceInputTitle") }}</h3>
                    <p>{{ $t("Home.VoiceInputDesc") }}</p>
                    <calories-button @click="openTelegramLink" class="mid-info_button">
                        {{ $t("Home.GoToVoice") }}
                    </calories-button>
                </div>
                <div class="feature">
                    <h3>{{ $t("Home.DiaryTitle") }}</h3>
                    <p>{{ $t("Home.DiaryDesc") }}</p>
                    <calories-button class="mid-info_button" @click="goToDiary">
                        {{ $t("Home.DiaryButton") }}
                    </calories-button>
                </div>
                <div class="feature">
                    <h3>{{ $t("Home.StatsTitle") }}</h3>
                    <p>{{ $t("Home.StatsDesc") }}</p>
                    <calories-button class="mid-info_button" @click="goToStats">
                        {{ $t("Home.StatsButton") }}
                    </calories-button>
                </div>
            </div>
        </section>

        <section class="transformation">
            <div class="container transformation-content">
                <div class="transformation-images">
                    <img src="@/assets/SadAndFat.jpg" alt="До" class="transformation-image" />
                    <div class="arrow_desktop">→</div>
                    <div class="arrow_mobile">↓</div>
                    <img src="@/assets/HappyAndFit.jpg" alt="После" class="transformation-image" />
                </div>
                <div class="hero-buttons">
                    <calories-button @click="goToCalculator" class="calculator-section_head-button" passed-class="extra-padding">
                        {{ $t("Home.LoseWeight") }}
                    </calories-button>
                </div>
            </div>
        </section>

<!--        <section class="premium" v-if="!$isAcademic">-->
<!--            <PremiumSection :isPremium="isPremium" :currentUser="currentUser" :is-home-page="true" />-->
<!--        </section>-->

        <footer class="main-footer" v-if="$route.name === 'home'">
            <div class="container">
                <div class="footer-links">
                    <router-link :to="{ name: 'privacyPolicy' }" class="privacy-link">{{ $t("Home.PrivacyPolicy") }}</router-link>
                    <router-link :to="{ name: 'termsOfService' }" class="privacy-link">{{ $t("Home.TermsOfService") }}</router-link>
                    <router-link :to="{ name: 'faq' }" class="privacy-link">{{ $t("Home.FAQ") }}</router-link>
                </div>
                <div class="main-footer_info-for-payment">
                    <div class="contact-info">
                        <p>Email: calories365.diary@gmail.com</p>
                    </div>
                    <div class="payment-logos">
                        <!-- <img src="@/assets/1159x220.svg" alt="Visa" /> -->
                    </div>
                </div>
            </div>
        </footer>
    </div>
</template>

<style scoped lang="scss">
.landing-page {
    font-family: Arial, Helvetica, sans-serif;
    font-size: $default-font-size;
    line-height: 1.5;
    color: #666666;

    .container {
        width: 90%;
        max-width: 1300px;
        margin: 0 auto;
        padding: 20px;
    }

    /* Banner section */
    .hero {
        background-color: #f9f9f9;
        padding: 40px 0;

        .hero-content {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 100px;
            flex-wrap: wrap;

            @media (max-width: $bp-medium) {
                gap: 30px;
            }
        }

        .hero-image-container {
            text-align: center;
            .hero-image {
                width: 400px;
                height: 400px;
                object-fit: cover;
                border-radius: 50%;
                border: 2px solid $main-color;

                @media (max-width: $bp-medium) {
                    width: 300px;
                    height: 300px;
                }
            }
        }

        .hero-action {
            max-width: 500px;
            display: flex;
            flex-direction: column;
            align-items: center;
            text-align: center;

            &__desc-link {
                text-decoration: underline;
                transition: all 0.3s ease;
                color: $main-color;
                cursor: pointer;
            }

            h2 {
                font-size: 2.5rem;
                margin-bottom: 20px;
            }

            p {
                font-size: 1.2rem;
                margin-bottom: 20px;
            }

            .hero-buttons {
                display: flex;
                flex-wrap: wrap;
                gap: 10px;
                button {
                    color: #fff;
                    border: none;
                    padding: 10px 20px;
                    font-size: 1.25rem;
                    border-radius: 5px;
                    cursor: pointer;
                    transition: background-color 0.3s ease;
                }
            }
        }
    }

    /* Features section */
    .features {
        background: #fff;
        padding: 60px 0;
        @media (max-width: $bp-medium) {
            padding: 15px 0;
        }

        .features-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
        }

        .feature {
            background: #f1f1f1;
            padding: 20px;
            border-radius: 8px;
            text-align: center;
            display: flex;
            flex-direction: column;
            height: 100%;

            h3 {
                font-size: 1.8rem;
                margin-bottom: 10px;
            }

            p {
                font-size: 1.1rem;
                line-height: 1.5;
                margin-bottom: 20px;
            }

            .mid-info_button {
                margin-top: auto;
            }
        }
    }

    /* Video section*/
    .video-section {
        background-color: #f9f9f9;
        padding: 30px 0;
        text-align: center;


        .video-container {
            max-width: 900px;
            margin: 0 auto;

            h2 {
                font-size: 1.8rem;
                margin-bottom: 10px;
            }
        }

        .video-wrapper {
            position: relative;
            width: 100%;
            padding-bottom: 56.25%;
            height: 0;
            margin-top: 20px;

            iframe {
                position: absolute;
                top: 0;
                left: 0;
                width: 100%;
                height: 100%;
            }
        }
    }

    /* Video section adaptation*/
    @media (max-width: 768px) {
        .video-section {
            padding: 30px 0;

            .video-wrapper {
                padding-bottom: 56.25%;
            }
        }
    }

    /* Video section adaptation*/
    @media (min-width: 1400px) {
        .video-section .video-container {
            max-width: 1000px;
        }
    }

    /* Transformations section*/
    .transformation {
        background-color: #fff;
        padding: 40px 0;

        @media (max-width: $bp-medium) {
            padding: 15px 0;
        }

        .transformation-content {
            display: flex;
            flex-direction: column;
            align-items: center;
            text-align: center;
            gap: 20px;
        }

        .transformation-images {
            display: flex;
            align-items: center;
            gap: 20px;

            @media (max-width: $bp-medium) {
                flex-direction: column;
                gap: 0;
            }

            .transformation-image {
                width: 400px;
                height: auto;
                border-radius: 10px;
                border: 2px solid $main-color;
            }

            .arrow_desktop {
                font-size: 7rem;
                color: $main-color;

                @media (max-width: $bp-medium) {
                    display: none;
                }
            }

            .arrow_mobile {
                font-size: 7rem;
                color: $main-color;

                @media (min-width: $bp-medium) {
                    display: none;
                }
            }
        }

        .transformation-button {
            background-color: #4caf50;
            color: #fff;
            border: none;
            padding: 10px 20px;
            font-size: 1rem;
            border-radius: 5px;
            cursor: pointer;
            transition: background-color 0.3s ease;
        }
    }

    /* Premium section*/
    .premium {
        background-color: #f9f9f9;
        padding: 60px 0;

        @media (min-width: $bp-medium) {
            padding: 15px 0;
        }

        text-align: center;
    }

    /* Footer*/
    .main-footer {
        background-color: #333;
        color: #fff;
        text-align: center;
        padding: 20px 0;

        .footer-links {
            display: flex;
            justify-content: center;
            gap: 20px;

            @media (max-width: $bp-medium) {
                flex-direction: column;
            }
        }

        .privacy-link {
            color: #fff;
            text-decoration: none;
            font-size: 1.2rem;
            transition: color 0.3s ease;

            @media (max-width: $bp-medium) {
                font-size: 1rem;
            }

            &:hover {
                color: #aaa;
            }
        }

        &_info-for-payment {
            display: flex;
            justify-content: space-between;
            padding-top: 30px;

            @media (max-width: $bp-medium) {
                flex-direction: column;
            }

            .contact-info {
                font-size: 0.85rem;

                p {
                    margin: 5px 0;
                }
            }

            .payment-logos {
                display: flex;
                justify-content: center;
                gap: 20px;
                margin: 10px 0;

                img {
                    height: 100px;
                }
            }
        }
    }
}
</style>

<script>
import { mapState } from "vuex";
import { actionTypes } from "@/store/modules/auth.js";
import CaloriesButton from "../Components/CaloriesButton.vue";

export default {
    name: "LandingPage",
    components: { CaloriesButton },
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
            if (!this.currentUser) {
                this.$router.push({ name: "cabinet" });
                return;
            }
            if (!this.currentUser?.email_verified_at) {
                return;
            }
            if (this.telegramLink) {
                window.open(this.telegramLink, "_blank");
            } else {
                console.error("Telegram link is not available");
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
                    <img src="@/assets/miaaa2.jpg" alt="Главное фото" class="hero-image" />
                </div>
                <div class="hero-action">
                    <h2>{{ $t("Home.Title") }}</h2>
                    <p>{{ $t("Home.Description") }}</p>
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
                    <h3>{{ $t("Home.TelegramBotTitle") }}</h3>
                    <p>{{ $t("Home.TelegramBotDesc") }}</p>
                    <calories-button @click="openTelegramLink" class="mid-info_button">
                        {{ telegramLinkText }}
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
                    <calories-button @click="goToCalculator" class="calculator-section_head-button" passed-class="extra-style1">
                        {{ $t("Home.LoseWeight") }}
                    </calories-button>
                </div>
            </div>
        </section>

        <section class="premium">
            <div class="container premium-content">
                <h2 class="premium_text">{{ $t("Home.PremiumTitle") }}</h2>
                <p>{{ $t("Home.PremiumDesc") }}</p>
                <div class="top-info_premium">
                    <div v-if="!isPremium">
                <span class="top-info_premium_text" @click="buyPremium">
                    {{ $t("Home.BuyPremium") }}
                </span>
                        <label>
                            <input type="checkbox" v-model="termsAgreed" />
                            {{ $t('Home.AgreeToTermsPart1') }}
                            <router-link :to="{ name: 'termsOfService' }">
                                {{ $t('Home.TermsOfService2') }}
                            </router-link>
                            {{ $t('Home.AgreeToTermsPart2') }}
                        </label>
                    </div>
                    <div v-else>
                <span class="top-info_premium_text_bought">
                    {{ $t("Home.Premium") }}
                </span>
                    </div>
                </div>
            </div>
        </section>

        <footer class="main-footer" v-if="$route.name === 'home'">
            <div class="container footer-links">
                <router-link :to="{ name: 'privacyPolicy' }" class="privacy-link">{{ $t("Home.PrivacyPolicy") }}</router-link>
                <router-link :to="{ name: 'termsOfService' }" class="privacy-link">{{ $t("Home.TermsOfService") }}</router-link>
                <router-link :to="{ name: 'faq' }" class="privacy-link">{{ $t("Home.FAQ") }}</router-link>
            </div>
        </footer>
    </div>
</template>


<style scoped lang="scss">
.landing-page {
    font-family: 'Arial', sans-serif;
    color: #333;

    .container {
        width: 90%;
        max-width: 1300px;
        margin: 0 auto;
        padding: 20px;

        font-family: Arial, Helvetica, sans-serif;
        font-size: $default-font-size;
        line-height: 1.5;
        color: #666666;
    }

    /* Баннер */
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
                    //&:hover {
                    //    background-color:  $main-color;
                    //}
                }
            }
        }
    }

    /* Блок возможностей */
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

    .transformation {
        background-color: #f9f9f9;
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

    .premium {
        background-color: #fff;
        padding: 60px 0;

        @media (min-width: $bp-medium) {
            padding: 15px 0;
        }

        text-align: center;
        .premium-content {
            max-width: 800px;
            margin: 0 auto;
            h2 {
                font-size: 2.5rem;
                margin-bottom: 20px;

                @media (max-width: $bp-medium) {
                    font-size: 2rem;
                }
            }
            p {
                font-size: 1.2rem;
                margin-bottom: 30px;


            }
            .premium-button {
                background-color: #ffd700;
                color: #333;
                border: none;
                padding: 12px 24px;
                font-size: 1.1rem;
                border-radius: 5px;
                cursor: pointer;
                transition: background-color 0.3s ease;
                &:hover {
                    background-color: #e6c200;
                }
            }
            a {
                color: #4CAF50;
                text-decoration: underline;
                transition: color 0.3s ease;

                &:hover {
                    color: #367C39;
                }
            }
        }
    }

    .main-footer {
        background-color: #333;
        color: #fff;
        text-align: center;
        padding: 20px 0;

        .footer-links {
            display: flex;
            justify-content: center;
            gap: 20px;
        }
        .privacy-link {
            color: #fff;
            text-decoration: none;
            font-size: 0.9rem;
            transition: color 0.3s ease;
            &:hover {
                color: #aaa;
            }
        }
    }
    //temporary
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
    //premium
    .top-info {
        display: flex;
        flex-direction: column;
        align-items: center;

        gap: 20px;
        padding: 40px 25px;

        @media (max-width: 768px) {
            padding: 0;
            justify-content: center;
        }

        &_premium {
            width: 100%;
            text-align: center;
            margin: 20px 0;
        }

        &_premium_text {
            color: #eeb82c;
            font-weight: 700;
            font-size: 24px;
            letter-spacing: 0.0625rem;
            text-transform: uppercase;
            cursor: pointer;
            display: block;

            background: linear-gradient(90deg, #eeb82c, $pink_color, #eeb82c);
            background-size: 200% 100%;
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            color: transparent;

            @keyframes shimmer {
                0% { background-position: 0% 50%; }
                100% { background-position: 200% 50%; }
            }

            @keyframes spaceboots {
                0%   { transform: translate(2px, 1px) rotate(0deg); }
                10%  { transform: translate(-1px, -2px) rotate(-1deg); }
                20%  { transform: translate(-3px, 0px) rotate(1deg); }
                30%  { transform: translate(0px, 2px) rotate(0deg); }
                40%  { transform: translate(1px, -1px) rotate(1deg); }
                50%  { transform: translate(-1px, 2px) rotate(-1deg); }
                60%  { transform: translate(-3px, 1px) rotate(0deg); }
                70%  { transform: translate(2px, 1px) rotate(-1deg); }
                80%  { transform: translate(-1px, -1px) rotate(1deg); }
                90%  { transform: translate(2px, 2px) rotate(0deg); }
                100% { transform: translate(1px, -2px) rotate(-1deg); }
            }

            @-webkit-keyframes spaceboots {
                0%   { -webkit-transform: translate(2px, 1px) rotate(0deg); }
                10%  { -webkit-transform: translate(-1px, -2px) rotate(-1deg); }
                20%  { -webkit-transform: translate(-3px, 0px) rotate(1deg); }
                30%  { -webkit-transform: translate(0px, 2px) rotate(0deg); }
                40%  { -webkit-transform: translate(1px, -1px) rotate(1deg); }
                50%  { -webkit-transform: translate(-1px, 2px) rotate(-1deg); }
                60%  { -webkit-transform: translate(-3px, 1px) rotate(0deg); }
                70%  { -webkit-transform: translate(2px, 1px) rotate(-1deg); }
                80%  { -webkit-transform: translate(-1px, -1px) rotate(1deg); }
                90%  { -webkit-transform: translate(2px, 2px) rotate(0deg); }
                100% { -webkit-transform: translate(1px, -2px) rotate(-1deg); }
            }

            -webkit-animation: shimmer 3s linear infinite, spaceboots 3s linear infinite;
            animation: shimmer 3s linear infinite, spaceboots 3s linear infinite;

            &:hover {
                -webkit-animation-duration: 1.5s, 1.5s;
                animation-duration: 1.5s, 1.5s;
            }
        }

        &_premium_text_bought {
            color: #eeb82c;
            font-weight: 700;
            font-size: 24px;
            letter-spacing: 0.0625rem;
            text-transform: uppercase;
            cursor: pointer;
            display: block;

            background: linear-gradient(90deg, #eeb82c, $pink_color, #eeb82c);
            background-size: 200% 100%;
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            color: transparent;

            @keyframes shimmer {
                0% { background-position: 0% 50%; }
                100% { background-position: 200% 50%; }
            }

            -webkit-animation: shimmer 3s linear infinite;
            animation: shimmer 3s linear infinite;

            &:hover {
                -webkit-animation-duration: 1.5s, 1.5s;
                animation-duration: 1.5s, 1.5s;
            }
        }

        &_avatar {
            width: 150px;
            height: 150px;
            border-radius: 50%;
            overflow: hidden;
            display: flex;
            justify-content: center;
            align-items: center;
            @media (max-width: 768px) {
                width: 100px;
                height: 100px;
            }

            img {
                width: 100%;
                height: 100%;
                object-fit: cover;
            }
        }

        &_name {
            flex: 1;
            color: black;
            font-size: 30px;
            letter-spacing: 0.0625rem;

            span {
                font-weight: 500;
            }
        }
    }
//temporary
    .premium_text {
        color: #eeb82c;
        font-weight: 700;
        font-size: 24px;
        letter-spacing: 0.0625rem;
        text-transform: uppercase;
        display: block;

        background: linear-gradient(90deg, #eeb82c, $pink_color, #eeb82c);
        background-size: 200% 100%;
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        background-clip: text;
        color: transparent;

        -webkit-animation: shimmer 3s linear infinite;
        animation: shimmer 3s linear infinite;

        &:hover {
            -webkit-animation-duration: 1.5s, 1.5s;
            animation-duration: 1.5s, 1.5s;
        }
    }
}
</style>

<script>
import { actionTypes } from "@/store/modules/auth.js";
import CaloriesButton from "../Components/CaloriesButton.vue";

export default {
    name: "PremiumSection",
    components: { CaloriesButton },
    props: {
        isPremium: Boolean,
        currentUser: Object,
        isHomePage: {
            type: Boolean,
            default: false
        }
    },
    data() {
        return {
            termsAgreed: false,
        };
    },
    methods: {
        buyPremium() {
            if (!this.termsAgreed) {
                this.$store.dispatch('setError', this.$t('Home.AgreeToTermsRequired'));
                return;
            }
            if (!this.currentUser) {
                this.$router.push({ name: "cabinet" });
                return;
            }
            this.$store.dispatch(actionTypes.buyPremium).then((premium_until) => {
                // window.location.href = url;
                this.currentUser.premium_until = premium_until;
            });
        },
        cancelPremium() {
            this.$store.dispatch('setSuccess', this.$t('Notification.Success.CanceledSub'));
        },
    },
};
</script>

<template>
    <div class="container premium-content">
        <div v-if="isHomePage" class="premium_home-page-content">
            <h2 class="premium_text">{{ $t("Home.PremiumTitle") }}</h2>
            <p v-if="!isPremium">{{ $t("Home.PremiumDesc") }}</p>
            <p v-else>{{ $t("Home.PremiumAlreadyHaveDesc") }}</p>
        </div>
        <div class="top-info_premium" :class="{ 'top-info_premium-cabinet': !isHomePage }">
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
                <span class="cancel-premium" @click="cancelPremium">
                    {{ $t("Home.CancelPremium") }}
                </span>
            </div>
        </div>
    </div>
</template>

<style scoped lang="scss">
.premium-content {
    max-width: 800px;
    margin: 0 auto;
}

.premium_text {
    font-size: 2.5rem;
    margin-bottom: 20px;
    font-weight: 700;
    letter-spacing: 0.0625rem;
    text-transform: uppercase;
    background: linear-gradient(90deg, #eeb82c, #ffc0cb, #eeb82c);
    background-size: 200% 100%;
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    animation: shimmer 3s linear infinite;
}

.top-info_premium-cabinet {
    width: 190px;
    text-align: center;
}

.top-info_premium {
    margin-top: 20px;

    .top-info_premium_text {
        color: #eeb82c;
        font-weight: 700;
        font-size: 24px;
        letter-spacing: 0.0625rem;
        text-transform: uppercase;
        cursor: pointer;
        display: block;
        padding-bottom: 10px;
        background: linear-gradient(90deg, #eeb82c, #ffc0cb, #eeb82c);
        background-size: 200% 100%;
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        -webkit-animation: shimmer 3s linear infinite, spaceboots 3s linear infinite;
        animation: shimmer 3s linear infinite, spaceboots 3s linear infinite;

        &:hover {
            -webkit-animation-duration: 1.5s, 1.5s;
            animation-duration: 1.5s, 1.5s;
        }
    }

    .top-info_premium_text_bought {
        color: #eeb82c;
        font-weight: 700;
        font-size: 24px;
        letter-spacing: 0.0625rem;
        text-transform: uppercase;
        display: block;
        background: linear-gradient(90deg, #eeb82c, #ffc0cb, #eeb82c);
        background-size: 200% 100%;
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        -webkit-animation: shimmer 3s linear infinite;
        animation: shimmer 3s linear infinite;
    }

    .cancel-premium {
        color: #ff4d4d;
        cursor: pointer;
        font-size: 1rem;
        font-weight: 700;
        margin-top: 10px;
        transition: color 0.3s ease;
        display: inline-block;
    }
    .cancel-premium:hover {
        text-decoration: underline;
        color: #cc0000;
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
</style>

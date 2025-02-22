<template>
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
</template>

<script>
import { mapState } from "vuex";
import { actionTypes } from "@/store/modules/auth.js";

export default {
    name: "PremiumSection",
    data() {
        return {
            termsAgreed: false,
        };
    },
    computed: {
        ...mapState({
            currentUser: state => state.auth.currentUser,
        }),
        isPremium() {
            if (!this.currentUser?.premium_until) {
                return false;
            }
            const premiumUntil = new Date(this.currentUser.premium_until);
            return premiumUntil > new Date();
        }
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
            this.$store.dispatch(actionTypes.buyPremium).then((url) => {
                window.location.href = url;
            });
        }
    }
};
</script>

<style scoped lang="scss">
.premium {
    background-color: #fff;
    padding: 60px 0;
    text-align: center;

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
            background: linear-gradient(90deg, #eeb82c, #ffc0cb, #eeb82c);
            background-size: 200% 100%;
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            animation: shimmer 3s linear infinite;
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
            animation: shimmer 3s linear infinite;
        }
    }
}

@keyframes shimmer {
    0% { background-position: 0% 50%; }
    100% { background-position: 200% 50%; }
}
</style>

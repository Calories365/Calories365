<script>
import { mapState } from "vuex";
import { actionTypes } from "@/store/modules/auth.js";
import { useAcademic } from '@/composables/useAcademic';
import CaloriesButton from "@/Components/CaloriesButton.vue";
import router from "@/router/router.js";
import PremiumSection from "../Components/PremiumSection.vue";

export default {
    name: "Cabinet",
    components: { PremiumSection, CaloriesButton },
    data() {
        return {
            name: null,
            email: null,
            caloriesLimit: null,
            telegramAuth: null,
            telegramLink: null,
        };
    },
    computed: {
        ...mapState({
            currentUser: (state) => state.auth.currentUser,
        }),
        telegramLinkText() {
            if (!this.currentUser?.email_verified_at) {
                return this.$t("Home.ConfirmEmail");
            }
            return this.telegramAuth
                ? this.$t("Home.Connected")
                : this.$t("Home.Connect");
        },
        isPremium() {
            if (!this.currentUser?.premium_until) {
                return false;
            }
            const premiumUntil = new Date(this.currentUser.premium_until);
            return premiumUntil > new Date();
        },
    },
    methods: {
        play() {
            // Логика для аватара, если нужна
        },
        changeName() {
            this.$store
                .dispatch(actionTypes.updateUsersData, {
                    name: this.name,
                    email: this.email,
                    calories_limit: this.caloriesLimit,
                })
                .then(() => {
                    // router.push({ name: "login" });
                });
        },
        logout() {
            this.$store.dispatch(actionTypes.logout).then(() => {
                router.push({ name: "login" });
            });
        },
        openTelegramLink() {
            if (!this.currentUser?.email_verified_at) {
                return;
            }
            if (this.telegramLink) {
                window.open(this.telegramLink, "_blank");
            } else {
                console.error("Telegram link is not available");
            }
        },
    },
    mounted() {
        const paymentStatus = this.$route.query.payment;

        if (paymentStatus === "success") {
            this.$store.dispatch("setSuccess", this.$t("Cabinet.PaymentSuccess"));
        } else if (paymentStatus === "error") {
            const errorMessage = this.$t("Cabinet.PaymentError");
            this.$store.dispatch("setError", errorMessage);
        }

        this.name = this.currentUser.name;
        this.email = this.currentUser.email;
        this.caloriesLimit = this.currentUser.calories_limit;
        this.telegramAuth = this.currentUser.telegram_auth;

        this.$store
            .dispatch(actionTypes.getTelegramLink)
            .then((link) => {
                this.telegramLink = link;
            })
            .catch((error) => {
                console.error("Error fetching telegram link:", error);
            });
    },
};
</script>

<template>
    <section class="page-cabinet_section">
        <div class="page-cabinet_container">
            <div class="page-cabinet_header">
                <h1 class="page-cabinet_title">{{ $t("Cabinet.PersonalCabinet") }}</h1>
            </div>
            <div class="page-cabinet_content">
                <div class="page-cabinet_top top-info">
                    <div class="top-info_avatar" @click="play">
                        <img src="@/assets/valera.png" />
                    </div>
                    <div class="top-info_premium" v-if="!$isAcademic">
                        <PremiumSection :isPremium="isPremium" :currentUser="currentUser" />
                    </div>
                </div>
                <div class="page-cabinet_mid mid-info">
                    <div class="mid-info_email">
                        <div class="mid-info_text">{{ $t("Cabinet.Mail") }}:</div>
                        <input v-model="email" disabled class="mid-info_credentials" />
                    </div>
                    <div class="mid-info_login">
                        <div class="mid-info_text">{{ $t("Cabinet.Name") }}:</div>
                        <input v-model="name" class="mid-info_credentials" />
                    </div>
                    <div class="mid-info_link">
                        <div class="mid-info_text"><a href="#" @click.prevent="openTelegramLink" class="telegram-bot-link">{{ $t("Cabinet.TelegramBot") }}</a>:</div>
                        <span
                            class="mid-info_credentials link"
                            @click="openTelegramLink"
                            style="cursor: pointer;"
                        >
                            {{ telegramLinkText }}
                        </span>
                    </div>
                    <div class="mid-info_login">
                        <div class="mid-info_text">{{ $t("Cabinet.CaloriesLimit") }}:</div>
                        <input v-model="caloriesLimit" class="mid-info_credentials" />
                    </div>
                    <div class="mid-info_link">
                        <div class="mid-info_text">{{ $t("Cabinet.Password") }}:</div>
                        <router-link :to="{ name: 'change-password' }">
                            <span class="mid-info_credentials link">
                                {{ $t("Cabinet.ChangingPassword") }}
                            </span>
                        </router-link>
                    </div>
                    <div class="mid-info_buttons new-password">
                        <calories-button @click="changeName" class="mid-info_button">
                            {{ $t("Cabinet.Save") }}
                        </calories-button>
                        <calories-button
                            @click="logout"
                            passed-class="danger"
                            class="mid-info_button"
                        >
                            {{ $t("Cabinet.logOut") }}
                        </calories-button>
                    </div>
                </div>
            </div>
        </div>
    </section>
</template>


<style scoped lang="scss">

.telegram-bot-link {
    color: inherit;
    text-decoration: none;
    transition: all 0.3s ease;

    &:hover {
        color: $main-color;
        text-decoration: underline;
    }
}

.page-cabinet {
    width: 100%;

    &_section {
        background-color: white;
        height: 100%;
        width: 100%;
    }

    &_container {
        max-width: 1400px;
        padding: 0 10px;
        margin: 0 auto;
        height: 100%;
        width: 100%;
    }

    &_header {
    }


    &_title {
        font-size: 30px;
        text-align: center;
        letter-spacing: 0.0625rem;
        font-weight: 700;
        color: black;
        padding: 30px 0;
    }

    &_content {
        position: relative;
        display: flex;
        width: 100%;
        background: #F2F2F2;
        border-radius: 5px;
        padding: 50px 10px;
        gap: 50px;
        @media (max-width: 768px) {
            flex-direction: column;
            gap: 10px;
        }
        @media (max-width: 375px) {
            gap: 10px;
        }
    }

    &_top {
    }

    &_mid {
    }

    &_bottom {

    }

    &_btn {
        display: flex;
        justify-content: flex-end;
        margin: 50px 0;

        .main-header_signout {
            background-color: red;
            color: white;
            padding: 40px 25px;

            &:hover {
                background-color: #73AE37FF;
            }
        }

        .main-header_save {
            background-color: $main-color;
            color: white;
            padding: 10px 25px;
            margin-right: 10px;

            &:hover {
                background-color: #62932e;
            }
        }
    }
}

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

.mid-info {
    width: 80%;
    font-size: 18px;
    line-height: 22px;
    letter-spacing: 0.0625rem;
    display: flex;
    flex-direction: column;
    gap: 10px;

    @media (max-width: 768px) {
        font-size: 14px;
        width: 100%;
    }
    @media (max-width: 375px) {
        font-size: 14px;
        width: 100%;
    }



    &_login, &_email {
        background-color: white;
        width: 80%;
        display: flex;
        border-radius: 5px;
        padding: 20px 10px 20px 10px;
        flex-wrap: wrap;
        overflow-y: hidden;
        //overflow-x: scroll;
        justify-content: space-between;

        @media (max-width: 768px) {
            width: 100%;
        }
    }

    &_link {
        background-color: white;
        width: 80%;
        display: flex;
        border-radius: 5px;
        align-items: center;
        padding: 20px 10px 20px 10px;
        flex-wrap: wrap;
        overflow-y: hidden;
        //overflow-x: scroll;
        @media (max-width: 768px) {
            width: 100%;
        }
    }

    &_buttons {
        width: 80%;
        display: flex;
        border-radius: 5px;
        flex-wrap: wrap;
        gap: 50px;
        justify-content: center;
        margin-top: 30px;
        @media (max-width: 768px) {
            width: 100%;
            gap: 20px;
        }

    }

    &_text {
        flex: 0 0 15%;
        color: #eeb82c;
        font-weight: 700;

        @media (max-width: 768px) {
            flex: 0 0 30%;
        }

    }

    &_credentials {
        flex: 1;
    }

    &_button {
        width: 33%;
        @media (max-width: 768px) {
            width: 46%;
        }
    }
}

.link {
    cursor: pointer;
    text-decoration: underline;

    &:hover {
        color: $pink_color;
    }
}

</style>

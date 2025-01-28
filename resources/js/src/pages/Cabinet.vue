<script>
import { mapState } from "vuex";
import CaloriesButton from "@/Components/CaloriesButton.vue";
import { actionTypes } from "@/store/modules/auth.js";
import router from "@/router/router.js";

export default {
    name: "Cabinet",
    components: { CaloriesButton },
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
        },
    },
    methods: {
        play() {
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

        buyPremium() {
            this.$store.dispatch(actionTypes.buyPremium).then((response) => {
            });
        }
    },
    async mounted() {
        this.name = this.currentUser.name;
        this.email = this.currentUser.email;
        this.caloriesLimit = this.currentUser.calories_limit;
        this.telegramAuth = this.currentUser.telegram_auth;

        try {
            this.telegramLink = await this.$store.dispatch(actionTypes.getTelegramLink);
        } catch (error) {
            console.error("Error fetching telegram link:", error);
        }
    },
};
</script>

<template>
    <section class="page-cabinet_section">
        <div class="page-cabinet_container">
            <div class="page-cabinet_header">
                <h1 class="page-cabinet_title">
                    {{ $t("Cabinet.PersonalCabinet") }}
                </h1>
            </div>
            <div class="page-cabinet_content">
                <div class="page-cabinet_top top-info">
                    <div class="top-info_avatar" @click="play">
                        <img src="@/assets/valera.png" />
                    </div>

                    <div class="top-info_premium">
                        <span
                            v-if="!isPremium"
                            class="top-info_premium_text"
                            @click="buyPremium"
                        >
                            {{ $t("Cabinet.BuyPremium") }}
                        </span>
                        <span
                            v-else
                            class="top-info_premium_text_bought"
                        >
                            {{ $t("Cabinet.Premium") }}
                        </span>
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
                        <div class="mid-info_text">{{ $t("Cabinet.TelegramBot") }}:</div>
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
                        <calories-button
                            @click="changeName"
                            class="mid-info_button"
                        >
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
        overflow-x: scroll;
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
        overflow-x: scroll;
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

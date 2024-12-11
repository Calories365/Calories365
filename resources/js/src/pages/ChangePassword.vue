<script>
import {mapState} from "vuex";
import CaloriesButton from "@/Components/CaloriesButton.vue";
import {actionTypes} from "@/store/modules/auth.js";
import router from "@/router/router.js";
import dedSound from "@/assets/ded.mp3";

export default {
    name: "Cabinet",
    components: {CaloriesButton},
    data() {
        return {
            currentPassword: null,
            password: null,
            passwordConfirmation: null,
        }
    },
    methods: {
        play() {
            const audio = new Audio(dedSound);
            audio.play().catch(e => console.error('Playback failed:', e));
        },
        goBack() {
            router.push({name: 'cabinet'});
        },
        changePassword() {
            console.log('start')
            this.$store.dispatch(actionTypes.updateUsersPassword,
                {
                    current_password: this.currentPassword,
                    password: this.password,
                    password_confirmation: this.passwordConfirmation
                }).then(() => {
                router.push({name: 'login'});
            })
        },
    },
    computed: {
        ...mapState({
            currentUser: state => state.auth.currentUser,
        }),
    },
}
</script>
<template>
    <section class="page-cabinet_section">
        <div class="page-cabinet_container">
            <div class="page-cabinet_header">
                <div class="page-cabinet_arrow-back" @click="goBack">
<!--                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 384 512">-->
<!--                        <path-->
<!--                            d="M342.6 150.6c12.5-12.5 12.5-32.8 0-45.3s-32.8-12.5-45.3 0L192 210.7 86.6 105.4c-12.5-12.5-32.8-12.5-45.3 0s-12.5 32.8 0 45.3L146.7 256 41.4 361.4c-12.5 12.5-12.5 32.8 0 45.3s32.8 12.5 45.3 0L192 301.3 297.4 406.6c12.5 12.5 32.8 12.5 45.3 0s12.5-32.8 0-45.3L237.3 256 342.6 150.6z"-->
<!--                            fill="darkgrey"/>-->
<!--                    </svg>-->
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 448 512">
                        <path
                            d="M9.4 233.4c-12.5 12.5-12.5 32.8 0 45.3l160 160c12.5 12.5 32.8 12.5 45.3 0s12.5-32.8 0-45.3L109.2 288 416 288c17.7 0 32-14.3 32-32s-14.3-32-32-32l-306.7 0L214.6 118.6c12.5-12.5 12.5-32.8 0-45.3s-32.8-12.5-45.3 0l-160 160z"
                            fill="darkgrey"/>
                    </svg>
                </div>
                <h1 class="page-cabinet_title">
                    {{ $t("Cabinet.ChangingPassword") }}
                </h1>

            </div>
            <div class="page-cabinet_content">
                <div class="page-cabinet_top top-info">
                    <div class="top-info_avatar"
                         @click="play">
                        <img src="@/assets/valera.png">
                    </div>
                </div>
                <div class="page-cabinet_mid mid-info">
                    <div
                        class="mid-info_email">
                        <div
                            class="mid-info_text">{{ $t("Cabinet.CurrentPassword") }}:
                        </div>
                        <input
                            type="password"
                            v-model="currentPassword"
                            class="mid-info_credentials">
                    </div>
                    <div class="mid-info_login">
                        <div class="mid-info_text">{{ $t("Cabinet.NewPassword") }}:</div>
                        <input
                            type="password"
                            v-model="password"
                            class="mid-info_credentials">
                    </div>
                    <div class="mid-info_login">
                        <div class="mid-info_text">{{ $t("Cabinet.RepeatPassword") }}:</div>
                        <input
                            type="password"
                            v-model="passwordConfirmation"
                            class="mid-info_credentials">
                    </div>
                    <div class="mid-info_buttons new-password">
                        <calories-button
                            @click="changePassword"
                            class="mid-info_button"> {{
                                $t("Cabinet.Update")
                            }}
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
        //border: 1px solid black;
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

    &_arrow-back {
        //padding: 5px 0 0 25px;
        position: absolute;
        z-index: 99999;
        cursor: pointer;
        width: 30px;
        height: 30px;
        top: 23%;
        left: 5%;
        //@media (max-width: 768px) {
        //    height: 70vh;
        //}
        @media (max-width: 375px) {
            top: 30%;
        }
    }

    &_content {
        position: relative;
        display: flex;
        //justify-content: space-between;
        width: 100%;
        //margin: 30px 0 30px 0;
        background: #F2F2F2;
        border-radius: 5px;

        @media (max-width: 768px) {
            height: 70vh;
        }
        @media (max-width: 375px) {
            height: 65vh;
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
    align-items: center;
    gap: 100px;
    padding: 40px 25px;
    border-bottom: 1px solid #dedede;

    @media (max-width: 768px) {
        padding: 0;
    }

    &_avatar {
        width: 150px;
        height: 150px;
        border-radius: 50%;
        overflow: hidden;
        display: flex;
        justify-content: center;
        align-items: center;
        margin-bottom: 80px;
        @media (max-width: 768px) {
            position: absolute;
            width: 100px;
            height: 100px;
            top: 10%;
            left: 40%;
        }
        @media (max-width: 380px) {
            position: absolute;
            width: 70px;
            height: 70px;
            top: 5%;
            left: 40%;
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
    padding: 100px 25px;
    font-size: 18px;
    line-height: 22px;
    letter-spacing: 0.0625rem;
    display: flex;
    flex-direction: column;
    gap: 10px;

    @media (max-width: 768px) {
        padding: 200px 25px;
        font-size: 14px;
        //height: 800px;
        width: 100%;
    }
    @media (max-width: 375px) {
        padding: 100px 25px;
        font-size: 14px;
        //height: 550px;
        width: 100%;
    }

    &_login, &_email {
        background-color: white;
        //border: 1px solid black;
        width: 80%;
        display: flex;
        //gap: 25px;
        border-radius: 5px;
        //padding: 10px 10px 55px 10px;
        padding: 20px 10px 20px 10px;
        flex-wrap: wrap;
        overflow-y: hidden;
        overflow-x: scroll;
        justify-content: space-between;

        @media (max-width: 768px) {
            //flex-direction: column;
            padding: 10px 10px 55px 10px;
            width: 100%;
        }
    }

    &_link {
        background-color: white;
        width: 80%;
        display: flex;
        //gap: 25px;
        border-radius: 5px;
        padding: 10px 10px 10px 10px;
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
        flex: 0 0 30%;
        color: #eeb82c;
        font-weight: 700;

        @media (max-width: 768px) {
            flex: 0 0 35%;
        }

    }

    &_credentials {
        flex: 1;
        border: 1px solid #eeb82c;
    }

    &_link {
        //width: 100%;
    }

    &_button {
        width: 33%;
        @media (max-width: 768px) {
            width: 47%;
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

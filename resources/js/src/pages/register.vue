<script>
import {actionTypes} from "@/store/modules/auth";
import {mapState} from "vuex";
import CaloriesErrors from "@/Components/CaloriesErrors.vue";
import CaloriesLoader from "@/Components/CaloriesLoader.vue";
import CaloriesButton from "@/Components/CaloriesButton.vue";


export default {
    name: "register",
    components: {CaloriesButton, CaloriesLoader, CaloriesErrors},
    data() {
        return {
            email: '',
            password: '',
            password_confirmation: '',
            name: '',
        }
    },
    computed: {
        ...mapState({
            isSybmiting: state => state.auth.isSybmiting,
            validationErrors: state => state.auth.validationErrors
        }),
    },
    beforeRouteLeave() {
        this.$store.dispatch(actionTypes.destroyErrors);
    },
    methods: {
        onSubmit() {
            this.$store.dispatch(actionTypes.register,
                {
                    email: this.email,
                    name: this.name,
                    password: this.password,
                    password_confirmation: this.password_confirmation,
                    remember: true,
                }
            ).then(user => {

                this.$router.push({name: 'home'})
                this.email = '';
                this.password = '';
                this.password_confirmation = '';
                this.name = '';
            })
        },
        loginWithGoogle (){
            window.location.href = '/api/auth/google';
        },
    }
}
</script>

<template>

    <calories-loader
        v-if="isSybmiting"/>

    <div class="flex min-h-full flex-1 flex-col justify-center px-6 py-12 lg:px-8"
         :class="{ 'hide': isSybmiting }">
        <div class="sm:mx-auto sm:w-full sm:max-w-sm">

            <!--            <img src="@/assets/calories2.jpeg" alt="">-->

            <h2 class="mt-10 text-center text-2xl font-bold leading-9 tracking-tight text-gray-900">
                {{ $t('message.registerYourAccount') }}</h2>
        </div>

        <div class="mt-10 sm:mx-auto sm:w-full sm:max-w-sm">
            <form class="space-y-6" @submit.prevent="onSubmit">

                <calories-errors :validation-errors=validationErrors v-if="validationErrors"></calories-errors>

                <div>
                    <label for="name" class="block text-sm font-medium leading-6 text-gray-900">{{
                            $t('message.name')
                        }}</label>
                    <div class="mt-2">
                        <input
                            class="name-input"
                            id="name" name="name" type="text" required="" v-model="name"/>
                    </div>
                </div>

                <div>
                    <label for="email" class="block text-sm font-medium leading-6 text-gray-900">{{
                            $t('message.emailAddress')
                        }}</label>
                    <div class="mt-2">
                        <!--                        <input id="email" name="email" type="email" autocomplete="email" required="" v-model="email"-->
                        <!--                               class="calories_input block w-full rounded-md border-0 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-indigo-600 sm:text-sm sm:leading-6"/>-->

                        <input
                            class="email-input"
                            id="email" name="email" type="email" autocomplete="email" required="" v-model="email"/>

                    </div>
                </div>

                <div>
                    <div class="flex items-center justify-between">
                        <label for="password" class="block text-sm font-medium leading-6 text-gray-900">{{
                                $t('message.password')
                            }}</label>
                    </div>
                    <div class="mt-2">
                        <input
                            class="password-input"
                            id="password" name="password" type="password"  required=""
                               v-model="password"/>
                    </div>
                </div>
                <div>
                    <div class="flex items-center justify-between">
                        <label for="confirm-password" class="block text-sm font-medium leading-6 text-gray-900">{{
                                $t('message.confirmPassword')
                            }}
                        </label>

                    </div>
                    <div class="mt-2">
                        <input
                            class="password-input"
                            id="confirm-password" name="confirm-password" type="password" autocomplete="current-password" required=""
                               v-model="password_confirmation"/>
                    </div>
                </div>

                <div>
                    <calories-button
                        type="submit"
                        :disabled="isSybmiting"
                        :class="{'opacity-50 cursor-not-allowed': isSybmiting}"
                        passed-class="recovery"
                        class="button">
                        {{ $t('message.signUp') }}
                    </calories-button>
                    <div class="google-login">
                        <div class="mt-2">
                        <span class="social-login">
                             {{ $t('message.orSignInWith') }}
                        </span>
                        </div>
                        <div class="mt-2 flex">
<!--                            <calories-button-->
<!--                                class="button w-1/2 mr-2"-->
<!--                                @click="loginWithGoogle"-->
<!--                            >-->
<!--                                Google-->
<!--                            </calories-button>-->
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>
</template>

<style scoped lang="scss">
.hide {
    opacity: 0.33;
}

.email-input, .password-input, .name-input {
    width: 100%;
    padding: 5px;
    color: #1f2937;
    background-color: #fff;
    box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
    border-radius: 0.375rem;
    font-size: 0.875rem;
    line-height: 1.25rem;
    border: 1px solid #d1d5db;
    &:focus {
        outline: 2px solid transparent;
        outline-offset: 2px;
        border-radius: 0.375rem;
        box-shadow: 0 0 0 2px $main-color;
    }

    box-shadow: inset 0 0 0 1px #d1d5db;

    // Responsive variants
    @media (min-width: 640px) {
        font-size: 0.875rem;
        line-height: 1.5rem;
    }
}


.button {
    width: 100%;
}
.social-login {
    font-family: Arial, Helvetica, sans-serif;
    font-size: $default-font-size;
    line-height: 1.5;
    color: #666666;
}
</style>

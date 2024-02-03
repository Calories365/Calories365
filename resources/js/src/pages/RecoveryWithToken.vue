<script>

import {mapState} from "vuex";
import {actionTypes} from "@/store/modules/auth.js";
import CaloriesErrors from "@/Components/CaloriesErrors.vue";
import CaloriesButton from "@/Components/CaloriesButton.vue";
import CaloriesLoader from "@/Components/CaloriesLoader.vue";

export default {
    name: "RecoveryWithToken",
    components: {CaloriesLoader, CaloriesButton, CaloriesErrors},
    data() {
        return {
            password: '',
            response: '',
            token: '',
            email: '',
            password_confirmation: '',
        }
    }, mounted() {
        this.token = this.$route.params.token;

        this.email = decodeURIComponent(this.$route.query.email);
    },
    beforeRouteLeave() {
        this.$store.dispatch(actionTypes.destroyErrors);
    },
    computed: {
        ...mapState({
            isSybmiting: state => state.auth.isSybmiting,
            validationErrors: state => state.auth.validationErrors
        }),
    },
    methods: {
        onSubmit() {
            this.$store.dispatch(actionTypes.recoveryWithToken,
                {
                    email: this.email,
                    password: this.password,
                    password_confirmation: this.password_confirmation,
                    token: this.token,
                }
            ).then(response => {
                this.$router.push({name: 'login'})
            })
        },
    }
}
</script>

<template>
    <calories-loader
        v-if="isSybmiting"/>
    <div
        :class="{ 'hide': isSybmiting }"
        class="flex min-h-full flex-1 flex-col justify-center px-6 py-12 lg:px-8">
        <div class="sm:mx-auto sm:w-full sm:max-w-sm">

            <h2 class="mt-10 text-center text-2xl font-bold leading-9 tracking-tight text-gray-900">
                {{ $t(`Recovery.RecoveryYourPassword`) }}
            </h2>
        </div>

        <div class="mt-10 sm:mx-auto sm:w-full sm:max-w-sm">
            <form class="space-y-6" @submit.prevent="onSubmit">

                <calories-errors :validation-errors=validationErrors v-if="validationErrors"></calories-errors>

                <div>
                    <div class="flex items-center justify-between">
                        <label for="password" class="block text-sm font-medium leading-6 text-gray-900">
                            {{ $t(`Recovery.Password`) }}
                        </label>
                    </div>
                    <div class="mt-2">
<!--                        <input id="password" name="password" type="password" autocomplete="current-password" required=""-->
<!--                               v-model="password"-->
<!--                               class="calories_input block w-full rounded-md border-0 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-indigo-600 sm:text-sm sm:leading-6"/>-->
                        <input
                            class="password-input"
                            id="password" name="password" type="password" autocomplete="current-password" required=""
                            v-model="password"/>
                    </div>
                </div>
                <div>
                    <div class="flex items-center justify-between">
                        <label for="password" class="block text-sm font-medium leading-6 text-gray-900">
                            {{ $t(`Recovery.ConfirmPassword`) }}
                        </label>

                    </div>
                    <div class="mt-2">
<!--                        <input id="password" name="password" type="password" autocomplete="current-password" required=""-->
<!--                               v-model="password_confirmation"-->
<!--                               class="calories_input block w-full rounded-md border-0 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-indigo-600 sm:text-sm sm:leading-6"/>-->
                        <input
                            class="password-input"
                            id="password" name="password" type="password" autocomplete="current-password" required=""
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
                        {{ $t(`Recovery.RecoverPassword`) }}
                    </calories-button>
                </div>
            </form>

        </div>
    </div>
</template>

<style scoped lang="scss">
.button {
    width: 100%;
}
.email-input, .password-input {
    width: 100%;
    //border: 0;
    padding: 5px;
    color: #1f2937; // text-gray-900 в Tailwind
    background-color: #fff; // BG is white by default, can be set explicitly if needed
    box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.05); // shadow-sm в Tailwind
    border-radius: 0.375rem; // rounded-md в Tailwind
    font-size: 0.875rem; // text-sm в Tailwind
    line-height: 1.25rem; // leading-6 в Tailwind
    border: 1px solid #d1d5db;
    &:focus {
        outline: 2px solid transparent;
        outline-offset: 2px;
        //border-radius: inherit;
        border-radius: 0.375rem; // rounded-md в Tailwind
        box-shadow: 0 0 0 2px $main-color; // focus:ring-2 focus:ring-indigo-600 в Tailwind
    }
    box-shadow: inset 0 0 0 1px #d1d5db; // ring-1 ring-gray-300 в Tailwind

    // Responsive variants
    @media (min-width: 640px) {
        font-size: 0.875rem; // sm:text-sm в Tailwind
        line-height: 1.5rem; // sm:leading-6 в Tailwind
    }
}

.calories_input {
    padding: 7px;
}

.hide {
    opacity: 0.33;
}

</style>

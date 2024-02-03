<script>

import {mapState} from "vuex";
import {actionTypes} from "@/store/modules/auth.js";
import CaloriesErrors from "@/Components/CaloriesErrors.vue";
import CaloriesButton from "@/Components/CaloriesButton.vue";
import CaloriesLoader from "@/Components/CaloriesLoader.vue";

export default {
    name: "Recovery",
    components: {CaloriesLoader, CaloriesButton, CaloriesErrors},
    data() {
        return {
            email: '',
            password: '',
            response: '',
        }
    }, computed: {
        ...mapState({
            isSybmiting: state => state.auth.isSybmiting,
            validationErrors: state => state.auth.validationErrors
        }),
    }, beforeRouteLeave() {
        this.$store.dispatch(actionTypes.destroyErrors);
    },
    methods: {
        onSubmit() {
            this.$store.dispatch(actionTypes.recovery,
                {
                    email: this.email,
                }
            ).then(response => {
                this.response = response.message;
                this.email = '';
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

                <div v-if="response" class="max-w-lg mx-auto">
                    <div class="p-4 mb-4 bg-green-100 border border-green-400 text-green-800 rounded-md shadow-sm">
                        <div class="flex">
                            <div class="flex-shrink-0">
                                <svg class="h-5 w-5 text-green-400" xmlns="http://www.w3.org/2000/svg" fill="none"
                                     viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                          d="M5 13l4 4L19 7"/>
                                </svg>
                            </div>
                            <div class="ml-3">
                                <p class="text-sm leading-5 font-medium text-green-800">
                                    {{ response }}
                                </p>
                            </div>
                        </div>
                    </div>
                </div>

                <div>
                    <label for="email" class="block text-sm font-medium leading-6 text-gray-900">
                        {{ $t(`Recovery.EmailAddress`) }}
                    </label>
                    <div class="mt-2">
<!--                        <input id="email" name="email" type="email" autocomplete="email" required="" v-model="email"-->
<!--                               class="calories_input block w-full rounded-md border-0 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-indigo-600 sm:text-sm sm:leading-6"/>-->
                        <input id="email" name="email" type="email" autocomplete="email" required v-model="email"
                               class="email-input"/>
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

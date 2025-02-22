<script setup>
import {computed, onBeforeUnmount, reactive} from 'vue';
import {useStore} from 'vuex';
import {useRouter} from 'vue-router';
import {actionTypes} from "@/store/modules/auth.js";
import CaloriesButton from "@/Components/CaloriesButton.vue";
import CaloriesLoader from "@/Components/CaloriesLoader.vue";
import CaloriesErrors from "@/Components/CaloriesErrors.vue";

const store = useStore();
const router = useRouter();

const formState = reactive({
    email: '',
    password: '',
});

const isSubmitting = computed(() => store.state.auth.isSybmiting);
const validationErrors = computed(() => store.state.auth.validationErrors);

onBeforeUnmount(() => {
    store.dispatch(actionTypes.destroyErrors);
});

const onSubmit = () => {
    store.dispatch(actionTypes.login, {
        email: formState.email,
        password: formState.password,
        remember: true,
    }).then(() => {
        router.push({name: 'home'});
        formState.email = '';
        formState.password = '';
    }).catch((e) => {
        console.error('Login failed:', e);
    });
};

const loginWithGoogle = () => {
        window.location.href = '/api/auth/google';
    };

</script>

<template>
    <calories-loader v-if="isSubmitting"/>

    <div class="flex min-h-full flex-1 flex-col justify-center px-6 py-12 lg:px-8" :class="{ 'hide': isSubmitting }">
        <div class="sm:mx-auto sm:w-full sm:max-w-sm">
            <h2 class="mt-10 text-center text-2xl font-bold leading-9 tracking-tight text-gray-900">
                {{ $t('message.signInToYourAccount') }}
            </h2>
        </div>

        <div class="mt-10 sm:mx-auto sm:w-full sm:max-w-sm">
            <form class="space-y-6" @submit.prevent="onSubmit">
                <calories-errors :validation-errors="validationErrors" v-if="validationErrors"></calories-errors>

                <div>
                    <label for="email" class="block text-sm font-medium leading-6 text-gray-900">
                        {{ $t('message.emailAddress') }}
                    </label>
                    <div class="mt-2">
                        <input id="email" name="email" type="email" autocomplete="email" required
                               v-model="formState.email"
                               class="email-input"/>
                    </div>
                </div>

                <div>
                    <div class="flex items-center justify-between">
                        <label for="password" class="block text-sm font-medium leading-6 text-gray-900">
                            {{ $t('message.password') }}
                        </label>
                        <div class="text-sm">
                            <router-link class="font-semibold text-green-600 hover:text-indigo-500"
                                         :to="{name: 'recovery'}">
                                {{ $t('message.forgotPassword') }}
                            </router-link>
                        </div>
                    </div>
                    <div class="mt-2">
                        <input id="password" name="password" type="password" autocomplete="current-password" required=""
                               v-model="formState.password" class="calories_input password-input"/>
                    </div>
                </div>

                <div>
                    <calories-button type="submit" :disabled="isSubmitting"
                                     :class="{'opacity-50 cursor-not-allowed': isSubmitting}" passed-class="recovery"
                                     class="button">
                        {{ $t('message.signInToYourAccount') }}
                    </calories-button>
                    <div class="google-login">
                        <div class="mt-2">
                        <span class="social-login">
                             {{ $t('message.orSignInWith') }}
                        </span>
                        </div>
                        <div class="mt-2 flex">
                            <calories-button
                                :disabled="isSubmitting"
                                :class="{'opacity-50 cursor-not-allowed': isSubmitting}"
                                passed-class="recovery"
                                class="button w-1/2 mr-2"
                                @click="loginWithGoogle"
                            >
                                Google
                            </calories-button>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>
</template>


<style scoped lang="scss">

.email-input, .password-input {
    width: 100%;
    padding: 5px;
    color: #1f2937;
    background-color: #fff;
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

    @media (min-width: 640px) {
        font-size: 0.875rem;
        line-height: 1.5rem;
    }
}


.hide {
    opacity: 0.33;
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

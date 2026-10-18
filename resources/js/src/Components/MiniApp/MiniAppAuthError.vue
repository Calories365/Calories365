<script>
import { miniAppAuthFailure } from "@/telegram/miniApp.js";

export default {
    name: "MiniAppAuthError",
    data() {
        return {
            failure: miniAppAuthFailure(),
        };
    },
    methods: {
        retry() {
            window.location.reload();
        },
    },
};
</script>

<template>
    <div class="mini-app-auth-error">
        <p class="mini-app-auth-error_text">{{ $t("MiniApp.authError") }}</p>
        <button class="mini-app-auth-error_button" @click="retry">
            {{ $t("MiniApp.retry") }}
        </button>
        <p v-if="failure" class="mini-app-auth-error_code">
            {{ $t("MiniApp.errorCode", { code: failure }) }}
        </p>
    </div>
</template>

<style scoped lang="scss">
.mini-app-auth-error {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    gap: 16px;
    min-height: 60vh;
    padding: 24px;
    text-align: center;

    &_text {
        color: #666666;
        font-size: 16px;
    }

    &_button {
        padding: 10px 24px;
        border: none;
        border-radius: 8px;
        background: $main-color;
        color: #ffffff;
        font-size: 15px;
        cursor: pointer;
    }

    &_code {
        color: #a7a7a7;
        font-size: 13px;
    }
}
</style>

<script>
import { webApp } from "@/telegram/miniApp.js";

export default {
    name: "MiniAppOpenHint",
    props: {
        // The login form is out of reach once the app runs as the Mini App.
        canSignInWithEmail: {
            type: Boolean,
            default: true,
        },
    },
    emits: ["dismiss"],
    methods: {
        close() {
            webApp()?.close();
        },
        signInWithEmail() {
            this.$emit("dismiss");
            this.$router.push({ name: "login" });
        },
    },
};
</script>

<template>
    <div class="mini-app-open-hint">
        <h1 class="mini-app-open-hint_title">
            {{ $t("MiniApp.openHintTitle") }}
        </h1>
        <p class="mini-app-open-hint_text">{{ $t("MiniApp.openHint") }}</p>
        <button class="mini-app-open-hint_button" @click="close">
            {{ $t("MiniApp.close") }}
        </button>
        <button
            v-if="canSignInWithEmail"
            class="mini-app-open-hint_link"
            @click="signInWithEmail"
        >
            {{ $t("MiniApp.signInWithEmail") }}
        </button>
    </div>
</template>

<style scoped lang="scss">
.mini-app-open-hint {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    gap: 16px;
    min-height: 80vh;
    padding: 24px;
    text-align: center;
    font-family: "Open Sans", sans-serif;

    &_title {
        margin: 0;
        font-size: 20px;
        font-weight: 700;
        line-height: 1.3;
        color: #333333;
    }

    &_text {
        margin: 0;
        font-size: 16px;
        line-height: 1.5;
        color: #666666;
    }

    &_button {
        padding: 12px 32px;
        border: none;
        border-radius: 8px;
        background: $main-color;
        color: #ffffff;
        font-size: 16px;
        cursor: pointer;
    }

    &_link {
        padding: 8px;
        border: none;
        background: none;
        color: #666666;
        font-size: 14px;
        text-decoration: underline;
        cursor: pointer;
    }
}
</style>

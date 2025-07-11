<script>
export default {
    name: "CookieBanner",
    data() {
        // читаем флаг из localStorage
        return {
            cookiesApproved: localStorage.getItem("cookiesAccepted") === "1",
        };
    },
    methods: {
        approveCookies() {
            localStorage.setItem("cookiesAccepted", "1");
            this.cookiesApproved = true;
        },
    },
};
</script>

<template>
    <!-- баннер показан, пока пользователь не принял cookies -->
    <div v-if="!cookiesApproved" class="cookie-banner">
        <span class="cookie-text">
            Ми використовуємо файли cookies, щоб покращити ваш досвід.
        </span>
        <button class="cookie-btn" @click="approveCookies">Прийняти</button>
    </div>
</template>

<style scoped lang="scss">
.cookie-banner {
    position: fixed;
    bottom: 20px;
    left: 20px;
    right: 20px;
    z-index: 1000;
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 1rem 1.5rem;
    border-radius: 8px;
    background: #f5f5f5;
    box-shadow: 0 2px 6px rgba(0, 0, 0, .15);
    font-size: 0.9rem;

    @media (max-width: 48em) {
        flex-direction: column;
        gap: 0.5rem;
        text-align: center;
    }

    .cookie-text {
        color: #444;
    }

    .cookie-btn {
        padding: 0.5rem 1rem;
        border: none;
        border-radius: 6px;
        cursor: pointer;
        background: #4caf50;
        color: #fff;
        font-weight: 600;
        transition: background 0.2s;

        &:hover {
            background: #43a047;
        }
    }
}
</style>

<script>
import CaloriesButton from "@/Components/CaloriesButton.vue";

export default {
    name: "ThankYou",
    components: { CaloriesButton },
    methods: {
        goHome() {
            this.$router.push({ name: "home" });
        },
    },
};
</script>

<template>
    <div class="thank-wrapper">
        <div class="thank-card">
            <h1 class="thank-title">{{ $t("ThankYou.Title") }}</h1>
            <p class="thank-text">
                {{ $t("ThankYou.Message") }}
            </p>

            <calories-button @click="goHome" passed-class="extra-padding">
                {{ $t("ThankYou.GoHome") }}
            </calories-button>
        </div>
    </div>
</template>
<style scoped lang="scss">
$main-color: #73ae37;

.thank-wrapper {
    min-height: calc(50vh - 60px);
    display: flex;
    justify-content: center;
    align-items: center;
    padding: 2rem;
}

.thank-card {
    background-color: lighten($main-color, 35%);
    border-radius: 24px;
    padding: 3rem 4rem;
    text-align: center;
    box-shadow: 0 4px 18px rgba(0, 0, 0, 0.12);
}

.thank-title {
    color: #000000;
    margin-bottom: 1.2rem;
    font-size: 2rem;
    font-weight: 700;
}

.thank-text {
    color: #000000;
    font-size: 1.15rem;
    margin-bottom: 2.2rem;
}
</style>

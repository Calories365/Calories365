<script setup>
import MainFooter from "../Components/MainFooter.vue";
</script>

<template>
    <div class="payment-delivery-wrapper">
        <h1>{{ $t("PaymentDelivery.Title") }}</h1>
        <p class="last-updated">
            <strong>{{ $t("PaymentDelivery.LastUpdated") }}</strong>
        </p>

        <p>{{ $t("PaymentDelivery.Intro") }}</p>

        <h2>{{ $t("PaymentDelivery.SectionPayment") }}</h2>
        <p>{{ $t("PaymentDelivery.PaymentIntro") }}</p>
        <ul>
            <li>{{ $t("PaymentDelivery.PaymentMethodCard") }}</li>
            <li>{{ $t("PaymentDelivery.PaymentMethodGPay") }}</li>
            <li>{{ $t("PaymentDelivery.PaymentMethodAPay") }}</li>
        </ul>

        <h2>{{ $t("PaymentDelivery.SectionService") }}</h2>
        <p>{{ $t("PaymentDelivery.ServiceParagraph1") }}</p>
        <p>{{ $t("PaymentDelivery.ServiceParagraph2") }}</p>

        <h2>{{ $t("PaymentDelivery.SectionRefund") }}</h2>
        <p>{{ $t("PaymentDelivery.RefundParagraph") }}</p>
    </div>

    <MainFooter />
</template>

<style scoped lang="scss">
.payment-delivery-wrapper {
    max-width: 1000px;
    margin: 40px auto;
    padding: 0 20px;
    line-height: 1.6;

    h1 {
        font-size: 1.8rem;
        margin-bottom: 1rem;
    }

    h2 {
        font-size: 1.4rem;
        margin-top: 2rem;
        margin-bottom: 0.5rem;
        font-weight: 600;
    }

    .last-updated {
        font-style: italic;
        margin-bottom: 1.5rem;
    }

    ul {
        margin-left: 1.5rem;
        margin-bottom: 1rem;
        list-style: disc;
    }

    p {
        margin-bottom: 1rem;
    }

    a {
        color: #1c7a27;
        text-decoration: underline;

        &:hover {
            color: #14561c;
        }
    }
}
</style>

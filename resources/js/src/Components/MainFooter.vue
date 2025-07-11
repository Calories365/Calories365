<script setup></script>

<template>
    <footer class="main-footer">
        <div class="container">
            <div class="footer-links">
                <router-link
                    :to="{ name: 'privacyPolicy' }"
                    class="privacy-link"
                >
                    {{ $t("Home.PrivacyPolicy") }}
                </router-link>
                <router-link
                    :to="{ name: 'termsOfService' }"
                    class="privacy-link"
                >
                    {{ $t("Home.TermsOfService") }}
                </router-link>
                <router-link :to="{ name: 'faq' }" class="privacy-link">
                    {{ $t("Home.FAQ") }}
                </router-link>
                <router-link
                    :to="{ name: 'payment-and-delivery' }"
                    class="privacy-link"
                >
                    {{ $t("Home.PaymentAndDelivery") }}
                </router-link>
            </div>

            <div class="main-footer_info-for-payment">
                <div class="payment-logos">
                    <div class="payment-logos__main">
                        <img
                            src="https://wfpstorage.s3-eu-west-1.amazonaws.com/help/1746550624_%D0%B4%D0%BB%D1%8F%20%D1%82%D0%B5%D0%BC%D0%BD%D0%BE%D0%B3%D0%BE%20%D1%84%D0%BE%D0%BD%D1%83.png"
                            alt="WayForPay logo"
                            width="500"
                            height="67"
                        />
                    </div>
                    <div class="payment-logos__minor">
                        <img
                            src="https://wfpstorage.s3-eu-west-1.amazonaws.com/help/1746549935_GPay-logo.png"
                            alt="Google Pay"
                            width="100"
                            height="40"
                        />
                        <img
                            src="https://wfpstorage.s3-eu-west-1.amazonaws.com/help/1746549949_Group.png"
                            alt="Apple Pay"
                            width="100"
                            height="42"
                        />
                    </div>
                </div>

                <div class="contact-info">
                    <p>ФОП Панченко Гліб Станіславович</p>
                    <p>
                        02068, Україна, м. Київ,<br />
                        вул. Драгоманова, 23 б
                    </p>
                    <p>ІПН: 3733906719</p>
                    <p>Тел: +380&nbsp;50&nbsp;171&nbsp;56&nbsp;24</p>
                    <p>Email: calories365.diary@gmail.com</p>
                </div>
            </div>
        </div>
    </footer>
</template>

<style scoped lang="scss">
.main-footer {
    font-family: Arial, Helvetica, sans-serif;
    font-size: $default-font-size;
    line-height: 1.5;

    background-color: #333;
    color: #fff;
    text-align: center;
    padding: 20px 0;

    .container {
        width: 90%;
        max-width: 1300px;
        margin: 0 auto;
        padding: 0 20px;
    }

    .footer-links {
        display: flex;
        justify-content: center;
        gap: 20px;
        padding-bottom: 20px;

        @media (max-width: $bp-medium) {
            flex-direction: column;
        }
    }

    .privacy-link {
        color: #fff;
        text-decoration: none;
        font-size: 1.2rem;
        transition: color 0.3s ease;

        @media (max-width: $bp-medium) {
            font-size: 1rem;
        }

        &:hover {
            color: #aaa;
        }
    }

    &_info-for-payment {
        display: flex;
        justify-content: space-between;
        padding-top: 30px;

        @media (max-width: $bp-medium) {
            flex-direction: column;
        }

        .contact-info {
            font-size: 0.85rem;

            p {
                margin: 5px 0;
            }
        }

        .payment-logos {
            display: flex;
            flex-direction: column;
            gap: 20px;

            &__minor {
                display: flex;
                gap: 20px;
                justify-content: center;
            }
        }
    }
}
</style>

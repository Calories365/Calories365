<script setup></script>

<template>
    <div class="terms-of-service-wrapper">
        <h1>{{ $t("TermsOfService.Title") }}</h1>
        <p class="last-updated">
            <strong>{{ $t("TermsOfService.LastUpdated") }}</strong>
        </p>

        <p>{{ $t("TermsOfService.Intro") }}</p>

        <!-- 1. Acceptance of Terms -->
        <h2>{{ $t("TermsOfService.Section1") }}</h2>
        <p>{{ $t("TermsOfService.Section1Text") }}</p>

        <!-- 2. Eligibility -->
        <h2>{{ $t("TermsOfService.Section2") }}</h2>
        <p>{{ $t("TermsOfService.Section2Text") }}</p>

        <!-- 3. User Accounts -->
        <h2>{{ $t("TermsOfService.Section3") }}</h2>
        <p>{{ $t("TermsOfService.Section3Text") }}</p>

        <!-- 4. User Conduct -->
        <h2>{{ $t("TermsOfService.Section4") }}</h2>
        <p>{{ $t("TermsOfService.Section4Text") }}</p>
        <ul>
            <li>{{ $t("TermsOfService.List4_1") }}</li>
            <li>{{ $t("TermsOfService.List4_2") }}</li>
            <li>{{ $t("TermsOfService.List4_3") }}</li>
            <li>{{ $t("TermsOfService.List4_4") }}</li>
            <li>{{ $t("TermsOfService.List4_5") }}</li>
        </ul>

        <!-- 5. Intellectual Property -->
        <h2>{{ $t("TermsOfService.Section5") }}</h2>
        <p>{{ $t("TermsOfService.Section5Text") }}</p>

        <!-- 6. Termination -->
        <h2>{{ $t("TermsOfService.Section6") }}</h2>
        <p>{{ $t("TermsOfService.Section6Text") }}</p>

        <!-- 7. Disclaimers -->
        <h2>{{ $t("TermsOfService.Section7") }}</h2>
        <p>{{ $t("TermsOfService.Section7Text") }}</p>

        <!-- 8. Limitation of Liability -->
        <h2>{{ $t("TermsOfService.Section8") }}</h2>
        <p>{{ $t("TermsOfService.Section8Text") }}</p>

        <!-- 9. Indemnification -->
        <h2>{{ $t("TermsOfService.Section9") }}</h2>
        <p>{{ $t("TermsOfService.Section9Text") }}</p>
        <ul>
            <li>{{ $t("TermsOfService.List9_1") }}</li>
            <li>{{ $t("TermsOfService.List9_2") }}</li>
            <li>{{ $t("TermsOfService.List9_3") }}</li>
            <li>{{ $t("TermsOfService.List9_4") }}</li>
        </ul>

        <!-- 10. Governing Law -->
        <h2>{{ $t("TermsOfService.Section10") }}</h2>
        <p>{{ $t("TermsOfService.Section10Text") }}</p>

        <!-- 11. Changes to Terms -->
        <h2>{{ $t("TermsOfService.Section11") }}</h2>
        <p>{{ $t("TermsOfService.Section11Text") }}</p>

        <!-- 12. Contact Us -->
        <h2>{{ $t("TermsOfService.Section12") }}</h2>
        <p>
            {{ $t("TermsOfService.Section12Text") }} calories365.diary@gmail.com
        </p>

        <!-- New Sections for WayForPay Compliance -->

        <!-- 13. Privacy Policy -->
        <h2>{{ $t("TermsOfService.Section13") }}</h2>
        <p>{{ $t("TermsOfService.Section13Text") }}</p>

        <!-- 14. Offer Agreement -->
        <h2>{{ $t("TermsOfService.Section14") }}</h2>
        <p>{{ $t("TermsOfService.Section14Text") }}</p>

        <!-- 15. Refund and Exchange Policy -->
        <h2>{{ $t("TermsOfService.Section15") }}</h2>
        <p>
            {{ $t("TermsOfService.Section15Text") }} calories365.diary@gmail.com
        </p>

        <!-- 16. Ordering and Using Services -->
        <h2>{{ $t("TermsOfService.Section16") }}</h2>
        <p>{{ $t("TermsOfService.Section16Text") }}</p>

        <!-- 17. Order Cancellation -->
        <h2>{{ $t("TermsOfService.Section17") }}</h2>
        <p>{{ $t("TermsOfService.Section17Text") }}</p>

        <!-- 18. Delivery -->
        <h2>{{ $t("TermsOfService.Section18") }}</h2>
        <p>{{ $t("TermsOfService.Section18Text") }}</p>

        <!-- 19. Refund Process -->
        <h2>{{ $t("TermsOfService.Section19") }}</h2>
        <p>{{ $t("TermsOfService.Section19Text") }}</p>

        <!-- 20. Full Cost Disclosure -->
        <h2>{{ $t("TermsOfService.Section20") }}</h2>
        <p>{{ $t("TermsOfService.Section20Text") }}</p>
    </div>
</template>

<style scoped lang="scss">
.terms-of-service-wrapper {
    max-width: 800px;
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

    ul, ol {
        margin-left: 1.5rem;
        margin-bottom: 1rem;
    }

    p {
        margin-bottom: 1rem;
    }
}
</style>

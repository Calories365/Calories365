<script setup>
import MainFooter from "../Components/MainFooter.vue";
</script>

<template>
    <div class="terms-of-service-wrapper">
        <h1>{{ $t("TermsOfService.Title") }}</h1>
        <p class="last-updated">
            <strong>{{ $t("TermsOfService.LastUpdated") }}</strong>
        </p>

        <p>{{ $t("TermsOfService.Intro") }}</p>

        <h2>{{ $t("TermsOfService.Section1") }}</h2>
        <p>{{ $t("TermsOfService.Section1Text") }}</p>

        <h2>{{ $t("TermsOfService.Section2") }}</h2>
        <p>{{ $t("TermsOfService.Section2Text") }}</p>

        <h2>{{ $t("TermsOfService.Section3") }}</h2>
        <p>{{ $t("TermsOfService.Section3Text") }}</p>

        <h2>{{ $t("TermsOfService.Section4") }}</h2>
        <p>{{ $t("TermsOfService.Section4Text") }}</p>
        <ul>
            <li>{{ $t("TermsOfService.List4_1") }}</li>
            <li>{{ $t("TermsOfService.List4_2") }}</li>
            <li>{{ $t("TermsOfService.List4_3") }}</li>
            <li>{{ $t("TermsOfService.List4_4") }}</li>
            <li>{{ $t("TermsOfService.List4_5") }}</li>
        </ul>

        <h2>{{ $t("TermsOfService.Section5") }}</h2>
        <p>{{ $t("TermsOfService.Section5Text") }}</p>

        <h2>{{ $t("TermsOfService.Section6") }}</h2>
        <p>{{ $t("TermsOfService.Section6Text") }}</p>

        <h2>{{ $t("TermsOfService.Section7") }}</h2>
        <p>{{ $t("TermsOfService.Section7Text") }}</p>

        <h2>{{ $t("TermsOfService.Section8") }}</h2>
        <p>{{ $t("TermsOfService.Section8Text") }}</p>

        <h2>{{ $t("TermsOfService.Section9") }}</h2>
        <p>{{ $t("TermsOfService.Section9Text") }}</p>
        <ul>
            <li>{{ $t("TermsOfService.List9_1") }}</li>
            <li>{{ $t("TermsOfService.List9_2") }}</li>
            <li>{{ $t("TermsOfService.List9_3") }}</li>
            <li>{{ $t("TermsOfService.List9_4") }}</li>
        </ul>

        <h2>{{ $t("TermsOfService.Section10") }}</h2>
        <p>{{ $t("TermsOfService.Section10Text") }}</p>

        <h2>{{ $t("TermsOfService.Section11") }}</h2>
        <p>{{ $t("TermsOfService.Section11Text") }}</p>

        <h2>{{ $t("TermsOfService.Section12") }}</h2>
        <p>
            {{ $t("TermsOfService.Section12Text") }} calories365.diary@gmail.com
        </p>

        <h2>{{ $t("TermsOfService.Section13") }}</h2>
        <p>{{ $t("TermsOfService.Section13Text") }}</p>

        <h2>{{ $t("TermsOfService.Section14") }}</h2>
        <p>{{ $t("TermsOfService.Section14Text") }}</p>

        <h2>{{ $t("TermsOfService.Section15") }}</h2>
        <p>
            {{ $t("TermsOfService.Section15Text") }} calories365.diary@gmail.com
        </p>

        <h2>{{ $t("TermsOfService.Section16") }}</h2>
        <p>{{ $t("TermsOfService.Section16Text") }}</p>

        <h2>{{ $t("TermsOfService.Section17") }}</h2>
        <p>{{ $t("TermsOfService.Section17Text") }}</p>

        <h2>{{ $t("TermsOfService.Section18") }}</h2>
        <p>{{ $t("TermsOfService.Section18Text") }}</p>

        <h2>{{ $t("TermsOfService.Section19") }}</h2>
        <p>{{ $t("TermsOfService.Section19Text") }}</p>

        <h2>{{ $t("TermsOfService.Section20") }}</h2>
        <p>{{ $t("TermsOfService.Section20Text") }}</p>
    </div>

    <MainFooter />
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

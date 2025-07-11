<script setup>
import MainFooter from "../Components/MainFooter.vue";
</script>

<template>
    <div class="faq-wrapper">
        <h1>{{ $t("FAQ.Title") }}</h1>
        <p class="last-updated">
            <strong>{{ $t("FAQ.LastUpdated") }}</strong>
        </p>

        <p>{{ $t("FAQ.Intro") }} calories365.diary@gmail.com.</p>

        <h2>{{ $t("FAQ.Section1") }}</h2>
        <p>{{ $t("FAQ.Section1Text") }}</p>

        <h2>{{ $t("FAQ.Section2") }}</h2>
        <ul>
            <li>{{ $t("FAQ.List2_1") }}</li>
            <li>{{ $t("FAQ.List2_2") }}</li>
            <li>{{ $t("FAQ.List2_3") }}</li>
            <li>{{ $t("FAQ.List2_4") }}</li>
            <li>{{ $t("FAQ.List2_5") }}</li>
        </ul>

        <h2>{{ $t("FAQ.Section3") }}</h2>
        <p>{{ $t("FAQ.Section3Text") }}</p>
        <ul>
            <li>{{ $t("FAQ.List3_1") }}</li>
            <li>{{ $t("FAQ.List3_2") }}</li>
        </ul>
        <p>{{ $t("FAQ.AdditionalText3") }}</p>

        <h2>{{ $t("FAQ.Section4") }}</h2>
        <p>{{ $t("FAQ.Section4Text") }}</p>

        <h2>{{ $t("FAQ.Section5") }}</h2>
        <ul>
            <li>{{ $t("FAQ.List5_1") }}</li>
            <li>{{ $t("FAQ.List5_2") }}</li>
            <li>{{ $t("FAQ.List5_3") }}</li>
        </ul>

        <h2>{{ $t("FAQ.Section6") }}</h2>
        <p>{{ $t("FAQ.Section6Text") }}</p>

        <h2>{{ $t("FAQ.Section7") }}</h2>
        <p>{{ $t("FAQ.Section7Text") }}</p>

        <h2>{{ $t("FAQ.Section8") }}</h2>
        <ul>
            <li>{{ $t("FAQ.List8_1") }}</li>
            <li>{{ $t("FAQ.List8_2") }}</li>
        </ul>

        <h2>{{ $t("FAQ.Section9") }}</h2>
        <p>{{ $t("FAQ.Section9Text") }}</p>

        <h2>{{ $t("FAQ.Section10") }}</h2>
        <p>{{ $t("FAQ.Section10Text") }}</p>

        <h2>{{ $t("FAQ.Section11") }}</h2>
        <p>{{ $t("FAQ.Section11Text") }}</p>

        <h2>{{ $t("FAQ.Section12") }}</h2>
        <ul>
            <li>{{ $t("FAQ.List12_1") }}</li>
            <li>{{ $t("FAQ.List12_2") }}</li>
        </ul>

        <h2>{{ $t("FAQ.Section13") }}</h2>
        <ul>
            <li>{{ $t("FAQ.List13_1") }} calories365.diary@gmail.com</li>
            <li>{{ $t("FAQ.List13_3") }}</li>
        </ul>

        <h2>{{ $t("FAQ.Section14") }}</h2>
        <p>{{ $t("FAQ.Section14Text") }}</p>

        <h2>{{ $t("FAQ.Conclusion") }}</h2>
        <p>{{ $t("FAQ.ConclusionText") }}</p>
    </div>
    <MainFooter />
</template>

<style scoped lang="scss">
.faq-wrapper {
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

<script setup>
</script>

<template>
    <div class="privacy-policy-wrapper">
        <h1>{{ $t("PrivacyPolicy.Title") }}</h1>
        <p class="last-updated"><strong>{{ $t("PrivacyPolicy.LastUpdated") }}</strong></p>

        <p>{{ $t("PrivacyPolicy.Intro") }}</p>

        <!-- 1. Информация, которую мы собираем -->
        <h2>{{ $t("PrivacyPolicy.Section1") }}</h2>
        <h3>{{ $t("PrivacyPolicy.Subsection1_1") }}</h3>
        <p>{{ $t("PrivacyPolicy.Subsection1_1Text") }}</p>
        <ul>
            <li>{{ $t("PrivacyPolicy.List1_1_1") }}</li>
            <li>{{ $t("PrivacyPolicy.List1_1_2") }}</li>
            <li>{{ $t("PrivacyPolicy.List1_1_3") }}</li>
            <li>{{ $t("PrivacyPolicy.List1_1_4") }}</li>
        </ul>

        <h3>{{ $t("PrivacyPolicy.Subsection1_2") }}</h3>
        <p>{{ $t("PrivacyPolicy.Subsection1_2Text") }}</p>
        <ul>
            <li>{{ $t("PrivacyPolicy.List1_2_1") }}</li>
            <li>{{ $t("PrivacyPolicy.List1_2_2") }}</li>
            <li>{{ $t("PrivacyPolicy.List1_2_3") }}</li>
            <li>{{ $t("PrivacyPolicy.List1_2_4") }}</li>
            <li>{{ $t("PrivacyPolicy.List1_2_5") }}</li>
        </ul>
        <p>{{ $t("PrivacyPolicy.AdditionalText1_2") }}</p>

        <!-- 2. Как мы используем вашу информацию -->
        <h2>{{ $t("PrivacyPolicy.Section2") }}</h2>
        <p>{{ $t("PrivacyPolicy.Section2Text") }}</p>
        <ol>
            <li>{{ $t("PrivacyPolicy.List2_1") }}</li>
            <li>{{ $t("PrivacyPolicy.List2_2") }}</li>
            <li>{{ $t("PrivacyPolicy.List2_3") }}</li>
            <li>{{ $t("PrivacyPolicy.List2_4") }}</li>
            <li>{{ $t("PrivacyPolicy.List2_5") }}</li>
            <li>{{ $t("PrivacyPolicy.List2_6") }}</li>
        </ol>

        <!-- 3. Как мы делимся вашей информацией -->
        <h2>{{ $t("PrivacyPolicy.Section3") }}</h2>
        <p>{{ $t("PrivacyPolicy.Section3Text") }}</p>

        <h3>{{ $t("PrivacyPolicy.Subsection3_1") }}</h3>
        <p>{{ $t("PrivacyPolicy.Subsection3_1Text") }}</p>

        <h3>{{ $t("PrivacyPolicy.Subsection3_2") }}</h3>
        <p>{{ $t("PrivacyPolicy.Subsection3_2Text") }}</p>

        <h3>{{ $t("PrivacyPolicy.Subsection3_3") }}</h3>
        <p>{{ $t("PrivacyPolicy.Subsection3_3Text") }}</p>

        <!-- 4. Куки и технологии отслеживания -->
        <h2>{{ $t("PrivacyPolicy.Section4") }}</h2>
        <p>{{ $t("PrivacyPolicy.Section4Text") }}</p>

        <!-- 5. Хранение данных -->
        <h2>{{ $t("PrivacyPolicy.Section5") }}</h2>
        <p>{{ $t("PrivacyPolicy.Section5Text") }}</p>

        <!-- 6. Безопасность данных -->
        <h2>{{ $t("PrivacyPolicy.Section6") }}</h2>
        <p>{{ $t("PrivacyPolicy.Section6Text") }}</p>

        <!-- 7. Ваши права и выбор -->
        <h2>{{ $t("PrivacyPolicy.Section7") }}</h2>
        <p>{{ $t("PrivacyPolicy.Section7Text") }}</p>
        <ul>
            <li>{{ $t("PrivacyPolicy.List7_1") }}</li>
            <li>{{ $t("PrivacyPolicy.List7_2") }}</li>
            <li>{{ $t("PrivacyPolicy.List7_3") }}</li>
            <li>{{ $t("PrivacyPolicy.List7_4") }}</li>
            <li>{{ $t("PrivacyPolicy.List7_5") }}</li>
        </ul>
        <p>{{ $t("PrivacyPolicy.AdditionalText7") }}</p>

        <!-- 8. Конфиденциальность детей -->
        <h2>{{ $t("PrivacyPolicy.Section8") }}</h2>
        <p>{{ $t("PrivacyPolicy.Section8Text") }}</p>

        <!-- 9. Ссылки на сторонние ресурсы -->
        <h2>{{ $t("PrivacyPolicy.Section9") }}</h2>
        <p>{{ $t("PrivacyPolicy.Section9Text") }}</p>

        <!-- 10. Изменения в Политике конфиденциальности -->
        <h2>{{ $t("PrivacyPolicy.Section10") }}</h2>
        <p>{{ $t("PrivacyPolicy.Section10Text") }}</p>

        <!-- 11. Свяжитесь с нами -->
        <h2>{{ $t("PrivacyPolicy.Section11") }}</h2>
        <p>{{ $t("PrivacyPolicy.Section11Text") }}</p>
    </div>
</template>

<style scoped lang="scss">
.privacy-policy-wrapper {
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

    h3 {
        font-size: 1.1rem;
        margin-top: 1.2rem;
        margin-bottom: 0.3rem;
        font-weight: 500;
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

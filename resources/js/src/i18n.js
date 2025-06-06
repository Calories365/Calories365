import { createI18n } from "vue-i18n";
import en from "@/locales/en.json";
import ru from "@/locales/ru.json";
import ua from "@/locales/ua.json";

const messages = {
    en,
    ru,
    ua,
};
let userLocale = localStorage.getItem("locale") || "ua";
const i18n = createI18n({
    legacy: false,
    locale: userLocale,
    fallbackLocale: "ua",
    messages,
});

export default i18n;

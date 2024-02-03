import {createI18n} from 'vue-i18n';
import en from '@/locales/en.json';
import ru from '@/locales/ru.json';
import ua from '@/locales/ua.json';

const messages = {
    en,
    ru,
    ua
};

const i18n = createI18n({
    locale: 'en', // установите локаль по умолчанию
    fallbackLocale: 'en', // установите запасную локаль
    messages, // установите сообщения локализации
});

export default i18n;

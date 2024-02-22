import {createI18n} from 'vue-i18n';
import en from '@/locales/en.json';
import ru from '@/locales/ru.json';
import ua from '@/locales/ua.json';

const messages = {
    en,
    ru,
    ua
};

// Убедитесь, что вы используете `legacy: false` для Vue 3 совместимости
const i18n = createI18n({
    legacy: false, // Важно для использования Composition API
    locale: 'en', // установите локаль по умолчанию
    fallbackLocale: 'en', // установите запасную локаль
    messages, // установите сообщения локализации
});

export default i18n;

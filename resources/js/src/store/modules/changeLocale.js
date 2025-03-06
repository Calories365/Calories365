const state = {
    selectedLocale: 'en',
    locales: ['en', 'ua', 'ru'],
}

export const getterTypes = {
    selectedLocale: '[localeChange] selectedLocale',
    availableLocales: '[localeChange] availableLocales',
}

//геттеры глобальные, но в данном случае они получают локальное состояние
const getters = {
    [getterTypes.selectedLocale]: state => {
        return state.selectedLocale
    },
    availableLocales: (state, getters, rootState) => {
        // Filter out Russian if it's disabled
        if (rootState.language && !rootState.language.russianLanguageEnabled) {
            return state.locales.filter(locale => locale !== 'ru');
        }
        return state.locales;
    },
}

export const mutationTypes = {
    setLocale: '[localeChange] setLocale',
}

const mutations = {
    [mutationTypes.setLocale](state, payload) {
        state.selectedLocale = payload;
    },
}

export const actionTypes = {
    setLocale: '[localeChange] setLocale',
}

const actions = {
    [actionTypes.setLocale](context, {locale, i18n}) {
        // If Russian is disabled and user tries to select it, default to English
        if (locale === 'ru' && context.rootState.language && !context.rootState.language.russianLanguageEnabled) {
            locale = 'en';
        }
        
        return new Promise(resolve => {
            context.commit(mutationTypes.setLocale, locale);
            i18n.locale = locale;
            localStorage.setItem('locale', locale);
            resolve();
        })
    },
}
export default {
    state, mutations, actions, getters,
}




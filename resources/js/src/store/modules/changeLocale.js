const state = {
    selectedLocale: 'en',
    locales: ['en', 'ua', 'ru'],
}

export const getterTypes = {
    selectedLocale: '[localeChange] selectedLocale',
}

//геттеры глобальные, но в данном случае они получают локальное состояние
const getters = {
    [getterTypes.selectedLocale]: state => {
        return state.selectedLocale
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
        return new Promise(resolve => {
            context.commit(mutationTypes.setLocale, locale);
            i18n.locale = locale;
            localStorage.setItem('locale', locale);
        })
    },
}
export default {
    state, mutations, actions, getters,
}




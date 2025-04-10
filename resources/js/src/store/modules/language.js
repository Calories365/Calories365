import { getLanguageStatus } from '@/api/language';

// initial state
const state = {
    russianLanguageEnabled: true,
    loaded: false
};

// getters
const getters = {
    isRussianLanguageEnabled: state => state.russianLanguageEnabled,
    isLanguageLoaded: state => state.loaded
};

// actions
const actions = {
    async fetchLanguageStatus({ commit }) {
        try {
            const response = await getLanguageStatus();
            if (response && response.data && response.data.success) {
                commit('SET_RUSSIAN_LANGUAGE_STATUS', response.data.data.russian_language_enabled);
                commit('SET_LANGUAGE_LOADED', true);
            } else {
                console.error('Invalid response format from language API:', response);
                // Default to true if response format is unexpected
                commit('SET_RUSSIAN_LANGUAGE_STATUS', true);
                commit('SET_LANGUAGE_LOADED', true);
            }
        } catch (error) {
            console.error('Error fetching language status:', error);
            // Default to true if there's an error
            commit('SET_RUSSIAN_LANGUAGE_STATUS', true);
            commit('SET_LANGUAGE_LOADED', true);
        }
    }
};

// mutations
const mutations = {
    SET_RUSSIAN_LANGUAGE_STATUS(state, status) {
        state.russianLanguageEnabled = status !== false; // Default to true unless explicitly false
    },
    SET_LANGUAGE_LOADED(state, status) {
        state.loaded = status;
    }
};

export default {
    namespaced: true,
    state,
    getters,
    actions,
    mutations
};

import { createStore } from "vuex";

import auth from "@/store/modules/auth";
import calculation from "@/store/modules/calculation.js";
import dairy from "@/store/modules/dairy.js";
import changeLocale from "@/store/modules/changeLocale.js";
import stats from "@/store/modules/stats.js";
import language from "@/store/modules/language.js";
import voice from "@/store/modules/voice.js";

export default createStore({
    state: {
        isSuccess: null,
        isError: null,
    },
    getters: {
        isSuccess: (state) => state.isSuccess,
        isError: (state) => state.isError,
    },
    mutations: {
        SET_SUCCESS(state, value) {
            state.isSuccess = value;
        },
        SET_ERROR(state, value) {
            state.isError = value;
        },
        RESET_NOTIFICATION(state) {
            state.isSuccess = null;
            state.isError = null;
        },
        RESET_ERROR(state) {
            state.isError = null;
        },
        RESET_SUCCESS(state) {
            state.isSuccess = null;
        },
    },
    actions: {
        setSuccess({ commit }, payload) {
            commit("SET_SUCCESS", payload);
            commit("RESET_ERROR");

            if (payload !== null) {
                setTimeout(() => {
                    commit("RESET_NOTIFICATION");
                }, 3000);
            }
        },
        setError({ commit }, payload) {
            commit("SET_ERROR", payload);
            commit("RESET_SUCCESS");
            if (payload !== null) {
                setTimeout(() => {
                    commit("RESET_NOTIFICATION");
                }, 3000);
            }
        },
    },
    modules: {
        auth,
        calculation,
        dairy,
        changeLocale,
        stats,
        language,
        voice,
    },
});

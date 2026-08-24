// Kept free of imports: api/axios reads the token from here, and this module
// must not import axios back.
const TOKEN_KEY = "mini_app_token";

export const getMiniAppToken = () => {
    try {
        return localStorage.getItem(TOKEN_KEY);
    } catch (error) {
        return null;
    }
};

export const setMiniAppToken = (token) => {
    try {
        localStorage.setItem(TOKEN_KEY, token);
    } catch (error) {
        // Private mode / storage disabled — the token stays in memory only.
    }
};

export const clearMiniAppToken = () => {
    try {
        localStorage.removeItem(TOKEN_KEY);
    } catch (error) {
        // nothing to clean up
    }
};

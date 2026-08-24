import axios from "axios";
import getCookie from "@/helpers/getCookie.js";
import { getMiniAppToken } from "@/telegram/token.js";

// axios.defaults.baseURL = window.location.origin;
axios.defaults.baseURL = window.location.origin;
//test
axios.defaults.withCredentials = true;

// Inside Telegram there is no login form and no session cookie: the Mini App
// authenticates with a Sanctum token instead.
axios.interceptors.request.use((config) => {
    const miniAppToken = getMiniAppToken();

    if (miniAppToken) {
        config.headers["Authorization"] = `Bearer ${miniAppToken}`;
    }

    return config;
});

axios.interceptors.request.use((config) => {
    const token = getCookie("X-XSRF-TOKEN");
    if (token) {
        config.headers["X-XSRF-TOKEN"] = decodeURIComponent(token);
    } else {
        config.headers["X-XSRF-TOKEN"] = "";
    }
    return config;
});

axios.interceptors.request.use(
    (config) => {
        const locale = localStorage.getItem("locale") || "en";

        config.headers["Accept-Language"] = locale;

        return config;
    },
    (error) => {
        return Promise.reject(error);
    }
);

export default axios;

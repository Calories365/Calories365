import axios from "axios";
import getCookie from "@/helpers/getCookie.js";
// import {getItem} from "@/helpers/persistanceStorage";

const APP_DEBUG = import.meta.env.VITE_APP_DEBUG;

if (APP_DEBUG) {
    axios.defaults.baseURL = 'https://calories-working.test'
} else {
    axios.defaults.baseURL = 'https://calculator.calories365.space'
}


axios.defaults.withCredentials = true;

axios.interceptors.request.use(config => {
    const token = getCookie('X-XSRF-TOKEN');
    if (token) {
        config.headers['X-XSRF-TOKEN'] = decodeURIComponent(token);
    } else {
        config.headers['X-XSRF-TOKEN'] = '';
    }
    return config;
});

axios.interceptors.request.use(config => {
    // Получение текущей локали из i18n или localStorage
    const locale = localStorage.getItem('locale') || 'en';

    // Добавление заголовка 'Accept-Language' с текущей локалью к каждому исходящему запросу
    config.headers['Accept-Language'] = locale;

    return config;
}, error => {
    return Promise.reject(error);
});

export default axios

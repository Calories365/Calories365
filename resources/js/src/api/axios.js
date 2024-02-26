import axios from "axios";
import getCookie from "@/helpers/getCookie.js";

axios.defaults.baseURL = `https://${window.location.host}`

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
    const locale = localStorage.getItem('locale') || 'en';

    config.headers['Accept-Language'] = locale;

    return config;
}, error => {
    return Promise.reject(error);
});

export default axios

// import './bootstrap';
// import '../css/app.css';
import {createApp} from 'vue';
import App from './src/App.vue';
import router from './src/router/router.js';
import store from '@/store/store.js';
import axios from 'axios';
import {actionTypes} from '@/store/modules/auth';
import i18n from "@/i18n.js";
// Сначала выполняем запрос на получение CSRF-токена
axios.get('/sanctum/csrf-cookie').then(() => {
    store.dispatch(actionTypes.getCurrentUser)
        .catch(error => {
            console.error('Error during user initialization', error);
        })
})
    .catch(error => {
        console.error('Error during CSRF token initialization', error);
    }).finally(() => {
    createApp(App)
        .use(router)
        .use(store)
        .use(i18n)
        .mount('#app');
});

// import './bootstrap';
// import '../css/app.css';
import {createApp} from 'vue';
import App from './src/App.vue';
import router from './src/router/router.js';
import store from '@/store/store.js';
import axios from 'axios';
import {actionTypes} from '@/store/modules/auth';
import i18n from "@/i18n.js";

// Determine if we're in academic environment based on hostname
const isAcademic = window.location.hostname.includes('calculator.calories365.xyz');

// Make it available globally
window.isAcademic = isAcademic;

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
    const app = createApp(App);
    
    // Make academic status available globally in Vue
    app.config.globalProperties.$isAcademic = isAcademic;
    
    app.use(router)
        .use(store)
        .use(i18n)
        .mount('#app');
});

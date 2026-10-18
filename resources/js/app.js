// import './bootstrap';
// import '../css/app.css';
import {createApp} from 'vue';
import App from './src/App.vue';
import router from './src/router/router.js';
import store from '@/store/store.js';
import axios from 'axios';
import {actionTypes} from '@/store/modules/auth';
import i18n from "@/i18n.js";
import {
    authenticateMiniApp,
    hasInitData,
    initMiniApp,
    isMiniApp,
    isTelegramWithoutData,
    prepareTelegramView,
    resetMiniAppSession,
} from '@/telegram/miniApp.js';

// Determine if we're in academic environment based on hostname
const isAcademic = window.location.hostname.includes('calculator.calories365.xyz');

// Make it available globally
window.isAcademic = isAcademic;

const miniApp = isMiniApp();

const bootstrapSession = async () => {
    if (miniApp) {
        initMiniApp();
    } else if (isTelegramWithoutData()) {
        prepareTelegramView();
    }

    // Needed in the Mini App too: Sanctum treats same-origin requests as
    // stateful and checks CSRF on them, bearer token or not.
    try {
        await axios.get('/sanctum/csrf-cookie');
    } catch (error) {
        console.error('Error during CSRF token initialization', error);
    }

    // Without initData the token of an earlier launch is all there is.
    if (miniApp && hasInitData()) {
        try {
            await authenticateMiniApp();
        } catch (error) {
            // A stale token would keep every later request failing.
            resetMiniAppSession();
            console.error('Error during Telegram Mini App authentication', error);
        }
    }

    try {
        await store.dispatch(actionTypes.getCurrentUser);
    } catch (error) {
        console.error('Error during user initialization', error);
    }
};

bootstrapSession().finally(() => {
    const app = createApp(App);

    // Make academic status available globally in Vue
    app.config.globalProperties.$isAcademic = isAcademic;
    app.config.globalProperties.$isMiniApp = miniApp;

    app.use(router)
        .use(store)
        .use(i18n)
        .mount('#app');
});

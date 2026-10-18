import { createRouter, createWebHistory } from "vue-router";
import routes from "./routes.js";
import store from "@/store/store.js";
import { getterTypes } from "@/store/modules/auth.js";
import i18n from "@/i18n.js";
import { isMiniApp } from "@/telegram/miniApp.js";

const router = createRouter({
    history: createWebHistory(),
    scrollBehavior(to, from, savedPosition) {
        if (savedPosition) {
            return savedPosition;
        }
        return { top: 0 };
    },
    linkActiveClass: "calories-menu-item-link-active",
    routes,
});

const miniApp = isMiniApp();

router.beforeEach((to, from, next) => {
    // Inside Telegram the marketing pages and the login form have no place:
    // the visitor is already signed in by the time the app mounts.
    if (miniApp && (to.name === "home" || to.meta.needNotAuth)) {
        next({ name: "diary" });
        return;
    }

    if (store.state.auth.isLoading) {
        const unwatch = store.watch(
            (state) => state.auth.isLoading,
            (isLoading) => {
                if (!isLoading) {
                    unwatch();
                    next(to);
                }
            }
        );
    } else {
        if (to.meta.needAuth) {
            if (store.getters[getterTypes.isLoggedIn]) {
                next();
            } else {
                // The Mini App shows its own screen of the failed sign-in.
                if (!miniApp) {
                    const message = i18n.global.t(
                        "Notification.Error.NeedAuth"
                    );
                    store.dispatch("setError", message, { root: true });
                }
                next(miniApp ? false : { name: "login" });
            }
        } else if (to.meta.needNotAuth) {
            if (!store.getters[getterTypes.isLoggedIn]) {
                next();
            } else {
                next({ name: "home" });
            }
        } else {
            next();
        }
    }
});

export default router;

import { createRouter, createWebHistory } from "vue-router";
import routes from "./routes.js";
import store from "@/store/store.js";
import { getterTypes } from "@/store/modules/auth.js";
import i18n from "@/i18n.js";

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

router.beforeEach((to, from, next) => {
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
                const message = i18n.global.t("Notification.Error.NeedAuth");
                store.dispatch("setError", message, { root: true });
                next({ name: "login" });
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

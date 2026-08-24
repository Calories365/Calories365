import axios from "@/api/axios";
import {
    clearMiniAppToken,
    getMiniAppToken,
    setMiniAppToken,
} from "@/telegram/token.js";

const VALID_LOCALES = ["en", "ru", "ua"];

export const webApp = () => window.Telegram?.WebApp ?? null;

/**
 * True only inside Telegram: the SDK exists everywhere it is loaded, but
 * initData is filled in by Telegram itself.
 */
export const isMiniApp = () => Boolean(webApp()?.initData);

const applyViewport = (tg) => {
    const height = tg.viewportStableHeight || tg.viewportHeight;

    if (height) {
        document.documentElement.style.setProperty(
            "--tg-viewport-height",
            `${height}px`
        );
    }
};

export const initMiniApp = () => {
    const tg = webApp();

    if (!tg) {
        return;
    }

    tg.ready();
    tg.expand();

    if (tg.disableVerticalSwipes) {
        tg.disableVerticalSwipes();
    }

    document.documentElement.classList.add("is-mini-app");

    applyViewport(tg);
    tg.onEvent("viewportChanged", () => applyViewport(tg));
};

/**
 * Trade Telegram's signed initData for a Sanctum token.
 *
 * @returns {Promise<object>} the auth payload from the API
 */
export const authenticateMiniApp = async () => {
    const tg = webApp();

    const { data } = await axios.post("/api/telegram/web-app-auth", {
        init_data: tg.initData,
    });

    setMiniAppToken(data.token);

    // Telegram's language only seeds the choice; it never overrides one the
    // user already made in the app.
    if (
        data.locale &&
        VALID_LOCALES.includes(data.locale) &&
        !localStorage.getItem("locale")
    ) {
        localStorage.setItem("locale", data.locale);
    }

    return data;
};

export const resetMiniAppSession = () => {
    clearMiniAppToken();
};

export { getMiniAppToken };

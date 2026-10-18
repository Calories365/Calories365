import axios from "@/api/axios";
import {
    clearMiniAppToken,
    getMiniAppToken,
    setMiniAppToken,
} from "@/telegram/token.js";

const VALID_LOCALES = ["en", "ru", "ua"];

export const webApp = () => window.Telegram?.WebApp ?? null;

let launch = null;

/**
 * How the page was opened, decided once: the stored token may be cleared later,
 * and the layout must not change under the person.
 *
 * - "signed": Telegram passed initData, it is traded for a token
 * - "token": inside Telegram without initData (Telegram leaves it out for a
 *   reply keyboard button), the token of an earlier launch signs the person in
 * - "no-data": inside Telegram without initData and without a token
 * - "site": outside Telegram, where the SDK reports the platform "unknown"
 */
const launchMode = () => {
    if (launch !== null) {
        return launch;
    }

    const tg = webApp();

    if (tg?.initData) {
        launch = "signed";
    } else if (tg?.platform && tg.platform !== "unknown") {
        launch = getMiniAppToken() ? "token" : "no-data";
    } else {
        launch = "site";
    }

    return launch;
};

export const isMiniApp = () => ["signed", "token"].includes(launchMode());

export const hasInitData = () => launchMode() === "signed";

/**
 * Inside Telegram, but without the data to sign the person in from scratch.
 */
export const isTelegramWithoutData = () =>
    ["token", "no-data"].includes(launchMode());

// The app has one light design: Telegram's dark header and background around
// it would frame the white pages in black.
const APP_BACKGROUND = "#ffffff";

const paintTelegramChrome = (tg) => {
    if (tg.isVersionAtLeast?.("6.9")) {
        tg.setHeaderColor(APP_BACKGROUND);
    }

    if (tg.isVersionAtLeast?.("6.1")) {
        tg.setBackgroundColor(APP_BACKGROUND);
    }

    if (tg.isVersionAtLeast?.("7.10")) {
        tg.setBottomBarColor(APP_BACKGROUND);
    }
};

/**
 * Tell Telegram the page is ready and give it the app's colors, signed in or not.
 */
export const prepareTelegramView = () => {
    const tg = webApp();

    if (!tg) {
        return;
    }

    tg.ready();
    tg.expand();
    paintTelegramChrome(tg);
};

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

    prepareTelegramView();

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

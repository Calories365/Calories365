import { afterEach, describe, expect, it, vi } from "vitest";

vi.mock("@/api/axios", () => ({ default: { post: vi.fn() } }));

/**
 * Load miniApp.js as a fresh page would: it decides how it was opened once.
 */
const launch = async ({
    initData = "",
    platform = "unknown",
    token = null,
}) => {
    vi.resetModules();
    vi.stubGlobal("window", { Telegram: { WebApp: { initData, platform } } });
    vi.stubGlobal("localStorage", {
        getItem: (key) => (key === "mini_app_token" ? token : null),
        setItem: vi.fn(),
        removeItem: vi.fn(),
    });

    return import("@/telegram/miniApp.js");
};

afterEach(() => {
    vi.unstubAllGlobals();
});

describe("how the app was opened", () => {
    it("is the Mini App with initData from Telegram", async () => {
        const miniApp = await launch({
            initData: "user=1&hash=x",
            platform: "ios",
        });

        expect(miniApp.isMiniApp()).toBe(true);
        expect(miniApp.hasInitData()).toBe(true);
        expect(miniApp.isTelegramWithoutData()).toBe(false);
    });

    // Telegram opens a Mini App from a keyboard button without initData.
    it("is still the Mini App from a keyboard button once it was signed in", async () => {
        const miniApp = await launch({ platform: "android", token: "1|token" });

        expect(miniApp.isMiniApp()).toBe(true);
        expect(miniApp.hasInitData()).toBe(false);
        expect(miniApp.isTelegramWithoutData()).toBe(true);
    });

    it("cannot sign in from a keyboard button the first time", async () => {
        const miniApp = await launch({ platform: "android" });

        expect(miniApp.isMiniApp()).toBe(false);
        expect(miniApp.isTelegramWithoutData()).toBe(true);
    });

    it("is the site outside Telegram, whatever is stored", async () => {
        const miniApp = await launch({ token: "1|token" });

        expect(miniApp.isMiniApp()).toBe(false);
        expect(miniApp.isTelegramWithoutData()).toBe(false);
    });
});

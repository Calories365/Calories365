import { afterEach, beforeEach, describe, expect, it, vi } from "vitest";

vi.mock("@/api/calculation.js", () => ({
    default: { getCalculationData: vi.fn(), saveCalculationData: vi.fn() },
}));
vi.mock("@/i18n.js", () => ({ default: { global: { t: (key) => key } } }));

const { default: calculationApi } = await import("@/api/calculation.js");
const { default: calculation, actionTypes } = await import(
    "@/store/modules/calculation.js"
);

const dispatch = (
    action,
    getters = { "[calculation] dataKey": "calculationData7" }
) =>
    calculation.actions[action]({
        commit: vi.fn(),
        dispatch: vi.fn(),
        getters,
    });

// Answers within the test, or reports that the promise never settled.
const settled = (promise) =>
    Promise.race([
        promise,
        new Promise((resolve) =>
            setTimeout(() => resolve("never settled"), 200)
        ),
    ]);

beforeEach(() => {
    const stored = {};
    vi.stubGlobal("localStorage", {
        getItem: (key) => stored[key] ?? null,
        setItem: (key, value) => {
            stored[key] = value;
        },
    });
});

afterEach(() => {
    vi.unstubAllGlobals();
});

// The calculator counts its results once the saved data is known: an answer
// that never comes left a new account with zeros and nothing to save.
describe("saved calculator data", () => {
    it("is nothing for a guest who never used the calculator", async () => {
        await expect(
            settled(dispatch(actionTypes.getCalculationDataNotAuth))
        ).resolves.toBeNull();
    });

    it("is nothing for an account the server has no record of", async () => {
        calculationApi.getCalculationData.mockResolvedValueOnce({
            data: { message: "Record not found" },
        });

        await expect(
            settled(dispatch(actionTypes.getCalculationData))
        ).resolves.toBeNull();
    });

    it("is what the guest entered before signing in", async () => {
        localStorage.setItem("calculationData", JSON.stringify({ weight: 80 }));

        await expect(
            settled(dispatch(actionTypes.getCalculationDataNotAuth))
        ).resolves.toEqual({ weight: 80 });
    });
});

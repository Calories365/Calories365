import { describe, expect, it, vi } from "vitest";

vi.mock("@/api/auth", () => ({
    default: { getCurrentUser: vi.fn() },
}));
vi.mock("@/i18n.js", () => ({ default: { global: { t: (key) => key } } }));

const { default: authApi } = await import("@/api/auth");
const { default: auth, actionTypes } = await import("@/store/modules/auth.js");

const dispatchGetCurrentUser = () => {
    const commit = vi.fn();
    const promise = auth.actions[actionTypes.getCurrentUser]({ commit });

    return { commit, promise };
};

// app.js waits for the current user before it mounts the app: an answer that
// never comes leaves a blank page.
describe("getCurrentUser", () => {
    it("settles for a guest, so the app still mounts", async () => {
        authApi.getCurrentUser.mockRejectedValueOnce({
            response: { status: 401 },
        });

        const { commit, promise } = dispatchGetCurrentUser();

        await expect(promise).resolves.toBeNull();
        expect(commit).toHaveBeenCalledWith("[auth] getCurrentUserFailure");
    });

    it("gives the signed-in user", async () => {
        authApi.getCurrentUser.mockResolvedValueOnce({ data: { id: 7 } });

        await expect(dispatchGetCurrentUser().promise).resolves.toEqual({
            id: 7,
        });
    });
});

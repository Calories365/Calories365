import { afterEach, describe, expect, it } from "vitest";
import { parseLocalDate, toLocalDateString } from "@/helpers/localDate.js";

// Node re-reads TZ on assignment, so each case runs in the zone it names.
const originalTimeZone = process.env.TZ;

afterEach(() => {
    process.env.TZ = originalTimeZone;
});

describe.each(["Europe/Kyiv", "America/New_York"])("in %s", (timeZone) => {
    it("keeps the local day right after midnight", () => {
        process.env.TZ = timeZone;

        expect(toLocalDateString(new Date(2026, 9, 17, 0, 30))).toBe("2026-10-17");
    });

    it("moves to the next day across the change to winter time", () => {
        process.env.TZ = timeZone;
        const date = parseLocalDate("2026-10-25");

        date.setDate(date.getDate() + 1);

        expect(toLocalDateString(date)).toBe("2026-10-26");
    });

    it("opens the day picked in the calendar", () => {
        process.env.TZ = timeZone;
        const date = parseLocalDate("2026-10-17");

        date.setDate(5);

        expect(toLocalDateString(date)).toBe("2026-10-05");
    });

    it("moves to the next month from the 31st without skipping one", () => {
        process.env.TZ = timeZone;
        const date = parseLocalDate("2026-10-31");

        date.setDate(1);
        date.setMonth(date.getMonth() + 1);

        expect(toLocalDateString(date)).toBe("2026-11-01");
    });
});

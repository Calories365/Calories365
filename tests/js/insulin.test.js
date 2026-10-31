import { describe, expect, it } from "vitest";
import { timeLabel, zoneOf } from "@/helpers/insulin.js";

describe("timeLabel", () => {
    it("writes minutes of the day as a time", () => {
        expect(timeLabel(0)).toBe("00:00");
        expect(timeLabel(510)).toBe("08:30");
        expect(timeLabel(1439)).toBe("23:59");
    });

    it("wraps around midnight", () => {
        expect(timeLabel(1440)).toBe("00:00");
        expect(timeLabel(-30)).toBe("23:30");
    });
});

describe("zoneOf", () => {
    const zones = { medium: 30, high: 60 };

    it("tells the zone of a level, with its bounds", () => {
        expect(zoneOf(29.9, zones)).toBe("low");
        expect(zoneOf(30, zones)).toBe("medium");
        expect(zoneOf(60, zones)).toBe("high");
    });
});

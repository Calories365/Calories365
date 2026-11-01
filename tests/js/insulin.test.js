import { describe, expect, it } from "vitest";
import { decimal, glucoseZones, timeLabel, zoneOf } from "@/helpers/insulin.js";

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

describe("glucoseZones", () => {
    it("turns the sugar limits of the day into zones", () => {
        const zones = glucoseZones({ base: 5, high: 7.8, very_high: 10 });

        expect(zoneOf(7.7, zones)).toBe("low");
        expect(zoneOf(7.8, zones)).toBe("medium");
        expect(zoneOf(10.2, zones)).toBe("high");
    });
});

describe("decimal", () => {
    it("writes the decimal mark of the language", () => {
        expect(decimal(6.14, "ru")).toBe("6,1");
        expect(decimal(6.14, "en")).toBe("6.1");
        expect(decimal(7, "ua")).toBe("7");
    });

    it("keeps the digit of a sugar level", () => {
        expect(decimal(6, "ru", 1, true)).toBe("6,0");
        expect(decimal(6.14, "en", 1, true)).toBe("6.1");
    });
});

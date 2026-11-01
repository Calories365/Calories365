// The sugar and insulin page counts time in minutes of the day, as the API gives it.

export const timeLabel = (minute) => {
    const inDay = ((Math.round(minute) % 1440) + 1440) % 1440;
    const hours = Math.floor(inDay / 60);
    const minutes = inDay % 60;

    return `${String(hours).padStart(2, "0")}:${String(minutes).padStart(2, "0")}`;
};

export const zoneOf = (level, zones) => {
    if (level >= zones.high) {
        return "high";
    }

    return level >= zones.medium ? "medium" : "low";
};

// Sugar has the same three zones: normal, above 7.8, above 10 mmol/L.
export const glucoseZones = (glucose) => ({
    medium: glucose.high,
    high: glucose.very_high,
});

export const ZONE_COLORS = {
    low: "#4f9d55",
    medium: "#d9a400",
    high: "#d0433a",
};

// 6.1 or 6,1 by the language of the page; not toLocaleString, which some
// engines without full ICU leave with a dot.
export const decimal = (value, locale, digits = 1, keepZero = false) => {
    const fixed = Number(value).toFixed(digits);
    const rounded = keepZero ? fixed : String(Number(fixed));

    return locale === "en" ? rounded : rounded.replace(".", ",");
};

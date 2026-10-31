// The insulin page counts time in minutes of the day, as the API gives it.

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

export const ZONE_COLORS = {
    low: "#4f9d55",
    medium: "#d9a400",
    high: "#d0433a",
};

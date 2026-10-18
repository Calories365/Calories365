// The meals a draft can be saved to, the one of the time of day first, as the
// Telegram bot orders its buttons: breakfast before 11, lunch before 16.

const MEALS = ["morning", "dinner", "supper"];

/**
 * @param {number} hour 0–23, local time
 * @returns {string[]} part_of_day values, the likely one first
 */
export const suggestedMeals = (hour) => {
    const now = hour < 11 ? "morning" : hour < 16 ? "dinner" : "supper";

    return [now, ...MEALS.filter((meal) => meal !== now)];
};

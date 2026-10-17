// Diary dates are calendar days of the user, "YYYY-MM-DD". toISOString() gives
// the UTC day instead: in Kyiv it is still yesterday until 02:00–03:00, and
// new Date("YYYY-MM-DD") is UTC midnight, the day before west of Greenwich.
// Both conversions here work in the browser's own time zone.

const pad = (value) => String(value).padStart(2, "0");

/**
 * @param {Date} date
 * @returns {string} the local calendar day as YYYY-MM-DD
 */
export const toLocalDateString = (date) =>
    `${date.getFullYear()}-${pad(date.getMonth() + 1)}-${pad(date.getDate())}`;

/**
 * @param {string} value YYYY-MM-DD
 * @returns {Date} local midnight of that day
 */
export const parseLocalDate = (value) => {
    const [year, month, day] = String(value).split("-").map(Number);

    return new Date(year, month - 1, day);
};

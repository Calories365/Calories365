import i18n from "@/i18n.js";

function formatDate(date) {
    let locale = i18n.global.locale.value;
    if (locale === 'ua') {
        locale = 'uk';
    }
    const parsedDate = new Date(date);
    const monthKey = `${parsedDate.toLocaleString(locale, {month: 'long', timeZone: 'UTC'})}`;
    const year = parsedDate.getFullYear();

    return `${monthKey} ${year}`;
}

export default formatDate;

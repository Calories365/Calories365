export function truncateString(data, maxLength) {
    if (data) {
        data = typeof data === 'string' ? data : String(data);
    } else {
        return '';
    }

    if (data.length > maxLength) {
        return data.substring(0, maxLength) + '...';
    }
    return data;
}

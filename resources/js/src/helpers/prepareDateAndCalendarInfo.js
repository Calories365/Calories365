function prepareDateAndCalendarInfo(currentTime) {
    if (typeof currentTime === "string") {
        currentTime = new Date(currentTime);
    } else if (!currentTime) {
        currentTime = new Date();
    }

    const currentDate = currentTime.toISOString().split("T")[0];
    const month = currentTime.getMonth();
    const daysInMonth = new Date(
        currentTime.getFullYear(),
        month + 1,
        0
    ).getDate();
    let startDayOfWeek = new Date(currentTime.getFullYear(), month, 1).getDay();
    if (startDayOfWeek === 0) {
        startDayOfWeek = 7;
    }

    return {
        currentDate,
        month,
        daysInMonth,
        startDayOfWeek,
    };
}

export default prepareDateAndCalendarInfo;

function prepareDateAndCalendarInfo(currentTime) {
    // Проверка, является ли переданный аргумент строкой, и если да, то преобразование в объект Date
    if (typeof currentTime === 'string') {
        currentTime = new Date(currentTime);
    }
    // В случае, если аргумент не передан, используется текущая дата
    else if (!currentTime) {
        currentTime = new Date();
    }

    const currentDate = currentTime.toISOString().split('T')[0];
    const month = currentTime.getMonth();
    const daysInMonth = new Date(currentTime.getFullYear(), month + 1, 0).getDate();
    let startDayOfWeek = new Date(currentTime.getFullYear(), month, 1).getDay();
    if (startDayOfWeek === 0) {
        startDayOfWeek = 7
    }

    return {
        currentDate,
        month,
        daysInMonth,
        startDayOfWeek,
    };
}

export default prepareDateAndCalendarInfo;

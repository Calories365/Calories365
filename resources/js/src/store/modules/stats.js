import statsApi from "@/api/stats.js";
import prepareDateAndCalendarInfo from "@/helpers/prepareDateAndCalendarInfo.js";
import {toRaw} from "vue";

const state = {
    isSybmiting: false,
    isLoading: false,
    errors: null,
    currentDate: null,
    currentData: null,
    calendarInfo: {
        month: null,
        daysInMonth: null,
        startDayOfWeek: null,
    },
    calendar: null,
}

export const getterTypes = {
    currentDate: '[stats] currentDate',
    currentData: '[stats] currentData',
    calendarInfo: '[stats] calendarInfo',
    calendar: '[stats] calendar',
}

const getters = {
    [getterTypes.currentDate]: state => {
        return state.currentDate
    },
    [getterTypes.currentData]: state => {
        return state.currentData
    },
    [getterTypes.calendarInfo]: state => {
        return state.calendarInfo
    },
    [getterTypes.calendar]: state => {
        return state.calendar
    },
}

export const mutationTypes = {
    setDate: '[stats] dataInitialization',
    setCalendar: '[stats] setCalendar',

    getDailyCaloriesStart: '[stats] getDailyCaloriesStart',
    getDailyCaloriesSuccess: '[stats] getDailyCaloriesSuccess',
    getDailyCaloriesFailure: '[stats] getDailyCaloriesFailure',
}

const mutations = {
    [mutationTypes.setDate](state, payload) {
        state.currentDate = payload.currentDate;
        state.calendarInfo.month = payload.month;
        state.calendarInfo.daysInMonth = payload.daysInMonth;
        state.calendarInfo.startDayOfWeek = payload.startDayOfWeek;
    },

    [mutationTypes.getDailyCaloriesStart](state) {
        state.isSybmiting = true;
    },
    [mutationTypes.getDailyCaloriesSuccess](state, payload) {
        state.currentData = payload;
        state.isSybmiting = false;
    },
    [mutationTypes.getDailyCaloriesFailure](state, payload) {
        state.errors = payload;
        state.isSybmiting = false;
    },
    [mutationTypes.setCalendar](state, payload) {
        state.calendar = payload;
    },
}

export const actionTypes = {
    dataInitialization: '[stats] dataInitialization',
    dateToggle: '[stats] dateToggle',
    getDailyCalories: '[stats] getDailyCalories',
    setCalendar: '[stats] setCalendar',
}

const actions = {
    [actionTypes.dataInitialization](context) {
        return new Promise(resolve => {
            const preparedData = prepareDateAndCalendarInfo();
            context.commit(mutationTypes.setDate, preparedData);

            resolve(preparedData);
        });
    },
    [actionTypes.dateToggle](context, direction) {
        return new Promise(resolve => {
            const currentDateStr = context.state.currentDate;
            const currentDate = new Date(currentDateStr);

            if (direction === 'next') {
                currentDate.setMonth(currentDate.getMonth() + 1);
            } else if (direction === 'previous') {
                currentDate.setMonth(currentDate.getMonth() - 1);
            } else {
                throw new Error('Invalid direction for dateToggle action');
            }

            const preparedData = prepareDateAndCalendarInfo(currentDate);

            context.commit(mutationTypes.setDate, preparedData);
            context.dispatch(actionTypes.getDailyCalories)
                .then(() => {
                    resolve(preparedData);
                });
        });
    },
    [actionTypes.getDailyCalories](context) {
        return new Promise(resolve => {

            context.commit(mutationTypes.getDailyCaloriesStart);
            statsApi.getCurrentData(state.currentDate)
                .then(response => {
                    const currentData = response.data.dailyCalories;
                    context.commit(mutationTypes.getDailyCaloriesSuccess, currentData);

                    context.dispatch(actionTypes.setCalendar);

                    resolve(currentData);
                })
                .catch(() => {
                    context.commit(mutationTypes.getDailyCaloriesFailure);
                });
        })
    },
    [actionTypes.setCalendar](context) {
        return new Promise(resolve => {

            const daysInMonth = context.state.calendarInfo.daysInMonth;
            const startDayOfWeek = context.state.calendarInfo.startDayOfWeek;
            let calendar = [];
            let dateIndex = 0;
            let week = Math.floor((daysInMonth + startDayOfWeek - 2) / 7);

            const normalArray = toRaw(context.state.currentData);
            let arrayWithData = new Array(31).fill(null);

            arrayWithData = arrayWithData.map((item, index) => {
                const foundItem = normalArray.find((obj) => {
                    const day = new Date(obj.date).getDate();
                    return day === index + 1;
                });
                return foundItem ? foundItem.total_calories : item;
            });

            for (let i = 0; i <= week; i++) {
                calendar[i] = new Array(7).fill('');
                for (let j = 0; j < 7; j++) {
                    if (i === 0 && j < startDayOfWeek - 1) {
                    } else if (dateIndex >= daysInMonth) {

                    } else {
                        dateIndex++;
                        let calories = arrayWithData[dateIndex - 1];
                        let caloriesLimit = context.rootState.auth.currentUser.calories_limit;
                        let classToAdd = '';

                        if (calories !== null) {
                            let percentage = (calories / caloriesLimit - 1) * 100;

                            if (percentage <= 0) {
                                classToAdd = 'nice';
                            } else if (percentage <= 20) {
                                classToAdd = 'overreach';
                            } else {
                                classToAdd = 'significant-overreach';
                            }
                        }
                        calendar[i][j] = {
                            day: dateIndex,
                            calories: calories,
                            class: classToAdd,
                        };
                    }
                }
            }
            context.commit(mutationTypes.setCalendar, calendar);
            resolve(calendar)
        })
    },
}
export default {
    state, mutations, actions, getters,
}

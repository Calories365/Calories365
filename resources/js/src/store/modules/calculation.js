import calculationApi from "@/api/calculation.js";
import i18n from "@/i18n.js";

const state = {
    isSybmiting: false,
    isLoading: false,
    errors: null,
    userData: {},
    userResults: {
        PFC: {
            carbsPercent: 40,
            fatPercent: 25,
            proteinPercent: 35
        },
        dailyCalories: 0,
        bmi: 0,
        bmr: 0,
        daysRequired: 0,
        humanWeightClassifications: 0

    },
}
export const getterTypes = {
    dataKey: '[calculation] dataKey',
}

//геттеры глобальные, но в данном случае они получают локальное состояние
const getters = {
    [getterTypes.dataKey]: (state, getters, rootState) => {
        if (rootState.auth.currentUser) {
            return `calculationData${rootState.auth.currentUser.id}`
        } else {
            return false;
        }
    },
}

export const mutationTypes = {
    getCalculationDataStart: '[calculation] get calculation data Start',
    getCalculationDataSuccess: '[calculation] get calculation data Success',
    getCalculationDataFailure: '[calculation] get calculation data Failure',

    saveCalculationDataStart: '[calculation] save calculation data Start',
    saveCalculationDataSuccess: '[calculation] save calculation data Success',
    saveCalculationDataFailure: '[calculation] save calculation data Failure',

    countResults: '[calculation] count results',

}
export const actionTypes = {
    getCalculationData: '[calculation] Get calculation data',
    getCalculationDataNotAuth: '[calculation] Get calculation data not auth',
    saveCalculationData: '[calculation] Save calculation data',
    countResults: '[calculation] count results',
}
const mutations = {
    [mutationTypes.getCalculationDataStart](state) {
        state.isLoading = true;
    },
    [mutationTypes.getCalculationDataSuccess](state, payload) {
        state.isLoading = false;
        state.userData = payload;
    },
    [mutationTypes.getCalculationDataFailure](state, payload) {
        state.isLoading = false;
        state.errors = payload;
    },
    [mutationTypes.saveCalculationDataStart](state) {
        state.isSybmiting = true;
    },
    [mutationTypes.saveCalculationDataSuccess](state) {
        state.isSybmiting = false;
    },
    [mutationTypes.saveCalculationDataFailure](state, payload) {
        state.errors = payload;
    },
    [mutationTypes.countResults](state, payload) {
        state.userResults = payload.results;
        state.userData = payload.data;
    },
}

const actions = {
    [actionTypes.getCalculationData](context) {
        return new Promise((resolve, reject) => {
            context.commit(mutationTypes.getCalculationDataStart);


            const key = context.getters[getterTypes.dataKey];
            let savedData;
            if (key) {
                savedData = localStorage.getItem(key);
            } else {
                savedData = false;
            }
            if (savedData) {
                console.log('Данные взяты из локального хранилища. Запрос не отправлен.');
                const parsedData = JSON.parse(savedData);
                context.commit(mutationTypes.getCalculationDataSuccess, parsedData);
                resolve(parsedData);
                return;
            }

            calculationApi.getCalculationData()
                .then(response => {
                    if (response.data.message === 'Record not found') {
                        savedData = localStorage.getItem('calculationData');
                        if (savedData) {
                            const parsedData = JSON.parse(savedData);
                            context.commit(mutationTypes.getCalculationDataSuccess, parsedData);
                            resolve(parsedData);
                        }
                    } else {
                        context.commit(mutationTypes.getCalculationDataSuccess, response.data);
                        console.log('Ответ сервера:', response.data);
                        localStorage.setItem(key, JSON.stringify(response.data));
                        resolve(response.data);
                    }
                })
                .catch(error => {
                    console.log('Ошибка при получении данных:', error);
                    context.commit(mutationTypes.getCalculationDataFailure, error);
                    reject(error);
                });
        });
    },
    [actionTypes.getCalculationDataNotAuth](context) {
        return new Promise((resolve, reject) => {
            context.commit(mutationTypes.getCalculationDataStart);


            const savedData = localStorage.getItem('calculationData');

            if (savedData) {
                const parsedData = JSON.parse(savedData);
                context.commit(mutationTypes.getCalculationDataSuccess, parsedData);
                resolve(parsedData);
            }
        });
    },
    [actionTypes.saveCalculationData](context) {
        return new Promise((resolve, reject) => {
            const data = context.state.userData;
            const key = context.getters[getterTypes.dataKey];

            const savedData = localStorage.getItem(key);
            const currentData = JSON.stringify(data);

            if (savedData === currentData) {
                console.log('Данные совпадают с сохраненными. Запрос не отправлен.');
                resolve();
                return;
            }

            context.commit(mutationTypes.saveCalculationDataStart);

            data.dailyCalories = context.state.userResults.dailyCalories;
            calculationApi.saveCalculationData(data)
                .then(response => {
                    context.commit(mutationTypes.saveCalculationDataSuccess);

                    const key = context.getters[getterTypes.dataKey];

                    localStorage.setItem(key, currentData);
                    const message = i18n.global.t('Notification.Success.ResultWasSaved');
                    context.dispatch('setSuccess', message, { root: true });

                    resolve();
                })
                .catch(error => {
                    context.commit(mutationTypes.saveCalculationDataFailure);
                    const message = i18n.global.t('Notification.Error.ResultSaveFailed');
                    context.dispatch('setError', message, { root: true });
                    reject(error);
                });
        });
    },
    [actionTypes.countResults](context, data) {
        return new Promise((resolve, reject) => {

            validation(data)
                .then(() => {

                    let result = {};

                    if (data.checkboxActive) {
                        result.bmr = Math.round(KatchMcArdleBMR(data.weight, data.height, data.fat));

                    } else {
                        result.bmr = Math.round(HarrisBenedictBMR(data.gender, data.birthYear, data.weight, data.height));
                    }

                    let goal = determineGoal(data.weight, data.goalWeight);

                    result.bmi = parseFloat(calculateBMI(data.weight, data.height).toFixed(2));

                    result.humanWeightClassifications = determineBMICategory(result.bmi);

                    result.dailyCalories = Math.round(calculateDailyCalories(result.bmr, Number(data.activity), Number(goal)));

                    result.PFC = determineMacronutrientPercentages(data.fat, Number(data.activity), Number(goal));

                    result.daysRequired = Math.round(calculateWeightLossTime(data.weight, data.goalWeight, result.dailyCalories, result.bmr, Number(data.activity)));

                    context.commit(mutationTypes.countResults, {results: result, data: data});

                    const message = i18n.global.t('Notification.Success.Result');

                    context.dispatch('setSuccess', message, {root: true});

                    const key = context.getters[getterTypes.dataKey];

                    if (!localStorage.getItem(key)) {
                        localStorage.setItem('calculationData', JSON.stringify(data));
                    }

                    resolve(result);
                })
                .catch(error => {
                    resolve(false);  // resolve with false instead of reject to indicate validation failure
                });

            function validation(data) {
                return new Promise((resolve, reject) => {
                    let messageKey = "";

                    // Validate birthYear (reasonable age)
                    const currentYear = new Date().getFullYear();
                    if (data.birthYear < 1900 || data.birthYear > currentYear) {
                        messageKey = "Notification.Error.invalidBirthYear";
                    }

                    // Validate fat percentage (reasonable range)
                    if (data.fat < 0 || data.fat > 80) {
                        messageKey = "Notification.Error.invalidFatPercentage";
                    }

                    // Validate gender
                    if (!["male", "female"].includes(data.gender)) {
                        messageKey = "Notification.Error.invalidGender";
                    }

                    // Validate goalWeight (reasonable weight)
                    if (data.goalWeight < 10 || data.goalWeight > 300) {
                        messageKey = "Notification.Error.invalidGoalWeight";
                    }

                    // Validate height (reasonable height in cm)
                    if (data.height < 30 || data.height > 300) {
                        messageKey = "Notification.Error.invalidHeight";
                    }

                    // Validate weight (reasonable weight in kg)
                    if (data.weight < 20 || data.weight > 300) {
                        messageKey = "Notification.Error.invalidWeight";
                    }

                    if (messageKey !== "") {
                        const message = i18n.global.t("Notification.Error.invalidData");
                        context.dispatch('setError', message, {root: true});
                        reject(new Error(message));  // reject validation promise if there's an error
                    } else {
                        resolve();  // resolve validation promise if all checks pass
                    }
                });
            }





            function HarrisBenedictBMR(gender, birthYear, weight, height) {
                const currentYear = new Date().getFullYear();
                const age = currentYear - birthYear;
                let BMR;

                if (gender === 'male') {
                    BMR = 88.362 + (13.397 * weight) + (4.799 * height) - (5.677 * age);
                } else if (gender === 'female') {
                    BMR = 447.593 + (9.247 * weight) + (3.098 * height) - (4.330 * age);
                } else {
                    throw new Error('Invalid gender');
                }
                return BMR;
            }

            function KatchMcArdleBMR(weight, height, fatPercentage) {

                let LBM = (weight * (100 - fatPercentage)) / 100;

                return 370 + (21.6 * LBM);

            }

            function calculateBMI(weight, height) {
                height = height / 100;

                // Расчет ИМТ
                return weight / (height * height);

            }

            function determineBMICategory(bmi) {
                if (bmi < 18.5) {
                    return 1;
                } else if (bmi >= 18.5 && bmi <= 24.9) {
                    return 2;
                } else if (bmi >= 25 && bmi <= 29.9) {
                    return 3;
                } else if (bmi >= 30 && bmi <= 34.9) {
                    return 4;
                } else if (bmi >= 35 && bmi <= 39.9) {
                    return 5;
                } else {
                    return 6;
                }
            }

            function determineGoal(weight, goalWeight) {
                if (weight > goalWeight) {
                    return 1;
                }
                if (weight === goalWeight) {
                    return 2;
                }
                if (weight < goalWeight) {
                    return 3;
                }
            }

            function calculateDailyCalories(bmr, activityLevel, goal) {

                let dailyCalories = calculateMaintenanceCalories(bmr, activityLevel);

                switch (goal) {
                    case 1:
                        dailyCalories *= 0.8; // Уменьшение на 20%
                        break;
                    case 2:
                        break;
                    case 3:
                        dailyCalories *= 1.2; // Увеличение на 20%
                        break;
                    default:
                        throw new Error('Invalid goal');
                }

                return dailyCalories;
            }

            function calculateMaintenanceCalories(bmr, activityLevel) {
                let activityMultiplier;

                switch (activityLevel) {
                    case 1:
                        activityMultiplier = 1;
                        break;
                    case 2:
                        activityMultiplier = 1.2;
                        break;
                    case 3:
                        activityMultiplier = 1.375;
                        break;
                    case 4:
                        activityMultiplier = 1.55;
                        break;
                    case 5:
                        activityMultiplier = 1.725;
                        break;
                    default:
                        activityMultiplier = 1;
                }

                return bmr * activityMultiplier;
            }

            function determineMacronutrientPercentages(fat, activity, goal) {
                let proteinPercent;
                let fatPercent;

                if (goal === 1) { // Похудение
                    proteinPercent = 35;
                    fatPercent = fat > 25 ? 20 : 25;
                } else if (goal === 2) { // Поддержание формы
                    proteinPercent = 30;
                    fatPercent = 25;
                } else if (goal === 3) { // Набор массы
                    proteinPercent = 30;
                    fatPercent = 30;
                }

                // Корректировка для высокоактивных людей
                if (activity > 3) {
                    fatPercent = Math.max(20, fatPercent - 5);
                }
                let carbsPercent = 100 - proteinPercent - fatPercent;

                return {proteinPercent, fatPercent, carbsPercent};
            }

            function calculateWeightLossTime(currentWeight, targetWeight, dailyCalories, bmr, activity) {

                let maintenanceCalories = calculateMaintenanceCalories(bmr, activity);

                const weightLossRequired = currentWeight - targetWeight;

                const dailyCalorieDeficit = maintenanceCalories - dailyCalories;

                const daysRequired = (weightLossRequired * 7700) / dailyCalorieDeficit;

                return Math.abs(daysRequired);
            }


        })
    }
}


export default {
    state,
    actions,
    mutations,
    getters
}

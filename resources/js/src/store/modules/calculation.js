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

            const savedData = localStorage.getItem('calculationData');
            // const savedData = false;
            if (savedData) {
                console.log('Данные взяты из локального хранилища. Запрос не отправлен.');
                const parsedData = JSON.parse(savedData);
                context.commit(mutationTypes.getCalculationDataSuccess, parsedData);
                resolve(parsedData);
                return;
            }

            calculationApi.getCalculationData()
                .then(response => {
                    context.commit(mutationTypes.getCalculationDataSuccess, response.data);
                    console.log('Ответ сервера:', response.data);
                    localStorage.setItem('calculationData', JSON.stringify(response.data));
                    resolve(response.data);
                })
                .catch(error => {
                    console.log('Ошибка при получении данных:', error);
                    context.commit(mutationTypes.getCalculationDataFailure, error);
                    reject(error);
                });
        });
    },

    [actionTypes.saveCalculationData](context) {
        return new Promise((resolve, reject) => {
            const data = context.state.userData;
            const savedData = localStorage.getItem('calculationData');
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
                    console.log('Ответ сервера:', response);
                    localStorage.setItem('calculationData', currentData);
                    const message = i18n.global.t('Notification.Success.ResultWasSaved');
                    context.dispatch('setSuccess', message, { root: true });
                    resolve();
                })
                .catch(error => {
                    console.log('Ошибка при сохранении данных:', error);
                    context.commit(mutationTypes.saveCalculationDataFailure);
                    const message = i18n.global.t('Notification.Success.ResultSaveFailed');
                    context.dispatch('setError', message, { root: true });
                    reject(error);
                });
        });
    },

    [actionTypes.countResults](context, data) {
        return new Promise((resolve, reject) => {

            let result = {};

            if (data.checkboxActive) {
                result.bmr = Math.round(KatchMcArdleBMR(data.weight, data.height, data.fat));

            } else {
                result.bmr = Math.round(HarrisBenedictBMR(data.gender, data.birthYear, data.weight, data.height));
            }

            result.bmi = parseFloat(calculateBMI(data.weight, data.height).toFixed(2));

            result.humanWeightClassifications = determineBMICategory(result.bmi);

            result.dailyCalories = Math.round(calculateDailyCalories(result.bmr, Number(data.activity), Number(data.goal)));

            result.PFC = determineMacronutrientPercentages(data.fat, Number(data.activity), Number(data.goal));

            result.daysRequired = Math.round(calculateWeightLossTime(data.weight, data.goalWeight, result.dailyCalories, result.bmr, Number(data.activity)));

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

            function calculateDailyCalories(bmr, activityLevel, goal) {

                let dailyCalories = calculateMaintenanceCalories(bmr, activityLevel);

                switch (goal) {
                    case 1:
                        dailyCalories *= 0.8; // Уменьшение на 20%
                        break;
                    case 2:
                        // Никаких изменений
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
                        throw new Error('Invalid activity level');
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

            context.commit(mutationTypes.countResults, {results: result, data: data});

            resolve(result);
        })
    }
}


export default {
    state,
    actions,
    mutations,
}

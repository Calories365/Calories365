import authApi from "@/api/dairy.js";
import i18n from "@/i18n.js";

const state = {
    isSybmiting: false,
    isLoading: false,
    errors: null,
    productsInMenu: [],
    currentProducts: [],
    part_of_day: null,
    currentDate: null,
    counter: 0,
    caloriesPerDay: 0,
    caloriesPerDayPart: 0,
    dateFromCalendar: 0,
}

export const getterTypes = {
    productsInMenu: '[dairy] productsInMenu',
    currentProducts: '[dairy] currentProducts',
    part_of_day: '[dairy] part_of_day',
    currentDate: '[dairy] currentDate',
}

const getters = {
    [getterTypes.productsInMenu]: state => {
        return state.productsInMenu
    },
    [getterTypes.currentProducts]: state => {
        return state.currentProducts
    },
    [getterTypes.part_of_day]: state => {
        return state.part_of_day
    },
    [getterTypes.currentDate]: state => {
        return state.currentDate
    },
}

export const mutationTypes = {
    getPopularStart: '[dairy] getPopularStart',
    getPopularSuccess: '[dairy] getPopularSuccess',
    getPopularFailure: '[dairy] getPopularFailure',

    saveCurrentProductsStart: '[dairy] saveCurrentProductsStart',
    saveCurrentProductsSuccess: '[dairy] saveCurrentProductsSuccess',
    saveCurrentProductsFailure: '[dairy] saveCurrentProductsFailure',

    setPartOfDay: '[dairy] setPartOfDay',

    setDate: '[dairy] setDate',

    getCurrentProductsStart: '[dairy] getCurrentProductsStart',
    getCurrentProductsSuccess: '[dairy] getCurrentProductsSuccess',
    getCurrentProductsFailure: '[dairy] getCurrentProductsFailure',

    deleteCurrentProductsStart: '[dairy] deleteCurrentProductsStart',
    deleteCurrentProductsSuccess: '[dairy] deleteCurrentProductsSuccess',
    deleteCurrentProductsFailure: '[dairy] deleteCurrentProductsFailure',

    updateCurrentProductsStart: '[dairy] updateCurrentProductsStart',
    updateCurrentProductsSuccess: '[dairy] updateCurrentProductsSuccess',
    updateCurrentProductsFailure: '[dairy] updateCurrentProductsFailure',

    getSearchedProductsStart: '[dairy] getSearchedProductsStart',
    getSearchedProductsSuccess: '[dairy] getSearchedProductsSuccess',
    getSearchedProductsFailure: '[dairy] getSearchedProductsFailure',

    clearProducts: '[dairy] clearProducts',

    setCaloriesPerDay: '[dairy] setCaloriesPerDay',

    setCaloriesPerDayPart: '[dairy] setCaloriesPerDayPart',

    clearCaloriesCounter: '[dairy] clearCaloriesCounter',

    setDateFromCalendar: '[dairy] setDateFromCalendar',

    deleteDateFromCalendar: '[dairy] deleteDateFromCalendar',
}

const mutations = {
    [mutationTypes.getPopularStart](state) {
        state.isSybmiting = true;
    },
    [mutationTypes.getPopularSuccess](state, payload) {
        state.productsInMenu = [];
        state.productsInMenu = payload;
        state.isSybmiting = false;
    },
    [mutationTypes.getPopularFailure](state, payload) {
        state.errors = payload;
        state.isSybmiting = false;
    },

    [mutationTypes.saveCurrentProductsStart](state) {
        state.isSybmiting = true;
    },
    [mutationTypes.saveCurrentProductsSuccess](state, payload) {
        state.currentProducts.push(payload);
        state.isSybmiting = false;
    },
    [mutationTypes.saveCurrentProductsFailure](state, payload) {
        state.errors = payload;
        state.isSybmiting = false;
    },

    [mutationTypes.setPartOfDay](state, payload) {
        state.part_of_day = payload;
    },

    [mutationTypes.setDate](state, payload) {
        state.currentDate = payload;
    },


    [mutationTypes.getCurrentProductsStart](state) {
        state.currentProducts = [];
        state.isSybmiting = true;
    },
    [mutationTypes.getCurrentProductsSuccess](state, payload) {
        payload.forEach(product => {
            state.currentProducts.push(product);
        });
        state.isSybmiting = false;
    },
    [mutationTypes.getCurrentProductsFailure](state, payload) {
        state.errors = payload;
        state.isSybmiting = false;
    },


    [mutationTypes.deleteCurrentProductsStart](state) {
        state.isSybmiting = true;
    },
    [mutationTypes.deleteCurrentProductsSuccess](state, payload) {
        const productIdToRemove = payload.id;

        state.currentProducts = state.currentProducts.filter(product => product.id !== productIdToRemove);

        state.isSybmiting = false;
    },
    [mutationTypes.deleteCurrentProductsFailure](state, payload) {
        state.errors = payload;
        state.isSybmiting = false;
    },


    [mutationTypes.updateCurrentProductsStart](state) {
        state.isSybmiting = true;
    },
    [mutationTypes.updateCurrentProductsSuccess](state, payload) {
        const {id, quantity} = payload;

        const productIndex = state.currentProducts.findIndex(product => product.id === id);

        if (productIndex !== -1) {
            state.currentProducts[productIndex].quantity = quantity;
        }
        state.isSuccess = true;

    },
    [mutationTypes.updateCurrentProductsFailure](state, payload) {
        state.errors = payload;
        state.isSybmiting = false;
    },


    [mutationTypes.getSearchedProductsStart](state) {
        state.isSybmiting = true;
    },
    [mutationTypes.getSearchedProductsSuccess](state, payload) {
        if (payload.currentPage === 1) {
            state.productsInMenu = [];
        }
        payload.products.forEach(product => {
            state.productsInMenu.push(product);
        });
        state.isSybmiting = false;
    },
    [mutationTypes.getSearchedProductsFailure](state, payload) {
        state.errors = payload;
        state.isSybmiting = false;
    },

    [mutationTypes.clearProducts](state) {
        state.productsInMenu = [];
    },

    [mutationTypes.setCaloriesPerDay](state, payload) {
        state.caloriesPerDay = payload;
    },

    [mutationTypes.setCaloriesPerDayPart](state, payload) {
        state.caloriesPerDayPart = payload;
    },

    [mutationTypes.setDateFromCalendar](state, payload) {
        state.dateFromCalendar = payload;
    },

    [mutationTypes.deleteDateFromCalendar](state) {
        state.dateFromCalendar = null;
    },
}

export const actionTypes = {
    getPopularProducts: '[dairy] getPopularProducts',
    saveCurrentProducts: '[dairy] saveCurrentProducts',
    saveUsersCurrentProducts: '[dairy] saveUsersCurrentProducts',
    initialization: '[dairy] initialization',
    dateToggle: '[dairy] dateToggle',
    getCurrentProducts: '[dairy] getCurrentProducts',
    deleteCurrentProducts: '[dairy] deleteCurrentProducts',
    updateCurrentProducts: '[dairy] updateCurrentProducts',
    getSearchedProducts: '[dairy] getSearchedProducts',
    calculateCaloriesPerDay: '[dairy] calculateCaloriesPerDay',
    calculateCaloriesPerDayPart: '[dairy] calculateCaloriesPerDayPart',
    setPartOfDay: '[dairy] setPartOfDay',
    setDateFromCalendar: '[dairy] setDateFromCalendar',
}

const actions = {
    [actionTypes.getPopularProducts](context) {
        return new Promise(resolve => {
            const currentLocale = context.rootState.changeLocale.selectedLocale;
            const cachedDataKey = `cachedPopularProducts_${currentLocale}`;
            const cachedData = JSON.parse(localStorage.getItem(cachedDataKey));
            const currentTime = new Date().getTime();

            if (cachedData && (currentTime - cachedData.timestamp < 36000)) {
                context.commit(mutationTypes.getPopularSuccess, cachedData.popularProducts);
                resolve(cachedData.popularProducts);
                return;
            }

            context.commit(mutationTypes.getPopularStart);
            authApi.getPopularProducts(currentLocale)
                .then(response => {
                    const popularProducts = response.data;
                    context.commit(mutationTypes.getPopularSuccess, popularProducts);
                    localStorage.setItem(cachedDataKey, JSON.stringify({
                        popularProducts: popularProducts,
                        timestamp: new Date().getTime()
                    }));
                    resolve(popularProducts);
                })
                .catch(() => {
                    context.commit(mutationTypes.getPopularFailure);
                });
        })
    },
    [actionTypes.saveCurrentProducts](context, payload) {
        return new Promise(resolve => {
            context.commit(mutationTypes.saveCurrentProductsStart);
            const {product, quantity} = payload;

            const consumed_at = state.currentDate;

            const part_of_day = state.part_of_day;

            const food_id = product.id;

            authApi.saveCurrentProducts({food_id, quantity, consumed_at, part_of_day})
                .then(response => {
                    const id = response.data.id;

                    const fullProduct = {
                        ...product,
                        quantity: quantity,
                        consumed_at: consumed_at,
                        part_of_day: part_of_day,
                        id: id,
                    }

                    context.commit(mutationTypes.saveCurrentProductsSuccess, fullProduct);

                    context.dispatch(actionTypes.calculateCaloriesPerDay)
                        .then(() => {
                            const message = i18n.global.t('Notification.Success.ProductWasAdded');
                            context.dispatch('setSuccess', message, { root: true });
                            return context.dispatch(actionTypes.calculateCaloriesPerDayPart);
                        })

                    resolve(response);

                })
                .catch(error => {
                    const message = i18n.global.t('Notification.Error.ProductAdditionFailed');
                    context.dispatch('setError', message, { root: true });
                    context.commit(mutationTypes.saveCurrentProductsFailure, error);
                });
        })
    },
    [actionTypes.saveUsersCurrentProducts](context, payload) {
        return new Promise(resolve => {
            context.commit(mutationTypes.saveCurrentProductsStart);
            const {product, quantity} = payload;

            product.quantity = quantity;

            product.consumed_at = state.currentDate;

            product.part_of_day = state.part_of_day;

            authApi.saveUsersCurrentProducts(product)
                .then(response => {

                    const fullProduct = {
                        ...product,
                        quantity: quantity,
                        consumed_at: product.consumed_at,
                        part_of_day: product.part_of_day,
                        id: response.data.consumption_id,
                        food_id: response.data.food_id
                    }

                    context.commit(mutationTypes.saveCurrentProductsSuccess, fullProduct);

                    context.dispatch(actionTypes.calculateCaloriesPerDay)
                        .then(() => {
                            const message = i18n.global.t('Notification.Success.ProductWasAdded');
                            context.dispatch('setSuccess', message, {root: true});
                            return context.dispatch(actionTypes.calculateCaloriesPerDayPart);
                        })

                    resolve(response);

                })
                .catch(error => {
                    const message = i18n.global.t('Notification.Error.ProductAdditionFailed');
                    context.dispatch('setError', message, {root: true});
                    context.commit(mutationTypes.saveCurrentProductsFailure, error);
                });
        })
    },
    [actionTypes.initialization](context) {
        return new Promise(resolve => {
            let currentTime;
            if (context.state.dateFromCalendar) {
                currentTime = new Date(context.state.dateFromCalendar);
            } else {
                currentTime = new Date();
            }
            const hours = currentTime.getHours();

            const currentDate = currentTime.toISOString().split('T')[0]; // Формат YYYY-MM-DD

            let partOfTheDay;

            if (hours >= 6 && hours < 12) {
                partOfTheDay = 'morning';
            } else if (hours >= 12 && hours < 18) {
                partOfTheDay = 'dinner';
            } else {
                partOfTheDay = 'supper';
            }

            context.commit(mutationTypes.setPartOfDay, partOfTheDay);

            context.commit(mutationTypes.setDate, currentDate);

            context.dispatch(actionTypes.getCurrentProducts)
                .then(() => {
                    return context.dispatch(actionTypes.calculateCaloriesPerDay);
                })
                .then(() => {
                    return context.dispatch(actionTypes.calculateCaloriesPerDayPart);
                })
            resolve({partOfTheDay, currentDate});
        });
    },
    [actionTypes.dateToggle](context, direction) {
        return new Promise(resolve => {

            const currentDateStr = context.state.currentDate;
            const currentDate = new Date(currentDateStr);

            if (direction === 'next') {
                currentDate.setDate(currentDate.getDate() + 1);
            } else if (direction === 'previous') {
                currentDate.setDate(currentDate.getDate() - 1);
            } else {
                throw new Error('Invalid direction for dateToggle action');
            }

            const updatedDateStr = currentDate.toISOString().split('T')[0]; // Формат YYYY-MM-DD

            context.commit(mutationTypes.setDate, updatedDateStr);


            context.dispatch(actionTypes.getCurrentProducts)
                .then(() => {
                    return context.dispatch(actionTypes.calculateCaloriesPerDay);
                })
                .then(() => {
                    return context.dispatch(actionTypes.calculateCaloriesPerDayPart);
                })
                .then(() => {
                    resolve(updatedDateStr);
                })
        });
    },
    [actionTypes.getCurrentProducts](context) {
        return new Promise(resolve => {
            context.commit(mutationTypes.getCurrentProductsStart);

            const date = state.currentDate;
            authApi.getCurrentProducts(date)
                .then(response => {
                    context.commit(mutationTypes.getCurrentProductsSuccess, response.data);
                    resolve(response.data);
                })
                .catch(error => {
                    context.commit(mutationTypes.getCurrentProductsFailure, error);
                });

        })
    },
    [actionTypes.deleteCurrentProducts](context, id) {
        return new Promise(resolve => {
            context.commit(mutationTypes.deleteCurrentProductsStart);


            authApi.deleteCurrentProducts(id)
                .then(() => {
                    context.commit(mutationTypes.deleteCurrentProductsSuccess, {id: id});

                    context.dispatch(actionTypes.calculateCaloriesPerDay)
                        .then(() => {
                            const message = i18n.global.t('Notification.Success.ProductWasDeleted');
                            context.dispatch('setSuccess', message, { root: true });
                            return context.dispatch(actionTypes.calculateCaloriesPerDayPart);
                        })

                })
                .catch(error => {
                    const message = i18n.global.t('Notification.Success.ProductDeletionFailed');
                    context.dispatch('setError', message, { root: true });
                    context.commit(mutationTypes.deleteCurrentProductsFailure, error);
                });
        })
    },
    [actionTypes.updateCurrentProducts](context, payload) {
        return new Promise(resolve => {
            context.commit(mutationTypes.updateCurrentProductsStart);

            const {product, quantity} = payload;

            const id = product.id;

            authApi.updateCurrentProducts(id, quantity)
                .then(response => {
                    context.commit(mutationTypes.updateCurrentProductsSuccess, {id, quantity});
                    resolve(response);

                    context.dispatch(actionTypes.calculateCaloriesPerDay)
                        .then(() => {
                            const message = i18n.global.t('Notification.Success.ProductWasUpdated');
                            context.dispatch('setSuccess', message, { root: true });
                            return context.dispatch(actionTypes.calculateCaloriesPerDayPart);
                        })
                })
                .catch(error => {
                    const message = i18n.global.t('Notification.Success.ProductUpdateFailed');
                    context.dispatch('setError', message, { root: true });
                    context.commit(mutationTypes.updateCurrentProductsFailure, error);
                });
        })
    },
    [actionTypes.getSearchedProducts](context, payload) {
        return new Promise(resolve => {

            context.commit(mutationTypes.getSearchedProductsStart);

            const trimedQuery = payload.query.trim()

            authApi.getSearchedProducts(trimedQuery, payload.page)
                .then(response => {
                    const products = response.data.products.data;
                    const currentPage = response.data.products.current_page;
                    context.commit(mutationTypes.getSearchedProductsSuccess, {products, currentPage});
                    resolve(response.data.products.total);
                })
                .catch(error => {
                    context.commit(mutationTypes.getSearchedProductsFailure, error);
                });
        })
    },
    [actionTypes.calculateCaloriesPerDay](context) {
        return new Promise(resolve => {
            const products = context.state.currentProducts;

            let totalCalories = products.reduce((total, product) => {
                const quantity = parseFloat((product.quantity));
                const calories = product.calories;
                return total + (quantity / 100 * calories);
            }, 0);

            totalCalories = parseFloat(totalCalories.toFixed(0));

            // Math.ceil(totalCalories);x

            context.commit(mutationTypes.setCaloriesPerDay, totalCalories);

            resolve(totalCalories);
        });
    },
    [actionTypes.calculateCaloriesPerDayPart](context) {
        return new Promise(resolve => {
            const products = context.state.currentProducts;
            const partOfDay = context.state.part_of_day;

            let totalCalories = products
                .filter(product => product.day_part === partOfDay)
                .reduce((total, product) => {
                    const quantity = parseFloat(product.quantity);
                    const calories = product.calories;
                    return total + (quantity / 100 * calories);
                }, 0);

            totalCalories = parseFloat(totalCalories.toFixed(0));

            // Math.ceil(totalCalories);

            context.commit(mutationTypes.setCaloriesPerDayPart, totalCalories);

            resolve(totalCalories);
        });
    },
    [actionTypes.setPartOfDay](context, payload) {
        return new Promise(resolve => {

            context.commit(mutationTypes.setPartOfDay, payload);

            context.dispatch(actionTypes.calculateCaloriesPerDayPart);

            resolve(payload);

        });
    },
    [actionTypes.setDateFromCalendar](context, payload) {
        return new Promise(resolve => {

            context.commit(mutationTypes.setDateFromCalendar, payload);

            resolve(payload);

        });
    }
}
export default {
    state, mutations, actions, getters,
}

import authApi from "@/api/auth";
import i18n from "@/i18n.js";
// import {setItem} from "@/helpers/persistanceStorage";

const state = {
    isSybmiting: false,
    isLoading: false,
    currentUser: null,
    validationErrors: null,
    isLoggedIn: null,
}

export const getterTypes = {
    currentUser: '[auth] currentUser',
    isLoggedIn: '[auth] isLoggedIn',
    isAnonymouse: '[auth] isAnonymouse',
}

//геттеры глобальные, но в данном случае они получают локальное состояние
const getters = {
    [getterTypes.currentUser]: state => {
        return state.currentUser
    }, [getterTypes.isLoggedIn]: state => {
        return Boolean(state.isLoggedIn)
    }, [getterTypes.isAnonymouse]: state => {
        return state.isLoggedIn === false
    },
}

export const mutationTypes = {
    registerStart: '[auth] registerStart',
    registerSuccess: '[auth] registerSuccess',
    registerFailure: '[auth] registerFailure',

    loginStart: '[auth] loginStart',
    loginSuccess: '[auth] loginSuccess',
    loginFailure: '[auth] loginFailure',

    getCurrentUserStart: '[auth] getCurrentUserStart',
    getCurrentUserSuccess: '[auth] getCurrentUserSuccess',
    getCurrentUserFailure: '[auth] getCurrentUserFailure',

    updateCurrentUserStart: '[auth] updateCurrentUserStart',
    updateCurrentUserSuccess: '[auth] updateCurrentUserSuccess',
    updateCurrentUserFailure: '[auth] updateCurrentUserFailure',

    logoutStart: '[auth] logoutStart',
    logoutSuccess: '[auth] logoutSuccess',
    logoutFailure: '[auth] logoutFailure',

    recoveryStart: '[auth] recoveryStart',
    recoverySuccess: '[auth] recoverySuccess',
    recoveryFailure: '[auth] recoveryFailure',

    recoveryWithTokenStart: '[auth] recoveryWithTokenStart',
    recoveryWithTokenSuccess: '[auth] recoveryWithTokenSuccess',
    recoveryWithTokenFailure: '[auth] recoveryWithTokenFailure',

    destroyErrors: '[auth] destroyErrors',

    resendVerificationEmailStart: '[auth] resendVerificationEmailStart',
    resendVerificationEmailSuccess: '[auth] resendVerificationEmailSuccess',
    resendVerificationEmailFailure: '[auth] resendVerificationEmailFailure',

    updateUsersPasswordStart: '[auth] updateUsersPasswordStart',
    updateUsersPasswordSuccess: '[auth] updateUsersPasswordSuccess',
    updateUsersPasswordFailure: '[auth] updateUsersPasswordFailure',

    updateUsersDataStart: '[auth] updateUsersDataStart',
    updateUsersDataSuccess: '[auth] updateUsersDataSuccess',
    updateUsersDataFailure: '[auth] updateUsersDataFailure',
}

const mutations = {
    [mutationTypes.registerStart](state) {
        state.isSybmiting = true;
        state.validationErrors = null;
    }, [mutationTypes.registerSuccess](state, payload) {
        state.currentUser = payload;
        state.isLoggedIn = true;
        state.isSybmiting = false;
    }, [mutationTypes.registerFailure](state, payload) {
        state.validationErrors = payload;
        state.isSybmiting = false;
    }, [mutationTypes.loginStart](state) {
        state.isSybmiting = true;
        state.validationErrors = null;
    }, [mutationTypes.loginSuccess](state, payload) {
        state.currentUser = payload;
        state.isLoggedIn = true;
        state.isSybmiting = false;
    }, [mutationTypes.loginFailure](state, payload) {
        state.validationErrors = payload;
        state.isSybmiting = false;
    }, [mutationTypes.getCurrentUserStart](state) {
        state.isLoading = true;
    }, [mutationTypes.getCurrentUserSuccess](state, payload) {
        state.currentUser = payload;
        state.isLoading = false;
        state.isLoggedIn = true;

    }, [mutationTypes.getCurrentUserFailure](state) {
        state.isLoading = false;
        state.isLoggedIn = false;
        state.currentUser = null;
    },

    [mutationTypes.updateCurrentUserStart]() {
    }, [mutationTypes.updateCurrentUserSuccess](state, payload) {
        state.currentUser = payload;

    }, [mutationTypes.updateCurrentUserFailure]() {
    },
    [mutationTypes.logoutStart](state) {
        state.isSybmiting = true;

    }, [mutationTypes.logoutSuccess](state) {
        state.currentUser = null;
        state.isLoggedIn = false;
        state.isSybmiting = false;

    }, [mutationTypes.logoutFailure](state, payload) {
        state.isSybmiting = false;
        state.validationErrors = payload;

    }, [mutationTypes.recoveryStart](state) {
        state.isSybmiting = true;

    }, [mutationTypes.recoverySuccess](state) {
        state.isSybmiting = false;
    }, [mutationTypes.recoveryFailure](state, payload) {
        state.isSybmiting = false;
        state.validationErrors = payload;

    }, [mutationTypes.recoveryWithTokenStart](state) {
        state.isSybmiting = true;

    }, [mutationTypes.recoveryWithTokenSuccess](state) {
        state.isSybmiting = false;
    }, [mutationTypes.recoveryWithTokenFailure](state, payload) {
        state.isSybmiting = false;
        state.validationErrors = payload;
    },
    [mutationTypes.destroyErrors](state) {
        state.validationErrors = '';
    },

    [mutationTypes.resendVerificationEmailStart](state) {
        state.isSybmiting = true;
    },
    [mutationTypes.resendVerificationEmailSuccess](state) {
        state.isSybmiting = false;
    },
    [mutationTypes.resendVerificationEmailFailure](state, payload) {
        state.isSybmiting = false;
        state.validationErrors = payload;
    },


    [mutationTypes.updateUsersPasswordStart](state) {
        state.isSybmiting = true;
    },
    [mutationTypes.updateUsersPasswordSuccess](state) {
        state.isSybmiting = false;
        state.isLoggedIn = false;
    },
    [mutationTypes.updateUsersPasswordFailure](state, payload) {
        state.isSybmiting = false;
        state.validationErrors = payload;
    },

    [mutationTypes.updateUsersDataStart](state) {
        state.isSybmiting = true;
    },
    [mutationTypes.updateUsersDataSuccess](state) {
        state.isSybmiting = false;
    },
    [mutationTypes.updateUsersDataFailure](state, payload) {
        state.isSybmiting = false;
        state.validationErrors = payload;
    },


}

export const actionTypes = {
    register: '[auth] register',
    login: '[auth] login',
    getCurrentUser: '[auth] getCurrentUser',
    updateCurrentUser: '[auth] updateCurrentUser',
    logout: '[auth] logout',
    recovery: '[auth] recovery',
    recoveryWithToken: '[auth] recoveryWithToken',
    destroyErrors: '[auth] destroyErrors',
    resendVerificationEmail: '[auth] resendVerificationEmail',
    updateUsersPassword: '[auth] updateUsersPassword',
    updateUsersData: '[auth] updateUsersData'
}

const actions = {
    [actionTypes.register](context, credentials) {
        return new Promise(resolve => {
            context.commit(mutationTypes.registerStart)
            console.log(111);
            authApi.register(credentials)
                .then(response => {
                    context.commit(mutationTypes.registerSuccess, response.data.user);
                    resolve(response.data.user);
                })
                .catch(result => {
                    context.commit(mutationTypes.registerFailure, result.response.data.errors);
                    console.log('result my errors', result.response.data.errors);
                })
        })
    },
    [actionTypes.login](context, credentials) {
        return new Promise(resolve => {
            context.commit(mutationTypes.loginStart)
            authApi.login(credentials)
                .then(response => {
                    context.commit(mutationTypes.loginSuccess, response.data.user)

                    context.dispatch(actionTypes.getCurrentUser);

                    resolve(response.data)
                })
                .catch(result => {
                    context.commit(mutationTypes.loginFailure, result.response.data.errors);
                    console.log('result errors', result);
                })
        })
    },
    [actionTypes.getCurrentUser](context) {
        return new Promise(resolve => {
            context.commit(mutationTypes.getCurrentUserStart)
            console.log('getCurrentUser')
            authApi.getCurrentUser()
                .then(response => {
                    console.log('response', response);
                    context.commit(mutationTypes.getCurrentUserSuccess, response.data)
                    //сигнализирование тем, кто ожидает окончания работы промиса с передачей
                    // response.data.user для .then
                    resolve(response.data)
                })
                .catch(() => {
                    context.commit(mutationTypes.getCurrentUserFailure);
                })
        })
    },
    [actionTypes.updateCurrentUser](context, {currentUserInput}) {
        return new Promise(resolve => {
            context.commit(mutationTypes.updateCurrentUserStart)
            authApi.updateCurrentUser(currentUserInput)
                .then(user => {
                    context.commit(mutationTypes.updateCurrentUserSuccess, user)
                    //сигнализирование тем, кто ожидает окончания работы промиса с передачей
                    // response.data.user для .then
                    resolve(user)
                })
                .catch((result) => {
                    context.commit(mutationTypes.updateCurrentUserFailure, result.result.data.errors);
                })
        })
    },
    [actionTypes.logout](context) {
        return new Promise(resolve => {
            context.commit(mutationTypes.logoutStart)
            authApi.logout()
                .then(() => {
                    context.commit(mutationTypes.logoutSuccess)
                    resolve('success')
                })
                .catch((result) => {
                    context.commit(mutationTypes.logoutFailure, result.data);
                })
        })
    },
    [actionTypes.recovery](context, currentUserInput) {
        return new Promise(resolve => {
            context.commit(mutationTypes.recoveryStart)
            authApi.recovery(currentUserInput)
                .then((response) => {
                    context.commit(mutationTypes.recoverySuccess)
                    resolve(response.data)
                })
                .catch((result) => {
                    console.log('111')
                    context.commit(mutationTypes.recoverySuccess, result.data);
                })
        })
    },
    [actionTypes.recoveryWithToken](context, currentUserInput) {
        return new Promise(resolve => {
            context.commit(mutationTypes.recoveryWithTokenStart)
            authApi.recoveryWithToken(currentUserInput)
                .then((response) => {
                    context.commit(mutationTypes.recoveryWithTokenSuccess)
                    const message = i18n.global.t('Notification.Success.PasswordWasUpdated');
                    context.dispatch('setSuccess', message, {root: true})
                    resolve(response.data)
                })
                .catch((result) => {
                    console.log(result.response.data.errors)
                    const message = i18n.global.t('Notification.Error.PasswordUpdatedFailed');
                    context.dispatch('setError', message, {root: true});
                    context.commit(mutationTypes.recoveryWithTokenFailure, result.response.data.errors);
                })
        })
    },
    [actionTypes.destroyErrors](context) {
        return new Promise(resolve => {
            context.commit(mutationTypes.destroyErrors)
        })
    },
    [actionTypes.resendVerificationEmail](context, email) {
        return new Promise(resolve => {
            context.commit(mutationTypes.resendVerificationEmailStart)
            authApi.resendVerificationEmail()
                .then((response) => {
                    context.commit(mutationTypes.resendVerificationEmailSuccess)
                    resolve(response.data)
                })
                .catch((result) => {
                    console.log(result.response.data.errors)
                    context.commit(mutationTypes.resendVerificationEmailFailure, result.response.data.errors);
                })
        })
    },
    [actionTypes.updateUsersPassword](context, currentUserInput) {
        return new Promise(resolve => {
            context.commit(mutationTypes.updateUsersPasswordStart)

            console.log(currentUserInput);

            authApi.updateUsersPassword(currentUserInput)
                .then(user => {
                    context.commit(mutationTypes.updateUsersPasswordSuccess, user)

                    const message = i18n.global.t('Notification.Success.PasswordWasUpdated');
                    context.dispatch('setSuccess', message, {root: true})
                        .then(() => {
                            context.dispatch(actionTypes.logout);
                        })
                    resolve(user)
                })
                .catch((result) => {

                    const message = i18n.global.t('Notification.Error.PasswordUpdatedFailed');
                    context.dispatch('setError', message, {root: true});

                    context.commit(mutationTypes.updateUsersPasswordFailure, result.result.data.errors);
                })
        })
    },
    [actionTypes.updateUsersData](context, currentUserInput) {
        return new Promise(resolve => {
            context.commit(mutationTypes.updateUsersPasswordStart)

            authApi.updateCurrentUser(currentUserInput)
                .then(user => {
                    context.commit(mutationTypes.updateUsersPasswordSuccess, user)

                    const message = i18n.global.t('Notification.Success.NameWasUpdated');
                    context.dispatch('setSuccess', message, {root: true});

                    resolve(user)
                })
                .catch((result) => {

                    const message = i18n.global.t('Notification.Error.NameUpdationFiled');
                    context.dispatch('setError', message, {root: true});

                    context.commit(mutationTypes.updateUsersPasswordFailure, result.result.data.errors);
                })
        })
    },


}
export default {
    state, mutations, actions, getters,
}




import axios from "@/api/axios";

const register = (credentials) => {
    return axios.post("/register", credentials);
};
const login = (credentials) => {
    return axios.post("/login", credentials);
};
const getCurrentUser = () => {
    return axios.get("/api/user");
};
const logout = () => {
    return axios.post("/logout");
};
const recovery = (credentials) => {
    return axios.post("/forgot-password", credentials);
};
const recoveryWithToken = (credentials) => {
    return axios.post("/reset-password", credentials);
};
const resendVerificationEmail = () => {
    return axios.post("email/verification-notification");
};
const updateUsersPassword = (credentials) => {
    return axios.put("/user/password", credentials);
};
const updateCurrentUser = (credentials) => {
    return axios.put("/user/profile-information", credentials);
};
const getTelegramLink = () => {
    const locale = localStorage.getItem("locale") || "ua";
    return axios.get("/api/telegram-link", { params: { locale } });
};
const buyPremium = () => {
    return axios.post("/api/buy-premium");
};
const cancelPremium = () => {
    return axios.post("/api/cancel-premium");
};

export default {
    register,
    login,
    getCurrentUser,
    logout,
    recovery,
    recoveryWithToken,
    resendVerificationEmail,
    updateUsersPassword,
    updateCurrentUser,
    getTelegramLink,
    buyPremium,
    cancelPremium,
};

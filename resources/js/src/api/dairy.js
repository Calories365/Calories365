import axios from "@/api/axios";

const getPopularProducts = () => {
    return axios.get("/api/products/popular");
};
const saveCurrentProducts = (credentials) => {
    return axios.post("/api/meals", credentials);
};
const getCurrentProducts = (date) => {
    return axios.get(`/api/meals/${date}`);
};
const deleteCurrentProducts = (id) => {
    return axios.delete(`/api/meals/${id}`);
};
const updateCurrentProducts = (id, quantity) => {
    return axios.put(`/api/meals/${id}`, { quantity });
};

const getSearchedProducts = (query, page = 1) => {
    return axios.get(`/api/products/search`, {
        params: {
            query: query,
            page: page,
        },
    });
};

const saveUsersCurrentProducts = (product) => {
    return axios.post(`/api/user-meals`, product);
};

const getFeedback = (date, part_of_day = null) => {
    const params = { date };
    if (part_of_day) {
        params.part_of_day = part_of_day;
    }
    return axios.get("/api/getFeedback", { params });
};

const getFeedbackStatus = ({ rid, date, part_of_day } = {}) => {
    const params = { rid };
    if (date) params.date = date;
    if (part_of_day) params.part_of_day = part_of_day;
    return axios.get("/api/getFeedback/status", { params });
};

export default {
    getPopularProducts,
    saveCurrentProducts,
    getCurrentProducts,
    deleteCurrentProducts,
    updateCurrentProducts,
    getSearchedProducts,
    saveUsersCurrentProducts,
    getFeedback,
    getFeedbackStatus,
};

import axios from "@/api/axios";

const getPopularProducts = () => {
    return axios.get('/api/products/popular');
}
const saveCurrentProducts = credentials => {
    return axios.post('/api/meals', credentials)
}
const getCurrentProducts = date => {
    return axios.get(`/api/meals/${date}`)
}
const deleteCurrentProducts = id => {
    return axios.delete(`/api/meals/${id}`);
}
const updateCurrentProducts = (id, quantity) => {
    return axios.put(`/api/meals/${id}`, {quantity});
}

const getSearchedProducts = (query, page = 1) => {
    return axios.get(`/api/products/search`, {
        params: {
            query: query,
            page: page
        }
    });
}

const saveUsersCurrentProducts = (product) => {
    return axios.post(`/api/user-meals`, product);
}


export default {
    getPopularProducts,
    saveCurrentProducts,
    getCurrentProducts,
    deleteCurrentProducts,
    updateCurrentProducts,
    getSearchedProducts,
    saveUsersCurrentProducts
}

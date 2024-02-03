import axios from "@/api/axios";

// const getPopularProducts = () => {
//     return axios.get('/api/getPopularProducts')
// }

const getPopularProducts = () => {
    const response = axios.get('/api/getPopularProducts');
    console.log('response ', response);
    return response;
}
const saveCurrentProducts = credentials => {
    return axios.post('/api/saveMeal', credentials)
}
const getCurrentProducts = credentials => {
    return axios.post('/api/getMeal', credentials)
}
const deleteCurrentProducts = id => {
    return axios.delete(`/api/deleteMeal/${id}`);
}
const updateCurrentProducts = credentials => {
    return axios.post('/api/updateMeal', credentials)
}
const getSearchedProducts = (query, page = 1) => {
    return axios.get(`/api/getSearchedMeal`, {
        params: {
            query: query,
            page: page
        }
    });
}


export default {
    getPopularProducts,
    saveCurrentProducts,
    getCurrentProducts,
    deleteCurrentProducts,
    updateCurrentProducts,
    getSearchedProducts
}

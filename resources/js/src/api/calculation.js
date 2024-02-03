import axios from "@/api/axios";

const getCalculationData = credentials => {
    return axios.get('/api/get-calculation-data', credentials)
}
const saveCalculationData = credentials => {
    return axios.post('/api/save-calculation-data', credentials)
}

export default {
    getCalculationData, saveCalculationData
}

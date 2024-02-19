import axios from "@/api/axios";

const getCalculationData = credentials => {
    return axios.get('/api/calculations', credentials)
}
const saveCalculationData = credentials => {
    return axios.post('/api/calculations', credentials)
}

export default {
    getCalculationData, saveCalculationData
}

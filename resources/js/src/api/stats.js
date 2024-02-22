import axios from "@/api/axios";

const getCurrentData = date => {
    return axios.get(`/api/calendar/${date}`)
}
export default {
    getCurrentData
}

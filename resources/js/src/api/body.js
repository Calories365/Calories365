import axios from "@/api/axios";

// The body weight and the activities of a day: { weight, activities, calories }.

export const getBodyDay = (date) => axios.get(`/api/body/${date}`);

export const saveWeight = (date, weight) =>
    axios.post("/api/body/weight", { date, weight });

export const removeActivity = (id) =>
    axios.delete(`/api/body/activities/${id}`);

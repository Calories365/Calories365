import axios from "@/api/axios";

// The estimated insulin curve of a day: { points, zones, now, peak, meals, activities, lifestyle }.

export const getInsulinDay = (date) => axios.get(`/api/insulin/${date}`);

export const saveLifestyle = (date, lifestyle) =>
    axios.put("/api/insulin/lifestyle", { date, lifestyle });

export const saveMealTime = (date, partOfDay, time) =>
    axios.put("/api/insulin/meal-time", {
        date,
        part_of_day: partOfDay,
        time,
    });

export const addActivity = (activity) =>
    axios.post("/api/body/activities", activity);

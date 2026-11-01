import axios from "@/api/axios";

// Transcribing a recording and reading it take a model call each.
const SAY_TIMEOUT_MS = 90000;

// Sugar and insulin of a day: { points, glucose, zones, now, peak, meals,
// activities, summary, tips, lifestyle }.

export const getInsulinDay = (date) => axios.get(`/api/insulin/${date}`);

export const saveLifestyle = (date, lifestyle) =>
    axios.put("/api/insulin/lifestyle", { date, lifestyle });

export const saveMealTime = (date, partOfDay, time) =>
    axios.put("/api/insulin/meal-time", {
        date,
        part_of_day: partOfDay,
        time,
    });

// The kinds of movement to tap: { types: [{ type, title, emoji, met, minutes }] }.
export const getActivityTypes = () => axios.get("/api/activity-types");

// A kind tapped ({ date, type, minutes, time }) or typed ({ date, title, intensity, minutes, time }).
export const addActivity = (activity) =>
    axios.post("/api/body/activities", activity);

export const updateActivity = (id, changes) =>
    axios.patch(`/api/body/activities/${id}`, changes);

// Movement told in words or recorded: { text, understood, recorded }.
export const sayActivity = (text) =>
    axios.post("/api/body/activities/say", { text }, { timeout: SAY_TIMEOUT_MS });

export const sayActivityByVoice = (blob) => {
    const form = new FormData();
    const extension = (blob.type || "").includes("mp4") ? "m4a" : "webm";
    form.append("audio", blob, `activity.${extension}`);

    return axios.post("/api/body/activities/say", form, { timeout: SAY_TIMEOUT_MS });
};

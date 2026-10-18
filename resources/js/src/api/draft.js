import axios from "@/api/axios";

// The meal draft of the "Приём пищи" page, shared with the Telegram bot. Every
// answer carries the whole draft ({ draft }), or null when there is none; the
// inputs waiting for the calculation ({ inputs }) and its state ({ calculation }).

// Transcribing a recording or sending a photo over a slow network takes a while.
const INPUT_TIMEOUT_MS = 90000;

export const getDraft = () => axios.get("/api/draft");

export const collectText = (text) =>
    axios.post("/api/draft/inputs", { text }, { timeout: INPUT_TIMEOUT_MS });

export const collectPhoto = (file) => {
    const form = new FormData();
    form.append("photo", file);

    return axios.post("/api/draft/inputs", form, { timeout: INPUT_TIMEOUT_MS });
};

export const collectVoice = (blob) => {
    const form = new FormData();
    const extension = (blob.type || "").includes("mp4") ? "m4a" : "webm";
    form.append("audio", blob, `record.${extension}`);

    return axios.post("/api/draft/inputs", form, { timeout: INPUT_TIMEOUT_MS });
};

export const getInputPhoto = (id) =>
    axios.get(`/api/draft/inputs/${id}/photo`, { responseType: "blob" });

export const removeInput = (id) => axios.delete(`/api/draft/inputs/${id}`);

// Answers at once: the calculation runs in the queue, getDraft tells when it is done.
export const calculateDraft = () => axios.post("/api/draft/calculate");

export const updateDraftItem = (id, changes) =>
    axios.patch(`/api/draft/items/${id}`, changes);

export const removeDraftItem = (id) => axios.delete(`/api/draft/items/${id}`);

export const saveDraft = (partOfDay, consumedAt) =>
    axios.post("/api/draft/save", {
        part_of_day: partOfDay,
        consumed_at: consumedAt,
    });

export const cancelDraft = () => axios.delete("/api/draft");

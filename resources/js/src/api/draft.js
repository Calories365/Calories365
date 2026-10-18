import axios from "@/api/axios";

// The meal draft of the "Приём пищи" page, shared with the Telegram bot. Every
// answer carries the whole draft ({ draft }), or null when there is none.

// Reading a photo or a recording with the model takes several seconds.
const INPUT_TIMEOUT_MS = 90000;

export const getDraft = () => axios.get("/api/draft");

export const sendDraftText = (text) =>
    axios.post("/api/draft/input", { text }, { timeout: INPUT_TIMEOUT_MS });

export const sendDraftPhoto = (file, text = "") => {
    const form = new FormData();
    form.append("photo", file);
    if (text) {
        form.append("text", text);
    }

    return axios.post("/api/draft/input", form, { timeout: INPUT_TIMEOUT_MS });
};

export const sendDraftVoice = (blob) => {
    const form = new FormData();
    const extension = (blob.type || "").includes("mp4") ? "m4a" : "webm";
    form.append("audio", blob, `record.${extension}`);

    return axios.post("/api/draft/input", form, { timeout: INPUT_TIMEOUT_MS });
};

export const updateDraftItem = (id, changes) =>
    axios.patch(`/api/draft/items/${id}`, changes);

export const removeDraftItem = (id) => axios.delete(`/api/draft/items/${id}`);

export const saveDraft = (partOfDay, consumedAt) =>
    axios.post("/api/draft/save", {
        part_of_day: partOfDay,
        consumed_at: consumedAt,
    });

export const cancelDraft = () => axios.delete("/api/draft");

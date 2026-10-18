<script>
import { FontAwesomeIcon } from "@fortawesome/vue-fontawesome";
import {
    faCalculator,
    faCamera,
    faChevronDown,
    faChevronUp,
    faCommentDots,
    faMicrophone,
    faPaperPlane,
    faSpinner,
    faStop,
    faTrash,
    faXmark,
} from "@fortawesome/free-solid-svg-icons";
import {
    calculateDraft,
    cancelDraft,
    collectPhoto,
    collectText,
    collectVoice,
    getDraft,
    getInputPhoto,
    removeDraftItem,
    removeInput,
    saveDraft,
    updateDraftItem,
} from "@/api/draft.js";
import { resizeImage } from "@/helpers/resizeImage.js";
import { suggestedMeals } from "@/helpers/mealTime.js";
import { toLocalDateString } from "@/helpers/localDate.js";
import CaloriesSuccessNotification from "@/Components/CaloriesSuccessNotification.vue";
import CaloriesErrorNotification from "@/Components/CaloriesErrorNotification.vue";

// Errors of the draft API and the text that explains each.
const ERRORS = {
    voice_rate_limited: "Meal.errors.rateLimited",
    voice_service_unavailable: "Meal.errors.unavailable",
    draft_input_failed: "Meal.errors.notKept",
    voice_not_recognized: "Meal.errors.notHeard",
    draft_not_found: "Meal.errors.gone",
    too_many_inputs: "Meal.errors.tooMany",
};

const FACTS = ["calories", "proteins", "fats", "carbohydrates"];

// How often the page asks whether the calculation is done, and for how long.
const POLL_MS = 2000;
const POLL_LIMIT_MS = 240000;

// Telegram keeps a voice message short; this bitrate keeps a minute of speech
// near 250 KB.
const AUDIO_BITS_PER_SECOND = 32000;

/**
 * The meal being put together: the same draft the Telegram bot shows. Voice
 * messages, photos of the dish and of labels, and texts wait in a list until
 * "Посчитать" sends all of them to the model at once; the draft below corrects
 * the result and saves it to the diary.
 */
export default {
    name: "Meal",
    components: {
        FontAwesomeIcon,
        CaloriesSuccessNotification,
        CaloriesErrorNotification,
    },
    data() {
        return {
            draft: null,
            inputs: [],
            photoUrls: {},
            notFound: [],
            text: "",
            loading: true,
            busy: false,
            uploading: null,
            calculating: false,
            recording: false,
            canRecord: false,
            mediaRecorder: null,
            stream: null,
            chunks: [],
            pollTimer: null,
            expanded: {},
            successMessage: "",
            errorMessage: "",
            icons: {
                faCalculator,
                faCamera,
                faChevronDown,
                faChevronUp,
                faCommentDots,
                faMicrophone,
                faPaperPlane,
                faSpinner,
                faStop,
                faTrash,
                faXmark,
            },
            facts: FACTS,
        };
    },
    computed: {
        items() {
            return this.draft?.items ?? [];
        },
        meals() {
            return suggestedMeals(new Date().getHours());
        },
        locked() {
            return this.busy || this.calculating || this.uploading !== null;
        },
    },
    async created() {
        this.canRecord =
            typeof window !== "undefined" &&
            Boolean(navigator.mediaDevices?.getUserMedia) &&
            typeof window.MediaRecorder !== "undefined";

        try {
            const { data } = await getDraft();
            this.show(data);

            // The bot or another tab may have started it.
            if (data.calculation?.status === "running") {
                this.waitForCalculation();
            }
        } catch (error) {
            this.fail(error);
        } finally {
            this.loading = false;
        }
    },
    beforeUnmount() {
        this.releaseMicrophone();
        clearTimeout(this.pollTimer);
        Object.values(this.photoUrls).forEach((url) =>
            URL.revokeObjectURL(url)
        );
    },
    methods: {
        round(value, digits = 0) {
            const factor = 10 ** digits;

            return Math.round((Number(value) || 0) * factor) / factor;
        },
        notify(kind, message) {
            this[kind] = message;
            setTimeout(() => {
                this[kind] = "";
            }, 4000);
        },
        fail(error) {
            const code = error?.response?.data?.message;
            const key =
                error?.response?.status === 413
                    ? "Meal.errors.tooLarge"
                    : ERRORS[code] ?? "Meal.errors.generic";
            this.notify("errorMessage", this.$t(key));
        },
        /**
         * Show what an answer of the draft API carries.
         */
        show(data) {
            this.draft = data.draft;
            this.inputs = data.inputs ?? [];
            this.loadPhotos();
        },
        async loadPhotos() {
            const waiting = new Set(this.inputs.map((input) => input.id));

            Object.keys(this.photoUrls).forEach((id) => {
                if (!waiting.has(Number(id))) {
                    URL.revokeObjectURL(this.photoUrls[id]);
                    delete this.photoUrls[id];
                }
            });

            for (const input of this.inputs) {
                if (input.kind !== "photo" || this.photoUrls[input.id]) {
                    continue;
                }

                try {
                    const { data } = await getInputPhoto(input.id);
                    this.photoUrls = {
                        ...this.photoUrls,
                        [input.id]: URL.createObjectURL(data),
                    };
                } catch (error) {
                    // The list shows the photo icon instead.
                }
            }
        },
        /**
         * Send an input to the list; a short edit ("2 250") changes the draft at once.
         */
        async collect(request) {
            this.busy = true;

            try {
                const { data } = await request();
                this.show(data);
                this.notFound = data.not_found ?? [];
            } catch (error) {
                this.fail(error);
            } finally {
                this.busy = false;
            }
        },
        sendText() {
            const text = this.text.trim();

            if (!text || this.locked) {
                return;
            }

            this.text = "";
            this.collect(() => collectText(text));
        },
        async sendPhotos(event) {
            const files = Array.from(event.target.files ?? []);
            event.target.value = "";

            if (!files.length || this.locked) {
                return;
            }

            for (const [index, file] of files.entries()) {
                this.uploading = { current: index + 1, total: files.length };

                try {
                    const { data } = await collectPhoto(
                        await resizeImage(file)
                    );
                    this.show(data);
                } catch (error) {
                    this.fail(error);
                    break;
                }
            }

            this.uploading = null;
        },
        async toggleRecording() {
            if (this.recording) {
                this.mediaRecorder?.stop();
                return;
            }

            try {
                this.stream = await navigator.mediaDevices.getUserMedia({
                    audio: true,
                });
            } catch (error) {
                this.notify("errorMessage", this.$t("Meal.errors.mic"));
                return;
            }

            const mimeType = [
                "audio/mp4",
                "audio/webm;codecs=opus",
                "audio/webm",
            ].find((type) => MediaRecorder.isTypeSupported(type));
            this.chunks = [];
            this.mediaRecorder = new MediaRecorder(this.stream, {
                ...(mimeType ? { mimeType } : {}),
                audioBitsPerSecond: AUDIO_BITS_PER_SECOND,
            });
            this.mediaRecorder.ondataavailable = (event) => {
                if (event.data.size > 0) {
                    this.chunks.push(event.data);
                }
            };
            this.mediaRecorder.onstop = () => {
                this.recording = false;
                const blob = new Blob(this.chunks, {
                    type:
                        this.mediaRecorder.mimeType || mimeType || "audio/webm",
                });
                this.releaseMicrophone();
                this.collect(() => collectVoice(blob));
            };
            this.mediaRecorder.start();
            this.recording = true;
        },
        releaseMicrophone() {
            this.stream?.getTracks().forEach((track) => track.stop());
            this.stream = null;
        },
        async dropInput(input) {
            try {
                const { data } = await removeInput(input.id);
                this.show(data);
            } catch (error) {
                this.fail(error);
            }
        },
        async calculate() {
            if (!this.inputs.length || this.locked) {
                return;
            }

            this.calculating = true;
            this.notFound = [];

            try {
                const { data } = await calculateDraft();
                this.finishOrWait(data);
            } catch (error) {
                this.calculating = false;
                this.fail(error);
            }
        },
        waitForCalculation(startedAt = Date.now()) {
            this.calculating = true;
            clearTimeout(this.pollTimer);

            this.pollTimer = setTimeout(async () => {
                try {
                    const { data } = await getDraft();
                    this.finishOrWait(data, startedAt);
                } catch (error) {
                    this.calculating = false;
                    this.fail(error);
                }
            }, POLL_MS);
        },
        finishOrWait(data, startedAt = Date.now()) {
            const state = data.calculation;

            if (state?.status === "running") {
                if (Date.now() - startedAt < POLL_LIMIT_MS) {
                    this.waitForCalculation(startedAt);
                    return;
                }

                this.calculating = false;
                this.notify("errorMessage", this.$t("Meal.errors.notRead"));
                return;
            }

            this.calculating = false;
            this.show(data);

            if (state?.status === "failed") {
                this.notify("errorMessage", this.$t("Meal.errors.notRead"));
            } else if (state?.status === "done") {
                this.notFound = state.not_found ?? [];

                if (!state.understood) {
                    this.notify("errorMessage", this.$t("Meal.notUnderstood"));
                }
            }
        },
        async change(item, changes) {
            try {
                const { data } = await updateDraftItem(item.id, changes);
                this.draft = data.draft;
            } catch (error) {
                this.fail(error);
            }
        },
        changeGrams(item, event) {
            const grams = Math.round(Number(event.target.value));

            if (!grams || grams < 1 || grams > 5000) {
                event.target.value = item.grams;
                return;
            }

            if (grams !== item.grams) {
                this.change(item, { grams });
            }
        },
        changeFact(item, fact, event) {
            const value = Number(event.target.value);
            const max = fact === "calories" ? 950 : 100;

            if (
                event.target.value === "" ||
                Number.isNaN(value) ||
                value < 0 ||
                value > max
            ) {
                event.target.value = item.per_100g[fact];
                return;
            }

            if (value !== item.per_100g[fact]) {
                this.change(item, { [fact]: value });
            }
        },
        toggleFacts(item) {
            this.expanded = {
                ...this.expanded,
                [item.id]: !this.expanded[item.id],
            };
        },
        async remove(item) {
            try {
                const { data } = await removeDraftItem(item.id);
                this.draft = data.draft;
            } catch (error) {
                this.fail(error);
            }
        },
        async save(partOfDay) {
            this.busy = true;

            try {
                const { data } = await saveDraft(
                    partOfDay,
                    toLocalDateString(new Date())
                );
                this.draft = null;
                this.notFound = [];
                this.notify(
                    "successMessage",
                    this.$t("Meal.saved", {
                        calories: this.round(data.total.calories),
                    })
                );
            } catch (error) {
                this.fail(error);
            } finally {
                this.busy = false;
            }
        },
        async cancel() {
            try {
                const { data } = await cancelDraft();
                this.show(data);
                this.notFound = [];
                this.notify("successMessage", this.$t("Meal.cancelled"));
            } catch (error) {
                this.fail(error);
            }
        },
    },
};
</script>

<template>
    <div class="meal-page">
        <div class="meal">
            <h1 class="meal_title">{{ $t("Meal.title") }}</h1>
            <p class="meal_lead">{{ $t("Meal.lead") }}</p>

            <CaloriesSuccessNotification v-if="successMessage">
                {{ successMessage }}
            </CaloriesSuccessNotification>
            <CaloriesErrorNotification v-if="errorMessage">
                {{ errorMessage }}
            </CaloriesErrorNotification>

            <div class="meal_input">
                <div class="meal_buttons">
                    <button
                        v-if="canRecord"
                        type="button"
                        class="meal_button"
                        :class="{ 'meal_button--recording': recording }"
                        :disabled="locked"
                        @click="toggleRecording"
                    >
                        <FontAwesomeIcon
                            :icon="
                                recording ? icons.faStop : icons.faMicrophone
                            "
                        />
                        <span>{{
                            recording ? $t("Meal.stop") : $t("Meal.record")
                        }}</span>
                    </button>
                    <label
                        class="meal_button meal_button--photo"
                        :class="{
                            'meal_button--disabled': locked || recording,
                        }"
                    >
                        <input
                            type="file"
                            accept="image/*"
                            multiple
                            class="meal_file"
                            :disabled="locked || recording"
                            @change="sendPhotos"
                        />
                        <FontAwesomeIcon :icon="icons.faCamera" />
                        <span>{{ $t("Meal.photo") }}</span>
                    </label>
                </div>

                <form class="meal_text" @submit.prevent="sendText">
                    <input
                        v-model="text"
                        type="text"
                        maxlength="2000"
                        :aria-label="$t('Meal.textLabel')"
                        :placeholder="$t('Meal.textHint')"
                        :disabled="locked"
                    />
                    <button
                        type="submit"
                        :disabled="locked || !text.trim()"
                        :title="$t('Meal.send')"
                    >
                        <FontAwesomeIcon :icon="icons.faPaperPlane" />
                    </button>
                </form>

                <p v-if="recording" class="meal_status">
                    {{ $t("Meal.listening") }}
                </p>
                <p v-else-if="uploading" class="meal_status">
                    <FontAwesomeIcon :icon="icons.faSpinner" spin />
                    {{ $t("Meal.uploading", uploading) }}
                </p>
                <p v-else-if="busy" class="meal_status">
                    <FontAwesomeIcon :icon="icons.faSpinner" spin />
                    {{ $t("Meal.keeping") }}
                </p>
            </div>

            <div v-if="inputs.length" class="meal_inputs">
                <h2 class="meal_inputs-title">{{ $t("Meal.inputsTitle") }}</h2>
                <ul class="meal_inputs-list">
                    <li
                        v-for="input in inputs"
                        :key="input.id"
                        class="meal-input"
                    >
                        <img
                            v-if="input.kind === 'photo' && photoUrls[input.id]"
                            :src="photoUrls[input.id]"
                            class="meal-input_photo"
                            alt=""
                        />
                        <span v-else class="meal-input_icon">
                            <FontAwesomeIcon
                                :icon="
                                    input.kind === 'photo'
                                        ? icons.faCamera
                                        : input.kind === 'voice'
                                        ? icons.faMicrophone
                                        : icons.faCommentDots
                                "
                            />
                        </span>
                        <span class="meal-input_text">{{
                            input.text || $t(`Meal.inputKinds.${input.kind}`)
                        }}</span>
                        <button
                            type="button"
                            class="meal-input_remove"
                            :title="$t('Meal.remove')"
                            :disabled="calculating"
                            @click="dropInput(input)"
                        >
                            <FontAwesomeIcon :icon="icons.faXmark" />
                        </button>
                    </li>
                </ul>
                <button
                    type="button"
                    class="meal_calculate"
                    :disabled="locked || recording"
                    @click="calculate"
                >
                    <FontAwesomeIcon
                        :icon="
                            calculating ? icons.faSpinner : icons.faCalculator
                        "
                        :spin="calculating"
                    />
                    {{
                        calculating
                            ? $t("Meal.calculating")
                            : draft
                            ? $t("Meal.recalculate")
                            : $t("Meal.calculate")
                    }}
                </button>
                <p class="meal_inputs-hint">
                    {{
                        calculating
                            ? $t("Meal.calculatingHint")
                            : $t("Meal.inputsHint")
                    }}
                </p>
            </div>

            <div v-if="items.length" class="meal_draft">
                <div v-for="item in items" :key="item.id" class="meal-item">
                    <div class="meal-item_head">
                        <span class="meal-item_number">{{ item.number }}</span>
                        <span class="meal-item_name">{{ item.name }}</span>
                        <span v-if="item.edited" class="meal-item_tag">{{
                            $t("Meal.edited")
                        }}</span>
                        <span
                            v-else-if="item.source === 'label'"
                            class="meal-item_tag"
                            >{{ $t("Meal.label") }}</span
                        >
                        <button
                            type="button"
                            class="meal-item_remove"
                            :title="$t('Meal.remove')"
                            @click="remove(item)"
                        >
                            <FontAwesomeIcon :icon="icons.faTrash" />
                        </button>
                    </div>

                    <div class="meal-item_portion">
                        <label class="meal-item_grams">
                            <input
                                type="number"
                                inputmode="numeric"
                                min="1"
                                max="5000"
                                :value="item.grams"
                                @change="changeGrams(item, $event)"
                            />
                            {{ $t("Meal.grams") }}
                        </label>
                        <span class="meal-item_calories"
                            >{{ round(item.portion.calories) }}
                            {{ $t("Meal.kcal") }}</span
                        >
                        <span class="meal-item_macros">
                            {{
                                $t("Meal.macros", {
                                    proteins: round(item.portion.proteins, 1),
                                    fats: round(item.portion.fats, 1),
                                    carbohydrates: round(
                                        item.portion.carbohydrates,
                                        1
                                    ),
                                })
                            }}
                        </span>
                    </div>

                    <button
                        type="button"
                        class="meal-item_toggle"
                        @click="toggleFacts(item)"
                    >
                        {{ $t("Meal.per100") }}
                        <FontAwesomeIcon
                            :icon="
                                expanded[item.id]
                                    ? icons.faChevronUp
                                    : icons.faChevronDown
                            "
                        />
                    </button>

                    <div v-if="expanded[item.id]" class="meal-item_facts">
                        <label
                            v-for="fact in facts"
                            :key="fact"
                            class="meal-item_fact"
                        >
                            <span>{{ $t(`Meal.${fact}`) }}</span>
                            <input
                                type="number"
                                inputmode="decimal"
                                min="0"
                                step="0.1"
                                :value="item.per_100g[fact]"
                                @change="changeFact(item, fact, $event)"
                            />
                        </label>
                        <p class="meal-item_hint">{{ $t("Meal.factsHint") }}</p>
                    </div>
                </div>

                <p v-if="notFound.length" class="meal_not-found">
                    {{ $t("Meal.notFound", { names: notFound.join(", ") }) }}
                </p>

                <p class="meal_total">
                    {{
                        $t("Meal.total", {
                            calories: round(draft.total.calories),
                        })
                    }}
                    <span>
                        {{
                            $t("Meal.macros", {
                                proteins: round(draft.total.proteins),
                                fats: round(draft.total.fats),
                                carbohydrates: round(draft.total.carbohydrates),
                            })
                        }}
                    </span>
                </p>

                <div class="meal_save">
                    <button
                        v-for="(meal, index) in meals"
                        :key="meal"
                        type="button"
                        class="meal_save-button"
                        :class="{ 'meal_save-button--main': index === 0 }"
                        :disabled="locked"
                        @click="save(meal)"
                    >
                        {{ $t(`Meal.saveTo.${meal}`) }}
                    </button>
                    <button
                        type="button"
                        class="meal_cancel"
                        :disabled="locked"
                        @click="cancel"
                    >
                        {{ $t("Meal.cancel") }}
                    </button>
                </div>
            </div>

            <div
                v-else-if="!loading && !locked && !inputs.length"
                class="meal_how-to"
            >
                <h2>{{ $t("Meal.howTo.title") }}</h2>
                <ol>
                    <li>{{ $t("Meal.howTo.say") }}</li>
                    <li>{{ $t("Meal.howTo.photo") }}</li>
                    <li>{{ $t("Meal.howTo.calculate") }}</li>
                    <li>{{ $t("Meal.howTo.correct") }}</li>
                    <li>{{ $t("Meal.howTo.save") }}</li>
                </ol>
                <p>{{ $t("Meal.howTo.bot") }}</p>
            </div>
        </div>
    </div>
</template>

<style scoped lang="scss">
.meal-page {
    padding: 16px;
}

.meal {
    max-width: 720px;
    margin: 0 auto;
    padding: 20px;
    color: #333;
    background: #fff;
    border-radius: 12px;
    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.08);

    &_title {
        margin: 0 0 8px;
        font-size: 1.6rem;
        text-align: center;
    }

    &_lead {
        margin: 0 0 20px;
        text-align: center;
        color: #666;
        line-height: 1.5;
    }

    &_input {
        display: flex;
        flex-direction: column;
        gap: 12px;
        margin-bottom: 20px;
    }

    &_buttons {
        display: flex;
        gap: 12px;
    }

    &_button {
        flex: 1;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 10px;
        min-height: 56px;
        padding: 12px 16px;
        font-size: 1.1rem;
        font-weight: 600;
        color: #fff;
        background: $main-color;
        border: none;
        border-radius: 14px;
        cursor: pointer;

        &--photo {
            color: $main-color;
            background: #eef7ee;
        }

        &--recording {
            background: #e53935;
            animation: meal-pulse 1.4s infinite;
        }

        &:disabled,
        &--disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }
    }

    &_file {
        display: none;
    }

    &_text {
        display: flex;
        gap: 8px;

        input {
            flex: 1;
            min-width: 0;
            padding: 12px 14px;
            font-size: 1rem;
            color: inherit;
            background: #f5f5f5;
            border: 1px solid transparent;
            border-radius: 12px;
        }

        button {
            width: 48px;
            color: #fff;
            background: $main-color;
            border: none;
            border-radius: 12px;
            cursor: pointer;

            &:disabled {
                opacity: 0.4;
                cursor: not-allowed;
            }
        }
    }

    &_status {
        margin: 0;
        font-size: 0.95rem;
        color: #666;
        text-align: center;
    }

    &_inputs {
        margin-bottom: 20px;
        padding: 16px;
        background: #f7f9f7;
        border-radius: 12px;
    }

    &_inputs-title {
        margin: 0 0 8px;
        font-size: 1.1rem;
    }

    &_inputs-list {
        margin: 0 0 12px;
        padding: 0;
        list-style: none;
    }

    &_calculate {
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 10px;
        width: 100%;
        min-height: 52px;
        font-size: 1.1rem;
        font-weight: 600;
        color: #fff;
        background: $main-color;
        border: none;
        border-radius: 14px;
        cursor: pointer;

        &:disabled {
            opacity: 0.6;
            cursor: not-allowed;
        }
    }

    &_inputs-hint {
        margin: 8px 0 0;
        font-size: 0.9rem;
        line-height: 1.4;
        color: #666;
        text-align: center;
    }

    &_not-found {
        margin: 8px 0;
        color: #c62828;
    }

    &_total {
        display: flex;
        flex-wrap: wrap;
        justify-content: space-between;
        gap: 8px;
        margin: 16px 0;
        font-size: 1.15rem;
        font-weight: 700;

        span {
            font-weight: 400;
            color: #666;
        }
    }

    &_save {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 8px;
    }

    &_save-button {
        padding: 14px 8px;
        font-size: 1rem;
        font-weight: 600;
        color: $main-color;
        background: #eef7ee;
        border: none;
        border-radius: 12px;
        cursor: pointer;

        &--main {
            color: #fff;
            background: $main-color;
        }
    }

    &_cancel {
        grid-column: 1 / -1;
        padding: 10px;
        color: #888;
        background: none;
        border: none;
        cursor: pointer;
    }

    &_how-to {
        padding: 16px;
        background: #f7f9f7;
        border-radius: 12px;
        line-height: 1.5;

        h2 {
            margin: 0 0 8px;
            font-size: 1.2rem;
        }

        ol {
            margin: 0 0 8px;
            padding-left: 20px;
        }

        li {
            margin-bottom: 6px;
        }

        p {
            margin: 0;
            color: #666;
        }
    }
}

.meal-input {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 8px 0;
    border-bottom: 1px solid #eee;

    &_photo,
    &_icon {
        flex-shrink: 0;
        width: 48px;
        height: 48px;
        border-radius: 8px;
    }

    &_photo {
        object-fit: cover;
    }

    &_icon {
        display: flex;
        align-items: center;
        justify-content: center;
        color: $main-color;
        background: #eef7ee;
    }

    &_text {
        flex: 1;
        min-width: 0;
        overflow-wrap: anywhere;
        line-height: 1.4;
    }

    &_remove {
        flex-shrink: 0;
        padding: 8px;
        color: #999;
        background: none;
        border: none;
        cursor: pointer;
    }
}

.meal-item {
    padding: 12px 0;
    border-bottom: 1px solid #eee;

    &_head {
        display: flex;
        align-items: center;
        gap: 8px;
    }

    &_number {
        flex-shrink: 0;
        width: 24px;
        height: 24px;
        line-height: 24px;
        font-size: 0.85rem;
        text-align: center;
        color: #fff;
        background: $main-color;
        border-radius: 50%;
    }

    &_name {
        flex: 1;
        font-weight: 600;
    }

    &_tag {
        padding: 2px 8px;
        font-size: 0.8rem;
        color: #666;
        background: #f0f0f0;
        border-radius: 8px;
    }

    &_remove {
        padding: 6px 8px;
        color: #999;
        background: none;
        border: none;
        cursor: pointer;
    }

    &_portion {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        gap: 12px;
        margin: 8px 0 0 32px;
    }

    &_grams input {
        width: 80px;
        padding: 6px 8px;
        font-size: 1rem;
        color: inherit;
        background: #f5f5f5;
        border: 1px solid transparent;
        border-radius: 8px;
    }

    &_calories {
        font-weight: 600;
    }

    &_macros {
        color: #666;
        font-size: 0.9rem;
    }

    &_toggle {
        margin: 6px 0 0 32px;
        padding: 0;
        font-size: 0.9rem;
        color: darken($main-color, 15%);
        background: none;
        border: none;
        cursor: pointer;
    }

    &_facts {
        display: grid;
        grid-template-columns: repeat(2, 1fr);
        gap: 8px;
        margin: 8px 0 0 32px;
    }

    &_fact {
        display: flex;
        flex-direction: column;
        gap: 4px;
        font-size: 0.85rem;
        color: #666;

        input {
            padding: 6px 8px;
            font-size: 1rem;
            color: #333;
            background: #f5f5f5;
            border: 1px solid transparent;
            border-radius: 8px;
        }
    }

    &_hint {
        grid-column: 1 / -1;
        margin: 0;
        font-size: 0.8rem;
        color: #888;
    }
}

@keyframes meal-pulse {
    0% {
        box-shadow: 0 0 0 0 rgba(229, 57, 53, 0.5);
    }
    70% {
        box-shadow: 0 0 0 12px rgba(229, 57, 53, 0);
    }
    100% {
        box-shadow: 0 0 0 0 rgba(229, 57, 53, 0);
    }
}
</style>

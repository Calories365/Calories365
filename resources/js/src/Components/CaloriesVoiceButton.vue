<script>
import { FontAwesomeIcon } from "@fortawesome/vue-fontawesome";
import { faMicrophone, faStop } from "@fortawesome/free-solid-svg-icons";

// Telegram keeps a voice message short; this bitrate keeps a minute of speech
// near 250 KB, as on the "Приём пищи" page.
const AUDIO_BITS_PER_SECOND = 32000;

/**
 * A button that records the microphone while pressed once and stops on the
 * second press: emits "recorded" with the audio, or "failed" when the browser
 * has no microphone or the user did not allow it. Hidden where recording is
 * impossible.
 */
export default {
    name: "CaloriesVoiceButton",
    components: { FontAwesomeIcon },
    props: {
        disabled: {
            type: Boolean,
            default: false,
        },
    },
    emits: ["recorded", "failed", "recording"],
    data() {
        return {
            canRecord:
                typeof window !== "undefined" &&
                Boolean(navigator.mediaDevices?.getUserMedia) &&
                typeof window.MediaRecorder !== "undefined",
            recording: false,
            icons: { faMicrophone, faStop },
        };
    },
    beforeUnmount() {
        this.release();
    },
    methods: {
        async toggle() {
            if (this.recording) {
                this.recorder?.stop();
                return;
            }

            try {
                this.stream = await navigator.mediaDevices.getUserMedia({ audio: true });
            } catch (error) {
                this.$emit("failed");
                return;
            }

            const mimeType = ["audio/mp4", "audio/webm;codecs=opus", "audio/webm"].find((type) =>
                MediaRecorder.isTypeSupported(type)
            );
            const chunks = [];
            this.recorder = new MediaRecorder(this.stream, {
                ...(mimeType ? { mimeType } : {}),
                audioBitsPerSecond: AUDIO_BITS_PER_SECOND,
            });
            this.recorder.ondataavailable = (event) => {
                if (event.data.size > 0) {
                    chunks.push(event.data);
                }
            };
            this.recorder.onstop = () => {
                const type = this.recorder.mimeType || mimeType || "audio/webm";
                this.recording = false;
                this.$emit("recording", false);
                this.release();
                this.$emit("recorded", new Blob(chunks, { type }));
            };
            this.recorder.start();
            this.recording = true;
            this.$emit("recording", true);
        },
        release() {
            this.stream?.getTracks().forEach((track) => track.stop());
            this.stream = null;
        },
    },
};
</script>

<template>
    <button
        v-if="canRecord"
        type="button"
        class="voice-button"
        :class="{ 'voice-button--recording': recording }"
        :disabled="disabled && !recording"
        @click="toggle"
    >
        <font-awesome-icon :icon="recording ? icons.faStop : icons.faMicrophone" />
        <span><slot :recording="recording"></slot></span>
    </button>
</template>

<style scoped lang="scss">
.voice-button {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 8px 14px;
    border: 0;
    border-radius: 6px;
    background: #73ad37;
    color: #fff;
    font-weight: 600;
    cursor: pointer;

    &--recording {
        background: #d0433a;
    }

    &:disabled {
        opacity: 0.5;
        cursor: default;
    }
}
</style>

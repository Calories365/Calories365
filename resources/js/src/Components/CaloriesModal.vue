<template>
    <div v-if="isOpen" class="feedback-modal-overlay" @click.self="closeModal">
        <div class="feedback-modal">
            <div
                class="slide-menu__arrow-back"
                @click="closeModal"
                aria-label="close"
            >
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 384 512">
                    <path
                        d="M342.6 150.6c12.5-12.5 12.5-32.8 0-45.3s-32.8-12.5-45.3 0L192 210.7 86.6 105.4c-12.5-12.5-32.8-12.5-45.3 0s-12.5 32.8 0 45.3L146.7 256 41.4 361.4c-12.5 12.5-12.5 32.8 0 45.3s32.8 12.5 45.3 0L192 301.3 297.4 406.6c12.5 12.5 32.8 12.5 45.3 0s12.5-32.8 0-45.3L237.3 256 342.6 150.6z"
                        fill="darkgrey"
                    />
                </svg>
            </div>
            <pre class="feedback-modal__content">{{ content }}</pre>
        </div>
    </div>
</template>

<script>
export default {
    name: "CaloriesModal",
    props: {
        isOpen: {
            type: Boolean,
            required: true,
        },
        content: {
            type: String,
            required: true,
        },
    },
    emits: ["close"],
    methods: {
        closeModal() {
            this.$emit("close");
        },
    },
};
</script>

<style scoped lang="scss">
.feedback-modal-overlay {
    position: fixed;
    z-index: 1000;
    inset: 0;
    background: rgba(0, 0, 0, 0.3);
    display: flex;
    align-items: center;
    justify-content: center;
}

.feedback-modal {
    position: relative;
    width: 90%;
    max-width: 700px;
    background: #ffffff;
    border-radius: 10px;
    box-shadow: -5px 0px 5px -4px rgba(0, 0, 0, 0.24);
    padding: 20px 20px 24px 20px;
    font-family: "Roboto", sans-serif;
}

.feedback-modal__content {
    font-family: "Roboto", sans-serif;
    font-weight: 500;
    font-size: 14px;
    line-height: 20px;
    letter-spacing: 0.03rem;
    color: #333;
    white-space: pre-wrap;
    background: #f7f7f7;
    border: 1px solid #e6e6e6;
    border-radius: 8px;
    padding: 12px;
    max-height: 60vh;
    overflow: auto;
}

.slide-menu__arrow-back {
    position: absolute;
    width: 20px;
    height: 20px;
    cursor: pointer;
    padding: 5px 0px 5px 5px;
    top: 1px;
    right: 3px;

    @media (max-width: 768px) {
        width: 20px;
    }
}
</style>

<!-- src/components/UI/BotsConfirmatiomModal.vue -->
<template>
    <div class="modal fade" :class="{ show: showModal }" tabindex="-1" role="dialog" aria-labelledby="confirmActionModalLabel" aria-hidden="true" :style="{ display: showModal ? 'block' : 'none' }">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="confirmActionModalLabel">{{ title }}</h5>
                    <button type="button" class="close" aria-label="Close" @click="closeModal">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    {{ message }}
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" @click="closeModal">{{ cancelText }}</button>
                    <button type="button" class="btn btn-primary" @click="confirmAction">{{ confirmText }}</button>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
export default {
    props: {
        title: {
            type: String,
            required: true
        },
        message: {
            type: String,
            required: true
        },
        showModal: {
            type: Boolean,
            required: true
        },
        cancelText: {
            type: String,
            default: 'Cancellation'
        },
        confirmText: {
            type: String,
            default: 'Confirm'
        }
    },
    methods: {
        closeModal() {
            this.$emit('update:showModal', false);
        },
        confirmAction() {
            this.$emit('confirm');
            this.closeModal();
        }
    }
};
</script>

<style scoped>
.modal.show {
    display: block;
    padding-right: 17px;
}
</style>

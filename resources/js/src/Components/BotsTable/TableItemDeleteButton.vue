<script setup>
import {defineEmits, defineProps} from 'vue';

const props = defineProps({
    data: {
        type: Object,
        required: true
    },
    limit: {
        type: String,
        required: true
    },
    action: {
        type: String,
        required: true
    },
    id: {
        type: Number,
        required: true
    }
});

const emit = defineEmits(['handle']);

function handle() {
    if (props.action) {
        emit('handle', {action: props.action, id: props.id, data: props.data});
    }
}
</script>

<template>
    <button type="button" class="btn btn-block btn-danger" @click="handle">
        Delete
    </button>
</template>

<style scoped lang="scss">
</style>

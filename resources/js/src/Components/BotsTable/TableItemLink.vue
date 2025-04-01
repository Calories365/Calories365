<script setup>
import {computed, defineEmits, defineProps} from 'vue';
import { truncateString } from '@/utils/truncateString';
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

const truncatedText = computed(() => {
    return truncateString(props.data, parseInt(props.limit, 10));
});

const emit = defineEmits(['handle']);

function handle() {
    if (props.action) {
        emit('handle', {action: props.action, id: props.id, data: props.data});
    }
}
</script>

<template>
    <a href="#" @click.prevent="handle">{{ truncatedText }}</a>
</template>

<style scoped lang="scss">
</style>

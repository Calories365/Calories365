<script setup>
import {computed, defineProps} from 'vue';
import {truncateString} from '@/utils/truncateString';

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
    return truncateString(props.data, props.limit);
});


</script>

<template>
    {{ truncatedText }}
</template>

<style scoped lang="scss">
</style>

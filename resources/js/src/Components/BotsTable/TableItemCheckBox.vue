<script setup>
import {computed, defineProps} from 'vue';

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

const isChecked = computed(() => {
    return props.data === 1;
});
</script>

<template>
    <input type="checkbox" v-model="isChecked" disabled>
</template>

<style scoped lang="scss">
</style>

<script setup>
import {defineEmits, defineProps, ref, watch} from 'vue';

const props = defineProps({
    options: {
        type: Array,
        required: true
    },
    value: {
        type: Number,
        default: null
    },
    perPageText: {
        type: String,
        default: null
    },
});

const emit = defineEmits(['update:value']);
const selectedValue = ref(null);

watch(() => props.value, (newValue) => {
    if (newValue !== null) {
        selectedValue.value = newValue;
    } else if (props.options.length > 0) {
        selectedValue.value = props.options[0];
        emit('update:value', selectedValue.value);
    }
}, {immediate: true});

const handleChange = (event) => {
    selectedValue.value = parseInt(event.target.value, 10);
    emit('update:page-size', selectedValue.value);
};
</script>

<template>
    <div class="perpage-container">
        <form class="perpage-form">
            <label class="perpage-label">{{ perPageText }}</label>
            <select class="perpage-select" v-model="selectedValue" @change="handleChange">
                <option v-for="option in props.options" :key="option" :value="option">{{ option }}</option>
            </select>
        </form>
    </div>
</template>

<style scoped>
.perpage-container {
    margin-bottom: 1rem;
    display: flex;
    justify-content: flex-end;
}

.perpage-form {
    display: flex;
    align-items: center;
    gap: 0.75rem;
}

.perpage-label {
    font-size: 0.875rem;
    color: #4b5563;
    margin: 0;
}

.perpage-select {
    padding: 0.5rem 2.5rem 0.5rem 0.75rem;
    font-size: 0.875rem;
    line-height: 1.25;
    color: #4b5563;
    background-color: #ffffff;
    background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 20 20'%3e%3cpath stroke='%236b7280' stroke-linecap='round' stroke-linejoin='round' stroke-width='1.5' d='M6 8l4 4 4-4'/%3e%3c/svg%3e");
    background-position: right 0.5rem center;
    background-repeat: no-repeat;
    background-size: 1rem;
    border: 1px solid #e5e7eb;
    border-radius: 0.375rem;
    appearance: none;
    width: auto;
    cursor: pointer;
    transition: border-color 0.2s, box-shadow 0.2s;
}

.perpage-select:focus {
    outline: none;
    border-color: #93c5fd;
    box-shadow: 0 0 0 3px rgba(147, 197, 253, 0.25);
}

@media (max-width: 640px) {
    .perpage-container {
        justify-content: center;
    }
    
    .perpage-form {
        flex-direction: column;
        align-items: flex-start;
        gap: 0.5rem;
    }
}
</style>

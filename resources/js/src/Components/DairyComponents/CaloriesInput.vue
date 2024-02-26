<script setup>
import {defineEmits, defineProps} from 'vue';

const props = defineProps({
    modelValue: Number
});
const emits = defineEmits(['update:modelValue']);
</script>

<template>
    <input
        type="number"
        class="styled-input"
        :value="modelValue !== null ? modelValue.toString() : ''"
        @input="emits('update:modelValue', $event.target.valueAsNumber)"
    >
</template>


<style scoped lang="scss">
.styled-input {
    border: 2px solid lightgray;
    border-radius: 20px;
    outline: none;
    text-align: center;
    width: 70px;

    &:focus {
        border-color: #3e8e41;
    }
}
</style>

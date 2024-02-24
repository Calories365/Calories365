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
    border: 2px solid lightgray; /* Цвет рамки */
    border-radius: 20px; /* Радиус скругления углов */
    outline: none; /* Убираем стандартный фокус */
    text-align: center; /* Текст по центру */
    width: 70px;

    &:focus {
        border-color: #3e8e41; /* Цвет рамки при фокусе */
    }
}
</style>

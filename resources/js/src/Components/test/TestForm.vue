<script setup>
import {ref} from 'vue';

// Используйте defineEmits для определения событий, которые может эмитировать этот компонент
const emit = defineEmits(['add-person']);

const props = defineProps({
  initialName: String,
  initialAge: Number
});

const name = ref(props.initialName);
const age = ref(props.initialAge);

function submitForm() {
  // Эмитируем событие add-person с данными формы
  emit('add-person', {name: name.value, age: age.value});

  // Очистка полей формы
  name.value = '';
  age.value = '';
}
</script>

<template>
  <form @submit.prevent="submitForm">
    <input type="text" v-model="name" placeholder="Имя">
    <input type="text" v-model="age" placeholder="Возраст">
    <button type="submit">
      <slot></slot>
    </button>
  </form>
</template>

<style scoped lang="scss">
/* Стили для формы */
form {
  display: flex;
  flex-direction: column; /* Элементы формы будут организованы вертикально */
  gap: 10px; /* Расстояние между элементами формы */
  width: 100%; /* Ширина формы */
  max-width: 400px; /* Максимальная ширина формы, чтобы она не была слишком широкой */
  margin: 0 auto; /* Центрирование формы по горизонтали */
  padding: 20px; /* Внутренние отступы формы */
  box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1); /* Тень для формы, чтобы добавить глубину */
  border-radius: 8px; /* Скругление углов формы */
}

/* Стили для текстовых полей ввода */
input[type="text"] {
  padding: 10px; /* Внутренние отступы текстовых полей */
  border: 1px solid #ccc; /* Граница текстовых полей */
  border-radius: 4px; /* Скругление углов текстовых полей */
  outline: none; /* Убираем стандартный фокус браузера */
}

input[type="text"]:focus {
  border-color: #007bff; /* Изменение цвета границы при фокусе */
  box-shadow: 0 0 0 2px rgba(0, 123, 255, 0.25); /* Добавление тени при фокусе */
}

button {
  padding: 10px; /* Внутренние отступы для кнопки */
  border: none; /* Убираем стандартную рамку */
  border-radius: 4px; /* Скругляем углы */
  background-color: #007bff; /* Фоновый цвет кнопки */
  color: white; /* Цвет текста */
  cursor: pointer; /* Курсор в виде указателя */
  transition: background-color 0.3s; /* Плавное изменение цвета фона при наведении */
}

button:hover {
  background-color: #0056b3; /* Цвет фона кнопки при наведении */
}

button:focus {
  outline: none; /* Убираем стандартный фокус браузера */
  box-shadow: 0 0 0 2px rgba(0, 123, 255, 0.5); /* Добавляем тень вокруг кнопки при фокусе */
}

</style>

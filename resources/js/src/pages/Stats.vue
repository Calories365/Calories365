<script setup>
import {ref} from 'vue';

// Эти данные изначально не реактивные, но благодаря использованию ref они становятся реактивные
const persons = ref([
    {name: 'volodya', age: 90},
    {name: 'vasya', age: 38},
]);

// Пример метода на Composition API; в Options API надо было бы указывать this, тут нет
// Чтобы вызвать метод, его надо передать в шаблон через return (в <script setup> это не требуется)
function message(person) {
    alert(person.name);
}

function addPerson(person) {
    persons.value.push(person);
}
</script>

<template>
    <VCalendar class="calories-calendar" :rows="2" :columns="2"/>123
</template>


<style scoped lang="scss">

.calories-calendar{
}
</style>

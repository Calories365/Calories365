<script setup>
import {ref} from 'vue';
import TestForm from "@/Components/test/TestForm.vue";

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
    <div class="container">
        <TestForm @add-person="addPerson" :initialName="'John'" :initialAge="30">Добавить</TestForm>
        <ul class="list">
            <li
                class="person"
                v-for="person in persons"
                :key="person.name"
                @click="message(person)"
            >
                {{ person.name }} <br>
                {{ person.age }}
            </li>
        </ul>
    </div>
</template>


<style scoped lang="scss">
.container {
    width: 80%;
    margin: 0 auto;
}

.list {
    padding: 30px 0 30px 0;
    display: block;
    margin: 0 auto;
    width: 50%;
}

.person {
    text-align: center;
    border: 1px solid black;
    margin: 5px;
    padding: 3px;
    cursor: pointer;
}

.button {
    display: block;
    margin: 0 auto;
    text-align: center;
}


</style>

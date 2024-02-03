<script>
export default {
    name: "CaloriesButton",
    props: {
        passedClass: {
            type: String,
            default: 'default',
        },
    },

}
</script>

<template>
    <button
        class="calories-button"
        :class="passedClass">
        <span class="calories-button_text"><slot></slot></span>
    </button>
</template>

<style scoped lang="scss">
.calories-button {
    //width: 500px;
    height: 50px;
    //background-color: #4CAF50; /* Основной зелёный цвет кнопки */
    background-color: $main-color; /* Основной зелёный цвет кнопки */
    color: white; /* Цвет текста */
    border: none; /* Убрать границу */
    padding: 1vh 8vh; /* Внутренние отступы для увеличения размера кнопки */
    //margin-bottom: 20px;
    font-size: $default-font-size; /* Размер шрифта */
    cursor: pointer; /* Курсор в виде руки при наведении на кнопку */
    border-radius: 5px; /* Скругление углов кнопки */
    transition: background-color 0.3s, box-shadow 0.3s, transform 0.3s; /* Плавные переходы для анимации */
    text-align: center;

    @media (max-width: 768px) {
        padding: 1vh 4vh; /* Уменьшенные внутренние отступы для мобильных устройств */
        font-size: 15px; /* Немного уменьшаем размер шрифта */
    }

    &_text {
        text-align: center;
    }

    &.danger {
        background-color: #c01515; /* Основной зелёный цвет кнопки */
    }

    &.recovery {
        height: 35px;
        font-size: 16px;
    }
}

.calories-button:hover {
    background-color: #367C39; /* Ещё более темный зелёный для активного состояния */
    box-shadow: inset 0 2px 4px rgba(0, 0, 0, 0.24); /* Внутренняя тень для эффекта вдавленности */
    &.danger {
        background-color: #9d1111; /* Основной зелёный цвет кнопки */
    }
}

.calories-button:active {
    background-color: #2E5933; /* Even darker green color for the pressed state */
    box-shadow: inset 0 3px 5px rgba(0, 0, 0, 0.2); /* Deeper shadow for a more pressed-in effect */
    transform: scale(0.98); /* Slightly scale down the button to mimic a pressing effect */
    &.danger {
        background-color: #9d1111; /* Основной зелёный цвет кнопки */
    }
}


</style>

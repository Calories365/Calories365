<script>
export default {
    name: "CaloriesButton",
    props: {
        passedClass: {
            type: String,
            default: 'default',
        },
        type: {
            type: String,
            default: 'button'
        }

    },

}
</script>

<template>
    <button
        class="calories-button"
        :type="type"
        @click="$emit('click')"
        :class="passedClass">
        <span class="calories-button_text"><slot></slot></span>
    </button>
</template>

<style scoped lang="scss">
.calories-button {
    height: 50px;
    background-color: $main-color;
    color: white;
    border: none;
    padding: 1vh 8vh;
    //margin-bottom: 20px;
    font-size: $default-font-size;
    cursor: pointer;
    border-radius: 5px;
    transition: background-color 0.3s, box-shadow 0.3s, transform 0.3s;
    text-align: center;

    @media (max-width: 768px) {
        padding: 1vh 4vh;
        font-size: 15px;

    &_text {
        text-align: center;
    }

    &.danger {
        background-color: #c01515;
    }

    &.recovery {
        height: 35px;
        font-size: 16px;
    }
    }
}

.calories-button:hover {
    background-color: #367C39;
    box-shadow: inset 0 2px 4px rgba(0, 0, 0, 0.24);
    &.danger {
        background-color: #9d1111;
    }
}

.calories-button:active {
    background-color: #2E5933;
    box-shadow: inset 0 3px 5px rgba(0, 0, 0, 0.2);
    transform: scale(0.98);
    &.danger {
        background-color: #9d1111;
    }
}


</style>

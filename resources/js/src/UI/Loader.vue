<script setup>
</script>

<template>
    <div class="loader-wrapper">
        <div class="loader"></div>
    </div>
</template>

<script>
export default {
    name: "Loader",
};
</script>

<style scoped>
body {
    background-color: #3f3c3b; /* Темный фон для контраста */
}

.loader-wrapper {
    display: flex;
    justify-content: center;
    align-items: center;
    width: 100%;
    height: 100vh; /* Полная высота экрана */
    position: fixed;
    top: 0;
    left: 0;
    z-index: 9999;
}

.loader {
    width: 40px;
    height: 40px;
    border: 4px solid rgba(96, 56, 56, 0.2); /* Светлая серая обводка */
    border-top-color: #000000; /* Белый цвет верхнего бордюра */
    border-radius: 50%; /* Круглая форма */
    animation: spin 2s linear infinite;
}

@keyframes spin {
    0% {
        transform: rotate(0deg);
    }
    100% {
        transform: rotate(360deg);
    }
}
</style>

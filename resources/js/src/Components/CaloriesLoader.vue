<script>
export default {
    name: "CaloriesLoader"
}
</script>

<template>
<span class="loader"></span>
</template>

<style scoped lang="scss">

.hide {
    opacity: 0.33;
}

.loader {
    position: absolute;
    top: 50%;
    right: 50%;
    width: 32px;
    height: 72px;
    display: inline-block;
    border: 2px solid #000000;
    box-sizing: border-box;
    animation: animloader 2s linear infinite alternate;
    color: $main-color;
    border-radius: 0 0 4px 4px;
    transform: perspective(140px) rotateX(-45deg);
}

@keyframes animloader {
    0% {
        box-shadow: 0 0 inset;
    }
    100% {
        box-shadow: 0 -70px inset;
    }
}
</style>

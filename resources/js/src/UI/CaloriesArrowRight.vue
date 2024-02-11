<script>
export default {
    name: "CaloriesArrowRight"
}
</script>

<template>
    <div class="arrow-right"></div>

</template>

<style scoped lang="scss">
.arrow-right {
    cursor: pointer;
    width: 0;
    height: 0;
    border-top: 10px solid transparent;
    border-bottom: 10px solid transparent;
    border-left: 10px solid #a49e9e;

    &:hover {
        border-left: 10px solid $pink_color;
    }
}
</style>

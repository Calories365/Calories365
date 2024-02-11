<script>
export default {
    name: "CaloriesArrowLeft"
}
</script>

<template>
    <div class="arrow-left"></div>

</template>

<style scoped lang="scss">
.arrow-left {
    cursor: pointer;
    width: 0;
    height: 0;
    border-top: 10px solid transparent;
    border-bottom: 10px solid transparent;
    border-right: 10px solid #a49e9e;
    &:hover {
        border-right: 10px solid $pink_color;
    }
}
</style>

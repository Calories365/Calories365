<script>
export default {
    name: "CaloriesCross",
};
</script>

<template>
    <div class="added-product__close"></div>
</template>

<style scoped lang="scss">
.close {
    width: 20px;
    height: 20px;
    opacity: 0.9;
    transition: opacity ease 0.5s, transform 0.3s ease;
    position: relative;

    &:hover {
        transform: scale(1.3);
    }

    &::before, &::after {
        content: "";
        position: absolute;
        z-index: 11111;
        top: 10px;
        display: block;
        width: 18px;
        height: 2px;
        background: #000;
    }

    &::before {
        transform: rotate(45deg);
    }

    &::after {
        transform: rotate(-45deg);
    }
}
</style>

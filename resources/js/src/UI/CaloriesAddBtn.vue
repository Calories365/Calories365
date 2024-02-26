<script>
export default {
    name: "CaloriesAddBtn"
}
</script>

<template>
    <div class="section-addProducts__btn"></div>
</template>

<style scoped lang="scss">
.section-addProducts__btn {

    background-color: $main-color;
    font-size: 16px;
    height: 3em;
    width: 3em;
    border-radius: 999px;
    position: relative;
    cursor: pointer;
    -webkit-box-shadow: 11px 4px 8px 0px rgba(34, 60, 80, 0.2);
    -moz-box-shadow: 11px 4px 8px 0px rgba(34, 60, 80, 0.2);
    box-shadow: 11px 4px 8px 0px rgba(34, 60, 80, 0.2);
}

.section-addProducts__btn:hover {

    transition: all 0.3s ease 0s;
}

.section-addProducts__btn:after, .section-addProducts__btn:before {
    content: "";
    display: block;
    background-color: white;
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
}

.section-addProducts__btn:before {
    height: 1.5em;
    width: 0.2em;
}

.section-addProducts__btn:after {
    height: 0.2em;
    width: 1.5em;
}

.section-addProducts__btn:hover:before, .section-addProducts__btn:hover:after {
    background-color: $pink_color;
    transition: all 0.3s ease 0s;
}
</style>


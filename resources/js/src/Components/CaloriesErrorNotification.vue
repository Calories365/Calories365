<script>
export default {
    name: "CaloriesErrorNotification"
}
</script>

<template>
    <div class="notification-container">
        <div class="notification error">
            <slot></slot>
        </div>
    </div>

</template>

<style scoped lang="scss">
.notification-container {
    position: fixed;
    top: 10%;
    left: 50%;
    transform: translate(-50%, -50%);
    width: auto;
    z-index: 9999999;
}

.notification {
    padding: 20px;
    background-color: #f8f8f8;
    border-left: 5px solid transparent;
    position: relative;
    margin-bottom: 15px;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
    width: 100%;

    &.error {
        background-color: #e8c6c6;
        border-color: #c73434;
        color: #3e1a1a;

        .close-button {
            color: #3e1a1a;
        }
    }

    .close-button {
        position: absolute;
        right: 10px;
        top: 50%;
        transform: translateY(-50%);
        border: none;
        background: none;
        font-size: 20px;
        cursor: pointer;
    }
}

</style>

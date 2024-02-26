<script>
export default {
    name: "CaloriesSuccessNotification"
}
</script>

<template>
    <div class="notification-container">
        <div class="notification success">
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
    z-index: 1000;
}

.notification {
    padding: 20px;
    background-color: #f8f8f8;
    border-left: 5px solid transparent;
    position: relative;
    margin-bottom: 15px;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
    width: 100%;

    &.success {
        background-color: #e9f6ec;
        border-color: #34c759;
        color: #1a3e29;

        .close-button {
            color: #1a3e29;
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


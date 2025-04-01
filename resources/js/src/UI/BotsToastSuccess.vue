<template>
    <transition-group name="list" tag="div" id="toastsContainerTopRight" class="toasts-top-right fixed">
        <div v-for="message in messages" :key="message" class="toast bg-success fade show" role="alert"
             aria-live="assertive" aria-atomic="true">
            <div class="toast-body">{{ message }}</div>
        </div>
    </transition-group>
</template>

<script setup>
import {defineProps} from 'vue';

const props = defineProps({
    messages: Array
});
</script>

<style scoped>
.list-enter-active, .list-leave-active {
    transition: opacity 0.5s;
}

.list-enter, .list-leave-to {
    opacity: 0;
}
</style>

<template>
    <transition-group name="list" tag="div" id="toastsContainerTopRight" class="toasts-top-right fixed">
        <div v-for="message in messages" :key="message" class="toast bg-danger fade show" role="alert"
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

.list-enter, .list-leave-to /* .list-leave-active in <2.1.8 */
{
    opacity: 0;
}
</style>

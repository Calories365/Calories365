<script setup>
import {defineEmits, defineProps} from 'vue';

const props = defineProps({
    data: {
        type: Object,
        required: true
    },
    limit: {
        type: String,
        required: true
    },
    action: {
        type: String,
        required: true
    },
    id: {
        type: Number,
        required: true
    }
});

const emit = defineEmits(['handle']);

function handle(id) {
    console.log('handle: ', id);
    if (props.action) {
        emit('handle', {action: props.action, id: id, data: id});
    }
}
</script>

<template>
    <div class="links-container">
        <span v-for="(value, key, index) in data" :key="key">
            <a href="#" @click.prevent="handle(value)">
                {{ value }}
            </a>

            <span v-if="index < Object.keys(data).length - 1" class="divider"> | </span>
        </span>
    </div>
</template>

<style scoped lang="scss">

a {
    padding: 0 10px;
}

.divider {
    color: #aaa;
}

</style>

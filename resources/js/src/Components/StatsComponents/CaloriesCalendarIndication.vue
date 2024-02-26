<script setup>
import {computed} from 'vue';
import {useI18n} from 'vue-i18n';

const {t} = useI18n();

const colors = computed(() => [
    {class: 'overreach', description: t('Stats.ColorDescriptions.Overreach')},
    {class: 'significant-overreach', description: t('Stats.ColorDescriptions.SignificantOverreach')},
    {class: 'nice', description: t('Stats.ColorDescriptions.Nice')},
]);
</script>

<template>
    <div class="color-explanations">
        <div v-for="color in colors" :key="color.class" class="color-explanation">
            <div :class="['color-box', color.class]"></div>
            <span>- {{ color.description }}</span>
        </div>
    </div>
</template>

<style scoped lang="scss">
.color-explanations {
    display: flex;
    padding-top: 20px;
    justify-content: center;
    @media (max-width: 768px) {
        flex-direction: column;
    }
}

.color-explanation {
    display: flex;
    align-items: center;
    margin-bottom: 8px;
    margin-left: 40px;
    @media (max-width: 768px) {
        margin-left: 0;
    }
}

.color-box {
    width: 20px;
    height: 20px;
    margin-right: 8px;
}

.overreach {
    background-color: rgba(225, 151, 63, 0.5);
}

.significant-overreach {
    background-color: rgba(174, 55, 55, 0.5);
}

.nice {
    background-color: rgba(71, 174, 55, 0.5);
}
</style>

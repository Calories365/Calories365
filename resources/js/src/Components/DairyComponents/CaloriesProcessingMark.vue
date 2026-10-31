<script>
/**
 * How processed a product is, by its NOVA group: no mark until the group is known.
 */
export default {
    name: "CaloriesProcessingMark",
    props: {
        nova: {
            type: Number,
            default: null,
        },
    },
};
</script>

<template>
    <span
        v-if="nova"
        class="processing-mark"
        :class="`processing-mark_nova${nova}`"
        >{{ $t(`Diary.Processing.nova${nova}`) }}</span
    >
</template>

<style scoped lang="scss">
.processing-mark {
    display: inline-block;
    padding: 2px 8px;
    border-radius: 10px;
    font-family: "Roboto", sans-serif;
    font-size: 12px;
    font-weight: 500;
    line-height: 14px;
    letter-spacing: 0;
    text-transform: none;
    white-space: nowrap;

    @media (max-width: 768px) {
        padding: 1px 6px;
        font-size: 10px;
        line-height: 12px;
    }

    &_nova1 {
        background-color: #e3f4e4;
        color: #1f6b2a;
    }

    &_nova2 {
        background-color: #ececec;
        color: #4d4d4d;
    }

    &_nova3 {
        background-color: #fdf1d3;
        color: #7a5600;
    }

    &_nova4 {
        background-color: #fbe2e0;
        color: #a3231a;
    }
}
</style>

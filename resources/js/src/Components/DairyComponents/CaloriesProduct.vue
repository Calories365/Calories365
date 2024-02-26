<script>
export default {
    name: "CaloriesProduct",
    props: {
        product: {
            type: Object,
            required: true
        }
    },
}
</script>

<template>
    <li class="product-card">
        <div class="product-card__body">
            <h2 class="product-card__title">{{ product.name }}</h2>
            <span class="product-card__user" v-if="product.user_id">({{ $t('Diary.YourProduct') }})</span>
        </div>
    </li>
</template>

<style scoped lang="scss">
.product-card {
    &__body {
        height: 100%;
        width: 100%;
        border-bottom: 1px solid #dbd9d9;
        display: flex;
        gap: 20px;
        justify-content: flex-start;
        align-items: center;
        padding: 15px;
        cursor: pointer;

        &:hover {
            background-color: #f0efef;
        }
    }

    &__img {
        pointer-events: none;
    }

    &__title {
        font-family: "Roboto", sans-serif;
        font-weight: 600;
        font-size: 13px;
        line-height: 22px;
        //letter-spacing: 0.0625rem;
        text-transform: uppercase;
        pointer-events: none;
        @media (max-width: 768px) {
            font-size: 12px;
        }
    }

    &__user {
        font-family: "Roboto", sans-serif;
        font-weight: 600;
        font-size: 10px;
        line-height: 22px;
        //letter-spacing: 0.0625rem;
        text-transform: uppercase;
        pointer-events: none;
        @media (max-width: 768px) {
            font-size: 10px;
        }
    }
}
</style>

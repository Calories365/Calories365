<script>
export default {
    name: "CaloriesAddProduct",
}
</script>

<template>
    <li class="product-card">
        <div class="product-card__body">
            <h2 class="product-card__title">
                <slot></slot>
                ({{ $t('Diary.NewProduct') }})
                <svg class="product-card__plus" style="height: 20px" xmlns="http://www.w3.org/2000/svg"
                     viewBox="0 0 448 512">
                    <path
                        d="M256 80c0-17.7-14.3-32-32-32s-32 14.3-32 32V224H48c-17.7 0-32 14.3-32 32s14.3 32 32 32H192V432c0 17.7 14.3 32 32 32s32-14.3 32-32V288H400c17.7 0 32-14.3 32-32s-14.3-32-32-32H256V80z"
                        fill="darkgrey"/>
                </svg>
            </h2>
        </div>
    </li>
</template>

<style scoped lang="scss">
.product-card {
    &__body {
        height: 100%;
        width: 100%;
        border-bottom: 3px solid #dbd9d9;
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
        display: flex;
        font-family: "Roboto", sans-serif;
        font-weight: 600;
        font-size: 13px;
        line-height: 22px;
        text-transform: uppercase;
        pointer-events: none;
        @media (max-width: 768px) {
            font-size: 12px;
        }
    }

    &__plus {
        margin-left: 10px;
    }
}
</style>

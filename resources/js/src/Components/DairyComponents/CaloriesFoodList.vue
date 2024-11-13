<script>
import CaloriesNavbar from "@/Components/DairyComponents/CaloriesNavbar.vue";
import CaloriesIndication from "@/Components/DairyComponents/CaloriesIndication.vue";
import CaloriesAddBtn from "@/UI/CaloriesAddBtn.vue";
import {actionTypes, getterTypes as dairyGetterTypes} from "@/store/modules/dairy.js";
import {mapGetters} from "vuex";
import CaloriesSlideProductMenu from "@/Components/DairyComponents/CaloriesSlideProductMenu.vue";
import CaloriesCross from "@/Components/DairyComponents/CaloriesCross.vue";


export default {
    name: "CaloriesFoodList",
    components: {CaloriesCross, CaloriesSlideProductMenu, CaloriesAddBtn, CaloriesIndication, CaloriesNavbar},
    data() {
        return {
            product: {},
            ProductMenuisOpen: false,
            action: 'update',
        }
    },
    methods: {
        deleteProduct(event, id) {
            event.stopPropagation();
            this.$store.dispatch(actionTypes.deleteCurrentProducts, id);
        },
        toggleProduct(product) {
            this.ProductMenuisOpen = !this.ProductMenuisOpen;
            this.product = product;
        }
    },

    computed: {
        ...mapGetters({
            currentProducts: dairyGetterTypes.currentProducts,
            part_of_day: dairyGetterTypes.part_of_day,
        }),
        filteredProducts() {
            return this.currentProducts.filter(product => product.part_of_day === this.part_of_day);
        },
    },
}
</script>

<template>
    <ul class="added-product__list">
<!--        <li-->
<!--            class="added-product__item"-->
<!--        >-->
        <li
            class="added-product__item"
            v-for="product in filteredProducts" :key="product.id"
            @click="toggleProduct(product)"
        >
            <div class="added-product__content desktop">

                <div class="added-product__left">
                    <div>
                        <img class="added-product__img"
                             src="@/images/burger.png" alt="">
                    </div>
                    <div class="added-product__title">{{ product.name }}</div>
                </div>
                <div class="added-product__right">
                    <div class="added-product__calories"> {{ $t('Diary.KCAL') }}
                        {{ Math.ceil(product.calories / 100 * product.quantity) }}
                    </div>
                    <div class="added-product__quantity">{{ $t('Diary.Grams') }} {{ product.quantity }}</div>
                </div>
            </div>


            <div class="added-product__content mobile">
                <div class="added-product__content-container">
                    <div class="added-product__left">
                        <div>
                            <img class="added-product__img"
                                 src="@/images/burger.png" alt="">
                        </div>
                        <div class="added-product__title">{{ product.name }}</div>
                    </div>

                    <div class="added-product__right">
                        <div class="added-product__calories">
                            {{ $t('Diary.KCAL') }}
                            {{ Math.ceil(product.calories / 100 * product.quantity) }}
                        </div>
                        <div class="added-product__calories">
                            {{ $t('Diary.Grams') }}
                            {{ Math.ceil(product.quantity) }}
                        </div>
                    </div>
                </div>

            </div>


            <calories-cross @click="deleteProduct($event, product.id)" />


        </li>

    </ul>

    <calories-slide-product-menu
        :isOpen="ProductMenuisOpen"
        :product="product"
        @update="ProductMenuisOpen = $event"
        :action="action"
        :productFromFoodList=true
    >
        {{ $t('Diary.Update') }}
    </calories-slide-product-menu>


</template>

<style scoped lang="scss">
.added-product {

    &__content-container {
        @media (max-width: 768px) {
            text-align: left;
        }
    }

    &__list {
        width: 100%;
        overflow: auto;
        overflow-y: scroll;
        height: 100%;
    }

    &__title {
        @media (max-width: 768px) {
            font-family: "Roboto", sans-serif;
            font-weight: 600;
            font-size: 13px;
            line-height: 10px;
            letter-spacing: 0.03rem;
            text-transform: uppercase;
        }
    }

    &__item {
        height: 65px;
        width: 100%;
        background-color: white;
        border-bottom: 1px solid #dbd9d9;
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 0px 20px;
        cursor: pointer;


        @media (max-width: 768px) {
            padding: 0;
        }

        &:hover {
            background-color: #f0efef;
        }

        @media (max-width: 768px) {
            font-family: "Roboto", sans-serif;
            font-weight: 500;
            font-size: 10px;
            line-height: 10px;
            letter-spacing: 0.03rem;
            text-transform: uppercase;
            height: 40px;
        }
    }

    &__img {
        height: 45px;
        @media (max-width: 768px) {
            height: 0;
            display: none;
        }
    }

    &__content {
        font-family: "Roboto", sans-serif;
        font-weight: 600;
        font-size: 15px;
        line-height: 22px;
        letter-spacing: 0.0625rem;
        text-transform: uppercase;

        flex: 0 0 90%;
        display: flex;
        justify-content: space-between;

        @media (max-width: 768px) {
            font-family: "Roboto", sans-serif;
            font-weight: 500;
            line-height: 10px;
            font-size: 10px;
            text-transform: uppercase;
            padding: 0 10px 0 10px;
        }

        &.desktop {
            @media (max-width: 768px) {
                display: none;
            }
        }

        &.mobile {
            @media (min-width: 768px) {
                display: none;
            }
        }
    }


    &__left {
        display: flex;
        align-items: center;
        gap: 20px;
        pointer-events: none;
        @media (max-width: 768px) {
            display: block;
            text-align: left;
            pointer-events: none;
            padding-bottom: 5px;
        }
    }

    &__right {
        display: flex;
        align-items: center;
        gap: 10px;
        pointer-events: none;
    }

    &__close {
        width: 20px;
        height: 20px;
        opacity: 1;
        transition: opacity ease 0.5s, transform 0.3s ease;
        position: relative;

        &:hover {
            transform: scale(1.3);
        }

        &::before, &::after {
            content: "";
            position: absolute;
            top: 10px;
            display: block;
            width: 18px;
            height: 2px;
            background: #000;
        }

        &::before {
            transform: rotate(45deg);
        }

        &::after {
            transform: rotate(-45deg);
        }
    }
}


</style>

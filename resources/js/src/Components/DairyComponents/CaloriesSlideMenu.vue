<script>
import {FontAwesomeIcon} from "@fortawesome/vue-fontawesome";
import CaloriesProduct from "@/Components/DairyComponents/CaloriesProduct.vue";
import {mapState} from "vuex";
import CaloriesSlideProductMenu from "@/Components/DairyComponents/CaloriesSlideProductMenu.vue";
import {actionTypes, mutationTypes as diaryMutations} from "@/store/modules/dairy.js";
import {debounce} from "lodash";


export default {
    name: "CaloriesSlideMenu",
    components: {CaloriesSlideProductMenu, CaloriesProduct, FontAwesomeIcon},
    data() {
        return {
            ProductMenuisOpen: false,
            product: null,
            action: 'add',
            query: null,
            page: 1,
            totalPage: 1,
        }
    },
    props: {
        isOpen: {
            type: Boolean,
            required: true,
        },
    },
    methods: {
        toggleMenu(isOpen) {
            this.$emit('update', !this.isOpen);
            this.query = null;
            this.totalPage = 1;
            this.page = 1;
            this.$store.commit(diaryMutations.clearProducts);
        },
        toggleProduct(product) {
            this.ProductMenuisOpen = !this.ProductMenuisOpen;
            this.product = product;
        },
        searchProducts() {
            if (this.query) {
                this.$store.commit(diaryMutations.clearProducts);
                this.page = 1;
                this.$store.dispatch(actionTypes.getSearchedProducts, {query: this.query, page: this.page})
                    .then((total) => {
                        this.totalPage = Math.ceil(total / 10);
                        this.page++;
                    })
            }
        },
        searchProductsViaObserverClick() {
            if (this.page >= 1 && this.query && this.page < this.totalPage) {
                this.$store.dispatch(actionTypes.getSearchedProducts, {query: this.query, page: this.page})
                this.page++;
            }
        }
    },
    watch: {
        query: debounce(function (newQuery) {
            this.searchProducts();
        }, 500)
    },
    computed: {
        ...mapState({
            products: state => state.dairy.productsInMenu,
        }),
    },
    mounted() {
        const options = {
            root: document.querySelector('.slide-menu'),
            rootMargin: '0px',
            threshold: 1.0
        }

        const callback = (entries, observer) => {
            if (entries[0].isIntersecting && this.page >= 1 && this.query && this.page < this.totalPage) {
                this.$store.dispatch(actionTypes.getSearchedProducts, {query: this.query, page: this.page})
                this.page++;
            }
        };

        const observer = new IntersectionObserver(callback, options);

        observer.observe(this.$refs["calories-observer"]);
    }
}
</script>

<template>

    <calories-slide-product-menu
        :isOpen="ProductMenuisOpen"
        :product="product"
        @update="ProductMenuisOpen = $event"
        :action="action"
    >
        {{ $t('Diary.AddProduct') }}
    </calories-slide-product-menu>


    <div :class="{ 'slide-menu': true, 'slide-menu_active': isOpen, 'slide-menu_inactive': !isOpen }">

        <div class="slide-menu__arrow-back" @click="toggleMenu(!isOpen)">

            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 384 512">
                <path
                    d="M342.6 150.6c12.5-12.5 12.5-32.8 0-45.3s-32.8-12.5-45.3 0L192 210.7 86.6 105.4c-12.5-12.5-32.8-12.5-45.3 0s-12.5 32.8 0 45.3L146.7 256 41.4 361.4c-12.5 12.5-12.5 32.8 0 45.3s32.8 12.5 45.3 0L192 301.3 297.4 406.6c12.5 12.5 32.8 12.5 45.3 0s12.5-32.8 0-45.3L237.3 256 342.6 150.6z"
                    fill="darkgrey"/>
            </svg>
        </div>

        <form @submit.prevent="" class="slide-menu__form">

            <input
                class="slide-menu__input"
                type="text"
                id="find-product"
                :placeholder="$t('Diary.AddProduct')"
                v-model="query"
            />

        </form>
        <div class="slide-menu__boxForProducts">

            <div class="slide-menu__subtitle-wrapper">
                <h2 class="slide-menu__subtitle">
                    <span v-if="!query">
                    {{ $t('Diary.PopularProducts') }}
                    </span>
                    <span v-else>
                    {{ $t('Diary.Search') }}: "{{ this.query }}"
                    </span>
                </h2>
            </div>
            <ul class="slide-menu__list">

                <calories-product
                    v-for="product in products"
                    :key="product.id"
                    :product="product"
                    @click="toggleProduct(product)"
                />

                <div
                    v-if="products"
                    ref="calories-observer"
                    class="calories-observer">
                    <div
                        class="calories-observer__text"
                        @click="searchProductsViaObserverClick">
                    <span>
                        еще товары
                    </span>
                    </div>
                </div>

            </ul>
        </div>
    </div>

</template>

<style scoped lang="scss">
.slide-menu {
    position: absolute;
    z-index: 99;
    top: 1px;
    right: -100%;
    width: 90%;
    height: 100%;
    background-color: white;
    transition: right 0.4s ease-out 0s;
    box-shadow: -5px 0px 5px -4px rgba(0, 0, 0, 0.24);
    overflow: auto;


    &.slide-menu_active {
        right: 0;
    }

    &__wrapper {
        padding: 15px 0 0 0;
        align-items: center;
        justify-content: space-between;
    }

    &__arrow-back {
        position: absolute;
        width: 20px;
        height: 20px;
        cursor: pointer;
        padding: 5px 0px 5px 5px;

        @media (max-width: 768px) {
            width: 20px;
        }
    }

    &__arrow-back img {
        width: 100%;
    }

    &__title, &__subtitle {
        font-family: "Roboto", sans-serif;
        font-weight: 600;
        font-size: 17px;
        line-height: 22px;
        letter-spacing: 0.0625rem;
        text-transform: uppercase;
        padding: 0 20px;
    }

    &__subtitle {
        padding: 10px 20px;

        @media (max-width: 768px) {
            font-size: 12px;
        }
    }

    &__subtitle-wrapper {
        background-color: #f0efef;
    }

    &__form {
        margin: 30px 0 30px 20px;
        max-width: 100%;

        input {
            border: 2px solid #dbd9d9;
            padding: 10px;
            border-radius: 7px;
            width: 60%;

            font-family: "Roboto", sans-serif;
            font-weight: 500;
            font-size: 15px;
            line-height: 22px;
            letter-spacing: 0.05rem;
        }
    }

    &__closeModal {
        position: absolute;
        z-index: 2;
        top: 10px;
        right: 20px;
        width: 24px;
        height: 24px;
        opacity: 0.9;
        cursor: pointer;
        transition: opacity ease 0.5s;

        &::before, &::after {
            content: "";
            position: absolute;
            top: 10px;
            display: block;
            width: 24px;
            height: 3px;
            background: #000;
        }

        &::before {
            transform: rotate(45deg);
        }

        &::after {
            transform: rotate(-45deg);
        }
    }

    &__list {
    }
}

.product-card {
    &__body {
        height: 20px;
        width: 100%;
        border-bottom: 1px solid #dbd9d9;
        display: flex;
        gap: 20px;
        justify-content: flex-start;
        align-items: center;
        padding: 25px;
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
        letter-spacing: 0.0625rem;
        text-transform: uppercase;
        pointer-events: none;
    }
}

.calories-observer {
    cursor: pointer;
    height: 30px;

    &__text {
        text-align: center;
        margin-top: 30px;
        font-family: "Roboto", sans-serif;
        font-weight: 500;
        font-size: 15px;
        line-height: 22px;
        letter-spacing: 0.05rem;
    }
}
</style>

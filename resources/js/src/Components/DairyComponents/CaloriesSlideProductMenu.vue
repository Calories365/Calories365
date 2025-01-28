<script>
import {FontAwesomeIcon} from "@fortawesome/vue-fontawesome";
import CaloriesCalculationSlider from "@/Components/CaloriesCalculationSlider.vue";
import {actionTypes} from "@/store/modules/dairy.js";

export default {
    name: "CaloriesSlideProductMenu",
    components: {CaloriesCalculationSlider, FontAwesomeIcon},
    data() {
        return {
            quantity: 1,
        }
    },
    props: {
        isOpen: {
            type: Boolean,
            required: true,
        },
        product: {
            type: Object,
            required: true,
        },
        action: {
            type: String,
            required: true,
        },
        productFromFoodList: {
            type: Boolean,
            required: false,
        },
    },
    methods: {
        toggleProduct() {
            this.$emit('update', !this.isOpen);
            if (!this.productFromFoodList){
                this.quantity = 1;
            }
        },
        addProduct() {
            if (this.quantity > 0 && this.quantity < 1250) {
                if (this.action === 'add') {
                    this.$store.dispatch(actionTypes.saveCurrentProducts,
                        {
                            product: this.product, quantity: this.quantity,
                        });
                } else if (this.action === 'update') {
                    this.$store.dispatch(actionTypes.updateCurrentProducts,
                        {
                            product: this.product, quantity: this.quantity,
                        })
                }
                this.$emit('update', !this.isOpen);
                //тут баг
                // this.quantity = 1;
            } else {
                this.$store.dispatch('setError', this.$t('Notification.Error.invalidData'));
            }
        },
    },
    watch: {
        product(newProduct) {
            if (newProduct && 'quantity' in newProduct) {
                this.quantity = newProduct.quantity;
            }
        },
    },
}
</script>


<template>
    <div :class="{ 'product-counter': true, 'product-counter_active': isOpen}">
        <div v-if="product" class="product-counter__body">
            <div class="product-counter__arrow-back" @click="toggleProduct">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 384 512">
                    <path
                        d="M342.6 150.6c12.5-12.5 12.5-32.8 0-45.3s-32.8-12.5-45.3 0L192 210.7 86.6 105.4c-12.5-12.5-32.8-12.5-45.3 0s-12.5 32.8 0 45.3L146.7 256 41.4 361.4c-12.5 12.5-12.5 32.8 0 45.3s32.8 12.5 45.3 0L192 301.3 297.4 406.6c12.5 12.5 32.8 12.5 45.3 0s12.5-32.8 0-45.3L237.3 256 342.6 150.6z"
                        fill="darkgrey"/>
                </svg>
            </div>
            <div class="product-counter__out">
                <div class="product-counter__card" data-id="1">
                    <div class="product-counter__wrapper1">
                        <div class="product-counter__img">
                        </div>
                        <h2 class="product-counter__title">
                            {{ product.name }}
                        </h2>
                    </div>
                    <div class="product-counter__wrapper2">
                        <div class="product-counter__info-card">
                            <div class="product-counter__portion desktop">
                                {{ $t('Diary.Portion') }}
                            </div>
                            <div class="product-counter__portion mobile">
                                {{ product.name }}
                            </div>
                            <div class="users-data-section">
                                <div class="slider-container">
                                    <form @submit.prevent="addProduct">
                                        <input type="range" :min="1" :max="1250" class="slider" id="myRange" v-model="quantity">
                                    </form>
                                </div>
                                <div class="input-container">
                                    <input type="number" class="styled-input" v-model="quantity">
                                    <span class="input_description">{{ $t('Diary.Grams') }}</span>
                                </div>

                            </div>
                            <ul class="product-counter__portions desktop">
                                <li class="product-counter__one-portion">
                                    <div class="product-counter__gramm">{{ $t('Diary.In100GramsOfProduct') }}</div>
                                </li>
                                <li class="product-counter__one-portion">
                                    <div class="product-counter__gramm">
                                        {{ product.calories }} {{ $t('Diary.KCAL') }} {{ product.proteins }}
                                        {{ $t('Diary.Protein') }}
                                        {{ product.carbohydrates }} {{ $t('Diary.Carbohydrates') }} {{ product.fats }}
                                        {{ $t('Diary.Fats') }}
                                    </div>
                                </li>
                                <li class="product-counter__one-portion">
                                    <div class="product-counter__gramm">
                                        {{ $t('Diary.YouAte') }}
                                        {{ Math.ceil(product.calories / 100 * this.quantity) }} {{ $t('Diary.KCAL') }}
                                        {{ Math.ceil(this.quantity / 100 * product.proteins) }}г {{
                                            $t('Diary.Protein')
                                        }}
                                        {{ Math.ceil(this.quantity / 100 * product.carbohydrates) }}г
                                        {{ $t('Diary.Carbohydrates') }}
                                        {{ Math.ceil(this.quantity / 100 * product.fats) }}г {{ $t('Diary.Fats') }}
                                    </div>
                                </li>
                            </ul>
                            <ul class="product-counter__portions mobile">
                                <li class="product-counter__one-portion">
                                    <span class="product-counter__portion-title">
                                    {{ $t('Diary.In100GramsOfProduct') }}:
                                    </span>
                                </li>
                                <li class="product-counter__one-portion">
                                    <div class="product-counter__gramm">
                                        <span>{{ product.calories }} {{ $t('Diary.KCAL') }}</span>
                                        <span> {{ product.proteins }} {{ $t('Diary.Protein') }}</span>
                                    </div>
                                </li>
                                <li class="product-counter__one-portion">
                                    <div class="product-counter__gramm">
                                        <span>   {{ product.carbohydrates }} {{ $t('Diary.Carbohydrates') }}</span>
                                        <span>   {{ product.fats }} {{ $t('Diary.Fats') }}</span>
                                    </div>
                                </li>
                                <li class="product-counter__one-portion">
                                    <span class="product-counter__portion-title">
                                    {{ $t('Diary.YouAte') }}
                                    </span>
                                </li>

                                <li class="product-counter__one-portion">
                                    <div class="product-counter__gramm">
                                        <span> {{
                                                Math.ceil(product.calories / 100 * this.quantity)
                                            }} {{ $t('Diary.KCAL') }}</span>
                                        <span> {{ Math.ceil(this.quantity / 100 * product.proteins) }}
                                        {{ $t('Diary.Protein') }}</span>
                                    </div>
                                </li>
                                <li class="product-counter__one-portion">
                                    <div class="product-counter__gramm">
                                        <span> {{ Math.ceil(this.quantity / 100 * product.carbohydrates) }}
                                        {{ $t('Diary.Carbohydrates') }}</span>
                                        <span> {{ Math.ceil(this.quantity / 100 * product.fats) }}{{
                                                $t('Diary.Fats')
                                            }}</span>
                                    </div>
                                </li>
                            </ul>
                            <button class="product-counter__btn button-addProduct" @click="addProduct">
                                <slot></slot>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<style scoped lang="scss">
.product-counter {
    position: absolute;
    z-index: 100;
    top: 0;
    right: -100%;
    width: 90%;
    height: 100%;
    background-color: white;
    transition: right 0.4s ease-out 0s;
    box-shadow: $product-counter-shadow;
    overflow: auto;

    &.product-counter_active {
        right: 0;
    }

    &__body {
        width: 100%;
        height: 100%;
        display: flex;
        justify-content: center;
        align-items: center;
    }

    &__arrow-back {
        position: absolute;
        left: 10px;
        top: 10px;
        width: 36px;
        height: 36px;
        cursor: pointer;
        padding: 10px;

        img {
            width: 100%;
        }

        @media (max-width: 768px) {
            left: 0;
            top: 0;
        }
        @media (max-width: 375px) {
            left: 0;
            top: -4px;
        }
    }

    &__out {
        width: 100%;
        height: 100%;
        display: flex;
        justify-content: space-between;
    }

    &__card {
        width: 100%;
        height: 100%;
        display: flex;
        justify-content: space-around;
    }

    &__wrapper1 {
        flex: 0 1 30%;
        height: 100%;
        display: flex;
        flex-direction: column;
        justify-content: center;
        align-items: center;

        @media (max-width: 768px) {
            display: none;
        }
    }

    &__wrapper2 {
        //height: 500px;
        flex: 1 0 auto;
        display: flex;
        flex-direction: column;
        justify-content: center;
        align-items: center;

        @media (max-width: 768px) {
            display: block;
            padding-top: 50px;
        }
        @media (max-width: 375px) {
            display: block;
            padding-top: 30px;
        }
    }

    &__info-card {
        border: 1px solid $border-color;
        width: 100%;
        border-radius: 5px;

        @media (max-width: 768px) {
            width: 320px;
            //height: 500px;
        }
        @media (max-width: 375px) {
            width: 300px;
            //height: 300px;
        }
    }

    &__img {
        padding: 10px;
    }

    &__title {
        padding: 10px;
        font-family: "Roboto", sans-serif;
        font-weight: 600;
        font-size: 14px;
        line-height: 22px;
        //letter-spacing: 0.0625rem;
        text-transform: uppercase;

        @media (max-width: 768px) {
            font-size: 12px;
            display: none;
        }

        span {
            font-weight: 700;
        }
    }

    &__portion {
        padding: 10px;
        background-color: $background-color;
        width: 100%;
        text-align: center;

        font-family: "Roboto", sans-serif;
        font-weight: 600;
        font-size: 14px;
        line-height: 22px;
        letter-spacing: 0.0625rem;
        text-transform: uppercase;

        &.mobile {
            @media (min-width: 768px) {
                display: none;
            }
        }

        &.desktop {
            @media (max-width: 767px) {
                display: none;
            }
        }
    }

    &__range {
        display: flex;
        flex-direction: row;
        justify-content: space-between;
        border-bottom: 1px solid $border-color;
        width: 100%;
        padding: 20px;
    }

    &__portions {
        width: 100%;
        font-family: "Roboto", sans-serif;
        font-weight: 600;
        font-size: 14px;
        line-height: 22px;
        letter-spacing: 0.0625rem;
        text-transform: uppercase;

        @media (max-width: 768px) {
            font-size: 12px;
            letter-spacing: 0.025rem;
        }

        &.desktop {
            @media (min-width: 376px) and (max-width: 767px) {
                display: none;
            }
        }

        &.mobile {
            display: none;

            @media (min-width: 376px) and (max-width: 767px) {
                display: block;
            }
        }

    }

    &__one-portion {
        display: flex;
        justify-content: space-between;
        align-items: center;
        border-bottom: 1px solid $border-color;
        width: 100%;
        position: relative;
        padding: 20px;

        @media (max-width: 768px) {

        }
        @media (max-width: 500px) {
            word-wrap: break-word;
            white-space: normal;
            padding: 10px;
        }
    }

    &__gramm {
        padding: 0 10px 0 10px;
        display: flex;
        justify-content: space-between;
        width: 100%;
    }

    &__btn {
        margin-top: 30px;
        width: 100%;
        padding: 10px 20px;
        font-weight: 700;
        font-size: 17px;
        background-color: $main-color;
        color: white;

        @media (max-width: 768px) {
            font-size: 14px;
        }

        &:hover {
            background-color: darken($main-color, 10%);
        }
    }

    &__close {
        width: 20px;
        height: 20px;
        opacity: 0.9;
        cursor: pointer;
        transition: opacity ease 0.5s;
        position: relative;

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

//Слайдера
.calories-checkBox {
    background-color: green;
    border: 2px solid green;
    position: absolute;
    top: 18px;
    left: 110px;

    &:hover + .calories-checkBox_description {
        display: inline-block;
    }

    &_description {
        display: none;
        font-size: $default-font-size;
        color: white;
        padding: 5px;
        width: 300px; /* или любая другая ширина */
        position: absolute;
        background-color: rgba(0, 0, 0, 0.4);
        right: 0;
        top: -30px;
        z-index: 1234;
    }
}

.input-container {
    position: relative;
}

.input_description {
    position: absolute;
    top: 15%;
    left: -70%;

    padding: 10px;
    font-family: "Roboto", sans-serif;
    font-weight: 600;
    font-size: 14px;
    line-height: 22px;
    text-transform: uppercase;

    @media (max-width: 768px) {
        left: 110%;
        font-size: 12px;
    }
}

.users-data-section {
    display: flex; // Используем flexbox для выравнивания элементов в ряд
    align-items: center; // Центрируем элементы по вертикали
    //gap: 50px; // Добавляем небольшой промежуток между элементами
    padding: 10px 20px 10px 20px;
    @media (max-width: 768px) {
        justify-content: center;
    }

    .slider-container {
        flex: 4;
        position: relative;
        width: 100%;

        @media (max-width: 600px) {
            display: none;
        }

        .slider {
            -webkit-appearance: none;
            width: 30vh;
            height: 10px;
            background: #ddd;
            outline: none;
            opacity: 0.7;
            -webkit-transition: .2s;
            transition: opacity .2s;

            &:hover {
                opacity: 1;
            }

            &::-webkit-slider-thumb {
                -webkit-appearance: none;
                width: 25px;
                height: 25px;
                background: #4CAF50;
                cursor: pointer;
                border-radius: 50%;
                border: solid 2px white;
            }

            &::-moz-range-thumb {
                width: 25px;
                height: 25px;
                background: #4CAF50;
                cursor: pointer;
                border-radius: 50%;
                border: solid 2px white;
            }
        }
    }

    .styled-input {
        border: 2px solid lightgray;
        border-radius: 20px;
        font-size: $default-font-size;
        padding: 10px 20px;
        outline: none;
        text-align: center;
        width: 100px;

        &:focus {
            border-color: #3e8e41;
        }
    }
}
</style>

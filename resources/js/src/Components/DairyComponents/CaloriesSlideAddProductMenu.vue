<script setup>
import {defineEmits, defineProps, reactive, ref, watchEffect} from 'vue';
import {actionTypes} from "@/store/modules/dairy.js";
import CaloriesInput from "@/Components/DairyComponents/CaloriesInput.vue";
import {useStore} from 'vuex';

const props = defineProps({
    isOpen: {
        type: Boolean,
        required: true,
    },
    name: {
        type: String,
        required: true,
    },
});

const emits = defineEmits(['update']);

const store = useStore();
const quantity = ref(1);
const product = reactive({
    calories: 0,
    carbohydrates: 0,
    fats: 0,
    fibers: 0,
    name: '',
    proteins: 0,
    quantity: 0,
});
watchEffect(() => {
    product.name = props.name;
});
const toggleProduct = () => {
    emits('update', !props.isOpen);
    quantity.value = 1;
};

const addProduct = () => {
    if (quantity.value > 0 && quantity.value < 1250) {
        store.dispatch(actionTypes.saveUsersCurrentProducts, {
            product: product, quantity: quantity.value,
        }).then(() => {
            quantity.value = 1;
            product.calories = 0;
            product.carbohydrates = 0;
            product.fats = 0;
            product.proteins = 0;
            emits('update', !props.isOpen);
        });
    } else {
        store.dispatch('setError', this.$t('Notification.Error.invalidData'));
    }
};

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
                            {{ name }}
                        </h2>
                    </div>
                    <div class="product-counter__wrapper2">
                        <div class="product-counter__info-card">
                            <div class="product-counter__portion desktop">
                                {{ $t('Diary.Portion') }}
                            </div>
                            <div class="product-counter__portion mobile">
                                {{ name }}
                            </div>
                            <div class="users-data-section">
                                <div class="slider-container">
                                    <form @submit.prevent="addProduct">
                                        <input type="range" :min="1" :max="1250" class="slider" id="myRange"
                                               v-model="quantity">
                                    </form>
                                </div>
                                <div class="input-container">
                                    <input type="text" class="styled-input" v-model="quantity">
                                    <span class="input_description">{{ $t('Diary.Grams') }}</span>
                                </div>

                            </div>
                            <ul class="product-counter__portions desktop">
                                <li class="product-counter__one-portion">
                                    <div class="product-counter__gramm">{{ $t('Diary.In100GramsOfProduct') }}</div>
                                </li>
                                <li class="product-counter__one-portion">
                                    <div class="product-counter__gramm">
                                        <div>
                                            <calories-input v-model="product.calories"/>
                                            {{ $t('Diary.KCAL') }}
                                        </div>
                                        <div>
                                            <calories-input v-model="product.proteins"/>
                                            {{ $t('Diary.Protein') }}
                                        </div>
                                        <div>
                                            <calories-input v-model="product.carbohydrates"/>
                                            {{ $t('Diary.Carbohydrates') }}
                                        </div>
                                        <div>
                                            <calories-input v-model="product.fats"/>
                                            {{ $t('Diary.Fats') }}
                                        </div>
                                    </div>
                                </li>
                                <li class="product-counter__one-portion">
                                    <div class="product-counter__gramm">
                                        {{ $t('Diary.YouAte') }}
                                        {{ Math.ceil(product.calories / 100 * quantity) }} {{ $t('Diary.KCAL') }}
                                        {{ Math.ceil(quantity / 100 * product.proteins) }}г {{
                                            $t('Diary.Protein')
                                        }}
                                        {{ Math.ceil(quantity / 100 * product.carbohydrates) }}г
                                        {{ $t('Diary.Carbohydrates') }}
                                        {{ Math.ceil(quantity / 100 * product.fats) }}г {{ $t('Diary.Fats') }}
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
                                        <div class="product-counter__span">
                                            <calories-input v-model="product.calories"/>
                                            <span> {{ $t('Diary.KCAL') }}</span>
                                        </div>
                                        <div class="product-counter__span">
                                            <calories-input v-model="product.proteins"/>
                                            <span>{{ $t('Diary.Protein') }}</span></div>
                                    </div>
                                </li>
                                <li class="product-counter__one-portion">
                                    <div class="product-counter__gramm">
                                        <div class="product-counter__span">
                                            <calories-input v-model="product.carbohydrates"/>
                                            <span>{{
                                                    $t('Diary.Carbohydrates')
                                                }}</span></div>
                                        <div class="product-counter__span">
                                            <calories-input v-model="product.fats"/>
                                            <span> {{ $t('Diary.Fats') }}</span></div>
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
                                                Math.ceil(product.calories / 100 * quantity)
                                            }} {{ $t('Diary.KCAL') }}</span>
                                        <span> {{ Math.ceil(quantity / 100 * product.proteins) }}
                                        {{ $t('Diary.Protein') }}</span>
                                    </div>
                                </li>
                                <li class="product-counter__one-portion">
                                    <div class="product-counter__gramm">
                                        <span> {{ Math.ceil(quantity / 100 * product.carbohydrates) }}
                                        {{ $t('Diary.Carbohydrates') }}</span>
                                        <span> {{ Math.ceil(quantity / 100 * product.fats) }}{{
                                                $t('Diary.Fats')
                                            }}</span>
                                    </div>
                                </li>
                            </ul>
                            <button class="product-counter__btn button-addProduct" @click="addProduct">
                                {{ $t('Diary.AddProduct') }}
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
        @media (max-width: 500px) {
            padding: 5px;
            font-size: 12px;
        }

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

    &__span {
        display: flex;
        flex-direction: column;

        & span {
            text-align: center;
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
        //display: flex;
        justify-content: space-between;
        align-items: center;
        border-bottom: 1px solid $border-color;
        width: 100%;
        position: relative;
        padding: 20px;

        @media (max-width: 768px) {
            display: block;
            justify-content: space-between;
            align-items: center;
            border-bottom: 1px solid $border-color;
            width: 100%;
            position: relative;
            padding: 20px;
        }
        @media (max-width: 375px) {
        //@media (max-width: 500px) {
            word-wrap: break-word;
            white-space: normal;
            padding: 10px;
        }
    }

    &__gramm {
        display: flex;
        flex-wrap: wrap;
        justify-content: space-around;
        @media (max-width: 375px) {
            justify-content: space-between;
        }

        & div {
            padding-bottom: 5px;
        }
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
        @media (max-width: 375px) {
        //@media (max-width: 500px) {
            margin-top: 10px;
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
    background-color: green; /* Задает зеленый фон для чекбокса */
    border: 2px solid green; /* Задает зеленую границу для чекбокса */
    position: absolute;
    top: 18px;
    left: 110px;

    &:hover + .calories-checkBox_description {
        display: inline-block; /* Показать при наведении на чекбокс */
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
    @media (max-width: 375px) {
    //@media (max-width: 500px) {
        padding: 5px 10px 5px 10px;
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
        border: 2px solid lightgray; /* Цвет рамки */
        border-radius: 20px; /* Радиус скругления углов */
        //font-size: $default-font-size; /* Размер шрифта */
        padding: 10px 20px; /* Внутренние отступы */
        //box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1); /* Тень */
        outline: none; /* Убираем стандартный фокус */
        text-align: center; /* Текст по центру */
        width: 100px;

        //@media (max-width: 500px) {
        @media (max-width: 375px) {
            height: 40px;
        }


        &:focus {
            border-color: #3e8e41; /* Цвет рамки при фокусе */
        }
    }
}
</style>

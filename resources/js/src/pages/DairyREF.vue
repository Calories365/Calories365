<script>
export default {
    name: "Dairy",
    methods: {}
}

</script>
<template>
    <div class="calories-section-wrappper">
        <div class="obertka">
            <div class="arrows arrow-left previous"></div>
            <div class=" obertka__item changer previous">
                previous day
            </div>
            <span class="obertka__item breakfast">Breakfast</span>
            <span class="obertka__item dinner">Dinner</span>
            <span class="obertka__item lunch">Lunch</span>
            <div class=" obertka__item changer next">next day</div>
            <div class="arrows arrow-right next"></div>
        </div>
        <section class="main__addProducts section-addProducts">
            <div class="section-addProducts__container">
                <div class="section-addProducts__block">
                    <div class="section-addProducts__header">
                        <div class="section-addProducts__title">Завтрак</div>
                        <div class="section-addProducts__summary">
                            Итого: <span class="section-addProducts__num">0</span> калорий
                        </div>
                    </div>
                    <div class="section-addProducts__begin">
                        <div class="section-addProducts__content">
                            <div class="added-product">
                                <ul class="added-product__list">

                                </ul>
                            </div>
                            <!-- <h2 class="section-addProducts__title">Начать!</h2> -->
                            <p class="section-addProducts__text">
                                Нажмите "+" чтобы добавить запись в журнал.
                            </p>
                            <div class="section-addProducts__btn"></div>
                            <div class="slide-menu">
                                <div class="slide-menu__wrapper">
                                    <h2 class="slide-menu__title">Добавить продукт</h2>
                                    <div class="slide-menu__closeModal"></div>
                                </div>
                                <form
                                    action="http://localhost:63342/%D1%8F%D1%94%D1%87%D0%BA%D0%B0/dist/daily-ration.html?_ijt=en414k0kkb9c3hpdp3n5nf67eq&amp;_ij_reload=RELOAD_ON_SAVE"
                                    class="slide-menu__form">
                                    <label for="find-product">
                                        <input type="text" id="find-product" placeholder="Имя продукта">
                                    </label>
                                </form>
                                <div class="slide-menu__boxForProducts">
                                    <h2 class="slide-menu__subtitle">Популярные продукты</h2>

                                    <ul class="slide-menu__list">
                                        <li class="slide-menu__item product-card" data-productname="Яблоко">
                                            <div class="product-card__body" data-name="apple" data-calories="52"
                                                 data-id="1">
                                                <div class="product-card__img">
                                                    <img src="./Document_files/apple.png" alt="">
                                                </div>
                                                <h2 class="product-card__title">Яблоко</h2>
                                            </div>
                                        </li>
                                        <li class="slide-menu__item product-card" data-productname="Помидор"
                                        >
                                            <div class="product-card__body" data-name="tomato" data-calories="18"
                                                 data-id="2">
                                                <div class="product-card__img">
                                                    <img src="./Document_files/tomato.png" alt="">
                                                </div>
                                                <h2 class="product-card__title">Помидор</h2>
                                            </div>
                                        </li>
                                        <li class="slide-menu__item product-card" data-productname="Апельсин">
                                            <div class="product-card__body" data-name="orange" data-calories="47"
                                                 data-id="3">
                                                <div class="product-card__img">
                                                    <img src="./Document_files/orange.png" alt="">
                                                </div>
                                                <h2 class="product-card__title">Апельсин</h2>
                                            </div>
                                        </li>
                                        <li class="slide-menu__item product-card" data-productname="Банан">
                                            <div class="product-card__body" data-name="banana" data-calories="89"
                                                 data-id="4">
                                                <div class="product-card__img">
                                                    <img src="./Document_files/banana.png" alt="">
                                                </div>
                                                <h2 class="product-card__title">Банан</h2>
                                            </div>
                                        </li>
                                        <li class="slide-menu__item product-card" data-productname="Яйцо">
                                            <div class="product-card__body" data-name="eggs" data-calories="70"
                                                 data-id="5">
                                                <div class="product-card__img">
                                                    <img src="./Document_files/eggs.png" alt="">
                                                </div>
                                                <h2 class="product-card__title">Яйцо</h2>
                                            </div>
                                        </li>
                                        <li class="slide-menu__item product-card" data-productname="Огурец">
                                            <div class="product-card__body" data-name="pickle" data-calories="16"
                                                 data-id="6">
                                                <div class="product-card__img">
                                                    <img src="./Document_files/pickle.png" alt="">
                                                </div>
                                                <h2 class="product-card__title">Огурец</h2>
                                            </div>
                                        </li>
                                        <li class="slide-menu__item product-card" data-productname="Курица">
                                            <div class="product-card__body" data-name="chicken" data-calories="239"
                                                 data-id="7">
                                                <div class="product-card__img">
                                                    <img src="./Document_files/chicken.png" alt="">
                                                </div>
                                                <h2 class="product-card__title">Курица</h2>
                                            </div>
                                        </li>
                                        <li class="slide-menu__item product-card" data-productname="Шоколад">
                                            <div class="product-card__body" data-name="chocolate" data-calories="546"
                                                 data-id="8">
                                                <div class="product-card__img">
                                                    <img src="./Document_files/chocolate.png" alt="">
                                                </div>
                                                <h2 class="product-card__title">Шоколад</h2>
                                            </div>
                                        </li>
                                        <li class="slide-menu__item product-card" data-productname="Макароны">
                                            <div class="product-card__body" data-name="macaroni" data-calories="371"
                                                 data-id="9">
                                                <div class="product-card__img">
                                                    <img src="./Document_files/macaroni.png" alt="">
                                                </div>
                                                <h2 class="product-card__title">Макароны</h2>
                                            </div>
                                        </li>
                                        <li class="slide-menu__item product-card" data-productname="Овсянка">
                                            <div class="product-card__body" data-name="porridge" data-calories="68"
                                                 data-id="10">
                                                <div class="product-card__img">
                                                    <img src="./Document_files/porridge.png" alt="">
                                                </div>
                                                <h2 class="product-card__title">Овсянка</h2>
                                            </div>
                                        </li>
                                    </ul>
                                </div>
                            </div>
                            <div class="product-counter">
                                <div class="product-counter__body">
                                    <div class="product-counter__arrow-back">
                                        <img src="./Document_files/arrow.png" alt="">
                                    </div>
                                    <div class="product-counter__out">
                                        <div class="product-counter__card" data-id="5">
                                            <div class="product-counter__wrapper1">
                                                <div class="product-counter__img">
                                                    <img src="./Document_files/eggs-xl-size.png" alt="">
                                                </div>
                                                <h2 class="product-counter__title">
                                                    <span> Имя продукта: </span> Яйцо
                                                </h2>
                                            </div>
                                            <div class="product-counter__wrapper2">
                                                <div class="product-counter__info-card">

                                                    <div class="product-counter__portion">Порция</div>
                                                    <div class="product-counter__range">
                                                        <input type="range" class="product-counter__input" min="0"
                                                               max="300" value="0">
                                                        <div class="product-counter__num">
          <span class="product-counter__quantity-gramm">
            0</span>
                                                            <span class="product-counter__span"> грамм</span>
                                                        </div>
                                                    </div>
                                                    <ul class="product-counter__portions" data-calories="70"
                                                        data-product="eggs" data-id="5">
                                                        <li class="product-counter__one-portion">
                                                            <div class="product-counter__gramm">
                                                                В 100 граммах Яйцо <span> 70 </span> каллорий
                                                            </div>
                                                        </li>
                                                        <li class="product-counter__one-portion">
                                                            <div class="product-counter__gramm">
                                                                Одна порция Яйцо - это 150 грамм
                                                            </div>
                                                        </li>
                                                        <li class="product-counter__one-portion">
                                                            <div class="product-counter__gramm">
                                                                Вы съели <span data-summonfood="all-food"> 0 </span>
                                                                каллорий
                                                            </div>
                                                        </li>
                                                    </ul>
                                                    <button class="product-counter__btn button-addProduct">
                                                        Добавить продукт
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="edit-product">
                                <div class="edit-product__body">
                                    <!-- <div class="edit-product__arrow-back">
                                      <img src="./img/arrow.png" alt="" />
                                    </div> -->
                                    <div class="edit-product__content"></div>
                                    <div class="edit-product__close"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </div>
</template>

<style scoped lang="scss">
@import url(https://fonts.googleapis.com/css?family=PT+Sans:regular,italic,700,700italic&display=swap);

@font-face {
    font-family: icons;
    font-display: swap;
    src: url("../fonts/icons.woff2") format("woff2"), url("../fonts/icons.woff") format("woff");
    font-weight: 400;
    font-style: normal;
}

@font-face {
    font-family: "icons";
    src: url("../fonts/icons.eot?kakdlm");
    src: url("../fonts/icons.eot?kakdlm#iefix") format("embedded-opentype"), url("../fonts/icons.ttf?kakdlm") format("truetype"), url("../fonts/icons.woff?kakdlm") format("woff"), url("../fonts/icons.svg?kakdlm#icons") format("svg");
    font-weight: normal;
    font-style: normal;
    font-display: block;
}

[class^=_icon-]::before,
[class*=" _icon-"]::before {
    /* use !important to prevent issues with browser extensions that change fonts */
    font-family: "icons" !important;
    speak: never;
    font-style: normal;
    font-weight: normal;
    font-variant: normal;
    text-transform: none;
    line-height: 1;
    /* Better Font Rendering =========== */
    -webkit-font-smoothing: antialiased;
    -moz-osx-font-smoothing: grayscale;
}

._icon-fb:before {
    content: "\e900";
}

._icon-Group:before {
    content: "\e901";
}

._icon-insta:before {
    content: "\e902";
}

._icon-Linkedin:before {
    content: "\e903";
}

body {
    color: #21243d;
}

* {
    padding: 0;
    margin: 0;
    border: 0;
}

*,
*:before,
*:after {
    -moz-box-sizing: border-box;
    -webkit-box-sizing: border-box;
    box-sizing: border-box;
}

:focus,
:active {
    outline: none;
}

a:focus,
a:active {
    outline: none;
}

nav,
footer,
header,
aside,
section {
    display: block;
}

html,
body {
    height: 100%;
    width: 100%;
    font-size: 100%;
    line-height: 1;
    font-size: 14px;
    -ms-text-size-adjust: 100%;
    -moz-text-size-adjust: 100%;
    -webkit-text-size-adjust: 100%;
}

input,
button,
textarea {
    font-family: inherit;
}

input::-ms-clear {
    display: none;
}

button {
    cursor: pointer;
}

button::-moz-focus-inner {
    padding: 0;
    border: 0;
}

a,
a:visited {
    text-decoration: none;
}

ul li {
    list-style: none;
}

img {
    vertical-align: top;
}

h1,
h2,
h3,
h4,
h5,
h6 {
    font-size: inherit;
    font-weight: 400;
}

body {
    font-family: "PT Sans";
}

.wrapper {
    width: 100%;
    min-height: 100%;
    overflow: hidden;
    display: flex;
    flex-direction: column;
}

.icon-menu {
    display: none;
}

@media (max-width: 768px) {
    .icon-menu {
        display: block;
        position: relative;
        width: 30px;
        height: 18px;
        cursor: pointer;
        z-index: 5;
    }

    .icon-menu span {
        transition: all 0.3s ease 0s;
        top: calc(50% - 1px);
        left: 0px;
        position: absolute;
        width: 100%;
        height: 2px;
        background-color: #000;
    }

    .icon-menu span:first-child {
        top: 0px;
    }

    .icon-menu span:last-child {
        top: auto;
        bottom: 0px;
    }

    .icon-menu._active span {
        transform: scale(0);
    }

    .icon-menu._active span:first-child {
        transform: rotate(-45deg);
        top: calc(50% - 1px);
    }

    .icon-menu._active span:last-child {
        transform: rotate(45deg);
        bottom: calc(50% - 1px);
    }
}

.welcome {
    height: 100vh;
    overflow: hidden;
}

.welcome__container {
    max-width: 1400px;
    height: 100%;
    margin: 0px auto;
    display: flex;
}

@media (min-width: 120em) {
    .welcome__container {
        max-width: 1600px;
    }
}

@media (min-width: 140em) {
    .welcome__container {
        max-width: 1800px;
    }
}

@media (min-width: 768px) {
    .welcome__left {
        flex: 0 0 50%;
    }
}

.welcome__left img {
    width: 100%;
    height: 100%;
}

.welcome__right {
    flex: 0 0 50%;
    height: 100%;
    display: flex;
    align-items: center;
    justify-content: center;
}

@media (min-width: 2000px) {
    .welcome__right {
        font-size: 28px;
        font-weight: 700;
    }
}

.card-registr {
    height: 60%;
    width: 55%;
    padding: 0px 10px 20px 10px;
}

@media (min-width: 120em) {
    .card-registr {
        height: 50%;
        width: 50%;
        padding: 50px 10px 20px 10px;
    }
}

@media (min-width: 140em) {
    .card-registr {
        height: 50%;
        width: 50%;
        padding: 50px 10px 20px 10px;
    }
}

.card-registr__body {
    display: flex;
    flex-direction: column;
}

.card-registr__title {
    font-size: 20px;
    font-weight: 700;
    margin-bottom: 4px;
}

@media (min-width: 2000px) {
    .card-registr__title {
        font-size: 35px;
    }
}

.card-registr__subtitle {
    color: gray;
    margin-bottom: 10px;
}

.card-registr__error {
    color: red;
    padding: 5px;
    background-color: rgba(224, 40, 40, 0.2);
    text-align: center;
}

.card-registr__form {
    display: flex;
    flex-direction: column;
    margin-top: 10px;
    margin-bottom: 10px;
}

@media (min-width: 2000px) {
    .card-registr__form {
        margin-top: 20px;
        margin-bottom: 10px;
    }
}

@media (min-width: 2000px) {
    .card-registr__form input {
        margin-top: 20px;
        font-size: 25px;
    }
}

@media (min-width: 2000px) {
    .card-registr__form label {
        margin-top: 10px;
    }
}

.card-registr__sign-in {
    color: gray;
}

.card-registr__sign-in-btn {
    font-weight: 700;
    margin-top: 5px;
    padding: 10px 50px 10px 50px;
    border-radius: 10px;
    border: 1.5px solid black;
    background-color: #fff;
    color: black;
}

@media (min-width: 2000px) {
    .card-registr__sign-in-btn {
        padding: 20px 50px 20px 50px;
        font-size: 25px;
    }
}

.form-registration input {
    border-bottom: 1.5px solid #000;
    margin-bottom: 10px;
    padding: 7px 0;
}

.form-registration__btn {
    font-weight: 700;
    margin-top: 20px;
    padding: 10px 50px 10px 50px;
    border-radius: 10px;
    background-color: #000;
    color: white;
}

@media (min-width: 2000px) {
    .form-registration__btn {
        padding: 20px 50px 20px 50px;
        font-size: 25px;
    }
}

.hide {
    display: none;
}

.header {
    margin-top: 20px;
    font-size: 20px;
    font-weight: 700;
}

.header__container {
    max-width: 1200px;
    height: 50px;
    margin: 0px auto;
    padding: 0 10px;
}

@media (min-width: 120em) {
    .header__container {
        max-width: 1600px;
    }
}

@media (min-width: 140em) {
    .header__container {
        max-width: 1800px;
    }
}

.header__menu {
    display: flex;
    align-items: center;
    justify-content: space-between;
    height: 100%;
}

.header__logo-parent {
    height: 100%;
}

.header__logo {
    flex: 0 0 60px;
}

.header__logo img {
    height: 90%;
}

.menu__body {
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex: 0 1 70%;
    height: 100%;
}

.menu__list {
    display: flex;
    flex: 0 0 80%;
    justify-content: space-around;
}

.menu__item:not(:last-child) {
    margin-right: 50px;
}

.menu__link {
    color: black;
}

.my-profile {
    height: 100%;
}

.my-profile__block {
    height: 100%;
    display: flex;
    align-items: center;
}

.my-profile__parent {
    font-size: 18px;
    font-weight: 700;
    display: flex;
    height: 70%;
    align-items: center;
    gap: 5px;
    border-radius: 5px;
    border: 2px solid black;
    padding: 0px 10px 0px 10px;
}

.my-profile__parent img {
    height: 40%;
}

.main {
    height: calc(100vh - 70px);
    margin-top: 30px;
}

.section-start {
    height: 20%;
}

.section-start__container {
    max-width: 1200px;
    margin: 0px auto;
    height: 100%;
    padding: 0 10px;
}

@media (min-width: 120em) {
    .section-start__container {
        max-width: 1600px;
    }
}

@media (min-width: 140em) {
    .section-start__container {
        max-width: 1800px;
    }
}

.section-start__block {
    background: url("../img/Scribble-Light.png") 0% 20%/220px no-repeat, url("../img/Scribble-Light2.png") 100% 80%/220px no-repeat, black;
    max-width: 100%;
    height: 100%;
    color: white;
    display: flex;
    align-items: center;
    justify-content: center;
    box-shadow: 9px 9px 17px -7px rgba(0, 0, 0, 0.67);
    border-radius: 5px;
}

.section-start__text {
    flex: 0 1 600px;
    text-align: center;
    line-height: 1;
    font-weight: 700;
    font-size: 18px;
}

@media (min-width: 120em) {
    .section-start__text {
        line-height: 2;
        flex: 0 1 800px;
    }
}

@media (min-width: 140em) {
    .section-start__text {
        line-height: 2.5;
        flex: 0 1 1000px;
    }
}

.section-addProducts {
    margin-top: 20px;
    height: 85%;
}

.section-addProducts__container {
    max-width: 1200px;
    margin: 0px auto;
    height: 100%;
    padding: 0 10px;
}

@media (min-width: 120em) {
    .section-addProducts__container {
        max-width: 1600px;
    }
}

@media (min-width: 140em) {
    .section-addProducts__container {
        max-width: 1800px;
    }
}

.section-addProducts__block {
    background-color: #f0efef;
    height: 100%;
    display: flex;
    flex-direction: column;
    font-weight: 700;
    font-size: 18px;
    box-shadow: 9px 9px 17px -7px rgba(0, 0, 0, 0.67);
    border-radius: 5px;
}

.section-addProducts__header {
    height: 10%;
    border-bottom: 1px solid darkgray;
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 20px;
    background-color: #f0efef;
}

.section-addProducts__begin {
    height: 100%;
    overflow: hidden;
}

.section-addProducts__content {
    height: 100%;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: start;
    gap: 10px;
    position: relative;
    overflow: hidden;
}

.section-addProducts__btn {
    border: 2px solid lightgrey;
    background-color: #fff;
    font-size: 16px;
    height: 2.5em;
    width: 2.5em;
    border-radius: 999px;
    position: relative;
    cursor: pointer;
}

.section-addProducts__btn:hover {
    border: 2px solid black;
    transition: all 0.3s ease 0s;
}

.section-addProducts__btn:after, .section-addProducts__btn:before {
    content: "";
    display: block;
    background-color: grey;
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
}

.section-addProducts__btn:before {
    height: 1em;
    width: 0.2em;
}

.section-addProducts__btn:after {
    height: 0.2em;
    width: 1em;
}

.section-addProducts__btn:hover:before, .section-addProducts__btn:hover:after {
    background-color: black;
    transition: all 0.3s ease 0s;
}

.slide-menu {
    position: absolute;
    top: 0;
    right: -100%;
    width: 90%;
    height: 100%;
    background-color: white;
    transition: right 0.4s ease-out 0s;
    box-shadow: -5px 0px 5px -4px rgba(0, 0, 0, 0.24);
    overflow: auto;
}

.slide-menu_active {
    right: 0;
}

.slide-menu__wrapper {
    display: flex;
    height: 10%;
    align-items: center;
    justify-content: space-between;
    padding: 0 20px;
}

.slide-menu__title {
    font-weight: 700;
}

.slide-menu__form {
    margin: 5px 0px 10px 20px;
    height: 10%;
}

.slide-menu__form input {
    border: 1px solid #000;
    padding: 10px 0px 10px 35px;
    background-color: #f0efef;
    border-radius: 7px;
    width: 30%;
    font-size: 18px;
    //background: url("../img/search-interface-symbol.png") 2% 50%/18px no-repeat;
}

.slide-menu__subtitle {
    padding: 10px 20px;
    background-color: #dbd9d9;

}

.slide-menu__closeModal {
    position: absolute;
    z-index: 2;
    top: 10px;
    right: 20px;
    width: 24px;
    height: 24px;
    opacity: 0.9;
    cursor: pointer;
    transition: opacity ease 0.5s;
}

.slide-menu__closeModal::before, .slide-menu__closeModal::after {
    content: "";
    position: absolute;
    top: 10px;
    display: block;
    width: 24px;
    height: 3px;
    background: #000;
}

.slide-menu__closeModal::before {
    transform: rotate(45deg);
}

.slide-menu__closeModal::after {
    transform: rotate(-45deg);
}

.slide-menu__list {
    margin: 10px 0px 10px 0px;
}

.product-card__body {
    height: 20px;
    width: 100%;
    border-bottom: 1px solid #dbd9d9;
    display: flex;
    gap: 20px;
    justify-content: flex-start;
    align-items: center;
    padding: 25px;
    cursor: pointer;
}

.product-card__body:hover {
    background-color: #f0efef;
}

.product-card__img {
    pointer-events: none;
}

.product-card__title {
    pointer-events: none;
}

.product-counter {
    position: absolute;
    top: 0;
    right: -100%;
    width: 90%;
    height: 100%;
    background-color: white;
    transition: right 0.4s ease-out 0s;
    box-shadow: -5px 0px 5px -4px rgba(0, 0, 0, 0.24);
    overflow: auto;
}

.product-counter_active {
    right: 0;
}

.product-counter__body {
    width: 100%;
    height: 100%;
    display: flex;
    justify-content: center;
    align-items: center;
}

.product-counter__arrow-back {
    position: absolute;
    left: 10px;
    top: 10px;
    width: 24px;
    height: 24px;
    cursor: pointer;
}

.product-counter__arrow-back img {
    width: 100%;
}

.product-counter__out {
    width: 100%;
    height: 100%;
    display: flex;
    justify-content: space-between;
}

.product-counter__card {
    width: 100%;
    height: 100%;
    display: flex;
    justify-content: space-around;
}

.product-counter__wrapper1 {
    flex: 0 1 30%;
    height: 100%;
    display: flex;
    flex-direction: column;
    justify-content: center;
    align-items: center;
}

.product-counter__wrapper2 {
    height: 100%;
    flex: 1 0 auto;
    display: flex;
    flex-direction: column;
    justify-content: center;
    align-items: center;
}

.product-counter__info-card {
    border: 1px solid #dbd9d9;
    width: 70%;
    border-radius: 5px;
}

.product-counter__img {
    padding: 10px;
}

.product-counter__title {
    padding: 10px;
}

.product-counter__title span {
    font-weight: 700;
}

.product-counter__portion {
    padding: 10px;
    background-color: #f0efef;
    width: 100%;
    text-align: center;
}

.product-counter__range {
    display: flex;
    flex-direction: row;
    justify-content: space-between;
    border-bottom: 1px solid #dbd9d9;
    width: 100%;
    padding: 20px;
}

.product-counter__portions {
    width: 100%;
    font-size: 16px;
}

.product-counter__one-portion {
    display: flex;
    justify-content: space-between;
    align-items: center;
    border-bottom: 1px solid #dbd9d9;
    width: 100%;
    position: relative;
    padding: 20px;
}

.product-counter__btn {
    margin-top: 15px;
    width: 100%;
    padding: 10px 20px;
    font-weight: 700;
    font-size: 17px;
    background-color: rgba(110, 196, 223, 0.8);
    color: white;
}

.product-counter__btn:hover {
    background-color: #6ec4df;
}

.product-counter__close {
    width: 20px;
    height: 20px;
    opacity: 0.9;
    cursor: pointer;
    transition: opacity ease 0.5s;
    position: relative;
}

.product-counter__close::before, .product-counter__close::after {
    content: "";
    position: absolute;
    top: 10px;
    display: block;
    width: 18px;
    height: 2px;
    background: #000;
}

.product-counter__close::before {
    transform: rotate(45deg);
}

.product-counter__close::after {
    transform: rotate(-45deg);
}

.added-product {
    width: 100%;
    overflow: auto;
    height: 70%;
}

.added-product__list {
    width: 100%;
    overflow: auto;
}

.added-product__item {
    height: 50px;
    width: 100%;
    background-color: white;
    border-bottom: 1px solid #dbd9d9;
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 0px 20px;
    cursor: pointer;
}

.added-product__item:hover {
    background-color: #f0efef;
}

.added-product__content {
    flex: 0 0 90%;
    display: flex;
    justify-content: space-between;
}

.added-product__left {
    display: flex;
    align-items: center;
    gap: 10px;
    pointer-events: none;
}

.added-product__right {
    display: flex;
    align-items: center;
    gap: 20px;
    pointer-events: none;
}

.added-product__close {
    width: 20px;
    height: 20px;
    opacity: 0.9;
    transition: opacity ease 0.5s;
    transition: transform 0.3s ease 0s;
    position: relative;
}

.added-product__close:hover {
    transform: scale(1.3);
}

.added-product__close::before, .added-product__close::after {
    content: "";
    position: absolute;
    top: 10px;
    display: block;
    width: 18px;
    height: 2px;
    background: #000;
}

.added-product__close::before {
    transform: rotate(45deg);
}

.added-product__close::after {
    transform: rotate(-45deg);
}

.edit-product {
    position: absolute;
    top: 0;
    right: -100%;
    width: 90%;
    height: 100%;
    background-color: white;
    transition: right 0.4s ease-out 0s;
    box-shadow: -5px 0px 5px -4px rgba(0, 0, 0, 0.24);
    overflow: auto;
}

.edit-product_active {
    right: 0;
}

.edit-product__body {
    display: flex;
    justify-content: space-between;
    padding: 20px 10px 10px 30px;
}

.edit-product__arrow-back {
    width: 20px;
    height: 20px;
    cursor: pointer;
}

.edit-product__arrow-back img {
    width: 100%;
}

.edit-product__content {
    flex: 0 0 90%;
}

.edit-product__close {
    width: 24px;
    height: 24px;
    opacity: 0.9;
    cursor: pointer;
    transition: opacity ease 0.5s;
    position: relative;
}

.edit-product__close::before, .edit-product__close::after {
    content: "";
    position: absolute;
    top: 10px;
    display: block;
    width: 24px;
    height: 2px;
    background: #000;
}

.edit-product__close::before {
    transform: rotate(45deg);
}

.edit-product__close::after {
    transform: rotate(-45deg);
}

.obertka {
    display: flex;
    justify-content: center;
    align-items: center;
}

.obertka__item {
    cursor: pointer;
    width: 100px;
    height: 50px;
    border: 1px solid black;
    text-align: center;
    margin: 10px;
    display: flex;
    justify-content: center;
    align-items: center;
}

.obertka__item:hover {
    background-color: #f0efef;
}

.active {
    background-color: #f0efef;
    font-weight: bold;
    border-radius: 5px;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.3);
}

.arrows {
    cursor: pointer;
}

.arrow-right {
    width: 0;
    height: 0;
    border-top: 10px solid transparent;
    border-bottom: 10px solid transparent;
    border-left: 10px solid black;
}

.arrow-left {
    width: 0;
    height: 0;
    border-top: 10px solid transparent;
    border-bottom: 10px solid transparent;
    border-right: 10px solid black;
}

</style>

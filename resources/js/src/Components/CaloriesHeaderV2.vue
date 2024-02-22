<script>
import {actionTypes} from "@/store/modules/auth";
import {mapState} from "vuex";
import {FontAwesomeIcon} from "@fortawesome/vue-fontawesome";
import caloriesCalculationLocaleChanger from "@/Components/CaloriesCalculationLocaleChanger.vue";
import CaloriesBurgerMenu from "@/Components/CaloriesBurgerMenu.vue";
import CaloriesCalculationDropdown from "@/Components/CaloriesCalculationDropdown.vue";


export default {
    name: "CaloriesHeaderV2",
    components: {CaloriesCalculationDropdown, CaloriesBurgerMenu, FontAwesomeIcon, caloriesCalculationLocaleChanger},
    data() {
        return {
            isBurgerOpen: false,
            // menuItems: ['home', 'calculation', 'diary', 'stats', 'recipes', 'instructions', 'cabinet', 'goals'],
            menuItems: ['home', 'calculation', 'diary', 'cabinet', 'stats'],
        };
    },
    computed: {
        ...mapState({
            currentUser: state => state.auth.currentUser,
            isEmailVerified() {
                if (this.currentUser && this.currentUser.email_verified_at) {
                    return true;
                }
                return false;
            },
        }),
    },
    methods: {
        toggleBurger() {
            this.isBurgerOpen = !this.isBurgerOpen;
        },
        closeBurger() {
            this.isBurgerOpen = false;
        },
        logout() {
            this.isBurgerOpen = false;
            this.$store.dispatch(actionTypes.logout).then((errors) => {
                console.log(errors);
                this.$router.push({name: 'login'})
            })
        },
        resendVerificationEmail() {
            this.isBurgerOpen = false;
            this.$store.dispatch(actionTypes.resendVerificationEmail).then((errors) => {
                console.log(errors);
            })
        },
    },
    mounted() {
        // Создаём обработчик прокрутки
        this.handleScroll = () => {
            const headerBottom = this.$refs.headerBottom; // Обращаемся к элементу через $refs
            const mainWrapper = document.querySelector('.main-wrapper');
            const fixedClass = 'fixed-header';
            const layoutFixedMarginClass = 'layout-fixed-margin';
            const threshold = 55;
            const scrollY = window.scrollY || window.pageYOffset;

            if (scrollY > threshold) {
                headerBottom.classList.add(fixedClass);
                if (mainWrapper) {
                    mainWrapper.classList.add(layoutFixedMarginClass);
                }
            } else {
                headerBottom.classList.remove(fixedClass);
                if (mainWrapper) {
                    mainWrapper.classList.remove(layoutFixedMarginClass);
                }
            }
        };

        // Добавляем обработчик прокрутки к window
        window.addEventListener('scroll', this.handleScroll);
        this.handleScroll(); // Вызываем для инициализации при монтировании
    },
    beforeDestroy() { // Используйте beforeUnmount в Vue 3
        // Удаляем обработчик прокрутки при уничтожении компонента
        window.removeEventListener('scroll', this.handleScroll);
    },

};
</script>

<template>
    <header class="main-header">
        <!--<header class="main-header fixed-header">-->
        <div class="main-header_top ">
            <div class="main-header_container">
                <div class="main-header_box">

                    <div class="main-header_logo logo-in-header">
                        <a href="#" rel="home">
                            <img class="logo-in-header_whole" src="../assets/CALORIES365vectorgreen.png"
                                 alt="logo">
                        </a>
                    </div>

                    <div class="main-header_info">


                        <calories-calculation-locale-changer/>


                        <!--                        <div class="main-header_auth" v-if="!currentUser">-->
                        <router-link
                            v-if="!currentUser"
                            @click="closeBurger"
                            :to="{name: 'login'}" href="" class="main-header_login">{{
                                $t('message.signIn')
                            }}
                        </router-link>
                        <router-link
                            v-if="!currentUser"
                            @click="closeBurger"
                            :to="{name: 'register'}" href="" class="main-header_signin">
                            {{ $t('message.signUp') }}
                        </router-link>
                        <!--                        </div>-->

                        <div class="main-header_auth" v-else>
                            <a
                                v-if="!isEmailVerified" href="" @click="resendVerificationEmail"
                                class="main-header_resend-email">{{ $t('message.resendVerificationEmail') }}</a>
                            <a @click="logout" class="main-header_login main-header_logout">{{
                                    $t('message.logOut')
                                }}</a>
                        </div>

                        <div
                            @click="toggleBurger"
                            id="burger"
                            class="main-header_burger" :class="{ 'active': isBurgerOpen }">
                            <span>
                            </span>
                        </div>


                    </div>
                </div>
            </div>
        </div>


        <div ref="headerBottom" class="main-header_bottom ">
            <div class="main-header_container">
                <div class="main-header_box">
                    <nav class="main-header_nav main-nav">
                        <ul id="menu-main-menu-in-header" class="main-nav_list">
                            <li v-if="true" v-for="menuItem in menuItems" :key="menuItem"
                                class="menu-item menu-item-type-custom menu-item-object-custom">
                                <router-link class="calories-menu-item-link"
                                             :to="{name: menuItem}">{{ $t(`message.${menuItem}`) }}
                                </router-link>
                            </li>
                        </ul>
                    </nav>
                </div>
            </div>
        </div>
        <calories-burger-menu
            :isBurgerOpen="isBurgerOpen"
            :menuItems="menuItems"
            @update="isBurgerOpen = $event"
        />
    </header>

</template>

<style scoped lang="scss">

/*sticky header*/
.fixed-header {
    @media (min-width: $bp-medium + 0.1em) {
        position: fixed;
        top: 0;
        left: 50%;
        transform: translateX(-50%);
        width: 100%;
        z-index: 1001;
        background-color: white;
        box-shadow: -2px 3px 24px -13px black;
    }
}


.main-header {
    position: relative;
    z-index: 999;
    background-color: white;
    box-shadow: -2px 3px 24px -13px black;

    &_container {
        max-width: 1400px;
        padding: 0 10px;
        margin: 0 auto;
        height: 100%;
        width: 100%;


        @media (max-width: $bp-large) {
            max-width: 1200px;
        }
    }

    &_resend-email {
        background: transparent;
        align-self: center;
        max-height: 40px;
        color: black;
        font-weight: 600;
        font-size: .95em;
        border-radius: 2px;
        text-transform: uppercase;
        margin-right: 20px;

        @media (max-width: $bp-small) {
            display: none;
        }


        &:hover {
            text-decoration: underline;
        }

        &.active {
            color: $pink_color !important;
        }
    }

    &_logout {
        @media (max-width: $bp-small) {
            margin-right: 1.4vh;
        }
    }


    &_top {
        height: 60px;
    }

    &_box {
        display: flex;
        justify-content: space-between;
        align-items: center;
        height: 100%;
        @media (max-width: 768px) {
            display: block;
        }
    }

    &_logo {
        @media (max-width: $bp-small) {
            display: none;
        }

        display: block;
        height: 30px;

        a {
            width: 100%;
            height: 100%;
        }

        img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        transition: transform 0.3s ease;

        &:hover {
            transform: translate(0, -3px);
        }
    }

    &_info {
        display: flex;
        justify-content: space-between;
        align-items: center;
        @media (min-width: $bp-medium) {
            gap: 40px;
            margin-right: 30px;
        }
    }

    &_login {
        background: transparent;
        align-self: center;
        max-height: 40px;
        color: black;
        font-weight: 600;
        font-size: .95em;
        border-radius: 2px;
        text-transform: uppercase;


        &:hover {
            text-decoration: underline;
        }

        @media (max-width: $bp-small) {
            font-size: .85em;
        }
    }

    &_signin {
        align-self: center;
        max-height: 40px;
        color: black;
        font-weight: 600;
        font-size: .95em;
        border-radius: 2px;
        text-transform: uppercase;

        &:hover {
            text-decoration: underline;
        }

        @media (max-width: $bp-small) {
            font-size: .85em;
            padding: 10px 10px;
        }
    }
}

/*search form*/
.main-nav {
    width: 100%;
    height: 100%;


    &_list {
        display: flex;
        flex-wrap: wrap;
        justify-content: space-between;
        align-items: center;
        width: 100%;
        height: 100%;

        gap: 10px;
        @media (max-width: $bp-small) {
            gap: 15px;
        }

        > li {
            position: relative;
            font-family: 'Roboto', sans-serif;
            font-weight: 700;
            font-size: 14px;
            line-height: 22px;
            letter-spacing: 0.0625rem;
            text-transform: uppercase;
            padding: 8px;
            cursor: pointer;

            a {
                display: inline-block;
                color: inherit;
                width: 100%;
                height: 100%;
                padding: 10px 5px;
            }
        }
    }
}


/*burger-menu*/
.main-header_burger {
    display: none;
}

@media (max-width: $bp-medium) {
    .main-header_burger {
        display: block;
        position: relative;
        width: 30px;
        height: 20px;
        z-index: 3;
        cursor: pointer;
        transition: transform 0.3s ease;

        &:hover {
            transform: translate(0, -3px);
        }

        span {
            position: absolute;
            background-color: black;
            left: 0;
            width: 100%;
            height: 2px;
            top: 9px;
            transition: all 0.3s ease 0s;
        }

        &::before,
        &::after {
            content: "";
            background-color: black;
            position: absolute;
            width: 100%;
            height: 2px;
            left: 0;
            transition: all 0.3s ease 0s;
        }

        &::before {
            top: 0;
        }

        &::after {
            bottom: 0;
        }

        &.active span {
            transform: scale(0);
        }

        &.active::before {
            transform: rotate(45deg);
            background-color: #000000;
            top: 9px;
        }

        &.active::after {
            transform: rotate(-45deg);
            bottom: 9px;
            background-color: #000000;
        }
    }
}

//@media (max-width: $bp-small) {
@media (max-width: $bp-medium) {
    .main-nav {
        display: none;
    }
}

/*dropdown in burger*/
.dropdown-arrow {
    display: inline-block;
    position: absolute;
    top: 30%;
    transform: translateY(-30%);
    right: 10px;
    content: '';
    margin-left: 6px;
    margin-top: -8px;
    width: 10px;
    height: 10px;
    border-left: 2px solid #fff;
    border-bottom: 2px solid #fff;
    transform: rotate(-45deg);
    transition: .3s ease;
    cursor: pointer;
    padding: 7px;

    &_opened {
        transform: rotate(135deg);
        top: 20px;
    }
}

.dropdown-menu-in-burger {
    margin-top: 20px;
}

.dropdown-burger_list {
    li:not(:last-child) {
        margin-bottom: 15px;
    }
}

.calories-menu-item-link:hover {
    text-decoration: underline;
}

.calories-menu-item-link-active {
    color: $pink_color !important;
}

</style>

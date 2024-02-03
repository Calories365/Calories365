<script>
export default {
    name: "CaloriesBurgerMenu",
    props: {
        isBurgerOpen: {
            type: Boolean,
            required: true,
        },
        menuItems: {
            type: Array,
            required: true,
        },
    },
    methods: {
        toggleBurger() {
            this.$emit('update', false);
        }
    }
}
</script>

<template>
    <div class="burger-menu_wrapper" :class="{ 'opened': isBurgerOpen }">
        <div class="burger-menu_container">
            <ul id="menu-burger-menu" class="menu">
                <li v-if="true" v-for="menuItem in menuItems" :key="menuItem"
                    class="menu-item menu-item-type-custom menu-item-object-custom current-menu-item current_page_item menu-item-has-children menu-item-1201">
                    <router-link
                        @click="toggleBurger"
                        :to="{name: menuItem}" aria-current="page">{{
                            $t(`message.${menuItem}`)
                        }}
                    </router-link>
                </li>
            </ul>
        </div>
    </div>

</template>

<style scoped lang="scss">

/*New burger menu with sidebar*/
.lock-scroll {
    overflow: hidden; /* Запрещает прокрутку на всем документе */
    height: 100%; /* Фиксирует высоту документа */
    width: 100%; /* Фиксирует ширину документа */
    position: fixed; /* Обеспечивает фиксированное положение */
    top: 0; /* Положение от верхнего края */
    left: 0; /* Положение от левого края */
}


.burger-menu {
    &_wrapper {
        margin-top: 60px;
        //padding: 20px;
        display: none;
        position: fixed;
        border-top: 2px solid white;
        background-color: white;
        color: #000000;
        height: calc(100dvh - 60px);
        width: 100%;
        z-index: 9999998;
        top: 0;
        left: 0;

        &.opened {
            display: block;
        }
    }

    &_container {

        height: 100%;
        overflow-y: scroll;
    }

    .menu {

    }
}

#menu-burger-menu {
    .menu-item {
        position: relative;
        //padding: 20px 15px;
        border-bottom: 1px solid white;

        &:last-child {
            border: none;
        }

        a {
            text-align: center;
            display: block;
            font-size: 18px;
            line-height: 22px;
            letter-spacing: 0.0625rem;
            text-transform: uppercase;
            font-weight: 700;
            padding: 20px 0;

        }
    }
}

.menu-item:hover {
    text-decoration: underline;
}

.menu-item:active {
    color: $pink_color !important;
}

.calories-menu-item-link-active {
    color: $pink_color !important;

}
</style>

<script>
import CaloriesFoodList from "@/Components/DairyComponents/CaloriesFoodList.vue";
import CaloriesIndication from "@/Components/DairyComponents/CaloriesIndication.vue";
import CaloriesNavbar from "@/Components/DairyComponents/CaloriesNavbar.vue";
import CaloriesAddBtn from "@/UI/CaloriesAddBtn.vue";
import CaloriesSlideMenu from "@/Components/DairyComponents/CaloriesSlideMenu.vue";
import CaloriesSlideProductMenu from "@/Components/DairyComponents/CaloriesSlideProductMenu.vue";
import { actionTypes } from "@/store/modules/dairy.js";
import { mapGetters } from "vuex";
import { getterTypes as localeChangeGetterTypes } from "@/store/modules/changeLocale.js";
import CaloriesSuccessNotification from "@/Components/CaloriesSuccessNotification.vue";
import CaloriesErrorNotification from "@/Components/CaloriesErrorNotification.vue";

export default {
    name: "Stats",
    components: {
        CaloriesErrorNotification,
        CaloriesSuccessNotification,
        CaloriesSlideProductMenu,
        CaloriesSlideMenu,
        CaloriesAddBtn,
        CaloriesNavbar,
        CaloriesIndication,
        CaloriesFoodList
    },
    data() {
        return {
            isOpen: false,
            counter: 0
        };
    },
    methods: {
        toggleMenu() {
            this.isOpen = !this.isOpen;
            if (!this.counter) {
                this.$store.dispatch(actionTypes.getPopularProducts);
            }
        }
    },
    computed: {
        ...mapGetters({
            currentLocale: localeChangeGetterTypes.selectedLocale
        }),
        successMessage() {
            return this.$store.getters.isSuccess;
        },
        errorMessage() {
            return this.$store.getters.isError;
        }
    },
    watch: {
        currentLocale(newLocale, oldLocale) {
            if (newLocale !== oldLocale) {
                this.$store.dispatch(actionTypes.getCurrentProducts);
                if (this.isOpen) {
                    this.$store.dispatch(actionTypes.getPopularProducts);
                }
            }
        }
    },

    // Когда компонент МОНТИРУЕТСЯ (открываем страницу)
    mounted() {
        // Запрещаем скролл на body ТОЛЬКО пока находимся на этой странице
        document.body.style.overflow = 'hidden';
    },

    // Когда компонент УНИЧТОЖАЕТСЯ (уходим со страницы, переключаемся на другую)
    beforeUnmount() {
        // Восстанавливаем скролл
        document.body.style.overflow = 'auto';
    }
};
</script>

<template>
    <section class="dairy-section">
        <div class="dairy-section__parent">
            <calories-navbar />

            <div class="dairy-section__food-header">
                <calories-indication />
            </div>

            <div class="dairy-section__food-container">
                <hr />
                <div class="dairy-section__food-list-container">
                    <calories-food-list />
                </div>

                <calories-slide-menu
                    :isOpen="isOpen"
                    @update="isOpen = $event"
                />

                <div class="dairy-section__addBtn">
                    <calories-add-btn @click="toggleMenu" />
                </div>
            </div>
        </div>
    </section>
</template>

<style scoped lang="scss">
.dairy-section {
    &__parent {
        width: 90%;
        margin: 0 auto;
        border-bottom: 3px solid #dbd9d9;
    }

    &__food-header {
        background-color: #f0efef;
        margin: 20px auto 0;
        width: 100%;
    }

    &__food-container {
        position: relative;
        background-color: #f0efef;
        height: calc(100vh - 300px);
        width: 100%;
        overflow: hidden;
    }

    &__food-list-container {
        height: 100%;
        width: 100%;
    }

    &__addBtn {
        position: absolute;
        bottom: 20px;
        right: 40px;
        z-index: 98;
    }
}

.notification-container {
    position: fixed;
    top: 10%;
    left: 50%;
    transform: translate(-50%, -50%);
    width: auto;
    z-index: 1000;
}

.notification {
    padding: 20px;
    background-color: #f8f8f8;
    border-left: 5px solid transparent;
    position: relative;
    margin-bottom: 15px;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
    width: 100%;

    &.success {
        background-color: #e9f6ec;
        border-color: #34c759;
        color: #1a3e29;

        .close-button {
            color: #1a3e29;
        }
    }

    .close-button {
        position: absolute;
        right: 10px;
        top: 50%;
        transform: translateY(-50%);
        border: none;
        background: none;
        font-size: 20px;
        cursor: pointer;
    }
}
</style>

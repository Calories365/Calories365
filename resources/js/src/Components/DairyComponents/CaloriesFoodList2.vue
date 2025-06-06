<script>
import CaloriesNavbar from "@/Components/DairyComponents/CaloriesNavbar.vue";
import CaloriesIndication from "@/Components/DairyComponents/CaloriesIndication.vue";
import CaloriesAddBtn from "@/UI/CaloriesAddBtn.vue";

export default {
    name: "CaloriesFoodList",
    components: { CaloriesAddBtn, CaloriesIndication, CaloriesNavbar },
};
</script>

<template>
    <div class="food-container">
        <div class="food-container__element">
            <img class="food-container__image" src="@/images/burger.png" />
            <span class="food-container__name">Яблоко</span>
            <span class="food-container__weight">100 грам</span>
            <span class="food-container__calories">100 ККАЛ</span>
        </div>

        <div class="food-container__element">
            <img class="food-container__image" src="@/images/burger.png" />
            <span class="food-container__name">Яблоко</span>
            <span class="food-container__weight">100 грам</span>
            <span class="food-container__calories">100 ККАЛ</span>
        </div>
    </div>
</template>

<style scoped lang="scss">
.food-container {

    &__element{
        margin-left: 5px;
        background-color: white;
        display: flex;
        justify-content: space-around;
    }

    &__image{
        max-width: 50px;
        max-height: 30px;
    }
}
</style>

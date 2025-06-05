<script>
import CaloriesAddBtn from "@/UI/CaloriesAddBtn.vue";
import { mapState } from "vuex";

export default {
    name: "CaloriesIndication",
    components: { CaloriesAddBtn },
    computed: {
        ...mapState({
            currentUser: (state) => state.auth.currentUser,
            caloriesPerDay: (state) => state.dairy.caloriesPerDay,
            caloriesPerDayPart: (state) => state.dairy.caloriesPerDayPart,
            part_of_day: (state) => state.dairy.part_of_day,

            localedPart_of_day() {
                if (this.part_of_day === "morning") {
                    return this.$t("Diary.morning");
                }
                if (this.part_of_day === "dinner") {
                    return this.$t("Diary.dinner");
                }
                if (this.part_of_day === "supper") {
                    return this.$t("Diary.supper");
                }
            },
        }),
    },
};
</script>

<template>
    <div class="indication-container">
        <ul class="indication-container__list">
            <li class="indication-container__element">
                {{ localedPart_of_day }}: {{ caloriesPerDayPart }}
                {{ $t("Diary.KCAL") }}
            </li>
            <li class="indication-container__element">
                {{ $t("Diary.summary") }}: {{ caloriesPerDay }}/{{
                    currentUser.calories_limit
                }}
                {{ $t("Diary.KCAL") }}
            </li>
        </ul>
    </div>
</template>

<style scoped lang="scss">
.indication-container {
    font-family: "Roboto", sans-serif;
    font-weight: 600;
    font-size: 17px;
    line-height: 22px;
    letter-spacing: 0.0625rem;
    text-transform: uppercase;
    @media (max-width: 768px) {
        font-size: 12px;
    }

    &__list {
        display: flex;
        justify-content: space-between;
    }

    &__element {
        padding: 20px 30px 20px;
        @media (max-width: 768px) {
            padding: 10px 15px 10px;
        }
    }

    &__btn {
        margin-top: 10px;
    }
}
</style>

<script>
import CaloriesArrowLeft from "@/UI/CaloriesArrowLeft.vue";
import CaloriesArrowRight from "@/UI/CaloriesArrowRight.vue";
import {actionTypes, getterTypes as diaryGetterTypes, mutationTypes} from "@/store/modules/dairy.js";
import {mapGetters} from "vuex";


export default {
    name: "CaloriesNavbar",
    components: {CaloriesArrowRight, CaloriesArrowLeft},
    methods: {
        dayPartToggle(dayPart) {
            this.$store.dispatch(actionTypes.setPartOfDay, dayPart);
        },
        dateToggle(direction) {
            this.$store.dispatch(actionTypes.dateToggle, direction);
        }

    },
    computed: {
        ...mapGetters({
            part_of_day: diaryGetterTypes.part_of_day,
            currentDate: diaryGetterTypes.currentDate,
        }),
    },
    mounted() {
        this.$store.dispatch(actionTypes.initialization);
    },
    beforeUnmount() {
        this.$store.commit(mutationTypes.deleteDateFromCalendar);
    }
}
</script>

<template>
    <div class="navbar-container">
        <div class="date">
            <calories-arrow-left @click="dateToggle('previous')"/>

            <span class="date__span">
                {{ currentDate }}
            </span>
            <calories-arrow-right @click="dateToggle('next')"/>

        </div>
        <div class="navbar">
            <ul class="navbar__list">
                <!--                <calories-arrow-left @click="dateToggle('previous')"/>-->
                <li class="navbar__element" :class="{ active: part_of_day === 'morning'}"
                    @click="dayPartToggle('morning')">{{ $t('Diary.morning') }}
                </li>
                <li class="navbar__element" :class="{ active: part_of_day === 'dinner'}"
                    @click="dayPartToggle('dinner')">{{ $t('Diary.dinner') }}
                </li>
                <li class="navbar__element" :class="{ active: part_of_day === 'supper'}"
                    @click="dayPartToggle('supper')">{{ $t('Diary.supper') }}
                </li>
                <!--                <calories-arrow-right @click="dateToggle('next')"/>-->
            </ul>
        </div>
    </div>
</template>

<style scoped lang="scss">

.navbar-container {
    font-family: "Roboto", sans-serif;
    font-weight: 700;
    font-size: 14px;
    line-height: 22px;
    letter-spacing: 0.0625rem;
    text-transform: uppercase;

}

.date {
    display: flex;
    justify-content: center;
    gap: 20px;
    margin-top: 15px;

    &__span {
        font-size: 15px;
    }
}

.navbar {
    width: 75%;
    margin: 0 auto;
    padding: 0;
    cursor: pointer;


    &__list {
        display: flex;
        justify-content: space-between;
        gap: 30px;
        margin: 15px auto 0;
        @media (max-width: 768px) {
            gap: 10px;
        }
    }

    &__element {
        font-size: $default-font-size;

        @media (max-width: 768px) {
            font-size: 18px;
        }
        @media (max-width: 375px) {
            font-size: 16px;
        }

        &.active {
            color: $pink_color !important;
        }

        &:hover {
            text-decoration: underline;
        }
    }
}
</style>

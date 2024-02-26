<script setup>
import {computed, onMounted} from 'vue';
import {useStore} from 'vuex';
import {actionTypes, getterTypes} from "@/store/modules/stats.js";
import CaloriesCalendar from "@/Components/StatsComponents/CaloriesCalendar.vue";
import CaloriesArrowLeft from "@/UI/CaloriesArrowLeft.vue";
import CaloriesArrowRight from "@/UI/CaloriesArrowRight.vue";
import formatDate from "@/helpers/formatDate.js";

const store = useStore();
const currentDate = computed(() => formatDate(store.getters[`${getterTypes.currentDate}`]));
const calendarInfo = computed(() => store.getters[`${getterTypes.calendarInfo}`]);
const currentData = computed(() => store.getters[`${getterTypes.currentData}`]);
onMounted(() => {
    store.dispatch(actionTypes.dataInitialization);
    store.dispatch(actionTypes.getDailyCalories);
});

function dateToggle(direction) {
    store.dispatch(actionTypes.dateToggle, direction);
}
</script>

<template>
    <section class="stats">
        <p class="stats_head">{{ $t('Stats.Stats') }}</p>

        <div class="stats_date">
            <div class="stats_arrow-left">
                <calories-arrow-left @click="dateToggle('previous')"/>
            </div>

            <span class="stats_date-span">
               {{ currentDate }}
            </span>
            <div class="stats_arrow-right">
                <calories-arrow-right @click="dateToggle('next')"/>
            </div>

        </div>
        <div class="stats_calendar">
            <CaloriesCalendar :currentDate="currentDate"/>
        </div>
    </section>
</template>


<style scoped lang="scss">
.stats {
    margin: 0 auto;

    &_head {
        font-weight: bold;
        padding-top: 30px;
        padding-bottom: 30px;
        font-size: $default-head-font-size;
        text-align: center;
        margin: auto;

        @media (max-width: 768px) {
            padding-top: 10px;
            font-size: 30px;
        }

        @media (max-width: 375px) {
            padding-top: 5px;
            font-size: 25px;
        }
    }

    &_date {
        display: flex;
        justify-content: center;
        gap: 20px;
        padding-bottom: 30px;
    }

    &_date-span {
        font-size: 30px;
    }

    &_arrow-right, &_arrow-left {
        margin: auto 0;
    }

    &_calendar {
        width: 90%;
        margin: 0 auto;
    }
}
</style>

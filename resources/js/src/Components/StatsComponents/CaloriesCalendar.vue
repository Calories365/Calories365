<script setup>
import {computed, defineProps} from 'vue';
import CaloriesCalendarIndication from "@/Components/StatsComponents/CaloriesCalendarIndication.vue";
import {getterTypes} from "@/store/modules/stats.js";
import {getterTypes as authGetterTypes} from "@/store/modules/auth.js";
import store from "@/store/store.js";

const props = defineProps({
    currentData: {
        type: Object,
        required: true,
    },
    calendarInfo: {
        type: Object,
        required: true,
    }
});
const calendar = computed(() => store.getters[`${getterTypes.calendar}`]);
const currentUser = computed(() => store.getters[`${authGetterTypes.currentUser}`]);

</script>

<template>
    <div class="calendar">
        <div class="calendar-head">
            <div class="dayname full">{{ $t('Stats.DaysOfWeek.Monday') }}</div>
            <div class="dayname short">{{ $t('Stats.DaysOfWeekShort.Monday') }}</div>

            <div class="dayname full">{{ $t('Stats.DaysOfWeek.Tuesday') }}</div>
            <div class="dayname short">{{ $t('Stats.DaysOfWeekShort.Tuesday') }}</div>

            <div class="dayname full">{{ $t('Stats.DaysOfWeek.Wednesday') }}</div>
            <div class="dayname short">{{ $t('Stats.DaysOfWeekShort.Wednesday') }}</div>

            <div class="dayname full">{{ $t('Stats.DaysOfWeek.Thursday') }}</div>
            <div class="dayname short">{{ $t('Stats.DaysOfWeekShort.Thursday') }}</div>

            <div class="dayname full">{{ $t('Stats.DaysOfWeek.Friday') }}</div>
            <div class="dayname short">{{ $t('Stats.DaysOfWeekShort.Friday') }}</div>

            <div class="dayname full">{{ $t('Stats.DaysOfWeek.Saturday') }}</div>
            <div class="dayname short">{{ $t('Stats.DaysOfWeekShort.Saturday') }}</div>

            <div class="dayname full">{{ $t('Stats.DaysOfWeek.Sunday') }}</div>
            <div class="dayname short">{{ $t('Stats.DaysOfWeekShort.Sunday') }}</div>
        </div>




        <div class="row" v-for="week in calendar" :key="week[0]?.day">
            <div v-for="day in week" :key="day?.day" class="day" :class="day?.class">
                <div v-if="day?.day" class="day-number">{{ day.day }}</div>
                <div v-if="day?.day" class="day-calories">
                    {{ day.calories !== null ? day.calories : '0' }}/{{ currentUser.calories_limit }}
                </div>
                <div v-else class="day-calories"> </div>
            </div>
        </div>



    </div>
    <CaloriesCalendarIndication/>
</template>

<style scoped lang="scss">
body {
    background-color: #000;
}

.calendar {
    width: 100%;
    background-color: #fff;
}

.calendar-head {
    display: flex;
    min-height: 30px;
    background-color: $main-color;
    color: #fff;
}

.dayname {
    padding-top: 10px;
    padding-bottom: 10px;
    flex-grow: 1;
    flex-shrink: 1;
    flex-basis: 0;
    text-transform: uppercase;
    color: #ffffff;
    overflow: hidden;
    background-color: $main-color;
    font-weight: bold;
    text-align: center;
    line-height: 1;
    //@media (max-width: 768px) {
    //    display: none;
    //}
}

.dayname.full {
    display: none;
}

.dayname.short {
    display: block;
}

@media (min-width: 600px) {
    .dayname.full {
        display: block;
    }
    .dayname.short {
        display: none;
    }
}

.day-calories {
    text-align: center;
    font-family: Arial, Helvetica, sans-serif;
    font-size: 17px;
    padding-top: 5px;
    color: #666666;
    @media (max-width: 768px) {
        display: none;
    }
}

.row {
    display: flex;
    height: 10vh;

    @media (max-width: 768px) {
        height: 7vh;
    }
}

.day {
    cursor: pointer;
    flex-grow: 1;
    flex-shrink: 1;
    flex-basis: 0;
    color: #000;
    padding: 5px;
    box-shadow: -1px -1px #D5D7DB, inset -1px -1px 0 0 #D5D7DB;
}

.day:hover {
    filter: brightness(85%);
    box-shadow: -2px -2px #BEC2C9, inset -2px -2px 0 0 #BEC2C9;
}

.day:active {
    transform: scale(0.95);
}

.overreach {
    background-color: rgba(225, 151, 63, 0.5);
}

.significant-overreach {
    background-color: rgba(174, 55, 55, 0.5);
}

.nice {
    background-color: rgba(71, 174, 55, 0.5);
}

.day.next {
    color: #d5d7db;
}
</style>

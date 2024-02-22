<script>
import CaloriesSucces from "@/Components/CaloriesSucces.vue";
import CaloriesCard from "@/Components/CaloriesCard.vue";

export default {
    name: "Home",
    components: {CaloriesCard, CaloriesSucces},
    data() {
        return {
            showVerifiedAlert: '',
        };
    },
    created() {
        if (this.$route.query.verified === '1') {
            this.showVerifiedAlert = 'Email verified successfully!';
            setTimeout(() => {
                this.showVerifiedAlert = '';
            }, 5000);
        }
    }
}
</script>

<template>

    <calories-succes v-if="showVerifiedAlert" :message="showVerifiedAlert"/>


    <div class="recipe-layout_content content-layout">
        <div class="content-layout_wrapper">

            <calories-card link="calculation" :sectionName="$t('message.calorieCalculation')"/>
            <calories-card link="diary" :sectionName="$t('message.calorieDiary')"/>
            <calories-card link="cabinet" :sectionName="$t('message.personalCabinet')"/>
            <calories-card link="stats" :sectionName="$t('message.statistics')"/>
            <!--            <calories-card link="instructions" :sectionName="$t('message.instructions')"/>-->
            <!--            <calories-card link="goals" :sectionName="$t('message.myRecipes')"/>-->


        </div>
    </div>

</template>


<style lang="scss">
.recipe-layout {
    width: 100%;
    height: 100%;
    background-color: white;
    color: black;
    padding: 20px;

    &_container {
        max-width: 1400px;
        padding: 0 10px;
        margin: 0 auto;
        display: flex;
        flex-direction: column;
        @media (max-width: $bp-large) {
            max-width: 1200px;
        }
    }

    &_header {

    }

    &_content {

    }
}

.header-layout {
    height: 50px;
    margin-top: 10px;
    margin-bottom: 30px;

    &_wrapper {
        height: 100%;
        //width: 100%;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    &_link {
        display: flex;
        align-items: center;
        //max-width: 100%;

        &.shake {
            animation: shake 0.5s ease-in-out infinite;
        }
    }


    &_linktext {
        font-size: 26px;
        text-transform: uppercase;
        font-weight: 700;
        color: black;
        margin-right: 10px;
        transition: transform 1s ease;
        transform: translateX(-100px);

        &._active {
            //color: red;
            transform: translateX(0px);

        }

        &:hover {
            border-bottom: 2px solid black;
        }

    }

    &_svg-wrapper {

        svg {
            transition: transform 0.3s ease;

            &:hover {
                transform: translateX(5px);
            }
        }

    }

}

.content-layout {
    margin-bottom: 50px;
    padding: 50px;

    &_wrapper {
        width: 90%;
        margin: 0 auto;
        display: grid;
        justify-content: space-between;
        grid-template-columns: minmax(300px, 1fr)  minmax(300px, 1fr)  minmax(300px, 1fr);
        grid-template-rows: 2fr;
        gap: 50px;

        @media (max-width: $bp-medium) and (min-width: $bp-small) {
            grid-template-columns: minmax(300px, 450px) minmax(300px, 450px);
            grid-template-rows: 1fr 1fr 1fr;

        }

        @media (max-width: $bp-small) {
            grid-template-columns: minmax(90%, 1fr);
            //grid-template-rows: repeat(6, 1fr); //для 6 карточек
            grid-template-rows: repeat(3, 1fr);
        }
    }

    &_card {

    }
}


@keyframes shake {
    0%, 100% {
        transform: translateX(0);
    }
    25%, 75% {
        transform: translateX(-5px);
    }
    50% {
        transform: translateX(5px);
    }
}

.recipe-day {
    width: 100%;
    height: 100%;
    background-color: #EAF6EB;
    color: black;
    padding: 20px;
}

.recipe-day_container {
    max-width: 1400px;
    padding: 0 10px;
    margin: 0 auto;
    display: flex;
    flex-direction: column;
}
</style>

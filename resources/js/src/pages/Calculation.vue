<script>
import CaloriesCalculationsSectionCalculator from "@/Components/CaloriesCalculationsSectionCalculator.vue";
import CaloriesCalculationsSectionResults from "@/Components/CaloriesCalculationsSectionResults.vue";


export default {
    name: "Calculation",
    components: {CaloriesCalculationsSectionResults, CaloriesCalculationsSectionCalculator},
}
</script>

<template>

    <CaloriesCalculationsSectionCalculator/>

    <CaloriesCalculationsSectionResults/>

</template>

<style scoped lang="scss">

</style>

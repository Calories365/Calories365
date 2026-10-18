<script>
import CaloriesCalculationsSectionCalculator from "@/Components/CaloriesCalculationsSectionCalculator.vue";
import CaloriesCalculationsSectionResults from "@/Components/CaloriesCalculationsSectionResults.vue";
import CaloriesEnergyCard from "@/Components/CaloriesEnergyCard.vue";

export default {
    name: "Calculation",
    components: {
        CaloriesCalculationsSectionResults,
        CaloriesCalculationsSectionCalculator,
        CaloriesEnergyCard,
    },
};
</script>

<template>
    <CaloriesEnergyCard />
    <CaloriesCalculationsSectionCalculator />

    <CaloriesCalculationsSectionResults />
</template>

<style scoped lang="scss"></style>

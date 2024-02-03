<script>

export default {
    name: "CaloriesSucces",
    props: {
        message: {
            type: String,
            required: true,
        }
    },

}
</script>

<template>
    <div>
        <div class="fixed top-0 left-1/2 transform -translate-x-1/2 bg-green-500 text-white px-4 py-2 rounded-md">
            {{ message }}
        </div>
    </div>
</template>

<style scoped lang="scss">

</style>

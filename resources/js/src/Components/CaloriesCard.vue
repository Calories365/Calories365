<script>
export default {
    name: "CaloriesCard",
    props: {
        link: {
            type: String,
            required: true,
        },
        sectionName: {
            type: String,
            required: true,
        },
    },
    computed: {
        imageSrc() {
            return new URL(`../assets/${this.link}.png`, import.meta.url).href;
        },
    },
};
</script>
<template>
    <div class="content-layout_card card-layout">
        <router-link :to="{ name: link }" class="card-layout_link">
            <div class="card-layout_header">
                <img :src="imageSrc" alt="Image in card" class="card-day_img" />
            </div>

            <div class="card-layout_text">
                {{ sectionName }}
            </div>
        </router-link>
    </div>
</template>
<style scoped lang="scss">
.card-day_img {
}

.card-layout {
    //width: 100%;
    //height: 380px;
    transition: transform 0.3s ease;
    display: flex;
    flex-direction: column;

    &:hover {
        transform: translateY(-5px);
    }
    &_link {
        position: relative;
    }
    &_text {
        position: absolute;
        text-align: center;
        font-size: 35px;
        bottom: 0;
        width: 100%;
        height: 40px;
        background-color: rgba(0, 0, 0, 0.5);
        color: white;
        @media (max-width: 768px) {
            font-size: 25px;
        }
    }

    &_img {
        //width: 100%;
        height: 100%;
        object-fit: cover;
    }
}
</style>

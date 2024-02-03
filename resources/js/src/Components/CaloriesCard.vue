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
    }
}
</script>
<template>

    <div class="content-layout_card card-layout">
        <router-link :to="{name: link}"
                     class="card-layout_link">
            <div class="card-layout_header">
                <img :src="imageSrc" alt="Image in card" class="card-day_img">
            </div>

            <div class="card-layout_info">

            </div>
            <div class="card-layout_text">
                {{ sectionName }}
            </div>
        </router-link>
    </div>

</template>
<style scoped lang="scss">
.card-day_img {
    width: 350px;
    height: 350px;
}

.card-layout {
    //width: 300px;
    width: 100%;
    height: 380px;
    transition: transform 0.3s ease;
    display: flex;
    flex-direction: column;

    &:hover {
        transform: translateY(-5px);
    }

    &_text {
        text-align: center;
        font-size: 35px;
        //width: 350px;
        width: 100%;
        height: 40px;
        background-color: rgba(0, 0, 0, 0.5);
        color: white;
        @media (max-width: 768px) {
            font-size: 25px;
        }
    }


    &_link {
        display: flex;
        flex-direction: column;
    }

    &_header {
        width: 100%;
        height: 290px;
    }

    &_img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }

    &_info {
        padding: 10px 0px;
        display: flex;
        justify-content: space-between;
        gap: 5px;

    }

    &_title {
        font-weight: 700;
        //margin-bottom: 5px; color: black;
        font-size: 24px;
        line-height: 30px;
        letter-spacing: 0.0625rem;
    }

    &_excerpt {
        color: black;
        text-align: justify;
        flex: 1;
        line-height: 22px;
        font-size: 18px;
        //line-height: 26px; //font-size: 20px; letter-spacing: 0.0625rem;

    }

    &_subtitle {
        display: flex;
        align-items: center;
        justify-content: space-between;
        margin-bottom: 5px;
        color: $darkgray;
        font-size: 15px;
        font-weight: 500;
    }

    &_calories {
        display: flex;
        align-items: center;
        gap: 5px;


        svg {

        }

        span {

        }
    }

    &_time {
        display: flex;
        align-items: center;
        gap: 5px;

        svg {

        }

        span {

        }
    }

    &_date {

        color: $lightgray;
    }

    &_taxonomies {
        display: flex;
        align-items: center;
        justify-content: space-between;
        flex-wrap: wrap;
        gap: 5px;
        margin-bottom: 5px;
        padding: 0px 17px 10px;
    }

    &_taxonomy {
        //padding: 0 2px; width: 80px;
        height: 30px;
        background-color: $main-color;
        border-radius: 5px;
        //color: black;
        display: flex;
        justify-content: center;
        align-items: center;
        color: white;
        font-size: 15px;

        &:hover {

        }
    }

}
</style>

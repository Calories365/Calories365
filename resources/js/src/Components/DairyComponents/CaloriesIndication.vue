<script>
import CaloriesAddBtn from "@/UI/CaloriesAddBtn.vue";
import CaloriesModal from "@/Components/CaloriesModal.vue";
import { mapState, mapActions } from "vuex";

export default {
    name: "CaloriesIndication",
    components: { CaloriesAddBtn, CaloriesModal },
    data() {
        return {
            isFeedbackModalOpen: false,
            feedbackResponse: "",
        };
    },
    computed: {
        ...mapState({
            currentUser: (state) => state.auth.currentUser,
            caloriesPerDay: (state) => state.dairy.caloriesPerDay,
            caloriesPerDayPart: (state) => state.dairy.caloriesPerDayPart,
            part_of_day: (state) => state.dairy.part_of_day,
            currentDate: (state) => state.dairy.currentDate,
            isLoading: (state) => state.dairy.isLoading,

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
                return "";
            },
        }),
    },
    methods: {
        ...mapActions({
            getFeedback: "[dairy] getFeedback",
        }),
        showCaloriesInfo(usePartOfDay = false) {
            if (this.isLoading) return;

            const payload = { date: this.currentDate };
            if (usePartOfDay && this.part_of_day) {
                payload.part_of_day = this.part_of_day;
            }

            this.getFeedback(payload).then((data) => {
                if (!data) return;
                this.feedbackResponse =
                    typeof data === "string"
                        ? data
                        : JSON.stringify(data, null, 2);
                this.isFeedbackModalOpen = true;
            });
        },
        closeFeedbackModal() {
            this.isFeedbackModalOpen = false;
        },
    },
};
</script>

<template>
    <div class="indication-container">
        <ul class="indication-container__list">
            <li class="indication-container__element">
                {{ localedPart_of_day }}: {{ caloriesPerDayPart }}
                {{ $t("Diary.KCAL") }}
                <span
                    class="info-icon"
                    :class="{ loading: isLoading }"
                    :aria-disabled="isLoading"
                    :aria-busy="isLoading"
                    @click="showCaloriesInfo(true)"
                >
                    <svg
                        xmlns="http://www.w3.org/2000/svg"
                        x="0"
                        y="0"
                        width="100"
                        height="100"
                        viewBox="0 0 50 50"
                    >
                        <path
                            d="M 25 2 C 12.309295 2 2 12.309295 2 25 C 2 37.690705 12.309295 48 25 48 C 37.690705 48 48 37.690705 48 25 C 48 12.309295 37.690705 2 25 2 z M 25 4 C 36.609824 4 46 13.390176 46 25 C 46 36.609824 36.609824 46 25 46 C 13.390176 46 4 36.609824 4 25 C 4 13.390176 13.390176 4 25 4 z M 25 11 A 3 3 0 0 0 22 14 A 3 3 0 0 0 25 17 A 3 3 0 0 0 28 14 A 3 3 0 0 0 25 11 z M 21 21 L 21 23 L 22 23 L 23 23 L 23 36 L 22 36 L 21 36 L 21 38 L 22 38 L 23 38 L 27 38 L 28 38 L 29 38 L 29 36 L 28 36 L 27 36 L 27 21 L 26 21 L 22 21 L 21 21 z"
                        ></path>
                    </svg>
                </span>
            </li>
            <li class="indication-container__element">
                {{ $t("Diary.summary") }}: {{ caloriesPerDay }}/{{
                    currentUser.calories_limit
                }}
                {{ $t("Diary.KCAL") }}
                <span
                    class="info-icon"
                    :class="{ loading: isLoading }"
                    :aria-disabled="isLoading"
                    :aria-busy="isLoading"
                    @click="showCaloriesInfo()"
                >
                    <svg
                        xmlns="http://www.w3.org/2000/svg"
                        x="0"
                        y="0"
                        width="100"
                        height="100"
                        viewBox="0 0 50 50"
                    >
                        <path
                            d="M 25 2 C 12.309295 2 2 12.309295 2 25 C 2 37.690705 12.309295 48 25 48 C 37.690705 48 48 37.690705 48 25 C 48 12.309295 37.690705 2 25 2 z M 25 4 C 36.609824 4 46 13.390176 46 25 C 46 36.609824 36.609824 46 25 46 C 13.390176 46 4 36.609824 4 25 C 4 13.390176 13.390176 4 25 4 z M 25 11 A 3 3 0 0 0 22 14 A 3 3 0 0 0 25 17 A 3 3 0 0 0 28 14 A 3 3 0 0 0 25 11 z M 21 21 L 21 23 L 22 23 L 23 23 L 23 36 L 22 36 L 21 36 L 21 38 L 22 38 L 23 38 L 27 38 L 28 38 L 29 38 L 29 36 L 28 36 L 27 36 L 27 21 L 26 21 L 22 21 L 21 21 z"
                        ></path>
                    </svg>
                </span>
            </li>
        </ul>

        <CaloriesModal
            :isOpen="isFeedbackModalOpen"
            :content="feedbackResponse"
            @close="closeFeedbackModal"
        />
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
        letter-spacing: 0.0450rem;
    }

    &__list {
        display: flex;
        justify-content: space-between;
    }

    &__element {
        display: flex;
        padding: 20px 30px 20px;
        @media (max-width: 768px) {
            padding: 10px 7px 10px;
        }
    }

    &__btn {
        margin-top: 10px;
    }
}

.info-icon {
    font-family: "Roboto", sans-serif;
    display: inline-block;
    width: 20px;
    height: 20px;
    line-height: 20px;
    text-align: center;
    background-color: $main-color;
    color: white;
    border-radius: 50%;
    font-size: 12px;
    font-weight: bold;
    cursor: pointer;
    margin-left: 10px;
    transition: background-color 0.2s ease;
    vertical-align: top;
    position: relative;

    svg {
        position: absolute;
        top: 50%;
        left: 50%;
        transform: translate(-50%, -50%);
        width: 12px;
        height: 12px;
        fill: white;
    }

    &.loading {
        pointer-events: none;
        cursor: not-allowed;
        opacity: 0.6;
    }

    &.loading svg {
        animation: spin 1s linear infinite;
    }

    &:not(.loading):hover {
        background-color: #45a049;
    }

    @media (max-width: 768px) {
        width: 18px;
        height: 18px;
        line-height: 18px;
        margin-left: 6px;

        svg {
            width: 10px;
            height: 10px;
        }
    }
}

@keyframes spin {
    from { transform: translate(-50%, -50%) rotate(0deg); }
    to { transform: translate(-50%, -50%) rotate(360deg); }
}
</style>

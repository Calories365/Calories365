<script>
import { FontAwesomeIcon } from "@fortawesome/vue-fontawesome";
import {
    faBookOpen,
    faCalculator,
    faChartColumn,
    faUser,
    faUtensils,
} from "@fortawesome/free-solid-svg-icons";

export default {
    name: "MiniAppTabBar",
    components: { FontAwesomeIcon },
    data() {
        return {
            tabs: [
                { name: "diary", icon: faBookOpen },
                { name: "stats", icon: faChartColumn },
                { name: "voice", icon: faUtensils },
                { name: "calculation", icon: faCalculator },
                { name: "cabinet", icon: faUser },
            ],
        };
    },
};
</script>

<template>
    <nav class="mini-app-tab-bar">
        <router-link
            v-for="tab in tabs"
            :key="tab.name"
            :to="{ name: tab.name }"
            class="mini-app-tab-bar_item"
            active-class="mini-app-tab-bar_item--active"
        >
            <font-awesome-icon :icon="tab.icon" class="mini-app-tab-bar_icon" />
            <span class="mini-app-tab-bar_label">{{
                $t(`message.${tab.name}`)
            }}</span>
        </router-link>
    </nav>
</template>

<style scoped lang="scss">
.mini-app-tab-bar {
    position: fixed;
    left: 0;
    right: 0;
    bottom: 0;
    z-index: 100;
    display: flex;
    justify-content: space-around;
    align-items: stretch;
    padding-bottom: env(safe-area-inset-bottom, 0);
    background: var(--tg-theme-secondary-bg-color, #ffffff);
    border-top: 1px solid var(--tg-theme-section-separator-color, #{$border-color});

    &_item {
        flex: 1;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        gap: 4px;
        padding: 8px 2px;
        text-decoration: none;
        color: var(--tg-theme-hint-color, #{$darkgray});
        transition: color 0.15s ease;

        &--active {
            color: var(--tg-theme-link-color, #{$main-color});
        }
    }

    &_icon {
        font-size: 18px;
    }

    &_label {
        font-size: 11px;
        line-height: 1;
        text-align: center;
    }
}
</style>

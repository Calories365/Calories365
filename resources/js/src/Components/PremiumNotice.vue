<script>
import { mapState } from 'vuex';

export default {
    name: "PremiumNotice",
    computed: {
        ...mapState({
            currentUser: state => state.auth.currentUser
        }),
        // Check if user has premium
        isPremium() {
            if (!this.currentUser?.premium_until) {
                return false;
            }
            const premiumUntil = new Date(this.currentUser.premium_until);
            return premiumUntil > new Date();
        }
    },
    methods: {
        goToPremium() {
            this.$router.push({ name: 'home', hash: '#premium-section' }).then(() => {
                this.$nextTick(() => {
                    setTimeout(() => {
                        const premiumSection = document.getElementById('premium-section');
                        if (premiumSection) {
                            premiumSection.scrollIntoView({ behavior: 'smooth' });
                        }
                    }, 500);
                });
            });
        },
        goToVoiceSection() {
            this.$router.push({ name: 'home', hash: '#hero-picture' }).then(() => {
                this.$nextTick(() => {
                    setTimeout(() => {

                        const isMobile = window.matchMedia('(max-width: 768px)').matches;

                        const telegramLink  = document.querySelector('.telegram_link');
                        const voicePageLink = document.querySelector('.voice_page_link');

                        const animateElement = (element, animationClass) => {
                            if (element) {
                                element.classList.add(animationClass);
                                setTimeout(() => element.classList.remove(animationClass), 6000);
                            }
                        };

                        if (telegramLink && voicePageLink) {

                            if (isMobile) {
                                const heroPicture = document.getElementById('hero-picture');
                                if (heroPicture) {
                                    heroPicture.scrollIntoView({ behavior: 'smooth' });
                                }
                            }

                            ['scale-animation', 'highlight-text-animation'].forEach(cls => {
                                animateElement(telegramLink,  cls);
                                animateElement(voicePageLink, cls);
                            });
                        }

                    }, 600);
                });
            });
        }
    }
}
</script>

<template>
    <div class="premium-notice" v-if="!isPremium">
        <div class="premium-notice-content">
            <p>
                {{ $t('Voice.premiumNoticeStart') }}
                <router-link :to="{ name: 'home' }" class="voice-link" @click.native="goToVoiceSection">
                    {{ $t('Voice.premiumNoticeAssistant') }}
                </router-link>
                {{ $t('Voice.premiumNoticeMiddle') }}
                <router-link :to="{ name: 'home', hash: '#premium-section' }" class="premium-link" @click.native="goToPremium">
                    {{ $t('Voice.premiumLink') }}
                </router-link>
            </p>
        </div>
    </div>
</template>

<style scoped lang="scss">
.premium-notice {
    background-color: #f8f1ff;
    border: 1px solid #d0b2ff;
    border-radius: 8px;
    padding: 12px 20px;
    margin: 10px auto;
    max-width: 1200px;
    position: relative;
    z-index: 100;

    .premium-notice-content {
        p {
            color: #5e35b1;
            font-size: 1rem;
            line-height: 1.4;
            margin: 0;
        }

        .premium-link {
            color: #7e57c2;
            font-weight: 600;
            text-decoration: none;
            border-bottom: 1px solid #7e57c2;
            transition: all 0.2s ease;
            cursor: pointer;

            &:hover {
                color: #5e35b1;
                border-bottom-color: #5e35b1;
            }
        }

        .voice-link {
            color: #5e35b1;
            font-weight: 600;
            text-decoration: none;
            border-bottom: 1px solid #5e35b1;
            transition: all 0.2s ease;
            cursor: pointer;

            &:hover {
                color: #3f1f91;
                border-bottom-color: #3f1f91;
            }
        }
    }
}

@media (max-width: 768px) {
    .premium-notice {
        margin: 10px;
        padding: 10px 15px;

        .premium-notice-content p {
            font-size: 0.9rem;
        }
    }
}

/* Глобальные стили для анимаций */
:global(.pulse-animation) {
    animation: pulse 1s ease-in-out infinite alternate;
}

:global(.highlight-animation) {
    animation: highlight 3s ease-in-out 2;
}

:global(.scale-animation) {
    animation: scale 3s ease-in-out 2;
}

:global(.button-pulse-animation) {
    animation: buttonPulse 1.5s ease-in-out 3;
}

:global(.highlight-text-animation) {
    animation: highlightText 2s ease-in-out 3;
}

@keyframes pulse {
    0% {
        transform: scale(1);
        box-shadow: 0 0 0 rgba(94, 53, 177, 0);
    }
    100% {
        transform: scale(1.05);
        box-shadow: 0 0 15px rgba(94, 53, 177, 0.3);
    }
}

@keyframes highlight {
    0% {
        box-shadow: 0 0 0 rgba(94, 53, 177, 0);
        background-color: #f1f1f1;
    }
    50% {
        box-shadow: 0 0 20px rgba(94, 53, 177, 0.6);
        background-color: #f0e6ff;
    }
    100% {
        box-shadow: 0 0 0 rgba(94, 53, 177, 0);
        background-color: #f1f1f1;
    }
}

@keyframes scale {
    0% {
        transform: scale(1);
        color: inherit;
    }
    50% {
        transform: scale(1.1);
        color: #5e35b1;
    }
    100% {
        transform: scale(1);
        color: inherit;
    }
}

@keyframes buttonPulse {
    0% {
        transform: scale(1);
        box-shadow: 0 0 0 rgba(94, 53, 177, 0);
    }
    50% {
        transform: scale(1.05);
        box-shadow: 0 0 15px rgba(94, 53, 177, 0.8);
        background-color: #7e57c2;
    }
    100% {
        transform: scale(1);
        box-shadow: 0 0 0 rgba(94, 53, 177, 0);
    }
}

@keyframes highlightText {
    0% {
        text-shadow: 0 0 0 rgba(94, 53, 177, 0);
        background-color: transparent;
    }
    50% {
        text-shadow: 0 0 10px rgba(94, 53, 177, 0.8);
        color: #5e35b1;
        background-color: rgba(240, 230, 255, 0.5);
        border-radius: 4px;
        padding: 2px 5px;
        margin: -2px -5px;
    }
    100% {
        text-shadow: 0 0 0 rgba(94, 53, 177, 0);
        background-color: transparent;
    }
}
</style>

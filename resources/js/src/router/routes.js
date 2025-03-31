import Home from '@/pages/Home.vue';
import Login from "@/pages/Login.vue";
import Register from "@/pages/register.vue";
import Recovery from "@/pages/Recovery.vue";
import RecoveryWithToken from "@/pages/RecoveryWithToken.vue";
import Calculation from "@/pages/Calculation.vue";
import Stats from "@/pages/Stats.vue";
import Dairy from "@/pages/Dairy.vue";
import Recipes from "@/pages/Recipes.vue";
import Instructions from "@/pages/Instructions.vue";
import Cabinet from "@/pages/Cabinet.vue";
import Goals from "@/pages/Goals.vue";
import ChangePassword from "@/pages/ChangePassword.vue";
import PrivacyPolicy from "@/pages/PrivacyPolicy.vue";
import TermsOfService from "@/pages/TermsOfService.vue";
import FAQ from "@/pages/FAQ.vue";
import Voice from "@/pages/Voice.vue";

const routes = [
    {
        path: '/',
        component: Home,
        name: 'home',
        meta: {
            needAuth: false,
        },
    },
    {
        path: '/privacy-policy',
        component: PrivacyPolicy,
        name: 'privacyPolicy',
        meta: {
            needAuth: false,
        },
    },{
        path: '/terms-of-service',
        component: TermsOfService,
        name: 'termsOfService',
        meta: {
            needAuth: false,
        },
    },{
        path: '/faq',
        component: FAQ,
        name: 'faq',
        meta: {
            needAuth: false,
        },
    },
    {
        path: '/login',
        component: Login,
        name: 'login',
        meta: {
            needNotAuth: true,
        },
    }, {
        path: '/register',
        component: Register,
        name: 'register',
        meta: {
            needNotAuth: true,
        },
    }, {
        path: '/recovery',
        component: Recovery,
        name: 'recovery',
        meta: {
            needNotAuth: true,
        },
    }, {
        path: '/reset-password/:token',
        component: RecoveryWithToken,
        name: 'recoveryWithToken',
        meta: {
            needNotAuth: true,
        },
    }, {
        path: '/calculation',
        component: Calculation,
        name: 'calculation',
        meta: {
            needAuth: false,
        },
    }, {
        path: '/stats',
        component: Stats,
        name: 'stats',
        meta: {
            needAuth: true,
        },
    }, {
        path: '/diary',
        component: Dairy,
        name: 'diary',
        meta: {
            needAuth: true,
        },
    }, {
        path: '/recipes',
        component: Recipes,
        name: 'recipes',
        meta: {
            needAuth: true,
        },
    }, {
        path: '/instructions',
        component: Instructions,
        name: 'instructions',
        meta: {
            needAuth: true,
        },
    }, {
        path: '/cabinet',
        component: Cabinet,
        name: 'cabinet',
        meta: {
            needAuth: true,
        },
    }, {
        path: '/goals',
        component: Goals,
        name: 'goals',
        meta: {
            needAuth: true,
        },

    },
    {
        path: '/cabinet/change-password',
        component: ChangePassword,
        name: 'change-password',
        meta: {
            needAuth: true,
        },
    },
    {
        path: '/voice',
        component: Voice,
        name: 'voice',
        meta: {
            needAuth: true,
        },
    },
];


export default routes;

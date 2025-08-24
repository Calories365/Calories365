const routes = [
    {
        path: "/",
        component: () => import("@/pages/Home.vue"),
        name: "home",
        meta: {
            needAuth: false,
        },
    },
    {
        path: "/privacy-policy",
        component: () => import("@/pages/PrivacyPolicy.vue"),
        name: "privacyPolicy",
        meta: {
            needAuth: false,
        },
    },
    {
        path: "/terms-of-service",
        component: () => import("@/pages/TermsOfService.vue"),
        name: "termsOfService",
        meta: {
            needAuth: false,
        },
    },
    {
        path: "/faq",
        component: () => import("@/pages/FAQ.vue"),
        name: "faq",
        meta: {
            needAuth: false,
        },
    },
    {
        path: "/payment-and-delivery",
        component: () => import("../pages/PaymentAndDelivery.vue"),
        name: "payment-and-delivery",
        meta: {
            needAuth: false,
        },
    },
    {
        path: "/login",
        component: () => import("@/pages/Login.vue"),
        name: "login",
        meta: {
            needNotAuth: true,
        },
    },
    {
        path: "/register",
        component: () => import("@/pages/register.vue"),
        name: "register",
        meta: {
            needNotAuth: true,
        },
    },
    {
        path: "/recovery",
        component: () => import("@/pages/Recovery.vue"),
        name: "recovery",
        meta: {
            needNotAuth: true,
        },
    },
    {
        path: "/reset-password/:token",
        component: () => import("@/pages/RecoveryWithToken.vue"),
        name: "recoveryWithToken",
        meta: {
            needNotAuth: true,
        },
    },
    {
        path: "/calculation",
        component: () => import("@/pages/Calculation.vue"),
        name: "calculation",
        meta: {
            needAuth: false,
        },
    },
    {
        path: "/stats",
        component: () => import("@/pages/Stats.vue"),
        name: "stats",
        meta: {
            needAuth: true,
        },
    },
    {
        path: "/diary",
        component: () => import("@/pages/Dairy.vue"),
        name: "diary",
        meta: {
            needAuth: true,
        },
    },
    {
        path: "/recipes",
        component: () => import("@/pages/Recipes.vue"),
        name: "recipes",
        meta: {
            needAuth: true,
        },
    },
    {
        path: "/instructions",
        component: () => import("@/pages/Instructions.vue"),
        name: "instructions",
        meta: {
            needAuth: true,
        },
    },
    {
        path: "/cabinet",
        component: () => import("@/pages/Cabinet.vue"),
        name: "cabinet",
        meta: {
            needAuth: true,
        },
    },
    {
        path: "/goals",
        component: () => import("@/pages/Goals.vue"),
        name: "goals",
        meta: {
            needAuth: true,
        },
    },
    {
        path: "/cabinet/change-password",
        component: () => import("@/pages/ChangePassword.vue"),
        name: "change-password",
        meta: {
            needAuth: true,
        },
    },
    {
        path: "/voice",
        component: () => import("@/pages/Voice.vue"),
        name: "voice",
        meta: {
            needAuth: true,
        },
    },
    {
        path: "/thank-you",
        component: () => import("../pages/ThankYou.vue"),
        name: "thank-you",
        meta: {
            needAuth: true,
        },
    },
];

export default routes;

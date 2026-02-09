import './assets/main.css'

import { createApp } from 'vue'
import { createRouter, createWebHistory } from 'vue-router'
import App from './App.vue'

import HomeView from './views/HomeView.vue'
import PatientBilling from "@/views/PatientBilling.vue";
import PatientBillingBad from "@/components/test/PatientBillingBad.vue";
import PatientBillingGood from "@/components/test/PatientBillingGood.vue";

const router = createRouter({
    history: createWebHistory(),
    routes: [
        {
            path: '/',
            name: 'home',
            component: HomeView,
        },
        {
            path: '/billing',
            name: 'billing',
            component: PatientBilling,
        },
        {
            path: '/billing/patient-billing-bad',
            name: 'patient-billing-bad',
            component: PatientBillingBad,
        },
        {
            path: '/billing/patient-billing-good',
            name: 'patient-billing-good',
            component: PatientBillingGood,
        },
    ],
})

createApp(App)
    .use(router)
    .mount('#app')

import { createApp } from 'vue'
import { createRouter, createWebHistory } from 'vue-router'
import App from './App.vue'

import HomeView from './views/HomeView.vue'
import BillingPlayground from './views/BillingPlayground.vue'

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
            component: BillingPlayground,
        },
    ],
})

createApp(App)
    .use(router)
    .mount('#app')

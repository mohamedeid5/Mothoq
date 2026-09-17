import { createRouter, createWebHistory } from 'vue-router';
import { auth } from './auth';
import HomeView from './views/HomeView.vue';
import ServiceCenterView from './views/ServiceCenterView.vue';

const router = createRouter({
    history: createWebHistory(),
    routes: [
        { path: '/', name: 'home', component: HomeView },
        {
            path: '/login',
            name: 'login',
            component: () => import('./views/LoginView.vue'),
            meta: { guestOnly: true },
        },
        {
            path: '/owner',
            name: 'owner.dashboard',
            component: () => import('./views/OwnerDashboardView.vue'),
            meta: { requiresAuth: true, roles: ['center_owner'] },
        },
        {
            path: '/admin',
            name: 'admin.dashboard',
            component: () => import('./views/AdminDashboardView.vue'),
            meta: { requiresAuth: true, roles: ['admin'] },
        },
        { path: '/centers/:slug', name: 'centers.show', component: ServiceCenterView },
        { path: '/:pathMatch(.*)*', redirect: '/' },
    ],
    scrollBehavior() {
        return { top: 0 };
    },
});

router.beforeEach(async (to) => {
    await auth.ensureInitialized();

    if (to.meta.requiresAuth && ! auth.isAuthenticated.value) {
        return { name: 'login', query: { redirect: to.fullPath } };
    }

    if (to.meta.roles && ! to.meta.roles.includes(auth.state.user?.role)) {
        return auth.homeForRole();
    }

    if (to.meta.guestOnly && auth.isAuthenticated.value) {
        return auth.homeForRole();
    }
});

export default router;

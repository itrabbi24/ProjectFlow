import { createRouter, createWebHistory } from 'vue-router';
import { useAuthStore } from '@/stores/auth';

const routes = [
    {
        path: '/login',
        name: 'login',
        component: () => import('@/views/LoginView.vue'),
        meta: { guest: true }
    },
    {
        path: '/',
        component: () => import('@/views/LayoutView.vue'),
        meta: { auth: true },
        children: [
            {
                path: '',
                redirect: '/dashboard'
            },
            {
                path: 'dashboard',
                name: 'dashboard',
                component: () => import('@/views/DashboardView.vue')
            },
            {
                path: 'projects',
                name: 'projects',
                component: () => import('@/views/ProjectsView.vue'),
                meta: { permission: 'view_projects' }
            },
            {
                path: 'projects/:id',
                name: 'project-details',
                component: () => import('@/views/ProjectDetailsView.vue'),
                meta: { permission: 'view_projects' }
            },
            {
                path: 'clients',
                name: 'clients',
                component: () => import('@/views/ClientsView.vue'),
                meta: { permission: 'view_clients' }
            },
            {
                path: 'purchases',
                name: 'purchases',
                component: () => import('@/views/PurchasesView.vue'),
                meta: { permission: 'view_purchases' }
            },
            {
                path: 'expenses',
                name: 'expenses',
                component: () => import('@/views/ExpensesView.vue'),
                meta: { permission: 'view_expenses' }
            },
            {
                path: 'reports',
                name: 'reports',
                component: () => import('@/views/ReportsView.vue'),
                meta: { permission: 'view_reports' }
            },
            {
                path: 'categories',
                name: 'categories',
                component: () => import('@/views/CategoriesView.vue'),
                meta: { permission: 'view_categories' }
            },
            {
                path: 'users',
                name: 'users',
                component: () => import('@/views/UsersView.vue'),
                meta: { permission: 'view_users' }
            },
            {
                path: 'settings',
                name: 'settings',
                component: () => import('@/views/SettingsView.vue'),
                meta: { permission: 'view_settings' }
            }
        ]
    },
    {
        path: '/:pathMatch(.*)*',
        redirect: '/'
    }
];

const router = createRouter({
    history: createWebHistory(),
    routes
});

router.beforeEach((to, from) => {
    const authStore = useAuthStore();

    if (to.meta.auth && !authStore.isAuthenticated) {
        return { name: 'login' };
    }

    if (to.meta.guest && authStore.isAuthenticated) {
        return { name: 'dashboard' };
    }

    if (to.meta.permission && !authStore.hasPermission(to.meta.permission)) {
        return { name: 'dashboard' };
    }

    return true;
});

export default router;

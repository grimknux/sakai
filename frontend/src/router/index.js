import AppLayout from '@/layout/AppLayout.vue';

import { createRouter, createWebHistory } from 'vue-router';

import { me } from '@/api/auth';
import { APP_NAME } from '@/config/app';
import Dashboard from '../views/Dashboard.vue';
import HomePage from '../views/HomePage.vue';
import UsersPage from '../views/UsersPage.vue';
import Forbidden from '../views/pages/Forbidden.vue';
import LoginPage from '../views/pages/auth/LoginPage.vue';

const router = createRouter({
    history: createWebHistory('/'),
    routes: [
        { path: '/', name: 'login', component: LoginPage, meta: { title: 'Login' } },
        { path: '/forgot-password', name: 'forgot-password', component: () => import('@/views/pages/auth/ForgotPassword.vue'), meta: { title: 'Forgot Password' } },
        { path: '/reset-password', name: 'reset-password', component: () => import('@/views/pages/auth/ResetPassword.vue'), meta: { title: 'Reset Password' } },

        {
            path: '/',
            component: AppLayout,
            meta: { title: 'App', requiresAuth: true }, // protects everything under /app
            children: [
                // MAIN ROUTES (also add to sidebar menu if you want them visible there)
                {
                    path: 'home',
                    name: 'home',
                    component: HomePage,
                    meta: { title: 'Home' }
                },
                {
                    path: 'dashboard',
                    name: 'dashboard',
                    component: Dashboard,
                    meta: { title: 'Dashboard', permission: 'dashboard.access' }
                },

                // ADMIN ROUTES (also add to sidebar menu if you want them visible there)
                {
                    path: 'admin/users',
                    name: 'users',
                    component: UsersPage,
                    meta: { title: 'Users', permission: 'users.access' }
                },
                {
                    path: 'admin/roles',
                    name: 'admin.roles',
                    component: () => import('@/views/admin/RolesPage.vue'),
                    meta: { title: 'Roles', permission: 'roles.access' }
                },
                {
                    path: 'admin/role-permissions',
                    name: 'admin.rolePermissions',
                    component: () => import('@/views/admin/RolePermissionsPage.vue'),
                    meta: { title: 'Role Permissions', permission: 'rolepermissions.assign' }
                },
                {
                    path: 'admin/permissions',
                    name: 'admin.permissions',
                    component: () => import('@/views/admin/PermissionsViewerPage.vue'),
                    meta: { title: 'Permissions', permission: 'permissions.view' }
                },
                {
                    path: 'admin/audit-logs',
                    name: 'admin.auditLogs',
                    component: () => import('@/views/admin/AuditLogsPage.vue'),
                    meta: { title: 'Audit Logs', permission: 'audit.view' }
                },
                {
                    path: 'admin/login-attempts',
                    name: 'admin.loginAttempts',
                    component: () => import('@/views/admin/LoginAttemptsPage.vue'),
                    meta: { title: 'Login Attempts', permission: 'security.view' }
                },
                { path: '/change-password', name: 'change-password', component: () => import('@/views/pages/auth/ChangePassword.vue'), meta: { title: 'Change Password' } }
            ]
        },
        {
            path: '/forbidden',
            name: 'forbidden',
            component: Forbidden,
            meta: { title: 'Forbidden', requiresAuth: true }
        },
        {
            path: '/:pathMatch(.*)*',
            name: 'notfound',
            component: () => import('@/views/pages/NotFound.vue'),
            meta: { title: 'Not Found' }
        }
    ]
});

// Simple auth guard
function getPerms() {
    try {
        return JSON.parse(localStorage.getItem('permissions') || '[]');
    } catch {
        return [];
    }
}

function getRoles() {
    try {
        return JSON.parse(localStorage.getItem('roles') || '[]');
    } catch {
        return [];
    }
}

function getUser() {
    try {
        return JSON.parse(localStorage.getItem('user') || 'null');
    } catch {
        return null;
    }
}
function clearAuthCache() {
    localStorage.removeItem('user');
    localStorage.removeItem('permissions');
    localStorage.removeItem('roles');
}

// ✅ prevent calling /me repeatedly on every route change
let meChecked = false;

router.beforeEach(async (to) => {
    const requiresAuth = to.matched.some((r) => r.meta.requiresAuth);

    // --------- Public routes ---------
    if (!requiresAuth) {
        // if already logged-in (cached), don't allow going to login
        if (to.name === 'login' && localStorage.getItem('user')) {
            return { name: 'home' }; // Option A landing
        }
        return;
    }

    // --------- Protected routes: ALWAYS validate session at least once ---------
    if (!meChecked) {
        try {
            const res = await me(); // validates cookie/session
            meChecked = true;

            if (res?.user) localStorage.setItem('user', JSON.stringify(res.user));
            localStorage.setItem('permissions', JSON.stringify(res?.permissions || []));
            localStorage.setItem('roles', JSON.stringify(res?.roles || []));
        } catch (e) {
            meChecked = false;
            clearAuthCache();
            return { name: 'login' };
        }
    } else {
        // already checked in this SPA session
        if (!localStorage.getItem('user')) {
            clearAuthCache();
            return { name: 'login' };
        }
    }

    // --------- Permission gate ---------
    const requiredPerm = [...to.matched].reverse().find((r) => r.meta?.permission)?.meta?.permission || null;

    if (requiredPerm) {
        const user = getUser();
        const perms = getPerms();

        // ✅ superadmin bypass
        if (!(user?.is_superadmin == 1 || perms.includes(requiredPerm))) {
            return { name: 'forbidden' };
        }
    }

    // --------- Roles gate (optional) ---------
    const requiredRoles = [...to.matched].reverse().find((r) => r.meta?.roles)?.meta?.roles || null;

    if (requiredRoles?.length) {
        const roles = getRoles();
        const allowed = requiredRoles.some((r) => roles.includes(r));
        if (!allowed) return { name: 'forbidden' };
    }

    return;
});

router.afterEach((to) => {
    const baseTitle = APP_NAME;

    if (to.meta.title) {
        document.title = `${to.meta.title} | ${baseTitle}`;
    } else {
        document.title = baseTitle;
    }
});

export default router;

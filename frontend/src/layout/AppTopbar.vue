<script setup>
import { logout } from '@/api/auth';
import { useLayout } from '@/layout/composables/layout';
import { ref } from 'vue';
import { useRouter } from 'vue-router';
import AppConfigurator from './AppConfigurator.vue';

const router = useRouter();

async function onLogout() {
    try {
        await logout();
    } finally {
        localStorage.removeItem('user');
        localStorage.removeItem('permissions');
        localStorage.removeItem('roles');

        await router.replace({ name: 'login' });
    }
}

function goToChangePassword() {
    router.push({ path: '/change-password' });
    // or: router.push({ name: 'change-password' })
}

const { toggleMenu, toggleDarkMode, isDarkTheme } = useLayout();

const profile = ref([
    { label: 'Change Password', icon: 'pi pi-fw pi-unlock', command: goToChangePassword },
    { label: 'Logout', icon: 'pi pi-fw pi-sign-out', command: onLogout }
]);
</script>

<template>
    <div class="layout-topbar">
        <div class="layout-topbar-logo-container">
            <button class="layout-menu-button layout-topbar-action" @click="toggleMenu">
                <i class="pi pi-bars"></i>
            </button>
            <router-link to="/" class="layout-topbar-logo">
                <img src="/layout/img/dohlogo.png" alt="DOH Logo" class="w-10 h-10 object-contain flex-shrink-0" />
                <div class="min-w-0">
                    <!-- Small screens (default): abbreviation -->
                    <span class="inline sm:hidden font-semibold truncate text-xs">
                        <div class="text-sm">ISCHED</div>
                    </span>

                    <!-- sm and up: full name -->
                    <span class="hidden sm:inline font-semibold truncate">
                        ICT Schedule System <br />
                        <div class="text-xs">Department of Health - Ilocos Center for Health Development</div>
                    </span>
                </div>
            </router-link>
        </div>

        <div class="layout-topbar-actions">
            <div class="layout-config-menu">
                <button type="button" class="layout-topbar-action" @click="toggleDarkMode">
                    <i :class="['pi', { 'pi-moon': isDarkTheme, 'pi-sun': !isDarkTheme }]"></i>
                </button>
                <div class="relative">
                    <button
                        v-styleclass="{ selector: '@next', enterFromClass: 'hidden', enterActiveClass: 'p-anchored-overlay-enter-active', leaveToClass: 'hidden', leaveActiveClass: 'p-anchored-overlay-leave-active', hideOnOutsideClick: true }"
                        type="button"
                        class="layout-topbar-action layout-topbar-action-highlight"
                    >
                        <i class="pi pi-palette"></i>
                    </button>
                    <AppConfigurator />
                </div>
            </div>

            <button
                class="layout-topbar-menu-button layout-topbar-action"
                v-styleclass="{ selector: '@next', enterFromClass: 'hidden', enterActiveClass: 'p-anchored-overlay-enter-active', leaveToClass: 'hidden', leaveActiveClass: 'p-anchored-overlay-leave-active', hideOnOutsideClick: true }"
            >
                <i class="pi pi-ellipsis-v"></i>
            </button>

            <div class="layout-topbar-menu hidden lg:block">
                <div class="layout-topbar-menu-content">
                    <button type="button" class="layout-topbar-action" @click="$refs.menu.toggle($event)">
                        <i class="pi pi-user"></i>
                        <span>Profile</span>
                    </button>
                    <Menu ref="menu" popup :model="profile" class="min-w-40!"></Menu>
                </div>
            </div>
        </div>
    </div>
</template>

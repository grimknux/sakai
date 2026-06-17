<script setup>
import { computed, ref } from 'vue';
import AppMenuItem from './AppMenuItem.vue';

function getPerms() {
    try {
        return JSON.parse(localStorage.getItem('permissions') || '[]');
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

const user = getUser();
const perms = getPerms();

const can = (perm) => {
    // if no permission required, allow
    if (!perm) return true;

    // superadmin bypass (your backend uses this too)
    if (user?.is_superadmin == 1) return true;

    return perms.includes(perm);
};

const model = ref([
    {
        label: 'Main',
        items: [
            {
                label: 'Home',
                icon: 'pi pi-fw pi-home',
                to: '/home'
            },
            {
                label: 'Dashboard',
                icon: 'pi pi-fw pi-chart-line',
                to: '/dashboard',
                permission: 'dashboard.access'
            }
        ]
    },

    {
        label: 'Preventive Maintenance Service',
        icon: 'pi pi-fw pi-cog',
        items: [
            {
                label: 'PMS Record',
                icon: 'pi pi-fw pi-file-edit',
                to: '/pms/records',
                permission: 'pms.record.access'
            },
            {
                label: 'PMS Schedule',
                icon: 'pi pi-fw pi-calendar',
                to: '/pms/schedules',
                permission: 'pms.schedule.access'
            },
            {
                label: 'PMS Question',
                icon: 'pi pi-fw pi-question-circle',
                to: '/pms/questions',
                permission: 'pms.question.access'
            }
        ]
    },

    {
        label: 'Reports',
        items: [
            {
                label: 'PMS Record Report',
                icon: 'pi pi-fw pi-book',
                to: '/report/pms/records',
                permission: 'pms.record.access.report'
            }
        ]
    },

    {
        label: 'Asset Management',
        items: [
            {
                label: 'Inventory',
                icon: 'pi pi-fw pi-box',
                to: '/assets/inventory',
                permission: 'inventory.access'
            },
            {
                label: 'Software',
                icon: 'pi pi-fw pi-microsoft',
                to: '/assets/softwares',
                permission: 'softwares.access'
            },
            {
                label: 'Device Types',
                icon: 'pi pi-fw pi-desktop',
                to: '/assets/device-types',
                permission: 'device_types.access'
            },
            {
                label: 'Software Types',
                icon: 'pi pi-fw pi-tags',
                to: '/assets/software-types',
                permission: 'software_types.access'
            },
            {
                label: 'License Types',
                icon: 'pi pi-fw pi-key',
                to: '/assets/license-types',
                permission: 'license_types.access'
            }
        ]
    },
    {
        label: 'Management',
        items: [
            {
                label: 'Organization',
                icon: 'pi pi-fw pi-sitemap',
                items: [
                    {
                        label: 'Users',
                        icon: 'pi pi-fw pi-users',
                        to: '/admin/users',
                        permission: 'users.access'
                    },
                    {
                        label: 'Sections',
                        icon: 'pi pi-fw pi-briefcase',
                        to: '/admin/org/sections',
                        permission: 'sections.access'
                    },
                    {
                        label: 'Division',
                        icon: 'pi pi-fw pi-share-alt',
                        to: '/admin/org/divisions',
                        permission: 'divisions.access'
                    },
                    {
                        label: 'Building',
                        icon: 'pi pi-fw pi-building',
                        to: '/admin/org/buildings',
                        permission: 'buildings.access'
                    }
                ]
            },
            {
                label: 'Access Control',
                icon: 'pi pi-fw pi-shield',
                items: [
                    {
                        label: 'Roles',
                        icon: 'pi pi-fw pi-list',
                        to: '/admin/roles',
                        permission: 'roles.access'
                    },
                    {
                        label: 'Permissions',
                        icon: 'pi pi-fw pi-list-check',
                        to: '/admin/permissions',
                        permission: 'permissions.view'
                    },
                    {
                        label: 'Role Permissions',
                        icon: 'pi pi-fw pi-key',
                        to: '/admin/role-permissions',
                        permission: 'rolepermissions.assign'
                    }
                ]
            },
            {
                label: 'Audit Logs',
                icon: 'pi pi-fw pi-history',
                to: '/admin/audit-logs',
                permission: 'audit.view'
            },
            {
                label: 'Login Attempts',
                icon: 'pi pi-fw pi-sign-in',
                to: '/admin/login-attempts',
                permission: 'security.view'
            }
        ]
    }
]);

// Recursively filter menu items + remove empty groups
const filteredModel = computed(() => {
    const filterItems = (items = []) => {
        return items
            .filter((it) => {
                if (it.separator) return true;
                if (!can(it.permission)) return false;
                return true;
            })
            .map((it) => {
                // If an item has nested children, filter them too
                if (it.items) {
                    const childItems = filterItems(it.items);
                    return { ...it, items: childItems };
                }
                return it;
            })
            .filter((it) => {
                // If item is a group with children, keep only if it still has children
                if (it.items) return it.items.length > 0;
                return true;
            });
    };

    return model.value
        .map((group) => {
            const items = filterItems(group.items || []);
            return { ...group, items };
        })
        .filter((group) => group.items && group.items.length > 0);
});
</script>

<template>
    <ul class="layout-menu">
        <template v-for="(item, i) in filteredModel" :key="i">
            <app-menu-item v-if="!item.separator" :item="item" :index="i"></app-menu-item>
            <li v-else class="menu-separator"></li>
        </template>
    </ul>
</template>

<style lang="scss" scoped></style>

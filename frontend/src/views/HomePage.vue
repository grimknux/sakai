<script setup>
import { computed } from 'vue';

const user = JSON.parse(localStorage.getItem('user') || 'null');
const perms = JSON.parse(localStorage.getItem('permissions') || '[]');
const roles = JSON.parse(localStorage.getItem('roles') || '[]');

const userName = computed(() => {
    if (!user) return 'Guest';
    return user.full_name || user.name || user.username || 'User';
});

const userRole = computed(() => {
    return roles || 'No role assigned';
});

const permissionCount = computed(() => perms.length || 0);
const rolesCount = computed(() => roles.length || 0);
const isSuperAdmin = computed(() => {
    return roles.includes('super-admin');
});

const groupedStats = computed(() => [
    {
        label: 'Active Session',
        value: user ? 'Yes' : 'No',
        icon: 'pi pi-user'
    },
    {
        label: 'Role',
        value: rolesCount.value,
        icon: 'pi pi-shield'
    },
    {
        label: 'Permissions',
        isPermissions: true, // 👈 flag
        icon: 'pi pi-key'
    }
]);
</script>

<template>
    <div class="grid grid-cols-12 gap-4">
        <!-- Welcome Hero -->
        <div class="col-span-12">
            <div class="card overflow-hidden">
                <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-6">
                    <div class="flex-1">
                        <div class="text-surface-500 font-medium mb-2">Welcome</div>
                        <h1 class="text-3xl lg:text-4xl font-bold m-0 mb-3">Online Preventive Maintenance Service System</h1>
                        <p class="text-surface-600 dark:text-surface-300 text-lg leading-relaxed m-0 mb-4">Manage preventive maintenance schedules, track equipment inspection records, and monitor service compliance in one place.</p>

                        <div class="flex flex-wrap gap-2">
                            <Tag severity="info" value="Preventive Maintenance" />
                            <Tag severity="success" value="Inspection Records" />
                            <Tag severity="warn" value="Monitoring" />
                        </div>
                    </div>

                    <div class="flex-shrink-0">
                        <div class="surface-100 dark:surface-800 rounded-2xl p-5 text-center min-w-64">
                            <div class="text-surface-500 mb-2">Signed in as</div>
                            <div class="text-2xl font-bold mb-1">{{ userName }}</div>
                            <div class="text-surface-500">{{ userRole }}</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Stats -->
        <div v-for="item in groupedStats" :key="item.label" class="col-span-12 md:col-span-4">
            <div class="card h-full">
                <div class="flex items-center justify-between">
                    <div>
                        <div class="text-surface-500 text-sm mb-2">{{ item.label }}</div>
                        <div class="text-2xl font-semibold">
                            <!-- Permissions Custom Display -->
                            <template v-if="item.isPermissions">
                                <!-- Super Admin -->
                                <Tag v-if="isSuperAdmin" value="Super Admin" severity="danger" />

                                <!-- Normal Permissions -->
                                <div v-else-if="perms.length" class="flex flex-wrap gap-1">
                                    <Tag v-for="perm in perms.slice(0, 2)" :key="perm || perm.slug" :value="perm || perm.slug" severity="contrast" />
                                    <Tag v-if="perms.length > 2" :value="`+${perms.length - 2}`" severity="info" />
                                </div>

                                <!-- No Permissions -->
                                <span v-else>None</span>
                            </template>

                            <!-- Default Display -->
                            <template v-else>
                                {{ item.value }}
                            </template>
                        </div>
                    </div>
                    <div class="flex items-center justify-center w-12 h-12 rounded-full bg-primary-100 dark:bg-primary-900">
                        <i :class="item.icon" class="text-primary text-xl"></i>
                    </div>
                </div>
            </div>
        </div>

        <!-- About System -->
        <div class="col-span-12 lg:col-span-8">
            <div class="card h-full">
                <div class="text-xl font-semibold mb-3">About the System</div>
                <p class="text-surface-600 dark:text-surface-300 leading-relaxed mb-4">
                    The Online Preventive Maintenance Service System helps your team organize maintenance schedules, perform routine inspections, record findings and recommendations, and ensure equipment remains operational and compliant.
                </p>

                <div class="grid grid-cols-12 gap-3">
                    <div class="col-span-12 md:col-span-6">
                        <div class="border-1 surface-border rounded-xl p-4 h-full">
                            <div class="flex items-center gap-2 mb-2">
                                <i class="pi pi-calendar text-primary"></i>
                                <span class="font-semibold">Schedule Management</span>
                            </div>
                            <div class="text-surface-500 text-sm">Organize and monitor preventive maintenance schedules for all tracked equipment and assets.</div>
                        </div>
                    </div>

                    <div class="col-span-12 md:col-span-6">
                        <div class="border-1 surface-border rounded-xl p-4 h-full">
                            <div class="flex items-center gap-2 mb-2">
                                <i class="pi pi-file-edit text-primary"></i>
                                <span class="font-semibold">PMS Records</span>
                            </div>
                            <div class="text-surface-500 text-sm">Capture inspection answers, findings, remarks, and recommendations in a structured way.</div>
                        </div>
                    </div>

                    <div class="col-span-12 md:col-span-6">
                        <div class="border-1 surface-border rounded-xl p-4 h-full">
                            <div class="flex items-center gap-2 mb-2">
                                <i class="pi pi-box text-primary"></i>
                                <span class="font-semibold">Inventory Tracking</span>
                            </div>
                            <div class="text-surface-500 text-sm">Link preventive maintenance activities to specific inventory items and equipment records.</div>
                        </div>
                    </div>

                    <div class="col-span-12 md:col-span-6">
                        <div class="border-1 surface-border rounded-xl p-4 h-full">
                            <div class="flex items-center gap-2 mb-2">
                                <i class="pi pi-chart-line text-primary"></i>
                                <span class="font-semibold">Monitoring & Compliance</span>
                            </div>
                            <div class="text-surface-500 text-sm">Improve visibility of maintenance activity and support timely follow-up and reporting.</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Session / Permissions -->
        <div class="col-span-12 lg:col-span-4">
            <div class="card h-full">
                <div class="text-xl font-semibold mb-3">Session Information</div>

                <div class="flex flex-col gap-3 mb-4">
                    <div>
                        <div class="text-sm text-surface-500 mb-1">User</div>
                        <div class="font-medium">{{ userName }}</div>
                    </div>

                    <div>
                        <div class="text-sm text-surface-500 mb-1">Role</div>
                        <div class="font-medium">{{ userRole }}</div>
                    </div>

                    <div>
                        <div class="text-sm text-surface-500 mb-1">Permission Count</div>
                        <div class="font-medium">{{ permissionCount }}</div>
                    </div>
                </div>

                <Divider />

                <div>
                    <div class="text-sm text-surface-500 mb-2">Permissions</div>

                    <!-- If Super Admin -->
                    <div v-if="isSuperAdmin">
                        <Tag value="Super Admin (All Permissions)" severity="danger" />
                    </div>

                    <!-- Normal Permissions -->
                    <div v-else-if="perms.length" class="flex flex-wrap gap-2">
                        <Tag v-for="perm in perms" :key="perm || perm.slug" :value="perm || perm.slug" severity="contrast" />
                    </div>

                    <!-- No Permissions -->
                    <div v-else class="text-surface-500 text-sm">No permissions loaded.</div>
                </div>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="col-span-12">
            <div class="card">
                <div class="text-xl font-semibold mb-4">Quick Actions</div>

                <div class="grid grid-cols-12 gap-3">
                    <div class="col-span-12 md:col-span-6 lg:col-span-3">
                        <div class="border-1 surface-border rounded-xl p-4 h-full hover:shadow-2 transition-duration-200">
                            <div class="flex items-center gap-3 mb-3">
                                <i class="pi pi-calendar-plus text-primary text-xl"></i>
                                <span class="font-semibold">PMS Schedules</span>
                            </div>
                            <p class="text-sm text-surface-500 m-0">View and manage preventive maintenance schedules.</p>
                        </div>
                    </div>

                    <div class="col-span-12 md:col-span-6 lg:col-span-3">
                        <div class="border-1 surface-border rounded-xl p-4 h-full hover:shadow-2 transition-duration-200">
                            <div class="flex items-center gap-3 mb-3">
                                <i class="pi pi-check-square text-primary text-xl"></i>
                                <span class="font-semibold">PMS Records</span>
                            </div>
                            <p class="text-sm text-surface-500 m-0">Create and review maintenance inspection records.</p>
                        </div>
                    </div>

                    <div class="col-span-12 md:col-span-6 lg:col-span-3">
                        <div class="border-1 surface-border rounded-xl p-4 h-full hover:shadow-2 transition-duration-200">
                            <div class="flex items-center gap-3 mb-3">
                                <i class="pi pi-database text-primary text-xl"></i>
                                <span class="font-semibold">Inventory</span>
                            </div>
                            <p class="text-sm text-surface-500 m-0">Browse and maintain equipment and inventory records.</p>
                        </div>
                    </div>

                    <div class="col-span-12 md:col-span-6 lg:col-span-3">
                        <div class="border-1 surface-border rounded-xl p-4 h-full hover:shadow-2 transition-duration-200">
                            <div class="flex items-center gap-3 mb-3">
                                <i class="pi pi-users text-primary text-xl"></i>
                                <span class="font-semibold">Administration</span>
                            </div>
                            <p class="text-sm text-surface-500 m-0">Manage users, roles, and permissions for the system.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

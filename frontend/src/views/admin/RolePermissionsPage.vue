<script setup>
import { permsApi, rolePermsApi, rolesApi } from '@/api/admin';
import { useToast } from 'primevue/usetoast';
import { computed, onMounted, ref } from 'vue';

const toast = useToast();

const roles = ref([]);
const permissions = ref([]);
const selectedRoleId = ref(null);

const assignedPermIds = ref([]);
const loadingRolePerms = ref(false);
const loading = ref(false);

// NEW
const collapsedModules = ref({});

const selectedRole = computed(() => roles.value.find((r) => r.id === selectedRoleId.value) || null);
const isSuperAdminRole = computed(() => (selectedRole.value?.slug || '') === 'super-admin');

async function loadBase() {
    try {
        const r = await rolesApi.dropdown();
        roles.value = (r.roles || []).map((x) => ({ ...x, id: Number(x.id) }));

        const p = await permsApi.list();
        permissions.value = (p.permissions || []).map((x) => ({ ...x, id: Number(x.id) }));
    } catch (e) {
        const msg = e?.response?.data?.message || e?.response?.data?.messages?.error || e?.message || 'Failed to load data';
        toast.add({ severity: 'error', summary: 'Error', detail: msg, life: 4000 });
    }
}

async function loadRolePerms(roleId) {
    const rid = roleId || selectedRoleId.value;
    if (!rid) {
        assignedPermIds.value = [];
        collapsedModules.value = {};
        return;
    }

    loadingRolePerms.value = true;
    assignedPermIds.value = [];

    try {
        const res = await rolePermsApi.get(rid);
        assignedPermIds.value = (res.permission_ids || []).map(Number);

        // set initial open/collapsed state only after loading role perms
        syncCollapsedModules();
    } catch (e) {
        assignedPermIds.value = [];
        collapsedModules.value = {};
        const msg = e?.response?.data?.message || e?.response?.data?.messages?.error || e?.message || 'Error';
        toast.add({ severity: 'error', summary: 'Error', detail: msg, life: 4000 });
    } finally {
        loadingRolePerms.value = false;
    }
}

onMounted(async () => {
    await loadBase();
    if (roles.value.length) {
        selectedRoleId.value = roles.value[0].id;
        await loadRolePerms(selectedRoleId.value);
    }
});

const permsByModule = computed(() => {
    const map = {};
    for (const p of permissions.value) {
        const mod = p.module || 'general';
        if (!map[mod]) map[mod] = [];
        map[mod].push(p);
    }

    for (const k of Object.keys(map)) {
        map[k].sort((a, b) => a.name.localeCompare(b.name));
    }

    return map;
});

function modulePermIds(list) {
    return list.map((p) => p.id);
}

function isModuleFullySelected(list) {
    const ids = modulePermIds(list);
    return ids.length > 0 && ids.every((id) => assignedPermIds.value.includes(id));
}

function isModulePartiallySelected(list) {
    const ids = modulePermIds(list);
    const selectedCount = ids.filter((id) => assignedPermIds.value.includes(id)).length;
    return selectedCount > 0 && selectedCount < ids.length;
}

function toggleModule(list, checked) {
    const ids = modulePermIds(list);

    if (checked) {
        assignedPermIds.value = [...new Set([...assignedPermIds.value, ...ids])];
    } else {
        assignedPermIds.value = assignedPermIds.value.filter((id) => !ids.includes(id));
    }
}

// NEW
function syncCollapsedModules() {
    const state = {};

    for (const [module, list] of Object.entries(permsByModule.value)) {
        const hasChecked = list.some((p) => assignedPermIds.value.includes(p.id));

        // checked = OPEN
        // none checked = COLLAPSED
        state[module] = !hasChecked;
    }

    collapsedModules.value = state;
}

async function save() {
    if (!selectedRoleId.value) return;

    if (isSuperAdminRole.value) {
        toast.add({ severity: 'warn', summary: 'Locked', detail: 'Super Admin role permissions are locked.', life: 3000 });
        return;
    }

    loading.value = true;
    try {
        const payloadIds = assignedPermIds.value.map(Number);
        await rolePermsApi.set(selectedRoleId.value, payloadIds);
        toast.add({ severity: 'success', summary: 'Saved', detail: 'Role permissions updated', life: 3000 });
    } catch (e) {
        const msg = e?.response?.data?.message || e?.response?.data?.messages?.error || e?.message || 'Error';
        toast.add({ severity: 'error', summary: 'Error', detail: msg, life: 4000 });
    } finally {
        loading.value = false;
    }
}
</script>

<template>
    <div class="card">
        <div class="flex items-center justify-between mb-4">
            <h4 class="m-0">Role Permissions</h4>
            <Button label="Save" icon="pi pi-check" :loading="loading" :disabled="!selectedRoleId || isSuperAdminRole" @click="save" />
        </div>

        <div class="grid grid-cols-12 gap-4">
            <div class="col-span-12 md:col-span-3">
                <label class="block font-bold mb-2">Select Role</label>

                <!-- Use update:modelValue for PrimeVue Select -->
                <Select v-model="selectedRoleId" :options="roles" optionLabel="name" optionValue="id" placeholder="Select role" class="w-full" @update:modelValue="loadRolePerms" />

                <small v-if="isSuperAdminRole" class="block mt-2 text-orange-600 dark:text-orange-400"> Super Admin role is locked (cannot edit permissions). </small>
                <small v-else class="block mt-2 text-surface-500"> Select permissions for the chosen role. </small>
            </div>

            <div class="col-span-12 md:col-span-9">
                <div v-if="!selectedRoleId" class="p-3 border rounded">Select a role to manage permissions.</div>

                <div v-else class="flex flex-col gap-4">
                    <Fieldset v-for="(list, module) in permsByModule" :key="module" v-model:collapsed="collapsedModules[module]" class="mb-3">
                        <template #legend>
                            <div class="flex items-center justify-between w-full gap-3">
                                <div class="flex items-center gap-2 cursor-pointer select-none hover:bg-surface-100 dark:hover:bg-surface-800 px-2 py-1 rounded" @click="collapsedModules[module] = !collapsedModules[module]">
                                    <span class="pi" style="color: var(--primary-color)" :class="collapsedModules[module] ? 'pi-chevron-right' : 'pi-chevron-down'" @click.stop></span>
                                    <span class="font-bold capitalize">{{ module }}</span>

                                    <small class="text-surface-500"> ({{ list.filter((p) => assignedPermIds.includes(p.id)).length }}/{{ list.length }}) </small>
                                </div>

                                <div class="flex items-center gap-2">
                                    <Checkbox :modelValue="isModuleFullySelected(list)" :indeterminate="isModulePartiallySelected(list)" :binary="true" :disabled="isSuperAdminRole" @update:modelValue="toggleModule(list, $event)" />
                                    <span class="text-sm">Select all</span>
                                </div>
                            </div>
                        </template>

                        <div class="grid grid-cols-12 gap-2">
                            <div v-for="p in list" :key="p.id" class="col-span-12 md:col-span-6 flex items-center gap-2">
                                <Checkbox v-model="assignedPermIds" :value="p.id" :disabled="isSuperAdminRole" />
                                <span>
                                    {{ p.name }}
                                    <small class="text-surface-500">({{ p.slug }})</small>
                                </span>
                            </div>
                        </div>
                    </Fieldset>

                    <div class="p-3 border rounded bg-surface-50 dark:bg-surface-900">
                        <div class="font-medium">Debug:</div>
                        <div class="text-sm">Role: {{ selectedRole?.slug }} | Selected IDs: {{ assignedPermIds }}</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

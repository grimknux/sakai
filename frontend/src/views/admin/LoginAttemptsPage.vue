<script setup>
import { securityApi } from '@/api/admin';
import { FilterMatchMode } from '@primevue/core/api';
import { useToast } from 'primevue/usetoast';
import { onMounted, ref } from 'vue';

const toast = useToast();
const rows = ref([]);
const filters = ref({ global: { value: null, matchMode: FilterMatchMode.CONTAINS } });

async function load() {
    const res = await securityApi.attempts();
    rows.value = res.attempts || [];
}

onMounted(load);

async function unlock(userId) {
    try {
        await securityApi.unlockUser(userId);
        toast.add({ severity: 'success', summary: 'Unlocked', detail: 'User unlocked', life: 3000 });
        await load();
    } catch (e) {
        const msg = e?.response?.data?.message || e?.response?.data?.messages?.error || e?.message || 'Error';
        toast.add({ severity: 'error', summary: 'Error', detail: msg, life: 4000 });
    }
}

function getPerms() {
    try {
        return JSON.parse(localStorage.getItem('permissions') || '[]');
    } catch {
        return [];
    }
}
const perms = getPerms();
const can = (perm) => perms.includes(perm) || JSON.parse(localStorage.getItem('user') || '{}')?.is_superadmin == 1;
</script>

<template>
    <div class="card">
        <DataTable :value="rows" :paginator="true" :rows="10" :filters="filters" dataKey="id">
            <template #header>
                <div class="flex flex-wrap gap-2 items-center justify-between">
                    <h4 class="m-0">Login Attempts / Locks</h4>
                    <IconField>
                        <InputIcon><i class="pi pi-search" /></InputIcon>
                        <InputText v-model="filters['global'].value" placeholder="Search..." />
                    </IconField>
                </div>
            </template>

            <Column field="username" header="Username" sortable style="min-width: 10rem" />
            <Column field="ip_address" header="IP" sortable style="min-width: 10rem" />
            <Column field="success" header="Success">
                <template #body="{ data }">
                    <Tag :severity="data.success ? 'success' : 'danger'" :value="data.success ? 'YES' : 'NO'" />
                </template>
            </Column>
            <Column field="attempted_at" header="Attempted At" sortable style="min-width: 12rem" />
            <Column field="locked_until" header="Locked Until" sortable style="min-width: 12rem" />

            <Column :exportable="false" style="min-width: 10rem">
                <template #body="{ data }">
                    <Button v-if="can('security.unlock') && data.user_id && data.locked_until" label="Unlock" icon="pi pi-unlock" severity="secondary" @click="unlock(data.user_id)" />
                </template>
            </Column>
        </DataTable>
    </div>
</template>

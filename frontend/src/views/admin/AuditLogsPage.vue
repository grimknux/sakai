<script setup>
import { auditApi } from '@/api/admin';
import { FilterMatchMode } from '@primevue/core/api';
import { onMounted, ref } from 'vue';

const logs = ref([]);
const filters = ref({ global: { value: null, matchMode: FilterMatchMode.CONTAINS } });

onMounted(async () => {
    const res = await auditApi.list();
    logs.value = res.logs || [];
});
</script>

<template>
    <div class="card">
        <DataTable :value="logs" :paginator="true" :rows="10" :filters="filters" dataKey="id">
            <template #header>
                <div class="flex flex-wrap gap-2 items-center justify-between">
                    <h4 class="m-0">Audit Logs</h4>
                    <IconField>
                        <InputIcon><i class="pi pi-search" /></InputIcon>
                        <InputText v-model="filters['global'].value" placeholder="Search..." />
                    </IconField>
                </div>
            </template>

            <Column field="created_at" header="Date" sortable style="min-width: 12rem" />
            <Column field="actor_username" header="Actor" sortable style="min-width: 10rem" />
            <Column field="action" header="Action" sortable style="min-width: 14rem" />
            <Column field="entity" header="Entity" sortable style="min-width: 10rem" />
            <Column field="entity_id" header="Entity ID" sortable style="min-width: 8rem" />
            <Column field="meta" header="Meta" style="min-width: 18rem" />
        </DataTable>
    </div>
</template>

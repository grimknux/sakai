<script setup>
import { permsApi } from '@/api/admin';
import { FilterMatchMode } from '@primevue/core/api';
import { onMounted, ref } from 'vue';

const permissions = ref([]);
const filters = ref({ global: { value: null, matchMode: FilterMatchMode.CONTAINS } });

onMounted(async () => {
    const res = await permsApi.list();
    permissions.value = res.permissions || [];
});
</script>

<template>
    <div class="card">
        <DataTable :value="permissions" :paginator="true" :rows="10" :filters="filters" dataKey="id">
            <template #header>
                <div class="flex flex-wrap gap-2 items-center justify-between">
                    <h4 class="m-0">Permissions (View Only)</h4>
                    <IconField>
                        <InputIcon><i class="pi pi-search" /></InputIcon>
                        <InputText v-model="filters['global'].value" placeholder="Search..." />
                    </IconField>
                </div>
            </template>

            <Column field="name" header="Name" sortable style="min-width: 14rem" />
            <Column field="slug" header="Slug" sortable style="min-width: 16rem" />
            <Column field="module" header="Module" sortable style="min-width: 10rem" />
            <Column field="action" header="Action" sortable style="min-width: 10rem" />
        </DataTable>
    </div>
</template>

<script setup>
import { buildingsApi } from '@/api/admin';
import { usePermissions } from '@/composables/usePermissions';
import { FilterMatchMode } from '@primevue/core/api';
import { useToast } from 'primevue/usetoast';
import { onMounted, reactive, ref } from 'vue';

const toast = useToast();
const dt = ref();

const loading = ref(false);
const saving = ref(false);
const { can } = usePermissions();

const buildings = ref([]);
const buildingDialog = ref(false);
const deleteBuildingDialog = ref(false);
const selectedBuildings = ref(null);
const deleteBuildingsDialog = ref(false);

const building = ref({});

const errorMsg = ref('');
const errors = reactive({
    name: '',
    code: '',
    status: ''
});

const filters = ref({
    global: { value: null, matchMode: FilterMatchMode.CONTAINS }
});

function defaultBuilding() {
    return {
        name: '',
        code: '',
        status: 1
    };
}

async function load() {
    const res = await buildingsApi.list();
    buildings.value = res.buildings || [];
}

onMounted(load);

function clearErrors() {
    errors.name = '';
    errors.code = '';
    errors.status = '';
    errorMsg.value = '';
}

function validate() {
    clearErrors();

    let ok = true;

    if (!building.value.name?.trim()) {
        errors.name = 'Name is required.';
        ok = false;
    }

    if (!building.value.code?.trim()) {
        errors.code = 'Code is required.';
        ok = false;
    }

    if (![0, 1, '0', '1', true, false].includes(building.value.status)) {
        errors.status = 'Status is required.';
        ok = false;
    }

    return ok;
}

function openNew() {
    clearErrors();
    building.value = defaultBuilding();
    buildingDialog.value = true;
}

function hideDialog() {
    clearErrors();
    buildingDialog.value = false;
}

function editBuilding(row) {
    clearErrors();
    building.value = {
        ...defaultBuilding(),
        ...row,
        status: Number(row.status) === 1 ? 1 : 0
    };
    buildingDialog.value = true;
}

function confirmDeleteBuilding(row) {
    building.value = row;
    deleteBuildingDialog.value = true;
}

function confirmDeleteSelected() {
    deleteBuildingsDialog.value = true;
}

async function saveBuilding() {
    if (!validate()) return;

    loading.value = true;
    saving.value = true;

    try {
        const payload = {
            name: building.value.name,
            code: building.value.code,
            status: building.value.status ? 1 : 0
        };

        if (building.value.id) {
            await buildingsApi.update(building.value.id, payload);
            toast.add({ severity: 'success', summary: 'Successful', detail: 'Building Updated', life: 3000 });
        } else {
            await buildingsApi.create(payload);
            toast.add({ severity: 'success', summary: 'Successful', detail: 'Building Created', life: 3000 });
        }

        buildingDialog.value = false;
        building.value = defaultBuilding();
        await load();
    } catch (err) {
        clearErrors();

        const status = err?.response?.status;
        const data = err?.response?.data || {};

        if (status === 422) {
            const fields = data?.messages?.fields || {};
            errors.name = fields.name || '';
            errors.code = fields.code || '';
            errors.status = fields.status || '';
            errorMsg.value = data?.messages?.error || 'Validation failed.';
            return;
        }

        const msg = data?.message || data?.messages?.error || err?.message || 'Error';
        toast.add({ severity: 'error', summary: 'Error', detail: msg, life: 4000 });
    } finally {
        loading.value = false;
        saving.value = false;
    }
}

async function deleteBuilding() {
    try {
        await buildingsApi.remove(building.value.id);
        toast.add({ severity: 'success', summary: 'Successful', detail: 'Building Deleted', life: 3000 });
        deleteBuildingDialog.value = false;
        building.value = defaultBuilding();
        await load();
    } catch (e) {
        const msg = e?.response?.data?.message || e?.response?.data?.messages?.error || e?.response?.data?.messages?.message || e?.message || 'Error';
        toast.add({ severity: 'error', summary: 'Error', detail: msg, life: 4000 });
    }
}

async function deleteSelectedBuildings() {
    const items = selectedBuildings.value || [];
    try {
        for (const row of items) await buildingsApi.remove(row.id);
        toast.add({ severity: 'success', summary: 'Successful', detail: 'Buildings Deleted', life: 3000 });
    } catch (e) {
        toast.add({ severity: 'error', summary: 'Error', detail: 'Some deletes failed', life: 4000 });
    } finally {
        deleteBuildingsDialog.value = false;
        selectedBuildings.value = null;
        await load();
    }
}

function exportCSV() {
    dt.value.exportCSV();
}
</script>

<template>
    <div class="card">
        <Toolbar class="mb-6">
            <template #start>
                <Button label="New" icon="pi pi-plus" severity="secondary" class="mr-2" @click="openNew" v-if="can('buildings.create')" />
                <Button label="Delete" icon="pi pi-trash" severity="secondary" @click="confirmDeleteSelected" :disabled="!selectedBuildings || !selectedBuildings.length" v-if="can('buildings.delete')" />
            </template>
            <template #end>
                <Button label="Export" icon="pi pi-upload" severity="secondary" @click="exportCSV" />
            </template>
        </Toolbar>

        <DataTable
            ref="dt"
            v-model:selection="selectedBuildings"
            :value="buildings"
            dataKey="id"
            :paginator="true"
            :rows="10"
            :filters="filters"
            paginatorTemplate="FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink CurrentPageReport RowsPerPageDropdown"
            :rowsPerPageOptions="[5, 10, 25]"
            currentPageReportTemplate="Showing {first} to {last} of {totalRecords} buildings"
        >
            <template #header>
                <div class="flex flex-wrap gap-2 items-center justify-between">
                    <h4 class="m-0">Manage Buildings</h4>
                    <IconField>
                        <InputIcon><i class="pi pi-search" /></InputIcon>
                        <InputText v-model="filters['global'].value" placeholder="Search..." />
                    </IconField>
                </div>
            </template>

            <Column selectionMode="multiple" style="width: 3rem" :exportable="false" v-if="can('buildings.delete')" />
            <Column field="name" header="Name" sortable style="min-width: 18rem" />
            <Column field="code" header="Code" sortable style="min-width: 10rem" />
            <Column field="status" header="Status" sortable style="min-width: 10rem">
                <template #body="{ data }">
                    <Tag :value="Number(data.status) === 1 ? 'Active' : 'Inactive'" :severity="Number(data.status) === 1 ? 'success' : 'danger'" />
                </template>
            </Column>
            <Column :exportable="false" style="min-width: 10rem" v-if="can('buildings.update') || can('buildings.delete')">
                <template #body="{ data }">
                    <Button icon="pi pi-pencil" outlined rounded class="mr-2" @click="editBuilding(data)" v-if="can('buildings.update')" />
                    <Button icon="pi pi-trash" outlined rounded severity="danger" @click="confirmDeleteBuilding(data)" v-if="can('buildings.delete')" />
                </template>
            </Column>
        </DataTable>

        <Dialog v-model:visible="buildingDialog" :style="{ width: '450px' }" header="Building Details" :modal="true">
            <div class="flex flex-col gap-4">
                <Message v-if="errorMsg" severity="error">
                    {{ errorMsg }}
                </Message>

                <div>
                    <label class="block font-bold mb-2">Name</label>
                    <InputText v-model.trim="building.name" :disabled="loading" :invalid="!!errors.name" fluid />
                    <small v-if="errors.name" class="text-red-500">{{ errors.name }}</small>
                </div>

                <div>
                    <label class="block font-bold mb-2">Code</label>
                    <InputText v-model.trim="building.code" :disabled="loading" :invalid="!!errors.code" fluid />
                    <small v-if="errors.code" class="text-red-500">{{ errors.code }}</small>
                </div>

                <div>
                    <label class="block font-bold mb-2">Status</label>
                    <div class="flex items-center gap-2">
                        <Checkbox v-model="building.status" :binary="true" :trueValue="1" :falseValue="0" inputId="building_status" :disabled="loading" />
                        <label for="building_status" class="mb-0">
                            {{ Number(building.status) === 1 ? 'Active' : 'Inactive' }}
                        </label>
                    </div>
                    <small v-if="errors.status" class="text-red-500">{{ errors.status }}</small>
                </div>
            </div>

            <template #footer>
                <Button label="Cancel" icon="pi pi-times" text @click="hideDialog" />
                <Button label="Save" icon="pi pi-check" :loading="saving" @click="saveBuilding" />
            </template>
        </Dialog>

        <Dialog v-model:visible="deleteBuildingDialog" :style="{ width: '450px' }" header="Confirm" :modal="true">
            <div class="flex items-center gap-4">
                <i class="pi pi-exclamation-triangle text-3xl!" />
                <span v-if="building">
                    Are you sure you want to delete <b>{{ building.name }}</b
                    >?
                </span>
            </div>
            <template #footer>
                <Button label="No" icon="pi pi-times" text @click="deleteBuildingDialog = false" />
                <Button label="Yes" icon="pi pi-check" @click="deleteBuilding" />
            </template>
        </Dialog>

        <Dialog v-model:visible="deleteBuildingsDialog" :style="{ width: '450px' }" header="Confirm" :modal="true">
            <div class="flex items-center gap-4">
                <i class="pi pi-exclamation-triangle text-3xl!" />
                <span>Are you sure you want to delete the selected buildings?</span>
            </div>
            <template #footer>
                <Button label="No" icon="pi pi-times" text @click="deleteBuildingsDialog = false" />
                <Button label="Yes" icon="pi pi-check" text @click="deleteSelectedBuildings" />
            </template>
        </Dialog>
    </div>
</template>

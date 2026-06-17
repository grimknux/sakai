<script setup>
import { deviceTypesApi } from '@/api/assets';
import { usePermissions } from '@/composables/usePermissions';
import { FilterMatchMode } from '@primevue/core/api';
import { useToast } from 'primevue/usetoast';
import { onMounted, reactive, ref } from 'vue';

const toast = useToast();
const dt = ref();

const loading = ref(false);
const saving = ref(false);
const { can } = usePermissions();

const deviceTypes = ref([]);
const deviceTypeDialog = ref(false);
const deleteDeviceTypeDialog = ref(false);
const selectedDeviceTypes = ref(null);
const deleteDeviceTypesDialog = ref(false);

const deviceType = ref({});

const errorMsg = ref('');
const errors = reactive({
    name: '',
    description: ''
});

const filters = ref({
    global: { value: null, matchMode: FilterMatchMode.CONTAINS }
});

async function load() {
    const res = await deviceTypesApi.list();
    deviceTypes.value = res.device_types || [];
}

onMounted(load);

function clearErrors() {
    errors.name = '';
    errors.description = '';
    errorMsg.value = '';
}

function validate() {
    clearErrors();

    let ok = true;

    if (!deviceType.value.name?.trim()) {
        errors.name = 'Name is required.';
        ok = false;
    }

    return ok;
}

function openNew() {
    clearErrors();
    deviceType.value = {};
    deviceTypeDialog.value = true;
}

function hideDialog() {
    clearErrors();
    deviceTypeDialog.value = false;
}

function editDeviceType(row) {
    clearErrors();
    deviceType.value = { ...row };
    deviceTypeDialog.value = true;
}

function confirmDeleteDeviceType(row) {
    deviceType.value = row;
    deleteDeviceTypeDialog.value = true;
}

function confirmDeleteSelected() {
    deleteDeviceTypesDialog.value = true;
}

async function saveDeviceType() {
    if (!validate()) return;

    loading.value = true;
    saving.value = true;

    try {
        if (deviceType.value.id) {
            await deviceTypesApi.update(deviceType.value.id, {
                name: deviceType.value.name,
                description: deviceType.value.description ?? ''
            });
            toast.add({ severity: 'success', summary: 'Successful', detail: 'Device Type Updated', life: 3000 });
        } else {
            await deviceTypesApi.create({
                name: deviceType.value.name,
                description: deviceType.value.description ?? ''
            });
            toast.add({ severity: 'success', summary: 'Successful', detail: 'Device Type Created', life: 3000 });
        }

        deviceTypeDialog.value = false;
        deviceType.value = {};
        await load();
    } catch (err) {
        clearErrors();

        const status = err?.response?.status;
        const data = err?.response?.data || {};

        if (status === 422) {
            const fields = data?.messages?.fields || {};
            errors.name = fields.name || '';
            errors.description = fields.description || '';

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

async function deleteDeviceType() {
    try {
        await deviceTypesApi.remove(deviceType.value.id);
        toast.add({ severity: 'success', summary: 'Successful', detail: 'Device Type Deleted', life: 3000 });
        deleteDeviceTypeDialog.value = false;
        deviceType.value = {};
        await load();
    } catch (e) {
        const msg = e?.response?.data?.message || e?.response?.data?.messages?.error || e?.response?.data?.messages?.message || e?.message || 'Error';

        toast.add({ severity: 'error', summary: 'Error', detail: msg, life: 4000 });
    }
}

async function deleteSelectedDeviceTypes() {
    const items = selectedDeviceTypes.value || [];
    try {
        for (const row of items) await deviceTypesApi.remove(row.id);
        toast.add({ severity: 'success', summary: 'Successful', detail: 'Device Types Deleted', life: 3000 });
    } catch (e) {
        toast.add({ severity: 'error', summary: 'Error', detail: 'Some deletes failed', life: 4000 });
    } finally {
        deleteDeviceTypesDialog.value = false;
        selectedDeviceTypes.value = null;
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
                <Button label="New" icon="pi pi-plus" severity="secondary" class="mr-2" @click="openNew" v-if="can('device_types.create')" />
                <Button label="Delete" icon="pi pi-trash" severity="secondary" @click="confirmDeleteSelected" :disabled="!selectedDeviceTypes || !selectedDeviceTypes.length" v-if="can('device_types.delete')" />
            </template>
            <template #end>
                <Button label="Export" icon="pi pi-upload" severity="secondary" @click="exportCSV" />
            </template>
        </Toolbar>

        <DataTable
            ref="dt"
            v-model:selection="selectedDeviceTypes"
            :value="deviceTypes"
            dataKey="id"
            :paginator="true"
            :rows="10"
            :filters="filters"
            paginatorTemplate="FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink CurrentPageReport RowsPerPageDropdown"
            :rowsPerPageOptions="[5, 10, 25]"
            currentPageReportTemplate="Showing {first} to {last} of {totalRecords} device types"
        >
            <template #header>
                <div class="flex flex-wrap gap-2 items-center justify-between">
                    <h4 class="m-0">Manage Device Types</h4>
                    <IconField>
                        <InputIcon><i class="pi pi-search" /></InputIcon>
                        <InputText v-model="filters['global'].value" placeholder="Search..." />
                    </IconField>
                </div>
            </template>

            <Column selectionMode="multiple" style="width: 3rem" :exportable="false" v-if="can('device_types.delete')" />
            <Column field="name" header="Name" sortable style="min-width: 14rem" />
            <Column field="description" header="Description" style="min-width: 18rem" />
            <Column :exportable="false" style="min-width: 10rem" v-if="can('device_types.update') || can('device_types.delete')">
                <template #body="{ data }">
                    <Button icon="pi pi-pencil" outlined rounded class="mr-2" @click="editDeviceType(data)" v-if="can('device_types.update')" />
                    <Button icon="pi pi-trash" outlined rounded severity="danger" @click="confirmDeleteDeviceType(data)" v-if="can('device_types.delete')" />
                </template>
            </Column>
        </DataTable>

        <!-- Create/Edit Device Type -->
        <Dialog v-model:visible="deviceTypeDialog" :style="{ width: '450px' }" header="Device Type Details" :modal="true">
            <div class="flex flex-col gap-4">
                <Message v-if="errorMsg" severity="error">
                    {{ errorMsg }}
                </Message>

                <div>
                    <label class="block font-bold mb-2">Name</label>
                    <InputText v-model.trim="deviceType.name" :disabled="loading" :invalid="!!errors.name" fluid />
                    <small v-if="errors.name" class="text-red-500">{{ errors.name }}</small>
                </div>

                <div>
                    <label class="block font-bold mb-2">Description</label>
                    <Textarea v-model="deviceType.description" :disabled="loading" rows="3" fluid />
                    <small v-if="errors.description" class="text-red-500">{{ errors.description }}</small>
                </div>
            </div>

            <template #footer>
                <Button label="Cancel" icon="pi pi-times" text @click="hideDialog" />
                <Button label="Save" icon="pi pi-check" :loading="saving" @click="saveDeviceType" />
            </template>
        </Dialog>

        <!-- Delete single -->
        <Dialog v-model:visible="deleteDeviceTypeDialog" :style="{ width: '450px' }" header="Confirm" :modal="true">
            <div class="flex items-center gap-4">
                <i class="pi pi-exclamation-triangle text-3xl!" />
                <span v-if="deviceType">
                    Are you sure you want to delete <b>{{ deviceType.name }}</b
                    >?
                </span>
            </div>
            <template #footer>
                <Button label="No" icon="pi pi-times" text @click="deleteDeviceTypeDialog = false" />
                <Button label="Yes" icon="pi pi-check" @click="deleteDeviceType" />
            </template>
        </Dialog>

        <!-- Delete selected -->
        <Dialog v-model:visible="deleteDeviceTypesDialog" :style="{ width: '450px' }" header="Confirm" :modal="true">
            <div class="flex items-center gap-4">
                <i class="pi pi-exclamation-triangle text-3xl!" />
                <span>Are you sure you want to delete the selected device types?</span>
            </div>
            <template #footer>
                <Button label="No" icon="pi pi-times" text @click="deleteDeviceTypesDialog = false" />
                <Button label="Yes" icon="pi pi-check" text @click="deleteSelectedDeviceTypes" />
            </template>
        </Dialog>
    </div>
</template>

<script setup>
import { divisionsApi } from '@/api/admin';
import { usePermissions } from '@/composables/usePermissions';
import { FilterMatchMode } from '@primevue/core/api';
import { useToast } from 'primevue/usetoast';
import { onMounted, reactive, ref } from 'vue';

const toast = useToast();
const dt = ref();

const loading = ref(false);
const saving = ref(false);
const { can } = usePermissions();

const divisions = ref([]);
const divisionDialog = ref(false);
const deleteDivisionDialog = ref(false);
const selectedDivisions = ref(null);
const deleteDivisionsDialog = ref(false);

const division = ref({});

const errorMsg = ref('');
const errors = reactive({
    name: '',
    code: '',
    status: ''
});

const filters = ref({
    global: { value: null, matchMode: FilterMatchMode.CONTAINS }
});

function defaultDivision() {
    return {
        name: '',
        code: '',
        status: 1
    };
}

async function load() {
    const res = await divisionsApi.list();
    divisions.value = res.divisions || [];
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

    if (!division.value.name?.trim()) {
        errors.name = 'Name is required.';
        ok = false;
    }

    if (!division.value.code?.trim()) {
        errors.code = 'Code is required.';
        ok = false;
    }

    if (![0, 1, '0', '1', true, false].includes(division.value.status)) {
        errors.status = 'Status is required.';
        ok = false;
    }

    return ok;
}

function openNew() {
    clearErrors();
    division.value = defaultDivision();
    divisionDialog.value = true;
}

function hideDialog() {
    clearErrors();
    divisionDialog.value = false;
}

function editDivision(row) {
    clearErrors();
    division.value = {
        ...defaultDivision(),
        ...row,
        status: Number(row.status) === 1 ? 1 : 0
    };
    divisionDialog.value = true;
}

function confirmDeleteDivision(row) {
    division.value = row;
    deleteDivisionDialog.value = true;
}

function confirmDeleteSelected() {
    deleteDivisionsDialog.value = true;
}

async function saveDivision() {
    if (!validate()) return;

    loading.value = true;
    saving.value = true;

    try {
        const payload = {
            name: division.value.name,
            code: division.value.code,
            status: division.value.status ? 1 : 0
        };

        if (division.value.id) {
            await divisionsApi.update(division.value.id, payload);
            toast.add({ severity: 'success', summary: 'Successful', detail: 'Division Updated', life: 3000 });
        } else {
            await divisionsApi.create(payload);
            toast.add({ severity: 'success', summary: 'Successful', detail: 'Division Created', life: 3000 });
        }

        divisionDialog.value = false;
        division.value = defaultDivision();
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

async function deleteDivision() {
    try {
        await divisionsApi.remove(division.value.id);
        toast.add({ severity: 'success', summary: 'Successful', detail: 'Division Deleted', life: 3000 });
        deleteDivisionDialog.value = false;
        division.value = defaultDivision();
        await load();
    } catch (e) {
        const msg = e?.response?.data?.message || e?.response?.data?.messages?.error || e?.response?.data?.messages?.message || e?.message || 'Error';
        toast.add({ severity: 'error', summary: 'Error', detail: msg, life: 4000 });
    }
}

async function deleteSelectedDivisions() {
    const items = selectedDivisions.value || [];
    try {
        for (const row of items) await divisionsApi.remove(row.id);
        toast.add({ severity: 'success', summary: 'Successful', detail: 'Divisions Deleted', life: 3000 });
    } catch (e) {
        toast.add({ severity: 'error', summary: 'Error', detail: 'Some deletes failed', life: 4000 });
    } finally {
        deleteDivisionsDialog.value = false;
        selectedDivisions.value = null;
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
                <Button label="New" icon="pi pi-plus" severity="secondary" class="mr-2" @click="openNew" v-if="can('divisions.create')" />
                <Button label="Delete" icon="pi pi-trash" severity="secondary" @click="confirmDeleteSelected" :disabled="!selectedDivisions || !selectedDivisions.length" v-if="can('divisions.delete')" />
            </template>
            <template #end>
                <Button label="Export" icon="pi pi-upload" severity="secondary" @click="exportCSV" />
            </template>
        </Toolbar>

        <DataTable
            ref="dt"
            v-model:selection="selectedDivisions"
            :value="divisions"
            dataKey="id"
            :paginator="true"
            :rows="10"
            :filters="filters"
            paginatorTemplate="FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink CurrentPageReport RowsPerPageDropdown"
            :rowsPerPageOptions="[5, 10, 25]"
            currentPageReportTemplate="Showing {first} to {last} of {totalRecords} divisions"
        >
            <template #header>
                <div class="flex flex-wrap gap-2 items-center justify-between">
                    <h4 class="m-0">Manage Divisions</h4>
                    <IconField>
                        <InputIcon><i class="pi pi-search" /></InputIcon>
                        <InputText v-model="filters['global'].value" placeholder="Search..." />
                    </IconField>
                </div>
            </template>

            <Column selectionMode="multiple" style="width: 3rem" :exportable="false" v-if="can('divisions.delete')" />
            <Column field="name" header="Name" sortable style="min-width: 18rem" />
            <Column field="code" header="Code" sortable style="min-width: 10rem" />
            <Column field="status" header="Status" sortable style="min-width: 10rem">
                <template #body="{ data }">
                    <Tag :value="Number(data.status) === 1 ? 'Active' : 'Inactive'" :severity="Number(data.status) === 1 ? 'success' : 'danger'" />
                </template>
            </Column>
            <Column :exportable="false" style="min-width: 10rem" v-if="can('divisions.update') || can('divisions.delete')">
                <template #body="{ data }">
                    <Button icon="pi pi-pencil" outlined rounded class="mr-2" @click="editDivision(data)" v-if="can('divisions.update')" />
                    <Button icon="pi pi-trash" outlined rounded severity="danger" @click="confirmDeleteDivision(data)" v-if="can('divisions.delete')" />
                </template>
            </Column>
        </DataTable>

        <Dialog v-model:visible="divisionDialog" :style="{ width: '500px' }" header="Division Details" :modal="true">
            <div class="flex flex-col gap-4">
                <Message v-if="errorMsg" severity="error">
                    {{ errorMsg }}
                </Message>

                <div>
                    <label class="block font-bold mb-2">Name</label>
                    <InputText v-model.trim="division.name" :disabled="loading" :invalid="!!errors.name" fluid />
                    <small v-if="errors.name" class="text-red-500">{{ errors.name }}</small>
                </div>

                <div>
                    <label class="block font-bold mb-2">Code</label>
                    <InputText v-model.trim="division.code" :disabled="loading" :invalid="!!errors.code" fluid />
                    <small v-if="errors.code" class="text-red-500">{{ errors.code }}</small>
                </div>

                <div>
                    <label class="block font-bold mb-2">Status</label>
                    <div class="flex items-center gap-2">
                        <Checkbox v-model="division.status" :binary="true" :trueValue="1" :falseValue="0" inputId="division_status" :disabled="loading" />
                        <label for="division_status" class="mb-0">
                            {{ Number(division.status) === 1 ? 'Active' : 'Inactive' }}
                        </label>
                    </div>
                    <small v-if="errors.status" class="text-red-500">{{ errors.status }}</small>
                </div>
            </div>

            <template #footer>
                <Button label="Cancel" icon="pi pi-times" text @click="hideDialog" />
                <Button label="Save" icon="pi pi-check" :loading="saving" @click="saveDivision" />
            </template>
        </Dialog>

        <Dialog v-model:visible="deleteDivisionDialog" :style="{ width: '450px' }" header="Confirm" :modal="true">
            <div class="flex items-center gap-4">
                <i class="pi pi-exclamation-triangle text-3xl!" />
                <span v-if="division">
                    Are you sure you want to delete <b>{{ division.name }}</b
                    >?
                </span>
            </div>
            <template #footer>
                <Button label="No" icon="pi pi-times" text @click="deleteDivisionDialog = false" />
                <Button label="Yes" icon="pi pi-check" @click="deleteDivision" />
            </template>
        </Dialog>

        <Dialog v-model:visible="deleteDivisionsDialog" :style="{ width: '450px' }" header="Confirm" :modal="true">
            <div class="flex items-center gap-4">
                <i class="pi pi-exclamation-triangle text-3xl!" />
                <span>Are you sure you want to delete the selected divisions?</span>
            </div>
            <template #footer>
                <Button label="No" icon="pi pi-times" text @click="deleteDivisionsDialog = false" />
                <Button label="Yes" icon="pi pi-check" text @click="deleteSelectedDivisions" />
            </template>
        </Dialog>
    </div>
</template>

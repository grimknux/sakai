<script setup>
import { buildingsApi, divisionsApi, sectionsApi } from '@/api/admin';
import { usePermissions } from '@/composables/usePermissions';
import { FilterMatchMode } from '@primevue/core/api';
import { useToast } from 'primevue/usetoast';
import { onMounted, reactive, ref } from 'vue';

const toast = useToast();
const dt = ref();

const loading = ref(false);
const saving = ref(false);
const { can } = usePermissions();

const sections = ref([]);
const divisions = ref([]);
const buildings = ref([]);
const sectionDialog = ref(false);
const deleteSectionDialog = ref(false);
const selectedSections = ref(null);
const deleteSectionsDialog = ref(false);

const section = ref({});

const errorMsg = ref('');
const errors = reactive({
    name: '',
    code: '',
    division_code: '',
    bldg: '',
    status: ''
});

const filters = ref({
    global: { value: null, matchMode: FilterMatchMode.CONTAINS }
});

function defaultSection() {
    return {
        name: '',
        code: '',
        division_code: '',
        bldg: '',
        status: 1
    };
}

async function load() {
    const [sectionsRes, divisionsRes, buildingsRes] = await Promise.all([sectionsApi.list(), divisionsApi.dropdown(), buildingsApi.dropdown()]);

    sections.value = sectionsRes.sections || [];

    divisions.value = (divisionsRes.divisions || []).map((item) => ({
        label: `${item.name} (${item.code})`,
        value: item.code,
        name: item.name,
        code: item.code
    }));

    buildings.value = (buildingsRes.buildings || []).map((item) => ({
        label: `${item.name} (${item.code})`,
        value: item.code,
        name: item.name,
        code: item.code
    }));
}

onMounted(load);

function clearErrors() {
    errors.name = '';
    errors.code = '';
    errors.division_code = '';
    errors.bldg = '';
    errors.status = '';
    errorMsg.value = '';
}

function validate() {
    clearErrors();

    let ok = true;

    if (!section.value.name?.trim()) {
        errors.name = 'Name is required.';
        ok = false;
    }

    if (!section.value.code?.trim()) {
        errors.code = 'Code is required.';
        ok = false;
    }

    if (!section.value.division_code?.trim()) {
        errors.division_code = 'Division is required.';
        ok = false;
    }

    if (![0, 1, '0', '1', true, false].includes(section.value.status)) {
        errors.status = 'Status is required.';
        ok = false;
    }

    return ok;
}

function openNew() {
    clearErrors();
    section.value = defaultSection();
    sectionDialog.value = true;
}

function hideDialog() {
    clearErrors();
    sectionDialog.value = false;
}

function editSection(row) {
    clearErrors();
    section.value = {
        ...defaultSection(),
        ...row,
        division_code: row.division_code || '',
        bldg: row.bldg || '',
        status: Number(row.status) === 1 ? 1 : 0
    };
    sectionDialog.value = true;
}

function confirmDeleteSection(row) {
    section.value = row;
    deleteSectionDialog.value = true;
}

function confirmDeleteSelected() {
    deleteSectionsDialog.value = true;
}

async function saveSection() {
    if (!validate()) return;

    loading.value = true;
    saving.value = true;

    try {
        const payload = {
            name: section.value.name,
            code: section.value.code,
            division_code: section.value.division_code,
            bldg: section.value.bldg,
            status: section.value.status ? 1 : 0
        };

        if (section.value.id) {
            await sectionsApi.update(section.value.id, payload);
            toast.add({ severity: 'success', summary: 'Successful', detail: 'Section Updated', life: 3000 });
        } else {
            await sectionsApi.create(payload);
            toast.add({ severity: 'success', summary: 'Successful', detail: 'Section Created', life: 3000 });
        }

        sectionDialog.value = false;
        section.value = defaultSection();
        await load();
    } catch (err) {
        clearErrors();

        const status = err?.response?.status;
        const data = err?.response?.data || {};

        if (status === 422) {
            const fields = data?.messages?.fields || {};
            errors.name = fields.name || '';
            errors.code = fields.code || '';
            errors.division_code = fields.division_code || '';
            errors.bldg = fields.bldg || '';
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

async function deleteSection() {
    try {
        await sectionsApi.remove(section.value.id);
        toast.add({ severity: 'success', summary: 'Successful', detail: 'Section Deleted', life: 3000 });
        deleteSectionDialog.value = false;
        section.value = defaultSection();
        await load();
    } catch (e) {
        const msg = e?.response?.data?.message || e?.response?.data?.messages?.error || e?.response?.data?.messages?.message || e?.message || 'Error';

        toast.add({ severity: 'error', summary: 'Error', detail: msg, life: 4000 });
    }
}

async function deleteSelectedSections() {
    const items = selectedSections.value || [];
    try {
        for (const row of items) await sectionsApi.remove(row.id);
        toast.add({ severity: 'success', summary: 'Successful', detail: 'Sections Deleted', life: 3000 });
    } catch (e) {
        toast.add({ severity: 'error', summary: 'Error', detail: 'Some deletes failed', life: 4000 });
    } finally {
        deleteSectionsDialog.value = false;
        selectedSections.value = null;
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
                <Button label="New" icon="pi pi-plus" severity="secondary" class="mr-2" @click="openNew" v-if="can('sections.create')" />
                <Button label="Delete" icon="pi pi-trash" severity="secondary" @click="confirmDeleteSelected" :disabled="!selectedSections || !selectedSections.length" v-if="can('sections.delete')" />
            </template>
            <template #end>
                <Button label="Export" icon="pi pi-upload" severity="secondary" @click="exportCSV" />
            </template>
        </Toolbar>

        <DataTable
            ref="dt"
            v-model:selection="selectedSections"
            :value="sections"
            dataKey="id"
            :paginator="true"
            :rows="10"
            :filters="filters"
            paginatorTemplate="FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink CurrentPageReport RowsPerPageDropdown"
            :rowsPerPageOptions="[5, 10, 25]"
            currentPageReportTemplate="Showing {first} to {last} of {totalRecords} sections"
        >
            <template #header>
                <div class="flex flex-wrap gap-2 items-center justify-between">
                    <h4 class="m-0">Manage Sections</h4>
                    <IconField>
                        <InputIcon><i class="pi pi-search" /></InputIcon>
                        <InputText v-model="filters['global'].value" placeholder="Search..." />
                    </IconField>
                </div>
            </template>

            <Column selectionMode="multiple" style="width: 3rem" :exportable="false" v-if="can('sections.delete')" />
            <Column field="name" header="Name" sortable style="min-width: 18rem" />
            <Column field="code" header="Code" sortable style="min-width: 10rem" />
            <Column field="division_code" header="Division" sortable style="min-width: 10rem" />
            <Column field="bldg" header="Building" sortable style="min-width: 10rem" />
            <Column field="status" header="Status" sortable style="min-width: 10rem">
                <template #body="{ data }">
                    <Tag :value="Number(data.status) === 1 ? 'Active' : 'Inactive'" :severity="Number(data.status) === 1 ? 'success' : 'danger'" />
                </template>
            </Column>
            <Column :exportable="false" style="min-width: 10rem" v-if="can('sections.update') || can('sections.delete')">
                <template #body="{ data }">
                    <Button icon="pi pi-pencil" outlined rounded class="mr-2" @click="editSection(data)" v-if="can('sections.update')" />
                    <Button icon="pi pi-trash" outlined rounded severity="danger" @click="confirmDeleteSection(data)" v-if="can('sections.delete')" />
                </template>
            </Column>
        </DataTable>

        <Dialog v-model:visible="sectionDialog" :style="{ width: '500px' }" header="Section Details" :modal="true">
            <div class="flex flex-col gap-4">
                <Message v-if="errorMsg" severity="error">
                    {{ errorMsg }}
                </Message>

                <div>
                    <label class="block font-bold mb-2">Name</label>
                    <InputText v-model.trim="section.name" :disabled="loading" :invalid="!!errors.name" fluid />
                    <small v-if="errors.name" class="text-red-500">{{ errors.name }}</small>
                </div>

                <div>
                    <label class="block font-bold mb-2">Code</label>
                    <InputText v-model.trim="section.code" :disabled="loading" :invalid="!!errors.code" fluid />
                    <small v-if="errors.code" class="text-red-500">{{ errors.code }}</small>
                </div>

                <div>
                    <label class="block font-bold mb-2">Division</label>
                    <Select v-model="section.division_code" :options="divisions" optionLabel="label" optionValue="value" placeholder="Select division" :disabled="loading" :invalid="!!errors.division_code" fluid filter />
                    <small v-if="errors.division_code" class="text-red-500">{{ errors.division_code }}</small>
                </div>

                <div>
                    <label class="block font-bold mb-2">Building</label>
                    <Select v-model="section.bldg" :options="buildings" optionLabel="label" optionValue="value" placeholder="Select building" :disabled="loading" :invalid="!!errors.bldg" fluid filter />
                    <small v-if="errors.bldg" class="text-red-500">{{ errors.bldg }}</small>
                </div>

                <div>
                    <label class="block font-bold mb-2">Status</label>
                    <div class="flex items-center gap-2">
                        <Checkbox v-model="section.status" :binary="true" :trueValue="1" :falseValue="0" inputId="section_status" :disabled="loading" />
                        <label for="section_status" class="mb-0">
                            {{ Number(section.status) === 1 ? 'Active' : 'Inactive' }}
                        </label>
                    </div>
                    <small v-if="errors.status" class="text-red-500">{{ errors.status }}</small>
                </div>
            </div>

            <template #footer>
                <Button label="Cancel" icon="pi pi-times" text @click="hideDialog" />
                <Button label="Save" icon="pi pi-check" :loading="saving" @click="saveSection" />
            </template>
        </Dialog>

        <Dialog v-model:visible="deleteSectionDialog" :style="{ width: '450px' }" header="Confirm" :modal="true">
            <div class="flex items-center gap-4">
                <i class="pi pi-exclamation-triangle text-3xl!" />
                <span v-if="section">
                    Are you sure you want to delete <b>{{ section.name }}</b
                    >?
                </span>
            </div>
            <template #footer>
                <Button label="No" icon="pi pi-times" text @click="deleteSectionDialog = false" />
                <Button label="Yes" icon="pi pi-check" @click="deleteSection" />
            </template>
        </Dialog>

        <Dialog v-model:visible="deleteSectionsDialog" :style="{ width: '450px' }" header="Confirm" :modal="true">
            <div class="flex items-center gap-4">
                <i class="pi pi-exclamation-triangle text-3xl!" />
                <span>Are you sure you want to delete the selected sections?</span>
            </div>
            <template #footer>
                <Button label="No" icon="pi pi-times" text @click="deleteSectionsDialog = false" />
                <Button label="Yes" icon="pi pi-check" text @click="deleteSelectedSections" />
            </template>
        </Dialog>
    </div>
</template>

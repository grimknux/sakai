<script setup>
import { buildingsApi, divisionsApi, sectionsApi } from '@/api/admin';
import { inventoryApi, deviceTypesApi } from '@/api/assets';
import { pmsRecordsApi, pmsSchedulesApi } from '@/api/pms';
import { usePermissions } from '@/composables/usePermissions';
import { FilterMatchMode } from '@primevue/core/api';
import { useConfirm } from 'primevue/useconfirm';
import { useToast } from 'primevue/usetoast';
import { computed, onMounted, reactive, ref, watch } from 'vue';
import { useRouter } from 'vue-router';

const toast = useToast();
const confirm = useConfirm();
const router = useRouter();
const { can } = usePermissions();

const dt = ref();
const records = ref([]);
const selectedRecords = ref(null);
const editRecordDialog = ref(false);
const editAnswersDialog = ref(false);
const selectedScheduleId = ref(null);
const selectedDeviceType = ref(null);
const selectedPMSItems = ref(null);

const record = ref({});

const schedules = ref([]);
const scheduleFilterOptions = computed(() => [
    { id: 'all', label: 'All Schedules' },
    ...schedules.value.map((s) => ({
        id: s.eid, // use eid here
        label: `${s.year} - ${s.semester.charAt(0).toUpperCase() + s.semester.slice(1)} semester`
    }))
]);
const inventoryItems = ref([]);
const answerRows = ref([]);

const sections = ref([]);
const divisions = ref([]);
const buildings = ref([]);
const deviceTypeOptions = ref([]);

const savingRecord = ref(false);
const savingAnswers = ref(false);

const answerFindings = ref('');
const answerStatus = ref('');

const filters = ref({
    global: { value: null, matchMode: FilterMatchMode.CONTAINS },
    section_code: { value: null, matchMode: FilterMatchMode.IN },
    division_code: { value: null, matchMode: FilterMatchMode.IN },
    bldg: { value: null, matchMode: FilterMatchMode.IN }
});

function clearFilter() {
    filters.value = {
        global: { value: null, matchMode: FilterMatchMode.CONTAINS },
        section_code: { value: null, matchMode: FilterMatchMode.IN },
        division_code: { value: null, matchMode: FilterMatchMode.IN },
        bldg: { value: null, matchMode: FilterMatchMode.IN }
    };
}

const actionMenuRefs = ref({});

function setActionMenuRef(el, id) {
    if (el) {
        actionMenuRefs.value[id] = el;
    }
}

function toggleActionMenu(event, row) {
    actionMenuRefs.value[row.id]?.toggle(event);
}

function viewAttachment(row) {
    pmsRecordsApi.view(row.id);
}

function getActionItems(row) {
    const items = [];

    if (can('pms.record.update')) {
        items.push(
            {
                label: 'Edit Record',
                icon: 'pi pi-pencil',
                command: () => openEditRecord(row)
            },
            {
                label: 'Modify Answers',
                icon: 'pi pi-file-edit',
                command: () => openEditAnswers(row)
            },
            {
                label: 'Clear Answers',
                icon: 'pi pi-eraser',
                command: () => confirmClearAnswers(row)
            }
        );
    }

    if (can('pms.record.delete')) {
        items.push({
            label: 'Delete Record',
            icon: 'pi pi-trash',
            command: () => confirmDeleteRecord(row)
        });
    }

    items.push(
        { separator: true },
        {
            label: 'View PDF',
            icon: 'pi pi-file-pdf',
            command: () => viewAttachment(row)
        }
    );

    return items;
}

const editForm = ref({
    id: null,
    pms_schedule_id: null,
    inventory_id: null,
    conducted_date: '',
    section_code: null,
    division_code: null,
    bldg: null,
    end_user: '',
    current_user: '',
    remarks: ''
});

const editErrors = reactive({
    pms_schedule_id: '',
    inventory_id: '',
    conducted_date: '',
    section_code: '',
    division_code: '',
    bldg: '',
    end_user: '',
    current_user: '',
    remarks: ''
});

const answerErrors = ref([]);
const answerErrorMsg = ref('');

function getSectionByCode(code) {
    return sections.value.find((s) => s.code === code);
}

function getSectionBuildingCode(section) {
    return section?.bldg ?? null;
}

function onEditSectionChange(sectionCode) {
    const selectedSection = getSectionByCode(sectionCode);

    if (!selectedSection) {
        editForm.value.division_code = null;
        editForm.value.bldg = null;
        return;
    }

    editForm.value.division_code = selectedSection.division_code ?? null;
    editForm.value.bldg = getSectionBuildingCode(selectedSection);
}

function clearEditErrors() {
    editErrors.pms_schedule_id = '';
    editErrors.inventory_id = '';
    editErrors.conducted_date = '';
    editErrors.section_code = '';
    editErrors.division_code = '';
    editErrors.bldg = '';
    editErrors.end_user = '';
    editErrors.current_user = '';
    editErrors.remarks = '';
}

function clearAnswerErrors() {
    answerErrorMsg.value = '';
    answerErrors.value = answerRows.value.map(() => ({
        id: '',
        question_id: '',
        answer: '',
        remarks: ''
    }));
}

function onEditInventoryChange(inventoryId) {
    const selected = inventoryItems.value.find((i) => i.id === inventoryId);

    if (!selected) {
        editForm.value.end_user = '';
        editForm.value.current_user = '';
        editForm.value.section_code = null;
        editForm.value.division_code = null;
        editForm.value.bldg = null;
        return;
    }

    editForm.value.end_user = selected.end_user || '';
    editForm.value.current_user = selected.current_user || '';

    const inventorySectionCode = selected.section_code ?? null;
    const matchedSection = getSectionByCode(inventorySectionCode);

    editForm.value.section_code = inventorySectionCode;
    editForm.value.division_code = selected.division_code ?? matchedSection?.division_code ?? null;
    editForm.value.bldg = selected.bldg ?? getSectionBuildingCode(matchedSection) ?? null;
}

watch([selectedScheduleId, selectedDeviceType], async () => {
    await load();
});

watch(
    filters,
    () => {
        selectedPMSItems.value = [];
    },
    { deep: true }
);

async function load() {
    const params = {};

    if (selectedScheduleId.value) {
        params.selectedScheduleId = selectedScheduleId.value;
    }

    if (selectedDeviceType.value) {
        params.deviceTypeId = selectedDeviceType.value;
    }

    const res = await pmsRecordsApi.list(params);

    records.value = (res.records || []).map((r) => ({
        ...r,
        id: r.id,
        pms_schedule_id: r.pms_schedule_id,
        inventory_id: r.inventory_id,
        conducted_by: r.conducted_by ? r.conducted_by : null
    }));
}

async function loadLookups() {
    const [scheduleRes, inventoryRes, sectionsRes, divisionsRes, buildingsRes, deviceTypeRes] = await Promise.all([
        pmsSchedulesApi.dropdown(),
        inventoryApi.dropdown ? inventoryApi.dropdown() : inventoryApi.list(),
        sectionsApi.dropdown(),
        divisionsApi.dropdown(),
        buildingsApi.dropdown(),
        deviceTypesApi.dropdown()
    ]);

    schedules.value = (scheduleRes.schedules || []).map((s) => ({
        ...s,
        id: Number(s.id),
        label: `${s.year} - ${s.semester.charAt(0).toUpperCase() + s.semester.slice(1)} semester`
    }));

    inventoryItems.value = (inventoryRes.items || inventoryRes.inventory || []).map((i) => ({
        ...i,
        id: Number(i.id),
        label: `${i.property_number} - ${i.brand_name || ''} ${i.serial_num ? `(${i.serial_num})` : ''}`.trim()
    }));

    sections.value = (sectionsRes.sections || sectionsRes.items || []).map((s) => ({
        ...s,
        label: `${s.code}${s.name ? ` - ${s.name}` : ''}`
    }));

    divisions.value = (divisionsRes.divisions || divisionsRes.items || []).map((d) => ({
        ...d,
        label: `${d.code}${d.description ? ` - ${d.description}` : ''}`
    }));

    buildings.value = (buildingsRes.buildings || buildingsRes.items || []).map((b) => ({
        ...b,
        label: `${b.code}${b.description ? ` - ${b.description}` : ''}`
    }));

    deviceTypeOptions.value = [
        { value: null, label: 'All Device Types' },
        ...(deviceTypeRes.device_types || []).map((dt) => ({
            value: dt.id, // explicitly set value
            label: dt.name
        }))
    ];

    if (schedules.value.length > 0 && !selectedScheduleId.value) {
        selectedScheduleId.value = schedules.value[0].eid;
    }
}

onMounted(async () => {
    await loadLookups();
});

function openNew() {
    router.push({ name: 'pms.records.create' });
}

function confirmDeleteRecord(row) {
    confirm.require({
        message: `Are you sure you want to delete this PMS record?`,
        header: 'Confirm Delete',
        icon: 'pi pi-exclamation-triangle',
        group: 'delete',
        recordText: `${row.property_number || 'No property number'}${row.serial_num ? ` · ${row.serial_num}` : ''}`,

        acceptLabel: 'Yes',
        rejectLabel: 'No',

        acceptProps: {
            severity: 'danger',
            icon: 'pi pi-check'
        },
        rejectProps: {
            severity: 'secondary',
            icon: 'pi pi-times'
        },
        accept: async () => {
            try {
                await pmsRecordsApi.remove(row.id);
                toast.add({ severity: 'success', summary: 'Successful', detail: 'PMS record deleted', life: 3000 });
                await load(selectedScheduleId.value || null);
            } catch (e) {
                const msg = e?.response?.data?.message || e?.response?.data?.messages?.message || e?.message || 'Error';
                toast.add({ severity: 'error', summary: 'Error', detail: msg, life: 4000 });
            }
        }
    });
}

function confirmClearAnswers(row) {
    confirm.require({
        message: `Are you sure you want to clear all answers for this PMS record?`,
        header: 'Confirm Clear Answers',
        icon: 'pi pi-exclamation-triangle',
        group: 'clearAnswers',
        recordText: `${row.property_number || 'No property number'}${row.serial_num ? ` · ${row.serial_num}` : ''}`,

        acceptLabel: 'Yes',
        rejectLabel: 'No',

        acceptProps: {
            severity: 'warn',
            icon: 'pi pi-check'
        },
        rejectProps: {
            severity: 'secondary',
            icon: 'pi pi-times'
        },
        accept: async () => {
            try {
                await pmsRecordsApi.clearAnswers(row.id);
                toast.add({ severity: 'success', summary: 'Successful', detail: 'PMS answers cleared', life: 3000 });
                await load(selectedScheduleId.value || null);
            } catch (e) {
                const msg = e?.response?.data?.message || e?.response?.data?.messages?.error || e?.message || 'Error';
                toast.add({ severity: 'error', summary: 'Error', detail: msg, life: 4000 });
            }
        }
    });
}

function openEditRecord(row) {
    clearEditErrors();

    editForm.value = {
        id: row.id ? row.id : null,
        pms_schedule_id: row.pms_schedule_id ? Number(row.pms_schedule_id) : null,
        inventory_id: row.inventory_id ? Number(row.inventory_id) : null,
        conducted_date: row.conducted_date || '',
        section_code: row.section_code ?? null,
        division_code: row.division_code ?? null,
        bldg: row.bldg ?? null,
        end_user: row.end_user || '',
        current_user: row.current_user || '',
        remarks: row.remarks || ''
    };

    editRecordDialog.value = true;
}

async function openEditAnswers(row) {
    const res = await pmsRecordsApi.answers(row.id);

    record.value = { ...row };
    answerRows.value = (res.answers || []).map((a) => ({
        id: a.id,
        sort_order: a.sort_order,
        cnt: a.cnt,
        question_id: a.question_id,
        question_text_snapshot: a.question_text_snapshot || '',
        answer: a.answer || '',
        remarks: a.remarks || ''
    }));

    answerFindings.value = row.findings || '';
    answerStatus.value = row.status || '';

    clearAnswerErrors();
    editAnswersDialog.value = true;
}

async function saveRecordEdit() {
    clearEditErrors();
    savingRecord.value = true;

    try {
        const payload = {
            pms_schedule_id: editForm.value.pms_schedule_id,
            inventory_id: editForm.value.inventory_id,
            conducted_date: editForm.value.conducted_date,
            section_code: editForm.value.section_code || null,
            division_code: editForm.value.division_code || null,
            bldg: editForm.value.bldg || null,
            end_user: editForm.value.end_user || '',
            current_user: editForm.value.current_user || '',
            remarks: editForm.value.remarks || ''
        };

        await pmsRecordsApi.update(editForm.value.id, payload);

        toast.add({
            severity: 'success',
            summary: 'Successful',
            detail: 'PMS record updated',
            life: 3000
        });

        editRecordDialog.value = false;
        await load(selectedScheduleId.value || null);
    } catch (e) {
        const status = e?.response?.status;
        const data = e?.response?.data || {};

        if (status === 422) {
            const fields = data?.messages?.fields || {};
            editErrors.pms_schedule_id = fields.pms_schedule_id || '';
            editErrors.inventory_id = fields.inventory_id || '';
            editErrors.conducted_date = fields.conducted_date || '';
            editErrors.section_code = fields.section_code || '';
            editErrors.division_code = fields.division_code || '';
            editErrors.bldg = fields.bldg || '';
            editErrors.end_user = fields.end_user || '';
            editErrors.current_user = fields.current_user || '';
            editErrors.remarks = fields.remarks || '';
            return;
        }

        const msg = data?.message || data?.messages?.error || e?.message || 'Error';
        toast.add({ severity: 'error', summary: 'Error', detail: msg, life: 4000 });
    } finally {
        savingRecord.value = false;
    }
}

function validateAnswerEdit() {
    clearAnswerErrors();

    let ok = true;

    answerRows.value.forEach((row, index) => {
        if (!row.question_id) {
            answerErrors.value[index].question_id = 'Question is required.';
            ok = false;
        }

        if (!row.answer) {
            answerErrors.value[index].answer = 'Answer is required.';
            ok = false;
        }

        if (row.remarks && row.remarks.length > 1000) {
            answerErrors.value[index].remarks = 'Remarks must not exceed 1000 characters.';
            ok = false;
        }
    });

    return ok;
}

function applyYesToAllAnswers() {
    answerRows.value.forEach((row) => {
        row.answer = 'yes';
    });
}

function applyNoToAllAnswers() {
    answerRows.value.forEach((row) => {
        row.answer = 'no';
    });
}

function clearAllAnswerSelections() {
    answerRows.value.forEach((row) => {
        row.answer = '';
    });
}

function bulkPrintPMS() {
    const items = selectedPMSItems.value || [];
    if (!items.length) return;

    const ids = items.map((item) => item.id);

    pmsRecordsApi.view_bulk({
        ids: ids.join(',') // "1,5,9"
    });
}

async function saveAnswerEdit() {
    clearAnswerErrors();

    if (!validateAnswerEdit()) return;

    savingAnswers.value = true;

    try {
        const payload = {
            findings: answerFindings.value || '',
            status: answerStatus.value || '',
            answers: answerRows.value.map((a) => ({
                id: a.id,
                question_id: a.question_id,
                answer: a.answer,
                remarks: a.remarks || ''
            }))
        };

        await pmsRecordsApi.updateAnswers(record.value.id, payload);

        toast.add({
            severity: 'success',
            summary: 'Successful',
            detail: 'PMS answers updated',
            life: 3000
        });

        editAnswersDialog.value = false;
        await load(selectedScheduleId.value || null);
    } catch (e) {
        const status = e?.response?.status;
        const data = e?.response?.data || {};

        if (status === 422) {
            const fields = data?.messages?.fields || {};
            answerErrorMsg.value = data?.messages?.error || 'Validation failed.';

            Object.entries(fields).forEach(([key, value]) => {
                const match = key.match(/^answers\.(\d+)\.(.+)$/);
                if (match) {
                    const index = Number(match[1]);
                    const field = match[2];

                    if (!answerErrors.value[index]) {
                        answerErrors.value[index] = {
                            id: '',
                            question_id: '',
                            answer: '',
                            remarks: ''
                        };
                    }

                    if (field in answerErrors.value[index]) {
                        answerErrors.value[index][field] = value;
                    }
                }
            });

            return;
        }

        const msg = data?.message || data?.messages?.error || e?.message || 'Error';
        toast.add({ severity: 'error', summary: 'Error', detail: msg, life: 4000 });
    } finally {
        savingAnswers.value = false;
    }
}

function exportCSV() {
    dt.value.exportCSV();
}
</script>

<template>
    <div class="card">
        <ConfirmDialog group="delete">
            <template #message="{ message }">
                <div>
                    <p class="mb-2">{{ message.message }}</p>
                    <p v-if="message.recordText" class="text-sm text-surface-500">
                        {{ message.recordText }}
                    </p>
                </div>
            </template>
        </ConfirmDialog>

        <ConfirmDialog group="clearAnswers">
            <template #message="{ message }">
                <div>
                    <p class="mb-2">{{ message.message }}</p>
                    <p v-if="message.recordText" class="text-sm text-surface-500">
                        {{ message.recordText }}
                    </p>
                </div>
            </template>
        </ConfirmDialog>

        <h4 class="m-0">My PMS Records</h4>
        <Toolbar class="mb-6">
            <template #start>
                <Button label="New PMS Record" icon="pi pi-plus" severity="secondary" class="mr-2" @click="openNew" v-if="can('pms.record.create')" />
                <Button label="Print PMS" icon="pi pi-file-pdf" severity="info" class="mr-2" @click="bulkPrintPMS" :disabled="!selectedPMSItems || !selectedPMSItems.length" />
            </template>

            <template #end>
                <Button label="Export" icon="pi pi-upload" severity="secondary" @click="exportCSV" />
            </template>
        </Toolbar>

        <DataTable
            ref="dt"
            v-model:filters="filters"
            v-model:selection="selectedPMSItems"
            :value="records"
            dataKey="id"
            :paginator="true"
            :rows="10"
            filterDisplay="menu"
            :globalFilterFields="['device_type', 'semester', 'property_number', 'serial_num', 'brand_name', 'section_code', 'division_code', 'bldg', 'remarks', 'findings', 'status', 'end_user', 'current_user', 'conducted_date']"
            paginatorTemplate="FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink CurrentPageReport RowsPerPageDropdown"
            :rowsPerPageOptions="[5, 10, 25, 100, 300, 500, 1000]"
            currentPageReportTemplate="Showing {first} to {last} of {totalRecords} items"
            showGridlines
        >
            <template #header>
                <div class="flex flex-wrap gap-2 items-center justify-between">
                    <div class="flex items-center gap-2">
                        <Button type="button" icon="pi pi-filter-slash" label="Clear" outlined @click="clearFilter()" />
                    </div>

                    <div class="flex flex-wrap gap-2 items-center">
                        <Select v-model="selectedScheduleId" :options="scheduleFilterOptions" optionLabel="label" optionValue="id" placeholder="Select PMS Schedule" class="w-full md:w-80" filter />

                        <Select v-model="selectedDeviceType" :options="deviceTypeOptions" optionLabel="label" optionValue="value" placeholder="Select Device Type" class="w-full md:w-80" filter />

                        <IconField>
                            <InputIcon><i class="pi pi-search" /></InputIcon>
                            <InputText v-model="filters['global'].value" placeholder="Search..." />
                        </IconField>
                    </div>
                </div>
            </template>

            <Column selectionMode="multiple" style="width: 3rem" :exportable="false" />
            <Column :exportable="false" header="Action" style="min-width: 10rem">
                <template #body="{ data }">
                    <div>
                        <Button icon="pi pi-ellipsis-v" label="Actions" outlined @click="toggleActionMenu($event, data)" />
                        <Menu :ref="(el) => setActionMenuRef(el, data.id)" :model="getActionItems(data)" popup />
                    </div>
                </template>
            </Column>
            <Column field="device_type" header="Type" sortable style="min-width: 10rem" />
            <Column field="property_number" header="Property Number" style="min-width: 14rem" />
            <Column field="serial_num" header="Serial Number" style="min-width: 14rem" />

            <Column field="section_code" header="Section" filterField="section_code" :showFilterMatchModes="false" :showFilterOperator="false" :showAddButton="false" :filterMenuStyle="{ width: '18rem' }" style="min-width: 10rem" sortable>
                <template #body="{ data }">
                    {{ data.section_code }}
                </template>
                <template #filter="{ filterModel, filterCallback }">
                    <MultiSelect v-model="filterModel.value" :options="sections" optionLabel="label" optionValue="code" placeholder="Any" class="w-full" @change="filterCallback()" filter />
                </template>
            </Column>

            <Column field="division_code" header="Division" filterField="division_code" :showFilterMatchModes="false" :showFilterOperator="false" :showAddButton="false" :filterMenuStyle="{ width: '18rem' }" style="min-width: 10rem" sortable>
                <template #body="{ data }">
                    {{ data.division_code }}
                </template>
                <template #filter="{ filterModel, filterCallback }">
                    <MultiSelect v-model="filterModel.value" :options="divisions" optionLabel="label" optionValue="code" placeholder="Any" class="w-full" @change="filterCallback()" filter />
                </template>
            </Column>

            <Column field="bldg" header="Building" filterField="bldg" :showFilterMatchModes="false" :showFilterOperator="false" :showAddButton="false" :filterMenuStyle="{ width: '18rem' }" style="min-width: 8rem" sortable>
                <template #body="{ data }">
                    {{ data.bldg }}
                </template>
                <template #filter="{ filterModel, filterCallback }">
                    <MultiSelect v-model="filterModel.value" :options="buildings" optionLabel="label" optionValue="code" placeholder="Any" class="w-full" @change="filterCallback()" filter />
                </template>
            </Column>
            <Column field="remarks" header="Remarks" style="min-width: 16rem" />
            <Column field="semester" header="Semester" sortable style="min-width: 8rem" />
            <Column field="end_user" header="End User" sortable style="min-width: 12rem" />
            <Column field="current_user" header="End User" sortable style="min-width: 12rem" />
            <Column field="conducted_date" header="Conducted At" sortable style="min-width: 12rem" />
            <Column field="conducted_by_name" header="Conducted By" style="min-width: 12rem" />
        </DataTable>

        <Dialog v-model:visible="editRecordDialog" :style="{ width: '700px' }" header="Edit PMS Record" :modal="true">
            <div class="flex flex-col gap-4">
                <div>
                    <label class="block font-bold mb-2">Conducted Date</label>
                    <InputText v-model="editForm.conducted_date" type="date" :invalid="!!editErrors.conducted_date" fluid />
                    <small v-if="editErrors.conducted_date" class="text-red-500">{{ editErrors.conducted_date }}</small>
                </div>
                <div>
                    <label class="block font-bold mb-2">PMS Schedule</label>
                    <Select v-model="editForm.pms_schedule_id" :options="schedules" optionLabel="label" optionValue="id" :invalid="!!editErrors.pms_schedule_id" fluid />
                    <small v-if="editErrors.pms_schedule_id" class="text-red-500">{{ editErrors.pms_schedule_id }}</small>
                </div>

                <div>
                    <label class="block font-bold mb-2">Inventory</label>
                    <Select v-model="editForm.inventory_id" :options="inventoryItems" optionLabel="label" optionValue="id" :invalid="!!editErrors.inventory_id" filter fluid @change="onEditInventoryChange($event.value)" />
                    <small v-if="editErrors.inventory_id" class="text-red-500">{{ editErrors.inventory_id }}</small>
                </div>

                <div class="grid grid-cols-12 gap-4">
                    <div class="col-span-12 md:col-span-4">
                        <label class="block font-bold mb-2">Section</label>
                        <Select v-model="editForm.section_code" :options="sections" optionLabel="label" optionValue="code" :invalid="!!editErrors.section_code" filter fluid @change="onEditSectionChange($event.value)" />
                        <small v-if="editErrors.section_code" class="text-red-500">{{ editErrors.section_code }}</small>
                    </div>

                    <div class="col-span-12 md:col-span-4">
                        <label class="block font-bold mb-2">Division</label>
                        <Select v-model="editForm.division_code" :options="divisions" optionLabel="label" optionValue="code" :invalid="!!editErrors.division_code" filter fluid />
                        <small v-if="editErrors.division_code" class="text-red-500">{{ editErrors.division_code }}</small>
                    </div>

                    <div class="col-span-12 md:col-span-4">
                        <label class="block font-bold mb-2">Building</label>
                        <Select v-model="editForm.bldg" :options="buildings" optionLabel="label" optionValue="code" :invalid="!!editErrors.bldg" filter fluid />
                        <small v-if="editErrors.bldg" class="text-red-500">{{ editErrors.bldg }}</small>
                    </div>
                </div>
                <div class="grid grid-cols-12 gap-4">
                    <div class="col-span-12 md:col-span-6">
                        <label class="block font-bold mb-2">End User</label>
                        <InputText v-model="editForm.end_user" fluid />
                    </div>

                    <div class="col-span-12 md:col-span-6">
                        <label class="block font-bold mb-2">Current User</label>
                        <InputText v-model="editForm.current_user" fluid />
                    </div>
                </div>

                <div>
                    <label class="block font-bold mb-2">Remarks</label>
                    <Textarea v-model="editForm.remarks" rows="3" fluid />
                </div>
            </div>

            <template #footer>
                <Button label="Cancel" icon="pi pi-times" text @click="editRecordDialog = false" />
                <Button label="Save" icon="pi pi-check" :loading="savingRecord" @click="saveRecordEdit" />
            </template>
        </Dialog>

        <Dialog v-model:visible="editAnswersDialog" header="Modify PMS Answers" :modal="true" :style="{ width: '95vw', maxWidth: '1000px' }" :breakpoints="{ '960px': '95vw', '640px': '100vw' }">
            <div class="flex flex-col gap-4">
                <div v-if="answerErrorMsg" class="text-red-500 text-sm">
                    {{ answerErrorMsg }}
                </div>

                <div class="flex flex-wrap items-center justify-between gap-2">
                    <h5 class="m-0">Answers</h5>

                    <div class="flex flex-wrap gap-2">
                        <Button label="Yes All" icon="pi pi-check" severity="success" size="small" @click="applyYesToAllAnswers" />
                        <Button label="No All" icon="pi pi-times" severity="danger" size="small" @click="applyNoToAllAnswers" />
                        <Button label="Clear" icon="pi pi-eraser" severity="secondary" size="small" outlined @click="clearAllAnswerSelections" />
                    </div>
                </div>

                <div class="hidden md:block">
                    <DataTable :value="answerRows" responsiveLayout="scroll" class="p-datatable-sm">
                        <Column header="" style="min-width: 3rem">
                            <template #body="{ data }">
                                <div class="flex flex-col gap-1">
                                    <div>{{ data.cnt }}</div>
                                </div>
                            </template>
                        </Column>

                        <Column header="Question" style="min-width: 22rem">
                            <template #body="{ data, index }">
                                <div class="flex flex-col gap-1">
                                    <div>{{ data.question_text_snapshot }}</div>
                                    <small v-if="answerErrors[index]?.question_id" class="text-red-500">
                                        {{ answerErrors[index].question_id }}
                                    </small>
                                </div>
                            </template>
                        </Column>

                        <Column header="Yes / No" style="min-width: 14rem">
                            <template #body="{ index }">
                                <div class="flex flex-col gap-1">
                                    <div class="flex items-center gap-4 flex-wrap">
                                        <div class="flex items-center gap-2">
                                            <RadioButton v-model="answerRows[index].answer" :inputId="`edit_answer_yes_${index}`" :name="`edit_answer_${index}`" value="yes" />
                                            <label :for="`edit_answer_yes_${index}`">Yes</label>
                                        </div>

                                        <div class="flex items-center gap-2">
                                            <RadioButton v-model="answerRows[index].answer" :inputId="`edit_answer_no_${index}`" :name="`edit_answer_${index}`" value="no" />
                                            <label :for="`edit_answer_no_${index}`">No</label>
                                        </div>
                                    </div>

                                    <small v-if="answerErrors[index]?.answer" class="text-red-500">
                                        {{ answerErrors[index].answer }}
                                    </small>
                                </div>
                            </template>
                        </Column>

                        <Column header="Remarks" style="min-width: 20rem">
                            <template #body="{ index }">
                                <div class="flex flex-col gap-1">
                                    <Textarea v-model="answerRows[index].remarks" rows="1" :invalid="!!answerErrors[index]?.remarks" fluid />
                                    <small v-if="answerErrors[index]?.remarks" class="text-red-500">
                                        {{ answerErrors[index].remarks }}
                                    </small>
                                </div>
                            </template>
                        </Column>
                    </DataTable>
                </div>

                <div class="block md:hidden">
                    <div v-for="(row, index) in answerRows" :key="row.id || index" class="border rounded-lg p-4 mb-3">
                        <div class="font-semibold mb-2">{{ index + 1 }}. {{ row.question_text_snapshot }}</div>

                        <small v-if="answerErrors[index]?.question_id" class="text-red-500 block mb-2">
                            {{ answerErrors[index].question_id }}
                        </small>

                        <div class="flex flex-wrap gap-4 mb-3">
                            <div class="flex items-center gap-2">
                                <RadioButton v-model="answerRows[index].answer" :inputId="`m_edit_answer_yes_${index}`" :name="`m_edit_answer_${index}`" value="yes" />
                                <label :for="`m_edit_answer_yes_${index}`">Yes</label>
                            </div>

                            <div class="flex items-center gap-2">
                                <RadioButton v-model="answerRows[index].answer" :inputId="`m_edit_answer_no_${index}`" :name="`m_edit_answer_${index}`" value="no" />
                                <label :for="`m_edit_answer_no_${index}`">No</label>
                            </div>
                        </div>

                        <small v-if="answerErrors[index]?.answer" class="text-red-500 block mb-2">
                            {{ answerErrors[index].answer }}
                        </small>

                        <label class="block font-medium mb-2">Remarks</label>
                        <Textarea v-model="answerRows[index].remarks" rows="1" :invalid="!!answerErrors[index]?.remarks" fluid />
                        <small v-if="answerErrors[index]?.remarks" class="text-red-500 block mt-1">
                            {{ answerErrors[index].remarks }}
                        </small>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-4">
                    <div>
                        <label class="block font-bold mb-2">Findings</label>
                        <Textarea v-model="answerFindings" rows="4" fluid />
                    </div>

                    <div>
                        <label class="block font-bold mb-2">Status</label>
                        <Textarea v-model="answerStatus" rows="4" fluid />
                    </div>
                </div>
            </div>

            <template #footer>
                <Button label="Cancel" icon="pi pi-times" text @click="editAnswersDialog = false" />
                <Button label="Save" icon="pi pi-check" :loading="savingAnswers" @click="saveAnswerEdit" />
            </template>
        </Dialog>
    </div>
</template>

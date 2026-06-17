<script setup>
import { buildingsApi, divisionsApi, sectionsApi } from '@/api/admin'; // adjust path if needed
import { inventoryApi } from '@/api/assets';
import { pmsRecordsApi, pmsSchedulesApi } from '@/api/pms';
import { usePermissions } from '@/composables/usePermissions';
import { FilterMatchMode } from '@primevue/core/api';
import { useToast } from 'primevue/usetoast';
import { computed, onBeforeUnmount, onMounted, reactive, ref, watch } from 'vue';
import { useRouter } from 'vue-router';

const toast = useToast();
const router = useRouter();
const { can } = usePermissions();

const dt = ref();
const records = ref([]);
const selectedRecords = ref(null);
const deleteRecordDialog = ref(false);
const clearAnswersDialog = ref(false);
const editRecordDialog = ref(false);
const editAnswersDialog = ref(false);
const selectedScheduleId = ref(null);

const record = ref({});

const schedules = ref([]);
const scheduleFilterOptions = computed(() => [{ id: 'all', label: 'All Schedules' }, ...schedules.value]);
const inventoryItems = ref([]);
const answerRows = ref([]);

const sections = ref([]);
const divisions = ref([]);
const buildings = ref([]);

const savingRecord = ref(false);
const savingAnswers = ref(false);

/**
 * Responsive / collapsible rows
 */
const expandedRows = ref({});
const isSmallScreen = ref(false);

function handleResize() {
    isSmallScreen.value = window.innerWidth < 1024; // lg breakpoint
}

function toggleRowExpansion(row) {
    if (!isSmallScreen.value) return;

    const newExpanded = { ...expandedRows.value };

    if (newExpanded[row.id]) {
        delete newExpanded[row.id];
    } else {
        newExpanded[row.id] = true;
    }

    expandedRows.value = newExpanded;
}

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

const editForm = ref({
    id: null,
    pms_schedule_id: null,
    inventory_id: null,
    section_code: null,
    division_code: null,
    bldg: null,
    end_user: '',
    remarks: '',
    findings: '',
    recommendation: ''
});

const editErrors = reactive({
    pms_schedule_id: '',
    inventory_id: '',
    section_code: '',
    division_code: '',
    bldg: '',
    end_user: '',
    remarks: '',
    findings: '',
    recommendation: ''
});

const answerErrors = ref([]);
const answerErrorMsg = ref('');

const sectionChangeSource = ref(null); // null | 'inventory' | 'manual'

function getSectionByCode(code) {
    return sections.value.find((s) => s.code === code);
}

function getSectionBuildingCode(section) {
    return section?.bldg ?? null;
}

function clearEditErrors() {
    editErrors.pms_schedule_id = '';
    editErrors.inventory_id = '';
    editErrors.section_code = '';
    editErrors.division_code = '';
    editErrors.bldg = '';
    editErrors.end_user = '';
    editErrors.remarks = '';
    editErrors.findings = '';
    editErrors.recommendation = '';
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

watch(
    () => editForm.value.inventory_id,
    (inventoryId) => {
        const selected = inventoryItems.value.find((i) => i.id === inventoryId);

        if (!selected) {
            editForm.value.end_user = '';
            editForm.value.section_code = null;
            editForm.value.division_code = null;
            editForm.value.bldg = null;
            return;
        }

        editForm.value.end_user = selected.end_user || '';

        const inventorySectionCode = selected.section_code ?? null;
        const matchedSection = getSectionByCode(inventorySectionCode);

        sectionChangeSource.value = 'inventory';

        editForm.value.section_code = inventorySectionCode;
        editForm.value.division_code = selected.division_code ?? matchedSection?.division_code ?? null;
        editForm.value.bldg = selected.bldg ?? getSectionBuildingCode(matchedSection) ?? null;

        sectionChangeSource.value = null;
    }
);

watch(
    () => editForm.value.section_code,
    (sectionCode) => {
        if (sectionChangeSource.value === 'inventory') return;

        const selectedSection = getSectionByCode(sectionCode);

        if (!selectedSection) {
            editForm.value.division_code = null;
            editForm.value.bldg = null;
            return;
        }

        sectionChangeSource.value = 'manual';

        editForm.value.division_code = selectedSection.division_code ?? null;
        editForm.value.bldg = getSectionBuildingCode(selectedSection);

        sectionChangeSource.value = null;
    }
);

watch(selectedScheduleId, async (scheduleId) => {
    await load(scheduleId);
});

async function load(scheduleId = null) {
    const effectiveScheduleId = scheduleId === 'all' ? null : scheduleId;
    const res = await pmsRecordsApi.list(effectiveScheduleId);

    records.value = (res.records || []).map((r) => ({
        ...r,
        id: r.id,
        pms_schedule_id: r.pms_schedule_id,
        inventory_id: r.inventory_id,
        conducted_by: r.conducted_by ? r.conducted_by : null
    }));
}

async function loadLookups() {
    const [scheduleRes, inventoryRes, sectionsRes, divisionsRes, buildingsRes] = await Promise.all([
        pmsSchedulesApi.dropdown(),
        inventoryApi.dropdown ? inventoryApi.dropdown() : inventoryApi.list(),
        sectionsApi.dropdown(),
        divisionsApi.dropdown(),
        buildingsApi.dropdown()
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
        label: `${s.code}${s.description ? ` - ${s.description}` : ''}`
    }));

    divisions.value = (divisionsRes.divisions || divisionsRes.items || []).map((d) => ({
        ...d,
        label: `${d.code}${d.description ? ` - ${d.description}` : ''}`
    }));

    buildings.value = (buildingsRes.buildings || buildingsRes.items || []).map((b) => ({
        ...b,
        label: `${b.code}${b.description ? ` - ${b.description}` : ''}`
    }));

    if (schedules.value.length > 0 && !selectedScheduleId.value) {
        selectedScheduleId.value = schedules.value[0].id;
    }
}

onMounted(async () => {
    await loadLookups();
    handleResize();
    window.addEventListener('resize', handleResize);
});

onBeforeUnmount(() => {
    window.removeEventListener('resize', handleResize);
});

function openNew() {
    router.push({ name: 'pms.records.create' });
}

function confirmDeleteRecord(row) {
    record.value = { ...row };
    deleteRecordDialog.value = true;
}

function confirmClearAnswers(row) {
    record.value = { ...row };
    clearAnswersDialog.value = true;
}

function openEditRecord(row) {
    clearEditErrors();

    editForm.value = {
        id: row.id ? row.id : null,
        pms_schedule_id: row.pms_schedule_id ? Number(row.pms_schedule_id) : null,
        inventory_id: row.inventory_id ? Number(row.inventory_id) : null,
        section_code: row.section_code ?? null,
        division_code: row.division_code ?? null,
        bldg: row.bldg ?? null,
        end_user: row.end_user || '',
        remarks: row.remarks || '',
        findings: row.findings || '',
        recommendation: row.recommendation || ''
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
            section_code: editForm.value.section_code || null,
            division_code: editForm.value.division_code || null,
            bldg: editForm.value.bldg || null,
            end_user: editForm.value.end_user || '',
            remarks: editForm.value.remarks || '',
            findings: editForm.value.findings || '',
            recommendation: editForm.value.recommendation || ''
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
            editErrors.section_code = fields.section_code || '';
            editErrors.division_code = fields.division_code || '';
            editErrors.bldg = fields.bldg || '';
            editErrors.end_user = fields.end_user || '';
            editErrors.remarks = fields.remarks || '';
            editErrors.findings = fields.findings || '';
            editErrors.recommendation = fields.recommendation || '';
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

async function saveAnswerEdit() {
    clearAnswerErrors();

    if (!validateAnswerEdit()) return;

    savingAnswers.value = true;

    try {
        const payload = {
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

async function deleteRecord() {
    try {
        await pmsRecordsApi.remove(record.value.id);
        toast.add({ severity: 'success', summary: 'Successful', detail: 'PMS record deleted', life: 3000 });
        deleteRecordDialog.value = false;
        record.value = {};
        await load(selectedScheduleId.value || null);
    } catch (e) {
        const msg = e?.response?.data?.message || e?.response?.data?.messages?.message || e?.message || 'Error';
        toast.add({ severity: 'error', summary: 'Error', detail: msg, life: 4000 });
    }
}

async function clearAnswers() {
    try {
        await pmsRecordsApi.clearAnswers(record.value.id);
        toast.add({ severity: 'success', summary: 'Successful', detail: 'PMS answers cleared', life: 3000 });
        clearAnswersDialog.value = false;
        await load(selectedScheduleId.value || null);
    } catch (e) {
        const msg = e?.response?.data?.message || e?.response?.data?.messages?.error || e?.message || 'Error';
        toast.add({ severity: 'error', summary: 'Error', detail: msg, life: 4000 });
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
                <Button label="New PMS Record" icon="pi pi-plus" severity="secondary" class="mr-2" @click="openNew" v-if="can('pms.record.create')" />
            </template>

            <template #end>
                <Button label="Export" icon="pi pi-upload" severity="secondary" @click="exportCSV" />
            </template>
        </Toolbar>

        <DataTable
            ref="dt"
            v-model:filters="filters"
            v-model:expandedRows="expandedRows"
            :value="records"
            dataKey="id"
            :paginator="true"
            :rows="10"
            filterDisplay="menu"
            :globalFilterFields="['semester', 'property_number', 'serial_num', 'brand_name', 'section_code', 'division_code', 'bldg', 'remarks', 'findings', 'recommendation', 'end_user', 'created_at']"
            paginatorTemplate="FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink CurrentPageReport RowsPerPageDropdown"
            :rowsPerPageOptions="[5, 10, 25]"
            currentPageReportTemplate="Showing {first} to {last} of {totalRecords} items"
            showGridlines
        >
            <template #header>
                <div class="flex flex-wrap gap-2 items-center justify-between">
                    <h4 class="m-0">My PMS Records</h4>

                    <div class="flex flex-wrap gap-2 items-center">
                        <Button type="button" icon="pi pi-filter-slash" label="Clear" outlined @click="clearFilter()" />
                        <Select v-model="selectedScheduleId" :options="scheduleFilterOptions" optionLabel="label" optionValue="id" placeholder="Select PMS Schedule" class="w-full md:w-80" filter />

                        <IconField>
                            <InputIcon><i class="pi pi-search" /></InputIcon>
                            <InputText v-model="filters['global'].value" placeholder="Search..." />
                        </IconField>
                    </div>
                </div>
            </template>

            <!-- Expander only on smaller screens -->
            <Column expander style="width: 3rem" headerClass="lg:hidden" bodyClass="lg:hidden" />

            <Column :exportable="false" header="Action" style="min-width: 15rem">
                <template #body="{ data }">
                    <div class="flex items-center gap-2 flex-wrap">
                        <Button icon="pi pi-pencil" outlined rounded @click="openEditRecord(data)" v-if="can('pms.record.update')" v-tooltip.top="'Edit Record'" />
                        <Button icon="pi pi-file-edit" outlined rounded @click="openEditAnswers(data)" v-if="can('pms.record.update')" v-tooltip.top="'Modify Answers'" />
                        <Button icon="pi pi-eraser" outlined rounded severity="warn" @click="confirmClearAnswers(data)" v-if="can('pms.record.update')" v-tooltip.top="'Clear Answers'" />
                        <Button icon="pi pi-trash" outlined rounded severity="danger" @click="confirmDeleteRecord(data)" v-if="can('pms.record.delete')" v-tooltip.top="'Delete Record'" />

                        <!-- Optional mobile detail toggle -->
                        <Button v-if="isSmallScreen" :icon="expandedRows?.[data.id] ? 'pi pi-chevron-up' : 'pi pi-chevron-down'" text rounded severity="secondary" @click="toggleRowExpansion(data)" v-tooltip.top="'Show Details'" />
                    </div>
                </template>
            </Column>

            <Column field="semester" header="Semester" sortable style="min-width: 8rem" />

            <Column field="property_number" header="Property Number" sortable style="min-width: 12rem" />

            <Column field="serial_num" header="Serial Number" sortable style="min-width: 12rem" headerClass="hidden lg:table-cell" bodyClass="hidden lg:table-cell" />

            <Column field="brand_name" header="Brand" sortable style="min-width: 10rem" />

            <Column field="section_code" header="Section" filterField="section_code" :showFilterMatchModes="false" :showFilterOperator="false" :showAddButton="false" :filterMenuStyle="{ width: '18rem' }" style="min-width: 16rem">
                <template #body="{ data }">
                    {{ data.section_code }}
                </template>

                <template #filter="{ filterModel, filterCallback }">
                    <MultiSelect v-model="filterModel.value" :options="sections" optionLabel="label" optionValue="code" placeholder="Any" class="w-full" @change="filterCallback()" />
                </template>
            </Column>

            <Column
                field="division_code"
                header="Division"
                filterField="division_code"
                :showFilterMatchModes="false"
                :showFilterOperator="false"
                :showAddButton="false"
                :filterMenuStyle="{ width: '18rem' }"
                style="min-width: 16rem"
                headerClass="hidden lg:table-cell"
                bodyClass="hidden lg:table-cell"
            >
                <template #body="{ data }">
                    {{ data.division_code }}
                </template>

                <template #filter="{ filterModel, filterCallback }">
                    <MultiSelect v-model="filterModel.value" :options="divisions" optionLabel="label" optionValue="code" placeholder="Any" class="w-full" @change="filterCallback()" filter />
                </template>
            </Column>

            <Column
                field="bldg"
                header="Building"
                filterField="bldg"
                :showFilterMatchModes="false"
                :showFilterOperator="false"
                :showAddButton="false"
                :filterMenuStyle="{ width: '18rem' }"
                style="min-width: 16rem"
                headerClass="hidden lg:table-cell"
                bodyClass="hidden lg:table-cell"
            >
                <template #body="{ data }">
                    {{ data.bldg }}
                </template>

                <template #filter="{ filterModel, filterCallback }">
                    <MultiSelect v-model="filterModel.value" :options="buildings" optionLabel="label" optionValue="code" placeholder="Any" class="w-full" @change="filterCallback()" filter />
                </template>
            </Column>

            <Column field="remarks" header="Remarks" style="min-width: 16rem" headerClass="hidden xl:table-cell" bodyClass="hidden xl:table-cell" />

            <Column field="findings" header="Findings" style="min-width: 16rem" headerClass="hidden xl:table-cell" bodyClass="hidden xl:table-cell" />

            <Column field="recommendation" header="Recommendation" style="min-width: 16rem" headerClass="hidden xl:table-cell" bodyClass="hidden xl:table-cell" />

            <Column field="end_user" header="End User" sortable style="min-width: 12rem" headerClass="hidden md:table-cell" bodyClass="hidden md:table-cell" />

            <Column field="created_at" header="Created At" sortable style="min-width: 12rem" headerClass="hidden lg:table-cell" bodyClass="hidden lg:table-cell" />

            <!-- Expansion content shown below row on smaller devices -->
            <template #expansion="{ data }">
                <div class="p-4 bg-surface-50 dark:bg-surface-900 border-t">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div class="lg:hidden">
                            <div class="text-sm font-semibold mb-1">Serial Number</div>
                            <div>{{ data.serial_num || '—' }}</div>
                        </div>

                        <div class="lg:hidden">
                            <div class="text-sm font-semibold mb-1">Division</div>
                            <div>{{ data.division_code || '—' }}</div>
                        </div>

                        <div class="lg:hidden">
                            <div class="text-sm font-semibold mb-1">Building</div>
                            <div>{{ data.bldg || '—' }}</div>
                        </div>

                        <div class="md:hidden">
                            <div class="text-sm font-semibold mb-1">End User</div>
                            <div>{{ data.end_user || '—' }}</div>
                        </div>

                        <div class="lg:hidden">
                            <div class="text-sm font-semibold mb-1">Created At</div>
                            <div>{{ data.created_at || '—' }}</div>
                        </div>

                        <div class="xl:hidden md:col-span-2">
                            <div class="text-sm font-semibold mb-1">Remarks</div>
                            <div>{{ data.remarks || '—' }}</div>
                        </div>

                        <div class="xl:hidden md:col-span-2">
                            <div class="text-sm font-semibold mb-1">Findings</div>
                            <div>{{ data.findings || '—' }}</div>
                        </div>

                        <div class="xl:hidden md:col-span-2">
                            <div class="text-sm font-semibold mb-1">Recommendation</div>
                            <div>{{ data.recommendation || '—' }}</div>
                        </div>
                    </div>
                </div>
            </template>
        </DataTable>

        <Dialog v-model:visible="editRecordDialog" :style="{ width: '700px' }" header="Edit PMS Record" :modal="true">
            <div class="flex flex-col gap-4">
                <div>
                    <label class="block font-bold mb-2">PMS Schedule</label>
                    <Select v-model="editForm.pms_schedule_id" :options="schedules" optionLabel="label" optionValue="id" :invalid="!!editErrors.pms_schedule_id" fluid />
                    <small v-if="editErrors.pms_schedule_id" class="text-red-500">{{ editErrors.pms_schedule_id }}</small>
                </div>

                <div>
                    <label class="block font-bold mb-2">Inventory</label>
                    <Select v-model="editForm.inventory_id" :options="inventoryItems" optionLabel="label" optionValue="id" :invalid="!!editErrors.inventory_id" filter fluid />
                    <small v-if="editErrors.inventory_id" class="text-red-500">{{ editErrors.inventory_id }}</small>
                </div>

                <div class="grid grid-cols-12 gap-4">
                    <div class="col-span-12 md:col-span-4">
                        <label class="block font-bold mb-2">Section</label>
                        <Select v-model="editForm.section_code" :options="sections" optionLabel="label" optionValue="code" :invalid="!!editErrors.section_code" filter fluid />
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

                <div>
                    <label class="block font-bold mb-2">End User</label>
                    <InputText v-model="editForm.end_user" fluid />
                </div>

                <div>
                    <label class="block font-bold mb-2">Remarks</label>
                    <Textarea v-model="editForm.remarks" rows="3" fluid />
                </div>

                <div>
                    <label class="block font-bold mb-2">Findings</label>
                    <Textarea v-model="editForm.findings" rows="3" fluid />
                </div>

                <div>
                    <label class="block font-bold mb-2">Recommendation</label>
                    <Textarea v-model="editForm.recommendation" rows="3" fluid />
                </div>
            </div>

            <template #footer>
                <Button label="Cancel" icon="pi pi-times" text @click="editRecordDialog = false" />
                <Button label="Save" icon="pi pi-check" :loading="savingRecord" @click="saveRecordEdit" />
            </template>
        </Dialog>

        <Dialog v-model:visible="deleteRecordDialog" :style="{ width: '450px' }" header="Confirm" :modal="true">
            <div class="flex items-center gap-4">
                <i class="pi pi-exclamation-triangle text-3xl!" />
                <span>Are you sure you want to delete this PMS record?</span>
            </div>
            <template #footer>
                <Button label="No" icon="pi pi-times" text @click="deleteRecordDialog = false" />
                <Button label="Yes" icon="pi pi-check" @click="deleteRecord" />
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

                <!-- DESKTOP TABLE -->
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

                <!-- MOBILE CARD LAYOUT -->
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
            </div>

            <template #footer>
                <Button label="Cancel" icon="pi pi-times" text @click="editAnswersDialog = false" />
                <Button label="Save" icon="pi pi-check" :loading="savingAnswers" @click="saveAnswerEdit" />
            </template>
        </Dialog>

        <Dialog v-model:visible="clearAnswersDialog" :style="{ width: '450px' }" header="Confirm" :modal="true">
            <div class="flex items-center gap-4">
                <i class="pi pi-exclamation-triangle text-3xl!" />
                <span>Are you sure you want to clear all answers for this PMS record?</span>
            </div>
            <template #footer>
                <Button label="No" icon="pi pi-times" text @click="clearAnswersDialog = false" />
                <Button label="Yes" icon="pi pi-check" severity="warn" @click="clearAnswers" />
            </template>
        </Dialog>
    </div>
</template>

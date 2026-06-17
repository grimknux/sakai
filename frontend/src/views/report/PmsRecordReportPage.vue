<script setup>
import { deviceTypesApi } from '@/api/assets';
import { pmsReportsApi, pmsSchedulesApi } from '@/api/pms';
import { usePermissions } from '@/composables/usePermissions';
import { FilterMatchMode } from '@primevue/core/api';
import { computed, onMounted, ref, watch } from 'vue';

const dt = ref();
const reports = ref([]);
const schedules = ref([]);
const deviceTypeOptions = ref([]);
const { can } = usePermissions();

const selectedYear = ref(null);
const selectedSemester = ref(null);
const selectedDeviceType = ref(null);

const semesterOptions = [
    { value: null, label: 'All Semesters' },
    { value: 'first', label: 'First Semester' },
    { value: 'second', label: 'Second Semester' }
];

const yearOptions = computed(() => {
    const years = [...new Set((schedules.value || []).map((s) => s.year))].sort((a, b) => b - a);

    return [
        { value: null, label: 'All Years' },
        ...years.map((year) => ({
            value: year,
            label: String(year)
        }))
    ];
});

const filters = ref({
    global: { value: null, matchMode: FilterMatchMode.CONTAINS }
});

watch([selectedYear, selectedSemester, selectedDeviceType], async () => {
    await load();
});

async function load() {
    const params = {};

    if (selectedYear.value) {
        params.year = selectedYear.value;
    }

    if (selectedSemester.value) {
        params.semester = selectedSemester.value;
    }

    if (selectedDeviceType.value) {
        params.deviceType = selectedDeviceType.value;
    }

    const res = await pmsReportsApi.list(params);
    reports.value = res.records || [];
}

async function loadLookups() {
    const scheduleRes = await pmsSchedulesApi.dropdown();
    const deviceTypeRes = await deviceTypesApi.dropdown();

    schedules.value = (scheduleRes.schedules || []).map((s) => ({
        ...s,
        id: Number(s.id),
        label: `${s.year} - ${s.semester.charAt(0).toUpperCase() + s.semester.slice(1)} semester`
    }));

    deviceTypeOptions.value = [
        { value: null, label: 'All Device Types' },
        ...(deviceTypeRes.device_types || []).map((dt) => ({
            value: dt.id, // explicitly set value
            label: dt.name
        }))
    ];

    await load();
}

function exportCSV() {
    dt.value.exportCSV();
}

function downloadPDF() {
    const params = {};

    if (selectedYear.value) {
        params.year = selectedYear.value;
    }

    if (selectedSemester.value) {
        params.semester = selectedSemester.value;
    }

    if (selectedDeviceType.value) {
        params.deviceType = selectedDeviceType.value;
    }

    console.log('Downloading PDF with params:', params);
    pmsReportsApi.pdf(params);
}

function clearFilter() {
    filters.value.global.value = null;
    selectedYear.value = null;
    selectedSemester.value = null;
    selectedDeviceType.value = null;
}

onMounted(async () => {
    await loadLookups();
});
</script>

<template>
    <div class="card">
        <h4 class="m-0">PMS Records</h4>
        <Toolbar class="mb-6">
            <template #start>
                <div class="flex gap-2">
                    <Button label="Export CSV" icon="pi pi-file-excel" severity="primary" @click="exportCSV" v-if="can('pms.record.view.report')" />
                    <Button label="Download PDF" icon="pi pi-file-pdf" severity="info" @click="downloadPDF" v-if="can('pms.record.view.report')" />
                </div>
            </template>
        </Toolbar>

        <DataTable
            ref="dt"
            v-model:filters="filters"
            :value="reports"
            dataKey="id"
            :paginator="true"
            :rows="10"
            filterDisplay="menu"
            :globalFilterFields="['device_type', 'semester', 'year', 'property_number', 'serial_num', 'brand_name', 'section_code', 'division_code', 'bldg', 'remarks', 'findings', 'status', 'end_user', 'current_user', 'conducted_date']"
            paginatorTemplate="FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink CurrentPageReport RowsPerPageDropdown"
            :rowsPerPageOptions="[5, 10, 25, 100, 300, 500, 1000]"
            currentPageReportTemplate="Showing {first} to {last} of {totalRecords} items"
            showGridlines
        >
            <template #header>
                <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-3">
                    <!-- Filters -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 md:grid md:grid-cols-3 gap-2 w-full md:w-auto">
                        <Select v-model="selectedYear" :options="yearOptions" optionLabel="label" optionValue="value" placeholder="Select Year" class="w-full md:w-40" />

                        <Select v-model="selectedSemester" :options="semesterOptions" optionLabel="label" optionValue="value" placeholder="Select Semester" class="w-full md:w-52" />

                        <Select v-model="selectedDeviceType" :options="deviceTypeOptions" optionLabel="label" optionValue="value" placeholder="Select Device Type" class="w-full md:w-52" />
                    </div>

                    <!-- Right Side (actions if needed) -->
                    <div class="flex flex-wrap gap-2 items-center justify-start md:justify-end w-full md:w-auto">
                        <!-- buttons or actions here -->
                    </div>
                </div>
            </template>

            <Column field="cnt" header="" style="min-width: 4rem" />
            <Column field="device_type" header="Type" style="min-width: 10rem" />
            <Column field="property_number" header="Property Number" style="min-width: 14rem" />
            <Column field="serial_num" header="Serial Number" style="min-width: 14rem" />
            <Column field="division_code" header="Division" style="min-width: 10rem" />
            <Column field="section_code" header="Section" style="min-width: 10rem" />
            <Column field="bldg" header="Building" style="min-width: 8rem" />
            <Column field="remarks" header="Remarks" style="min-width: 16rem" />
            <Column field="year" header="Year" style="min-width: 8rem" />
            <Column field="semester" header="Semester" style="min-width: 8rem" />
            <Column field="end_user" header="End User" style="min-width: 12rem" />
            <Column field="current_user" header="Current User" style="min-width: 12rem" />
            <Column field="conducted_date" header="Conducted At" style="min-width: 12rem" />
            <Column field="conducted_by_name" header="Conducted By" style="min-width: 12rem" />
        </DataTable>
    </div>
</template>

<script setup>
import { buildingsApi, divisionsApi, sectionsApi } from '@/api/admin';
import { inventoryApi } from '@/api/assets';
import { usePermissions } from '@/composables/usePermissions';
import { FilterMatchMode } from '@primevue/core/api';
import { useConfirm } from 'primevue/useconfirm';
import { useToast } from 'primevue/usetoast';
import { computed, onMounted, reactive, ref } from 'vue';

const toast = useToast();
const confirm = useConfirm();
const dt = ref();

const loading = ref(false);
const saving = ref(false);
const softwareSaving = ref(false);

const { can } = usePermissions();

const inventoryItems = ref([]);
const selectedInventoryItems = ref(null);

const inventoryDialog = ref(false);
const softwareDialog = ref(false);

const inventory = ref({});
const deviceTypes = ref([]);
const sections = ref([]);
const divisions = ref([]);
const buildings = ref([]);
const softwareOptions = ref([]);
const licenseTypeOptions = ref([]);
const isProgrammaticSectionUpdate = ref(false);

const locationDialog = ref(false);

const locationForm = ref({
    inventory_id: null,
    section_code: null,
    division_code: null,
    bldg: null,
    end_user: '',
    current_user: ''
});

const locationErrors = reactive({
    section_code: '',
    division_code: '',
    bldg: ''
});

const locationSaving = ref(false);
const locationErrorMsg = ref('');

const errorMsg = ref('');
const softwareErrorMsg = ref('');

const errors = reactive({
    device_type_id: '',
    section_code: '',
    division_code: '',
    bldg: '',
    property_number: '',
    year: '',
    brand_name: '',
    serial_num: '',
    ict_tag: '',
    status: '',
    is_active: ''
});

const softwareForm = ref({
    id: null,
    inventory_id: null,
    software_id: null,
    license_key: '',
    license_type: null,
    effective_from: null
});

const softwareFormErrors = reactive({
    software_id: '',
    license_key: '',
    license_type: '',
    effective_from: ''
});

const filters = ref({
    global: { value: null, matchMode: FilterMatchMode.CONTAINS },
    property_number: { value: null, matchMode: FilterMatchMode.CONTAINS },
    year: { value: null, matchMode: FilterMatchMode.CONTAINS },
    brand_name: { value: null, matchMode: FilterMatchMode.CONTAINS },
    serial_num: { value: null, matchMode: FilterMatchMode.CONTAINS },
    ict_tag: { value: null, matchMode: FilterMatchMode.CONTAINS },
    end_user: { value: null, matchMode: FilterMatchMode.CONTAINS },
    current_user: { value: null, matchMode: FilterMatchMode.CONTAINS },
    status: { value: null, matchMode: FilterMatchMode.CONTAINS }
});

const statusOptions = ref([
    { label: 'Available', value: 'available' },
    { label: 'In Use', value: 'in_use' },
    { label: 'For Repair', value: 'for_repair' },
    { label: 'Retired', value: 'retired' },
    { label: 'Lost', value: 'lost' },
    { label: 'Disposed', value: 'disposed' }
]);

const currentItemTitle = computed(() => {
    return inventory.value?.property_number || inventory.value?.serial_num || `#${inventory.value?.id || ''}`;
});

const selectedSyncableItems = computed(() => {
    return (selectedInventoryItems.value || []).filter((item) => item.pms_sync?.can_sync_from_pms);
});

const hasSelectedSyncableItems = computed(() => {
    return selectedSyncableItems.value.length > 0;
});

const hasAnyPmsMismatch = computed(() => {
    return inventoryItems.value.some((item) => item.pms_sync?.is_mismatch);
});

const actionMenus = ref({});

function setActionMenuRef(el, rowId) {
    if (el) {
        actionMenus.value[rowId] = el;
    } else {
        delete actionMenus.value[rowId];
    }
}

function toggleActionMenu(event, row) {
    actionMenus.value[row.id]?.toggle(event);
}

function getInventoryActionItems(row) {
    const items = [];

    if (can('inventory.software.create')) {
        items.push({
            label: 'Add Software',
            icon: 'pi pi-desktop',
            command: () => openAddSoftwareDialog(row)
        });
    }

    if (can('inventory.update')) {
        items.push(
            {
                label: 'Update Location / Assignment',
                icon: 'pi pi-map-marker',
                command: () => openLocationDialog(row)
            },
            {
                label: 'Edit Inventory',
                icon: 'pi pi-pencil',
                command: () => editInventory(row)
            }
        );
    }

    if (can('inventory.delete')) {
        items.push({
            label: 'Delete Inventory',
            icon: 'pi pi-trash',
            command: () => confirmDeleteInventory(row),
            class: 'text-red-600'
        });
    }

    return items;
}

function formatDateTime(date) {
    if (!date) return '-';

    const d = new Date(date);

    return d.toLocaleDateString(undefined, {
        year: 'numeric',
        month: 'short',
        day: 'numeric'
    });
}

function normalizeArray(res, keys = []) {
    for (const key of keys) {
        if (Array.isArray(res?.[key])) return res[key];
    }
    if (Array.isArray(res)) return res;
    return [];
}

function getSelectedSection(sectionCode) {
    return sections.value.find((s) => s.code === sectionCode) || null;
}

function onSectionChange(sectionCode) {
    const selectedSection = getSelectedSection(sectionCode);
    if (!selectedSection) return;

    inventory.value.division_code = selectedSection.division_code || null;
    inventory.value.bldg = selectedSection.bldg || null;
}

function clearLocationErrors() {
    locationErrors.section_code = '';
    locationErrors.division_code = '';
    locationErrors.bldg = '';
    locationErrorMsg.value = '';
}

function validateLocationForm() {
    clearLocationErrors();
    let ok = true;

    if (!locationForm.value.section_code) {
        locationErrors.section_code = 'Section is required.';
        ok = false;
    }

    if (!locationForm.value.division_code) {
        locationErrors.division_code = 'Division is required.';
        ok = false;
    }

    if (!locationForm.value.bldg) {
        locationErrors.bldg = 'Building is required.';
        ok = false;
    }

    return ok;
}

function onLocationSectionChange(sectionCode) {
    const selectedSection = getSelectedSection(sectionCode);
    if (!selectedSection) return;

    locationForm.value.division_code = selectedSection.division_code || null;
    locationForm.value.bldg = selectedSection.bldg || null;
}

function openLocationDialog(row) {
    clearLocationErrors();
    inventory.value = { ...row };

    locationForm.value = {
        inventory_id: row.id,
        section_code: row.section_code || null,
        division_code: row.division_code || null,
        bldg: row.bldg || null,
        end_user: row.end_user || '',
        current_user: row.current_user || ''
    };

    locationDialog.value = true;
}

async function load() {
    const res = await inventoryApi.list();

    inventoryItems.value = (res.items || res.inventory || []).map((item) => {
        const history = (item.software_history || []).map((h) => ({
            ...h,
            id: h.id,
            inventory_id: h.inventory_id,
            software_id: h.software_id || null,
            software_type_id: h.software_type_id || null,
            license_type: h.license_type || null
        }));

        return {
            ...item,
            id: item.id,
            device_type_id: item.device_type_id || null,
            section_code: item.section_code || null,
            division_code: item.division_code || null,
            bldg: item.bldg || null,
            year: item.year || null,
            is_active: Number(item.is_active) === 1 ? 1 : 0,
            software_history: history,
            software_map: Object.fromEntries(history.map((sw) => [sw.software_type_id, sw])),
            pms_sync: item.pms_sync || null
        };
    });
}

const softwareTypeColumns = computed(() => {
    const map = new Map();

    for (const item of inventoryItems.value) {
        for (const sw of item.software_history || []) {
            const typeId = sw.software_type_id;
            if (!typeId) continue;

            if (!map.has(typeId)) {
                map.set(typeId, {
                    id: typeId,
                    name: sw.software_type_name || `Software Type ${typeId}`
                });
            }
        }
    }

    return Array.from(map.values()).sort((a, b) => a.name.localeCompare(b.name));
});

async function loadLookups() {
    try {
        const [deviceTypeRes, softwareRes, licenseTypeRes, sectionRes, divisionRes, buildingRes] = await Promise.all([
            inventoryApi.deviceTypes(),
            inventoryApi.softwareList(),
            inventoryApi.licenseType(),
            sectionsApi.dropdown(),
            divisionsApi.dropdown(),
            buildingsApi.dropdown()
        ]);

        deviceTypes.value = (deviceTypeRes.device_types || deviceTypeRes.items || []).map((d) => ({
            ...d,
            id: d.id
        }));

        softwareOptions.value = (softwareRes.softwares || softwareRes.items || []).map((s) => ({
            ...s,
            id: s.id
        }));

        licenseTypeOptions.value = (licenseTypeRes.license_types || licenseTypeRes.items || []).map((s) => ({
            ...s,
            id: s.id
        }));

        sections.value = normalizeArray(sectionRes, ['sections', 'items']).map((s) => ({
            ...s,
            code: s.code,
            name: s.name,
            division_code: s.division_code || null,
            bldg: s.bldg || null
        }));

        divisions.value = normalizeArray(divisionRes, ['divisions', 'items']).map((d) => ({
            ...d,
            code: d.code,
            name: d.name
        }));

        buildings.value = normalizeArray(buildingRes, ['buildings', 'items']).map((b) => ({
            ...b,
            code: b.code,
            name: b.name
        }));
    } catch (e) {
        const msg = e?.response?.data?.message || e?.response?.data?.messages?.error || e?.message || 'Failed to load lookup data';
        toast.add({ severity: 'error', summary: 'Error', detail: msg, life: 4000 });
    }
}

async function saveLocation() {
    if (!validateLocationForm()) return;

    locationSaving.value = true;

    try {
        await inventoryApi.updateLocation(locationForm.value.inventory_id, {
            section_code: locationForm.value.section_code,
            division_code: locationForm.value.division_code,
            bldg: locationForm.value.bldg,
            end_user: locationForm.value.end_user || '',
            current_user: locationForm.value.current_user || ''
        });

        toast.add({
            severity: 'success',
            summary: 'Successful',
            detail: 'Location / assignment updated',
            life: 3000
        });

        locationDialog.value = false;
        await load();
    } catch (e) {
        const data = e?.response?.data || {};
        const msg = data?.message || data?.messages?.error || e?.message || 'Failed to update location / assignment';

        locationErrorMsg.value = msg;
    } finally {
        locationSaving.value = false;
    }
}

onMounted(async () => {
    await Promise.all([load(), loadLookups()]);
});

function clearErrors() {
    errors.device_type_id = '';
    errors.section_code = '';
    errors.division_code = '';
    errors.bldg = '';
    errors.property_number = '';
    errors.year = '';
    errors.brand_name = '';
    errors.serial_num = '';
    errors.ict_tag = '';
    errors.status = '';
    errors.is_active = '';
    errorMsg.value = '';
}

function clearSoftwareFormErrors() {
    softwareFormErrors.software_id = '';
    softwareFormErrors.license_key = '';
    softwareFormErrors.license_type = '';
    softwareFormErrors.effective_from = '';
    softwareErrorMsg.value = '';
}

function validate() {
    clearErrors();
    let ok = true;

    if (!inventory.value.device_type_id) {
        errors.device_type_id = 'Device type is required.';
        ok = false;
    }

    if (!inventory.value.section_code) {
        errors.section_code = 'Section is required.';
        ok = false;
    }

    if (!inventory.value.division_code) {
        errors.division_code = 'Division is required.';
        ok = false;
    }

    if (!inventory.value.property_number?.trim()) {
        errors.property_number = 'Property number is required.';
        ok = false;
    }

    if (!inventory.value.status?.trim()) {
        errors.status = 'Status is required.';
        ok = false;
    }

    if (![0, 1, '0', '1', true, false].includes(inventory.value.is_active)) {
        errors.is_active = 'Active status is required.';
        ok = false;
    }

    return ok;
}

function validateSoftwareForm() {
    clearSoftwareFormErrors();
    let ok = true;

    if (!softwareForm.value.software_id) {
        softwareFormErrors.software_id = 'Software is required.';
        ok = false;
    }

    if (!softwareForm.value.license_type) {
        softwareFormErrors.license_type = 'License type is required.';
        ok = false;
    }

    if (!softwareForm.value.effective_from) {
        softwareFormErrors.effective_from = 'Effective from is required.';
        ok = false;
    }

    return ok;
}

function openNew() {
    clearErrors();

    isProgrammaticSectionUpdate.value = true;
    inventory.value = {
        device_type_id: null,
        section_code: null,
        division_code: null,
        bldg: null,
        property_number: '',
        year: '',
        end_user: '',
        current_user: '',
        brand_name: '',
        serial_num: '',
        ict_tag: '',
        status: 'available',
        is_active: 1
    };
    inventoryDialog.value = true;

    queueMicrotask(() => {
        isProgrammaticSectionUpdate.value = false;
    });
}

function hideDialog() {
    clearErrors();
    inventoryDialog.value = false;
    loading.value = false;
}

function editInventory(row) {
    clearErrors();

    isProgrammaticSectionUpdate.value = true;
    inventory.value = {
        ...row,
        device_type_id: row.device_type_id || null,
        section_code: row.section_code || null,
        division_code: row.division_code || null,
        bldg: row.bldg || null,
        year: row.year ? Number(row.year) : null,
        is_active: Number(row.is_active) === 1 ? 1 : 0
    };

    inventoryDialog.value = true;

    queueMicrotask(() => {
        isProgrammaticSectionUpdate.value = false;
    });
}

async function saveInventory() {
    if (!validate()) return;

    loading.value = true;
    saving.value = true;

    try {
        const payload = {
            device_type_id: inventory.value.device_type_id,
            section_code: inventory.value.section_code,
            division_code: inventory.value.division_code,
            bldg: inventory.value.bldg,
            property_number: inventory.value.property_number,
            year: inventory.value.year,
            end_user: inventory.value.end_user || '',
            current_user: inventory.value.current_user || '',
            brand_name: inventory.value.brand_name,
            serial_num: inventory.value.serial_num,
            ict_tag: inventory.value.ict_tag,
            status: inventory.value.status,
            is_active: inventory.value.is_active ? 1 : 0
        };

        if (inventory.value.id) {
            await inventoryApi.update(inventory.value.id, payload);
            toast.add({ severity: 'success', summary: 'Successful', detail: 'Inventory updated', life: 3000 });
        } else {
            await inventoryApi.create(payload);
            toast.add({ severity: 'success', summary: 'Successful', detail: 'Inventory created', life: 3000 });
        }

        inventoryDialog.value = false;
        inventory.value = {};
        await load();
    } catch (err) {
        clearErrors();

        const status = err?.response?.status;
        const data = err?.response?.data || {};

        if (status === 422) {
            const fields = data?.messages?.fields || {};

            errors.device_type_id = fields.device_type_id || '';
            errors.section_code = fields.section_code || '';
            errors.division_code = fields.division_code || '';
            errors.bldg = fields.bldg || '';
            errors.property_number = fields.property_number || '';
            errors.year = fields.year || '';
            errors.brand_name = fields.brand_name || '';
            errors.serial_num = fields.serial_num || '';
            errors.ict_tag = fields.ict_tag || '';
            errors.status = fields.status || '';
            errors.is_active = fields.is_active || '';

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

function confirmDeleteInventory(row) {
    confirm.require({
        message: `Are you sure you want to delete this inventory item?`,
        header: 'Confirm Delete',
        icon: 'pi pi-exclamation-triangle',
        group: 'deleteInventory',
        inventoryText: `${row.property_number || 'No property number'}${row.serial_num ? ` · ${row.serial_num}` : ''}`,

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
                await inventoryApi.remove(row.id);
                toast.add({ severity: 'success', summary: 'Successful', detail: 'Inventory deleted', life: 3000 });
                await load();
            } catch (e) {
                const msg = e?.response?.data?.message || e?.response?.data?.messages?.message || e?.message || 'Error';
                toast.add({ severity: 'error', summary: 'Error', detail: msg, life: 4000 });
            }
        }
    });
}

function confirmDeleteSelected() {
    const items = selectedInventoryItems.value || [];
    if (!items.length) return;

    confirm.require({
        message: `Are you sure you want to delete the selected inventory items?`,
        header: 'Confirm Bulk Delete',
        icon: 'pi pi-exclamation-triangle',
        group: 'deleteSelectedInventory',
        selectionText: `${items.length} item(s) selected`,

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
                for (const item of items) {
                    await inventoryApi.remove(item.id);
                }

                toast.add({
                    severity: 'success',
                    summary: 'Successful',
                    detail: 'Selected inventory deleted',
                    life: 3000
                });
            } catch {
                toast.add({
                    severity: 'error',
                    summary: 'Error',
                    detail: 'Some deletes failed',
                    life: 4000
                });
            } finally {
                selectedInventoryItems.value = null;
                await load();
            }
        }
    });
}

function exportCSV() {
    dt.value.exportCSV();
}

function openAddSoftwareDialog(row) {
    clearSoftwareFormErrors();
    inventory.value = { ...row };

    softwareForm.value = {
        id: null,
        inventory_id: row.id,
        software_id: null,
        license_key: '',
        license_type: null,
        effective_from: null
    };

    softwareDialog.value = true;
}

function openEditSoftwareDialog(row, software) {
    clearSoftwareFormErrors();
    inventory.value = { ...row };

    softwareForm.value = {
        id: software.id,
        inventory_id: row.id,
        software_id: software.software_id || null,
        license_key: software.license_key || '',
        license_type: software.license_type || null,
        effective_from: software.effective_from || null
    };

    softwareDialog.value = true;
}

async function saveSoftware() {
    if (!validateSoftwareForm()) return;

    softwareSaving.value = true;

    try {
        const payload = {
            software_id: softwareForm.value.software_id,
            license_key: softwareForm.value.license_key || '',
            license_type: softwareForm.value.license_type,
            effective_from: softwareForm.value.effective_from || null
        };

        if (softwareForm.value.id) {
            await inventoryApi.updateSoftware(softwareForm.value.inventory_id, softwareForm.value.id, payload);
            toast.add({
                severity: 'success',
                summary: 'Successful',
                detail: 'Software updated',
                life: 3000
            });
        } else {
            await inventoryApi.addSoftware(softwareForm.value.inventory_id, payload);
            toast.add({
                severity: 'success',
                summary: 'Successful',
                detail: 'Software added',
                life: 3000
            });
        }

        softwareDialog.value = false;
        await load();
    } catch (e) {
        clearSoftwareFormErrors();

        const status = e?.response?.status;
        const data = e?.response?.data || {};

        if (status === 422) {
            const fields = data?.messages?.fields || {};
            softwareErrorMsg.value = data?.messages?.error || 'Validation failed.';

            softwareFormErrors.software_id = fields.software_id || '';
            softwareFormErrors.license_key = fields.license_key || '';
            softwareFormErrors.license_type = fields.license_type || '';
            softwareFormErrors.effective_from = fields.effective_from || '';
            return;
        }

        const msg = data?.message || data?.messages?.error || e?.message || 'Failed to save software';
        toast.add({ severity: 'error', summary: 'Error', detail: msg, life: 4000 });
    } finally {
        softwareSaving.value = false;
    }
}

function confirmDeleteSoftware(row, software) {
    confirm.require({
        message: `Are you sure you want to delete this software?`,
        header: 'Confirm Delete',
        icon: 'pi pi-exclamation-triangle',
        group: 'deleteSoftware',
        softwareText: `${software.software_name || 'Unknown software'} · ${row.property_number || row.serial_num || 'Inventory item'}`,

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
                await inventoryApi.deleteSoftware(row.id, software.id);
                toast.add({
                    severity: 'success',
                    summary: 'Successful',
                    detail: 'Software deleted',
                    life: 3000
                });
                await load();
            } catch (e) {
                const msg = e?.response?.data?.message || e?.response?.data?.messages?.error || e?.message || 'Failed to delete software';
                toast.add({ severity: 'error', summary: 'Error', detail: msg, life: 4000 });
            }
        }
    });
}

function syncLocationFromPms(row) {
    const current = {
        section: row.section_code || '-',
        division: row.division_code || '-',
        bldg: row.bldg || '-',
        end_user: row.end_user || '-',
        current_user: row.current_user || '-'
    };

    const next = {
        section: row.pms_sync?.latest_values?.section_code || '-',
        division: row.pms_sync?.latest_values?.division_code || '-',
        bldg: row.pms_sync?.latest_values?.bldg || '-',
        end_user: row.pms_sync?.latest_values?.end_user || '-',
        current_user: row.pms_sync?.latest_values?.current_user || '-'
    };

    confirm.require({
        group: 'pmsSync',
        header: 'Confirm Location Sync',
        icon: 'pi pi-exclamation-triangle',
        itemName: row.property_number || row.serial_num || row.id,
        current,
        next,
        acceptLabel: 'Yes, Update',
        rejectLabel: 'Cancel',
        acceptClass: 'p-button-info',
        accept: async () => {
            try {
                await inventoryApi.syncPms(row.id);

                toast.add({
                    severity: 'success',
                    summary: 'Successful',
                    detail: 'Inventory location updated from latest PMS record',
                    life: 3000
                });

                await load();
            } catch (e) {
                const msg = e?.response?.data?.message || e?.response?.data?.messages?.error || e?.message || 'Failed to sync location from PMS';
                toast.add({
                    severity: 'error',
                    summary: 'Error',
                    detail: msg,
                    life: 4000
                });
            }
        }
    });
}

async function confirmBulkSyncFromPms() {
    const selectedItems = selectedInventoryItems.value || [];
    const syncableItems = selectedSyncableItems.value;

    if (!selectedItems.length) {
        toast.add({
            severity: 'warn',
            summary: 'No Selection',
            detail: 'Please select inventory items first.',
            life: 3000
        });
        return;
    }

    if (!syncableItems.length) {
        toast.add({
            severity: 'warn',
            summary: 'Nothing to Sync',
            detail: 'None of the selected inventory items need PMS sync.',
            life: 3000
        });
        return;
    }

    const skippedCount = selectedItems.length - syncableItems.length;

    confirm.require({
        group: 'bulkPmsSync',
        header: 'Confirm Bulk PMS Sync',
        icon: 'pi pi-exclamation-triangle',
        totalSelected: selectedItems.length,
        syncableCount: syncableItems.length,
        skippedCount,
        acceptLabel: 'Yes, Sync',
        rejectLabel: 'Cancel',
        acceptClass: 'p-button-info',
        accept: async () => {
            loading.value = true;

            try {
                const results = await Promise.allSettled(syncableItems.map((item) => inventoryApi.syncPms(item.id)));

                const successCount = results.filter((r) => r.status === 'fulfilled').length;
                const failedCount = results.filter((r) => r.status === 'rejected').length;

                if (successCount && !failedCount) {
                    toast.add({
                        severity: 'success',
                        summary: 'Successful',
                        detail: `${successCount} inventory item(s) synced from PMS.`,
                        life: 4000
                    });
                } else if (successCount && failedCount) {
                    toast.add({
                        severity: 'warn',
                        summary: 'Partially Successful',
                        detail: `${successCount} synced, ${failedCount} failed.`,
                        life: 5000
                    });
                } else {
                    toast.add({
                        severity: 'error',
                        summary: 'Error',
                        detail: 'Bulk PMS sync failed.',
                        life: 4000
                    });
                }

                selectedInventoryItems.value = [];

                await load();
            } finally {
                loading.value = false;
            }
        }
    });
}

function getDeviceTypeLabel(deviceTypeId) {
    const found = deviceTypes.value.find((d) => d.id === deviceTypeId);
    return found?.name || found?.label || found?.device_type || '-';
}

function getStatusSeverity(status) {
    switch ((status || '').toLowerCase()) {
        case 'available':
            return 'success';
        case 'in_use':
            return 'info';
        case 'for_repair':
            return 'warn';
        case 'retired':
        case 'lost':
        case 'disposed':
            return 'danger';
        default:
            return 'secondary';
    }
}

function onSingleLicenseTypeChange() {
    if (softwareForm.value.license_type === 7) {
        softwareForm.value.license_key = 'N/A';
    } else if (softwareForm.value.license_key === 'N/A') {
        softwareForm.value.license_key = '';
    }
}

function getRowClass(data) {
    return data?.pms_sync?.is_mismatch ? 'inventory-pms-mismatch' : '';
}
</script>

<template>
    <div class="card">
        <Toolbar class="mb-6">
            <template #start>
                <Button label="New" icon="pi pi-plus" severity="secondary" class="mr-2" @click="openNew" v-if="can('inventory.create')" />
                <Button label="Delete" icon="pi pi-trash" severity="secondary" class="mr-2" @click="confirmDeleteSelected" :disabled="!selectedInventoryItems || !selectedInventoryItems.length" v-if="can('inventory.delete')" />
                <Button label="Bulk Sync PMS" icon="pi pi-refresh" outlined severity="info" class="mr-2" @click="confirmBulkSyncFromPms" :disabled="!hasSelectedSyncableItems" v-if="can('inventory.update')" />
            </template>

            <template #end>
                <Button label="Export" icon="pi pi-upload" severity="secondary" @click="exportCSV" />
            </template>
        </Toolbar>

        <DataTable
            ref="dt"
            v-model:selection="selectedInventoryItems"
            :value="inventoryItems"
            dataKey="id"
            :paginator="true"
            :rows="10"
            :filters="filters"
            :rowClass="getRowClass"
            paginatorTemplate="FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink CurrentPageReport RowsPerPageDropdown"
            :rowsPerPageOptions="[5, 10, 25]"
            currentPageReportTemplate="Showing {first} to {last} of {totalRecords} items"
        >
            <template #header>
                <div class="flex flex-wrap gap-2 items-center justify-between">
                    <h4 class="m-0">Manage Inventory</h4>
                    <IconField>
                        <InputIcon><i class="pi pi-search" /></InputIcon>
                        <InputText v-model="filters['global'].value" placeholder="Search..." />
                    </IconField>
                </div>
            </template>

            <Column selectionMode="multiple" style="width: 3rem" :exportable="false" v-if="can('inventory.delete')" />
            <Column :exportable="false" header="Action" style="min-width: 9rem" v-if="can('inventory.software.create') || can('inventory.update') || can('inventory.delete')">
                <template #body="{ data }">
                    <Button icon="pi pi-ellipsis-v" label="Actions" outlined @click="toggleActionMenu($event, data)" />
                    <Menu :ref="(el) => setActionMenuRef(el, data.id)" :model="getInventoryActionItems(data)" popup />
                </template>
            </Column>
            <Column header="PMS Sync" sortField="pms_sync.sort_order" sortable style="min-width: 15rem">
                <template #body="{ data }">
                    <div class="flex items-start gap-2">
                        <Button
                            v-if="can('inventory.update') && data.pms_sync?.can_sync_from_pms"
                            icon="pi pi-refresh"
                            outlined
                            rounded
                            severity="warn"
                            size="small"
                            v-tooltip.top="'Update section/division/building from latest PMS'"
                            @click="syncLocationFromPms(data)"
                        />

                        <div class="flex flex-col gap-2">
                            <div class="flex items-center gap-2 flex-wrap">
                                <Tag :value="data.pms_sync?.tag_label" :severity="data.pms_sync?.severity" />
                            </div>

                            <div v-if="data.pms_sync?.display_date" class="text-xs text-surface-500">
                                <span class="font-medium"> {{ data.pms_sync.display_label }}: </span>
                                {{ formatDateTime(data.pms_sync.display_date) }}
                            </div>
                        </div>
                    </div>
                </template>
            </Column>
            <Column field="property_number" header="Property Number" sortable style="min-width: 12rem" />
            <Column field="years" header="Year" sortable style="min-width: 7rem">
                <template #body="{ data }">
                    {{ data.year }}
                    <div class="text-xs text-surface-500">
                        <span class="font-medium">{{ data.years }}</span>
                    </div>
                </template>
            </Column>
            <Column field="device_type_name" header="Device Type" style="min-width: 12rem" />
            <Column field="section_code" header="Section" style="min-width: 12rem" />
            <Column field="division_code" header="Division" style="min-width: 12rem" />
            <Column field="bldg" header="Building" style="min-width: 12rem" />
            <Column field="brand_name" header="Brand" sortable style="min-width: 10rem" />
            <Column field="serial_num" header="Serial Number" sortable style="min-width: 12rem" />
            <Column field="ict_tag" header="ICT Tag" sortable style="min-width: 10rem" />
            <Column field="end_user" header="End User" sortable style="min-width: 12rem" />
            <Column field="current_user" header="Current User" sortable style="min-width: 12rem" />
            <Column field="status" header="Status" sortable style="min-width: 10rem">
                <template #body="{ data }">
                    <Tag :value="data.status || '-'" :severity="getStatusSeverity(data.status)" />
                </template>
            </Column>
            <Column field="is_active" header="Active Status" sortable style="min-width: 10rem">
                <template #body="{ data }">
                    <Tag :value="Number(data.is_active) === 1 ? 'Active' : 'Inactive'" :severity="Number(data.is_active) === 1 ? 'success' : 'danger'" />
                </template>
            </Column>

            <Column v-for="type in softwareTypeColumns" :key="type.id" :header="type.name" style="min-width: 16rem">
                <template #body="{ data }">
                    <div v-if="data.software_map?.[type.id]" class="text-sm">
                        <div class="font-medium">
                            {{ data.software_map[type.id].software_name }}
                            {{ data.software_map[type.id].version || '' }}
                        </div>
                        <div class="text-surface-500">
                            {{ data.software_map[type.id].license_type_name || '-' }}
                            <span v-if="data.software_map[type.id].license_key"> · {{ data.software_map[type.id].license_key }} </span>
                        </div>
                        <div class="text-surface-500">as of {{ data.software_map[type.id].effective_from || '-' }}</div>

                        <div class="flex gap-1 mt-2" v-if="can('inventory.software.update') || can('inventory.software.delete')">
                            <Button icon="pi pi-pencil" outlined rounded size="small" @click="openEditSoftwareDialog(data, data.software_map[type.id])" v-if="can('inventory.software.update')" />
                            <Button icon="pi pi-trash" outlined rounded severity="danger" size="small" @click="confirmDeleteSoftware(data, data.software_map[type.id])" v-if="can('inventory.software.delete')" />
                        </div>
                    </div>
                </template>
            </Column>
        </DataTable>

        <Dialog v-model:visible="inventoryDialog" :style="{ width: '700px' }" :header="inventory.id ? 'Edit Inventory Details' : 'Create Inventory'" :modal="true">
            <div class="flex flex-col gap-4">
                <div v-if="errorMsg" class="text-red-500 text-sm">
                    {{ errorMsg }}
                </div>

                <div class="grid grid-cols-12 gap-4">
                    <div class="col-span-12 md:col-span-6">
                        <label class="block font-bold mb-2">Device Type</label>
                        <Select v-model="inventory.device_type_id" :options="deviceTypes" optionLabel="name" optionValue="id" placeholder="Select device type" :invalid="!!errors.device_type_id" :disabled="loading" fluid />
                        <small v-if="errors.device_type_id" class="text-red-500">{{ errors.device_type_id }}</small>
                    </div>

                    <div class="col-span-12 md:col-span-6">
                        <label class="block font-bold mb-2">Property Number</label>
                        <InputText v-model.trim="inventory.property_number" :disabled="loading" :invalid="!!errors.property_number" fluid />
                        <small v-if="errors.property_number" class="text-red-500">{{ errors.property_number }}</small>
                    </div>
                </div>

                <div v-if="!inventory.id" class="grid grid-cols-12 gap-4">
                    <div class="col-span-12 md:col-span-5">
                        <label class="block font-bold mb-2">Section</label>
                        <Select
                            v-model="inventory.section_code"
                            :options="sections"
                            optionLabel="name"
                            optionValue="code"
                            placeholder="Select section"
                            :invalid="!!errors.section_code"
                            :disabled="loading"
                            fluid
                            filter
                            @change="onSectionChange($event.value)"
                        />
                        <small v-if="errors.section_code" class="text-red-500">{{ errors.section_code }}</small>
                    </div>

                    <div class="col-span-12 md:col-span-5">
                        <label class="block font-bold mb-2">Division</label>
                        <Select v-model="inventory.division_code" :options="divisions" optionLabel="name" optionValue="code" placeholder="Select division" :invalid="!!errors.division_code" :disabled="loading" fluid filter />
                        <small v-if="errors.division_code" class="text-red-500">{{ errors.division_code }}</small>
                    </div>

                    <div class="col-span-12 md:col-span-2">
                        <label class="block font-bold mb-2">Building</label>
                        <Select v-model="inventory.bldg" :options="buildings" optionLabel="name" optionValue="code" placeholder="Select building" :invalid="!!errors.bldg" :disabled="loading" fluid filter />
                        <small v-if="errors.bldg" class="text-red-500">{{ errors.bldg }}</small>
                    </div>
                </div>

                <div class="grid grid-cols-12 gap-4">
                    <div class="col-span-12 md:col-span-6">
                        <label class="block font-bold mb-2">Year</label>
                        <InputNumber v-model="inventory.year" :useGrouping="false" :disabled="loading" :invalid="!!errors.year" fluid />
                        <small v-if="errors.year" class="text-red-500">{{ errors.year }}</small>
                    </div>

                    <div class="col-span-12 md:col-span-6">
                        <label class="block font-bold mb-2">Brand Name</label>
                        <InputText v-model.trim="inventory.brand_name" :disabled="loading" :invalid="!!errors.brand_name" fluid />
                        <small v-if="errors.brand_name" class="text-red-500">{{ errors.brand_name }}</small>
                    </div>
                </div>

                <div class="grid grid-cols-12 gap-4">
                    <div class="col-span-12 md:col-span-6">
                        <label class="block font-bold mb-2">Serial Number</label>
                        <InputText v-model.trim="inventory.serial_num" :disabled="loading" :invalid="!!errors.serial_num" fluid />
                        <small v-if="errors.serial_num" class="text-red-500">{{ errors.serial_num }}</small>
                    </div>

                    <div class="col-span-12 md:col-span-6">
                        <label class="block font-bold mb-2">ICT Tag</label>
                        <InputText v-model.trim="inventory.ict_tag" :disabled="loading" :invalid="!!errors.ict_tag" fluid />
                        <small v-if="errors.ict_tag" class="text-red-500">{{ errors.ict_tag }}</small>
                    </div>
                </div>

                <div v-if="!inventory.id" class="grid grid-cols-12 gap-4">
                    <div class="col-span-12 md:col-span-6">
                        <label class="block font-bold mb-2">End User</label>
                        <InputText v-model.trim="inventory.end_user" :disabled="loading" fluid />
                    </div>

                    <div class="col-span-12 md:col-span-6">
                        <label class="block font-bold mb-2">Current User</label>
                        <InputText v-model.trim="inventory.current_user" :disabled="loading" fluid />
                    </div>
                </div>

                <div class="grid grid-cols-12 gap-4">
                    <div class="col-span-12 md:col-span-6">
                        <label class="block font-bold mb-2">Status</label>
                        <Select v-model="inventory.status" :options="statusOptions" optionLabel="label" optionValue="value" placeholder="Select status" :invalid="!!errors.status" :disabled="loading" fluid />
                        <small v-if="errors.status" class="text-red-500">{{ errors.status }}</small>
                    </div>

                    <div class="col-span-12 md:col-span-6">
                        <label class="block font-bold mb-2">Active Status</label>
                        <div class="flex items-center gap-2">
                            <Checkbox v-model="inventory.is_active" :binary="true" :trueValue="1" :falseValue="0" inputId="inventory_is_active" :disabled="loading" />
                            <label for="inventory_is_active" class="mb-0">
                                {{ Number(inventory.is_active) === 1 ? 'Active' : 'Inactive' }}
                            </label>
                        </div>
                        <small v-if="errors.is_active" class="text-red-500">{{ errors.is_active }}</small>
                    </div>
                </div>
            </div>

            <template #footer>
                <Button label="Cancel" icon="pi pi-times" text @click="hideDialog" />
                <Button label="Save" icon="pi pi-check" :loading="saving" @click="saveInventory" />
            </template>
        </Dialog>

        <Dialog v-model:visible="softwareDialog" header="Software Details" :modal="true" :style="{ width: '600px' }">
            <div class="flex flex-col gap-4">
                <div class="font-medium">Inventory: {{ currentItemTitle }}</div>

                <div v-if="softwareErrorMsg" class="text-red-500 text-sm">
                    {{ softwareErrorMsg }}
                </div>

                <div>
                    <label class="block font-bold mb-2">Software</label>
                    <Select v-model="softwareForm.software_id" :options="softwareOptions" optionLabel="name" optionValue="id" placeholder="Select software" fluid :invalid="!!softwareFormErrors.software_id" />
                    <small v-if="softwareFormErrors.software_id" class="text-red-500">
                        {{ softwareFormErrors.software_id }}
                    </small>
                </div>

                <div>
                    <label class="block font-bold mb-2">License Type</label>
                    <Select
                        v-model="softwareForm.license_type"
                        :options="licenseTypeOptions"
                        optionLabel="name"
                        optionValue="id"
                        placeholder="Select license type"
                        fluid
                        :invalid="!!softwareFormErrors.license_type"
                        @change="onSingleLicenseTypeChange"
                    />
                    <small v-if="softwareFormErrors.license_type" class="text-red-500">
                        {{ softwareFormErrors.license_type }}
                    </small>
                </div>

                <div>
                    <label class="block font-bold mb-2">License Key</label>
                    <InputText v-model="softwareForm.license_key" fluid :disabled="softwareForm.license_type === 7" :invalid="!!softwareFormErrors.license_key" />
                    <small v-if="softwareFormErrors.license_key" class="text-red-500">
                        {{ softwareFormErrors.license_key }}
                    </small>
                </div>

                <div class="grid grid-cols-12 gap-4">
                    <div class="col-span-12 md:col-span-6">
                        <label class="block font-bold mb-2">Effective From</label>
                        <InputText v-model="softwareForm.effective_from" type="date" fluid :invalid="!!softwareFormErrors.effective_from" />
                        <small v-if="softwareFormErrors.effective_from" class="text-red-500">
                            {{ softwareFormErrors.effective_from }}
                        </small>
                    </div>
                </div>
            </div>

            <template #footer>
                <Button label="Cancel" icon="pi pi-times" text @click="softwareDialog = false" />
                <Button label="Save" icon="pi pi-check" :loading="softwareSaving" @click="saveSoftware" />
            </template>
        </Dialog>

        <Dialog v-model:visible="locationDialog" header="Location / Assignment" :modal="true" :style="{ width: '700px' }">
            <div class="flex flex-col gap-4">
                <div class="font-medium">Inventory: {{ currentItemTitle }}</div>

                <div v-if="locationErrorMsg" class="text-red-500 text-sm">
                    {{ locationErrorMsg }}
                </div>

                <div class="grid grid-cols-12 gap-4">
                    <div class="col-span-12 md:col-span-5">
                        <label class="block font-bold mb-2">Section</label>
                        <Select
                            v-model="locationForm.section_code"
                            :options="sections"
                            optionLabel="name"
                            optionValue="code"
                            placeholder="Select section"
                            :invalid="!!locationErrors.section_code"
                            fluid
                            @change="onLocationSectionChange($event.value)"
                        />
                        <small v-if="locationErrors.section_code" class="text-red-500">{{ locationErrors.section_code }}</small>
                    </div>

                    <div class="col-span-12 md:col-span-5">
                        <label class="block font-bold mb-2">Division</label>
                        <Select v-model="locationForm.division_code" :options="divisions" optionLabel="name" optionValue="code" placeholder="Select division" :invalid="!!locationErrors.division_code" fluid />
                        <small v-if="locationErrors.division_code" class="text-red-500">{{ locationErrors.division_code }}</small>
                    </div>

                    <div class="col-span-12 md:col-span-2">
                        <label class="block font-bold mb-2">Building</label>
                        <Select v-model="locationForm.bldg" :options="buildings" optionLabel="name" optionValue="code" placeholder="Select building" :invalid="!!locationErrors.bldg" fluid />
                        <small v-if="locationErrors.bldg" class="text-red-500">{{ locationErrors.bldg }}</small>
                    </div>
                </div>

                <div class="grid grid-cols-12 gap-4">
                    <div class="col-span-12 md:col-span-6">
                        <label class="block font-bold mb-2">End User</label>
                        <InputText v-model.trim="locationForm.end_user" fluid />
                    </div>

                    <div class="col-span-12 md:col-span-6">
                        <label class="block font-bold mb-2">Current User</label>
                        <InputText v-model.trim="locationForm.current_user" fluid />
                    </div>
                </div>
            </div>

            <template #footer>
                <Button label="Cancel" icon="pi pi-times" text @click="locationDialog = false" />
                <Button label="Save" icon="pi pi-check" :loading="locationSaving" @click="saveLocation" />
            </template>
        </Dialog>

        <ConfirmDialog group="deleteInventory">
            <template #message="{ message }">
                <div>
                    <p class="mb-2">{{ message.message }}</p>
                    <p v-if="message.inventoryText" class="text-sm text-surface-500">
                        {{ message.inventoryText }}
                    </p>
                </div>
            </template>
        </ConfirmDialog>

        <ConfirmDialog group="deleteSelectedInventory">
            <template #message="{ message }">
                <div>
                    <p class="mb-2">{{ message.message }}</p>
                    <p v-if="message.selectionText" class="text-sm text-surface-500">
                        {{ message.selectionText }}
                    </p>
                </div>
            </template>
        </ConfirmDialog>

        <ConfirmDialog group="deleteSoftware">
            <template #message="{ message }">
                <div>
                    <p class="mb-2">{{ message.message }}</p>
                    <p v-if="message.softwareText" class="text-sm text-surface-500">
                        {{ message.softwareText }}
                    </p>
                </div>
            </template>
        </ConfirmDialog>

        <ConfirmDialog group="pmsSync">
            <template #message="slotProps">
                <div class="w-full">
                    <div class="mb-3">
                        Update location of <b>{{ slotProps.message.itemName }}</b> from latest PMS record?
                    </div>

                    <div class="border rounded p-3 text-sm">
                        <div class="grid grid-cols-3 gap-2 font-semibold mb-2">
                            <div>Field</div>
                            <div>Current</div>
                            <div>PMS</div>
                        </div>

                        <div class="grid grid-cols-3 gap-2 mb-2">
                            <div>Section</div>
                            <div :class="{ 'text-red-500 line-through': slotProps.message.current.section !== slotProps.message.next.section }">
                                {{ slotProps.message.current.section }}
                            </div>
                            <div :class="{ 'text-green-600 font-semibold': slotProps.message.current.section !== slotProps.message.next.section }">
                                {{ slotProps.message.next.section }}
                            </div>
                        </div>

                        <div class="grid grid-cols-3 gap-2 mb-2">
                            <div>Division</div>
                            <div :class="{ 'text-red-500 line-through': slotProps.message.current.division !== slotProps.message.next.division }">
                                {{ slotProps.message.current.division }}
                            </div>
                            <div :class="{ 'text-green-600 font-semibold': slotProps.message.current.division !== slotProps.message.next.division }">
                                {{ slotProps.message.next.division }}
                            </div>
                        </div>

                        <div class="grid grid-cols-3 gap-2 mb-2">
                            <div>Building</div>
                            <div :class="{ 'text-red-500 line-through': slotProps.message.current.bldg !== slotProps.message.next.bldg }">
                                {{ slotProps.message.current.bldg }}
                            </div>
                            <div :class="{ 'text-green-600 font-semibold': slotProps.message.current.bldg !== slotProps.message.next.bldg }">
                                {{ slotProps.message.next.bldg }}
                            </div>
                        </div>

                        <div class="grid grid-cols-3 gap-2 mb-2">
                            <div>End User</div>
                            <div :class="{ 'text-red-500 line-through': slotProps.message.current.end_user !== slotProps.message.next.end_user }">
                                {{ slotProps.message.current.end_user }}
                            </div>
                            <div :class="{ 'text-green-600 font-semibold': slotProps.message.current.end_user !== slotProps.message.next.end_user }">
                                {{ slotProps.message.next.end_user }}
                            </div>
                        </div>

                        <div class="grid grid-cols-3 gap-2">
                            <div>Current User</div>
                            <div :class="{ 'text-red-500 line-through': slotProps.message.current.current_user !== slotProps.message.next.current_user }">
                                {{ slotProps.message.current.current_user }}
                            </div>
                            <div :class="{ 'text-green-600 font-semibold': slotProps.message.current.current_user !== slotProps.message.next.current_user }">
                                {{ slotProps.message.next.current_user }}
                            </div>
                        </div>
                    </div>
                </div>
            </template>
        </ConfirmDialog>

        <ConfirmDialog group="bulkPmsSync">
            <template #message="slotProps">
                <div class="w-full">
                    <div class="mb-3">Sync selected inventory items from the latest PMS record?</div>

                    <div class="border rounded p-3 text-sm">
                        <div class="flex justify-between mb-2">
                            <span>Total selected</span>
                            <span class="font-semibold">{{ slotProps.message.totalSelected }}</span>
                        </div>
                        <div class="flex justify-between mb-2">
                            <span>Will be synced</span>
                            <span class="font-semibold text-green-600">{{ slotProps.message.syncableCount }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span>Will be skipped</span>
                            <span class="font-semibold text-surface-500">{{ slotProps.message.skippedCount }}</span>
                        </div>
                    </div>

                    <small class="block mt-3 text-surface-500"> Only selected rows with location mismatch will be updated. </small>
                </div>
            </template>
        </ConfirmDialog>
    </div>
</template>

<style scoped>
:deep(.inventory-pms-mismatch > td) {
    background: color-mix(in srgb, var(--p-primary-100) 5%, transparent);
}

:deep(.inventory-pms-mismatch > td:first-child) {
    border-left: 4px solid var(--p-primary-color);
}

:deep(.text-red-600) {
    color: #dc2626 !important;
}

:deep(.text-blue-600) {
    color: #2563eb !important;
}

:deep(.text-primary-600) {
    color: var(--p-primary-color) !important;
}
</style>

<?php

namespace App\Controllers\Api\Assets;

use App\Models\InventoryModel;
use App\Models\InventorySoftwareModel;
use CodeIgniter\Database\BaseConnection;
use CodeIgniter\RESTful\ResourceController;

class InventoryController extends ResourceController
{
    protected $format = 'json';

    protected BaseConnection $db;
    protected InventoryModel $inventory;
    protected InventorySoftwareModel $softwareHistory;

    /**
     * ==========================================
     * Constructor
     * ==========================================
     */
    public function __construct()
    {
        $this->db = \Config\Database::connect();
        $this->inventory = new InventoryModel();
        $this->softwareHistory = new InventorySoftwareModel();

        helper('security');
    }

    /**
     * ==========================================
     * List Inventory with PMS and Software History
     * ==========================================
     */
    public function index()
    {
        $today = date('Y-m-d');

        $inventoryRows = $this->inventory
            ->select('inventory.id, inventory.device_type_id, inventory.property_number, inventory.year, inventory.section_code, inventory.division_code, inventory.bldg, inventory.end_user, inventory.current_user, inventory.brand_name, inventory.serial_num, inventory.ict_tag, inventory.status, inventory.is_active, inventory.created_at, inventory.updated_at, inventory.last_pms_schedule_synced_id, inventory.last_pms_synced_at, device_types.name AS device_type_name')
            ->join('device_types', 'device_types.id = inventory.device_type_id', 'left')
            ->where('inventory.deleted_at', null)
            ->orderBy('inventory.id', 'DESC')
            ->findAll();

        if (empty($inventoryRows)) {
            return $this->respond(['inventory' => []]);
        }

        $inventoryIds = array_map(static fn($row) => (int) $row['id'], $inventoryRows);

        $manualHistoryRows = $this->db->table('inventory_location_assignment_history h')
            ->select('h.inventory_id, h.changed_at, h.id')
            ->where('h.deleted_at', null)
            ->where('h.source_type', 'manual_update')
            ->whereIn('h.inventory_id', $inventoryIds)
            ->orderBy('h.inventory_id', 'ASC')
            ->orderBy('h.changed_at', 'DESC')
            ->orderBy('h.id', 'DESC')
            ->get()
            ->getResultArray();

        $latestManualByInventory = [];
        foreach ($manualHistoryRows as $row) {
            $inventoryId = (int) $row['inventory_id'];
            if (! isset($latestManualByInventory[$inventoryId])) {
                $latestManualByInventory[$inventoryId] = $row;
            }
        }

        $latestSchedule = $this->db->table('pms_schedules')
            ->select('id, semester, schedule_start, schedule_end')
            ->where('deleted_at', null)
            ->groupStart()
                ->where('schedule_start <=', $today)
                ->where('schedule_end >=', $today)
            ->groupEnd()
            ->orderBy('schedule_start', 'DESC')
            ->get()
            ->getRowArray();

        if (! $latestSchedule) {
            $latestSchedule = $this->db->table('pms_schedules')
                ->select('id, semester, schedule_start, schedule_end')
                ->where('deleted_at', null)
                ->orderBy('schedule_end', 'DESC')
                ->orderBy('id', 'DESC')
                ->get()
                ->getRowArray();
        }

        $latestScheduleId = $latestSchedule['id'] ?? null;

        $softwareRows = $this->db->table('inventory_software_history ish')
            ->select('
                ish.id,
                ish.inventory_id,
                ish.software_id,
                ish.license_key,
                ish.license_type,
                ish.effective_from,
                ish.effective_to,
                s.name as software_name,
                s.version,
                s.software_type_id,
                st.name as software_type_name,
                lt.name as license_type_name
            ')
            ->join('softwares s', 's.id = ish.software_id', 'left')
            ->join('software_types st', 'st.id = s.software_type_id', 'left')
            ->join('license_types lt', 'lt.id = ish.license_type', 'left')
            ->whereIn('ish.inventory_id', $inventoryIds)
            ->where('ish.deleted_at', null)
            ->orderBy('ish.inventory_id', 'ASC')
            ->orderBy('s.software_type_id', 'ASC')
            ->orderBy('ish.effective_from', 'DESC')
            ->orderBy('ish.id', 'DESC')
            ->get()
            ->getResultArray();

        $softwareByInventory = [];
        foreach ($softwareRows as $row) {
            $inventoryId = (int) $row['inventory_id'];
            $softwareTypeId = (int) ($row['software_type_id'] ?? 0);

            if ($softwareTypeId <= 0) {
                continue;
            }

            $row['id'] = encrypt_id($row['id']);

            if (! isset($softwareByInventory[$inventoryId])) {
                $softwareByInventory[$inventoryId] = [];
            }

            if (! isset($softwareByInventory[$inventoryId][$softwareTypeId])) {
                $softwareByInventory[$inventoryId][$softwareTypeId] = $row;
            }
        }

        $pmsByInventory = [];
        if ($latestScheduleId) {
            $pmsRows = $this->db->table('pms_records pr')
                ->select('pr.id, pr.inventory_id, pr.pms_schedule_id, pr.section_code, pr.division_code, pr.bldg, pr.updated_at, pr.created_at, pr.current_user, pr.end_user')
                ->where('pr.deleted_at', null)
                ->where('pr.pms_schedule_id', $latestScheduleId)
                ->whereIn('pr.inventory_id', $inventoryIds)
                ->orderBy('pr.inventory_id', 'ASC')
                ->orderBy('pr.updated_at', 'DESC')
                ->orderBy('pr.id', 'DESC')
                ->get()
                ->getResultArray();

            foreach ($pmsRows as $row) {
                $inventoryId = (int) $row['inventory_id'];
                if (! isset($pmsByInventory[$inventoryId])) {
                    $pmsByInventory[$inventoryId] = $row;
                }
            }
        }

        $result = array_map(function ($item) use ($softwareByInventory, $pmsByInventory, $latestSchedule, $latestManualByInventory) {
            $inventoryId = (int) $item['id'];
            $pmsRecord = $pmsByInventory[$inventoryId] ?? null;

            $sectionMismatch = $pmsRecord && (($item['section_code'] ?? null) !== ($pmsRecord['section_code'] ?? null));
            $divisionMismatch = $pmsRecord && (($item['division_code'] ?? null) !== ($pmsRecord['division_code'] ?? null));
            $bldgMismatch = $pmsRecord && (($item['bldg'] ?? null) !== ($pmsRecord['bldg'] ?? null));
            $endUserMismatch = $pmsRecord && (($item['end_user'] ?? null) !== ($pmsRecord['end_user'] ?? null));
            $currentUserMismatch = $pmsRecord && (($item['current_user'] ?? null) !== ($pmsRecord['current_user'] ?? null));

            $lastSyncedAt = $item['last_pms_synced_at'] ?? null;
            $lastManualAt = $latestManualByInventory[$inventoryId]['changed_at'] ?? null;
            $lastPmsUpdatedAt = $pmsRecord['updated_at'] ?? null;
            
            $inv_year   = $item['year'] ?? '';
            // Check if it’s a valid 4-digit year
            if (preg_match('/^\d{4}$/', $inv_year)) {
                $current_year = date('Y'); // or Time::now()->getYear() in CI4
                $diff =  $current_year - $inv_year . ' year(s) old';
            } else {
                $diff = "Not Available.";
            }

            $alreadySyncedForLatestSchedule =
                ! empty($latestSchedule['id']) &&
                (int) ($item['last_pms_schedule_synced_id'] ?? 0) === (int) $latestSchedule['id'];

            $isMismatch = $pmsRecord && (
                $sectionMismatch ||
                $divisionMismatch ||
                $bldgMismatch ||
                $endUserMismatch ||
                $currentUserMismatch
            );

            $statusCode = 'no_pms';
            $sortLabel = 'No PMS record';
            $sortOrder = 5;
            $canSyncFromPms = false;

            if ($pmsRecord) {
                if (! $isMismatch) {
                    if ($alreadySyncedForLatestSchedule) {
                        $statusCode = 'synced';
                        $sortLabel = 'Synced from PMS';
                        $sortOrder = 2;
                    } else {
                        $statusCode = 'matches';
                        $sortLabel = 'Matches PMS';
                        $sortOrder = 3;
                    }
                } else {
                    $manualAfterSync = $lastSyncedAt && $lastManualAt && strtotime($lastManualAt) > strtotime($lastSyncedAt);
                    $pmsAfterSync = $lastSyncedAt && $lastPmsUpdatedAt && strtotime($lastPmsUpdatedAt) > strtotime($lastSyncedAt);

                    if ($manualAfterSync || $pmsAfterSync) {
                        $manualTs = $lastManualAt ? strtotime($lastManualAt) : 0;
                        $pmsTs = $lastPmsUpdatedAt ? strtotime($lastPmsUpdatedAt) : 0;

                        if ($manualTs > $pmsTs) {
                            $statusCode = 'manual_override';
                            $sortLabel = 'Manual override';
                            $sortOrder = 4;
                            $canSyncFromPms = false;
                        } else {
                            $statusCode = 'needs_update';
                            $sortLabel = 'Needs location update';
                            $sortOrder = 1;
                            $canSyncFromPms = true;
                        }
                    } else {
                        $statusCode = 'needs_update';
                        $sortLabel = 'Needs location update';
                        $sortOrder = 1;
                        $canSyncFromPms = true;
                    }
                }
            }

            $displayDate = null;
            $displayLabel = null;
            $tagLabel = null;
            $severity = null;

            if ($statusCode === 'needs_update') {
                $displayDate = $pmsRecord['updated_at'] ?? $pmsRecord['created_at'] ?? null;
                $displayLabel = 'PMS Updated';
                $severity = 'warn';
                $tagLabel = 'Sync PMS Location';
            } elseif ($statusCode === 'manual_override') {
                $displayDate = $lastManualAt;
                $displayLabel = 'Updated Last';
                $severity = 'contrast';
                $tagLabel = 'Manual Update';
            } elseif ($statusCode === 'synced') {
                $displayDate = $item['last_pms_synced_at'] ?? null;
                $displayLabel = 'Synced Last';
                $severity = 'success';
                $tagLabel = 'Synced from PMS';
            } elseif ($statusCode === 'matches') {
                $displayDate = $pmsRecord['updated_at'] ?? $pmsRecord['created_at'] ?? null;
                $displayLabel = 'PMS Updated';
                $severity = 'info';
                $tagLabel = 'Matches PMS';
            } else {
                $displayDate = $item['created_at'] ?? null;
                $displayLabel = 'Created at';
                $severity = 'secondary';
                $tagLabel = 'No PMS record';
            }

            $item['id'] = encrypt_id($item['id']);
            $item['years'] = $diff;
            $item['software_history'] = array_values($softwareByInventory[$inventoryId] ?? []);

            $item['pms_sync'] = [
                'schedule_id' => $latestSchedule['id'] ?? null,
                'semester' => $latestSchedule['semester'] ?? null,
                'schedule_start' => $latestSchedule['schedule_start'] ?? null,
                'schedule_end' => $latestSchedule['schedule_end'] ?? null,
                'has_record' => (bool) $pmsRecord,
                'is_mismatch' => (bool) $isMismatch,
                'already_synced' => (bool) $alreadySyncedForLatestSchedule,
                'section_mismatch' => (bool) $sectionMismatch,
                'division_mismatch' => (bool) $divisionMismatch,
                'bldg_mismatch' => (bool) $bldgMismatch,
                'end_user_mismatch' => (bool) $endUserMismatch,
                'current_user_mismatch' => (bool) $currentUserMismatch,
                'latest_values' => $pmsRecord ? [
                    'section_code' => $pmsRecord['section_code'],
                    'division_code' => $pmsRecord['division_code'],
                    'bldg' => $pmsRecord['bldg'],
                    'end_user' => $pmsRecord['end_user'],
                    'current_user' => $pmsRecord['current_user'],
                ] : null,
                'status_code' => $statusCode,
                'can_sync_from_pms' => $canSyncFromPms,
                'display_date' => $displayDate,
                'display_label' => $displayLabel,
                'tag_label' => $tagLabel,
                'severity' => $severity,
                'sort_label' => $sortLabel,
                'sort_order' => $sortOrder,
            ];

            return $item;
        }, $inventoryRows);

        return $this->respond(['inventory' => $result]);
    }

    /**
     * ==========================================
     * Inventory Dropdown List
     * ==========================================
     */
    public function dropdown()
    {
        $result = $this->inventory
            ->select('
                inventory.id,
                inventory.device_type_id,
                inventory.property_number,
                inventory.year,
                inventory.section_code,
                inventory.division_code,
                inventory.bldg,
                inventory.end_user,
                inventory.current_user,
                inventory.brand_name,
                inventory.serial_num,
                inventory.ict_tag,
                inventory.status,
                inventory.created_at,
                inventory.updated_at,
                device_types.name AS device_type_name
            ')
            ->join('device_types', 'device_types.id = inventory.device_type_id', 'left')
            ->where('inventory.deleted_at', null)
            ->where('inventory.is_active', 1)
            ->orderBy('inventory.id', 'DESC')
            ->findAll();

        $rows = [];

        foreach ($result as $row) {
            // Determine identifier priority
            $identifier = '';

            if (!empty($row['property_number'])) {
                $identifier = $row['property_number'];
            } elseif (!empty($row['serial_num'])) {
                $identifier = $row['serial_num'];
            }

            // Append ICT tag if exists
            if (!empty($row['ict_tag'])) {
                $identifier .= ($identifier ? ' - ' : '') . $row['ict_tag'];
            }

            // Build label
            $label = $row['device_type_name'];
            if (!empty($identifier)) {
                $label .= ' - ' . $identifier;
            }

            // Add label to row
            $row['label'] = $label;

            $rows[] = $row;
        }

        return $this->respond(['inventory' => $rows]);
    }

    /**
     * ==========================================
     * Dropdown Available for PMS Schedule
     * ==========================================
     */
    public function dropdownAvailableForPmsSchedule($scheduleId = null)
    {
        $scheduleId = (int) $scheduleId;

        if ($scheduleId <= 0) {
            return $this->respond(['items' => []]);
        }

        $result = $this->db->table('inventory i')
            ->select('
                i.id,
                i.device_type_id,
                i.property_number,
                i.brand_name,
                i.serial_num,
                i.ict_tag,
                i.end_user,
                i.current_user,
                i.section_code,
                i.division_code,
                i.bldg,
                dt.name AS device_type_name
            ')
            ->join('device_types dt', 'dt.id = i.device_type_id', 'left')
            ->where('i.deleted_at', null)
            ->where('i.is_active', 1)
            ->where("NOT EXISTS (
                SELECT 1
                FROM pms_records pr
                WHERE pr.inventory_id = i.id
                AND pr.pms_schedule_id = {$scheduleId}
                AND pr.deleted_at IS NULL
            )", null, false)
            ->orderBy('dt.name', 'ASC')
            ->orderBy('i.ict_tag', 'ASC')
            ->get()
            ->getResultArray();

        $rows = [];

        foreach ($result as $row) {
            $identifier = '';

            if (!empty($row['property_number'])) {
                $identifier = $row['property_number'];
            } elseif (!empty($row['serial_num'])) {
                $identifier = $row['serial_num'];
            }

            if (!empty($row['ict_tag'])) {
                $identifier .= ($identifier ? ' - ' : '') . $row['ict_tag'];
            }

            $label = $row['device_type_name'] ?? '';
            if (!empty($identifier)) {
                $label .= ($label ? ' - ' : '') . $identifier;
            }

            $row['label'] = $label;
            $rows[] = $row;
        }

        return $this->respond(['inventory' => $rows]);
    }

    /**
     * ==========================================
     * Sync Inventory from Latest PMS
     * ==========================================
     */
    public function syncPms($encryptedId = null)
    {
        try {
            $id = decrypt_id($encryptedId);

            $inventory = $this->db->table('inventory')
                ->where('id', $id)
                ->where('deleted_at', null)
                ->get()
                ->getRowArray();

            if (! $inventory) {
                return $this->failNotFound('Inventory not found.');
            }

            $latestSchedule = $this->db->table('pms_schedules')
                ->where('deleted_at', null)
                ->orderBy('id', 'DESC')
                ->get()
                ->getRowArray();

            if (! $latestSchedule) {
                return $this->failValidationErrors('No PMS schedule found.');
            }

            $pmsRecord = $this->db->table('pms_records')
                ->where('deleted_at', null)
                ->where('inventory_id', $id)
                ->where('pms_schedule_id', $latestSchedule['id'])
                ->orderBy('updated_at', 'DESC')
                ->orderBy('id', 'DESC')
                ->get()
                ->getRowArray();

            if (! $pmsRecord) {
                return $this->failValidationErrors('No PMS record found for this inventory.');
            }

            $oldValues = [
                'section_code'  => $inventory['section_code'] ?? null,
                'division_code' => $inventory['division_code'] ?? null,
                'bldg'          => $inventory['bldg'] ?? null,
                'end_user'      => $inventory['end_user'] ?? null,
                'current_user'  => $inventory['current_user'] ?? null,
            ];

            $newValues = [
                'section_code'  => $pmsRecord['section_code'] ?? null,
                'division_code' => $pmsRecord['division_code'] ?? null,
                'bldg'          => $pmsRecord['bldg'] ?? null,
                'end_user'      => $pmsRecord['end_user'] ?? null,
                'current_user'  => $pmsRecord['current_user'] ?? null,
            ];

            if (! $this->hasLocationAssignmentChange($oldValues, $newValues)) {
                $updated = $this->db->table('inventory')
                    ->where('id', $id)
                    ->update([
                        'last_pms_schedule_synced_id' => $latestSchedule['id'],
                        'last_pms_synced_at'          => date('Y-m-d H:i:s'),
                        'updated_at'                  => date('Y-m-d H:i:s'),
                    ]);

                if (! $updated) {
                    return $this->failServerError('Failed to update sync status.');
                }

                return $this->respond([
                    'message' => 'Inventory already matches latest PMS values.',
                ]);
            }

            $this->db->transBegin();

            $updated = $this->db->table('inventory')
                ->where('id', $id)
                ->update([
                    ...$newValues,
                    'last_pms_schedule_synced_id' => $latestSchedule['id'],
                    'last_pms_synced_at'          => date('Y-m-d H:i:s'),
                    'updated_at'                  => date('Y-m-d H:i:s'),
                ]);

            if (! $updated) {
                throw new \RuntimeException('Failed to update inventory from PMS.');
            }

            $this->logLocationAssignmentHistory([
                'inventory_id'        => $id,
                'source_type'         => 'pms_sync',
                'source_reference_id' => $pmsRecord['id'],
                'pms_schedule_id'     => $latestSchedule['id'],
                'changed_by'          => (int) session('uid'),
                'old'                 => $oldValues,
                'new'                 => $newValues,
                'remarks'             => 'Synced from latest PMS record',
            ]);

            if ($this->db->transStatus() === false) {
                throw new \RuntimeException('Transaction failed during PMS sync.');
            }

            $this->db->transCommit();

            return $this->respond([
                'message' => 'Inventory location/assignment updated from PMS.',
            ]);
        } catch (\Throwable $e) {
            if ($this->db->transStatus() !== false) {
                $this->db->transRollback();
            }

            log_message('error', 'Sync PMS Error: ' . $e->getMessage());

            return $this->respond([
                'status'  => 500,
                'error'   => 500,
                'message' => 'Failed to sync inventory from PMS.',
                'details' => ENVIRONMENT === 'development' ? $e->getMessage() : null,
            ], 500);
        }
    }

    /**
     * ==========================================
     * Create Inventory
     * ==========================================
     */
    public function create()
    {
        try {
            $data = $this->request->getJSON(true) ?? [];

            if (! $this->validateData($data, [
                'device_type_id'  => 'required|integer',
                'property_number' => 'required|max_length[100]|min_length[2]',
                'year'            => 'permit_empty|integer',
                'section_code'    => 'required|max_length[150]',
                'division_code'   => 'required|max_length[150]',
                'bldg'            => 'required',
                'status'          => 'required|max_length[50]',
                'is_active'       => 'required|in_list[0,1]',
                'end_user'        => 'permit_empty|max_length[150]',
                'current_user'    => 'permit_empty|max_length[150]',
                'brand_name'      => 'permit_empty|max_length[150]',
                'serial_num'      => 'permit_empty|max_length[150]',
                'ict_tag'         => 'permit_empty|max_length[100]',
            ])) {
                return $this->respond([
                    'status'   => 422,
                    'error'    => 422,
                    'messages' => [
                        'error'  => 'Validation failed',
                        'fields' => $this->validator->getErrors(),
                    ],
                ], 422);
            }

            $insert = [
                'device_type_id'  => (int) $data['device_type_id'],
                'property_number' => $data['property_number'],
                'year'            => ! empty($data['year']) ? (int) $data['year'] : null,
                'section_code'    => $data['section_code'],
                'division_code'   => $data['division_code'],
                'bldg'            => $data['bldg'] ?? null,
                'end_user'        => $data['end_user'] ?? null,
                'current_user'    => $data['current_user'] ?? null,
                'brand_name'      => $data['brand_name'] ?? null,
                'serial_num'      => $data['serial_num'] ?? null,
                'ict_tag'         => $data['ict_tag'] ?? null,
                'status'          => $data['status'],
                'is_active'       => (int) $data['is_active'],
            ];

            $this->db->transBegin();

            $inserted = $this->inventory->insert($insert);
            if (! $inserted) {
                throw new \RuntimeException('Failed to create inventory item.');
            }

            $newId = (int) $this->inventory->getInsertID();

            $this->logLocationAssignmentHistory([
                'inventory_id' => $newId,
                'source_type'  => 'create',
                'changed_by'   => (int) session('uid'),
                'old' => [
                    'section_code' => null,
                    'division_code' => null,
                    'bldg' => null,
                    'end_user' => null,
                    'current_user' => null,
                ],
                'new' => [
                    'section_code' => $data['section_code'] ?? null,
                    'division_code' => $data['division_code'] ?? null,
                    'bldg' => $data['bldg'] ?? null,
                    'end_user' => $data['end_user'] ?? null,
                    'current_user' => $data['current_user'] ?? null,
                ],
                'remarks' => 'Initial location/assignment on inventory creation',
            ]);

            service('audit')->log('inventory.create', 'inventory', $newId, [
                'property_number' => $insert['property_number'],
                'serial_num'      => $insert['serial_num'],
                'status'          => $insert['status'],
                'is_active'       => $insert['is_active'],
            ]);

            if ($this->db->transStatus() === false) {
                throw new \RuntimeException('Transaction failed while creating inventory.');
            }

            $this->db->transCommit();

            return $this->respondCreated([
                'message' => 'Inventory item created',
                'id'      => $newId,
            ]);
        } catch (\Throwable $e) {
            if ($this->db->transStatus() !== false) {
                $this->db->transRollback();
            }

            log_message('error', 'Create Inventory Error: ' . $e->getMessage());

            return $this->respond([
                'status'  => 500,
                'error'   => 500,
                'message' => 'Something went wrong',
                'details' => ENVIRONMENT === 'development' ? $e->getMessage() : null,
            ], 500);
        }
    }

    /**
     * ==========================================
     * Update Inventory
     * ==========================================
     */
    public function update($id = null)
    {
        try {
            $id = decrypt_id($id);

            $item = $this->inventory->find($id);
            if (! $item) {
                return $this->failNotFound('Inventory item not found');
            }

            $data = $this->request->getJSON(true) ?? [];

            if (! $this->validateData($data, [
                'device_type_id'  => 'required|integer',
                'property_number' => 'required|max_length[100]',
                'year'            => 'permit_empty|integer',
                'status'          => 'required|max_length[50]',
                'is_active'       => 'required|in_list[0,1]',
                'brand_name'      => 'permit_empty|max_length[150]',
                'serial_num'      => 'permit_empty|max_length[150]',
                'ict_tag'         => 'permit_empty|max_length[100]',
            ])) {
                return $this->respond([
                    'status'   => 422,
                    'error'    => 422,
                    'messages' => [
                        'error'  => 'Validation failed',
                        'fields' => $this->validator->getErrors(),
                    ],
                ], 422);
            }

            $update = [
                'device_type_id'  => array_key_exists('device_type_id', $data) ? (int) $data['device_type_id'] : (int) ($item['device_type_id'] ?? 0),
                'property_number' => array_key_exists('property_number', $data) ? $data['property_number'] : ($item['property_number'] ?? null),
                'year'            => array_key_exists('year', $data) ? (! empty($data['year']) ? (int) $data['year'] : null) : ($item['year'] ?? null),
                'brand_name'      => array_key_exists('brand_name', $data) ? ($data['brand_name'] ?: null) : ($item['brand_name'] ?? null),
                'serial_num'      => array_key_exists('serial_num', $data) ? ($data['serial_num'] ?: null) : ($item['serial_num'] ?? null),
                'ict_tag'         => array_key_exists('ict_tag', $data) ? ($data['ict_tag'] ?: null) : ($item['ict_tag'] ?? null),
                'status'          => array_key_exists('status', $data) ? $data['status'] : ($item['status'] ?? null),
                'is_active'       => array_key_exists('is_active', $data) ? $data['is_active'] : ($item['is_active'] ?? null),
            ];

            $updated = $this->inventory->update($id, $update);
            if (! $updated) {
                return $this->failServerError('Failed to update inventory item.');
            }

            service('audit')->log('inventory.update', 'inventory', $id, [
                'fields' => array_keys($update),
                'before' => [
                    'device_type_id'  => $item['device_type_id'] ?? null,
                    'property_number' => $item['property_number'] ?? null,
                    'year'            => $item['year'] ?? null,
                    'brand_name'      => $item['brand_name'] ?? null,
                    'serial_num'      => $item['serial_num'] ?? null,
                    'ict_tag'         => $item['ict_tag'] ?? null,
                    'status'          => $item['status'] ?? null,
                    'is_active'       => $item['is_active'] ?? null,
                ],
                'after' => [
                    'device_type_id'  => $update['device_type_id'],
                    'property_number' => $update['property_number'],
                    'year'            => $update['year'],
                    'brand_name'      => $update['brand_name'],
                    'serial_num'      => $update['serial_num'],
                    'ict_tag'         => $update['ict_tag'],
                    'status'          => $update['status'],
                    'is_active'       => $item['is_active'],
                ],
            ]);

            return $this->respond(['message' => 'Inventory item updated']);
        } catch (\Throwable $e) {
            log_message('error', 'Update Inventory Error: ' . $e->getMessage());

            return $this->respond([
                'status'  => 500,
                'error'   => 500,
                'message' => 'Failed to update inventory item.',
                'details' => ENVIRONMENT === 'development' ? $e->getMessage() : null,
            ], 500);
        }
    }

    /**
     * ==========================================
     * Delete Inventory
     * ==========================================
     */
    public function delete($id = null)
    {
        try {
            $id = decrypt_id($id);

            $item = $this->inventory
                ->select('id, property_number, serial_num, status')
                ->find($id);

            if (! $item) {
                return $this->failNotFound('Inventory item not found');
            }

            $hasSoftwareHistory = $this->db->table('inventory_software_history')
                ->where('inventory_id', $id)
                ->countAllResults() > 0;

            if ($hasSoftwareHistory) {
                return $this->fail([
                    'message' => 'Inventory item cannot be deleted because it has software history assigned. Remove them first.',
                ], 409);
            }

            $deleted = $this->inventory->delete($id);
            if (! $deleted) {
                return $this->failServerError('Failed to delete inventory item.');
            }

            service('audit')->log('inventory.delete', 'inventory', $id, [
                'property_number' => $item['property_number'] ?? null,
                'serial_num'      => $item['serial_num'] ?? null,
                'status'          => $item['status'] ?? null,
            ]);

            return $this->respond(['message' => 'Inventory item deleted']);
        } catch (\Throwable $e) {
            log_message('error', 'Delete Inventory Error: ' . $e->getMessage());

            return $this->respond([
                'status'  => 500,
                'error'   => 500,
                'message' => 'Failed to delete inventory item.',
                'details' => ENVIRONMENT === 'development' ? $e->getMessage() : null,
            ], 500);
        }
    }

    /**
     * ==========================================
     * Update Inventory Location / Assignment
     * ==========================================
     */
    public function updateLocation($encryptedId = null)
    {
        try {
            $id = decrypt_id($encryptedId);

            $row = $this->db->table('inventory')
                ->where('id', $id)
                ->where('deleted_at', null)
                ->get()
                ->getRowArray();

            if (! $row) {
                return $this->failNotFound('Inventory not found.');
            }

            $payload = $this->request->getJSON(true) ?: $this->request->getRawInput();

            $newValues = [
                'section_code'  => $payload['section_code'] ?? null,
                'division_code' => $payload['division_code'] ?? null,
                'bldg'          => $payload['bldg'] ?? null,
                'end_user'      => $payload['end_user'] ?? null,
                'current_user'  => $payload['current_user'] ?? null,
            ];

            $oldValues = [
                'section_code'  => $row['section_code'] ?? null,
                'division_code' => $row['division_code'] ?? null,
                'bldg'          => $row['bldg'] ?? null,
                'end_user'      => $row['end_user'] ?? null,
                'current_user'  => $row['current_user'] ?? null,
            ];

            if (! $this->hasLocationAssignmentChange($oldValues, $newValues)) {
                return $this->respond([
                    'message' => 'No location/assignment changes detected.',
                ]);
            }

            $this->db->transBegin();

            $updated = $this->db->table('inventory')
                ->where('id', $id)
                ->update([
                    ...$newValues,
                    'updated_at' => date('Y-m-d H:i:s'),
                ]);

            if (! $updated) {
                throw new \RuntimeException('Failed to update location/assignment.');
            }

            $this->logLocationAssignmentHistory([
                'inventory_id' => $id,
                'source_type'  => 'manual_update',
                'changed_by'   => (int) session('uid'),
                'old'          => $oldValues,
                'new'          => $newValues,
                'remarks'      => 'Manual location/assignment update',
            ]);

            if ($this->db->transStatus() === false) {
                throw new \RuntimeException('Transaction failed during location update.');
            }

            $this->db->transCommit();

            return $this->respond([
                'message' => 'Location/assignment updated successfully.',
            ]);
        } catch (\Throwable $e) {
            if ($this->db->transStatus() !== false) {
                $this->db->transRollback();
            }

            log_message('error', 'Update Location Error: ' . $e->getMessage());

            return $this->respond([
                'status'  => 500,
                'error'   => 500,
                'message' => 'Failed to update location/assignment.',
                'details' => ENVIRONMENT === 'development' ? $e->getMessage() : null,
            ], 500);
        }
    }

    /**
     * ==========================================
     * Log Location Assignment History
     * ==========================================
     */
    private function logLocationAssignmentHistory(array $params): void
    {
        $inserted = $this->db->table('inventory_location_assignment_history')->insert([
            'inventory_id'        => $params['inventory_id'],
            'source_type'         => $params['source_type'],
            'source_reference_id' => $params['source_reference_id'] ?? null,
            'pms_schedule_id'     => $params['pms_schedule_id'] ?? null,
            'changed_by'          => $params['changed_by'] ?? null,

            'old_section_code'    => $params['old']['section_code'] ?? null,
            'old_division_code'   => $params['old']['division_code'] ?? null,
            'old_bldg'            => $params['old']['bldg'] ?? null,
            'old_end_user'        => $params['old']['end_user'] ?? null,
            'old_current_user'    => $params['old']['current_user'] ?? null,

            'new_section_code'    => $params['new']['section_code'] ?? null,
            'new_division_code'   => $params['new']['division_code'] ?? null,
            'new_bldg'            => $params['new']['bldg'] ?? null,
            'new_end_user'        => $params['new']['end_user'] ?? null,
            'new_current_user'    => $params['new']['current_user'] ?? null,

            'remarks'             => $params['remarks'] ?? null,
            'changed_at'          => date('Y-m-d H:i:s'),
            'created_at'          => date('Y-m-d H:i:s'),
            'updated_at'          => date('Y-m-d H:i:s'),
        ]);

        if (! $inserted) {
            throw new \RuntimeException('Failed to insert inventory location assignment history.');
        }
    }

    /**
     * ==========================================
     * Check Location Assignment Changes
     * ==========================================
     */
    private function hasLocationAssignmentChange(array $before, array $after): bool
    {
        return
            ($before['section_code'] ?? null) !== ($after['section_code'] ?? null) ||
            ($before['division_code'] ?? null) !== ($after['division_code'] ?? null) ||
            ($before['bldg'] ?? null) !== ($after['bldg'] ?? null) ||
            ($before['end_user'] ?? null) !== ($after['end_user'] ?? null) ||
            ($before['current_user'] ?? null) !== ($after['current_user'] ?? null);
    }
}
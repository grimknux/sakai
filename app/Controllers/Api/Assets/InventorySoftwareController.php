<?php

namespace App\Controllers\Api\Assets;

use App\Models\InventoryModel;
use CodeIgniter\Database\BaseConnection;
use CodeIgniter\RESTful\ResourceController;

class InventorySoftwareController extends ResourceController
{
    protected $format = 'json';

    protected BaseConnection $db;
    protected InventoryModel $inventory;

    /**
     * ==========================================
     * Constructor
     * ==========================================
     */
    public function __construct()
    {
        $this->db = \Config\Database::connect();
        $this->inventory = new InventoryModel();

        helper('security');
    }

    /**
     * ==========================================
     * List Latest Software Per Type by Inventory
     * ==========================================
     */
    public function index($inventoryId = null)
    {
        $inventoryId = decrypt_id($inventoryId);

        if (! $inventoryId) {
            return $this->fail('Invalid inventory ID');
        }

        $item = $this->inventory->find($inventoryId);

        if (! $item) {
            return $this->failNotFound('Inventory item not found');
        }

        $rows = $this->db->table('inventory_software_history ish')
            ->select('ish.*, s.name as software_name, s.software_type_id, s.version, st.name as software_type_name, lt.name as license_type_name')
            ->join('softwares s', 's.id = ish.software_id', 'left')
            ->join('software_types st', 'st.id = s.software_type_id', 'left')
            ->join('license_types lt', 'lt.id = ish.license_type', 'left')
            ->where('ish.inventory_id', $inventoryId)
            ->orderBy('s.software_type_id', 'ASC')
            ->orderBy('ish.effective_from', 'DESC')
            ->orderBy('ish.id', 'DESC')
            ->get()
            ->getResultArray();

        $latestPerType = [];

        foreach ($rows as $row) {
            $softwareTypeId = (int) ($row['software_type_id'] ?? 0);

            if ($softwareTypeId > 0 && ! isset($latestPerType[$softwareTypeId])) {
                $row['id'] = encrypt_id($row['id']);
                $row['inventory_id'] = encrypt_id($row['inventory_id']);
                $row['software_id'] = encrypt_id($row['software_id']);

                $latestPerType[$softwareTypeId] = $row;
            }
        }

        return $this->respond([
            'history' => array_values($latestPerType),
        ]);
    }

    /**
     * ==========================================
     * Create Inventory Software History
     * ==========================================
     */
    public function create($inventoryId = null)
    {
        try {
            $inventoryId = decrypt_id($inventoryId);

            $item = $this->inventory->find($inventoryId);
            if (! $item) {
                return $this->failNotFound('Inventory item not found');
            }

            $data = $this->request->getJSON(true) ?? [];

            $allowedLicenseTypes = array_map(
                'intval',
                array_column(
                    $this->db->table('license_types')->select('id')->get()->getResultArray(),
                    'id'
                )
            );

            if (! $this->validateData($data, [
                'software_id'    => 'required|integer',
                'license_key'    => 'permit_empty|max_length[255]',
                'license_type'   => 'required|integer',
                'effective_from' => 'required|valid_date',
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

            $errors = [];

            $softwareId    = (int) $data['software_id'];
            $licenseTypeId = (int) $data['license_type'];
            $effectiveFrom = $data['effective_from'];

            $software = $this->db->table('softwares')
                ->select('id, software_type_id, name')
                ->where('id', $softwareId)
                ->get()
                ->getRowArray();

            if (! $software) {
                $errors['software_id'] = 'Selected software is invalid.';
            }

            if (! in_array($licenseTypeId, $allowedLicenseTypes, true)) {
                $errors['license_type'] = 'Selected license type is invalid.';
            }

            $softwareTypeId = isset($software['software_type_id']) ? (int) $software['software_type_id'] : 0;

            if ($software && $softwareTypeId <= 0) {
                $errors['software_id'] = 'Selected software has no valid software type.';
            }

            $previous = null;

            if ($software && $softwareTypeId > 0) {
                $previous = $this->db->table('inventory_software_history ish')
                    ->select('ish.*, s.software_type_id, s.name as software_name')
                    ->join('softwares s', 's.id = ish.software_id', 'inner')
                    ->where('ish.inventory_id', $inventoryId)
                    ->where('s.software_type_id', $softwareTypeId)
                    ->orderBy('ish.effective_from', 'DESC')
                    ->orderBy('ish.id', 'DESC')
                    ->get()
                    ->getRowArray();
            }

            if ($previous && ! empty($previous['effective_from'])) {
                if (strtotime($effectiveFrom) <= strtotime($previous['effective_from'])) {
                    $errors['effective_from'] = 'Effective from must be later than the current latest software under the same software type.';
                }
            }

            if (! empty($errors)) {
                return $this->respond([
                    'status'   => 422,
                    'error'    => 422,
                    'messages' => [
                        'error'  => 'Validation failed',
                        'fields' => $errors,
                    ],
                ], 422);
            }

            $this->db->transBegin();

            if ($previous && ! empty($previous['effective_from'])) {
                $previousEffectiveTo = date('Y-m-d', strtotime($effectiveFrom . ' -1 day'));

                $updatedPrevious = $this->db->table('inventory_software_history')
                    ->where('id', (int) $previous['id'])
                    ->where('inventory_id', $inventoryId)
                    ->update([
                        'effective_to' => $previousEffectiveTo,
                        'updated_at'   => date('Y-m-d H:i:s'),
                    ]);

                if (! $updatedPrevious) {
                    throw new \RuntimeException('Failed to close previous software history record.');
                }

                service('audit')->log('inventory.software.auto_close', 'inventory_software_history', (int) $previous['id'], [
                    'inventory_id'             => $inventoryId,
                    'previous_software_id'     => (int) ($previous['software_id'] ?? 0),
                    'previous_software_name'   => $previous['software_name'] ?? null,
                    'software_type_id'         => $softwareTypeId,
                    'old_effective_to'         => $previous['effective_to'] ?? null,
                    'new_effective_to'         => $previousEffectiveTo,
                    'triggered_by_new_from'    => $effectiveFrom,
                    'triggered_by_software_id' => $softwareId,
                ]);
            }

            $payload = [
                'inventory_id'   => $inventoryId,
                'software_id'    => $softwareId,
                'license_key'    => $data['license_key'] ?? null,
                'license_type'   => $licenseTypeId,
                'effective_from' => $effectiveFrom,
                'effective_to'   => null,
                'created_at'     => date('Y-m-d H:i:s'),
                'updated_at'     => date('Y-m-d H:i:s'),
            ];

            $inserted = $this->db->table('inventory_software_history')->insert($payload);

            if (! $inserted) {
                throw new \RuntimeException('Failed to insert software history.');
            }

            $newId = (int) $this->db->insertID();

            service('audit')->log('inventory.software.create', 'inventory_software_history', $newId, [
                'inventory_id'     => $inventoryId,
                'software_id'      => $payload['software_id'],
                'software_type_id' => $softwareTypeId,
                'license_type'     => $payload['license_type'],
                'effective_from'   => $payload['effective_from'],
                'effective_to'     => $payload['effective_to'],
            ]);

            if ($this->db->transStatus() === false) {
                throw new \RuntimeException('Transaction failed while creating software history.');
            }

            $this->db->transCommit();

            return $this->respondCreated([
                'message' => 'Software added successfully.',
                'id'      => $newId,
            ]);
        } catch (\Throwable $e) {
            $this->db->transRollback();

            log_message('error', 'Create Inventory Software Error: ' . $e->getMessage());

            return $this->respond([
                'status'   => 500,
                'error'    => 500,
                'messages' => [
                    'error'   => 'Failed to add software.',
                    'details' => ENVIRONMENT === 'development' ? $e->getMessage() : null,
                ],
            ], 500);
        }
    }

    /**
     * ==========================================
     * Update Inventory Software History
     * ==========================================
     */
    public function update($inventoryId = null, $historyId = null)
    {
        try {
            $inventoryId = decrypt_id($inventoryId);
            $historyId = decrypt_id($historyId);

            $item = $this->inventory->find($inventoryId);
            if (! $item) {
                return $this->failNotFound('Inventory item not found');
            }

            $history = $this->db->table('inventory_software_history ish')
                ->select('ish.*, s.software_type_id')
                ->join('softwares s', 's.id = ish.software_id', 'inner')
                ->where('ish.id', $historyId)
                ->where('ish.inventory_id', $inventoryId)
                ->get()
                ->getRowArray();

            if (! $history) {
                return $this->failNotFound('Software history not found');
            }

            $data = $this->request->getJSON(true) ?? [];

            if (! $this->validateData($data, [
                'software_id'    => 'required|integer',
                'license_key'    => 'permit_empty|max_length[255]',
                'license_type'   => 'required|integer',
                'effective_from' => 'required|valid_date',
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

            $software = $this->db->table('softwares')
                ->select('id, software_type_id')
                ->where('id', (int) $data['software_id'])
                ->get()
                ->getRowArray();

            if (! $software) {
                return $this->respond([
                    'status'   => 422,
                    'error'    => 422,
                    'messages' => [
                        'error'  => 'Validation failed',
                        'fields' => [
                            'software_id' => 'Selected software is invalid.',
                        ],
                    ],
                ], 422);
            }

            $oldSoftwareTypeId = (int) $history['software_type_id'];
            $newSoftwareTypeId = (int) $software['software_type_id'];

            $payload = [
                'software_id'    => (int) $data['software_id'],
                'license_key'    => $data['license_key'] ?? null,
                'license_type'   => (int) $data['license_type'],
                'effective_from' => $data['effective_from'],
                'updated_at'     => date('Y-m-d H:i:s'),
            ];

            $this->db->transBegin();

            $updated = $this->db->table('inventory_software_history')
                ->where('id', $historyId)
                ->where('inventory_id', $inventoryId)
                ->update($payload);

            if (! $updated) {
                throw new \RuntimeException('Failed to update software history.');
            }

            $this->rebuildSoftwareTimeline($inventoryId, $oldSoftwareTypeId);

            if ($newSoftwareTypeId !== $oldSoftwareTypeId) {
                $this->rebuildSoftwareTimeline($inventoryId, $newSoftwareTypeId);
            }

            service('audit')->log('inventory.software.update', 'inventory_software_history', $historyId, [
                'fields' => array_keys($payload),
                'before' => [
                    'software_id'    => $history['software_id'] ?? null,
                    'license_key'    => $history['license_key'] ?? null,
                    'license_type'   => $history['license_type'] ?? null,
                    'effective_from' => $history['effective_from'] ?? null,
                ],
                'after' => [
                    'software_id'    => $payload['software_id'],
                    'license_key'    => $payload['license_key'],
                    'license_type'   => $payload['license_type'],
                    'effective_from' => $payload['effective_from'],
                ],
            ]);

            if ($this->db->transStatus() === false) {
                throw new \RuntimeException('Transaction failed while updating software.');
            }

            $this->db->transCommit();

            return $this->respond([
                'message' => 'Software updated successfully.',
            ]);
        } catch (\Throwable $e) {
            $this->db->transRollback();

            log_message('error', 'Update Inventory Software Error: ' . $e->getMessage());

            return $this->respond([
                'status'   => 500,
                'error'    => 500,
                'messages' => [
                    'error'   => 'Failed to update software.',
                    'details' => ENVIRONMENT === 'development' ? $e->getMessage() : null,
                ],
            ], 500);
        }
    }

    /**
     * ==========================================
     * Delete Inventory Software History
     * ==========================================
     */
    public function delete($inventoryId = null, $historyId = null)
    {
        try {
            $inventoryId = decrypt_id($inventoryId);
            $historyId = decrypt_id($historyId);

            $item = $this->inventory->find($inventoryId);
            if (! $item) {
                return $this->failNotFound('Inventory item not found');
            }

            $history = $this->db->table('inventory_software_history ish')
                ->select('ish.*, s.software_type_id')
                ->join('softwares s', 's.id = ish.software_id', 'inner')
                ->where('ish.id', $historyId)
                ->where('ish.inventory_id', $inventoryId)
                ->get()
                ->getRowArray();

            if (! $history) {
                return $this->failNotFound('Software history not found');
            }

            $softwareTypeId = (int) $history['software_type_id'];

            $this->db->transBegin();

            $deleted = $this->db->table('inventory_software_history')
                ->where('id', $historyId)
                ->where('inventory_id', $inventoryId)
                ->delete();

            if (! $deleted) {
                throw new \RuntimeException('Failed to delete software history.');
            }

            $this->rebuildSoftwareTimeline($inventoryId, $softwareTypeId);

            service('audit')->log('inventory.software.delete', 'inventory_software_history', $historyId, [
                'inventory_id'     => $inventoryId,
                'software_id'      => $history['software_id'] ?? null,
                'software_type_id' => $softwareTypeId,
                'effective_from'   => $history['effective_from'] ?? null,
                'effective_to'     => $history['effective_to'] ?? null,
            ]);

            if ($this->db->transStatus() === false) {
                throw new \RuntimeException('Transaction failed while deleting software.');
            }

            $this->db->transCommit();

            return $this->respond([
                'message' => 'Software deleted successfully.',
            ]);
        } catch (\Throwable $e) {
            $this->db->transRollback();

            log_message('error', 'Delete Inventory Software Error: ' . $e->getMessage());

            return $this->respond([
                'status'   => 500,
                'error'    => 500,
                'messages' => [
                    'error'   => 'Failed to delete software.',
                    'details' => ENVIRONMENT === 'development' ? $e->getMessage() : null,
                ],
            ], 500);
        }
    }

    /**
     * ==========================================
     * Rebuild Software Timeline by Software Type
     * ==========================================
     */
    private function rebuildSoftwareTimeline(int $inventoryId, int $softwareTypeId): void
    {
        $rows = $this->db->table('inventory_software_history ish')
            ->select('ish.id, ish.effective_from, ish.effective_to, ish.software_id, s.software_type_id')
            ->join('softwares s', 's.id = ish.software_id', 'inner')
            ->where('ish.inventory_id', $inventoryId)
            ->where('s.software_type_id', $softwareTypeId)
            ->orderBy('ish.effective_from', 'ASC')
            ->orderBy('ish.id', 'ASC')
            ->get()
            ->getResultArray();

        $count = count($rows);

        for ($i = 0; $i < $count; $i++) {
            $current = $rows[$i];
            $next = $rows[$i + 1] ?? null;

            $newEffectiveTo = null;

            if ($next && ! empty($next['effective_from'])) {
                $newEffectiveTo = date('Y-m-d', strtotime($next['effective_from'] . ' -1 day'));
            }

            $updated = $this->db->table('inventory_software_history')
                ->where('id', (int) $current['id'])
                ->update([
                    'effective_to' => $newEffectiveTo,
                    'updated_at'   => date('Y-m-d H:i:s'),
                ]);

            if (! $updated) {
                throw new \RuntimeException('Failed to rebuild software timeline.');
            }
        }
    }
}
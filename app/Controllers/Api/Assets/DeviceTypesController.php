<?php

namespace App\Controllers\Api\Assets;

use App\Models\DeviceTypeModel;
use CodeIgniter\Database\BaseConnection;
use CodeIgniter\RESTful\ResourceController;

class DeviceTypesController extends ResourceController
{
    protected $format = 'json';

    protected BaseConnection $db;
    protected DeviceTypeModel $deviceTypes;

    /**
     * ==========================================
     * Constructor
     * ==========================================
     */
    public function __construct()
    {
        $this->db = \Config\Database::connect();
        $this->deviceTypes = new DeviceTypeModel();

        helper('security');
    }

    /**
     * ==========================================
     * List Device Types
     * ==========================================
     */
    public function index()
    {
        $rows = $this->deviceTypes
            ->orderBy('id', 'ASC')
            ->findAll();

        $data = [];

        foreach ($rows as $row) {
            $data[] = [
                'id' => encrypt_id($row['id']),
                'name' => $row['name'],
                'description' => $row['description'],
                'created_at' => $row['created_at'],
                'updated_at' => $row['updated_at'],
            ];
        }

        return $this->respond(['device_types' => $data]);
    }

    /**
     * ==========================================
     * Device Types Dropdown List
     * ==========================================
     */
    public function dropdown()
    {
        $rows = $this->deviceTypes
            ->orderBy('id', 'ASC')
            ->findAll();

        return $this->respond(['device_types' => $rows]);
    }

    /**
     * ==========================================
     * Create Device Type
     * ==========================================
     */
    public function create()
    {
        try {
            $data = $this->request->getJSON(true) ?? [];

            if (! $this->validateData($data, [
                'name' => 'required|min_length[2]|max_length[100]',
            ])) {
                return $this->respond([
                    'status' => 422,
                    'error' => 422,
                    'messages' => [
                        'error' => 'Validation failed',
                        'fields' => $this->validator->getErrors(),
                    ],
                ], 422);
            }

            $newId = $this->deviceTypes->insert([
                'name'        => $data['name'],
                'description' => $data['description'] ?? '',
            ], true);

            if (! $newId) {
                throw new \RuntimeException('Failed to create device type.');
            }

            service('audit')->log('device_types.create', 'device_types', (int) $newId, [
                'name' => $data['name'],
            ]);

            return $this->respondCreated([
                'message' => 'Device type created',
                'id'      => (int) $newId,
            ]);
        } catch (\Throwable $e) {
            log_message('error', 'Create Device Type Error: ' . $e->getMessage());

            return $this->respond([
                'status'  => 500,
                'error'   => 500,
                'message' => 'Failed to create device type.',
                'details' => ENVIRONMENT === 'development' ? $e->getMessage() : null,
            ], 500);
        }
    }

    /**
     * ==========================================
     * Update Device Type
     * ==========================================
     */
    public function update($id = null)
    {
        try {
            $id = decrypt_id($id);

            $deviceType = $this->deviceTypes->find($id);
            if (! $deviceType) {
                return $this->failNotFound('Device type not found');
            }

            $data = $this->request->getJSON(true) ?? [];

            if (! $this->validateData($data, [
                'name' => 'required|min_length[2]|max_length[100]',
            ])) {
                return $this->respond([
                    'status' => 422,
                    'error' => 422,
                    'messages' => [
                        'error' => 'Validation failed',
                        'fields' => $this->validator->getErrors(),
                    ],
                ], 422);
            }

            $before = $deviceType;

            $updated = $this->deviceTypes->update($id, [
                'name'        => $data['name'],
                'description' => $data['description'] ?? '',
            ]);

            if (! $updated) {
                throw new \RuntimeException('Failed to update device type.');
            }

            service('audit')->log('device_types.update', 'device_types', $id, [
                'before' => [
                    'name' => $before['name'] ?? null,
                    'description' => $before['description'] ?? null,
                ],
                'after'  => [
                    'name' => $data['name'],
                    'description' => $data['description'] ?? '',
                ],
            ]);

            return $this->respond(['message' => 'Device type updated']);
        } catch (\Throwable $e) {
            log_message('error', 'Update Device Type Error: ' . $e->getMessage());

            return $this->respond([
                'status'  => 500,
                'error'   => 500,
                'message' => 'Failed to update device type.',
                'details' => ENVIRONMENT === 'development' ? $e->getMessage() : null,
            ], 500);
        }
    }

    /**
     * ==========================================
     * Delete Device Type
     * ==========================================
     */
    public function delete($id = null)
    {
        try {
            $id = decrypt_id($id);

            $deviceType = $this->deviceTypes->find($id);
            if (! $deviceType) {
                return $this->failNotFound('Device type not found');
            }

            $hasInventory = $this->db->table('inventory')
                ->where('device_type_id', $id)
                ->countAllResults() > 0;

            if ($hasInventory) {
                return $this->fail([
                    'message' => 'Device type cannot be deleted because it is assigned to inventory items. Remove or reassign them first.',
                ], 409);
            }

            $deleted = $this->deviceTypes->delete($id);

            if (! $deleted) {
                throw new \RuntimeException('Failed to delete device type.');
            }

            service('audit')->log('device_types.delete', 'device_types', $id, [
                'name' => $deviceType['name'] ?? null,
            ]);

            return $this->respond(['message' => 'Device type deleted']);
        } catch (\Throwable $e) {
            log_message('error', 'Delete Device Type Error: ' . $e->getMessage());

            return $this->respond([
                'status'  => 500,
                'error'   => 500,
                'message' => 'Failed to delete device type.',
                'details' => ENVIRONMENT === 'development' ? $e->getMessage() : null,
            ], 500);
        }
    }
}
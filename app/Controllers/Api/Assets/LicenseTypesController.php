<?php

namespace App\Controllers\Api\Assets;

use App\Models\LicenseTypeModel;
use CodeIgniter\Database\BaseConnection;
use CodeIgniter\RESTful\ResourceController;

class LicenseTypesController extends ResourceController
{
    protected $format = 'json';

    protected BaseConnection $db;
    protected LicenseTypeModel $licenseTypes;

    /**
     * ==========================================
     * Constructor
     * ==========================================
     */
    public function __construct()
    {
        $this->db = \Config\Database::connect();
        $this->licenseTypes = new LicenseTypeModel();

        helper('security');
    }

    /**
     * ==========================================
     * List License Types
     * ==========================================
     */
    public function index()
    {
        $rows = $this->licenseTypes
            ->orderBy('id', 'ASC')
            ->findAll();

        $rows = array_map(function ($row) {
            $row['id'] = encrypt_id($row['id']);
            return $row;
        }, $rows);

        return $this->respond(['license_types' => $rows]);
    }

    /**
     * ==========================================
     * License Types Dropdown List
     * ==========================================
     */
    public function dropdown()
    {
        $rows = $this->licenseTypes
            ->orderBy('id', 'ASC')
            ->findAll();

        return $this->respond(['license_types' => $rows]);
    }

    /**
     * ==========================================
     * Create License Type
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

            $newId = $this->licenseTypes->insert([
                'name' => $data['name'],
            ], true);

            if (! $newId) {
                throw new \RuntimeException('Failed to create license type.');
            }

            service('audit')->log('license_types.create', 'license_types', (int) $newId, [
                'name' => $data['name'],
            ]);

            return $this->respondCreated([
                'message' => 'License type created',
                'id'      => (int) $newId,
            ]);
        } catch (\Throwable $e) {
            log_message('error', 'Create License Type Error: ' . $e->getMessage());

            return $this->respond([
                'status'  => 500,
                'error'   => 500,
                'message' => 'Failed to create license type.',
                'details' => ENVIRONMENT === 'development' ? $e->getMessage() : null,
            ], 500);
        }
    }

    /**
     * ==========================================
     * Update License Type
     * ==========================================
     */
    public function update($id = null)
    {
        try {
            $id = decrypt_id($id);

            $licenseType = $this->licenseTypes->find($id);
            if (! $licenseType) {
                return $this->failNotFound('License type not found');
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

            $before = $licenseType;

            $updated = $this->licenseTypes->update($id, [
                'name' => $data['name'],
            ]);

            if (! $updated) {
                throw new \RuntimeException('Failed to update license type.');
            }

            service('audit')->log('license_types.update', 'license_types', $id, [
                'before' => [
                    'name' => $before['name'] ?? null,
                ],
                'after'  => [
                    'name' => $data['name'],
                ],
            ]);

            return $this->respond(['message' => 'License type updated']);
        } catch (\Throwable $e) {
            log_message('error', 'Update License Type Error: ' . $e->getMessage());

            return $this->respond([
                'status'  => 500,
                'error'   => 500,
                'message' => 'Failed to update license type.',
                'details' => ENVIRONMENT === 'development' ? $e->getMessage() : null,
            ], 500);
        }
    }

    /**
     * ==========================================
     * Delete License Type
     * ==========================================
     */
    public function delete($id = null)
    {
        try {
            $id = decrypt_id($id);

            $licenseType = $this->licenseTypes->find($id);
            if (! $licenseType) {
                return $this->failNotFound('License type not found');
            }

            $hasUsage = $this->db->table('inventory_software_history')
                ->where('license_type', $id)
                ->countAllResults() > 0;

            if ($hasUsage) {
                return $this->fail([
                    'message' => 'License type cannot be deleted because it is used in software history records.',
                ], 409);
            }

            $deleted = $this->licenseTypes->delete($id);

            if (! $deleted) {
                throw new \RuntimeException('Failed to delete license type.');
            }

            service('audit')->log('license_types.delete', 'license_types', $id, [
                'name' => $licenseType['name'] ?? null,
            ]);

            return $this->respond(['message' => 'License type deleted']);
        } catch (\Throwable $e) {
            log_message('error', 'Delete License Type Error: ' . $e->getMessage());

            return $this->respond([
                'status'  => 500,
                'error'   => 500,
                'message' => 'Failed to delete license type.',
                'details' => ENVIRONMENT === 'development' ? $e->getMessage() : null,
            ], 500);
        }
    }
}
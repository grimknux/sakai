<?php

namespace App\Controllers\Api\Assets;

use App\Models\SoftwareTypeModel;
use CodeIgniter\Database\BaseConnection;
use CodeIgniter\RESTful\ResourceController;

class SoftwareTypesController extends ResourceController
{
    protected $format = 'json';

    protected BaseConnection $db;
    protected SoftwareTypeModel $softwareTypes;

    /**
     * ==========================================
     * Constructor
     * ==========================================
     */
    public function __construct()
    {
        $this->db = \Config\Database::connect();
        $this->softwareTypes = new SoftwareTypeModel();

        helper('security');
    }

    /**
     * ==========================================
     * List Software Types
     * ==========================================
     */
    public function index()
    {
        $rows = $this->softwareTypes
            ->orderBy('id', 'ASC')
            ->findAll();

        $rows = array_map(function ($row) {
            $row['id'] = encrypt_id($row['id']);
            return $row;
        }, $rows);

        return $this->respond(['software_types' => $rows]);
    }

    /**
     * ==========================================
     * Software Types Dropdown List
     * ==========================================
     */
    public function dropdown()
    {
        $rows = $this->softwareTypes
            ->orderBy('id', 'ASC')
            ->findAll();

        return $this->respond(['software_types' => $rows]);
    }

    /**
     * ==========================================
     * Create Software Type
     * ==========================================
     */
    public function create()
    {
        try {
            $data = $this->request->getJSON(true) ?? [];

            if (! $this->validateData($data, [
                'name'      => 'required|min_length[2]|max_length[100]',
                'shortname' => 'required|min_length[1]|max_length[10]',
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

            $newId = $this->softwareTypes->insert([
                'name'      => $data['name'],
                'shortname' => $data['shortname'],
            ], true);

            if (! $newId) {
                throw new \RuntimeException('Failed to create software type.');
            }

            service('audit')->log('software_types.create', 'software_types', (int) $newId, [
                'name'      => $data['name'],
                'shortname' => $data['shortname'],
            ]);

            return $this->respondCreated([
                'message' => 'Software type created',
                'id'      => (int) $newId,
            ]);
        } catch (\Throwable $e) {
            log_message('error', 'Create Software Type Error: ' . $e->getMessage());

            return $this->respond([
                'status'  => 500,
                'error'   => 500,
                'message' => 'Failed to create software type.',
                'details' => ENVIRONMENT === 'development' ? $e->getMessage() : null,
            ], 500);
        }
    }

    /**
     * ==========================================
     * Update Software Type
     * ==========================================
     */
    public function update($id = null)
    {
        try {
            $id = decrypt_id($id);

            $softwareType = $this->softwareTypes->find($id);
            if (! $softwareType) {
                return $this->failNotFound('Software type not found');
            }

            $data = $this->request->getJSON(true) ?? [];

            if (! $this->validateData($data, [
                'name'      => 'required|min_length[2]|max_length[100]',
                'shortname' => 'required|min_length[1]|max_length[10]',
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

            $before = $softwareType;

            $updated = $this->softwareTypes->update($id, [
                'name'      => $data['name'],
                'shortname' => $data['shortname'],
            ]);

            if (! $updated) {
                throw new \RuntimeException('Failed to update software type.');
            }

            service('audit')->log('software_types.update', 'software_types', $id, [
                'before' => [
                    'name'      => $before['name'] ?? null,
                    'shortname' => $before['shortname'] ?? null,
                ],
                'after'  => [
                    'name'      => $data['name'],
                    'shortname' => $data['shortname'],
                ],
            ]);

            return $this->respond(['message' => 'Software type updated']);
        } catch (\Throwable $e) {
            log_message('error', 'Update Software Type Error: ' . $e->getMessage());

            return $this->respond([
                'status'  => 500,
                'error'   => 500,
                'message' => 'Failed to update software type.',
                'details' => ENVIRONMENT === 'development' ? $e->getMessage() : null,
            ], 500);
        }
    }

    /**
     * ==========================================
     * Delete Software Type
     * ==========================================
     */
    public function delete($id = null)
    {
        try {
            $id = decrypt_id($id);

            $softwareType = $this->softwareTypes->find($id);
            if (! $softwareType) {
                return $this->failNotFound('Software type not found');
            }

            $hasUsage = $this->db->table('softwares')
                ->where('software_type_id', $id)
                ->countAllResults() > 0;

            if ($hasUsage) {
                return $this->fail([
                    'message' => 'Software type cannot be deleted because it is used by software records.',
                ], 409);
            }

            $deleted = $this->softwareTypes->delete($id);

            if (! $deleted) {
                throw new \RuntimeException('Failed to delete software type.');
            }

            service('audit')->log('software_types.delete', 'software_types', $id, [
                'name' => $softwareType['name'] ?? null,
                'shortname' => $softwareType['shortname'] ?? null,
            ]);

            return $this->respond(['message' => 'Software type deleted']);
        } catch (\Throwable $e) {
            log_message('error', 'Delete Software Type Error: ' . $e->getMessage());

            return $this->respond([
                'status'  => 500,
                'error'   => 500,
                'message' => 'Failed to delete software type.',
                'details' => ENVIRONMENT === 'development' ? $e->getMessage() : null,
            ], 500);
        }
    }
}
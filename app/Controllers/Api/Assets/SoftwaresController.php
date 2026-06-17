<?php

namespace App\Controllers\Api\Assets;

use App\Models\SoftwareModel;
use CodeIgniter\Database\BaseConnection;
use CodeIgniter\RESTful\ResourceController;

class SoftwaresController extends ResourceController
{
    protected $format = 'json';

    protected BaseConnection $db;
    protected SoftwareModel $softwares;

    /**
     * ==========================================
     * Constructor
     * ==========================================
     */
    public function __construct()
    {
        $this->db = \Config\Database::connect();
        $this->softwares = new SoftwareModel();

        helper('security');
    }

    /**
     * ==========================================
     * List Softwares
     * ==========================================
     */
    public function index()
    {
        $rows = $this->softwares
            ->orderBy('name', 'ASC')
            ->findAll();

        $rows = array_map(function ($row) {
            $row['id'] = encrypt_id($row['id']);
            return $row;
        }, $rows);

        return $this->respond(['softwares' => $rows]);
    }

    /**
     * ==========================================
     * Softwares Dropdown List
     * ==========================================
     */
    public function dropdown()
    {
        $rows = $this->softwares
            ->select('softwares.id, softwares.name, softwares.version, software_types.shortname as software_type')
            ->join('software_types', 'software_types.id = softwares.software_type_id', 'left')
            ->orderBy('softwares.name', 'ASC')
            ->findAll();

        $result = [];

        foreach ($rows as $row) {
            $result[] = [
                'id' => $row['id'],
                'name' => $row['software_type'] . ' - ' . trim($row['name'] . ' ' . ($row['version'] ?? '')),
            ];
        }

        return $this->respond([
            'softwares' => $result,
        ]);
    }

    /**
     * ==========================================
     * Create Software
     * ==========================================
     */
    public function create()
    {
        try {
            $data = $this->request->getJSON(true) ?? [];

            if (! $this->validateData($data, [
                'software_type_id' => 'required|integer',
                'name'             => 'required|min_length[2]|max_length[150]',
                'version'          => 'permit_empty|max_length[100]',
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

            $newId = $this->softwares->insert([
                'software_type_id' => $data['software_type_id'],
                'name'             => $data['name'],
                'version'          => $data['version'] ?? null,
                'status'           => 1,
            ], true);

            if (! $newId) {
                throw new \RuntimeException('Failed to create software.');
            }

            service('audit')->log('softwares.create', 'softwares', (int) $newId, [
                'software_type_id' => $data['software_type_id'],
                'name'             => $data['name'],
                'version'          => $data['version'] ?? null,
                'status'           => 1,
            ]);

            return $this->respondCreated([
                'message' => 'Software created',
                'id'      => (int) $newId,
            ]);
        } catch (\Throwable $e) {
            log_message('error', 'Create Software Error: ' . $e->getMessage());

            return $this->respond([
                'status'  => 500,
                'error'   => 500,
                'message' => 'Failed to create software.',
                'details' => ENVIRONMENT === 'development' ? $e->getMessage() : null,
            ], 500);
        }
    }

    /**
     * ==========================================
     * Update Software
     * ==========================================
     */
    public function update($id = null)
    {
        try {
            $id = decrypt_id($id);

            $software = $this->softwares->find($id);
            if (! $software) {
                return $this->failNotFound('Software not found');
            }

            $data = $this->request->getJSON(true) ?? [];

            if (! $this->validateData($data, [
                'software_type_id' => 'required|integer',
                'name'             => 'required|min_length[2]|max_length[150]',
                'version'          => 'permit_empty|max_length[100]',
                'status'           => 'permit_empty|in_list[0,1]',
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

            $update = [
                'software_type_id' => $data['software_type_id'],
                'name'             => $data['name'],
                'version'          => $data['version'] ?? null,
            ];

            if (array_key_exists('status', $data)) {
                $update['status'] = (int) $data['status'];
            }

            $updated = $this->softwares->update($id, $update);

            if (! $updated) {
                throw new \RuntimeException('Failed to update software.');
            }

            service('audit')->log('softwares.update', 'softwares', $id, [
                'before' => [
                    'software_type_id' => $software['software_type_id'] ?? null,
                    'name'             => $software['name'] ?? null,
                    'version'          => $software['version'] ?? null,
                    'status'           => $software['status'] ?? null,
                ],
                'after' => [
                    'software_type_id' => $update['software_type_id'],
                    'name'             => $update['name'],
                    'version'          => $update['version'] ?? null,
                    'status'           => $update['status'] ?? ($software['status'] ?? null),
                ],
            ]);

            return $this->respond(['message' => 'Software updated']);
        } catch (\Throwable $e) {
            log_message('error', 'Update Software Error: ' . $e->getMessage());

            return $this->respond([
                'status'  => 500,
                'error'   => 500,
                'message' => 'Failed to update software.',
                'details' => ENVIRONMENT === 'development' ? $e->getMessage() : null,
            ], 500);
        }
    }

    /**
     * ==========================================
     * Delete Software
     * ==========================================
     */
    public function delete($id = null)
    {
        try {
            $id = decrypt_id($id);

            $software = $this->softwares->find($id);
            if (! $software) {
                return $this->failNotFound('Software not found');
            }

            $hasUsage = $this->db->table('inventory_software_history')
                ->where('software_id', $id)
                ->countAllResults() > 0;

            if ($hasUsage) {
                return $this->fail([
                    'message' => 'Software cannot be deleted because it is used in software history records.',
                ], 409);
            }

            $deleted = $this->softwares->delete($id);

            if (! $deleted) {
                throw new \RuntimeException('Failed to delete software.');
            }

            service('audit')->log('softwares.delete', 'softwares', $id, [
                'software_type_id' => $software['software_type_id'] ?? null,
                'name'             => $software['name'] ?? null,
                'version'          => $software['version'] ?? null,
                'status'           => $software['status'] ?? null,
            ]);

            return $this->respond(['message' => 'Software deleted']);
        } catch (\Throwable $e) {
            log_message('error', 'Delete Software Error: ' . $e->getMessage());

            return $this->respond([
                'status'  => 500,
                'error'   => 500,
                'message' => 'Failed to delete software.',
                'details' => ENVIRONMENT === 'development' ? $e->getMessage() : null,
            ], 500);
        }
    }
}
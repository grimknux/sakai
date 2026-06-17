<?php

namespace App\Controllers\Api\Admin\Organization;

use App\Models\BuildingModel;
use CodeIgniter\Database\BaseConnection;
use CodeIgniter\RESTful\ResourceController;

class BuildingsController extends ResourceController
{
    protected $format = 'json';

    protected BaseConnection $db;
    protected BuildingModel $buildings;

    /**
     * ==========================================
     * Constructor
     * ==========================================
     */
    public function __construct()
    {
        $this->db = \Config\Database::connect();
        $this->buildings = new BuildingModel();

        helper('security');
    }

    /**
     * ==========================================
     * List Buildings
     * ==========================================
     */
    public function index()
    {
        $rows = $this->buildings
            ->orderBy('id', 'ASC')
            ->findAll();

        $rows = array_map(function ($row) {
            $row['id'] = encrypt_id($row['id']);
            $row['status'] = (int) ($row['status'] ?? 0);
            return $row;
        }, $rows);

        return $this->respond(['buildings' => $rows]);
    }

    /**
     * ==========================================
     * Buildings Dropdown
     * ==========================================
     */
    public function dropdown()
    {
        $rows = $this->buildings
            ->where('status', 1)
            ->orderBy('id', 'ASC')
            ->findAll();

        $rows = array_map(function ($row) {
            $row['status'] = (int) ($row['status'] ?? 0);
            return $row;
        }, $rows);

        return $this->respond(['buildings' => $rows]);
    }

    /**
     * ==========================================
     * Create Building
     * ==========================================
     */
    public function create()
    {
        try {
            $data = $this->request->getJSON(true) ?? [];

            if (isset($data['status'])) {
                $data['status'] = (int) $data['status'];
            }

            if (! $this->validateData($data, [
                'name'   => 'required|min_length[1]|max_length[100]',
                'code'   => 'required|min_length[1]|max_length[50]',
                'status' => 'required|in_list[0,1]',
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

            $newId = $this->buildings->insert([
                'name'   => $data['name'],
                'code'   => $data['code'],
                'status' => (int) $data['status'],
            ], true);

            if (! $newId) {
                throw new \RuntimeException('Failed to create building.');
            }

            service('audit')->log('buildings.create', 'bldg', (int) $newId, [
                'name'   => $data['name'],
                'code'   => $data['code'],
                'status' => (int) $data['status'],
            ]);

            return $this->respondCreated([
                'message' => 'Building created',
                'id'      => (int) $newId,
            ]);
        } catch (\Throwable $e) {
            log_message('error', 'Create Building Error: ' . $e->getMessage());

            return $this->respond([
                'status'  => 500,
                'error'   => 500,
                'message' => 'Failed to create building.',
                'details' => ENVIRONMENT === 'development' ? $e->getMessage() : null,
            ], 500);
        }
    }

    /**
     * ==========================================
     * Update Building
     * ==========================================
     */
    public function update($id = null)
    {
        try {
            $id = decrypt_id($id);

            $building = $this->buildings->find($id);
            if (! $building) {
                return $this->failNotFound('Building not found');
            }

            $data = $this->request->getJSON(true) ?? [];

            if (isset($data['status'])) {
                $data['status'] = (int) $data['status'];
            }

            if (! $this->validateData($data, [
                'name'   => 'required|min_length[1]|max_length[100]',
                'code'   => 'required|min_length[1]|max_length[50]',
                'status' => 'required|in_list[0,1]',
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

            $before = $building;

            $updated = $this->buildings->update($id, [
                'name'   => $data['name'],
                'code'   => $data['code'],
                'status' => (int) $data['status'],
            ]);

            if (! $updated) {
                throw new \RuntimeException('Failed to update building.');
            }

            service('audit')->log('buildings.update', 'bldg', $id, [
                'before' => [
                    'name'   => $before['name'],
                    'code'   => $before['code'] ?? null,
                    'status' => (int) ($before['status'] ?? 0),
                ],
                'after' => [
                    'name'   => $data['name'],
                    'code'   => $data['code'],
                    'status' => (int) $data['status'],
                ],
            ]);

            return $this->respond(['message' => 'Building updated']);
        } catch (\Throwable $e) {
            log_message('error', 'Update Building Error: ' . $e->getMessage());

            return $this->respond([
                'status'  => 500,
                'error'   => 500,
                'message' => 'Failed to update building.',
                'details' => ENVIRONMENT === 'development' ? $e->getMessage() : null,
            ], 500);
        }
    }

    /**
     * ==========================================
     * Delete Building
     * ==========================================
     */
    public function delete($id = null)
    {
        try {
            $id = decrypt_id($id);

            $building = $this->buildings->find($id);
            if (! $building) {
                return $this->failNotFound('Building not found');
            }

            // Prevent delete if used by sections
            $hasUsage = $this->db
                ->table('section')
                ->groupStart()
                    ->where('bldg', $building['code'])
                    ->orWhere('bldg', $building['name'])
                ->groupEnd()
                ->countAllResults() > 0;

            if ($hasUsage) {
                return $this->fail([
                    'message' => 'Building cannot be deleted because it is used by section records.'
                ], 409);
            }

            $deleted = $this->buildings->delete($id);

            if (! $deleted) {
                throw new \RuntimeException('Failed to delete building.');
            }

            service('audit')->log('buildings.delete', 'bldg', $id, [
                'name'   => $building['name'] ?? null,
                'code'   => $building['code'] ?? null,
                'status' => isset($building['status']) ? (int) $building['status'] : null,
            ]);

            return $this->respond(['message' => 'Building deleted']);
        } catch (\Throwable $e) {
            log_message('error', 'Delete Building Error: ' . $e->getMessage());

            return $this->respond([
                'status'  => 500,
                'error'   => 500,
                'message' => 'Failed to delete building.',
                'details' => ENVIRONMENT === 'development' ? $e->getMessage() : null,
            ], 500);
        }
    }
}
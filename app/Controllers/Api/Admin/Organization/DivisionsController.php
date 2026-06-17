<?php

namespace App\Controllers\Api\Admin\Organization;

use App\Models\DivisionModel;
use CodeIgniter\Database\BaseConnection;
use CodeIgniter\RESTful\ResourceController;

class DivisionsController extends ResourceController
{
    protected $format = 'json';

    protected BaseConnection $db;
    protected DivisionModel $divisions;

    /**
     * ==========================================
     * Constructor
     * ==========================================
     */
    public function __construct()
    {
        $this->db = \Config\Database::connect();
        $this->divisions = new DivisionModel();

        helper('security');
    }

    /**
     * ==========================================
     * List Divisions
     * ==========================================
     */
    public function index()
    {
        $rows = $this->divisions
            ->orderBy('id', 'ASC')
            ->findAll();

        $rows = array_map(function ($row) {
            $row['id'] = encrypt_id($row['id']);
            $row['status'] = (int) ($row['status'] ?? 0);
            return $row;
        }, $rows);

        return $this->respond(['divisions' => $rows]);
    }

    /**
     * ==========================================
     * Divisions Dropdown List
     * ==========================================
     */
    public function dropdown()
    {
        $rows = $this->divisions
            ->where('status', 1)
            ->orderBy('id', 'ASC')
            ->findAll();

        $rows = array_map(function ($row) {
            $row['status'] = (int) ($row['status'] ?? 0);
            return $row;
        }, $rows);

        return $this->respond(['divisions' => $rows]);
    }

    /**
     * ==========================================
     * Create Division
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
                'name'   => 'required|min_length[2]|max_length[150]',
                'code'   => 'required|min_length[1]|max_length[50]|regex_match[/^\S+$/]',
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

            $newId = $this->divisions->insert([
                'name'   => $data['name'],
                'code'   => $data['code'],
                'status' => (int) $data['status'],
            ], true);

            if (! $newId) {
                throw new \RuntimeException('Failed to create division.');
            }

            service('audit')->log('divisions.create', 'division', (int) $newId, [
                'name'   => $data['name'],
                'code'   => $data['code'],
                'status' => (int) $data['status'],
            ]);

            return $this->respondCreated([
                'message' => 'Division created',
                'id'      => (int) $newId,
            ]);
        } catch (\Throwable $e) {
            log_message('error', 'Create Division Error: ' . $e->getMessage());

            return $this->respond([
                'status'  => 500,
                'error'   => 500,
                'message' => 'Failed to create division.',
                'details' => ENVIRONMENT === 'development' ? $e->getMessage() : null,
            ], 500);
        }
    }

    /**
     * ==========================================
     * Update Division
     * ==========================================
     */
    public function update($id = null)
    {
        try {
            $id = decrypt_id($id);

            $division = $this->divisions->find($id);
            if (! $division) {
                return $this->failNotFound('Division not found');
            }

            $data = $this->request->getJSON(true) ?? [];

            if (isset($data['status'])) {
                $data['status'] = (int) $data['status'];
            }

            if (! $this->validateData($data, [
                'name'   => 'required|min_length[2]|max_length[150]',
                'code'   => 'required|min_length[1]|max_length[50]|regex_match[/^\S+$/]',
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

            $before = $division;

            $updated = $this->divisions->update($id, [
                'name'   => $data['name'],
                'code'   => $data['code'],
                'status' => (int) $data['status'],
            ]);

            if (! $updated) {
                throw new \RuntimeException('Failed to update division.');
            }

            service('audit')->log('divisions.update', 'division', $id, [
                'before' => [
                    'name'   => $before['name'] ?? null,
                    'code'   => $before['code'] ?? null,
                    'status' => (int) ($before['status'] ?? 0),
                ],
                'after' => [
                    'name'   => $data['name'],
                    'code'   => $data['code'],
                    'status' => (int) $data['status'],
                ],
            ]);

            return $this->respond(['message' => 'Division updated']);
        } catch (\Throwable $e) {
            log_message('error', 'Update Division Error: ' . $e->getMessage());

            return $this->respond([
                'status'  => 500,
                'error'   => 500,
                'message' => 'Failed to update division.',
                'details' => ENVIRONMENT === 'development' ? $e->getMessage() : null,
            ], 500);
        }
    }

    /**
     * ==========================================
     * Delete Division
     * ==========================================
     */
    public function delete($id = null)
    {
        try {
            $id = decrypt_id($id);

            $division = $this->divisions->find($id);
            if (! $division) {
                return $this->failNotFound('Division not found');
            }

            $deleted = $this->divisions->delete($id);

            if (! $deleted) {
                throw new \RuntimeException('Failed to delete division.');
            }

            service('audit')->log('divisions.delete', 'division', $id, [
                'name'   => $division['name'] ?? null,
                'code'   => $division['code'] ?? null,
                'status' => isset($division['status']) ? (int) $division['status'] : null,
            ]);

            return $this->respond(['message' => 'Division deleted']);
        } catch (\Throwable $e) {
            log_message('error', 'Delete Division Error: ' . $e->getMessage());

            return $this->respond([
                'status'  => 500,
                'error'   => 500,
                'message' => 'Failed to delete division.',
                'details' => ENVIRONMENT === 'development' ? $e->getMessage() : null,
            ], 500);
        }
    }
}
<?php

namespace App\Controllers\Api\Admin\Organization;

use App\Models\SectionModel;
use CodeIgniter\Database\BaseConnection;
use CodeIgniter\RESTful\ResourceController;

class SectionsController extends ResourceController
{
    protected $format = 'json';

    protected BaseConnection $db;
    protected SectionModel $sections;

    /**
     * ==========================================
     * Constructor
     * ==========================================
     */
    public function __construct()
    {
        $this->db = \Config\Database::connect();
        $this->sections = new SectionModel();

        helper('security');
    }

    /**
     * ==========================================
     * List Sections
     * ==========================================
     */
    public function index()
    {
        $rows = $this->sections
            ->orderBy('name', 'ASC')
            ->findAll();

        $rows = array_map(function ($row) {
            $row['id'] = encrypt_id($row['id']);
            $row['status'] = (int) ($row['status'] ?? 0);
            return $row;
        }, $rows);

        return $this->respond(['sections' => $rows]);
    }

    /**
     * ==========================================
     * Sections Dropdown List
     * ==========================================
     */
    public function dropdown()
    {
        $rows = $this->sections
            ->where('status', 1)
            ->orderBy('id', 'ASC')
            ->findAll();

        $rows = array_map(function ($row) {
            $row['status'] = (int) ($row['status'] ?? 0);
            return $row;
        }, $rows);

        return $this->respond(['sections' => $rows]);
    }

    /**
     * ==========================================
     * Create Section
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
                'name'          => 'required|min_length[2]|max_length[150]',
                'code'          => 'required|min_length[1]|max_length[50]|regex_match[/^\S+$/]',
                'division_code' => 'required|min_length[1]|max_length[50]',
                'bldg'          => 'permit_empty|min_length[1]|max_length[20]',
                'status'        => 'required|in_list[0,1]',
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

            $newId = $this->sections->insert([
                'name'          => $data['name'],
                'code'          => $data['code'],
                'division_code' => $data['division_code'],
                'bldg'          => $data['bldg'] ?? null,
                'status'        => (int) $data['status'],
            ], true);

            if (! $newId) {
                throw new \RuntimeException('Failed to create section.');
            }

            service('audit')->log('sections.create', 'section', (int) $newId, [
                'name'          => $data['name'],
                'code'          => $data['code'],
                'division_code' => $data['division_code'],
                'bldg'          => $data['bldg'] ?? null,
                'status'        => (int) $data['status'],
            ]);

            return $this->respondCreated([
                'message' => 'Section created',
                'id'      => (int) $newId,
            ]);
        } catch (\Throwable $e) {
            log_message('error', 'Create Section Error: ' . $e->getMessage());

            return $this->respond([
                'status'  => 500,
                'error'   => 500,
                'message' => 'Failed to create section.',
                'details' => ENVIRONMENT === 'development' ? $e->getMessage() : null,
            ], 500);
        }
    }

    /**
     * ==========================================
     * Update Section
     * ==========================================
     */
    public function update($id = null)
    {
        try {
            $id = decrypt_id($id);

            $section = $this->sections->find($id);
            if (! $section) {
                return $this->failNotFound('Section not found');
            }

            $data = $this->request->getJSON(true) ?? [];

            if (isset($data['status'])) {
                $data['status'] = (int) $data['status'];
            }

            if (! $this->validateData($data, [
                'name'          => 'required|min_length[2]|max_length[150]',
                'code'          => 'required|min_length[1]|max_length[50]|regex_match[/^\S+$/]',
                'division_code' => 'required|min_length[1]|max_length[50]',
                'bldg'          => 'permit_empty|min_length[1]|max_length[20]',
                'status'        => 'required|in_list[0,1]',
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

            $before = $section;

            $updated = $this->sections->update($id, [
                'name'          => $data['name'],
                'code'          => $data['code'],
                'division_code' => $data['division_code'],
                'bldg'          => $data['bldg'] ?? null,
                'status'        => (int) $data['status'],
            ]);

            if (! $updated) {
                throw new \RuntimeException('Failed to update section.');
            }

            service('audit')->log('sections.update', 'section', $id, [
                'before' => [
                    'name'          => $before['name'] ?? null,
                    'code'          => $before['code'] ?? null,
                    'division_code' => $before['division_code'] ?? null,
                    'bldg'          => $before['bldg'] ?? null,
                    'status'        => (int) ($before['status'] ?? 0),
                ],
                'after' => [
                    'name'          => $data['name'],
                    'code'          => $data['code'],
                    'division_code' => $data['division_code'],
                    'bldg'          => $data['bldg'] ?? null,
                    'status'        => (int) $data['status'],
                ],
            ]);

            return $this->respond(['message' => 'Section updated']);
        } catch (\Throwable $e) {
            log_message('error', 'Update Section Error: ' . $e->getMessage());

            return $this->respond([
                'status'  => 500,
                'error'   => 500,
                'message' => 'Failed to update section.',
                'details' => ENVIRONMENT === 'development' ? $e->getMessage() : null,
            ], 500);
        }
    }

    /**
     * ==========================================
     * Delete Section
     * ==========================================
     */
    public function delete($id = null)
    {
        try {
            $id = decrypt_id($id);

            $section = $this->sections->find($id);
            if (! $section) {
                return $this->failNotFound('Section not found');
            }

            $deleted = $this->sections->delete($id);

            if (! $deleted) {
                throw new \RuntimeException('Failed to delete section.');
            }

            service('audit')->log('sections.delete', 'section', $id, [
                'name'   => $section['name'] ?? null,
                'code'   => $section['code'] ?? null,
                'status' => isset($section['status']) ? (int) $section['status'] : null,
            ]);

            return $this->respond(['message' => 'Section deleted']);
        } catch (\Throwable $e) {
            log_message('error', 'Delete Section Error: ' . $e->getMessage());

            return $this->respond([
                'status'  => 500,
                'error'   => 500,
                'message' => 'Failed to delete section.',
                'details' => ENVIRONMENT === 'development' ? $e->getMessage() : null,
            ], 500);
        }
    }
}
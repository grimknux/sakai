<?php

namespace App\Controllers\Api\Admin;

use App\Models\RoleModel;
use CodeIgniter\Database\BaseConnection;
use CodeIgniter\RESTful\ResourceController;

class RolesController extends ResourceController
{
    protected $format = 'json';

    protected BaseConnection $db;
    protected RoleModel $roles;

    /**
     * ==========================================
     * Constructor
     * ==========================================
     */
    public function __construct()
    {
        $this->db = \Config\Database::connect();
        $this->roles = new RoleModel();

        helper('security');
    }

    /**
     * ==========================================
     * List Roles
     * ==========================================
     */
    public function index()
    {
        $rows = $this->roles
            ->orderBy('id', 'ASC')
            ->findAll();

        $data = [];

        foreach ($rows as $row) {
            $data[] = [
                'id'          => encrypt_id($row['id']),
                'name'        => $row['name'],
                'slug'        => $row['slug'],
                'description' => $row['description'],
                'created_at'  => $row['created_at'],
            ];
        }

        return $this->respond(['roles' => $data]);
    }

    /**
     * ==========================================
     * Roles Dropdown List
     * ==========================================
     */
    public function dropdown()
    {
        $rows = $this->roles
            ->orderBy('id', 'ASC')
            ->findAll();

        return $this->respond(['roles' => $rows]);
    }

    /**
     * ==========================================
     * Create Role
     * ==========================================
     */
    public function create()
    {
        try {
            $data = $this->request->getJSON(true) ?? [];

            if (! $this->validateData($data, [
                'name' => 'required|min_length[2]|max_length[100]',
                'slug' => 'required|min_length[2]|max_length[100]',
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

            $newId = $this->roles->insert([
                'name'        => $data['name'],
                'slug'        => $data['slug'],
                'description' => $data['description'] ?? '',
            ], true);

            if (! $newId) {
                throw new \RuntimeException('Failed to create role.');
            }

            service('audit')->log('roles.create', 'roles', (int) $newId, [
                'name' => $data['name'],
                'slug' => $data['slug'],
            ]);

            return $this->respondCreated([
                'message' => 'Role created',
                'id'      => (int) $newId,
            ]);
        } catch (\Throwable $e) {
            log_message('error', 'Create Role Error: ' . $e->getMessage());

            return $this->respond([
                'status'  => 500,
                'error'   => 500,
                'message' => 'Failed to create role.',
                'details' => ENVIRONMENT === 'development' ? $e->getMessage() : null,
            ], 500);
        }
    }

    /**
     * ==========================================
     * Update Role
     * ==========================================
     */
    public function update($id = null)
    {
        try {
            $id = decrypt_id($id);

            $role = $this->roles->find($id);
            if (! $role) {
                return $this->failNotFound('Role not found');
            }

            if (($role['slug'] ?? '') === 'super-admin') {
                return $this->failForbidden('Super Admin role cannot be edited');
            }

            $data = $this->request->getJSON(true) ?? [];

            if (! $this->validateData($data, [
                'name' => 'required|min_length[2]|max_length[100]',
                'slug' => 'required|min_length[2]|max_length[100]',
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

            $before = $role;

            $updated = $this->roles->update($id, [
                'name'        => $data['name'],
                'slug'        => $data['slug'],
                'description' => $data['description'] ?? '',
            ]);

            if (! $updated) {
                throw new \RuntimeException('Failed to update role.');
            }

            service('audit')->log('roles.update', 'roles', $id, [
                'before' => [
                    'name'        => $before['name'] ?? null,
                    'slug'        => $before['slug'] ?? null,
                    'description' => $before['description'] ?? null,
                ],
                'after'  => [
                    'name'        => $data['name'],
                    'slug'        => $data['slug'],
                    'description' => $data['description'] ?? '',
                ],
            ]);

            return $this->respond(['message' => 'Role updated']);
        } catch (\Throwable $e) {
            log_message('error', 'Update Role Error: ' . $e->getMessage());

            return $this->respond([
                'status'  => 500,
                'error'   => 500,
                'message' => 'Failed to update role.',
                'details' => ENVIRONMENT === 'development' ? $e->getMessage() : null,
            ], 500);
        }
    }

    /**
     * ==========================================
     * Delete Role
     * ==========================================
     */
    public function delete($id = null)
    {
        try {
            $id = decrypt_id($id);

            $role = $this->roles->find($id);
            if (! $role) {
                return $this->failNotFound('Role not found');
            }

            if (($role['slug'] ?? '') === 'super-admin') {
                return $this->failForbidden('Super Admin role cannot be deleted');
            }

            $hasPermissions = $this->db->table('role_permissions')
                ->where('role_id', $id)
                ->countAllResults() > 0;

            if ($hasPermissions) {
                return $this->fail([
                    'message' => 'Role cannot be deleted because it has permissions assigned. Remove permissions first.',
                ], 409);
            }

            $hasUsers = $this->db->table('user_roles')
                ->where('role_id', $id)
                ->countAllResults() > 0;

            if ($hasUsers) {
                return $this->fail([
                    'message' => 'Role cannot be deleted because it is assigned to users. Unassign it first.',
                ], 409);
            }

            $deleted = $this->roles->delete($id);

            if (! $deleted) {
                throw new \RuntimeException('Failed to delete role.');
            }

            service('audit')->log('roles.delete', 'roles', $id, [
                'slug' => $role['slug'] ?? null,
                'name' => $role['name'] ?? null,
            ]);

            return $this->respond(['message' => 'Role deleted']);
        } catch (\Throwable $e) {
            log_message('error', 'Delete Role Error: ' . $e->getMessage());

            return $this->respond([
                'status'  => 500,
                'error'   => 500,
                'message' => 'Failed to delete role.',
                'details' => ENVIRONMENT === 'development' ? $e->getMessage() : null,
            ], 500);
        }
    }
}
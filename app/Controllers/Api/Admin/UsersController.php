<?php

namespace App\Controllers\Api\Admin;

use App\Models\UserModel;
use CodeIgniter\Database\BaseConnection;
use CodeIgniter\RESTful\ResourceController;

class UsersController extends ResourceController
{
    protected $format = 'json';

    protected BaseConnection $db;
    protected UserModel $users;

    /**
     * ==========================================
     * Constructor
     * ==========================================
     */
    public function __construct()
    {
        $this->db = \Config\Database::connect();
        $this->users = new UserModel();

        helper('security');
    }

    /**
     * ==========================================
     * List Users
     * ==========================================
     */
    public function index()
    {
        $rows = $this->users
            ->where('deleted_at', null)
            ->orderBy('id', 'DESC')
            ->findAll();

        $data = [];

        foreach ($rows as $row) {
            $data[] = [
                'id'            => encrypt_id($row['id']),
                'username'      => $row['username'],
                'email'         => $row['email'],
                'firstname'     => $row['firstname'],
                'lastname'      => $row['lastname'],
                'middlename'    => $row['middlename'],
                'suffix'        => $row['suffix'],
                'is_active'     => $row['is_active'],
                'is_superadmin' => $row['is_superadmin'],
                'locked_until'  => $row['locked_until'],
                'created_at'    => $row['created_at'],
            ];
        }

        return $this->respond(['users' => $data]);
    }

    /**
     * ==========================================
     * Create User
     * ==========================================
     */
    public function create()
    {
        try {
            $data = $this->request->getJSON(true) ?? [];

            if (! $this->validateData($data, [
                'username'  => 'required|min_length[3]|max_length[50]',
                'password'  => 'required|min_length[6]|max_length[72]',
                'email'     => 'required|valid_email|max_length[150]',
                'firstname' => 'required|max_length[100]',
                'lastname'  => 'required|max_length[100]',
                'middlename'  => 'permit_empty|max_length[100]',
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
                'username'      => $data['username'],
                'email'         => $data['email'] ?? null,
                'password_hash' => password_hash($data['password'], PASSWORD_ARGON2ID),
                'firstname'     => $data['firstname'] ?? '',
                'lastname'      => $data['lastname'] ?? '',
                'middlename'      => $data['middlename'] ?? '',
                'is_active'     => 1,
                'is_superadmin' => 0,
            ];

            $this->db->transBegin();

            $inserted = $this->users->insert($insert);

            if (! $inserted) {
                throw new \RuntimeException('Failed to create user.');
            }

            $newId = (int) $this->users->getInsertID();

            service('audit')->log('users.create', 'users', $newId, [
                'username' => $insert['username'],
                'email'    => $insert['email'],
            ]);

            if ($this->db->transStatus() === false) {
                throw new \RuntimeException('Transaction failed while creating user.');
            }

            $this->db->transCommit();

            return $this->respondCreated([
                'message' => 'User created',
                'id'      => $newId,
            ]);
        } catch (\Throwable $e) {
            $this->db->transRollback();

            log_message('error', 'Create User Error: ' . $e->getMessage());

            return $this->respond([
                'status'  => 500,
                'error'   => 500,
                'message' => 'Failed to create user.',
                'details' => ENVIRONMENT === 'development' ? $e->getMessage() : null,
            ], 500);
        }
    }

    /**
     * ==========================================
     * Update User
     * ==========================================
     */
    public function update($id = null)
    {
        try {
            $id = decrypt_id($id);

            $user = $this->users->find($id);
            if (! $user) {
                return $this->failNotFound('User not found');
            }

            $data = $this->request->getJSON(true) ?? [];

            if (! $this->validateData($data, [
                'email'     => 'required|valid_email|max_length[150]',
                'firstname' => 'required|max_length[100]',
                'lastname'  => 'required|max_length[100]',
                'middlename'  => 'permit_empty|max_length[100]',
                'is_active' => 'permit_empty|in_list[0,1]',
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
                'email'     => array_key_exists('email', $data) ? ($data['email'] ?: null) : ($user['email'] ?? null),
                'firstname' => array_key_exists('firstname', $data) ? ($data['firstname'] ?? '') : ($user['firstname'] ?? ''),
                'lastname'  => array_key_exists('lastname', $data) ? ($data['lastname'] ?? '') : ($user['lastname'] ?? ''),
                'middlename'  => array_key_exists('middlename', $data) ? ($data['middlename'] ?? '') : ($user['middlename'] ?? ''),
            ];

            if (array_key_exists('is_active', $data)) {
                $update['is_active'] = (int) $data['is_active'];
            }

            $updated = $this->users->update($id, $update);

            if (! $updated) {
                throw new \RuntimeException('Failed to update user.');
            }

            service('audit')->log('users.update', 'users', $id, [
                'fields' => array_keys($update),
                'before' => [
                    'email'     => $user['email'] ?? null,
                    'firstname' => $user['firstname'] ?? null,
                    'lastname'  => $user['lastname'] ?? null,
                    'middlename'  => $user['middlename'] ?? null,
                    'is_active' => $user['is_active'] ?? null,
                ],
                'after' => [
                    'email'     => $update['email'] ?? null,
                    'firstname' => $update['firstname'] ?? null,
                    'lastname'  => $update['lastname'] ?? null,
                    'middlename'  => $update['middlename'] ?? null,
                    'is_active' => $update['is_active'] ?? ($user['is_active'] ?? null),
                ],
            ]);

            return $this->respond(['message' => 'User updated']);
        } catch (\Throwable $e) {
            log_message('error', 'Update User Error: ' . $e->getMessage());

            return $this->respond([
                'status'  => 500,
                'error'   => 500,
                'message' => 'Failed to update user.',
                'details' => ENVIRONMENT === 'development' ? $e->getMessage() : null,
            ], 500);
        }
    }

    /**
     * ==========================================
     * Delete User
     * ==========================================
     */
    public function delete($id = null)
    {
        try {
            $id = decrypt_id($id);

            $currentUid = (int) session('uid');
            if ($currentUid === $id) {
                return $this->failForbidden('You cannot delete your own account');
            }

            $user = $this->users
                ->select('id, username, email, is_superadmin')
                ->find($id);

            if (! $user) {
                return $this->failNotFound('User not found');
            }

            if ((int) ($user['is_superadmin'] ?? 0) === 1) {
                return $this->failForbidden('Super admin accounts cannot be deleted');
            }

            $hasRoles = $this->db->table('user_roles')
                ->where('user_id', $id)
                ->countAllResults() > 0;

            if ($hasRoles) {
                return $this->fail([
                    'message' => 'User cannot be deleted because it has roles assigned. Unassign them first.',
                ], 409);
            }

            $this->db->transBegin();

            $deleted = $this->users->delete($id);

            if (! $deleted) {
                throw new \RuntimeException('Failed to delete user.');
            }

            $deactivated = $this->users->update($id, ['is_active' => 0]);

            if (! $deactivated) {
                throw new \RuntimeException('Failed to deactivate deleted user.');
            }

            service('audit')->log('users.delete', 'users', $id, [
                'username' => $user['username'] ?? null,
                'email'    => $user['email'] ?? null,
            ]);

            if ($this->db->transStatus() === false) {
                throw new \RuntimeException('Transaction failed while deleting user.');
            }

            $this->db->transCommit();

            return $this->respond(['message' => 'User deleted']);
        } catch (\Throwable $e) {
            $this->db->transRollback();

            log_message('error', 'Delete User Error: ' . $e->getMessage());

            return $this->respond([
                'status'  => 500,
                'error'   => 500,
                'message' => 'Failed to delete user.',
                'details' => ENVIRONMENT === 'development' ? $e->getMessage() : null,
            ], 500);
        }
    }

    /**
     * ==========================================
     * Get User Roles
     * ==========================================
     */
    public function roles($id = null)
    {
        $id = decrypt_id($id);

        $roleRows = $this->db->table('roles')
            ->select('id, name, slug')
            ->orderBy('name', 'ASC')
            ->get()
            ->getResultArray();

        $assigned = $this->db->table('user_roles')
            ->select('role_id')
            ->where('user_id', $id)
            ->get()
            ->getResultArray();

        $assignedIds = array_map(fn($r) => (int) $r['role_id'], $assigned);

        return $this->respond([
            'roles' => $roleRows,
            'assigned_role_ids' => $assignedIds,
        ]);
    }

    /**
     * ==========================================
     * Set User Roles
     * ==========================================
     */
    public function setRoles($id = null)
    {
        try {
            $id = decrypt_id($id);
            $data = $this->request->getJSON(true) ?? [];

            $user = $this->users->find($id);
            if (! $user) {
                return $this->failNotFound('User not found');
            }

            $roleIds = $data['role_ids'] ?? [];

            if (! is_array($roleIds)) {
                return $this->failValidationErrors('role_ids must be an array');
            }

            $roleIds = array_values(array_unique(array_map('intval', $roleIds)));

            if (! empty($roleIds)) {
                $validRoleIds = $this->db->table('roles')
                    ->select('id')
                    ->whereIn('id', $roleIds)
                    ->get()
                    ->getResultArray();

                $validRoleIds = array_map(static fn($row) => (int) $row['id'], $validRoleIds);

                foreach ($roleIds as $roleId) {
                    if (! in_array($roleId, $validRoleIds, true)) {
                        return $this->respond([
                            'status'   => 422,
                            'error'    => 422,
                            'messages' => [
                                'error'  => 'Validation failed',
                                'fields' => [
                                    'role_ids' => 'One or more selected roles are invalid.',
                                ],
                            ],
                        ], 422);
                    }
                }
            }

            $this->db->transBegin();

            $deleted = $this->db->table('user_roles')
                ->where('user_id', $id)
                ->delete();

            if ($deleted === false) {
                throw new \RuntimeException('Failed to clear existing user roles.');
            }

            foreach ($roleIds as $rid) {
                $inserted = $this->db->table('user_roles')->insert([
                    'user_id' => $id,
                    'role_id' => $rid,
                ]);

                if (! $inserted) {
                    throw new \RuntimeException('Failed to assign user role.');
                }
            }

            service('audit')->log('users.roles.assign', 'users', $id, [
                'role_ids' => $roleIds,
            ]);

            if ($this->db->transStatus() === false) {
                throw new \RuntimeException('Transaction failed while updating user roles.');
            }

            $this->db->transCommit();

            return $this->respond(['message' => 'User roles updated']);
        } catch (\Throwable $e) {
            $this->db->transRollback();

            log_message('error', 'Set User Roles Error: ' . $e->getMessage());

            return $this->respond([
                'status'  => 500,
                'error'   => 500,
                'message' => 'Failed to update user roles.',
                'details' => ENVIRONMENT === 'development' ? $e->getMessage() : null,
            ], 500);
        }
    }

    /**
     * ==========================================
     * Set Superadmin Flag
     * ==========================================
     */
    public function setSuperadmin($id = null)
    {
        try {
            $id = decrypt_id($id);

            $currentUid = (int) session('uid');
            $data = $this->request->getJSON(true) ?? [];
            $isSuper = (int) ($data['is_superadmin'] ?? 0);

            $user = $this->users->find($id);
            if (! $user) {
                return $this->failNotFound('User not found');
            }

            if ($currentUid === $id && $isSuper === 0) {
                return $this->failForbidden('You cannot remove your own superadmin privileges');
            }

            $updated = $this->users->update($id, ['is_superadmin' => $isSuper]);

            if (! $updated) {
                throw new \RuntimeException('Failed to update superadmin flag.');
            }

            service('audit')->log('users.superadmin.toggle', 'users', $id, [
                'is_superadmin' => $isSuper,
            ]);

            return $this->respond(['message' => 'Superadmin flag updated']);
        } catch (\Throwable $e) {
            log_message('error', 'Set Superadmin Error: ' . $e->getMessage());

            return $this->respond([
                'status'  => 500,
                'error'   => 500,
                'message' => 'Failed to update superadmin flag.',
                'details' => ENVIRONMENT === 'development' ? $e->getMessage() : null,
            ], 500);
        }
    }
}
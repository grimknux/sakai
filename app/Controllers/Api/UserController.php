<?php

namespace App\Controllers\Api;

use App\Models\UserModel;
use CodeIgniter\Database\BaseConnection;
use CodeIgniter\RESTful\ResourceController;

class UserController extends ResourceController
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
    }

    /**
     * ==========================================
     * Get Current Authenticated User
     * ==========================================
     */
    public function me()
    {
        $uid = (int) session('uid');

        if (! $uid) {
            return $this->failUnauthorized();
        }

        $user = $this->users
            ->select('id, username, email, firstname, lastname, is_superadmin')
            ->find($uid);

        if (! $user) {
            return $this->failNotFound();
        }

        $permissions = service('permission')->getUserPermissions($uid);

        $roles = $this->db->table('user_roles ur')
            ->select('r.slug')
            ->join('roles r', 'r.id = ur.role_id')
            ->where('ur.user_id', $uid)
            ->get()
            ->getResultArray();

        $roles = array_map(fn($r) => $r['slug'], $roles);

        return $this->respond([
            'user' => $user,
            'permissions' => $permissions,
            'roles' => $roles,
        ]);
    }
}
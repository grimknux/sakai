<?php

namespace App\Controllers\Api\Admin;

use CodeIgniter\Database\BaseConnection;
use CodeIgniter\RESTful\ResourceController;

class PermissionsController extends ResourceController
{
    protected $format = 'json';

    protected BaseConnection $db;

    /**
     * ==========================================
     * Constructor
     * ==========================================
     */
    public function __construct()
    {
        $this->db = \Config\Database::connect();

        helper('security');
    }

    /**
     * ==========================================
     * List Permissions
     * ==========================================
     */
    public function index()
    {
        $rows = $this->db->table('permissions')
            ->orderBy('module', 'ASC')
            ->orderBy('id', 'ASC')
            ->get()
            ->getResultArray();

        return $this->respond(['permissions' => $rows]);
    }
}
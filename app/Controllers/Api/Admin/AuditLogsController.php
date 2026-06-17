<?php

namespace App\Controllers\Api\Admin;

use CodeIgniter\Database\BaseConnection;
use CodeIgniter\RESTful\ResourceController;

class AuditLogsController extends ResourceController
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
     * List Audit Logs
     * ==========================================
     */
    public function index()
    {
        $rows = $this->db->table('audit_logs')
            ->orderBy('id', 'DESC')
            ->limit(500)
            ->get()
            ->getResultArray();

        $data = [];

        foreach ($rows as $row) {
            $data[] = [
                'id'             => encrypt_id($row['id']),
                'actor_user_id'  => $row['actor_user_id'],
                'actor_username' => $row['actor_username'],
                'ip_address'     => $row['ip_address'],
                'user_agent'     => $row['user_agent'],
                'action'         => $row['action'],
                'entity'         => $row['entity'],
                'entity_id'      => $row['entity_id'],
                'meta'           => $row['meta'],
                'created_at'     => $row['created_at'],
            ];
        }

        return $this->respond(['logs' => $data]);
    }
}
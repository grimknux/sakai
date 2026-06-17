<?php

namespace App\Controllers\Api\Admin;

use CodeIgniter\Database\BaseConnection;
use CodeIgniter\RESTful\ResourceController;

class SecurityController extends ResourceController
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
     * List Login Attempts
     * ==========================================
     */
    public function attempts()
    {
        $rows = $this->db->table('login_attempts la')
            ->select([
                'la.id',
                'la.user_id',
                'la.username',
                'la.ip_address',
                'la.user_agent',
                'la.success',
                'la.reason',
                'la.attempted_at',
                'u.locked_until',
                'CASE WHEN u.locked_until IS NOT NULL AND u.locked_until > NOW() THEN 1 ELSE 0 END AS is_locked',
            ])
            ->join('users u', 'u.id = la.user_id', 'left')
            ->orderBy('la.id', 'DESC')
            ->limit(500)
            ->get()
            ->getResultArray();

        $data = [];

        foreach ($rows as $row) {
            $data[] = [
                'id'           => encrypt_id($row['id']),
                'user_id'      => ! empty($row['user_id']) ? encrypt_id($row['user_id']) : null,
                'username'     => $row['username'],
                'ip_address'   => $row['ip_address'],
                'user_agent'   => $row['user_agent'],
                'success'      => $row['success'],
                'reason'       => $row['reason'],
                'attempted_at' => $row['attempted_at'],
                'locked_until' => $row['locked_until'],
                'is_locked'    => $row['is_locked'],
            ];
        }

        return $this->respond(['attempts' => $data]);
    }

    /**
     * ==========================================
     * Unlock User Account
     * ==========================================
     */
    public function unlock($userId = null)
    {
        try {
            $userId = decrypt_id($userId);

            if (! $userId || ! is_numeric($userId)) {
                return $this->failValidationErrors('Invalid user ID.');
            }

            $user = $this->db->table('users')
                ->select('id, username, locked_until')
                ->where('id', $userId)
                ->get()
                ->getRowArray();

            if (! $user) {
                return $this->failNotFound('User not found');
            }

            $updated = $this->db->table('users')
                ->where('id', $userId)
                ->update([
                    'locked_until' => null,
                    'updated_at'   => date('Y-m-d H:i:s'),
                ]);

            if (! $updated) {
                throw new \RuntimeException('Failed to unlock user.');
            }

            service('audit')->log('auth.unlock', 'users', $userId, [
                'by'       => session('username'),
                'username' => $user['username'] ?? null,
            ]);

            return $this->respond(['message' => 'User unlocked']);
        } catch (\Throwable $e) {
            log_message('error', 'Unlock User Error: ' . $e->getMessage());

            return $this->respond([
                'status'  => 500,
                'error'   => 500,
                'message' => 'Failed to unlock user.',
                'details' => ENVIRONMENT === 'development' ? $e->getMessage() : null,
            ], 500);
        }
    }
}
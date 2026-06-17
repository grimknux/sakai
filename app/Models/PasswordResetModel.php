<?php

namespace App\Models;

use CodeIgniter\Model;

class PasswordResetModel extends Model
{
    protected $table            = 'password_resets';
    protected $primaryKey       = 'id';
    protected $returnType       = 'array';
    protected $allowedFields    = [
        'user_id','token_hash','expires_at','used_at','ip_address','user_agent','created_at'
    ];

    public $useTimestamps       = false;
    
}
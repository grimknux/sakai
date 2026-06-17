<?php

namespace App\Models;

use CodeIgniter\Model;

class UserModel extends Model
{
    protected $table = 'users';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $allowedFields = [
        'username','email','password_hash',
        'firstname','lastname','middlename',
        'suffix','is_active','is_superadmin', 'locked_until'
    ];

    protected $useSoftDeletes = true;
    protected $useTimestamps = true;
}
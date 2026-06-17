<?php

namespace App\Models;

use CodeIgniter\Model;

class InventorySoftwareModel extends Model
{
    protected $table = 'inventory_software_history';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $allowedFields = [
        'inventory_id','software_id','license_key',
        'license_type','effective_from','effective_to',
        'created_at', 'updated_at', 'deleted_at',
    ];

    protected $useTimestamps = true;
}
<?php

namespace App\Models;

use CodeIgniter\Model;

class BuildingModel extends Model
{
    protected $table = 'bldg';
    protected $primaryKey = 'id';
    protected $returnType = 'array';
    protected $allowedFields = [
        'name',
        'code',
        'status',
        'created_at',
    ];
}
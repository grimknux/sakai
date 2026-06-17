<?php

namespace App\Models;

use CodeIgniter\Model;

class LicenseTypeModel extends Model
{
    protected $table = 'license_types';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $allowedFields = ['name','created_at'];
}
<?php

namespace App\Models;

use CodeIgniter\Model;

class DeviceTypeModel extends Model
{
    protected $table = 'device_types';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $allowedFields = ['name','description','created_at','updated_at','deleted_at'];

    protected $useTimestamps = true;
}
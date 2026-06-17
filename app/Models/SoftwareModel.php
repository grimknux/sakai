<?php

namespace App\Models;

use CodeIgniter\Model;

class SoftwareModel extends Model
{
    protected $table = 'softwares';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $allowedFields = ['software_type_id', 'name', 'version', 'status','created_at', 'updated_at', 'deleted_at'];

    protected $useTimestamps = true;
    protected $useSoftDeletes = true;

}
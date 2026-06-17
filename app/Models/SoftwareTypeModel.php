<?php

namespace App\Models;

use CodeIgniter\Model;

class SoftwareTypeModel extends Model
{
    protected $table = 'software_types';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $allowedFields = ['name', 'shortname', 'created_at'];

}
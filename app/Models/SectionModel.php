<?php

namespace App\Models;

use CodeIgniter\Model;

class SectionModel extends Model
{
    protected $table = 'section';
    protected $primaryKey = 'id';
    protected $returnType = 'array';
    protected $allowedFields = [
        'name',
        'shortname',
        'code',
        'division_code',
        'bldg',
        'status',
        'created_at',
        'updated_at',
        'deleted_at'
    ];

    protected $useSoftDeletes = true;
    protected $useTimestamps = true;
}
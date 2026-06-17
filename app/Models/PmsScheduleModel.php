<?php

namespace App\Models;

use CodeIgniter\Model;

class PmsScheduleModel extends Model
{
    protected $table          = 'pms_schedules';
    protected $primaryKey     = 'id';
    protected $returnType     = 'array';
    protected $useSoftDeletes = true;
    protected $useTimestamps  = true;

    protected $allowedFields = [
        'year',
        'semester',
        'schedule_start',
        'schedule_end',
        'attachment_name',
        'attachment_path',
        'remarks',
        'created_by',
    ];
}
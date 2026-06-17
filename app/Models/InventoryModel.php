<?php

namespace App\Models;

use CodeIgniter\Model;

class InventoryModel extends Model
{
    protected $table = 'inventory';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $allowedFields = [
        'device_type_id','property_number','year', 'section_code',
        'division_code','bldg','end_user','current_user','brand_name',
        'serial_num','ict_tag','status', 'is_active',
        'created_at', 'updated_at', 'deleted_at', 'last_pms_schedule_synced_id', 'last_pms_synced_at',
    ];

    protected $useSoftDeletes = true;
    protected $useTimestamps = true;
}
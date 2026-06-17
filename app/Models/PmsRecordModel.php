<?php

namespace App\Models;

use CodeIgniter\Model;

class PmsRecordModel extends Model
{
    protected $table          = 'pms_records';
    protected $primaryKey     = 'id';
    protected $returnType     = 'array';

    protected $allowedFields = [
        'pms_schedule_id',
        'inventory_id',
        'conducted_by',
        'conducted_date',
        'section_code',
        'division_code',
        'bldg',
        'end_user',
        'current_user',
        'remarks',
        'findings',
        'status',
    ];

    
    protected $useSoftDeletes = true;
    protected $useTimestamps  = true;

    public function getPdfDetails(int $id): ?array
    {
        return $this->db->table($this->table . ' pr')
            ->select([
                'pr.id',
                'pr.section_code',
                's.name as section_name',
                's.shortname',
                'pr.division_code',
                'pr.findings',
                'pr.status',
                'pr.conducted_date',
                'pr.created_at',
                'pr.end_user',
                'inv.ict_tag',
                'u.firstname',
                'u.middlename',
                'u.lastname',
                'u.suffix',
            ])
            ->join('inventory inv', 'inv.id = pr.inventory_id', 'left')
            ->join('section s', 's.code = pr.section_code', 'left')
            ->join('users u', 'u.id = pr.conducted_by', 'left')
            ->where('pr.id', $id)
            ->where('pr.deleted_at', null)
            ->get()
            ->getRowArray();
    }
}
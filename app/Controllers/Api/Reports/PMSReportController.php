<?php

namespace App\Controllers\Api\Reports;

use App\Models\PmsRecordModel;
use CodeIgniter\Database\BaseConnection;
use CodeIgniter\RESTful\ResourceController;

class PMSReportController extends ResourceController
{
    protected $format = 'json';

    protected BaseConnection $db;
    protected PmsRecordModel $records;

    /**
     * ==========================================
     * Constructor
     * ==========================================
     */
    public function __construct()
    {
        $this->db = \Config\Database::connect();
        $this->records = new PmsRecordModel();

        helper('security');
    }

    /**
     * ==========================================
     * PMS Records Report
     * ==========================================
     */
    public function pms_record_view()
    {
        $year = $this->request->getGet('year');
        $semester = $this->request->getGet('semester');
        $deviceType = $this->request->getGet('deviceType');

        $builder = $this->records
            ->select('
                pms_records.id,
                pms_records.pms_schedule_id,
                pms_records.inventory_id,
                pms_records.conducted_by,
                pms_records.conducted_date,
                pms_records.section_code,
                pms_records.division_code,
                pms_records.bldg,
                pms_records.end_user,
                pms_records.current_user,
                pms_records.remarks,
                pms_records.findings,
                pms_records.status,
                pms_records.created_at,
                pms_records.updated_at,
                ps.year,
                ps.semester,
                ps.schedule_start,
                ps.schedule_end,
                i.property_number,
                i.serial_num,
                i.brand_name,
                d.name as device_type,
                d.id as device_type_id,
                u.firstname,
                u.lastname,
                u.middlename,
                u.suffix
            ')
            ->join('pms_schedules ps', 'ps.id = pms_records.pms_schedule_id', 'left')
            ->join('inventory i', 'i.id = pms_records.inventory_id', 'left')
            ->join('device_types d', 'd.id = i.device_type_id', 'left')
            ->join('users u', 'u.id = pms_records.conducted_by', 'left')
            ->where('pms_records.deleted_at', null);

        if (!empty($year)) {
            $builder->where('ps.year', $year);
        }

        if (!empty($semester) && $semester !== 'all') {
            $builder->where('ps.semester', strtolower($semester));
        }
        
        if (!empty($deviceType) && $deviceType !== 'all') {
            $builder->where('d.id', $deviceType);
        }

        $rows = $builder
            ->orderBy('d.name', 'ASC')
            ->orderBy('pms_records.division_code', 'ASC')
            ->orderBy('pms_records.section_code', 'ASC')
            ->findAll();

        $rows = array_map(function ($row, $index) {
            $row['cnt'] = $index + 1;
            $firstname  = $row['firstname'] ?? '';
            $middlename = $row['middlename'] ?? '';
            $lastname   = $row['lastname'] ?? '';
            $suffix     = $row['suffix'] ?? '';

            $middleInitial = '';
            if (!empty($middlename) && strtoupper($middlename) !== 'N/A') {
                $middleInitial = strtoupper(substr(trim($middlename), 0, 1)) . '.';
            }

            $nameParts = array_filter([
                $firstname,
                $middleInitial,
                $lastname
            ]);

            $name = implode(' ', $nameParts);

            if (!empty($suffix) && strtoupper($suffix) !== 'N/A') {
                $name .= ', ' . $suffix;
            }

            $name = ucwords(strtolower($name));

            $row['conducted_by_name'] = $name;
            $row['id'] = encrypt_id($row['id']);

            return $row;
        }, $rows, array_keys($rows));

        return $this->respond([
            'records' => $rows,
            'filters' => [
                'year' => $year,
                'semester' => $semester
            ]
        ]);
    }

}
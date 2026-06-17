<?php

namespace App\Controllers\Api\Reports\PDF;

use App\Models\PmsRecordModel;
use CodeIgniter\Database\BaseConnection;
use CodeIgniter\RESTful\ResourceController;
use TCPDF;

class PMSReport extends ResourceController
{
    protected $format = 'json';

    protected BaseConnection $db;
    protected PmsRecordModel $records;

    public function __construct()
    {
        $this->db = \Config\Database::connect();
        $this->records = new PmsRecordModel();

        helper('security');
    }

    /**
     * ==========================================
     * PMS Records Report PDF
     * ==========================================
     */
    public function pdf()
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
                ps.year as pms_year,
                ps.semester,
                ps.schedule_start,
                ps.schedule_end,
                i.property_number,
                i.serial_num,
                i.brand_name,
                i.year,
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

        if (!empty($semester)) {
            $builder->where('ps.semester', strtolower($semester));
        }

        if (!empty($deviceType)) {
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
            $inv_year = $row['year'] ?? '';
            $conducted_date = $row['conducted_date'] ?? '';

            // Check if year is 4 digits and date is valid
            if (preg_match('/^\d{4}$/', $inv_year) && !empty($conducted_date) && strtotime($conducted_date) !== false) {
                $current_year = date('Y', strtotime($conducted_date));
                $diff = $current_year - $inv_year;
            } else {
                $diff = "N/A";
            }

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
            $row['years'] = $diff;
            $row['id'] = encrypt_id($row['id']);

            return $row;
        }, $rows, array_keys($rows));

        // Report title values
        $reportYear = '';
        $reportSemester = '';
        $reportDateRange = '';

        if (!empty($rows)) {
            $firstRow = $rows[0];

            $reportYear = $firstRow['pms_year'] ?? '';
            $reportSemester = ucfirst(strtolower($firstRow['semester'] ?? ''));

            $scheduleStart = !empty($firstRow['schedule_start'])
                ? date('F d, Y', strtotime($firstRow['schedule_start']))
                : '';

            $scheduleEnd = !empty($firstRow['schedule_end'])
                ? date('F d, Y', strtotime($firstRow['schedule_end']))
                : '';

            if ($scheduleStart && $scheduleEnd) {
                $reportDateRange = $scheduleStart . ' - ' . $scheduleEnd;
            }
        } else {
            $reportYear = $year ?: '';
            $reportSemester = !empty($semester) ? ucfirst(strtolower($semester)) : '';
        }

        $pdf = new MYPDF('L', 'mm', array(330.2, 215.9), true, 'UTF-8', false);

        $pdf->SetCreator(PDF_CREATOR);
        $pdf->SetAuthor('System');
        $pdf->SetTitle('Preventive Maintenance Report');
        $pdf->SetSubject('Preventive Maintenance Report');

        // Enable custom header/footer
        $pdf->setPrintHeader(true);
        $pdf->setPrintFooter(true);

        // IMPORTANT: margins adjusted for custom header/footer
        $pdf->SetMargins(10, 30, 10);
        $pdf->SetHeaderMargin(5);
        $pdf->SetFooterMargin(12);
        $pdf->SetAutoPageBreak(true, 28);

        $pdf->customHeaderTitle = 'Preventive Maintenance Report';

        $pdf->AddPage();

        // Body Title
        $pdf->Ln(2);

        $pdf->SetFont('helvetica', 'B', 12);
        $pdf->Cell(0, 6, 'Preventive Maintenance Report', 0, 1, 'C');

        $pdf->SetFont('helvetica', 'B', 11);
        $semesterLine = trim(
            $reportYear .
            (!empty($reportSemester) ? ' - ' . $reportSemester . ' Semester' : '')
        );
        $pdf->Cell(0, 6, $semesterLine, 0, 1, 'C');

        if (!empty($reportDateRange)) {
            $pdf->SetFont('helvetica', '', 10);
            $pdf->Cell(0, 6, $reportDateRange, 0, 1, 'C');
        }

        $pdf->Ln(4);

        // Table HTML
        $html = '
            <table border="1" cellpadding="3">
                <thead>
                    <tr style="font-weight:bold; background-color:#f2f2f2; text-align:center;">
                        <th width="23">#</th>
                        <th width="65">Type</th>
                        <th width="85">Property No.</th>
                        <th width="85">Serial No.</th>
                        <th width="60">Division</th>
                        <th width="80">Section</th>
                        <th width="95">End User</th>
                        <th width="70">Findings</th>
                        <th width="70">Status</th>
                        <th width="70">Remarks</th>
                        <th width="32">Age<br><small><i>(in years)</i></small></th>
                        <th width="60">Date</th>
                        <th width="85">Conducted By</th>
                    </tr>
                </thead>
                <tbody>
        ';

        if (!empty($rows)) {
            foreach ($rows as $row) {
                $conductedDate = !empty($row['conducted_date'])
                    ? date('M. d, Y', strtotime($row['conducted_date']))
                    : (!empty($row['created_at']) ? date('M. d, Y', strtotime($row['created_at'])) : '');

                $html .= '
                    <tr>
                        <td width="23" align="center">' . htmlspecialchars((string) ($row['cnt'] ?? '')) . '</td>
                        <td width="65">' . htmlspecialchars((string) ($row['device_type'] ?? '')) . '</td>
                        <td width="85">' . htmlspecialchars((string) ($row['property_number'] ?? '')) . '</td>
                        <td width="85">' . htmlspecialchars((string) ($row['serial_num'] ?? '')) . '</td>
                        <td width="60">' . htmlspecialchars((string) ($row['division_code'] ?? '')) . '</td>
                        <td width="80">' . htmlspecialchars((string) ($row['section_code'] ?? '')) . '</td>
                        <td width="95">' . htmlspecialchars((string) ($row['end_user'] ?? '')) . '</td>
                        <td width="70">' . htmlspecialchars((string) ($row['findings'] ?? '')) . '</td>
                        <td width="70">' . htmlspecialchars((string) ($row['status'] ?? '')) . '</td>
                        <td width="70">' . htmlspecialchars((string) ($row['remarks'] ?? '')) . '</td>
                        <td width="32" align="center">' . htmlspecialchars((string) ($row['years'] ?? '')) . '</td>
                        <td width="60" align="center">' . htmlspecialchars($conductedDate) . '</td>
                        <td width="85">' . htmlspecialchars((string) ($row['conducted_by_name'] ?? '')) . '</td>
                    </tr>
                ';
            }
        } else {
            $html .= '
                <tr>
                    <td colspan="13" align="center">No records found.</td>
                </tr>
            ';
        }

        $html .= '
                </tbody>
            </table>
        ';

        $pdf->SetFont('helvetica', '', 8.5);
        $pdf->writeHTML($html, true, false, true, false, '');

        // Clean any accidental output before sending PDF
        if (ob_get_length()) {
            ob_end_clean();
        }

        $pdf->Output('preventive-maintenance-report.pdf', 'I');
        exit;
    }
}

class MYPDF extends TCPDF
{
    public string $customHeaderTitle = '';

    public function Header()
    {
        if ($this->getPage() === 1) {
            $this->SetY(8);

            $this->SetFont('times', '', 10);
            $this->Cell(0, 5, 'Republic of the Philippines', 0, 1, 'C');

            $this->SetFont('times', '', 12);
            $this->Cell(0, 5, 'DEPARTMENT OF HEALTH', 0, 1, 'C');

            $this->SetFont('times', 'B', 14);
            $this->Cell(0, 6, 'ILOCOS CENTER FOR HEALTH DEVELOPMENT', 0, 1, 'C');

            $leftLogo = FCPATH . 'assets/img/dohlogo_border.png';
            if (is_file($leftLogo)) {
                $this->Image($leftLogo, 60, 7, 23, 23);
            }

            $rightLogo = FCPATH . 'assets/img/bagong_pilipinas.png';
            if (is_file($rightLogo)) {
                $this->Image($rightLogo, 245, 7, 23, 23);
            }
        } else {
            $this->SetY(8);
            $this->SetFont('times', 'I', 11);
            $this->MultiCell(0, 6, $this->customHeaderTitle, 0, 'R', 0, 1, '', '', true);
        }
    }

    public function Footer()
    {
        if ($this->getPage() === 1) {
            $this->SetY(-24);
            $this->SetFont('times', '', 8);

            $html = '
                <div style="text-align:center;">
                    <b>___________________________________________________________________________________________________________</b><br/>
                    McArthur Highway, Brgy. Parian, City of San Fernando, La Union 2500 Philippines<br/>
                    Trunkline No. (072) 607-6413<br/>
                    Facsimile No. (072) 242-4774; (072) 242-5981<br/>
                    Email Address: rd@ilocos.doh.gov.ph | Website: ro1.doh.gov.ph
                </div>
            ';

            $this->writeHTML($html, true, false, true, false, '');
        } else {
            $this->SetY(-14);
            $this->SetFont('times', '', 8);
            $this->Cell(
                0,
                10,
                'Page ' . $this->getAliasNumPage() . ' of ' . $this->getAliasNbPages(),
                0,
                0,
                'R'
            );
        }
    }
}
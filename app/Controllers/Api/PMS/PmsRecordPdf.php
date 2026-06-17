<?php

namespace App\Controllers\Api\PMS;


use CodeIgniter\RESTful\ResourceController;
use CodeIgniter\Database\BaseConnection;

use TCPDF;

use App\Models\PmsRecordModel;
use App\Models\PmsQuestionModel;
use App\Models\PmsRecordAnswerModel;

class PmsRecordPdf extends ResourceController
{
    
    protected $pmsrecord_model;
    protected $question_model;
    protected $answer_model;
    protected BaseConnection $db;

    public function __construct()
    {
        $this->pmsrecord_model = new PmsRecordModel();
        $this->question_model = new PmsQuestionModel();
        $this->answer_model = new PmsRecordAnswerModel();
        $this->db = \Config\Database::connect();


        helper(['security','html']);
        
    }

    public function index($id = null)
    {

        $pms_id = decrypt_id($id);
        
        $record = $this->pmsrecord_model->getPdfDetails((int) $pms_id);
        $rows = $this->question_model->getQuestionsWithAnswers((int) $pms_id);
        
        $division = "";
        if($record['section_code'] == 'RLED'){
            $division = $record['section_name'];
        }else{
            $division = $record['division_code'].' - '.$record['shortname'];
        }

        $first  = $record['firstname'] ?? '';
        $middle = $record['middlename'] ?? '';
        $last   = $record['lastname'] ?? '';
        $suffix = $record['suffix'] ?? '';
        $conducted_by = $first;
        if (!empty($middle)) {
            $conducted_by .= ' ' . strtoupper(substr($middle, 0, 1)) . '.';
        }

        $conducted_by .= ' ' . $last;

        if (!empty($suffix)) {
            $conducted_by .= ', ' . $suffix;
        }

        $conducted_date = !empty($record['conducted_date']) ? date('M. d, Y', strtotime($record['conducted_date'])) : "";

        $end_user = $record['end_user'];

        $pdf = new MYPDF('P', 'mm', 'A4', true, 'UTF-8', false);

        $pdf->SetCreator('CI4');
        $pdf->SetAuthor('System');
        $pdf->SetTitle('PMS Record');

        $pdf->SetHeaderMargin(15);
        $pdf->SetFooterMargin(0);
        $pdf->setPrintFooter(false);

        $pdf->SetFont('calibri', '', 12);
        $pdf->SetAutoPageBreak(true, 1);
        $pdf->SetMargins(14, 48, 14, 1);
        $pdf->AddPage();

        $pdf->SetFont('calibri', 'b', 11);
        $pdf->Cell(0, 5, 'PREVENTIVE AND MAINTENANCE MEASURES', 0, 1, 'C' );
        $pdf->Ln(2);

        // Column widths
        $wNoCol    = 10;
        $wQuestion = 82;
        $wYes      = 21;
        $wNo       = 21;
        $wRemarks  = 48;

        $this->drawTableHeader($pdf, $wNoCol, $wQuestion, $wYes, $wNo, $wRemarks);

        
        $pdf->SetFont('calibri', '', 11);

        foreach ($rows as $index => $row) {
            $question = trim($row['question_text_snapshot'] ?: $row['question_text'] ?: '');
            $answer   = strtolower(trim($row['answer'] ?? ''));
            $remarks  = trim($row['remarks'] ?? '');

            if ($question === '') {
                $question = 'N/A';
            }

            $lineHeight = 5;

            // include number column in layout, but line count is only needed for question/remarks
            $questionLines = $pdf->getNumLines($question, $wQuestion);
            $remarksLines  = $pdf->getNumLines($remarks ?: ' ', $wRemarks);

            $rowHeight = max($questionLines, $remarksLines) * $lineHeight;
            $rowHeight = max($rowHeight, 6);

            // page break
            if ($pdf->GetY() + $rowHeight > ($pdf->getPageHeight() - 25)) {
                $pdf->AddPage();
                $pdf->SetY(55);
                $this->drawTableHeader($pdf, $wNoCol, $wQuestion, $wYes, $wNo, $wRemarks);
                $pdf->SetFont('calibri', '', 10);
            }

            $x = $pdf->GetX();
            $y = $pdf->GetY();

            // NO. column
            $pdf->MultiCell(
                $wNoCol,
                $rowHeight,
                ($index + 1) . '.',
                0,
                'R',
                false,
                0,
                $x,
                $y,
                true,
                0,
                false,
                true,
                $rowHeight,
                'T'
            );

            // Question
            $pdf->MultiCell(
                $wQuestion,
                $rowHeight,
                $question,
                0,
                'L',
                false,
                0,
                $x + $wNoCol,
                $y,
                true,
                0,
                false,
                true,
                $rowHeight,
                'T'
            );

            // YES cell area
            $xYes = $x + $wNoCol + $wQuestion;
            $yYes = $y;
            $pdf->SetXY($xYes, $yYes);
            $pdf->Cell($wYes, $rowHeight, '', 0, 0, 'C');

            $boxSize = 3;
            $boxX = $xYes + ($wYes / 2) - ($boxSize / 2);
            $boxY = $yYes + ($rowHeight / 2) - ($boxSize / 2);

            $pdf->SetFillColor(0, 0, 0);
            if ($answer === 'yes') {
                $pdf->Rect($boxX, $boxY, $boxSize, $boxSize, 'DF');
            } else {
                $pdf->Rect($boxX, $boxY, $boxSize, $boxSize);
            }

            // NO cell area
            $xNoCell = $xYes + $wYes;
            $yNoCell = $y;
            $pdf->SetXY($xNoCell, $yNoCell);
            $pdf->Cell($wNo, $rowHeight, '', 0, 0, 'C');

            $boxX = $xNoCell + ($wNo / 2) - ($boxSize / 2);
            $boxY = $yNoCell + ($rowHeight / 2) - ($boxSize / 2);

            if ($answer === 'no') {
                $pdf->Rect($boxX, $boxY, $boxSize, $boxSize, 'DF');
            } else {
                $pdf->Rect($boxX, $boxY, $boxSize, $boxSize);
            }

            $pdf->SetFont('calibri', '', 10);
            // Remarks
            $pdf->MultiCell(
                $wRemarks,
                $rowHeight,
                $remarks,
                'B',
                'L',
                false,
                1,
                $x + $wNoCol + $wQuestion + $wYes + $wNo,
                $y,
                true,
                0,
                false,
                true,
                $rowHeight,
                'B'
            );

            $pdf->SetFont('calibri', '', 11);
        }

        $pdf->Ln(6);

        $pdf->SetFont('calibri', '', 11);


        $leftWidth  = 90;
        $rightWidth = 90;
        $lineHeight = 6;
        $gap = 6; // adjust spacing here
        
        $x = $pdf->GetX();
        $y = $pdf->GetY()+2;

        $pdf->SetFont('calibri', 'B', 11);
        $pdf->SetXY($x, $y);
        $pdf->Cell(19, 6, 'DIVISION: ', 0, 0, 'L');

        $pdf->SetFont('calibri', '', 11);
        $pdf->SetXY($x+19, $y);
        $pdf->Cell(60, 6, $division, 'B', 0, 'L');

        $pdf->SetFont('calibri', 'B', 11);
        $pdf->SetXY($x+100, $y);
        $pdf->Cell(30, 6, 'COMPUTER NO.: ', 0, 0, 'L');

        $pdf->SetFont('calibri', '', 11);
        $pdf->SetXY($x+130, $y);
        $pdf->Cell(52, 6, $record['ict_tag'], 'B', 0, 'L');


        //FINDINGS
        $pdf->SetFont('calibri', 'B', 11);
        $pdf->SetXY($x, $y+10);
        $pdf->Cell(52, 6, 'FINDINGS:', 0, 0, 'L');

        $pdf->SetFont('calibri', '', 11);
        $pdf->MultiCell(84, 6, '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;' . $record['findings'], 0, 'L', false, 0, $x, $y+10.5, true, 0, true, true, 18, 'T');
        
        $pdf->Line($x+1,$y+15, 98, $y+15);
        $pdf->Line($x+1,$y+20, 98, $y+20);
        $pdf->Line($x+1,$y+25, 98, $y+25);

        //STATUS
        $pdf->SetFont('calibri', 'B', 11);
        $pdf->SetXY($x+98, $y+10);
        $pdf->Cell(52, 6, 'STATUS:', 0, 0, 'L');

        $pdf->SetFont('calibri', '', 11);
        $pdf->MultiCell(84, 6, '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;' . $record['status'], 0, 'L', false, 0, $x+98, $y+10.5, true, 0, true, true, 18, 'T');
        
        $pdf->Line($x+99,$y+15, 196, $y+15);
        $pdf->Line($x+99,$y+20, 196, $y+20);
        $pdf->Line($x+99,$y+25, 196, $y+25);


        //CONDUCTED BY
        $pdf->SetFont('calibri', 'B', 11);
        $pdf->SetXY($x, $y+30);
        $pdf->Cell(52, 6, 'CONDUCTED/PERFORMED BY:', 0, 0, 'L');

        //CONDUCTED BY VALUE
        $pdf->SetFont('calibri', '', 11);
        $pdf->SetXY($x+1, $y+42);
        $pdf->Cell(52, 6, strtoupper($conducted_by), 0, 0, 'C', 0, '', 1);
        $pdf->Line($x+1,$y+48, 67, $y+48);

        $pdf->SetFont('calibri', 'B', 10);
        $pdf->SetXY($x+1, $y+48);
        $pdf->Cell(52, 6, 'SIGNATURE OVER PRINTED NAME', 0, 0, 'C', 0, '', 1);

        //DATE CONDUCTED BY
        $pdf->SetFont('calibri', 'B', 11);
        $pdf->SetXY($x+60, $y+30);
        $pdf->Cell(24, 6, 'DATE:', 0, 0, 'L');

        //DATE CONDUCTED BY VALUE
        $pdf->SetFont('calibri', '', 11);
        $pdf->SetXY($x+61, $y+42);
        $pdf->Cell(24, 6, $conducted_date, 0, 0, 'C');
        $pdf->Line($x+61,$y+48, 99, $y+48);

        //END USER
        $pdf->SetFont('calibri', 'B', 11);
        $pdf->SetXY($x+98, $y+30);
        $pdf->Cell(52, 6, 'END USER/REPRESENTATIVE:', 0, 0, 'L');

        //END USER VALUE
        $pdf->SetFont('calibri', '', 11);
        $pdf->SetXY($x+99, $y+42);
        $pdf->Cell(52, 6, strtoupper($end_user), 0, 0, 'C', 0, '', 1);
        $pdf->Line($x+99,$y+48, 166, $y+48);

        $pdf->SetFont('calibri', 'B', 10);
        $pdf->SetXY($x+100, $y+48);
        $pdf->Cell(52, 6, 'SIGNATURE OVER PRINTED NAME', 0, 0, 'C', 0, '', 1);

        //DATE END USER
        $pdf->SetFont('calibri', 'B', 11);
        $pdf->SetXY($x+158, $y+30);
        $pdf->Cell(24, 6, 'DATE:', 0, 0, 'L');

        //DATE END USER VALUE
        $pdf->SetFont('calibri', '', 11);
        $pdf->SetXY($x+159, $y+42);
        $pdf->Cell(24, 6, '', 0, 0, 'C');
        $pdf->Line($x+159,$y+48, 197, $y+48);



        $pdf->SetFont('calibri', 'B', 11);
        $pdf->SetXY($x+50, $y+62);
        $pdf->Cell(52, 6, 'NOTED:', 0, 0, 'L');

        $pdf->SetFont('calibri', 'B', 11);
        $pdf->SetXY($x+1, $y+69);
        $pdf->Cell(182, 6, 'KRIZ RAEL YVES L. PUGAL', 0, 0, 'C');

        $pdf->SetFont('calibri', 'B', 11);
        $pdf->SetXY($x+1, $y+74 );
        $pdf->Cell(182, 6, 'Computer Maintenance Technologist III', 0, 0, 'C');

        $pdf->SetFont('calibri', 'B', 11);
        $pdf->SetXY($x+150, $y+62);
        $pdf->Cell(52, 6, 'DATE:', 0, 0, 'L');






        $pdf->Output('pms.pdf', 'I');

        exit;
    }

    public function bulk_pdf()
    {
        
        $idsParam = $this->request->getGet('ids');

        if (empty($idsParam)) {
            return;
        }

        // Convert "a,b,c" → ['a','b','c']
        $ids = array_filter(array_map('trim', explode(',', $idsParam)));

        $pdf = new MYPDF('P', 'mm', 'A4', true, 'UTF-8', false);

        foreach ($ids as $enc_id) {
            $pms_id = decrypt_id($enc_id);
            
            $record = $this->pmsrecord_model->getPdfDetails((int) $pms_id);
            $rows = $this->question_model->getQuestionsWithAnswers((int) $pms_id);
            
            $division = "";
            if($record['section_code'] == 'RLED'){
                $division = $record['section_name'];
            }else{
                $division = $record['division_code'].' - '.$record['shortname'];
            }

            $first  = $record['firstname'] ?? '';
            $middle = $record['middlename'] ?? '';
            $last   = $record['lastname'] ?? '';
            $suffix = $record['suffix'] ?? '';
            $conducted_by = $first;
            if (!empty($middle)) {
                $conducted_by .= ' ' . strtoupper(substr($middle, 0, 1)) . '.';
            }

            $conducted_by .= ' ' . $last;

            if (!empty($suffix)) {
                $conducted_by .= ', ' . $suffix;
            }

            $conducted_date = !empty($record['conducted_date']) ? date('M. d, Y', strtotime($record['conducted_date'])) : "";

            $end_user = $record['end_user'];

            $pdf->SetCreator('CI4');
            $pdf->SetAuthor('System');
            $pdf->SetTitle('PMS Record');

            $pdf->SetHeaderMargin(15);
            $pdf->SetFooterMargin(0);
            $pdf->setPrintFooter(false);

            $pdf->SetFont('calibri', '', 12);
            $pdf->SetAutoPageBreak(true, 1);
            $pdf->SetMargins(14, 48, 14, 1);
            $pdf->AddPage();

            $pdf->SetFont('calibri', 'b', 11);
            $pdf->Cell(0, 5, 'PREVENTIVE AND MAINTENANCE MEASURES', 0, 1, 'C' );
            $pdf->Ln(2);

            // Column widths
            $wNoCol    = 10;
            $wQuestion = 82;
            $wYes      = 21;
            $wNo       = 21;
            $wRemarks  = 48;

            $this->drawTableHeader($pdf, $wNoCol, $wQuestion, $wYes, $wNo, $wRemarks);

            
            $pdf->SetFont('calibri', '', 11);

            foreach ($rows as $index => $row) {
                $question = trim($row['question_text_snapshot'] ?: $row['question_text'] ?: '');
                $answer   = strtolower(trim($row['answer'] ?? ''));
                $remarks  = trim($row['remarks'] ?? '');

                if ($question === '') {
                    $question = 'N/A';
                }

                $lineHeight = 5;

                // include number column in layout, but line count is only needed for question/remarks
                $questionLines = $pdf->getNumLines($question, $wQuestion);
                $remarksLines  = $pdf->getNumLines($remarks ?: ' ', $wRemarks);

                $rowHeight = max($questionLines, $remarksLines) * $lineHeight;
                $rowHeight = max($rowHeight, 6);

                // page break
                if ($pdf->GetY() + $rowHeight > ($pdf->getPageHeight() - 25)) {
                    $pdf->AddPage();
                    $pdf->SetY(55);
                    $this->drawTableHeader($pdf, $wNoCol, $wQuestion, $wYes, $wNo, $wRemarks);
                    $pdf->SetFont('calibri', '', 10);
                }

                $x = $pdf->GetX();
                $y = $pdf->GetY();

                // NO. column
                $pdf->MultiCell(
                    $wNoCol,
                    $rowHeight,
                    ($index + 1) . '.',
                    0,
                    'R',
                    false,
                    0,
                    $x,
                    $y,
                    true,
                    0,
                    false,
                    true,
                    $rowHeight,
                    'T'
                );

                // Question
                $pdf->MultiCell(
                    $wQuestion,
                    $rowHeight,
                    $question,
                    0,
                    'L',
                    false,
                    0,
                    $x + $wNoCol,
                    $y,
                    true,
                    0,
                    false,
                    true,
                    $rowHeight,
                    'T'
                );

                // YES cell area
                $xYes = $x + $wNoCol + $wQuestion;
                $yYes = $y;
                $pdf->SetXY($xYes, $yYes);
                $pdf->Cell($wYes, $rowHeight, '', 0, 0, 'C');

                $boxSize = 3;
                $boxX = $xYes + ($wYes / 2) - ($boxSize / 2);
                $boxY = $yYes + ($rowHeight / 2) - ($boxSize / 2);

                $pdf->SetFillColor(0, 0, 0);
                if ($answer === 'yes') {
                    $pdf->Rect($boxX, $boxY, $boxSize, $boxSize, 'DF');
                } else {
                    $pdf->Rect($boxX, $boxY, $boxSize, $boxSize);
                }

                // NO cell area
                $xNoCell = $xYes + $wYes;
                $yNoCell = $y;
                $pdf->SetXY($xNoCell, $yNoCell);
                $pdf->Cell($wNo, $rowHeight, '', 0, 0, 'C');

                $boxX = $xNoCell + ($wNo / 2) - ($boxSize / 2);
                $boxY = $yNoCell + ($rowHeight / 2) - ($boxSize / 2);

                if ($answer === 'no') {
                    $pdf->Rect($boxX, $boxY, $boxSize, $boxSize, 'DF');
                } else {
                    $pdf->Rect($boxX, $boxY, $boxSize, $boxSize);
                }

                $pdf->SetFont('calibri', '', 10);
                // Remarks
                $pdf->MultiCell(
                    $wRemarks,
                    $rowHeight,
                    $remarks,
                    'B',
                    'L',
                    false,
                    1,
                    $x + $wNoCol + $wQuestion + $wYes + $wNo,
                    $y,
                    true,
                    0,
                    false,
                    true,
                    $rowHeight,
                    'B'
                );

                $pdf->SetFont('calibri', '', 11);
            }

            $pdf->Ln(6);

            $pdf->SetFont('calibri', '', 11);


            $leftWidth  = 90;
            $rightWidth = 90;
            $lineHeight = 6;
            $gap = 6; // adjust spacing here
            
            $x = $pdf->GetX();
            $y = $pdf->GetY()+2;

            $pdf->SetFont('calibri', 'B', 11);
            $pdf->SetXY($x, $y);
            $pdf->Cell(19, 6, 'DIVISION: ', 0, 0, 'L');

            $pdf->SetFont('calibri', '', 11);
            $pdf->SetXY($x+19, $y);
            $pdf->Cell(60, 6, $division, 'B', 0, 'L');

            $pdf->SetFont('calibri', 'B', 11);
            $pdf->SetXY($x+100, $y);
            $pdf->Cell(30, 6, 'COMPUTER NO.: ', 0, 0, 'L');

            $pdf->SetFont('calibri', '', 11);
            $pdf->SetXY($x+130, $y);
            $pdf->Cell(52, 6, $record['ict_tag'], 'B', 0, 'L');


            //FINDINGS
            $pdf->SetFont('calibri', 'B', 11);
            $pdf->SetXY($x, $y+10);
            $pdf->Cell(52, 6, 'FINDINGS:', 0, 0, 'L');

            $pdf->SetFont('calibri', '', 11);
            $pdf->MultiCell(84, 6, '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;' . $record['findings'], 0, 'L', false, 0, $x, $y+10.5, true, 0, true, true, 18, 'T');
            
            $pdf->Line($x+1,$y+15, 98, $y+15);
            $pdf->Line($x+1,$y+20, 98, $y+20);
            $pdf->Line($x+1,$y+25, 98, $y+25);

            //STATUS
            $pdf->SetFont('calibri', 'B', 11);
            $pdf->SetXY($x+98, $y+10);
            $pdf->Cell(52, 6, 'STATUS:', 0, 0, 'L');

            $pdf->SetFont('calibri', '', 11);
            $pdf->MultiCell(84, 6, '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;' . $record['status'], 0, 'L', false, 0, $x+98, $y+10.5, true, 0, true, true, 18, 'T');
            
            $pdf->Line($x+99,$y+15, 196, $y+15);
            $pdf->Line($x+99,$y+20, 196, $y+20);
            $pdf->Line($x+99,$y+25, 196, $y+25);


            //CONDUCTED BY
            $pdf->SetFont('calibri', 'B', 11);
            $pdf->SetXY($x, $y+30);
            $pdf->Cell(52, 6, 'CONDUCTED/PERFORMED BY:', 0, 0, 'L');

            //CONDUCTED BY VALUE
            $pdf->SetFont('calibri', '', 11);
            $pdf->SetXY($x+1, $y+42);
            $pdf->Cell(52, 6, strtoupper($conducted_by), 0, 0, 'C', 0, '', 1);
            $pdf->Line($x+1,$y+48, 67, $y+48);

            $pdf->SetFont('calibri', 'B', 10);
            $pdf->SetXY($x+1, $y+48);
            $pdf->Cell(52, 6, 'SIGNATURE OVER PRINTED NAME', 0, 0, 'C', 0, '', 1);

            //DATE CONDUCTED BY
            $pdf->SetFont('calibri', 'B', 11);
            $pdf->SetXY($x+60, $y+30);
            $pdf->Cell(24, 6, 'DATE:', 0, 0, 'L');

            //DATE CONDUCTED BY VALUE
            $pdf->SetFont('calibri', '', 11);
            $pdf->SetXY($x+61, $y+42);
            $pdf->Cell(24, 6, $conducted_date, 0, 0, 'C');
            $pdf->Line($x+61,$y+48, 99, $y+48);

            //END USER
            $pdf->SetFont('calibri', 'B', 11);
            $pdf->SetXY($x+98, $y+30);
            $pdf->Cell(52, 6, 'END USER/REPRESENTATIVE:', 0, 0, 'L');

            //END USER VALUE
            $pdf->SetFont('calibri', '', 11);
            $pdf->SetXY($x+99, $y+42);
            $pdf->Cell(52, 6, strtoupper($end_user), 0, 0, 'C', 0, '', 1);
            $pdf->Line($x+99,$y+48, 166, $y+48);

            $pdf->SetFont('calibri', 'B', 10);
            $pdf->SetXY($x+100, $y+48);
            $pdf->Cell(52, 6, 'SIGNATURE OVER PRINTED NAME', 0, 0, 'C', 0, '', 1);

            //DATE END USER
            $pdf->SetFont('calibri', 'B', 11);
            $pdf->SetXY($x+158, $y+30);
            $pdf->Cell(24, 6, 'DATE:', 0, 0, 'L');

            //DATE END USER VALUE
            $pdf->SetFont('calibri', '', 11);
            $pdf->SetXY($x+159, $y+42);
            $pdf->Cell(24, 6, '', 0, 0, 'C');
            $pdf->Line($x+159,$y+48, 197, $y+48);



            $pdf->SetFont('calibri', 'B', 11);
            $pdf->SetXY($x+50, $y+62);
            $pdf->Cell(52, 6, 'NOTED:', 0, 0, 'L');

            $pdf->SetFont('calibri', 'B', 11);
            $pdf->SetXY($x+1, $y+69);
            $pdf->Cell(182, 6, 'KRIZ RAEL YVES L. PUGAL', 0, 0, 'C');

            $pdf->SetFont('calibri', 'B', 11);
            $pdf->SetXY($x+1, $y+74 );
            $pdf->Cell(182, 6, 'Computer Maintenance Technologist III', 0, 0, 'C');

            $pdf->SetFont('calibri', 'B', 11);
            $pdf->SetXY($x+150, $y+62);
            $pdf->Cell(52, 6, 'DATE:', 0, 0, 'L');


        }


        $pdf->Output('pms.pdf', 'I');

        exit;
    }

    private function drawTableHeader($pdf, $wNoCol, $wQuestion, $wYes, $wNo, $wRemarks)
    {
        $pdf->SetFont('calibri', 'B', 11);

        $x = $pdf->GetX();
        $y = $pdf->GetY();
        $pdf->MultiCell($wNoCol, 12, '', 0, 'C', false, 0, $x, $y, true, 0, false, true, 12, 'M');

        $pdf->MultiCell($wQuestion, 12, '', 0, 'C', false, 0, $x + $wNoCol, $y, true, 0, false, true, 12, 'M');

        
        $pdf->SetFont('calibri', 'B', 10);
        $pdf->MultiCell($wYes + $wNo, 6, 'CONDUCTED/PERFORMED', 0, 'C', false, 0, $x + $wNoCol + $wQuestion, $y, true, 0, false, true, 6, 'T');

        
        $pdf->SetFont('calibri', 'B', 11);
        $pdf->MultiCell($wRemarks, 12, 'REMARKS', 0, 'C', false, 1, $x + $wNoCol + $wQuestion + $wYes + $wNo, $y, true, 0, false, true, 12, 'T');

        // YES / NO labels
        $pdf->SetXY($x + $wNoCol + $wQuestion, $y + 6);
        $pdf->Cell($wYes, 6, 'YES', 0, 0, 'C');
        $pdf->Cell($wNo, 6, 'NO', 0, 1, 'C');

        $pdf->Ln(1);
    }
}

class MYPDF extends TCPDF
{
    
    public string $customHeaderTitle  = '';

    public function Header()
    {
        $this->SetY(11);
        $this->SetX(14);

        
        $this->SetFont('calibri', '', 11);
        $html = '
            <table border="1" style="width: 543px;" cellpadding="2">
                <tr>
                    <td rowspan="3" style="text-align: center; width: 17%; ">
                        <img src="'.FCPATH . 'assets/img/dohlogo_border.png" width="70px" />
                    </td>
                    <td rowspan="2" style="text-align: center; width: 34%; ">
                    <b>
                    Republic of the Philippines <br />
                    Department of Health <br />
                    Regional Office I <br />
                    Information and Communication <br />
                    Technology Unit
                    </b>
                    </td>
                    <td style="height: 25%: width: 30%;">
                    Control No. <br />
                    </td>
                    <td style="text-align: center; width: 19%; ">
                    DOH-RO1-ICT-Form16
                    </td>
                </tr>
                <tr>
                    <td>
                    Revision No.:
                    </td>
                    <td style="text-align: center; ">
                    4
                    </td>
                </tr>
                <tr>
                    <td style="text-align: center; ">
                    <b>
                    IT Equipment Preventive <br />
                    Maintenance Quarterly Checklist
                    </b>
                    </td>
                    <td>
                    Effectivity:
                    </td>
                    <td>
                    October 10, 2023
                    </td>
                </tr>
            </table>
        ';

        $this->writeHTML($html, true, false, true, false, '');  

    }

    public function Footer()
    {
        

        
    }
}
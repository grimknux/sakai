<?php

namespace App\Controllers\Api\PMS;

use App\Models\PmsRecordModel;
use CodeIgniter\Database\BaseConnection;
use CodeIgniter\RESTful\ResourceController;

class PmsRecordsController extends ResourceController
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
     * List PMS Records
     * ==========================================
     */
    public function index()
    {
        $currentUid = (int) session('uid');

        $schedule = $this->request->getGet('selectedScheduleId');
        $device_type = $this->request->getGet('deviceTypeId');

        if ($schedule !== 'all') {
            $schedule = decrypt_id($schedule);
        }

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
                ps.semester,
                ps.schedule_start,
                ps.schedule_end,
                i.property_number,
                i.serial_num,
                i.brand_name,
                i.year,
                d.name as device_type,
                u.firstname,
                u.lastname,
                u.middlename,
                u.suffix
            ')
            ->join('pms_schedules ps', 'ps.id = pms_records.pms_schedule_id', 'left')
            ->join('inventory i', 'i.id = pms_records.inventory_id', 'left')
            ->join('device_types d', 'd.id = i.device_type_id', 'left')
            ->join('users u', 'u.id = pms_records.conducted_by', 'left')
            ->where('pms_records.deleted_at', null)
            ->where('pms_records.conducted_by', $currentUid);

        if ($schedule !== 'all') {
            $builder->where('pms_records.pms_schedule_id', $schedule);
        }

        if (!empty($device_type) && $device_type !== 'all') {
            $builder->where('i.device_type_id', $device_type);
        }

        $rows = $builder
            ->orderBy('pms_records.id', 'DESC')
            ->findAll();

        $rows = array_map(function ($row) {
            $firstname  = $row['firstname'] ?? '';
            $middlename = $row['middlename'] ?? '';
            $lastname   = $row['lastname'] ?? '';
            $suffix     = $row['suffix'] ?? '';
            $inv_year   = $row['year'] ?? '';
            // Check if it’s a valid 4-digit year
            if (preg_match('/^\d{4}$/', $inv_year)) {
                $current_year = date('Y'); // or Time::now()->getYear() in CI4
                $diff = $current_year - $inv_year . ' year(s) old';
            } else {
                $diff = "Not Available.";
            }

            // middle initial (skip if N/A, null, empty)
            $middleInitial = '';
            if (!empty($middlename) && strtoupper($middlename) !== 'N/A') {
                $middleInitial = strtoupper(substr(trim($middlename), 0, 1)) . '.';
            }

            // build name parts
            $nameParts = array_filter([
                $firstname,
                $middleInitial,
                $lastname
            ]);

            $name = implode(' ', $nameParts);

            // add suffix with comma
            if (!empty($suffix) && strtoupper($suffix) !== 'N/A') {
                $name .= ', ' . $suffix;
            }

            // lowercase then capitalize each word
            $name = ucwords(strtolower($name));

            // add new key
            $row['conducted_by_name'] = $name;
            $row['years'] = $diff;

            return $row;
        }, $rows);

        $rows = array_map(function ($row) {
            $row['id'] = encrypt_id($row['id']);
            return $row;
        }, $rows);

        return $this->respond(['records' => $rows, 'id' => $schedule]);
    }

    /**
     * ==========================================
     * Create PMS Record
     * ==========================================
     */
    public function create()
    {
        try {
            $data = $this->request->getJSON(true) ?? [];

            if (! $this->validateData($data, [
                'conducted_date'  => 'required',
                'pms_schedule_id' => 'required',
                'inventory_id'    => 'required',
                'section_code'    => 'required',
                'division_code'   => 'required',
                'bldg'            => 'permit_empty',
                'remarks'         => 'permit_empty',
                'findings'        => 'required',
                'status'          => 'required',
            ])) {
                return $this->respond([
                    'status'   => 422,
                    'error'    => 422,
                    'messages' => [
                        'error'  => 'Validation failed',
                        'fields' => $this->validator->getErrors(),
                    ],
                ], 422);
            }

            if (! isset($data['answers']) || ! is_array($data['answers']) || count($data['answers']) < 1) {
                return $this->respond([
                    'status'   => 422,
                    'error'    => 422,
                    'messages' => [
                        'error'  => 'Validation failed',
                        'fields' => [
                            'answers' => 'At least one question answer is required.',
                        ],
                    ],
                ], 422);
            }

            $pmsScheduleId = $data['pms_schedule_id'];
            $inventoryId   = $data['inventory_id'];

            $existing = $this->db->table('pms_records')
                ->where('pms_schedule_id', $pmsScheduleId)
                ->where('inventory_id', $inventoryId)
                ->where('deleted_at', null)
                ->countAllResults();

            if ($existing > 0) {
                return $this->respond([
                    'status'   => 422,
                    'error'    => 422,
                    'messages' => [
                        'error'  => 'Duplicate PMS record',
                        'fields' => [
                            'inventory_id' => 'A PMS record for this inventory item and schedule already exists.',
                        ],
                    ],
                ], 422);
            }

            if (! $pmsScheduleId) {
                return $this->respond([
                    'status'   => 422,
                    'error'    => 422,
                    'messages' => [
                        'error'  => 'Validation failed',
                        'fields' => [
                            'pms_schedule_id' => 'Invalid PMS schedule ID.',
                        ],
                    ],
                ], 422);
            }

            if (! $inventoryId) {
                return $this->respond([
                    'status'   => 422,
                    'error'    => 422,
                    'messages' => [
                        'error'  => 'Validation failed',
                        'fields' => [
                            'inventory_id' => 'Invalid inventory ID.',
                        ],
                    ],
                ], 422);
            }

            $scheduleExists = $this->db->table('pms_schedules')
                ->where('id', $pmsScheduleId)
                ->where('deleted_at', null)
                ->countAllResults();

            if (! $scheduleExists) {
                return $this->respond([
                    'status'   => 422,
                    'error'    => 422,
                    'messages' => [
                        'error'  => 'Validation failed',
                        'fields' => [
                            'pms_schedule_id' => 'Selected PMS schedule is invalid.',
                        ],
                    ],
                ], 422);
            }

            $inventoryExists = $this->db->table('inventory')
                ->where('id', $inventoryId)
                ->where('deleted_at', null)
                ->countAllResults();

            if (! $inventoryExists) {
                return $this->respond([
                    'status'   => 422,
                    'error'    => 422,
                    'messages' => [
                        'error'  => 'Validation failed',
                        'fields' => [
                            'inventory_id' => 'Selected inventory item is invalid.',
                        ],
                    ],
                ], 422);
            }

            $activeQuestions = $this->db->table('pms_questions')
                ->select('id, question_text, question_type')
                ->where('is_active', 1)
                ->where('deleted_at', null)
                ->orderBy('sort_order', 'ASC')
                ->orderBy('id', 'ASC')
                ->get()
                ->getResultArray();

            $questionMap = [];
            foreach ($activeQuestions as $q) {
                $questionMap[(int) $q['id']] = $q;
            }

            $errors = [];
            $submittedQuestionIds = [];
            $normalizedAnswers = [];

            foreach ($data['answers'] as $index => $row) {
                if (! is_array($row)) {
                    $errors["answers.$index"] = 'Invalid answer row.';
                    continue;
                }

                if (! $this->validateData($row, [
                    'question_id' => 'required',
                    'answer'      => 'required|in_list[yes,no]',
                    'remarks'     => 'permit_empty|max_length[30]',
                ])) {
                    foreach ($this->validator->getErrors() as $field => $message) {
                        $errors["answers.$index.$field"] = $message;
                    }
                    continue;
                }

                $questionId = decrypt_id($row['question_id']);

                if (! $questionId) {
                    $errors["answers.$index.question_id"] = 'Invalid question ID.';
                    continue;
                }

                $questionId = (int) $questionId;

                if (! isset($questionMap[$questionId])) {
                    $errors["answers.$index.question_id"] = 'Selected question is invalid.';
                    continue;
                }

                if (in_array($questionId, $submittedQuestionIds, true)) {
                    $errors["answers.$index.question_id"] = 'Duplicate question answer detected.';
                    continue;
                }

                $submittedQuestionIds[] = $questionId;

                $normalizedAnswers[] = [
                    'question_id' => $questionId,
                    'answer'      => strtolower(trim((string) $row['answer'])),
                    'remarks'     => $row['remarks'] ?? null,
                ];
            }

            foreach ($activeQuestions as $q) {
                $qid = (int) $q['id'];

                if (! in_array($qid, $submittedQuestionIds, true)) {
                    $errors["answers_missing_$qid"] = "Question '{$q['question_text']}' is required.";
                }
            }

            if (! empty($errors)) {
                return $this->respond([
                    'status'   => 422,
                    'error'    => 422,
                    'messages' => [
                        'error'  => 'Validation failed',
                        'fields' => $errors,
                    ],
                ], 422);
            }

            $this->db->transBegin();

            $insert = [
                'pms_schedule_id' => $pmsScheduleId,
                'inventory_id'    => $inventoryId,
                'section_code'    => $data['section_code'],
                'division_code'   => $data['division_code'],
                'bldg'            => $data['bldg'] ?? null,
                'conducted_by'    => (int) session('uid'),
                'conducted_date'  => $data['conducted_date'],
                'remarks'         => $data['remarks'] ?? null,
                'findings'        => $data['findings'] ?? null,
                'end_user'        => $data['end_user'] ?? null,
                'current_user'    => $data['current_user'] ?? null,
                'status'          => $data['status'] ?? null,
            ];

            $inserted = $this->records->insert($insert);

            if (! $inserted) {
                throw new \RuntimeException('Failed to insert PMS record.');
            }

            $newId = (int) $this->records->getInsertID();

            foreach ($normalizedAnswers as $row) {
                $question = $questionMap[$row['question_id']];

                $answerInserted = $this->db->table('pms_record_answers')->insert([
                    'pms_record_id'          => $newId,
                    'question_id'            => $row['question_id'],
                    'question_text_snapshot' => $question['question_text'],
                    'answer'                 => $row['answer'],
                    'remarks'                => $row['remarks'],
                    'created_at'             => date('Y-m-d H:i:s'),
                    'updated_at'             => date('Y-m-d H:i:s'),
                ]);

                if (! $answerInserted) {
                    throw new \RuntimeException('Failed to insert PMS record answer.');
                }
            }

            service('audit')->log('pms_records.create', 'pms_records', $newId, [
                'pms_schedule_id' => $insert['pms_schedule_id'],
                'inventory_id'    => $insert['inventory_id'],
                'section_code'    => $insert['section_code'],
                'division_code'   => $insert['division_code'],
                'bldg'            => $insert['bldg'],
                'conducted_by'    => $insert['conducted_by'],
                'conducted_date'  => $insert['conducted_date'],
                'end_user'        => $insert['end_user'],
                'current_user'    => $insert['current_user'],
                'answers_count'   => count($normalizedAnswers),
            ]);

            if ($this->db->transStatus() === false) {
                throw new \RuntimeException('Transaction failed while creating PMS record.');
            }

            $this->db->transCommit();

            return $this->respondCreated([
                'message' => 'PMS record created',
                'id'      => encrypt_id($newId),
            ]);
        } catch (\Throwable $e) {
            $this->db->transRollback();

            log_message('error', 'Create PMS Record Error: ' . $e->getMessage());

            return $this->respond([
                'status'   => 500,
                'error'    => 500,
                'messages' => [
                    'error'   => 'Failed to create PMS record.',
                    'details' => ENVIRONMENT === 'development' ? $e->getMessage() : null,
                ],
            ], 500);
        }
    }

    /**
     * ==========================================
     * Update PMS Record
     * ==========================================
     */
    public function update($id = null)
    {
        try {
            $id = decrypt_id($id);
            $currentUid = (int) session('uid');

            $record = $this->records->find($id);
            if (! $record) {
                return $this->failNotFound('PMS record not found');
            }

            if ((int) ($record['conducted_by'] ?? 0) !== $currentUid) {
                return $this->failForbidden('You are not allowed to update this PMS record');
            }

            $data = $this->request->getJSON(true) ?? [];

            if (! $this->validateData($data, [
                'conducted_date'  => 'required',
                'pms_schedule_id' => 'required|integer',
                'inventory_id'    => 'required|integer',
                'section_code'    => 'required',
                'division_code'   => 'required',
                'remarks'         => 'permit_empty',
            ])) {
                return $this->respond([
                    'status' => 422,
                    'error' => 422,
                    'messages' => [
                        'error' => 'Validation failed',
                        'fields' => $this->validator->getErrors(),
                    ],
                ], 422);
            }

            $scheduleExists = $this->db->table('pms_schedules')
                ->where('id', (int) $data['pms_schedule_id'])
                ->where('deleted_at', null)
                ->countAllResults();

            if (! $scheduleExists) {
                return $this->respond([
                    'status' => 422,
                    'error' => 422,
                    'messages' => [
                        'error' => 'Validation failed',
                        'fields' => ['pms_schedule_id' => 'Selected PMS schedule is invalid.'],
                    ],
                ], 422);
            }

            $inventoryExists = $this->db->table('inventory')
                ->where('id', (int) $data['inventory_id'])
                ->where('deleted_at', null)
                ->countAllResults();

            if (! $inventoryExists) {
                return $this->respond([
                    'status' => 422,
                    'error' => 422,
                    'messages' => [
                        'error' => 'Validation failed',
                        'fields' => ['inventory_id' => 'Selected inventory item is invalid.'],
                    ],
                ], 422);
            }

            $duplicateExists = $this->db->table('pms_records')
                ->where('pms_schedule_id', (int) $data['pms_schedule_id'])
                ->where('inventory_id', (int) $data['inventory_id'])
                ->where('deleted_at', null)
                ->where('id !=', $id)
                ->countAllResults();

            if ($duplicateExists > 0) {
                return $this->respond([
                    'status' => 422,
                    'error' => 422,
                    'messages' => [
                        'error' => 'Duplicate PMS record',
                        'fields' => [
                            'inventory_id' => 'A PMS record for this inventory item and schedule already exists.',
                        ],
                    ],
                ], 422);
            }

            $update = [
                'pms_schedule_id' => (int) $data['pms_schedule_id'],
                'inventory_id'    => (int) $data['inventory_id'],
                'section_code'    => $data['section_code'],
                'division_code'   => $data['division_code'],
                'bldg'            => $data['bldg'] ?? null,
                'conducted_date'  => $data['conducted_date'],
                'end_user'        => $data['end_user'] ?? null,
                'current_user'    => $data['current_user'] ?? null,
                'remarks'         => $data['remarks'] ?? null,
            ];

            $updated = $this->records->update($id, $update);

            if (! $updated) {
                throw new \RuntimeException('Failed to update PMS record.');
            }

            service('audit')->log('pms_records.update', 'pms_records', $id, [
                'fields' => array_keys($update),
                'before' => [
                    'pms_schedule_id' => $record['pms_schedule_id'] ?? null,
                    'inventory_id'    => $record['inventory_id'] ?? null,
                    'section_code'    => $record['section_code'] ?? null,
                    'division_code'   => $record['division_code'] ?? null,
                    'bldg'            => $record['bldg'] ?? null,
                    'conducted_date'  => $record['conducted_date'] ?? null,
                    'end_user'        => $record['end_user'] ?? null,
                    'current_user'    => $record['current_user'] ?? null,
                    'remarks'         => $record['remarks'] ?? null,
                ],
                'after' => $update,
            ]);

            return $this->respond(['message' => 'PMS record updated']);
        } catch (\Throwable $e) {
            log_message('error', 'Update PMS Record Error: ' . $e->getMessage());

            return $this->respond([
                'status'  => 500,
                'error'   => 500,
                'message' => 'Failed to update PMS record.',
                'details' => ENVIRONMENT === 'development' ? $e->getMessage() : null,
            ], 500);
        }
    }

    /**
     * ==========================================
     * Delete PMS Record
     * ==========================================
     */
    public function delete($id = null)
    {
        try {
            $id = decrypt_id($id);
            $currentUid = (int) session('uid');

            $record = $this->records->find($id);
            if (! $record) {
                return $this->failNotFound('PMS record not found');
            }

            if ((int) ($record['conducted_by'] ?? 0) !== $currentUid) {
                return $this->failForbidden('You are not allowed to delete this PMS record');
            }

            $isUsed = $this->db->table('pms_record_answers')
                ->where('pms_record_id', $id)
                ->where('deleted_at', null)
                ->countAllResults() > 0;

            if ($isUsed) {
                return $this->fail([
                    'message' => 'PMS record cannot be deleted because it already has recorded answers.',
                ], 409);
            }

            $deleted = $this->records->delete($id);

            if (! $deleted) {
                throw new \RuntimeException('Failed to delete PMS record.');
            }

            service('audit')->log('pms_records.delete', 'pms_records', $id, [
                'pms_schedule_id' => $record['pms_schedule_id'] ?? null,
                'inventory_id'    => $record['inventory_id'] ?? null,
                'conducted_by'    => $record['conducted_by'] ?? null,
            ]);

            return $this->respond(['message' => 'PMS record deleted']);
        } catch (\Throwable $e) {
            log_message('error', 'Delete PMS Record Error: ' . $e->getMessage());

            return $this->respond([
                'status'  => 500,
                'error'   => 500,
                'message' => 'Failed to delete PMS record.',
                'details' => ENVIRONMENT === 'development' ? $e->getMessage() : null,
            ], 500);
        }
    }

    /**
     * ==========================================
     * List PMS Record Answers
     * ==========================================
     */
    public function answers($id = null)
    {
        $id = (int) decrypt_id($id);
        if ($id <= 0) {
            return $this->failValidationErrors('Invalid PMS record ID');
        }

        $currentUid = (int) session('uid');

        $record = $this->records
            ->select('id, conducted_by')
            ->where('id', $id)
            ->where('deleted_at', null)
            ->first();

        if (! $record) {
            return $this->failNotFound('PMS record not found');
        }

        if ((int) ($record['conducted_by'] ?? 0) !== $currentUid) {
            return $this->failForbidden('You are not allowed to access this PMS record');
        }

        $rows = $this->db->table('pms_questions q')
            ->select("
                a.id,
                q.id as question_id,
                q.question_text,
                COALESCE(a.question_text_snapshot, q.question_text) as question_text_snapshot,
                COALESCE(a.answer, '') as answer,
                COALESCE(a.remarks, '') as remarks,
                q.sort_order,
                a.created_at,
                a.updated_at
            ", false)
            ->join(
                'pms_record_answers a',
                'a.question_id = q.id AND a.pms_record_id = ' . $id . ' AND a.deleted_at IS NULL',
                'left'
            )
            ->where('q.is_active', 1)
            ->where('q.deleted_at', null)
            ->orderBy('q.sort_order', 'ASC')
            ->orderBy('q.id', 'ASC')
            ->get()
            ->getResultArray();

        $rows = array_map(function ($row, $index) use ($id) {
            $row['cnt'] = $index + 1;
            $row['pms_record_id'] = $id;
            return $row;
        }, $rows, array_keys($rows));

        return $this->respond(['answers' => array_values($rows)]);
    }

    /**
     * ==========================================
     * Update PMS Record Answers
     * ==========================================
     */
    public function updateAnswers($id = null)
    {
        try {
            $id = decrypt_id($id);
            $currentUid = (int) session('uid');

            $record = $this->records->find($id);
            if (! $record) {
                return $this->failNotFound('PMS record not found');
            }

            if ((int) ($record['conducted_by'] ?? 0) !== $currentUid) {
                return $this->failForbidden('You are not allowed to update this PMS record');
            }

            $data = $this->request->getJSON(true) ?? [];

            if (! isset($data['answers']) || ! is_array($data['answers']) || count($data['answers']) < 1) {
                return $this->respond([
                    'status' => 422,
                    'error' => 422,
                    'messages' => [
                        'error' => 'Validation failed',
                        'fields' => [
                            'answers' => 'At least one answer is required.',
                        ],
                    ],
                ], 422);
            }

            $metaData = [
                'findings' => $data['findings'] ?? '',
                'status'   => $data['status'] ?? '',
            ];

            if (! $this->validateData($metaData, [
                'findings' => 'required|max_length[2000]',
                'status'   => 'required|max_length[2000]',
            ])) {
                return $this->respond([
                    'status' => 422,
                    'error' => 422,
                    'messages' => [
                        'error' => 'Validation failed',
                        'fields' => $this->validator->getErrors(),
                    ],
                ], 422);
            }

            $activeQuestions = $this->db->table('pms_questions')
                ->select('id, question_text')
                ->where('is_active', 1)
                ->where('deleted_at', null)
                ->orderBy('sort_order', 'ASC')
                ->orderBy('id', 'ASC')
                ->get()
                ->getResultArray();

            $questionMap = [];
            foreach ($activeQuestions as $q) {
                $questionMap[(int) $q['id']] = $q;
            }

            $existing = $this->db->table('pms_record_answers')
                ->select('id, pms_record_id, question_id, answer, remarks')
                ->where('pms_record_id', $id)
                ->where('deleted_at', null)
                ->get()
                ->getResultArray();

            $existingMap = [];
            foreach ($existing as $row) {
                $existingMap[(int) $row['id']] = $row;
            }

            $errors = [];
            $submittedQuestionIds = [];

            foreach ($data['answers'] as $index => $row) {
                if (! is_array($row)) {
                    $errors["answers.$index"] = 'Invalid answer row.';
                    continue;
                }

                if (! $this->validateData($row, [
                    'id'          => 'permit_empty|integer',
                    'question_id' => 'required|integer',
                    'answer'      => 'required|in_list[yes,no]',
                    'remarks'     => 'permit_empty|max_length[1000]',
                ])) {
                    foreach ($this->validator->getErrors() as $field => $message) {
                        $errors["answers.$index.$field"] = $message;
                    }
                    continue;
                }

                $questionId = (int) $row['question_id'];
                if (! isset($questionMap[$questionId])) {
                    $errors["answers.$index.question_id"] = 'Selected question is invalid.';
                    continue;
                }

                if (in_array($questionId, $submittedQuestionIds, true)) {
                    $errors["answers.$index.question_id"] = 'Duplicate question answer detected.';
                } else {
                    $submittedQuestionIds[] = $questionId;
                }

                $answerId = isset($row['id']) && $row['id'] !== null && $row['id'] !== '' ? (int) $row['id'] : null;

                if ($answerId !== null && ! isset($existingMap[$answerId])) {
                    $errors["answers.$index.id"] = 'Selected answer record is invalid.';
                }
            }

            foreach ($activeQuestions as $q) {
                $qid = (int) $q['id'];
                if (! in_array($qid, $submittedQuestionIds, true)) {
                    $errors["answers_missing_$qid"] = "Question '{$q['question_text']}' is required.";
                }
            }

            if (! empty($errors)) {
                return $this->respond([
                    'status' => 422,
                    'error' => 422,
                    'messages' => [
                        'error' => 'Validation failed',
                        'fields' => $errors,
                    ],
                ], 422);
            }

            $findings = trim((string) ($data['findings'] ?? ''));
            $status   = trim((string) ($data['status'] ?? ''));

            $this->db->transBegin();

            foreach ($data['answers'] as $row) {
                $answerId   = isset($row['id']) && $row['id'] !== null && $row['id'] !== '' ? (int) $row['id'] : null;
                $questionId = (int) $row['question_id'];
                $answer     = strtolower(trim((string) $row['answer']));
                $remarks    = $row['remarks'] ?? null;

                if ($answerId !== null) {
                    $updatedAnswer = $this->db->table('pms_record_answers')
                        ->where('id', $answerId)
                        ->where('pms_record_id', $id)
                        ->update([
                            'answer'     => $answer,
                            'remarks'    => $remarks,
                            'updated_at' => date('Y-m-d H:i:s'),
                        ]);

                    if (! $updatedAnswer) {
                        throw new \RuntimeException('Failed to update PMS answer.');
                    }
                } else {
                    $insertedAnswer = $this->db->table('pms_record_answers')->insert([
                        'pms_record_id'          => $id,
                        'question_id'            => $questionId,
                        'question_text_snapshot' => $questionMap[$questionId]['question_text'],
                        'answer'                 => $answer,
                        'remarks'                => $remarks,
                        'created_at'             => date('Y-m-d H:i:s'),
                        'updated_at'             => date('Y-m-d H:i:s'),
                    ]);

                    if (! $insertedAnswer) {
                        throw new \RuntimeException('Failed to insert PMS answer.');
                    }
                }
            }

            $updatedRecord = $this->records->update($id, [
                'findings'   => $findings !== '' ? $findings : null,
                'status'     => $status !== '' ? $status : null,
                'updated_at' => date('Y-m-d H:i:s'),
            ]);

            if (! $updatedRecord) {
                throw new \RuntimeException('Failed to update PMS record metadata.');
            }

            service('audit')->log('pms_record_answers.update', 'pms_record_answers', $id, [
                'pms_record_id' => $id,
                'answers_count' => count($data['answers']),
                'findings'      => $findings,
                'status'        => $status,
            ]);

            if ($this->db->transStatus() === false) {
                throw new \RuntimeException('Transaction failed while updating PMS answers.');
            }

            $this->db->transCommit();

            return $this->respond(['message' => 'PMS answers updated']);
        } catch (\Throwable $e) {
            $this->db->transRollback();

            log_message('error', 'Update PMS Answers Error: ' . $e->getMessage());

            return $this->respond([
                'status' => 500,
                'error' => 500,
                'messages' => [
                    'error'   => 'Failed to update PMS answers.',
                    'details' => ENVIRONMENT === 'development' ? $e->getMessage() : null,
                ],
            ], 500);
        }
    }

    /**
     * ==========================================
     * Clear PMS Record Answers
     * ==========================================
     */
    public function clearAnswers($id = null)
    {
        try {
            $id = decrypt_id($id);
            $currentUid = (int) session('uid');

            $record = $this->records->find($id);
            if (! $record) {
                return $this->failNotFound('PMS record not found');
            }

            if ((int) ($record['conducted_by'] ?? 0) !== $currentUid) {
                return $this->failForbidden('You are not allowed to clear answers for this PMS record');
            }

            $hasAnswers = $this->db->table('pms_record_answers')
                ->where('pms_record_id', $id)
                ->where('deleted_at', null)
                ->countAllResults() > 0;

            $this->db->transBegin();

            if ($hasAnswers) {
                $cleared = $this->db->table('pms_record_answers')
                    ->where('pms_record_id', $id)
                    ->where('deleted_at', null)
                    ->update([
                        'deleted_at' => date('Y-m-d H:i:s'),
                        'updated_at' => date('Y-m-d H:i:s'),
                    ]);

                if (! $cleared) {
                    throw new \RuntimeException('Failed to clear PMS answers.');
                }
            }

            $updatedRecord = $this->records->update($id, [
                'findings'   => null,
                'status'     => null,
                'updated_at' => date('Y-m-d H:i:s'),
            ]);

            if (! $updatedRecord) {
                throw new \RuntimeException('Failed to reset PMS record metadata.');
            }

            service('audit')->log('pms_record_answers.clear', 'pms_record_answers', $id, [
                'pms_record_id' => $id,
            ]);

            if ($this->db->transStatus() === false) {
                throw new \RuntimeException('Transaction failed while clearing PMS answers.');
            }

            $this->db->transCommit();

            return $this->respond([
                'message' => $hasAnswers ? 'PMS answers cleared' : 'No answers to clear',
            ]);
        } catch (\Throwable $e) {
            $this->db->transRollback();

            log_message('error', 'Clear PMS Answers Error: ' . $e->getMessage());

            return $this->respond([
                'status' => 500,
                'error' => 500,
                'messages' => [
                    'error'   => 'Failed to clear PMS answers.',
                    'details' => ENVIRONMENT === 'development' ? $e->getMessage() : null,
                ],
            ], 500);
        }
    }
}
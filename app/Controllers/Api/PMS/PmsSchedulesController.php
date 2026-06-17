<?php

namespace App\Controllers\Api\PMS;

use App\Models\PmsScheduleModel;
use CodeIgniter\Database\BaseConnection;
use CodeIgniter\RESTful\ResourceController;

class PmsSchedulesController extends ResourceController
{
    protected $format = 'json';

    protected BaseConnection $db;
    protected PmsScheduleModel $schedules;

    /**
     * ==========================================
     * Constructor
     * ==========================================
     */
    public function __construct()
    {
        $this->db = \Config\Database::connect();
        $this->schedules = new PmsScheduleModel();

        helper('security');
    }

    /**
     * ==========================================
     * List PMS Schedules
     * ==========================================
     */
    public function index()
    {
        $rows = $this->schedules
            ->where('deleted_at', null)
            ->orderBy('schedule_start', 'DESC')
            ->orderBy('id', 'DESC')
            ->findAll();

        $data = [];

        foreach ($rows as $row) {
            $data[] = [
                'id'              => encrypt_id($row['id']),
                'year'            => $row['year'],
                'semester'        => $row['semester'],
                'schedule_start'  => $row['schedule_start'],
                'schedule_end'    => $row['schedule_end'],
                'attachment_name' => $row['attachment_name'],
                'attachment_path' => $row['attachment_path'],
                'remarks'         => $row['remarks'],
                'created_by'      => $row['created_by'],
                'created_at'      => $row['created_at'],
                'updated_at'      => $row['updated_at'],
                'deleted_at'      => $row['deleted_at'],
            ];
        }

        return $this->respond(['schedules' => $data]);
    }

    /**
     * ==========================================
     * PMS Schedules Dropdown List
     * ==========================================
     */
    public function dropdown()
    {
        $rows = $this->schedules
            ->where('deleted_at', null)
            ->orderBy('schedule_start', 'DESC')
            ->orderBy('id', 'DESC')
            ->findAll();

        $data = [];

        foreach ($rows as $row) {
            $data[] = [
                'id'             => $row['id'],
                'eid'            => encrypt_id($row['id']),
                'semester'       => $row['semester'],
                'schedule_start' => $row['schedule_start'],
                'schedule_end'   => $row['schedule_end'],
                'year'           => $row['year'],
            ];
        }

        return $this->respond(['schedules' => $data]);
    }

    /**
     * ==========================================
     * Create PMS Schedule
     * ==========================================
     */
    public function create()
    {
        $newUploadedFullPath = null;

        try {
            $post = $this->request->getPost();

            if (! $this->validate([
                'year'           => 'required|valid_date[Y]',
                'semester'       => 'required|in_list[first,second]',
                'schedule_start' => 'required|valid_date',
                'schedule_end'   => 'required|valid_date',
                'remarks'        => 'permit_empty',
                'attachment'     => 'permit_empty|uploaded[attachment]|max_size[attachment,10240]|ext_in[attachment,pdf]|mime_in[attachment,application/pdf]',
            ], [
                'attachment' => [
                    'uploaded' => 'The attachment upload failed.',
                    'max_size' => 'The attachment must not exceed 10MB.',
                    'ext_in'   => 'Only PDF files are allowed.',
                    'mime_in'  => 'Only PDF files are allowed.',
                ],
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

            $scheduleStart = $post['schedule_start'] ?? null;
            $scheduleEnd   = $post['schedule_end'] ?? null;

            if ($scheduleStart && $scheduleEnd && strtotime($scheduleEnd) < strtotime($scheduleStart)) {
                return $this->respond([
                    'status'   => 422,
                    'error'    => 422,
                    'messages' => [
                        'error'  => 'Validation failed',
                        'fields' => [
                            'schedule_end' => 'Schedule end must be after or equal to schedule start.',
                        ],
                    ],
                ], 422);
            }

            $file = $this->request->getFile('attachment');

            $attachmentName = null;
            $attachmentPath = null;

            if ($file && $file->isValid() && ! $file->hasMoved()) {
                $attachmentName = $file->getClientName();
                $newName = $file->getRandomName();
                $file->move(WRITEPATH . 'uploads/pms_schedules', $newName);
                $attachmentPath = 'pms_schedules/' . $newName;
                $newUploadedFullPath = WRITEPATH . 'uploads/' . $attachmentPath;
            }

            $insert = [
                'year'            => $post['year'],
                'semester'        => $post['semester'],
                'schedule_start'  => $scheduleStart,
                'schedule_end'    => $scheduleEnd,
                'attachment_name' => $attachmentName,
                'attachment_path' => $attachmentPath,
                'remarks'         => $post['remarks'] ?? null,
                'created_by'      => session('uid') ? (int) session('uid') : null,
            ];

            $this->db->transBegin();

            $inserted = $this->schedules->insert($insert);

            if (! $inserted) {
                throw new \RuntimeException('Failed to create PMS schedule.');
            }

            $newId = (int) $this->schedules->getInsertID();

            service('audit')->log('pms_schedules.create', 'pms_schedules', $newId, [
                'year'            => $insert['year'],
                'semester'        => $insert['semester'],
                'schedule_start'  => $insert['schedule_start'],
                'schedule_end'    => $insert['schedule_end'],
                'attachment_name' => $insert['attachment_name'],
            ]);

            if ($this->db->transStatus() === false) {
                throw new \RuntimeException('Transaction failed while creating PMS schedule.');
            }

            $this->db->transCommit();

            return $this->respondCreated([
                'message' => 'PMS schedule created',
                'id'      => $newId,
            ]);
        } catch (\Throwable $e) {
            $this->db->transRollback();

            if ($newUploadedFullPath && is_file($newUploadedFullPath)) {
                @unlink($newUploadedFullPath);
            }

            log_message('error', 'Create PMS Schedule Error: ' . $e->getMessage());

            return $this->respond([
                'status'  => 500,
                'error'   => 500,
                'message' => 'Failed to create PMS schedule.',
                'details' => ENVIRONMENT === 'development' ? $e->getMessage() : null,
            ], 500);
        }
    }

    /**
     * ==========================================
     * Update PMS Schedule
     * ==========================================
     */
    public function update($id = null)
    {
        $newUploadedFullPath = null;
        $oldFullPathToDelete = null;

        try {
            $id = decrypt_id($id);

            $schedule = $this->schedules->find($id);
            if (! $schedule) {
                return $this->failNotFound('PMS schedule not found');
            }

            $post = $this->request->getPost();

            if (! $this->validate([
                'year'           => 'required|valid_date[Y]',
                'semester'       => 'required|in_list[first,second]',
                'schedule_start' => 'required|valid_date',
                'schedule_end'   => 'required|valid_date',
                'remarks'        => 'permit_empty',
                'attachment'     => 'permit_empty|max_size[attachment,10240]|ext_in[attachment,pdf]|mime_in[attachment,application/pdf]',
            ], [
                'attachment' => [
                    'max_size' => 'The attachment must not exceed 10MB.',
                    'ext_in'   => 'Only PDF files are allowed.',
                    'mime_in'  => 'Only PDF files are allowed.',
                ],
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

            $scheduleStart = $post['schedule_start'] ?? null;
            $scheduleEnd   = $post['schedule_end'] ?? null;

            if ($scheduleStart && $scheduleEnd && strtotime($scheduleEnd) < strtotime($scheduleStart)) {
                return $this->respond([
                    'status'   => 422,
                    'error'    => 422,
                    'messages' => [
                        'error'  => 'Validation failed',
                        'fields' => [
                            'schedule_end' => 'Schedule end must be after or equal to schedule start.',
                        ],
                    ],
                ], 422);
            }

            $update = [
                'year'           => $post['year'],
                'semester'       => $post['semester'],
                'schedule_start' => $scheduleStart,
                'schedule_end'   => $scheduleEnd,
                'remarks'        => $post['remarks'] ?? null,
            ];

            $file = $this->request->getFile('attachment');

            if ($file && $file->isValid() && ! $file->hasMoved()) {
                $newName = $file->getRandomName();
                $file->move(WRITEPATH . 'uploads/pms_schedules', $newName);

                $update['attachment_name'] = $file->getClientName();
                $update['attachment_path'] = 'pms_schedules/' . $newName;

                $newUploadedFullPath = WRITEPATH . 'uploads/' . $update['attachment_path'];

                if (! empty($schedule['attachment_path'])) {
                    $oldFullPathToDelete = WRITEPATH . 'uploads/' . $schedule['attachment_path'];
                }
            }

            $this->db->transBegin();

            $updated = $this->schedules->update($id, $update);

            if (! $updated) {
                throw new \RuntimeException('Failed to update PMS schedule.');
            }

            service('audit')->log('pms_schedules.update', 'pms_schedules', $id, [
                'fields' => array_keys($update),
                'before' => [
                    'year'            => $schedule['year'] ?? null,
                    'semester'        => $schedule['semester'] ?? null,
                    'schedule_start'  => $schedule['schedule_start'] ?? null,
                    'schedule_end'    => $schedule['schedule_end'] ?? null,
                    'attachment_name' => $schedule['attachment_name'] ?? null,
                    'remarks'         => $schedule['remarks'] ?? null,
                ],
                'after' => [
                    'year'            => $update['year'],
                    'semester'        => $update['semester'],
                    'schedule_start'  => $update['schedule_start'],
                    'schedule_end'    => $update['schedule_end'],
                    'attachment_name' => $update['attachment_name'] ?? ($schedule['attachment_name'] ?? null),
                    'remarks'         => $update['remarks'],
                ],
            ]);

            if ($this->db->transStatus() === false) {
                throw new \RuntimeException('Transaction failed while updating PMS schedule.');
            }

            $this->db->transCommit();

            if ($oldFullPathToDelete && is_file($oldFullPathToDelete)) {
                @unlink($oldFullPathToDelete);
            }

            return $this->respond(['message' => 'PMS schedule updated']);
        } catch (\Throwable $e) {
            $this->db->transRollback();

            if ($newUploadedFullPath && is_file($newUploadedFullPath)) {
                @unlink($newUploadedFullPath);
            }

            log_message('error', 'Update PMS Schedule Error: ' . $e->getMessage());

            return $this->respond([
                'status'  => 500,
                'error'   => 500,
                'message' => 'Failed to update PMS schedule.',
                'details' => ENVIRONMENT === 'development' ? $e->getMessage() : null,
            ], 500);
        }
    }

    /**
     * ==========================================
     * Delete PMS Schedule
     * ==========================================
     */
    public function delete($id = null)
    {
        try {
            $id = decrypt_id($id);

            $schedule = $this->schedules
                ->select('id, semester, schedule_start, schedule_end, attachment_name, attachment_path')
                ->find($id);

            if (! $schedule) {
                return $this->failNotFound('PMS schedule not found');
            }

            $isUsed = $this->db->table('pms_records')
                ->where('pms_schedule_id', $id)
                ->where('deleted_at', null)
                ->countAllResults() > 0;

            if ($isUsed) {
                return $this->fail([
                    'message' => 'PMS schedule cannot be deleted because it is already used in PMS records.',
                ], 409);
            }

            $this->db->transBegin();

            $deleted = $this->schedules->delete($id);

            if (! $deleted) {
                throw new \RuntimeException('Failed to delete PMS schedule.');
            }

            service('audit')->log('pms_schedules.delete', 'pms_schedules', $id, [
                'semester'        => $schedule['semester'] ?? null,
                'schedule_start'  => $schedule['schedule_start'] ?? null,
                'schedule_end'    => $schedule['schedule_end'] ?? null,
                'attachment_name' => $schedule['attachment_name'] ?? null,
            ]);

            if ($this->db->transStatus() === false) {
                throw new \RuntimeException('Transaction failed while deleting PMS schedule.');
            }

            $this->db->transCommit();

            if (! empty($schedule['attachment_path'])) {
                $fullPath = WRITEPATH . 'uploads/' . $schedule['attachment_path'];
                if (is_file($fullPath)) {
                    @unlink($fullPath);
                }
            }

            return $this->respond(['message' => 'PMS schedule deleted']);
        } catch (\Throwable $e) {
            $this->db->transRollback();

            log_message('error', 'Delete PMS Schedule Error: ' . $e->getMessage());

            return $this->respond([
                'status'  => 500,
                'error'   => 500,
                'message' => 'Failed to delete PMS schedule.',
                'details' => ENVIRONMENT === 'development' ? $e->getMessage() : null,
            ], 500);
        }
    }

    /**
     * ==========================================
     * View PMS Schedule Attachment
     * ==========================================
     */
    public function view($id = null)
    {
        $id = decrypt_id($id);

        $schedule = $this->schedules->find($id);
        if (! $schedule) {
            return $this->failNotFound('PMS schedule not found');
        }

        if (empty($schedule['attachment_path'])) {
            return $this->failNotFound('Attachment not found');
        }

        $fullPath = WRITEPATH . 'uploads/' . $schedule['attachment_path'];

        if (! is_file($fullPath)) {
            return $this->failNotFound('Attachment file not found');
        }

        return $this->response
            ->setHeader('Content-Type', 'application/pdf')
            ->setHeader('Content-Disposition', 'inline; filename="' . ($schedule['attachment_name'] ?: basename($fullPath)) . '"')
            ->setBody(file_get_contents($fullPath));
    }
}
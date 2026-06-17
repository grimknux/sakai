<?php

namespace App\Controllers\Api\PMS;

use App\Models\PmsQuestionModel;
use CodeIgniter\Database\BaseConnection;
use CodeIgniter\RESTful\ResourceController;

class PmsQuestionsController extends ResourceController
{
    protected $format = 'json';

    protected BaseConnection $db;
    protected PmsQuestionModel $questions;

    /**
     * ==========================================
     * Constructor
     * ==========================================
     */
    public function __construct()
    {
        $this->db = \Config\Database::connect();
        $this->questions = new PmsQuestionModel();

        helper('security');
    }

    /**
     * ==========================================
     * List PMS Questions
     * ==========================================
     */
    public function index()
    {
        $rows = $this->questions
            ->select('id, question_text, question_type, sort_order, is_active, created_at, updated_at, deleted_at')
            ->where('deleted_at', null)
            ->orderBy('sort_order', 'ASC')
            ->orderBy('id', 'ASC')
            ->findAll();

        $rows = array_values(array_map(function ($row, $index) {
            $row['cnt'] = $index + 1;
            $row['id']  = encrypt_id($row['id']);
            return $row;
        }, $rows, array_keys($rows)));

        return $this->respond(['questions' => $rows]);
    }

    /**
     * ==========================================
     * Create PMS Question
     * ==========================================
     */
    public function create()
    {
        try {
            $data = $this->request->getJSON(true) ?? [];

            if (! $this->validateData($data, [
                'question_text' => 'required|max_length[500]',
                'question_type' => 'required|in_list[yes_no]',
                'sort_order'    => 'permit_empty|integer',
                'is_active'     => 'permit_empty|in_list[0,1]',
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

            $insert = [
                'question_text' => trim($data['question_text']),
                'question_type' => $data['question_type'],
                'sort_order'    => array_key_exists('sort_order', $data) && $data['sort_order'] !== '' ? (int) $data['sort_order'] : 0,
                'is_active'     => array_key_exists('is_active', $data) ? (int) $data['is_active'] : 1,
            ];

            $inserted = $this->questions->insert($insert);

            if (! $inserted) {
                throw new \RuntimeException('Failed to create PMS question.');
            }

            $newId = (int) $this->questions->getInsertID();

            service('audit')->log('pms_questions.create', 'pms_questions', $newId, [
                'question_text' => $insert['question_text'],
                'question_type' => $insert['question_type'],
                'sort_order'    => $insert['sort_order'],
                'is_active'     => $insert['is_active'],
            ]);

            return $this->respondCreated([
                'message' => 'PMS question created',
                'id'      => $newId,
            ]);
        } catch (\Throwable $e) {
            log_message('error', 'Create PMS Question Error: ' . $e->getMessage());

            return $this->respond([
                'status'  => 500,
                'error'   => 500,
                'message' => 'Failed to create PMS question.',
                'details' => ENVIRONMENT === 'development' ? $e->getMessage() : null,
            ], 500);
        }
    }

    /**
     * ==========================================
     * Update PMS Question
     * ==========================================
     */
    public function update($id = null)
    {
        try {
            if (! $id) {
                return $this->failValidationError('Invalid PMS question ID.');
            }

            try {
                $id = decrypt_id($id);
            } catch (\Throwable $e) {
                return $this->failValidationError('Invalid PMS question ID.');
            }

            if (! is_numeric($id)) {
                return $this->failValidationError('Invalid PMS question ID.');
            }

            $question = $this->questions
                ->where('deleted_at', null)
                ->find($id);

            if (! $question) {
                return $this->failNotFound('PMS question not found.');
            }

            $data = $this->request->getJSON(true) ?? [];

            if (! $this->validateData($data, [
                'question_text' => 'required|max_length[500]',
                'question_type' => 'required|in_list[yes_no]',
                'sort_order'    => 'permit_empty|integer',
                'is_active'     => 'permit_empty|in_list[0,1]',
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

            $update = [
                'question_text' => trim($data['question_text']),
                'question_type' => $data['question_type'],
                'sort_order'    => array_key_exists('sort_order', $data) && $data['sort_order'] !== '' ? (int) $data['sort_order'] : 0,
                'is_active'     => array_key_exists('is_active', $data) ? (int) $data['is_active'] : 1,
            ];

            $updated = $this->questions->update($id, $update);

            if (! $updated) {
                throw new \RuntimeException('Failed to update PMS question.');
            }

            service('audit')->log('pms_questions.update', 'pms_questions', $id, [
                'fields' => array_keys($update),
                'before' => [
                    'question_text' => $question['question_text'] ?? null,
                    'question_type' => $question['question_type'] ?? null,
                    'sort_order'    => $question['sort_order'] ?? null,
                    'is_active'     => $question['is_active'] ?? null,
                ],
                'after' => $update,
            ]);

            return $this->respond(['message' => 'PMS question updated']);
        } catch (\Throwable $e) {
            log_message('error', 'Update PMS Question Error: ' . $e->getMessage());

            return $this->respond([
                'status'  => 500,
                'error'   => 500,
                'message' => 'Failed to update PMS question.',
                'details' => ENVIRONMENT === 'development' ? $e->getMessage() : null,
            ], 500);
        }
    }

    /**
     * ==========================================
     * Delete PMS Question
     * ==========================================
     */
    public function delete($id = null)
    {
        try {
            if (! $id) {
                return $this->failValidationError('Invalid PMS question ID.');
            }

            try {
                $id = decrypt_id($id);
            } catch (\Throwable $e) {
                return $this->failValidationError('Invalid PMS question ID.');
            }

            if (! is_numeric($id)) {
                return $this->failValidationError('Invalid PMS question ID.');
            }

            $question = $this->questions
                ->select('id, question_text, question_type')
                ->where('deleted_at', null)
                ->find($id);

            if (! $question) {
                return $this->failNotFound('PMS question not found.');
            }

            $isUsed = $this->db->table('pms_record_answers')
                ->where('question_id', $id)
                ->where('deleted_at', null)
                ->countAllResults() > 0;

            if ($isUsed) {
                return $this->fail([
                    'message' => 'PMS question cannot be deleted because it is already used in PMS records.',
                ], 409);
            }

            $deleted = $this->questions->delete($id);

            if (! $deleted) {
                throw new \RuntimeException('Failed to delete PMS question.');
            }

            service('audit')->log('pms_questions.delete', 'pms_questions', $id, [
                'question_text' => $question['question_text'] ?? null,
                'question_type' => $question['question_type'] ?? null,
            ]);

            return $this->respond(['message' => 'PMS question deleted']);
        } catch (\Throwable $e) {
            log_message('error', 'Delete PMS Question Error: ' . $e->getMessage());

            return $this->respond([
                'status'  => 500,
                'error'   => 500,
                'message' => 'Failed to delete PMS question.',
                'details' => ENVIRONMENT === 'development' ? $e->getMessage() : null,
            ], 500);
        }
    }

    /**
     * ==========================================
     * List Active PMS Questions
     * ==========================================
     */
    public function active()
    {
        $rows = $this->questions
            ->select('id, question_text, question_type, sort_order')
            ->where('is_active', 1)
            ->where('deleted_at', null)
            ->orderBy('sort_order', 'ASC')
            ->orderBy('id', 'ASC')
            ->findAll();

        $rows = array_values(array_map(function ($row, $index) {
            $row['cnt'] = $index + 1;
            $row['id']  = encrypt_id($row['id']);
            return $row;
        }, $rows, array_keys($rows)));

        return $this->respond(['questions' => $rows]);
    }
}
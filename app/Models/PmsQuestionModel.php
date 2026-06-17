<?php

namespace App\Models;

use CodeIgniter\Model;

class PmsQuestionModel extends Model
{
    protected $table            = 'pms_questions';
    protected $primaryKey       = 'id';
    protected $returnType       = 'array';
    protected $useSoftDeletes   = true;
    protected $useTimestamps    = true;

    protected $allowedFields = [
        'question_text',
        'question_type',
        'sort_order',
        'is_active',
    ];

    /**
     * Get all active PMS questions ordered by sort_order.
     */
    public function getActiveQuestions(): array
    {
        return $this->select('id, question_text, sort_order')
            ->where('is_active', 1)
            ->orderBy('sort_order', 'ASC')
            ->findAll();
    }

    /**
     * Get questions with answer data for a specific PMS record.
     */
    public function getQuestionsWithAnswers(int $pmsRecordId): array
    {
        return $this->db->table($this->table . ' q')
            ->select([
                'q.id as question_id',
                'q.question_text',
                'q.sort_order',
                'a.answer',
                'a.remarks',
                'a.question_text_snapshot',
            ])
            ->join(
                'pms_record_answers a',
                'a.question_id = q.id
                AND a.pms_record_id = ' . $this->db->escape($pmsRecordId) . '
                AND a.deleted_at IS NULL',
                'left'
            )
            ->where('q.is_active', 1)
            ->orderBy('q.sort_order', 'ASC')
            ->get()
            ->getResultArray();
    }
}
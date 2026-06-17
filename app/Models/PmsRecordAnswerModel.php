<?php

namespace App\Models;

use CodeIgniter\Model;

class PmsRecordAnswerModel extends Model
{
    protected $table          = 'pms_record_answers';
    protected $primaryKey     = 'id';
    protected $returnType     = 'array';
    protected $useSoftDeletes = true;
    protected $useTimestamps  = true;

    protected $allowedFields = [
        'pms_record_id',
        'question_id',
        'question_text_snapshot',
        'answer',
        'remarks',
    ];

    /**
     * Get all answers for one PMS record.
     */
    public function getByRecordId(int $pmsRecordId): array
    {
        return $this->where('pms_record_id', $pmsRecordId)
            ->where('deleted_at', null)
            ->findAll();
    }

    /**
     * Get one answer by PMS record + question.
     */
    public function getByRecordAndQuestion(int $pmsRecordId, int $questionId): ?array
    {
        return $this->where('pms_record_id', $pmsRecordId)
            ->where('question_id', $questionId)
            ->where('deleted_at', null)
            ->first();
    }

    /**
     * Save or update an answer for a PMS record/question pair.
     */
    public function saveAnswer(int $pmsRecordId, int $questionId, array $data): bool
    {
        $existing = $this->getByRecordAndQuestion($pmsRecordId, $questionId);

        $payload = [
            'pms_record_id'          => $pmsRecordId,
            'question_id'            => $questionId,
            'answer'                 => $data['answer'] ?? null,
            'remarks'                => $data['remarks'] ?? null,
            'question_text_snapshot' => $data['question_text_snapshot'] ?? null,
        ];

        if ($existing) {
            return $this->update($existing['id'], $payload);
        }

        return (bool) $this->insert($payload);
    }
}
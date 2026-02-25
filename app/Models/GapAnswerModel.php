<?php

namespace App\Models;

use CodeIgniter\Model;

/**
 * Records and retrieves individual quiz answers for a gap session.
 */
class GapAnswerModel extends Model
{
    protected $table         = 'gap_answers';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $useTimestamps = true;
    protected $allowedFields = [
        'session_id', 'control_id', 'choice_id', 'choice_key',
        'score_pct', 'status', 'justification', 'other_text',
        'is_manual_review', 'answered_by',
    ];

    // ── Public API ─────────────────────────────────────────────────────────

    /**
     * Insert or update an answer for (session, control).
     * Loads score / status from the chosen control_choices row.
     *
     * @return array  The saved answer row.
     * @throws \RuntimeException  if the choiceId does not exist.
     */
    public function upsert(
        int $sessionId,
        int $controlId,
        int $choiceId,
        string $justification,
        string $otherText,
        int $userId
    ): array {
        // Load the choice metadata so status/score are never user-supplied
        $choice = $this->db->table('control_choices')
            ->where('id', $choiceId)
            ->get()->getRowArray();

        if (! $choice) {
            throw new \RuntimeException("Choix introuvable : #{$choiceId}");
        }

        $data = [
            'session_id'       => $sessionId,
            'control_id'       => $controlId,
            'choice_id'        => $choiceId,
            'choice_key'       => $choice['choice_key'],
            'score_pct'        => $choice['score_pct'],
            'status'           => $choice['status'],
            'justification'    => trim($justification),
            'other_text'       => trim($otherText),
            'is_manual_review' => (int) $choice['is_manual_review'],
            'answered_by'      => $userId,
        ];

        // Upsert: update if the answer already exists for this (session, control)
        $existing = $this->where('session_id', $sessionId)
                         ->where('control_id', $controlId)
                         ->first();

        if ($existing) {
            $this->update($existing['id'], $data);
            return $this->find($existing['id']);
        }

        $id = $this->insert($data, true);
        return $this->find($id);
    }

    /**
     * Returns all answers for a session, indexed by control_id.
     */
    public function forSession(int $sessionId): array
    {
        $rows = $this->where('session_id', $sessionId)->findAll();
        $indexed = [];
        foreach ($rows as $row) {
            $indexed[(int) $row['control_id']] = $row;
        }
        return $indexed;
    }

    /**
     * Per-domain score breakdown for the summary page.
     *
     * Returns an array of rows with:
     *   domain_id, domain_name, domain_code,
     *   total, answered, manual_review, avg_score
     */
    public function domainBreakdown(int $sessionId): array
    {
        return $this->db->table('gap_answers ga')
            ->select([
                'd.id   AS domain_id',
                'd.name AS domain_name',
                'd.code AS domain_code',
                'COUNT(c.id)             AS total',
                'COUNT(ga.id)            AS answered',
                'SUM(ga.is_manual_review) AS manual_review',
                'IFNULL(AVG(ga.score_pct), 0) AS avg_score',
            ])
            ->join('controls c',  'c.id  = ga.control_id')
            ->join('clauses cl',  'cl.id = c.clause_id')
            ->join('domains d',   'd.id  = cl.domain_id')
            ->where('ga.session_id', $sessionId)
            ->groupBy('d.id')
            ->orderBy('d.code', 'ASC')
            ->get()->getResultArray();
    }

    /**
     * Returns answers flagged for manual review, with control context.
     */
    public function manualReviewItems(int $sessionId): array
    {
        return $this->db->table('gap_answers ga')
            ->select([
                'ga.*',
                'c.code  AS control_code',
                'c.title AS control_title',
            ])
            ->join('controls c', 'c.id = ga.control_id')
            ->where('ga.session_id', $sessionId)
            ->where('ga.is_manual_review', 1)
            ->orderBy('c.code', 'ASC')
            ->get()->getResultArray();
    }
}

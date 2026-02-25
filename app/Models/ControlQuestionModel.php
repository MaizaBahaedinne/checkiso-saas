<?php

namespace App\Models;

use CodeIgniter\Model;

/**
 * Fetches the quiz question (and its choices) for a given control.
 */
class ControlQuestionModel extends Model
{
    protected $table      = 'control_questions';
    protected $primaryKey = 'id';
    protected $returnType = 'array';

    /**
     * Returns the question row + an ordered array of choices for one control.
     * Returns null when no question has been seeded for that control.
     */
    public function forControl(int $controlId): ?array
    {
        $question = $this->where('control_id', $controlId)->first();
        if (! $question) {
            return null;
        }

        $choices = $this->db->table('control_choices')
            ->where('question_id', $question['id'])
            ->orderBy('sort_order', 'ASC')
            ->get()->getResultArray();

        $question['choices'] = $choices;
        return $question;
    }
}

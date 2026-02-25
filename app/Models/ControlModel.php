<?php

namespace App\Models;

use CodeIgniter\Model;

class ControlModel extends Model
{
    protected $table      = 'controls';
    protected $primaryKey = 'id';
    protected $returnType = 'array';

    /**
     * Return all controls for a clause, ordered by code.
     */
    public function forClause(int $clauseId): array
    {
        return $this->where('clause_id', $clauseId)
            ->orderBy('code', 'ASC')
            ->findAll();
    }

    /**
     * Return all controls for a standard version in one query.
     * Result is keyed by clause_id for easy grouping in views.
     */
    public function forVersion(int $versionId): array
    {
        $rows = $this->db->table('controls ctrl')
            ->select('ctrl.*')
            ->join('clauses c',  'c.id = ctrl.clause_id')
            ->join('domains d',  'd.id = c.domain_id')
            ->where('d.standard_version_id', $versionId)
            ->orderBy('ctrl.code', 'ASC')
            ->get()
            ->getResultArray();

        // Group by clause_id
        $grouped = [];
        foreach ($rows as $row) {
            $grouped[$row['clause_id']][] = $row;
        }

        return $grouped;
    }
}

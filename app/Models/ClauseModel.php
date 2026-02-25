<?php

namespace App\Models;

use CodeIgniter\Model;

class ClauseModel extends Model
{
    protected $table      = 'clauses';
    protected $primaryKey = 'id';
    protected $returnType = 'array';

    /**
     * Return all clauses for a domain, ordered by code.
     */
    public function forDomain(int $domainId): array
    {
        return $this->where('domain_id', $domainId)
            ->orderBy('code', 'ASC')
            ->findAll();
    }

    /**
     * Return all clauses for a standard version, grouped by domain_id.
     * Useful to load everything in one query.
     */
    public function forVersion(int $versionId): array
    {
        return $this->db->table('clauses c')
            ->select('c.*')
            ->join('domains d', 'd.id = c.domain_id')
            ->where('d.standard_version_id', $versionId)
            ->orderBy('c.code', 'ASC')
            ->get()
            ->getResultArray();
    }
}

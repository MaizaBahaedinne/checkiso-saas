<?php

namespace App\Models;

use CodeIgniter\Model;

class DomainModel extends Model
{
    protected $table      = 'domains';
    protected $primaryKey = 'id';
    protected $returnType = 'array';

    /**
     * Return all domains for a standard version, ordered by their code.
     */
    public function forVersion(int $versionId): array
    {
        return $this->where('standard_version_id', $versionId)
            ->orderBy('code', 'ASC')
            ->findAll();
    }
}

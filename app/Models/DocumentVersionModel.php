<?php

namespace App\Models;

use CodeIgniter\Model;

class DocumentVersionModel extends Model
{
    protected $table         = 'document_versions';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $useTimestamps = true;
    protected $updatedField  = '';   // no updated_at on versions

    protected $allowedFields = [
        'document_id', 'version_number', 'title', 'content',
        'change_summary', 'changed_by',
    ];

    /**
     * All versions for a document, newest first, with author name.
     */
    public function forDocument(int $documentId): array
    {
        return $this->db->table('document_versions dv')
            ->select('dv.*, u.first_name, u.last_name')
            ->join('users u', 'u.id = dv.changed_by', 'left')
            ->where('dv.document_id', $documentId)
            ->orderBy('dv.version_number', 'DESC')
            ->get()->getResultArray();
    }

    /**
     * A specific version of a document, with author.
     */
    public function getVersion(int $documentId, int $versionNumber): ?array
    {
        return $this->db->table('document_versions dv')
            ->select('dv.*, u.first_name, u.last_name')
            ->join('users u', 'u.id = dv.changed_by', 'left')
            ->where('dv.document_id', $documentId)
            ->where('dv.version_number', $versionNumber)
            ->get()->getRowArray() ?: null;
    }
}

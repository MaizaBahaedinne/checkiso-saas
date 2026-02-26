<?php

namespace App\Models;

use CodeIgniter\Model;

class DocumentModel extends Model
{
    protected $table          = 'documents';
    protected $primaryKey     = 'id';
    protected $returnType     = 'array';
    protected $useSoftDeletes = true;
    protected $useTimestamps  = true;

    protected $allowedFields = [
        'tenant_id', 'title', 'slug', 'category', 'description',
        'content', 'current_version', 'created_by',
    ];

    public function forTenant(int $tenantId, ?string $category = null, ?string $search = null): array
    {
        $this->where('tenant_id', $tenantId);

        if ($category) {
            $this->where('category', $category);
        }

        if ($search) {
            $this->groupStart()
                 ->like('title', $search)
                 ->orLike('description', $search)
                 ->groupEnd();
        }

        return $this->orderBy('updated_at', 'DESC')->findAll();
    }

    public function forTenantById(int $tenantId, int $docId): ?array
    {
        return $this->where('tenant_id', $tenantId)->find($docId);
    }

    public function categoryStats(int $tenantId): array
    {
        $rows = $this->db->table('documents')
            ->select('category, COUNT(*) as count')
            ->where('tenant_id', $tenantId)
            ->where('deleted_at IS NULL', null, false)
            ->groupBy('category')
            ->get()->getResultArray();

        return array_column($rows, 'count', 'category');
    }

    public function uniqueSlug(int $tenantId, string $slug): string
    {
        $base = $slug;
        $i    = 1;
        while (true) {
            $exists = $this->db->table('documents')
                ->where('tenant_id', $tenantId)
                ->where('slug', $slug)
                ->where('deleted_at IS NULL', null, false)
                ->countAllResults();
            if (! $exists) break;
            $slug = $base . '-' . $i++;
        }
        return $slug;
    }
}

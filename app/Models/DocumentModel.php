<?php

namespace App\Models;

use CodeIgniter\Model;

class DocumentModel extends Model
{
    protected $table         = 'documents';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $useSoftDeletes = true;
    protected $useTimestamps = true;

    protected $allowedFields = [
        'tenant_id', 'title', 'description', 'category',
        'file_name', 'file_path', 'file_size', 'mime_type',
        'linked_control_id', 'linked_action_plan_id', 'uploaded_by',
    ];

    protected $validationRules = [
        'tenant_id' => 'required|is_natural_no_zero',
        'title'     => 'required|min_length[2]|max_length[255]',
        'category'  => 'required|in_list[policy,procedure,evidence,template,other]',
        'file_name' => 'required',
        'file_path' => 'required',
    ];

    /**
     * Returns all non-deleted documents for a tenant, newest first.
     * Optionally filter by category.
     */
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

        return $this->orderBy('created_at', 'DESC')->findAll();
    }

    /**
     * Returns a single document scoped to a tenant (prevents cross-tenant access).
     */
    public function forTenantById(int $tenantId, int $docId): ?array
    {
        return $this->where('tenant_id', $tenantId)->find($docId);
    }

    /**
     * Count documents per category for a tenant.
     */
    public function categoryStats(int $tenantId): array
    {
        $rows = $this->db->table('documents')
            ->select('category, COUNT(*) as count')
            ->where('tenant_id', $tenantId)
            ->whereNull('deleted_at')
            ->groupBy('category')
            ->get()->getResultArray();

        return array_column($rows, 'count', 'category');
    }
}

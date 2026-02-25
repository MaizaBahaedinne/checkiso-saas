<?php

namespace App\Models;

use CodeIgniter\Model;

class StandardVersionModel extends Model
{
    protected $table         = 'standard_versions';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $useSoftDeletes = false;
    protected $allowedFields = ['standard_id', 'version_code', 'published_year', 'is_active', 'description'];

    // -------------------------------------------------------------------------
    // Queries
    // -------------------------------------------------------------------------

    /**
     * Return all active standard versions joined with standard name/code.
     */
    public function getActive(): array
    {
        return $this->db->table('standard_versions sv')
            ->select('sv.*, s.name AS standard_name, s.code AS standard_code, s.organization AS standard_organization')
            ->join('standards s', 's.id = sv.standard_id')
            ->where('sv.is_active', 1)
            ->orderBy('s.code', 'ASC')
            ->orderBy('sv.published_year', 'DESC')
            ->get()
            ->getResultArray();
    }

    /**
     * Return standard versions subscribed by a given tenant.
     */
    public function forTenant(int $tenantId): array
    {
        return $this->db->table('tenant_standards ts')
            ->select('sv.*, s.name AS standard_name, s.code AS standard_code, s.organization AS standard_organization, ts.id AS subscription_id, ts.created_at AS subscribed_at')
            ->join('standard_versions sv', 'sv.id = ts.standard_version_id')
            ->join('standards s', 's.id = sv.standard_id')
            ->where('ts.tenant_id', $tenantId)
            ->orderBy('s.code', 'ASC')
            ->get()
            ->getResultArray();
    }

    /**
     * Check if a tenant is subscribed to a specific version.
     */
    public function isSubscribed(int $tenantId, int $versionId): bool
    {
        $row = $this->db->table('tenant_standards')
            ->where('tenant_id', $tenantId)
            ->where('standard_version_id', $versionId)
            ->get()
            ->getRowArray();

        return $row !== null;
    }

    /**
     * Subscribe a tenant to a standard version.
     * Returns false if already subscribed.
     */
    public function subscribe(int $tenantId, int $versionId, int $subscribedBy): bool
    {
        if ($this->isSubscribed($tenantId, $versionId)) {
            return false;
        }

        $this->db->table('tenant_standards')->insert([
            'tenant_id'           => $tenantId,
            'standard_version_id' => $versionId,
            'subscribed_by'       => $subscribedBy,
            'created_at'          => date('Y-m-d H:i:s'),
            'updated_at'          => date('Y-m-d H:i:s'),
        ]);

        return true;
    }

    /**
     * Unsubscribe a tenant from a standard version.
     */
    public function unsubscribe(int $tenantId, int $versionId): bool
    {
        $this->db->table('tenant_standards')
            ->where('tenant_id', $tenantId)
            ->where('standard_version_id', $versionId)
            ->delete();

        return true;
    }

    /**
     * Fetch a single version with its standard info.
     */
    public function getWithStandard(int $versionId): ?array
    {
        $row = $this->db->table('standard_versions sv')
            ->select('sv.*, s.name AS standard_name, s.code AS standard_code, s.organization AS standard_organization, s.description AS standard_description')
            ->join('standards s', 's.id = sv.standard_id')
            ->where('sv.id', $versionId)
            ->get()
            ->getRowArray();

        return $row ?: null;
    }
}

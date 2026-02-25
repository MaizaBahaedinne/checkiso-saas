<?php

namespace App\Models;

use CodeIgniter\Model;

/**
 * Action Plan model — corrective tasks linked to a tenant's gap analysis.
 *
 * Every query must be scoped to a tenant_id for multi-tenancy safety.
 */
class ActionPlanModel extends Model
{
    protected $table            = 'action_plans';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = true;
    protected $protectFields    = true;
    protected $allowedFields    = [
        'tenant_id',
        'gap_session_id',
        'control_id',
        'title',
        'description',
        'owner_user_id',
        'due_date',
        'priority',
        'status',
        'created_by',
    ];

    protected $useTimestamps = true;
    protected $dateFormat    = 'datetime';
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';
    protected $deletedField  = 'deleted_at';

    // -------------------------------------------------------------------------
    // Fetch all action plans for a tenant, enriched with owner name + control code
    // -------------------------------------------------------------------------
    public function forTenant(int $tenantId): array
    {
        return $this->db->table('action_plans ap')
            ->select('ap.*')
            ->select('CONCAT(u.first_name, " ", u.last_name) AS owner_name')
            ->select('c.code AS control_code, c.title AS control_title')
            ->select('cb.first_name AS creator_first, cb.last_name AS creator_last')
            ->join('users u',    'u.id = ap.owner_user_id', 'left')
            ->join('users cb',   'cb.id = ap.created_by', 'left')
            ->join('controls c', 'c.id = ap.control_id', 'left')
            ->where('ap.tenant_id', $tenantId)
            ->where('ap.deleted_at IS NULL')
            ->orderBy('ap.due_date', 'ASC')
            ->orderBy('ap.priority', 'DESC')
            ->get()->getResultArray();
    }

    // -------------------------------------------------------------------------
    // Single plan (must belong to tenant)
    // -------------------------------------------------------------------------
    public function forTenantById(int $tenantId, int $id): ?array
    {
        $row = $this->db->table('action_plans ap')
            ->select('ap.*')
            ->select('CONCAT(u.first_name, " ", u.last_name) AS owner_name')
            ->select('c.code AS control_code, c.title AS control_title')
            ->join('users u',    'u.id = ap.owner_user_id', 'left')
            ->join('controls c', 'c.id = ap.control_id', 'left')
            ->where('ap.tenant_id', $tenantId)
            ->where('ap.id', $id)
            ->where('ap.deleted_at IS NULL')
            ->get()->getRowArray();

        return $row ?: null;
    }

    // -------------------------------------------------------------------------
    // Stats: total, done, overdue — for a tenant (used by dashboard)
    // -------------------------------------------------------------------------
    public function statsForTenant(int $tenantId): array
    {
        $rows = $this->db->table('action_plans')
            ->select('status, due_date')
            ->where('tenant_id', $tenantId)
            ->where('deleted_at IS NULL')
            ->get()->getResultArray();

        $total   = count($rows);
        $done    = 0;
        $overdue = 0;
        $today   = date('Y-m-d');

        foreach ($rows as $r) {
            if ($r['status'] === 'done') {
                $done++;
            } elseif ($r['due_date'] && $r['due_date'] < $today && $r['status'] !== 'done') {
                $overdue++;
            }
        }

        return [
            'total'   => $total,
            'done'    => $done,
            'overdue' => $overdue,
            'open'    => $total - $done,
        ];
    }
}

<?php

namespace App\Models;

use CodeIgniter\Model;

/**
 * Manages gap-analysis sessions (one per tenant × standard version).
 */
class GapSessionModel extends Model
{
    protected $table         = 'gap_sessions';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $useTimestamps = true;
    protected $allowedFields = [
        'tenant_id', 'standard_version_id', 'status',
        'total_controls', 'answered_controls', 'score',
        'submitted_by', 'submitted_at',
    ];

    // ── Public API ─────────────────────────────────────────────────────────

    /**
     * Returns the existing draft/submitted session for (tenant, version),
     * or creates a fresh draft if none exists.
     */
    public function getOrCreate(int $tenantId, int $versionId): array
    {
        $session = $this->where('tenant_id', $tenantId)
                        ->where('standard_version_id', $versionId)
                        ->first();

        if ($session) {
            return $session;
        }

        // Count total controls for this version
        $total = $this->db->table('controls c')
            ->join('clauses cl', 'cl.id = c.clause_id')
            ->join('domains d',  'd.id = cl.domain_id')
            ->where('d.standard_version_id', $versionId)
            ->countAllResults();

        $id = $this->insert([
            'tenant_id'           => $tenantId,
            'standard_version_id' => $versionId,
            'status'              => 'draft',
            'total_controls'      => $total,
            'answered_controls'   => 0,
            'score'               => 0,
        ], true);

        return $this->find($id);
    }

    /**
     * Recomputes answered_controls and score from gap_answers, then saves.
     */
    public function updateProgress(int $sessionId): array
    {
        $stats = $this->db->table('gap_answers')
            ->select('COUNT(*) AS answered, IFNULL(AVG(score_pct),0) AS avg_score')
            ->where('session_id', $sessionId)
            ->get()->getRowArray();

        $this->update($sessionId, [
            'answered_controls' => (int) $stats['answered'],
            'score'             => round((float) $stats['avg_score'], 2),
        ]);

        return $this->find($sessionId);
    }

    /**
     * Marks the session as submitted (only if 100 % answered).
     * Returns ['ok'=>bool, 'message'=>string, 'session'=>array].
     */
    public function finalize(int $sessionId, int $userId): array
    {
        $session = $this->updateProgress($sessionId);

        if ((int) $session['answered_controls'] < (int) $session['total_controls']) {
            return [
                'ok'      => false,
                'message' => 'Toutes les questions doivent être répondues avant la soumission.',
                'session' => $session,
            ];
        }

        $this->update($sessionId, [
            'status'       => 'submitted',
            'submitted_by' => $userId,
            'submitted_at' => date('Y-m-d H:i:s'),
        ]);

        return [
            'ok'      => true,
            'message' => 'Évaluation soumise avec succès.',
            'session' => $this->find($sessionId),
        ];
    }

    /**
     * Returns all sessions for a tenant, joined to standard / version info.
     */
    public function forTenant(int $tenantId): array
    {
        return $this->db->table('gap_sessions gs')
            ->select([
                'gs.*',
                'sv.version  AS version_code',
                'YEAR(sv.published_at) AS published_year',
                's.name       AS standard_name',
                's.code       AS standard_code',
            ])
            ->join('standard_versions sv', 'sv.id = gs.standard_version_id')
            ->join('standards s',          's.id  = sv.standard_id')
            ->where('gs.tenant_id', $tenantId)
            ->orderBy('gs.updated_at', 'DESC')
            ->get()->getResultArray();
    }

    /**
     * Returns a single session with standard metadata, checking tenant ownership.
     */
    public function getForTenant(int $sessionId, int $tenantId): ?array
    {
        $row = $this->db->table('gap_sessions gs')
            ->select([
                'gs.*',
                'sv.version  AS version_code',
                'YEAR(sv.published_at) AS published_year',
                's.name       AS standard_name',
                's.code       AS standard_code',
            ])
            ->join('standard_versions sv', 'sv.id = gs.standard_version_id')
            ->join('standards s',          's.id  = sv.standard_id')
            ->where('gs.id', $sessionId)
            ->where('gs.tenant_id', $tenantId)
            ->get()->getRowArray();

        return $row ?: null;
    }
}

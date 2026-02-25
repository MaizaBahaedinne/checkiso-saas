<?php

namespace App\Models;

use CodeIgniter\Model;

class ControlAssessmentModel extends Model
{
    protected $table      = 'control_assessments';
    protected $primaryKey = 'id';
    protected $returnType = 'array';

    protected $allowedFields = [
        'tenant_id', 'standard_version_id', 'control_id',
        'status', 'notes', 'assessed_by', 'assessed_at',
        'created_at', 'updated_at',
    ];

    public const STATUSES = ['conforme', 'partiel', 'non_conforme', 'na'];

    // -------------------------------------------------------------------------
    // Read
    // -------------------------------------------------------------------------

    /**
     * All assessments for a tenant+version, indexed by control_id.
     */
    public function forTenantVersion(int $tenantId, int $versionId): array
    {
        $rows = $this->where('tenant_id', $tenantId)
            ->where('standard_version_id', $versionId)
            ->findAll();

        $indexed = [];
        foreach ($rows as $row) {
            $indexed[$row['control_id']] = $row;
        }
        return $indexed;
    }

    // -------------------------------------------------------------------------
    // Write
    // -------------------------------------------------------------------------

    /**
     * Insert or update a single assessment using MySQL's ON DUPLICATE KEY UPDATE
     * (relies on the unique key tenant_id+control_id in the DB).
     */
    public function upsert(
        int    $tenantId,
        int    $versionId,
        int    $controlId,
        string $status,
        string $notes,
        int    $userId
    ): void {
        $now = date('Y-m-d H:i:s');

        $this->db->query(
            "INSERT INTO control_assessments
                (tenant_id, standard_version_id, control_id, status, notes, assessed_by, assessed_at, created_at, updated_at)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
             ON DUPLICATE KEY UPDATE
                status      = VALUES(status),
                notes       = VALUES(notes),
                assessed_by = VALUES(assessed_by),
                assessed_at = VALUES(assessed_at),
                updated_at  = VALUES(updated_at)",
            [$tenantId, $versionId, $controlId, $status, $notes, $userId, $now, $now, $now]
        );
    }

    /**
     * Remove an assessment (allows "undo" from the UI).
     */
    public function remove(int $tenantId, int $controlId): void
    {
        $this->where('tenant_id', $tenantId)
            ->where('control_id', $controlId)
            ->delete();
    }

    // -------------------------------------------------------------------------
    // Stats
    // -------------------------------------------------------------------------

    /**
     * Global stats for the version: total, assessed, progress %, score, status counts.
     */
    public function getGlobalStats(int $tenantId, int $versionId): array
    {
        $total = (int) $this->db->table('controls ctrl')
            ->join('clauses c', 'c.id = ctrl.clause_id')
            ->join('domains d', 'd.id = c.domain_id')
            ->where('d.standard_version_id', $versionId)
            ->countAllResults();

        $byStatus = $this->db->table('control_assessments')
            ->select('status, COUNT(id) AS cnt')
            ->where('tenant_id', $tenantId)
            ->where('standard_version_id', $versionId)
            ->groupBy('status')
            ->get()->getResultArray();

        $counts = ['conforme' => 0, 'partiel' => 0, 'non_conforme' => 0, 'na' => 0];
        $assessed = 0;
        foreach ($byStatus as $row) {
            $counts[$row['status']] = (int) $row['cnt'];
            $assessed += (int) $row['cnt'];
        }

        $evaluated = $total - $counts['na'];
        $score = $evaluated > 0
            ? (int) round(($counts['conforme'] * 100 + $counts['partiel'] * 50) / $evaluated)
            : null;

        return [
            'total'        => $total,
            'assessed'     => $assessed,
            'progress'     => $total > 0 ? (int) round($assessed / $total * 100) : 0,
            'score'        => $score,
            'conforme'     => $counts['conforme'],
            'partiel'      => $counts['partiel'],
            'non_conforme' => $counts['non_conforme'],
            'na'           => $counts['na'],
        ];
    }

    /**
     * Per-domain stats: total, assessed, progress %, score, status counts.
     * Returns array of domain stat rows.
     */
    public function getStats(int $tenantId, int $versionId): array
    {
        // Total controls per domain
        $totals = $this->db->table('controls ctrl')
            ->select('d.id AS domain_id, d.name AS domain_name, d.code AS domain_code, COUNT(ctrl.id) AS total')
            ->join('clauses c', 'c.id = ctrl.clause_id')
            ->join('domains d', 'd.id = c.domain_id')
            ->where('d.standard_version_id', $versionId)
            ->groupBy('d.id')
            ->orderBy('d.code', 'ASC')
            ->get()->getResultArray();

        // Assessed breakdown per domain+status
        $assessed = $this->db->table('control_assessments ca')
            ->select('d.id AS domain_id, ca.status, COUNT(ca.id) AS cnt')
            ->join('controls ctrl', 'ctrl.id = ca.control_id')
            ->join('clauses c', 'c.id = ctrl.clause_id')
            ->join('domains d', 'd.id = c.domain_id')
            ->where('ca.tenant_id', $tenantId)
            ->where('ca.standard_version_id', $versionId)
            ->groupBy(['d.id', 'ca.status'])
            ->get()->getResultArray();

        // Build index
        $stats = [];
        foreach ($totals as $row) {
            $stats[$row['domain_id']] = [
                'domain_id'      => (int) $row['domain_id'],
                'domain_name'    => $row['domain_name'],
                'domain_code'    => $row['domain_code'],
                'total'          => (int) $row['total'],
                'conforme'       => 0,
                'partiel'        => 0,
                'non_conforme'   => 0,
                'na'             => 0,
                'assessed_total' => 0,
                'score'          => null,
                'progress'       => 0,
            ];
        }

        foreach ($assessed as $row) {
            $did = (int) $row['domain_id'];
            if (isset($stats[$did])) {
                $stats[$did][$row['status']] += (int) $row['cnt'];
                $stats[$did]['assessed_total'] += (int) $row['cnt'];
            }
        }

        foreach ($stats as &$s) {
            $evaluated = $s['total'] - $s['na'];
            if ($evaluated > 0 && $s['assessed_total'] > 0) {
                $s['score'] = (int) round(
                    ($s['conforme'] * 100 + $s['partiel'] * 50) / $evaluated
                );
            }
            $s['progress'] = $s['total'] > 0
                ? (int) round($s['assessed_total'] / $s['total'] * 100)
                : 0;
        }
        unset($s);

        return array_values($stats);
    }
}

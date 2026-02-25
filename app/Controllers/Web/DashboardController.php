<?php

namespace App\Controllers\Web;

use App\Controllers\BaseController;
use App\Models\StandardVersionModel;
use App\Models\GapSessionModel;
use App\Models\ActionPlanModel;

class DashboardController extends BaseController
{
    public function index(): string
    {
        $tenantId = (int) session()->get('tenant_id');
        $db       = \Config\Database::connect();

        // ── Subscribed standards (with version info) ───────────────────────
        $svModel       = new StandardVersionModel();
        $subscriptions = $svModel->forTenant($tenantId);

        // ── Gap sessions for this tenant (indexed by standard_version_id) ──
        $gsModel  = new GapSessionModel();
        $sessions = $gsModel->forTenant($tenantId);
        $sessionsByVersionId = array_column($sessions, null, 'standard_version_id');

        // Merge gap session into each subscription row
        foreach ($subscriptions as &$sv) {
            $sv['gap_session'] = $sessionsByVersionId[$sv['id']] ?? null;
        }
        unset($sv);

        // ── Total controls across ALL subscribed standards (single query) ──
        $totalControls = 0;
        if (! empty($subscriptions)) {
            $versionIds = array_column($subscriptions, 'id');
            $totalControls = (int) $db->table('controls c')
                ->join('clauses cl', 'cl.id = c.clause_id')
                ->join('domains d',  'd.id = cl.domain_id')
                ->whereIn('d.standard_version_id', $versionIds)
                ->countAllResults();
        }

        // ── Active members count ───────────────────────────────────────────
        $memberCount = (int) $db->table('memberships')
            ->where('tenant_id', $tenantId)
            ->where('status', 'active')
            ->countAllResults();

        // ── Pending manual reviews (answers needing human evaluation) ──────
        $manualCount = (int) $db->table('gap_answers ga')
            ->join('gap_sessions gs', 'gs.id = ga.session_id')
            ->where('gs.tenant_id', $tenantId)
            ->where('ga.is_manual_review', 1)
            ->countAllResults();

        // ── Action plan stats ──────────────────────────────────────────────
        $actionPlanModel = new ActionPlanModel();
        $actionStats     = $actionPlanModel->statsForTenant($tenantId);

        // ── Summary counters ───────────────────────────────────────────────
        $submittedCount = count(array_filter($sessions, fn($s) => $s['status'] === 'submitted'));
        $inProgressCount = count(array_filter($sessions, fn($s) => $s['status'] === 'draft' && (int)$s['answered_controls'] > 0));

        // ── Domain breakdown per session (single query, indexed by standard_version_id) ──
        $domainBreakdowns = [];
        if (! empty($sessions)) {
            $sessionIds           = array_column($sessions, 'id');
            $sessionIdToVersionId = array_column($sessions, 'standard_version_id', 'id');
            $breakdownRows = $db->table('gap_answers ga')
                ->select([
                    'ga.session_id',
                    'd.code AS domain_code',
                    'IFNULL(AVG(ga.score_pct), 0) AS avg_score',
                ])
                ->join('controls c',  'c.id  = ga.control_id')
                ->join('clauses cl',  'cl.id = c.clause_id')
                ->join('domains d',   'd.id  = cl.domain_id')
                ->whereIn('ga.session_id', $sessionIds)
                ->groupBy('ga.session_id, d.id')
                ->orderBy('d.code', 'ASC')
                ->get()->getResultArray();
            foreach ($breakdownRows as $row) {
                $svId = $sessionIdToVersionId[$row['session_id']] ?? null;
                if ($svId !== null) {
                    $domainBreakdowns[$svId][] = $row;
                }
            }
        }

        return view('dashboard/index', [
            'title'            => 'Dashboard — CheckISO',
            'subscriptions'    => $subscriptions,
            'memberCount'      => $memberCount,
            'manualCount'      => $manualCount,
            'totalControls'    => $totalControls,
            'submittedCount'   => $submittedCount,
            'inProgressCount'  => $inProgressCount,
            'actionStats'      => $actionStats,
            'domainBreakdowns' => $domainBreakdowns,
        ]);
    }
}

<?php

namespace App\Controllers\Web;

use App\Controllers\BaseController;

/**
 * Platform-level administration panel.
 * All routes are protected by AdminFilter (is_platform_admin session flag).
 *
 * Routes:
 *   GET /admin              → index()   — global stats dashboard
 *   GET /admin/tenants      → tenants() — all organisations
 *   GET /admin/users        → users()   — all users
 *   POST /admin/users/{id}/toggle → userToggle($id) — activate / deactivate
 *   POST /admin/tenants/{id}/toggle → tenantToggle($id) — activate / suspend
 */
class AdminController extends BaseController
{
    private $db;

    public function initController(
        \CodeIgniter\HTTP\RequestInterface $request,
        \CodeIgniter\HTTP\ResponseInterface $response,
        \Psr\Log\LoggerInterface $logger
    ): void {
        parent::initController($request, $response, $logger);
        $this->db = \Config\Database::connect();
    }

    // -------------------------------------------------------------------------
    // GET /admin  — platform dashboard
    // -------------------------------------------------------------------------
    public function index()
    {
        $stats = [
            'tenants'      => $this->db->table('tenants')->where('deleted_at IS NULL')->countAllResults(),
            'users'        => $this->db->table('users')->where('deleted_at IS NULL')->countAllResults(),
            'memberships'  => $this->db->table('memberships')->where('status', 'active')->countAllResults(),
            'invitations'  => $this->db->table('org_invitations')->where('status', 'pending')->countAllResults(),
            'join_requests'=> $this->db->table('join_requests')->where('status', 'pending')->countAllResults(),
        ];

        // Latest 5 tenants
        $latestTenants = $this->db->table('tenants')
            ->where('deleted_at IS NULL')
            ->orderBy('created_at', 'DESC')
            ->limit(5)
            ->get()->getResultArray();

        // Latest 5 users
        $latestUsers = $this->db->table('users')
            ->select('id, first_name, last_name, email, status, created_at')
            ->where('deleted_at IS NULL')
            ->orderBy('created_at', 'DESC')
            ->limit(5)
            ->get()->getResultArray();

        return view('admin/dashboard', [
            'title'         => 'Platform Admin — CheckISO',
            'stats'         => $stats,
            'latestTenants' => $latestTenants,
            'latestUsers'   => $latestUsers,
        ]);
    }

    // -------------------------------------------------------------------------
    // GET /admin/tenants
    // -------------------------------------------------------------------------
    public function tenants()
    {
        $tenants = $this->db->table('tenants t')
            ->select('t.*, COUNT(m.id) AS member_count')
            ->join('memberships m', 'm.tenant_id = t.id AND m.status = "active"', 'left')
            ->where('t.deleted_at IS NULL')
            ->groupBy('t.id')
            ->orderBy('t.created_at', 'DESC')
            ->get()->getResultArray();

        return view('admin/tenants', [
            'title'   => 'Organisations — Platform Admin',
            'tenants' => $tenants,
        ]);
    }

    // -------------------------------------------------------------------------
    // GET /admin/users
    // -------------------------------------------------------------------------
    public function users()
    {
        $users = $this->db->table('users u')
            ->select('u.id, u.first_name, u.last_name, u.email, u.status, u.created_at, u.last_login_at,
                      COUNT(m.id) AS tenant_count')
            ->join('memberships m', 'm.user_id = u.id AND m.status = "active"', 'left')
            ->where('u.deleted_at IS NULL')
            ->groupBy('u.id')
            ->orderBy('u.created_at', 'DESC')
            ->get()->getResultArray();

        return view('admin/users', [
            'title' => 'Users — Platform Admin',
            'users' => $users,
        ]);
    }

    // -------------------------------------------------------------------------
    // POST /admin/users/{id}/toggle  — activate / deactivate a user
    // -------------------------------------------------------------------------
    public function userToggle(int $id)
    {
        $user = $this->db->table('users')->where('id', $id)->get()->getRowArray();
        if (! $user) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound();
        }

        // Cannot deactivate yourself
        if ((int) $id === (int) session()->get('user_id')) {
            return redirect()->to(site_url('admin/users'))
                ->with('error', 'You cannot deactivate your own account.');
        }

        $newStatus = $user['status'] === 'active' ? 'inactive' : 'active';
        $this->db->table('users')->where('id', $id)->update(['status' => $newStatus]);

        return redirect()->to(site_url('admin/users'))
            ->with('success', "User #{$id} set to {$newStatus}.");
    }

    // -------------------------------------------------------------------------
    // POST /admin/tenants/{id}/toggle  — activate / suspend a tenant
    // -------------------------------------------------------------------------
    public function tenantToggle(int $id)
    {
        $tenant = $this->db->table('tenants')->where('id', $id)->get()->getRowArray();
        if (! $tenant) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound();
        }

        $newStatus = $tenant['status'] === 'active' ? 'suspended' : 'active';
        $this->db->table('tenants')->where('id', $id)->update(['status' => $newStatus]);

        return redirect()->to(site_url('admin/tenants'))
            ->with('success', "Organisation #{$id} set to {$newStatus}.");
    }

    // =========================================================================
    // ISO CATALOGUE MANAGEMENT
    // =========================================================================

    // GET /admin/catalog — list all standards + versions with counts
    public function catalog()
    {
        $standards = $this->db->table('standards s')
            ->select('s.id, s.code, s.name, s.description,
                      sv.id AS version_id, sv.version AS version_code, sv.title AS version_title,
                      sv.is_active, sv.published_at,
                      COUNT(DISTINCT d.id)  AS domain_count,
                      COUNT(DISTINCT cl.id) AS clause_count,
                      COUNT(DISTINCT c.id)  AS control_count,
                      COUNT(DISTINCT sub.id) AS subscription_count')
            ->join('standard_versions sv', 'sv.standard_id = s.id', 'left')
            ->join('domains d',   'd.standard_version_id = sv.id', 'left')
            ->join('clauses cl',  'cl.domain_id = d.id', 'left')
            ->join('controls c',  'c.clause_id = cl.id', 'left')
            ->join('tenant_standards sub', 'sub.standard_version_id = sv.id', 'left')
            ->groupBy('s.id, sv.id')
            ->orderBy('s.code ASC, sv.version ASC')
            ->get()->getResultArray();

        // Gap session stats per version
        $gapStats = $this->db->table('gap_sessions')
            ->select('standard_version_id,
                      COUNT(*) AS total_sessions,
                      SUM(status = "submitted") AS submitted_sessions')
            ->groupBy('standard_version_id')
            ->get()->getResultArray();
        $gapStatsByVersion = array_column($gapStats, null, 'standard_version_id');

        foreach ($standards as &$sv) {
            $sv['gap_stats'] = $gapStatsByVersion[$sv['version_id']] ?? null;
        }
        unset($sv);

        return view('admin/catalog', [
            'title'     => 'Catalogue ISO — Admin',
            'standards' => $standards,
        ]);
    }

    // GET /admin/catalog/{versionId} — full domain → clause → control tree
    public function catalogShow(int $versionId)
    {
        $version = $this->db->table('standard_versions sv')
            ->select('sv.*, s.code AS standard_code, s.name AS standard_name')
            ->join('standards s', 's.id = sv.standard_id')
            ->where('sv.id', $versionId)
            ->get()->getRowArray();

        if (! $version) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound();
        }

        $domains = $this->db->table('domains')
            ->where('standard_version_id', $versionId)
            ->orderBy('code', 'ASC')
            ->get()->getResultArray();

        foreach ($domains as &$domain) {
            $clauses = $this->db->table('clauses')
                ->where('domain_id', $domain['id'])
                ->orderBy('code', 'ASC')
                ->get()->getResultArray();

            foreach ($clauses as &$clause) {
                $clause['controls'] = $this->db->table('controls')
                    ->where('clause_id', $clause['id'])
                    ->orderBy('code', 'ASC')
                    ->get()->getResultArray();
            }
            unset($clause);
            $domain['clauses'] = $clauses;
        }
        unset($domain);

        return view('admin/catalog_show', [
            'title'   => esc($version['standard_code']) . ' — Catalogue Admin',
            'version' => $version,
            'domains' => $domains,
        ]);
    }

    // POST /admin/catalog/domain/{id}/save — update domain name/name_fr
    public function catalogDomainSave(int $id)
    {
        $name   = trim($this->request->getPost('name'));
        $nameFr = trim($this->request->getPost('name_fr'));

        if ($name !== '') {
            $this->db->table('domains')->where('id', $id)->update([
                'name'    => $name,
                'name_fr' => $nameFr ?: null,
            ]);
        }

        // Return versionId to redirect back
        $domain = $this->db->table('domains')->where('id', $id)->get()->getRowArray();
        $versionId = $domain['standard_version_id'] ?? null;

        return redirect()->to(site_url("admin/catalog/{$versionId}"))
            ->with('success', "Domaine #{$id} mis à jour.");
    }

    // POST /admin/catalog/control/{id}/save — update control title/title_fr
    public function catalogControlSave(int $id)
    {
        $title   = trim($this->request->getPost('title'));
        $titleFr = trim($this->request->getPost('title_fr'));

        if ($title !== '') {
            $this->db->table('controls')->where('id', $id)->update([
                'title'    => $title,
                'title_fr' => $titleFr ?: null,
            ]);
            // Keep clause in sync if same code
            $ctrl = $this->db->table('controls')->where('id', $id)->get()->getRowArray();
            if ($ctrl) {
                $this->db->table('clauses')->where('code', $ctrl['code'])->update([
                    'title'    => $title,
                    'title_fr' => $titleFr ?: null,
                ]);
            }
        }

        $referer = $this->request->getServer('HTTP_REFERER');
        if ($referer && str_starts_with($referer, base_url())) {
            return redirect()->to($referer)->with('success', "Contrôle #{$id} mis à jour.");
        }
        return redirect()->to(site_url('admin/catalog'))->with('success', "Contrôle #{$id} mis à jour.");
    }

    // =========================================================================
    // GAP ANALYSIS OVERSIGHT
    // =========================================================================

    // GET /admin/gap — all gap sessions across all tenants
    public function gapSessions()
    {
        $sessions = $this->db->table('gap_sessions gs')
            ->select('gs.*,
                      t.name AS tenant_name, t.slug AS tenant_slug,
                      s.code AS standard_code,
                      sv.version AS version_code, sv.title AS version_title')
            ->join('tenants t',           't.id  = gs.tenant_id')
            ->join('standard_versions sv','sv.id = gs.standard_version_id')
            ->join('standards s',         's.id  = sv.standard_id')
            ->orderBy('gs.updated_at', 'DESC')
            ->get()->getResultArray();

        // Global quick stats
        $totalSessions   = count($sessions);
        $submittedCount  = count(array_filter($sessions, fn($s) => $s['status'] === 'submitted'));
        $inProgressCount = count(array_filter($sessions, fn($s) => $s['status'] === 'draft' && (int)$s['answered_controls'] > 0));
        $notStartedCount = count(array_filter($sessions, fn($s) => $s['status'] === 'draft' && (int)$s['answered_controls'] === 0));

        return view('admin/gap_sessions', [
            'title'          => 'Gap Analysis — Admin',
            'sessions'       => $sessions,
            'totalSessions'  => $totalSessions,
            'submittedCount' => $submittedCount,
            'inProgressCount'=> $inProgressCount,
            'notStartedCount'=> $notStartedCount,
        ]);
    }

    // GET /admin/gap/{id} — detail of a specific session with all answers
    public function gapSessionDetail(int $sessionId)
    {
        $session = $this->db->table('gap_sessions gs')
            ->select('gs.*,
                      t.name AS tenant_name,
                      s.code AS standard_code,
                      sv.version AS version_code, sv.title AS version_title')
            ->join('tenants t',           't.id  = gs.tenant_id')
            ->join('standard_versions sv','sv.id = gs.standard_version_id')
            ->join('standards s',         's.id  = sv.standard_id')
            ->where('gs.id', $sessionId)
            ->get()->getRowArray();

        if (! $session) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound();
        }

        $answers = $this->db->table('gap_answers ga')
            ->select('ga.*,
                      c.code  AS control_code,
                      c.title AS control_title, c.title_fr AS control_title_fr,
                      d.name  AS domain_name,   d.name_fr  AS domain_name_fr,
                      d.code  AS domain_code,
                      cc.label AS choice_label')
            ->join('controls c',          'c.id  = ga.control_id')
            ->join('clauses cl',          'cl.id = c.clause_id')
            ->join('domains d',           'd.id  = cl.domain_id')
            ->join('control_choices cc',  'cc.id = ga.choice_id', 'left')
            ->where('ga.session_id', $sessionId)
            ->orderBy('c.code', 'ASC')
            ->get()->getResultArray();

        // Group by domain
        $byDomain = [];
        foreach ($answers as $a) {
            $byDomain[$a['domain_code']]['name']    = $a['domain_name'];
            $byDomain[$a['domain_code']]['name_fr'] = $a['domain_name_fr'];
            $byDomain[$a['domain_code']]['answers'][] = $a;
        }

        return view('admin/gap_session', [
            'title'    => 'Session #{$sessionId} — Gap Admin',
            'session'  => $session,
            'byDomain' => $byDomain,
        ]);
    }

    // POST /admin/gap/{id}/reset — delete all answers and reset session to draft
    public function gapSessionReset(int $sessionId)
    {
        $session = $this->db->table('gap_sessions')->where('id', $sessionId)->get()->getRowArray();
        if (! $session) {
            return redirect()->to(site_url('admin/gap'))->with('error', 'Session introuvable.');
        }

        $this->db->transStart();
        $this->db->table('gap_answers')->where('session_id', $sessionId)->delete();
        $this->db->table('gap_sessions')->where('id', $sessionId)->update([
            'status'            => 'draft',
            'answered_controls' => 0,
            'score'             => 0,
            'submitted_at'      => null,
            'submitted_by'      => null,
            'updated_at'        => date('Y-m-d H:i:s'),
        ]);
        $this->db->transComplete();

        return redirect()->to(site_url('admin/gap'))
            ->with('success', "Session #{$sessionId} réinitialisée.");
    }
}

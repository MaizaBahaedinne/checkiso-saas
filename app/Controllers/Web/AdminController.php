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
}

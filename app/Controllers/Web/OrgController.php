<?php

namespace App\Controllers\Web;

use App\Controllers\BaseController;
use App\Models\MembershipModel;
use App\Models\TenantModel;
use App\Models\UserModel;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Psr\Log\LoggerInterface;

/**
 * Organisation settings and member management.
 *
 * All actions are scoped to the current session's tenant_id.
 *
 * Routes:
 *   GET  /org/settings          → settings()
 *   POST /org/settings          → settingsPost()
 *   GET  /org/members           → members()
 *   POST /org/members/{id}/role → memberRole($id)
 *   POST /org/members/{id}/remove → memberRemove($id)
 */
class OrgController extends BaseController
{
    private TenantModel $tenantModel;
    private MembershipModel $membershipModel;
    private UserModel $userModel;

    public function initController(RequestInterface $request, ResponseInterface $response, LoggerInterface $logger): void
    {
        parent::initController($request, $response, $logger);
        $this->tenantModel     = new TenantModel();
        $this->membershipModel = new MembershipModel();
        $this->userModel       = new UserModel();
    }

    // -------------------------------------------------------------------------
    // GET /org/settings
    // -------------------------------------------------------------------------
    public function settings()
    {
        $tenant = $this->tenantModel->find(session()->get('tenant_id'));
        if (! $tenant) {
            return redirect()->to('/dashboard');
        }

        return view('org/settings', [
            'title'  => 'Organisation Settings — CheckISO',
            'tenant' => $tenant,
        ]);
    }

    // -------------------------------------------------------------------------
    // POST /org/settings
    // -------------------------------------------------------------------------
    public function settingsPost()
    {
        if (! $this->validate([
            'org_name'        => 'required|min_length[2]|max_length[200]',
            'sector'          => 'permit_empty|max_length[100]',
            'employees_range' => 'permit_empty|in_list[1-10,11-50,51-200,201-500,500+]',
            'address_line'    => 'permit_empty|max_length[255]',
            'city'            => 'permit_empty|max_length[100]',
            'postal_code'     => 'permit_empty|max_length[20]',
            'country_code'    => 'permit_empty|exact_length[2]',
            'website'         => 'permit_empty|max_length[255]',
            'contact_email'   => 'permit_empty|valid_email',
        ])) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        $tenantId = (int) session()->get('tenant_id');

        $this->tenantModel->update($tenantId, [
            'name'            => $this->request->getPost('org_name'),
            'sector'          => $this->request->getPost('sector'),
            'employees_range' => $this->request->getPost('employees_range'),
            'address_line'    => $this->request->getPost('address_line'),
            'city'            => $this->request->getPost('city'),
            'postal_code'     => $this->request->getPost('postal_code'),
            'country_code'    => $this->request->getPost('country_code'),
            'website'         => $this->request->getPost('website'),
            'contact_email'   => $this->request->getPost('contact_email'),
        ]);

        return redirect()->to(site_url('org/settings'))->with('success', 'Settings saved.');
    }

    // -------------------------------------------------------------------------
    // GET /org/members
    // -------------------------------------------------------------------------
    public function members()
    {
        $tenantId = (int) session()->get('tenant_id');

        // Load members with user data + their role (via membership_roles → roles)
        $db = \Config\Database::connect();
        $members = $db->table('memberships m')
            ->select('m.id AS membership_id, m.status, m.is_default, m.created_at AS joined_at,
                      u.id AS user_id, u.first_name, u.last_name, u.email,
                      r.code AS role_code, r.name AS role_name')
            ->join('users u', 'u.id = m.user_id')
            ->join('membership_roles mr', 'mr.membership_id = m.id', 'left')
            ->join('roles r', 'r.id = mr.role_id', 'left')
            ->where('m.tenant_id', $tenantId)
            ->orderBy('m.created_at', 'ASC')
            ->get()->getResultArray();

        return view('org/members', [
            'title'   => 'Members — CheckISO',
            'members' => $members,
        ]);
    }

    // -------------------------------------------------------------------------
    // POST /org/members/{id}/role   — toggle role between org.admin and org.member
    // -------------------------------------------------------------------------
    public function memberRole(int $membershipId)
    {
        $membership = $this->getMembershipOr404($membershipId);

        // Prevent changing your own role
        if ((int) $membership['user_id'] === (int) session()->get('user_id')) {
            return redirect()->to(site_url('org/members'))->with('error', 'You cannot change your own role.');
        }

        $db = \Config\Database::connect();

        // Determine target role
        $newRoleCode = $this->request->getPost('role'); // 'org.admin' or 'org.member'
        if (! in_array($newRoleCode, ['org.admin', 'org.member'], true)) {
            return redirect()->to(site_url('org/members'))->with('error', 'Invalid role.');
        }

        $role = $db->table('roles')->where('code', $newRoleCode)->get()->getRowArray();
        if (! $role) {
            return redirect()->to(site_url('org/members'))->with('error', 'Role not found. Please run migrations/seeds.');
        }

        // Replace the membership's role
        $db->table('membership_roles')->where('membership_id', $membershipId)->delete();
        $db->table('membership_roles')->insert([
            'membership_id' => $membershipId,
            'role_id'       => $role['id'],
        ]);

        return redirect()->to(site_url('org/members'))->with('success', 'Role updated.');
    }

    // -------------------------------------------------------------------------
    // POST /org/members/{id}/remove — deactivate a membership
    // -------------------------------------------------------------------------
    public function memberRemove(int $membershipId)
    {
        $membership = $this->getMembershipOr404($membershipId);

        // Cannot remove yourself
        if ((int) $membership['user_id'] === (int) session()->get('user_id')) {
            return redirect()->to(site_url('org/members'))->with('error', 'You cannot remove yourself.');
        }

        $this->membershipModel->update($membershipId, ['status' => 'inactive']);

        return redirect()->to(site_url('org/members'))->with('success', 'Member removed.');
    }

    // -------------------------------------------------------------------------
    private function getMembershipOr404(int $membershipId): array
    {
        $membership = $this->membershipModel->find($membershipId);

        if (! $membership || (int) $membership['tenant_id'] !== (int) session()->get('tenant_id')) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound();
        }

        return $membership;
    }
}

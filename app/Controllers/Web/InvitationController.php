<?php

namespace App\Controllers\Web;

use App\Controllers\BaseController;
use App\Models\InvitationModel;
use App\Models\MembershipModel;
use App\Models\TenantModel;
use App\Models\UserModel;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Psr\Log\LoggerInterface;

/**
 * Handles member invitations:
 *
 *   POST /org/invite                — send invitation email
 *   POST /org/invite/{id}/cancel    — cancel a pending invitation
 *   GET  /invite/{token}            — public: show accept page
 *   POST /invite/{token}            — public: accept invitation (creates membership)
 */
class InvitationController extends BaseController
{
    private InvitationModel $invitationModel;
    private MembershipModel $membershipModel;
    private TenantModel     $tenantModel;
    private UserModel       $userModel;

    public function initController(RequestInterface $request, ResponseInterface $response, LoggerInterface $logger): void
    {
        parent::initController($request, $response, $logger);
        $this->invitationModel = new InvitationModel();
        $this->membershipModel = new MembershipModel();
        $this->tenantModel     = new TenantModel();
        $this->userModel       = new UserModel();
    }

    // -------------------------------------------------------------------------
    // POST /org/invite  — admin sends an invitation
    // -------------------------------------------------------------------------
    public function send()
    {
        if (! $this->validate([
            'email'     => 'required|valid_email',
            'role_code' => 'required|in_list[org.admin,org.member]',
        ])) {
            return redirect()->to(site_url('org/members'))
                ->with('errors', $this->validator->getErrors());
        }

        $tenantId = (int) session()->get('tenant_id');
        $email    = strtolower(trim($this->request->getPost('email')));

        // Is that email already a member?
        $existingUser = $this->userModel->findByEmail($email);
        if ($existingUser) {
            $alreadyMember = $this->membershipModel
                ->where('tenant_id', $tenantId)
                ->where('user_id', $existingUser['id'])
                ->where('status', 'active')
                ->first();
            if ($alreadyMember) {
                return redirect()->to(site_url('org/members'))
                    ->with('error', "{$email} is already a member of this organisation.");
            }
        }

        // Already has a pending invitation?
        $existing = $this->invitationModel
            ->where('tenant_id', $tenantId)
            ->where('email', $email)
            ->where('status', 'pending')
            ->first();
        if ($existing) {
            return redirect()->to(site_url('org/members'))
                ->with('error', "An invitation is already pending for {$email}.");
        }

        $token     = InvitationModel::generateToken();
        $expiresAt = date('Y-m-d H:i:s', strtotime('+7 days'));

        $this->invitationModel->insert([
            'tenant_id'  => $tenantId,
            'invited_by' => session()->get('user_id'),
            'email'      => $email,
            'role_code'  => $this->request->getPost('role_code'),
            'token'      => $token,
            'status'     => 'pending',
            'expires_at' => $expiresAt,
        ]);

        // Send the invitation email
        $tenant  = $this->tenantModel->find($tenantId);
        $inviteUrl = site_url('invite/' . $token);
        $senderName = session()->get('user_name');

        $this->sendInviteEmail($email, $tenant['name'], $senderName, $inviteUrl);

        return redirect()->to(site_url('org/members'))
            ->with('success', "Invitation sent to {$email}.");
    }

    // -------------------------------------------------------------------------
    // POST /org/invite/{id}/cancel
    // -------------------------------------------------------------------------
    public function cancel(int $id)
    {
        $inv = $this->invitationModel->find($id);

        if (! $inv || (int) $inv['tenant_id'] !== (int) session()->get('tenant_id')) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound();
        }

        $this->invitationModel->update($id, ['status' => 'cancelled']);

        return redirect()->to(site_url('org/members'))
            ->with('success', 'Invitation cancelled.');
    }

    // -------------------------------------------------------------------------
    // GET /invite/{token}  — public page (no auth required)
    // -------------------------------------------------------------------------
    public function accept(string $token)
    {
        $inv = $this->invitationModel->findValid($token);
        if (! $inv) {
            return view('invite/invalid', ['title' => 'Invalid invitation — CheckISO']);
        }

        $tenant = $this->tenantModel->find($inv['tenant_id']);

        return view('invite/accept', [
            'title'  => 'Join ' . $tenant['name'] . ' — CheckISO',
            'inv'    => $inv,
            'tenant' => $tenant,
        ]);
    }

    // -------------------------------------------------------------------------
    // POST /invite/{token}  — accept the invitation
    // -------------------------------------------------------------------------
    public function acceptPost(string $token)
    {
        $inv = $this->invitationModel->findValid($token);
        if (! $inv) {
            return view('invite/invalid', ['title' => 'Invalid invitation — CheckISO']);
        }

        $tenant = $this->tenantModel->find($inv['tenant_id']);
        $db     = \Config\Database::connect();

        // Case A: user is already logged in
        if (session()->get('user_id')) {
            $userId = (int) session()->get('user_id');
            $this->createMembership($userId, $inv, $db);
            $this->markAccepted($inv['id']);

            $newMembership = $this->membershipModel
                ->where('tenant_id', $inv['tenant_id'])
                ->where('user_id', $userId)
                ->first();

            session()->set([
                'tenant_id'     => $inv['tenant_id'],
                'membership_id' => $newMembership['id'] ?? null,
                'role_code'     => $inv['role_code'],
            ]);

            return redirect()->to(site_url('dashboard'))
                ->with('success', 'You have joined ' . $tenant['name'] . '!');
        }

        // Case B: new user — validate registration fields
        if (! $this->validate([
            'first_name'       => 'required|min_length[2]|max_length[100]',
            'last_name'        => 'required|min_length[2]|max_length[100]',
            'password'         => 'required|min_length[8]',
            'password_confirm' => 'required|matches[password]',
        ])) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        $db->transStart();

        // Create or find the user account
        $user = $this->userModel->findByEmail($inv['email']);
        if (! $user) {
            $userId = $this->userModel->insert([
                'email'         => $inv['email'],
                'password_hash' => password_hash($this->request->getPost('password'), PASSWORD_BCRYPT),
                'first_name'    => $this->request->getPost('first_name'),
                'last_name'     => $this->request->getPost('last_name'),
                'status'        => 'active',
            ]);
            $user = $this->userModel->find($userId);
        } else {
            $userId = $user['id'];
        }

        $membershipId = $this->createMembership($userId, $inv, $db);
        $this->markAccepted($inv['id']);

        $db->transComplete();

        if (! $db->transStatus()) {
            return redirect()->back()->with('error', 'Something went wrong. Please try again.');
        }

        // Log the new user in
        session()->set([
            'user_id'       => $userId,
            'user_email'    => $user['email'],
            'user_name'     => UserModel::fullName($user),
            'tenant_id'     => $inv['tenant_id'],
            'membership_id' => $membershipId,
            'role_code'     => $inv['role_code'],
        ]);

        return redirect()->to(site_url('dashboard'))
            ->with('success', 'Welcome! You have joined ' . $tenant['name'] . '.');
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------
    private function createMembership(int $userId, array $inv, $db): int
    {
        // Avoid duplicate
        $existing = $this->membershipModel
            ->where('tenant_id', $inv['tenant_id'])
            ->where('user_id', $userId)
            ->first();
        if ($existing) {
            return (int) $existing['id'];
        }

        $membershipId = $this->membershipModel->insert([
            'tenant_id'  => $inv['tenant_id'],
            'user_id'    => $userId,
            'status'     => 'active',
            'is_default' => 1,
        ]);

        // Assign role
        $role = $db->table('roles')->where('code', $inv['role_code'])->get()->getRowArray();
        if ($role) {
            $db->table('membership_roles')->insert([
                'membership_id' => $membershipId,
                'role_id'       => $role['id'],
            ]);
        }

        return (int) $membershipId;
    }

    private function markAccepted(int $invId): void
    {
        $this->invitationModel->update($invId, [
            'status'      => 'accepted',
            'accepted_at' => date('Y-m-d H:i:s'),
        ]);
    }

    private function sendInviteEmail(string $to, string $orgName, string $senderName, string $inviteUrl): void
    {
        try {
            $email = \Config\Services::email();
            $email->setFrom(config('Email')->fromEmail ?: 'no-reply@checkiso.app', 'CheckISO');
            $email->setTo($to);
            $email->setSubject("{$senderName} invited you to join {$orgName} on CheckISO");
            $email->setMessage(view('emails/invitation', [
                'orgName'    => $orgName,
                'senderName' => $senderName,
                'inviteUrl'  => $inviteUrl,
            ]));
            $email->setMailType('html');
            $email->send();
        } catch (\Throwable $e) {
            log_message('error', 'Invitation email failed: ' . $e->getMessage());
            // Non-blocking — the token is stored in DB regardless
        }
    }
}

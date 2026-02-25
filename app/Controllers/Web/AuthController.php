<?php

namespace App\Controllers\Web;

use App\Controllers\BaseController;
use App\Models\MembershipModel;
use App\Models\UserModel;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Psr\Log\LoggerInterface;

class AuthController extends BaseController
{
    protected $helpers = ['form', 'url'];

    private UserModel $userModel;
    private MembershipModel $membershipModel;

    public function initController(RequestInterface $request, ResponseInterface $response, LoggerInterface $logger): void
    {
        parent::initController($request, $response, $logger);
        $this->userModel       = new UserModel();
        $this->membershipModel = new MembershipModel();
    }

    // -------------------------------------------------------------------------
    // GET /login
    // -------------------------------------------------------------------------
    public function login()
    {
        if (session()->get('user_id')) {
            return redirect()->to('/dashboard');
        }

        return view('auth/login', ['title' => 'Sign In — CheckISO']);
    }

    // -------------------------------------------------------------------------
    // POST /login
    // -------------------------------------------------------------------------
    public function loginPost()
    {
        if (! $this->validate([
            'email'    => 'required|valid_email',
            'password' => 'required',
        ])) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        $user = $this->userModel->findByEmail($this->request->getPost('email'));

        if (! $user || ! password_verify($this->request->getPost('password'), $user['password_hash'])) {
            return redirect()->back()->withInput()->with('error', 'Invalid email or password.');
        }

        if ($user['status'] !== 'active') {
            return redirect()->back()->withInput()->with('error', 'Your account is inactive. Please contact support.');
        }

        // Resolve tenant context from the default membership
        $membership = $this->membershipModel->getDefaultForUser($user['id']);

        $roleCode = $membership
            ? $this->membershipModel->getRoleCode($membership['id'])
            : null;

        // Check platform admin status
        $db = \Config\Database::connect();
        $isPlatformAdmin = (bool) $db->table('user_platform_roles upr')
            ->join('roles r', 'r.id = upr.role_id')
            ->where('upr.user_id', $user['id'])
            ->where('r.code', 'platform.admin')
            ->countAllResults();

        session()->set([
            'user_id'            => $user['id'],
            'user_email'         => $user['email'],
            'user_name'          => UserModel::fullName($user),
            'tenant_id'          => $membership['tenant_id'] ?? null,
            'membership_id'      => $membership['id'] ?? null,
            'role_code'          => $roleCode,
            'is_platform_admin'  => $isPlatformAdmin,
            'lang'               => $user['lang_preference'] ?? 'fr',
        ]);

        $this->userModel->update($user['id'], ['last_login_at' => date('Y-m-d H:i:s')]);

        // If user has no active tenant yet, send to onboarding
        if (! session()->get('tenant_id')) {
            return redirect()->to('/onboarding');
        }

        return redirect()->to('/dashboard');
    }

    // -------------------------------------------------------------------------
    // GET /register
    // -------------------------------------------------------------------------
    public function register()
    {
        if (session()->get('user_id')) {
            return redirect()->to('/dashboard');
        }

        return view('auth/register', ['title' => 'Create Account — CheckISO']);
    }

    // -------------------------------------------------------------------------
    // POST /register
    // Creates user + tenant + membership in a single transaction.
    // -------------------------------------------------------------------------
    public function registerPost()
    {
        if (! $this->validate([
            'first_name'       => 'required|min_length[2]|max_length[100]',
            'last_name'        => 'required|min_length[2]|max_length[100]',
            'email'            => 'required|valid_email|is_unique[users.email]',
            'password'         => 'required|min_length[8]',
            'password_confirm' => 'required|matches[password]',
        ])) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        $userId = $this->userModel->insert([
            'email'         => $this->request->getPost('email'),
            'password_hash' => password_hash($this->request->getPost('password'), PASSWORD_BCRYPT),
            'first_name'    => $this->request->getPost('first_name'),
            'last_name'     => $this->request->getPost('last_name'),
            'status'        => 'active',
        ]);

        $user = $this->userModel->find($userId);
        session()->set([
            'user_id'       => $userId,
            'user_email'    => $user['email'],
            'user_name'     => UserModel::fullName($user),
            'tenant_id'     => null,
            'membership_id' => null,
        ]);

        // New users always go to onboarding — no tenant yet
        return redirect()->to('/onboarding');
    }

    // -------------------------------------------------------------------------
    // GET /logout
    // -------------------------------------------------------------------------
    public function logout()
    {
        session()->destroy();

        return redirect()->to('/login');
    }

}

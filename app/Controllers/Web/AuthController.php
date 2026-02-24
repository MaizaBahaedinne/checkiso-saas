<?php

namespace App\Controllers\Web;

use App\Controllers\BaseController;
use App\Models\MembershipModel;
use App\Models\TenantModel;
use App\Models\UserModel;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Psr\Log\LoggerInterface;

class AuthController extends BaseController
{
    protected $helpers = ['form', 'url'];

    private UserModel $userModel;
    private TenantModel $tenantModel;
    private MembershipModel $membershipModel;

    public function initController(RequestInterface $request, ResponseInterface $response, LoggerInterface $logger): void
    {
        parent::initController($request, $response, $logger);
        $this->userModel       = new UserModel();
        $this->tenantModel     = new TenantModel();
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

        session()->set([
            'user_id'       => $user['id'],
            'user_email'    => $user['email'],
            'user_name'     => $user['display_name'] ?? $user['email'],
            'tenant_id'     => $membership['tenant_id'] ?? null,
            'membership_id' => $membership['id'] ?? null,
        ]);

        $this->userModel->update($user['id'], ['last_login_at' => date('Y-m-d H:i:s')]);

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
            'display_name'     => 'required|min_length[2]|max_length[150]',
            'email'            => 'required|valid_email|is_unique[users.email]',
            'password'         => 'required|min_length[8]',
            'password_confirm' => 'required|matches[password]',
            'tenant_name'      => 'required|min_length[2]|max_length[200]',
        ])) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        $db = \Config\Database::connect();
        $db->transStart();

        $userId = $this->userModel->insert([
            'email'         => $this->request->getPost('email'),
            'password_hash' => password_hash($this->request->getPost('password'), PASSWORD_BCRYPT),
            'display_name'  => $this->request->getPost('display_name'),
            'status'        => 'active',
        ]);

        $tenantName = $this->request->getPost('tenant_name');
        $tenantId   = $this->tenantModel->insert([
            'name'   => $tenantName,
            'slug'   => $this->makeSlug($tenantName),
            'status' => 'active',
        ]);

        $membershipId = $this->membershipModel->insert([
            'tenant_id'  => $tenantId,
            'user_id'    => $userId,
            'status'     => 'active',
            'is_default' => 1,
        ]);

        $db->transComplete();

        if (! $db->transStatus()) {
            return redirect()->back()->withInput()->with('error', 'Registration failed. Please try again.');
        }

        session()->set([
            'user_id'       => $userId,
            'user_email'    => $this->request->getPost('email'),
            'user_name'     => $this->request->getPost('display_name'),
            'tenant_id'     => $tenantId,
            'membership_id' => $membershipId,
        ]);

        return redirect()->to('/dashboard');
    }

    // -------------------------------------------------------------------------
    // GET /logout
    // -------------------------------------------------------------------------
    public function logout()
    {
        session()->destroy();

        return redirect()->to('/login');
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    /**
     * Generates a URL-safe slug from a string and ensures uniqueness in tenants.slug.
     */
    private function makeSlug(string $text): string
    {
        $slug = mb_strtolower($text);
        $slug = preg_replace('/[^a-z0-9\s-]/', '', $slug);
        $slug = trim((string) preg_replace('/[\s-]+/', '-', $slug), '-') ?: 'tenant';

        $original = $slug;
        $i        = 1;
        while ($this->tenantModel->where('slug', $slug)->countAllResults() > 0) {
            $slug = $original . '-' . $i++;
        }

        return $slug;
    }
}

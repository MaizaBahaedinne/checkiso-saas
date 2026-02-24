<?php

namespace App\Controllers\Web;

use App\Controllers\BaseController;
use App\Models\JoinRequestModel;
use App\Models\MembershipModel;
use App\Models\TenantModel;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Psr\Log\LoggerInterface;

/**
 * Handles the post-registration onboarding flow:
 *  1. User can create a new organisation
 *  2. User can search for an existing org and request to join
 *
 * Also exposes a JSON search endpoint for the autocomplete.
 */
class OnboardingController extends BaseController
{
    private TenantModel $tenantModel;
    private MembershipModel $membershipModel;
    private JoinRequestModel $joinRequestModel;

    public function initController(RequestInterface $request, ResponseInterface $response, LoggerInterface $logger): void
    {
        parent::initController($request, $response, $logger);
        $this->tenantModel      = new TenantModel();
        $this->membershipModel  = new MembershipModel();
        $this->joinRequestModel = new JoinRequestModel();
    }

    // -------------------------------------------------------------------------
    // GET /onboarding
    // -------------------------------------------------------------------------
    public function index()
    {
        // Already has a tenant — skip onboarding
        if (session()->get('tenant_id')) {
            return redirect()->to('/dashboard');
        }

        return view('onboarding/index', ['title' => 'Set up your organisation — CheckISO']);
    }

    // -------------------------------------------------------------------------
    // GET /onboarding/search?q=...  (JSON autocomplete)
    // -------------------------------------------------------------------------
    public function search()
    {
        $term    = trim($this->request->getGet('q') ?? '');
        $results = $term !== '' ? $this->tenantModel->search($term, 8) : [];

        return $this->response->setJSON(array_map(fn ($t) => [
            'id'   => $t['id'],
            'name' => $t['name'],
            'city' => $t['city'] ?? '',
        ], $results));
    }

    // -------------------------------------------------------------------------
    // POST /onboarding/create  — create a brand-new organisation
    // -------------------------------------------------------------------------
    public function create()
    {
        if (! $this->validate([
            'org_name'         => 'required|min_length[2]|max_length[200]',
            'sector'           => 'permit_empty|max_length[100]',
            'employees_range'  => 'permit_empty|in_list[1-10,11-50,51-200,201-500,500+]',
            'address_line'     => 'permit_empty|max_length[255]',
            'city'             => 'permit_empty|max_length[100]',
            'postal_code'      => 'permit_empty|max_length[20]',
            'country_code'     => 'permit_empty|exact_length[2]',
            'website'          => 'permit_empty|max_length[255]',
            'contact_email'    => 'permit_empty|valid_email',
        ])) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        // Duplicate check (exact name, case-insensitive)
        $existing = $this->tenantModel->findByName($this->request->getPost('org_name'));
        if ($existing) {
            return redirect()->back()->withInput()
                ->with('duplicate', $existing)
                ->with('error', 'An organisation with this name already exists. You can request to join it below.');
        }

        $db = \Config\Database::connect();
        $db->transStart();

        $tenantId = $this->tenantModel->insert([
            'name'            => $this->request->getPost('org_name'),
            'slug'            => $this->makeSlug($this->request->getPost('org_name')),
            'status'          => 'active',
            'sector'          => $this->request->getPost('sector'),
            'employees_range' => $this->request->getPost('employees_range'),
            'address_line'    => $this->request->getPost('address_line'),
            'city'            => $this->request->getPost('city'),
            'postal_code'     => $this->request->getPost('postal_code'),
            'country_code'    => $this->request->getPost('country_code'),
            'website'         => $this->request->getPost('website'),
            'contact_email'   => $this->request->getPost('contact_email'),
        ]);

        $membershipId = $this->membershipModel->insert([
            'tenant_id'  => $tenantId,
            'user_id'    => session()->get('user_id'),
            'status'     => 'active',
            'is_default' => 1,
        ]);

        $db->transComplete();

        if (! $db->transStatus()) {
            return redirect()->back()->withInput()->with('error', 'Failed to create organisation. Please try again.');
        }

        session()->set([
            'tenant_id'     => $tenantId,
            'membership_id' => $membershipId,
            'role_code'     => 'org.admin',
        ]);

        return redirect()->to('/dashboard')->with('success', 'Organisation created! Welcome to CheckISO.');
    }

    // -------------------------------------------------------------------------
    // POST /onboarding/join  — request to join an existing organisation
    // -------------------------------------------------------------------------
    public function join()
    {
        if (! $this->validate([
            'tenant_id' => 'required|is_natural_no_zero',
            'message'   => 'permit_empty|max_length[500]',
        ])) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        $tenantId = (int) $this->request->getPost('tenant_id');
        $userId   = (int) session()->get('user_id');

        $tenant = $this->tenantModel->find($tenantId);
        if (! $tenant || $tenant['status'] !== 'active') {
            return redirect()->back()->with('error', 'Organisation not found.');
        }

        // Already a member?
        $existing = $this->membershipModel
            ->where('tenant_id', $tenantId)
            ->where('user_id', $userId)
            ->first();
        if ($existing) {
            return redirect()->back()->with('error', 'You are already a member of this organisation.');
        }

        // Already has a pending request?
        if ($this->joinRequestModel->getPending($userId, $tenantId)) {
            return redirect()->back()->with('error', 'You already have a pending request for this organisation.');
        }

        $this->joinRequestModel->insert([
            'tenant_id' => $tenantId,
            'user_id'   => $userId,
            'status'    => 'pending',
            'message'   => $this->request->getPost('message'),
        ]);

        return redirect()->to('/onboarding/pending')->with('success', 'Your request has been sent. You\'ll be notified when approved.');
    }

    // -------------------------------------------------------------------------
    // GET /onboarding/pending — waiting room after join request
    // -------------------------------------------------------------------------
    public function pending()
    {
        return view('onboarding/pending', ['title' => 'Request pending — CheckISO']);
    }

    // -------------------------------------------------------------------------
    private function makeSlug(string $text): string
    {
        $slug = mb_strtolower($text);
        $slug = preg_replace('/[^a-z0-9\s-]/', '', $slug);
        $slug = trim((string) preg_replace('/[\s-]+/', '-', $slug), '-') ?: 'org';

        $original = $slug;
        $i        = 1;
        while ($this->tenantModel->where('slug', $slug)->countAllResults() > 0) {
            $slug = $original . '-' . $i++;
        }

        return $slug;
    }
}

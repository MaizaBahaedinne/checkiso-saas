<?php

namespace App\Controllers\Web;

use App\Controllers\BaseController;
use App\Models\JoinRequestModel;
use App\Models\MembershipModel;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Psr\Log\LoggerInterface;

/**
 * Allows tenant admins to review and approve/reject join requests.
 * Accessible at /org/requests
 */
class JoinRequestController extends BaseController
{
    private JoinRequestModel $joinRequestModel;
    private MembershipModel $membershipModel;

    public function initController(RequestInterface $request, ResponseInterface $response, LoggerInterface $logger): void
    {
        parent::initController($request, $response, $logger);
        $this->joinRequestModel = new JoinRequestModel();
        $this->membershipModel  = new MembershipModel();
    }

    // -------------------------------------------------------------------------
    // GET /org/requests  — list pending requests for the current tenant
    // -------------------------------------------------------------------------
    public function index()
    {
        $tenantId = session()->get('tenant_id');
        $requests = $this->joinRequestModel->pendingForTenant($tenantId);

        return view('org/requests', [
            'title'    => 'Join Requests — CheckISO',
            'requests' => $requests,
        ]);
    }

    // -------------------------------------------------------------------------
    // POST /org/requests/{id}/approve
    // -------------------------------------------------------------------------
    public function approve(int $id)
    {
        $req = $this->getRequestOr404($id);

        $db = \Config\Database::connect();
        $db->transStart();

        $this->joinRequestModel->update($id, [
            'status'      => 'approved',
            'reviewed_by' => session()->get('user_id'),
            'reviewed_at' => date('Y-m-d H:i:s'),
        ]);

        // Check not already a member (edge case)
        $existing = $this->membershipModel
            ->where('tenant_id', $req['tenant_id'])
            ->where('user_id', $req['user_id'])
            ->first();

        if (! $existing) {
            $this->membershipModel->insert([
                'tenant_id'  => $req['tenant_id'],
                'user_id'    => $req['user_id'],
                'status'     => 'active',
                'is_default' => 1,
            ]);
        }

        $db->transComplete();

        return redirect()->to('/org/requests')->with('success', 'Request approved — user is now a member.');
    }

    // -------------------------------------------------------------------------
    // POST /org/requests/{id}/reject
    // -------------------------------------------------------------------------
    public function reject(int $id)
    {
        $this->getRequestOr404($id);

        $this->joinRequestModel->update($id, [
            'status'      => 'rejected',
            'reviewed_by' => session()->get('user_id'),
            'reviewed_at' => date('Y-m-d H:i:s'),
        ]);

        return redirect()->to('/org/requests')->with('success', 'Request rejected.');
    }

    // -------------------------------------------------------------------------
    private function getRequestOr404(int $id): array
    {
        $req = $this->joinRequestModel->find($id);

        if (! $req || (int) $req['tenant_id'] !== (int) session()->get('tenant_id')) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound();
        }

        return $req;
    }
}

<?php

namespace App\Controllers\Web;

use App\Controllers\BaseController;
use App\Models\ActionPlanModel;
use App\Models\ControlModel;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Psr\Log\LoggerInterface;

/**
 * Manages corrective Action Plans for a tenant.
 *
 * Routes:
 *   GET  /action-plan                    → index()        — kanban board
 *   GET  /action-plan/create             → create()       — new plan form
 *   POST /action-plan                    → store()        — save new plan
 *   GET  /action-plan/(:num)/edit        → edit($id)      — edit form (returns JSON for modal)
 *   POST /action-plan/(:num)/update      → update($id)    — save edits
 *   POST /action-plan/(:num)/status      → updateStatus() — change status (AJAX)
 *   POST /action-plan/(:num)/delete      → delete($id)    — soft delete
 */
class ActionPlanController extends BaseController
{
    private ActionPlanModel $planModel;

    public function initController(
        RequestInterface $request,
        ResponseInterface $response,
        LoggerInterface $logger
    ): void {
        parent::initController($request, $response, $logger);
        $this->planModel = new ActionPlanModel();
    }

    // -------------------------------------------------------------------------
    // GET /action-plan  — kanban board: todo / in_progress / done
    // -------------------------------------------------------------------------
    public function index(): string
    {
        $tenantId = (int) session()->get('tenant_id');
        $plans    = $this->planModel->forTenant($tenantId);

        $columns = [
            'todo'        => [],
            'in_progress' => [],
            'done'        => [],
        ];
        foreach ($plans as $p) {
            $columns[$p['status']][] = $p;
        }

        // Tenant members for the owner dropdown (create/edit modals)
        $db      = \Config\Database::connect();
        $members = $db->table('memberships m')
            ->select('u.id, u.first_name, u.last_name, u.email')
            ->join('users u', 'u.id = m.user_id')
            ->where('m.tenant_id', $tenantId)
            ->where('m.status', 'active')
            ->where('u.deleted_at IS NULL')
            ->orderBy('u.first_name')
            ->get()->getResultArray();

        return view('action_plan/index', [
            'title'   => lang('ActionPlan.title'),
            'columns' => $columns,
            'members' => $members,
        ]);
    }

    // -------------------------------------------------------------------------
    // GET /action-plan/create  — pre-filled form (from gap summary link)
    // -------------------------------------------------------------------------
    public function create(): string
    {
        $tenantId  = (int) session()->get('tenant_id');
        $db        = \Config\Database::connect();

        // Optional query-string context from gap summary
        $controlId    = (int) $this->request->getGet('control_id');
        $sessionId    = (int) $this->request->getGet('session_id');
        $controlTitle = '';
        $controlCode  = '';

        if ($controlId) {
            $ctrl = $db->table('controls')->where('id', $controlId)->get()->getRowArray();
            if ($ctrl) {
                $controlTitle = $ctrl['title'];
                $controlCode  = $ctrl['code'];
            }
        }

        // Tenant members for owner dropdown
        $members = $db->table('memberships m')
            ->select('u.id, u.first_name, u.last_name, u.email')
            ->join('users u', 'u.id = m.user_id')
            ->where('m.tenant_id', $tenantId)
            ->where('m.status', 'active')
            ->where('u.deleted_at IS NULL')
            ->orderBy('u.first_name')
            ->get()->getResultArray();

        return view('action_plan/create', [
            'title'        => lang('ActionPlan.create_title'),
            'members'      => $members,
            'controlId'    => $controlId ?: null,
            'sessionId'    => $sessionId ?: null,
            'controlTitle' => $controlTitle,
            'controlCode'  => $controlCode,
        ]);
    }

    // -------------------------------------------------------------------------
    // POST /action-plan  — save new plan
    // -------------------------------------------------------------------------
    public function store()
    {
        if (! $this->validate([
            'title'    => 'required|max_length[255]',
            'priority' => 'required|in_list[low,medium,high]',
        ])) {
            return redirect()->back()
                ->withInput()
                ->with('errors', $this->validator->getErrors());
        }

        $tenantId = (int) session()->get('tenant_id');
        $userId   = (int) session()->get('user_id');

        $this->planModel->insert([
            'tenant_id'      => $tenantId,
            'gap_session_id' => $this->request->getPost('gap_session_id') ?: null,
            'control_id'     => $this->request->getPost('control_id') ?: null,
            'title'          => trim($this->request->getPost('title')),
            'description'    => trim($this->request->getPost('description') ?? ''),
            'owner_user_id'  => $this->request->getPost('owner_user_id') ?: null,
            'due_date'       => $this->request->getPost('due_date') ?: null,
            'priority'       => $this->request->getPost('priority'),
            'status'         => 'todo',
            'created_by'     => $userId,
        ]);

        return redirect()->to(site_url('action-plan'))
            ->with('success', lang('ActionPlan.created'));
    }

    // -------------------------------------------------------------------------
    // GET /action-plan/(:num)/edit  — returns JSON for modal population
    // -------------------------------------------------------------------------
    public function edit(int $id): ResponseInterface
    {
        $tenantId = (int) session()->get('tenant_id');
        $plan     = $this->planModel->forTenantById($tenantId, $id);

        if (! $plan) {
            return $this->response->setStatusCode(404)->setJSON(['error' => 'Not found']);
        }

        return $this->response->setJSON($plan);
    }

    // -------------------------------------------------------------------------
    // POST /action-plan/(:num)/update  — save edits
    // -------------------------------------------------------------------------
    public function update(int $id)
    {
        $tenantId = (int) session()->get('tenant_id');
        $plan     = $this->planModel->forTenantById($tenantId, $id);

        if (! $plan) {
            return redirect()->to(site_url('action-plan'))->with('error', lang('ActionPlan.not_found'));
        }

        if (! $this->validate([
            'title'    => 'required|max_length[255]',
            'priority' => 'required|in_list[low,medium,high]',
            'status'   => 'required|in_list[todo,in_progress,done]',
        ])) {
            return redirect()->back()
                ->withInput()
                ->with('errors', $this->validator->getErrors());
        }

        $this->planModel->update($id, [
            'title'         => trim($this->request->getPost('title')),
            'description'   => trim($this->request->getPost('description') ?? ''),
            'owner_user_id' => $this->request->getPost('owner_user_id') ?: null,
            'due_date'      => $this->request->getPost('due_date') ?: null,
            'priority'      => $this->request->getPost('priority'),
            'status'        => $this->request->getPost('status'),
        ]);

        return redirect()->to(site_url('action-plan'))
            ->with('success', lang('ActionPlan.updated'));
    }

    // -------------------------------------------------------------------------
    // POST /action-plan/(:num)/status  — AJAX: change status only
    // -------------------------------------------------------------------------
    public function updateStatus(int $id): ResponseInterface
    {
        $tenantId = (int) session()->get('tenant_id');
        $plan     = $this->planModel->forTenantById($tenantId, $id);

        if (! $plan) {
            return $this->response->setStatusCode(404)->setJSON(['error' => 'Not found']);
        }

        $status = $this->request->getPost('status');
        if (! in_array($status, ['todo', 'in_progress', 'done'], true)) {
            return $this->response->setStatusCode(422)->setJSON(['error' => 'Invalid status']);
        }

        $this->planModel->update($id, ['status' => $status]);

        return $this->response->setJSON([
            'ok'             => true,
            'csrf_token_name'=> csrf_token(),
            'csrf_hash'      => csrf_hash(),
        ]);
    }

    // -------------------------------------------------------------------------
    // POST /action-plan/(:num)/delete  — soft delete
    // -------------------------------------------------------------------------
    public function delete(int $id)
    {
        $tenantId = (int) session()->get('tenant_id');
        $plan     = $this->planModel->forTenantById($tenantId, $id);

        if (! $plan) {
            return redirect()->to(site_url('action-plan'))->with('error', lang('ActionPlan.not_found'));
        }

        $this->planModel->delete($id);

        return redirect()->to(site_url('action-plan'))
            ->with('success', lang('ActionPlan.deleted'));
    }
}

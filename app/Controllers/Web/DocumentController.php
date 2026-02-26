<?php

namespace App\Controllers\Web;

use App\Controllers\BaseController;
use App\Models\DocumentModel;
use App\Models\ControlModel;
use App\Models\ActionPlanModel;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Psr\Log\LoggerInterface;

/**
 * Document management — per-tenant file vault.
 *
 * Routes:
 *   GET  /docs                      → index()    — list with filters
 *   GET  /docs/upload               → upload()   — upload form
 *   POST /docs/upload               → store()    — save file + DB row
 *   GET  /docs/(:num)/download      → download() — stream file
 *   POST /docs/(:num)/delete        → destroy()  — soft-delete + unlink
 */
class DocumentController extends BaseController
{
    private DocumentModel   $docModel;
    private ControlModel    $controlModel;
    private ActionPlanModel $planModel;

    /** Maximum upload size: 20 MB */
    private const MAX_SIZE_BYTES = 20 * 1024 * 1024;

    /** Allowed MIME types */
    private const ALLOWED_MIMES = [
        'application/pdf',
        'application/msword',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'application/vnd.ms-excel',
        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        'application/vnd.ms-powerpoint',
        'application/vnd.openxmlformats-officedocument.presentationml.presentation',
        'text/plain',
        'text/csv',
        'image/png',
        'image/jpeg',
        'image/gif',
        'application/zip',
    ];

    public function initController(
        RequestInterface $request,
        ResponseInterface $response,
        LoggerInterface $logger
    ): void {
        parent::initController($request, $response, $logger);
        $this->docModel     = new DocumentModel();
        $this->controlModel = new ControlModel();
        $this->planModel    = new ActionPlanModel();
    }

    // -------------------------------------------------------------------------
    // GET /docs  — list documents
    // -------------------------------------------------------------------------
    public function index(): string
    {
        $tenantId = (int) session()->get('tenant_id');
        $category = $this->request->getGet('category') ?: null;
        $search   = trim((string)($this->request->getGet('q') ?? '')) ?: null;

        $docs  = $this->docModel->forTenant($tenantId, $category, $search);
        $stats = $this->docModel->categoryStats($tenantId);

        $categories = ['policy', 'procedure', 'evidence', 'template', 'other'];

        return view('docs/index', [
            'title'      => lang('Doc.page_title'),
            'docs'       => $docs,
            'stats'      => $stats,
            'categories' => $categories,
            'currentCat' => $category,
            'search'     => $search ?? '',
        ]);
    }

    // -------------------------------------------------------------------------
    // GET /docs/upload  — upload form
    // -------------------------------------------------------------------------
    public function upload(): string
    {
        $tenantId = (int) session()->get('tenant_id');

        // Controls for optional link dropdown
        $db           = \Config\Database::connect();
        $svIds        = $db->table('tenant_standards ts')
            ->select('ts.standard_version_id')
            ->where('ts.tenant_id', $tenantId)
            ->get()->getResultArray();
        $versionIds   = array_column($svIds, 'standard_version_id');

        $controls = [];
        if (! empty($versionIds)) {
            $controls = $db->table('controls c')
                ->select('c.id, c.code, c.title, c.title_fr, d.code AS domain_code')
                ->join('clauses cl', 'cl.id = c.clause_id')
                ->join('domains d',  'd.id  = cl.domain_id')
                ->whereIn('d.standard_version_id', $versionIds)
                ->orderBy('c.code', 'ASC')
                ->get()->getResultArray();
        }

        // Action plans for optional link dropdown
        $plans = $this->planModel->forTenant($tenantId);

        return view('docs/upload', [
            'title'    => lang('Doc.upload_title'),
            'controls' => $controls,
            'plans'    => $plans,
        ]);
    }

    // -------------------------------------------------------------------------
    // POST /docs/upload  — store uploaded file
    // -------------------------------------------------------------------------
    public function store(): \CodeIgniter\HTTP\RedirectResponse|string
    {
        $tenantId = (int) session()->get('tenant_id');
        $userId   = (int) session()->get('user_id');

        $file = $this->request->getFile('document');

        // --- Validations ---
        if (! $file || ! $file->isValid() || $file->hasMoved()) {
            return redirect()->back()->with('error', lang('Doc.error_no_file'));
        }

        if ($file->getSize() > self::MAX_SIZE_BYTES) {
            return redirect()->back()->with('error', lang('Doc.error_too_large'));
        }

        if (! in_array($file->getMimeType(), self::ALLOWED_MIMES, true)) {
            return redirect()->back()->with('error', lang('Doc.error_mime'));
        }

        $title    = trim($this->request->getPost('title') ?? '');
        $category = $this->request->getPost('category') ?? 'other';

        if (empty($title)) {
            return redirect()->back()->with('error', lang('Doc.error_title_required'));
        }

        // --- Move file to writable/uploads/docs/{tenantId}/ ---
        $uploadDir = WRITEPATH . 'uploads/docs/' . $tenantId . '/';
        if (! is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        $newName = $file->getRandomName();
        $file->move($uploadDir, $newName);

        // --- DB insert ---
        $this->docModel->insert([
            'tenant_id'             => $tenantId,
            'title'                 => $title,
            'description'           => trim($this->request->getPost('description') ?? ''),
            'category'              => $category,
            'file_name'             => $file->getClientName(),
            'file_path'             => 'uploads/docs/' . $tenantId . '/' . $newName,
            'file_size'             => $file->getSize(),
            'mime_type'             => $file->getMimeType(),
            'linked_control_id'     => ($this->request->getPost('linked_control_id')    ?: null),
            'linked_action_plan_id' => ($this->request->getPost('linked_action_plan_id') ?: null),
            'uploaded_by'           => $userId,
        ]);

        return redirect()->to(site_url('docs'))->with('success', lang('Doc.upload_success'));
    }

    // -------------------------------------------------------------------------
    // GET /docs/(:num)/download  — stream file to browser
    // -------------------------------------------------------------------------
    public function download(int $docId): \CodeIgniter\HTTP\Response|string
    {
        $tenantId = (int) session()->get('tenant_id');
        $doc      = $this->docModel->forTenantById($tenantId, $docId);

        if (! $doc) {
            return redirect()->to(site_url('docs'))->with('error', lang('Doc.error_not_found'));
        }

        $fullPath = WRITEPATH . $doc['file_path'];

        if (! is_file($fullPath)) {
            return redirect()->to(site_url('docs'))->with('error', lang('Doc.error_file_missing'));
        }

        return $this->response
            ->setHeader('Content-Type', $doc['mime_type'] ?? 'application/octet-stream')
            ->setHeader('Content-Disposition', 'attachment; filename="' . $doc['file_name'] . '"')
            ->setHeader('Content-Length', (string)filesize($fullPath))
            ->setBody(file_get_contents($fullPath));
    }

    // -------------------------------------------------------------------------
    // POST /docs/(:num)/delete  — soft-delete + unlink physical file
    // -------------------------------------------------------------------------
    public function destroy(int $docId): \CodeIgniter\HTTP\RedirectResponse
    {
        $tenantId = (int) session()->get('tenant_id');
        $doc      = $this->docModel->forTenantById($tenantId, $docId);

        if (! $doc) {
            return redirect()->to(site_url('docs'))->with('error', lang('Doc.error_not_found'));
        }

        // Soft-delete in DB
        $this->docModel->delete($docId);

        // Remove physical file
        $fullPath = WRITEPATH . $doc['file_path'];
        if (is_file($fullPath)) {
            @unlink($fullPath);
        }

        return redirect()->to(site_url('docs'))->with('success', lang('Doc.delete_success'));
    }
}

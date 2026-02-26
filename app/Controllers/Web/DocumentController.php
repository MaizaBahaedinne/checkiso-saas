<?php

namespace App\Controllers\Web;

use App\Controllers\BaseController;
use App\Models\DocumentModel;
use App\Models\DocumentVersionModel;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Psr\Log\LoggerInterface;

/**
 * Wiki-style document management.
 *
 * Routes:
 *   GET  /docs                              → index()       — list
 *   GET  /docs/create                       → create()      — editor (new)
 *   POST /docs                              → store()       — save new doc
 *   GET  /docs/(:num)                       → show()        — read view
 *   GET  /docs/(:num)/edit                  → edit()        — editor (existing)
 *   POST /docs/(:num)/update                → update()      — save edits
 *   GET  /docs/(:num)/history               → history()     — version list
 *   GET  /docs/(:num)/version/(:num)        → showVersion() — view old version
 *   POST /docs/(:num)/restore/(:num)        → restore()     — restore old version
 *   POST /docs/(:num)/delete                → destroy()     — soft delete
 */
class DocumentController extends BaseController
{
    private DocumentModel        $docModel;
    private DocumentVersionModel $versionModel;

    public function initController(
        RequestInterface $request,
        ResponseInterface $response,
        LoggerInterface $logger
    ): void {
        parent::initController($request, $response, $logger);
        $this->docModel     = new DocumentModel();
        $this->versionModel = new DocumentVersionModel();
    }

    // -------------------------------------------------------------------------
    // GET /docs  — list
    // -------------------------------------------------------------------------
    public function index(): string
    {
        $tenantId   = (int) session()->get('tenant_id');
        $category   = $this->request->getGet('category') ?: null;
        $search     = trim((string)($this->request->getGet('q') ?? '')) ?: null;
        $docs       = $this->docModel->forTenant($tenantId, $category, $search);
        $stats      = $this->docModel->categoryStats($tenantId);
        $categories = ['policy', 'procedure', 'guide', 'reference', 'template', 'other'];

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
    // GET /docs/create  — new document editor
    // -------------------------------------------------------------------------
    public function create(): string
    {
        return view('docs/create', ['title' => lang('Doc.create_title')]);
    }

    // -------------------------------------------------------------------------
    // POST /docs  — save new document
    // -------------------------------------------------------------------------
    public function store(): \CodeIgniter\HTTP\RedirectResponse
    {
        $tenantId = (int) session()->get('tenant_id');
        $userId   = (int) session()->get('user_id');

        $title    = trim($this->request->getPost('title') ?? '');
        $content  = $this->request->getPost('content') ?? '';
        $category = $this->request->getPost('category') ?? 'other';
        $desc     = trim($this->request->getPost('description') ?? '');
        $summary  = trim($this->request->getPost('change_summary') ?? 'Version initiale');

        if (empty($title)) {
            return redirect()->back()->withInput()->with('error', lang('Doc.error_title_required'));
        }

        $slug = $this->docModel->uniqueSlug($tenantId, $this->slugify($title));

        $db = \Config\Database::connect();
        $db->transStart();

        $docId = $this->docModel->insert([
            'tenant_id'       => $tenantId,
            'title'           => $title,
            'slug'            => $slug,
            'category'        => $category,
            'description'     => $desc,
            'content'         => $content,
            'current_version' => 1,
            'created_by'      => $userId,
        ]);

        $this->versionModel->insert([
            'document_id'    => $docId,
            'version_number' => 1,
            'title'          => $title,
            'content'        => $content,
            'change_summary' => $summary,
            'changed_by'     => $userId,
        ]);

        $db->transComplete();

        return redirect()->to(site_url('docs/' . $docId))->with('success', lang('Doc.create_success'));
    }

    // -------------------------------------------------------------------------
    // GET /docs/(:num)  — read view
    // -------------------------------------------------------------------------
    public function show(int $id): string|\CodeIgniter\HTTP\RedirectResponse
    {
        $tenantId = (int) session()->get('tenant_id');
        $doc      = $this->docModel->forTenantById($tenantId, $id);

        if (! $doc) {
            return redirect()->to(site_url('docs'))->with('error', lang('Doc.error_not_found'));
        }

        $author = null;
        if ($doc['created_by']) {
            $author = \Config\Database::connect()
                ->table('users')
                ->select('first_name, last_name')
                ->where('id', $doc['created_by'])
                ->get()->getRowArray();
        }

        return view('docs/show', [
            'title'     => esc($doc['title']) . ' — ' . lang('Doc.page_title'),
            'doc'       => $doc,
            'author'    => $author,
            'isArchive' => false,
        ]);
    }

    // -------------------------------------------------------------------------
    // GET /docs/(:num)/edit  — editor (existing)
    // -------------------------------------------------------------------------
    public function edit(int $id): string|\CodeIgniter\HTTP\RedirectResponse
    {
        $tenantId = (int) session()->get('tenant_id');
        $doc      = $this->docModel->forTenantById($tenantId, $id);

        if (! $doc) {
            return redirect()->to(site_url('docs'))->with('error', lang('Doc.error_not_found'));
        }

        return view('docs/edit', [
            'title' => lang('Doc.edit_title') . ' — ' . esc($doc['title']),
            'doc'   => $doc,
        ]);
    }

    // -------------------------------------------------------------------------
    // POST /docs/(:num)/update  — save edits + new version
    // -------------------------------------------------------------------------
    public function update(int $id): \CodeIgniter\HTTP\RedirectResponse
    {
        $tenantId = (int) session()->get('tenant_id');
        $userId   = (int) session()->get('user_id');
        $doc      = $this->docModel->forTenantById($tenantId, $id);

        if (! $doc) {
            return redirect()->to(site_url('docs'))->with('error', lang('Doc.error_not_found'));
        }

        $title    = trim($this->request->getPost('title') ?? '');
        $content  = $this->request->getPost('content') ?? '';
        $category = $this->request->getPost('category') ?? $doc['category'];
        $desc     = trim($this->request->getPost('description') ?? '');
        $summary  = trim($this->request->getPost('change_summary') ?? '');

        if (empty($title)) {
            return redirect()->back()->withInput()->with('error', lang('Doc.error_title_required'));
        }

        $newVersion = (int)$doc['current_version'] + 1;

        $db = \Config\Database::connect();
        $db->transStart();

        $this->docModel->update($id, [
            'title'           => $title,
            'category'        => $category,
            'description'     => $desc,
            'content'         => $content,
            'current_version' => $newVersion,
        ]);

        $this->versionModel->insert([
            'document_id'    => $id,
            'version_number' => $newVersion,
            'title'          => $title,
            'content'        => $content,
            'change_summary' => $summary ?: 'Mise à jour v' . $newVersion,
            'changed_by'     => $userId,
        ]);

        $db->transComplete();

        return redirect()->to(site_url('docs/' . $id))->with('success', lang('Doc.update_success'));
    }

    // -------------------------------------------------------------------------
    // GET /docs/(:num)/history  — version list
    // -------------------------------------------------------------------------
    public function history(int $id): string|\CodeIgniter\HTTP\RedirectResponse
    {
        $tenantId = (int) session()->get('tenant_id');
        $doc      = $this->docModel->forTenantById($tenantId, $id);

        if (! $doc) {
            return redirect()->to(site_url('docs'))->with('error', lang('Doc.error_not_found'));
        }

        $versions = $this->versionModel->forDocument($id);

        return view('docs/history', [
            'title'    => lang('Doc.history_title') . ' — ' . esc($doc['title']),
            'doc'      => $doc,
            'versions' => $versions,
        ]);
    }

    // -------------------------------------------------------------------------
    // GET /docs/(:num)/version/(:num)  — view old version
    // -------------------------------------------------------------------------
    public function showVersion(int $docId, int $vn): string|\CodeIgniter\HTTP\RedirectResponse
    {
        $tenantId = (int) session()->get('tenant_id');
        $doc      = $this->docModel->forTenantById($tenantId, $docId);

        if (! $doc) {
            return redirect()->to(site_url('docs'))->with('error', lang('Doc.error_not_found'));
        }

        $version = $this->versionModel->getVersion($docId, $vn);
        if (! $version) {
            return redirect()->to(site_url('docs/' . $docId . '/history'))->with('error', lang('Doc.error_not_found'));
        }

        return view('docs/show', [
            'title'     => esc($doc['title']) . ' v' . $vn . ' — ' . lang('Doc.page_title'),
            'doc'       => $doc,
            'version'   => $version,
            'isArchive' => true,
            'author'    => null,
        ]);
    }

    // -------------------------------------------------------------------------
    // POST /docs/(:num)/restore/(:num)  — restore old version as new current
    // -------------------------------------------------------------------------
    public function restore(int $docId, int $vn): \CodeIgniter\HTTP\RedirectResponse
    {
        $tenantId = (int) session()->get('tenant_id');
        $userId   = (int) session()->get('user_id');
        $doc      = $this->docModel->forTenantById($tenantId, $docId);

        if (! $doc) {
            return redirect()->to(site_url('docs'))->with('error', lang('Doc.error_not_found'));
        }

        $version = $this->versionModel->getVersion($docId, $vn);
        if (! $version) {
            return redirect()->to(site_url('docs/' . $docId . '/history'))->with('error', lang('Doc.error_not_found'));
        }

        $newVersion = (int)$doc['current_version'] + 1;

        $db = \Config\Database::connect();
        $db->transStart();

        $this->docModel->update($docId, [
            'title'           => $version['title'],
            'content'         => $version['content'],
            'current_version' => $newVersion,
        ]);

        $this->versionModel->insert([
            'document_id'    => $docId,
            'version_number' => $newVersion,
            'title'          => $version['title'],
            'content'        => $version['content'],
            'change_summary' => 'Restauration depuis v' . $vn,
            'changed_by'     => $userId,
        ]);

        $db->transComplete();

        return redirect()->to(site_url('docs/' . $docId))->with('success', lang('Doc.restore_success'));
    }

    // -------------------------------------------------------------------------
    // POST /docs/(:num)/delete  — soft delete
    // -------------------------------------------------------------------------
    public function destroy(int $id): \CodeIgniter\HTTP\RedirectResponse
    {
        $tenantId = (int) session()->get('tenant_id');
        $doc      = $this->docModel->forTenantById($tenantId, $id);

        if (! $doc) {
            return redirect()->to(site_url('docs'))->with('error', lang('Doc.error_not_found'));
        }

        $this->docModel->delete($id);

        return redirect()->to(site_url('docs'))->with('success', lang('Doc.delete_success'));
    }

    // -------------------------------------------------------------------------
    private function slugify(string $text): string
    {
        $text = mb_strtolower($text, 'UTF-8');
        $text = preg_replace('/[^a-z0-9\s-]/u', '', $text);
        $text = preg_replace('/[\s-]+/', '-', $text);
        return trim($text, '-') ?: 'document';
    }
}

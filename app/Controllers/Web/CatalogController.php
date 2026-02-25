<?php

namespace App\Controllers\Web;

use App\Controllers\BaseController;
use App\Models\StandardVersionModel;
use App\Models\DomainModel;
use App\Models\ClauseModel;
use App\Models\ControlModel;

class CatalogController extends BaseController
{
    private StandardVersionModel $svModel;
    private DomainModel          $domainModel;
    private ClauseModel          $clauseModel;
    private ControlModel         $controlModel;

    public function __construct()
    {
        $this->svModel      = new StandardVersionModel();
        $this->domainModel  = new DomainModel();
        $this->clauseModel  = new ClauseModel();
        $this->controlModel = new ControlModel();
    }

    // -------------------------------------------------------------------------
    // GET /catalog
    // -------------------------------------------------------------------------

    /**
     * List all active standard versions with subscription status for the tenant.
     */
    public function index(): string
    {
        $tenantId   = (int) session()->get('tenant_id');
        $allVersions = $this->svModel->getActive();

        // Tag each version with subscription state
        $subscribedIds = array_column($this->svModel->forTenant($tenantId), 'standard_version_id');

        foreach ($allVersions as &$v) {
            $v['subscribed'] = in_array($v['id'], $subscribedIds, true);
        }
        unset($v);

        return view('catalog/index', [
            'versions'      => $allVersions,
            'subscribedIds' => $subscribedIds,
        ]);
    }

    // -------------------------------------------------------------------------
    // GET /catalog/(:num)
    // -------------------------------------------------------------------------

    /**
     * Show the full arborescence: domain → clause → controls.
     */
    public function show(int $versionId): string
    {
        $tenantId = (int) session()->get('tenant_id');

        $version = $this->svModel->getWithStandard($versionId);
        if ($version === null) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound();
        }

        $subscribed = $this->svModel->isSubscribed($tenantId, $versionId);
        $domains    = $this->domainModel->forVersion($versionId);
        $clauses    = $this->clauseModel->forVersion($versionId);
        $controls   = $this->controlModel->forVersion($versionId);

        // Index clauses by domain_id
        $clausesByDomain = [];
        foreach ($clauses as $clause) {
            $clausesByDomain[$clause['domain_id']][] = $clause;
        }

        return view('catalog/show', [
            'version'        => $version,
            'subscribed'     => $subscribed,
            'domains'        => $domains,
            'clausesByDomain'=> $clausesByDomain,
            'controlsByClause' => $controls,  // already grouped by clause_id from ControlModel
        ]);
    }

    // -------------------------------------------------------------------------
    // POST /catalog/(:num)/subscribe
    // -------------------------------------------------------------------------

    public function subscribe(int $versionId): \CodeIgniter\HTTP\RedirectResponse
    {
        $tenantId = (int) session()->get('tenant_id');
        $userId   = (int) session()->get('user_id');

        $version = $this->svModel->getWithStandard($versionId);
        if ($version === null) {
            return redirect()->to('/catalog')->with('error', 'Standard introuvable.');
        }

        $this->svModel->subscribe($tenantId, $versionId, $userId);

        return redirect()->to('/catalog/' . $versionId)
            ->with('success', 'Vous êtes maintenant abonné à ' . esc($version['standard_code']) . ' ' . esc($version['version_code']) . '.');
    }

    // -------------------------------------------------------------------------
    // POST /catalog/(:num)/unsubscribe
    // -------------------------------------------------------------------------

    public function unsubscribe(int $versionId): \CodeIgniter\HTTP\RedirectResponse
    {
        $tenantId = (int) session()->get('tenant_id');

        $version = $this->svModel->getWithStandard($versionId);
        if ($version === null) {
            return redirect()->to('/catalog')->with('error', 'Standard introuvable.');
        }

        $this->svModel->unsubscribe($tenantId, $versionId);

        return redirect()->to('/catalog')
            ->with('success', 'Abonnement à ' . esc($version['standard_code']) . ' ' . esc($version['version_code']) . ' annulé.');
    }
}

<?php

namespace App\Controllers\Web;

use App\Controllers\BaseController;
use App\Models\StandardVersionModel;
use App\Models\DomainModel;
use App\Models\ClauseModel;
use App\Models\ControlModel;
use App\Models\ControlAssessmentModel;
use CodeIgniter\HTTP\ResponseInterface;

class GapController extends BaseController
{
    private StandardVersionModel   $svModel;
    private DomainModel            $domainModel;
    private ClauseModel            $clauseModel;
    private ControlModel           $controlModel;
    private ControlAssessmentModel $assessModel;

    public function __construct()
    {
        $this->svModel      = new StandardVersionModel();
        $this->domainModel  = new DomainModel();
        $this->clauseModel  = new ClauseModel();
        $this->controlModel = new ControlModel();
        $this->assessModel  = new ControlAssessmentModel();
    }

    // -------------------------------------------------------------------------
    // GET /gap  — list of subscribed standards with gap progress
    // -------------------------------------------------------------------------

    public function index(): string
    {
        $tenantId  = (int) session()->get('tenant_id');
        $standards = $this->svModel->forTenant($tenantId);

        foreach ($standards as &$s) {
            $s['stats'] = $this->assessModel->getGlobalStats($tenantId, (int) $s['id']);
        }
        unset($s);

        return view('gap/index', ['standards' => $standards]);
    }

    // -------------------------------------------------------------------------
    // GET /gap/(:num)  — full assessment form
    // -------------------------------------------------------------------------

    public function show(int $versionId)
    {
        $tenantId = (int) session()->get('tenant_id');

        $version = $this->svModel->getWithStandard($versionId);
        if ($version === null) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound();
        }

        if (! $this->svModel->isSubscribed($tenantId, $versionId)) {
            return redirect()->to('/catalog/' . $versionId)
                ->with('error', "Abonnez-vous d'abord à ce référentiel pour l'évaluer.");
        }

        $domains         = $this->domainModel->forVersion($versionId);
        $clauses         = $this->clauseModel->forVersion($versionId);
        $controlsByClause = $this->controlModel->forVersion($versionId);
        $assessments     = $this->assessModel->forTenantVersion($tenantId, $versionId);
        $globalStats     = $this->assessModel->getGlobalStats($tenantId, $versionId);
        $domainStats     = $this->assessModel->getStats($tenantId, $versionId);

        $clausesByDomain = [];
        foreach ($clauses as $clause) {
            $clausesByDomain[$clause['domain_id']][] = $clause;
        }

        $domainStatsById = [];
        foreach ($domainStats as $ds) {
            $domainStatsById[$ds['domain_id']] = $ds;
        }

        return view('gap/show', [
            'version'          => $version,
            'versionId'        => $versionId,
            'domains'          => $domains,
            'clausesByDomain'  => $clausesByDomain,
            'controlsByClause' => $controlsByClause,
            'assessments'      => $assessments,
            'globalStats'      => $globalStats,
            'domainStatsById'  => $domainStatsById,
        ]);
    }

    // -------------------------------------------------------------------------
    // POST /gap/(:num)/control  — AJAX: save one control assessment
    // -------------------------------------------------------------------------

    public function saveControl(int $versionId): ResponseInterface
    {
        if (! $this->request->isAJAX()) {
            return $this->response->setStatusCode(403)->setBody('Forbidden');
        }

        $tenantId  = (int) session()->get('tenant_id');
        $userId    = (int) session()->get('user_id');

        $json      = $this->request->getJSON(true);
        $controlId = (int) ($json['control_id'] ?? 0);
        $status    = $json['status'] ?? '';
        $notes     = trim($json['notes'] ?? '');

        if ($controlId === 0) {
            return $this->response->setJSON(['success' => false, 'message' => 'Contrôle invalide']);
        }

        if ($status === '') {
            // Clear the assessment
            $this->assessModel->remove($tenantId, $controlId);
        } elseif (in_array($status, ControlAssessmentModel::STATUSES, true)) {
            $this->assessModel->upsert($tenantId, $versionId, $controlId, $status, $notes, $userId);
        } else {
            return $this->response->setJSON(['success' => false, 'message' => 'Statut invalide']);
        }

        $globalStats = $this->assessModel->getGlobalStats($tenantId, $versionId);
        $domainStats = $this->assessModel->getStats($tenantId, $versionId);

        return $this->response->setJSON([
            'success'      => true,
            'global'       => $globalStats,
            'domain_stats' => $domainStats,
            'csrf_hash'    => csrf_hash(),
        ]);
    }

    // -------------------------------------------------------------------------
    // GET /gap/(:num)/summary  — conformity dashboard
    // -------------------------------------------------------------------------

    public function summary(int $versionId)
    {
        $tenantId = (int) session()->get('tenant_id');

        $version = $this->svModel->getWithStandard($versionId);
        if ($version === null) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound();
        }

        if (! $this->svModel->isSubscribed($tenantId, $versionId)) {
            return redirect()->to('/catalog/' . $versionId)
                ->with('error', "Abonnez-vous d'abord à ce référentiel.");
        }

        $globalStats = $this->assessModel->getGlobalStats($tenantId, $versionId);
        $domainStats = $this->assessModel->getStats($tenantId, $versionId);

        return view('gap/summary', [
            'version'     => $version,
            'versionId'   => $versionId,
            'globalStats' => $globalStats,
            'domainStats' => $domainStats,
        ]);
    }
}

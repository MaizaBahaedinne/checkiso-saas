<?php

namespace App\Controllers\Web;

use App\Controllers\BaseController;
use App\Models\StandardVersionModel;
use App\Models\DomainModel;
use App\Models\ClauseModel;
use App\Models\ControlModel;
use App\Models\ControlQuestionModel;
use App\Models\GapSessionModel;
use App\Models\GapAnswerModel;
use CodeIgniter\HTTP\ResponseInterface;

class GapController extends BaseController
{
    private StandardVersionModel $svModel;
    private DomainModel          $domainModel;
    private ClauseModel          $clauseModel;
    private ControlModel         $controlModel;
    private ControlQuestionModel $questionModel;
    private GapSessionModel      $sessionModel;
    private GapAnswerModel       $answerModel;

    public function __construct()
    {
        $this->svModel       = new StandardVersionModel();
        $this->domainModel   = new DomainModel();
        $this->clauseModel   = new ClauseModel();
        $this->controlModel  = new ControlModel();
        $this->questionModel = new ControlQuestionModel();
        $this->sessionModel  = new GapSessionModel();
        $this->answerModel   = new GapAnswerModel();
    }

    // =========================================================================
    // GET /gap  — list subscribed standards with session progress
    // =========================================================================

    public function index(): string
    {
        $tenantId  = (int) session()->get('tenant_id');
        $standards = $this->svModel->forTenant($tenantId);

        foreach ($standards as &$sv) {
            $gapSession = $this->sessionModel
                ->where('tenant_id', $tenantId)
                ->where('standard_version_id', $sv['id'])
                ->first();

            $sv['gap_session'] = $gapSession;
        }
        unset($sv);

        return view('gap/index', ['standards' => $standards]);
    }

    // =========================================================================
    // GET /gap/(:num)  — quiz interface (per-domain accordion)
    // =========================================================================

    public function show(int $versionId): string
    {
        $tenantId = (int) session()->get('tenant_id');

        // Verify subscription
        if (! $this->svModel->isSubscribed($tenantId, $versionId)) {
            return redirect()->to('/gap')->with('error', "Vous n'êtes pas abonné à cette norme.")->send();
        }

        $sv      = $this->svModel->getWithStandard($versionId);
        $domains = $this->domainModel->forVersion($versionId);

        // Build domain → clauses → controls tree, attach quiz questions
        foreach ($domains as &$domain) {
            $domain['clauses'] = $this->clauseModel->forDomain($domain['id']);
            foreach ($domain['clauses'] as &$clause) {
                $clause['controls'] = $this->controlModel->forClause($clause['id']);
                foreach ($clause['controls'] as &$control) {
                    $control['question'] = $this->questionModel->forControl($control['id']);
                }
                unset($control);
            }
            unset($clause);
        }
        unset($domain);

        // Locale-aware display names
        $locale = session()->get('lang') ?? 'fr';
        foreach ($domains as &$domain) {
            $domain['display_name'] = ($locale === 'fr' && !empty($domain['name_fr'])) ? $domain['name_fr'] : $domain['name'];
            foreach ($domain['clauses'] as &$clause) {
                $clause['display_title'] = ($locale === 'fr' && !empty($clause['title_fr'])) ? $clause['title_fr'] : $clause['title'];
                foreach ($clause['controls'] as &$control) {
                    $control['display_title'] = ($locale === 'fr' && !empty($control['title_fr'])) ? $control['title_fr'] : $control['title'];
                }
                unset($control);
            }
            unset($clause);
        }
        unset($domain);

        // Get or create the gap session
        $gapSession = $this->sessionModel->getOrCreate($tenantId, $versionId);

        // Load existing answers indexed by control_id
        $answers = $this->answerModel->forSession($gapSession['id']);

        return view('gap/show', [
            'sv'         => $sv,
            'domains'    => $domains,
            'gapSession' => $gapSession,
            'answers'    => $answers,
        ]);
    }

    // =========================================================================
    // POST /gap/(:num)/answer  — AJAX: save one answer (auto-save draft)
    // =========================================================================

    public function saveAnswer(int $versionId): ResponseInterface
    {
        if (! $this->request->isAJAX()) {
            return $this->response->setStatusCode(400)->setJSON(['error' => 'AJAX uniquement.']);
        }

        $tenantId  = (int) session()->get('tenant_id');
        $userId    = (int) session()->get('user_id');
        $controlId = (int) $this->request->getPost('control_id');
        $choiceId  = (int) $this->request->getPost('choice_id');
        $justif    = (string) $this->request->getPost('justification');
        $otherText = (string) $this->request->getPost('other_text');

        // Basic validation
        if (! $controlId || ! $choiceId) {
            return $this->response->setStatusCode(422)->setJSON(['error' => 'Paramètres manquants.']);
        }

        // Load the chosen choice to check requires_justification
        $choice = db_connect()->table('control_choices')
            ->where('id', $choiceId)->get()->getRowArray();

        if (! $choice) {
            return $this->response->setStatusCode(422)->setJSON(['error' => 'Choix invalide.']);
        }

        if ($choice['requires_justification'] && trim($justif) === '' && trim($otherText) === '') {
            return $this->response->setStatusCode(422)->setJSON([
                'error' => 'Une justification est requise pour cette réponse.',
            ]);
        }

        // Ensure session belongs to this tenant
        $gapSession = $this->sessionModel
            ->where('tenant_id', $tenantId)
            ->where('standard_version_id', $versionId)
            ->first();

        if (! $gapSession) {
            $gapSession = $this->sessionModel->getOrCreate($tenantId, $versionId);
        }

        if ($gapSession['status'] === 'submitted') {
            return $this->response->setStatusCode(403)->setJSON([
                'error' => 'Cette évaluation a déjà été soumise et ne peut plus être modifiée.',
            ]);
        }

        // Save / update the answer
        $answer = $this->answerModel->upsert(
            $gapSession['id'],
            $controlId,
            $choiceId,
            $justif,
            $otherText,
            $userId
        );

        // Recompute progress
        $gapSession = $this->sessionModel->updateProgress($gapSession['id']);

        return $this->response->setJSON([
            'ok'               => true,
            'answer'           => $answer,
            'answered'         => (int) $gapSession['answered_controls'],
            'total'            => (int) $gapSession['total_controls'],
            'score'            => (float) $gapSession['score'],
            'is_complete'      => $gapSession['answered_controls'] >= $gapSession['total_controls'],
            'csrf_token_name'  => csrf_token(),
            'csrf_hash'        => csrf_hash(),
        ]);
    }

    // =========================================================================
    // POST /gap/(:num)/submit  — finalize the session
    // =========================================================================

    public function submit(int $versionId): ResponseInterface
    {
        $tenantId = (int) session()->get('tenant_id');
        $userId   = (int) session()->get('user_id');

        $gapSession = $this->sessionModel
            ->where('tenant_id', $tenantId)
            ->where('standard_version_id', $versionId)
            ->first();

        if (! $gapSession) {
            if ($this->request->isAJAX()) {
                return $this->response->setStatusCode(404)->setJSON(['error' => 'Session introuvable.']);
            }
            return redirect()->to('/gap')->with('error', 'Session introuvable.')->send();
        }

        $result = $this->sessionModel->finalize($gapSession['id'], $userId);

        if (! $result['ok']) {
            if ($this->request->isAJAX()) {
                return $this->response->setStatusCode(422)->setJSON($result);
            }
            return redirect()->to("/gap/{$versionId}")->with('error', $result['message'])->send();
        }

        if ($this->request->isAJAX()) {
            return $this->response->setJSON([
                'ok'          => true,
                'redirect_to' => base_url("/gap/{$versionId}/summary"),
            ]);
        }

        return redirect()->to("/gap/{$versionId}/summary");
    }

    // =========================================================================
    // GET /gap/(:num)/summary  — results summary
    // =========================================================================

    public function summary(int $versionId): string
    {
        $tenantId = (int) session()->get('tenant_id');

        $gapSession = $this->sessionModel
            ->where('tenant_id', $tenantId)
            ->where('standard_version_id', $versionId)
            ->first();

        if (! $gapSession) {
            return redirect()->to('/gap')->send();
        }

        $sv              = $this->svModel->getWithStandard($versionId);
        $domainBreakdown = $this->answerModel->domainBreakdown($gapSession['id']);
        $manualItems     = $this->answerModel->manualReviewItems($gapSession['id']);

        // Locale-aware display names
        $locale = session()->get('lang') ?? 'fr';
        foreach ($domainBreakdown as &$d) {
            $d['display_name'] = ($locale === 'fr' && !empty($d['domain_name_fr'])) ? $d['domain_name_fr'] : $d['domain_name'];
        }
        unset($d);
        foreach ($manualItems as &$m) {
            $m['display_title'] = ($locale === 'fr' && !empty($m['control_title_fr'])) ? $m['control_title_fr'] : $m['control_title'];
        }
        unset($m);

        return view('gap/summary', [
            'sv'              => $sv,
            'gapSession'      => $gapSession,
            'domainBreakdown' => $domainBreakdown,
            'manualItems'     => $manualItems,
        ]);
    }
}

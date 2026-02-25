<?php

namespace App\Controllers\Web;

use App\Controllers\BaseController;
use App\Models\StandardVersionModel;
use App\Models\GapSessionModel;
use App\Models\GapAnswerModel;
use Dompdf\Dompdf;
use Dompdf\Options;

/**
 * Generates downloadable PDF reports.
 *
 * Routes:
 *   GET /gap/{versionId}/export-pdf  → gapReport($versionId)
 */
class PdfController extends BaseController
{
    // -------------------------------------------------------------------------
    // GET /gap/(:num)/export-pdf
    // -------------------------------------------------------------------------
    public function gapReport(int $versionId): void
    {
        $tenantId = (int) session()->get('tenant_id');

        // ── Load data (same as GapController::summary) ────────────────────
        $svModel      = new StandardVersionModel();
        $sessionModel = new GapSessionModel();
        $answerModel  = new GapAnswerModel();

        if (! $svModel->isSubscribed($tenantId, $versionId)) {
            redirect()->to('/gap')->with('error', "Accès non autorisé.")->send();
            return;
        }

        $sv = $svModel->getWithStandard($versionId);

        $gapSession = $sessionModel
            ->where('tenant_id', $tenantId)
            ->where('standard_version_id', $versionId)
            ->first();

        if (! $gapSession) {
            redirect()->to('/gap')->with('error', 'Session introuvable.')->send();
            return;
        }

        $domainBreakdown = $answerModel->domainBreakdown($gapSession['id']);
        $manualItems     = $answerModel->manualReviewItems($gapSession['id']);

        // Locale-aware display names
        $locale = session()->get('lang') ?? 'fr';
        foreach ($domainBreakdown as &$d) {
            $d['display_name'] = ($locale === 'fr' && !empty($d['domain_name_fr']))
                ? $d['domain_name_fr']
                : $d['domain_name'];
        }
        unset($d);
        foreach ($manualItems as &$m) {
            $m['display_title'] = ($locale === 'fr' && !empty($m['control_title_fr']))
                ? $m['control_title_fr']
                : $m['control_title'];
        }
        unset($m);

        // Tenant name for the cover page
        $db         = \Config\Database::connect();
        $tenant     = $db->table('tenants')->where('id', $tenantId)->get()->getRowArray();
        $tenantName = $tenant['name'] ?? 'Organisation';
        $userName   = session()->get('user_name') ?? '';

        // ── Render the HTML view ──────────────────────────────────────────
        $html = view('pdf/gap_report', [
            'sv'             => $sv,
            'gapSession'     => $gapSession,
            'domainBreakdown'=> $domainBreakdown,
            'manualItems'    => $manualItems,
            'tenantName'     => $tenantName,
            'userName'       => $userName,
            'generatedAt'    => date('d/m/Y à H:i'),
        ]);

        // ── Configure Dompdf ──────────────────────────────────────────────
        $options = new Options();
        $options->set('defaultFont', 'DejaVu Sans');
        $options->set('isHtml5ParserEnabled', true);
        $options->set('isPhpEnabled', false);
        $options->set('isFontSubsettingEnabled', true);
        $options->set('chroot', ROOTPATH);

        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($html, 'UTF-8');
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        // ── Stream as download ────────────────────────────────────────────
        $filename = sprintf(
            'rapport-gap-%s-%s-%s.pdf',
            strtolower(preg_replace('/[^a-zA-Z0-9]/', '', $sv['standard_code'])),
            strtolower(preg_replace('/[^a-zA-Z0-9]/', '', $sv['version_code'])),
            date('Ymd')
        );

        $dompdf->stream($filename, ['Attachment' => true]);
    }
}

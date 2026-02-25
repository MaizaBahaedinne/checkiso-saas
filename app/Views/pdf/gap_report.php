<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<title>Rapport GAP — <?= esc($sv['standard_code']) ?></title>
<style>
/* ── Base ── */
* { margin: 0; padding: 0; box-sizing: border-box; }
body {
    font-family: 'DejaVu Sans', Arial, sans-serif;
    font-size: 10pt;
    color: #1a1a2e;
    line-height: 1.5;
}

/* ── Cover page ── */
.cover {
    page-break-after: always;
    text-align: center;
    padding: 80px 40px 40px;
}
.cover .logo-band {
    background: #1a56db;
    color: #fff;
    padding: 18px 30px;
    border-radius: 8px;
    margin-bottom: 50px;
    display: inline-block;
}
.cover .logo-band .app-name {
    font-size: 22pt;
    font-weight: bold;
    letter-spacing: .08em;
}
.cover .logo-band .app-tag {
    font-size: 9pt;
    opacity: .8;
    margin-top: 2px;
}
.cover h1 {
    font-size: 20pt;
    font-weight: bold;
    color: #1a56db;
    margin-bottom: 8px;
}
.cover .subtitle {
    font-size: 13pt;
    color: #4b5563;
    margin-bottom: 40px;
}
.cover .meta-table {
    width: 100%;
    border-collapse: collapse;
    margin: 0 auto 40px;
    max-width: 420px;
    text-align: left;
}
.cover .meta-table td {
    padding: 7px 12px;
    border-bottom: 1px solid #e5e7eb;
    font-size: 10pt;
}
.cover .meta-table td:first-child {
    color: #6b7280;
    width: 140px;
}
.cover .meta-table td:last-child {
    font-weight: bold;
    color: #111827;
}
.cover .status-badge {
    display: inline-block;
    padding: 4px 14px;
    border-radius: 20px;
    font-size: 9pt;
    font-weight: bold;
    margin-top: 6px;
}
.cover .status-submitted { background: #d1fae5; color: #065f46; }
.cover .status-draft     { background: #fef3c7; color: #92400e; }
.cover .footer-note {
    font-size: 8pt;
    color: #9ca3af;
    margin-top: 40px;
}

/* ── Section header ── */
.section-title {
    font-size: 13pt;
    font-weight: bold;
    color: #1a56db;
    border-bottom: 2px solid #1a56db;
    padding-bottom: 4px;
    margin: 20px 0 14px;
}

/* ── KPI row ── */
.kpi-table {
    width: 100%;
    border-collapse: collapse;
    margin-bottom: 20px;
}
.kpi-table td {
    width: 25%;
    text-align: center;
    border: 1px solid #e5e7eb;
    padding: 14px 8px;
    border-radius: 6px;
}
.kpi-value {
    font-size: 18pt;
    font-weight: bold;
}
.kpi-label {
    font-size: 8pt;
    color: #6b7280;
    margin-top: 4px;
}
.color-blue    { color: #1a56db; }
.color-green   { color: #059669; }
.color-amber   { color: #d97706; }
.color-red     { color: #dc2626; }
.color-gray    { color: #6b7280; }

/* ── Progress bar ── */
.progress-wrap {
    background: #e5e7eb;
    border-radius: 6px;
    height: 14px;
    margin: 4px 0 6px;
    overflow: hidden;
}
.progress-fill {
    height: 14px;
    border-radius: 6px;
}
.fill-green  { background: #059669; }
.fill-amber  { background: #d97706; }
.fill-red    { background: #dc2626; }
.fill-blue   { background: #1a56db; }
.fill-gray   { background: #9ca3af; }

/* ── Domain table ── */
.domain-table {
    width: 100%;
    border-collapse: collapse;
    margin-bottom: 20px;
    font-size: 9.5pt;
}
.domain-table th {
    background: #f3f4f6;
    padding: 7px 10px;
    text-align: left;
    font-size: 9pt;
    color: #374151;
    border-bottom: 2px solid #d1d5db;
}
.domain-table td {
    padding: 7px 10px;
    border-bottom: 1px solid #f3f4f6;
    vertical-align: middle;
}
.domain-table tr:last-child td { border-bottom: none; }
.domain-badge {
    display: inline-block;
    background: #1a56db;
    color: #fff;
    font-size: 8pt;
    padding: 2px 7px;
    border-radius: 4px;
    font-family: 'Courier New', monospace;
    margin-right: 6px;
}
.score-badge {
    display: inline-block;
    padding: 2px 8px;
    border-radius: 10px;
    font-size: 8.5pt;
    font-weight: bold;
}
.badge-green { background: #d1fae5; color: #065f46; }
.badge-amber { background: #fef3c7; color: #92400e; }
.badge-red   { background: #fee2e2; color: #991b1b; }
.badge-gray  { background: #f3f4f6; color: #6b7280; }

/* ── Manual review section ── */
.manual-item {
    border: 1px solid #e5e7eb;
    border-left: 4px solid #0ea5e9;
    border-radius: 4px;
    padding: 10px 12px;
    margin-bottom: 8px;
}
.manual-item .ctrl-code {
    display: inline-block;
    background: #1e293b;
    color: #fff;
    font-family: 'Courier New', monospace;
    font-size: 8pt;
    padding: 1px 6px;
    border-radius: 3px;
    margin-right: 8px;
}
.manual-item .ctrl-title {
    font-weight: bold;
    font-size: 9.5pt;
    margin-bottom: 4px;
}
.manual-item .ctrl-text {
    color: #4b5563;
    font-size: 9pt;
    font-style: italic;
    margin-top: 4px;
}

/* ── Page break ── */
.page-break { page-break-before: always; }

/* ── Footer (via @page) ── */
@page {
    margin: 18mm 15mm 20mm;
}
</style>
</head>
<body>

<?php
$gs         = $gapSession;
$answered   = (int)$gs['answered_controls'];
$total      = (int)$gs['total_controls'];
$score      = (float)$gs['score'];
$pct        = $total > 0 ? round($answered / $total * 100) : 0;
$isLocked   = $gs['status'] === 'submitted';
$scoreColor = $score >= 75 ? 'green' : ($score >= 50 ? 'amber' : 'red');
$fillClass  = 'fill-' . $scoreColor;
$colorClass = 'color-' . $scoreColor;
?>

<!-- ═══════════════════════════════════════ COVER PAGE ══════════════════════ -->
<div class="cover">
    <div class="logo-band">
        <div class="app-name">CheckISO</div>
        <div class="app-tag">Plateforme d'évaluation ISO</div>
    </div>

    <h1><?= esc($sv['standard_code']) ?> <?= esc($sv['version_code']) ?></h1>
    <div class="subtitle">Rapport d'analyse de conformité Gap</div>

    <table class="meta-table">
        <tr>
            <td>Organisation</td>
            <td><?= esc($tenantName) ?></td>
        </tr>
        <tr>
            <td>Référentiel</td>
            <td><?= esc($sv['standard_name']) ?></td>
        </tr>
        <tr>
            <td>Version</td>
            <td><?= esc($sv['version_code']) ?></td>
        </tr>
        <tr>
            <td>Score global</td>
            <td class="<?= $colorClass ?>">
                <?= $answered > 0 ? number_format($score, 1) . ' %' : '—' ?>
            </td>
        </tr>
        <tr>
            <td>Avancement</td>
            <td><?= $answered ?> / <?= $total ?> contrôles (<?= $pct ?> %)</td>
        </tr>
        <tr>
            <td>Statut</td>
            <td>
                <span class="status-badge <?= $isLocked ? 'status-submitted' : 'status-draft' ?>">
                    <?= $isLocked ? '✓ Évaluation soumise' : '⏳ En cours' ?>
                </span>
            </td>
        </tr>
        <?php if ($isLocked && $gs['submitted_at']): ?>
        <tr>
            <td>Soumis le</td>
            <td><?= date('d/m/Y à H:i', strtotime($gs['submitted_at'])) ?></td>
        </tr>
        <?php endif ?>
        <tr>
            <td>Généré par</td>
            <td><?= esc($userName) ?></td>
        </tr>
        <tr>
            <td>Généré le</td>
            <td><?= esc($generatedAt) ?></td>
        </tr>
    </table>

    <div class="footer-note">
        Document généré automatiquement par CheckISO — confidentiel
    </div>
</div>

<!-- ═══════════════════════════════════════ PAGE 2 : RÉSUMÉ ═════════════════ -->
<div class="section-title">1. Vue d'ensemble</div>

<!-- KPI cards -->
<table class="kpi-table">
    <tr>
        <td>
            <div class="kpi-value color-blue"><?= $total ?></div>
            <div class="kpi-label">Contrôles total</div>
        </td>
        <td>
            <div class="kpi-value color-blue"><?= $answered ?></div>
            <div class="kpi-label">Contrôles répondus</div>
        </td>
        <td>
            <div class="kpi-value <?= $answered > 0 ? $colorClass : 'color-gray' ?>">
                <?= $answered > 0 ? number_format($score, 1) . '%' : '—' ?>
            </div>
            <div class="kpi-label">Score de conformité</div>
        </td>
        <td>
            <div class="kpi-value color-blue"><?= count($manualItems) ?></div>
            <div class="kpi-label">Revues manuelles</div>
        </td>
    </tr>
</table>

<!-- Global progress bar -->
<div style="margin-bottom:20px;">
    <div style="display:table;width:100%;margin-bottom:4px;">
        <span style="display:table-cell;font-size:9pt;color:#6b7280;">Progression globale</span>
        <span style="display:table-cell;text-align:right;font-size:9pt;font-weight:bold;"><?= $pct ?> %</span>
    </div>
    <div class="progress-wrap">
        <div class="progress-fill <?= $pct >= 75 ? 'fill-green' : ($pct >= 50 ? 'fill-amber' : 'fill-red') ?>"
             style="width:<?= $pct ?>%;"></div>
    </div>
</div>

<!-- ═══════════════════════════════════════ DÉTAIL PAR DOMAINE ══════════════ -->
<div class="section-title">2. Détail par domaine</div>

<table class="domain-table">
    <thead>
        <tr>
            <th style="width:40%">Domaine</th>
            <th style="width:12%;text-align:center">Répondus</th>
            <th style="width:12%;text-align:center">Revue manuelle</th>
            <th style="width:36%">Score moyen</th>
        </tr>
    </thead>
    <tbody>
    <?php foreach ($domainBreakdown as $d):
        $dScore  = round((float)$d['avg_score'], 1);
        $dAns    = (int)$d['answered'];
        $dManual = (int)$d['manual_review'];
        $dColor  = $dScore >= 75 ? 'green' : ($dScore >= 50 ? 'amber' : 'red');
        $dFill   = 'fill-' . $dColor;
        $dBadge  = 'badge-' . $dColor;
    ?>
    <tr>
        <td>
            <span class="domain-badge"><?= esc($d['domain_code']) ?></span>
            <?= esc($d['display_name']) ?>
        </td>
        <td style="text-align:center;"><?= $dAns ?></td>
        <td style="text-align:center;color:#0ea5e9;font-weight:bold;">
            <?= $dManual ?: '—' ?>
        </td>
        <td>
            <?php if ($dAns > 0): ?>
            <div class="progress-wrap" style="margin-bottom:3px;">
                <div class="progress-fill <?= $dFill ?>" style="width:<?= $dScore ?>%;"></div>
            </div>
            <span class="score-badge <?= $dBadge ?>"><?= number_format($dScore, 1) ?> %</span>
            <?php else: ?>
            <span style="color:#9ca3af;font-size:9pt;font-style:italic;">Non répondu</span>
            <?php endif ?>
        </td>
    </tr>
    <?php endforeach; ?>
    </tbody>
</table>

<!-- ═══════════════════════════════════════ REVUES MANUELLES ════════════════ -->
<?php if (! empty($manualItems)): ?>
<div class="page-break"></div>
<div class="section-title">3. Contrôles nécessitant une revue manuelle (<?= count($manualItems) ?>)</div>

<?php foreach ($manualItems as $m): ?>
<div class="manual-item">
    <div class="ctrl-title">
        <span class="ctrl-code"><?= esc($m['control_code']) ?></span>
        <?= esc($m['display_title']) ?>
    </div>
    <?php if ($m['other_text']): ?>
    <div class="ctrl-text">💬 <?= esc($m['other_text']) ?></div>
    <?php elseif ($m['justification']): ?>
    <div class="ctrl-text">✏️ <?= esc($m['justification']) ?></div>
    <?php else: ?>
    <div class="ctrl-text" style="color:#9ca3af;">Aucune réponse textuelle fournie.</div>
    <?php endif ?>
</div>
<?php endforeach; ?>
<?php endif; ?>

<!-- ═══════════════════════════════════════ RECOMMANDATIONS ═════════════════ -->
<?php
$conformeCount   = 0;
$partielCount    = 0;
$nonConfCount    = 0;
foreach ($domainBreakdown as $d) {
    $s = (float)$d['avg_score'];
    if ($s >= 75)      $conformeCount++;
    elseif ($s >= 50)  $partielCount++;
    elseif ((int)$d['answered'] > 0) $nonConfCount++;
}
?>
<div class="section-title" style="margin-top:22px;">
    <?= empty($manualItems) ? '3' : '4' ?>. Synthèse et recommandations
</div>
<table style="width:100%;border-collapse:collapse;margin-bottom:14px;">
    <tr>
        <td style="width:33%;padding:10px;border:1px solid #e5e7eb;border-radius:4px;text-align:center;">
            <div style="font-size:15pt;font-weight:bold;color:#059669;"><?= $conformeCount ?></div>
            <div style="font-size:8pt;color:#6b7280;">Domaines conformes (≥75%)</div>
        </td>
        <td style="width:4%;"></td>
        <td style="width:30%;padding:10px;border:1px solid #e5e7eb;border-radius:4px;text-align:center;">
            <div style="font-size:15pt;font-weight:bold;color:#d97706;"><?= $partielCount ?></div>
            <div style="font-size:8pt;color:#6b7280;">Domaines partiels (50–74%)</div>
        </td>
        <td style="width:4%;"></td>
        <td style="width:29%;padding:10px;border:1px solid #e5e7eb;border-radius:4px;text-align:center;">
            <div style="font-size:15pt;font-weight:bold;color:#dc2626;"><?= $nonConfCount ?></div>
            <div style="font-size:8pt;color:#6b7280;">Domaines insuffisants (&lt;50%)</div>
        </td>
    </tr>
</table>

<?php if ($score >= 75): ?>
<p style="font-size:9.5pt;color:#374151;line-height:1.7;padding:10px 14px;background:#d1fae5;border-radius:6px;border-left:4px solid #059669;">
    ✅ <strong>Bon niveau de conformité.</strong> L'organisation démontre une maîtrise globale du référentiel <?= esc($sv['standard_code']) ?>.
    Les points nécessitant une revue manuelle doivent être traités en priorité pour consolider la conformité.
</p>
<?php elseif ($score >= 50): ?>
<p style="font-size:9.5pt;color:#374151;line-height:1.7;padding:10px 14px;background:#fef3c7;border-radius:6px;border-left:4px solid #d97706;">
    ⚠️ <strong>Niveau de conformité partiel.</strong> Des lacunes significatives ont été identifiées.
    Il est recommandé de créer des plans d'action ciblés pour les domaines en dessous de 75% afin d'atteindre un niveau de conformité acceptable.
</p>
<?php else: ?>
<p style="font-size:9.5pt;color:#374151;line-height:1.7;padding:10px 14px;background:#fee2e2;border-radius:6px;border-left:4px solid #dc2626;">
    ❌ <strong>Niveau de conformité insuffisant.</strong> Des actions correctives urgentes sont nécessaires.
    Un plan d'amélioration global couvrant l'ensemble des domaines est fortement recommandé avant toute démarche de certification.
</p>
<?php endif; ?>

<p style="font-size:8pt;color:#9ca3af;margin-top:30px;text-align:center;border-top:1px solid #e5e7eb;padding-top:10px;">
    Rapport généré le <?= esc($generatedAt) ?> · CheckISO · Confidentiel
</p>

</body>
</html>

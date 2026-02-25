<?= $this->extend('layouts/app') ?>
<?= $this->section('content') ?>

<meta name="csrf-token" content="<?= csrf_hash() ?>">

<!-- ── Top Header ─────────────────────────────────────────────────────────── -->
<div class="d-flex align-items-start justify-content-between mb-3 flex-wrap gap-3">
    <div>
        <a href="<?= site_url('gap') ?>" class="text-muted text-decoration-none small">
            <i class="bi bi-arrow-left me-1"></i>Gap Analysis
        </a>
        <h1 class="h3 mb-0 fw-bold mt-1">
            <?= esc($version['standard_code']) ?>
            <span class="text-muted fw-normal fs-5"><?= esc($version['version_code']) ?></span>
        </h1>
        <p class="text-muted mb-0"><?= esc($version['standard_name']) ?></p>
    </div>
    <a href="<?= site_url('gap/' . $versionId . '/summary') ?>" class="btn btn-outline-primary">
        <i class="bi bi-bar-chart me-1"></i>Voir le résumé
    </a>
</div>

<!-- ── Sticky progress bar ───────────────────────────────────────────────── -->
<div class="sticky-top bg-white border-bottom shadow-sm py-2 px-3 mb-4" style="z-index:1010;top:0">
    <div class="d-flex align-items-center gap-3">
        <span class="text-muted small text-nowrap">Progression</span>
        <div class="progress flex-grow-1" style="height:10px">
            <div id="globalProgressBar" class="progress-bar bg-primary"
                 style="width:<?= $globalStats['progress'] ?>%"
                 title="<?= $globalStats['progress'] ?>% évalué"></div>
        </div>
        <span id="globalProgressPct" class="text-muted small text-nowrap fw-semibold">
            <?= $globalStats['progress'] ?>%
        </span>
        <span id="globalScoreBadge"
              class="badge <?= $globalStats['score'] !== null ? ($globalStats['score'] >= 70 ? 'bg-success' : ($globalStats['score'] >= 40 ? 'bg-warning text-dark' : 'bg-danger')) : 'bg-secondary' ?> text-nowrap">
            Score : <?= $globalStats['score'] !== null ? $globalStats['score'] . '%' : '—' ?>
        </span>
    </div>
</div>

<!-- ── Domain Accordion ──────────────────────────────────────────────────── -->
<div class="accordion" id="gapAccordion" data-version-id="<?= $versionId ?>">
    <?php foreach ($domains as $di => $domain):
        $clauses = $clausesByDomain[$domain['id']] ?? [];
        $ds = $domainStatsById[$domain['id']] ?? null;

        if ($ds && $ds['score'] !== null) {
            $dBadgeText  = $ds['score'] . '%';
            $dBadgeClass = $ds['score'] >= 70 ? 'bg-success' : ($ds['score'] >= 40 ? 'bg-warning text-dark' : 'bg-danger');
        } elseif ($ds && $ds['assessed_total'] > 0) {
            $dBadgeText  = $ds['progress'] . '% évalué';
            $dBadgeClass = 'bg-secondary';
        } else {
            $dBadgeText  = 'Non évalué';
            $dBadgeClass = 'bg-light text-muted border';
        }
    ?>
    <div class="accordion-item mb-2 border rounded shadow-sm">
        <h2 class="accordion-header">
            <button class="accordion-button <?= $di > 0 ? 'collapsed' : '' ?> fw-semibold"
                    type="button"
                    data-bs-toggle="collapse"
                    data-bs-target="#domain-<?= $domain['id'] ?>"
                    aria-expanded="<?= $di === 0 ? 'true' : 'false' ?>">
                <span class="badge bg-primary me-3"><?= esc($domain['code']) ?></span>
                <?= esc($domain['name']) ?>
                <span class="badge ms-auto me-3 <?= $dBadgeClass ?>"
                      data-domain-badge="<?= $domain['id'] ?>">
                    <?= $dBadgeText ?>
                </span>
            </button>
        </h2>
        <div id="domain-<?= $domain['id'] ?>"
             class="accordion-collapse collapse <?= $di === 0 ? 'show' : '' ?>">
            <div class="accordion-body p-0">

                <?php foreach ($clauses as $clause):
                    $controls = $controlsByClause[$clause['id']] ?? [];
                ?>
                <!-- Clause header -->
                <div class="px-4 py-2 bg-light border-bottom d-flex align-items-center gap-2">
                    <code class="text-secondary"><?= esc($clause['code']) ?></code>
                    <span class="fw-medium"><?= esc($clause['title']) ?></span>
                </div>

                <!-- Controls -->
                <?php foreach ($controls as $control):
                    $assess = $assessments[$control['id']] ?? null;
                    $currentStatus = $assess['status'] ?? '';
                    $currentNotes  = $assess['notes'] ?? '';

                    $statusStyle = match($currentStatus) {
                        'conforme'     => 'border-success text-success',
                        'partiel'      => 'border-warning text-warning',
                        'non_conforme' => 'border-danger text-danger',
                        'na'           => 'border-secondary text-secondary',
                        default        => '',
                    };
                ?>
                <div class="d-flex align-items-center px-4 py-2 border-bottom gap-3 control-row"
                     data-control-id="<?= $control['id'] ?>"
                     data-domain-id="<?= $domain['id'] ?>">
                    <!-- Code -->
                    <span class="badge bg-secondary bg-opacity-75 text-nowrap" style="min-width:56px;font-size:.72rem">
                        <?= esc($control['code']) ?>
                    </span>
                    <!-- Title -->
                    <span class="flex-grow-1 small"><?= esc($control['title']) ?></span>

                    <!-- Status select -->
                    <select class="status-select form-select form-select-sm <?= $statusStyle ?>"
                            style="width:160px;min-width:120px"
                            data-control-id="<?= $control['id'] ?>">
                        <option value="" <?= $currentStatus === '' ? 'selected' : '' ?>>— Évaluer —</option>
                        <option value="conforme"     <?= $currentStatus === 'conforme'     ? 'selected' : '' ?>>✅ Conforme</option>
                        <option value="partiel"      <?= $currentStatus === 'partiel'      ? 'selected' : '' ?>>⚠️ Partiel</option>
                        <option value="non_conforme" <?= $currentStatus === 'non_conforme' ? 'selected' : '' ?>>❌ Non-conforme</option>
                        <option value="na"           <?= $currentStatus === 'na'           ? 'selected' : '' ?>>○ N/A</option>
                    </select>

                    <!-- Hidden notes storage -->
                    <input type="hidden"
                           class="notes-store"
                           data-control-id="<?= $control['id'] ?>"
                           value="<?= esc($currentNotes) ?>">

                    <!-- Notes toggle -->
                    <button type="button"
                            class="btn btn-sm <?= $currentNotes !== '' ? 'btn-info' : 'btn-outline-secondary' ?> notes-btn flex-shrink-0"
                            data-control-id="<?= $control['id'] ?>"
                            data-control-code="<?= esc($control['code']) ?>"
                            title="Notes">
                        <i class="bi bi-sticky<?= $currentNotes !== '' ? '-fill' : '' ?>"></i>
                    </button>

                    <!-- Save indicator -->
                    <span class="save-indicator flex-shrink-0" data-indicator="<?= $control['id'] ?>" style="width:18px;text-align:center"></span>
                </div>
                <?php endforeach; ?>

                <?php endforeach; ?>

            </div>
        </div>
    </div>
    <?php endforeach; ?>
</div>

<!-- ── Notes Modal ───────────────────────────────────────────────────────── -->
<div class="modal fade" id="notesModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="notesModalLabel">Notes</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <textarea id="notesTextarea" class="form-control" rows="5"
                          placeholder="Observations, preuves, plan d'action..."></textarea>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                <button type="button" class="btn btn-primary" id="saveNotesBtn">
                    <i class="bi bi-floppy me-1"></i>Enregistrer
                </button>
            </div>
        </div>
    </div>
</div>

<!-- ── JavaScript ────────────────────────────────────────────────────────── -->
<script>
(function () {
    const versionId  = document.getElementById('gapAccordion').dataset.versionId;
    let   csrfToken  = document.querySelector('meta[name="csrf-token"]').content;
    let   currentCtrlId = null;

    // Status select colour classes
    const STATUS_STYLE = {
        conforme:     'border-success text-success',
        partiel:      'border-warning text-warning',
        non_conforme: 'border-danger  text-danger',
        na:           'border-secondary text-secondary',
    };

    function styleSelect(sel) {
        sel.className = 'status-select form-select form-select-sm';
        const cls = STATUS_STYLE[sel.value];
        if (cls) cls.split(/\s+/).forEach(c => sel.classList.add(c));
    }

    function indicator(id, state) {
        const el = document.querySelector(`[data-indicator="${id}"]`);
        if (!el) return;
        if      (state === 'saving') el.innerHTML = '<span class="spinner-border spinner-border-sm text-primary" style="width:.9rem;height:.9rem"></span>';
        else if (state === 'saved')  el.innerHTML = '<i class="bi bi-check-circle-fill text-success"></i>';
        else if (state === 'error')  el.innerHTML = '<i class="bi bi-exclamation-circle-fill text-danger"></i>';
        else                         el.innerHTML = '';
        if (state === 'saved') setTimeout(() => indicator(id, ''), 2000);
    }

    function updateGlobalUI(g) {
        document.getElementById('globalProgressBar').style.width = g.progress + '%';
        document.getElementById('globalProgressPct').textContent  = g.progress + '%';
        const badge   = document.getElementById('globalScoreBadge');
        badge.textContent = 'Score : ' + (g.score !== null ? g.score + '%' : '—');
        badge.className   = 'badge text-nowrap ' + (
            g.score === null ? 'bg-secondary' :
            g.score >= 70   ? 'bg-success'   :
            g.score >= 40   ? 'bg-warning text-dark' : 'bg-danger'
        );
    }

    function updateDomainUI(domainStats) {
        domainStats.forEach(ds => {
            const el = document.querySelector(`[data-domain-badge="${ds.domain_id}"]`);
            if (!el) return;
            if (ds.score !== null) {
                el.textContent = ds.score + '%';
                el.className   = 'badge ms-auto me-3 ' + (ds.score >= 70 ? 'bg-success' : ds.score >= 40 ? 'bg-warning text-dark' : 'bg-danger');
            } else if (ds.assessed_total > 0) {
                el.textContent = ds.progress + '% évalué';
                el.className   = 'badge ms-auto me-3 bg-secondary';
            } else {
                el.textContent = 'Non évalué';
                el.className   = 'badge ms-auto me-3 bg-light text-muted border';
            }
        });
    }

    async function saveControl(controlId, status, notes) {
        indicator(controlId, 'saving');
        try {
            const res  = await fetch(`/gap/${versionId}/control`, {
                method:  'POST',
                headers: {
                    'Content-Type':     'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN':     csrfToken,
                },
                body: JSON.stringify({ control_id: parseInt(controlId), status, notes }),
            });
            const data = await res.json();
            if (data.csrf_hash) {
                csrfToken = data.csrf_hash;
                document.querySelector('meta[name="csrf-token"]').content = csrfToken;
            }
            if (data.success) {
                indicator(controlId, 'saved');
                updateGlobalUI(data.global);
                if (data.domain_stats) updateDomainUI(data.domain_stats);
                // Update notes button style
                const btn = document.querySelector(`.notes-btn[data-control-id="${controlId}"]`);
                if (btn) {
                    const hasNotes = notes.trim() !== '';
                    btn.className = 'btn btn-sm flex-shrink-0 ' + (hasNotes ? 'btn-info' : 'btn-outline-secondary') + ' notes-btn';
                    btn.querySelector('i').className = 'bi bi-sticky' + (hasNotes ? '-fill' : '');
                }
            } else {
                indicator(controlId, 'error');
            }
        } catch (e) {
            indicator(controlId, 'error');
            console.error(e);
        }
    }

    // ── Status select events ──────────────────────────────────────────────
    document.querySelectorAll('.status-select').forEach(sel => {
        styleSelect(sel);
        sel.addEventListener('change', function () {
            styleSelect(this);
            const id    = this.dataset.controlId;
            const notes = document.querySelector(`.notes-store[data-control-id="${id}"]`).value;
            saveControl(id, this.value, notes);
        });
    });

    // ── Notes modal ───────────────────────────────────────────────────────
    const notesModal    = document.getElementById('notesModal');
    const notesTextarea = document.getElementById('notesTextarea');

    document.querySelectorAll('.notes-btn').forEach(btn => {
        btn.addEventListener('click', function () {
            currentCtrlId         = this.dataset.controlId;
            const stored          = document.querySelector(`.notes-store[data-control-id="${currentCtrlId}"]`);
            notesTextarea.value   = stored ? stored.value : '';
            document.getElementById('notesModalLabel').textContent =
                this.dataset.controlCode + ' — Notes';
            bootstrap.Modal.getOrCreateInstance(notesModal).show();
        });
    });

    document.getElementById('saveNotesBtn').addEventListener('click', function () {
        if (!currentCtrlId) return;
        const notes   = notesTextarea.value;
        const stored  = document.querySelector(`.notes-store[data-control-id="${currentCtrlId}"]`);
        if (stored) stored.value = notes;
        const sel = document.querySelector(`.status-select[data-control-id="${currentCtrlId}"]`);
        saveControl(currentCtrlId, sel ? sel.value : '', notes);
        bootstrap.Modal.getOrCreateInstance(notesModal).hide();
    });

})();
</script>

<?= $this->endSection() ?>

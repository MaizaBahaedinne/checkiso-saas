<?= $this->extend('layouts/app') ?>
<?= $this->section('content') ?>

<?php
$versionId  = $sv['id'];
$sessionId  = $gapSession['id'];
$answered   = (int)$gapSession['answered_controls'];
$total      = (int)$gapSession['total_controls'];
$score      = (float)$gapSession['score'];
$isLocked   = $gapSession['status'] === 'submitted';
$pct        = $total > 0 ? round($answered / $total * 100) : 0;
?>

<meta name="csrf-token" content="<?= csrf_hash() ?>">

<!-- ── Page header ───────────────────────────────────────────────────────── -->
<div class="d-flex align-items-start justify-content-between mb-3 flex-wrap gap-3">
    <div>
        <a href="<?= site_url('gap') ?>" class="text-muted text-decoration-none small">
            <i class="bi bi-arrow-left me-1"></i><?= lang('Gap.back_to_gap') ?>
        </a>
        <h1 class="h3 mb-0 fw-bold mt-1">
            <?= esc($sv['standard_code']) ?>
            <span class="text-muted fw-normal fs-5"><?= esc($sv['version_code']) ?></span>
        </h1>
        <p class="text-muted mb-0"><?= esc($sv['standard_name']) ?></p>
    </div>
    <a href="<?= site_url('gap/' . $versionId . '/summary') ?>" class="btn btn-outline-secondary btn-sm">
        <i class="bi bi-bar-chart me-1"></i><?= lang('Gap.resume_btn') ?>
    </a>
</div>

<?php if ($isLocked): ?>
<div class="alert alert-success d-flex align-items-center gap-2">
    <i class="bi bi-lock-fill fs-5"></i>
    <span><?= lang('Gap.locked_msg') ?></span>
</div>
<?php endif; ?>

<!-- ── Sticky progress bar ───────────────────────────────────────────────── -->
<div id="stickyBar" class="sticky-top bg-white border-bottom shadow-sm py-2 px-3 mb-4" style="z-index:1010;top:0">
    <div class="d-flex align-items-center gap-3">
        <span class="text-muted small text-nowrap"><?= lang('Gap.progression') ?></span>
        <div class="progress flex-grow-1" style="height:10px">
            <div id="globalProgressBar" class="progress-bar <?= $isLocked ? 'bg-success' : 'bg-primary' ?>"
                 style="width:<?= $pct ?>%"></div>
        </div>
        <span id="globalProgressText" class="text-muted small fw-semibold text-nowrap">
            <?= $answered ?> / <?= $total ?>
        </span>
        <?php if (! $isLocked): ?>
        <button id="submitBtn" type="button"
                class="btn btn-success btn-sm text-nowrap <?= $answered < $total ? 'disabled' : '' ?>">
            <i class="bi bi-send me-1"></i><?= lang('Gap.submit_btn') ?>
        </button>
        <?php endif; ?>
    </div>
</div>

<!-- ── Domain accordion ──────────────────────────────────────────────────── -->
<div class="accordion" id="gapAccordion">

<?php foreach ($domains as $di => $domain): ?>

    <div class="accordion-item mb-2 border rounded shadow-sm">
        <h2 class="accordion-header">
            <button class="accordion-button <?= $di > 0 ? 'collapsed' : '' ?> fw-semibold"
                    type="button"
                    data-bs-toggle="collapse"
                    data-bs-target="#domain-<?= $domain['id'] ?>"
                    aria-expanded="<?= $di === 0 ? 'true' : 'false' ?>">
                <span class="badge bg-primary me-3 font-monospace"><?= esc($domain['code']) ?></span>
                <?= esc($domain['display_name']) ?>
                <span class="ms-auto me-3 badge bg-secondary domain-progress-badge"
                      data-domain-id="<?= $domain['id'] ?>"></span>
            </button>
        </h2>

        <div id="domain-<?= $domain['id'] ?>" class="accordion-collapse collapse <?= $di === 0 ? 'show' : '' ?>">
            <div class="accordion-body p-0">

<?php foreach ($domain['clauses'] as $clause): ?>

                <!-- Clause header -->
                <div class="px-4 py-2 border-bottom bg-light d-flex align-items-center gap-2">
                    <code class="text-secondary"><?= esc($clause['code']) ?></code>
                    <span class="fw-medium small"><?= esc($clause['display_title']) ?></span>
                </div>

                <!-- Controls / quiz cards -->
<?php foreach ($clause['controls'] as $control):
    $cid      = (int)$control['id'];
    $qa       = $control['question'];          // null if no question seeded
    $ans      = $answers[$cid] ?? null;        // existing answer or null
    $savedKey = $ans ? $ans['choice_key'] : null;
    $cardBorder = '';
    if ($ans) {
        $cardBorder = match($ans['status']) {
            'conforme'     => 'border-success',
            'partiel'      => 'border-warning',
            'non_conforme' => 'border-danger',
            default        => '',
        };
    }
?>
                <div class="quiz-card p-4 border-bottom <?= $cardBorder ?>"
                     data-control-id="<?= $cid ?>"
                     data-domain-id="<?= $domain['id'] ?>">

                    <!-- Control title + code -->
                    <div class="d-flex align-items-center gap-2 mb-2">
                        <span class="badge bg-secondary font-monospace" style="font-size:.72rem"><?= esc($control['code']) ?></span>
                        <span class="fw-semibold"><?= esc($control['display_title']) ?></span>
                    </div>

<?php if ($qa): ?>
                    <!-- Question text -->
                    <p class="mb-1 text-body"><?= esc($qa['question']) ?></p>
                    <?php if ($qa['hint']): ?>
                    <p class="text-muted small mb-3"><i class="bi bi-lightbulb me-1"></i><?= esc($qa['hint']) ?></p>
                    <?php else: ?>
                    <div class="mb-3"></div>
                    <?php endif; ?>

                    <!-- Choices -->
                    <div class="d-flex flex-column gap-2">
<?php foreach ($qa['choices'] as $ch): ?>
                        <?php
                        $isSelected = $savedKey === $ch['choice_key'];
                        $choiceClass = 'quiz-choice btn btn-outline-secondary text-start w-100 position-relative';
                        if ($isSelected) {
                            $choiceClass = 'quiz-choice btn text-start w-100 position-relative ';
                            $choiceClass .= match($ans['status']) {
                                'conforme'     => 'btn-success',
                                'partiel'      => 'btn-warning',
                                'non_conforme' => 'btn-danger',
                                default        => 'btn-secondary',
                            };
                        }
                        if ($ch['is_manual_review']) {
                            $choiceClass .= $isSelected ? '' : ' text-muted fst-italic';
                        }
                        ?>
                        <button type="button"
                                class="<?= $choiceClass ?>"
                                data-control-id="<?= $cid ?>"
                                data-choice-id="<?= $ch['id'] ?>"
                                data-choice-key="<?= $ch['choice_key'] ?>"
                                data-requires-justification="<?= $ch['requires_justification'] ?>"
                                data-is-manual-review="<?= $ch['is_manual_review'] ?>"
                                <?= $isLocked ? 'disabled' : '' ?>>
                            <span class="badge bg-white text-dark border me-2 font-monospace"><?= strtoupper($ch['choice_key']) ?></span>
                            <?= esc($ch['label']) ?>
                        </button>
<?php endforeach; ?>
                    </div>

                    <!-- Justification area (shown when needed) -->
                    <div class="justification-area mt-3" style="display:none">
                        <label class="form-label small fw-semibold text-danger">
                            <i class="bi bi-pencil me-1"></i>Justification requise
                        </label>
                        <textarea class="justification-input form-control form-control-sm"
                                  rows="2"
                                  placeholder="Expliquez brièvement votre situation..."
                                  <?= $isLocked ? 'readonly' : '' ?>><?= esc($ans ? $ans['justification'] : '') ?></textarea>
                    </div>

                    <!-- "Autre" free-text area -->
                    <div class="other-text-area mt-3" style="display:none">
                        <label class="form-label small fw-semibold text-info">
                            <i class="bi bi-chat-text me-1"></i>Décrivez votre situation pour évaluation manuelle
                        </label>
                        <textarea class="other-text-input form-control form-control-sm"
                                  rows="3"
                                  placeholder="Décrivez en détail votre approche actuelle..."
                                  <?= $isLocked ? 'readonly' : '' ?>><?= esc($ans ? $ans['other_text'] : '') ?></textarea>
                    </div>

                    <!-- Save button (appears after choice) -->
                    <?php if (! $isLocked): ?>
                    <div class="save-row mt-3 align-items-center gap-2" style="display:none">
                        <button type="button"
                                class="save-answer-btn btn btn-primary btn-sm"
                                data-control-id="<?= $cid ?>">
                            <i class="bi bi-floppy me-1"></i><?= lang('Gap.save_btn') ?>
                        </button>
                        <span class="save-indicator text-muted small"></span>
                    </div>
                    <?php endif; ?>

                    <!-- Show already-saved justification/other_text in read mode -->
                    <?php if ($ans && $ans['is_manual_review'] && $ans['other_text']): ?>
                    <div class="mt-2 small text-info border border-info-subtle rounded p-2">
                        <i class="bi bi-info-circle me-1"></i><strong><?= lang('Gap.manual_eval_display') ?></strong>
                        <?= nl2br(esc($ans['other_text'])) ?>
                    </div>
                    <?php elseif ($ans && $ans['justification']): ?>
                    <div class="mt-2 small text-muted border rounded p-2">
                        <i class="bi bi-chat-left-text me-1"></i><em><?= nl2br(esc($ans['justification'])) ?></em>
                    </div>
                    <?php endif; ?>

<?php else: ?>
                    <p class="text-muted fst-italic small"><?= lang('Gap.question_unavailable') ?></p>
<?php endif; ?>

                </div><!-- /.quiz-card -->
<?php endforeach; // controls ?>
<?php endforeach; // clauses ?>

            </div>
        </div>
    </div><!-- /.accordion-item -->

<?php endforeach; // domains ?>

</div><!-- /#gapAccordion -->

<!-- ── Submit confirmation modal ─────────────────────────────────────────── -->
<div class="modal fade" id="submitModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header border-0">
                <h5 class="modal-title fw-bold"><?= lang('Gap.submit_modal_title') ?></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p><?= lang('Gap.submit_modal_warning') ?></p>
                <p class="mb-0"><?= lang('Gap.submit_modal_answered') ?> : <strong><span id="modalAnswered"><?= $answered ?></span> / <?= $total ?></strong>.</p>
            </div>
            <div class="modal-footer border-0">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><?= lang('Gap.cancel_btn') ?></button>
                <button type="button" id="confirmSubmitBtn" class="btn btn-success">
                    <i class="bi bi-send me-1"></i><?= lang('Gap.confirm_submit_btn') ?>
                </button>
            </div>
        </div>
    </div>
</div>

<!-- ── JavaScript ────────────────────────────────────────────────────────── -->
<script>
(function () {
    const CSRF_NAME = '<?= csrf_token() ?>';
    let   csrfHash  = '<?= csrf_hash() ?>';
    const VERSION_ID = <?= (int)$versionId ?>;

    // ── Track current global progress ──────────────────────────────────────
    let gAnswered = <?= $answered ?>;
    let gTotal    = <?= $total ?>;

    function updateStickyBar(answered, total) {
        gAnswered = answered; gTotal = total;
        const pct = total > 0 ? Math.round(answered / total * 100) : 0;
        document.getElementById('globalProgressBar').style.width = pct + '%';
        document.getElementById('globalProgressText').textContent  = answered + ' / ' + total;
        const submitBtn = document.getElementById('submitBtn');
        if (submitBtn) {
            if (answered >= total) submitBtn.classList.remove('disabled');
            else                   submitBtn.classList.add('disabled');
        }
    }

    // ── Refresh domain progress badges ────────────────────────────────────
    function refreshDomainBadge(domainId) {
        const cards  = document.querySelectorAll(`.quiz-card[data-domain-id="${domainId}"]`);
        let answered = 0;
        cards.forEach(c => {
            const active = c.querySelector('.quiz-choice.btn-success, .quiz-choice.btn-warning, .quiz-choice.btn-danger');
            if (active) answered++;
        });
        const badge = document.querySelector(`.domain-progress-badge[data-domain-id="${domainId}"]`);
        if (!badge) return;
        if (answered === 0) {
            badge.textContent = '';
            badge.className   = 'ms-auto me-3 badge bg-secondary domain-progress-badge';
        } else {
            const allDone = answered === cards.length;
            badge.textContent = answered + '/' + cards.length;
            badge.className   = 'ms-auto me-3 badge domain-progress-badge ' +
                (allDone ? 'bg-success' : 'bg-primary');
        }
    }

    // ── Choice click ──────────────────────────────────────────────────────
    document.querySelectorAll('.quiz-choice').forEach(btn => {
        btn.addEventListener('click', function () {
            const card    = this.closest('.quiz-card');
            const cid     = this.dataset.controlId;
            const reqJus  = this.dataset.requiresJustification === '1';
            const isMR    = this.dataset.isManualReview === '1';

            // Deselect siblings
            card.querySelectorAll('.quiz-choice').forEach(b => {
                b.className = b.className
                    .replace(/btn-(success|warning|danger|info|primary)/g, 'btn-outline-secondary')
                    .replace(/\s+/g, ' ').trim();
            });

            // Highlight selection (we don't know score yet — will get from AJAX)
            this.classList.remove('btn-outline-secondary');
            this.classList.add('btn-secondary'); // temporary

            // Store choice id on the card
            card.dataset.pendingChoiceId = this.dataset.choiceId;

            // Show/hide justification areas
            const jusArea   = card.querySelector('.justification-area');
            const otherArea = card.querySelector('.other-text-area');
            if (jusArea)   jusArea.style.display   = reqJus && !isMR ? 'block' : 'none';
            if (otherArea) otherArea.style.display  = isMR ? 'block' : 'none';

            // Show save row
            const saveRow = card.querySelector('.save-row');
            if (saveRow) saveRow.style.display = 'flex';
        });
    });

    // ── Save button ───────────────────────────────────────────────────────
    document.querySelectorAll('.save-answer-btn').forEach(btn => {
        btn.addEventListener('click', async function () {
            const card      = this.closest('.quiz-card');
            const cid       = parseInt(this.dataset.controlId);
            const choiceId  = parseInt(card.dataset.pendingChoiceId || 0);
            if (!choiceId) return;

            const jusArea   = card.querySelector('.justification-area');
            const otherArea = card.querySelector('.other-text-area');
            const justif    = jusArea   ? card.querySelector('.justification-input').value  : '';
            const otherTxt  = otherArea ? card.querySelector('.other-text-input').value     : '';

            const indicator = card.querySelector('.save-indicator');
            const saveBtn   = this;
            saveBtn.disabled = true;
            if (indicator) indicator.innerHTML = '<span class="spinner-border spinner-border-sm"></span>';

            const body = new URLSearchParams({
                [CSRF_NAME]: csrfHash,
                control_id:   cid,
                choice_id:    choiceId,
                justification: justif,
                other_text:   otherTxt,
            });

            try {
                const res  = await fetch(`/gap/${VERSION_ID}/answer`, {
                    method:  'POST',
                    headers: { 'X-Requested-With': 'XMLHttpRequest' },
                    body,
                });
                const data = await res.json();

                if (data.csrf_hash) {
                    csrfHash = data.csrf_hash;
                }

                if (data.ok) {
                    // Update CSRF
                    document.querySelector('meta[name="csrf-token"]').content = csrfHash;

                    // Restyle the selected button with correct colour
                    const statusColour = {
                        conforme:     'btn-success',
                        partiel:      'btn-warning',
                        non_conforme: 'btn-danger',
                    };
                    const chosen = card.querySelector(`.quiz-choice[data-choice-id="${choiceId}"]`);
                    if (chosen) {
                        chosen.classList.remove('btn-secondary', 'btn-outline-secondary');
                        chosen.classList.add(statusColour[data.answer.status] || 'btn-secondary');
                        chosen.dataset.scorePct = data.answer.score_pct;
                    }

                    // Update card border
                    card.classList.remove('border-success', 'border-warning', 'border-danger');
                    if (data.answer.status === 'conforme')     card.classList.add('border-success');
                    else if (data.answer.status === 'partiel') card.classList.add('border-warning');
                    else                                       card.classList.add('border-danger');

                    // Global progress
                    updateStickyBar(data.answered, data.total);
                    refreshDomainBadge(card.dataset.domainId);

                    if (indicator) indicator.innerHTML = '<i class="bi bi-check-circle-fill text-success"></i>';
                    setTimeout(() => { if (indicator) indicator.innerHTML = ''; }, 2500);

                    // If complete, offer to submit
                    if (data.is_complete) {
                        const el = document.getElementById('modalAnswered');
                        if (el) el.textContent = data.answered;
                        bootstrap.Modal.getOrCreateInstance(document.getElementById('submitModal')).show();
                    }

                    // Hide save row if not manual review / justif
                    const saveRow = card.querySelector('.save-row');
                    if (saveRow && !data.answer.is_manual_review) saveRow.style.display = 'none';

                } else {
                    if (indicator) indicator.innerHTML = '<i class="bi bi-exclamation-circle-fill text-danger"></i> ' + (data.error || 'Erreur');
                }
            } catch (e) {
                if (indicator) indicator.innerHTML = '<i class="bi bi-exclamation-circle-fill text-danger"></i>';
                console.error(e);
            } finally {
                saveBtn.disabled = false;
            }
        });
    });

    // ── Submit flow ───────────────────────────────────────────────────────
    const submitBtn = document.getElementById('submitBtn');
    if (submitBtn) {
        submitBtn.addEventListener('click', () => {
            bootstrap.Modal.getOrCreateInstance(document.getElementById('submitModal')).show();
        });
    }

    document.getElementById('confirmSubmitBtn').addEventListener('click', async function () {
        this.disabled = true;
        this.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span><?= lang('Gap.submitting') ?>';

        const body = new URLSearchParams({ [CSRF_NAME]: csrfHash });
        try {
            const res  = await fetch(`/gap/${VERSION_ID}/submit`, {
                method:  'POST',
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
                body,
            });
            const data = await res.json();
            if (data.ok && data.redirect_to) {
                window.location.href = data.redirect_to;
            } else {
                alert(data.message || '<?= lang('Gap.submit_error') ?>');
                this.disabled = false;
                this.innerHTML = '<i class="bi bi-send me-1"></i><?= lang('Gap.confirm_submit_btn') ?>';
            }
        } catch (e) {
            alert('<?= lang('Gap.network_error') ?>');
            this.disabled = false;
        }
    });

    // ── Restore already-answered choices on load ──────────────────────────
    // Highlight previously selected choices using PHP-rendered class (already done server-side)
    // Re-run domain badges init
    document.querySelectorAll('.quiz-card').forEach(card => {
        const domainId = card.dataset.domainId;
        // Store score_pct on buttons that are already selected
        const selected = card.querySelector('.quiz-choice.btn-success, .quiz-choice.btn-warning, .quiz-choice.btn-danger');
        if (selected) {
            // Show justification/other_text if saved
            const jusArea   = card.querySelector('.justification-area');
            const otherArea = card.querySelector('.other-text-area');
            const jusText   = card.querySelector('.justification-input');
            const otherText = card.querySelector('.other-text-input');
            if (jusArea   && jusText   && jusText.value.trim())   jusArea.style.display   = 'block';
            if (otherArea && otherText && otherText.value.trim()) otherArea.style.display  = 'block';
        }
    });

    // Init domain badges
    new Set([...document.querySelectorAll('.quiz-card')].map(c => c.dataset.domainId))
        .forEach(refreshDomainBadge);

})();
</script>

<?= $this->endSection() ?>
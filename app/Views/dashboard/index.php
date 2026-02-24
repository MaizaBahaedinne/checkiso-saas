<?= $this->extend('layouts/app') ?>
<?= $this->section('content') ?>

<h4 class="fw-semibold mb-1">Welcome back, <?= esc(session()->get('user_name')) ?>!</h4>
<p class="text-muted small mb-4">Tenant #<?= esc(session()->get('tenant_id')) ?> &mdash; Membership #<?= esc(session()->get('membership_id')) ?></p>

<div class="row g-3">
    <div class="col-md-4">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <p class="text-uppercase text-muted small fw-semibold mb-1">Standards</p>
                <h2 class="fw-bold text-primary mb-0">0</h2>
                <p class="text-muted small mt-1 mb-0">No standards configured yet.</p>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <p class="text-uppercase text-muted small fw-semibold mb-1">Controls</p>
                <h2 class="fw-bold text-primary mb-0">0</h2>
                <p class="text-muted small mt-1 mb-0">No controls assigned yet.</p>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <p class="text-uppercase text-muted small fw-semibold mb-1">Team members</p>
                <h2 class="fw-bold text-primary mb-0">1</h2>
                <p class="text-muted small mt-1 mb-0">Just you for now.</p>
            </div>
        </div>
    </div>
</div>

<?= $this->endSection() ?>

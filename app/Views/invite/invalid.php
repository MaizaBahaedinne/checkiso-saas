<?= $this->extend('layouts/auth') ?>
<?= $this->section('content') ?>

<div class="card shadow-sm border-0 text-center">
    <div class="card-body p-5">
        <div style="font-size:3rem;">❌</div>
        <h5 class="fw-semibold mt-3 mb-2">Invalid or expired invitation</h5>
        <p class="text-muted small mb-4">
            This invitation link is no longer valid.<br>
            It may have already been used, cancelled, or expired (links are valid 7 days).
        </p>
        <a href="<?= site_url('login') ?>" class="btn btn-outline-primary btn-sm">Go to login</a>
    </div>
</div>

<?= $this->endSection() ?>

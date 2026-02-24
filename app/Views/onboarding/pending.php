<?= $this->extend('layouts/auth') ?>
<?= $this->section('content') ?>

<div class="card shadow-sm border-0 text-center">
    <div class="card-body p-5">
        <div class="mb-3" style="font-size:3rem;">⏳</div>
        <h5 class="fw-semibold mb-2">Request sent!</h5>
        <p class="text-muted small mb-4">
            Your request to join the organisation is pending approval by an admin.<br>
            You'll get access as soon as it's reviewed.
        </p>
        <a href="/logout" class="btn btn-outline-secondary btn-sm">Log out</a>
    </div>
</div>

<?= $this->endSection() ?>

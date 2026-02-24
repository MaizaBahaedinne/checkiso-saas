<?= $this->extend('layouts/auth') ?>
<?= $this->section('content') ?>

<div class="card shadow-sm border-0">
    <div class="card-body p-4">
        <div class="text-center mb-4">
            <div style="font-size:2.5rem;">👋</div>
            <h5 class="fw-semibold mt-2 mb-1">You've been invited!</h5>
            <p class="text-muted small mb-0">
                Join <strong><?= esc($tenant['name']) ?></strong> on CheckISO.
            </p>
        </div>

        <?php if ($errors = session()->getFlashdata('errors')): ?>
            <div class="alert alert-danger py-2">
                <ul class="mb-0 ps-3"><?php foreach ($errors as $e): ?><li><?= esc($e) ?></li><?php endforeach ?></ul>
            </div>
        <?php endif ?>
        <?php if ($error = session()->getFlashdata('error')): ?>
            <div class="alert alert-danger py-2"><?= esc($error) ?></div>
        <?php endif ?>

        <?php if (session()->get('user_id')): ?>
            <!-- Already logged in — one-click accept -->
            <p class="text-center text-muted small mb-3">
                Logged in as <strong><?= esc(session()->get('user_email')) ?></strong>.
            </p>
            <form method="post" action="<?= site_url('invite/' . $inv['token']) ?>">
                <?= csrf_field() ?>
                <button type="submit" class="btn btn-primary w-100">
                    Join <?= esc($tenant['name']) ?>
                </button>
            </form>
            <p class="text-center mt-3 small text-muted">
                Wrong account? <a href="<?= site_url('logout') ?>">Log out</a>
            </p>
        <?php else: ?>
            <!-- New user — fill in name + password -->
            <p class="text-muted small mb-3">
                Create your account to join <strong><?= esc($tenant['name']) ?></strong>.
                Your email address will be <strong><?= esc($inv['email']) ?></strong>.
            </p>
            <form method="post" action="<?= site_url('invite/' . $inv['token']) ?>">
                <?= csrf_field() ?>

                <div class="row g-3 mb-3">
                    <div class="col-6">
                        <label class="form-label fw-medium">First name</label>
                        <input type="text" name="first_name" class="form-control"
                               value="<?= esc(old('first_name')) ?>" required autofocus>
                    </div>
                    <div class="col-6">
                        <label class="form-label fw-medium">Last name</label>
                        <input type="text" name="last_name" class="form-control"
                               value="<?= esc(old('last_name')) ?>" required>
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label fw-medium">Password <span class="text-muted fw-normal small">(min. 8 chars)</span></label>
                    <input type="password" name="password" class="form-control" required>
                </div>

                <div class="mb-4">
                    <label class="form-label fw-medium">Confirm password</label>
                    <input type="password" name="password_confirm" class="form-control" required>
                </div>

                <button type="submit" class="btn btn-primary w-100">
                    Create account &amp; join <?= esc($tenant['name']) ?>
                </button>
            </form>
            <p class="text-center mt-3 small text-muted">
                Already have an account? <a href="<?= site_url('login') ?>">Sign in</a>
                — then click the invitation link again.
            </p>
        <?php endif ?>
    </div>
</div>

<?= $this->endSection() ?>

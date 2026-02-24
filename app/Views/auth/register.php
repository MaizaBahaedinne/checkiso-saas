<?= $this->extend('layouts/auth') ?>
<?= $this->section('content') ?>

<div class="card shadow-sm border-0">
    <div class="card-body p-4">
        <h5 class="card-title mb-4">Create your account</h5>

        <?php if ($error = session()->getFlashdata('error')): ?>
            <div class="alert alert-danger py-2"><?= esc($error) ?></div>
        <?php endif ?>

        <?php if ($errors = session()->getFlashdata('errors')): ?>
            <div class="alert alert-danger py-2">
                <ul class="mb-0 ps-3">
                    <?php foreach ($errors as $e): ?>
                        <li><?= esc($e) ?></li>
                    <?php endforeach ?>
                </ul>
            </div>
        <?php endif ?>

        <form method="post" action="/register">
            <?= csrf_field() ?>

            <div class="mb-3">
                <label class="form-label fw-medium">Full name</label>
                <input type="text" name="display_name" class="form-control"
                       value="<?= esc(old('display_name')) ?>" required autofocus>
            </div>

            <div class="mb-3">
                <label class="form-label fw-medium">Email address</label>
                <input type="email" name="email" class="form-control"
                       value="<?= esc(old('email')) ?>" required>
            </div>

            <div class="mb-3">
                <label class="form-label fw-medium">Password <span class="text-muted fw-normal small">(min. 8 chars)</span></label>
                <input type="password" name="password" class="form-control" required>
            </div>

            <div class="mb-3">
                <label class="form-label fw-medium">Confirm password</label>
                <input type="password" name="password_confirm" class="form-control" required>
            </div>

            <hr class="my-3">

            <div class="mb-4">
                <label class="form-label fw-medium">Organisation name</label>
                <input type="text" name="tenant_name" class="form-control"
                       value="<?= esc(old('tenant_name')) ?>" required>
                <div class="form-text">Your company or team workspace on CheckISO.</div>
            </div>

            <button type="submit" class="btn btn-primary w-100">Create account</button>
        </form>
    </div>
</div>

<p class="text-center mt-3 text-muted small">
    Already have an account? <a href="/login">Sign in</a>
</p>

<?= $this->endSection() ?>

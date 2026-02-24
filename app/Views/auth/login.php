<?= $this->extend('layouts/auth') ?>
<?= $this->section('content') ?>

<div class="card shadow-sm border-0">
    <div class="card-body p-4">
        <h5 class="card-title mb-4">Sign in to your account</h5>

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

        <form method="post" action="/login">
            <?= csrf_field() ?>

            <div class="mb-3">
                <label class="form-label fw-medium">Email address</label>
                <input type="email" name="email" class="form-control"
                       value="<?= esc(old('email')) ?>" required autofocus>
            </div>

            <div class="mb-4">
                <label class="form-label fw-medium">Password</label>
                <input type="password" name="password" class="form-control" required>
            </div>

            <button type="submit" class="btn btn-primary w-100">Sign in</button>
        </form>
    </div>
</div>

<p class="text-center mt-3 text-muted small">
    No account yet? <a href="/register">Create one</a>
</p>

<?= $this->endSection() ?>

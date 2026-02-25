<?= $this->extend('layouts/auth') ?>
<?= $this->section('content') ?>

<div class="card shadow-sm border-0">
    <div class="card-body p-4">
        <h5 class="card-title mb-4"><?= lang('Auth.register_title') ?></h5>

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

            <div class="row g-3 mb-3">
                <div class="col-6">
                    <label class="form-label fw-medium"><?= lang('Auth.first_name') ?></label>
                    <input type="text" name="first_name" class="form-control"
                           value="<?= esc(old('first_name')) ?>" required autofocus>
                </div>
                <div class="col-6">
                    <label class="form-label fw-medium"><?= lang('Auth.last_name') ?></label>
                    <input type="text" name="last_name" class="form-control"
                           value="<?= esc(old('last_name')) ?>" required>
                </div>
            </div>

            <div class="mb-3">
                <label class="form-label fw-medium"><?= lang('Auth.email') ?></label>
                <input type="email" name="email" class="form-control"
                       value="<?= esc(old('email')) ?>" required>
            </div>

            <div class="mb-3">
                <label class="form-label fw-medium"><?= lang('Auth.password') ?> <span class="text-muted fw-normal small"><?= lang('Auth.password_hint') ?></span></label>
                <input type="password" name="password" class="form-control" required>
            </div>

            <div class="mb-4">
                <label class="form-label fw-medium"><?= lang('Auth.confirm_password') ?></label>
                <input type="password" name="password_confirm" class="form-control" required>
            </div>

            <button type="submit" class="btn btn-primary w-100"><?= lang('Auth.create_btn') ?></button>
        </form>
    </div>
</div>

<p class="text-center mt-3 text-muted small">
    <?= lang('Auth.have_account') ?> <a href="/login"><?= lang('Auth.sign_in_link') ?></a>
</p>

<?= $this->endSection() ?>

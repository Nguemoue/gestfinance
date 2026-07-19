<?php $title = __('login_title') . ' - GestFinance'; ?>

<div style="display: flex; justify-content: center; align-items: center; min-height: 70vh;">
    <div class="card" style="width: 100%; max-width: 400px; border: none; box-shadow: 0 4px 12px rgba(0,0,0,0.1);">
        <h2 style="margin-top: 0; color: var(--md-sys-color-primary); margin-bottom: 32px; text-align: center;">
            <img src="/logo.png" alt="logo" width="120" height="100">
            <br />
            <?= __('login_title') ?>
        </h2>

        <form action="/login" method="POST">
            <input type="hidden" name="csrf_token" value="<?= \App\Middleware\CsrfMiddleware::generateToken() ?>">

            <div class="form-group">
                <label for="email"><?= __('admin_email') ?></label>
                <input type="email" id="email" name="email" class="form-control" placeholder="nom@domaine.com" required>
            </div>

            <div class="form-group">
                <label for="password"><?= __('password') ?></label>
                <input type="password" id="password" name="password" class="form-control" placeholder="••••••••"
                    required>
            </div>

            <button type="submit" class="btn btn-filled"
                style="width: 100%; margin-top: 16px; height: 48px; font-size: 16px;">
                <?= __('login_btn') ?>
            </button>
        </form>
    </div>
</div>
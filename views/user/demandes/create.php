<?php $title = __('new_request'); ?>

<div class="card" style="max-width: 900px; margin: 0 auto; border-radius: 24px; padding: 40px;">
    <div style="text-align: center; margin-bottom: 40px;">
        <div style="display: inline-flex; background: var(--md-sys-color-primary-container); color: var(--md-sys-color-on-primary-container); padding: 16px; border-radius: 20px; margin-bottom: 16px;">
            <span class="material-symbols-outlined" style="font-size: 40px;">edit_document</span>
        </div>
        <h1 style="margin: 0; font-size: 28px;"><?= __('new_request_title') ?></h1>
        <p style="color: var(--md-sys-color-outline); margin-top: 8px;"><?= __('fill_form') ?></p>
    </div>

    <form action="/demandes/create" method="POST">
        <input type="hidden" name="csrf_token" value="<?= \App\Middleware\CsrfMiddleware::generateToken() ?>">

        <!-- Section Demandeur (Lecture seule) -->
        <div class="grid-1-1" style="background: #F8F9FA; padding: 24px; border-radius: 16px; margin-bottom: 32px; gap: 24px; border: 1px dashed var(--md-sys-color-outline);">
            <div class="form-group" style="margin-bottom: 0;">
                <label><?= __('requester') ?></label>
                <div style="font-weight: 700; font-size: 16px; color: var(--md-sys-color-on-surface);"><?= htmlspecialchars($_SESSION['user_name']) ?></div>
            </div>
            <div class="form-group" style="margin-bottom: 0;">
                <label><?= __('current_service') ?></label>
                <div style="font-weight: 700; font-size: 16px; color: var(--md-sys-color-on-surface);"><?= htmlspecialchars($currentUserDetails['service_nom'] ?? 'Aucun') ?></div>
            </div>
        </div>

        <div class="form-group">
            <label for="service_id"><?= __('beneficiary_service') ?></label>
            <select name="service_id" id="service_id" class="form-control" required>
                <?php foreach ($services as $service): ?>
                    <option value="<?= $service['id'] ?>" <?= $service['id'] == ($_SESSION['service_id'] ?? '') ? 'selected' : '' ?>>
                        <?= htmlspecialchars($service['libelle']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <small style="display: block; margin-top: 4px; color: #777;"><?= __('select_service_help') ?></small>
        </div>

        <div class="form-group">
            <label for="fonction"><?= __('exact_function') ?></label>
            <input type="text" name="fonction" id="fonction" class="form-control" placeholder="<?= __('function_placeholder') ?>" value="<?= htmlspecialchars($currentUserDetails['role_nom'] ?? '') ?>" required>
        </div>
        
        <div class="form-group">
            <label for="objet"><?= __('expense_object') ?></label>
            <textarea name="objet" id="objet" class="form-control" rows="4" placeholder="<?= __('expense_placeholder') ?>" required></textarea>
        </div>

        <div class="form-group">
            <label for="montant"><?= __('estimated_amount_ttc') ?></label>
            <div style="position: relative;">
                <input type="number" name="montant" id="montant" class="form-control" step="0.01" placeholder="0.00" required style="padding-right: 80px; font-size: 20px; font-weight: 700; color: var(--md-sys-color-primary);">
                <span style="position: absolute; right: 16px; top: 50%; transform: translateY(-50%); color: var(--md-sys-color-outline); font-weight: 700; font-size: 14px;">FCFA</span>
            </div>
        </div>

        <div class="flex gap-16 mt-24" style="border-top: 1px solid var(--md-sys-color-surface-variant); padding-top: 32px;">
            <button type="submit" name="submit_action" value="soumettre" class="btn btn-filled" style="padding: 12px 32px; height: 48px;">
                <span class="material-symbols-outlined">send</span>
                <?= __('submit_to_director') ?>
            </button>
            <button type="submit" name="submit_action" value="brouillon" class="btn btn-outlined" style="padding: 12px 24px; height: 48px;">
                <span class="material-symbols-outlined">draft</span>
                <?= __('save_draft') ?>
            </button>
            <a href="/demandes" class="btn btn-text" style="height: 48px;"><?= __('cancel') ?></a>
        </div>
    </form>
</div>

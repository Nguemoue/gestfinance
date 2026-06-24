<?php $title = __('my_requests'); ?>

<div class="flex-between" style="margin-bottom: 32px;">
    <div style="display: flex; align-items: center; gap: 16px;">
        <div
            style="background: var(--md-sys-color-primary-container); color: var(--md-sys-color-on-primary-container); padding: 12px; border-radius: 12px;">
            <span class="material-symbols-outlined" style="font-size: 32px;">account_balance_wallet</span>
        </div>
        <div>
            <h1 style="margin: 0; font-size: 24px;"><?= __('my_requests_title') ?></h1>
            <p style="margin: 0; font-size: 14px; color: var(--md-sys-color-outline);"><?= __('manage_requests') ?></p>
        </div>
    </div>
    <a href="/demandes/create" class="btn btn-filled">
        <span class="material-symbols-outlined">add</span>
        <?= __('new_request') ?>
    </a>
</div>

<div class="card" style="padding: 0; border-radius: 20px;">
    <table class="data-table">
        <thead style="background: #F1F4F9;">
            <tr>
                <th style="padding-left: 24px;"><?= __('date') ?></th>
                <th><?= __('request_object') ?></th>
                <th><?= __('estimated_amount') ?></th>
                <th><?= __('current_status') ?></th>
                <th style="text-align: right; padding-right: 24px;"><?= __('actions') ?></th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($demandes)): ?>
                <tr>
                    <td colspan="5" style="text-align: center; padding: 64px; color: var(--md-sys-color-outline);">
                        <span class="material-symbols-outlined"
                            style="font-size: 64px; display: block; margin-bottom: 16px; opacity: 0.2;">folder_off</span>
                        <div style="font-size: 18px; font-weight: 500;"><?= __('no_request') ?></div>
                        <p style="margin-top: 8px;"><?= __('start_request') ?></p>
                        <a href="/demandes/create" class="btn btn-outlined"
                            style="margin-top: 24px;"><?= __('create_request') ?></a>
                    </td>
                </tr>
            <?php endif; ?>
            <?php foreach ($demandes ?? [] as $demande): ?>
                <?php $status = \App\Enums\StatutDemande::from($demande['statut']); ?>
                <tr style="transition: background 0.2s;">
                    <td style="padding-left: 24px;">
                        <div style="font-weight: 700; font-size: 15px;">
                            <?= date('d M', strtotime($demande['created_at'])) ?></div>
                        <div style="font-size: 12px; color: var(--md-sys-color-outline);">
                            <?= date('Y', strtotime($demande['created_at'])) ?></div>
                    </td>
                    <td style="max-width: 350px;">
                        <div style="font-weight: 500; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;"
                            title="<?= htmlspecialchars($demande['objet']) ?>">
                            <?= htmlspecialchars($demande['objet']) ?>
                        </div>
                        <div style="font-size: 12px; color: var(--md-sys-color-outline);">
                            <?= htmlspecialchars($demande['fonction']) ?></div>
                    </td>
                    <td>
                        <div style="font-weight: 700; color: var(--md-sys-color-primary); font-size: 16px;">
                            <?= number_format((float) $demande['montant'], 2, ',', ' ') ?>
                            <small style="font-size: 10px; vertical-align: middle;">FCFA</small>
                        </div>
                    </td>
                    <td>
                        <span
                            style="display: inline-flex; align-items: center; gap: 6px; padding: 6px 14px; border-radius: 100px; font-size: 12px; font-weight: 700; background-color: <?= $status->color() ?>; color: white;">
                            <span class="material-symbols-outlined" style="font-size: 14px;">fiber_manual_record</span>
                            <?= $status->label() ?>
                        </span>
                        <?php if (!empty($demande['is_justified'])): ?>
                            <span
                                style="display: inline-flex; align-items: center; gap: 4px; padding: 4px 10px; border-radius: 100px; font-size: 11px; font-weight: 700; background-color: #00695C; color: white; margin-top: 4px;">
                                <span class="material-symbols-outlined" style="font-size: 12px;">verified</span>
                                Justifiée
                            </span>
                        <?php endif; ?>
                    </td>
                    <td style="text-align: right; padding-right: 24px;">
                        <div class="flex" style="justify-content: flex-end; gap: 8px;">
                            <a href="/demandes/<?= $demande['id'] ?>" class="btn btn-text"
                                style="padding: 8px; min-width: 40px;" title="<?= __('details') ?>">
                                <span class="material-symbols-outlined">visibility</span>
                            </a>
                            <?php if ($demande['statut'] === 'mis_a_disposition'): ?>
                                <a href="/demandes/<?= $demande['id'] ?>/pdf" target="_blank" class="btn btn-text"
                                    style="padding: 8px; min-width: 40px; color: var(--md-sys-color-error);"
                                    title="<?= __('download_pdf') ?>">
                                    <span class="material-symbols-outlined">picture_as_pdf</span>
                                </a>
                            <?php endif; ?>
                        </div>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
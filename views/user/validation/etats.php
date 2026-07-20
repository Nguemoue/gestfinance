<?php $title = __('etats'); ?>

<div class="card" style="margin-bottom: 24px;">
    <div style="display: flex; align-items: center; gap: 12px;">
        <span class="material-symbols-outlined" style="color: var(--md-sys-color-primary); font-size: 32px;">assessment</span>
        <div>
            <h2 style="margin: 0; font-size: 22px; font-weight: 700; color: var(--md-sys-color-on-surface);"><?= __('etats') ?></h2>
            <p style="margin: 4px 0 0 0; font-size: 14px; color: var(--md-sys-color-outline);">
                Liste de toutes les demandes de financement que vous avez mises à disposition.
            </p>
        </div>
    </div>
</div>

<?php if (empty($demandes)): ?>
    <div class="card" style="text-align: center; padding: 48px; color: var(--md-sys-color-on-surface-variant);">
        <span class="material-symbols-outlined" style="font-size: 48px; display: block; margin-bottom: 12px; opacity: 0.3;">
            folder_open
        </span>
        Aucun état ou demande mise à disposition pour le moment.
    </div>
<?php else: ?>
    <div class="card" style="padding: 0; border-radius: 20px; overflow: hidden;">
        <div style="overflow-x: auto;">
            <table class="data-table">
                <thead style="background: #F1F4F9;">
                    <tr>
                        <th style="padding-left: 24px;"><?= __('disposal_date') ?></th>
                        <th><?= __('ref_number') ?></th>
                        <th><?= __('beneficiary') ?></th>
                        <th><?= __('request_object') ?></th>
                        <th><?= __('estimated_amount') ?></th>
                        <th><?= __('justification_status') ?></th>
                        <th style="text-align: right; padding-right: 24px;"><?= __('actions') ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($demandes as $d): ?>
                        <tr style="transition: background 0.2s;" onmouseover="this.style.backgroundColor='rgba(0, 97, 164, 0.04)'" onmouseout="this.style.backgroundColor='transparent'">
                            <td style="padding-left: 24px;">
                                <div style="font-weight: 700; font-size: 14px; color: var(--md-sys-color-on-surface);">
                                    <?= date('d/m/Y', strtotime($d['date_mise_a_disposition'])) ?>
                                </div>
                                <div style="font-size: 12px; color: var(--md-sys-color-outline);">
                                    <?= date('H:i', strtotime($d['date_mise_a_disposition'])) ?>
                                </div>
                            </td>
                            <td>
                                <span style="font-family: monospace; font-weight: 700; font-size: 14px; background: var(--md-sys-color-secondary-container); color: var(--md-sys-color-on-secondary-container); padding: 4px 8px; border-radius: 6px;">
                                    BF-<?= date('Y', strtotime($d['created_at'])) ?>-<?= str_pad((string)$d['id'], 4, '0', STR_PAD_LEFT) ?>
                                </span>
                            </td>
                            <td>
                                <div style="font-weight: 500;"><?= htmlspecialchars($d['prenom'] . ' ' . $d['nom']) ?></div>
                            </td>
                            <td style="max-width: 250px;">
                                <div style="white-space: nowrap; overflow: hidden; text-overflow: ellipsis;" title="<?= htmlspecialchars($d['objet']) ?>">
                                    <?= htmlspecialchars($d['objet']) ?>
                                </div>
                            </td>
                            <td>
                                <div style="font-weight: 700; color: var(--md-sys-color-primary); font-size: 15px;">
                                    <?= number_format((float) $d['montant'], 2, ',', ' ') ?>
                                    <small style="font-size: 10px;">FCFA</small>
                                </div>
                            </td>
                            <td>
                                <?php if ($d['is_justified']): ?>
                                    <span style="display: inline-flex; align-items: center; gap: 4px; background: #E8F5E9; color: #2E7D32; padding: 6px 12px; border-radius: 100px; font-size: 12px; font-weight: 500;">
                                        <span class="material-symbols-outlined" style="font-size: 16px;">check_circle</span>
                                        <?= __('justified') ?>
                                    </span>
                                <?php else: ?>
                                    <span style="display: inline-flex; align-items: center; gap: 4px; background: #FFF3E0; color: #E65100; padding: 6px 12px; border-radius: 100px; font-size: 12px; font-weight: 500;">
                                        <span class="material-symbols-outlined" style="font-size: 16px;">pending</span>
                                        <?= __('not_justified') ?>
                                    </span>
                                <?php endif; ?>
                            </td>
                            <td style="text-align: right; padding-right: 24px; white-space: nowrap;">
                                <?php if ($d['is_justified']): ?>
                                    <form action="/validations/<?= $d['id'] ?>/rollback" method="POST" style="display: inline;">
                                        <input type="hidden" name="csrf_token" value="<?= \App\Middleware\CsrfMiddleware::generateToken() ?>">
                                        <button type="submit" class="btn btn-outlined" style="padding: 4px 12px; font-size: 13px; height: 32px; border-radius: 8px; color: var(--md-sys-color-error); border-color: var(--md-sys-color-error); background: transparent;" title="Annuler la justification" onclick="return confirm('Annuler la justification de cette demande ?')">
                                            <span class="material-symbols-outlined" style="font-size: 16px; vertical-align: middle;">undo</span>
                                            Annuler
                                        </button>
                                    </form>
                                <?php else: ?>
                                    <form action="/validations/<?= $d['id'] ?>/justify" method="POST" style="display: inline;">
                                        <input type="hidden" name="csrf_token" value="<?= \App\Middleware\CsrfMiddleware::generateToken() ?>">
                                        <button type="submit" class="btn btn-filled" style="background: #00695C; padding: 4px 12px; font-size: 13px; height: 32px; border-radius: 8px; color: white;" title="Marquer comme justifiée" onclick="return confirm('Confirmer la justification de cette demande ?')">
                                            <span class="material-symbols-outlined" style="font-size: 16px; vertical-align: middle;">verified</span>
                                            Justifier
                                        </button>
                                    </form>
                                <?php endif; ?>

                                <a href="/demandes/<?= $d['id'] ?>" class="btn btn-text" style="padding: 8px; min-width: 40px;" title="<?= __('details') ?>">
                                    <span class="material-symbols-outlined" style="font-size: 20px;">visibility</span>
                                </a>
                                <a href="/demandes/<?= $d['id'] ?>/pdf" class="btn btn-text btn-outlined" style="padding: 4px 12px; font-size: 13px; height: 32px; border-radius: 8px; color: var(--md-sys-color-primary); border-color: var(--md-sys-color-primary);" title="<?= __('download_pdf') ?>" target="_blank">
                                    <span class="material-symbols-outlined" style="font-size: 16px;">download</span>
                                    PDF
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
<?php endif; ?>

<?php $title = __('pending_validations_title'); ?>

<?php
use App\Core\AuthHelper;
$isRA = AuthHelper::isRA();
?>

<div style="margin-bottom: 24px; border-bottom: 1px solid var(--md-sys-color-surface-variant); display: flex; gap: 8px; overflow-x: auto;">
    <button class="btn btn-text active-tab" id="tab-pending" onclick="showTab('pending')"
        style="border-radius: 0; border-bottom: 2px solid var(--md-sys-color-primary); color: var(--md-sys-color-primary); font-weight: 700;">
        <?= __('pending_validations_title') ?>
    </button>
    <?php if ($isRA): ?>
    <button class="btn btn-text" id="tab-a-justifier" onclick="showTab('a-justifier')"
        style="border-radius: 0; color: var(--md-sys-color-outline);">
        À justifier
        <?php if (!empty($demandesAJustifier)): ?>
            <span style="background: var(--md-sys-color-error); color: white; border-radius: 100px; padding: 2px 8px; font-size: 11px; margin-left: 4px;">
                <?= count($demandesAJustifier) ?>
            </span>
        <?php endif; ?>
    </button>
    <button class="btn btn-text" id="tab-justifiees" onclick="showTab('justifiees')"
        style="border-radius: 0; color: var(--md-sys-color-outline);">
        Justifiées
    </button>
    <?php endif; ?>
    <button class="btn btn-text" id="tab-history" onclick="showTab('history')"
        style="border-radius: 0; color: var(--md-sys-color-outline);">
        Mes validations
    </button>
    <button class="btn btn-text" id="tab-rejected" onclick="showTab('rejected')"
        style="border-radius: 0; color: var(--md-sys-color-outline);">
        Mes rejets
    </button>
</div>

<!-- TAB: En attente -->
<div id="content-pending">
    <?php if (empty($demandes)): ?>
        <div class="card" style="text-align: center; padding: 48px; color: var(--md-sys-color-on-surface-variant);">
            <span class="material-symbols-outlined"
                style="font-size: 48px; display: block; margin-bottom: 12px; opacity: 0.3;">fact_check</span>
            <?= __('no_pending_validation') ?>
        </div>
    <?php else: ?>
        <div style="display: flex; flex-direction: column; gap: 24px;">
            <?php foreach ($demandes as $demande): ?>
                <div class="card" style="margin-bottom: 0;">
                    <div
                        style="display: flex; justify-content: space-between; align-items: flex-start; gap: 32px; flex-wrap: wrap;">
                        <div style="flex: 1; min-width: 300px;">
                            <div style="display: flex; align-items: center; gap: 12px; margin-bottom: 16px;">
                                <span class="material-symbols-outlined"
                                    style="color: var(--md-sys-color-primary); font-size: 32px;">account_circle</span>
                                <div>
                                    <h3 style="margin: 0; font-size: 18px;">
                                        <?= htmlspecialchars($demande['prenom'] . ' ' . $demande['nom']) ?></h3>
                                    <span style="font-size: 13px; color: var(--md-sys-color-outline);"><?= __('received_on') ?>
                                        <?= date('d/m/Y H:i', strtotime($demande['created_at'])) ?></span>
                                </div>
                            </div>

                            <p style="margin-bottom: 8px;"><strong><?= __('object_label') ?></strong>
                                <?= htmlspecialchars($demande['objet']) ?></p>
                            <p style="font-size: 20px; color: var(--md-sys-color-primary); margin-top: 12px;">
                                <strong><?= __('amount_label') ?></strong> <span
                                    style="font-weight: 700;"><?= number_format((float) $demande['montant'], 2, ',', ' ') ?>
                                    FCFA</span>
                            </p>
                        </div>

                        <div style="width: 1px; background: var(--md-sys-color-surface-variant); align-self: stretch;"></div>

                        <form action="/validations/<?= $demande['id'] ?>/approve" method="POST"
                            style="flex: 1; min-width: 300px;">
                            <input type="hidden" name="csrf_token"
                                value="<?= \App\Middleware\CsrfMiddleware::generateToken() ?>">

                            <div class="form-group">
                                <label for="commentaire_<?= $demande['id'] ?>"><?= __('decision_comment') ?></label>
                                <textarea id="commentaire_<?= $demande['id'] ?>" name="commentaire" class="form-control"
                                    rows="3" placeholder="<?= __('decision_placeholder') ?>" required> </textarea>
                            </div>

                            <div class="flex gap-16">
                                <button type="submit" class="btn btn-filled" style="flex: 2;">
                                    <span class="material-symbols-outlined">check_circle</span>
                                    <?= $isRA ? __('put_at_disposal') : __('approve_request') ?>
                                </button>
                                <button type="submit" formaction="/validations/<?= $demande['id'] ?>/reject"
                                    class="btn btn-outlined btn-danger"
                                    style="flex: 1; border-color: var(--md-sys-color-error);">
                                    <span class="material-symbols-outlined">cancel</span>
                                    <?= __('reject_request') ?>
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<?php if ($isRA): ?>
<!-- TAB: À justifier (mis_a_disposition, is_justified = 0) -->
<div id="content-a-justifier" style="display: none;">
    <?php if (empty($demandesAJustifier)): ?>
        <div class="card" style="text-align: center; padding: 48px; color: var(--md-sys-color-on-surface-variant);">
            <span class="material-symbols-outlined"
                style="font-size: 48px; display: block; margin-bottom: 12px; opacity: 0.3;">task_alt</span>
            Aucune demande en attente de justification.
        </div>
    <?php else: ?>
        <div class="card" style="padding: 0; border-radius: 20px;">
            <table class="data-table">
                <thead style="background: #F1F4F9;">
                    <tr>
                        <th style="padding-left: 24px;"><?= __('date') ?></th>
                        <th><?= __('requester_upper') ?></th>
                        <th><?= __('request_object') ?></th>
                        <th><?= __('estimated_amount') ?></th>
                        <th>JUSTIFIER</th>
                        <th style="text-align: right; padding-right: 24px;"><?= __('actions') ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($demandesAJustifier as $d): ?>
                        <tr style="transition: background 0.2s;">
                            <td style="padding-left: 24px;">
                                <div style="font-weight: 700; font-size: 15px;"><?= date('d M', strtotime($d['created_at'])) ?></div>
                                <div style="font-size: 12px; color: var(--md-sys-color-outline);"><?= date('Y', strtotime($d['created_at'])) ?></div>
                            </td>
                            <td>
                                <div style="font-weight: 500;"><?= htmlspecialchars($d['prenom'] . ' ' . $d['nom']) ?></div>
                            </td>
                            <td style="max-width: 250px;">
                                <div style="white-space: nowrap; overflow: hidden; text-overflow: ellipsis;"
                                    title="<?= htmlspecialchars($d['objet']) ?>">
                                    <?= htmlspecialchars($d['objet']) ?>
                                </div>
                            </td>
                            <td>
                                <div style="font-weight: 700; color: var(--md-sys-color-primary); font-size: 16px;">
                                    <?= number_format((float) $d['montant'], 2, ',', ' ') ?>
                                    <small style="font-size: 10px;">FCFA</small>
                                </div>
                            </td>
                            <td>
                                <form action="/validations/<?= $d['id'] ?>/justify" method="POST" style="display: inline;">
                                    <input type="hidden" name="csrf_token" value="<?= \App\Middleware\CsrfMiddleware::generateToken() ?>">
                                    <button type="submit" class="btn btn-filled" style="background: #00695C; padding: 6px 16px; font-size: 13px;"
                                        title="Marquer comme justifiée"
                                        onclick="return confirm('Confirmer la justification de cette demande ?')">
                                        <span class="material-symbols-outlined" style="font-size: 16px;">verified</span>
                                        Justifier
                                    </button>
                                </form>
                            </td>
                            <td style="text-align: right; padding-right: 24px;">
                                <a href="/demandes/<?= $d['id'] ?>" class="btn btn-text" style="padding: 8px; min-width: 40px;"
                                    title="<?= __('details') ?>">
                                    <span class="material-symbols-outlined">visibility</span>
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<!-- TAB: Justifiées (mis_a_disposition, is_justified = 1) -->
<div id="content-justifiees" style="display: none;">
    <?php if (empty($demandesJustifiees)): ?>
        <div class="card" style="text-align: center; padding: 48px; color: var(--md-sys-color-on-surface-variant);">
            <span class="material-symbols-outlined"
                style="font-size: 48px; display: block; margin-bottom: 12px; opacity: 0.3;">checklist</span>
            Aucune demande justifiée pour le moment.
        </div>
    <?php else: ?>
        <div class="card" style="padding: 0; border-radius: 20px;">
            <table class="data-table">
                <thead style="background: #E8F5E9;">
                    <tr>
                        <th style="padding-left: 24px;"><?= __('date') ?></th>
                        <th><?= __('requester_upper') ?></th>
                        <th><?= __('request_object') ?></th>
                        <th><?= __('estimated_amount') ?></th>
                        <th>STATUT</th>
                        <th>ANNULER JUSTIF.</th>
                        <th style="text-align: right; padding-right: 24px;"><?= __('actions') ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($demandesJustifiees as $d): ?>
                        <tr style="transition: background 0.2s; background: #F1FFF3;">
                            <td style="padding-left: 24px;">
                                <div style="font-weight: 700; font-size: 15px;"><?= date('d M', strtotime($d['created_at'])) ?></div>
                                <div style="font-size: 12px; color: var(--md-sys-color-outline);"><?= date('Y', strtotime($d['created_at'])) ?></div>
                            </td>
                            <td>
                                <div style="font-weight: 500;"><?= htmlspecialchars($d['prenom'] . ' ' . $d['nom']) ?></div>
                            </td>
                            <td style="max-width: 250px;">
                                <div style="white-space: nowrap; overflow: hidden; text-overflow: ellipsis;"
                                    title="<?= htmlspecialchars($d['objet']) ?>">
                                    <?= htmlspecialchars($d['objet']) ?>
                                </div>
                            </td>
                            <td>
                                <div style="font-weight: 700; color: var(--md-sys-color-primary); font-size: 16px;">
                                    <?= number_format((float) $d['montant'], 2, ',', ' ') ?>
                                    <small style="font-size: 10px;">FCFA</small>
                                </div>
                            </td>
                            <td>
                                <span style="display: inline-flex; align-items: center; gap: 6px; padding: 6px 14px; border-radius: 100px; font-size: 12px; font-weight: 700; background-color: #00695C; color: white;">
                                    <span class="material-symbols-outlined" style="font-size: 14px;">verified</span>
                                    Justifiée
                                </span>
                            </td>
                            <td>
                                <form action="/validations/<?= $d['id'] ?>/rollback" method="POST" style="display: inline;">
                                    <input type="hidden" name="csrf_token" value="<?= \App\Middleware\CsrfMiddleware::generateToken() ?>">
                                    <button type="submit" class="btn btn-outlined btn-danger"
                                        style="border-color: var(--md-sys-color-error); padding: 6px 14px; font-size: 13px;"
                                        title="Annuler la justification"
                                        onclick="return confirm('Annuler la justification de cette demande ?')">
                                        <span class="material-symbols-outlined" style="font-size: 16px;">undo</span>
                                        Annuler
                                    </button>
                                </form>
                            </td>
                            <td style="text-align: right; padding-right: 24px;">
                                <a href="/demandes/<?= $d['id'] ?>" class="btn btn-text" style="padding: 8px; min-width: 40px;"
                                    title="<?= __('details') ?>">
                                    <span class="material-symbols-outlined">visibility</span>
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>
<?php endif; ?>

<!-- TAB: Mes validations passées -->
<div id="content-history" style="display: none;">
    <?php if (empty($demandesPassees)): ?>
        <div class="card" style="text-align: center; padding: 48px; color: var(--md-sys-color-on-surface-variant);">
            <span class="material-symbols-outlined"
                style="font-size: 48px; display: block; margin-bottom: 12px; opacity: 0.3;">history</span>
            Aucun historique de validation.
        </div>
    <?php else: ?>
        <div class="card" style="padding: 0; border-radius: 20px;">
            <table class="data-table">
                <thead style="background: #F1F4F9;">
                    <tr>
                        <th style="padding-left: 24px;"><?= __('date') ?></th>
                        <th><?= __('requester_upper') ?></th>
                        <th><?= __('request_object') ?></th>
                        <th><?= __('estimated_amount') ?></th>
                        <th><?= __('current_status') ?></th>
                        <th style="text-align: right; padding-right: 24px;"><?= __('actions') ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($demandesPassees as $dp): ?>
                        <?php $status = \App\Enums\StatutDemande::from($dp['statut']); ?>
                        <tr style="transition: background 0.2s;">
                            <td style="padding-left: 24px;">
                                <div style="font-weight: 700; font-size: 15px;"><?= date('d M', strtotime($dp['created_at'])) ?>
                                </div>
                                <div style="font-size: 12px; color: var(--md-sys-color-outline);">
                                    <?= date('Y', strtotime($dp['created_at'])) ?></div>
                            </td>
                            <td>
                                <div style="font-weight: 500;"><?= htmlspecialchars($dp['prenom'] . ' ' . $dp['nom']) ?></div>
                            </td>
                            <td style="max-width: 250px;">
                                <div style="white-space: nowrap; overflow: hidden; text-overflow: ellipsis;"
                                    title="<?= htmlspecialchars($dp['objet']) ?>">
                                    <?= htmlspecialchars($dp['objet']) ?>
                                </div>
                            </td>
                            <td>
                                <div style="font-weight: 700; color: var(--md-sys-color-primary); font-size: 16px;">
                                    <?= number_format((float) $dp['montant'], 2, ',', ' ') ?>
                                    <small style="font-size: 10px;">FCFA</small>
                                </div>
                            </td>
                            <td>
                                <span
                                    style="display: inline-flex; align-items: center; gap: 6px; padding: 6px 14px; border-radius: 100px; font-size: 12px; font-weight: 700; background-color: <?= $status->color() ?>; color: white;">
                                    <span class="material-symbols-outlined" style="font-size: 14px;">fiber_manual_record</span>
                                    <?= $status->label() ?>
                                </span>
                            </td>
                            <td style="text-align: right; padding-right: 24px;">
                                <a href="/demandes/<?= $dp['id'] ?>" class="btn btn-text" style="padding: 8px; min-width: 40px;"
                                    title="<?= __('details') ?>">
                                    <span class="material-symbols-outlined">visibility</span>
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<!-- TAB: Mes rejets -->
<div id="content-rejected" style="display: none;">
    <?php if (empty($demandesRejetees)): ?>
        <div class="card" style="text-align: center; padding: 48px; color: var(--md-sys-color-on-surface-variant);">
            <span class="material-symbols-outlined"
                style="font-size: 48px; display: block; margin-bottom: 12px; opacity: 0.3;">block</span>
            Aucune demande rejetée.
        </div>
    <?php else: ?>
        <div class="card" style="padding: 0; border-radius: 20px;">
            <table class="data-table">
                <thead style="background: #F1F4F9;">
                    <tr>
                        <th style="padding-left: 24px;"><?= __('date') ?></th>
                        <th><?= __('requester_upper') ?></th>
                        <th><?= __('request_object') ?></th>
                        <th><?= __('estimated_amount') ?></th>
                        <th><?= __('current_status') ?></th>
                        <th style="text-align: right; padding-right: 24px;"><?= __('actions') ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($demandesRejetees as $dp): ?>
                        <?php $status = \App\Enums\StatutDemande::from($dp['statut']); ?>
                        <tr style="transition: background 0.2s;">
                            <td style="padding-left: 24px;">
                                <div style="font-weight: 700; font-size: 15px;"><?= date('d M', strtotime($dp['created_at'])) ?>
                                </div>
                                <div style="font-size: 12px; color: var(--md-sys-color-outline);">
                                    <?= date('Y', strtotime($dp['created_at'])) ?></div>
                            </td>
                            <td>
                                <div style="font-weight: 500;"><?= htmlspecialchars($dp['prenom'] . ' ' . $dp['nom']) ?></div>
                            </td>
                            <td style="max-width: 250px;">
                                <div style="white-space: nowrap; overflow: hidden; text-overflow: ellipsis;"
                                    title="<?= htmlspecialchars($dp['objet']) ?>">
                                    <?= htmlspecialchars($dp['objet']) ?>
                                </div>
                            </td>
                            <td>
                                <div style="font-weight: 700; color: var(--md-sys-color-primary); font-size: 16px;">
                                    <?= number_format((float) $dp['montant'], 2, ',', ' ') ?>
                                    <small style="font-size: 10px;">FCFA</small>
                                </div>
                            </td>
                            <td>
                                <span
                                    style="display: inline-flex; align-items: center; gap: 6px; padding: 6px 14px; border-radius: 100px; font-size: 12px; font-weight: 700; background-color: <?= $status->color() ?>; color: white;">
                                    <span class="material-symbols-outlined" style="font-size: 14px;">fiber_manual_record</span>
                                    <?= $status->label() ?>
                                </span>
                            </td>
                            <td style="text-align: right; padding-right: 24px;">
                                <a href="/demandes/<?= $dp['id'] ?>" class="btn btn-text" style="padding: 8px; min-width: 40px;"
                                    title="<?= __('details') ?>">
                                    <span class="material-symbols-outlined">visibility</span>
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<script>
    function showTab(tab) {
        const tabs = ['pending', 'a-justifier', 'justifiees', 'history', 'rejected'];
        tabs.forEach(t => {
            const content = document.getElementById('content-' + t);
            const btn = document.getElementById('tab-' + t);
            if (content) content.style.display = (t === tab) ? 'block' : 'none';
            if (btn) {
                btn.style.borderBottom = 'none';
                btn.style.color = 'var(--md-sys-color-outline)';
                btn.style.fontWeight = 'normal';
            }
        });

        const activeBtn = document.getElementById('tab-' + tab);
        if (activeBtn) {
            activeBtn.style.borderBottom = '2px solid var(--md-sys-color-primary)';
            activeBtn.style.color = 'var(--md-sys-color-primary)';
            activeBtn.style.fontWeight = '700';
        }
    }
</script>
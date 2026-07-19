<?php $title = __('request_details_title'); 
/** @var array $demande @var array $validations **/ 
?>

<div class="grid-2-1" style="gap: 32px; align-items: start;">
    <div class="card">
        <div class="flex-between" style="margin-bottom: 24px; border-bottom: 1px solid var(--md-sys-color-surface-variant); padding-bottom: 16px;">
            <h2 style="font-size: 22px; color: var(--md-sys-color-primary); margin: 0;"><?= __('request_info') ?></h2>
            <span style="font-size: 14px; color: var(--md-sys-color-outline);"><?= __('ref') ?> BF-<?= date('Y') ?>-<?= str_pad((string)$demande['id'], 4, '0', STR_PAD_LEFT) ?></span>
        </div>

        <div class="grid-1-1" style="gap: 24px; margin-bottom: 32px;">
            <div>
                <label style="font-size: 12px; color: var(--md-sys-color-outline); text-transform: uppercase;"><?= __('requester_upper') ?></label>
                <div style="font-weight: 500; font-size: 16px;"><?= htmlspecialchars($demande['prenom'] . ' ' . $demande['nom']) ?></div>
            </div>
            <div>
                <label style="font-size: 12px; color: var(--md-sys-color-outline); text-transform: uppercase;"><?= __('service_upper') ?></label>
                <div style="font-weight: 500; font-size: 16px;"><?= htmlspecialchars($demande['service_nom']) ?></div>
            </div>
            <div>
                <label style="font-size: 12px; color: var(--md-sys-color-outline); text-transform: uppercase;"><?= __('function_upper') ?></label>
                <div style="font-weight: 500; font-size: 16px;"><?= htmlspecialchars($demande['fonction']) ?></div>
            </div>
            <div>
                <label style="font-size: 12px; color: var(--md-sys-color-outline); text-transform: uppercase;"><?= __('creation_date') ?></label>
                <div style="font-weight: 500; font-size: 16px;"><?= date('d/m/Y H:i', strtotime($demande['created_at'])) ?></div>
            </div>
        </div>

        <div style="margin-bottom: 32px;">
            <label style="font-size: 12px; color: var(--md-sys-color-outline); text-transform: uppercase;"><?= __('request_object_upper') ?></label>
            <div style="padding: 20px; background: var(--md-sys-color-surface); border-radius: 12px; border: 1px solid var(--md-sys-color-surface-variant); margin-top: 8px; line-height: 1.6;">
                <?= nl2br(htmlspecialchars($demande['objet'])) ?>
            </div>
        </div>

        <div style="background: var(--md-sys-color-primary-container); color: var(--md-sys-color-on-primary-container); padding: 24px; border-radius: 16px; display: flex; justify-content: space-between; align-items: center;">
            <span style="font-weight: 500; font-size: 18px;"><?= __('total_amount') ?></span>
            <span style="font-size: 28px; font-weight: 700;"><?= number_format((float)$demande['montant'], 2, ',', ' ') ?> FCFA</span>
        </div>
    </div>

    <div class="card">
        <h2 style="font-size: 18px; margin-top: 0; margin-bottom: 24px; border-bottom: 1px solid var(--md-sys-color-surface-variant); padding-bottom: 12px;"><?= __('validation_path') ?></h2>
        
        <?php if (empty($validations)): ?>
            <div style="text-align: center; padding: 24px; color: var(--md-sys-color-outline);">
                <span class="material-symbols-outlined" style="font-size: 32px; display: block; margin-bottom: 8px; opacity: 0.5;">hourglass_empty</span>
                <?= __('waiting_processing') ?>
            </div>
        <?php else: ?>
            <div style="display: flex; flex-direction: column; gap: 20px;">
                <?php foreach ($validations as $v): ?>
                    <div style="position: relative; padding-left: 20px; border-left: 2px solid var(--md-sys-color-primary);">
                        <div style="position: absolute; left: -7px; top: 0; width: 12px; height: 12px; border-radius: 50%; background: var(--md-sys-color-primary);"></div>
                        <div style="font-weight: 700; font-size: 12px; color: var(--md-sys-color-primary); text-transform: uppercase;">
                            <?php $etapeEnum = \App\Enums\EtapeValidation::tryFrom($v['etape']); ?>
                            <?= $etapeEnum ? htmlspecialchars($etapeEnum->label()) : ucfirst(htmlspecialchars($v['etape'])) ?>
                        </div>
                        <div style="font-weight: 500; margin: 4px 0;"><?= htmlspecialchars($v['prenom'] . ' ' . $v['nom']) ?></div>
                        <div style="font-size: 11px; color: var(--md-sys-color-outline);"><?= date('d/m/Y H:i', strtotime($v['created_at'])) ?></div>
                        <div style="margin-top: 8px; font-size: 13px; font-style: italic; background: #F5F5F5; padding: 8px; border-radius: 8px;">"<?= htmlspecialchars($v['commentaire']) ?>"</div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
        
        <div style="margin-top: 32px; padding-top: 24px; border-top: 1px solid var(--md-sys-color-surface-variant);">
            <label style="font-size: 12px; color: var(--md-sys-color-outline); text-transform: uppercase; display: block; margin-bottom: 12px;"><?= __('current_status_upper') ?></label>
            <?php $status = \App\Enums\StatutDemande::from($demande['statut']); ?>
            <div style="display: flex; align-items: center; gap: 12px; padding: 12px 16px; border-radius: 12px; background-color: <?= $status->color() ?>; color: white;">
                <span class="material-symbols-outlined">info</span>
                <span style="font-weight: 700; font-size: 15px;"><?= $status->label() ?></span>
            </div>
            <?php if (!empty($demande['is_justified'])): ?>
            <div style="display: flex; align-items: center; gap: 8px; padding: 10px 16px; border-radius: 12px; background-color: #00695C; color: white; margin-top: 8px;">
                <span class="material-symbols-outlined">verified</span>
                <span style="font-weight: 700; font-size: 14px;">Justifiée par le Responsable Administratif</span>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<div class="flex gap-16 mt-24">
    <a href="/demandes" class="btn btn-outlined">
        <span class="material-symbols-outlined">arrow_back</span>
        <?= __('back_to_list') ?>
    </a>
    <?php if ($demande['statut'] === 'mis_a_disposition'): ?>
        <a href="/demandes/<?= $demande['id'] ?>/pdf" target="_blank" class="btn btn-filled">
            <span class="material-symbols-outlined">picture_as_pdf</span>
            <?= __('print_sheet') ?>
        </a>
    <?php endif; ?>
</div>
